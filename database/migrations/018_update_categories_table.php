<?php
// database/migrations/018_update_categories_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class UpdateCategoriesTable extends Migration
{
    public function up()
    {
        // Add code column for category codes (JUNIOR, SPIKE, ARDUINO, INVENTOR)
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `code` VARCHAR(20) NOT NULL AFTER `name`",
            "Adding code column to categories table"
        );
        
        // Add description column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `description` TEXT NULL AFTER `code`",
            "Adding description column to categories table"
        );
        
        // Add age range columns
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `min_age` INT UNSIGNED NOT NULL AFTER `description`",
            "Adding min_age column to categories table"
        );
        
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `max_age` INT UNSIGNED NOT NULL AFTER `min_age`",
            "Adding max_age column to categories table"
        );
        
        // Add grade range columns
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `min_grade` VARCHAR(20) NOT NULL AFTER `max_age`",
            "Adding min_grade column to categories table"
        );
        
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `max_grade` VARCHAR(20) NOT NULL AFTER `min_grade`",
            "Adding max_grade column to categories table"
        );
        
        // Add equipment requirements column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `equipment_requirements` TEXT NULL AFTER `max_grade`",
            "Adding equipment_requirements column to categories table"
        );
        
        // Add scoring rubric column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `scoring_rubric` JSON NULL AFTER `equipment_requirements`",
            "Adding scoring_rubric column to categories table"
        );
        
        // Add max teams per school column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `max_teams_per_school` INT UNSIGNED DEFAULT 1 AFTER `scoring_rubric`",
            "Adding max_teams_per_school column to categories table"
        );
        
        // Add competition duration column (in minutes)
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `competition_duration` INT UNSIGNED NULL AFTER `max_teams_per_school`",
            "Adding competition_duration column to categories table"
        );
        
        // Add status column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `status` ENUM('active', 'inactive', 'draft') DEFAULT 'active' AFTER `competition_duration`",
            "Adding status column to categories table"
        );
        
        // Add rules document column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `rules_document` VARCHAR(500) NULL AFTER `status`",
            "Adding rules_document column to categories table"
        );
        
        // Add notes column
        $this->execute(
            "ALTER TABLE `categories` ADD COLUMN `notes` TEXT NULL AFTER `rules_document`",
            "Adding notes column to categories table"
        );
        
        // Add indexes
        $this->execute(
            "ALTER TABLE `categories` ADD UNIQUE INDEX `idx_categories_code` (`code`)",
            "Adding unique index on code for categories table"
        );
        
        $this->execute(
            "ALTER TABLE `categories` ADD INDEX `idx_categories_status` (`status`)",
            "Adding index on status for categories table"
        );
        
        $this->execute(
            "ALTER TABLE `categories` ADD INDEX `idx_categories_age_range` (`min_age`, `max_age`)",
            "Adding index on age range for categories table"
        );
    }
    
    public function down()
    {
        // Remove indexes first
        $this->execute(
            "ALTER TABLE `categories` DROP INDEX `idx_categories_code`",
            "Removing code index from categories table"
        );
        
        $this->execute(
            "ALTER TABLE `categories` DROP INDEX `idx_categories_status`",
            "Removing status index from categories table"
        );
        
        $this->execute(
            "ALTER TABLE `categories` DROP INDEX `idx_categories_age_range`",
            "Removing age range index from categories table"
        );
        
        // Remove columns
        $columns = [
            'code', 'description', 'min_age', 'max_age', 'min_grade', 'max_grade',
            'equipment_requirements', 'scoring_rubric', 'max_teams_per_school',
            'competition_duration', 'status', 'rules_document', 'notes'
        ];
        
        foreach ($columns as $column) {
            $this->execute(
                "ALTER TABLE `categories` DROP COLUMN `{$column}`",
                "Removing {$column} column from categories table"
            );
        }
    }
}