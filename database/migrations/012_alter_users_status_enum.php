<?php
// database/migrations/012_alter_users_status_enum.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AlterUsersStatusEnum extends Migration
{
    public function up()
    {
        // Alter the status column to include 'pending'
        $sql = "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active'";
        $this->execute($sql);
    }
    
    public function down()
    {
        // Revert back to original enum values
        $sql = "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'";
        $this->execute($sql);
    }
}