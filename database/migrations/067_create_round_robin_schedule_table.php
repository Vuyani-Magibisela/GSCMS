<?php

class CreateRoundRobinScheduleTable_067 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE round_robin_schedule (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            round_number INT NOT NULL,
            match_day DATE NOT NULL,
            team1_id INT NOT NULL,
            team2_id INT NOT NULL,
            venue_id INT NULL,
            time_slot TIME NULL,
            match_id INT NULL,
            is_played BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (team1_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (team2_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
            FOREIGN KEY (match_id) REFERENCES tournament_matches(id) ON DELETE SET NULL,
            INDEX idx_round (tournament_id, round_number),
            INDEX idx_day (tournament_id, match_day),
            INDEX idx_teams (team1_id, team2_id),
            INDEX idx_played (is_played, match_day)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created round_robin_schedule table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS round_robin_schedule");
        echo "Dropped round_robin_schedule table\n";
    }
}