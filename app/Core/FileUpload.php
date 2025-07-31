<?php

namespace App\Core;

use Exception;
use App\Core\FileValidator;
use App\Core\FileManager;
use App\Core\Security;
use App\Core\Logger;

class FileUpload
{
    protected $config;
    protected $validator;
    protected $fileManager;
    protected $logger;
    protected $tempDir;
    protected $uploadedFiles = [];
    protected $errors = [];
    
    public function __construct()
    {
        $this->config = $this->getDefaultConfig();
        $this->validator = new FileValidator();
        $this->fileManager = new FileManager();
        $this->logger = Logger::getInstance();
        $this->tempDir = sys_get_temp_dir() . '/gscms_uploads';
        
        // Ensure temp directory exists
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Handle single or multiple file uploads
     */
    public function handleUpload($files, $uploadType, $options = [])
    {
        try {
            // Normalize files array for consistent handling
            $normalizedFiles = $this->normalizeFilesArray($files);
            
            // Validate upload type and get configuration
            $typeConfig = $this->getTypeConfig($uploadType);
            if (!$typeConfig) {
                throw new Exception("Invalid upload type: {$uploadType}");
            }
            
            // Check upload limits
            if (!$this->checkUploadLimits($normalizedFiles, $typeConfig)) {
                return $this->getResponse(false, 'Upload limits exceeded', $this->errors);
            }
            
            $results = [];
            
            foreach ($normalizedFiles as $file) {
                $result = $this->processFile($file, $uploadType, $typeConfig, $options);
                $results[] = $result;
                
                if (!$result['success']) {
                    $this->errors[] = $result['message'];
                }
            }
            
            // Check if any files failed
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalCount = count($results);
            
            if ($successCount === 0) {
                return $this->getResponse(false, 'All files failed to upload', $this->errors);
            } elseif ($successCount < $totalCount) {
                return $this->getResponse(true, "Only {$successCount} of {$totalCount} files uploaded successfully", $results, $this->errors);
            }
            
            return $this->getResponse(true, 'All files uploaded successfully', $results);
            
        } catch (Exception $e) {
            $this->logger->error('File upload error: ' . $e->getMessage());
            return $this->getResponse(false, $e->getMessage());
        }
    }
    
    /**
     * Process individual file
     */
    protected function processFile($file, $uploadType, $typeConfig, $options = [])
    {
        try {
            // Stage 1: Basic file validation
            if (!$this->validator->validateFile($file, $typeConfig)) {
                return $this->getFileResponse(false, 'File validation failed: ' . implode(', ', $this->validator->getErrors()));
            }
            
            // Stage 2: Security scanning
            if ($typeConfig['scan_for_virus'] ?? false) {
                if (!$this->scanForVirus($file)) {
                    return $this->getFileResponse(false, 'File failed security scan');
                }
            }
            
            // Stage 3: Move to temporary secure location
            $tempPath = $this->moveToTemp($file);
            if (!$tempPath) {
                return $this->getFileResponse(false, 'Failed to secure file');
            }
            
            // Stage 4: Additional content validation
            if (!$this->validator->validateContent($tempPath, $typeConfig)) {
                unlink($tempPath);
                return $this->getFileResponse(false, 'Content validation failed: ' . implode(', ', $this->validator->getErrors()));
            }
            
            // Stage 5: Determine final storage path
            $finalPath = $this->determineFinalPath($file, $uploadType, $options);
            
            // Stage 6: Move to final location
            if (!$this->fileManager->moveFile($tempPath, $finalPath)) {
                unlink($tempPath);
                return $this->getFileResponse(false, 'Failed to move file to final location');
            }
            
            // Stage 7: Set proper permissions
            $this->fileManager->setPermissions($finalPath, 0644);
            
            // Stage 8: Generate metadata
            $metadata = $this->extractMetadata($finalPath, $file);
            
            // Stage 9: Post-processing (thumbnails, etc.)
            $this->postProcess($finalPath, $uploadType, $typeConfig);
            
            return $this->getFileResponse(true, 'File uploaded successfully', [
                'path' => $finalPath,
                'relative_path' => str_replace(PUBLIC_PATH, '', $finalPath),
                'filename' => basename($finalPath),
                'original_name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
                'metadata' => $metadata
            ]);
            
        } catch (Exception $e) {
            $this->logger->error("File processing error: " . $e->getMessage());
            return $this->getFileResponse(false, $e->getMessage());
        }
    }
    
    /**
     * Normalize files array to handle both single and multiple uploads
     */
    protected function normalizeFilesArray($files)
    {
        // If single file upload
        if (isset($files['name']) && !is_array($files['name'])) {
            return [$files];
        }
        
        // If multiple file upload
        $normalized = [];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $normalized[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Check upload limits
     */
    protected function checkUploadLimits($files, $typeConfig)
    {
        $totalSize = array_sum(array_column($files, 'size'));
        $fileCount = count($files);
        
        // Check file count limit
        if (isset($typeConfig['max_files']) && $fileCount > $typeConfig['max_files']) {
            $this->errors[] = "Maximum {$typeConfig['max_files']} files allowed";
            return false;
        }
        
        // Check individual file size limits
        $maxSize = $this->parseSize($typeConfig['max_size']);
        foreach ($files as $file) {
            if ($file['size'] > $maxSize) {
                $this->errors[] = "File {$file['name']} exceeds maximum size of {$typeConfig['max_size']}";
                return false;
            }
        }
        
        // Check total upload size
        if (isset($typeConfig['max_total_size'])) {
            $maxTotalSize = $this->parseSize($typeConfig['max_total_size']);
            if ($totalSize > $maxTotalSize) {
                $this->errors[] = "Total upload size exceeds limit of {$typeConfig['max_total_size']}";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Move file to temporary secure location
     */
    protected function moveToTemp($file)
    {
        $tempFilename = uniqid('upload_') . '_' . Security::sanitizeFilename($file['name']);
        $tempPath = $this->tempDir . '/' . $tempFilename;
        
        if (move_uploaded_file($file['tmp_name'], $tempPath)) {
            return $tempPath;
        }
        
        return false;
    }
    
    /**
     * Determine final storage path
     */
    protected function determineFinalPath($file, $uploadType, $options = [])
    {
        $uploadDir = PUBLIC_PATH . '/uploads/' . $uploadType;
        
        // Create year-based directory structure
        $year = date('Y');
        $uploadDir .= '/' . $year;
        
        // Add school-based separation if provided
        if (isset($options['school_id'])) {
            $uploadDir .= '/school_' . $options['school_id'];
        }
        
        // Add team-based separation if provided
        if (isset($options['team_id'])) {
            $uploadDir .= '/team_' . $options['team_id'];
        }
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file['name'], $uploadDir, $options);
        
        return $uploadDir . '/' . $filename;
    }
    
    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename($originalName, $directory, $options = [])
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Auto-naming for consent forms
        if (isset($options['participant_id']) && isset($options['form_type'])) {
            $basename = "consent_{$options['form_type']}_participant_{$options['participant_id']}";
        }
        
        // Auto-naming for team submissions
        if (isset($options['team_id']) && isset($options['phase'])) {
            $basename = "submission_phase_{$options['phase']}_team_{$options['team_id']}";
        }
        
        // Sanitize filename
        $basename = Security::sanitizeFilename($basename);
        
        // Ensure uniqueness
        $counter = 1;
        $filename = $basename . '.' . $extension;
        
        while (file_exists($directory . '/' . $filename)) {
            $filename = $basename . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $filename;
    }
    
    /**
     * Scan file for viruses (placeholder for virus scanning integration)
     */
    protected function scanForVirus($file)
    {
        // TODO: Integrate with ClamAV or similar virus scanner
        // For now, return true (no scanning)
        return true;
    }
    
    /**
     * Extract file metadata
     */
    protected function extractMetadata($filePath, $originalFile)
    {
        $metadata = [
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'md5_hash' => md5_file($filePath),
            'upload_date' => date('Y-m-d H:i:s')
        ];
        
        // Extract image metadata if applicable
        if (strpos($metadata['mime_type'], 'image/') === 0) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['image_type'] = $imageInfo[2];
            }
        }
        
        return $metadata;
    }
    
    /**
     * Post-process uploaded file
     */
    protected function postProcess($filePath, $uploadType, $typeConfig)
    {
        // Generate thumbnails for images
        if ($uploadType === 'profile_photos' && strpos(mime_content_type($filePath), 'image/') === 0) {
            $this->generateThumbnail($filePath);
        }
        
        // Auto-resize if configured
        if (isset($typeConfig['auto_resize']) && $typeConfig['auto_resize']) {
            $this->autoResizeImage($filePath, $typeConfig);
        }
    }
    
    /**
     * Generate thumbnail for image
     */
    protected function generateThumbnail($imagePath)
    {
        // TODO: Implement thumbnail generation
        // This would create thumbnail versions of uploaded images
    }
    
    /**
     * Auto-resize image if needed
     */
    protected function autoResizeImage($imagePath, $typeConfig)
    {
        // TODO: Implement auto-resize functionality
        // This would resize images that exceed maximum dimensions
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
     * Get upload type configuration
     */
    protected function getTypeConfig($uploadType)
    {
        return $this->config[$uploadType] ?? null;
    }
    
    /**
     * Get default configuration
     */
    protected function getDefaultConfig()
    {
        return [
            'consent_forms' => [
                'allowed_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                'max_size' => '5MB',
                'scan_for_virus' => true,
                'required_content' => ['signature', 'date']
            ],
            'team_submissions' => [
                'allowed_types' => ['pdf', 'zip', 'mp4', 'jpg', 'png', 'docx', 'pptx'],
                'max_size' => '50MB',
                'max_files' => 10,
                'scan_for_virus' => true
            ],
            'profile_photos' => [
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => '2MB',
                'dimensions' => ['min' => '100x100', 'max' => '2000x2000'],
                'auto_resize' => true
            ],
            'certificates' => [
                'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size' => '10MB',
                'scan_for_virus' => false
            ]
        ];
    }
    
    /**
     * Clean up temporary files
     */
    public function cleanup()
    {
        $tempFiles = glob($this->tempDir . '/upload_*');
        foreach ($tempFiles as $file) {
            if (filemtime($file) < time() - 3600) { // Remove files older than 1 hour
                unlink($file);
            }
        }
    }
    
    /**
     * Get upload progress (for AJAX uploads)
     */
    public function getUploadProgress($uploadId)
    {
        // TODO: Implement upload progress tracking
        // This would work with chunked uploads and AJAX progress bars
        return ['progress' => 0, 'status' => 'pending'];
    }
    
    /**
     * Get response array
     */
    protected function getResponse($success, $message, $data = null, $errors = [])
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors
        ];
    }
    
    /**
     * Get file response array
     */
    protected function getFileResponse($success, $message, $data = null)
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * Get upload errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Set configuration
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Add upload type configuration
     */
    public function addUploadType($type, $config)
    {
        $this->config[$type] = $config;
    }
}