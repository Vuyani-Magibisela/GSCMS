<?php
// app/Controllers/JudgeRegistrationController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\JudgeRegistrationService;
use App\Models\Organization;
use App\Models\EnhancedJudgeProfile;

class JudgeRegistrationController extends Controller
{
    private $registrationService;
    
    public function __construct()
    {
        parent::__construct();
        $this->registrationService = new JudgeRegistrationService();
    }
    
    /**
     * Show judge registration form
     */
    public function index()
    {
        $data = [
            'organizations' => $this->getActiveOrganizations(),
            'judge_types' => EnhancedJudgeProfile::getJudgeTypes(),
            'experience_levels' => EnhancedJudgeProfile::getExperienceLevels(),
            'categories' => $this->getCategories(),
            'venues' => $this->getVenues()
        ];
        
        return $this->view('judge/registration/index', $data);
    }
    
    /**
     * Process judge registration
     */
    public function register()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->redirect('/judge/register');
        }
        
        try {
            $data = $this->request->all();
            
            // Handle file uploads
            $data['documents'] = $_FILES ?? [];
            
            $result = $this->registrationService->registerJudge($data);
            
            if ($result['success']) {
                $this->session->setFlash('success', 'Registration successful! Please check your email for verification instructions.');
                
                return $this->json([
                    'success' => true,
                    'message' => 'Registration completed successfully',
                    'judge_code' => $result['judge_profile']->judge_code,
                    'next_steps' => $result['next_steps'],
                    'redirect' => '/judge/registration/success/' . $result['judge_profile']->judge_code
                ]);
            }
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 400);
        }
    }
    
    /**
     * Show registration success page
     */
    public function success($judgeCode)
    {
        $data = [
            'judge_code' => $judgeCode,
            'next_steps' => [
                'Verify your email address',
                'Complete your profile information',
                'Upload required documents',
                'Complete training modules'
            ]
        ];
        
        return $this->view('judge/registration/success', $data);
    }
    
    /**
     * Show onboarding dashboard
     */
    public function onboarding($judgeCode = null)
    {
        if (!$judgeCode) {
            // If no judge code provided, try to get from authenticated user
            $user = $this->getCurrentUser();
            if ($user && $user['role'] === 'judge') {
                $judgeProfile = $this->db->query("
                    SELECT * FROM judge_profiles WHERE user_id = ?
                ", [$user['id']]);
                
                if (!empty($judgeProfile)) {
                    $judgeCode = $judgeProfile[0]['judge_code'];
                }
            }
        }
        
        if (!$judgeCode) {
            return $this->redirect('/judge/register');
        }
        
        $judgeProfile = $this->db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email, u.email_verified
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE jp.judge_code = ?
        ", [$judgeCode]);
        
        if (empty($judgeProfile)) {
            $this->session->setFlash('error', 'Judge profile not found.');
            return $this->redirect('/judge/register');
        }
        
        $judge = $judgeProfile[0];
        
        // Get onboarding checklist
        $checklist = $this->db->query("
            SELECT * FROM judge_onboarding_checklist
            WHERE judge_id = ?
            ORDER BY order_index ASC
        ", [$judge['id']]);
        
        // Get uploaded documents
        $documents = $this->db->query("
            SELECT * FROM judge_documents
            WHERE judge_id = ?
            ORDER BY uploaded_at DESC
        ", [$judge['id']]);
        
        // Calculate progress
        $totalItems = count($checklist);
        $completedItems = count(array_filter($checklist, function($item) {
            return $item['completed'];
        }));
        $progress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
        
        $data = [
            'judge' => $judge,
            'checklist' => $checklist,
            'documents' => $documents,
            'progress' => $progress,
            'organizations' => $this->getActiveOrganizations(),
            'categories' => $this->getCategories()
        ];
        
        return $this->view('judge/onboarding/dashboard', $data);
    }
    
    /**
     * Update onboarding item completion
     */
    public function updateOnboardingItem()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $data = $this->request->all();
        $judgeId = $data['judge_id'] ?? null;
        $itemKey = $data['item_key'] ?? null;
        $completed = $data['completed'] ?? false;
        
        if (!$judgeId || !$itemKey) {
            return $this->json(['success' => false, 'message' => 'Missing required parameters'], 400);
        }
        
        try {
            $this->db->query("
                UPDATE judge_onboarding_checklist 
                SET completed = ?, completed_at = ?
                WHERE judge_id = ? AND item_key = ?
            ", [
                $completed ? 1 : 0,
                $completed ? date('Y-m-d H:i:s') : null,
                $judgeId,
                $itemKey
            ]);
            
            // Check if all required items are completed
            $remainingRequired = $this->db->query("
                SELECT COUNT(*) as count
                FROM judge_onboarding_checklist
                WHERE judge_id = ? AND required = 1 AND completed = 0
            ", [$judgeId]);
            
            $allRequiredComplete = $remainingRequired[0]['count'] == 0;
            
            if ($allRequiredComplete) {
                // Update judge profile onboarding status
                $this->db->query("
                    UPDATE judge_profiles 
                    SET onboarding_completed = 1
                    WHERE id = ?
                ", [$judgeId]);
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Onboarding item updated successfully',
                'all_required_complete' => $allRequiredComplete
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update onboarding item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload judge documents
     */
    public function uploadDocuments()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $judgeId = $this->request->input('judge_id');
        
        if (!$judgeId) {
            return $this->json(['success' => false, 'message' => 'Judge ID is required'], 400);
        }
        
        try {
            $uploadedFiles = [];
            $errors = [];
            
            foreach ($_FILES as $documentType => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $result = $this->processDocumentUpload($judgeId, $documentType, $file);
                    if ($result['success']) {
                        $uploadedFiles[] = $result['filename'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            if (!empty($uploadedFiles)) {
                // Mark document upload as completed in onboarding
                $this->db->query("
                    UPDATE judge_onboarding_checklist 
                    SET completed = 1, completed_at = NOW()
                    WHERE judge_id = ? AND item_key = 'document_upload'
                ", [$judgeId]);
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Documents uploaded successfully',
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process individual document upload
     */
    private function processDocumentUpload($judgeId, $documentType, $file)
    {
        $allowedTypes = [
            'cv' => ['pdf', 'doc', 'docx'],
            'id_document' => ['pdf', 'jpg', 'jpeg', 'png'],
            'qualifications' => ['pdf'],
            'police_clearance' => ['pdf']
        ];
        
        if (!isset($allowedTypes[$documentType])) {
            return ['success' => false, 'message' => "Unknown document type: {$documentType}"];
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes[$documentType])) {
            return [
                'success' => false, 
                'message' => "Invalid file type for {$documentType}. Allowed: " . implode(', ', $allowedTypes[$documentType])
            ];
        }
        
        // Create upload directory
        $uploadDir = "public/uploads/judge_documents/{$judgeId}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate secure filename
        $filename = $documentType . '_' . $judgeId . '_' . time() . '.' . $fileExtension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Save to database
            $this->db->insert('judge_documents', [
                'judge_id' => $judgeId,
                'document_type' => $documentType,
                'filename' => $filename,
                'file_path' => $filepath,
                'uploaded_at' => date('Y-m-d H:i:s'),
                'verified' => false
            ]);
            
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    /**
     * Get active organizations for registration form
     */
    private function getActiveOrganizations()
    {
        return $this->db->query("
            SELECT id, organization_name, organization_type
            FROM organizations
            WHERE partnership_status = 'active'
            ORDER BY organization_name ASC
        ");
    }
    
    /**
     * Get categories for registration form
     */
    private function getCategories()
    {
        return $this->db->query("
            SELECT id, category_name, description
            FROM categories
            WHERE is_active = 1
            ORDER BY category_name ASC
        ");
    }
    
    /**
     * Get venues for registration form
     */
    private function getVenues()
    {
        return $this->db->query("
            SELECT id, name, location
            FROM venues
            WHERE status = 'active'
            ORDER BY name ASC
        ");
    }
    
    /**
     * Admin: View all judge registrations
     */
    public function adminIndex()
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        $judges = $this->db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email, u.status as user_status,
                   o.organization_name,
                   COUNT(joc.id) as total_checklist_items,
                   COUNT(CASE WHEN joc.completed = 1 THEN 1 END) as completed_items
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN organizations o ON jp.organization_id = o.id
            LEFT JOIN judge_onboarding_checklist joc ON jp.id = joc.judge_id
            GROUP BY jp.id
            ORDER BY jp.created_at DESC
        ");
        
        $statistics = $this->registrationService->getRegistrationStatistics();
        
        $data = [
            'judges' => $judges,
            'statistics' => $statistics,
            'judge_types' => EnhancedJudgeProfile::getJudgeTypes(),
            'statuses' => EnhancedJudgeProfile::getStatuses()
        ];
        
        return $this->view('admin/judges/registrations', $data);
    }
    
    /**
     * Admin: Approve judge registration
     */
    public function adminApprove($judgeId)
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        try {
            $currentUser = $this->getCurrentUser();
            $result = $this->registrationService->approveJudge($judgeId, $currentUser['id']);
            
            return $this->json([
                'success' => true,
                'message' => 'Judge approved successfully',
                'judge' => $result->getProfileSummary()
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Approval failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Admin: View judge registration details
     */
    public function adminView($judgeId)
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        $judge = $this->db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email, u.email_verified, u.status as user_status,
                   o.organization_name, o.organization_type
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN organizations o ON jp.organization_id = o.id
            WHERE jp.id = ?
        ", [$judgeId]);
        
        if (empty($judge)) {
            $this->session->setFlash('error', 'Judge not found.');
            return $this->redirect('/admin/judges/registrations');
        }
        
        $judge = $judge[0];
        
        // Get onboarding checklist
        $checklist = $this->db->query("
            SELECT * FROM judge_onboarding_checklist
            WHERE judge_id = ?
            ORDER BY order_index ASC
        ", [$judgeId]);
        
        // Get documents
        $documents = $this->db->query("
            SELECT * FROM judge_documents
            WHERE judge_id = ?
            ORDER BY uploaded_at DESC
        ", [$judgeId]);
        
        // Get qualifications
        $qualifications = $this->db->query("
            SELECT * FROM judge_qualifications
            WHERE judge_id = ?
            ORDER BY issue_date DESC
        ", [$judgeId]);
        
        $data = [
            'judge' => $judge,
            'checklist' => $checklist,
            'documents' => $documents,
            'qualifications' => $qualifications
        ];
        
        return $this->view('admin/judges/view', $data);
    }
}