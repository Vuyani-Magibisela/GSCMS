<?php
// app/Services/JudgeRegistrationService.php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\EnhancedJudgeProfile;
use App\Models\Organization;
use App\Core\Mail;

class JudgeRegistrationService
{
    private $db;
    
    const REQUIRED_DOCUMENTS = [
        'cv' => ['required' => true, 'formats' => ['pdf', 'doc', 'docx']],
        'id_document' => ['required' => true, 'formats' => ['pdf', 'jpg', 'png']],
        'qualifications' => ['required' => false, 'formats' => ['pdf']],
        'police_clearance' => ['required' => false, 'formats' => ['pdf']]
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new judge with complete onboarding
     */
    public function registerJudge($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Validate registration data
            $validation = $this->validateRegistrationData($data);
            if (!$validation['valid']) {
                throw new \Exception('Registration validation failed: ' . implode(', ', $validation['errors']));
            }
            
            // Create user account
            $user = $this->createUserAccount($data);
            
            // Create judge profile
            $judgeProfile = $this->createJudgeProfile($user->id, $data);
            
            // Process uploaded documents
            if (isset($data['documents'])) {
                $this->processDocuments($judgeProfile->id, $data['documents']);
            }
            
            // Create onboarding checklist
            $this->createOnboardingChecklist($judgeProfile->id);
            
            // Send welcome email with onboarding instructions
            $this->sendWelcomeEmail($user, $judgeProfile);
            
            // Schedule onboarding follow-up
            $this->scheduleOnboardingFollowUp($judgeProfile);
            
            // Update organization judge count if applicable
            if ($judgeProfile->organization_id) {
                $organization = Organization::find($judgeProfile->organization_id);
                if ($organization) {
                    $organization->updateJudgesCount();
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'user' => $user,
                'judge_profile' => $judgeProfile,
                'onboarding_required' => true,
                'next_steps' => $this->getNextSteps($judgeProfile)
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistrationData($data)
    {
        $errors = [];
        
        // Required fields validation
        $requiredFields = [
            'first_name', 'last_name', 'email', 'password', 'password_confirmation',
            'judge_type', 'experience_level', 'phone'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        // Email validation
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Check if email already exists
        if (isset($data['email'])) {
            $existingUser = User::findByEmail($data['email']);
            if ($existingUser) {
                $errors[] = 'Email address is already registered';
            }
        }
        
        // Password validation
        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            }
            
            if ($data['password'] !== $data['password_confirmation']) {
                $errors[] = 'Password confirmation does not match';
            }
        }
        
        // Judge type validation
        $validJudgeTypes = array_keys(EnhancedJudgeProfile::getJudgeTypes());
        if (isset($data['judge_type']) && !in_array($data['judge_type'], $validJudgeTypes)) {
            $errors[] = 'Invalid judge type';
        }
        
        // Experience level validation
        $validExperienceLevels = array_keys(EnhancedJudgeProfile::getExperienceLevels());
        if (isset($data['experience_level']) && !in_array($data['experience_level'], $validExperienceLevels)) {
            $errors[] = 'Invalid experience level';
        }
        
        // Organization validation (if provided)
        if (!empty($data['organization_id'])) {
            $organization = Organization::find($data['organization_id']);
            if (!$organization || !$organization->isActive()) {
                $errors[] = 'Invalid or inactive organization';
            }
        }
        
        // Phone number validation
        if (isset($data['phone']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $data['phone'])) {
            $errors[] = 'Invalid phone number format';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Create user account for judge
     */
    private function createUserAccount($data)
    {
        $userData = [
            'username' => $this->generateUsername($data['first_name'], $data['last_name']),
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'role' => 'judge',
            'status' => 'pending', // Pending until onboarding complete
            'email_verified' => 0
        ];
        
        return User::createUser($userData);
    }
    
    /**
     * Generate unique username
     */
    private function generateUsername($firstName, $lastName)
    {
        $baseUsername = strtolower($firstName . '.' . $lastName);
        $baseUsername = preg_replace('/[^a-z0-9.]/', '', $baseUsername);
        
        $username = $baseUsername;
        $counter = 1;
        
        while (User::findByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Create judge profile
     */
    private function createJudgeProfile($userId, $data)
    {
        $profileData = [
            'user_id' => $userId,
            'judge_type' => $data['judge_type'],
            'organization_id' => $data['organization_id'] ?? null,
            'experience_level' => $data['experience_level'],
            'years_experience' => $data['years_experience'] ?? $this->calculateYearsFromLevel($data['experience_level']),
            'professional_title' => $data['professional_title'] ?? null,
            'professional_bio' => $data['bio'] ?? null,
            'linkedin_profile' => $data['linkedin_profile'] ?? null,
            'expertise_areas' => $data['expertise_areas'] ?? [],
            'categories_qualified' => $data['categories_qualified'] ?? [],
            'preferred_categories' => $data['preferred_categories'] ?? null,
            'max_assignments_per_day' => $data['max_assignments_per_day'] ?? 10,
            'languages_spoken' => $data['languages_spoken'] ?? 'English',
            'availability_notes' => $data['availability_notes'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
            'accessibility_needs' => $data['accessibility_needs'] ?? null,
            'special_requirements' => $data['special_requirements'] ?? null,
            'availability' => $data['availability'] ?? [],
            'preferred_venues' => $data['preferred_venues'] ?? [],
            'contact_preferences' => $data['contact_preferences'] ?? [],
            'certifications' => $data['certifications'] ?? []
        ];
        
        return EnhancedJudgeProfile::createJudgeProfile($profileData);
    }
    
    /**
     * Calculate years experience from experience level
     */
    private function calculateYearsFromLevel($level)
    {
        $levelMap = [
            'novice' => 0,
            'intermediate' => 2,
            'advanced' => 4,
            'expert' => 6
        ];
        
        return $levelMap[$level] ?? 0;
    }
    
    /**
     * Process uploaded documents
     */
    private function processDocuments($judgeId, $documents)
    {
        $uploadDir = 'public/uploads/judge_documents/' . $judgeId . '/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($documents as $documentType => $file) {
            if (!isset(self::REQUIRED_DOCUMENTS[$documentType])) {
                continue;
            }
            
            $documentConfig = self::REQUIRED_DOCUMENTS[$documentType];
            
            // Validate file format
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $documentConfig['formats'])) {
                throw new \Exception("Invalid file format for {$documentType}. Allowed: " . implode(', ', $documentConfig['formats']));
            }
            
            // Generate secure filename
            $filename = $documentType . '_' . $judgeId . '_' . time() . '.' . $fileExtension;
            $filepath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save document record
                $this->db->insert('judge_documents', [
                    'judge_id' => $judgeId,
                    'document_type' => $documentType,
                    'filename' => $filename,
                    'file_path' => $filepath,
                    'uploaded_at' => date('Y-m-d H:i:s'),
                    'verified' => false
                ]);
            }
        }
    }
    
    /**
     * Create onboarding checklist
     */
    private function createOnboardingChecklist($judgeId)
    {
        $checklistItems = [
            'email_verification' => [
                'title' => 'Verify Email Address',
                'description' => 'Click the verification link sent to your email',
                'required' => true,
                'order' => 1
            ],
            'document_upload' => [
                'title' => 'Upload Required Documents',
                'description' => 'Upload CV, ID document, and any qualifications',
                'required' => true,
                'order' => 2
            ],
            'profile_completion' => [
                'title' => 'Complete Profile Information',
                'description' => 'Fill in all required profile fields',
                'required' => true,
                'order' => 3
            ],
            'training_completion' => [
                'title' => 'Complete Online Training',
                'description' => 'Complete the mandatory judge training modules',
                'required' => true,
                'order' => 4
            ],
            'background_check' => [
                'title' => 'Background Check',
                'description' => 'Complete background verification process',
                'required' => false,
                'order' => 5
            ],
            'first_assignment' => [
                'title' => 'First Judging Assignment',
                'description' => 'Complete your first judging assignment with mentorship',
                'required' => true,
                'order' => 6
            ]
        ];
        
        foreach ($checklistItems as $itemKey => $item) {
            $this->db->insert('judge_onboarding_checklist', [
                'judge_id' => $judgeId,
                'item_key' => $itemKey,
                'title' => $item['title'],
                'description' => $item['description'],
                'required' => $item['required'],
                'order_index' => $item['order'],
                'completed' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Send welcome email with onboarding instructions
     */
    private function sendWelcomeEmail($user, $judgeProfile)
    {
        $mail = new Mail();
        
        $subject = 'Welcome to GDE SciBOTICS Judge Program';
        
        $emailData = [
            'judge_name' => "{$user['first_name']} {$user['last_name']}",
            'judge_code' => $judgeProfile->judge_code,
            'judge_type' => EnhancedJudgeProfile::getJudgeTypes()[$judgeProfile->judge_type],
            'onboarding_url' => $this->generateOnboardingUrl($judgeProfile),
            'verification_token' => $user['email_verification_token']
        ];
        
        $message = $this->generateWelcomeEmailContent($emailData);
        
        return $mail->send($user['email'], $subject, $message);
    }
    
    /**
     * Generate welcome email content
     */
    private function generateWelcomeEmailContent($data)
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 30px;'>
                    <h1 style='color: #007bff; margin: 0;'>GDE SciBOTICS</h1>
                    <h2 style='color: #6c757d; margin: 10px 0 0 0;'>Judge Program</h2>
                </div>
                
                <h2 style='color: #007bff;'>Welcome, {$data['judge_name']}!</h2>
                
                <p>Congratulations! You have been registered as a <strong>{$data['judge_type']}</strong> for the GDE SciBOTICS Competition.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #007bff; margin-top: 0;'>Your Judge Details:</h3>
                    <p><strong>Judge Code:</strong> {$data['judge_code']}</p>
                    <p><strong>Judge Type:</strong> {$data['judge_type']}</p>
                </div>
                
                <h3 style='color: #007bff;'>Next Steps:</h3>
                <ol style='line-height: 1.8;'>
                    <li><strong>Verify your email:</strong> Click the verification link below</li>
                    <li><strong>Complete onboarding:</strong> Visit your onboarding dashboard</li>
                    <li><strong>Upload documents:</strong> Submit required documentation</li>
                    <li><strong>Complete training:</strong> Finish mandatory training modules</li>
                </ol>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['onboarding_url']}' style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Start Onboarding Process</a>
                </div>
                
                <p style='color: #6c757d; font-size: 14px;'>If you have any questions, please contact our support team at <a href='mailto:support@gdescibotics.co.za'>support@gdescibotics.co.za</a></p>
                
                <div style='border-top: 1px solid #dee2e6; padding-top: 20px; margin-top: 30px; text-align: center; color: #6c757d; font-size: 12px;'>
                    <p>GDE SciBOTICS Competition Management System<br>
                    This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generate onboarding URL
     */
    private function generateOnboardingUrl($judgeProfile)
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return $baseUrl . '/judge/onboarding/' . $judgeProfile->judge_code;
    }
    
    /**
     * Schedule onboarding follow-up
     */
    private function scheduleOnboardingFollowUp($judgeProfile)
    {
        // Create reminder records for automated follow-up
        $reminderDates = [
            date('Y-m-d H:i:s', strtotime('+3 days')),
            date('Y-m-d H:i:s', strtotime('+1 week')),
            date('Y-m-d H:i:s', strtotime('+2 weeks'))
        ];
        
        foreach ($reminderDates as $index => $reminderDate) {
            $this->db->insert('judge_onboarding_reminders', [
                'judge_id' => $judgeProfile->id,
                'reminder_type' => 'onboarding_followup',
                'scheduled_for' => $reminderDate,
                'reminder_number' => $index + 1,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Get next steps for judge onboarding
     */
    private function getNextSteps($judgeProfile)
    {
        return [
            [
                'step' => 'verify_email',
                'title' => 'Verify Email Address',
                'description' => 'Click the verification link sent to your email',
                'status' => 'pending'
            ],
            [
                'step' => 'complete_profile',
                'title' => 'Complete Profile',
                'description' => 'Fill in additional profile information',
                'status' => 'pending'
            ],
            [
                'step' => 'upload_documents',
                'title' => 'Upload Documents',
                'description' => 'Submit required documentation',
                'status' => 'pending'
            ],
            [
                'step' => 'training',
                'title' => 'Complete Training',
                'description' => 'Finish mandatory training modules',
                'status' => 'pending'
            ]
        ];
    }
    
    /**
     * Approve judge registration
     */
    public function approveJudge($judgeId, $approverId)
    {
        $judgeProfile = EnhancedJudgeProfile::find($judgeId);
        
        if (!$judgeProfile) {
            throw new \Exception("Judge profile not found");
        }
        
        // Verify all requirements are met
        if (!$this->verifyRequirements($judgeProfile)) {
            throw new \Exception("Judge has not met all requirements for approval");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update judge profile status
            $this->db->query("
                UPDATE judge_profiles 
                SET status = 'active', onboarding_completed = 1, updated_at = NOW()
                WHERE id = ?
            ", [$judgeId]);
            
            // Update user status
            $this->db->query("
                UPDATE users 
                SET status = 'active', updated_at = NOW()
                WHERE id = ?
            ", [$judgeProfile->user_id]);
            
            // Log approval
            $this->logApproval($judgeId, $approverId);
            
            // Send approval notification
            $this->sendApprovalNotification($judgeProfile);
            
            // Add to active judge pool
            $this->addToJudgePool($judgeProfile);
            
            $this->db->commit();
            
            return $judgeProfile;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Verify judge requirements
     */
    private function verifyRequirements($judgeProfile)
    {
        // Check onboarding completion
        $completedItems = $this->db->query("
            SELECT COUNT(*) as completed, 
                   COUNT(CASE WHEN required = 1 THEN 1 END) as required_total,
                   COUNT(CASE WHEN required = 1 AND completed = 1 THEN 1 END) as required_completed
            FROM judge_onboarding_checklist 
            WHERE judge_id = ?
        ", [$judgeProfile->id]);
        
        $stats = $completedItems[0];
        
        // All required items must be completed
        if ($stats['required_completed'] < $stats['required_total']) {
            return false;
        }
        
        // Email must be verified
        $user = $judgeProfile->getUser();
        if (!$user || !$user['email_verified']) {
            return false;
        }
        
        // Background check if required
        if ($judgeProfile->background_check_status === 'failed') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log judge approval
     */
    private function logApproval($judgeId, $approverId)
    {
        $this->db->insert('judge_approval_log', [
            'judge_id' => $judgeId,
            'approved_by' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
            'action' => 'approved'
        ]);
    }
    
    /**
     * Send approval notification
     */
    private function sendApprovalNotification($judgeProfile)
    {
        $user = $judgeProfile->getUser();
        if (!$user) return;
        
        $mail = new Mail();
        
        $subject = 'Judge Registration Approved - GDE SciBOTICS';
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Congratulations! Your judge registration has been approved.</h2>
            <p>Dear {$user['first_name']} {$user['last_name']},</p>
            <p>We are pleased to inform you that your application to become a judge for the GDE SciBOTICS Competition has been approved.</p>
            <p><strong>Judge Code:</strong> {$judgeProfile->judge_code}</p>
            <p>You can now access the judge portal and will receive assignment notifications as competitions are scheduled.</p>
            <p>Thank you for joining our judging panel!</p>
        </body>
        </html>
        ";
        
        return $mail->send($user['email'], $subject, $message);
    }
    
    /**
     * Add judge to active pool
     */
    private function addToJudgePool($judgeProfile)
    {
        // This could involve adding to scheduling systems, notification lists, etc.
        // For now, we'll just ensure the status is correctly set
        return true;
    }
    
    /**
     * Get registration statistics
     */
    public function getRegistrationStatistics()
    {
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN jp.status = 'pending' THEN 1 END) as pending_approval,
                COUNT(CASE WHEN jp.status = 'active' THEN 1 END) as active_judges,
                COUNT(CASE WHEN jp.status = 'inactive' THEN 1 END) as inactive_judges,
                COUNT(CASE WHEN jp.onboarding_completed = 1 THEN 1 END) as onboarding_complete,
                COUNT(CASE WHEN jp.judge_type = 'coordinator' THEN 1 END) as coordinators,
                COUNT(CASE WHEN jp.judge_type = 'adjudicator' THEN 1 END) as adjudicators,
                COUNT(CASE WHEN jp.judge_type = 'technical' THEN 1 END) as technical_judges,
                COUNT(CASE WHEN jp.judge_type = 'volunteer' THEN 1 END) as volunteers,
                COUNT(CASE WHEN jp.judge_type = 'industry' THEN 1 END) as industry_experts
            FROM judge_profiles jp
        ");
        
        return $stats[0] ?? [];
    }
}