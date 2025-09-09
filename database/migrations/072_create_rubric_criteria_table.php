<?php
// database/migrations/072_create_rubric_criteria_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateRubricCriteriaTable extends Migration
{
    public function up()
    {
        $columns = [
            'section_id' => 'INT NOT NULL COMMENT "Parent section ID"',
            'criteria_name' => 'VARCHAR(200) NOT NULL COMMENT "Name of the scoring criteria"',
            'criteria_description' => 'TEXT NULL COMMENT "Detailed description of what to evaluate"',
            'max_points' => 'DECIMAL(10,2) NOT NULL COMMENT "Maximum points for this criteria"',
            'weight_percentage' => 'DECIMAL(5,2) NULL COMMENT "Weight within the section (optional)"',
            'display_order' => 'INT NOT NULL COMMENT "Display order within section"',
            'scoring_type' => 'ENUM("points", "levels", "percentage", "binary", "custom") DEFAULT "levels" COMMENT "How this criteria is scored"',
            'is_bonus' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether this is a bonus criteria"',
            'scoring_notes' => 'TEXT NULL COMMENT "Additional scoring guidance for judges"',
            'validation_rules' => 'JSON NULL COMMENT "Custom validation rules for this criteria"'
        ];
        
        $this->createTable('rubric_criteria', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Individual scoring criteria within rubric sections'
        ]);
        
        // Add indexes
        $this->addIndex('rubric_criteria', 'idx_section_order', 'section_id, display_order');
        $this->addIndex('rubric_criteria', 'idx_scoring_type', 'scoring_type');
        $this->addIndex('rubric_criteria', 'idx_bonus', 'is_bonus');
        
        echo "Created rubric_criteria table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('rubric_criteria');
        echo "Dropped rubric_criteria table.\n";
    }
}