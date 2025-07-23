<?php
// app/Core/Validator.php

namespace App\Core;

use App\Core\Database;

class Validator
{
    private $errors = [];
    private $data = [];
    
    /**
     * Validate data against rules
     */
    public function validate($data, $rules)
    {
        $this->data = $data;
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Validate individual field
     */
    private function validateField($field, $rules)
    {
        $value = $this->data[$field] ?? null;
        
        foreach ($rules as $rule => $parameter) {
            if ($rule === 'required' && $parameter) {
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "The {$field} field is required");
                    continue;
                }
            }
            
            // Skip other validations if field is empty and not required
            if (empty($value) && $value !== '0' && !($rules['required'] ?? false)) {
                continue;
            }
            
            switch ($rule) {
                case 'min_length':
                    if (strlen($value) < $parameter) {
                        $this->addError($field, "The {$field} must be at least {$parameter} characters");
                    }
                    break;
                    
                case 'max_length':
                    if (strlen($value) > $parameter) {
                        $this->addError($field, "The {$field} must not exceed {$parameter} characters");
                    }
                    break;
                    
                case 'email':
                    if ($parameter && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, "The {$field} must be a valid email address");
                    }
                    break;
                    
                case 'regex':
                    if (!preg_match($parameter, $value)) {
                        $this->addError($field, "The {$field} format is invalid");
                    }
                    break;
                    
                case 'in':
                    if (is_array($parameter) && !in_array($value, $parameter)) {
                        $this->addError($field, "The selected {$field} is invalid");
                    }
                    break;
                    
                case 'unique':
                    if (is_array($parameter)) {
                        $table = $parameter['table'];
                        $column = $parameter['column'];
                        $except = $parameter['except'] ?? null;
                        
                        if ($this->isUnique($table, $column, $value, $except)) {
                            $this->addError($field, "The {$field} has already been taken");
                        }
                    }
                    break;
                    
                case 'matches':
                    $matchField = $parameter;
                    $matchValue = $this->data[$matchField] ?? null;
                    if ($value !== $matchValue) {
                        $this->addError($field, "The {$field} must match {$matchField}");
                    }
                    break;
                    
                case 'numeric':
                    if ($parameter && !is_numeric($value)) {
                        $this->addError($field, "The {$field} must be a number");
                    }
                    break;
                    
                case 'integer':
                    if ($parameter && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $this->addError($field, "The {$field} must be an integer");
                    }
                    break;
                    
                case 'url':
                    if ($parameter && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->addError($field, "The {$field} must be a valid URL");
                    }
                    break;
                    
                case 'min':
                    if (is_numeric($value) && $value < $parameter) {
                        $this->addError($field, "The {$field} must be at least {$parameter}");
                    }
                    break;
                    
                case 'max':
                    if (is_numeric($value) && $value > $parameter) {
                        $this->addError($field, "The {$field} must not be greater than {$parameter}");
                    }
                    break;
                    
                case 'date':
                    if ($parameter && !strtotime($value)) {
                        $this->addError($field, "The {$field} must be a valid date");
                    }
                    break;
                    
                case 'boolean':
                    if ($parameter && !in_array($value, [true, false, 1, 0, '1', '0'], true)) {
                        $this->addError($field, "The {$field} must be true or false");
                    }
                    break;
            }
        }
    }
    
    /**
     * Check if value is unique in database
     */
    private function isUnique($table, $column, $value, $except = null)
    {
        try {
            $db = Database::getInstance();
            $query = $db->table($table)->where($column, $value);
            
            if ($except) {
                $query->where('id', '!=', $except);
            }
            
            $result = $query->first();
            return $result !== null;
            
        } catch (\Exception $e) {
            // If database check fails, assume not unique to be safe
            return false;
        }
    }
    
    /**
     * Add error for field
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     */
    public function getFieldErrors($field)
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if field has errors
     */
    public function hasErrors($field = null)
    {
        if ($field) {
            return isset($this->errors[$field]);
        }
        
        return !empty($this->errors);
    }
    
    /**
     * Get first error message
     */
    public function getFirstError($field = null)
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        
        return null;
    }
    
    /**
     * Static validation helper
     */
    public static function make($data, $rules)
    {
        $validator = new self();
        return $validator->validate($data, $rules);
    }
    
    /**
     * Common validation rules
     */
    public static function passwordRules()
    {
        return [
            'required' => true,
            'min_length' => 8,
            'regex' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
        ];
    }
    
    public static function emailRules()
    {
        return [
            'required' => true,
            'email' => true,
            'max_length' => 255
        ];
    }
    
    public static function usernameRules()
    {
        return [
            'required' => true,
            'min_length' => 3,
            'max_length' => 50,
            'regex' => '/^[a-zA-Z0-9_]+$/'
        ];
    }
}