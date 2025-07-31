<?php
// app/Core/Middleware/ValidationMiddleware.php

namespace App\Core\Middleware;

use App\Core\Validator;
use App\Core\Sanitizer;

class ValidationMiddleware
{
    private $rules = [];
    
    public function __construct($rules = [])
    {
        $this->rules = $rules;
    }
    
    /**
     * Execute validation middleware
     */
    public function handle($request, callable $next)
    {
        // Skip validation for GET requests unless specifically configured
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET' && empty($this->rules)) {
            return $next($request);
        }
        
        // Get validation rules based on route or method
        $validationRules = $this->getValidationRules();
        
        if (!empty($validationRules)) {
            $this->validateRequest($validationRules);
        }
        
        return $next($request);
    }
    
    /**
     * Validate request data
     */
    private function validateRequest($rules)
    {
        $data = $this->getRequestData();
        
        // Sanitize data before validation
        $data = Sanitizer::sanitize($data, Sanitizer::LEVEL_DATABASE);
        
        $validator = new Validator();
        $result = $validator->validate($data, $rules);
        
        if (!$result['valid']) {
            $this->handleValidationErrors($result['errors']);
        }
        
        // Store validated data for use in controllers
        $_REQUEST['validated_data'] = $result['validated_data'] ?? [];
    }
    
    /**
     * Get request data based on method
     */
    private function getRequestData()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        switch ($method) {
            case 'POST':
                return $_POST;
            case 'PUT':
            case 'PATCH':
                return $this->getInputData();
            case 'GET':
                return $_GET;
            default:
                return [];
        }
    }
    
    /**
     * Get input data from request body (for PUT/PATCH)
     */
    private function getInputData()
    {
        $input = file_get_contents('php://input');
        
        // Try to decode JSON
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        
        // Try to parse form data
        parse_str($input, $data);
        return $data;
    }
    
    /**
     * Get validation rules based on current route
     */
    private function getValidationRules()
    {
        if (!empty($this->rules)) {
            return $this->rules;
        }
        
        // Try to determine rules based on URI and method
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        return $this->getRouteValidationRules($uri, $method);
    }
    
    /**
     * Get validation rules for specific routes
     */
    private function getRouteValidationRules($uri, $method)
    {
        $rules = [];
        
        // Authentication routes
        if (strpos($uri, '/auth/login') !== false && $method === 'POST') {
            $rules = [
                'email' => ['required' => true, 'email' => true],
                'password' => ['required' => true, 'min_length' => 1]
            ];
        } elseif (strpos($uri, '/auth/register') !== false && $method === 'POST') {
            $rules = [
                'name' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'email' => ['required' => true, 'email' => true, 'unique' => ['table' => 'users', 'column' => 'email']],
                'password' => ['required' => true, 'min_length' => 8, 'password_strength' => true],
                'password_confirmation' => ['required' => true, 'matches' => 'password']
            ];
        } elseif (strpos($uri, '/auth/forgot-password') !== false && $method === 'POST') {
            $rules = [
                'email' => ['required' => true, 'email' => true]
            ];
        } elseif (strpos($uri, '/auth/reset-password') !== false && $method === 'POST') {
            $rules = [
                'token' => ['required' => true],
                'email' => ['required' => true, 'email' => true],
                'password' => ['required' => true, 'min_length' => 8, 'password_strength' => true],
                'password_confirmation' => ['required' => true, 'matches' => 'password']
            ];
        }
        
        // Profile routes
        elseif (strpos($uri, '/profile') !== false && $method === 'POST') {
            $rules = [
                'name' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'email' => ['required' => true, 'email' => true],
                'phone' => ['phone' => true],
                'bio' => ['max_length' => 1000]
            ];
        }
        
        // Settings routes
        elseif (strpos($uri, '/settings/password') !== false && $method === 'POST') {
            $rules = [
                'current_password' => ['required' => true],
                'new_password' => ['required' => true, 'min_length' => 8, 'password_strength' => true],
                'new_password_confirmation' => ['required' => true, 'matches' => 'new_password']
            ];
        }
        
        // School management routes
        elseif (strpos($uri, '/admin/schools') !== false && $method === 'POST') {
            $rules = [
                'name' => ['required' => true, 'min_length' => 2, 'max_length' => 200],
                'address' => ['required' => true, 'max_length' => 500],
                'contact_person' => ['required' => true, 'max_length' => 100],
                'contact_email' => ['required' => true, 'email' => true],
                'contact_phone' => ['phone' => true]
            ];
        }
        
        // Team management routes
        elseif (strpos($uri, '/teams') !== false && $method === 'POST') {
            $rules = [
                'name' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'school_id' => ['required' => true, 'integer' => true, 'exists' => ['table' => 'schools', 'column' => 'id']],
                'category' => ['required' => true, 'in' => ['junior', 'senior']],
                'coach_name' => ['required' => true, 'max_length' => 100],
                'coach_email' => ['required' => true, 'email' => true]
            ];
        }
        
        // Participant routes
        elseif (strpos($uri, '/participants') !== false && $method === 'POST') {
            $rules = [
                'first_name' => ['required' => true, 'min_length' => 2, 'max_length' => 50],
                'last_name' => ['required' => true, 'min_length' => 2, 'max_length' => 50],
                'email' => ['email' => true],
                'grade' => ['required' => true, 'integer' => true, 'between' => [6, 12]],
                'team_id' => ['required' => true, 'integer' => true, 'exists' => ['table' => 'teams', 'column' => 'id']]
            ];
        }
        
        // File upload validation
        elseif ($method === 'POST' && !empty($_FILES)) {
            $rules = $this->getFileValidationRules($_FILES);
        }
        
        return $rules;
    }
    
    /**
     * Get file validation rules
     */
    private function getFileValidationRules($files)
    {
        $rules = [];
        
        foreach ($files as $fieldName => $file) {
            if (is_array($file['name'])) {
                // Multiple files
                continue; // Handle multiple files separately
            }
            
            // Single file validation
            $rules[$fieldName] = ['file' => true];
            
            // Add specific rules based on field name
            if (strpos($fieldName, 'image') !== false || strpos($fieldName, 'photo') !== false) {
                $rules[$fieldName]['image'] = true;
                $rules[$fieldName]['mimes'] = ['image/jpeg', 'image/png', 'image/gif'];
                $rules[$fieldName]['max_file_size'] = 5242880; // 5MB
            } elseif (strpos($fieldName, 'document') !== false || strpos($fieldName, 'consent') !== false) {
                $rules[$fieldName]['mimes'] = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $rules[$fieldName]['max_file_size'] = 10485760; // 10MB
            } else {
                // General file
                $rules[$fieldName]['max_file_size'] = 2097152; // 2MB
            }
        }
        
        return $rules;
    }
    
    /**
     * Handle validation errors
     */
    private function handleValidationErrors($errors)
    {
        if ($this->isAjaxRequest()) {
            $this->handleAjaxValidationErrors($errors);
        } else {
            $this->handleFormValidationErrors($errors);
        }
    }
    
    /**
     * Handle AJAX validation errors
     */
    private function handleAjaxValidationErrors($errors)
    {
        http_response_code(422);
        header('Content-Type: application/json');
        
        echo json_encode([
            'error' => 'Validation failed',
            'errors' => $errors,
            'code' => 422
        ]);
        
        exit;
    }
    
    /**
     * Handle form validation errors
     */
    private function handleFormValidationErrors($errors)
    {
        session_start();
        
        // Store errors and old input in session
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        
        // Redirect back
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}