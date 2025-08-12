<?php
// database/migrations/027_create_emergency_contacts_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateEmergencyContactsTable extends Migration
{
    public function up()
    {
        $columns = [
            'participant_id' => 'INT UNSIGNED NOT NULL COMMENT "Reference to participants table"',
            'contact_type' => "ENUM('primary_guardian', 'secondary_guardian', 'emergency_contact', 'medical_contact', 'school_contact', 'family_doctor', 'specialist_doctor') NOT NULL COMMENT 'Type of emergency contact'",
            'priority_order' => 'TINYINT UNSIGNED DEFAULT 1 COMMENT "Contact priority order (1 = highest priority)"',
            'name' => 'VARCHAR(255) NOT NULL COMMENT "Contact person full name"',
            'relationship' => 'VARCHAR(100) NULL COMMENT "Relationship to participant"',
            'phone_primary' => 'VARCHAR(20) NOT NULL COMMENT "Primary phone number"',
            'phone_secondary' => 'VARCHAR(20) NULL COMMENT "Secondary phone number"',
            'phone_work' => 'VARCHAR(20) NULL COMMENT "Work phone number"',
            'email' => 'VARCHAR(255) NULL COMMENT "Email address"',
            'address' => 'TEXT NULL COMMENT "Physical address"',
            'availability_hours' => 'JSON NULL COMMENT "Availability hours and preferences"',
            'medical_authority' => 'BOOLEAN DEFAULT FALSE COMMENT "Has medical decision authority"',
            'pickup_authority' => 'BOOLEAN DEFAULT FALSE COMMENT "Authorized to pick up participant"',
            'emergency_only' => 'BOOLEAN DEFAULT FALSE COMMENT "Contact only in emergencies"',
            'preferred_contact_method' => "ENUM('phone', 'sms', 'email', 'whatsapp') DEFAULT 'phone' COMMENT 'Preferred contact method'",
            'language_preference' => 'VARCHAR(50) DEFAULT "english" COMMENT "Preferred communication language"',
            'medical_professional' => 'BOOLEAN DEFAULT FALSE COMMENT "Is this contact a medical professional"',
            'practice_name' => 'VARCHAR(255) NULL COMMENT "Medical practice or organization name"',
            'practice_address' => 'TEXT NULL COMMENT "Medical practice address"',
            'specialization' => 'VARCHAR(255) NULL COMMENT "Medical specialization (if applicable)"',
            'license_number' => 'VARCHAR(100) NULL COMMENT "Medical license number (if applicable)"',
            'hospital_affiliation' => 'VARCHAR(255) NULL COMMENT "Hospital affiliation (if applicable)"',
            'verification_status' => "ENUM('pending', 'verified', 'failed', 'expired') DEFAULT 'pending' COMMENT 'Contact verification status'",
            'verified_at' => 'DATETIME NULL COMMENT "When contact was verified"',
            'verification_method' => 'VARCHAR(50) NULL COMMENT "Method used to verify contact"',
            'last_contact_attempt' => 'DATETIME NULL COMMENT "Last time contact was attempted"',
            'last_successful_contact' => 'DATETIME NULL COMMENT "Last successful contact"',
            'contact_notes' => 'TEXT NULL COMMENT "Additional notes about this contact"',
            'is_active' => 'BOOLEAN DEFAULT TRUE COMMENT "Is this contact currently active"',
            'gdpr_consent' => 'BOOLEAN DEFAULT FALSE COMMENT "Has given GDPR/POPIA consent for data processing"',
            'data_retention_date' => 'DATE NULL COMMENT "Date when contact data should be reviewed"'
        ];
        
        $this->createTable('emergency_contacts', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Emergency contact information for participants with verification and POPIA compliance'
        ]);
        
        // Add indexes for performance
        $this->addIndex('emergency_contacts', 'idx_emergency_participant_id', 'participant_id');
        $this->addIndex('emergency_contacts', 'idx_emergency_contact_type', 'contact_type');
        $this->addIndex('emergency_contacts', 'idx_emergency_priority', 'participant_id, priority_order');
        $this->addIndex('emergency_contacts', 'idx_emergency_verification_status', 'verification_status');
        $this->addIndex('emergency_contacts', 'idx_emergency_medical_authority', 'medical_authority');
        $this->addIndex('emergency_contacts', 'idx_emergency_pickup_authority', 'pickup_authority');
        $this->addIndex('emergency_contacts', 'idx_emergency_active', 'is_active');
        $this->addIndex('emergency_contacts', 'idx_emergency_phone_primary', 'phone_primary');
        $this->addIndex('emergency_contacts', 'idx_emergency_email', 'email');
        $this->addIndex('emergency_contacts', 'idx_emergency_retention_date', 'data_retention_date');
        
        // Add composite indexes for common queries
        $this->addIndex('emergency_contacts', 'idx_emergency_participant_type_priority', 'participant_id, contact_type, priority_order');
        $this->addIndex('emergency_contacts', 'idx_emergency_active_verified', 'is_active, verification_status');
        
        // Add foreign key constraint
        $this->addForeignKey('emergency_contacts', 'fk_emergency_participant_id', 'participant_id', 'participants', 'id', 'CASCADE', 'RESTRICT');
        
        echo "Created emergency_contacts table with comprehensive contact management and verification features.\n";
    }
    
    public function down()
    {
        $this->dropTable('emergency_contacts');
        echo "Dropped emergency_contacts table.\n";
    }
}