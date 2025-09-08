<?php

namespace App\Models;

use App\Core\Logger;
use Exception;

class EmergencyContact extends BaseModel
{
    protected $table = 'emergency_contacts';
    protected $softDeletes = true;
    
    protected $fillable = [
        'participant_id', 'contact_type', 'priority_order', 'name', 'relationship',
        'phone_primary', 'phone_secondary', 'phone_work', 'email', 'address',
        'availability_hours', 'medical_authority', 'pickup_authority', 'emergency_only',
        'preferred_contact_method', 'language_preference', 'medical_professional',
        'practice_name', 'practice_address', 'specialization', 'license_number',
        'hospital_affiliation', 'verification_status', 'verified_at', 'verification_method',
        'last_contact_attempt', 'last_successful_contact', 'contact_notes', 'is_active',
        'gdpr_consent', 'data_retention_date'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Contact type constants
    const TYPE_PRIMARY_GUARDIAN = 'primary_guardian';
    const TYPE_SECONDARY_GUARDIAN = 'secondary_guardian';
    const TYPE_EMERGENCY_CONTACT = 'emergency_contact';
    const TYPE_MEDICAL_CONTACT = 'medical_contact';
    const TYPE_SCHOOL_CONTACT = 'school_contact';
    const TYPE_FAMILY_DOCTOR = 'family_doctor';
    const TYPE_SPECIALIST_DOCTOR = 'specialist_doctor';
    
    // Verification status constants
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_FAILED = 'failed';
    const VERIFICATION_EXPIRED = 'expired';
    
    // Contact method constants
    const METHOD_PHONE = 'phone';
    const METHOD_SMS = 'sms';
    const METHOD_EMAIL = 'email';
    const METHOD_WHATSAPP = 'whatsapp';
    
    protected $belongsTo = [
        'participant' => ['model' => Participant::class, 'foreign_key' => 'participant_id']
    ];
    
    protected $logger;
    
    public function __construct()
    {
        parent::__construct();
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
     * Get emergency contacts for participant ordered by priority
     */
    public static function getForParticipant($participantId, $activeOnly = true)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('participant_id', $participantId)
            ->whereNull('deleted_at')
            ->orderBy('priority_order')
            ->orderBy('contact_type');
            
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        
        return $query->get();
    }
    
    /**
     * Get emergency contacts by type
     */
    public static function getByType($participantId, $contactType)
    {
        $model = new static();
        return $model->db->table($model->table)
            ->where('participant_id', $participantId)
            ->where('contact_type', $contactType)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('priority_order')
            ->get();
    }
    
    /**
     * Get contacts with medical authority
     */
    public static function getMedicalAuthorityContacts($participantId)
    {
        $model = new static();
        return $model->db->table($model->table)
            ->where('participant_id', $participantId)
            ->where('medical_authority', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('priority_order')
            ->get();
    }
    
    /**
     * Get contacts with pickup authority
     */
    public static function getPickupAuthorityContacts($participantId)
    {
        $model = new static();
        return $model->db->table($model->table)
            ->where('participant_id', $participantId)
            ->where('pickup_authority', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('priority_order')
            ->get();
    }
    
    /**
     * Get medical professionals for participant
     */
    public static function getMedicalProfessionals($participantId)
    {
        $model = new static();
        return $model->db->table($model->table)
            ->where('participant_id', $participantId)
            ->where('medical_professional', true)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('contact_type')
            ->orderBy('priority_order')
            ->get();
    }
    
    /**
     * Verify contact information
     */
    public function verifyContact($verificationMethod = 'manual')
    {
        try {
            $verificationResult = $this->performContactVerification($verificationMethod);
            
            $this->verification_status = $verificationResult['success'] ? self::VERIFICATION_VERIFIED : self::VERIFICATION_FAILED;
            $this->verification_method = $verificationMethod;
            $this->verified_at = date('Y-m-d H:i:s');
            $this->last_contact_attempt = date('Y-m-d H:i:s');
            
            if ($verificationResult['success']) {
                $this->last_successful_contact = date('Y-m-d H:i:s');
            }
            
            $this->save();
            
            // Log verification attempt
            $this->logger->info("Emergency contact verification attempted", [
                'contact_id' => $this->id,
                'participant_id' => $this->participant_id,
                'verification_method' => $verificationMethod,
                'success' => $verificationResult['success']
            ]);
            
            return $verificationResult;
            
        } catch (Exception $e) {
            $this->logger->error('Contact verification failed: ' . $e->getMessage());
            $this->verification_status = self::VERIFICATION_FAILED;
            $this->save();
            throw new Exception('Contact verification failed');
        }
    }
    
    /**
     * Test contact availability
     */
    public function testContactAvailability($method = null)
    {
        $method = $method ?? $this->preferred_contact_method;
        
        try {
            $testResult = false;
            
            switch ($method) {
                case self::METHOD_PHONE:
                    $testResult = $this->testPhoneContact();
                    break;
                case self::METHOD_SMS:
                    $testResult = $this->testSMSContact();
                    break;
                case self::METHOD_EMAIL:
                    $testResult = $this->testEmailContact();
                    break;
                case self::METHOD_WHATSAPP:
                    $testResult = $this->testWhatsAppContact();
                    break;
            }
            
            $this->last_contact_attempt = date('Y-m-d H:i:s');
            if ($testResult) {
                $this->last_successful_contact = date('Y-m-d H:i:s');
            }
            
            $this->save();
            
            return $testResult;
            
        } catch (Exception $e) {
            $this->logger->error('Contact availability test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate emergency contact card for venues
     */
    public function generateEmergencyCard()
    {
        return [
            'participant_id' => $this->participant_id,
            'contact_name' => $this->name,
            'relationship' => $this->relationship,
            'primary_phone' => $this->phone_primary,
            'secondary_phone' => $this->phone_secondary,
            'email' => $this->email,
            'medical_authority' => $this->medical_authority,
            'pickup_authority' => $this->pickup_authority,
            'preferred_method' => $this->preferred_contact_method,
            'language' => $this->language_preference,
            'priority' => $this->priority_order,
            'emergency_only' => $this->emergency_only,
            'generated_at' => date('Y-m-d H:i:s'),
            'card_id' => uniqid('EC-')
        ];
    }
    
    /**
     * Send emergency notification to contact
     */
    public function sendEmergencyNotification($message, $urgency = 'high')
    {
        try {
            $notificationSent = false;
            
            // Try multiple contact methods based on urgency
            if ($urgency === 'critical') {
                // For critical emergencies, try all available methods
                $methods = [$this->preferred_contact_method];
                if ($this->phone_primary) $methods[] = self::METHOD_PHONE;
                if ($this->email) $methods[] = self::METHOD_EMAIL;
                if ($this->phone_primary) $methods[] = self::METHOD_SMS;
                
                $methods = array_unique($methods);
            } else {
                $methods = [$this->preferred_contact_method];
            }
            
            foreach ($methods as $method) {
                $result = $this->sendNotificationByMethod($message, $method, $urgency);
                if ($result) {
                    $notificationSent = true;
                    if ($urgency !== 'critical') {
                        break; // For non-critical, one successful method is enough
                    }
                }
            }
            
            // Log emergency notification
            $this->logger->warning("Emergency notification sent to contact", [
                'contact_id' => $this->id,
                'participant_id' => $this->participant_id,
                'urgency' => $urgency,
                'methods_used' => implode(',', $methods),
                'success' => $notificationSent
            ]);
            
            $this->last_contact_attempt = date('Y-m-d H:i:s');
            if ($notificationSent) {
                $this->last_successful_contact = date('Y-m-d H:i:s');
            }
            $this->save();
            
            return $notificationSent;
            
        } catch (Exception $e) {
            $this->logger->error('Emergency notification failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if contact is currently available
     */
    public function isCurrentlyAvailable()
    {
        if (!$this->availability_hours) {
            return true; // Assume available if no restrictions set
        }
        
        try {
            $availability = json_decode($this->availability_hours, true);
            $currentHour = (int) date('H');
            $currentDay = strtolower(date('l'));
            
            if (isset($availability[$currentDay])) {
                $dayAvailability = $availability[$currentDay];
                if ($dayAvailability['available']) {
                    $startHour = (int) $dayAvailability['start_time'];
                    $endHour = (int) $dayAvailability['end_time'];
                    
                    return $currentHour >= $startHour && $currentHour <= $endHour;
                }
                return false;
            }
            
            return true; // Default to available if day not specified
            
        } catch (Exception $e) {
            return true; // Default to available if parsing fails
        }
    }
    
    /**
     * Update priority order
     */
    public function updatePriority($newPriority)
    {
        $this->priority_order = $newPriority;
        return $this->save();
    }
    
    /**
     * Check if contact needs verification renewal
     */
    public function needsVerificationRenewal($renewalPeriod = '6 months')
    {
        if (!$this->verified_at) {
            return true;
        }
        
        $renewalDate = date('Y-m-d H:i:s', strtotime($this->verified_at . " +{$renewalPeriod}"));
        return date('Y-m-d H:i:s') > $renewalDate;
    }
    
    /**
     * Get available contact types
     */
    public static function getAvailableContactTypes()
    {
        return [
            self::TYPE_PRIMARY_GUARDIAN => 'Primary Guardian',
            self::TYPE_SECONDARY_GUARDIAN => 'Secondary Guardian',
            self::TYPE_EMERGENCY_CONTACT => 'Emergency Contact',
            self::TYPE_MEDICAL_CONTACT => 'Medical Contact',
            self::TYPE_SCHOOL_CONTACT => 'School Contact',
            self::TYPE_FAMILY_DOCTOR => 'Family Doctor',
            self::TYPE_SPECIALIST_DOCTOR => 'Specialist Doctor'
        ];
    }
    
    /**
     * Get available verification statuses
     */
    public static function getAvailableVerificationStatuses()
    {
        return [
            self::VERIFICATION_PENDING => 'Pending Verification',
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_FAILED => 'Verification Failed',
            self::VERIFICATION_EXPIRED => 'Verification Expired'
        ];
    }
    
    /**
     * Get available contact methods
     */
    public static function getAvailableContactMethods()
    {
        return [
            self::METHOD_PHONE => 'Phone Call',
            self::METHOD_SMS => 'SMS/Text',
            self::METHOD_EMAIL => 'Email',
            self::METHOD_WHATSAPP => 'WhatsApp'
        ];
    }
    
    // Helper methods for contact testing
    
    protected function performContactVerification($method)
    {
        // Placeholder for actual contact verification logic
        return [
            'success' => true,
            'method' => $method,
            'verified_at' => date('Y-m-d H:i:s')
        ];
    }
    
    protected function testPhoneContact()
    {
        // Placeholder for phone contact testing
        return true;
    }
    
    protected function testSMSContact()
    {
        // Placeholder for SMS contact testing
        return true;
    }
    
    protected function testEmailContact()
    {
        // Placeholder for email contact testing
        return true;
    }
    
    protected function testWhatsAppContact()
    {
        // Placeholder for WhatsApp contact testing
        return true;
    }
    
    protected function sendNotificationByMethod($message, $method, $urgency)
    {
        // Placeholder for actual notification sending
        // This would integrate with SMS, email, and other services
        return true;
    }
    
    /**
     * Scope: Active contacts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope: Verified contacts
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }
    
    /**
     * Scope: Contacts with medical authority
     */
    public function scopeWithMedicalAuthority($query)
    {
        return $query->where('medical_authority', true);
    }
    
    /**
     * Scope: Contacts with pickup authority
     */
    public function scopeWithPickupAuthority($query)
    {
        return $query->where('pickup_authority', true);
    }
    
    /**
     * Scope: Medical professionals
     */
    public function scopeMedicalProfessionals($query)
    {
        return $query->where('medical_professional', true);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['contact_type_label'] = self::getAvailableContactTypes()[$this->contact_type] ?? $this->contact_type;
        $attributes['verification_status_label'] = self::getAvailableVerificationStatuses()[$this->verification_status] ?? $this->verification_status;
        $attributes['contact_method_label'] = self::getAvailableContactMethods()[$this->preferred_contact_method] ?? $this->preferred_contact_method;
        $attributes['is_verified'] = $this->verification_status === self::VERIFICATION_VERIFIED;
        $attributes['needs_verification_renewal'] = $this->needsVerificationRenewal();
        $attributes['is_currently_available'] = $this->isCurrentlyAvailable();
        $attributes['availability_hours_parsed'] = $this->availability_hours ? json_decode($this->availability_hours, true) : null;
        
        return $attributes;
    }
}