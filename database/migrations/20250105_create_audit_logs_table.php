<?php
// database/migrations/20250105_create_audit_logs_table.php

class CreateAuditLogsTable
{
    public function up($pdo)
    {
        $sql = "
            CREATE TABLE audit_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                changes JSON NULL,
                metadata JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_user (user_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql);
        
        echo "✓ Created audit_logs table\n";
    }
    
    public function down($pdo)
    {
        $pdo->exec("DROP TABLE IF EXISTS audit_logs");
        echo "✓ Dropped audit_logs table\n";
    }
}