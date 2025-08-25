<?php

/**
 * Migration: Create team_compositions table
 * Team composition validation and management
 */

class CreateTeamCompositionsTable 
{
    public function up($pdo) 
    {
        $sql = "CREATE TABLE team_compositions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            max_participants INT NOT NULL DEFAULT 4,
            current_participant_count INT NOT NULL DEFAULT 0,
            composition_status ENUM('incomplete', 'complete', 'oversize', 'invalid') NOT NULL DEFAULT 'incomplete',
            last_validated_at TIMESTAMP NULL,
            validation_errors JSON NULL,
            category_specific_rules JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            INDEX idx_team_compositions_team_id (team_id),
            INDEX idx_team_compositions_status (composition_status),
            INDEX idx_team_compositions_validated (last_validated_at)
        ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "Created team_compositions table\n";
    }
    
    public function down($pdo) 
    {
        $pdo->exec("DROP TABLE IF EXISTS team_compositions");
        echo "Dropped team_compositions table\n";
    }
}