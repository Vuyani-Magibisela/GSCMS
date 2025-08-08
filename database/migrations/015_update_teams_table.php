<?php
// database/migrations/015_update_teams_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class UpdateTeamsTable extends Migration
{
    public function up()
    {
        // Check and add phase_id column if it doesn't exist (it already exists, so skip)
        // Phase_id already exists based on table description
        
        // Check and add coach2_id column if it doesn't exist (it already exists, so skip)
        // coach2_id already exists based on table description
        
        // Add temporary status column with new enum values
        $this->execute(
            "ALTER TABLE `teams` ADD COLUMN `status_new` ENUM('registered', 'approved', 'competing', 'eliminated', 'completed') DEFAULT 'registered'",
            "Adding temporary status column with new enum values"
        );
        
        // Map old status values to new enum values
        $this->execute(
            "UPDATE `teams` SET `status_new` = 'registered' WHERE `status` IN ('draft', 'submitted')",
            "Mapping draft/submitted to registered"
        );
        
        $this->execute(
            "UPDATE `teams` SET `status_new` = 'approved' WHERE `status` = 'approved'",
            "Mapping approved status"
        );
        
        $this->execute(
            "UPDATE `teams` SET `status_new` = 'competing' WHERE `status` = 'qualified'",
            "Mapping qualified to competing"
        );
        
        $this->execute(
            "UPDATE `teams` SET `status_new` = 'eliminated' WHERE `status` IN ('rejected', 'eliminated')",
            "Mapping rejected/eliminated to eliminated"
        );
        
        // Drop old status column
        $this->execute(
            "ALTER TABLE `teams` DROP COLUMN `status`",
            "Dropping old status column"
        );
        
        // Rename new status column
        $this->execute(
            "ALTER TABLE `teams` CHANGE `status_new` `status` ENUM('registered', 'approved', 'competing', 'eliminated', 'completed') DEFAULT 'registered'",
            "Renaming status_new to status"
        );
        
        // qualification_score already exists, skip
        // special_requirements already exists, skip
        
        // Add notes column if it doesn't exist
        $this->execute(
            "ALTER TABLE `teams` ADD COLUMN `notes` TEXT NULL",
            "Adding notes column to teams table"
        );
        
        // Add indexes
        $this->execute(
            "ALTER TABLE `teams` ADD INDEX `idx_teams_phase_id` (`phase_id`)",
            "Adding index on phase_id for teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` ADD INDEX `idx_teams_coach2_id` (`coach2_id`)",
            "Adding index on coach2_id for teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` ADD INDEX `idx_teams_status` (`status`)",
            "Adding index on status for teams table"
        );
    }
    
    public function down()
    {
        // Remove indexes first
        $this->execute(
            "ALTER TABLE `teams` DROP INDEX `idx_teams_phase_id`",
            "Removing phase_id index from teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` DROP INDEX `idx_teams_coach2_id`",
            "Removing coach2_id index from teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` DROP INDEX `idx_teams_status`",
            "Removing status index from teams table"
        );
        
        // Remove columns
        $this->execute(
            "ALTER TABLE `teams` DROP COLUMN `phase_id`",
            "Removing phase_id column from teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` DROP COLUMN `coach2_id`",
            "Removing coach2_id column from teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` DROP COLUMN `qualification_score`",
            "Removing qualification_score column from teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` DROP COLUMN `special_requirements`",
            "Removing special_requirements column from teams table"
        );
        
        $this->execute(
            "ALTER TABLE `teams` DROP COLUMN `notes`",
            "Removing notes column from teams table"
        );
        
        // Revert status enum
        $this->execute(
            "ALTER TABLE `teams` MODIFY COLUMN `status` ENUM('active', 'inactive') DEFAULT 'active'",
            "Reverting status enum values for teams table"
        );
    }
}