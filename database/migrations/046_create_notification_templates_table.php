<?php
// database/migrations/046_create_notification_templates_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateNotificationTemplatesTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(100) NOT NULL',
            'type' => 'ENUM("email", "sms", "whatsapp", "push") NOT NULL',
            'trigger_event' => 'VARCHAR(100) NOT NULL',
            'trigger_timing' => 'VARCHAR(50) NOT NULL',
            'subject' => 'VARCHAR(200) NULL',
            'body_template' => 'TEXT NOT NULL',
            'variables' => 'JSON NULL',
            'active' => 'BOOLEAN DEFAULT TRUE',
            'priority' => 'ENUM("low", "normal", "high", "urgent") DEFAULT "normal"',
            'category' => 'VARCHAR(50) DEFAULT "general"',
            'language' => 'VARCHAR(5) DEFAULT "en"',
            'approval_required' => 'BOOLEAN DEFAULT FALSE'
        ];
        
        $this->createTable('notification_templates', $columns);
        
        // Add indexes
        $this->addIndex('notification_templates', 'idx_templates_name', 'name');
        $this->addIndex('notification_templates', 'idx_templates_type', 'type');
        $this->addIndex('notification_templates', 'idx_templates_trigger', 'trigger_event');
        $this->addIndex('notification_templates', 'idx_templates_active', 'active');
        $this->addIndex('notification_templates', 'idx_templates_category', 'category');
        
        // Add composite indexes
        $this->addIndex('notification_templates', 'idx_templates_event_type', 'trigger_event, type');
        $this->addIndex('notification_templates', 'idx_templates_active_type', 'active, type');
    }
    
    public function down()
    {
        $this->dropTable('notification_templates');
    }
}