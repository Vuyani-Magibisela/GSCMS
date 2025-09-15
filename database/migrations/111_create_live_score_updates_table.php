<?php
// database/migrations/111_create_live_score_updates_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateLiveScoreUpdatesTable extends Migration
{
    public function up()
    {
        $columns = [
            'session_id' => 'INT NOT NULL COMMENT "Live scoring session ID"',
            'team_id' => 'INT NOT NULL COMMENT "Team being scored"',
            'judge_id' => 'INT NOT NULL COMMENT "Judge providing the score"',
            'criteria_id' => 'INT NOT NULL COMMENT "Scoring criteria"',
            'score_value' => 'DECIMAL(10,2) NOT NULL COMMENT "Score value"',
            'previous_value' => 'DECIMAL(10,2) NULL COMMENT "Previous score value for this criteria"',
            'update_type' => 'ENUM("initial", "correction", "final", "disputed") DEFAULT "initial" COMMENT "Type of score update"',
            'client_timestamp' => 'TIMESTAMP NOT NULL COMMENT "Timestamp from client device"',
            'server_timestamp' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT "Server processing timestamp"',
            'sync_status' => 'ENUM("pending", "synced", "conflict", "resolved") DEFAULT "pending" COMMENT "Synchronization status"',
            'conflict_resolution' => 'TEXT NULL COMMENT "Conflict resolution notes"',
            'device_info' => 'JSON NULL COMMENT "Client device information"',
            'connection_quality' => 'ENUM("excellent", "good", "fair", "poor") DEFAULT "good" COMMENT "Network connection quality"',
            'validation_status' => 'ENUM("valid", "invalid", "pending_review") DEFAULT "valid" COMMENT "Score validation status"',
            'auto_save_sequence' => 'INT DEFAULT 1 COMMENT "Auto-save sequence number"'
        ];
        
        $this->createTable('live_score_updates', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Real-time score updates with conflict tracking'
        ]);
        
        // Add indexes for fast lookups
        $this->addIndex('live_score_updates', 'idx_session_team', 'session_id, team_id');
        $this->addIndex('live_score_updates', 'idx_judge_timestamp', 'judge_id, server_timestamp');
        $this->addIndex('live_score_updates', 'idx_sync_status', 'sync_status');
        $this->addIndex('live_score_updates', 'idx_criteria_team', 'criteria_id, team_id');
        $this->addIndex('live_score_updates', 'idx_server_timestamp', 'server_timestamp');
        $this->addIndex('live_score_updates', 'idx_conflict_detection', 'team_id, criteria_id, sync_status');
        
        // Add foreign key constraints
        $this->addForeignKey('live_score_updates', 'fk_lsu_session', 'session_id', 'live_scoring_sessions', 'id');
        $this->addForeignKey('live_score_updates', 'fk_lsu_team', 'team_id', 'teams', 'id');
        $this->addForeignKey('live_score_updates', 'fk_lsu_judge', 'judge_id', 'judge_profiles', 'id');
        $this->addForeignKey('live_score_updates', 'fk_lsu_criteria', 'criteria_id', 'rubric_criteria', 'id');
        
        echo "Created live_score_updates table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('live_score_updates');
        echo "Dropped live_score_updates table.\n";
    }
}