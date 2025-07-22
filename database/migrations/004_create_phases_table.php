<?php
// database/migrations/004_create_phases_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreatePhasesTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(100) NOT NULL',
            'description' => 'TEXT',
            'phase_number' => 'INT NOT NULL',
            'start_date' => 'DATE NOT NULL',
            'end_date' => 'DATE NOT NULL',
            'registration_deadline' => 'DATE',
            'max_teams_per_category' => 'INT',
            'location_type' => "ENUM('school_based', 'district_based', 'provincial') NOT NULL",
            'status' => "ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming'",
            'requirements' => 'TEXT'
        ];
        
        $this->createTable('phases', $columns);
        
        // Add indexes
        $this->addIndex('phases', 'idx_phase_number', 'phase_number');
        $this->addIndex('phases', 'idx_status', 'status');
        $this->addIndex('phases', 'idx_dates', ['start_date', 'end_date']);
    }
    
    public function down()
    {
        $this->dropTable('phases');
    }
}