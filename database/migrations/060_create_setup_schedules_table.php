<?php
// database/migrations/060_create_setup_schedules_table.php

class CreateSetupSchedulesTable
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
        echo "Creating setup_schedules table...\n";
        
        $sql = "CREATE TABLE `setup_schedules` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `event_id` INT NOT NULL,
            `venue_id` INT NOT NULL,
            `schedule_type` ENUM('setup', 'breakdown') NOT NULL,
            `scheduled_date` DATE NOT NULL,
            `scheduled_start_time` TIME NOT NULL,
            `scheduled_end_time` TIME NOT NULL,
            `actual_start_time` TIME NULL,
            `actual_end_time` TIME NULL,
            `team_leader` INT NOT NULL,
            `team_size` INT NOT NULL DEFAULT 1,
            `estimated_hours` DECIMAL(4,2) NOT NULL,
            `actual_hours` DECIMAL(4,2) NULL,
            `status` ENUM('scheduled', 'in_progress', 'completed', 'delayed') DEFAULT 'scheduled',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`event_id`) REFERENCES `calendar_events`(`id`),
            FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`),
            FOREIGN KEY (`team_leader`) REFERENCES `users`(`id`),
            INDEX `idx_date_type` (`scheduled_date`, `schedule_type`),
            INDEX `idx_venue_date` (`venue_id`, `scheduled_date`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Setup schedules table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping setup_schedules table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `setup_schedules`");
        echo "Setup schedules table dropped.\n";
    }
}