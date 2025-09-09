<?php

class CreateTournamentMatchesTable_064 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE tournament_matches (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            bracket_id INT NOT NULL,
            match_number INT NOT NULL,
            match_position INT NOT NULL COMMENT 'Position in bracket',
            team1_id INT NULL,
            team2_id INT NULL,
            team1_seed INT NULL,
            team2_seed INT NULL,
            team1_score DECIMAL(10,2) NULL,
            team2_score DECIMAL(10,2) NULL,
            winner_team_id INT NULL,
            loser_team_id INT NULL,
            next_match_id INT NULL COMMENT 'Where winner advances to',
            consolation_match_id INT NULL COMMENT 'Where loser goes (if applicable)',
            venue_id INT NULL,
            table_number VARCHAR(10) NULL,
            scheduled_time DATETIME NULL,
            actual_start_time DATETIME NULL,
            actual_end_time DATETIME NULL,
            match_status ENUM('pending', 'ready', 'in_progress', 'completed', 'forfeit', 'bye') DEFAULT 'pending',
            forfeit_reason TEXT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (bracket_id) REFERENCES tournament_brackets(id) ON DELETE CASCADE,
            FOREIGN KEY (team1_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (team2_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (winner_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (loser_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (next_match_id) REFERENCES tournament_matches(id) ON DELETE SET NULL,
            FOREIGN KEY (consolation_match_id) REFERENCES tournament_matches(id) ON DELETE SET NULL,
            FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
            INDEX idx_bracket_match (bracket_id, match_number),
            INDEX idx_schedule (scheduled_time, match_status),
            INDEX idx_tournament_status (tournament_id, match_status),
            INDEX idx_teams (team1_id, team2_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created tournament_matches table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS tournament_matches");
        echo "Dropped tournament_matches table\n";
    }
}