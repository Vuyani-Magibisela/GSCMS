<?php
// app/Core/CSRF.php

namespace App\Core;

use Exception;

class CSRF
{
    private static $instance = null;
    private $session;
    private $tokenName = '_csrf_token';
    private $tokenLength = 32;
    
    private function __construct()
    {
        $this->session = Session::getInstance();
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
     * Generate CSRF token
     */
    public function generateToken()
    {
        $token = bin2hex(random_bytes($this->tokenLength));
        $this->session->set($this->tokenName, $token);
        return $token;
    }
    
    /**
     * Get current CSRF token
     */
    public function getToken()
    {
        $token = $this->session->get($this->tokenName);
        
        if (!$token) {
            $token = $this->generateToken();
        }
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validateToken($token)
    {
        $sessionToken = $this->session->get($this->tokenName);
        
        if (!$sessionToken || !$token) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Verify CSRF token from request
     */
    public function verifyRequest()
    {
        // Skip CSRF for GET requests (should be idempotent)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        // Get token from various sources
        $token = $this->getTokenFromRequest();
        
        if (!$this->validateToken($token)) {
            throw new Exception('CSRF token mismatch. Please try again.', 419);
        }
        
        return true;
    }
    
    /**
     * Get token from request (POST data, headers, etc.)
     */
    private function getTokenFromRequest()
    {
        // Check POST data first
        if (isset($_POST[$this->tokenName])) {
            return $_POST[$this->tokenName];
        }
        
        // Check custom header
        $headerName = 'HTTP_X_CSRF_TOKEN';
        if (isset($_SERVER[$headerName])) {
            return $_SERVER[$headerName];
        }
        
        // Check meta header (for AJAX requests)
        $metaHeaderName = 'HTTP_X_XSRF_TOKEN';
        if (isset($_SERVER[$metaHeaderName])) {
            return $_SERVER[$metaHeaderName];
        }
        
        return null;
    }
    
    /**
     * Generate hidden input field for forms
     */
    public function field()
    {
        $token = $this->getToken();
        return '<input type="hidden" name="' . $this->tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get token for JavaScript/AJAX requests
     */
    public function getTokenForJs()
    {
        return [
            'name' => $this->tokenName,
            'value' => $this->getToken()
        ];
    }
    
    /**
     * Regenerate token (after successful form submission)
     */
    public function regenerateToken()
    {
        return $this->generateToken();
    }
    
    /**
     * Clear token
     */
    public function clearToken()
    {
        $this->session->remove($this->tokenName);
    }
}