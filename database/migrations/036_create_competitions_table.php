<?php
// database/migrations/036_create_competitions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCompetitionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(255) NOT NULL',
            'year' => 'INT NOT NULL',
            'type' => "ENUM('pilot', 'full_system', 'regional', 'national') NOT NULL DEFAULT 'pilot'",
            'status' => "ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'",
            'start_date' => 'DATE NOT NULL',
            'end_date' => 'DATE NOT NULL',
            'geographic_scope' => "ENUM('single_province', 'multi_province', 'national') NOT NULL DEFAULT 'single_province'",
            'phase_configuration' => 'JSON',
            'registration_opening' => 'DATETIME',
            'registration_closing' => 'DATETIME',
            'description' => 'TEXT',
            'rules_document' => 'VARCHAR(500)',
            'contact_email' => 'VARCHAR(255)',
            'venue_information' => 'TEXT',
            'awards_ceremony_date' => 'DATETIME',
            'created_by' => 'INT UNSIGNED NOT NULL',
            'updated_by' => 'INT UNSIGNED'
        ];
        
        $this->createTable('competitions', $columns);
        
        // Add indexes
        $this->addIndex('competitions', 'idx_competitions_year', 'year');
        $this->addIndex('competitions', 'idx_competitions_type', 'type');
        $this->addIndex('competitions', 'idx_competitions_status', 'status');
        $this->addIndex('competitions', 'idx_competitions_start_date', 'start_date');
        $this->addIndex('competitions', 'idx_competitions_created_by', 'created_by');
    }
    
    public function down()
    {
        $this->dropTable('competitions');
    }
}