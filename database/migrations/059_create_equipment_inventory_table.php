<?php
// database/migrations/059_create_equipment_inventory_table.php

class CreateEquipmentInventoryTable
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
        echo "Creating equipment_inventory table...\n";
        
        $sql = "CREATE TABLE `equipment_inventory` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `equipment_type_id` INT NOT NULL,
            `serial_number` VARCHAR(100) UNIQUE NULL,
            `asset_tag` VARCHAR(50) UNIQUE NOT NULL,
            `purchase_date` DATE NULL,
            `purchase_cost` DECIMAL(10,2) NULL,
            `current_value` DECIMAL(10,2) NULL,
            `condition_status` ENUM('new', 'excellent', 'good', 'fair', 'poor', 'broken') DEFAULT 'good',
            `location_type` ENUM('venue', 'storage', 'allocated', 'maintenance', 'lost') DEFAULT 'storage',
            `current_location_id` INT NULL,
            `last_maintenance_date` DATE NULL,
            `next_maintenance_date` DATE NULL,
            `warranty_expiry` DATE NULL,
            `disposal_date` DATE NULL,
            `qr_code` VARCHAR(100) UNIQUE NULL,
            `rfid_tag` VARCHAR(100) UNIQUE NULL,
            `notes` TEXT NULL,
            `status` ENUM('available', 'in_use', 'maintenance', 'retired') DEFAULT 'available',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`equipment_type_id`) REFERENCES `equipment_types`(`id`),
            INDEX `idx_status_location` (`status`, `location_type`),
            INDEX `idx_asset_tag` (`asset_tag`),
            INDEX `idx_qr_code` (`qr_code`),
            INDEX `idx_rfid` (`rfid_tag`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
        echo "Equipment inventory table created successfully.\n";
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        echo "Dropping equipment_inventory table...\n";
        $this->db->exec("DROP TABLE IF EXISTS `equipment_inventory`");
        echo "Equipment inventory table dropped.\n";
    }
}