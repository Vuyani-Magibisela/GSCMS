<?php
// database/migrations/076_create_score_details_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateScoreDetailsTable extends Migration
{
    public function up()
    {
        $columns = [
            'score_id' => 'INT NOT NULL COMMENT "Parent score record ID"',
            'criteria_id' => 'INT NOT NULL COMMENT "Rubric criteria being scored"',
            'level_selected' => 'INT NULL COMMENT "Level selected (1-4 for level-based scoring)"',
            'points_awarded' => 'DECIMAL(10,2) NOT NULL COMMENT "Points awarded for this criteria"',
            'max_possible' => 'DECIMAL(10,2) NOT NULL COMMENT "Maximum possible points for this criteria"',
            'percentage_achieved' => 'DECIMAL(5,2) GENERATED ALWAYS AS ((points_awarded / max_possible) * 100) STORED COMMENT "Percentage of max score achieved"',
            'judge_comment' => 'TEXT NULL COMMENT "Judge comment specific to this criteria"',
            'scoring_timestamp' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT "When this criteria was scored"',
            'time_spent_seconds' => 'INT NULL COMMENT "Time spent scoring this criteria"',
            'revision_number' => 'INT DEFAULT 1 COMMENT "Revision number if score was changed"'
        ];
        
        $this->createTable('score_details', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Detailed scores for individual rubric criteria'
        ]);
        
        // Add indexes
        $this->addIndex('score_details', 'idx_score', 'score_id');
        $this->addIndex('score_details', 'idx_criteria', 'criteria_id');
        $this->addIndex('score_details', 'idx_level', 'level_selected');
        $this->addIndex('score_details', 'idx_timestamp', 'scoring_timestamp');
        
        // Add unique constraint to prevent duplicate criteria scores
        $this->addUniqueKey('score_details', 'unique_score_criteria', 'score_id, criteria_id');
        
        echo "Created score_details table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('score_details');
        echo "Dropped score_details table.\n";
    }
}