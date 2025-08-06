<?php
// app/Core/Logger.php

namespace App\Core;

use Exception;

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    private $logPath;
    private $maxFileSize = 10485760; // 10MB
    private $maxFiles = 5;
    private $isLogging = false; // Recursion protection
    
    public function __construct($logPath = null)
    {
        $this->logPath = $logPath ?: LOG_PATH;
        $this->ensureLogDirectoryExists();
    }
    
    /**
     * Log emergency message
     */
    public function emergency($message, $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log alert message
     */
    public function alert($message, $context = [])
    {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, $context = [])
    {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log notice message
     */
    public function notice($message, $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log a message with given level
     */
    public function log($level, $message, $context = [])
    {
        // Prevent recursion if logger encounters an error while logging
        if ($this->isLogging) {
            return;
        }
        
        $this->isLogging = true;
        
        try {
            $logEntry = $this->formatLogEntry($level, $message, $context);
            $this->writeToFile($level, $logEntry);
        } catch (Exception $e) {
            // If logging fails, try to write to error_log as last resort
            // Don't recurse back into our logger
            error_log("Logger failed: " . $e->getMessage() . " | Original message: " . $message);
        } finally {
            $this->isLogging = false;
        }
    }
    
    /**
     * Format log entry
     */
    private function formatLogEntry($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        $entry = "[{$timestamp}] [{$levelUpper}] {$message}";
        
        if (!empty($context)) {
            $entry .= ' ' . json_encode($context);
        }
        
        // Add stack trace for errors
        if (in_array($level, [self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY])) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $entry .= "\nStack trace:\n" . $this->formatStackTrace($trace);
        }
        
        return $entry . "\n";
    }
    
    /**
     * Format stack trace
     */
    private function formatStackTrace($trace)
    {
        $formatted = [];
        
        foreach ($trace as $index => $frame) {
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? 'unknown';
            $function = $frame['function'] ?? 'unknown';
            $class = isset($frame['class']) ? $frame['class'] . '::' : '';
            
            $formatted[] = "#{$index} {$file}({$line}): {$class}{$function}()";
        }
        
        return implode("\n", $formatted);
    }
    
    /**
     * Write log entry to file
     */
    private function writeToFile($level, $entry)
    {
        $filename = $this->getLogFilename($level);
        $filepath = $this->logPath . '/' . $filename;
        
        // Ensure directory is writable
        if (!is_writable($this->logPath)) {
            throw new Exception("Log directory is not writable: {$this->logPath}");
        }
        
        // Check if log rotation is needed
        if (file_exists($filepath) && filesize($filepath) >= $this->maxFileSize) {
            $this->rotateLogFile($filepath);
        }
        
        // Write log entry with error handling
        $result = file_put_contents($filepath, $entry, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            throw new Exception("Failed to write to log file: {$filepath}");
        }
    }
    
    /**
     * Get log filename for level
     */
    private function getLogFilename($level)
    {
        $date = date('Y-m-d');
        
        // Separate files for different log levels
        switch ($level) {
            case self::ERROR:
            case self::CRITICAL:
            case self::ALERT:
            case self::EMERGENCY:
                return "error-{$date}.log";
            case self::WARNING:
            case self::NOTICE:
                return "warning-{$date}.log";
            case self::DEBUG:
                return "debug-{$date}.log";
            default:
                return "app-{$date}.log";
        }
    }
    
    /**
     * Rotate log files when they get too large
     */
    private function rotateLogFile($filepath)
    {
        $pathInfo = pathinfo($filepath);
        $basename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = $pathInfo['dirname'];
        
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = "{$directory}/{$basename}.{$i}.{$extension}";
            $newFile = "{$directory}/{$basename}." . ($i + 1) . ".{$extension}";
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxFiles - 1) {
                    unlink($oldFile); // Delete oldest file
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Rotate current file
        if (file_exists($filepath)) {
            rename($filepath, "{$directory}/{$basename}.1.{$extension}");
        }
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectoryExists()
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Clear logs older than specified days
     */
    public function clearOldLogs($days = 30)
    {
        $files = glob($this->logPath . '/*.log*');
        $threshold = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log files
     */
    public function getLogFiles()
    {
        return glob($this->logPath . '/*.log*');
    }
    
    /**
     * Read log file
     */
    public function readLogFile($filename, $lines = 100)
    {
        $filepath = $this->logPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return '';
        }
        
        return $this->tailFile($filepath, $lines);
    }
    
    /**
     * Read last N lines from file (like tail command)
     */
    private function tailFile($filepath, $lines)
    {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return '';
        }
        
        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $buffer[] = $line;
            if (count($buffer) > $lines) {
                array_shift($buffer);
            }
        }
        
        fclose($handle);
        return implode('', $buffer);
    }
}