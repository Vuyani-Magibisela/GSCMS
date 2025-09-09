<?php
// database/migrations/088_create_tournament_scoring_status_table.php

use App\Core\Database;

class CreateTournamentScoringStatusTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS tournament_scoring_status (
                id INT PRIMARY KEY AUTO_INCREMENT,
                tournament_id INT NOT NULL,
                total_matches INT NOT NULL DEFAULT 0,
                completed_matches INT NOT NULL DEFAULT 0,
                matches_in_progress INT NOT NULL DEFAULT 0,
                judges_assigned INT NOT NULL DEFAULT 0,
                scores_submitted INT NOT NULL DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status ENUM('initialized', 'active', 'paused', 'completed') DEFAULT 'initialized',
                status_details JSON NULL,
                UNIQUE KEY unique_tournament_status (tournament_id),
                INDEX idx_tournament_scoring_status (status),
                INDEX idx_tournament_scoring_updated (last_updated)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS tournament_scoring_status');
    }
}