<?php
// app/Controllers/BaseController.php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Core\CSRF;
use App\Core\RateLimit;
use App\Core\Validator;
use App\Core\Sanitizer;
use App\Core\Security;
use App\Core\Logger;
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
    protected $logger;
    protected $data = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->session = Session::getInstance();
        $this->csrf = CSRF::getInstance();
        $this->rateLimit = RateLimit::getInstance();
        $this->logger = new Logger();
        
        // Set security headers
        $this->setSecurityHeaders();
        
        // Attempt remember me login if not already authenticated
        if (!$this->auth->check()) {
            $this->auth->attemptRememberLogin();
        }
        
        // Make CSRF token available to all views
        $this->data['csrf_token'] = $this->csrf->getToken();
        $this->data['csrf_field'] = $this->csrf->field();
        $this->data['csrf_meta'] = $this->csrf->getMetaTag();
        
        // Make current user available to views
        $this->data['current_user'] = $this->auth->user();
        $this->data['is_authenticated'] = $this->auth->check();
    }
    
    /**
     * Set security headers
     */
    private function setSecurityHeaders()
    {
        if (!headers_sent()) {
            Security::setSecurityHeaders();
        }
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
     * Return JSON response (alias for json method)
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        return $this->json($data, $statusCode);
    }
    
    /**
     * Return error response
     */
    protected function errorResponse($message, $statusCode = 500)
    {
        if (headers_sent()) {
            return $message;
        }
        
        http_response_code($statusCode);
        
        // Return JSON for AJAX requests
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            return json_encode(['error' => $message, 'status' => $statusCode]);
        }
        
        // Return HTML error page for regular requests
        return $this->view('errors/' . $statusCode, ['message' => $message]);
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
     * Get request input (sanitized)
     */
    protected function input($key = null, $default = null, $sanitize = true)
    {
        $data = $_REQUEST;
        
        if ($sanitize) {
            $data = Sanitizer::sanitize($data, Sanitizer::LEVEL_BASIC);
        }
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }
    
    /**
     * Get raw request input (unsanitized)
     */
    protected function rawInput($key = null, $default = null)
    {
        return $this->input($key, $default, false);
    }
    
    /**
     * Get sanitized input with specific level
     */
    protected function sanitizedInput($key = null, $level = Sanitizer::LEVEL_BASIC, $options = [])
    {
        $data = Sanitizer::sanitize($_REQUEST, $level, $options);
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? null;
    }
    
    /**
     * Validate request input
     */
    protected function validate($rules, $messages = [])
    {
        $validator = new Validator();
        $data = $this->sanitizedInput(null, Sanitizer::LEVEL_DATABASE);
        
        $result = $validator->validate($data, $rules);
        
        if (!$result['valid']) {
            $this->handleValidationErrors($result['errors'], $messages);
        }
        
        return $result['validated_data'];
    }
    
    /**
     * Handle validation errors
     */
    private function handleValidationErrors($errors, $customMessages = [])
    {
        // Apply custom messages if provided
        if (!empty($customMessages)) {
            foreach ($errors as $field => $fieldErrors) {
                if (isset($customMessages[$field])) {
                    $errors[$field] = [$customMessages[$field]];
                }
            }
        }
        
        if ($this->isAjaxRequest()) {
            http_response_code(422);
            echo $this->json([
                'error' => 'Validation failed',
                'errors' => $errors,
                'code' => 422
            ], 422);
            exit;
        } else {
            $this->flash('validation_errors', $errors);
            $this->flash('old_input', $_POST);
            $this->back();
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
        
        // Handle both direct public access and root access
        // If accessed via /public/, remove the /public part for consistent URLs
        if (substr($scriptPath, -7) === '/public') {
            $scriptPath = substr($scriptPath, 0, -7); // Remove '/public'
        }
        
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
    
    /**
     * Sanitize output for display
     */
    protected function escape($value, $encoding = 'UTF-8')
    {
        if (is_array($value)) {
            return array_map([$this, 'escape'], $value);
        }
        
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, $encoding);
    }
    
    /**
     * Generate secure token
     */
    protected function generateToken($length = 32)
    {
        return Security::generateToken($length);
    }
    
    /**
     * Log security event
     */
    protected function logSecurityEvent($event, $details = [])
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'details' => $details,
            'ip' => Security::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => $this->auth->id(),
            'session_id' => session_id()
        ];
        
        $logDir = STORAGE_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log('SECURITY_EVENT: ' . json_encode($logEntry), 3, $logDir . '/security.log');
    }
    
    /**
     * Handle file upload securely
     */
    protected function handleFileUpload($fieldName, $uploadDir, $allowedTypes = [], $maxSize = 2097152)
    {
        if (!isset($_FILES[$fieldName])) {
            throw new Exception('No file uploaded');
        }
        
        return Security::handleFileUpload($_FILES[$fieldName], $uploadDir, $allowedTypes, $maxSize);
    }
    
    /**
     * Check and enforce rate limit
     */
    protected function checkRateLimit($identifier, $maxAttempts, $timeWindow = 3600)
    {
        if (!Security::checkRateLimit($identifier, $maxAttempts, $timeWindow)) {
            http_response_code(429);
            
            if ($this->isAjaxRequest()) {
                echo $this->json([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $timeWindow
                ], 429);
            } else {
                $this->flash('error', 'Too many requests. Please try again later.');
                $this->back();
            }
            
            exit;
        }
    }
    
    /**
     * Verify request came from same origin
     */
    protected function verifySameOrigin()
    {
        if (!CSRF::verifySameSite()) {
            $this->logSecurityEvent('invalid_origin', [
                'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            throw new Exception('Invalid request origin', 403);
        }
        
        return true;
    }
    
    /**
     * Get validation errors from session
     */
    protected function getValidationErrors()
    {
        return $this->session->flash('validation_errors');
    }
    
    /**
     * Get old input from session
     */
    protected function getOldInput($key = null, $default = null)
    {
        $oldInput = $this->session->flash('old_input') ?? [];
        
        if ($key === null) {
            return $oldInput;
        }
        
        return $oldInput[$key] ?? $default;
    }
}