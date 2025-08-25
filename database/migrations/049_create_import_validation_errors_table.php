<?php

/**
 * Migration: Create Import Validation Errors Table
 * Description: Create table for storing detailed import validation errors and corrections
 * Date: 2025-01-19
 */

class CreateImportValidationErrorsTable
{
    /**
     * Run the migration
     */
    public function up($pdo)
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS import_validation_errors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bulk_import_id INT NOT NULL COMMENT 'Associated bulk import operation',
            
            -- Error Location
            import_row_number INT NOT NULL COMMENT 'Row number in import file (1-based)',
            column_name VARCHAR(100) NULL COMMENT 'Column name where error occurred',
            field_name VARCHAR(100) NULL COMMENT 'Database field name',
            cell_value TEXT NULL COMMENT 'Original cell value that caused error',
            
            -- Error Details
            error_type ENUM(
                'required_field_missing',
                'invalid_format',
                'data_type_mismatch',
                'duplicate_value',
                'foreign_key_violation',
                'business_rule_violation',
                'length_exceeded',
                'invalid_enum_value',
                'date_format_invalid',
                'email_format_invalid',
                'phone_format_invalid',
                'age_validation_failed',
                'grade_eligibility_failed',
                'category_incompatible',
                'participant_already_registered',
                'school_association_invalid'
            ) NOT NULL COMMENT 'Type of validation error',
            
            error_severity ENUM('error', 'warning', 'info') DEFAULT 'error' COMMENT 'Error severity level',
            error_message TEXT NOT NULL COMMENT 'Detailed error description',
            error_code VARCHAR(50) NULL COMMENT 'System error code for categorization',
            
            -- Suggested Corrections
            suggested_correction TEXT NULL COMMENT 'Suggested fix for the error',
            correction_type ENUM('manual', 'automatic', 'lookup', 'skip') NULL COMMENT 'Type of correction needed',
            valid_values TEXT NULL COMMENT 'List of valid values if applicable',
            correction_applied BOOLEAN DEFAULT FALSE COMMENT 'Whether correction was applied',
            
            -- Context Information
            related_record_id INT NULL COMMENT 'Related database record ID if applicable',
            related_table VARCHAR(100) NULL COMMENT 'Related database table',
            context_data JSON NULL COMMENT 'Additional context for error resolution',
            
            -- Resolution Status
            is_resolved BOOLEAN DEFAULT FALSE COMMENT 'Error has been resolved',
            resolved_at TIMESTAMP NULL COMMENT 'Time when error was resolved',
            resolved_by INT NULL COMMENT 'User who resolved the error',
            resolution_method ENUM('correction', 'skip', 'override', 'manual_entry') NULL COMMENT 'How error was resolved',
            resolution_notes TEXT NULL COMMENT 'Notes about error resolution',
            
            -- Impact Assessment
            blocking_import BOOLEAN DEFAULT TRUE COMMENT 'Error prevents import completion',
            affects_other_records BOOLEAN DEFAULT FALSE COMMENT 'Error impacts other records',
            cascade_effects TEXT NULL COMMENT 'Description of cascading effects',
            
            -- User Feedback
            user_acknowledged BOOLEAN DEFAULT FALSE COMMENT 'User has seen this error',
            user_feedback TEXT NULL COMMENT 'User comments about the error',
            help_requested BOOLEAN DEFAULT FALSE COMMENT 'User requested help with error',
            
            -- Validation Rules Applied
            validation_rule VARCHAR(255) NULL COMMENT 'Specific validation rule that failed',
            rule_parameters JSON NULL COMMENT 'Parameters of the validation rule',
            expected_format VARCHAR(255) NULL COMMENT 'Expected data format',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_bulk_import_errors (bulk_import_id),
            INDEX idx_row_number (import_row_number),
            INDEX idx_error_type (error_type),
            INDEX idx_error_severity (error_severity),
            INDEX idx_resolution_status (is_resolved),
            INDEX idx_blocking_errors (blocking_import),
            INDEX idx_field_errors (field_name, error_type),
            INDEX idx_user_interaction (user_acknowledged, help_requested),
            
            -- Foreign Keys
            FOREIGN KEY (bulk_import_id) REFERENCES bulk_imports(id) ON DELETE CASCADE,
            FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Detailed validation errors for bulk import operations with correction guidance';
        ";

        $pdo->exec($sql);
        
        echo "Created import_validation_errors table\n";
    }

    /**
     * Reverse the migration
     */
    public function down($pdo)
    {
        $pdo->exec("DROP TABLE IF EXISTS import_validation_errors");
        echo "Dropped import_validation_errors table\n";
    }
}