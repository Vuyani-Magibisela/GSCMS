<?php
// app/Core/Middleware/CSRFMiddleware.php

namespace App\Core\Middleware;

use App\Core\CSRF;
use Exception;

class CSRFMiddleware
{
    private $csrf;
    
    public function __construct()
    {
        $this->csrf = CSRF::getInstance();
    }
    
    /**
     * Execute CSRF protection middleware
     */
    public function handle($request, callable $next)
    {
        try {
            // Skip CSRF check for safe methods
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
                return $next($request);
            }
            
            // Skip CSRF for API endpoints (should use API tokens instead)
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($uri, '/api/') !== false) {
                return $next($request);
            }
            
            // Verify CSRF token
            $this->csrf->verifyRequest();
            
            return $next($request);
            
        } catch (Exception $e) {
            if ($e->getCode() === 419) {
                // CSRF token mismatch
                http_response_code(419);
                
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'error' => 'CSRF token mismatch',
                        'message' => 'Please refresh the page and try again.',
                        'code' => 419
                    ]);
                } else {
                    // Redirect back with error
                    $this->redirectWithError('Security token mismatch. Please try again.');
                }
                
                exit;
            }
            
            throw $e;
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Redirect back with error message
     */
    private function redirectWithError($message)
    {
        session_start();
        $_SESSION['error'] = $message;
        
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
    }
}