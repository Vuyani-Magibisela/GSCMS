<?php
// database/migrations/045_create_scheduling_conflicts_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateSchedulingConflictsTable extends Migration
{
    public function up()
    {
        $columns = [
            'conflict_type' => 'ENUM("double_booking", "judge_overlap", "venue_capacity", "team_availability", "resource_shortage") NOT NULL',
            'severity' => 'ENUM("warning", "error", "critical") NOT NULL',
            'entity_type' => 'VARCHAR(50) NOT NULL',
            'entity_id' => 'INT UNSIGNED NOT NULL',
            'conflicting_entity_id' => 'INT UNSIGNED NULL',
            'conflict_date' => 'DATE NOT NULL',
            'conflict_time' => 'TIME NULL',
            'description' => 'TEXT NOT NULL',
            'resolution_status' => 'ENUM("pending", "resolved", "ignored") DEFAULT "pending"',
            'resolved_by' => 'INT UNSIGNED NULL',
            'resolution_notes' => 'TEXT NULL',
            'detected_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'resolved_at' => 'TIMESTAMP NULL',
            'auto_resolvable' => 'BOOLEAN DEFAULT FALSE',
            'impact_score' => 'INT DEFAULT 0'
        ];
        
        $this->createTable('scheduling_conflicts', $columns);
        
        // Add indexes
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_type', 'conflict_type');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_severity', 'severity');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_status', 'resolution_status, severity');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_entity', 'entity_type, entity_id');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_date', 'conflict_date');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_detected', 'detected_at');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_resolved_by', 'resolved_by');
        
        // Add composite indexes
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_active', 'resolution_status, detected_at');
        $this->addIndex('scheduling_conflicts', 'idx_conflicts_auto', 'auto_resolvable, resolution_status');
    }
    
    public function down()
    {
        $this->dropTable('scheduling_conflicts');
    }
}