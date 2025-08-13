<?php
// app/Core/Mail.php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;

class Mail
{
    private static $instance = null;
    private $config;
    private $mailer;
    
    private function __construct()
    {
        $this->config = require CONFIG_PATH . '/mail.php';
        $this->setupMailer();
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
     * Setup PHPMailer instance
     */
    private function setupMailer()
    {
        $this->mailer = new PHPMailer(true);
        
        $defaultMailer = $this->config['default'];
        $mailerConfig = $this->config['mailers'][$defaultMailer];
        
        try {
            switch ($mailerConfig['transport']) {
                case 'smtp':
                    $this->setupSMTP($mailerConfig);
                    break;
                    
                case 'sendmail':
                    $this->setupSendmail($mailerConfig);
                    break;
                    
                case 'log':
                    // Log emails instead of sending
                    break;
                    
                default:
                    throw new Exception("Unsupported mail transport: {$mailerConfig['transport']}");
            }
            
            // Set global from address
            $from = $this->config['from'];
            $this->mailer->setFrom($from['address'], $from['name']);
            
        } catch (PHPMailerException $e) {
            throw new Exception("Mail configuration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Setup SMTP configuration
     */
    private function setupSMTP($config)
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['host'];
        $this->mailer->SMTPAuth = !empty($config['username']);
        $this->mailer->Username = $config['username'];
        $this->mailer->Password = $config['password'];
        $this->mailer->Port = $config['port'];
        
        if ($config['encryption']) {
            $this->mailer->SMTPSecure = $config['encryption'];
        }
        
        // Disable SMTP debug to prevent headers already sent issues
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
    }
    
    /**
     * Setup Sendmail configuration
     */
    private function setupSendmail($config)
    {
        $this->mailer->isSendmail();
        $this->mailer->Sendmail = $config['path'];
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $body, $options = [])
    {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            
            // Set recipient
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addAddress($name);
                    } else {
                        $this->mailer->addAddress($email, $name);
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            // Set CC recipients
            if (isset($options['cc'])) {
                if (is_array($options['cc'])) {
                    foreach ($options['cc'] as $email => $name) {
                        if (is_numeric($email)) {
                            $this->mailer->addCC($name);
                        } else {
                            $this->mailer->addCC($email, $name);
                        }
                    }
                } else {
                    $this->mailer->addCC($options['cc']);
                }
            }
            
            // Set BCC recipients
            if (isset($options['bcc'])) {
                if (is_array($options['bcc'])) {
                    foreach ($options['bcc'] as $email => $name) {
                        if (is_numeric($email)) {
                            $this->mailer->addBCC($name);
                        } else {
                            $this->mailer->addBCC($email, $name);
                        }
                    }
                } else {
                    $this->mailer->addBCC($options['bcc']);
                }
            }
            
            // Set reply-to
            if (isset($options['reply_to'])) {
                if (is_array($options['reply_to'])) {
                    $this->mailer->addReplyTo($options['reply_to']['email'], $options['reply_to']['name'] ?? '');
                } else {
                    $this->mailer->addReplyTo($options['reply_to']);
                }
            }
            
            // Set subject and body
            $this->mailer->Subject = $subject;
            
            // Determine if body is HTML
            if (isset($options['html']) && $options['html']) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $body;
                
                // Set plain text alternative if provided
                if (isset($options['text'])) {
                    $this->mailer->AltBody = $options['text'];
                }
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $body;
            }
            
            // Add attachments
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? '',
                            $attachment['encoding'] ?? 'base64',
                            $attachment['type'] ?? ''
                        );
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Handle different mail transports
            $defaultMailer = $this->config['default'];
            $mailerConfig = $this->config['mailers'][$defaultMailer];
            
            if ($mailerConfig['transport'] === 'log') {
                $this->logEmail($to, $subject, $body);
                return true;
            }
            
            // Send the email with output buffering to prevent any debug output
            ob_start();
            $result = $this->mailer->send();
            ob_end_clean();
            
            return $result;
            
        } catch (PHPMailerException $e) {
            // Clean up output buffer if still active
            if (ob_get_level()) {
                ob_end_clean();
            }
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }
    
    /**
     * Send email using template
     */
    public function sendTemplate($templateName, $to, $data = [], $options = [])
    {
        if (!isset($this->config['templates'][$templateName])) {
            throw new Exception("Email template not found: {$templateName}");
        }
        
        $template = $this->config['templates'][$templateName];
        $subject = $template['subject'];
        
        // Load and render template
        $body = $this->renderTemplate($template['template'], $data);
        
        // Default to HTML for templates
        $options['html'] = $options['html'] ?? true;
        
        return $this->send($to, $subject, $body, $options);
    }
    
    /**
     * Render email template
     */
    private function renderTemplate($templatePath, $data = [])
    {
        $templateFile = VIEW_PATH . '/' . str_replace('.', '/', $templatePath) . '.php';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Email template file not found: {$templateFile}");
        }
        
        // Extract variables to template scope
        extract($data);
        
        // Start output buffering
        ob_start();
        
        try {
            include $templateFile;
            $content = ob_get_contents();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        
        ob_end_clean();
        
        // Wrap content in email layout
        return $this->wrapInLayout($content);
    }
    
    /**
     * Wrap content in email layout
     */
    private function wrapInLayout($content)
    {
        $layoutFile = VIEW_PATH . '/emails/layout.php';
        
        if (!file_exists($layoutFile)) {
            return $content; // Return content without layout if layout doesn't exist
        }
        
        // Start output buffering for layout
        ob_start();
        
        try {
            include $layoutFile;
            $wrappedContent = ob_get_contents();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        
        ob_end_clean();
        
        return $wrappedContent;
    }
    
    /**
     * Log email instead of sending (for development)
     */
    private function logEmail($to, $subject, $body)
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'body' => $body
        ];
        
        $logFile = LOG_PATH . '/emails-' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Also log to error log for immediate visibility
        error_log("EMAIL: To={$to}, Subject={$subject}");
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($user, $token)
    {
        $resetUrl = $this->getBaseUrl() . "/auth/reset-password?token={$token}";
        
        $data = [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'token' => $token,
            'expiresIn' => '1 hour'
        ];
        
        return $this->sendTemplate('password_reset', $user->email, $data);
    }
    
    /**
     * Send email verification
     */
    public function sendEmailVerification($user)
    {
        // Generate verification token
        $token = hash('sha256', $user->email . $user->created_at . time());
        
        $verifyUrl = $this->getBaseUrl() . "/auth/verify-email?token={$token}&email=" . urlencode($user->email);
        
        $data = [
            'user' => $user,
            'verifyUrl' => $verifyUrl,
            'token' => $token
        ];
        
        return $this->sendTemplate('email_verification', $user->email, $data);
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcome($user)
    {
        $data = [
            'user' => $user,
            'loginUrl' => $this->getBaseUrl() . '/auth/login'
        ];
        
        return $this->sendTemplate('welcome', $user->email, $data);
    }
    
    /**
     * Get application base URL
     */
    private function getBaseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptPath = $scriptPath === '/' ? '' : $scriptPath;
        
        return $protocol . $host . $scriptPath;
    }
    
    /**
     * Test email configuration
     */
    public function testConnection()
    {
        try {
            $defaultMailer = $this->config['default'];
            $mailerConfig = $this->config['mailers'][$defaultMailer];
            
            if ($mailerConfig['transport'] === 'smtp') {
                // Test SMTP connection
                $this->mailer->smtpConnect();
                $this->mailer->smtpClose();
                return true;
            }
            
            return true;
            
        } catch (PHPMailerException $e) {
            throw new Exception("Mail connection test failed: " . $e->getMessage());
        }
    }
}