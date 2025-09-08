<?php
// database/migrations/042_create_training_sessions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateTrainingSessionsTable extends Migration
{
    public function up()
    {
        $columns = [
            'session_date' => 'DATE NOT NULL',
            'day_of_week' => 'ENUM("Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday") NOT NULL',
            'morning_slot_start' => 'TIME DEFAULT "08:30:00"',
            'morning_slot_end' => 'TIME DEFAULT "12:30:00"',
            'afternoon_slot_start' => 'TIME DEFAULT "13:00:00"',
            'afternoon_slot_end' => 'TIME DEFAULT "16:30:00"',
            'morning_activity' => 'VARCHAR(100)',
            'afternoon_activity' => 'VARCHAR(100)',
            'venue_id' => 'INT UNSIGNED NULL',
            'max_capacity' => 'INT DEFAULT 50',
            'registered_teams' => 'INT DEFAULT 0',
            'status' => 'ENUM("available", "full", "cancelled") DEFAULT "available"',
            'notes' => 'TEXT'
        ];
        
        $this->createTable('training_sessions', $columns);
        
        // Add indexes
        $this->addIndex('training_sessions', 'idx_training_date', 'session_date');
        $this->addIndex('training_sessions', 'idx_training_venue', 'venue_id');
        $this->addIndex('training_sessions', 'idx_training_status', 'status');
        $this->addIndex('training_sessions', 'idx_training_day', 'day_of_week');
        
        // Add unique constraint
        $this->addIndex('training_sessions', 'unique_session', 'session_date, venue_id', true);
    }
    
    public function down()
    {
        $this->dropTable('training_sessions');
    }
}