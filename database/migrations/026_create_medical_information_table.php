<?php
// database/migrations/026_create_medical_information_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateMedicalInformationTable extends Migration
{
    public function up()
    {
        $columns = [
            'participant_id' => 'INT UNSIGNED NOT NULL COMMENT "Reference to participants table"',
            'allergies_encrypted' => 'TEXT NULL COMMENT "AES-256 encrypted allergy information"',
            'medical_conditions_encrypted' => 'TEXT NULL COMMENT "AES-256 encrypted medical conditions"',
            'medications_encrypted' => 'TEXT NULL COMMENT "AES-256 encrypted current medications and dosages"',
            'medical_aid_info' => 'VARCHAR(255) NULL COMMENT "Medical aid/insurance provider name"',
            'medical_aid_number_encrypted' => 'VARCHAR(500) NULL COMMENT "Encrypted medical aid number"',
            'dietary_requirements' => 'TEXT NULL COMMENT "Special dietary needs and restrictions"',
            'physical_limitations' => 'TEXT NULL COMMENT "Physical limitations or mobility issues"',
            'learning_difficulties' => 'TEXT NULL COMMENT "Learning difficulties or special educational needs"',
            'accessibility_needs' => 'TEXT NULL COMMENT "Accessibility requirements and accommodations"',
            'behavioral_support' => 'TEXT NULL COMMENT "Behavioral support requirements"',
            'additional_supervision' => 'BOOLEAN DEFAULT FALSE COMMENT "Requires additional supervision"',
            'special_instructions' => 'TEXT NULL COMMENT "Special care instructions"',
            'emergency_instructions_encrypted' => 'TEXT NULL COMMENT "Encrypted emergency medical instructions"',
            'consent_medical_treatment' => 'BOOLEAN DEFAULT FALSE COMMENT "Consent for emergency medical treatment"',
            'consent_medication_admin' => 'BOOLEAN DEFAULT FALSE COMMENT "Consent for medication administration"',
            'data_sharing_consent' => 'BOOLEAN DEFAULT FALSE COMMENT "Consent for medical data sharing"',
            'validation_status' => "ENUM('pending', 'validated', 'requires_review', 'rejected') DEFAULT 'pending' COMMENT 'Medical data validation status'",
            'validation_notes' => 'JSON NULL COMMENT "Validation notes and feedback"',
            'validated_at' => 'DATETIME NULL COMMENT "When medical data was validated"',
            'validated_by' => 'INT UNSIGNED NULL COMMENT "User who validated the medical data"',
            'last_updated_by' => 'INT UNSIGNED NULL COMMENT "User who last updated this record"',
            'access_level' => "ENUM('private', 'medical_staff', 'emergency_only', 'authorized_personnel') DEFAULT 'private' COMMENT 'Access level for medical data'",
            'retention_date' => 'DATE NULL COMMENT "Date when this data should be reviewed for retention"',
            'encryption_key_version' => 'INT UNSIGNED DEFAULT 1 COMMENT "Version of encryption key used"'
        ];
        
        $this->createTable('medical_information', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Encrypted medical information for participants (POPIA compliant)'
        ]);
        
        // Add indexes for performance and security
        $this->addIndex('medical_information', 'idx_medical_participant_id', 'participant_id');
        $this->addIndex('medical_information', 'idx_medical_validation_status', 'validation_status');
        $this->addIndex('medical_information', 'idx_medical_validated_by', 'validated_by');
        $this->addIndex('medical_information', 'idx_medical_access_level', 'access_level');
        $this->addIndex('medical_information', 'idx_medical_retention_date', 'retention_date');
        $this->addIndex('medical_information', 'idx_medical_created_at', 'created_at');
        
        // Add foreign key constraints
        $this->addForeignKey('medical_information', 'fk_medical_participant_id', 'participant_id', 'participants', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('medical_information', 'fk_medical_validated_by', 'validated_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->addForeignKey('medical_information', 'fk_medical_updated_by', 'last_updated_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        
        echo "Created medical_information table with encryption and POPIA compliance features.\n";
    }
    
    public function down()
    {
        $this->dropTable('medical_information');
        echo "Dropped medical_information table.\n";
    }
}