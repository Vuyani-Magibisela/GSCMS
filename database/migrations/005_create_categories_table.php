<?php
// database/migrations/005_create_categories_table.php
require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(100) NOT NULL',
            'code' => 'VARCHAR(20) UNIQUE NOT NULL',
            'description' => 'TEXT',
            'grade_range' => 'VARCHAR(50) NOT NULL',
            'hardware_requirements' => 'TEXT',
            'mission_description' => 'TEXT',
            'research_topic' => 'VARCHAR(200)',
            'max_team_size' => 'INT DEFAULT 6',
            'max_coaches' => 'INT DEFAULT 2',
            'scoring_criteria' => 'JSON',
            'rules' => 'TEXT',
            'equipment_list' => 'TEXT',
            'age_restrictions' => 'TEXT'
        ];
        
        $this->createTable('categories', $columns);
        
        // Add indexes
        $this->addIndex('categories', 'idx_code', 'code');
        $this->addIndex('categories', 'idx_grade_range', 'grade_range');
    }
    
    public function down()
    {
        $this->dropTable('categories');
    }
}