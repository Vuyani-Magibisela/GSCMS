<?php
// database/migrations/094_create_judge_auth_table.php

use App\Core\Database;

class CreateJudgeAuthTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_auth (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL UNIQUE,
                auth_method ENUM('password', 'pin', 'biometric', 'two_factor') DEFAULT 'password',
                pin_code VARCHAR(10) NULL,
                two_factor_secret VARCHAR(100) NULL,
                two_factor_enabled BOOLEAN DEFAULT FALSE,
                biometric_data TEXT NULL,
                last_login TIMESTAMP NULL,
                last_login_ip VARCHAR(45) NULL,
                failed_attempts INT DEFAULT 0,
                locked_until TIMESTAMP NULL,
                password_changed_at TIMESTAMP NULL,
                require_password_change BOOLEAN DEFAULT FALSE,
                session_timeout_minutes INT DEFAULT 120,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_judge_auth_judge (judge_id),
                INDEX idx_judge_auth_method (auth_method),
                INDEX idx_judge_auth_locked (locked_until),
                INDEX idx_judge_auth_2fa (two_factor_enabled)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_auth');
    }
}