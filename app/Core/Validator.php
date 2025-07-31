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
            'errors' => $this->errors,
            'validated_data' => $this->getValidatedData()
        ];
    }
    
    /**
     * Get validated and sanitized data
     */
    private function getValidatedData()
    {
        $validated = [];
        foreach ($this->data as $key => $value) {
            if (!$this->hasErrors($key)) {
                $validated[$key] = $value;
            }
        }
        return $validated;
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
                    
                case 'alpha':
                    if ($parameter && !preg_match('/^[a-zA-Z]+$/', $value)) {
                        $this->addError($field, "The {$field} may only contain letters");
                    }
                    break;
                    
                case 'alpha_num':
                    if ($parameter && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                        $this->addError($field, "The {$field} may only contain letters and numbers");
                    }
                    break;
                    
                case 'alpha_dash':
                    if ($parameter && !preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                        $this->addError($field, "The {$field} may only contain letters, numbers, dashes and underscores");
                    }
                    break;
                    
                case 'between':
                    if (is_array($parameter) && count($parameter) === 2) {
                        [$min, $max] = $parameter;
                        if (is_numeric($value)) {
                            if ($value < $min || $value > $max) {
                                $this->addError($field, "The {$field} must be between {$min} and {$max}");
                            }
                        } else {
                            $length = strlen($value);
                            if ($length < $min || $length > $max) {
                                $this->addError($field, "The {$field} must be between {$min} and {$max} characters");
                            }
                        }
                    }
                    break;
                    
                case 'length':
                    if (is_numeric($parameter) && strlen($value) !== (int)$parameter) {
                        $this->addError($field, "The {$field} must be exactly {$parameter} characters");
                    }
                    break;
                    
                case 'exists':
                    if (is_array($parameter)) {
                        $table = $parameter['table'];
                        $column = $parameter['column'];
                        
                        if (!$this->recordExists($table, $column, $value)) {
                            $this->addError($field, "The selected {$field} is invalid");
                        }
                    }
                    break;
                    
                case 'confirmed':
                    $confirmField = $field . '_confirmation';
                    $confirmValue = $this->data[$confirmField] ?? null;
                    if ($value !== $confirmValue) {
                        $this->addError($field, "The {$field} confirmation does not match");
                    }
                    break;
                    
                case 'date_format':
                    if ($parameter && !$this->validateDateFormat($value, $parameter)) {
                        $this->addError($field, "The {$field} does not match the format {$parameter}");
                    }
                    break;
                    
                case 'before':
                    if ($parameter && strtotime($value) >= strtotime($parameter)) {
                        $this->addError($field, "The {$field} must be a date before {$parameter}");
                    }
                    break;
                    
                case 'after':
                    if ($parameter && strtotime($value) <= strtotime($parameter)) {
                        $this->addError($field, "The {$field} must be a date after {$parameter}");
                    }
                    break;
                    
                case 'file':
                    if ($parameter && !$this->validateFile($field)) {
                        $this->addError($field, "The {$field} must be a file");
                    }
                    break;
                    
                case 'image':
                    if ($parameter && !$this->validateImage($field)) {
                        $this->addError($field, "The {$field} must be an image");
                    }
                    break;
                    
                case 'mimes':
                    if (is_array($parameter) && !$this->validateMimes($field, $parameter)) {
                        $allowedTypes = implode(', ', $parameter);
                        $this->addError($field, "The {$field} must be a file of type: {$allowedTypes}");
                    }
                    break;
                    
                case 'max_file_size':
                    if (is_numeric($parameter) && !$this->validateFileSize($field, $parameter)) {
                        $maxSize = $parameter / 1024; // Convert to KB
                        $this->addError($field, "The {$field} may not be greater than {$maxSize}KB");
                    }
                    break;
                    
                case 'array':
                    if ($parameter && !is_array($value)) {
                        $this->addError($field, "The {$field} must be an array");
                    }
                    break;
                    
                case 'json':
                    if ($parameter && !$this->validateJson($value)) {
                        $this->addError($field, "The {$field} must be a valid JSON string");
                    }
                    break;
                    
                case 'ip':
                    if ($parameter && !filter_var($value, FILTER_VALIDATE_IP)) {
                        $this->addError($field, "The {$field} must be a valid IP address");
                    }
                    break;
                    
                case 'phone':
                    if ($parameter && !$this->validatePhone($value)) {
                        $this->addError($field, "The {$field} must be a valid phone number");
                    }
                    break;
                    
                case 'password_strength':
                    if ($parameter && !$this->validatePasswordStrength($value)) {
                        $this->addError($field, "The {$field} must contain at least 8 characters with uppercase, lowercase, numbers and special characters");
                    }
                    break;
                    
                case 'not_in':
                    if (is_array($parameter) && in_array($value, $parameter)) {
                        $this->addError($field, "The selected {$field} is invalid");
                    }
                    break;
                    
                case 'different':
                    $otherField = $parameter;
                    $otherValue = $this->data[$otherField] ?? null;
                    if ($value === $otherValue) {
                        $this->addError($field, "The {$field} and {$otherField} must be different");
                    }
                    break;
                    
                case 'starts_with':
                    if (is_array($parameter)) {
                        $valid = false;
                        foreach ($parameter as $prefix) {
                            if (strpos($value, $prefix) === 0) {
                                $valid = true;
                                break;
                            }
                        }
                        if (!$valid) {
                            $prefixes = implode(', ', $parameter);
                            $this->addError($field, "The {$field} must start with one of the following: {$prefixes}");
                        }
                    }
                    break;
                    
                case 'ends_with':
                    if (is_array($parameter)) {
                        $valid = false;
                        foreach ($parameter as $suffix) {
                            if (substr($value, -strlen($suffix)) === $suffix) {
                                $valid = true;
                                break;
                            }
                        }
                        if (!$valid) {
                            $suffixes = implode(', ', $parameter);
                            $this->addError($field, "The {$field} must end with one of the following: {$suffixes}");
                        }
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
     * Check if record exists in database
     */
    private function recordExists($table, $column, $value)
    {
        try {
            $db = Database::getInstance();
            $result = $db->table($table)->where($column, $value)->first();
            return $result !== null;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate date format
     */
    private function validateDateFormat($value, $format)
    {
        $date = \DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }
    
    /**
     * Validate file upload
     */
    private function validateFile($field)
    {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * Validate image file
     */
    private function validateImage($field)
    {
        if (!$this->validateFile($field)) {
            return false;
        }
        
        $imageInfo = getimagesize($_FILES[$field]['tmp_name']);
        return $imageInfo !== false;
    }
    
    /**
     * Validate file MIME types
     */
    private function validateMimes($field, $allowedMimes)
    {
        if (!$this->validateFile($field)) {
            return false;
        }
        
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES[$field]['tmp_name']);
        finfo_close($fileInfo);
        
        return in_array($mimeType, $allowedMimes);
    }
    
    /**
     * Validate file size
     */
    private function validateFileSize($field, $maxSize)
    {
        if (!$this->validateFile($field)) {
            return false;
        }
        
        return $_FILES[$field]['size'] <= $maxSize;
    }
    
    /**
     * Validate JSON string
     */
    private function validateJson($value)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Validate phone number
     */
    private function validatePhone($value)
    {
        // Basic phone validation - can be enhanced for specific formats
        return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[\s\-\(\)]/', '', $value));
    }
    
    /**
     * Validate password strength
     */
    private function validatePasswordStrength($value)
    {
        return strlen($value) >= 8 &&
               preg_match('/[a-z]/', $value) &&
               preg_match('/[A-Z]/', $value) &&
               preg_match('/\d/', $value) &&
               preg_match('/[^\w\s]/', $value);
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