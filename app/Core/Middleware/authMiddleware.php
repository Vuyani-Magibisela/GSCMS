<?php
// app/Core/Middleware/AuthMiddleware.php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Response;
use Exception;

class AuthMiddleware
{
    /**
     * Handle middleware request
     */
    public function handle($request, $next)
    {
        $auth = Auth::getInstance();
        
        try {
            $auth->requireAuth();
            return $next($request);
        } catch (Exception $e) {
            // Redirect to login page
            $response = new Response();
            
            if ($request->expectsJson()) {
                return $response->json([
                    'error' => 'Authentication required',
                    'message' => $e->getMessage()
                ], 401);
            }
            
            // Store intended URL for redirect after login
            $session = \App\Core\Session::getInstance();
            $session->setIntendedUrl($request->getUri());
            
            return $response->redirect($this->getAppUrl('/auth/login'));
        }
    }
    
    /**
     * Get application URL with proper base path handling
     */
    private function getAppUrl($path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $scriptPath = $scriptPath === '/' ? '' : $scriptPath;
        
        $baseUrl = $protocol . $host . $scriptPath;
        
        return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
    }
}