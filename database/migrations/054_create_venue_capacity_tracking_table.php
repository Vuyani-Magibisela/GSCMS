<?php
// database/migrations/054_create_venue_capacity_tracking_table.php

class CreateVenueCapacityTrackingTable
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
        echo "Creating venue_capacity_tracking table...\n";
        
        $sql = "CREATE TABLE `venue_capacity_tracking` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `venue_id` INT NOT NULL,
            `space_id` INT NULL,
            `event_id` INT NOT NULL,
            `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `current_occupancy` INT NOT NULL DEFAULT 0,
            `max_capacity` INT NOT NULL,
            `entrance_count` INT DEFAULT 0,
            `exit_count` INT DEFAULT 0,
            `occupancy_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('normal', 'approaching_capacity', 'at_capacity', 'over_capacity') DEFAULT 'normal',
            `alert_triggered` BOOLEAN DEFAULT FALSE,
            `alert_level` ENUM('none', 'warning', 'critical') DEFAULT 'none',
            `notes` TEXT NULL,
            `recorded_by` INT NULL,
            `tracking_method` ENUM('manual', 'automatic', 'rfid', 'camera') DEFAULT 'manual',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`),
            FOREIGN KEY (`space_id`) REFERENCES `venue_spaces`(`id`),
            FOREIGN KEY (`event_id`) REFERENCES `calendar_events`(`id`),
            FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`),
            INDEX `idx_venue_timestamp` (`venue_id`, `timestamp`),
            INDEX `idx_event_timestamp` (`event_id`, `timestamp`),
            INDEX `idx_status` (`status`),
            INDEX `idx_alert_level` (`alert_level`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Venue capacity tracking table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping venue_capacity_tracking table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `venue_capacity_tracking`");
        echo "Venue capacity tracking table dropped.\n";
    }
}