#!/usr/bin/env php
<?php
/**
 * Database Setup Script
 * 
 * This script will:
 * 1. Create the database if it doesn't exist
 * 2. Run all migrations
 * 3. Optionally seed the database
 * 
 * Usage:
 *   php setup.php              # Setup with development seeds
 *   php setup.php --prod       # Setup with production seeds only
 *   php setup.php --no-seed    # Setup without seeding
 */

require_once __DIR__ . '/../../app/Core/Migration.php';
require_once __DIR__ . '/../../app/Core/Seeder.php';

class SetupCommand
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
            $this->info("GDE SciBOTICS Database Setup");
            $this->info(str_repeat("=", 40));
            
            // Step 1: Create database
            $this->createDatabase();
            
            // Step 2: Run migrations
            $this->runMigrations();
            
            // Step 3: Seed database (if requested)
            if (!isset($this->options['no-seed'])) {
                $this->seedDatabase();
            }
            
            $this->success("Database setup completed successfully!");
            $this->info("You can now access the application.");
            
        } catch (Exception $e) {
            $this->error("Setup failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    private function createDatabase()
    {
        $this->info("Creating database...");
        
        try {
            // Connect without database name
            $dsn = "mysql:host={$this->config['host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->config['database']}` 
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);
            
            $this->success("Database '{$this->config['database']}' created successfully");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'database exists') !== false) {
                $this->info("Database already exists, continuing...");
            } else {
                throw new Exception("Failed to create database: " . $e->getMessage());
            }
        }
    }
    
    private function runMigrations()
    {
        $this->info("Running migrations...");
        
        // Connect to the specific database
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        
        $manager = new MigrationManager($pdo);
        $manager->migrate();
        
        $this->success("Migrations completed successfully");
    }
    
    private function seedDatabase()
    {
        $this->info("Seeding database...");
        
        $environment = isset($this->options['prod']) ? 'production' : 'development';
        $this->info("Environment: {$environment}");
        
        // Connect to the database
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

// Run the setup command
if (php_sapi_name() === 'cli') {
    $command = new SetupCommand();
    $command->run();
} else {
    echo "This script can only be run from the command line.\n";
    exit(1);
}