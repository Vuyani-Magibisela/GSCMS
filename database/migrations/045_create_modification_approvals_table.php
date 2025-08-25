<?php

/**
 * Migration: Create modification_approvals table
 * Approval workflow tracking for roster modifications
 */

class CreateModificationApprovalsTable 
{
    public function up($pdo) 
    {
        $sql = "CREATE TABLE modification_approvals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            modification_id INT NOT NULL,
            approver_role ENUM('school_coordinator', 'admin', 'competition_director', 'system') NOT NULL,
            approver_user_id INT NULL,
            approval_status ENUM('pending', 'approved', 'rejected', 'conditionally_approved') NOT NULL DEFAULT 'pending',
            approval_date TIMESTAMP NULL,
            conditions TEXT NULL,
            comments TEXT NULL,
            approval_order INT NOT NULL DEFAULT 1,
            required_approval BOOLEAN NOT NULL DEFAULT TRUE,
            automatic_approval BOOLEAN NOT NULL DEFAULT FALSE,
            deadline TIMESTAMP NULL,
            notification_sent BOOLEAN NOT NULL DEFAULT FALSE,
            reminder_count INT NOT NULL DEFAULT 0,
            last_reminder_sent TIMESTAMP NULL,
            approval_criteria JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            
            FOREIGN KEY (modification_id) REFERENCES roster_modifications(id) ON DELETE CASCADE,
            FOREIGN KEY (approver_user_id) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY uk_modification_approver (modification_id, approver_role, approver_user_id),
            INDEX idx_approvals_modification (modification_id),
            INDEX idx_approvals_status (approval_status),
            INDEX idx_approvals_role (approver_role),
            INDEX idx_approvals_user (approver_user_id),
            INDEX idx_approvals_deadline (deadline),
            INDEX idx_approvals_order (approval_order)
        ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "Created modification_approvals table\n";
    }
    
    public function down($pdo) 
    {
        $pdo->exec("DROP TABLE IF EXISTS modification_approvals");
        echo "Dropped modification_approvals table\n";
    }
}