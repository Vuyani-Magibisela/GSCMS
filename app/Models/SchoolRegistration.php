<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * School Registration Model
 * Handles comprehensive school self-registration process
 */
class SchoolRegistration extends BaseModel
{
    protected $table = 'school_registrations';
    protected $fillable = [
        'school_name',
        'emis_number', 
        'school_type',
        'establishment_date',
        'current_enrollment',
        'physical_address',
        'postal_address',
        'gps_coordinates',
        'district_id',
        'main_phone',
        'main_email',
        'website_url',
        'social_media',
        'principal_name',
        'principal_email',
        'principal_phone',
        'principal_appointment_date',
        'coordinator_user_id',
        'coordinator_qualifications',
        'coordinator_availability',
        'backup_coordinator_user_id',
        'computer_lab_available',
        'internet_connectivity',
        'classroom_space_available',
        'storage_facilities',
        'existing_robotics_kits',
        'computer_count',
        'software_access',
        'previous_robotics_experience',
        'intended_categories',
        'estimated_participants',
        'coach_availability',
        'training_commitment_hours',
        'training_needs',
        'equipment_needs',
        'transport_arrangements',
        'special_accommodations',
        'registration_status',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'documents_complete',
        'verification_complete'
    ];
    
    protected $casts = [
        'social_media' => 'json',
        'intended_categories' => 'json',
        'computer_lab_available' => 'boolean',
        'classroom_space_available' => 'boolean',
        'storage_facilities' => 'boolean',
        'documents_complete' => 'boolean',
        'verification_complete' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'principal_appointment_date' => 'date'
    ];
    
    /**
     * Valid registration statuses
     */
    const STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    ];
    
    /**
     * Valid school types
     */
    const SCHOOL_TYPES = [
        'primary' => 'Primary School',
        'secondary' => 'Secondary School', 
        'combined' => 'Combined School'
    ];
    
    /**
     * Internet connectivity levels
     */
    const CONNECTIVITY_LEVELS = [
        'none' => 'No Internet',
        'basic' => 'Basic Connection',
        'good' => 'Good Connection',
        'excellent' => 'Excellent Connection'
    ];
    
    /**
     * Get coordinator user
     */
    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }
    
    /**
     * Get backup coordinator user
     */
    public function backupCoordinator()
    {
        return $this->belongsTo(User::class, 'backup_coordinator_user_id');
    }
    
    /**
     * Get approving user
     */
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    /**
     * Get district
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
    
    /**
     * Create new school registration
     */
    public static function createRegistration($data)
    {
        // Validate required fields
        $requiredFields = [
            'school_name', 'school_type', 'physical_address', 
            'main_phone', 'main_email', 'principal_name', 
            'principal_email', 'principal_phone'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Required field '{$field}' is missing");
            }
        }
        
        // Validate EMIS number uniqueness if provided
        if (!empty($data['emis_number'])) {
            $existing = static::where('emis_number', $data['emis_number'])->first();
            if ($existing) {
                throw new \Exception('EMIS number already registered');
            }
        }
        
        // Validate email uniqueness
        $existing = static::where('main_email', $data['main_email'])->first();
        if ($existing) {
            throw new \Exception('School email address already registered');
        }
        
        $registration = new static($data);
        $registration->registration_status = 'draft';
        $registration->save();
        
        return $registration;
    }
    
    /**
     * Submit registration for review
     */
    public function submitForReview()
    {
        if ($this->registration_status !== 'draft') {
            throw new \Exception('Only draft registrations can be submitted');
        }
        
        // Validate completeness
        $validation = $this->validateCompleteness();
        if (!$validation['complete']) {
            throw new \Exception('Registration incomplete: ' . implode(', ', $validation['missing_fields']));
        }
        
        $this->registration_status = 'submitted';
        $this->submitted_at = date('Y-m-d H:i:s');
        $this->save();
        
        // Send confirmation notification
        $this->sendSubmissionConfirmation();
        
        return $this;
    }
    
    /**
     * Approve registration
     */
    public function approve($approvedBy, $notes = null)
    {
        if (!in_array($this->registration_status, ['submitted', 'under_review'])) {
            throw new \Exception('Only submitted or under review registrations can be approved');
        }
        
        $this->registration_status = 'approved';
        $this->approved_by = $approvedBy;
        $this->approved_at = date('Y-m-d H:i:s');
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->save();
        
        // Create actual school record
        $schoolId = $this->createSchoolRecord();
        
        // Send approval notification
        $this->sendApprovalNotification($schoolId);
        
        return $schoolId;
    }
    
    /**
     * Reject registration
     */
    public function reject($rejectedBy, $reason)
    {
        if (!in_array($this->registration_status, ['submitted', 'under_review'])) {
            throw new \Exception('Only submitted or under review registrations can be rejected');
        }
        
        $this->registration_status = 'rejected';
        $this->approved_by = $rejectedBy; // For audit trail
        $this->rejection_reason = $reason;
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->save();
        
        // Send rejection notification
        $this->sendRejectionNotification();
        
        return $this;
    }
    
    /**
     * Update registration data (draft only)
     */
    public function updateRegistrationData($data)
    {
        if ($this->registration_status !== 'draft') {
            throw new \Exception('Only draft registrations can be modified');
        }
        
        // Filter allowed fields
        $allowedFields = array_intersect_key($data, array_flip($this->fillable));
        
        foreach ($allowedFields as $field => $value) {
            $this->$field = $value;
        }
        
        $this->save();
        
        return $this;
    }
    
    /**
     * Validate registration completeness
     */
    public function validateCompleteness()
    {
        $requiredFields = [
            'school_name' => 'School Name',
            'school_type' => 'School Type',
            'physical_address' => 'Physical Address',
            'main_phone' => 'Main Phone',
            'main_email' => 'Main Email',
            'principal_name' => 'Principal Name',
            'principal_email' => 'Principal Email',
            'principal_phone' => 'Principal Phone',
            'coordinator_user_id' => 'School Coordinator',
            'computer_lab_available' => 'Computer Lab Status',
            'internet_connectivity' => 'Internet Connectivity',
            'intended_categories' => 'Intended Categories',
            'estimated_participants' => 'Estimated Participants',
            'coach_availability' => 'Coach Availability'
        ];
        
        $missingFields = [];
        $warnings = [];
        
        foreach ($requiredFields as $field => $label) {
            if (empty($this->$field)) {
                $missingFields[] = $label;
            }
        }
        
        // Check optional but recommended fields
        $recommendedFields = [
            'emis_number' => 'EMIS Number',
            'website_url' => 'School Website',
            'backup_coordinator_user_id' => 'Backup Coordinator'
        ];
        
        foreach ($recommendedFields as $field => $label) {
            if (empty($this->$field)) {
                $warnings[] = $label;
            }
        }
        
        return [
            'complete' => empty($missingFields),
            'missing_fields' => $missingFields,
            'warnings' => $warnings,
            'completion_percentage' => $this->calculateCompletionPercentage()
        ];
    }
    
    /**
     * Calculate registration completion percentage
     */
    public function calculateCompletionPercentage()
    {
        $totalFields = count($this->fillable);
        $completedFields = 0;
        
        foreach ($this->fillable as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }
        
        return round(($completedFields / $totalFields) * 100);
    }
    
    /**
     * Get registration progress steps
     */
    public function getProgressSteps()
    {
        return [
            'school_information' => [
                'name' => 'School Information',
                'fields' => ['school_name', 'emis_number', 'school_type', 'establishment_date', 'current_enrollment'],
                'completed' => $this->isStepCompleted(['school_name', 'school_type'])
            ],
            'location_contact' => [
                'name' => 'Location & Contact',
                'fields' => ['physical_address', 'main_phone', 'main_email', 'district_id'],
                'completed' => $this->isStepCompleted(['physical_address', 'main_phone', 'main_email'])
            ],
            'administration' => [
                'name' => 'Administration',
                'fields' => ['principal_name', 'principal_email', 'principal_phone', 'coordinator_user_id'],
                'completed' => $this->isStepCompleted(['principal_name', 'principal_email', 'principal_phone'])
            ],
            'facilities' => [
                'name' => 'Facilities & Resources',
                'fields' => ['computer_lab_available', 'internet_connectivity', 'classroom_space_available'],
                'completed' => $this->isStepCompleted(['computer_lab_available', 'internet_connectivity'])
            ],
            'participation' => [
                'name' => 'Competition Participation',
                'fields' => ['intended_categories', 'estimated_participants', 'coach_availability'],
                'completed' => $this->isStepCompleted(['intended_categories', 'estimated_participants'])
            ],
            'support' => [
                'name' => 'Support Requirements',
                'fields' => ['training_needs', 'equipment_needs', 'transport_arrangements'],
                'completed' => true // Optional step
            ]
        ];
    }
    
    /**
     * Check if a step is completed
     */
    private function isStepCompleted($requiredFields)
    {
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Create actual school record from approved registration
     */
    private function createSchoolRecord()
    {
        $schoolData = [
            'name' => $this->school_name,
            'emis_number' => $this->emis_number,
            'type' => $this->school_type,
            'address' => $this->physical_address,
            'phone' => $this->main_phone,
            'email' => $this->main_email,
            'website' => $this->website_url,
            'district_id' => $this->district_id,
            'principal_name' => $this->principal_name,
            'principal_email' => $this->principal_email,
            'principal_phone' => $this->principal_phone,
            'status' => 'active'
        ];
        
        $school = School::create($schoolData);
        
        // Update coordinator's school association
        if ($this->coordinator_user_id) {
            $coordinator = User::find($this->coordinator_user_id);
            if ($coordinator) {
                $coordinator->school_id = $school->id;
                $coordinator->save();
            }
        }
        
        if ($this->backup_coordinator_user_id) {
            $backupCoordinator = User::find($this->backup_coordinator_user_id);
            if ($backupCoordinator) {
                $backupCoordinator->school_id = $school->id;
                $backupCoordinator->save();
            }
        }
        
        return $school->id;
    }
    
    /**
     * Send submission confirmation
     */
    private function sendSubmissionConfirmation()
    {
        // This would integrate with the notification system
        // For now, just log it
        error_log("School registration submitted: {$this->school_name} (ID: {$this->id})");
    }
    
    /**
     * Send approval notification
     */
    private function sendApprovalNotification($schoolId)
    {
        // This would send approval email with school details
        error_log("School registration approved: {$this->school_name} (School ID: {$schoolId})");
    }
    
    /**
     * Send rejection notification
     */
    private function sendRejectionNotification()
    {
        // This would send rejection email with reason
        error_log("School registration rejected: {$this->school_name} - Reason: {$this->rejection_reason}");
    }
    
    /**
     * Get pending registrations
     */
    public static function getPending()
    {
        return static::where('registration_status', 'submitted')
                    ->orderBy('submitted_at', 'asc')
                    ->get();
    }
    
    /**
     * Get registrations under review
     */
    public static function getUnderReview()
    {
        return static::where('registration_status', 'under_review')
                    ->orderBy('submitted_at', 'asc')
                    ->get();
    }
    
    /**
     * Get registration summary
     */
    public function getSummary()
    {
        return [
            'id' => $this->id,
            'school_name' => $this->school_name,
            'emis_number' => $this->emis_number,
            'school_type' => $this->school_type,
            'registration_status' => $this->registration_status,
            'status_name' => self::STATUSES[$this->registration_status] ?? 'Unknown',
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'completion_percentage' => $this->calculateCompletionPercentage(),
            'principal_name' => $this->principal_name,
            'main_email' => $this->main_email,
            'main_phone' => $this->main_phone,
            'estimated_participants' => $this->estimated_participants,
            'intended_categories' => $this->intended_categories,
            'documents_complete' => $this->documents_complete,
            'verification_complete' => $this->verification_complete
        ];
    }
}