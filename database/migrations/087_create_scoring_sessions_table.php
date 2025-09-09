<?php
// database/migrations/087_create_scoring_sessions_table.php

use App\Core\Database;

class CreateScoringSessionsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS scoring_sessions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                match_id INT NOT NULL,
                judge_id INT NOT NULL,
                tournament_id INT NOT NULL,
                session_status ENUM('pending', 'in_progress', 'completed', 'abandoned') DEFAULT 'pending',
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                last_activity TIMESTAMP NULL,
                session_data JSON NULL COMMENT 'Session state and progress data',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_scoring_sessions_match (match_id),
                INDEX idx_scoring_sessions_judge (judge_id),
                INDEX idx_scoring_sessions_tournament (tournament_id),
                INDEX idx_scoring_sessions_status (session_status),
                UNIQUE KEY unique_judge_match (judge_id, match_id)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS scoring_sessions');
    }
}