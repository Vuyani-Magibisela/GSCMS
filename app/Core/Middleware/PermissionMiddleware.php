<?php
// app/Core/Middleware/PermissionMiddleware.php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Response;
use Exception;

class PermissionMiddleware
{
    /**
     * Handle middleware request
     * 
     * Usage: 'permission:user.manage' or 'permission:user.manage,school.view'
     */
    public function handle($request, $next, $permissions = null)
    {
        $auth = Auth::getInstance();
        
        try {
            // Ensure user is authenticated first
            $auth->requireAuth();
            
            if ($permissions) {
                $requiredPermissions = is_string($permissions) ? explode(',', $permissions) : (array)$permissions;
                $auth->requireAnyPermission($requiredPermissions);
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
                return $response->redirect('/auth/login');
            } else {
                // Access denied - redirect to dashboard with error
                $session = \App\Core\Session::getInstance();
                $session->flash('error', $e->getMessage());
                return $response->redirect('/dashboard');
            }
        }
    }
}