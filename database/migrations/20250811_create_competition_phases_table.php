<?php

/**
 * Migration: Create Competition Phases Table
 * Description: Create table for managing competition phases and timeline
 * Date: 2025-08-11
 */

class CreateCompetitionPhasesTable
{
    private $db;

    public function __construct()
    {
        $this->db = new App\Core\Database();
    }

    /**
     * Run the migration
     */
    public function up()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS competition_phases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL COMMENT 'Phase name',
            display_name VARCHAR(150) NOT NULL COMMENT 'Display name for users',
            description TEXT COMMENT 'Phase description',
            start_date DATETIME NOT NULL COMMENT 'Phase start date and time',
            end_date DATETIME NOT NULL COMMENT 'Phase end date and time',
            status VARCHAR(50) DEFAULT 'active' COMMENT 'Phase status (active, inactive, completed)',
            phase_order INT NOT NULL DEFAULT 0 COMMENT 'Order of phases',
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_dates (start_date, end_date),
            INDEX idx_status (status),
            INDEX idx_phase_order (phase_order),
            
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Competition phases and timeline for GDE SciBOTICS Competition';
        ";

        $this->db->exec($sql);

        // Insert default phases for 2025 competition
        $this->seedDefaultPhases();
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS competition_phases");
    }

    /**
     * Seed default phases
     */
    private function seedDefaultPhases()
    {
        $currentYear = date('Y');
        
        $phases = [
            [
                'name' => 'school_registration',
                'display_name' => 'School Registration Phase',
                'description' => 'Schools can register and create coordinator accounts',
                'start_date' => $currentYear . '-01-01 00:00:00',
                'end_date' => $currentYear . '-03-15 23:59:59',
                'status' => 'active',
                'phase_order' => 1
            ],
            [
                'name' => 'team_registration',
                'display_name' => 'Team Registration Phase',
                'description' => 'Schools can create teams and register participants',
                'start_date' => $currentYear . '-02-01 00:00:00',
                'end_date' => $currentYear . '-04-30 23:59:59',
                'status' => 'active',
                'phase_order' => 2
            ],
            [
                'name' => 'participant_registration',
                'display_name' => 'Participant Registration Phase',
                'description' => 'Final period for adding participants to teams',
                'start_date' => $currentYear . '-03-01 00:00:00',
                'end_date' => $currentYear . '-05-15 23:59:59',
                'status' => 'active',
                'phase_order' => 3
            ],
            [
                'name' => 'modification_period',
                'display_name' => 'Modification Period',
                'description' => 'Limited modifications to team compositions allowed',
                'start_date' => $currentYear . '-05-16 00:00:00',
                'end_date' => $currentYear . '-06-01 23:59:59',
                'status' => 'active',
                'phase_order' => 4
            ],
            [
                'name' => 'competition_locked',
                'display_name' => 'Competition Preparation',
                'description' => 'No changes allowed - preparation for competition',
                'start_date' => $currentYear . '-06-02 00:00:00',
                'end_date' => $currentYear . '-06-30 23:59:59',
                'status' => 'active',
                'phase_order' => 5
            ],
            [
                'name' => 'competition_period',
                'display_name' => 'Competition Period',
                'description' => 'Active competition period - district and provincial competitions',
                'start_date' => $currentYear . '-07-01 00:00:00',
                'end_date' => $currentYear . '-09-30 23:59:59',
                'status' => 'active',
                'phase_order' => 6
            ]
        ];

        foreach ($phases as $phase) {
            $this->db->table('competition_phases')->insert($phase);
        }
    }
}