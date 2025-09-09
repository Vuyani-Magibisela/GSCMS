<?php

class CreateResultsPublicationsTable_069 {
    
    public function up($db) {
        $sql = "
        CREATE TABLE results_publications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tournament_id INT NOT NULL,
            publication_type ENUM('preliminary', 'official', 'amended') NOT NULL,
            publication_channel ENUM('website', 'email', 'social', 'print', 'all') NOT NULL,
            published_by INT NOT NULL,
            publication_url VARCHAR(255) NULL,
            document_path VARCHAR(255) NULL,
            recipients_count INT DEFAULT 0,
            publication_status ENUM('draft', 'scheduled', 'published', 'retracted') DEFAULT 'draft',
            scheduled_for DATETIME NULL,
            published_at TIMESTAMP NULL,
            retracted_at TIMESTAMP NULL,
            retraction_reason TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_publication_status (publication_status, scheduled_for),
            INDEX idx_tournament_type (tournament_id, publication_type),
            INDEX idx_published_date (published_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($sql);
        echo "Created results_publications table\n";
    }
    
    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS results_publications");
        echo "Dropped results_publications table\n";
    }
}