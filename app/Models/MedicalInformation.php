<?php

namespace App\Models;

use App\Core\Encryption;
use App\Core\Logger;
use Exception;

class MedicalInformation extends BaseModel
{
    protected $table = 'medical_information';
    protected $softDeletes = true;
    
    protected $fillable = [
        'participant_id', 'allergies_encrypted', 'medical_conditions_encrypted',
        'medications_encrypted', 'medical_aid_info', 'medical_aid_number_encrypted',
        'dietary_requirements', 'physical_limitations', 'learning_difficulties',
        'accessibility_needs', 'behavioral_support', 'additional_supervision',
        'special_instructions', 'emergency_instructions_encrypted',
        'consent_medical_treatment', 'consent_medication_admin', 'data_sharing_consent',
        'validation_status', 'validation_notes', 'validated_at', 'validated_by',
        'last_updated_by', 'access_level', 'retention_date', 'encryption_key_version'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation status constants
    const STATUS_PENDING = 'pending';
    const STATUS_VALIDATED = 'validated';
    const STATUS_REQUIRES_REVIEW = 'requires_review';
    const STATUS_REJECTED = 'rejected';
    
    // Access level constants
    const ACCESS_PRIVATE = 'private';
    const ACCESS_MEDICAL_STAFF = 'medical_staff';
    const ACCESS_EMERGENCY_ONLY = 'emergency_only';
    const ACCESS_AUTHORIZED_PERSONNEL = 'authorized_personnel';
    
    protected $belongsTo = [
        'participant' => ['model' => Participant::class, 'foreign_key' => 'participant_id'],
        'validator' => ['model' => User::class, 'foreign_key' => 'validated_by'],
        'lastUpdater' => ['model' => User::class, 'foreign_key' => 'last_updated_by']
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
     * Get validator relationship
     */
    public function validator()
    {
        return $this->belongsTo('App\Models\User', 'validated_by');
    }
    
    /**
     * Get last updater relationship
     */
    public function lastUpdater()
    {
        return $this->belongsTo('App\Models\User', 'last_updated_by');
    }
    
    /**
     * Decrypt sensitive medical data for authorized access
     */
    public function getDecryptedData($accessLevel = null)
    {
        // Verify access level authorization
        if (!$this->canAccessMedicalData($accessLevel)) {
            throw new Exception('Insufficient access level for medical data');
        }
        
        $decryptedData = [];
        
        try {
            // Decrypt allergies
            if ($this->allergies_encrypted) {
                $decryptedData['allergies'] = $this->encryption->decrypt($this->allergies_encrypted);
            }
            
            // Decrypt medical conditions
            if ($this->medical_conditions_encrypted) {
                $decryptedData['medical_conditions'] = $this->encryption->decrypt($this->medical_conditions_encrypted);
            }
            
            // Decrypt medications
            if ($this->medications_encrypted) {
                $decryptedData['medications'] = $this->encryption->decrypt($this->medications_encrypted);
            }
            
            // Decrypt medical aid number
            if ($this->medical_aid_number_encrypted) {
                $decryptedData['medical_aid_number'] = $this->encryption->decrypt($this->medical_aid_number_encrypted);
            }
            
            // Decrypt emergency instructions
            if ($this->emergency_instructions_encrypted) {
                $decryptedData['emergency_instructions'] = $this->encryption->decrypt($this->emergency_instructions_encrypted);
            }
            
            // Add non-encrypted fields
            $decryptedData['medical_aid_info'] = $this->medical_aid_info;
            $decryptedData['dietary_requirements'] = $this->dietary_requirements;
            $decryptedData['physical_limitations'] = $this->physical_limitations;
            $decryptedData['learning_difficulties'] = $this->learning_difficulties;
            $decryptedData['accessibility_needs'] = $this->accessibility_needs;
            $decryptedData['behavioral_support'] = $this->behavioral_support;
            $decryptedData['additional_supervision'] = $this->additional_supervision;
            $decryptedData['special_instructions'] = $this->special_instructions;
            
            // Log medical data access
            $this->logMedicalDataAccess('decrypt', $accessLevel);
            
            return $decryptedData;
            
        } catch (Exception $e) {
            $this->logger->error('Medical data decryption failed: ' . $e->getMessage(), [
                'medical_info_id' => $this->id,
                'participant_id' => $this->participant_id
            ]);
            throw new Exception('Unable to decrypt medical data');
        }
    }
    
    /**
     * Encrypt and store sensitive medical data
     */
    public function storeEncryptedData($medicalData)
    {
        try {
            $encryptedData = [];
            
            // Encrypt allergies
            if (!empty($medicalData['allergies'])) {
                $encryptedData['allergies_encrypted'] = $this->encryption->encrypt($medicalData['allergies']);
            }
            
            // Encrypt medical conditions
            if (!empty($medicalData['medical_conditions'])) {
                $encryptedData['medical_conditions_encrypted'] = $this->encryption->encrypt($medicalData['medical_conditions']);
            }
            
            // Encrypt medications
            if (!empty($medicalData['medications'])) {
                $encryptedData['medications_encrypted'] = $this->encryption->encrypt($medicalData['medications']);
            }
            
            // Encrypt medical aid number
            if (!empty($medicalData['medical_aid_number'])) {
                $encryptedData['medical_aid_number_encrypted'] = $this->encryption->encrypt($medicalData['medical_aid_number']);
            }
            
            // Encrypt emergency instructions
            if (!empty($medicalData['emergency_instructions'])) {
                $encryptedData['emergency_instructions_encrypted'] = $this->encryption->encrypt($medicalData['emergency_instructions']);
            }
            
            // Store non-sensitive data as-is
            $nonSensitiveFields = [
                'medical_aid_info', 'dietary_requirements', 'physical_limitations',
                'learning_difficulties', 'accessibility_needs', 'behavioral_support',
                'additional_supervision', 'special_instructions', 'consent_medical_treatment',
                'consent_medication_admin', 'data_sharing_consent'
            ];
            
            foreach ($nonSensitiveFields as $field) {
                if (isset($medicalData[$field])) {
                    $encryptedData[$field] = $medicalData[$field];
                }
            }
            
            // Update the model
            foreach ($encryptedData as $key => $value) {
                $this->$key = $value;
            }
            
            $this->encryption_key_version = $this->encryption->getCurrentKeyVersion();
            $this->last_updated_by = auth()->getId();
            
            return $this->save();
            
        } catch (Exception $e) {
            $this->logger->error('Medical data encryption failed: ' . $e->getMessage(), [
                'participant_id' => $this->participant_id
            ]);
            throw new Exception('Unable to encrypt medical data');
        }
    }
    
    /**
     * Get critical allergy information for emergency situations
     */
    public function getCriticalAllergies()
    {
        try {
            if (!$this->allergies_encrypted) {
                return [];
            }
            
            $allergies = json_decode($this->encryption->decrypt($this->allergies_encrypted), true);
            
            if (!is_array($allergies)) {
                return [];
            }
            
            // Filter for high-severity and life-threatening allergies
            $criticalAllergies = array_filter($allergies, function($allergy) {
                return in_array($allergy['severity'] ?? '', ['severe', 'life_threatening', 'anaphylaxis']);
            });
            
            // Log emergency access
            $this->logMedicalDataAccess('emergency_critical_allergies', self::ACCESS_EMERGENCY_ONLY);
            
            return array_values($criticalAllergies);
            
        } catch (Exception $e) {
            $this->logger->error('Critical allergies access failed: ' . $e->getMessage(), [
                'medical_info_id' => $this->id
            ]);
            return [];
        }
    }
    
    /**
     * Get current medications for emergency reference
     */
    public function getCurrentMedications()
    {
        try {
            if (!$this->medications_encrypted) {
                return [];
            }
            
            $medications = json_decode($this->encryption->decrypt($this->medications_encrypted), true);
            
            if (!is_array($medications)) {
                return [];
            }
            
            // Filter for currently active medications
            $currentMedications = array_filter($medications, function($medication) {
                return $medication['status'] === 'active' && !empty($medication['name']);
            });
            
            // Log emergency access
            $this->logMedicalDataAccess('emergency_medications', self::ACCESS_EMERGENCY_ONLY);
            
            return array_values($currentMedications);
            
        } catch (Exception $e) {
            $this->logger->error('Current medications access failed: ' . $e->getMessage(), [
                'medical_info_id' => $this->id
            ]);
            return [];
        }
    }
    
    /**
     * Generate emergency medical protocol
     */
    public function generateEmergencyProtocol()
    {
        try {
            $protocol = [
                'participant_id' => $this->participant_id,
                'critical_allergies' => $this->getCriticalAllergies(),
                'current_medications' => $this->getCurrentMedications(),
                'emergency_instructions' => null,
                'medical_aid_info' => $this->medical_aid_info,
                'dietary_restrictions' => $this->dietary_requirements,
                'physical_limitations' => $this->physical_limitations,
                'accessibility_needs' => $this->accessibility_needs,
                'consent_treatment' => $this->consent_medical_treatment,
                'consent_medication' => $this->consent_medication_admin,
                'generated_at' => date('Y-m-d H:i:s'),
                'protocol_id' => uniqid('EMP-')
            ];
            
            // Decrypt emergency instructions if available
            if ($this->emergency_instructions_encrypted) {
                $protocol['emergency_instructions'] = $this->encryption->decrypt($this->emergency_instructions_encrypted);
            }
            
            // Log emergency protocol generation
            $this->logMedicalDataAccess('emergency_protocol_generation', self::ACCESS_EMERGENCY_ONLY);
            
            return $protocol;
            
        } catch (Exception $e) {
            $this->logger->error('Emergency protocol generation failed: ' . $e->getMessage(), [
                'medical_info_id' => $this->id
            ]);
            throw new Exception('Unable to generate emergency protocol');
        }
    }
    
    /**
     * Validate medical data completeness and accuracy
     */
    public function validateMedicalData($validatedBy)
    {
        $validationErrors = [];
        $validationWarnings = [];
        
        // Check for critical missing information
        if (!$this->allergies_encrypted && !$this->medical_conditions_encrypted) {
            $validationWarnings[] = 'No allergies or medical conditions recorded - confirm if participant has none';
        }
        
        if (!$this->consent_medical_treatment) {
            $validationErrors[] = 'Medical treatment consent is required';
        }
        
        if (!$this->medical_aid_info && !$this->medical_aid_number_encrypted) {
            $validationWarnings[] = 'No medical aid information provided';
        }
        
        // Check emergency contact information
        $emergencyContacts = EmergencyContact::where('participant_id', $this->participant_id)
            ->where('medical_authority', true)
            ->where('is_active', true)
            ->get();
            
        if (empty($emergencyContacts)) {
            $validationErrors[] = 'No emergency contact with medical authority found';
        }
        
        // Determine validation status
        if (empty($validationErrors)) {
            $status = empty($validationWarnings) ? self::STATUS_VALIDATED : self::STATUS_REQUIRES_REVIEW;
        } else {
            $status = self::STATUS_REJECTED;
        }
        
        // Update validation status
        $this->validation_status = $status;
        $this->validation_notes = json_encode([
            'errors' => $validationErrors,
            'warnings' => $validationWarnings,
            'validated_at' => date('Y-m-d H:i:s')
        ]);
        $this->validated_at = date('Y-m-d H:i:s');
        $this->validated_by = $validatedBy;
        
        $this->save();
        
        return [
            'status' => $status,
            'errors' => $validationErrors,
            'warnings' => $validationWarnings,
            'is_valid' => $status === self::STATUS_VALIDATED
        ];
    }
    
    /**
     * Check if user can access medical data at specified level
     */
    protected function canAccessMedicalData($requestedAccessLevel)
    {
        // This would integrate with the authentication system
        // For now, return true - implement proper access control
        return true;
    }
    
    /**
     * Log medical data access for audit trail
     */
    protected function logMedicalDataAccess($accessType, $accessLevel)
    {
        try {
            $this->db->table('medical_access_logs')->insert([
                'participant_id' => $this->participant_id,
                'accessed_by' => auth()->getId() ?? 0,
                'access_type' => $accessType,
                'access_timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'risk_level' => $this->calculateAccessRiskLevel($accessType),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to log medical data access: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate risk level for access type
     */
    protected function calculateAccessRiskLevel($accessType)
    {
        $riskLevels = [
            'view' => 'low',
            'edit' => 'medium',
            'decrypt' => 'medium',
            'export' => 'high',
            'emergency_critical_allergies' => 'critical',
            'emergency_medications' => 'critical',
            'emergency_protocol_generation' => 'critical'
        ];
        
        return $riskLevels[$accessType] ?? 'medium';
    }
    
    /**
     * Get available validation statuses
     */
    public static function getAvailableValidationStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending Validation',
            self::STATUS_VALIDATED => 'Validated',
            self::STATUS_REQUIRES_REVIEW => 'Requires Review',
            self::STATUS_REJECTED => 'Rejected'
        ];
    }
    
    /**
     * Get available access levels
     */
    public static function getAvailableAccessLevels()
    {
        return [
            self::ACCESS_PRIVATE => 'Private',
            self::ACCESS_MEDICAL_STAFF => 'Medical Staff Only',
            self::ACCESS_EMERGENCY_ONLY => 'Emergency Access Only',
            self::ACCESS_AUTHORIZED_PERSONNEL => 'Authorized Personnel'
        ];
    }
    
    /**
     * Override toArray to include calculated fields and sanitize sensitive data
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Remove encrypted fields from array output for security
        unset($attributes['allergies_encrypted']);
        unset($attributes['medical_conditions_encrypted']);
        unset($attributes['medications_encrypted']);
        unset($attributes['medical_aid_number_encrypted']);
        unset($attributes['emergency_instructions_encrypted']);
        
        // Add calculated fields
        $attributes['validation_status_label'] = self::getAvailableValidationStatuses()[$this->validation_status] ?? $this->validation_status;
        $attributes['access_level_label'] = self::getAvailableAccessLevels()[$this->access_level] ?? $this->access_level;
        $attributes['has_critical_allergies'] = !empty($this->getCriticalAllergies());
        $attributes['has_current_medications'] = !empty($this->getCurrentMedications());
        $attributes['requires_additional_supervision'] = $this->additional_supervision;
        $attributes['medical_treatment_consent'] = $this->consent_medical_treatment;
        $attributes['medication_admin_consent'] = $this->consent_medication_admin;
        $attributes['data_sharing_consent'] = $this->data_sharing_consent;
        
        return $attributes;
    }
}