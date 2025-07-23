<?php
// app/Core/Auth.php

namespace App\Core;

use App\Models\User;
use Exception;

class Auth
{
    private static $instance = null;
    private $session;
    /** @var User|null */
    private $user = null;
    private $loginAttempts = [];
    
    private function __construct()
    {
        $this->session = Session::getInstance();
        $this->session->start();
        $this->loadUser();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load authenticated user from session
     */
    private function loadUser()
    {
        $userId = $this->session->get('user_id');
        
        if ($userId) {
            $userModel = new User();
            $this->user = $userModel->find($userId);
            
            // Verify user still exists and is active
            if (!$this->user || !$this->user->canLogin()) {
                $this->logout();
            }
        }
    }
    
    /**
     * Attempt to log in user
     */
    public function attempt($credentials, $remember = false)
    {
        $identifier = $credentials['email'] ?? $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        
        if (!$identifier || !$password) {
            throw new Exception('Email/username and password are required');
        }
        
        // Check rate limiting
        if ($this->isRateLimited($identifier)) {
            throw new Exception('Too many login attempts. Please try again later.');
        }
        
        // Find user by email or username
        $user = User::findByEmail($identifier) ?? User::findByUsername($identifier);
        
        if (!$user) {
            $this->recordFailedAttempt($identifier);
            throw new Exception('Invalid credentials');
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            $this->recordFailedAttempt($identifier);
            throw new Exception('Invalid credentials');
        }
        
        // Check if user can login
        if (!$user->canLogin()) {
            throw new Exception('Account is not active or email not verified');
        }
        
        // Login successful
        $this->login($user, $remember);
        $this->clearFailedAttempts($identifier);
        
        return true;
    }
    
    /**
     * Log in user
     */
    public function login(User $user, $remember = false)
    {
        // Regenerate session ID for security
        $this->session->regenerateId();
        
        // Store user information in session
        $this->session->set('user_id', $user->id);
        $this->session->set('user_role', $user->role);
        $this->session->set('user_email', $user->email);
        $this->session->set('login_time', time());
        
        // Update user's last login
        $user->updateLastLogin();
        $user->save();
        
        // Handle remember me functionality
        if ($remember) {
            $this->setRememberToken($user);
        }
        
        $this->user = $user;
        
        return true;
    }
    
    /**
     * Log out user
     */
    public function logout()
    {
        if ($this->user) {
            // Clear remember token
            $this->clearRememberToken($this->user);
        }
        
        $this->user = null;
        $this->session->destroy();
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    public function check()
    {
        return $this->user !== null;
    }
    
    /**
     * Get authenticated user
     */
    public function user()
    {
        return $this->user;
    }
    
    /**
     * Get user ID
     */
    public function id()
    {
        return $this->user ? $this->user->id : null;
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        return $this->user ? $this->user->hasRole($role) : false;
    }
    
    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        return $this->user ? $this->user->hasAnyRole($roles) : false;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->user ? $this->user->isAdmin() : false;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth()
    {
        if (!$this->check()) {
            // Store intended URL
            $this->session->setIntendedUrl($_SERVER['REQUEST_URI'] ?? '/');
            throw new Exception('Authentication required', 401);
        }
        
        return true;
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role)
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            throw new Exception("Access denied. Required role: {$role}", 403);
        }
        
        return true;
    }
    
    /**
     * Require any of the given roles
     */
    public function requireAnyRole($roles)
    {
        $this->requireAuth();
        
        if (!$this->hasAnyRole($roles)) {
            $roleNames = is_array($roles) ? implode(', ', $roles) : $roles;
            throw new Exception("Access denied. Required roles: {$roleNames}", 403);
        }
        
        return true;
    }
    
    /**
     * Set remember token
     */
    private function setRememberToken(User $user)
    {
        $token = $user->generateRememberToken();
        $user->save();
        
        // Set cookie for 30 days
        $expiry = time() + (30 * 24 * 60 * 60);
        $secure = isset($_SERVER['HTTPS']);
        
        setcookie(
            'remember_token',
            $token,
            $expiry,
            '/',
            '',
            $secure,
            true // HTTP only
        );
    }
    
    /**
     * Clear remember token
     */
    private function clearRememberToken(User $user)
    {
        $user->remember_token = null;
        $user->save();
        
        // Clear cookie
        setcookie(
            'remember_token',
            '',
            time() - 3600,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }
    
    /**
     * Attempt login via remember token
     */
    public function attemptRememberLogin()
    {
        $token = $_COOKIE['remember_token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $user = User::findByRememberToken($token);
        
        if (!$user || !$user->canLogin()) {
            $this->clearRememberTokenCookie();
            return false;
        }
        
        $this->login($user, true);
        return true;
    }
    
    /**
     * Clear remember token cookie
     */
    private function clearRememberTokenCookie()
    {
        setcookie(
            'remember_token',
            '',
            time() - 3600,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($identifier)
    {
        $ip = $this->getClientIp();
        $key = $identifier . ':' . $ip;
        
        if (!isset($this->loginAttempts[$key])) {
            $this->loginAttempts[$key] = [];
        }
        
        $this->loginAttempts[$key][] = time();
        
        // Keep only attempts from last hour
        $this->loginAttempts[$key] = array_filter(
            $this->loginAttempts[$key],
            function($timestamp) {
                return (time() - $timestamp) < 3600;
            }
        );
        
        // Store in session for persistence
        $this->session->set('login_attempts', $this->loginAttempts);
    }
    
    /**
     * Clear failed attempts for identifier
     */
    private function clearFailedAttempts($identifier)
    {
        $ip = $this->getClientIp();
        $key = $identifier . ':' . $ip;
        
        unset($this->loginAttempts[$key]);
        $this->session->set('login_attempts', $this->loginAttempts);
    }
    
    /**
     * Check if login attempts are rate limited
     */
    private function isRateLimited($identifier)
    {
        // Load attempts from session
        $this->loginAttempts = $this->session->get('login_attempts', []);
        
        $ip = $this->getClientIp();
        $key = $identifier . ':' . $ip;
        
        if (!isset($this->loginAttempts[$key])) {
            return false;
        }
        
        $attempts = $this->loginAttempts[$key];
        
        // Remove old attempts (older than 1 hour)
        $attempts = array_filter($attempts, function($timestamp) {
            return (time() - $timestamp) < 3600;
        });
        
        // Rate limit: 5 attempts per hour
        return count($attempts) >= 5;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($email)
    {
        $user = User::findByEmail($email);
        
        if (!$user) {
            throw new Exception('User not found with this email address');
        }
        
        $token = $user->generateResetToken();
        $user->save();
        
        return $token;
    }
    
    /**
     * Validate password reset token
     */
    public function validatePasswordResetToken($token)
    {
        return User::findByResetToken($token);
    }
    
    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword)
    {
        $user = $this->validatePasswordResetToken($token);
        
        if (!$user) {
            throw new Exception('Invalid or expired reset token');
        }
        
        // Validate password strength
        if (!$this->isStrongPassword($newPassword)) {
            throw new Exception('Password must be at least 8 characters and contain uppercase, lowercase, number, and special character');
        }
        
        $user->setPassword($newPassword);
        $user->clearResetToken();
        $user->save();
        
        return true;
    }
    
    /**
     * Check if password meets strength requirements
     */
    private function isStrongPassword($password)
    {
        return strlen($password) >= 8 &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/\d/', $password) &&
               preg_match('/[@$!%*?&]/', $password);
    }
    
    /**
     * Change user password
     */
    public function changePassword($currentPassword, $newPassword)
    {
        $this->requireAuth();
        
        if (!$this->user->verifyPassword($currentPassword)) {
            throw new Exception('Current password is incorrect');
        }
        
        if (!$this->isStrongPassword($newPassword)) {
            throw new Exception('Password must be at least 8 characters and contain uppercase, lowercase, number, and special character');
        }
        
        $this->user->setPassword($newPassword);
        $this->user->save();
        
        return true;
    }
}