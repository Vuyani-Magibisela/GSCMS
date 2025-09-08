<?php
// database/migrations/043_create_time_slots_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateTimeSlotsTable extends Migration
{
    public function up()
    {
        $columns = [
            'event_id' => 'INT UNSIGNED NOT NULL',
            'venue_id' => 'INT UNSIGNED NOT NULL',
            'slot_date' => 'DATE NOT NULL',
            'start_time' => 'TIME NOT NULL',
            'end_time' => 'TIME NOT NULL',
            'slot_type' => 'ENUM("competition", "practice", "judging", "break", "setup") NOT NULL',
            'category_id' => 'INT UNSIGNED NULL',
            'team_id' => 'INT UNSIGNED NULL',
            'judge_panel_id' => 'INT UNSIGNED NULL',
            'table_number' => 'VARCHAR(10) NULL',
            'duration_minutes' => 'INT NOT NULL',
            'buffer_minutes' => 'INT DEFAULT 15',
            'status' => 'ENUM("available", "reserved", "confirmed", "completed") DEFAULT "available"'
        ];
        
        $this->createTable('time_slots', $columns);
        
        // Add indexes
        $this->addIndex('time_slots', 'idx_slots_event', 'event_id');
        $this->addIndex('time_slots', 'idx_slots_venue', 'venue_id');
        $this->addIndex('time_slots', 'idx_slots_datetime', 'slot_date, start_time');
        $this->addIndex('time_slots', 'idx_slots_team', 'team_id');
        $this->addIndex('time_slots', 'idx_slots_category', 'category_id');
        $this->addIndex('time_slots', 'idx_slots_status', 'status');
        $this->addIndex('time_slots', 'idx_slots_type', 'slot_type');
        
        // Add unique constraint for venue/date/time/table combination
        $this->addIndex('time_slots', 'unique_slot', 'venue_id, slot_date, start_time, table_number', true);
        
        // Add composite indexes
        $this->addIndex('time_slots', 'idx_slots_venue_date', 'venue_id, slot_date');
        $this->addIndex('time_slots', 'idx_slots_event_category', 'event_id, category_id');
    }
    
    public function down()
    {
        $this->dropTable('time_slots');
    }
}