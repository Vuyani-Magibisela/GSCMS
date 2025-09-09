<?php
// database/migrations/075_create_scores_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateScoresTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT NOT NULL COMMENT "Team being scored"',
            'competition_id' => 'INT NULL COMMENT "Competition ID (from competitions table)"',
            'tournament_id' => 'INT NULL COMMENT "Tournament ID (from tournaments table)"',
            'rubric_template_id' => 'INT NOT NULL COMMENT "Rubric template used for scoring"',
            'judge_id' => 'INT NOT NULL COMMENT "Judge who provided the score"',
            'total_score' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Total calculated score"',
            'normalized_score' => 'DECIMAL(5,2) DEFAULT 0.00 COMMENT "Score as percentage (0-100)"',
            'game_challenge_score' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Game challenge portion score"',
            'research_challenge_score' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Research challenge portion score"',
            'bonus_points' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Bonus points awarded"',
            'penalty_points' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "Penalty points deducted"',
            'final_score' => 'DECIMAL(10,2) GENERATED ALWAYS AS (total_score + bonus_points - penalty_points) STORED COMMENT "Final calculated score"',
            'scoring_status' => 'ENUM("draft", "in_progress", "submitted", "validated", "disputed", "final") DEFAULT "draft" COMMENT "Status of the scoring"',
            'submitted_at' => 'TIMESTAMP NULL COMMENT "When score was submitted"',
            'validated_at' => 'TIMESTAMP NULL COMMENT "When score was validated"',
            'validated_by' => 'INT NULL COMMENT "Who validated the score"',
            'judge_notes' => 'TEXT NULL COMMENT "General judge comments and observations"',
            'scoring_duration_minutes' => 'INT NULL COMMENT "Time taken to complete scoring"',
            'device_info' => 'JSON NULL COMMENT "Information about scoring device/browser"'
        ];
        
        $this->createTable('scores', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Individual judge scores for teams'
        ]);
        
        // Add indexes
        $this->addIndex('scores', 'idx_team_competition', 'team_id, competition_id');
        $this->addIndex('scores', 'idx_team_tournament', 'team_id, tournament_id');
        $this->addIndex('scores', 'idx_judge', 'judge_id');
        $this->addIndex('scores', 'idx_status', 'scoring_status');
        $this->addIndex('scores', 'idx_submitted', 'submitted_at');
        $this->addIndex('scores', 'idx_rubric', 'rubric_template_id');
        
        // Add unique constraint to prevent duplicate scores
        $this->addUniqueKey('scores', 'unique_team_judge_competition', 'team_id, judge_id, COALESCE(competition_id, 0), COALESCE(tournament_id, 0)');
        
        echo "Created scores table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('scores');
        echo "Dropped scores table.\n";
    }
}