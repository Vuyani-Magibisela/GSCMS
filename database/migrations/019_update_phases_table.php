<?php
// database/migrations/019_update_phases_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class UpdatePhasesTable extends Migration
{
    public function up()
    {
        // Add code column for phase codes (SCHOOL, DISTRICT, PROVINCIAL, NATIONAL)
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `code` VARCHAR(20) NOT NULL AFTER `name`",
            "Adding code column to phases table"
        );
        
        // Add description column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `description` TEXT NULL AFTER `code`",
            "Adding description column to phases table"
        );
        
        // Add order_sequence column for phase ordering
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `order_sequence` INT UNSIGNED NOT NULL AFTER `description`",
            "Adding order_sequence column to phases table"
        );
        
        // Add status column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `status` ENUM('draft', 'registration_open', 'registration_closed', 'active', 'completed', 'cancelled') DEFAULT 'draft' AFTER `order_sequence`",
            "Adding status column to phases table"
        );
        
        // Add registration timeline columns
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `registration_start` DATETIME NULL AFTER `status`",
            "Adding registration_start column to phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `registration_end` DATETIME NULL AFTER `registration_start`",
            "Adding registration_end column to phases table"
        );
        
        // Add competition timeline columns
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `competition_start` DATETIME NULL AFTER `registration_end`",
            "Adding competition_start column to phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `competition_end` DATETIME NULL AFTER `competition_start`",
            "Adding competition_end column to phases table"
        );
        
        // Add qualification criteria column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `qualification_criteria` JSON NULL AFTER `competition_end`",
            "Adding qualification_criteria column to phases table"
        );
        
        // Add max_teams column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `max_teams` INT UNSIGNED NULL AFTER `qualification_criteria`",
            "Adding max_teams column to phases table"
        );
        
        // Add venue requirements column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `venue_requirements` JSON NULL AFTER `max_teams`",
            "Adding venue_requirements column to phases table"
        );
        
        // Add requires_qualification column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `requires_qualification` BOOLEAN DEFAULT FALSE AFTER `venue_requirements`",
            "Adding requires_qualification column to phases table"
        );
        
        // Add advancement_percentage column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `advancement_percentage` DECIMAL(5,2) DEFAULT 0 AFTER `requires_qualification`",
            "Adding advancement_percentage column to phases table"  
        );
        
        // Add notes column
        $this->execute(
            "ALTER TABLE `phases` ADD COLUMN `notes` TEXT NULL AFTER `advancement_percentage`",
            "Adding notes column to phases table"
        );
        
        // Add indexes
        $this->execute(
            "ALTER TABLE `phases` ADD UNIQUE INDEX `idx_phases_code` (`code`)",
            "Adding unique index on code for phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` ADD INDEX `idx_phases_order_sequence` (`order_sequence`)",
            "Adding index on order_sequence for phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` ADD INDEX `idx_phases_status` (`status`)",
            "Adding index on status for phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` ADD INDEX `idx_phases_registration_dates` (`registration_start`, `registration_end`)",
            "Adding index on registration dates for phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` ADD INDEX `idx_phases_competition_dates` (`competition_start`, `competition_end`)",
            "Adding index on competition dates for phases table"
        );
    }
    
    public function down()
    {
        // Remove indexes first
        $this->execute(
            "ALTER TABLE `phases` DROP INDEX `idx_phases_code`",
            "Removing code index from phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` DROP INDEX `idx_phases_order_sequence`",
            "Removing order_sequence index from phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` DROP INDEX `idx_phases_status`",
            "Removing status index from phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` DROP INDEX `idx_phases_registration_dates`",
            "Removing registration dates index from phases table"
        );
        
        $this->execute(
            "ALTER TABLE `phases` DROP INDEX `idx_phases_competition_dates`",
            "Removing competition dates index from phases table"
        );
        
        // Remove columns
        $columns = [
            'code', 'description', 'order_sequence', 'status', 'registration_start',
            'registration_end', 'competition_start', 'competition_end', 'qualification_criteria',
            'max_teams', 'venue_requirements', 'requires_qualification', 'advancement_percentage', 'notes'
        ];
        
        foreach ($columns as $column) {
            $this->execute(
                "ALTER TABLE `phases` DROP COLUMN `{$column}`",
                "Removing {$column} column from phases table"
            );
        }
    }
}