<?php

namespace App\Models;

use App\Core\Encryption;
use App\Core\Logger;
use Exception;

class DigitalSignature extends BaseModel
{
    protected $table = 'digital_signatures';
    protected $softDeletes = true;
    
    protected $fillable = [
        'document_id', 'document_type', 'signature_data_encrypted', 'signature_method',
        'signer_name', 'signer_email', 'signer_phone', 'signer_role', 'signer_ip_address',
        'signer_user_agent', 'signer_device_fingerprint', 'signature_timestamp',
        'intent_statement', 'witness_name', 'witness_email', 'witness_signature_data',
        'biometric_data_encrypted', 'signature_features', 'signature_quality_score',
        'signature_bounds', 'verification_status', 'verification_method',
        'verification_details', 'verification_hash', 'non_repudiation_hash',
        'verified_at', 'verified_by', 'legal_binding_confirmed',
        'electronic_signature_act_compliance', 'popia_compliance', 'audit_trail',
        'certificate_chain', 'timestamp_authority', 'docusign_envelope_id',
        'docusign_status', 'adobe_sign_agreement_id', 'external_signature_id',
        'original_filename', 'signature_image_path', 'signed_document_hash',
        'signing_session_id', 'geolocation', 'is_valid', 'invalidation_reason',
        'invalidated_at', 'invalidated_by', 'retention_date', 'legal_hold',
        'encryption_key_version', 'compliance_metadata'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Document type constants
    const DOCUMENT_CONSENT_FORM = 'consent_form';
    const DOCUMENT_MEDICAL_FORM = 'medical_form';
    const DOCUMENT_CONTRACT = 'contract';
    const DOCUMENT_AGREEMENT = 'agreement';
    const DOCUMENT_WAIVER = 'waiver';
    
    // Signature method constants
    const METHOD_WEB_CAPTURE = 'web_capture';
    const METHOD_IMAGE_UPLOAD = 'image_upload';
    const METHOD_DOCUSIGN = 'docusign';
    const METHOD_ADOBE_SIGN = 'adobe_sign';
    const METHOD_BIOMETRIC = 'biometric';
    const METHOD_DIGITAL_CERTIFICATE = 'digital_certificate';
    
    // Signer role constants
    const ROLE_PARENT = 'parent';
    const ROLE_GUARDIAN = 'guardian';
    const ROLE_PARTICIPANT = 'participant';
    const ROLE_WITNESS = 'witness';
    const ROLE_OFFICIAL = 'official';
    const ROLE_LEGAL_REPRESENTATIVE = 'legal_representative';
    
    // Verification status constants
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';
    
    protected $belongsTo = [
        'verifier' => ['model' => User::class, 'foreign_key' => 'verified_by'],
        'invalidator' => ['model' => User::class, 'foreign_key' => 'invalidated_by']
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
     * Get verifier relationship
     */
    public function verifier()
    {
        return $this->belongsTo('App\Models\User', 'verified_by');
    }
    
    /**
     * Get invalidator relationship
     */
    public function invalidator()
    {
        return $this->belongsTo('App\Models\User', 'invalidated_by');
    }
    
    /**
     * Create digital signature record
     */
    public static function createSignature($signatureData)
    {
        try {
            $model = new static();
            
            // Encrypt sensitive signature data
            $encryptedSignatureData = $model->encryption->encrypt($signatureData['signature_data']);
            
            // Generate hashes for integrity and non-repudiation
            $verificationHash = hash('sha256', $encryptedSignatureData . $signatureData['timestamp']);
            $nonRepudiationHash = hash('sha256', $signatureData['signer_name'] . $signatureData['document_id'] . $signatureData['timestamp']);
            
            // Create signature record
            $signature = $model->create([
                'document_id' => $signatureData['document_id'],
                'document_type' => $signatureData['document_type'],
                'signature_data_encrypted' => $encryptedSignatureData,
                'signature_method' => $signatureData['method'],
                'signer_name' => $signatureData['signer_name'],
                'signer_email' => $signatureData['signer_email'] ?? null,
                'signer_phone' => $signatureData['signer_phone'] ?? null,
                'signer_role' => $signatureData['signer_role'],
                'signer_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'signer_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'signer_device_fingerprint' => $signatureData['device_fingerprint'] ?? null,
                'signature_timestamp' => $signatureData['timestamp'] ?? date('Y-m-d H:i:s'),
                'intent_statement' => $signatureData['intent_statement'] ?? null,
                'signature_features' => json_encode($signatureData['signature_features'] ?? []),
                'signature_quality_score' => $signatureData['quality_score'] ?? null,
                'signature_bounds' => json_encode($signatureData['signature_bounds'] ?? []),
                'verification_hash' => $verificationHash,
                'non_repudiation_hash' => $nonRepudiationHash,
                'verification_status' => self::STATUS_PENDING,
                'electronic_signature_act_compliance' => true,
                'popia_compliance' => true,
                'signed_document_hash' => $signatureData['document_hash'] ?? null,
                'signing_session_id' => $signatureData['session_id'] ?? uniqid('sign-'),
                'geolocation' => json_encode($signatureData['geolocation'] ?? null),
                'is_valid' => true,
                'encryption_key_version' => $model->encryption->getCurrentKeyVersion(),
                'compliance_metadata' => json_encode($signatureData['compliance_metadata'] ?? [])
            ]);
            
            // Create initial audit trail entry
            $signature->addAuditTrailEntry('signature_created', [
                'signer_name' => $signatureData['signer_name'],
                'document_type' => $signatureData['document_type'],
                'signature_method' => $signatureData['method'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $signature;
            
        } catch (Exception $e) {
            Logger::getInstance()->error('Digital signature creation failed: ' . $e->getMessage());
            throw new Exception('Failed to create digital signature');
        }
    }
    
    /**
     * Verify digital signature
     */
    public function verifySignature($verifiedBy)
    {
        try {
            $verificationResult = $this->performSignatureVerification();
            
            $this->verification_status = $verificationResult['is_valid'] ? self::STATUS_VERIFIED : self::STATUS_FAILED;
            $this->verification_method = $verificationResult['method'];
            $this->verification_details = json_encode($verificationResult['details']);
            $this->verified_at = date('Y-m-d H:i:s');
            $this->verified_by = $verifiedBy;
            
            if ($verificationResult['is_valid']) {
                $this->legal_binding_confirmed = true;
            }
            
            $this->save();
            
            // Add audit trail entry
            $this->addAuditTrailEntry('signature_verified', [
                'verified_by' => $verifiedBy,
                'verification_result' => $verificationResult,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $verificationResult;
            
        } catch (Exception $e) {
            $this->logger->error('Signature verification failed: ' . $e->getMessage());
            $this->verification_status = self::STATUS_FAILED;
            $this->save();
            throw new Exception('Signature verification failed');
        }
    }
    
    /**
     * Get decrypted signature data for authorized access
     */
    public function getDecryptedSignatureData()
    {
        try {
            if (!$this->signature_data_encrypted) {
                throw new Exception('No signature data available');
            }
            
            $decryptedData = $this->encryption->decrypt($this->signature_data_encrypted);
            
            // Log signature data access
            $this->logSignatureAccess('decrypt');
            
            return $decryptedData;
            
        } catch (Exception $e) {
            $this->logger->error('Signature data decryption failed: ' . $e->getMessage());
            throw new Exception('Unable to decrypt signature data');
        }
    }
    
    /**
     * Get signature image for display
     */
    public function getSignatureImage()
    {
        try {
            if ($this->signature_image_path && file_exists($this->signature_image_path)) {
                return $this->signature_image_path;
            }
            
            // Generate image from signature data if needed
            $signatureData = $this->getDecryptedSignatureData();
            
            // This would convert signature strokes to image
            // For now, return a placeholder
            return null;
            
        } catch (Exception $e) {
            $this->logger->error('Signature image retrieval failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate signature integrity
     */
    public function validateIntegrity()
    {
        try {
            // Check verification hash
            $currentHash = hash('sha256', $this->signature_data_encrypted . $this->signature_timestamp);
            
            if ($currentHash !== $this->verification_hash) {
                $this->invalidateSignature('Signature integrity check failed', auth()->getId());
                return false;
            }
            
            // Check non-repudiation hash
            $currentNonRepudiationHash = hash('sha256', $this->signer_name . $this->document_id . $this->signature_timestamp);
            
            if ($currentNonRepudiationHash !== $this->non_repudiation_hash) {
                $this->invalidateSignature('Non-repudiation check failed', auth()->getId());
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Signature integrity validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Invalidate signature
     */
    public function invalidateSignature($reason, $invalidatedBy)
    {
        $this->is_valid = false;
        $this->invalidation_reason = $reason;
        $this->invalidated_at = date('Y-m-d H:i:s');
        $this->invalidated_by = $invalidatedBy;
        $this->verification_status = self::STATUS_REVOKED;
        
        $this->save();
        
        // Add audit trail entry
        $this->addAuditTrailEntry('signature_invalidated', [
            'reason' => $reason,
            'invalidated_by' => $invalidatedBy,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Log signature invalidation
        $this->logger->warning("Digital signature invalidated", [
            'signature_id' => $this->id,
            'document_id' => $this->document_id,
            'reason' => $reason,
            'invalidated_by' => $invalidatedBy
        ]);
    }
    
    /**
     * Check if signature is expired
     */
    public function isExpired($validityPeriod = '2 years')
    {
        if (!$this->signature_timestamp) {
            return false;
        }
        
        $expiryDate = date('Y-m-d H:i:s', strtotime($this->signature_timestamp . " +{$validityPeriod}"));
        return date('Y-m-d H:i:s') > $expiryDate;
    }
    
    /**
     * Get legal status of signature
     */
    public function getLegalStatus()
    {
        $status = [
            'is_valid' => $this->is_valid,
            'is_verified' => $this->verification_status === self::STATUS_VERIFIED,
            'is_expired' => $this->isExpired(),
            'legal_binding_confirmed' => $this->legal_binding_confirmed,
            'compliance_status' => [
                'electronic_signature_act' => $this->electronic_signature_act_compliance,
                'popia' => $this->popia_compliance
            ],
            'integrity_valid' => $this->validateIntegrity(),
            'legal_hold' => $this->legal_hold
        ];
        
        $status['legally_binding'] = $status['is_valid'] && 
                                   $status['is_verified'] && 
                                   !$status['is_expired'] && 
                                   $status['legal_binding_confirmed'] && 
                                   $status['integrity_valid'];
        
        return $status;
    }
    
    /**
     * Generate signature certificate
     */
    public function generateSignatureCertificate()
    {
        $legalStatus = $this->getLegalStatus();
        
        return [
            'signature_id' => $this->id,
            'document_id' => $this->document_id,
            'document_type' => $this->document_type,
            'signer_name' => $this->signer_name,
            'signer_role' => $this->signer_role,
            'signature_timestamp' => $this->signature_timestamp,
            'verification_status' => $this->verification_status,
            'verified_at' => $this->verified_at,
            'legal_status' => $legalStatus,
            'compliance_confirmed' => $legalStatus['legally_binding'],
            'certificate_generated_at' => date('Y-m-d H:i:s'),
            'certificate_id' => uniqid('CERT-'),
            'verification_hash' => $this->verification_hash,
            'non_repudiation_hash' => $this->non_repudiation_hash
        ];
    }
    
    /**
     * Get signatures for document
     */
    public static function getForDocument($documentId, $documentType)
    {
        $model = new static();
        return $model->db->table($model->table)
            ->where('document_id', $documentId)
            ->where('document_type', $documentType)
            ->where('is_valid', true)
            ->whereNull('deleted_at')
            ->orderBy('signature_timestamp')
            ->get();
    }
    
    /**
     * Get signatures by signer
     */
    public static function getBySigner($signerEmail = null, $signerName = null)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->whereNull('deleted_at')
            ->orderBy('signature_timestamp', 'DESC');
            
        if ($signerEmail) {
            $query->where('signer_email', $signerEmail);
        }
        
        if ($signerName) {
            $query->where('signer_name', 'LIKE', "%{$signerName}%");
        }
        
        return $query->get();
    }
    
    /**
     * Get available document types
     */
    public static function getAvailableDocumentTypes()
    {
        return [
            self::DOCUMENT_CONSENT_FORM => 'Consent Form',
            self::DOCUMENT_MEDICAL_FORM => 'Medical Form',
            self::DOCUMENT_CONTRACT => 'Contract',
            self::DOCUMENT_AGREEMENT => 'Agreement',
            self::DOCUMENT_WAIVER => 'Waiver'
        ];
    }
    
    /**
     * Get available signature methods
     */
    public static function getAvailableSignatureMethods()
    {
        return [
            self::METHOD_WEB_CAPTURE => 'Web Capture',
            self::METHOD_IMAGE_UPLOAD => 'Image Upload',
            self::METHOD_DOCUSIGN => 'DocuSign',
            self::METHOD_ADOBE_SIGN => 'Adobe Sign',
            self::METHOD_BIOMETRIC => 'Biometric',
            self::METHOD_DIGITAL_CERTIFICATE => 'Digital Certificate'
        ];
    }
    
    /**
     * Get available signer roles
     */
    public static function getAvailableSignerRoles()
    {
        return [
            self::ROLE_PARENT => 'Parent',
            self::ROLE_GUARDIAN => 'Guardian',
            self::ROLE_PARTICIPANT => 'Participant',
            self::ROLE_WITNESS => 'Witness',
            self::ROLE_OFFICIAL => 'Official',
            self::ROLE_LEGAL_REPRESENTATIVE => 'Legal Representative'
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
            self::STATUS_REVOKED => 'Revoked'
        ];
    }
    
    // Helper methods
    
    protected function performSignatureVerification()
    {
        // Placeholder for actual signature verification logic
        // This would implement various verification methods
        return [
            'is_valid' => true,
            'method' => 'digital_verification',
            'details' => [
                'integrity_check' => $this->validateIntegrity(),
                'timestamp_valid' => !$this->isExpired(),
                'compliance_check' => true,
                'verified_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    protected function logSignatureAccess($accessType)
    {
        try {
            $this->db->table('signature_access_logs')->insert([
                'signature_id' => $this->id,
                'document_id' => $this->document_id,
                'accessed_by' => auth()->getId() ?? 0,
                'access_type' => $accessType,
                'access_timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to log signature access: ' . $e->getMessage());
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
        unset($attributes['signature_data_encrypted']);
        unset($attributes['biometric_data_encrypted']);
        
        // Add calculated fields
        $attributes['document_type_label'] = self::getAvailableDocumentTypes()[$this->document_type] ?? $this->document_type;
        $attributes['signature_method_label'] = self::getAvailableSignatureMethods()[$this->signature_method] ?? $this->signature_method;
        $attributes['signer_role_label'] = self::getAvailableSignerRoles()[$this->signer_role] ?? $this->signer_role;
        $attributes['verification_status_label'] = self::getAvailableVerificationStatuses()[$this->verification_status] ?? $this->verification_status;
        $attributes['is_expired'] = $this->isExpired();
        $attributes['legal_status'] = $this->getLegalStatus();
        $attributes['integrity_valid'] = $this->validateIntegrity();
        
        return $attributes;
    }
}