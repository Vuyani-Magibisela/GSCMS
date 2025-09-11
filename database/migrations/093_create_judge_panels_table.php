<?php
// database/migrations/093_create_judge_panels_table.php

use App\Core\Database;

class CreateJudgePanelsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_panels (
                id INT PRIMARY KEY AUTO_INCREMENT,
                panel_name VARCHAR(100) NOT NULL,
                competition_id INT NOT NULL,
                category_id INT NOT NULL,
                head_judge_id INT NOT NULL,
                panel_members JSON NOT NULL COMMENT 'Array of judge IDs',
                panel_type ENUM('standard', 'technical', 'finals', 'special') DEFAULT 'standard',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_judge_panels_competition (competition_id),
                INDEX idx_judge_panels_category (category_id),
                INDEX idx_judge_panels_head (head_judge_id),
                INDEX idx_judge_panels_type (panel_type),
                INDEX idx_judge_panels_active (is_active)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_panels');
    }
}