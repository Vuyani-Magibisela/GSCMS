<?php
// database/migrations/001_create_migrations_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateMigrationsTable extends Migration
{
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_migration` (`migration`)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        
        $this->execute($sql, "Creating migrations tracking table");
    }
    
    public function down()
    {
        $this->dropTable('migrations');
    }
}