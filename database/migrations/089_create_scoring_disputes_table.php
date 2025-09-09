<?php
// database/migrations/089_create_scoring_disputes_table.php

use App\Core\Database;

class CreateScoringDisputesTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS scoring_disputes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                match_id INT NOT NULL,
                disputed_by INT NOT NULL COMMENT 'User ID who filed the dispute',
                dispute_type ENUM('scoring_error', 'judge_bias', 'technical_issue', 'rule_violation', 'other') NOT NULL,
                description TEXT NOT NULL,
                evidence_files JSON NULL COMMENT 'Array of uploaded evidence files',
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                status ENUM('pending', 'under_review', 'resolved', 'dismissed', 'escalated') DEFAULT 'pending',
                assigned_to INT NULL COMMENT 'Admin/reviewer assigned to handle dispute',
                resolution TEXT NULL,
                resolved_by INT NULL,
                resolved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_scoring_disputes_match (match_id),
                INDEX idx_scoring_disputes_by (disputed_by),
                INDEX idx_scoring_disputes_type (dispute_type),
                INDEX idx_scoring_disputes_status (status),
                INDEX idx_scoring_disputes_priority (priority),
                INDEX idx_scoring_disputes_assigned (assigned_to)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS scoring_disputes');
    }
}