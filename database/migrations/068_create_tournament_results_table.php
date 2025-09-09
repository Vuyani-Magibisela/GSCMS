<?php

class CreateTournamentResultsTable_068 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE tournament_results (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            category_id INT NOT NULL,
            placement INT NOT NULL,
            team_id INT NOT NULL,
            team_score DECIMAL(10,2) NULL,
            medal_type ENUM('gold', 'silver', 'bronze', 'none') NULL,
            prize_description TEXT NULL,
            certificate_number VARCHAR(50) NULL,
            published BOOLEAN DEFAULT FALSE,
            published_at TIMESTAMP NULL,
            verified_by INT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_placement (tournament_id, category_id, placement),
            INDEX idx_team_results (team_id, tournament_id),
            INDEX idx_published (published, published_at),
            INDEX idx_category_placement (category_id, placement)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created tournament_results table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS tournament_results");
        echo "Dropped tournament_results table\n";
    }
}