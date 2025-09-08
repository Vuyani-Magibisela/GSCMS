<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\ConsentForm;
use App\Models\MedicalInformation;
use App\Models\EmergencyContact;
use App\Models\StudentDocument;
use App\Models\DigitalSignature;
use App\Models\User;
use App\Models\Participant;
use App\Models\School;
use Exception;

class DocumentManagementController extends BaseController
{
    protected $auth;
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance();
    }
    
    /**
     * Document management dashboard
     */
    public function index()
    {
        // Check authentication and permissions
        if (!$this->auth->isLoggedIn()) {
            return redirect('/login');
        }
        
        $user = $this->auth->getUser();
        $userRole = $this->auth->getRole();
        
        // Get document statistics based on user role
        $stats = $this->getDocumentStatistics($user, $userRole);
        
        // Get pending actions
        $pendingActions = $this->getPendingActions($user, $userRole);
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity($user, $userRole);
        
        return $this->render('documents/dashboard', [
            'pageTitle' => 'Document Management Dashboard',
            'pageCSS' => ['/css/documents.css'],
            'pageJS' => ['/js/documents-dashboard.js'],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Document Management', 'url' => '']
            ],
            'stats' => $stats,
            'pendingActions' => $pendingActions,
            'recentActivity' => $recentActivity,
            'userRole' => $userRole
        ]);
    }
    
    /**
     * Document verification queue
     */
    public function verificationQueue()
    {
        // Admin and authorized users only
        if (!$this->auth->hasPermission('document.verify')) {
            return Response::json(['error' => 'Permission denied'], 403);
        }
        
        $status = Request::input('status', 'pending');
        $documentType = Request::input('type');
        $schoolId = Request::input('school_id');
        $page = (int) Request::input('page', 1);
        $limit = 25;
        
        // Build query for pending documents
        $query = $this->buildVerificationQuery($status, $documentType, $schoolId);
        
        // Get total count
        $totalCount = $this->db->query("SELECT COUNT(*) as count FROM ($query) as total")->fetch()['count'];
        
        // Get paginated results
        $offset = ($page - 1) * $limit;
        $documents = $this->db->query("$query LIMIT $limit OFFSET $offset")->fetchAll();
        
        // Process documents for display
        $processedDocuments = array_map([$this, 'processDocumentForDisplay'], $documents);
        
        return $this->render('documents/verification-queue', [
            'pageTitle' => 'Document Verification Queue',
            'pageCSS' => ['/css/documents.css', '/css/verification-queue.css'],
            'pageJS' => ['/js/document-verification.js'],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Document Management', 'url' => '/admin/documents'],
                ['title' => 'Verification Queue', 'url' => '']
            ],
            'documents' => $processedDocuments,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $limit),
                'total_count' => $totalCount,
                'per_page' => $limit
            ],
            'filters' => [
                'status' => $status,
                'type' => $documentType,
                'school_id' => $schoolId
            ],
            'schools' => $this->getSchoolOptions()
        ]);
    }
    
    /**
     * Document security audit
     */
    public function securityAudit()
    {
        // Admin only
        if (!$this->auth->hasRole('admin')) {
            return Response::json(['error' => 'Admin access required'], 403);
        }
        
        $auditType = Request::input('audit_type', 'access');
        $dateRange = Request::input('date_range', '30_days');
        
        switch ($auditType) {
            case 'access':
                $auditData = $this->getAccessAudit($dateRange);
                break;
            case 'security':
                $auditData = $this->getSecurityAudit($dateRange);
                break;
            case 'compliance':
                $auditData = $this->getComplianceAudit($dateRange);
                break;
            default:
                $auditData = $this->getComprehensiveAudit($dateRange);
        }
        
        return $this->render('documents/security-audit', [
            'pageTitle' => 'Document Security Audit',
            'pageCSS' => ['/css/documents.css', '/css/security-audit.css'],
            'pageJS' => ['/js/security-audit.js'],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Document Management', 'url' => '/admin/documents'],
                ['title' => 'Security Audit', 'url' => '']
            ],
            'auditData' => $auditData,
            'auditType' => $auditType,
            'dateRange' => $dateRange
        ]);
    }
    
    /**
     * POPIA compliance report
     */
    public function popiaCompliance()
    {
        // Admin and compliance officers only
        if (!$this->auth->hasPermission('compliance.view')) {
            return Response::json(['error' => 'Permission denied'], 403);
        }
        
        $complianceReport = [
            'data_collection' => $this->getDataCollectionCompliance(),
            'consent_management' => $this->getConsentCompliance(),
            'data_security' => $this->getDataSecurityCompliance(),
            'retention_policies' => $this->getRetentionCompliance(),
            'access_rights' => $this->getAccessRightsCompliance(),
            'breach_incidents' => $this->getBreachIncidents(),
            'audit_trail' => $this->getAuditTrailCompliance()
        ];
        
        return $this->render('documents/popia-compliance', [
            'pageTitle' => 'POPIA Compliance Report',
            'pageCSS' => ['/css/documents.css', '/css/popia-compliance.css'],
            'pageJS' => ['/js/popia-compliance.js'],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Document Management', 'url' => '/admin/documents'],
                ['title' => 'POPIA Compliance', 'url' => '']
            ],
            'complianceReport' => $complianceReport,
            'generatedAt' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Statistical methods
    
    /**
     * Get document statistics based on user role
     */
    protected function getDocumentStatistics($user, $userRole)
    {
        $stats = [];
        
        if ($userRole === 'admin') {
            // System-wide statistics
            $stats = [
                'total_documents' => $this->getTotalDocumentCount(),
                'pending_verification' => $this->getPendingVerificationCount(),
                'compliance_issues' => $this->getComplianceIssuesCount(),
                'storage_usage' => $this->getStorageUsage(),
                'consent_forms' => $this->getConsentFormStats(),
                'medical_forms' => $this->getMedicalFormStats(),
                'id_documents' => $this->getIdDocumentStats(),
                'digital_signatures' => $this->getDigitalSignatureStats()
            ];
        } elseif ($userRole === 'school_coordinator') {
            // School-specific statistics
            $schoolId = $user->school_id;
            $stats = [
                'school_documents' => $this->getSchoolDocumentCount($schoolId),
                'pending_school_verification' => $this->getSchoolPendingCount($schoolId),
                'school_compliance' => $this->getSchoolComplianceRate($schoolId),
                'participant_completion' => $this->getParticipantCompletionRate($schoolId)
            ];
        } elseif ($userRole === 'team_coach') {
            // Team-specific statistics
            $teams = $this->getUserTeams($user->id);
            $stats = [
                'team_documents' => $this->getTeamDocumentCount($teams),
                'participant_documents' => $this->getTeamParticipantDocuments($teams),
                'completion_rate' => $this->getTeamCompletionRate($teams)
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get pending actions for the user
     */
    protected function getPendingActions($user, $userRole)
    {
        $actions = [];
        
        if ($userRole === 'admin') {
            $actions = [
                'documents_to_verify' => ConsentForm::getPendingReview(),
                'expired_documents' => ConsentForm::getExpiredForms(),
                'security_alerts' => $this->getSecurityAlerts(),
                'compliance_issues' => $this->getComplianceIssues()
            ];
        } elseif ($userRole === 'school_coordinator') {
            $actions = [
                'incomplete_participants' => $this->getIncompleteParticipants($user->school_id),
                'expiring_documents' => $this->getExpiringDocuments($user->school_id),
                'missing_signatures' => $this->getMissingSignatures($user->school_id)
            ];
        }
        
        return $actions;
    }
    
    /**
     * Get recent document activity
     */
    protected function getRecentActivity($user, $userRole)
    {
        $query = "
            SELECT 
                'consent_form' as type,
                cf.id,
                cf.status,
                cf.form_type,
                cf.updated_at,
                CONCAT(p.first_name, ' ', p.last_name) as participant_name,
                s.name as school_name,
                u.name as reviewer_name
            FROM consent_forms cf
            JOIN participants p ON cf.participant_id = p.id
            JOIN teams t ON p.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN users u ON cf.reviewed_by = u.id
            WHERE cf.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        
        if ($userRole === 'school_coordinator') {
            $query .= " AND s.id = " . $user->school_id;
        } elseif ($userRole === 'team_coach') {
            $teams = $this->getUserTeams($user->id);
            $teamIds = implode(',', array_map('intval', $teams));
            $query .= " AND t.id IN ($teamIds)";
        }
        
        $query .= " ORDER BY cf.updated_at DESC LIMIT 20";
        
        return $this->db->query($query)->fetchAll();
    }
    
    /**
     * Save digital signature
     */
    public function saveDigitalSignature()
    {
        try {
            // Check authentication
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                return Response::json(['error' => 'Invalid JSON data'], 400);
            }
            
            // Validate required fields
            $requiredFields = ['signature_data', 'signer_name', 'signer_role', 'document_id', 'document_type'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    return Response::json(['error' => "Missing required field: {$field}"], 400);
                }
            }
            
            // Create digital signature record
            $signatureData = [
                'document_id' => $input['document_id'],
                'document_type' => $input['document_type'],
                'signature_data' => $input['signature_data'],
                'method' => $input['signature_method'] ?? 'web_capture',
                'signer_name' => $input['signer_name'],
                'signer_email' => $input['signer_email'] ?? null,
                'signer_phone' => $input['signer_phone'] ?? null,
                'signer_role' => $input['signer_role'],
                'intent_statement' => $input['intent_statement'] ?? null,
                'signature_features' => $input['signature_features'] ?? [],
                'signature_bounds' => $input['signature_bounds'] ?? null,
                'device_fingerprint' => json_encode($input['device_info'] ?? []),
                'timestamp' => $input['timestamp'] ?? date('Y-m-d H:i:s'),
                'session_id' => $input['session_id'] ?? null,
                'geolocation' => $input['geolocation'] ?? null
            ];
            
            $digitalSignature = DigitalSignature::createSignature($signatureData);
            
            if ($digitalSignature) {
                // Log successful signature capture
                $this->logger->info("Digital signature captured", [
                    'signature_id' => $digitalSignature->id,
                    'document_id' => $input['document_id'],
                    'document_type' => $input['document_type'],
                    'signer_name' => $input['signer_name'],
                    'signer_role' => $input['signer_role']
                ]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'Digital signature saved successfully',
                    'signature' => [
                        'id' => $digitalSignature->id,
                        'verification_hash' => $digitalSignature->verification_hash,
                        'timestamp' => $digitalSignature->signature_timestamp,
                        'status' => $digitalSignature->verification_status
                    ]
                ]);
            } else {
                return Response::json(['error' => 'Failed to save digital signature'], 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Digital signature save error: ' . $e->getMessage());
            return Response::json(['error' => 'Failed to save digital signature: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Show digital signature capture form
     */
    public function showDigitalSignature()
    {
        // Check authentication
        if (!$this->auth->isLoggedIn()) {
            return $this->redirect('/auth/login');
        }
        
        $documentId = Request::input('document_id');
        $documentType = Request::input('document_type');
        
        if (!$documentId || !$documentType) {
            return $this->redirect('/admin/documents?error=missing_parameters');
        }
        
        // Get document information
        $document = $this->getDocumentInfo($documentId, $documentType);
        if (!$document) {
            return $this->redirect('/admin/documents?error=document_not_found');
        }
        
        return $this->render('documents/digital-signature', [
            'pageTitle' => 'Digital Signature Capture',
            'pageCSS' => ['/css/digital-signature.css'],
            'pageJS' => ['/js/digital-signature.js'],
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'Document Management', 'url' => '/admin/documents'],
                ['title' => 'Digital Signature', 'url' => '']
            ],
            'document' => $document
        ]);
    }
    
    /**
     * Get document information for signature capture
     */
    private function getDocumentInfo($documentId, $documentType)
    {
        try {
            switch ($documentType) {
                case 'consent_form':
                    $consentModel = new ConsentForm();
                    $document = $consentModel->find($documentId);
                    if ($document) {
                        // Get participant and school info
                        $participant = Participant::find($document->participant_id);
                        $team = $participant ? Team::find($participant->team_id) : null;
                        $school = $team ? School::find($team->school_id) : null;
                        
                        return [
                            'id' => $document->id,
                            'document_type' => 'consent_form',
                            'participant_name' => $participant ? "{$participant->first_name} {$participant->last_name}" : 'N/A',
                            'school_name' => $school ? $school->name : 'N/A',
                            'signer_role' => 'parent' // Default for consent forms
                        ];
                    }
                    break;
                    
                case 'medical_form':
                    $medicalModel = new MedicalInformation();
                    $document = $medicalModel->find($documentId);
                    if ($document) {
                        // Get participant and school info
                        $participant = Participant::find($document->participant_id);
                        $team = $participant ? Team::find($participant->team_id) : null;
                        $school = $team ? School::find($team->school_id) : null;
                        
                        return [
                            'id' => $document->id,
                            'document_type' => 'medical_form',
                            'participant_name' => $participant ? "{$participant->first_name} {$participant->last_name}" : 'N/A',
                            'school_name' => $school ? $school->name : 'N/A',
                            'signer_role' => 'parent' // Default for medical forms
                        ];
                    }
                    break;
                    
                case 'student_document':
                    $studentDocModel = new StudentDocument();
                    $document = $studentDocModel->find($documentId);
                    if ($document) {
                        // Get participant and school info
                        $participant = Participant::find($document->participant_id);
                        $team = $participant ? Team::find($participant->team_id) : null;
                        $school = $team ? School::find($team->school_id) : null;
                        
                        return [
                            'id' => $document->id,
                            'document_type' => 'student_document',
                            'participant_name' => $participant ? "{$participant->first_name} {$participant->last_name}" : 'N/A',
                            'school_name' => $school ? $school->name : 'N/A',
                            'signer_role' => 'participant' // Default for student documents
                        ];
                    }
                    break;
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logger->error('Error getting document info: ' . $e->getMessage());
            return null;
        }
    }

    // Helper methods for statistics
    
    protected function getTotalDocumentCount()
    {
        return $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM consent_forms WHERE deleted_at IS NULL) +
                (SELECT COUNT(*) FROM medical_information WHERE deleted_at IS NULL) +
                (SELECT COUNT(*) FROM student_documents WHERE deleted_at IS NULL) as total
        ")->fetch()['total'];
    }
    
    protected function getPendingVerificationCount()
    {
        return $this->db->query("
            SELECT COUNT(*) as count 
            FROM consent_forms 
            WHERE status = 'pending' AND deleted_at IS NULL
        ")->fetch()['count'];
    }
    
    protected function getComplianceIssuesCount()
    {
        // Count various compliance issues
        return $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM consent_forms WHERE status = 'expired') +
                (SELECT COUNT(*) FROM medical_information WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)) +
                (SELECT COUNT(*) FROM digital_signatures WHERE verification_status = 'failed') as total
        ")->fetch()['total'];
    }
    
    protected function getStorageUsage()
    {
        $result = $this->db->query("
            SELECT SUM(file_size) as total_size 
            FROM uploaded_files 
            WHERE deleted_at IS NULL
        ")->fetch();
        
        $totalBytes = $result['total_size'] ?? 0;
        
        return [
            'bytes' => $totalBytes,
            'formatted' => $this->formatBytes($totalBytes),
            'percentage' => $this->calculateStoragePercentage($totalBytes)
        ];
    }
    
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    protected function calculateStoragePercentage($bytes)
    {
        $maxStorage = 50 * 1024 * 1024 * 1024; // 50GB limit
        return ($bytes / $maxStorage) * 100;
    }
    
    // Audit and compliance methods will be expanded in subsequent parts...
}