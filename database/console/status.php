#!/usr/bin/env php
<?php
/**
 * Database Status Script
 * 
 * This script displays:
 * 1. Database connection status
 * 2. Migration status (executed vs pending)
 * 3. Table information and row counts
 * 4. Database schema version
 * 
 * Usage:
 *   php status.php              # Show full status
 *   php status.php --migrations # Show migrations only
 *   php status.php --tables     # Show tables only
 */

require_once __DIR__ . '/../../app/Core/Migration.php';

class StatusCommand
{
    private $options;
    private $config;
    private $db;
    
    public function __construct()
    {
        $this->parseOptions();
        $this->config = require __DIR__ . '/../../config/database.php';
    }
    
    public function run()
    {
        try {
            $this->info("GDE SciBOTICS Database Status");
            $this->info(str_repeat("=", 50));
            
            // Test database connection
            $this->checkDatabaseConnection();
            
            // Show migration status
            if (!isset($this->options['tables'])) {
                $this->showMigrationStatus();
            }
            
            // Show table information
            if (!isset($this->options['migrations'])) {
                $this->showTableStatus();
            }
            
        } catch (Exception $e) {
            $this->error("Status check failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    private function checkDatabaseConnection()
    {
        $this->info("Database Connection Status:");
        
        try {
            $this->db = $this->createConnection();
            
            // Test connection
            $stmt = $this->db->query("SELECT VERSION() as version");
            $version = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->success("✓ Connected to MySQL " . $version['version']);
            $this->info("  Host: " . $this->config['host'] . ":" . $this->config['port']);
            $this->info("  Database: " . $this->config['database']);
            $this->info("  User: " . $this->config['username']);
            
        } catch (Exception $e) {
            $this->error("✗ Database connection failed: " . $e->getMessage());
            throw $e;
        }
        
        echo "\n";
    }
    
    private function showMigrationStatus()
    {
        $this->info("Migration Status:");
        
        try {
            $migrationManager = new MigrationManager($this->db);
            $status = $migrationManager->status();
            
            if (empty($status)) {
                $this->warning("  No migrations found");
                echo "\n";
                return;
            }
            
            $executed = 0;
            $pending = 0;
            
            foreach ($status as $migration) {
                $statusIcon = $migration['status'] === 'Executed' ? '✓' : '○';
                $statusColor = $migration['status'] === 'Executed' ? 'success' : 'warning';
                
                $this->output("  {$statusIcon} " . $migration['migration'], $statusColor);
                
                if ($migration['status'] === 'Executed') {
                    $executed++;
                } else {
                    $pending++;
                }
            }
            
            echo "\n";
            $this->info("Summary:");
            $this->success("  ✓ Executed: {$executed}");
            if ($pending > 0) {
                $this->warning("  ○ Pending: {$pending}");
            } else {
                $this->success("  ○ Pending: 0");
            }
            
        } catch (Exception $e) {
            $this->error("  Failed to get migration status: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    private function showTableStatus()
    {
        $this->info("Table Status:");
        
        try {
            // Get all tables
            $stmt = $this->db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                $this->warning("  No tables found in database");
                echo "\n";
                return;
            }
            
            $totalRows = 0;
            $tableData = [];
            
            foreach ($tables as $table) {
                // Get row count
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM `{$table}`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Get table info
                $stmt = $this->db->query("SHOW TABLE STATUS LIKE '{$table}'");
                $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $tableData[] = [
                    'name' => $table,
                    'rows' => $count,
                    'size' => $this->formatBytes($tableInfo['Data_length'] + $tableInfo['Index_length']),
                    'engine' => $tableInfo['Engine'],
                    'created' => $tableInfo['Create_time']
                ];
                
                $totalRows += $count;
            }
            
            // Display table information
            $this->info("  " . str_pad("Table", 25) . str_pad("Rows", 10) . str_pad("Size", 10) . "Engine");
            $this->info("  " . str_repeat("-", 55));
            
            foreach ($tableData as $table) {
                $rowColor = $table['rows'] > 0 ? 'success' : 'info';
                $this->output(
                    "  " . str_pad($table['name'], 25) . 
                    str_pad(number_format($table['rows']), 10) . 
                    str_pad($table['size'], 10) . 
                    $table['engine'],
                    $rowColor
                );
            }
            
            echo "\n";
            $this->info("Summary:");
            $this->success("  ✓ Total tables: " . count($tables));
            $this->success("  ✓ Total rows: " . number_format($totalRows));
            
            // Check for key tables
            $this->checkKeyTables($tables);
            
        } catch (Exception $e) {
            $this->error("  Failed to get table status: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    private function checkKeyTables($tables)
    {
        $keyTables = [
            'users' => 'User management',
            'schools' => 'School registrations',
            'teams' => 'Team registrations',
            'participants' => 'Team participants',
            'competitions' => 'Competition management',
            'categories' => 'Competition categories',
            'phases' => 'Competition phases'
        ];
        
        $this->info("  Key Tables Check:");
        foreach ($keyTables as $table => $description) {
            if (in_array($table, $tables)) {
                // Get row count
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM `{$table}`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                $status = $count > 0 ? "✓ ({$count} records)" : "○ (empty)";
                $color = $count > 0 ? 'success' : 'warning';
                
                $this->output("    {$status} {$table} - {$description}", $color);
            } else {
                $this->error("    ✗ {$table} - Missing table!");
            }
        }
    }
    
    private function createConnection()
    {
        $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset=utf8mb4";
        
        return new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
        ]);
    }
    
    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 1) . $units[$unit];
    }
    
    private function parseOptions()
    {
        $this->options = [];
        
        foreach ($_SERVER['argv'] as $arg) {
            if (strpos($arg, '--') === 0) {
                $this->options[substr($arg, 2)] = true;
            }
        }
    }
    
    // Output formatting methods
    private function info($message)
    {
        echo "\033[36m{$message}\033[0m\n";
    }
    
    private function success($message)
    {
        echo "\033[32m{$message}\033[0m\n";
    }
    
    private function warning($message)
    {
        echo "\033[33m{$message}\033[0m\n";
    }
    
    private function error($message)
    {
        echo "\033[31m{$message}\033[0m\n";
    }
    
    private function output($message, $type = 'info')
    {
        switch ($type) {
            case 'success':
                $this->success($message);
                break;
            case 'warning':
                $this->warning($message);
                break;
            case 'error':
                $this->error($message);
                break;
            default:
                $this->info($message);
        }
    }
}

// Run the status command
if (basename($_SERVER['PHP_SELF']) === 'status.php') {
    $command = new StatusCommand();
    $command->run();
}