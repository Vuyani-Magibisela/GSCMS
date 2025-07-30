<?php
// database/migrations/020_update_competitions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class UpdateCompetitionsTable extends Migration
{
    public function up()
    {
        // Update the competitions table structure to match our enhanced model
        
        // Add description column
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `description` TEXT NULL AFTER `name`",
            "Adding description column to competitions table"
        );
        
        // Add venue_id column (replacing venue_name, venue_address, venue_capacity)
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `venue_id` INT UNSIGNED NULL AFTER `description`",
            "Adding venue_id column to competitions table"
        );
        
        // Rename date to start_date and add end_date
        $this->execute(
            "ALTER TABLE `competitions` CHANGE COLUMN `date` `start_date` DATETIME NOT NULL",
            "Renaming date column to start_date in competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `end_date` DATETIME NOT NULL AFTER `start_date`",
            "Adding end_date column to competitions table"
        );
        
        // Remove old venue columns (we'll use venue_id instead)
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `venue_name`",
            "Removing venue_name column from competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `venue_address`",
            "Removing venue_address column from competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `venue_capacity`",
            "Removing venue_capacity column from competitions table"
        );
        
        // Remove old time columns (incorporated into start_date/end_date)
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `start_time`",
            "Removing start_time column from competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `end_time`",
            "Removing end_time column from competitions table"
        );
        
        // Rename max_participants to max_teams
        $this->execute(
            "ALTER TABLE `competitions` CHANGE COLUMN `max_participants` `max_teams` INT UNSIGNED NULL",
            "Renaming max_participants to max_teams in competitions table"
        );
        
        // Remove current_participants (will be calculated dynamically)
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `current_participants`",
            "Removing current_participants column from competitions table"
        );
        
        // Update status enum
        $this->execute(
            "ALTER TABLE `competitions` MODIFY COLUMN `status` ENUM('draft', 'scheduled', 'active', 'completed', 'cancelled') DEFAULT 'draft'",
            "Updating status enum for competitions table"
        );
        
        // Add competition_format column
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `competition_format` ENUM('single_elimination', 'double_elimination', 'round_robin', 'swiss', 'time_trial') NULL AFTER `status`",
            "Adding competition_format column to competitions table"
        );
        
        // Rename entry_requirements to requirements
        $this->execute(
            "ALTER TABLE `competitions` CHANGE COLUMN `entry_requirements` `requirements` JSON NULL",
            "Renaming entry_requirements to requirements in competitions table"
        );
        
        // Rename competition_rules to rules_document
        $this->execute(
            "ALTER TABLE `competitions` CHANGE COLUMN `competition_rules` `rules_document` VARCHAR(500) NULL",
            "Renaming competition_rules to rules_document in competitions table"
        );
        
        // Add notes column
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `notes` TEXT NULL AFTER `rules_document`",
            "Adding notes column to competitions table"
        );
        
        // Remove old contact columns (these should be managed separately)
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `prizes`",
            "Removing prizes column from competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `contact_person`",
            "Removing contact_person column from competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `contact_email`",
            "Removing contact_email column from competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `contact_phone`",
            "Removing contact_phone column from competitions table"
        );
        
        // Remove year column (can be derived from dates)
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `year`",
            "Removing year column from competitions table"
        );
        
        // Remove category_id (competitions are phase-based, teams have categories)
        $this->execute(
            "ALTER TABLE `competitions` DROP COLUMN `category_id`",
            "Removing category_id column from competitions table"
        );
        
        // Add indexes
        $this->execute(
            "ALTER TABLE `competitions` ADD INDEX `idx_competitions_venue_id` (`venue_id`)",
            "Adding index on venue_id for competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD INDEX `idx_competitions_dates` (`start_date`, `end_date`)",
            "Adding index on dates for competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD INDEX `idx_competitions_status` (`status`)",
            "Adding index on status for competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD INDEX `idx_competitions_format` (`competition_format`)",
            "Adding index on competition_format for competitions table"
        );
    }
    
    public function down()
    {
        // This is complex to reverse, but here's the basic structure
        // Add back the old columns
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `year` INT NOT NULL AFTER `name`",
            "Adding back year column to competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `category_id` INT UNSIGNED NOT NULL AFTER `phase_id`",
            "Adding back category_id column to competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `venue_name` VARCHAR(255) NOT NULL AFTER `venue_id`",
            "Adding back venue_name column to competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `venue_address` TEXT AFTER `venue_name`",
            "Adding back venue_address column to competitions table"
        );
        
        $this->execute(
            "ALTER TABLE `competitions` ADD COLUMN `venue_capacity` INT UNSIGNED AFTER `venue_address`",
            "Adding back venue_capacity column to competitions table"
        );
        
        // Note: Full reversal would be quite complex, so this is a simplified version
        // In practice, you might want to create a new migration instead of rolling this back
    }
}