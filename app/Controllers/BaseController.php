<?php
// app/Controllers/BaseController.php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Core\CSRF;
use App\Core\RateLimit;
use Exception;

abstract class BaseController
{
    protected $request;
    protected $response;
    protected $db;
    protected $auth;
    protected $session;
    protected $csrf;
    protected $rateLimit;
    protected $data = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->session = Session::getInstance();
        $this->csrf = CSRF::getInstance();
        $this->rateLimit = RateLimit::getInstance();
        
        // Attempt remember me login if not already authenticated
        if (!$this->auth->check()) {
            $this->auth->attemptRememberLogin();
        }
        
        // Make CSRF token available to all views
        $this->data['csrf_token'] = $this->csrf->getToken();
        $this->data['csrf_field'] = '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($this->csrf->getToken()) . '">';
    }
    
    /**
     * Render a view
     */
    protected function view($template, $data = [])
    {
        $data = array_merge($this->data, $data);
        
        // Make baseUrl function available to views
        $data['baseUrl'] = $this->baseUrl();
        
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $template) . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }
        
        // Extract variables to view scope
        extract($data);
        
        // Start output buffering
        ob_start();
        
        try {
            include $viewFile;
            $content = ob_get_contents();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        
        ob_end_clean();
        
        return $content;
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        return json_encode($data);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    /**
     * Redirect back
     */
    protected function back()
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referrer);
    }
    
    /**
     * Get request input
     */
    protected function input($key = null, $default = null)
    {
        if ($key === null) {
            return $_REQUEST;
        }
        
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Validate request input
     */
    protected function validate($rules, $messages = [])
    {
        $validator = new \App\Core\Validator();
        
        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $fieldRule) {
                $ruleName = $fieldRule;
                $ruleValue = null;
                
                if (strpos($fieldRule, ':') !== false) {
                    [$ruleName, $ruleValue] = explode(':', $fieldRule, 2);
                }
                
                if (!$validator->validate($value, $ruleName, $ruleValue)) {
                    $message = $messages["{$field}.{$ruleName}"] ?? 
                              $messages[$field] ?? 
                              "The {$field} field is invalid";
                    
                    throw new Exception($message, 422);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Flash message to session
     */
    protected function flash($key, $message)
    {
        $this->session->flash($key, $message);
    }
    
    /**
     * Get flashed message
     */
    protected function getFlash($key)
    {
        return $this->session->flash($key);
    }
    
    /**
     * Set view data
     */
    protected function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Require authentication
     */
    protected function requireAuth()
    {
        try {
            $this->auth->requireAuth();
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                $this->redirect($this->baseUrl('auth/login'));
            }
            throw $e;
        }
    }
    
    /**
     * Require specific role
     */
    protected function requireRole($role)
    {
        $this->auth->requireRole($role);
    }
    
    /**
     * Require any of the given roles
     */
    protected function requireAnyRole($roles)
    {
        $this->auth->requireAnyRole($roles);
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return $this->auth->check();
    }
    
    /**
     * Check if user has role
     */
    protected function hasRole($role)
    {
        return $this->auth->hasRole($role);
    }
    
    /**
     * Check if user has any of the given roles
     */
    protected function hasAnyRole($roles)
    {
        return $this->auth->hasAnyRole($roles);
    }
    
    /**
     * Check if user is admin
     */
    protected function isAdmin()
    {
        return $this->auth->isAdmin();
    }
    
    /**
     * Get current authenticated user
     */
    protected function user()
    {
        return $this->auth->user();
    }
    
    /**
     * Check if user has specific permission
     */
    protected function hasPermission($permission)
    {
        return $this->auth->hasPermission($permission);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    protected function hasAnyPermission($permissions)
    {
        return $this->auth->hasAnyPermission($permissions);
    }
    
    /**
     * Check if user has all of the given permissions
     */
    protected function hasAllPermissions($permissions)
    {
        return $this->auth->hasAllPermissions($permissions);
    }
    
    /**
     * Require specific permission
     */
    protected function requirePermission($permission)
    {
        try {
            $this->auth->requirePermission($permission);
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                $this->redirect($this->baseUrl('auth/login'));
            } elseif ($e->getCode() === 403) {
                $this->flash('error', $e->getMessage());
                $this->redirect($this->baseUrl('dashboard'));
            }
            throw $e;
        }
    }
    
    /**
     * Require any of the given permissions
     */
    protected function requireAnyPermission($permissions)
    {
        try {
            $this->auth->requireAnyPermission($permissions);
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                $this->redirect($this->baseUrl('auth/login'));
            } elseif ($e->getCode() === 403) {
                $this->flash('error', $e->getMessage());
                $this->redirect($this->baseUrl('dashboard'));
            }
            throw $e;
        }
    }
    
    /**
     * Require all of the given permissions
     */
    protected function requireAllPermissions($permissions)
    {
        try {
            $this->auth->requireAllPermissions($permissions);
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                $this->redirect($this->baseUrl('auth/login'));
            } elseif ($e->getCode() === 403) {
                $this->flash('error', $e->getMessage());
                $this->redirect($this->baseUrl('dashboard'));
            }
            throw $e;
        }
    }
    
    /**
     * Check if user can access resource based on role hierarchy
     */
    protected function canAccess($requiredRole)
    {
        return $this->auth->canAccess($requiredRole);
    }
    
    /**
     * Check if user can manage another role
     */
    protected function canManage($targetRole)
    {
        return $this->auth->canManage($targetRole);
    }
    
    /**
     * Check if user owns specific resource
     */
    protected function ownsResource($resourceType, $resourceId)
    {
        return $this->auth->ownsResource($resourceType, $resourceId);
    }
    
    /**
     * Require resource ownership or admin access
     */
    protected function requireResourceOwnership($resourceType, $resourceId)
    {
        if (!$this->ownsResource($resourceType, $resourceId)) {
            $this->flash('error', 'You do not have permission to access this resource.');
            $this->redirect($this->baseUrl('dashboard'));
        }
    }
    
    /**
     * Get all permissions for current user
     */
    protected function getPermissions()
    {
        return $this->auth->getPermissions();
    }
    
    /**
     * Get role hierarchy level for current user
     */
    protected function getRoleLevel()
    {
        return $this->auth->getRoleLevel();
    }
    
    /**
     * Get all roles that current user can manage
     */
    protected function getManageableRoles()
    {
        return $this->auth->getManageableRoles();
    }
    
    /**
     * Verify CSRF token
     */
    protected function verifyCsrf()
    {
        try {
            $this->csrf->verifyRequest();
        } catch (Exception $e) {
            if ($e->getCode() === 419) {
                $this->flash('error', 'Security token mismatch. Please try again.');
                return $this->back();
            }
            throw $e;
        }
        
        return true;
    }
    
    /**
     * Enforce rate limit
     */
    protected function enforceRateLimit($action, $maxAttempts, $windowMinutes = 60, $identifier = null)
    {
        try {
            $this->rateLimit->enforce($action, $maxAttempts, $windowMinutes, $identifier);
        } catch (Exception $e) {
            if ($e->getCode() === 429) {
                $this->flash('error', $e->getMessage());
                return $this->back();
            }
            throw $e;
        }
        
        return true;
    }
    
    /**
     * Get rate limit info
     */
    protected function getRateLimitInfo($action, $maxAttempts, $windowMinutes = 60, $identifier = null)
    {
        return $this->rateLimit->getInfo($action, $maxAttempts, $windowMinutes, $identifier);
    }
    
    /**
     * Record rate limit attempt
     */
    protected function recordRateLimitAttempt($action, $windowMinutes = 60, $identifier = null)
    {
        return $this->rateLimit->recordAttempt($action, $windowMinutes, $identifier);
    }
    
    /**
     * Handle 404 errors
     */
    protected function notFound($message = 'Page not found')
    {
        http_response_code(404);
        return $this->view('errors.404', ['message' => $message]);
    }
    
    /**
     * Handle method not allowed
     */
    protected function methodNotAllowed()
    {
        http_response_code(405);
        return $this->view('errors.405');
    }
    
    /**
     * Handle server errors
     */
    protected function serverError($message = 'Internal server error')
    {
        http_response_code(500);
        return $this->view('errors.500', ['message' => $message]);
    }
    
    /**
     * Get base URL for the application
     */
    protected function baseUrl($path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $scriptPath = $scriptPath === '/' ? '' : $scriptPath;
        
        $baseUrl = $protocol . $host . $scriptPath;
        
        return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
    }
    
    /**
     * Generate URL for a given path
     */
    protected function url($path = '')
    {
        return $this->baseUrl($path);
    }
    
    /**
     * Function to check if current page matches the given path
     */
    protected function isActivePage($pageName)
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        
        if ($scriptPath !== '/' && strpos($uri, $scriptPath) === 0) {
            $uri = substr($uri, strlen($scriptPath));
        }
        
        $uri = trim($uri, '/');
        $currentPage = explode('/', $uri)[0] ?: 'home';
        
        return $currentPage === $pageName;
    }
}