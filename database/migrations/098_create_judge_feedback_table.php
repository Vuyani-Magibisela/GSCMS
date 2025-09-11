<?php
// database/migrations/098_create_judge_feedback_table.php

use App\Core\Database;

class CreateJudgeFeedbackTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_feedback (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                feedback_from ENUM('admin', 'peer', 'team', 'self') NOT NULL,
                feedback_from_id INT NULL,
                competition_id INT NULL,
                feedback_type ENUM('positive', 'constructive', 'concern', 'complaint') NOT NULL,
                category VARCHAR(100) NULL,
                feedback_text TEXT NOT NULL,
                rating INT NULL COMMENT '1-5 scale',
                is_anonymous BOOLEAN DEFAULT FALSE,
                requires_action BOOLEAN DEFAULT FALSE,
                action_taken TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_judge_feedback_judge (judge_id),
                INDEX idx_judge_feedback_from (feedback_from),
                INDEX idx_judge_feedback_type (feedback_type),
                INDEX idx_judge_feedback_competition (competition_id),
                INDEX idx_judge_feedback_rating (rating),
                INDEX idx_judge_feedback_action (requires_action)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_feedback');
    }
}