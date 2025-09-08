<?php
// database/migrations/052_create_venue_spaces_table.php

class CreateVenueSpacesTable
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
        echo "Creating venue_spaces table...\n";
        
        $sql = "CREATE TABLE `venue_spaces` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `venue_id` INT NOT NULL,
            `space_name` VARCHAR(100) NOT NULL,
            `space_type` ENUM('competition_hall', 'judging_room', 'catering_area', 
                              'boardroom', 'classroom', 'foyer', 'outdoor', 'storage') NOT NULL,
            `floor_level` VARCHAR(20) DEFAULT 'Ground',
            `capacity_seated` INT NOT NULL,
            `capacity_standing` INT NULL,
            `area_sqm` DECIMAL(10,2) NULL,
            `competition_tables` INT DEFAULT 0,
            `has_av_equipment` BOOLEAN DEFAULT FALSE,
            `has_aircon` BOOLEAN DEFAULT FALSE,
            `has_wifi` BOOLEAN DEFAULT TRUE,
            `power_outlets` INT DEFAULT 0,
            `amenities` JSON NULL,
            `hourly_rate` DECIMAL(10,2) DEFAULT 0.00,
            `daily_rate` DECIMAL(10,2) DEFAULT 0.00,
            `setup_time_minutes` INT DEFAULT 30,
            `breakdown_time_minutes` INT DEFAULT 30,
            `status` ENUM('available', 'booked', 'maintenance', 'setup') DEFAULT 'available',
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`) ON DELETE CASCADE,
            INDEX `idx_venue_type` (`venue_id`, `space_type`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Venue spaces table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping venue_spaces table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `venue_spaces`");
        echo "Venue spaces table dropped.\n";
    }
}