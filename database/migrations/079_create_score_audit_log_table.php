<?php
// database/migrations/079_create_score_audit_log_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateScoreAuditLogTable extends Migration
{
    public function up()
    {
        $columns = [
            'score_id' => 'INT NOT NULL COMMENT "Score record being audited"',
            'action' => 'ENUM("created", "updated", "submitted", "validated", "disputed", "resolved", "finalized", "deleted") NOT NULL COMMENT "Action performed"',
            'performed_by' => 'INT NOT NULL COMMENT "User who performed the action"',
            'previous_value' => 'JSON NULL COMMENT "Previous values (for updates)"',
            'new_value' => 'JSON NULL COMMENT "New values"',
            'reason' => 'TEXT NULL COMMENT "Reason for the change"',
            'ip_address' => 'VARCHAR(45) NULL COMMENT "IP address of user"',
            'user_agent' => 'TEXT NULL COMMENT "Browser/device information"',
            'session_id' => 'VARCHAR(255) NULL COMMENT "Session identifier"',
            'additional_data' => 'JSON NULL COMMENT "Additional context data"'
        ];
        
        $this->createTable('score_audit_log', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Audit trail for all scoring actions'
        ]);
        
        // Add indexes
        $this->addIndex('score_audit_log', 'idx_score_action', 'score_id, action');
        $this->addIndex('score_audit_log', 'idx_performed_by', 'performed_by');
        $this->addIndex('score_audit_log', 'idx_created_at', 'created_at');
        $this->addIndex('score_audit_log', 'idx_action', 'action');
        
        echo "Created score_audit_log table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('score_audit_log');
        echo "Dropped score_audit_log table.\n";
    }
}