<?php
// database/migrations/032_create_mission_templates_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateMissionTemplatesTable extends Migration
{
    public function up()
    {
        $columns = [
            'category_id' => 'INT NOT NULL',
            'mission_name' => 'VARCHAR(255) NOT NULL',
            'mission_code' => 'VARCHAR(50) NOT NULL UNIQUE',
            'mission_description' => 'TEXT',
            'story_context' => 'TEXT',
            'objective' => 'TEXT',
            'difficulty_level' => "ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner'",
            'time_limit_minutes' => 'INT DEFAULT 15',
            'max_attempts' => 'INT DEFAULT 3',
            'technical_requirements' => 'JSON',
            'competition_rules' => 'JSON',
            'scoring_rubric' => 'JSON',
            'mission_stages' => 'JSON',
            'research_component' => 'JSON',
            'deliverables' => 'JSON',
            'equipment_requirements' => 'JSON',
            'safety_requirements' => 'JSON',
            'status' => "ENUM('draft', 'active', 'archived') DEFAULT 'draft'",
            'notes' => 'TEXT'
        ];
        
        $this->createTable('mission_templates', $columns);
        
        // Add indexes
        $this->addIndex('mission_templates', 'idx_mission_category', 'category_id');
        $this->addIndex('mission_templates', 'idx_mission_code', 'mission_code');
        $this->addIndex('mission_templates', 'idx_mission_difficulty', 'difficulty_level');
        $this->addIndex('mission_templates', 'idx_mission_status', 'status');
    }
    
    public function down()
    {
        $this->dropTable('mission_templates');
    }
}