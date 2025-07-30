<?php
// database/migrations/014_add_school_id_to_users.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddSchoolIdToUsers extends Migration
{
    public function up()
    {
        // Add school_id column to users table for coordinators
        $this->execute(
            "ALTER TABLE `users` ADD COLUMN `school_id` INT UNSIGNED NULL AFTER `role`",
            "Adding school_id column to users table"
        );
        
        // Add index for school_id
        $this->execute(
            "ALTER TABLE `users` ADD INDEX `idx_users_school_id` (`school_id`)",
            "Adding index on school_id for users table"
        );
        
        // Add foreign key constraint (will be done separately to avoid issues)
        // This will be handled in a later migration after all tables are updated
    }
    
    public function down()
    {
        // Remove foreign key constraint first (if exists)
        $this->execute(
            "ALTER TABLE `users` DROP FOREIGN KEY IF EXISTS `fk_users_school_id`",
            "Removing foreign key constraint from users.school_id"
        );
        
        // Remove index
        $this->execute(
            "ALTER TABLE `users` DROP INDEX `idx_users_school_id`",
            "Removing school_id index from users table"
        );
        
        // Remove column
        $this->execute(
            "ALTER TABLE `users` DROP COLUMN `school_id`",
            "Removing school_id column from users table"
        );
    }
}