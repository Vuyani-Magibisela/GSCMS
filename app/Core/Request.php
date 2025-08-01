<?php
// app/Core/Request.php

namespace App\Core;

class Request
{
    private $parameters = [];
    
    public function __construct()
    {
        // Initialize request data
    }
    
    /**
     * Get request URI
     */
    public function getUri()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    /**
     * Get request method
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * Get input value
     */
    public function input($key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Get GET parameter
     */
    public function get($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get POST parameter
     */
    public function post($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get all input
     */
    public function all()
    {
        return $_REQUEST;
    }
    
    /**
     * Check if input exists
     */
    public function has($key)
    {
        return isset($_REQUEST[$key]);
    }
    
    /**
     * Set route parameter
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }
    
    /**
     * Get route parameter
     */
    public function getParameter($key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }
    
    /**
     * Get all route parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    
    /**
     * Check if request expects JSON response
     */
    public function expectsJson()
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Check Accept header for JSON
        if (strpos($acceptHeader, 'application/json') !== false) {
            return true;
        }
        
        // Check Content-Type header for JSON
        if (strpos($contentType, 'application/json') !== false) {
            return true;
        }
        
        // Check if request is AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get client IP address
     */
    public function getClientIp()
    {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Return normal IP
        else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Get user agent
     */
    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}