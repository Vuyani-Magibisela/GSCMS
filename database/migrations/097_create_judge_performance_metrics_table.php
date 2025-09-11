<?php
// database/migrations/097_create_judge_performance_metrics_table.php

use App\Core\Database;

class CreateJudgePerformanceMetricsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS judge_performance_metrics (
                id INT PRIMARY KEY AUTO_INCREMENT,
                judge_id INT NOT NULL,
                competition_id INT NOT NULL,
                metric_period ENUM('session', 'day', 'competition', 'season') NOT NULL,
                teams_scored INT DEFAULT 0,
                average_scoring_time DECIMAL(10,2) NULL COMMENT 'minutes',
                consistency_score DECIMAL(5,2) NULL COMMENT '0-100',
                deviation_from_mean DECIMAL(5,2) NULL COMMENT 'percentage',
                conflicts_raised INT DEFAULT 0,
                conflicts_resolved INT DEFAULT 0,
                on_time_rate DECIMAL(5,2) NULL COMMENT 'percentage',
                completion_rate DECIMAL(5,2) NULL COMMENT 'percentage',
                peer_rating DECIMAL(3,2) NULL COMMENT '1-5 scale',
                admin_rating DECIMAL(3,2) NULL COMMENT '1-5 scale',
                feedback_count INT DEFAULT 0,
                calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_judge_performance_judge (judge_id),
                INDEX idx_judge_performance_competition (competition_id),
                INDEX idx_judge_performance_period (metric_period),
                INDEX idx_judge_performance_consistency (consistency_score),
                INDEX idx_judge_performance_calculated (calculated_at)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS judge_performance_metrics');
    }
}