<?php
// database/migrations/021_create_uploaded_files_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateUploadedFilesTable extends Migration
{
    public function up()
    {
        $columns = [
            'original_name' => 'VARCHAR(255) NOT NULL COMMENT "Original filename from user"',
            'stored_name' => 'VARCHAR(255) NOT NULL COMMENT "Actual filename on disk"',
            'file_path' => 'VARCHAR(500) NOT NULL COMMENT "Full file path on disk"',
            'relative_path' => 'VARCHAR(500) NOT NULL COMMENT "Relative path from public directory"',
            'file_size' => 'BIGINT UNSIGNED NOT NULL COMMENT "File size in bytes"',
            'mime_type' => 'VARCHAR(100) NOT NULL COMMENT "MIME type of file"',
            'file_extension' => 'VARCHAR(10) NOT NULL COMMENT "File extension"',
            'upload_type' => "ENUM('consent_forms', 'team_submissions', 'profile_photos', 'certificates', 'system', 'backups', 'temp') NOT NULL COMMENT 'Type of upload'",
            'uploaded_by' => 'INT UNSIGNED NOT NULL COMMENT "User ID who uploaded the file"',
            'related_type' => 'VARCHAR(100) NULL COMMENT "Model class name for polymorphic relationship"',
            'related_id' => 'INT UNSIGNED NULL COMMENT "Related model ID for polymorphic relationship"',
            'metadata' => 'JSON NULL COMMENT "Additional file metadata"',
            'hash_md5' => 'VARCHAR(32) NULL COMMENT "MD5 hash of file"',
            'hash_sha256' => 'VARCHAR(64) NULL COMMENT "SHA256 hash of file"',
            'status' => "ENUM('uploaded', 'processing', 'ready', 'error', 'quarantine', 'archived') DEFAULT 'uploaded' COMMENT 'File processing status'",
            'access_level' => "ENUM('private', 'school', 'team', 'public', 'admin') DEFAULT 'private' COMMENT 'Access level for file'",
            'download_count' => 'INT UNSIGNED DEFAULT 0 COMMENT "Number of times file has been downloaded"'
        ];
        
        $this->createTable('uploaded_files', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Tracks all uploaded files in the system'
        ]);
        
        // Add indexes for performance
        $this->addIndex('uploaded_files', 'idx_uploaded_files_uploaded_by', 'uploaded_by');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_upload_type', 'upload_type');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_status', 'status');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_access_level', 'access_level');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_hash_md5', 'hash_md5');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_hash_sha256', 'hash_sha256');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_created_at', 'created_at');
        
        // Composite indexes for common queries
        $this->addIndex('uploaded_files', 'idx_uploaded_files_polymorphic', 'related_type, related_id');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_user_type', 'uploaded_by, upload_type');
        $this->addIndex('uploaded_files', 'idx_uploaded_files_type_status', 'upload_type, status');
        
        echo "Created uploaded_files table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('uploaded_files');
        echo "Dropped uploaded_files table.\n";
    }
}