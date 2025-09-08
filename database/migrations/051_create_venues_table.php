<?php
// database/migrations/051_create_venues_table.php

class CreateVenuesTable
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
        echo "Creating venues table...\n";
        
        $sql = "CREATE TABLE `venues` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `venue_name` VARCHAR(200) NOT NULL,
            `venue_type` ENUM('main', 'district', 'school', 'training') NOT NULL,
            `district_id` INT NULL,
            `address` TEXT NOT NULL,
            `gps_coordinates` VARCHAR(100) NULL,
            `contact_person` VARCHAR(100) NOT NULL,
            `contact_phone` VARCHAR(20) NOT NULL,
            `contact_email` VARCHAR(100) NOT NULL,
            `emergency_contact` VARCHAR(20) NULL,
            `total_capacity` INT NOT NULL,
            `parking_spaces` INT DEFAULT 0,
            `accessibility_features` JSON NULL,
            `facilities` JSON NULL,
            `operating_hours` JSON NULL,
            `cost_per_day` DECIMAL(10,2) DEFAULT 0.00,
            `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_type_status` (`venue_type`, `status`),
            INDEX `idx_district` (`district_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Venues table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping venues table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `venues`");
        echo "Venues table dropped.\n";
    }
}