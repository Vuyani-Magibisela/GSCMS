<?php
// database/migrations/055_create_resource_categories_table.php

class CreateResourceCategoriesTable
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
        echo "Creating resource_categories table...\n";
        
        $sql = "CREATE TABLE `resource_categories` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `category_name` VARCHAR(100) NOT NULL,
            `category_type` ENUM('equipment', 'furniture', 'technology', 'supplies', 'safety') NOT NULL,
            `description` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_type` (`category_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Resource categories table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping resource_categories table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `resource_categories`");
        echo "Resource categories table dropped.\n";
    }
}