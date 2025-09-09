<?php

class CreateTournamentBracketsTable_063 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE tournament_brackets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            bracket_type ENUM('winners', 'losers', 'consolation') DEFAULT 'winners',
            round_number INT NOT NULL,
            round_name VARCHAR(100) NULL COMMENT 'Quarter-Finals, Semi-Finals, etc.',
            matches_in_round INT NOT NULL,
            start_datetime DATETIME NULL,
            end_datetime DATETIME NULL,
            status ENUM('pending', 'active', 'completed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            UNIQUE KEY unique_round (tournament_id, bracket_type, round_number),
            INDEX idx_tournament_round (tournament_id, round_number),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created tournament_brackets table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS tournament_brackets");
        echo "Dropped tournament_brackets table\n";
    }
}