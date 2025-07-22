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
}