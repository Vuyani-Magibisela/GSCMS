<?php
// database/migrations/023_update_consent_forms_add_file_fields.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class UpdateConsentFormsAddFileFields extends Migration
{
    public function up()
    {
        // Add new file-related columns to consent_forms table
        $this->addColumn('consent_forms', 'file_name', 'VARCHAR(255) NULL COMMENT "Original filename of uploaded consent form" AFTER file_path');
        $this->addColumn('consent_forms', 'file_size', 'BIGINT UNSIGNED NULL COMMENT "File size in bytes" AFTER file_name');
        $this->addColumn('consent_forms', 'file_type', 'VARCHAR(100) NULL COMMENT "MIME type of uploaded file" AFTER file_size');
        $this->addColumn('consent_forms', 'metadata', 'JSON NULL COMMENT "File metadata including signatures, verification status" AFTER rejection_reason');
        $this->addColumn('consent_forms', 'uploaded_file_id', 'INT UNSIGNED NULL COMMENT "Reference to uploaded_files table" AFTER metadata');
        
        // Add index for uploaded_file_id
        $this->addIndex('consent_forms', 'idx_consent_forms_uploaded_file_id', 'uploaded_file_id');
        
        echo "Added file-related columns to consent_forms table.\n";
    }
    
    public function down()
    {
        // Remove the added columns
        $this->dropIndex('consent_forms', 'idx_consent_forms_uploaded_file_id');
        $this->dropColumn('consent_forms', 'uploaded_file_id');
        $this->dropColumn('consent_forms', 'metadata');
        $this->dropColumn('consent_forms', 'file_type');
        $this->dropColumn('consent_forms', 'file_size');
        $this->dropColumn('consent_forms', 'file_name');
        
        echo "Removed file-related columns from consent_forms table.\n";
    }
}