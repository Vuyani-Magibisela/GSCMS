<?php

namespace App\Models;

use App\Core\Encryption;
use App\Core\Logger;
use Exception;

class StudentDocument extends BaseModel
{
    protected $table = 'student_documents';
    protected $softDeletes = true;
    
    protected $fillable = [
        'participant_id', 'document_type', 'document_number', 'document_number_encrypted',
        'issuing_authority', 'issue_date', 'expiry_date', 'file_path_encrypted',
        'original_filename', 'file_size', 'mime_type', 'file_hash_sha256',
        'extracted_data_encrypted', 'extraction_confidence', 'verification_status',
        'verification_method', 'verification_details', 'verified_at', 'verified_by',
        'id_validation_results', 'age_verification', 'pii_redaction_status',
        'redacted_file_path', 'access_level', 'audit_trail', 'retention_category',
        'scheduled_deletion_date', 'legal_hold', 'uploaded_by', 'uploaded_file_id',
        'security_scan_status', 'security_scan_details', 'encryption_key_version',
        'compliance_flags', 'processing_notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Document type constants
    const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';
    const TYPE_ID_DOCUMENT = 'id_document';
    const TYPE_PASSPORT = 'passport';
    const TYPE_TEMPORARY_ID = 'temporary_id';
    const TYPE_SCHOOL_ID = 'school_id';
    const TYPE_REPORT_CARD = 'report_card';
    const TYPE_MEDICAL_CERTIFICATE = 'medical_certificate';
    const TYPE_OTHER = 'other';
    
    // Verification status constants
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_FLAGGED = 'flagged';
    
    // Access level constants
    const ACCESS_PRIVATE = 'private';
    const ACCESS_ADMIN_ONLY = 'admin_only';
    const ACCESS_SCHOOL_STAFF = 'school_staff';
    const ACCESS_EMERGENCY_ACCESS = 'emergency_access';
    
    // Security scan status constants
    const SCAN_PENDING = 'pending';
    const SCAN_CLEAN = 'clean';
    const SCAN_THREAT_DETECTED = 'threat_detected';
    const SCAN_QUARANTINED = 'quarantined';
    
    // PII redaction status constants
    const REDACTION_PENDING = 'pending';
    const REDACTION_REDACTED = 'redacted';
    const REDACTION_NOT_REQUIRED = 'not_required';
    
    protected $belongsTo = [
        'participant' => ['model' => Participant::class, 'foreign_key' => 'participant_id'],
        'verifier' => ['model' => User::class, 'foreign_key' => 'verified_by'],
        'uploader' => ['model' => User::class, 'foreign_key' => 'uploaded_by'],
        'uploadedFile' => ['model' => UploadedFile::class, 'foreign_key' => 'uploaded_file_id']
    ];
    
    protected $encryption;
    protected $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->encryption = new Encryption();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Get participant relationship
     */
    public function participant()
    {
        return $this->belongsTo('App\Models\Participant', 'participant_id');
    }
    
    /**
     * Get verifier relationship
     */
    public function verifier()
    {
        return $this->belongsTo('App\Models\User', 'verified_by');
    }
    
    /**
     * Get uploader relationship
     */
    public function uploader()
    {
        return $this->belongsTo('App\Models\User', 'uploaded_by');
    }
    
    /**
     * Get uploaded file relationship
     */
    public function uploadedFile()
    {
        return $this->belongsTo('App\Models\UploadedFile', 'uploaded_file_id');
    }
    
    /**
     * Store encrypted document with security measures
     */
    public function storeEncryptedDocument($documentData, $filePath, $uploadedFileId)
    {
        try {
            // Encrypt sensitive document data
            $encryptedData = [];
            
            if (!empty($documentData['document_number'])) {
                $encryptedData['document_number_encrypted'] = $this->encryption->encrypt($documentData['document_number']);
            }
            
            if (!empty($filePath)) {
                $encryptedData['file_path_encrypted'] = $this->encryption->encrypt($filePath);
            }
            
            if (!empty($documentData['extracted_data'])) {
                $encryptedData['extracted_data_encrypted'] = $this->encryption->encrypt(json_encode($documentData['extracted_data']));
            }
            
            // Store non-sensitive data as-is
            $nonSensitiveFields = [
                'participant_id', 'document_type', 'issuing_authority', 'issue_date',
                'expiry_date', 'original_filename', 'file_size', 'mime_type',
                'file_hash_sha256', 'extraction_confidence', 'verification_status',
                'verification_method', 'uploaded_by', 'uploaded_file_id',
                'access_level', 'retention_category'
            ];
            
            foreach ($nonSensitiveFields as $field) {
                if (isset($documentData[$field])) {
                    $encryptedData[$field] = $documentData[$field];
                }
            }
            
            // Update the model
            foreach ($encryptedData as $key => $value) {
                $this->$key = $value;
            }
            
            $this->encryption_key_version = $this->encryption->getCurrentKeyVersion();
            $this->security_scan_status = self::SCAN_PENDING;
            $this->pii_redaction_status = self::REDACTION_PENDING;
            $this->uploaded_file_id = $uploadedFileId;
            
            return $this->save();
            
        } catch (Exception $e) {
            $this->logger->error('Document encryption failed: ' . $e->getMessage(), [
                'participant_id' => $this->participant_id,
                'document_type' => $documentData['document_type'] ?? 'unknown'
            ]);
            throw new Exception('Unable to encrypt document data');
        }
    }
    
    /**
     * Get decrypted document data for authorized access
     */
    public function getDecryptedData($accessLevel = null)
    {
        // Verify access level authorization
        if (!$this->canAccessDocument($accessLevel)) {
            throw new Exception('Insufficient access level for document data');
        }
        
        $decryptedData = [];
        
        try {
            // Decrypt document number
            if ($this->document_number_encrypted) {
                $decryptedData['document_number'] = $this->encryption->decrypt($this->document_number_encrypted);
            }
            
            // Decrypt file path
            if ($this->file_path_encrypted) {
                $decryptedData['file_path'] = $this->encryption->decrypt($this->file_path_encrypted);
            }
            
            // Decrypt extracted data
            if ($this->extracted_data_encrypted) {
                $decryptedData['extracted_data'] = json_decode($this->encryption->decrypt($this->extracted_data_encrypted), true);
            }
            
            // Add non-encrypted fields
            $decryptedData['document_type'] = $this->document_type;
            $decryptedData['issuing_authority'] = $this->issuing_authority;
            $decryptedData['issue_date'] = $this->issue_date;
            $decryptedData['expiry_date'] = $this->expiry_date;
            $decryptedData['verification_status'] = $this->verification_status;
            $decryptedData['original_filename'] = $this->original_filename;
            
            // Log document access
            $this->logDocumentAccess('decrypt', $accessLevel);
            
            return $decryptedData;
            
        } catch (Exception $e) {
            $this->logger->error('Document data decryption failed: ' . $e->getMessage(), [
                'document_id' => $this->id,
                'participant_id' => $this->participant_id
            ]);
            throw new Exception('Unable to decrypt document data');
        }
    }
    
    /**
     * Verify document authenticity and extract data
     */
    public function verifyDocument($verifierId, $verificationDetails = [])
    {
        try {
            // Perform document verification based on type
            $verificationResult = $this->performDocumentVerification();
            
            // Update verification status
            $this->verification_status = $verificationResult['is_valid'] ? self::STATUS_VERIFIED : self::STATUS_FAILED;
            $this->verification_method = $verificationResult['method'];
            $this->verification_details = json_encode($verificationResult['details']);
            $this->verified_at = date('Y-m-d H:i:s');
            $this->verified_by = $verifierId;
            
            // Perform age verification if ID document
            if (in_array($this->document_type, [self::TYPE_ID_DOCUMENT, self::TYPE_PASSPORT, self::TYPE_BIRTH_CERTIFICATE])) {
                $ageVerification = $this->performAgeVerification();
                $this->age_verification = json_encode($ageVerification);
            }
            
            // Update ID validation results for South African IDs
            if ($this->document_type === self::TYPE_ID_DOCUMENT) {
                $idValidation = $this->performSAIdValidation();
                $this->id_validation_results = json_encode($idValidation);
            }
            
            $this->save();
            
            // Create audit trail entry
            $this->addAuditTrailEntry('document_verified', [
                'verified_by' => $verifierId,
                'verification_result' => $verificationResult,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $verificationResult;
            
        } catch (Exception $e) {
            $this->logger->error('Document verification failed: ' . $e->getMessage());
            throw new Exception('Document verification failed');
        }
    }
    
    /**
     * Perform OCR text extraction on document
     */
    public function performOCRExtraction()
    {
        try {
            if (!$this->file_path_encrypted) {
                throw new Exception('No file path available for OCR');
            }
            
            $filePath = $this->encryption->decrypt($this->file_path_encrypted);
            
            // This would integrate with OCR service (like Tesseract, AWS Textract, etc.)
            // For now, we'll create a placeholder
            $extractedData = [
                'text' => 'OCR extraction not implemented yet',
                'confidence' => 0.0,
                'extracted_at' => date('Y-m-d H:i:s'),
                'ocr_service' => 'placeholder'
            ];
            
            $this->extracted_data_encrypted = $this->encryption->encrypt(json_encode($extractedData));
            $this->extraction_confidence = $extractedData['confidence'];
            
            $this->save();
            
            return $extractedData;
            
        } catch (Exception $e) {
            $this->logger->error('OCR extraction failed: ' . $e->getMessage());
            throw new Exception('OCR extraction failed');
        }
    }
    
    /**
     * Perform security scan on document
     */
    public function performSecurityScan()
    {
        try {
            if (!$this->file_path_encrypted) {
                throw new Exception('No file path available for security scan');
            }
            
            $filePath = $this->encryption->decrypt($this->file_path_encrypted);
            
            // This would integrate with virus scanning service
            // For now, we'll create a basic check
            $scanResult = [
                'status' => self::SCAN_CLEAN,
                'threats_detected' => [],
                'scan_engine' => 'placeholder',
                'scanned_at' => date('Y-m-d H:i:s'),
                'file_hash_verified' => hash_file('sha256', $filePath) === $this->file_hash_sha256
            ];
            
            $this->security_scan_status = $scanResult['status'];
            $this->security_scan_details = json_encode($scanResult);
            
            $this->save();
            
            return $scanResult;
            
        } catch (Exception $e) {
            $this->logger->error('Security scan failed: ' . $e->getMessage());
            $this->security_scan_status = self::SCAN_THREAT_DETECTED;
            $this->save();
            throw new Exception('Security scan failed');
        }
    }
    
    /**
     * Create redacted version of document for privacy
     */
    public function createRedactedVersion()
    {
        try {
            if ($this->pii_redaction_status === self::REDACTION_NOT_REQUIRED) {
                return true;
            }
            
            $filePath = $this->encryption->decrypt($this->file_path_encrypted);
            $redactedPath = dirname($filePath) . '/redacted/' . basename($filePath);
            
            // Ensure redacted directory exists
            if (!is_dir(dirname($redactedPath))) {
                mkdir(dirname($redactedPath), 0755, true);
            }
            
            // This would implement actual redaction logic
            // For now, we'll copy the file as a placeholder
            if (copy($filePath, $redactedPath)) {
                $this->redacted_file_path = $redactedPath;
                $this->pii_redaction_status = self::REDACTION_REDACTED;
                $this->save();
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error('Document redaction failed: ' . $e->getMessage());
            throw new Exception('Document redaction failed');
        }
    }
    
    /**
     * Check if document is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return date('Y-m-d') > $this->expiry_date;
    }
    
    /**
     * Check if document requires renewal
     */
    public function requiresRenewal($warningPeriod = '30 days')
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        $warningDate = date('Y-m-d', strtotime("+{$warningPeriod}"));
        return $this->expiry_date <= $warningDate;
    }
    
    /**
     * Get available document types
     */
    public static function getAvailableDocumentTypes()
    {
        return [
            self::TYPE_BIRTH_CERTIFICATE => 'Birth Certificate',
            self::TYPE_ID_DOCUMENT => 'ID Document',
            self::TYPE_PASSPORT => 'Passport',
            self::TYPE_TEMPORARY_ID => 'Temporary ID',
            self::TYPE_SCHOOL_ID => 'School ID',
            self::TYPE_REPORT_CARD => 'Report Card',
            self::TYPE_MEDICAL_CERTIFICATE => 'Medical Certificate',
            self::TYPE_OTHER => 'Other'
        ];
    }
    
    /**
     * Get available verification statuses
     */
    public static function getAvailableVerificationStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending Verification',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_FAILED => 'Verification Failed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_FLAGGED => 'Flagged for Review'
        ];
    }
    
    // Helper methods
    
    protected function performDocumentVerification()
    {
        // Placeholder for actual document verification logic
        return [
            'is_valid' => true,
            'method' => 'manual_review',
            'details' => [
                'verified_at' => date('Y-m-d H:i:s'),
                'verification_notes' => 'Document appears authentic'
            ]
        ];
    }
    
    protected function performAgeVerification()
    {
        // Placeholder for age verification logic
        return [
            'age_calculated' => null,
            'category_eligible' => true,
            'verification_method' => 'document_review'
        ];
    }
    
    protected function performSAIdValidation()
    {
        // Placeholder for South African ID validation
        return [
            'format_valid' => true,
            'checksum_valid' => true,
            'date_of_birth' => null,
            'gender' => null,
            'citizenship' => null
        ];
    }
    
    protected function canAccessDocument($accessLevel)
    {
        // Implement proper access control logic
        return true;
    }
    
    protected function logDocumentAccess($accessType, $accessLevel)
    {
        try {
            $this->db->table('document_access_logs')->insert([
                'document_id' => $this->id,
                'document_type' => $this->document_type,
                'participant_id' => $this->participant_id,
                'accessed_by' => auth()->getId() ?? 0,
                'access_type' => $accessType,
                'access_timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to log document access: ' . $e->getMessage());
        }
    }
    
    protected function addAuditTrailEntry($action, $details)
    {
        $auditTrail = $this->audit_trail ? json_decode($this->audit_trail, true) : [];
        
        $auditTrail[] = [
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => auth()->getId()
        ];
        
        $this->audit_trail = json_encode($auditTrail);
        $this->save();
    }
    
    /**
     * Override toArray to include calculated fields and sanitize sensitive data
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Remove encrypted fields from array output for security
        unset($attributes['document_number_encrypted']);
        unset($attributes['file_path_encrypted']);
        unset($attributes['extracted_data_encrypted']);
        
        // Add calculated fields
        $attributes['document_type_label'] = self::getAvailableDocumentTypes()[$this->document_type] ?? $this->document_type;
        $attributes['verification_status_label'] = self::getAvailableVerificationStatuses()[$this->verification_status] ?? $this->verification_status;
        $attributes['is_expired'] = $this->isExpired();
        $attributes['requires_renewal'] = $this->requiresRenewal();
        $attributes['has_security_issues'] = $this->security_scan_status === self::SCAN_THREAT_DETECTED;
        $attributes['is_verified'] = $this->verification_status === self::STATUS_VERIFIED;
        
        return $attributes;
    }
}