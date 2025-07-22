#!/usr/bin/env php
<?php
// database/console/seed.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Seeder.php';

/**
 * Seeding Console Command
 * 
 * Usage:
 *   php seed.php                    # Run all seeders for development environment
 *   php seed.php --env=production   # Run seeders for production environment
 *   php seed.php --class=UserSeeder # Run specific seeder class
 *   php seed.php --fresh            # Truncate tables before seeding
 */

class SeedCommand
{
    private $manager;
    private $options;
    
    public function __construct()
    {
        $this->parseOptions();
        $this->initializeManager();
    }
    
    public function run()
    {
        try {
            if (isset($this->options['help'])) {
                $this->showHelp();
                return;
            }
            
            $environment = $this->options['env'] ?? 'development';
            $specificClass = $this->options['class'] ?? null;
            $fresh = isset($this->options['fresh']);
            
            if ($fresh) {
                $this->confirmFreshSeed($environment);
            }
            
            $this->info("Starting seeding process...");
            $this->info("Environment: {$environment}");
            
            if ($specificClass) {
                $this->info("Running specific seeder: {$specificClass}");
            }
            
            $this->manager->run($specificClass);
            $this->success("Seeding completed successfully!");
            
        } catch (Exception $e) {
            $this->error("Seeding failed: " . $e->getMessage());
            exit(1);
        }
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
    
    private function initializeManager()
    {
        try {
            // Get database connection from config
            $config = require __DIR__ . '/../../config/database.php';
            
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            $environment = $this->options['env'] ?? 'development';
            $this->manager = new SeederManager($pdo, $environment);
            
        } catch (Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    private function confirmFreshSeed($environment)
    {
        $this->warning("WARNING: Fresh seeding will truncate existing data!");
        
        if ($environment === 'production') {
            $this->error("Fresh seeding is not allowed in production environment!");
            exit(1);
        }
        
        echo "Are you sure you want to continue? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirmation) !== 'yes') {
            $this->info("Seeding cancelled.");
            exit(0);
        }
    }
    
    private function showHelp()
    {
        echo "\033[32mGDE SciBOTICS Seeding Tool\033[0m\n\n";
        echo "Usage:\n";
        echo "  php seed.php [options]\n\n";
        echo "Options:\n";
        echo "  --help                Show this help message\n";
        echo "  --env=environment     Set environment (development|production)\n";
        echo "  --class=SeederClass   Run specific seeder class\n";
        echo "  --fresh               Truncate tables before seeding (dev only)\n\n";
        echo "Examples:\n";
        echo "  php seed.php                      Run development seeders\n";
        echo "  php seed.php --env=production     Run production seeders\n";
        echo "  php seed.php --class=UserSeeder   Run specific seeder\n";
        echo "  php seed.php --fresh              Fresh seed (development only)\n";
    }
    
    private function info($message)
    {
        echo "\033[36m[INFO]\033[0m {$message}\n";
    }
    
    private function success($message)
    {
        echo "\033[32m[SUCCESS]\033[0m {$message}\n";
    }
    
    private function warning($message)
    {
        echo "\033[33m[WARNING]\033[0m {$message}\n";
    }
    
    private function error($message)
    {
        echo "\033[31m[ERROR]\033[0m {$message}\n";
    }
}

// Run the command
if (php_sapi_name() === 'cli') {
    $command = new SeedCommand();
    $command->run();
} else {
    echo "This script can only be run from the command line.\n";
    exit(1);
}