<?php
// database/migrations/047_create_scheduled_notifications_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateScheduledNotificationsTable extends Migration
{
    public function up()
    {
        $columns = [
            'template_id' => 'INT UNSIGNED NOT NULL',
            'recipient_type' => 'ENUM("team", "school", "judge", "volunteer", "all", "user") NOT NULL',
            'recipient_id' => 'INT UNSIGNED NULL',
            'recipient_email' => 'VARCHAR(255) NULL',
            'recipient_phone' => 'VARCHAR(20) NULL',
            'recipient_whatsapp' => 'VARCHAR(20) NULL',
            'scheduled_for' => 'DATETIME NOT NULL',
            'data' => 'JSON NULL',
            'status' => 'ENUM("pending", "sent", "failed", "cancelled") DEFAULT "pending"',
            'attempts' => 'INT DEFAULT 0',
            'max_attempts' => 'INT DEFAULT 3',
            'sent_at' => 'TIMESTAMP NULL',
            'error_message' => 'TEXT NULL',
            'delivery_method' => 'VARCHAR(20) NULL',
            'batch_id' => 'VARCHAR(50) NULL',
            'priority' => 'ENUM("low", "normal", "high", "urgent") DEFAULT "normal"'
        ];
        
        $this->createTable('scheduled_notifications', $columns);
        
        // Add indexes
        $this->addIndex('scheduled_notifications', 'idx_notifications_template', 'template_id');
        $this->addIndex('scheduled_notifications', 'idx_notifications_scheduled', 'scheduled_for, status');
        $this->addIndex('scheduled_notifications', 'idx_notifications_recipient', 'recipient_type, recipient_id');
        $this->addIndex('scheduled_notifications', 'idx_notifications_status', 'status');
        $this->addIndex('scheduled_notifications', 'idx_notifications_batch', 'batch_id');
        $this->addIndex('scheduled_notifications', 'idx_notifications_priority', 'priority, scheduled_for');
        
        // Add composite indexes
        $this->addIndex('scheduled_notifications', 'idx_notifications_pending', 'status, scheduled_for');
        $this->addIndex('scheduled_notifications', 'idx_notifications_failed', 'status, attempts, max_attempts');
    }
    
    public function down()
    {
        $this->dropTable('scheduled_notifications');
    }
}