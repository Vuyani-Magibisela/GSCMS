<?php
// database/migrations/035_create_mission_assets_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateMissionAssetsTable extends Migration
{
    public function up()
    {
        $columns = [
            'mission_template_id' => 'INT NOT NULL',
            'asset_type' => "ENUM('document', 'image', 'video', 'audio', '3d_model', 'code_template', 'instruction_manual') NOT NULL",
            'asset_name' => 'VARCHAR(255) NOT NULL',
            'file_path' => 'VARCHAR(500)',
            'file_size' => 'BIGINT',
            'mime_type' => 'VARCHAR(100)',
            'description' => 'TEXT',
            'usage_instructions' => 'TEXT',
            'download_count' => 'INT DEFAULT 0',
            'is_public' => 'BOOLEAN DEFAULT FALSE',
            'version' => 'VARCHAR(20) DEFAULT "1.0"',
            'upload_date' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'status' => "ENUM('active', 'archived', 'pending_review') DEFAULT 'active'"
        ];
        
        $this->createTable('mission_assets', $columns);
        
        // Add indexes
        $this->addIndex('mission_assets', 'idx_assets_mission', 'mission_template_id');
        $this->addIndex('mission_assets', 'idx_assets_type', 'asset_type');
        $this->addIndex('mission_assets', 'idx_assets_public', 'is_public');
        $this->addIndex('mission_assets', 'idx_assets_status', 'status');
    }
    
    public function down()
    {
        $this->dropTable('mission_assets');
    }
}