<?php
// app/Core/Sanitizer.php

namespace App\Core;

class Sanitizer
{
    /**
     * Sanitization levels
     */
    const LEVEL_BASIC = 1;
    const LEVEL_STRIP_TAGS = 2;
    const LEVEL_PURIFY = 3;
    const LEVEL_DATABASE = 4;
    const LEVEL_FILE = 5;
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data, $level = self::LEVEL_BASIC, $options = [])
    {
        if (is_array($data)) {
            return self::sanitizeArray($data, $level, $options);
        }
        
        return self::sanitizeValue($data, $level, $options);
    }
    
    /**
     * Sanitize array of data
     */
    private static function sanitizeArray($data, $level, $options)
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = self::sanitizeKey($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = self::sanitizeArray($value, $level, $options);
            } else {
                $sanitized[$sanitizedKey] = self::sanitizeValue($value, $level, $options);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize single value
     */
    private static function sanitizeValue($value, $level, $options)
    {
        if ($value === null) {
            return null;
        }
        
        switch ($level) {
            case self::LEVEL_BASIC:
                return self::basicSanitize($value, $options);
                
            case self::LEVEL_STRIP_TAGS:
                return self::stripTagsSanitize($value, $options);
                
            case self::LEVEL_PURIFY:
                return self::purifySanitize($value, $options);
                
            case self::LEVEL_DATABASE:
                return self::databaseSanitize($value, $options);
                
            case self::LEVEL_FILE:
                return self::fileSanitize($value, $options);
                
            default:
                return self::basicSanitize($value, $options);
        }
    }
    
    /**
     * Basic HTML encoding sanitization
     */
    private static function basicSanitize($value, $options)
    {
        $value = trim($value);
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        return $value;
    }
    
    /**
     * Strip dangerous tags but allow safe ones
     */
    private static function stripTagsSanitize($value, $options)
    {
        $allowedTags = $options['allowed_tags'] ?? '<p><br><strong><em><u><a><ul><ol><li>';
        
        $value = trim($value);
        $value = strip_tags($value, $allowedTags);
        
        // Remove dangerous attributes
        $value = preg_replace('/(<[^>]+)\s*on\w+\s*=\s*["\'][^"\']*["\']([^>]*>)/i', '$1$2', $value);
        $value = preg_replace('/(<[^>]+)\s*javascript\s*:\s*[^>]*>/i', '$1>', $value);
        $value = preg_replace('/(<[^>]+)\s*data\s*:\s*[^>]*>/i', '$1>', $value);
        
        return $value;
    }
    
    /**
     * Full HTML purification
     */
    private static function purifySanitize($value, $options)
    {
        // Remove all script tags and their content
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        // Remove all style tags and their content
        $value = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $value);
        
        // Remove all event handlers
        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $value);
        
        // Remove javascript: and data: protocols
        $value = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/', '', $value);
        $value = preg_replace('/src\s*=\s*["\']data:[^"\']*["\']/', '', $value);
        
        // Remove form tags
        $value = preg_replace('/<\/?form[^>]*>/i', '', $value);
        
        // Remove iframe, embed, object tags
        $value = preg_replace('/<(iframe|embed|object|applet)[^>]*>.*?<\/\1>/is', '', $value);
        
        return trim($value);
    }
    
    /**
     * Database-safe sanitization
     */
    private static function databaseSanitize($value, $options)
    {
        $value = trim($value);
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Normalize line endings
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        
        // Remove control characters except tabs and newlines
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        return $value;
    }
    
    /**
     * File name sanitization
     */
    private static function fileSanitize($value, $options)
    {
        $value = trim($value);
        
        // Remove directory traversal attempts
        $value = str_replace(['../', '..\\', '../', '..\\'], '', $value);
        
        // Remove dangerous characters
        $value = preg_replace('/[^a-zA-Z0-9._-]/', '', $value);
        
        // Limit length
        $maxLength = $options['max_length'] ?? 255;
        if (strlen($value) > $maxLength) {
            $extension = pathinfo($value, PATHINFO_EXTENSION);
            $name = pathinfo($value, PATHINFO_FILENAME);
            $value = substr($name, 0, $maxLength - strlen($extension) - 1) . '.' . $extension;
        }
        
        return $value;
    }
    
    /**
     * Sanitize array key
     */
    private static function sanitizeKey($key)
    {
        // Only allow alphanumeric, underscore, and dash in keys
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    }
    
    /**
     * Sanitize email address
     */
    public static function sanitizeEmail($email)
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return strtolower($email);
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitizeUrl($url)
    {
        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Only allow http and https protocols
        if (!preg_match('/^https?:\/\//i', $url)) {
            return '';
        }
        
        return $url;
    }
    
    /**
     * Sanitize phone number
     */
    public static function sanitizePhone($phone)
    {
        // Remove all non-numeric characters except + at the beginning
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure + is only at the beginning
        if (strpos($phone, '+') !== false) {
            $phone = '+' . preg_replace('/[^0-9]/', '', $phone);
        }
        
        return $phone;
    }
    
    /**
     * Sanitize integer value
     */
    public static function sanitizeInt($value, $min = null, $max = null)
    {
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        $value = (int) $value;
        
        if ($min !== null && $value < $min) {
            $value = $min;
        }
        
        if ($max !== null && $value > $max) {
            $value = $max;
        }
        
        return $value;
    }
    
    /**
     * Sanitize float value
     */
    public static function sanitizeFloat($value, $min = null, $max = null)
    {
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value = (float) $value;
        
        if ($min !== null && $value < $min) {
            $value = $min;
        }
        
        if ($max !== null && $value > $max) {
            $value = $max;
        }
        
        return $value;
    }
    
    /**
     * Sanitize boolean value
     */
    public static function sanitizeBool($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
    }
    
    /**
     * Remove XSS attempts from string
     */
    public static function removeXSS($data)
    {
        // Remove any script tags
        $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
        
        // Remove any event handlers
        $data = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $data);
        
        // Remove javascript: and data: protocols
        $data = preg_replace('/(href|src)\s*=\s*["\']javascript:[^"\']*["\']/', '', $data);
        $data = preg_replace('/(href|src)\s*=\s*["\']data:[^"\']*["\']/', '', $data);
        
        // Remove any <iframe>, <embed>, or <object> tags
        $data = preg_replace('/<(iframe|embed|object|applet)[^>]*>.*?<\/\1>/is', '', $data);
        
        return $data;
    }
    
    /**
     * Clean file upload name
     */
    public static function cleanFileName($filename)
    {
        // Get file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Sanitize name
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        $name = trim($name, '.-_');
        
        // Ensure we have a name
        if (empty($name)) {
            $name = 'file_' . uniqid();
        }
        
        // Rebuild filename
        return $extension ? $name . '.' . strtolower($extension) : $name;
    }
    
    /**
     * Validate and sanitize array of IDs
     */
    public static function sanitizeIds($ids)
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        
        $sanitized = [];
        foreach ($ids as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $sanitized[] = $id;
            }
        }
        
        return array_unique($sanitized);
    }
}