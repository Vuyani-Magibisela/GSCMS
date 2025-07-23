<?php
// app/Core/RateLimit.php

namespace App\Core;

use Exception;

class RateLimit
{
    private static $instance = null;
    private $session;
    private $storage = [];
    
    private function __construct()
    {
        $this->session = Session::getInstance();
        $this->loadFromSession();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load rate limit data from session
     */
    private function loadFromSession()
    {
        $this->storage = $this->session->get('rate_limits', []);
        $this->cleanExpiredEntries();
    }
    
    /**
     * Save rate limit data to session
     */
    private function saveToSession()
    {
        $this->session->set('rate_limits', $this->storage);
    }
    
    /**
     * Clean expired entries
     */
    private function cleanExpiredEntries()
    {
        $now = time();
        
        foreach ($this->storage as $key => $data) {
            if ($data['reset_time'] <= $now) {
                unset($this->storage[$key]);
            }
        }
    }
    
    /**
     * Generate rate limit key
     */
    private function generateKey($action, $identifier = null)
    {
        if ($identifier === null) {
            $identifier = $this->getClientIdentifier();
        }
        
        return $action . ':' . $identifier;
    }
    
    /**
     * Get client identifier (IP + User Agent hash)
     */
    private function getClientIdentifier()
    {
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Create a hash of IP and User Agent for privacy
        return hash('sha256', $ip . $userAgent);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if rate limit is exceeded
     */
    public function isExceeded($action, $maxAttempts, $windowMinutes = 60, $identifier = null)
    {
        $key = $this->generateKey($action, $identifier);
        $now = time();
        
        if (!isset($this->storage[$key])) {
            return false;
        }
        
        $data = $this->storage[$key];
        
        // Check if window has expired
        if ($data['reset_time'] <= $now) {
            unset($this->storage[$key]);
            $this->saveToSession();
            return false;
        }
        
        return $data['attempts'] >= $maxAttempts;
    }
    
    /**
     * Record an attempt
     */
    public function recordAttempt($action, $windowMinutes = 60, $identifier = null)
    {
        $key = $this->generateKey($action, $identifier);
        $now = time();
        $resetTime = $now + ($windowMinutes * 60);
        
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = [
                'attempts' => 1,
                'first_attempt' => $now,
                'last_attempt' => $now,
                'reset_time' => $resetTime
            ];
        } else {
            $data = $this->storage[$key];
            
            // If window has expired, reset
            if ($data['reset_time'] <= $now) {
                $this->storage[$key] = [
                    'attempts' => 1,
                    'first_attempt' => $now,
                    'last_attempt' => $now,
                    'reset_time' => $resetTime
                ];
            } else {
                // Increment attempts
                $this->storage[$key]['attempts']++;
                $this->storage[$key]['last_attempt'] = $now;
            }
        }
        
        $this->saveToSession();
        return $this->storage[$key]['attempts'];
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts($action, $maxAttempts, $windowMinutes = 60, $identifier = null)
    {
        $key = $this->generateKey($action, $identifier);
        
        if (!isset($this->storage[$key])) {
            return $maxAttempts;
        }
        
        $data = $this->storage[$key];
        $now = time();
        
        // Check if window has expired
        if ($data['reset_time'] <= $now) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $data['attempts']);
    }
    
    /**
     * Get time until reset (in seconds)
     */
    public function getTimeUntilReset($action, $identifier = null)
    {
        $key = $this->generateKey($action, $identifier);
        
        if (!isset($this->storage[$key])) {
            return 0;
        }
        
        $data = $this->storage[$key];
        $now = time();
        
        return max(0, $data['reset_time'] - $now);
    }
    
    /**
     * Clear rate limit for specific action
     */
    public function clearRateLimit($action, $identifier = null)
    {
        $key = $this->generateKey($action, $identifier);
        unset($this->storage[$key]);
        $this->saveToSession();
    }
    
    /**
     * Clear all rate limits
     */
    public function clearAllRateLimits()
    {
        $this->storage = [];
        $this->saveToSession();
    }
    
    /**
     * Enforce rate limit (throws exception if exceeded)
     */
    public function enforce($action, $maxAttempts, $windowMinutes = 60, $identifier = null)
    {
        if ($this->isExceeded($action, $maxAttempts, $windowMinutes, $identifier)) {
            $timeUntilReset = $this->getTimeUntilReset($action, $identifier);
            $minutesUntilReset = ceil($timeUntilReset / 60);
            
            throw new Exception(
                "Rate limit exceeded for {$action}. Please try again in {$minutesUntilReset} minute(s).",
                429
            );
        }
        
        $this->recordAttempt($action, $windowMinutes, $identifier);
        return true;
    }
    
    /**
     * Get rate limit info
     */
    public function getInfo($action, $maxAttempts, $windowMinutes = 60, $identifier = null)
    {
        $key = $this->generateKey($action, $identifier);
        $data = $this->storage[$key] ?? null;
        
        if (!$data) {
            return [
                'attempts' => 0,
                'remaining' => $maxAttempts,
                'reset_time' => time() + ($windowMinutes * 60),
                'is_exceeded' => false
            ];
        }
        
        $now = time();
        $isExpired = $data['reset_time'] <= $now;
        
        if ($isExpired) {
            return [
                'attempts' => 0,
                'remaining' => $maxAttempts,
                'reset_time' => $now + ($windowMinutes * 60),
                'is_exceeded' => false
            ];
        }
        
        return [
            'attempts' => $data['attempts'],
            'remaining' => max(0, $maxAttempts - $data['attempts']),
            'reset_time' => $data['reset_time'],
            'is_exceeded' => $data['attempts'] >= $maxAttempts
        ];
    }
    
    /**
     * Pre-defined rate limits for common actions
     */
    public function enforceLoginAttempts($identifier = null)
    {
        return $this->enforce('login', 5, 60, $identifier); // 5 attempts per hour
    }
    
    public function enforcePasswordReset($identifier = null)
    {
        return $this->enforce('password_reset', 3, 15, $identifier); // 3 attempts per 15 minutes
    }
    
    public function enforceRegistration($identifier = null)
    {
        return $this->enforce('registration', 3, 60, $identifier); // 3 registrations per hour
    }
    
    public function enforceEmailVerification($identifier = null)
    {
        return $this->enforce('email_verification', 5, 60, $identifier); // 5 verification emails per hour
    }
}