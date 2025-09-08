<?php
// database/migrations/044_create_scheduling_preferences_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateSchedulingPreferencesTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT UNSIGNED NOT NULL',
            'preferred_date' => 'DATE NULL',
            'preferred_time_slot' => 'ENUM("morning", "afternoon", "any") DEFAULT "any"',
            'avoid_dates' => 'TEXT NULL',
            'special_requirements' => 'TEXT NULL',
            'transportation_needed' => 'BOOLEAN DEFAULT FALSE',
            'accommodation_needed' => 'BOOLEAN DEFAULT FALSE',
            'contact_preference' => 'ENUM("email", "sms", "whatsapp", "phone") DEFAULT "email"',
            'emergency_contact_number' => 'VARCHAR(20) NULL',
            'accessibility_requirements' => 'TEXT NULL',
            'dietary_requirements' => 'TEXT NULL',
            'submitted_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ];
        
        $this->createTable('scheduling_preferences', $columns);
        
        // Add indexes
        $this->addIndex('scheduling_preferences', 'idx_prefs_team', 'team_id');
        $this->addIndex('scheduling_preferences', 'idx_prefs_date', 'preferred_date');
        $this->addIndex('scheduling_preferences', 'idx_prefs_slot', 'preferred_time_slot');
        $this->addIndex('scheduling_preferences', 'idx_prefs_transport', 'transportation_needed');
        $this->addIndex('scheduling_preferences', 'idx_prefs_submitted', 'submitted_at');
        
        // Add unique constraint - one preference per team
        $this->addIndex('scheduling_preferences', 'unique_team_preference', 'team_id', true);
    }
    
    public function down()
    {
        $this->dropTable('scheduling_preferences');
    }
}