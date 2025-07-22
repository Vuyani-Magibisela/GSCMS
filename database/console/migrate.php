#!/usr/bin/env php
<?php
// database/console/migrate.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Migration.php';

/**
 * Migration Console Command
 * 
 * Usage:
 *   php migrate.php              # Run all pending migrations
 *   php migrate.php --rollback   # Rollback last migration
 *   php migrate.php --rollback=3 # Rollback last 3 migrations
 *   php migrate.php --status     # Show migration status
 *   php migrate.php --reset      # Reset all migrations (dangerous!)
 */

class MigrateCommand
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
            
            if (isset($this->options['status'])) {
                $this->showStatus();
                return;
            }
            
            if (isset($this->options['reset'])) {
                $this->resetMigrations();
                return;
            }
            
            if (isset($this->options['rollback'])) {
                $steps = is_string($this->options['rollback']) ? 
                    (int)$this->options['rollback'] : 1;
                $this->manager->rollback($steps);
                return;
            }
            
            // Default: run migrations
            $this->manager->migrate();
            
        } catch (Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
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
            
            $this->manager = new MigrationManager($pdo);
            
        } catch (Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    private function showStatus()
    {
        $status = $this->manager->status();
        
        $this->info("Migration Status:");
        $this->info(str_repeat("-", 60));
        
        foreach ($status as $migration) {
            $statusColor = $migration['status'] === 'Executed' ? '32' : '33';
            echo sprintf(
                "%-40s \033[%sm%s\033[0m\n",
                $migration['migration'],
                $statusColor,
                $migration['status']
            );
        }
    }
    
    private function resetMigrations()
    {
        $this->warning("WARNING: This will drop all tables and reset migrations!");
        $this->warning("This action cannot be undone.");
        
        echo "Are you sure you want to continue? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirmation) !== 'yes') {
            $this->info("Reset cancelled.");
            return;
        }
        
        try {
            // Get all executed migrations in reverse order
            $status = $this->manager->status();
            $executedMigrations = array_filter($status, function($m) {
                return $m['status'] === 'Executed';
            });
            
            $totalMigrations = count($executedMigrations);
            
            if ($totalMigrations > 0) {
                $this->info("Rolling back {$totalMigrations} migrations...");
                $this->manager->rollback($totalMigrations);
            }
            
            $this->success("All migrations have been reset!");
            
        } catch (Exception $e) {
            $this->error("Reset failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    private function showHelp()
    {
        echo "\033[32mGDE SciBOTICS Migration Tool\033[0m\n\n";
        echo "Usage:\n";
        echo "  php migrate.php [options]\n\n";
        echo "Options:\n";
        echo "  --help           Show this help message\n";
        echo "  --status         Show migration status\n";
        echo "  --rollback[=n]   Rollback last n migrations (default: 1)\n";
        echo "  --reset          Reset all migrations (WARNING: destructive!)\n\n";
        echo "Examples:\n";
        echo "  php migrate.php              Run all pending migrations\n";
        echo "  php migrate.php --status     Show which migrations have been run\n";
        echo "  php migrate.php --rollback   Rollback the last migration\n";
        echo "  php migrate.php --rollback=3 Rollback the last 3 migrations\n";
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
    $command = new MigrateCommand();
    $command->run();
} else {
    echo "This script can only be run from the command line.\n";
    exit(1);
}