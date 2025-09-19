<?php
// database/migrations/112_create_gameplay_runs_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateGameplayRunsTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT NOT NULL COMMENT "Team performing the run"',
            'session_id' => 'INT NOT NULL COMMENT "Live scoring session ID"',
            'judge_id' => 'INT NOT NULL COMMENT "Judge timing/evaluating the run"',
            'run_number' => 'TINYINT NOT NULL COMMENT "Run number (1-3)"',
            'start_time' => 'TIMESTAMP NULL COMMENT "When the run started"',
            'end_time' => 'TIMESTAMP NULL COMMENT "When the run ended"',
            'completion_time_seconds' => 'INT NULL COMMENT "Total time in seconds for completed run"',
            'run_status' => 'ENUM("not_started", "in_progress", "completed", "failed", "disqualified") DEFAULT "not_started" COMMENT "Status of this run"',
            'mission_completion_data' => 'JSON NULL COMMENT "Data about which mission objectives were completed"',
            'is_fastest_run' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether this is the fastest successful run for the team"',
            'technical_notes' => 'TEXT NULL COMMENT "Judge notes about technical performance"',
            'penalty_seconds' => 'INT DEFAULT 0 COMMENT "Time penalty applied to this run"',
            'bonus_points' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Bonus points awarded for exceptional performance"',
            'mission_score' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Score based on mission completion"',
            'total_run_score' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Total score for this run including bonuses/penalties"'
        ];

        $this->createTable('gameplay_runs', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Individual gameplay runs for timing-based judging'
        ]);

        // Add indexes for efficient queries
        $this->addIndex('gameplay_runs', 'idx_team_session', 'team_id, session_id');
        $this->addIndex('gameplay_runs', 'idx_session', 'session_id');
        $this->addIndex('gameplay_runs', 'idx_judge', 'judge_id');
        $this->addIndex('gameplay_runs', 'idx_run_status', 'run_status');
        $this->addIndex('gameplay_runs', 'idx_fastest_run', 'is_fastest_run');
        $this->addIndex('gameplay_runs', 'idx_completion_time', 'completion_time_seconds');

        // Add unique constraint to prevent duplicate run numbers per team per session
        $this->addUniqueKey('gameplay_runs', 'unique_team_session_run', 'team_id, session_id, run_number');

        // Add foreign key constraints
        $this->addForeignKey('gameplay_runs', 'fk_gr_team', 'team_id', 'teams', 'id');
        $this->addForeignKey('gameplay_runs', 'fk_gr_session', 'session_id', 'live_scoring_sessions', 'id');
        $this->addForeignKey('gameplay_runs', 'fk_gr_judge', 'judge_id', 'users', 'id');

        echo "Created gameplay_runs table with indexes and constraints.\n";
    }

    public function down()
    {
        $this->dropTable('gameplay_runs');
        echo "Dropped gameplay_runs table.\n";
    }
}