<?php
// database/migrations/102_add_status_to_participants_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddStatusToParticipantsTable extends Migration
{
    public function up()
    {
        // Add status column to participants table
        $this->execute(
            "ALTER TABLE `participants` ADD COLUMN `status` ENUM('active', 'inactive', 'deleted') DEFAULT 'active' AFTER `consent_status`",
            "Adding status column to participants table"
        );
        
        // Add index on status column for performance
        $this->execute(
            "ALTER TABLE `participants` ADD INDEX `idx_participants_status` (`status`)",
            "Adding index on status for participants table"
        );
        
        // Update existing participants to have 'active' status based on consent_status
        // If consent_status is 'approved', set status to 'active'
        // If consent_status is 'pending' or 'rejected', set status to 'inactive'
        $this->execute(
            "UPDATE `participants` SET `status` = CASE 
                WHEN `consent_status` = 'approved' THEN 'active'
                WHEN `consent_status` IN ('pending', 'rejected') THEN 'inactive'
                ELSE 'active'
            END",
            "Setting initial status values for existing participants"
        );
    }
    
    public function down()
    {
        // Remove index first
        $this->execute(
            "ALTER TABLE `participants` DROP INDEX `idx_participants_status`",
            "Removing status index from participants table"
        );
        
        // Remove status column
        $this->execute(
            "ALTER TABLE `participants` DROP COLUMN `status`",
            "Removing status column from participants table"
        );
    }
}