<?php
// app/Core/Response.php

namespace App\Core;

class Response
{
    private $content;
    private $statusCode = 200;
    private $headers = [];
    
    /**
     * Set response content
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Get response content
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Set status code
     */
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * Get status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Set header
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Get header
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }
    
    /**
     * Get all headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Send response
     */
    public function send()
    {
        // Set status code
        http_response_code($this->statusCode);
        
        // Set headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        // Output content
        echo $this->content;
    }
}