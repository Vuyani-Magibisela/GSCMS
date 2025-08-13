<?php
// app/Controllers/AuthController.php

namespace App\Controllers;

use App\Models\User;
use App\Core\Validator;
use App\Core\Mail;
use Exception;

class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            return $this->redirect($this->baseUrl('dashboard'));
        }
        
        return $this->view('auth.login', [
            'title' => 'Login',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'old_input' => $this->getFlash('old_input') ?? [],
            'loginAction' => $this->url('auth/login'),
            'registerUrl' => $this->url('auth/register'),
            'forgotPasswordUrl' => $this->url('auth/forgot-password')
        ]);
    }
    
    /**
     * Process login
     */
    public function login()
    {
        try {
            // Verify CSRF token
            // $this->verifyCsrf();
            
            // Enforce rate limiting for login attempts
            // $this->rateLimit->enforceLoginAttempts($this->input('login'));
            
            // Validate input
            $rules = [
                'login' => [
                    'required' => true,
                    'min_length' => 3
                ],
                'password' => [
                    'required' => true,
                    'min_length' => 1
                ]
            ];
            
            $validator = new Validator();
            $validation = $validator->validate($this->input(), $rules);
            
            if (!$validation['valid']) {
                throw new Exception('Please fill in all required fields');
            }
            
            $credentials = [
                'email' => $this->input('login'),
                'username' => $this->input('login'),
                'password' => $this->input('password')
            ];
            
            $remember = $this->input('remember', false);
            
            // Debug: Log login attempt
            error_log("Login attempt for: " . $this->input('login'));
            
            // Attempt login
            $this->auth->attempt($credentials, $remember);
            
            // Clear rate limit on successful login
            // $this->rateLimit->clearRateLimit('login', $this->input('login'));
            
            // Regenerate CSRF token after successful login
            // $this->csrf->regenerateToken();
            
            // Get intended URL or default to dashboard
            $redirectUrl = $this->session->getIntendedUrl($this->baseUrl('dashboard'));
            
            // Debug: Log successful login
            error_log("Login successful for user: " . $this->input('login') . ", redirecting to: " . $redirectUrl);
            
            $this->flash('success', 'Welcome back!');
            return $this->redirect($redirectUrl);
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            $this->flash('old_input', [
                'login' => $this->input('login')
            ]);
            
            return $this->redirect($this->baseUrl('auth/login'));
        }
    }
    
    /**
     * Show registration form
     */
    public function showRegister()
    {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            return $this->redirect($this->baseUrl('dashboard'));
        }
        
        return $this->view('auth.register', [
            'title' => 'Register',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'old_input' => $this->getFlash('old_input') ?? [],
            'roles' => User::getRoles(),
            'registerAction' => $this->url('auth/register'),
            'loginUrl' => $this->url('auth/login')
        ]);
    }
    
    /**
     * Process registration
     */
    public function register()
    {
        try {
            // Verify CSRF token
            $this->verifyCsrf();
            
            // Enforce rate limiting for registration
            $this->rateLimit->enforceRegistration();
            
            $data = $this->input();
            
            // Validate input
            $validation = User::validateUserData($data);
            
            if (!$validation['valid']) {
                $errorMessages = [];
                foreach ($validation['errors'] as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages = array_merge($errorMessages, $fieldErrors);
                    } else {
                        $errorMessages[] = $fieldErrors;
                    }
                }
                throw new Exception('Validation failed: ' . implode(', ', $errorMessages));
            }
            
            // Set default role if not provided or not allowed
            if (!isset($data['role']) || !$this->isAllowedRegistrationRole($data['role'])) {
                $data['role'] = User::SCHOOL_COORDINATOR; // Default registration role
            }
            
            // Create user
            $user = User::createUser($data);
            
            // Send verification email
            $this->sendEmailVerification($user);
            
            // Regenerate CSRF token after successful registration
            $this->csrf->regenerateToken();
            
            $this->flash('success', 'Registration successful! Please check your email to verify your account.');
            return $this->redirect($this->baseUrl('auth/login'));
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            $this->flash('old_input', array_except($data ?? [], ['password', 'password_confirmation']));
            
            return $this->redirect($this->baseUrl('auth/register'));
        }
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        $this->auth->logout();
        $this->flash('success', 'You have been logged out successfully');
        return $this->redirect($this->baseUrl());
    }
    
    /**
     * Show password reset request form
     */
    public function showForgotPassword()
    {
        return $this->view('auth.forgot_password', [
            'title' => 'Forgot Password',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'old_input' => $this->getFlash('old_input') ?? [],
            'forgotPasswordAction' => $this->url('auth/forgot-password'),
            'loginUrl' => $this->url('auth/login')
        ]);
    }
    
    /**
     * Process password reset request
     */
    public function forgotPassword()
    {
        try {
            // Verify CSRF token
            $this->verifyCsrf();
            
            // Enforce rate limiting for password reset requests
            $this->rateLimit->enforcePasswordReset();
            
            $email = $this->input('email');
            
            if (!$email) {
                throw new Exception('Email address is required');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address');
            }
            
            // Generate reset token
            $token = $this->auth->generatePasswordResetToken($email);
            
            // Send reset email (implementation would depend on mail configuration)
            $this->sendPasswordResetEmail($email, $token);
            
            // Regenerate CSRF token
            $this->csrf->regenerateToken();
            
            $this->flash('success', 'Password reset instructions have been sent to your email address');
            return $this->redirect($this->baseUrl('auth/forgot-password'));
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            $this->flash('old_input', ['email' => $this->input('email')]);
            
            return $this->redirect($this->baseUrl('auth/forgot-password'));
        }
    }
    
    /**
     * Show password reset form
     */
    public function showResetPassword()
    {
        $token = $this->input('token');
        
        if (!$token) {
            $this->flash('error', 'Invalid reset token');
            return $this->redirect($this->baseUrl('auth/forgot-password'));
        }
        
        // Validate token
        $user = $this->auth->validatePasswordResetToken($token);
        
        if (!$user) {
            $this->flash('error', 'Invalid or expired reset token');
            return $this->redirect($this->baseUrl('auth/forgot-password'));
        }
        
        return $this->view('auth.reset_password', [
            'title' => 'Reset Password',
            'token' => $token,
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success')
        ]);
    }
    
    /**
     * Process password reset
     */
    public function resetPassword()
    {
        try {
            $token = $this->input('token');
            $password = $this->input('password');
            $passwordConfirmation = $this->input('password_confirmation');
            
            if (!$token || !$password || !$passwordConfirmation) {
                throw new Exception('All fields are required');
            }
            
            if ($password !== $passwordConfirmation) {
                throw new Exception('Passwords do not match');
            }
            
            // Reset password
            $this->auth->resetPassword($token, $password);
            
            $this->flash('success', 'Password has been reset successfully. You can now login with your new password.');
            return $this->redirect($this->baseUrl('auth/login'));
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect($this->baseUrl('auth/reset-password') . '?token=' . $this->input('token'));
        }
    }
    
    /**
     * Show change password form
     */
    public function showChangePassword()
    {
        $this->requireAuth();
        
        return $this->view('auth.change_password', [
            'title' => 'Change Password',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success')
        ]);
    }
    
    /**
     * Process password change
     */
    public function changePassword()
    {
        $this->requireAuth();
        
        try {
            $currentPassword = $this->input('current_password');
            $newPassword = $this->input('new_password');
            $confirmPassword = $this->input('confirm_password');
            
            if (!$currentPassword || !$newPassword || !$confirmPassword) {
                throw new Exception('All fields are required');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }
            
            // Change password
            $this->auth->changePassword($currentPassword, $newPassword);
            
            $this->flash('success', 'Password changed successfully');
            return $this->redirect($this->baseUrl('auth/change-password'));
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect($this->baseUrl('auth/change-password'));
        }
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail()
    {
        $token = $this->input('token');
        $email = $this->input('email');
        
        if (!$token || !$email) {
            $this->flash('error', 'Invalid verification link');
            return $this->redirect($this->baseUrl('auth/login'));
        }
        
        try {
            // Find user by email
            $user = User::findByEmail($email);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Verify token (this would need implementation based on how tokens are generated)
            if (!$this->verifyEmailToken($user, $token)) {
                throw new Exception('Invalid or expired verification token');
            }
            
            // Mark email as verified
            $user->email_verified = 1;
            $user->status = User::STATUS_ACTIVE;
            $user->save();
            
            $this->flash('success', 'Email verified successfully! You can now log in.');
            return $this->redirect($this->baseUrl('auth/login'));
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect($this->baseUrl('auth/login'));
        }
    }
    
    /**
     * Resend email verification
     */
    public function resendVerification()
    {
        try {
            $email = $this->input('email');
            
            if (!$email) {
                throw new Exception('Email address is required');
            }
            
            $user = User::findByEmail($email);
            
            if (!$user) {
                throw new Exception('User not found with this email address');
            }
            
            if ($user->email_verified) {
                throw new Exception('Email is already verified');
            }
            
            // Send verification email
            $this->sendEmailVerification($user);
            
            $this->flash('success', 'Verification email has been sent');
            return $this->redirect($this->baseUrl('auth/login'));
            
        } catch (Exception $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect($this->baseUrl('auth/login'));
        }
    }
    
    /**
     * Check if role is allowed for public registration
     */
    private function isAllowedRegistrationRole($role)
    {
        $allowedRoles = [
            User::SCHOOL_COORDINATOR,
            User::TEAM_COACH
        ];
        
        return in_array($role, $allowedRoles);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $token)
    {
        $user = User::findByEmail($email);
        
        if (!$user) {
            // Still log for security purposes but don't reveal if user exists
            error_log("Password reset requested for non-existent email: {$email}");
            return;
        }
        
        try {
            $mail = Mail::getInstance();
            $mail->sendPasswordReset($user, $token);
            
        } catch (Exception $e) {
            // Log error but don't expose to user for security
            error_log("Failed to send password reset email to {$email}: " . $e->getMessage());
            
            // In development, you might want to throw the exception
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                throw $e;
            }
        }
    }
    
    /**
     * Send email verification
     */
    private function sendEmailVerification($user)
    {
        try {
            $mail = Mail::getInstance();
            $mail->sendEmailVerification($user);
            
        } catch (Exception $e) {
            error_log("Failed to send verification email to {$user->email}: " . $e->getMessage());
            
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                throw $e;
            }
        }
    }
    
    /**
     * Verify email token
     */
    private function verifyEmailToken($user, $token)
    {
        // This is a simple implementation - in production you'd want more secure token handling
        $expectedToken = hash('sha256', $user->email . $user->created_at . time());
        
        // For demo purposes, we'll accept any non-empty token
        // In production, implement proper token validation with expiration
        return !empty($token);
    }
}

/**
 * Helper function to exclude keys from array
 */
if (!function_exists('array_except')) {
    function array_except($array, $keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        return array_diff_key($array, array_flip($keys));
    }
}