<?php
// database/migrations/082_create_judge_profiles_table.php

use App\Core\Database;

class CreateJudgeProfilesTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_profiles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                experience_level ENUM('novice', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
                specialty_categories TEXT NULL COMMENT 'Comma-separated category IDs',
                max_assignments_per_day INT DEFAULT 10,
                preferred_categories TEXT NULL COMMENT 'Comma-separated category IDs',
                availability_notes TEXT NULL,
                bio TEXT NULL,
                certifications TEXT NULL COMMENT 'JSON array of certifications',
                languages_spoken VARCHAR(255) DEFAULT 'English',
                contact_preferences JSON NULL,
                emergency_contact VARCHAR(255) NULL,
                dietary_restrictions TEXT NULL,
                accessibility_needs TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_judge_profiles_judge_id (judge_id),
                INDEX idx_judge_profiles_experience (experience_level),
                INDEX idx_judge_profiles_specialty (specialty_categories(50))
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_profiles');
    }
}