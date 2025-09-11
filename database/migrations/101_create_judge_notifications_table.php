<?php

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS judge_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            judge_id INT NOT NULL,
            notification_type ENUM('assignment', 'schedule_change', 'score_reminder', 'training', 'system', 'feedback') NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            action_url VARCHAR(255) NULL,
            action_text VARCHAR(100) NULL,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (judge_id) REFERENCES judge_profiles(id) ON DELETE CASCADE,
            INDEX idx_judge_unread (judge_id, is_read),
            INDEX idx_priority_created (priority, created_at),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    
    'down' => "DROP TABLE IF EXISTS judge_notifications;"
];