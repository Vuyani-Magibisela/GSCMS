<?php
// database/migrations/031_create_medical_access_logs_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateMedicalAccessLogsTable extends Migration
{
    public function up()
    {
        $columns = [
            'participant_id' => 'INT UNSIGNED NOT NULL COMMENT "Reference to participants table"',
            'accessed_by' => 'INT UNSIGNED NOT NULL COMMENT "User who accessed the medical data"',
            'access_type' => "ENUM('view', 'edit', 'decrypt', 'export', 'emergency_critical_allergies', 'emergency_medications', 'emergency_protocol_generation', 'secure_access', 'validation') NOT NULL COMMENT 'Type of access performed'",
            'access_timestamp' => 'DATETIME NOT NULL COMMENT "Exact timestamp of access"',
            'ip_address' => 'VARCHAR(45) NOT NULL COMMENT "IP address of the user"',
            'user_agent' => 'TEXT NULL COMMENT "Browser/device user agent string"',
            'session_id' => 'VARCHAR(255) NULL COMMENT "Session identifier"',
            'access_details' => 'JSON NULL COMMENT "Additional details about the access"',
            'data_accessed' => 'JSON NULL COMMENT "Specific data fields that were accessed"',
            'risk_level' => "ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium' COMMENT 'Risk level of the access'",
            'access_reason' => 'VARCHAR(500) NULL COMMENT "Reason for accessing medical data"',
            'emergency_context' => 'BOOLEAN DEFAULT FALSE COMMENT "Was this access during an emergency situation"',
            'compliance_flags' => 'JSON NULL COMMENT "POPIA and other compliance flags"',
            'audit_notes' => 'TEXT NULL COMMENT "Additional audit notes"'
        ];
        
        $this->createTable('medical_access_logs', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Comprehensive audit trail for medical data access (POPIA compliance)'
        ]);
        
        // Add indexes for performance and security auditing
        $this->addIndex('medical_access_logs', 'idx_medical_access_participant_id', 'participant_id');
        $this->addIndex('medical_access_logs', 'idx_medical_access_accessed_by', 'accessed_by');
        $this->addIndex('medical_access_logs', 'idx_medical_access_type', 'access_type');
        $this->addIndex('medical_access_logs', 'idx_medical_access_timestamp', 'access_timestamp');
        $this->addIndex('medical_access_logs', 'idx_medical_access_ip_address', 'ip_address');
        $this->addIndex('medical_access_logs', 'idx_medical_access_risk_level', 'risk_level');
        $this->addIndex('medical_access_logs', 'idx_medical_access_emergency', 'emergency_context');
        
        // Composite indexes for common audit queries
        $this->addIndex('medical_access_logs', 'idx_medical_access_participant_type', 'participant_id, access_type');
        $this->addIndex('medical_access_logs', 'idx_medical_access_user_timestamp', 'accessed_by, access_timestamp');
        $this->addIndex('medical_access_logs', 'idx_medical_access_risk_timestamp', 'risk_level, access_timestamp');
        $this->addIndex('medical_access_logs', 'idx_medical_access_emergency_timestamp', 'emergency_context, access_timestamp');
        
        // Add foreign key constraints
        $this->addForeignKey('medical_access_logs', 'fk_medical_access_participant_id', 'participant_id', 'participants', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('medical_access_logs', 'fk_medical_access_accessed_by', 'accessed_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        
        echo "Created medical_access_logs table with comprehensive audit trail features.\n";
    }
    
    public function down()
    {
        $this->dropTable('medical_access_logs');
        echo "Dropped medical_access_logs table.\n";
    }
}