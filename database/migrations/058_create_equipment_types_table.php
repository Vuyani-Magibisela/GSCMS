<?php
// database/migrations/058_create_equipment_types_table.php

class CreateEquipmentTypesTable
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
        echo "Creating equipment_types table...\n";
        
        $sql = "CREATE TABLE `equipment_types` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `type_name` VARCHAR(100) NOT NULL,
            `category` ENUM('robotics', 'electronics', 'furniture', 'av_equipment', 'safety', 'tools') NOT NULL,
            `brand` VARCHAR(100) NULL,
            `model` VARCHAR(100) NULL,
            `specifications` JSON NULL,
            `manual_url` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Equipment types table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping equipment_types table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `equipment_types`");
        echo "Equipment types table dropped.\n";
    }
}