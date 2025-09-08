<?php
// database/migrations/056_create_resources_table.php

class CreateResourcesTable
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
        echo "Creating resources table...\n";
        
        $sql = "CREATE TABLE `resources` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `category_id` INT NOT NULL,
            `resource_name` VARCHAR(200) NOT NULL,
            `resource_code` VARCHAR(50) UNIQUE NOT NULL,
            `description` TEXT NULL,
            `unit_type` ENUM('piece', 'set', 'kit', 'box', 'roll') DEFAULT 'piece',
            `total_quantity` INT NOT NULL DEFAULT 0,
            `available_quantity` INT NOT NULL DEFAULT 0,
            `reserved_quantity` INT DEFAULT 0,
            `damaged_quantity` INT DEFAULT 0,
            `unit_cost` DECIMAL(10,2) DEFAULT 0.00,
            `replacement_cost` DECIMAL(10,2) DEFAULT 0.00,
            `supplier_name` VARCHAR(200) NULL,
            `supplier_contact` VARCHAR(100) NULL,
            `purchase_date` DATE NULL,
            `warranty_expiry` DATE NULL,
            `storage_location` VARCHAR(100) NULL,
            `min_quantity` INT DEFAULT 1,
            `reorder_point` INT DEFAULT 5,
            `image_path` VARCHAR(255) NULL,
            `specifications` JSON NULL,
            `status` ENUM('active', 'discontinued', 'maintenance') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`id`),
            INDEX `idx_available` (`available_quantity`, `status`),
            INDEX `idx_code` (`resource_code`),
            INDEX `idx_reorder` (`min_quantity`, `available_quantity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Resources table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping resources table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `resources`");
        echo "Resources table dropped.\n";
    }
}