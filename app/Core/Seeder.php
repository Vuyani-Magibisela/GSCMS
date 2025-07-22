<?php
// app/Core/Seeder.php

abstract class Seeder
{
    protected $db;
    protected $logger;
    protected $environment;
    
    public function __construct($database, $environment = 'development')
    {
        $this->db = $database;
        $this->environment = $environment;
        $this->logger = new SeederLogger();
    }
    
    /**
     * Run the seeder
     */
    abstract public function run();
    
    /**
     * Execute SQL with error handling
     */
    protected function execute($sql, $params = [], $description = null)
    {
        try {
            $this->logger->info("Executing: " . ($description ?: $sql));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                $error = $stmt->errorInfo();
                throw new Exception("SQL Error: " . $error[2]);
            }
            
            $this->logger->info("Success: " . ($description ?: "SQL executed"));
            return $stmt;
            
        } catch (Exception $e) {
            $this->logger->error("Failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Insert data in batches for performance
     */
    protected function insertBatch($table, array $data, $batchSize = 100)
    {
        if (empty($data)) {
            return;
        }
        
        $chunks = array_chunk($data, $batchSize);
        $totalChunks = count($chunks);
        
        $this->logger->info("Inserting " . count($data) . " records into {$table} in {$totalChunks} batches");
        
        foreach ($chunks as $index => $chunk) {
            $this->insertChunk($table, $chunk);
            $this->logger->info("Batch " . ($index + 1) . "/{$totalChunks} completed");
        }
    }
    
    /**
     * Insert a single chunk of data
     */
    private function insertChunk($table, array $data)
    {
        if (empty($data)) {
            return;
        }
        
        $columns = array_keys($data[0]);
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $values = implode(',', array_fill(0, count($data), $placeholders));
        
        $sql = "INSERT INTO `{$table}` (`" . implode('`,`', $columns) . "`) VALUES " . $values;
        
        $params = [];
        foreach ($data as $row) {
            foreach ($columns as $column) {
                $params[] = $row[$column] ?? null;
            }
        }
        
        $this->execute($sql, $params, "Inserting batch into {$table}");
    }
    
    /**
     * Truncate table
     */
    protected function truncate($table)
    {
        $this->execute("SET FOREIGN_KEY_CHECKS = 0", [], "Disabling foreign key checks");
        $this->execute("TRUNCATE TABLE `{$table}`", [], "Truncating table: {$table}");
        $this->execute("SET FOREIGN_KEY_CHECKS = 1", [], "Enabling foreign key checks");
    }
    
    /**
     * Call another seeder
     */
    protected function call($seederClass)
    {
        $this->logger->info("Calling seeder: {$seederClass}");
        
        if (!class_exists($seederClass)) {
            throw new Exception("Seeder class not found: {$seederClass}");
        }
        
        $seeder = new $seederClass($this->db, $this->environment);
        $seeder->run();
    }
    
    /**
     * Check if we're in development environment
     */
    protected function isDevelopment()
    {
        return $this->environment === 'development';
    }
    
    /**
     * Check if we're in production environment
     */
    protected function isProduction()
    {
        return $this->environment === 'production';
    }
    
    /**
     * Generate fake data using factory
     */
    protected function factory($factoryClass, $count = 1, $attributes = [])
    {
        if (!class_exists($factoryClass)) {
            throw new Exception("Factory class not found: {$factoryClass}");
        }
        
        $factory = new $factoryClass();
        
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = $factory->make($attributes);
        }
        
        return $data;
    }
}

/**
 * Seeder Logger Class
 */
class SeederLogger
{
    private $logFile;
    
    public function __construct($logFile = null)
    {
        $this->logFile = $logFile ?: __DIR__ . '/../../logs/seeds.log';
        
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
 * Seeder Manager Class
 */
class SeederManager
{
    private $db;
    private $logger;
    private $environment;
    private $seedersPath;
    
    public function __construct($database, $environment = 'development', $seedersPath = null)
    {
        $this->db = $database;
        $this->environment = $environment;
        $this->logger = new SeederLogger();
        $this->seedersPath = $seedersPath ?: __DIR__ . '/../../database/seeds';
    }
    
    /**
     * Run all seeders for the environment
     */
    public function run($specific = null)
    {
        $this->logger->info("Starting seeding process for environment: {$this->environment}");
        
        try {
            if ($specific) {
                $this->runSpecificSeeder($specific);
            } else {
                $this->runEnvironmentSeeders();
            }
            
            $this->logger->info("Seeding completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Seeding failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Run specific seeder
     */
    private function runSpecificSeeder($seederName)
    {
        $seederFile = $this->findSeederFile($seederName);
        
        if (!$seederFile) {
            throw new Exception("Seeder not found: {$seederName}");
        }
        
        require_once $seederFile;
        
        if (!class_exists($seederName)) {
            throw new Exception("Seeder class not found: {$seederName}");
        }
        
        $this->logger->info("Running seeder: {$seederName}");
        
        $seeder = new $seederName($this->db, $this->environment);
        $seeder->run();
    }
    
    /**
     * Run all seeders for the current environment
     */
    private function runEnvironmentSeeders()
    {
        $envPath = $this->seedersPath . '/' . $this->environment;
        
        if (!is_dir($envPath)) {
            throw new Exception("Seeders directory not found for environment: {$this->environment}");
        }
        
        // Look for main seeder file
        $mainSeederFile = $envPath . '/' . ucfirst($this->environment) . 'Seeder.php';
        
        if (file_exists($mainSeederFile)) {
            $seederClass = ucfirst($this->environment) . 'Seeder';
            require_once $mainSeederFile;
            
            $this->logger->info("Running main seeder: {$seederClass}");
            $seeder = new $seederClass($this->db, $this->environment);
            $seeder->run();
        } else {
            // Run all seeders in the environment directory
            $files = glob($envPath . '/*Seeder.php');
            foreach ($files as $file) {
                $seederClass = basename($file, '.php');
                require_once $file;
                
                $this->logger->info("Running seeder: {$seederClass}");
                $seeder = new $seederClass($this->db, $this->environment);
                $seeder->run();
            }
        }
    }
    
    /**
     * Find seeder file in any environment
     */
    private function findSeederFile($seederName)
    {
        $environments = ['development', 'production'];
        
        foreach ($environments as $env) {
            $file = $this->seedersPath . "/{$env}/{$seederName}.php";
            if (file_exists($file)) {
                return $file;
            }
        }
        
        // Check root seeders directory
        $file = $this->seedersPath . "/{$seederName}.php";
        if (file_exists($file)) {
            return $file;
        }
        
        return null;
    }
}

// app/Core/Factory.php

abstract class Factory
{
    /**
     * Make a single instance
     */
    abstract public function make($attributes = []);
    
    /**
     * Generate fake data
     */
    protected function faker()
    {
        return new FakeDataGenerator();
    }
    
    /**
     * Merge attributes with defaults
     */
    protected function mergeAttributes(array $defaults, array $attributes = [])
    {
        return array_merge($defaults, $attributes);
    }
}

/**
 * Simple Fake Data Generator
 */
class FakeDataGenerator
{
    private $firstNames = [
        'Thabo', 'Nomsa', 'Sipho', 'Fatima', 'Johan', 'Priya', 'Mandla', 'Zanele',
        'Ahmed', 'Sarah', 'David', 'Lerato', 'Michael', 'Aisha', 'Peter', 'Naledi'
    ];
    
    private $lastNames = [
        'Mthembu', 'Van Der Merwe', 'Naidoo', 'Khumalo', 'Smith', 'Patel', 'Dlamini',
        'Johnson', 'Mbeki', 'Williams', 'Molefe', 'Brown', 'Zulu', 'Adams', 'Sithole'
    ];
    
    private $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'school.za', 'edu.za'];
    
    private $districts = [
        'Johannesburg East', 'Johannesburg West', 'Johannesburg Central', 'Johannesburg South',
        'Ekurhuleni North', 'Ekurhuleni South', 'Tshwane North', 'Tshwane South',
        'Sedibeng East', 'Sedibeng West', 'West Rand'
    ];
    
    public function firstName()
    {
        return $this->firstNames[array_rand($this->firstNames)];
    }
    
    public function lastName()
    {
        return $this->lastNames[array_rand($this->lastNames)];
    }
    
    public function name()
    {
        return $this->firstName() . ' ' . $this->lastName();
    }
    
    public function email($name = null)
    {
        $name = $name ?: $this->firstName() . '.' . $this->lastName();
        $domain = $this->domains[array_rand($this->domains)];
        return strtolower(str_replace(' ', '.', $name)) . '@' . $domain;
    }
    
    public function phone()
    {
        return '0' . rand(60, 89) . rand(1000000, 9999999);
    }
    
    public function district()
    {
        return $this->districts[array_rand($this->districts)];
    }
    
    public function schoolName()
    {
        $prefixes = ['Mandela', 'Sisulu', 'Tambo', 'Sobukwe', 'Biko', 'Machel', 'Luthuli'];
        $suffixes = ['Primary School', 'High School', 'Secondary School', 'Combined School'];
        
        return $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)];
    }
    
    public function address()
    {
        $streets = ['Main', 'Church', 'School', 'Park', 'Central', 'Victoria', 'Nelson Mandela'];
        $types = ['Street', 'Road', 'Avenue', 'Drive'];
        
        return rand(1, 999) . ' ' . $streets[array_rand($streets)] . ' ' . $types[array_rand($types)];
    }
    
    public function city()
    {
        $cities = ['Johannesburg', 'Pretoria', 'Germiston', 'Benoni', 'Boksburg', 'Springs', 'Vanderbijlpark'];
        return $cities[array_rand($cities)];
    }
    
    public function postalCode()
    {
        return rand(1000, 9999);
    }
    
    public function date($startDate = '1990-01-01', $endDate = '2020-12-31')
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $random = rand($start, $end);
        return date('Y-m-d', $random);
    }
    
    public function dateOfBirth($minAge = 5, $maxAge = 18)
    {
        $currentYear = date('Y');
        $birthYear = $currentYear - rand($minAge, $maxAge);
        $month = rand(1, 12);
        $day = rand(1, 28); // Safe day for all months
        
        return sprintf('%04d-%02d-%02d', $birthYear, $month, $day);
    }
    
    public function grade()
    {
        return rand(0, 12); // Grade R = 0, Grade 1-12
    }
    
    public function gender()
    {
        $genders = ['male', 'female'];
        return $genders[array_rand($genders)];
    }
    
    public function boolean($probability = 0.5)
    {
        return rand(0, 100) / 100 < $probability;
    }
    
    public function randomElement(array $array)
    {
        return $array[array_rand($array)];
    }
    
    public function text($maxLength = 100)
    {
        $words = [
            'the', 'quick', 'brown', 'fox', 'jumps', 'over', 'lazy', 'dog', 'and', 'runs',
            'through', 'forest', 'into', 'meadow', 'where', 'flowers', 'bloom', 'under',
            'bright', 'sunshine', 'while', 'birds', 'sing', 'beautiful', 'songs'
        ];
        
        $text = '';
        while (strlen($text) < $maxLength) {
            $word = $words[array_rand($words)];
            if (strlen($text) + strlen($word) + 1 <= $maxLength) {
                $text .= ($text ? ' ' : '') . $word;
            } else {
                break;
            }
        }
        
        return ucfirst($text) . '.';
    }
    
    public function number($min = 1, $max = 100)
    {
        return rand($min, $max);
    }
}