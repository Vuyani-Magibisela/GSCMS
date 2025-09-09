<?php
// database/migrations/074_create_category_rubric_config_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCategoryRubricConfigTable extends Migration
{
    public function up()
    {
        $columns = [
            'category_id' => 'INT NOT NULL COMMENT "Competition category ID"',
            'rubric_template_id' => 'INT NOT NULL COMMENT "Associated rubric template ID"',
            'grade_range_start' => 'VARCHAR(10) NOT NULL COMMENT "Starting grade (e.g., R, 1, 4)"',
            'grade_range_end' => 'VARCHAR(10) NOT NULL COMMENT "Ending grade (e.g., 3, 7, 11)"',
            'difficulty_level' => 'ENUM("beginner", "intermediate", "advanced", "expert") NOT NULL COMMENT "Complexity level for this category"',
            'special_requirements' => 'JSON NULL COMMENT "Category-specific scoring requirements"',
            'scoring_interface_type' => 'ENUM("visual", "standard", "technical", "comprehensive") DEFAULT "standard" COMMENT "UI interface type to use"',
            'time_limit_minutes' => 'INT NULL COMMENT "Time limit for scoring this category"',
            'is_active' => 'BOOLEAN DEFAULT TRUE COMMENT "Whether this configuration is active"'
        ];
        
        $this->createTable('category_rubric_config', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Configuration linking categories to their scoring rubrics'
        ]);
        
        // Add indexes
        $this->addIndex('category_rubric_config', 'idx_category', 'category_id');
        $this->addIndex('category_rubric_config', 'idx_rubric_template', 'rubric_template_id');
        $this->addIndex('category_rubric_config', 'idx_difficulty', 'difficulty_level');
        $this->addIndex('category_rubric_config', 'idx_active', 'is_active');
        
        // Add unique constraint
        $this->addUniqueKey('category_rubric_config', 'unique_category_difficulty', 'category_id, difficulty_level');
        
        echo "Created category_rubric_config table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('category_rubric_config');
        echo "Dropped category_rubric_config table.\n";
    }
}