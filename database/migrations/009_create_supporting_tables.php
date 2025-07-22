<?php
// database/migrations/009_create_supporting_tables.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateSupportingTables extends Migration
{
    public function up()
    {
        // User Sessions Table
        $sessionColumns = [
            'id' => 'VARCHAR(128) PRIMARY KEY',
            'user_id' => 'INT NOT NULL',
            'ip_address' => 'VARCHAR(45)',
            'user_agent' => 'TEXT',
            'expires_at' => 'TIMESTAMP NOT NULL'
        ];
        
        $this->createTable('user_sessions', $sessionColumns);
        $this->addForeignKey('user_sessions', 'user_id', 'users', 'id', 'CASCADE');
        $this->addIndex('user_sessions', 'idx_user_id', 'user_id');
        $this->addIndex('user_sessions', 'idx_expires_at', 'expires_at');
        
        // User Activity Log
        $activityColumns = [
            'id' => 'BIGINT AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'INT',
            'action' => 'VARCHAR(100) NOT NULL',
            'resource_type' => 'VARCHAR(50)',
            'resource_id' => 'INT',
            'details' => 'TEXT',
            'ip_address' => 'VARCHAR(45)',
            'user_agent' => 'TEXT'
        ];
        
        $this->createTable('user_activity_log', $activityColumns, ['no_timestamps' => true]);
        $this->execute("ALTER TABLE user_activity_log ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        
        $this->addForeignKey('user_activity_log', 'user_id', 'users', 'id', 'SET NULL');
        $this->addIndex('user_activity_log', 'idx_user_id', 'user_id');
        $this->addIndex('user_activity_log', 'idx_action', 'action');
        $this->addIndex('user_activity_log', 'idx_created_at', 'created_at');
        
        $this->logger->info("Supporting tables created successfully");
    }
    
    public function down()
    {
        $this->dropTable('user_activity_log');
        $this->dropTable('user_sessions');
    }
}