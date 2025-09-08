<?php
// database/migrations/041_create_calendar_events_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCalendarEventsTable extends Migration
{
    public function up()
    {
        $columns = [
            'phase_id' => 'INT UNSIGNED NOT NULL',
            'event_type' => 'ENUM("training", "competition", "meeting", "deadline", "announcement") NOT NULL',
            'event_name' => 'VARCHAR(200) NOT NULL',
            'event_description' => 'TEXT',
            'start_datetime' => 'DATETIME NOT NULL',
            'end_datetime' => 'DATETIME NOT NULL',
            'venue_id' => 'INT UNSIGNED NULL',
            'category_id' => 'INT UNSIGNED NULL',
            'district_id' => 'INT UNSIGNED NULL',
            'recurrence_rule' => 'VARCHAR(255) NULL',
            'color_code' => 'VARCHAR(7) DEFAULT "#0066CC"',
            'is_mandatory' => 'BOOLEAN DEFAULT FALSE',
            'max_participants' => 'INT NULL',
            'current_participants' => 'INT DEFAULT 0',
            'status' => 'ENUM("scheduled", "in_progress", "completed", "cancelled") DEFAULT "scheduled"',
            'created_by' => 'INT UNSIGNED NOT NULL'
        ];
        
        $this->createTable('calendar_events', $columns);
        
        // Add indexes
        $this->addIndex('calendar_events', 'idx_events_phase', 'phase_id');
        $this->addIndex('calendar_events', 'idx_events_datetime', 'start_datetime, end_datetime');
        $this->addIndex('calendar_events', 'idx_events_venue', 'venue_id');
        $this->addIndex('calendar_events', 'idx_events_category', 'category_id');
        $this->addIndex('calendar_events', 'idx_events_status', 'status');
        $this->addIndex('calendar_events', 'idx_events_type', 'event_type');
        
        // Add composite indexes
        $this->addIndex('calendar_events', 'idx_events_phase_type', 'phase_id, event_type');
        $this->addIndex('calendar_events', 'idx_events_venue_datetime', 'venue_id, start_datetime');
    }
    
    public function down()
    {
        $this->dropTable('calendar_events');
    }
}