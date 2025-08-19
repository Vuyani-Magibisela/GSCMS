<?php
// database/migrations/037_create_competition_phases_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCompetitionPhasesTable extends Migration
{
    public function up()
    {
        $columns = [
            'competition_id' => 'INT UNSIGNED NOT NULL',
            'phase_number' => 'INT NOT NULL',
            'name' => 'VARCHAR(255) NOT NULL',
            'description' => 'TEXT',
            'start_date' => 'DATETIME NOT NULL',
            'end_date' => 'DATETIME NOT NULL',
            'capacity_per_category' => 'INT DEFAULT 30',
            'venue_requirements' => 'JSON',
            'advancement_criteria' => 'JSON',
            'scoring_configuration' => 'JSON',
            'judge_requirements' => 'JSON',
            'equipment_allocation' => 'JSON',
            'safety_protocols' => 'TEXT',
            'communication_template' => 'TEXT',
            'is_active' => 'BOOLEAN DEFAULT TRUE',
            'is_completed' => 'BOOLEAN DEFAULT FALSE',
            'completion_date' => 'DATETIME NULL',
            'phase_order' => 'INT DEFAULT 1'
        ];
        
        $this->createTable('competition_phases', $columns);
        
        // Add indexes
        $this->addIndex('competition_phases', 'idx_phases_competition', 'competition_id');
        $this->addIndex('competition_phases', 'idx_phases_number', 'phase_number');
        $this->addIndex('competition_phases', 'idx_phases_dates', 'start_date, end_date');
        $this->addIndex('competition_phases', 'idx_phases_active', 'is_active');
        $this->addIndex('competition_phases', 'idx_phases_order', 'phase_order');
        
        // Add composite index for competition and phase
        $this->addIndex('competition_phases', 'idx_phases_comp_phase', 'competition_id, phase_number');
    }
    
    public function down()
    {
        $this->dropTable('competition_phases');
    }
}