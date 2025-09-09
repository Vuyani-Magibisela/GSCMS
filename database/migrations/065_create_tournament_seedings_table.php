<?php

class CreateTournamentSeedingsTable_065 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE tournament_seedings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            team_id INT NOT NULL,
            seed_number INT NOT NULL,
            seeding_score DECIMAL(10,2) NULL,
            previous_phase_rank INT NULL,
            district_rank INT NULL,
            elo_rating INT DEFAULT 1200,
            matches_played INT DEFAULT 0,
            matches_won INT DEFAULT 0,
            matches_lost INT DEFAULT 0,
            points_for DECIMAL(10,2) DEFAULT 0.00,
            points_against DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            UNIQUE KEY unique_seed (tournament_id, team_id),
            UNIQUE KEY unique_seed_number (tournament_id, seed_number),
            INDEX idx_tournament_seed (tournament_id, seed_number),
            INDEX idx_seeding_score (seeding_score DESC),
            INDEX idx_elo_rating (elo_rating DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created tournament_seedings table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS tournament_seedings");
        echo "Dropped tournament_seedings table\n";
    }
}