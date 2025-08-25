<?php

/**
 * Migration: Create team_coaches table
 * Coach assignment and qualification management
 */

class CreateTeamCoachesTable 
{
    public function up($pdo) 
    {
        $sql = "CREATE TABLE team_coaches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            coach_user_id INT NOT NULL,
            coach_role ENUM('primary', 'secondary', 'assistant', 'mentor') NOT NULL DEFAULT 'primary',
            status ENUM('active', 'inactive', 'removed', 'pending_approval') NOT NULL DEFAULT 'pending_approval',
            qualification_status ENUM('qualified', 'pending', 'unqualified', 'expired') NOT NULL DEFAULT 'pending',
            training_completed BOOLEAN NOT NULL DEFAULT FALSE,
            training_completion_date DATE NULL,
            certification_expiry DATE NULL,
            background_check_status ENUM('verified', 'pending', 'failed', 'expired') NOT NULL DEFAULT 'pending',
            assigned_date DATE NOT NULL,
            removed_date DATE NULL,
            removal_reason VARCHAR(255) NULL,
            specialization VARCHAR(100) NULL,
            experience_years INT DEFAULT 0,
            previous_competitions INT DEFAULT 0,
            performance_rating DECIMAL(3,2) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (coach_user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uk_team_coach_role (team_id, coach_role, status),
            INDEX idx_team_coaches_team (team_id),
            INDEX idx_team_coaches_user (coach_user_id),
            INDEX idx_team_coaches_role (coach_role),
            INDEX idx_team_coaches_status (status),
            INDEX idx_team_coaches_qualification (qualification_status)
        ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "Created team_coaches table\n";
    }
    
    public function down($pdo) 
    {
        $pdo->exec("DROP TABLE IF EXISTS team_coaches");
        echo "Dropped team_coaches table\n";
    }
}