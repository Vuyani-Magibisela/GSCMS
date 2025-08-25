<?php

/**
 * Migration: Create team_participants table
 * Advanced participant role and status tracking within teams
 */

class CreateTeamParticipantsTable 
{
    public function up($pdo) 
    {
        $sql = "CREATE TABLE team_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            participant_id INT NOT NULL,
            role ENUM('team_leader', 'programmer', 'builder', 'designer', 'researcher', 'regular') NOT NULL DEFAULT 'regular',
            status ENUM('active', 'inactive', 'removed', 'suspended', 'substitute') NOT NULL DEFAULT 'active',
            joined_date DATE NOT NULL,
            removed_date DATE NULL,
            removal_reason ENUM('voluntary', 'academic', 'disciplinary', 'medical', 'transfer', 'other') NULL,
            specialization VARCHAR(100) NULL,
            performance_notes TEXT NULL,
            eligibility_status ENUM('eligible', 'pending', 'ineligible') NOT NULL DEFAULT 'pending',
            documents_complete BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
            UNIQUE KEY uk_team_participant (team_id, participant_id),
            INDEX idx_team_participants_team (team_id),
            INDEX idx_team_participants_participant (participant_id),
            INDEX idx_team_participants_status (status),
            INDEX idx_team_participants_role (role),
            INDEX idx_team_participants_eligibility (eligibility_status)
        ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "Created team_participants table\n";
    }
    
    public function down($pdo) 
    {
        $pdo->exec("DROP TABLE IF EXISTS team_participants");
        echo "Dropped team_participants table\n";
    }
}