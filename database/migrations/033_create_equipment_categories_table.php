<?php
// database/migrations/033_create_equipment_categories_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateEquipmentCategoriesTable extends Migration
{
    public function up()
    {
        $columns = [
            'category_id' => 'INT NOT NULL',
            'equipment_name' => 'VARCHAR(255) NOT NULL',
            'equipment_code' => 'VARCHAR(50) NOT NULL',
            'equipment_type' => "ENUM('robot_kit', 'sensor', 'actuator', 'programming_tool', 'material', 'accessory') NOT NULL",
            'required_quantity' => 'INT DEFAULT 1',
            'is_required' => 'BOOLEAN DEFAULT TRUE',
            'alternative_options' => 'JSON',
            'specifications' => 'JSON',
            'safety_requirements' => 'JSON',
            'cost_estimate' => 'DECIMAL(10,2)',
            'supplier_info' => 'JSON',
            'maintenance_requirements' => 'TEXT',
            'compatibility_notes' => 'TEXT',
            'status' => "ENUM('available', 'discontinued', 'recommended') DEFAULT 'available'"
        ];
        
        $this->createTable('equipment_categories', $columns);
        
        // Add indexes
        $this->addIndex('equipment_categories', 'idx_equipment_category', 'category_id');
        $this->addIndex('equipment_categories', 'idx_equipment_code', 'equipment_code');
        $this->addIndex('equipment_categories', 'idx_equipment_type', 'equipment_type');
        $this->addIndex('equipment_categories', 'idx_equipment_required', 'is_required');
        $this->addIndex('equipment_categories', 'idx_equipment_status', 'status');
    }
    
    public function down()
    {
        $this->dropTable('equipment_categories');
    }
}