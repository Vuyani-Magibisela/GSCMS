<?php
// app/Controllers/BaseController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use Exception;

abstract class BaseController
{
    protected $request;
    protected $response;
    protected $db;
    protected $data = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Render a view
     */
    protected function view($template, $data = [])
    {
        $data = array_merge($this->data, $data);
        
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
        $_SESSION['flash'][$key] = $message;
    }
    
    /**
     * Get flashed message
     */
    protected function getFlash($key)
    {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
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
     * Check if user is authenticated
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/auth/login');
        }
    }
    
    /**
     * Check if user has role
     */
    protected function requireRole($role)
    {
        if (!$this->hasRole($role)) {
            throw new Exception("Access denied. Required role: {$role}", 403);
        }
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Check if user has role
     */
    protected function hasRole($role)
    {
        $userRole = $_SESSION['user_role'] ?? null;
        return $userRole === $role || $userRole === 'super_admin';
    }
    
    /**
     * Get current authenticated user
     */
    protected function user()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $this->db->table('users')->find($_SESSION['user_id']);
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
}