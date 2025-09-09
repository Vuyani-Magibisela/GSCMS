<?php
// database/migrations/070_create_rubric_templates_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateRubricTemplatesTable extends Migration
{
    public function up()
    {
        $columns = [
            'template_name' => 'VARCHAR(200) NOT NULL COMMENT "Name of the rubric template"',
            'category_id' => 'INT NOT NULL COMMENT "Competition category ID"',
            'competition_phase_id' => 'INT NULL COMMENT "Competition phase ID"',
            'rubric_type' => 'ENUM("technical", "core_values", "combined") NOT NULL DEFAULT "combined" COMMENT "Type of rubric"',
            'total_points' => 'DECIMAL(10,2) NOT NULL DEFAULT 100.00 COMMENT "Maximum possible points"',
            'version' => 'VARCHAR(20) NOT NULL DEFAULT "1.0" COMMENT "Rubric version"',
            'is_active' => 'BOOLEAN DEFAULT TRUE COMMENT "Whether template is active"',
            'created_by' => 'INT NOT NULL COMMENT "User who created the template"',
            'approved_by' => 'INT NULL COMMENT "User who approved the template"',
            'approval_date' => 'TIMESTAMP NULL COMMENT "When template was approved"',
            'template_description' => 'TEXT NULL COMMENT "Description of the rubric template"'
        ];
        
        $this->createTable('rubric_templates', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Master rubric templates for scoring'
        ]);
        
        // Add indexes
        $this->addIndex('rubric_templates', 'idx_category_phase', 'category_id, competition_phase_id');
        $this->addIndex('rubric_templates', 'idx_active', 'is_active');
        $this->addIndex('rubric_templates', 'idx_created_by', 'created_by');
        $this->addIndex('rubric_templates', 'idx_approved_by', 'approved_by');
        
        echo "Created rubric_templates table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('rubric_templates');
        echo "Dropped rubric_templates table.\n";
    }
}