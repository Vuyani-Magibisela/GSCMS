<?php
// database/migrations/092_create_judge_competition_assignments_table.php

use App\Core\Database;

class CreateJudgeCompetitionAssignmentsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_competition_assignments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                competition_id INT NOT NULL,
                phase_id INT NOT NULL,
                category_id INT NULL,
                venue_id INT NULL,
                assignment_role ENUM('head_judge', 'primary', 'secondary', 'backup', 'observer') NOT NULL,
                table_numbers JSON NULL COMMENT 'Multiple tables possible',
                session_date DATE NOT NULL,
                session_time TIME NULL,
                check_in_time TIME NULL,
                check_out_time TIME NULL,
                teams_assigned INT DEFAULT 0,
                teams_completed INT DEFAULT 0,
                assignment_status ENUM('assigned', 'confirmed', 'declined', 'completed', 'no_show') DEFAULT 'assigned',
                confirmation_token VARCHAR(100) NULL,
                confirmed_at TIMESTAMP NULL,
                declined_reason TEXT NULL,
                performance_rating INT NULL COMMENT '1-5 scale',
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_assignment (judge_id, competition_id, session_date),
                INDEX idx_judge_comp_assignments_judge (judge_id),
                INDEX idx_judge_comp_assignments_competition (competition_id),
                INDEX idx_judge_comp_assignments_date (session_date),
                INDEX idx_judge_comp_assignments_status (assignment_status),
                INDEX idx_judge_comp_assignments_role (assignment_role)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_competition_assignments');
    }
}