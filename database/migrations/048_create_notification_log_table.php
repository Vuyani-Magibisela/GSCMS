<?php
// database/migrations/048_create_notification_log_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateNotificationLogTable extends Migration
{
    public function up()
    {
        $columns = [
            'notification_id' => 'INT UNSIGNED NOT NULL',
            'channel' => 'ENUM("email", "sms", "whatsapp", "push") NOT NULL',
            'recipient' => 'VARCHAR(255) NOT NULL',
            'status' => 'ENUM("delivered", "failed", "bounced", "opened", "clicked") NOT NULL',
            'delivered_at' => 'TIMESTAMP NULL',
            'opened_at' => 'TIMESTAMP NULL',
            'clicked_at' => 'TIMESTAMP NULL',
            'error_details' => 'TEXT NULL',
            'provider_response' => 'JSON NULL',
            'cost' => 'DECIMAL(10,4) DEFAULT 0.0000',
            'tracking_id' => 'VARCHAR(100) NULL',
            'user_agent' => 'TEXT NULL',
            'ip_address' => 'VARCHAR(45) NULL'
        ];
        
        $this->createTable('notification_log', $columns);
        
        // Add indexes
        $this->addIndex('notification_log', 'idx_log_notification', 'notification_id');
        $this->addIndex('notification_log', 'idx_log_channel', 'channel');
        $this->addIndex('notification_log', 'idx_log_status', 'status');
        $this->addIndex('notification_log', 'idx_log_recipient', 'recipient');
        $this->addIndex('notification_log', 'idx_log_delivered', 'delivered_at');
        $this->addIndex('notification_log', 'idx_log_tracking', 'tracking_id');
        
        // Add composite indexes
        $this->addIndex('notification_log', 'idx_log_notification_channel', 'notification_id, channel');
        $this->addIndex('notification_log', 'idx_log_status_delivered', 'status, delivered_at');
    }
    
    public function down()
    {
        $this->dropTable('notification_log');
    }
}