<?php
// database/migrations/112_create_websocket_connections_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateWebsocketConnectionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'connection_id' => 'VARCHAR(100) UNIQUE NOT NULL COMMENT "Unique WebSocket connection identifier"',
            'user_id' => 'INT NULL COMMENT "Connected user ID"',
            'user_type' => 'ENUM("judge", "admin", "spectator", "team") NOT NULL COMMENT "Type of user"',
            'session_id' => 'INT NOT NULL COMMENT "Live scoring session"',
            'ip_address' => 'VARCHAR(45) NOT NULL COMMENT "Client IP address"',
            'user_agent' => 'TEXT NULL COMMENT "Client user agent string"',
            'connected_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT "Connection start time"',
            'last_ping' => 'TIMESTAMP NULL COMMENT "Last ping received"',
            'disconnected_at' => 'TIMESTAMP NULL COMMENT "Connection end time"',
            'total_messages_sent' => 'INT DEFAULT 0 COMMENT "Messages sent to client"',
            'total_messages_received' => 'INT DEFAULT 0 COMMENT "Messages received from client"',
            'connection_quality' => 'ENUM("excellent", "good", "fair", "poor") DEFAULT "good" COMMENT "Connection quality"',
            'bandwidth_usage_mb' => 'DECIMAL(10,3) DEFAULT 0.000 COMMENT "Total bandwidth used"',
            'error_count' => 'INT DEFAULT 0 COMMENT "Number of connection errors"',
            'last_error' => 'TEXT NULL COMMENT "Last error message"',
            'device_type' => 'ENUM("desktop", "tablet", "mobile", "tv") DEFAULT "desktop" COMMENT "Client device type"',
            'browser_type' => 'VARCHAR(50) NULL COMMENT "Client browser type"',
            'connection_metadata' => 'JSON NULL COMMENT "Additional connection data"'
        ];
        
        $this->createTable('websocket_connections', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Active WebSocket connections tracking'
        ]);
        
        // Add indexes for monitoring and cleanup
        $this->addIndex('websocket_connections', 'idx_connection_id', 'connection_id');
        $this->addIndex('websocket_connections', 'idx_session_user', 'session_id, user_type');
        $this->addIndex('websocket_connections', 'idx_connected_at', 'connected_at');
        $this->addIndex('websocket_connections', 'idx_active_connections', 'disconnected_at');
        $this->addIndex('websocket_connections', 'idx_user_session', 'user_id, session_id');
        $this->addIndex('websocket_connections', 'idx_last_ping', 'last_ping');
        
        // Add foreign key constraints
        $this->addForeignKey('websocket_connections', 'fk_wsc_user', 'user_id', 'users', 'id');
        $this->addForeignKey('websocket_connections', 'fk_wsc_session', 'session_id', 'live_scoring_sessions', 'id');
        
        echo "Created websocket_connections table with indexes and constraints.\n";
    }
    
    public function down()
    {
        $this->dropTable('websocket_connections');
        echo "Dropped websocket_connections table.\n";
    }
}