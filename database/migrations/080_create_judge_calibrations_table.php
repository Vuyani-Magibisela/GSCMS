<?php
// database/migrations/080_create_judge_calibrations_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateJudgeCalibrationsTable extends Migration
{
    public function up()
    {
        $columns = [
            'judge_id' => 'INT NOT NULL COMMENT "Judge being calibrated"',
            'category_id' => 'INT NOT NULL COMMENT "Category for calibration"',
            'calibration_date' => 'DATE NOT NULL COMMENT "Date of calibration"',
            'calibration_type' => 'ENUM("training", "consistency_check", "validation", "remedial") DEFAULT "training" COMMENT "Type of calibration"',
            'reference_score' => 'DECIMAL(10,2) NOT NULL COMMENT "Known correct/expected score"',
            'judge_score' => 'DECIMAL(10,2) NOT NULL COMMENT "Score given by judge"',
            'deviation' => 'DECIMAL(10,2) GENERATED ALWAYS AS (judge_score - reference_score) STORED COMMENT "Deviation from reference"',
            'deviation_percentage' => 'DECIMAL(5,2) GENERATED ALWAYS AS ((ABS(judge_score - reference_score) / reference_score) * 100) STORED COMMENT "Percentage deviation"',
            'passed' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether calibration was passed"',
            'pass_threshold' => 'DECIMAL(5,2) DEFAULT 10.00 COMMENT "Maximum allowed deviation percentage"',
            'calibration_scenario' => 'TEXT NULL COMMENT "Description of scenario used"',
            'rubric_template_id' => 'INT NULL COMMENT "Rubric used for calibration"',
            'notes' => 'TEXT NULL COMMENT "Calibration notes and feedback"',
            'conducted_by' => 'INT NULL COMMENT "Who conducted the calibration"',
            'time_taken_minutes' => 'INT NULL COMMENT "Time taken for calibration"',
            'retry_number' => 'INT DEFAULT 1 COMMENT "Attempt number if retried"'
        ];
        
        $this->createTable('judge_calibrations', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Judge calibration and training records'
        ]);
        
        // Add indexes
        $this->addIndex('judge_calibrations', 'idx_judge_category', 'judge_id, category_id');
        $this->addIndex('judge_calibrations', 'idx_calibration_date', 'calibration_date');
        $this->addIndex('judge_calibrations', 'idx_passed', 'passed');
        $this->addIndex('judge_calibrations', 'idx_deviation', 'deviation_percentage');
        $this->addIndex('judge_calibrations', 'idx_type', 'calibration_type');
        
        echo "Created judge_calibrations table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('judge_calibrations');
        echo "Dropped judge_calibrations table.\n";
    }
}