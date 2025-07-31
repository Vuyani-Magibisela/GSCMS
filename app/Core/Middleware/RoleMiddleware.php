<?php
// app/Core/Middleware/RoleMiddleware.php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Response;
use Exception;

class RoleMiddleware
{
    /**
     * Handle middleware request
     * 
     * Usage: 'role:super_admin' or 'role:super_admin,competition_admin'
     */
    public function handle($request, $next, $roles = null)
    {
        $auth = Auth::getInstance();
        
        try {
            // Ensure user is authenticated first
            $auth->requireAuth();
            
            if ($roles) {
                $allowedRoles = is_string($roles) ? explode(',', $roles) : (array)$roles;
                $auth->requireAnyRole($allowedRoles);
            }
            
            return $next($request);
        } catch (Exception $e) {
            $response = new Response();
            
            if ($request->expectsJson()) {
                return $response->json([
                    'error' => 'Access denied',
                    'message' => $e->getMessage()
                ], 403);
            }
            
            // Redirect to appropriate page based on error code
            if ($e->getCode() === 401) {
                // Not authenticated - redirect to login
                $session = \App\Core\Session::getInstance();
                $session->setIntendedUrl($request->getUri());
                return $response->redirect($this->getAppUrl('/auth/login'));
            } else {
                // Access denied - redirect to dashboard with error
                $session = \App\Core\Session::getInstance();
                $session->flash('error', $e->getMessage());
                return $response->redirect($this->getAppUrl('/dashboard'));
            }
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