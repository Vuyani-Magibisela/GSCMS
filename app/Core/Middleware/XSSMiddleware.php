<?php
// app/Core/Middleware/XSSMiddleware.php

namespace App\Core\Middleware;

use App\Core\Sanitizer;
use App\Core\Security;

class XSSMiddleware
{
    /**
     * Execute XSS protection middleware
     */
    public function handle($request, callable $next)
    {
        // Set XSS protection headers
        $this->setXSSHeaders();
        
        // Sanitize request data
        $this->sanitizeRequestData();
        
        return $next($request);
    }
    
    /**
     * Set XSS protection headers
     */
    private function setXSSHeaders()
    {
        if (!headers_sent()) {
            // XSS Protection
            header('X-XSS-Protection: 1; mode=block');
            
            // Content Type Options
            header('X-Content-Type-Options: nosniff');
            
            // Frame Options
            header('X-Frame-Options: DENY');
            
            // Content Security Policy
            $csp = Security::generateCSPHeader([
                'default-src' => "'self'",
                'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net",
                'style-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                'img-src' => "'self' data: https:",
                'font-src' => "'self' https:",
                'connect-src' => "'self'",
                'media-src' => "'self'",
                'object-src' => "'none'",
                'child-src' => "'self'",
                'frame-ancestors' => "'none'",
                'form-action' => "'self'",
                'base-uri' => "'self'"
            ]);
            
            header('Content-Security-Policy: ' . $csp);
        }
    }
    
    /**
     * Sanitize request data to prevent XSS
     */
    private function sanitizeRequestData()
    {
        // Don't sanitize file uploads or binary data
        if ($this->isBinaryRequest()) {
            return;
        }
        
        // Sanitize GET parameters
        if (!empty($_GET)) {
            $_GET = $this->sanitizeArray($_GET);
        }
        
        // Sanitize POST parameters (except for specific fields that need HTML)
        if (!empty($_POST)) {
            $_POST = $this->sanitizeArray($_POST, $this->getHtmlAllowedFields());
        }
        
        // Sanitize COOKIE data
        if (!empty($_COOKIE)) {
            $_COOKIE = $this->sanitizeArray($_COOKIE);
        }
    }
    
    /**
     * Sanitize array recursively
     */
    private function sanitizeArray($data, $htmlAllowedFields = [])
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeKey($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $htmlAllowedFields);
            } else {
                // Check if this field allows HTML content
                if (in_array($key, $htmlAllowedFields)) {
                    $sanitized[$sanitizedKey] = Sanitizer::sanitize($value, Sanitizer::LEVEL_STRIP_TAGS, [
                        'allowed_tags' => '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>'
                    ]);
                } else {
                    $sanitized[$sanitizedKey] = Sanitizer::sanitize($value, Sanitizer::LEVEL_BASIC);
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize array key
     */
    private function sanitizeKey($key)
    {
        // Only allow alphanumeric, underscore, and dash in keys
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    }
    
    /**
     * Get fields that are allowed to contain HTML
     */
    private function getHtmlAllowedFields()
    {
        return [
            'content',
            'description',
            'message',
            'body',
            'notes',
            'comment',
            'details',
            'announcement_content',
            'resource_description'
        ];
    }
    
    /**
     * Check if request contains binary data
     */
    private function isBinaryRequest()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        return strpos($contentType, 'multipart/form-data') !== false ||
               strpos($contentType, 'application/octet-stream') !== false ||
               strpos($contentType, 'image/') !== false ||
               strpos($contentType, 'video/') !== false ||
               strpos($contentType, 'audio/') !== false;
    }
    
    /**
     * Check if value contains potential XSS
     */
    private function containsXSS($value)
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript\s*:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/<applet\b[^>]*>/i',
            '/<meta\b[^>]*>/i',
            '/<link\b[^>]*>/i',
            '/data\s*:/i',
            '/vbscript\s*:/i',
            '/expression\s*\(/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log potential XSS attempt
     */
    private function logXSSAttempt($field, $value, $ip)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'field' => $field,
            'value' => substr($value, 0, 500), // Limit log entry size
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ];
        
        error_log('XSS Attempt: ' . json_encode($logData), 3, STORAGE_PATH . '/logs/security.log');
    }
}