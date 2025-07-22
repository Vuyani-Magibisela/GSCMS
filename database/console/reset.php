#!/usr/bin/env php
<?php
/**
 * Database Reset Script
 * 
 * WARNING: This script will completely reset your database!
 * Use with extreme caution, especially in production.
 * 
 * Usage:
 *   php reset.php               # Reset and re-seed development
 *   php reset.php --prod        # Reset and re-seed production
 *   php reset.php --no-seed     # Reset without seeding
 */

require_once __DIR__ . '/../../app/Core/Migration.php';
require_once __DIR__ . '/../../app/Core/Seeder.php';

class ResetCommand
{
    private $options;
    private $config;
    
    public function __construct()
    {
        $this->parseOptions();
        $this->config = require __DIR__ . '/../../config/database.php';
    }
    
    public function run()
    {
        try {
            $this->warning("DATABASE RESET WARNING!");
            $this->warning("This will completely destroy all data in the database!");
            
            if (isset($this->options['prod'])) {
                $this->error("Production reset is extremely dangerous!");
                $this->warning("Make sure you have a complete backup before proceeding.");
            }
            
            echo "Are you absolutely sure you want to continue? Type 'RESET' to confirm: ";
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            fclose($handle);
            
            if ($confirmation !== 'RESET') {
                $this->info("Reset cancelled. No changes made.");
                exit(0);
            }
            
            $this->info("Starting database reset...");
            
            // Step 1: Drop and recreate database
            $this->resetDatabase();
            
            // Step 2: Run migrations
            $this->runMigrations();
            
            // Step 3: Seed database (if requested)
            if (!isset($this->options['no-seed'])) {
                $this->seedDatabase();
            }
            
            $this->success("Database reset completed successfully!");
            
        } catch (Exception $e) {
            $this->error("Reset failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    private function resetDatabase()
    {
        $this->info("Dropping and recreating database...");
        
        // Connect without database name
        $dsn = "mysql:host={$this->config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Drop database
        $pdo->exec("DROP DATABASE IF EXISTS `{$this->config['database']}`");
        
        // Recreate database
        $sql = "CREATE DATABASE `{$this->config['database']}` 
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        
        $this->success("Database reset successfully");
    }
    
    private function runMigrations()
    {
        $this->info("Running migrations...");
        
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        
        $manager = new MigrationManager($pdo);
        $manager->migrate();
        
        $this->success("Migrations completed");
    }
    
    private function seedDatabase()
    {
        $this->info("Seeding database...");
        
        $environment = isset($this->options['prod']) ? 'production' : 'development';
        
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        
        $manager = new SeederManager($pdo, $environment);
        $manager->run();
        
        $this->success("Database seeded successfully");
    }
    
    private function parseOptions()
    {
        $this->options = [];
        $args = array_slice($_SERVER['argv'], 1);
        
        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                $parts = explode('=', substr($arg, 2), 2);
                $this->options[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
            }
        }
    }
    
    private function info($message)
    {
        echo "\033[36m[INFO]\033[0m {$message}\n";
    }
    
    private function success($message)
    {
        echo "\033[32m[SUCCESS]\033[0m {$message}\n";
    }
    
    private function error($message)
    {
        echo "\033[31m[ERROR]\033[0m {$message}\n";
    }
    
    private function warning($message)
    {
        echo "\033[33m[WARNING]\033[0m {$message}\n";
    }
}

// Run the reset command
if (php_sapi_name() === 'cli') {
    $command = new ResetCommand();
    $command->run();
} else {
    echo "This script can only be run from the command line.\n";
    exit(1);
}