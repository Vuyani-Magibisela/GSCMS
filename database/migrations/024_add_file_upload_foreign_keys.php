<?php
// database/migrations/024_add_file_upload_foreign_keys.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddFileUploadForeignKeys extends Migration
{
    public function up()
    {
        // Add foreign key constraints for uploaded_files table
        $this->addForeignKey(
            'uploaded_files', 
            'fk_uploaded_files_uploaded_by', 
            'uploaded_by', 
            'users', 
            'id', 
            'RESTRICT', 
            'CASCADE'
        );
        
        // Add foreign key constraints for team_submissions table
        $this->addForeignKey(
            'team_submissions', 
            'fk_team_submissions_team_id', 
            'team_id', 
            'teams', 
            'id', 
            'RESTRICT', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'team_submissions', 
            'fk_team_submissions_phase_id', 
            'phase_id', 
            'phases', 
            'id', 
            'RESTRICT', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'team_submissions', 
            'fk_team_submissions_reviewed_by', 
            'reviewed_by', 
            'users', 
            'id', 
            'SET NULL', 
            'CASCADE'
        );
        
        $this->addForeignKey(
            'team_submissions', 
            'fk_team_submissions_uploaded_file_id', 
            'uploaded_file_id', 
            'uploaded_files', 
            'id', 
            'SET NULL', 
            'CASCADE'
        );
        
        // Add foreign key for consent_forms to uploaded_files
        $this->addForeignKey(
            'consent_forms', 
            'fk_consent_forms_uploaded_file_id', 
            'uploaded_file_id', 
            'uploaded_files', 
            'id', 
            'SET NULL', 
            'CASCADE'
        );
        
        echo "Added foreign key constraints for file upload tables.\n";
    }
    
    public function down()
    {
        // Drop foreign key constraints in reverse order
        $this->dropForeignKey('consent_forms', 'fk_consent_forms_uploaded_file_id');
        $this->dropForeignKey('team_submissions', 'fk_team_submissions_uploaded_file_id');
        $this->dropForeignKey('team_submissions', 'fk_team_submissions_reviewed_by');
        $this->dropForeignKey('team_submissions', 'fk_team_submissions_phase_id');
        $this->dropForeignKey('team_submissions', 'fk_team_submissions_team_id');
        $this->dropForeignKey('uploaded_files', 'fk_uploaded_files_uploaded_by');
        
        echo "Dropped foreign key constraints for file upload tables.\n";
    }
}