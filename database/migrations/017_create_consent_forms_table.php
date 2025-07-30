<?php
// database/migrations/017_create_consent_forms_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateConsentFormsTable extends Migration
{
    public function up()
    {
        $columns = [
            'participant_id' => 'INT UNSIGNED NOT NULL',
            'form_type' => "ENUM('participation_consent', 'medical_consent', 'photo_video_consent', 'transport_consent') NOT NULL",
            'status' => "ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending'",
            'submitted_date' => 'DATETIME NOT NULL',
            'reviewed_date' => 'DATETIME NULL',
            'reviewed_by' => 'INT UNSIGNED NULL',
            'parent_guardian_name' => 'VARCHAR(255) NOT NULL',
            'parent_guardian_signature' => 'VARCHAR(255) NULL',
            'parent_guardian_date' => 'DATE NULL',
            'file_path' => 'VARCHAR(500) NULL',
            'notes' => 'TEXT NULL',
            'rejection_reason' => 'TEXT NULL'
        ];
        
        $this->createTable('consent_forms', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci'
        ]);
        
        // Add indexes
        $this->addIndex('consent_forms', 'idx_consent_forms_participant_id', 'participant_id');
        $this->addIndex('consent_forms', 'idx_consent_forms_form_type', 'form_type');
        $this->addIndex('consent_forms', 'idx_consent_forms_status', 'status');
        $this->addIndex('consent_forms', 'idx_consent_forms_reviewed_by', 'reviewed_by');
        $this->addIndex('consent_forms', 'idx_consent_forms_submitted_date', 'submitted_date');
        
        // Add composite index for participant + form type (should be unique)
        $this->addIndex('consent_forms', 'idx_consent_forms_participant_form', 'participant_id, form_type');
    }
    
    public function down()
    {
        $this->dropTable('consent_forms');
    }
}