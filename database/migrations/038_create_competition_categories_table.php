<?php
// database/migrations/038_create_competition_categories_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCompetitionCategoriesTable extends Migration
{
    public function up()
    {
        $columns = [
            'competition_id' => 'INT UNSIGNED NOT NULL',
            'category_id' => 'INT UNSIGNED NOT NULL',
            'category_code' => 'VARCHAR(50) NOT NULL',
            'name' => 'VARCHAR(255) NOT NULL',
            'grades' => 'JSON NOT NULL',
            'team_size' => 'INT DEFAULT 4',
            'max_teams_per_school' => 'INT DEFAULT 3',
            'equipment_requirements' => 'JSON',
            'mission_template_id' => 'INT UNSIGNED',
            'scoring_rubric' => 'JSON',
            'registration_rules' => 'JSON',
            'special_requirements' => 'TEXT',
            'safety_protocols' => 'TEXT',
            'time_limit_minutes' => 'INT DEFAULT 15',
            'max_attempts' => 'INT DEFAULT 3',
            'is_active' => 'BOOLEAN DEFAULT TRUE',
            'registration_count' => 'INT DEFAULT 0',
            'capacity_limit' => 'INT',
            'custom_rules' => 'JSON'
        ];
        
        $this->createTable('competition_categories', $columns);
        
        // Add indexes
        $this->addIndex('competition_categories', 'idx_comp_categories_competition', 'competition_id');
        $this->addIndex('competition_categories', 'idx_comp_categories_category', 'category_id');
        $this->addIndex('competition_categories', 'idx_comp_categories_code', 'category_code');
        $this->addIndex('competition_categories', 'idx_comp_categories_active', 'is_active');
        $this->addIndex('competition_categories', 'idx_comp_categories_mission', 'mission_template_id');
        
        // Add composite index for competition and category
        $this->addIndex('competition_categories', 'idx_comp_categories_comp_cat', 'competition_id, category_id');
    }
    
    public function down()
    {
        $this->dropTable('competition_categories');
    }
}