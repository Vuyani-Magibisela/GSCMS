<?php

class CreateRoundRobinStandingsTable_066 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE round_robin_standings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            team_id INT NOT NULL,
            matches_played INT DEFAULT 0,
            wins INT DEFAULT 0,
            draws INT DEFAULT 0,
            losses INT DEFAULT 0,
            points_for DECIMAL(10,2) DEFAULT 0.00,
            points_against DECIMAL(10,2) DEFAULT 0.00,
            point_differential DECIMAL(10,2) GENERATED ALWAYS AS (points_for - points_against) STORED,
            league_points INT DEFAULT 0 COMMENT '3 for win, 1 for draw, 0 for loss',
            head_to_head JSON NULL COMMENT 'Store H2H results for tiebreaking',
            ranking INT NULL,
            qualified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            UNIQUE KEY unique_team (tournament_id, team_id),
            INDEX idx_ranking (tournament_id, league_points DESC, point_differential DESC),
            INDEX idx_qualified (tournament_id, qualified, ranking)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created round_robin_standings table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS round_robin_standings");
        echo "Dropped round_robin_standings table\n";
    }
}