<?php

namespace App\Core;

use Exception;
use App\Core\Security;

class FileValidator
{
    protected $errors = [];
    protected $mimeTypes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'mp4' => ['video/mp4'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation']
    ];
    
    protected $magicBytes = [
        'pdf' => ['25504446'],  // %PDF
        'jpg' => ['FFD8FF'],    // JPEG
        'jpeg' => ['FFD8FF'],   // JPEG
        'png' => ['89504E47'],  // PNG
        'zip' => ['504B0304', '504B0506', '504B0708'], // ZIP
        'doc' => ['D0CF11E0'], // MS Office
        'docx' => ['504B0304'], // DOCX (ZIP-based)
        'pptx' => ['504B0304'], // PPTX (ZIP-based)
        'mp4' => ['66747970'] // MP4
    ];
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file, $config)
    {
        $this->errors = [];
        
        // Basic file upload validation
        if (!$this->validateUploadErrors($file)) {
            return false;
        }
        
        // Empty file check
        if (!$this->validateFileSize($file, $config)) {
            return false;
        }
        
        // File type validation
        if (!$this->validateFileType($file, $config)) {
            return false;
        }
        
        // MIME type validation
        if (!$this->validateMimeType($file, $config)) {
            return false;
        }
        
        // File signature validation (magic bytes)
        if (!$this->validateFileSignature($file)) {
            return false;
        }
        
        // Filename validation
        if (!$this->validateFilename($file)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file content after upload
     */
    public function validateContent($filePath, $config)
    {
        $this->errors = [];
        
        // Additional security checks
        if (!$this->scanForMaliciousContent($filePath)) {
            return false;
        }
        
        // Image-specific validation
        if ($this->isImageFile($filePath)) {
            if (!$this->validateImageContent($filePath, $config)) {
                return false;
            }
        }
        
        // Document-specific validation
        if ($this->isDocumentFile($filePath)) {
            if (!$this->validateDocumentContent($filePath, $config)) {
                return false;
            }
        }
        
        // Archive-specific validation
        if ($this->isArchiveFile($filePath)) {
            if (!$this->validateArchiveContent($filePath, $config)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate upload errors
     */
    protected function validateUploadErrors($file)
    {
        if (!isset($file['error'])) {
            $this->errors[] = 'File upload error information missing';
            return false;
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
                $this->errors[] = 'File exceeds server upload limit';
                return false;
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = 'File exceeds form upload limit';
                return false;
            case UPLOAD_ERR_PARTIAL:
                $this->errors[] = 'File upload was incomplete';
                return false;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = 'No file was uploaded';
                return false;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->errors[] = 'Server temporary directory missing';
                return false;
            case UPLOAD_ERR_CANT_WRITE:
                $this->errors[] = 'Failed to write file to disk';
                return false;
            case UPLOAD_ERR_EXTENSION:
                $this->errors[] = 'File upload stopped by extension';
                return false;
            default:
                $this->errors[] = 'Unknown file upload error';
                return false;
        }
    }
    
    /**
     * Validate file size
     */
    protected function validateFileSize($file, $config)
    {
        if (!isset($file['size']) || $file['size'] <= 0) {
            $this->errors[] = 'File is empty or size information missing';
            return false;
        }
        
        if (isset($config['max_size'])) {
            $maxSize = $this->parseSize($config['max_size']);
            if ($file['size'] > $maxSize) {
                $this->errors[] = "File size ({$this->formatBytes($file['size'])}) exceeds maximum allowed size ({$config['max_size']})";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate file type by extension
     */
    protected function validateFileType($file, $config)
    {
        if (!isset($config['allowed_types']) || !is_array($config['allowed_types'])) {
            return true; // No restrictions
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $config['allowed_types'])) {
            $this->errors[] = "File type '.{$extension}' is not allowed. Allowed types: " . implode(', ', $config['allowed_types']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate MIME type
     */
    protected function validateMimeType($file, $config)
    {
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            $this->errors[] = 'Temporary file not found for MIME type validation';
            return false;
        }
        
        $actualMimeType = mime_content_type($file['tmp_name']);
        
        if (!$actualMimeType) {
            $this->errors[] = 'Could not determine file MIME type';
            return false;
        }
        
        // Get expected MIME types based on file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!isset($this->mimeTypes[$extension])) {
            $this->errors[] = "MIME type validation not available for '.{$extension}' files";
            return false;
        }
        
        $expectedMimeTypes = $this->mimeTypes[$extension];
        
        if (!in_array($actualMimeType, $expectedMimeTypes)) {
            $this->errors[] = "File MIME type '{$actualMimeType}' does not match expected type for '.{$extension}' files";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file signature (magic bytes)
     */
    protected function validateFileSignature($file)
    {
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return true; // Skip if file not available
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!isset($this->magicBytes[$extension])) {
            return true; // No magic bytes defined for this extension
        }
        
        $fileHandle = fopen($file['tmp_name'], 'rb');
        if (!$fileHandle) {
            $this->errors[] = 'Could not open file for signature validation';
            return false;
        }
        
        $header = fread($fileHandle, 8); // Read first 8 bytes
        fclose($fileHandle);
        
        $hexHeader = strtoupper(bin2hex($header));
        $expectedSignatures = $this->magicBytes[$extension];
        
        foreach ($expectedSignatures as $signature) {
            if (strpos($hexHeader, $signature) === 0) {
                return true; // Valid signature found
            }
        }
        
        $this->errors[] = "File signature does not match expected format for '.{$extension}' files";
        return false;
    }
    
    /**
     * Validate filename for security
     */
    protected function validateFilename($file)
    {
        $filename = $file['name'];
        
        // Check filename length
        if (strlen($filename) > 255) {
            $this->errors[] = 'Filename is too long (maximum 255 characters)';
            return false;
        }
        
        // Check for dangerous characters
        if (preg_match('/[\x00-\x1f\x7f-\x9f]/', $filename)) {
            $this->errors[] = 'Filename contains invalid control characters';
            return false;
        }
        
        // Check for path traversal attempts
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            $this->errors[] = 'Filename contains path traversal characters';
            return false;
        }
        
        // Check for executable extensions
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar', 'sh'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, $dangerousExtensions)) {
            $this->errors[] = "File extension '.{$extension}' is not allowed for security reasons";
            return false;
        }
        
        return true;
    }
    
    /**
     * Scan for malicious content
     */
    protected function scanForMaliciousContent($filePath)
    {
        // Read file content for pattern matching
        $content = file_get_contents($filePath, false, null, 0, 1024 * 1024); // Read first 1MB
        
        if ($content === false) {
            $this->errors[] = 'Could not read file content for security scan';
            return false;
        }
        
        // Common malicious patterns
        $maliciousPatterns = [
            '/<?php/i',
            '/<%/i', // ASP tags
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/shell_exec\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->errors[] = 'File contains potentially malicious content';
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate image content
     */
    protected function validateImageContent($filePath, $config)
    {
        $imageInfo = getimagesize($filePath);
        
        if (!$imageInfo) {
            $this->errors[] = 'Invalid or corrupted image file';
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Validate dimensions if specified
        if (isset($config['dimensions'])) {
            if (isset($config['dimensions']['min'])) {
                list($minWidth, $minHeight) = explode('x', $config['dimensions']['min']);
                if ($width < $minWidth || $height < $minHeight) {
                    $this->errors[] = "Image dimensions ({$width}x{$height}) are below minimum required ({$config['dimensions']['min']})";
                    return false;
                }
            }
            
            if (isset($config['dimensions']['max'])) {
                list($maxWidth, $maxHeight) = explode('x', $config['dimensions']['max']);
                if ($width > $maxWidth || $height > $maxHeight) {
                    $this->errors[] = "Image dimensions ({$width}x{$height}) exceed maximum allowed ({$config['dimensions']['max']})";
                    return false;
                }
            }
        }
        
        // Validate image ratio if specified
        if (isset($config['aspect_ratio'])) {
            $ratio = $width / $height;
            $expectedRatio = $config['aspect_ratio'];
            $tolerance = 0.1; // 10% tolerance
            
            if (abs($ratio - $expectedRatio) > $tolerance) {
                $this->errors[] = "Image aspect ratio ({$ratio}) does not match required ratio ({$expectedRatio})";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate document content
     */
    protected function validateDocumentContent($filePath, $config)
    {
        // PDF-specific validation
        if (mime_content_type($filePath) === 'application/pdf') {
            return $this->validatePdfContent($filePath, $config);
        }
        
        // Word document validation
        if (in_array(mime_content_type($filePath), ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return $this->validateWordDocumentContent($filePath, $config);
        }
        
        return true;
    }
    
    /**
     * Validate PDF content
     */
    protected function validatePdfContent($filePath, $config)
    {
        // Basic PDF structure validation
        $content = file_get_contents($filePath, false, null, 0, 1024);
        
        if (strpos($content, '%PDF') !== 0) {
            $this->errors[] = 'Invalid PDF file structure';
            return false;
        }
        
        // Check for required content if specified
        if (isset($config['required_content'])) {
            $fullContent = file_get_contents($filePath);
            
            foreach ($config['required_content'] as $requiredItem) {
                if (stripos($fullContent, $requiredItem) === false) {
                    $this->errors[] = "PDF does not contain required content: {$requiredItem}";
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate Word document content
     */
    protected function validateWordDocumentContent($filePath, $config)
    {
        // TODO: Implement Word document validation
        // This could include checking for macros, required fields, etc.
        return true;
    }
    
    /**
     * Validate archive content
     */
    protected function validateArchiveContent($filePath, $config)
    {
        if (mime_content_type($filePath) === 'application/zip') {
            return $this->validateZipContent($filePath, $config);
        }
        
        return true;
    }
    
    /**
     * Validate ZIP archive content
     */
    protected function validateZipContent($filePath, $config)
    {
        $zip = new \ZipArchive();
        
        if ($zip->open($filePath) !== TRUE) {
            $this->errors[] = 'Invalid or corrupted ZIP archive';
            return false;
        }
        
        // Check number of files in archive
        $numFiles = $zip->numFiles;
        
        if (isset($config['max_archive_files']) && $numFiles > $config['max_archive_files']) {
            $zip->close();
            $this->errors[] = "ZIP archive contains too many files ({$numFiles} > {$config['max_archive_files']})";
            return false;
        }
        
        // Scan each file in the archive
        for ($i = 0; $i < $numFiles; $i++) {
            $fileInfo = $zip->statIndex($i);
            $filename = $fileInfo['name'];
            
            // Check for dangerous files
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'scr', 'vbs', 'js'];
            
            if (in_array($extension, $dangerousExtensions)) {
                $zip->close();
                $this->errors[] = "ZIP archive contains dangerous file: {$filename}";
                return false;
            }
            
            // Check for path traversal in filenames
            if (strpos($filename, '..') !== false) {
                $zip->close();
                $this->errors[] = "ZIP archive contains path traversal: {$filename}";
                return false;
            }
        }
        
        $zip->close();
        return true;
    }
    
    /**
     * Check if file is an image
     */
    protected function isImageFile($filePath)
    {
        $mimeType = mime_content_type($filePath);
        return strpos($mimeType, 'image/') === 0;
    }
    
    /**
     * Check if file is a document
     */
    protected function isDocumentFile($filePath)
    {
        $mimeType = mime_content_type($filePath);
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];
        
        return in_array($mimeType, $documentMimes);
    }
    
    /**
     * Check if file is an archive
     */
    protected function isArchiveFile($filePath)
    {
        $mimeType = mime_content_type($filePath);
        $archiveMimes = [
            'application/zip',
            'application/x-zip-compressed'
        ];
        
        return in_array($mimeType, $archiveMimes);
    }
    
    /**
     * Parse size string to bytes
     */
    protected function parseSize($sizeString)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $sizeString = trim($sizeString);
        
        preg_match('/^([0-9.]+)\s*([A-Z]*)/i', $sizeString, $matches);
        
        $size = floatval($matches[1]);
        $unit = strtoupper($matches[2] ?? 'B');
        
        $power = array_search($unit, $units);
        if ($power === false) {
            $power = 0;
        }
        
        return $size * pow(1024, $power);
    }
    
    /**
     * Format bytes to human readable string
     */
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    
    /**
     * Get validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Clear errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }
}