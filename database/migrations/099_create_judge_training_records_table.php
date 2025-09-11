<?php
// database/migrations/099_create_judge_training_records_table.php

use App\Core\Database;

class CreateJudgeTrainingRecordsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_training_records (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                training_type ENUM('onboarding', 'refresher', 'advanced', 'category_specific') NOT NULL,
                training_name VARCHAR(200) NOT NULL,
                provider VARCHAR(200) NULL,
                completion_date DATE NOT NULL,
                score DECIMAL(5,2) NULL,
                passed BOOLEAN DEFAULT TRUE,
                certificate_url VARCHAR(255) NULL,
                expiry_date DATE NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_judge_training_judge (judge_id),
                INDEX idx_judge_training_type (training_type),
                INDEX idx_judge_training_expiry (expiry_date),
                INDEX idx_judge_training_completion (completion_date),
                INDEX idx_judge_training_passed (passed)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_training_records');
    }
}