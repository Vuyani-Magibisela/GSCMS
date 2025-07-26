<?php
// database/console/export_schema.php
// Generate production database schema

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance();
    echo "Generating database schema...\n";
    
    // Get database name from config
    $config = require __DIR__ . '/../../config/database.php';
    $dbName = $config['database'];
    
    $schema = "-- GSCMS Database Schema\n";
    $schema .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $schema .= "-- Database: {$dbName}\n\n";
    
    $schema .= "-- Create database if not exists\n";
    $schema .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $schema .= "USE `{$dbName}`;\n\n";
    
    $schema .= "-- Disable foreign key checks during table creation\n";
    $schema .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Get all tables
    $tables = $db->query("SHOW TABLES");
    
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "Exporting table: $tableName\n";
        
        // Get CREATE TABLE statement
        $createResult = $db->query("SHOW CREATE TABLE `$tableName`");
        if (!empty($createResult)) {
            $createStatement = $createResult[0]['Create Table'];
            $schema .= "-- Table: $tableName\n";
            $schema .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $schema .= $createStatement . ";\n\n";
        }
    }
    
    $schema .= "-- Re-enable foreign key checks\n";
    $schema .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Write to file
    $outputFile = __DIR__ . '/../../local_deployment_prep/database_setup/schema.sql';
    file_put_contents($outputFile, $schema);
    
    echo "✓ Schema exported to: $outputFile\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}