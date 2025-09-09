<?php
// database/migrations/083_create_judge_calibration_exercises_table.php

use App\Core\Database;

class CreateJudgeCalibrationExercisesTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_calibration_exercises (
                id INT PRIMARY KEY AUTO_INCREMENT,
                category_id INT NOT NULL,
                exercise_name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                difficulty_level ENUM('basic', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
                reference_scenarios JSON NOT NULL COMMENT 'Array of scoring scenarios',
                expert_scores JSON NOT NULL COMMENT 'Expert reference scores for each scenario',
                instructions TEXT NULL,
                estimated_duration INT DEFAULT 30 COMMENT 'Minutes to complete',
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                version VARCHAR(20) DEFAULT '1.0',
                INDEX idx_calibration_exercises_category (category_id),
                INDEX idx_calibration_exercises_difficulty (difficulty_level),
                INDEX idx_calibration_exercises_active (is_active)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_calibration_exercises');
    }
}