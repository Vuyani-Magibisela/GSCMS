<?php

class CreateTournamentsTable_062 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE tournaments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_name VARCHAR(200) NOT NULL,
            competition_phase_id INT NOT NULL,
            tournament_type ENUM('elimination', 'round_robin', 'swiss', 'double_elimination') NOT NULL,
            category_id INT NOT NULL,
            venue_id INT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            max_teams INT NOT NULL,
            current_teams INT DEFAULT 0,
            rounds_total INT NULL,
            current_round INT DEFAULT 0,
            seeding_method ENUM('random', 'performance', 'regional', 'manual') DEFAULT 'performance',
            advancement_count INT NOT NULL COMMENT 'How many teams advance',
            status ENUM('setup', 'registration', 'seeding', 'active', 'completed') DEFAULT 'setup',
            winner_team_id INT NULL,
            second_place_id INT NULL,
            third_place_id INT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (competition_phase_id) REFERENCES competition_phases(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
            FOREIGN KEY (winner_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (second_place_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (third_place_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_phase_category (competition_phase_id, category_id),
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created tournaments table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS tournaments");
        echo "Dropped tournaments table\n";
    }
}