<?php
// database/migrations/090_create_organizations_table.php

use App\Core\Database;

class CreateOrganizationsTable
{
    public static function up()
    {
        $db = Database::getInstance();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS organizations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                organization_name VARCHAR(200) NOT NULL,
                organization_type ENUM('educational', 'corporate', 'government', 'ngo', 'other') NOT NULL,
                contact_person VARCHAR(100) NOT NULL,
                contact_email VARCHAR(100) NOT NULL,
                contact_phone VARCHAR(20) NOT NULL,
                address TEXT NULL,
                website VARCHAR(255) NULL,
                partnership_status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
                judges_provided INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_organizations_type (organization_type),
                INDEX idx_organizations_status (partnership_status),
                INDEX idx_organizations_name (organization_name)
            )
        ";
        
        return $db->execute($sql);
    }
    
    public static function down()
    {
        $db = Database::getInstance();
        return $db->execute('DROP TABLE IF EXISTS organizations');
    }
}