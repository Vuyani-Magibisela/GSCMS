<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Encryption;
use App\Core\Database;
use App\Models\MedicalInformation;
use App\Models\EmergencyContact;
use App\Models\Participant;
use App\Models\UploadedFile;
use App\Core\FileUpload;
use Exception;

class MedicalFormController extends BaseController
{
    protected $auth;
    protected $db;
    protected $encryption;
    protected $fileUpload;
    
    public function __construct()
    {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance();
        $this->encryption = new Encryption();
        $this->fileUpload = new FileUpload();
    }
    
    /**
     * Collect medical information for participant
     */
    public function collectMedicalInfo()
    {
        try {
            // Check authentication and permissions
            if (!$this->auth->isLoggedIn()) {
                return Response::json(['error' => 'Authentication required'], 401);
            }
            
            $participantId = Request::input('participant_id');
            if (!$participantId) {
                return Response::json(['error' => 'Participant ID is required'], 400);
            }
            
            // Verify user can manage this participant's medical info
            if (!$this->canManageParticipantMedical($participantId)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Get medical form data
            $medicalData = [
                'participant_id' => $participantId,
                'allergies' => Request::input('allergies'),
                'medical_conditions' => Request::input('medical_conditions'),
                'current_medications' => Request::input('current_medications'),
                'medication_dosages' => Request::input('medication_dosages'),
                'medical_aid_info' => Request::input('medical_aid_info'),
                'medical_aid_number' => Request::input('medical_aid_number'),
                'dietary_requirements' => Request::input('dietary_requirements'),
                'physical_limitations' => Request::input('physical_limitations'),
                'learning_difficulties' => Request::input('learning_difficulties'),
                'accessibility_needs' => Request::input('accessibility_needs'),
                'behavioral_support' => Request::input('behavioral_support'),
                'additional_supervision' => Request::input('additional_supervision') === 'true',
                'special_instructions' => Request::input('special_instructions'),
                'medical_emergency_instructions' => Request::input('medical_emergency_instructions'),
                'consent_medical_treatment' => Request::input('consent_medical_treatment') === 'true',
                'consent_medication_admin' => Request::input('consent_medication_admin') === 'true',
                'data_sharing_consent' => Request::input('data_sharing_consent') === 'true'
            ];
            
            // Encrypt sensitive medical data
            $encryptedData = $this->encryptMedicalData($medicalData);
            
            // Save or update medical information
            $medicalInfo = $this->saveOrUpdateMedicalInfo($participantId, $encryptedData);
            
            if ($medicalInfo) {
                // Handle file uploads for medical documents
                $this->handleMedicalDocumentUploads($medicalInfo->id);
                
                // Process emergency contacts
                $this->processEmergencyContacts($participantId, Request::input('emergency_contacts', []));
                
                // Log the medical data collection
                $this->logger->info("Medical information collected for participant {$participantId}", [
                    'participant_id' => $participantId,
                    'collected_by' => $this->auth->getId(),
                    'has_allergies' => !empty($medicalData['allergies']),
                    'has_conditions' => !empty($medicalData['medical_conditions']),
                    'has_medications' => !empty($medicalData['current_medications'])
                ]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'Medical information saved successfully',
                    'medical_info_id' => $medicalInfo->id
                ]);
            } else {
                return Response::json(['error' => 'Failed to save medical information'], 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Medical form collection error: ' . $e->getMessage());
            return Response::json(['error' => 'Failed to collect medical information'], 500);
        }
    }
    
    /**
     * Validate medical data completeness and accuracy
     */
    public function validateMedicalData()
    {
        try {
            $participantId = Request::input('participant_id');
            $medicalInfoId = Request::input('medical_info_id');
            
            if (!$participantId || !$medicalInfoId) {
                return Response::json(['error' => 'Participant ID and Medical Info ID are required'], 400);
            }
            
            // Get medical information
            $medicalInfo = MedicalInformation::find($medicalInfoId);
            if (!$medicalInfo || $medicalInfo->participant_id != $participantId) {
                return Response::json(['error' => 'Medical information not found'], 404);
            }
            
            // Decrypt and validate data
            $decryptedData = $this->decryptMedicalData($medicalInfo);
            $validation = $this->performMedicalDataValidation($decryptedData);
            
            // Update validation status
            $medicalInfo->validation_status = $validation['is_valid'] ? 'validated' : 'requires_review';
            $medicalInfo->validation_notes = json_encode($validation['notes']);
            $medicalInfo->validated_at = date('Y-m-d H:i:s');
            $medicalInfo->validated_by = $this->auth->getId();
            $medicalInfo->save();
            
            return Response::json([
                'success' => true,
                'validation' => $validation,
                'medical_info_id' => $medicalInfo->id
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Medical data validation error: ' . $e->getMessage());
            return Response::json(['error' => 'Validation failed'], 500);
        }
    }
    
    /**
     * Manage allergy and condition tracking
     */
    public function manageAllergies()
    {
        try {
            $participantId = Request::input('participant_id');
            $allergies = Request::input('allergies', []);
            
            if (!$participantId) {
                return Response::json(['error' => 'Participant ID is required'], 400);
            }
            
            // Verify permissions
            if (!$this->canManageParticipantMedical($participantId)) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Process each allergy with severity and treatment info
            $processedAllergies = [];
            foreach ($allergies as $allergy) {
                $processedAllergies[] = [
                    'allergen' => $allergy['allergen'],
                    'severity' => $allergy['severity'],
                    'symptoms' => $allergy['symptoms'],
                    'treatment' => $allergy['treatment'],
                    'emergency_medication' => $allergy['emergency_medication'] ?? null,
                    'verified_by_doctor' => $allergy['verified_by_doctor'] === 'true',
                    'last_incident' => $allergy['last_incident'] ?? null
                ];
            }
            
            // Update medical information with processed allergies
            $medicalInfo = MedicalInformation::where('participant_id', $participantId)->first();
            if ($medicalInfo) {
                $encryptedAllergies = $this->encryption->encrypt(json_encode($processedAllergies));
                $medicalInfo->allergies_encrypted = $encryptedAllergies;
                $medicalInfo->allergy_management_plan = Request::input('management_plan');
                $medicalInfo->updated_at = date('Y-m-d H:i:s');
                $medicalInfo->save();
                
                // Create allergy alert if high-severity allergies exist
                $this->createAllergyAlerts($participantId, $processedAllergies);
                
                return Response::json([
                    'success' => true,
                    'message' => 'Allergy information updated successfully',
                    'allergy_count' => count($processedAllergies)
                ]);
            }
            
            return Response::json(['error' => 'Medical information not found'], 404);
            
        } catch (Exception $e) {
            $this->logger->error('Allergy management error: ' . $e->getMessage());
            return Response::json(['error' => 'Failed to update allergy information'], 500);
        }
    }
    
    /**
     * Emergency response protocols and procedures
     */
    public function emergencyProtocols()
    {
        try {
            $participantId = Request::input('participant_id');
            
            if (!$participantId) {
                return Response::json(['error' => 'Participant ID is required'], 400);
            }
            
            // Get comprehensive medical emergency information
            $emergencyInfo = $this->generateEmergencyProtocol($participantId);
            
            if (!$emergencyInfo) {
                return Response::json(['error' => 'No medical information found for participant'], 404);
            }
            
            // Format for emergency responders
            $emergencyProtocol = [
                'participant_info' => $emergencyInfo['participant'],
                'critical_allergies' => $emergencyInfo['critical_allergies'],
                'medical_conditions' => $emergencyInfo['medical_conditions'],
                'current_medications' => $emergencyInfo['medications'],
                'emergency_contacts' => $emergencyInfo['emergency_contacts'],
                'medical_contacts' => $emergencyInfo['medical_contacts'],
                'emergency_instructions' => $emergencyInfo['instructions'],
                'medical_aid_info' => $emergencyInfo['medical_aid'],
                'hospital_preferences' => $emergencyInfo['hospital_preferences'],
                'accessibility_needs' => $emergencyInfo['accessibility_needs'],
                'generated_at' => date('Y-m-d H:i:s'),
                'emergency_id' => uniqid('EMRG-')
            ];
            
            // Log emergency protocol access
            $this->logger->warning("Emergency protocol accessed for participant {$participantId}", [
                'participant_id' => $participantId,
                'accessed_by' => $this->auth->getId(),
                'emergency_id' => $emergencyProtocol['emergency_id'],
                'critical_info_present' => !empty($emergencyProtocol['critical_allergies'])
            ]);
            
            return Response::json([
                'success' => true,
                'emergency_protocol' => $emergencyProtocol,
                'quick_reference' => $this->generateQuickReferenceCard($emergencyProtocol)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Emergency protocol error: ' . $e->getMessage());
            return Response::json(['error' => 'Failed to generate emergency protocol'], 500);
        }
    }
    
    /**
     * Secure medical data access for authorized personnel
     */
    public function secureMedicalAccess($participantId)
    {
        try {
            // Strict permission check for medical data access
            if (!$this->auth->hasPermission('medical.access') && 
                !$this->auth->hasRole('medical_staff') &&
                !$this->auth->hasRole('admin')) {
                
                // Log unauthorized access attempt
                $this->logger->warning("Unauthorized medical data access attempt", [
                    'participant_id' => $participantId,
                    'user_id' => $this->auth->getId(),
                    'user_role' => $this->auth->getRole(),
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                ]);
                
                return Response::json(['error' => 'Medical data access denied'], 403);
            }
            
            // Get medical information with audit trail
            $medicalInfo = MedicalInformation::find($participantId);
            if (!$medicalInfo) {
                return Response::json(['error' => 'Medical information not found'], 404);
            }
            
            // Decrypt medical data for authorized access
            $decryptedData = $this->decryptMedicalData($medicalInfo);
            
            // Create access log entry
            $this->createMedicalAccessLog($participantId, $this->auth->getId(), 'secure_access');
            
            // Return sanitized medical data (remove highly sensitive fields for non-medical staff)
            $sanitizedData = $this->sanitizeMedicalData($decryptedData, $this->auth->getRole());
            
            return Response::json([
                'success' => true,
                'medical_data' => $sanitizedData,
                'access_level' => $this->getMedicalAccessLevel($this->auth->getRole()),
                'accessed_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Secure medical access error: ' . $e->getMessage());
            return Response::json(['error' => 'Medical data access failed'], 500);
        }
    }
    
    // Helper methods for medical data processing
    
    /**
     * Encrypt sensitive medical data using AES-256
     */
    protected function encryptMedicalData($medicalData)
    {
        $sensitiveFields = [
            'allergies', 'medical_conditions', 'current_medications', 
            'medication_dosages', 'medical_aid_number', 'special_instructions',
            'medical_emergency_instructions'
        ];
        
        $encryptedData = $medicalData;
        
        foreach ($sensitiveFields as $field) {
            if (!empty($medicalData[$field])) {
                $encryptedData[$field . '_encrypted'] = $this->encryption->encrypt($medicalData[$field]);
                unset($encryptedData[$field]); // Remove plain text
            }
        }
        
        return $encryptedData;
    }
    
    /**
     * Decrypt medical data for authorized access
     */
    protected function decryptMedicalData($medicalInfo)
    {
        $encryptedFields = [
            'allergies_encrypted', 'medical_conditions_encrypted', 
            'medications_encrypted', 'emergency_instructions_encrypted'
        ];
        
        $decryptedData = $medicalInfo->toArray();
        
        foreach ($encryptedFields as $field) {
            if (!empty($medicalInfo->$field)) {
                $plainField = str_replace('_encrypted', '', $field);
                $decryptedData[$plainField] = $this->encryption->decrypt($medicalInfo->$field);
            }
        }
        
        return $decryptedData;
    }
    
    /**
     * Check if user can manage participant's medical information
     */
    protected function canManageParticipantMedical($participantId)
    {
        $userRole = $this->auth->getRole();
        $userId = $this->auth->getId();
        
        // Admin and medical staff can manage all
        if (in_array($userRole, ['admin', 'medical_staff'])) {
            return true;
        }
        
        // Get participant details
        $participant = Participant::find($participantId);
        if (!$participant) {
            return false;
        }
        
        // School coordinator can manage their school's participants
        if ($userRole === 'school_coordinator') {
            $user = User::find($userId);
            $team = Team::find($participant->team_id);
            return $user && $team && $user->school_id == $team->school_id;
        }
        
        // Team coach can manage their team's participants
        if ($userRole === 'team_coach') {
            $team = Team::find($participant->team_id);
            return $team && ($team->coach1_id == $userId || $team->coach2_id == $userId);
        }
        
        return false;
    }
    
    /**
     * Create medical access audit log
     */
    protected function createMedicalAccessLog($participantId, $userId, $accessType)
    {
        $this->db->table('medical_access_logs')->insert([
            'participant_id' => $participantId,
            'accessed_by' => $userId,
            'access_type' => $accessType,
            'access_timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}