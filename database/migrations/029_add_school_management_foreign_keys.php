<?php
// database/migrations/029_add_school_management_foreign_keys.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddSchoolManagementForeignKeys extends Migration
{
    public function up()
    {
        // Add foreign key constraints
        
        // Districts table foreign keys
        $this->addForeignKey('districts', 'coordinator_id', 'users', 'id', 'SET NULL');
        
        // Contacts table foreign keys
        $this->addForeignKey('contacts', 'school_id', 'schools', 'id', 'CASCADE');
        
        // Schools table foreign keys (only if districts table has data)
        // We'll add this conditionally
        try {
            // Check if districts table has data
            $districtCount = $this->db->table('districts')->count();
            if ($districtCount > 0) {
                $this->addForeignKey('schools', 'district_id', 'districts', 'id', 'SET NULL');
            }
        } catch (Exception $e) {
            // Districts table might not have data yet, skip for now
        }
        
        $this->addForeignKey('schools', 'coordinator_id', 'users', 'id', 'SET NULL');
    }
    
    public function down()
    {
        // Remove foreign key constraints
        $this->removeForeignKey('districts', 'districts_coordinator_id_foreign');
        $this->removeForeignKey('contacts', 'contacts_school_id_foreign');
        
        try {
            $this->removeForeignKey('schools', 'schools_district_id_foreign');
        } catch (Exception $e) {
            // Foreign key might not exist
        }
        
        try {
            $this->removeForeignKey('schools', 'schools_coordinator_id_foreign');
        } catch (Exception $e) {
            // Foreign key might not exist
        }
    }
}