<?php
// database/migrations/053_create_venue_bookings_table.php

class CreateVenueBookingsTable
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
        echo "Creating venue_bookings table...\n";
        
        $sql = "CREATE TABLE `venue_bookings` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `venue_id` INT NOT NULL,
            `space_id` INT NULL,
            `event_id` INT NOT NULL,
            `booking_date` DATE NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `setup_start_time` TIME NULL,
            `breakdown_end_time` TIME NULL,
            `purpose` ENUM('competition', 'training', 'meeting', 'judging', 'catering') NOT NULL,
            `expected_attendance` INT NOT NULL,
            `actual_attendance` INT NULL,
            `special_requirements` TEXT NULL,
            `catering_required` BOOLEAN DEFAULT FALSE,
            `av_required` BOOLEAN DEFAULT FALSE,
            `security_required` BOOLEAN DEFAULT FALSE,
            `booking_status` ENUM('tentative', 'confirmed', 'cancelled', 'completed') DEFAULT 'tentative',
            `booking_cost` DECIMAL(10,2) DEFAULT 0.00,
            `invoice_number` VARCHAR(50) NULL,
            `payment_status` ENUM('pending', 'paid', 'waived') DEFAULT 'pending',
            `booked_by` INT NOT NULL,
            `approved_by` INT NULL,
            `booking_notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`),
            FOREIGN KEY (`space_id`) REFERENCES `venue_spaces`(`id`),
            FOREIGN KEY (`event_id`) REFERENCES `calendar_events`(`id`),
            FOREIGN KEY (`booked_by`) REFERENCES `users`(`id`),
            FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`),
            UNIQUE KEY `unique_booking` (`space_id`, `booking_date`, `start_time`),
            INDEX `idx_date_status` (`booking_date`, `booking_status`),
            INDEX `idx_venue_date` (`venue_id`, `booking_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Venue bookings table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping venue_bookings table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `venue_bookings`");
        echo "Venue bookings table dropped.\n";
    }
}