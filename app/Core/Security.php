<?php
// app/Core/Security.php

namespace App\Core;

class Security
{
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate secure random password
     */
    public static function generatePassword($length = 12, $includeSymbols = true)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($includeSymbols) {
            $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }
        
        $password = '';
        $charLength = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charLength - 1)];
        }
        
        return $password;
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Encrypt data using AES-256-GCM
     */
    public static function encrypt($data, $key = null)
    {
        $key = $key ?: self::getEncryptionKey();
        $cipher = 'aes-256-gcm';
        $iv = random_bytes(16);
        $tag = '';
        
        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt data using AES-256-GCM
     */
    public static function decrypt($encryptedData, $key = null)
    {
        $key = $key ?: self::getEncryptionKey();
        $cipher = 'aes-256-gcm';
        
        $data = base64_decode($encryptedData);
        if ($data === false || strlen($data) < 32) {
            throw new \Exception('Invalid encrypted data');
        }
        
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * Get encryption key from environment or generate one
     */
    private static function getEncryptionKey()
    {
        $key = $_ENV['ENCRYPTION_KEY'] ?? null;
        
        if (!$key) {
            // In production, this should be properly configured
            throw new \Exception('Encryption key not configured');
        }
        
        return base64_decode($key);
    }
    
    /**
     * Generate secure session ID
     */
    public static function generateSessionId()
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Constant time string comparison to prevent timing attacks
     */
    public static function hashEquals($known, $user)
    {
        return hash_equals($known, $user);
    }
    
    /**
     * Generate HMAC signature
     */
    public static function generateHmac($data, $key, $algo = 'sha256')
    {
        return hash_hmac($algo, $data, $key, true);
    }
    
    /**
     * Verify HMAC signature
     */
    public static function verifyHmac($data, $signature, $key, $algo = 'sha256')
    {
        $expected = self::generateHmac($data, $key, $algo);
        return hash_equals($expected, $signature);
    }
    
    /**
     * Generate secure UUID v4
     */
    public static function generateUuid()
    {
        $bytes = random_bytes(16);
        
        // Set version (4) and variant bits
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($identifier, $maxAttempts, $timeWindow = 3600)
    {
        $cacheKey = 'rate_limit_' . md5($identifier);
        $attempts = Session::getInstance()->get($cacheKey, []);
        $currentTime = time();
        
        // Clean old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        // Record this attempt
        $attempts[] = $currentTime;
        Session::getInstance()->set($cacheKey, $attempts);
        
        return true;
    }
    
    /**
     * Get remaining rate limit attempts
     */
    public static function getRateLimitRemaining($identifier, $maxAttempts, $timeWindow = 3600)
    {
        $cacheKey = 'rate_limit_' . md5($identifier);
        $attempts = Session::getInstance()->get($cacheKey, []);
        $currentTime = time();
        
        // Clean old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        return max(0, $maxAttempts - count($attempts));
    }
    
    /**
     * Clear rate limit for identifier
     */
    public static function clearRateLimit($identifier)
    {
        $cacheKey = 'rate_limit_' . md5($identifier);
        Session::getInstance()->remove($cacheKey);
    }
    
    /**
     * Check if IP address is in CIDR range
     */
    public static function ipInRange($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::ipv4InRange($ip, $subnet, $mask);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::ipv6InRange($ip, $subnet, $mask);
        }
        
        return false;
    }
    
    /**
     * Check if IPv4 address is in range
     */
    private static function ipv4InRange($ip, $subnet, $mask)
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    /**
     * Check if IPv6 address is in range
     */
    private static function ipv6InRange($ip, $subnet, $mask)
    {
        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);
        $maskBytes = (int)($mask / 8);
        $maskBits = $mask % 8;
        
        // Compare full bytes
        if (substr($ipBinary, 0, $maskBytes) !== substr($subnetBinary, 0, $maskBytes)) {
            return false;
        }
        
        // Compare remaining bits
        if ($maskBits > 0) {
            $byte = ord($ipBinary[$maskBytes]) >> (8 - $maskBits);
            $subnetByte = ord($subnetBinary[$maskBytes]) >> (8 - $maskBits);
            
            if ($byte !== $subnetByte) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get client IP address (considering proxies)
     */
    public static function getClientIP()
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Generate Content Security Policy header
     */
    public static function generateCSPHeader($options = [])
    {
        $defaults = [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline'",
            'style-src' => "'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' https://cdnjs.cloudflare.com",
            'connect-src' => "'self'",
            'media-src' => "'self'",
            'object-src' => "'none'",
            'child-src' => "'self'",
            'frame-ancestors' => "'none'",
            'form-action' => "'self'",
            'base-uri' => "'self'",
            'manifest-src' => "'self'"
        ];
        
        $csp = array_merge($defaults, $options);
        $cspString = '';
        
        foreach ($csp as $directive => $value) {
            $cspString .= $directive . ' ' . $value . '; ';
        }
        
        return rtrim($cspString, '; ');
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders($options = [])
    {
        $headers = array_merge([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
        ], $options);
        
        foreach ($headers as $name => $value) {
            if (!headers_sent()) {
                header($name . ': ' . $value);
            }
        }
        
        // Set CSP header
        if (!headers_sent()) {
            header('Content-Security-Policy: ' . self::generateCSPHeader());
        }
    }
    
    /**
     * Validate file upload security
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 2097152)
    {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded or invalid upload';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check MIME type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        // Check for malicious content
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (preg_match('/<\?php|<script|javascript:/i', $content)) {
            $errors[] = 'File contains malicious content';
        }
        
        return $errors;
    }
    
    /**
     * Secure file upload handling
     */
    public static function handleFileUpload($file, $uploadDir, $allowedTypes = [], $maxSize = 2097152)
    {
        $errors = self::validateFileUpload($file, $allowedTypes, $maxSize);
        
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
        
        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = self::generateToken(16) . '.' . strtolower($extension);
        $filepath = rtrim($uploadDir, '/') . '/' . $filename;
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \Exception('Failed to move uploaded file');
        }
        
        // Set secure permissions
        chmod($filepath, 0644);
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'mime_type' => $file['type']
        ];
    }
}