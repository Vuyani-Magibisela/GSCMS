<?php
// database/migrations/077_create_judge_assignments_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateJudgeAssignmentsTable extends Migration
{
    public function up()
    {
        $columns = [
            'competition_id' => 'INT NULL COMMENT "Competition ID (if competition-based)"',
            'tournament_id' => 'INT NULL COMMENT "Tournament ID (if tournament-based)"',
            'category_id' => 'INT NOT NULL COMMENT "Competition category"',
            'judge_id' => 'INT NOT NULL COMMENT "Assigned judge"',
            'judge_type' => 'ENUM("primary", "secondary", "backup", "head", "calibration") DEFAULT "primary" COMMENT "Type of judge assignment"',
            'table_number' => 'VARCHAR(10) NULL COMMENT "Table or station assignment"',
            'phase' => 'ENUM("preliminary", "semifinal", "final", "all") DEFAULT "preliminary" COMMENT "Competition phase"',
            'status' => 'ENUM("assigned", "active", "completed", "unavailable", "replaced") DEFAULT "assigned" COMMENT "Assignment status"',
            'assigned_by' => 'INT NOT NULL COMMENT "Who made the assignment"',
            'assigned_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT "When assignment was made"',
            'start_time' => 'DATETIME NULL COMMENT "Expected start time"',
            'end_time' => 'DATETIME NULL COMMENT "Expected end time"',
            'team_count' => 'INT DEFAULT 0 COMMENT "Number of teams assigned to this judge"',
            'max_teams' => 'INT DEFAULT 20 COMMENT "Maximum teams this judge can handle"',
            'special_instructions' => 'TEXT NULL COMMENT "Special instructions for this assignment"'
        ];
        
        $this->createTable('judge_assignments', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Judge assignments for competitions and tournaments'
        ]);
        
        // Add indexes
        $this->addIndex('judge_assignments', 'idx_competition_category', 'competition_id, category_id');
        $this->addIndex('judge_assignments', 'idx_tournament_category', 'tournament_id, category_id');
        $this->addIndex('judge_assignments', 'idx_judge', 'judge_id');
        $this->addIndex('judge_assignments', 'idx_phase', 'phase');
        $this->addIndex('judge_assignments', 'idx_status', 'status');
        $this->addIndex('judge_assignments', 'idx_assigned_by', 'assigned_by');
        
        // Add unique constraint to prevent duplicate assignments
        $this->addUniqueKey('judge_assignments', 'unique_judge_assignment', 'judge_id, COALESCE(competition_id, 0), COALESCE(tournament_id, 0), category_id, phase');
        
        echo "Created judge_assignments table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('judge_assignments');
        echo "Dropped judge_assignments table.\n";
    }
}