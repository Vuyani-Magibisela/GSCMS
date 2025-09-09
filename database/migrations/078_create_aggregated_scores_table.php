<?php
// database/migrations/078_create_aggregated_scores_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateAggregatedScoresTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT NOT NULL COMMENT "Team being scored"',
            'competition_id' => 'INT NULL COMMENT "Competition ID"',
            'tournament_id' => 'INT NULL COMMENT "Tournament ID"',
            'rubric_template_id' => 'INT NOT NULL COMMENT "Rubric template used"',
            'num_judges' => 'INT NOT NULL DEFAULT 0 COMMENT "Number of judges who scored"',
            'aggregation_method' => 'ENUM("average", "median", "trimmed_mean", "highest", "consensus") DEFAULT "average" COMMENT "How scores were combined"',
            'raw_scores' => 'JSON NOT NULL COMMENT "Individual judge scores for reference"',
            'total_score' => 'DECIMAL(10,2) NOT NULL COMMENT "Aggregated total score"',
            'normalized_score' => 'DECIMAL(5,2) NOT NULL COMMENT "Score as percentage"',
            'game_challenge_score' => 'DECIMAL(10,2) NOT NULL COMMENT "Game challenge portion"',
            'research_challenge_score' => 'DECIMAL(10,2) NOT NULL COMMENT "Research challenge portion"',
            'score_variance' => 'DECIMAL(10,2) NULL COMMENT "Variance between judge scores"',
            'confidence_level' => 'DECIMAL(5,2) NULL COMMENT "Statistical confidence in aggregated score"',
            'requires_review' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether score needs manual review"',
            'review_reason' => 'TEXT NULL COMMENT "Why review is required"',
            'outliers_detected' => 'JSON NULL COMMENT "Outlier judge scores identified"',
            'finalized' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether score is finalized"',
            'finalized_by' => 'INT NULL COMMENT "Who finalized the score"',
            'finalized_at' => 'TIMESTAMP NULL COMMENT "When score was finalized"',
            'review_notes' => 'TEXT NULL COMMENT "Review and finalization notes"'
        ];
        
        $this->createTable('aggregated_scores', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Final aggregated scores from multiple judges'
        ]);
        
        // Add indexes
        $this->addIndex('aggregated_scores', 'idx_team_competition', 'team_id, competition_id');
        $this->addIndex('aggregated_scores', 'idx_team_tournament', 'team_id, tournament_id');
        $this->addIndex('aggregated_scores', 'idx_finalized', 'finalized');
        $this->addIndex('aggregated_scores', 'idx_requires_review', 'requires_review');
        $this->addIndex('aggregated_scores', 'idx_total_score', 'total_score DESC');
        
        // Add unique constraint
        $this->addUniqueKey('aggregated_scores', 'unique_team_aggregated', 'team_id, COALESCE(competition_id, 0), COALESCE(tournament_id, 0)');
        
        echo "Created aggregated_scores table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('aggregated_scores');
        echo "Dropped aggregated_scores table.\n";
    }
}