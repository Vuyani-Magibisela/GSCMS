<?php
// database/migrations/027_create_contacts_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateContactsTable extends Migration
{
    public function up()
    {
        $columns = [
            'school_id' => 'INT NOT NULL',
            'contact_type' => "ENUM('principal', 'coordinator', 'deputy', 'administrative', 'it_coordinator', 'security', 'facilities', 'medical', 'other') NOT NULL",
            'title' => 'VARCHAR(10)',
            'first_name' => 'VARCHAR(100) NOT NULL',
            'last_name' => 'VARCHAR(100) NOT NULL',
            'position' => 'VARCHAR(100) NOT NULL',
            'email' => 'VARCHAR(255) NOT NULL',
            'phone' => 'VARCHAR(20)',
            'mobile' => 'VARCHAR(20)',
            'fax' => 'VARCHAR(20)',
            'address' => 'TEXT',
            'is_primary' => 'BOOLEAN DEFAULT FALSE',
            'is_emergency' => 'BOOLEAN DEFAULT FALSE',
            'language_preference' => "ENUM('english', 'afrikaans', 'zulu', 'xhosa', 'sotho', 'tswana', 'pedi', 'venda', 'tsonga', 'ndebele', 'swati') DEFAULT 'english'",
            'communication_preference' => "ENUM('email', 'phone', 'sms', 'whatsapp') DEFAULT 'email'",
            'status' => "ENUM('active', 'inactive') DEFAULT 'active'",
            'notes' => 'TEXT'
        ];
        
        $this->createTable('contacts', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci'
        ]);
        
        // Add indexes
        $this->addIndex('contacts', 'idx_contacts_school', 'school_id');
        $this->addIndex('contacts', 'idx_contacts_type', 'contact_type');
        $this->addIndex('contacts', 'idx_contacts_status', 'status');
        $this->addIndex('contacts', 'idx_contacts_primary', 'is_primary');
        $this->addIndex('contacts', 'idx_contacts_emergency', 'is_emergency');
        $this->addIndex('contacts', 'idx_contacts_email', 'email');
    }
    
    public function down()
    {
        $this->dropTable('contacts');
    }
}