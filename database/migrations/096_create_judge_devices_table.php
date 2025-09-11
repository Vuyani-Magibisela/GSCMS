<?php
// database/migrations/096_create_judge_devices_table.php

use App\Core\Database;

class CreateJudgeDevicesTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_devices (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                device_id VARCHAR(100) NOT NULL,
                device_name VARCHAR(100) NULL,
                device_type ENUM('desktop', 'tablet', 'mobile') NOT NULL,
                browser VARCHAR(50) NULL,
                os VARCHAR(50) NULL,
                last_used TIMESTAMP NULL,
                trusted BOOLEAN DEFAULT FALSE,
                blocked BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_device (judge_id, device_id),
                INDEX idx_judge_devices_judge (judge_id),
                INDEX idx_judge_devices_device (device_id),
                INDEX idx_judge_devices_trusted (trusted),
                INDEX idx_judge_devices_blocked (blocked)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_devices');
    }
}