<?php

/**
 * Migration: Create Bulk Imports Table
 * Description: Create table for tracking bulk student import operations
 * Date: 2025-01-19
 */

class CreateBulkImportsTable
{
    /**
     * Run the migration
     */
    public function up($pdo)
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS bulk_imports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL COMMENT 'School performing the import',
            imported_by INT NOT NULL COMMENT 'User who initiated import',
            
            -- File Information
            file_name VARCHAR(255) NOT NULL COMMENT 'Original uploaded file name',
            file_path VARCHAR(500) NOT NULL COMMENT 'Server file storage path',
            file_size BIGINT NOT NULL COMMENT 'File size in bytes',
            file_type ENUM('csv', 'excel', 'xlsx', 'xls') NOT NULL COMMENT 'File format type',
            file_hash VARCHAR(64) NOT NULL COMMENT 'File content hash for integrity',
            
            -- Import Configuration
            import_type ENUM('participants', 'teams', 'mixed') DEFAULT 'participants' COMMENT 'Type of data being imported',
            field_mapping JSON NULL COMMENT 'Column to field mapping configuration',
            validation_rules JSON NULL COMMENT 'Applied validation rules',
            import_settings JSON NULL COMMENT 'Import configuration settings',
            
            -- Processing Statistics
            total_records INT DEFAULT 0 COMMENT 'Total records in import file',
            processed_records INT DEFAULT 0 COMMENT 'Successfully processed records',
            failed_records INT DEFAULT 0 COMMENT 'Records that failed validation',
            duplicate_records INT DEFAULT 0 COMMENT 'Duplicate records skipped',
            updated_records INT DEFAULT 0 COMMENT 'Existing records updated',
            
            -- Import Status and Workflow
            import_status ENUM('uploaded', 'validating', 'validation_complete', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'uploaded',
            validation_passed BOOLEAN DEFAULT FALSE COMMENT 'All validation checks passed',
            processing_started_at TIMESTAMP NULL COMMENT 'Processing start time',
            processing_completed_at TIMESTAMP NULL COMMENT 'Processing completion time',
            
            -- Error and Result Tracking
            error_report TEXT NULL COMMENT 'Detailed error information',
            validation_summary JSON NULL COMMENT 'Validation results summary',
            import_summary JSON NULL COMMENT 'Import operation summary',
            rollback_data JSON NULL COMMENT 'Data for potential rollback',
            
            -- Processing Performance
            validation_duration INT NULL COMMENT 'Validation time in seconds',
            processing_duration INT NULL COMMENT 'Processing time in seconds',
            memory_usage BIGINT NULL COMMENT 'Peak memory usage in bytes',
            
            -- Approval and Review
            requires_approval BOOLEAN DEFAULT FALSE COMMENT 'Import requires admin approval',
            approved_by INT NULL COMMENT 'Admin who approved import',
            approved_at TIMESTAMP NULL COMMENT 'Import approval timestamp',
            approval_notes TEXT NULL COMMENT 'Approval or rejection notes',
            
            -- Notification Status
            notifications_sent BOOLEAN DEFAULT FALSE COMMENT 'Import completion notifications sent',
            stakeholders_notified BOOLEAN DEFAULT FALSE COMMENT 'Stakeholders informed of import',
            
            -- Audit and Cleanup
            audit_trail_created BOOLEAN DEFAULT FALSE COMMENT 'Audit trail entries created',
            cleanup_completed BOOLEAN DEFAULT FALSE COMMENT 'Temporary files cleaned up',
            retention_until DATE NULL COMMENT 'Data retention date',
            
            -- Timestamps
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Import initiation time',
            completed_at TIMESTAMP NULL COMMENT 'Import completion time',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_school_imports (school_id),
            INDEX idx_import_status (import_status),
            INDEX idx_imported_by (imported_by),
            INDEX idx_started_at (started_at),
            INDEX idx_file_type (file_type),
            INDEX idx_approval_status (requires_approval, approved_by),
            INDEX idx_completion (completed_at),
            
            -- Foreign Keys
            FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
            FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Bulk import operations tracking for student and team data';
        ";

        $pdo->exec($sql);
        
        echo "Created bulk_imports table\n";
    }

    /**
     * Reverse the migration
     */
    public function down($pdo)
    {
        $pdo->exec("DROP TABLE IF EXISTS bulk_imports");
        echo "Dropped bulk_imports table\n";
    }
}