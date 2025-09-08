<?php
// run_venue_migrations.php
require_once 'app/bootstrap.php';

try {
    $config = require 'config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    // List of venue management migrations to run
    $migrations = [
        '051_create_venues_table',
        '052_create_venue_spaces_table', 
        '053_create_venue_bookings_table',
        '054_create_venue_capacity_tracking_table',
        '055_create_resource_categories_table',
        '056_create_resources_table',
        '057_create_resource_allocations_table',
        '058_create_equipment_types_table',
        '059_create_equipment_inventory_table',
        '060_create_setup_schedules_table',
        '061_create_setup_tasks_table'
    ];
    
    foreach ($migrations as $migration) {
        echo "Running migration: {$migration}\n";
        
        require_once "database/migrations/{$migration}.php";
        
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', substr($migration, 4))));
        $migrationInstance = new $className($pdo);
        
        try {
            $migrationInstance->up();
            
            // Mark as completed in migrations table
            $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migration, 2]);
            
            echo "✓ Migration {$migration} completed successfully\n";
        } catch (Exception $e) {
            echo "✗ Migration {$migration} failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nVenue management migrations completed!\n";
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}