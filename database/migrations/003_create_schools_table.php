<?php
// database/migrations/003_create_schools_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateSchoolsTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(200) NOT NULL',
            'emis_number' => 'VARCHAR(20) UNIQUE',
            'registration_number' => 'VARCHAR(50)',
            'district' => 'VARCHAR(100) NOT NULL',
            'province' => "VARCHAR(50) DEFAULT 'Gauteng'",
            'address_line1' => 'VARCHAR(200)',
            'address_line2' => 'VARCHAR(200)',
            'city' => 'VARCHAR(100)',
            'postal_code' => 'VARCHAR(10)',
            'phone' => 'VARCHAR(20)',
            'email' => 'VARCHAR(100)',
            'principal_name' => 'VARCHAR(100)',
            'principal_email' => 'VARCHAR(100)',
            'principal_phone' => 'VARCHAR(20)',
            'coordinator_id' => 'INT',
            'school_type' => "ENUM('primary', 'secondary', 'combined', 'special') NOT NULL",
            'quintile' => 'INT CHECK (quintile BETWEEN 1 AND 5)',
            'total_learners' => 'INT',
            'facilities' => 'TEXT',
            'status' => "ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending'",
            'registration_date' => 'DATE'
        ];
        
        $this->createTable('schools', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci'
        ]);
        
        // Add foreign key
        $this->addForeignKey('schools', 'coordinator_id', 'users', 'id', 'SET NULL');
        
        // Add indexes
        $this->addIndex('schools', 'idx_district', 'district');
        $this->addIndex('schools', 'idx_status', 'status');
        $this->addIndex('schools', 'idx_emis', 'emis_number');
    }
    
    public function down()
    {
        $this->dropTable('schools');
    }
}