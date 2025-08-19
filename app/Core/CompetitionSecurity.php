<?php

namespace App\Core;

/**
 * Competition Security Manager
 * Handles security and access control for competition setup interface
 */
class CompetitionSecurity
{
    private static $instance = null;
    
    // Security configuration
    private $config = [
        'max_competitions_per_year' => 10,
        'max_phases_per_competition' => 10,
        'max_categories_per_competition' => 20,
        'allowed_file_uploads' => ['json', 'csv', 'xlsx'],
        'max_file_size' => 5242880, // 5MB
        'session_timeout' => 3600, // 1 hour
        'rate_limit_requests' => 100,
        'rate_limit_window' => 900, // 15 minutes
    ];
    
    // Security flags
    private $securityEnabled = true;
    private $auditLogging = true;
    private $ipWhitelist = [];
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        // Initialize security settings from environment
        $this->loadSecurityConfig();
    }
    
    /**
     * Load security configuration from environment
     */
    private function loadSecurityConfig()
    {
        // Load from environment variables if available
        if (isset($_ENV['COMPETITION_SECURITY_ENABLED'])) {
            $this->securityEnabled = filter_var($_ENV['COMPETITION_SECURITY_ENABLED'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (isset($_ENV['COMPETITION_AUDIT_LOGGING'])) {
            $this->auditLogging = filter_var($_ENV['COMPETITION_AUDIT_LOGGING'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (isset($_ENV['COMPETITION_IP_WHITELIST'])) {
            $this->ipWhitelist = explode(',', $_ENV['COMPETITION_IP_WHITELIST']);
        }
    }
    
    /**
     * Validate admin access for competition setup
     */
    public function validateAdminAccess($requiredRole = 'super_admin')
    {
        if (!$this->securityEnabled) {
            return true;
        }
        
        // Check session
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $this->logSecurityEvent('unauthorized_access_attempt', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            return false;
        }
        
        // Check role permissions
        $userRole = $_SESSION['user_role'];
        if (!$this->hasRequiredRole($userRole, $requiredRole)) {
            $this->logSecurityEvent('insufficient_permissions', [
                'user_id' => $_SESSION['user_id'],
                'user_role' => $userRole,
                'required_role' => $requiredRole
            ]);
            return false;
        }
        
        // Check IP whitelist if configured
        if (!empty($this->ipWhitelist) && !$this->isIpWhitelisted()) {
            $this->logSecurityEvent('ip_not_whitelisted', [
                'user_id' => $_SESSION['user_id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }
        
        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->logSecurityEvent('session_expired', [
                'user_id' => $_SESSION['user_id']
            ]);
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Validate competition data input
     */
    public function validateCompetitionData($data, $step = null)
    {
        $errors = [];
        
        if (!is_array($data)) {
            $errors[] = 'Invalid data format';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Step-specific validation
        switch ($step) {
            case 1:
                $errors = array_merge($errors, $this->validateStep1Data($data));
                break;
            case 2:
                $errors = array_merge($errors, $this->validateStep2Data($data));
                break;
            case 3:
                $errors = array_merge($errors, $this->validateStep3Data($data));
                break;
            case 4:
                $errors = array_merge($errors, $this->validateStep4Data($data));
                break;
            case 5:
                $errors = array_merge($errors, $this->validateStep5Data($data));
                break;
        }
        
        // General security validation
        $errors = array_merge($errors, $this->validateSecurityConstraints($data));
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate file upload security
     */
    public function validateFileUpload($file)
    {
        $errors = [];
        
        if (!is_array($file) || !isset($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $this->config['max_file_size']) {
            $errors[] = 'File size exceeds maximum allowed (' . 
                       $this->formatBytes($this->config['max_file_size']) . ')';
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_file_uploads'])) {
            $errors[] = 'File type not allowed. Allowed types: ' . 
                       implode(', ', $this->config['allowed_file_uploads']);
        }
        
        // Check for malicious content
        if ($this->containsMaliciousContent($file)) {
            $errors[] = 'File contains potentially malicious content';
            $this->logSecurityEvent('malicious_file_upload_attempt', [
                'user_id' => $_SESSION['user_id'] ?? 'unknown',
                'filename' => $file['name'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Rate limiting for API endpoints
     */
    public function checkRateLimit($identifier = null)
    {
        if (!$this->securityEnabled) {
            return true;
        }
        
        $identifier = $identifier ?: ($_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = "rate_limit_{$identifier}";
        
        // Simple file-based rate limiting (replace with Redis in production)
        $rateLimitFile = sys_get_temp_dir() . '/' . md5($key) . '.txt';
        
        $now = time();
        $windowStart = $now - $this->config['rate_limit_window'];
        
        // Read existing requests
        $requests = [];
        if (file_exists($rateLimitFile)) {
            $content = file_get_contents($rateLimitFile);
            $requests = $content ? json_decode($content, true) : [];
        }
        
        // Filter requests within window
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if limit exceeded
        if (count($requests) >= $this->config['rate_limit_requests']) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'identifier' => $identifier,
                'requests_count' => count($requests),
                'limit' => $this->config['rate_limit_requests']
            ]);
            return false;
        }
        
        // Add current request
        $requests[] = $now;
        
        // Save updated requests
        file_put_contents($rateLimitFile, json_encode($requests));
        
        return true;
    }
    
    /**
     * Sanitize input data for XSS prevention
     */
    public function sanitizeInput($data)
    {
        if (is_string($data)) {
            // Remove potentially dangerous characters
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            
            // Remove script tags and other dangerous elements
            $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
            $data = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $data);
            
            return trim($data);
        }
        
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return $data;
    }
    
    /**
     * Validate CSRF token for form submissions
     */
    public function validateCSRFToken($token)
    {
        if (!$this->securityEnabled) {
            return true;
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Log security events for audit trail
     */
    public function logSecurityEvent($event, $data = [])
    {
        if (!$this->auditLogging) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'data' => $data
        ];
        
        // Log to file (replace with proper logging system in production)
        $logFile = defined('STORAGE_PATH') ? STORAGE_PATH . '/logs/security.log' : 
                   sys_get_temp_dir() . '/gscms_security.log';
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    // Private helper methods
    
    private function hasRequiredRole($userRole, $requiredRole)
    {
        $roleHierarchy = [
            'super_admin' => 5,
            'admin' => 4,
            'competition_admin' => 3,
            'school_coordinator' => 2,
            'team_coach' => 1
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 99;
        
        return $userLevel >= $requiredLevel;
    }
    
    private function isIpWhitelisted()
    {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($clientIp, $this->ipWhitelist);
    }
    
    private function isSessionExpired()
    {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        return (time() - $_SESSION['last_activity']) > $this->config['session_timeout'];
    }
    
    private function validateStep1Data($data)
    {
        $errors = [];
        
        // Required fields
        $required = ['name', 'year', 'type', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '$field' is required";
            }
        }
        
        // Name validation
        if (isset($data['name'])) {
            if (strlen($data['name']) > 200) {
                $errors[] = 'Competition name too long (max 200 characters)';
            }
            if (preg_match('/[<>"\']/', $data['name'])) {
                $errors[] = 'Competition name contains invalid characters';
            }
        }
        
        // Year validation
        if (isset($data['year'])) {
            $currentYear = (int) date('Y');
            if ($data['year'] < $currentYear || $data['year'] > ($currentYear + 5)) {
                $errors[] = 'Invalid competition year';
            }
        }
        
        // Date validation
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                $errors[] = 'End date must be after start date';
            }
        }
        
        return $errors;
    }
    
    private function validateStep2Data($data)
    {
        $errors = [];
        
        if (!isset($data['phases']) || !is_array($data['phases'])) {
            $errors[] = 'Phase data is required';
            return $errors;
        }
        
        $enabledPhases = 0;
        foreach ($data['phases'] as $phaseKey => $phase) {
            if (isset($phase['enabled']) && $phase['enabled']) {
                $enabledPhases++;
                
                // Validate phase dates
                if (empty($phase['start_date']) || empty($phase['end_date'])) {
                    $errors[] = "Phase '{$phaseKey}' requires start and end dates";
                } elseif (strtotime($phase['end_date']) <= strtotime($phase['start_date'])) {
                    $errors[] = "Phase '{$phaseKey}' end date must be after start date";
                }
                
                // Validate capacity
                if (isset($phase['capacity']) && ($phase['capacity'] < 1 || $phase['capacity'] > 1000)) {
                    $errors[] = "Phase '{$phaseKey}' capacity must be between 1 and 1000";
                }
            }
        }
        
        if ($enabledPhases === 0) {
            $errors[] = 'At least one phase must be enabled';
        }
        
        if ($enabledPhases > $this->config['max_phases_per_competition']) {
            $errors[] = "Maximum {$this->config['max_phases_per_competition']} phases allowed";
        }
        
        return $errors;
    }
    
    private function validateStep3Data($data)
    {
        $errors = [];
        
        if (!isset($data['categories']) || !is_array($data['categories'])) {
            $errors[] = 'Category data is required';
            return $errors;
        }
        
        if (count($data['categories']) > $this->config['max_categories_per_competition']) {
            $errors[] = "Maximum {$this->config['max_categories_per_competition']} categories allowed";
        }
        
        foreach ($data['categories'] as $index => $category) {
            if (empty($category['name'])) {
                $errors[] = "Category {$index}: Name is required";
            }
            
            if (isset($category['team_size']) && ($category['team_size'] < 1 || $category['team_size'] > 10)) {
                $errors[] = "Category {$index}: Team size must be between 1 and 10";
            }
        }
        
        return $errors;
    }
    
    private function validateStep4Data($data)
    {
        $errors = [];
        
        // Validate registration timeline
        if (isset($data['registration_opening']) && isset($data['registration_closing'])) {
            if (strtotime($data['registration_closing']) <= strtotime($data['registration_opening'])) {
                $errors[] = 'Registration closing must be after opening date';
            }
        }
        
        return $errors;
    }
    
    private function validateStep5Data($data)
    {
        $errors = [];
        
        // Validate competition rules and scoring
        if (isset($data['scoring_method']) && !in_array($data['scoring_method'], 
            ['best_attempt', 'average_attempts', 'last_attempt'])) {
            $errors[] = 'Invalid scoring method';
        }
        
        return $errors;
    }
    
    private function validateSecurityConstraints($data)
    {
        $errors = [];
        
        // Check for potentially dangerous content
        $jsonData = json_encode($data);
        if (preg_match('/<script|javascript:|on\w+\s*=|data:text\/html/i', $jsonData)) {
            $errors[] = 'Data contains potentially dangerous content';
            $this->logSecurityEvent('dangerous_content_detected', [
                'user_id' => $_SESSION['user_id'] ?? 'unknown',
                'data_sample' => substr($jsonData, 0, 200)
            ]);
        }
        
        return $errors;
    }
    
    private function containsMaliciousContent($file)
    {
        // Basic malicious content detection
        $content = file_get_contents($file['tmp_name']);
        
        // Check for suspicious patterns
        $maliciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}