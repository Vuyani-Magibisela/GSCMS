<?php
// database/migrations/008_create_participants_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateParticipantsTable extends Migration
{
    public function up()
    {
        $columns = [
            'team_id' => 'INT NOT NULL',
            'user_id' => 'INT',
            'first_name' => 'VARCHAR(50) NOT NULL',
            'last_name' => 'VARCHAR(50) NOT NULL',
            'id_number' => 'VARCHAR(20)',
            'date_of_birth' => 'DATE NOT NULL',
            'grade' => 'INT NOT NULL',
            'gender' => "ENUM('male', 'female', 'other', 'prefer_not_to_say') NOT NULL",
            'email' => 'VARCHAR(100)',
            'phone' => 'VARCHAR(20)',
            'parent_guardian_name' => 'VARCHAR(100) NOT NULL',
            'parent_guardian_phone' => 'VARCHAR(20) NOT NULL',
            'parent_guardian_email' => 'VARCHAR(100)',
            'medical_conditions' => 'TEXT',
            'dietary_requirements' => 'TEXT',
            'special_needs' => 'TEXT',
            'consent_form_signed' => 'BOOLEAN DEFAULT FALSE',
            'consent_form_path' => 'VARCHAR(255)',
            'photo_permission' => 'BOOLEAN DEFAULT FALSE',
            'media_permission' => 'BOOLEAN DEFAULT FALSE',
            'emergency_contact_name' => 'VARCHAR(100)',
            'emergency_contact_phone' => 'VARCHAR(20)',
            'emergency_contact_relationship' => 'VARCHAR(50)',
            'previous_experience' => 'TEXT',
            'skills_assessment' => 'TEXT',
            't_shirt_size' => "ENUM('XS', 'S', 'M', 'L', 'XL', 'XXL')"
        ];
        
        $this->createTable('participants', $columns);
        
        // Add foreign keys
        $this->addForeignKey('participants', 'team_id', 'teams', 'id', 'CASCADE');
        $this->addForeignKey('participants', 'user_id', 'users', 'id', 'SET NULL');
        
        // Add indexes
        $this->addIndex('participants', 'idx_team', 'team_id');
        $this->addIndex('participants', 'idx_grade', 'grade');
        $this->addIndex('participants', 'idx_gender', 'gender');
        $this->addIndex('participants', 'idx_consent', 'consent_form_signed');
    }
    
    public function down()
    {
        $this->dropTable('participants');
    }
}