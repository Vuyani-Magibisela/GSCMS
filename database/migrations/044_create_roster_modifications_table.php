<?php

/**
 * Migration: Create roster_modifications table
 * Team roster change workflow and audit tracking
 */

class CreateRosterModificationsTable 
{
    public function up($pdo) 
    {
        $sql = "CREATE TABLE roster_modifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            modification_type ENUM('add_participant', 'remove_participant', 'substitute_participant', 'change_coach', 'update_role') NOT NULL,
            requested_by INT NOT NULL,
            request_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            current_status ENUM('pending', 'approved', 'rejected', 'implemented', 'cancelled') NOT NULL DEFAULT 'pending',
            approved_by INT NULL,
            approved_date TIMESTAMP NULL,
            implemented_date TIMESTAMP NULL,
            implemented_by INT NULL,
            modification_details JSON NOT NULL,
            reason TEXT NOT NULL,
            impact_assessment TEXT NULL,
            deadline TIMESTAMP NULL,
            priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
            conditions TEXT NULL,
            rejection_reason TEXT NULL,
            rollback_data JSON NULL,
            notification_sent BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (implemented_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_roster_mods_team (team_id),
            INDEX idx_roster_mods_status (current_status),
            INDEX idx_roster_mods_type (modification_type),
            INDEX idx_roster_mods_requested_by (requested_by),
            INDEX idx_roster_mods_deadline (deadline),
            INDEX idx_roster_mods_priority (priority)
        ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "Created roster_modifications table\n";
    }
    
    public function down($pdo) 
    {
        $pdo->exec("DROP TABLE IF EXISTS roster_modifications");
        echo "Dropped roster_modifications table\n";
    }
}