<?php

namespace App\Core\Storage;

use Exception;
use App\Core\Logger;

class LocalStorage implements StorageInterface
{
    protected $basePath;
    protected $baseUrl;
    protected $logger;
    
    public function __construct($basePath = null, $baseUrl = null)
    {
        $this->basePath = $basePath ?: PUBLIC_PATH . '/uploads';
        $this->baseUrl = $baseUrl ?: '/uploads';
        $this->logger = Logger::getInstance();
        
        // Ensure base path exists
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }
    
    /**
     * Store a file
     */
    public function store($source, $destination, $options = [])
    {
        try {
            $fullDestination = $this->getFullPath($destination);
            
            // Ensure destination directory exists
            $destinationDir = dirname($fullDestination);
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            
            // Copy or move the file
            $success = false;
            if (isset($options['move']) && $options['move']) {
                $success = rename($source, $fullDestination);
            } else {
                $success = copy($source, $fullDestination);
            }
            
            if ($success) {
                // Set permissions
                chmod($fullDestination, 0644);
                $this->logger->info("File stored: {$source} -> {$fullDestination}");
                return $destination;
            }
            
            throw new Exception("Failed to store file");
            
        } catch (Exception $e) {
            $this->logger->error("Storage error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Retrieve a file
     */
    public function retrieve($path)
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: {$path}");
        }
        
        return file_get_contents($fullPath);
    }
    
    /**
     * Delete a file
     */
    public function delete($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            
            if (!file_exists($fullPath)) {
                return true; // Already deleted
            }
            
            if (unlink($fullPath)) {
                $this->logger->info("File deleted: {$fullPath}");
                return true;
            }
            
            throw new Exception("Failed to delete file");
            
        } catch (Exception $e) {
            $this->logger->error("Delete error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if file exists
     */
    public function exists($path)
    {
        return file_exists($this->getFullPath($path));
    }
    
    /**
     * Get file size
     */
    public function size($path)
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: {$path}");
        }
        
        return filesize($fullPath);
    }
    
    /**
     * Get file URL
     */
    public function url($path)
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Copy a file
     */
    public function copy($source, $destination)
    {
        try {
            $fullSource = $this->getFullPath($source);
            $fullDestination = $this->getFullPath($destination);
            
            if (!file_exists($fullSource)) {
                throw new Exception("Source file not found: {$source}");
            }
            
            // Ensure destination directory exists
            $destinationDir = dirname($fullDestination);
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            
            if (copy($fullSource, $fullDestination)) {
                chmod($fullDestination, 0644);
                $this->logger->info("File copied: {$fullSource} -> {$fullDestination}");
                return true;
            }
            
            throw new Exception("Failed to copy file");
            
        } catch (Exception $e) {
            $this->logger->error("Copy error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Move a file
     */
    public function move($source, $destination)
    {
        try {
            $fullSource = $this->getFullPath($source);
            $fullDestination = $this->getFullPath($destination);
            
            if (!file_exists($fullSource)) {
                throw new Exception("Source file not found: {$source}");
            }
            
            // Ensure destination directory exists
            $destinationDir = dirname($fullDestination);
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            
            if (rename($fullSource, $fullDestination)) {
                $this->logger->info("File moved: {$fullSource} -> {$fullDestination}");
                return true;
            }
            
            throw new Exception("Failed to move file");
            
        } catch (Exception $e) {
            $this->logger->error("Move error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * List files in directory
     */
    public function listFiles($directory)
    {
        $fullPath = $this->getFullPath($directory);
        
        if (!is_dir($fullPath)) {
            throw new Exception("Directory not found: {$directory}");
        }
        
        $files = [];
        $iterator = new \DirectoryIterator($fullPath);
        
        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            
            $relativePath = $directory . '/' . $file->getFilename();
            $files[] = [
                'path' => $relativePath,
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'type' => mime_content_type($file->getPathname())
            ];
        }
        
        return $files;
    }
    
    /**
     * Create directory
     */
    public function createDirectory($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            
            if (is_dir($fullPath)) {
                return true; // Already exists
            }
            
            if (mkdir($fullPath, 0755, true)) {
                $this->logger->info("Directory created: {$fullPath}");
                return true;
            }
            
            throw new Exception("Failed to create directory");
            
        } catch (Exception $e) {
            $this->logger->error("Directory creation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete directory
     */
    public function deleteDirectory($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            
            if (!is_dir($fullPath)) {
                return true; // Already deleted
            }
            
            // Remove all files first
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            
            if (rmdir($fullPath)) {
                $this->logger->info("Directory deleted: {$fullPath}");
                return true;
            }
            
            throw new Exception("Failed to delete directory");
            
        } catch (Exception $e) {
            $this->logger->error("Directory deletion error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get file metadata
     */
    public function getMetadata($path)
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new Exception("File not found: {$path}");
        }
        
        return [
            'path' => $path,
            'full_path' => $fullPath,
            'name' => basename($fullPath),
            'size' => filesize($fullPath),
            'type' => mime_content_type($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
            'modified' => filemtime($fullPath),
            'created' => filectime($fullPath),
            'permissions' => substr(sprintf('%o', fileperms($fullPath)), -4),
            'readable' => is_readable($fullPath),
            'writable' => is_writable($fullPath),
            'md5' => md5_file($fullPath),
            'url' => $this->url($path)
        ];
    }
    
    /**
     * Get full filesystem path
     */
    protected function getFullPath($path)
    {
        // Remove leading slash and ensure path is within base path
        $path = ltrim($path, '/');
        $fullPath = $this->basePath . '/' . $path;
        
        // Security check: ensure path is within base directory
        $realBasePath = realpath($this->basePath);
        $realFullPath = realpath(dirname($fullPath));
        
        if ($realFullPath && strpos($realFullPath, $realBasePath) !== 0) {
            throw new Exception("Path traversal attempt detected: {$path}");
        }
        
        return $fullPath;
    }
    
    /**
     * Get storage statistics
     */
    public function getStorageStats()
    {
        $totalSize = 0;
        $fileCount = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
                $fileCount++;
            }
        }
        
        $freeSpace = disk_free_space($this->basePath);
        $totalSpace = disk_total_space($this->basePath);
        
        return [
            'total_files' => $fileCount,
            'total_size' => $totalSize,
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
            'usage_percentage' => round(($totalSpace - $freeSpace) / $totalSpace * 100, 2)
        ];
    }
    
    /**
     * Create backup of file
     */
    public function backup($path, $backupSuffix = null)
    {
        if (!$backupSuffix) {
            $backupSuffix = '_backup_' . date('Y-m-d_H-i-s');
        }
        
        $pathInfo = pathinfo($path);
        $backupPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . $backupSuffix . '.' . $pathInfo['extension'];
        
        return $this->copy($path, $backupPath);
    }
    
    /**
     * Restore from backup
     */
    public function restore($backupPath, $originalPath)
    {
        return $this->copy($backupPath, $originalPath);
    }
}