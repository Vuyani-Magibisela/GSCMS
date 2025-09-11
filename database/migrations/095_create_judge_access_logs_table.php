<?php
// database/migrations/095_create_judge_access_logs_table.php

use App\Core\Database;

class CreateJudgeAccessLogsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_access_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                action ENUM('login', 'logout', 'score_submit', 'score_edit', 'profile_update') NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                device_type ENUM('desktop', 'tablet', 'mobile') NULL,
                location VARCHAR(255) NULL,
                success BOOLEAN DEFAULT TRUE,
                failure_reason VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_judge_access_logs_judge (judge_id),
                INDEX idx_judge_access_logs_action (action),
                INDEX idx_judge_access_logs_date (created_at),
                INDEX idx_judge_access_logs_success (success),
                INDEX idx_judge_access_logs_ip (ip_address)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_access_logs');
    }
}