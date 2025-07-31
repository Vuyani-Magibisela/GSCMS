<?php
// database/migrations/022_create_team_submissions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateTeamSubmissionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT UNSIGNED NOT NULL COMMENT "Team that made the submission"',
            'phase_id' => 'INT UNSIGNED NOT NULL COMMENT "Competition phase this submission is for"',
            'submission_type' => "ENUM('design_portfolio', 'technical_report', 'presentation', 'video_demo', 'source_code', 'prototype_images', 'testing_results', 'documentation', 'other') NOT NULL COMMENT 'Type of submission'",
            'title' => 'VARCHAR(255) NOT NULL COMMENT "Title of the submission"',
            'description' => 'TEXT NULL COMMENT "Description of the submission"',
            'file_path' => 'VARCHAR(500) NULL COMMENT "Path to uploaded file"',
            'file_name' => 'VARCHAR(255) NULL COMMENT "Original filename"',
            'file_size' => 'BIGINT UNSIGNED NULL COMMENT "File size in bytes"',
            'file_type' => 'VARCHAR(100) NULL COMMENT "MIME type of file"',
            'status' => "ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'requires_revision', 'revised') DEFAULT 'draft' COMMENT 'Submission status'",
            'submitted_date' => 'DATETIME NULL COMMENT "When submission was submitted for review"',
            'reviewed_date' => 'DATETIME NULL COMMENT "When submission was reviewed"',
            'reviewed_by' => 'INT UNSIGNED NULL COMMENT "User ID who reviewed the submission"',
            'score' => 'DECIMAL(5,2) NULL COMMENT "Score given to submission (if applicable)"',
            'feedback' => 'TEXT NULL COMMENT "Feedback from reviewer"',
            'notes' => 'TEXT NULL COMMENT "Internal notes"',
            'metadata' => 'JSON NULL COMMENT "Additional submission metadata"',
            'uploaded_file_id' => 'INT UNSIGNED NULL COMMENT "Reference to uploaded_files table"'
        ];
        
        $this->createTable('team_submissions', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Team submissions for competition phases'
        ]);
        
        // Add indexes for performance
        $this->addIndex('team_submissions', 'idx_team_submissions_team_id', 'team_id');
        $this->addIndex('team_submissions', 'idx_team_submissions_phase_id', 'phase_id');
        $this->addIndex('team_submissions', 'idx_team_submissions_submission_type', 'submission_type');
        $this->addIndex('team_submissions', 'idx_team_submissions_status', 'status');
        $this->addIndex('team_submissions', 'idx_team_submissions_reviewed_by', 'reviewed_by');
        $this->addIndex('team_submissions', 'idx_team_submissions_submitted_date', 'submitted_date');
        $this->addIndex('team_submissions', 'idx_team_submissions_uploaded_file_id', 'uploaded_file_id');
        
        // Composite indexes for common queries
        $this->addIndex('team_submissions', 'idx_team_submissions_team_phase', 'team_id, phase_id');
        $this->addIndex('team_submissions', 'idx_team_submissions_phase_type', 'phase_id, submission_type');
        $this->addIndex('team_submissions', 'idx_team_submissions_status_submitted', 'status, submitted_date');
        
        // Unique constraint for team + phase + submission_type (prevent duplicates)
        $this->addIndex('team_submissions', 'uk_team_submissions_unique', 'team_id, phase_id, submission_type', 'UNIQUE');
        
        echo "Created team_submissions table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('team_submissions');
        echo "Dropped team_submissions table.\n";
    }
}