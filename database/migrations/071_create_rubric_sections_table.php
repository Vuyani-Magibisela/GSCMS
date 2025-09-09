<?php
// database/migrations/071_create_rubric_sections_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateRubricSectionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'rubric_template_id' => 'INT NOT NULL COMMENT "Parent rubric template ID"',
            'section_name' => 'VARCHAR(200) NOT NULL COMMENT "Name of the scoring section"',
            'section_type' => 'ENUM("game_challenge", "research_challenge", "presentation", "teamwork", "technical", "innovation") NOT NULL COMMENT "Type of section"',
            'section_description' => 'TEXT NULL COMMENT "Description of what this section measures"',
            'section_weight' => 'DECIMAL(5,2) NOT NULL COMMENT "Weight percentage of this section"',
            'max_points' => 'DECIMAL(10,2) NOT NULL COMMENT "Maximum points for this section"',
            'multiplier' => 'DECIMAL(5,2) DEFAULT 1.00 COMMENT "Score multiplier (e.g., 3.0 for game challenge)"',
            'display_order' => 'INT NOT NULL COMMENT "Display order of sections"',
            'is_required' => 'BOOLEAN DEFAULT TRUE COMMENT "Whether all criteria in section must be scored"',
            'scoring_instructions' => 'TEXT NULL COMMENT "Instructions for judges on this section"'
        ];
        
        $this->createTable('rubric_sections', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Sections within rubric templates (e.g., game challenge, research challenge)'
        ]);
        
        // Add indexes
        $this->addIndex('rubric_sections', 'idx_template_order', 'rubric_template_id, display_order');
        $this->addIndex('rubric_sections', 'idx_section_type', 'section_type');
        
        echo "Created rubric_sections table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('rubric_sections');
        echo "Dropped rubric_sections table.\n";
    }
}