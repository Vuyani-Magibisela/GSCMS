<?php
// database/migrations/100_enhance_judge_profiles_table.php

use App\Core\Database;

class EnhanceJudgeProfilesTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        // Add missing columns to judge_profiles table
        $sql = "
            ALTER TABLE judge_profiles 
            ADD COLUMN IF NOT EXISTS user_id INT NOT NULL AFTER id,
            ADD COLUMN IF NOT EXISTS judge_code VARCHAR(20) UNIQUE NULL AFTER user_id,
            ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER judge_code,
            ADD COLUMN IF NOT EXISTS judge_type ENUM('coordinator', 'adjudicator', 'technical', 'volunteer', 'industry') NULL AFTER organization_id,
            ADD COLUMN IF NOT EXISTS expertise_areas JSON NULL AFTER judge_type,
            ADD COLUMN IF NOT EXISTS categories_qualified JSON NULL AFTER expertise_areas,
            ADD COLUMN IF NOT EXISTS years_experience INT DEFAULT 0 AFTER experience_level,
            ADD COLUMN IF NOT EXISTS professional_title VARCHAR(200) NULL AFTER years_experience,
            ADD COLUMN IF NOT EXISTS professional_bio TEXT NULL AFTER professional_title,
            ADD COLUMN IF NOT EXISTS linkedin_profile VARCHAR(255) NULL AFTER professional_bio,
            ADD COLUMN IF NOT EXISTS availability JSON NULL AFTER certifications,
            ADD COLUMN IF NOT EXISTS preferred_venues JSON NULL AFTER availability,
            ADD COLUMN IF NOT EXISTS special_requirements TEXT NULL AFTER preferred_venues,
            ADD COLUMN IF NOT EXISTS status ENUM('pending', 'active', 'inactive', 'blacklisted') DEFAULT 'pending' AFTER special_requirements,
            ADD COLUMN IF NOT EXISTS onboarding_completed BOOLEAN DEFAULT FALSE AFTER status,
            ADD COLUMN IF NOT EXISTS background_check_status ENUM('not_required', 'pending', 'cleared', 'failed') DEFAULT 'not_required' AFTER onboarding_completed,
            ADD COLUMN IF NOT EXISTS background_check_date DATE NULL AFTER background_check_status
        ";
        
        $db->execute($sql);
        
        // Add indexes
        $indexSql = "
            ALTER TABLE judge_profiles
            ADD INDEX IF NOT EXISTS idx_judge_profiles_user (user_id),
            ADD INDEX IF NOT EXISTS idx_judge_profiles_code (judge_code),
            ADD INDEX IF NOT EXISTS idx_judge_profiles_organization (organization_id),
            ADD INDEX IF NOT EXISTS idx_judge_profiles_type (judge_type),
            ADD INDEX IF NOT EXISTS idx_judge_profiles_status_enhanced (status)
        ";
        
        return $db->execute($indexSql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        
        $sql = "
            ALTER TABLE judge_profiles 
            DROP COLUMN IF EXISTS user_id,
            DROP COLUMN IF EXISTS judge_code,
            DROP COLUMN IF EXISTS organization_id,
            DROP COLUMN IF EXISTS judge_type,
            DROP COLUMN IF EXISTS expertise_areas,
            DROP COLUMN IF EXISTS categories_qualified,
            DROP COLUMN IF EXISTS years_experience,
            DROP COLUMN IF EXISTS professional_title,
            DROP COLUMN IF EXISTS professional_bio,
            DROP COLUMN IF EXISTS linkedin_profile,
            DROP COLUMN IF EXISTS availability,
            DROP COLUMN IF EXISTS preferred_venues,
            DROP COLUMN IF EXISTS special_requirements,
            DROP COLUMN IF EXISTS status,
            DROP COLUMN IF EXISTS onboarding_completed,
            DROP COLUMN IF EXISTS background_check_status,
            DROP COLUMN IF EXISTS background_check_date
        ";
        
        return $db->execute($sql);
    }
}