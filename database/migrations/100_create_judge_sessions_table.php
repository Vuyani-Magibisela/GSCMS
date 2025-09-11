<?php

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS judge_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            token VARCHAR(64) NOT NULL UNIQUE,
            judge_id INT NOT NULL,
            user_id INT NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            session_data JSON NULL,
            last_activity TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (judge_id) REFERENCES judge_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_judge_expires (judge_id, expires_at),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    
    'down' => "DROP TABLE IF EXISTS judge_sessions;"
];