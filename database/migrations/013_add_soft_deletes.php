<?php
// database/migrations/013_add_soft_deletes.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class AddSoftDeletes extends Migration
{
    public function up()
    {
        // Add deleted_at column to all tables that need soft deletes
        $tables = [
            'users',
            'schools', 
            'teams',
            'participants',
            'categories',
            'phases',
            'competitions'
        ];
        
        foreach ($tables as $table) {
            $this->execute(
                "ALTER TABLE `{$table}` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`",
                "Adding deleted_at column to {$table} table"
            );
            
            // Add index for soft delete queries
            $this->execute(
                "ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_deleted_at` (`deleted_at`)",
                "Adding index on deleted_at for {$table} table"
            );
        }
    }
    
    public function down()
    {
        // Remove deleted_at columns and indexes
        $tables = [
            'users',
            'schools', 
            'teams',
            'participants',
            'categories',
            'phases',
            'competitions'
        ];
        
        foreach ($tables as $table) {
            $this->execute(
                "ALTER TABLE `{$table}` DROP INDEX `idx_{$table}_deleted_at`",
                "Removing deleted_at index from {$table} table"
            );
            
            $this->execute(
                "ALTER TABLE `{$table}` DROP COLUMN `deleted_at`",
                "Removing deleted_at column from {$table} table"
            );
        }
    }
}