<?php
// app/Core/Session.php

namespace App\Core;

use Exception;

class Session
{
    private static $instance = null;
    private $sessionStarted = false;
    private $config = [];
    
    private function __construct()
    {
        $this->config = [
            'name' => 'GSCMS_SESSION',
            'lifetime' => 7200, // 2 hours
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ];
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
     * Start secure session
     */
    public function start()
    {
        if ($this->sessionStarted) {
            return true;
        }
        
        // Check if session is already active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->sessionStarted = true;
        } else {
            // Configure session settings only if session is not started
            ini_set('session.name', $this->config['name']);
            ini_set('session.cookie_lifetime', $this->config['lifetime']);
            ini_set('session.cookie_path', $this->config['path']);
            ini_set('session.cookie_domain', $this->config['domain']);
            ini_set('session.cookie_secure', $this->config['secure']);
            ini_set('session.cookie_httponly', $this->config['httponly']);
            ini_set('session.cookie_samesite', $this->config['samesite']);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Start session
            session_start();
            $this->sessionStarted = true;
        }
        
        // Initialize session security
        $this->initializeSecurity();
        
        return true;
    }
    
    /**
     * Initialize session security measures
     */
    private function initializeSecurity()
    {
        // Regenerate session ID on first visit
        if (!$this->has('_initialized')) {
            $this->regenerateId();
            $this->set('_initialized', true);
            $this->set('_created', time());
        }
        
        // Check for session hijacking
        $this->validateSession();
        
        // Check for session timeout
        $this->checkTimeout();
        
        // Update last activity
        $this->set('_last_activity', time());
    }
    
    /**
     * Validate session against hijacking
     */
    private function validateSession()
    {
        try {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = $this->getClientIp();
            
            if ($this->has('_user_agent')) {
                if ($this->get('_user_agent') !== $userAgent) {
                    $this->destroy();
                    throw new Exception('Session validation failed: User agent mismatch');
                }
            } else {
                $this->set('_user_agent', $userAgent);
            }
            
            if ($this->has('_ip_address')) {
                if ($this->get('_ip_address') !== $ipAddress) {
                    $this->destroy();
                    throw new Exception('Session validation failed: IP address mismatch');
                }
            } else {
                $this->set('_ip_address', $ipAddress);
            }
        } catch (Exception $e) {
            // Log validation errors but don't throw them to prevent blocking legitimate users
            error_log('Session validation warning: ' . $e->getMessage());
        }
    }
    
    /**
     * Check session timeout
     */
    private function checkTimeout()
    {
        try {
            $maxLifetime = $this->config['lifetime'];
            $lastActivity = $this->get('_last_activity', time());
            
            if ((time() - $lastActivity) > $maxLifetime) {
                $this->destroy();
                // Don't throw exception for timeout - just log it
                error_log('Session expired due to inactivity');
            }
        } catch (Exception $e) {
            error_log('Session timeout check error: ' . $e->getMessage());
        }
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
     * Set session value
     */
    public function set($key, $value)
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public function get($key, $default = null)
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public function has($key)
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public function remove($key)
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        unset($_SESSION[$key]);
    }
    
    /**
     * Get all session data
     */
    public function all()
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        return $_SESSION;
    }
    
    /**
     * Clear all session data
     */
    public function clear()
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        $_SESSION = [];
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerateId($deleteOld = true)
    {
        if (!$this->sessionStarted) {
            $this->start();
        }
        
        session_regenerate_id($deleteOld);
    }
    
    /**
     * Destroy session
     */
    public function destroy()
    {
        if (!$this->sessionStarted) {
            return true;
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session
        session_destroy();
        $this->sessionStarted = false;
        
        return true;
    }
    
    /**
     * Flash message functionality
     */
    public function flash($key, $message = null)
    {
        if ($message === null) {
            // Get flash message
            $flash = $this->get('_flash', []);
            $message = $flash[$key] ?? null;
            
            // Remove after retrieving
            unset($flash[$key]);
            $this->set('_flash', $flash);
            
            return $message;
        } else {
            // Set flash message
            $flash = $this->get('_flash', []);
            $flash[$key] = $message;
            $this->set('_flash', $flash);
        }
    }
    
    /**
     * Get session ID
     */
    public function getId()
    {
        return session_id();
    }
    
    /**
     * Get session name
     */
    public function getName()
    {
        return session_name();
    }
    
    /**
     * Check if session is active
     */
    public function isActive()
    {
        return $this->sessionStarted && session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Get session creation time
     */
    public function getCreatedTime()
    {
        return $this->get('_created', 0);
    }
    
    /**
     * Get last activity time
     */
    public function getLastActivity()
    {
        return $this->get('_last_activity', 0);
    }
    
    /**
     * Get session age in seconds
     */
    public function getAge()
    {
        return time() - $this->getCreatedTime();
    }
    
    /**
     * Get time since last activity in seconds
     */
    public function getIdleTime()
    {
        return time() - $this->getLastActivity();
    }
    
    /**
     * Store previous URL for redirects
     */
    public function setPreviousUrl($url = null)
    {
        if ($url === null) {
            $url = $_SERVER['REQUEST_URI'] ?? '/';
        }
        
        $this->set('_previous_url', $url);
    }
    
    /**
     * Get previous URL
     */
    public function getPreviousUrl($default = '/')
    {
        return $this->get('_previous_url', $default);
    }
    
    /**
     * Store intended URL for post-login redirect
     */
    public function setIntendedUrl($url)
    {
        $this->set('_intended_url', $url);
    }
    
    /**
     * Get intended URL
     */
    public function getIntendedUrl($default = '/')
    {
        $intended = $this->get('_intended_url', $default);
        $this->remove('_intended_url');
        return $intended;
    }
}