<?php
// app/Core/Middleware/SecurityMiddleware.php

namespace App\Core\Middleware;

use App\Core\Security;

class SecurityMiddleware
{
    /**
     * Execute security middleware
     */
    public function handle($request, callable $next)
    {
        // Set security headers
        $this->setSecurityHeaders();
        
        // Check for suspicious activity
        $this->checkSuspiciousActivity();
        
        // Rate limiting
        $this->enforceRateLimit();
        
        // IP blocking
        $this->checkIPBlocking();
        
        return $next($request);
    }
    
    /**
     * Set comprehensive security headers
     */
    private function setSecurityHeaders()
    {
        if (headers_sent()) {
            return;
        }
        
        $headers = [
            // XSS Protection
            'X-XSS-Protection' => '1; mode=block',
            
            // Content Type Options
            'X-Content-Type-Options' => 'nosniff',
            
            // Frame Options
            'X-Frame-Options' => 'DENY',
            
            // Referrer Policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions Policy
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), fullscreen=(), payment=()',
            
            // Remove Server header
            'Server' => '',
            
            // Remove X-Powered-By header
            'X-Powered-By' => ''
        ];
        
        // HTTPS-only headers
        if ($this->isHttpsRequest()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }
        
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
        
        // Content Security Policy
        $csp = $this->generateCSP();
        header('Content-Security-Policy: ' . $csp);
    }
    
    /**
     * Generate Content Security Policy
     */
    private function generateCSP()
    {
        $nonce = base64_encode(random_bytes(16));
        
        // Store nonce for use in templates
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['csp_nonce'] = $nonce;
        
        $policies = [
            'default-src' => "'self'",
            'script-src' => "'self' 'nonce-{$nonce}' 'strict-dynamic'",
            'style-src' => "'self' 'unsafe-inline'",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' https:",
            'connect-src' => "'self'",
            'media-src' => "'self'",
            'object-src' => "'none'",
            'child-src' => "'self'",
            'frame-ancestors' => "'none'",
            'form-action' => "'self'",
            'base-uri' => "'self'",
            'manifest-src' => "'self'",
            'worker-src' => "'self'"
        ];
        
        $cspString = '';
        foreach ($policies as $directive => $value) {
            $cspString .= $directive . ' ' . $value . '; ';
        }
        
        return rtrim($cspString, '; ');
    }
    
    /**
     * Check for suspicious activity
     */
    private function checkSuspiciousActivity()
    {
        $ip = Security::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $suspiciousPatterns = [
            // SQL injection attempts
            '/union\\s+select/i',
            '/\\bor\\s+1\\s*=\\s*1/i',
            '/\\band\\s+1\\s*=\\s*1/i',
            '/\\/\\*.*\\*\\//i',
            '/\\bdrop\\s+table/i',
            '/\\binsert\\s+into/i',
            '/\\bdelete\\s+from/i',
            '/\\bupdate\\s+set/i',
            
            // XSS attempts
            '/<script/i',
            '/javascript:/i',
            '/on\\w+\\s*=/i',
            '/<iframe/i',
            
            // Path traversal
            '/\\.\\.\\//',
            '/\\.\\.\\\\\\\\/i',
            
            // Command injection
            '/;\\s*(cat|ls|pwd|whoami|id)/i',
            '/\\|\\s*(cat|ls|pwd|whoami|id)/i',
            '/`[^`]*`/i',
            
            // Common attack tools
            '/sqlmap/i',
            '/nikto/i',
            '/nmap/i',
            '/burp/i',
            '/dirbuster/i'
        ];
        
        $suspiciousData = $uri . ' ' . http_build_query($_GET) . ' ' . http_build_query($_POST) . ' ' . $userAgent;
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $suspiciousData)) {
                $this->logSuspiciousActivity($ip, $pattern, $suspiciousData);
                $this->blockSuspiciousRequest();
            }
        }
        
        // Check for unusual request patterns
        $this->checkRequestPatterns($ip);
    }
    
    /**
     * Check request patterns for anomalies
     */
    private function checkRequestPatterns($ip)
    {
        $cacheKey = 'request_pattern_' . md5($ip);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $requests = $_SESSION[$cacheKey] ?? [];
        $currentTime = time();
        
        // Clean old requests (older than 1 hour)
        $requests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 3600;
        });
        
        // Add current request
        $requests[] = $currentTime;
        $_SESSION[$cacheKey] = $requests;
        
        // Check for rapid requests (more than 60 in 1 minute)
        $recentRequests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60;
        });
        
        if (count($recentRequests) > 60) {
            $this->logSuspiciousActivity($ip, 'rapid_requests', 'Too many requests in short time');
            $this->blockSuspiciousRequest();
        }
    }
    
    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit()
    {
        $ip = Security::getClientIP();
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Different limits for different endpoints
        $limits = [
            '/auth/login' => ['max' => 5, 'window' => 900], // 5 attempts per 15 minutes
            '/auth/register' => ['max' => 3, 'window' => 3600], // 3 attempts per hour
            '/auth/forgot-password' => ['max' => 3, 'window' => 3600], // 3 attempts per hour
            'default' => ['max' => 100, 'window' => 3600] // 100 requests per hour
        ];
        
        $limit = $limits['default'];
        foreach ($limits as $pattern => $config) {
            if ($pattern !== 'default' && strpos($uri, $pattern) !== false) {
                $limit = $config;
                break;
            }
        }
        
        $identifier = $ip . '_' . $method . '_' . parse_url($uri, PHP_URL_PATH);
        
        if (!Security::checkRateLimit($identifier, $limit['max'], $limit['window'])) {
            $this->logSuspiciousActivity($ip, 'rate_limit_exceeded', "Rate limit exceeded for {$uri}");
            $this->sendRateLimitResponse();
        }
    }
    
    /**
     * Check IP blocking
     */
    private function checkIPBlocking()
    {
        $ip = Security::getClientIP();
        
        // Check against blocked IP list
        $blockedIPs = $this->getBlockedIPs();
        
        foreach ($blockedIPs as $blockedIP) {
            if (strpos($blockedIP, '/') !== false) {
                // CIDR range
                if (Security::ipInRange($ip, $blockedIP)) {
                    $this->blockIPAddress($ip, 'IP in blocked range');
                }
            } else {
                // Exact IP
                if ($ip === $blockedIP) {
                    $this->blockIPAddress($ip, 'IP in blocked list');
                }
            }
        }
        
        // Check temporary blocks
        $this->checkTemporaryBlocks($ip);
    }
    
    /**
     * Get list of blocked IPs
     */
    private function getBlockedIPs()
    {
        // This could be loaded from database, config file, or external service
        return [
            // Example blocked IPs/ranges - remove private ranges in production
            // '10.0.0.0/8',
            // '172.16.0.0/12',
            // '192.168.0.0/16',
            // Add specific IPs as needed
        ];
    }
    
    /**
     * Check temporary IP blocks
     */
    private function checkTemporaryBlocks($ip)
    {
        $cacheKey = 'temp_block_' . md5($ip);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $blockInfo = $_SESSION[$cacheKey] ?? null;
        
        if ($blockInfo && $blockInfo['expires'] > time()) {
            $this->blockIPAddress($ip, 'Temporarily blocked');
        }
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity($ip, $pattern, $data)
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'pattern' => $pattern,
            'data' => substr($data, 0, 1000), // Limit log size
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        $logDir = defined('STORAGE_PATH') ? STORAGE_PATH . '/logs' : __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log('SECURITY: ' . json_encode($logEntry), 3, $logDir . '/security.log');
    }
    
    /**
     * Block suspicious request
     */
    private function blockSuspiciousRequest()
    {
        http_response_code(403);
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Access denied',
                'message' => 'Suspicious activity detected',
                'code' => 403
            ]);
        } else {
            echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1><p>Suspicious activity detected.</p></body></html>';
        }
        
        exit;
    }
    
    /**
     * Block IP address
     */
    private function blockIPAddress($ip, $reason)
    {
        $this->logSuspiciousActivity($ip, 'ip_blocked', $reason);
        
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1><p>Your IP address has been blocked.</p></body></html>';
        exit;
    }
    
    /**
     * Send rate limit response
     */
    private function sendRateLimitResponse()
    {
        http_response_code(429);
        header('Retry-After: 3600');
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'code' => 429,
                'retry_after' => 3600
            ]);
        } else {
            echo '<!DOCTYPE html><html><head><title>Rate Limit Exceeded</title></head><body><h1>Rate Limit Exceeded</h1><p>Too many requests. Please try again later.</p></body></html>';
        }
        
        exit;
    }
    
    /**
     * Check if request is HTTPS
     */
    private function isHttpsRequest()
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
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