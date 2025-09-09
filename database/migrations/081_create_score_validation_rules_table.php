<?php
// database/migrations/081_create_score_validation_rules_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateScoreValidationRulesTable extends Migration
{
    public function up()
    {
        $columns = [
            'rule_name' => 'VARCHAR(200) NOT NULL COMMENT "Name of the validation rule"',
            'rule_type' => 'ENUM("range", "consistency", "completeness", "deviation", "outlier", "timing") NOT NULL COMMENT "Type of validation"',
            'category_id' => 'INT NULL COMMENT "Specific category (NULL for all categories)"',
            'phase_id' => 'INT NULL COMMENT "Specific phase (NULL for all phases)"',
            'min_value' => 'DECIMAL(10,2) NULL COMMENT "Minimum allowed value"',
            'max_value' => 'DECIMAL(10,2) NULL COMMENT "Maximum allowed value"',
            'max_deviation' => 'DECIMAL(5,2) NULL COMMENT "Maximum deviation percentage for multi-judge"',
            'is_active' => 'BOOLEAN DEFAULT TRUE COMMENT "Whether rule is active"',
            'severity' => 'ENUM("warning", "error", "critical") DEFAULT "error" COMMENT "Severity level"',
            'error_message' => 'VARCHAR(500) NULL COMMENT "Custom error message"',
            'rule_description' => 'TEXT NULL COMMENT "Description of what this rule validates"',
            'auto_fix' => 'BOOLEAN DEFAULT FALSE COMMENT "Whether system can auto-fix violations"',
            'created_by' => 'INT NOT NULL COMMENT "User who created the rule"',
            'last_modified_by' => 'INT NULL COMMENT "User who last modified the rule"'
        ];
        
        $this->createTable('score_validation_rules', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Rules for validating scoring data integrity'
        ]);
        
        // Add indexes
        $this->addIndex('score_validation_rules', 'idx_rule_type', 'rule_type');
        $this->addIndex('score_validation_rules', 'idx_category', 'category_id');
        $this->addIndex('score_validation_rules', 'idx_active', 'is_active');
        $this->addIndex('score_validation_rules', 'idx_severity', 'severity');
        
        echo "Created score_validation_rules table with indexes.\n";
    }
    
    public function down()
    {
        $this->dropTable('score_validation_rules');
        echo "Dropped score_validation_rules table.\n";
    }
}