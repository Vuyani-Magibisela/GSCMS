<?php
// database/migrations/061_create_setup_tasks_table.php

class CreateSetupTasksTable
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    public function up()
    {
        echo "Creating setup_tasks table...\n";
        
        $sql = "CREATE TABLE `setup_tasks` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `schedule_id` INT NOT NULL,
            `task_name` VARCHAR(200) NOT NULL,
            `task_category` ENUM('furniture', 'technology', 'signage', 'catering', 
                                 'registration', 'competition_area', 'safety') NOT NULL,
            `description` TEXT NULL,
            `assigned_to` INT NULL,
            `estimated_duration_minutes` INT NOT NULL,
            `actual_duration_minutes` INT NULL,
            `dependencies` JSON NULL,
            `priority` ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
            `status` ENUM('pending', 'in_progress', 'completed', 'blocked') DEFAULT 'pending',
            `completed_at` TIMESTAMP NULL,
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`schedule_id`) REFERENCES `setup_schedules`(`id`),
            FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`),
            INDEX `idx_schedule_status` (`schedule_id`, `status`),
            INDEX `idx_priority_status` (`priority`, `status`),
            INDEX `idx_assigned` (`assigned_to`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Setup tasks table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping setup_tasks table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `setup_tasks`");
        echo "Setup tasks table dropped.\n";
    }
}