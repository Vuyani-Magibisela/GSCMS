<?php
// database/migrations/039_create_competition_configurations_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCompetitionConfigurationsTable extends Migration
{
    public function up()
    {
        $columns = [
            'competition_id' => 'INT UNSIGNED NOT NULL',
            'config_key' => 'VARCHAR(100) NOT NULL',
            'config_value' => 'TEXT',
            'config_type' => "ENUM('string', 'integer', 'boolean', 'json', 'text') DEFAULT 'string'",
            'description' => 'TEXT',
            'category' => 'VARCHAR(50)',
            'is_required' => 'BOOLEAN DEFAULT FALSE',
            'validation_rules' => 'JSON',
            'default_value' => 'TEXT',
            'last_modified_by' => 'INT UNSIGNED NOT NULL',
            'last_modified_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        $this->createTable('competition_configurations', $columns);
        
        // Add indexes
        $this->addIndex('competition_configurations', 'idx_config_competition', 'competition_id');
        $this->addIndex('competition_configurations', 'idx_config_key', 'config_key');
        $this->addIndex('competition_configurations', 'idx_config_category', 'category');
        $this->addIndex('competition_configurations', 'idx_config_required', 'is_required');
        $this->addIndex('competition_configurations', 'idx_config_modified_by', 'last_modified_by');
        
        // Add unique composite index for competition and config key
        $this->addIndex('competition_configurations', 'idx_config_comp_key', 'competition_id, config_key', true);
    }
    
    public function down()
    {
        $this->dropTable('competition_configurations');
    }
}