<?php
// database/migrations/026_create_districts_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateDistrictsTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(100) NOT NULL',
            'province' => 'VARCHAR(50) NOT NULL',
            'code' => 'VARCHAR(10) UNIQUE',
            'region' => 'VARCHAR(50)',
            'coordinator_id' => 'INT',
            'description' => 'TEXT',
            'boundary_coordinates' => 'TEXT',
            'status' => "ENUM('active', 'inactive') DEFAULT 'active'"
        ];
        
        $this->createTable('districts', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci'
        ]);
        
        // Add indexes
        $this->addIndex('districts', 'idx_districts_province', 'province');
        $this->addIndex('districts', 'idx_districts_status', 'status');
        $this->addIndex('districts', 'idx_districts_code', 'code');
        $this->addIndex('districts', 'idx_districts_coordinator', 'coordinator_id');
    }
    
    public function down()
    {
        $this->dropTable('districts');
    }
}