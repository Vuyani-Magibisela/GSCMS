<?php
// database/migrations/085_create_judge_calibration_scores_table.php

use App\Core\Database;

class CreateJudgeCalibrationScoresTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_calibration_scores (
                id INT PRIMARY KEY AUTO_INCREMENT,
                session_id INT NOT NULL,
                scenario_id INT NOT NULL,
                judge_scores JSON NOT NULL COMMENT 'Judge scores for all criteria',
                agreement_score DECIMAL(5,2) NOT NULL COMMENT 'Agreement with expert scores 0-100',
                detailed_analysis JSON NULL COMMENT 'Detailed comparison analysis',
                feedback TEXT NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                review_notes TEXT NULL,
                INDEX idx_calibration_scores_session (session_id),
                INDEX idx_calibration_scores_scenario (scenario_id),
                INDEX idx_calibration_scores_agreement (agreement_score)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_calibration_scores');
    }
}