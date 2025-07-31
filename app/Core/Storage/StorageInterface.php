<?php

namespace App\Core\Storage;

interface StorageInterface
{
    /**
     * Store a file
     */
    public function store($source, $destination, $options = []);
    
    /**
     * Retrieve a file
     */
    public function retrieve($path);
    
    /**
     * Delete a file
     */
    public function delete($path);
    
    /**
     * Check if file exists
     */
    public function exists($path);
    
    /**
     * Get file size
     */
    public function size($path);
    
    /**
     * Get file URL
     */
    public function url($path);
    
    /**
     * Copy a file
     */
    public function copy($source, $destination);
    
    /**
     * Move a file
     */
    public function move($source, $destination);
    
    /**
     * List files in directory
     */
    public function listFiles($directory);
    
    /**
     * Create directory
     */
    public function createDirectory($path);
    
    /**
     * Delete directory
     */
    public function deleteDirectory($path);
    
    /**
     * Get file metadata
     */
    public function getMetadata($path);
}