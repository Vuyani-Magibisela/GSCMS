<?php
// database/migrations/006_create_competitions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCompetitionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(200) NOT NULL',
            'year' => 'YEAR NOT NULL',
            'phase_id' => 'INT NOT NULL',
            'category_id' => 'INT NOT NULL',
            'venue_name' => 'VARCHAR(200)',
            'venue_address' => 'TEXT',
            'venue_capacity' => 'INT',
            'date' => 'DATE NOT NULL',
            'start_time' => 'TIME',
            'end_time' => 'TIME',
            'registration_deadline' => 'DATETIME',
            'max_participants' => 'INT',
            'current_participants' => 'INT DEFAULT 0',
            'status' => "ENUM('planned', 'open_registration', 'registration_closed', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned'",
            'entry_requirements' => 'TEXT',
            'competition_rules' => 'TEXT',
            'prizes' => 'TEXT',
            'contact_person' => 'VARCHAR(100)',
            'contact_email' => 'VARCHAR(100)',
            'contact_phone' => 'VARCHAR(20)'
        ];
        
        $this->createTable('competitions', $columns);
        
        // Add foreign keys
        $this->addForeignKey('competitions', 'phase_id', 'phases', 'id', 'CASCADE');
        $this->addForeignKey('competitions', 'category_id', 'categories', 'id', 'CASCADE');
        
        // Add indexes
        $this->addIndex('competitions', 'idx_year', 'year');
        $this->addIndex('competitions', 'idx_date', 'date');
        $this->addIndex('competitions', 'idx_status', 'status');
        $this->addIndex('competitions', 'idx_phase_category', ['phase_id', 'category_id']);
    }
    
    public function down()
    {
        $this->dropTable('competitions');
    }
}