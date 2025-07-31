<?php
// database/migrations/025_add_file_management_enhancements.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddFileManagementEnhancements extends Migration
{
    public function up()
    {
        // Add additional indexes for common file management queries
        
        // Index for finding files by date range (cleanup operations)
        $this->addIndex('uploaded_files', 'idx_uploaded_files_type_created', 'upload_type, created_at');
        
        // Index for finding files by size (storage analysis)
        $this->addIndex('uploaded_files', 'idx_uploaded_files_size', 'file_size');
        
        // Index for finding duplicates by hash
        $this->addIndex('uploaded_files', 'idx_uploaded_files_hash_lookup', 'hash_md5, upload_type');
        
        // Composite index for access control queries
        $this->addIndex('uploaded_files', 'idx_uploaded_files_access_user', 'access_level, uploaded_by');
        
        // Add indexes for team submissions reporting
        $this->addIndex('team_submissions', 'idx_team_submissions_score', 'score');
        $this->addIndex('team_submissions', 'idx_team_submissions_review_date', 'reviewed_date');
        
        // Add full-text search index for submission titles and descriptions
        $this->query("ALTER TABLE team_submissions ADD FULLTEXT(title, description)");
        
        // Create a view for file storage statistics
        $this->query("
            CREATE VIEW file_storage_stats AS
            SELECT 
                upload_type,
                COUNT(*) as file_count,
                SUM(file_size) as total_size_bytes,
                AVG(file_size) as avg_size_bytes,
                MAX(file_size) as max_size_bytes,
                MIN(file_size) as min_size_bytes,
                COUNT(CASE WHEN status = 'ready' THEN 1 END) as ready_files,
                COUNT(CASE WHEN status = 'error' THEN 1 END) as error_files,
                COUNT(CASE WHEN status = 'quarantine' THEN 1 END) as quarantined_files,
                MAX(created_at) as latest_upload,
                MIN(created_at) as earliest_upload
            FROM uploaded_files 
            WHERE deleted_at IS NULL
            GROUP BY upload_type
        ");
        
        // Create a view for submission status overview
        $this->query("
            CREATE VIEW submission_status_overview AS
            SELECT 
                p.name as phase_name,
                ts.submission_type,
                ts.status,
                COUNT(*) as submission_count,
                AVG(ts.score) as avg_score,
                COUNT(CASE WHEN ts.file_path IS NOT NULL THEN 1 END) as files_attached,
                COUNT(CASE WHEN ts.reviewed_date IS NOT NULL THEN 1 END) as reviewed_count
            FROM team_submissions ts
            JOIN phases p ON ts.phase_id = p.id
            WHERE ts.deleted_at IS NULL
            GROUP BY p.id, ts.submission_type, ts.status
            ORDER BY p.name, ts.submission_type, ts.status
        ");
        
        // Create a view for consent form completeness
        $this->query("
            CREATE VIEW consent_form_completeness AS
            SELECT 
                s.name as school_name,
                t.name as team_name,
                COUNT(DISTINCT p.id) as total_participants,
                COUNT(DISTINCT cf.participant_id) as participants_with_forms,
                COUNT(CASE WHEN cf.status = 'approved' THEN cf.id END) as approved_forms,
                COUNT(CASE WHEN cf.status = 'pending' THEN cf.id END) as pending_forms,
                COUNT(CASE WHEN cf.status = 'rejected' THEN cf.id END) as rejected_forms,
                ROUND(
                    (COUNT(CASE WHEN cf.status = 'approved' THEN cf.id END) * 100.0) / 
                    (COUNT(DISTINCT p.id) * 4), 2
                ) as completion_percentage
            FROM schools s
            JOIN teams t ON s.id = t.school_id
            JOIN participants p ON t.id = p.team_id
            LEFT JOIN consent_forms cf ON p.id = cf.participant_id AND cf.deleted_at IS NULL
            WHERE s.deleted_at IS NULL 
            AND t.deleted_at IS NULL 
            AND p.deleted_at IS NULL
            GROUP BY s.id, t.id
            ORDER BY s.name, t.name
        ");
        
        echo "Added file management enhancements: indexes, full-text search, and reporting views.\n";
    }
    
    public function down()
    {
        // Drop views
        $this->query("DROP VIEW IF EXISTS consent_form_completeness");
        $this->query("DROP VIEW IF EXISTS submission_status_overview");
        $this->query("DROP VIEW IF EXISTS file_storage_stats");
        
        // Drop full-text index
        $this->query("ALTER TABLE team_submissions DROP INDEX title");
        
        // Drop additional indexes
        $this->dropIndex('team_submissions', 'idx_team_submissions_review_date');
        $this->dropIndex('team_submissions', 'idx_team_submissions_score');
        $this->dropIndex('uploaded_files', 'idx_uploaded_files_access_user');
        $this->dropIndex('uploaded_files', 'idx_uploaded_files_hash_lookup');
        $this->dropIndex('uploaded_files', 'idx_uploaded_files_size');
        $this->dropIndex('uploaded_files', 'idx_uploaded_files_type_created');
        
        echo "Removed file management enhancements.\n";
    }
}