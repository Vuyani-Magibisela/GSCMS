<?php
// database/migrations/073_create_rubric_levels_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateRubricLevelsTable extends Migration
{
    public function up()
    {
        $columns = [
            'criteria_id' => 'INT NOT NULL COMMENT "Parent criteria ID"',
            'level_number' => 'INT NOT NULL COMMENT "Level number (1-4: Basic to Exceeded)"',
            'level_name' => 'VARCHAR(100) NOT NULL COMMENT "Name of the level (Basic, Developing, etc.)"',
            'level_description' => 'TEXT NOT NULL COMMENT "Detailed description of performance at this level"',
            'points_awarded' => 'DECIMAL(10,2) NOT NULL COMMENT "Points awarded for this level"',
            'percentage_value' => 'DECIMAL(5,2) NULL COMMENT "Percentage of max score (25%, 50%, 75%, 100%)"',
            'display_color' => 'VARCHAR(7) DEFAULT "#6c757d" COMMENT "Color for UI display (hex code)"',
            'icon_class' => 'VARCHAR(50) NULL COMMENT "CSS icon class for visual representation"',
            'sort_order' => 'INT GENERATED ALWAYS AS (level_number) STORED COMMENT "Auto-generated sort order"'
        ];
        
        $this->createTable('rubric_levels', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => '4-level scoring system for rubric criteria'
        ]);
        
        // Add indexes
        $this->addIndex('rubric_levels', 'idx_criteria_level', 'criteria_id, level_number');
        $this->addIndex('rubric_levels', 'idx_level_number', 'level_number');
        
        // Add unique constraint
        $this->addUniqueKey('rubric_levels', 'unique_criteria_level', 'criteria_id, level_number');
        
        echo "Created rubric_levels table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('rubric_levels');
        echo "Dropped rubric_levels table.\n";
    }
}