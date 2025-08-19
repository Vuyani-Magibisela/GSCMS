<?php
// database/migrations/031_create_phase_progressions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreatePhaseProgressionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT NOT NULL',
            'from_phase_id' => 'INT',
            'to_phase_id' => 'INT NOT NULL',
            'progression_date' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'score' => 'DECIMAL(10,2)',
            'rank_in_category' => 'INT',
            'qualified' => 'BOOLEAN DEFAULT FALSE',
            'advancement_reason' => 'TEXT',
            'competition_type' => "ENUM('pilot', 'full', 'regional', 'national') DEFAULT 'pilot'",
            'notes' => 'TEXT'
        ];
        
        $this->createTable('phase_progressions', $columns);
        
        // Add indexes
        $this->addIndex('phase_progressions', 'idx_team_id', 'team_id');
        $this->addIndex('phase_progressions', 'idx_from_phase', 'from_phase_id');
        $this->addIndex('phase_progressions', 'idx_to_phase', 'to_phase_id');
        $this->addIndex('phase_progressions', 'idx_qualified', 'qualified');
        $this->addIndex('phase_progressions', 'idx_progression_date', 'progression_date');
        $this->addIndex('phase_progressions', 'idx_competition_type', 'competition_type');
    }
    
    public function down()
    {
        $this->dropTable('phase_progressions');
    }
}