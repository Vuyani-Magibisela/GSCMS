<?php
// database/migrations/016_update_participants_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class UpdateParticipantsTable extends Migration
{
    public function up()
    {
        // Add phone column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `phone` VARCHAR(20) NULL AFTER `date_of_birth`",
            "Adding phone column to participants table"
        );
        
        // Add email column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `email` VARCHAR(255) NULL AFTER `phone`",
            "Adding email column to participants table"
        );
        
        // Add address column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `address` TEXT NULL AFTER `email`",
            "Adding address column to participants table"
        );
        
        // Add id_number column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `id_number` VARCHAR(20) NULL AFTER `address`",
            "Adding id_number column to participants table"
        );
        
        // Add emergency_contact_relationship column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `emergency_contact_relationship` VARCHAR(100) NULL AFTER `emergency_contact_phone`",
            "Adding emergency_contact_relationship column to participants table"
        );
        
        // Add special_needs column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `special_needs` TEXT NULL AFTER `dietary_restrictions`",
            "Adding special_needs column to participants table"
        );
        
        // Add consent_status column
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `consent_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `special_needs`",
            "Adding consent_status column to participants table"
        );
        
        // Update gender enum to include 'other'
        $this->execute(
            "ALTER TABLE `participants` MODIFY COLUMN `gender` ENUM('male', 'female', 'other') NOT NULL",
            "Updating gender enum to include 'other' option"
        );
        
        // Add indexes
        $this->execute(
            "ALTER TABLE `participants` ADD INDEX `idx_participants_email` (`email`)",
            "Adding index on email for participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` ADD INDEX `idx_participants_consent_status` (`consent_status`)",
            "Adding index on consent_status for participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` ADD INDEX `idx_participants_grade` (`grade`)",
            "Adding index on grade for participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` ADD INDEX `idx_participants_gender` (`gender`)",
            "Adding index on gender for participants table"
        );
    }
    
    public function down()
    {
        // Remove indexes first
        $this->execute(
            "ALTER TABLE `participants` DROP INDEX `idx_participants_email`",
            "Removing email index from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP INDEX `idx_participants_consent_status`",
            "Removing consent_status index from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP INDEX `idx_participants_grade`",
            "Removing grade index from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP INDEX `idx_participants_gender`",
            "Removing gender index from participants table"
        );
        
        // Remove columns
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `phone`",
            "Removing phone column from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `email`",
            "Removing email column from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `address`",
            "Removing address column from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `id_number`",
            "Removing id_number column from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `emergency_contact_relationship`",
            "Removing emergency_contact_relationship column from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `special_needs`",
            "Removing special_needs column from participants table"
        );
        
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `consent_status`",
            "Removing consent_status column from participants table"
        );
        
        // Revert gender enum
        $this->execute(
            "ALTER TABLE `participants` MODIFY COLUMN `gender` ENUM('male', 'female') NOT NULL",
            "Reverting gender enum to original values"
        );
    }
}