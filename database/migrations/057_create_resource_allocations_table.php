<?php
// database/migrations/057_create_resource_allocations_table.php

class CreateResourceAllocationsTable
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
        echo "Creating resource_allocations table...\n";
        
        $sql = "CREATE TABLE `resource_allocations` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `resource_id` INT NOT NULL,
            `allocation_type` ENUM('venue', 'event', 'team', 'category') NOT NULL,
            `allocated_to_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `allocation_date` DATE NOT NULL,
            `return_date` DATE NULL,
            `actual_return_date` DATE NULL,
            `allocated_by` INT NOT NULL,
            `returned_to` INT NULL,
            `condition_on_allocation` ENUM('new', 'good', 'fair', 'poor') DEFAULT 'good',
            `condition_on_return` ENUM('good', 'damaged', 'lost', 'not_returned') NULL,
            `damage_notes` TEXT NULL,
            `replacement_charge` DECIMAL(10,2) DEFAULT 0.00,
            `status` ENUM('allocated', 'returned', 'overdue', 'lost') DEFAULT 'allocated',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`),
            FOREIGN KEY (`allocated_by`) REFERENCES `users`(`id`),
            FOREIGN KEY (`returned_to`) REFERENCES `users`(`id`),
            INDEX `idx_status_date` (`status`, `allocation_date`),
            INDEX `idx_resource_status` (`resource_id`, `status`),
            INDEX `idx_allocation_type` (`allocation_type`, `allocated_to_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Resource allocations table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping resource_allocations table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `resource_allocations`");
        echo "Resource allocations table dropped.\n";
    }
}