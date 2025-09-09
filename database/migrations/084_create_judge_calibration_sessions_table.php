<?php
// database/migrations/084_create_judge_calibration_sessions_table.php

use App\Core\Database;

class CreateJudgeCalibrationSessionsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_calibration_sessions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                exercise_id INT NOT NULL,
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                status ENUM('in_progress', 'completed', 'cancelled', 'expired') DEFAULT 'in_progress',
                final_score DECIMAL(5,2) NULL COMMENT 'Overall calibration score 0-100',
                calibration_level ENUM('needs_improvement', 'acceptable', 'good', 'excellent') NULL,
                time_taken INT NULL COMMENT 'Minutes taken to complete',
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_calibration_sessions_judge (judge_id),
                INDEX idx_calibration_sessions_exercise (exercise_id),
                INDEX idx_calibration_sessions_status (status),
                INDEX idx_calibration_sessions_completed (completed_at)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_calibration_sessions');
    }
}