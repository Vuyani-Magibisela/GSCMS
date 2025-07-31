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
    
    /**
     * Create a redirect response
     */
    public function redirect($url, $statusCode = 302)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->setContent('');
        
        // Send immediately for redirects
        $this->send();
        exit;
    }
    
    /**
     * Create a JSON response
     */
    public function json($data, $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        return $this;
    }
    
    /**
     * Create a view response
     */
    public function view($view, $data = [], $layout = null)
    {
        // This would typically be handled by the Controller
        return $this;
    }
    
    /**
     * Set response as not found
     */
    public function notFound($message = 'Not Found')
    {
        $this->setStatusCode(404);
        $this->setContent($message);
        return $this;
    }
    
    /**
     * Set response as forbidden
     */
    public function forbidden($message = 'Forbidden')
    {
        $this->setStatusCode(403);
        $this->setContent($message);
        return $this;
    }
    
    /**
     * Set response as server error
     */
    public function serverError($message = 'Internal Server Error')
    {
        $this->setStatusCode(500);
        $this->setContent($message);
        return $this;
    }
}