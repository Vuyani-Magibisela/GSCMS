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
    private $tokenLifetime = 3600; // 1 hour
    private $doubleSubmitCookie = true;
    
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
    public function generateToken($form = null)
    {
        $token = bin2hex(random_bytes($this->tokenLength));
        $timestamp = time();
        
        $tokenData = [
            'token' => $token,
            'timestamp' => $timestamp,
            'form' => $form
        ];
        
        if ($form) {
            // Form-specific token
            $this->session->set($this->tokenName . '_' . $form, $tokenData);
        } else {
            // General token
            $this->session->set($this->tokenName, $tokenData);
        }
        
        // Set double-submit cookie if enabled
        if ($this->doubleSubmitCookie) {
            $cookieName = $this->tokenName . '_cookie';
            setcookie($cookieName, $token, [
                'expires' => time() + $this->tokenLifetime,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        return $token;
    }
    
    /**
     * Get current CSRF token
     */
    public function getToken($form = null)
    {
        $sessionKey = $form ? $this->tokenName . '_' . $form : $this->tokenName;
        $tokenData = $this->session->get($sessionKey);
        
        if (!$tokenData || !is_array($tokenData)) {
            return $this->generateToken($form);
        }
        
        // Check if token has expired
        if (isset($tokenData['timestamp']) && 
            (time() - $tokenData['timestamp']) > $this->tokenLifetime) {
            return $this->generateToken($form);
        }
        
        return $tokenData['token'] ?? $this->generateToken($form);
    }
    
    /**
     * Validate CSRF token
     */
    public function validateToken($token, $form = null)
    {
        $sessionKey = $form ? $this->tokenName . '_' . $form : $this->tokenName;
        $tokenData = $this->session->get($sessionKey);
        
        if (!$tokenData || !$token) {
            return false;
        }
        
        $sessionToken = is_array($tokenData) ? $tokenData['token'] : $tokenData;
        
        // Check token expiration
        if (is_array($tokenData) && isset($tokenData['timestamp'])) {
            if ((time() - $tokenData['timestamp']) > $this->tokenLifetime) {
                return false;
            }
        }
        
        // Validate double-submit cookie if enabled
        if ($this->doubleSubmitCookie) {
            $cookieName = $this->tokenName . '_cookie';
            $cookieToken = $_COOKIE[$cookieName] ?? null;
            
            if (!$cookieToken || !hash_equals($cookieToken, $token)) {
                return false;
            }
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Verify CSRF token from request
     */
    public function verifyRequest($form = null)
    {
        // Skip CSRF for GET requests (should be idempotent)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        // Verify origin header
        if (!$this->verifyOrigin()) {
            throw new Exception('Request origin verification failed.', 419);
        }
        
        // Get token from various sources
        $token = $this->getTokenFromRequest();
        
        if (!$this->validateToken($token, $form)) {
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
        
        // Check Authorization header for Bearer token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Verify request origin
     */
    private function verifyOrigin()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Build expected origins
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $expectedOrigin = $protocol . '://' . $host;
        
        // Check origin header
        if ($origin && $origin !== $expectedOrigin) {
            return false;
        }
        
        // Check referer header as fallback
        if (!$origin && $referer && !str_starts_with($referer, $expectedOrigin)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate hidden input field for forms
     */
    public function field($form = null)
    {
        $token = $this->getToken($form);
        return '<input type="hidden" name="' . $this->tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get token for JavaScript/AJAX requests
     */
    public function getTokenForJs($form = null)
    {
        return [
            'name' => $this->tokenName,
            'value' => $this->getToken($form),
            'header' => 'X-CSRF-TOKEN'
        ];
    }
    
    /**
     * Regenerate token (after successful form submission)
     */
    public function regenerateToken($form = null)
    {
        // Clear old token
        $this->clearToken($form);
        
        // Generate new token
        return $this->generateToken($form);
    }
    
    /**
     * Clear token
     */
    public function clearToken($form = null)
    {
        $sessionKey = $form ? $this->tokenName . '_' . $form : $this->tokenName;
        $this->session->remove($sessionKey);
        
        // Clear cookie if using double-submit
        if ($this->doubleSubmitCookie) {
            $cookieName = $this->tokenName . '_cookie';
            setcookie($cookieName, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }
    
    /**
     * Set token lifetime
     */
    public function setTokenLifetime($seconds)
    {
        $this->tokenLifetime = $seconds;
        return $this;
    }
    
    /**
     * Enable/disable double-submit cookie
     */
    public function setDoubleSubmitCookie($enabled)
    {
        $this->doubleSubmitCookie = $enabled;
        return $this;
    }
    
    /**
     * Clean expired tokens from session
     */
    public function cleanExpiredTokens()
    {
        $sessionData = $this->session->all();
        $currentTime = time();
        
        foreach ($sessionData as $key => $value) {
            if (strpos($key, $this->tokenName) === 0 && is_array($value)) {
                if (isset($value['timestamp']) && 
                    ($currentTime - $value['timestamp']) > $this->tokenLifetime) {
                    $this->session->remove($key);
                }
            }
        }
    }
    
    /**
     * Get meta tag for CSRF token (for HTML head)
     */
    public function getMetaTag($form = null)
    {
        $token = $this->getToken($form);
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Verify request is from same site
     */
    public static function verifySameSite()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $expectedOrigin = $protocol . '://' . $host;
        
        if ($origin && $origin !== $expectedOrigin) {
            return false;
        }
        
        if (!$origin && $referer && !str_starts_with($referer, $expectedOrigin)) {
            return false;
        }
        
        return true;
    }
}