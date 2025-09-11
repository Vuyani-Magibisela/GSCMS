<?php
// database/migrations/091_create_judge_qualifications_table.php

use App\Core\Database;

class CreateJudgeQualificationsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_qualifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                qualification_type ENUM('training', 'certification', 'workshop', 'competition') NOT NULL,
                qualification_name VARCHAR(200) NOT NULL,
                issuing_body VARCHAR(200) NULL,
                issue_date DATE NOT NULL,
                expiry_date DATE NULL,
                certificate_number VARCHAR(100) NULL,
                document_path VARCHAR(255) NULL,
                verified BOOLEAN DEFAULT FALSE,
                verified_by INT NULL,
                verified_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_judge_qualifications_judge (judge_id),
                INDEX idx_judge_qualifications_type (qualification_type),
                INDEX idx_judge_qualifications_expiry (expiry_date),
                INDEX idx_judge_qualifications_verified (verified)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_qualifications');
    }
}