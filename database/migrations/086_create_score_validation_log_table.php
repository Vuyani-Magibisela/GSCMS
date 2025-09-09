<?php
// database/migrations/086_create_score_validation_log_table.php

use App\Core\Database;

class CreateScoreValidationLogTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS score_validation_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                team_id INT NULL,
                judge_id INT NULL,
                category_id INT NULL,
                validation_result JSON NOT NULL COMMENT 'Full validation results including errors, warnings, flags',
                confidence_score DECIMAL(5,2) NULL COMMENT 'Validation confidence score 0-100',
                requires_review BOOLEAN DEFAULT FALSE,
                reviewed_by INT NULL,
                reviewed_at TIMESTAMP NULL,
                resolution_notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_validation_log_team (team_id),
                INDEX idx_validation_log_judge (judge_id),
                INDEX idx_validation_log_category (category_id),
                INDEX idx_validation_log_review (requires_review),
                INDEX idx_validation_log_confidence (confidence_score),
                INDEX idx_validation_log_created (created_at)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS score_validation_log');
    }
}