<?php

namespace App\Core;

use Exception;
use App\Core\Logger;
use App\Core\Security;

class FileManager
{
    protected $logger;
    
    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Move file from source to destination
     */
    public function moveFile($source, $destination)
    {
        try {
            // Ensure destination directory exists
            $destinationDir = dirname($destination);
            if (!$this->ensureDirectoryExists($destinationDir)) {
                return false;
            }
            
            // Move the file
            if (rename($source, $destination)) {
                $this->logger->info("File moved from {$source} to {$destination}");
                return true;
            }
            
            $this->logger->error("Failed to move file from {$source} to {$destination}");
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("File move error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Copy file from source to destination
     */
    public function copyFile($source, $destination)
    {
        try {
            // Ensure destination directory exists
            $destinationDir = dirname($destination);
            if (!$this->ensureDirectoryExists($destinationDir)) {
                return false;
            }
            
            // Copy the file
            if (copy($source, $destination)) {
                $this->logger->info("File copied from {$source} to {$destination}");
                return true;
            }
            
            $this->logger->error("Failed to copy file from {$source} to {$destination}");
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("File copy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete file
     */
    public function deleteFile($filePath)
    {
        try {
            if (!file_exists($filePath)) {
                return true; // File already doesn't exist
            }
            
            if (unlink($filePath)) {
                $this->logger->info("File deleted: {$filePath}");
                return true;
            }
            
            $this->logger->error("Failed to delete file: {$filePath}");
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("File deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create directory if it doesn't exist
     */
    public function ensureDirectoryExists($directory, $permissions = 0755)
    {
        try {
            if (is_dir($directory)) {
                return true;
            }
            
            if (mkdir($directory, $permissions, true)) {
                $this->logger->info("Directory created: {$directory}");
                return true;
            }
            
            $this->logger->error("Failed to create directory: {$directory}");
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("Directory creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set file permissions
     */
    public function setPermissions($filePath, $permissions = 0644)
    {
        try {
            if (chmod($filePath, $permissions)) {
                return true;
            }
            
            $this->logger->warning("Failed to set permissions for file: {$filePath}");
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("Permission setting error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get file information
     */
    public function getFileInfo($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'path' => $filePath,
            'name' => basename($filePath),
            'size' => filesize($filePath),
            'type' => mime_content_type($filePath),
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'modified' => filemtime($filePath),
            'created' => filectime($filePath),
            'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
            'readable' => is_readable($filePath),
            'writable' => is_writable($filePath),
            'md5' => md5_file($filePath)
        ];
    }
    
    /**
     * Get directory listing
     */
    public function getDirectoryListing($directory, $recursive = false)
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                $files[] = $this->getFileInfo($file->getPathname());
            }
        } else {
            $iterator = new \DirectoryIterator($directory);
            
            foreach ($iterator as $file) {
                if ($file->isDot()) {
                    continue;
                }
                
                $files[] = $this->getFileInfo($file->getPathname());
            }
        }
        
        return $files;
    }
    
    /**
     * Create backup of file
     */
    public function backupFile($filePath, $backupDir = null)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (!$backupDir) {
            $backupDir = dirname($filePath) . '/backups';
        }
        
        if (!$this->ensureDirectoryExists($backupDir)) {
            return false;
        }
        
        $filename = basename($filePath);
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = $backupDir . '/' . $timestamp . '_' . $filename;
        
        return $this->copyFile($filePath, $backupPath);
    }
    
    /**
     * Clean up old files
     */
    public function cleanupOldFiles($directory, $maxAge = 2592000, $pattern = '*') // 30 days default
    {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $cleanedCount = 0;
        $cutoffTime = time() - $maxAge;
        
        $files = glob($directory . '/' . $pattern);
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if ($this->deleteFile($file)) {
                    $cleanedCount++;
                }
            }
        }
        
        $this->logger->info("Cleaned up {$cleanedCount} old files from {$directory}");
        return $cleanedCount;
    }
    
    /**
     * Calculate directory size
     */
    public function getDirectorySize($directory)
    {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }
    
    /**
     * Check disk space
     */
    public function checkDiskSpace($path, $requiredSpace = null)
    {
        $freeSpace = disk_free_space($path);
        $totalSpace = disk_total_space($path);
        
        $result = [
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
            'used_space' => $totalSpace - $freeSpace,
            'usage_percentage' => round(($totalSpace - $freeSpace) / $totalSpace * 100, 2)
        ];
        
        if ($requiredSpace) {
            $result['sufficient_space'] = $freeSpace >= $requiredSpace;
        }
        
        return $result;
    }
    
    /**
     * Generate unique filename
     */
    public function generateUniqueFilename($directory, $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $basename = Security::sanitizeFilename($basename);
        
        $counter = 1;
        $newFilename = $basename . '.' . $extension;
        
        while (file_exists($directory . '/' . $newFilename)) {
            $newFilename = $basename . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $newFilename;
    }
    
    /**
     * Create secure download link
     */
    public function createSecureDownloadLink($filePath, $expires = 3600) // 1 hour default
    {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $token = bin2hex(random_bytes(32));
        $expiry = time() + $expires;
        
        // Store token information (in a real implementation, this would be in a database)
        $tokenData = [
            'file_path' => $filePath,
            'expires' => $expiry,
            'token' => $token
        ];
        
        // For now, we'll just return the token
        // In a real implementation, you'd store this in a database or cache
        return $token;
    }
    
    /**
     * Validate secure download token
     */
    public function validateDownloadToken($token)
    {
        // This would check against stored token data in a real implementation
        // For now, return false as placeholder
        return false;
    }
    
    /**
     * Stream file download
     */
    public function streamFile($filePath, $filename = null, $mimeType = null)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (!$mimeType) {
            $mimeType = mime_content_type($filePath);
        }
        
        if (!$filename) {
            $filename = basename($filePath);
        }
        
        // Set headers for file download
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Stream the file
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
            return true;
        }
        
        return false;
    }
    
    /**
     * Create thumbnail for image
     */
    public function createThumbnail($imagePath, $thumbnailPath, $width = 150, $height = 150)
    {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($originalWidth, $originalHeight, $imageType) = $imageInfo;
        
        // Calculate thumbnail dimensions while maintaining aspect ratio
        $aspectRatio = $originalWidth / $originalHeight;
        
        if ($width / $height > $aspectRatio) {
            $width = $height * $aspectRatio;
        } else {
            $height = $width / $aspectRatio;
        }
        
        // Create source image resource
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
        }
        
        // Resize image
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
        
        // Save thumbnail
        $result = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($thumbnail, $thumbnailPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($thumbnail, $thumbnailPath, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($thumbnail, $thumbnailPath);
                break;
        }
        
        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return $result;
    }
    
    /**
     * Compress file (ZIP)
     */
    public function compressFile($filePath, $zipPath = null)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (!$zipPath) {
            $zipPath = $filePath . '.zip';
        }
        
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        
        $zip->addFile($filePath, basename($filePath));
        $result = $zip->close();
        
        if ($result) {
            $this->logger->info("File compressed: {$filePath} -> {$zipPath}");
        }
        
        return $result;
    }
    
    /**
     * Extract ZIP archive
     */
    public function extractZip($zipPath, $extractTo)
    {
        if (!file_exists($zipPath)) {
            return false;
        }
        
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            return false;
        }
        
        if (!$this->ensureDirectoryExists($extractTo)) {
            $zip->close();
            return false;
        }
        
        $result = $zip->extractTo($extractTo);
        $zip->close();
        
        if ($result) {
            $this->logger->info("Archive extracted: {$zipPath} -> {$extractTo}");
        }
        
        return $result;
    }
    
    /**
     * Get file hash
     */
    public function getFileHash($filePath, $algorithm = 'md5')
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        return hash_file($algorithm, $filePath);
    }
    
    /**
     * Compare files by hash
     */
    public function compareFiles($file1, $file2, $algorithm = 'md5')
    {
        $hash1 = $this->getFileHash($file1, $algorithm);
        $hash2 = $this->getFileHash($file2, $algorithm);
        
        return $hash1 && $hash2 && $hash1 === $hash2;
    }
    
    /**
     * Find duplicate files in directory
     */
    public function findDuplicates($directory, $algorithm = 'md5')
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $hashes = [];
        $duplicates = [];
        
        $files = $this->getDirectoryListing($directory, true);
        
        foreach ($files as $file) {
            if (!$file || !isset($file['path'])) {
                continue;
            }
            
            $hash = $this->getFileHash($file['path'], $algorithm);
            
            if (isset($hashes[$hash])) {
                if (!isset($duplicates[$hash])) {
                    $duplicates[$hash] = [$hashes[$hash]];
                }
                $duplicates[$hash][] = $file['path'];
            } else {
                $hashes[$hash] = $file['path'];
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Format file size
     */
    public function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}