<?php
// database/migrations/034_create_equipment_inventory_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateEquipmentInventoryTable extends Migration
{
    public function up()
    {
        $columns = [
            'equipment_category_id' => 'INT NOT NULL',
            'school_id' => 'INT',
            'available_quantity' => 'INT DEFAULT 0',
            'condition_status' => "ENUM('excellent', 'good', 'fair', 'poor', 'broken') DEFAULT 'good'",
            'last_maintenance' => 'DATE',
            'next_maintenance_due' => 'DATE',
            'acquisition_date' => 'DATE',
            'replacement_needed' => 'BOOLEAN DEFAULT FALSE',
            'location' => 'VARCHAR(255)',
            'serial_numbers' => 'JSON',
            'usage_history' => 'JSON',
            'notes' => 'TEXT'
        ];
        
        $this->createTable('equipment_inventory', $columns);
        
        // Add indexes
        $this->addIndex('equipment_inventory', 'idx_inventory_equipment', 'equipment_category_id');
        $this->addIndex('equipment_inventory', 'idx_inventory_school', 'school_id');
        $this->addIndex('equipment_inventory', 'idx_inventory_condition', 'condition_status');
        $this->addIndex('equipment_inventory', 'idx_inventory_maintenance', 'next_maintenance_due');
        $this->addIndex('equipment_inventory', 'idx_inventory_replacement', 'replacement_needed');
    }
    
    public function down()
    {
        $this->dropTable('equipment_inventory');
    }
}