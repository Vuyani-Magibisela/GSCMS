<?php

namespace App\Middleware;

use App\Core\CompetitionSecurity;
use App\Core\Request;
use App\Core\Response;

/**
 * Competition Setup Middleware
 * Provides security and validation for competition setup interface
 */
class CompetitionSetupMiddleware
{
    private $security;
    
    public function __construct()
    {
        $this->security = CompetitionSecurity::getInstance();
    }
    
    /**
     * Handle the middleware
     */
    public function handle(Request $request, $next)
    {
        // Check if this is a competition setup related request
        if (!$this->isCompetitionSetupRequest($request)) {
            return $next($request);
        }
        
        // Rate limiting
        if (!$this->security->checkRateLimit()) {
            return $this->createErrorResponse('Rate limit exceeded. Please try again later.', 429);
        }
        
        // Admin access validation
        if (!$this->security->validateAdminAccess()) {
            $this->security->logSecurityEvent('competition_setup_access_denied', [
                'path' => $request->getPath(),
                'method' => $request->getMethod()
            ]);
            
            return $this->createErrorResponse('Access denied. Admin privileges required.', 403);
        }
        
        // CSRF protection for POST requests
        if ($request->getMethod() === 'POST') {
            $token = $request->input('csrf_token') ?: $request->header('X-CSRF-Token');
            
            if (!$this->security->validateCSRFToken($token)) {
                $this->security->logSecurityEvent('csrf_token_validation_failed', [
                    'path' => $request->getPath(),
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                
                return $this->createErrorResponse('Invalid security token. Please refresh and try again.', 403);
            }
        }
        
        // Input sanitization for form submissions
        if ($request->getMethod() === 'POST' && $this->isFormSubmission($request)) {
            $this->sanitizeRequestData($request);
        }
        
        // File upload validation
        if ($request->hasFiles()) {
            $fileValidation = $this->validateFileUploads($request);
            if (!$fileValidation['valid']) {
                return $this->createErrorResponse(
                    'File upload validation failed: ' . implode(', ', $fileValidation['errors']), 
                    400
                );
            }
        }
        
        // Log successful access
        $this->security->logSecurityEvent('competition_setup_access_granted', [
            'path' => $request->getPath(),
            'method' => $request->getMethod(),
            'user_id' => $_SESSION['user_id'] ?? 'unknown'
        ]);
        
        // Add security headers to response
        $response = $next($request);
        $this->addSecurityHeaders($response);
        
        return $response;
    }
    
    /**
     * Check if request is for competition setup interface
     */
    private function isCompetitionSetupRequest(Request $request)
    {
        $path = $request->getPath();
        
        $competitionSetupPaths = [
            '/admin/competition-setup',
            '/admin/phase-scheduler',
            '/admin/category-manager'
        ];
        
        foreach ($competitionSetupPaths as $setupPath) {
            if (strpos($path, $setupPath) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if request is a form submission
     */
    private function isFormSubmission(Request $request)
    {
        $contentType = $request->header('Content-Type');
        return strpos($contentType, 'application/json') !== false || 
               strpos($contentType, 'application/x-www-form-urlencoded') !== false ||
               strpos($contentType, 'multipart/form-data') !== false;
    }
    
    /**
     * Sanitize request data
     */
    private function sanitizeRequestData(Request $request)
    {
        // Get all input data
        $data = $request->all();
        
        // Sanitize the data
        $sanitizedData = $this->security->sanitizeInput($data);
        
        // Replace the request data (this would need to be implemented in the Request class)
        // For now, we'll just log if dangerous content was found
        if ($data !== $sanitizedData) {
            $this->security->logSecurityEvent('input_sanitized', [
                'user_id' => $_SESSION['user_id'] ?? 'unknown',
                'path' => $request->getPath()
            ]);
        }
    }
    
    /**
     * Validate file uploads
     */
    private function validateFileUploads(Request $request)
    {
        $files = $_FILES; // Direct access since Request class might not have file handling
        $errors = [];
        
        foreach ($files as $fieldName => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $validation = $this->security->validateFileUpload($file);
                if (!$validation['valid']) {
                    $errors = array_merge($errors, $validation['errors']);
                }
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Add security headers to response
     */
    private function addSecurityHeaders(Response $response)
    {
        // Content Security Policy
        $response->header('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' https://cdnjs.cloudflare.com; " .
            "connect-src 'self';"
        );
        
        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');
        
        // Prevent clickjacking
        $response->header('X-Frame-Options', 'DENY');
        
        // XSS protection
        $response->header('X-XSS-Protection', '1; mode=block');
        
        // Strict transport security (if HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // Referrer policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Feature policy
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        return $response;
    }
    
    /**
     * Create error response
     */
    private function createErrorResponse($message, $statusCode = 400)
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
        
        // Determine response format
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        if (strpos($acceptHeader, 'application/json') !== false) {
            // JSON response for AJAX requests
            $response->header('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $message,
                'error_code' => $statusCode
            ]));
        } else {
            // HTML response for regular requests
            $response->header('Content-Type', 'text/html');
            $response->setContent($this->createErrorPage($message, $statusCode));
        }
        
        return $response;
    }
    
    /**
     * Create error page HTML
     */
    private function createErrorPage($message, $statusCode)
    {
        $title = $this->getStatusCodeTitle($statusCode);
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - GSCMS</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 40px; 
            background-color: #f5f5f5; 
            color: #333;
        }
        .error-container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-code { 
            font-size: 72px; 
            font-weight: bold; 
            color: #dc3545; 
            margin: 0;
        }
        .error-title { 
            font-size: 24px; 
            margin: 20px 0 10px; 
            color: #333;
        }
        .error-message { 
            font-size: 16px; 
            color: #666; 
            margin-bottom: 30px;
        }
        .back-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">' . $statusCode . '</div>
        <div class="error-title">' . htmlspecialchars($title) . '</div>
        <div class="error-message">' . htmlspecialchars($message) . '</div>
        <a href="/admin/dashboard" class="back-button">Return to Dashboard</a>
    </div>
</body>
</html>';
    }
    
    /**
     * Get status code title
     */
    private function getStatusCodeTitle($statusCode)
    {
        $titles = [
            400 => 'Bad Request',
            403 => 'Access Forbidden',
            404 => 'Not Found',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error'
        ];
        
        return $titles[$statusCode] ?? 'Error';
    }
}