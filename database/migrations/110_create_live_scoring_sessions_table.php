<?php
// database/migrations/110_create_live_scoring_sessions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateLiveScoringSessionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'competition_id' => 'INT NOT NULL COMMENT "Competition ID"',
            'session_name' => 'VARCHAR(200) NOT NULL COMMENT "Name of the scoring session"',
            'session_type' => 'ENUM("practice", "qualifying", "semifinal", "final") NOT NULL COMMENT "Type of session"',
            'category_id' => 'INT NOT NULL COMMENT "Competition category"',
            'venue_id' => 'INT NULL COMMENT "Venue where scoring takes place"',
            'start_time' => 'TIMESTAMP NOT NULL COMMENT "Session start time"',
            'end_time' => 'TIMESTAMP NULL COMMENT "Session end time"',
            'status' => 'ENUM("scheduled", "active", "paused", "completed") DEFAULT "scheduled" COMMENT "Session status"',
            'live_stream_url' => 'VARCHAR(500) NULL COMMENT "Live stream URL if available"',
            'spectator_access_code' => 'VARCHAR(20) NULL COMMENT "Public access code for spectators"',
            'max_concurrent_judges' => 'INT DEFAULT 10 COMMENT "Maximum judges allowed simultaneously"',
            'scoring_duration_minutes' => 'INT DEFAULT 30 COMMENT "Expected scoring duration per team"',
            'conflict_threshold_percent' => 'DECIMAL(5,2) DEFAULT 15.00 COMMENT "Maximum allowed deviation between judges"',
            'auto_resolve_conflicts' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether to auto-resolve minor conflicts"',
            'head_judge_id' => 'INT NULL COMMENT "Head judge for conflict resolution"',
            'session_metadata' => 'JSON NULL COMMENT "Additional session configuration"'
        ];
        
        $this->createTable('live_scoring_sessions', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Real-time scoring sessions for competitions'
        ]);
        
        // Add indexes
        $this->addIndex('live_scoring_sessions', 'idx_competition', 'competition_id');
        $this->addIndex('live_scoring_sessions', 'idx_category', 'category_id');
        $this->addIndex('live_scoring_sessions', 'idx_status', 'status');
        $this->addIndex('live_scoring_sessions', 'idx_start_time', 'start_time');
        $this->addIndex('live_scoring_sessions', 'idx_session_type', 'session_type');
        
        // Add foreign key constraints
        $this->addForeignKey('live_scoring_sessions', 'fk_lss_competition', 'competition_id', 'competitions', 'id');
        $this->addForeignKey('live_scoring_sessions', 'fk_lss_category', 'category_id', 'categories', 'id');
        $this->addForeignKey('live_scoring_sessions', 'fk_lss_venue', 'venue_id', 'venues', 'id');
        $this->addForeignKey('live_scoring_sessions', 'fk_lss_head_judge', 'head_judge_id', 'judge_profiles', 'id');
        
        echo "Created live_scoring_sessions table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('live_scoring_sessions');
        echo "Dropped live_scoring_sessions table.\n";
    }
}