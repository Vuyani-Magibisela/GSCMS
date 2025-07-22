<?php
// app/Core/Migration.php

class Migration
{
    protected $db;
    protected $logger;
    
    public function __construct($database)
    {
        $this->db = $database;
        $this->logger = new MigrationLogger();
    }
    
    /**
     * Run the migration
     */
    public function up()
    {
        throw new Exception("up() method must be implemented");
    }
    
    /**
     * Reverse the migration
     */
    public function down()
    {
        throw new Exception("down() method must be implemented");
    }
    
    /**
     * Execute SQL with error handling and logging
     */
    protected function execute($sql, $description = null)
    {
        try {
            $this->logger->info("Executing: " . ($description ?: $sql));
            
            $result = $this->db->exec($sql);
            
            if ($result === false) {
                $error = $this->db->errorInfo();
                throw new Exception("SQL Error: " . $error[2]);
            }
            
            $this->logger->info("Success: " . ($description ?: "SQL executed"));
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("Failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute multiple SQL statements in a transaction
     */
    protected function executeInTransaction(array $statements, $description = null)
    {
        try {
            $this->db->beginTransaction();
            $this->logger->info("Starting transaction: " . ($description ?: "Multiple statements"));
            
            foreach ($statements as $sql) {
                $this->execute($sql);
            }
            
            $this->db->commit();
            $this->logger->info("Transaction committed successfully");
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Transaction rolled back: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create table helper with standard structure
     */
    protected function createTable($tableName, $columns, $options = [])
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n";
        
        // Add ID column if not specified
        if (!isset($columns['id'])) {
            $sql .= "    `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
        }
        
        // Add columns
        foreach ($columns as $name => $definition) {
            $sql .= "    `{$name}` {$definition},\n";
        }
        
        // Add timestamps if not disabled
        if (!isset($options['no_timestamps']) || !$options['no_timestamps']) {
            $sql .= "    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
            $sql .= "    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        }
        
        // Remove trailing comma
        $sql = rtrim($sql, ",\n") . "\n";
        
        $sql .= ")";
        
        // Add table options
        if (isset($options['engine'])) {
            $sql .= " ENGINE=" . $options['engine'];
        }
        if (isset($options['charset'])) {
            $sql .= " CHARACTER SET " . $options['charset'];
        }
        if (isset($options['collate'])) {
            $sql .= " COLLATE " . $options['collate'];
        }
        
        $sql .= ";";
        
        $this->execute($sql, "Creating table: {$tableName}");
    }
    
    /**
     * Drop table helper
     */
    protected function dropTable($tableName)
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`;";
        $this->execute($sql, "Dropping table: {$tableName}");
    }
    
    /**
     * Add index helper
     */
    protected function addIndex($tableName, $indexName, $columns, $type = 'INDEX')
    {
        $columnList = is_array($columns) ? implode(',', $columns) : $columns;
        $sql = "CREATE {$type} `{$indexName}` ON `{$tableName}` ({$columnList});";
        $this->execute($sql, "Adding {$type}: {$indexName} on {$tableName}");
    }
    
    /**
     * Add foreign key helper
     */
    protected function addForeignKey($tableName, $column, $referencedTable, $referencedColumn = 'id', $onDelete = 'RESTRICT', $onUpdate = 'CASCADE')
    {
        $constraintName = "fk_{$tableName}_{$column}";
        $sql = "ALTER TABLE `{$tableName}` 
                ADD CONSTRAINT `{$constraintName}` 
                FOREIGN KEY (`{$column}`) 
                REFERENCES `{$referencedTable}`(`{$referencedColumn}`) 
                ON DELETE {$onDelete} 
                ON UPDATE {$onUpdate};";
        
        $this->execute($sql, "Adding foreign key: {$constraintName}");
    }
}

/**
 * Migration Logger Class
 */
class MigrationLogger
{
    private $logFile;
    
    public function __construct($logFile = null)
    {
        $this->logFile = $logFile ?: __DIR__ . '/../../logs/migrations.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function info($message)
    {
        $this->log('INFO', $message);
    }
    
    public function error($message)
    {
        $this->log('ERROR', $message);
    }
    
    public function warning($message)
    {
        $this->log('WARNING', $message);
    }
    
    private function log($level, $message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to console if running in CLI
        if (php_sapi_name() === 'cli') {
            $color = $this->getColorCode($level);
            echo "\033[{$color}m{$logEntry}\033[0m";
        }
    }
    
    private function getColorCode($level)
    {
        switch ($level) {
            case 'ERROR':
                return '31'; // Red
            case 'WARNING':
                return '33'; // Yellow
            case 'INFO':
                return '32'; // Green
            default:
                return '37'; // White
        }
    }
}

/**
 * Migration Manager Class
 */
class MigrationManager
{
    private $db;
    private $logger;
    private $migrationsPath;
    
    public function __construct($database, $migrationsPath = null)
    {
        $this->db = $database;
        $this->logger = new MigrationLogger();
        $this->migrationsPath = $migrationsPath ?: __DIR__ . '/../../database/migrations';
        
        $this->ensureMigrationsTable();
    }
    
    /**
     * Create migrations tracking table
     */
    private function ensureMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_migration` (`migration`)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        
        try {
            $this->db->exec($sql);
        } catch (Exception $e) {
            $this->logger->error("Failed to create migrations table: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Run pending migrations
     */
    public function migrate()
    {
        $this->logger->info("Starting migration process...");
        
        try {
            $pendingMigrations = $this->getPendingMigrations();
            
            if (empty($pendingMigrations)) {
                $this->logger->info("No pending migrations found.");
                return true;
            }
            
            $this->logger->info("Found " . count($pendingMigrations) . " pending migrations");
            
            foreach ($pendingMigrations as $migration) {
                $this->runMigration($migration);
            }
            
            $this->logger->info("All migrations completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Migration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Rollback last migration
     */
    public function rollback($steps = 1)
    {
        $this->logger->info("Starting rollback process for {$steps} migration(s)...");
        
        try {
            $executedMigrations = $this->getExecutedMigrations($steps);
            
            if (empty($executedMigrations)) {
                $this->logger->info("No migrations to rollback.");
                return true;
            }
            
            foreach (array_reverse($executedMigrations) as $migration) {
                $this->rollbackMigration($migration);
            }
            
            $this->logger->info("Rollback completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Rollback failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get migration status
     */
    public function status()
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrationNames();
        
        $status = [];
        foreach ($allMigrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => in_array($migration, $executedMigrations) ? 'Executed' : 'Pending'
            ];
        }
        
        return $status;
    }
    
    /**
     * Get pending migrations
     */
    private function getPendingMigrations()
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrationNames();
        
        return array_diff($allMigrations, $executedMigrations);
    }
    
    /**
     * Get all migration files
     */
    private function getAllMigrationFiles()
    {
        $migrations = [];
        $files = glob($this->migrationsPath . '/*.php');
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.php');
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Get executed migration names
     */
    private function getExecutedMigrationNames()
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY migration");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
    
    /**
     * Get executed migrations for rollback
     */
    private function getExecutedMigrations($limit)
    {
        $stmt = $this->db->prepare("SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Run a single migration
     */
    private function runMigration($migrationName)
    {
        $this->logger->info("Running migration: {$migrationName}");
        
        $migrationFile = $this->migrationsPath . '/' . $migrationName . '.php';
        
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: {$migrationFile}");
        }
        
        // Include the migration file
        require_once $migrationFile;
        
        // Get class name from file name
        $className = $this->getMigrationClassName($migrationName);
        
        if (!class_exists($className)) {
            throw new Exception("Migration class not found: {$className}");
        }
        
        // Run migration in transaction
        $this->db->beginTransaction();
        
        try {
            $migration = new $className($this->db);
            $migration->up();
            
            // Record migration as executed
            $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationName]);
            
            $this->db->commit();
            $this->logger->info("Migration completed: {$migrationName}");
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Migration failed: {$migrationName} - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Rollback a single migration
     */
    private function rollbackMigration($migrationName)
    {
        $this->logger->info("Rolling back migration: {$migrationName}");
        
        $migrationFile = $this->migrationsPath . '/' . $migrationName . '.php';
        
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: {$migrationFile}");
        }
        
        require_once $migrationFile;
        $className = $this->getMigrationClassName($migrationName);
        
        if (!class_exists($className)) {
            throw new Exception("Migration class not found: {$className}");
        }
        
        $this->db->beginTransaction();
        
        try {
            $migration = new $className($this->db);
            $migration->down();
            
            // Remove migration record
            $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationName]);
            
            $this->db->commit();
            $this->logger->info("Migration rolled back: {$migrationName}");
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Rollback failed: {$migrationName} - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Convert file name to class name
     */
    private function getMigrationClassName($migrationName)
    {
        // Convert snake_case to PascalCase
        $parts = explode('_', $migrationName);
        // Remove the number prefix
        array_shift($parts);
        
        return implode('', array_map('ucfirst', $parts));
    }
}