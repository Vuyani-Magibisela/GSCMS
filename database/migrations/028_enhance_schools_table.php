<?php
// database/migrations/028_enhance_schools_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class EnhanceSchoolsTable extends Migration
{
    public function up()
    {
        // First, let's add the new columns that don't exist yet
        $this->addColumn('schools', 'emis_number', 'VARCHAR(20) UNIQUE AFTER name');
        $this->addColumn('schools', 'school_type', "ENUM('primary', 'secondary', 'combined', 'special') NOT NULL DEFAULT 'combined' AFTER emis_number");
        $this->addColumn('schools', 'quintile', 'INT CHECK (quintile BETWEEN 1 AND 5) AFTER school_type');
        $this->addColumn('schools', 'district_id', 'INT AFTER quintile');
        
        // Address fields
        $this->addColumn('schools', 'address_line1', 'VARCHAR(200) AFTER district_id');
        $this->addColumn('schools', 'address_line2', 'VARCHAR(200) AFTER address_line1');
        $this->addColumn('schools', 'city', 'VARCHAR(100) AFTER address_line2');
        $this->addColumn('schools', 'fax', 'VARCHAR(20) AFTER phone');
        $this->addColumn('schools', 'website', 'VARCHAR(255) AFTER email');
        $this->addColumn('schools', 'gps_coordinates', 'VARCHAR(100) AFTER website');
        
        // Principal information
        $this->addColumn('schools', 'principal_phone', 'VARCHAR(20) AFTER principal_email');
        $this->addColumn('schools', 'coordinator_id', 'INT AFTER principal_phone');
        
        // Additional school information
        $this->addColumn('schools', 'establishment_date', 'DATE AFTER coordinator_id');
        $this->addColumn('schools', 'facilities', 'TEXT AFTER total_learners');
        $this->addColumn('schools', 'computer_lab', 'TEXT AFTER facilities');
        $this->addColumn('schools', 'internet_status', 'VARCHAR(100) AFTER computer_lab');
        $this->addColumn('schools', 'accessibility_features', 'TEXT AFTER internet_status');
        $this->addColumn('schools', 'previous_participation', 'TEXT AFTER accessibility_features');
        $this->addColumn('schools', 'communication_preference', "ENUM('email', 'phone', 'sms', 'postal') DEFAULT 'email' AFTER previous_participation");
        $this->addColumn('schools', 'logo_path', 'VARCHAR(255) AFTER communication_preference');
        $this->addColumn('schools', 'approval_date', 'DATETIME AFTER registration_date');
        
        // Update existing data to match new structure
        // Move address to address_line1
        $this->execute("UPDATE schools SET address_line1 = address WHERE address_line1 IS NULL AND address IS NOT NULL");
        
        // Move district to district_id (we'll need to create districts first)
        // This will be handled in the seeder or a separate migration
        
        // Update status enum to include new values
        $this->execute("ALTER TABLE schools MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'suspended', 'archived') DEFAULT 'pending'");
        
        // Add new indexes
        $this->addIndex('schools', 'idx_schools_emis', 'emis_number');
        $this->addIndex('schools', 'idx_schools_type', 'school_type');
        $this->addIndex('schools', 'idx_schools_quintile', 'quintile');
        $this->addIndex('schools', 'idx_schools_district_id', 'district_id');
        $this->addIndex('schools', 'idx_schools_coordinator', 'coordinator_id');
        $this->addIndex('schools', 'idx_schools_city', 'city');
        
        // We'll add foreign key constraints in a separate migration after populating districts
    }
    
    public function down()
    {
        // Remove the columns added in this migration
        $columns_to_remove = [
            'emis_number', 'school_type', 'quintile', 'district_id',
            'address_line1', 'address_line2', 'city', 'fax', 'website', 'gps_coordinates',
            'principal_phone', 'coordinator_id', 'establishment_date',
            'facilities', 'computer_lab', 'internet_status', 'accessibility_features',
            'previous_participation', 'communication_preference', 'logo_path', 'approval_date'
        ];
        
        foreach ($columns_to_remove as $column) {
            $this->removeColumn('schools', $column);
        }
        
        // Revert status enum to original values
        $this->execute("ALTER TABLE schools MODIFY COLUMN status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending'");
        
        // Remove indexes
        $indexes_to_remove = [
            'idx_schools_emis', 'idx_schools_type', 'idx_schools_quintile', 
            'idx_schools_district_id', 'idx_schools_coordinator', 'idx_schools_city'
        ];
        
        foreach ($indexes_to_remove as $index) {
            try {
                $this->removeIndex('schools', $index);
            } catch (Exception $e) {
                // Index might not exist, continue
            }
        }
    }
}