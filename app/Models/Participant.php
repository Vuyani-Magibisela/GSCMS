<?php
// app/Models/Participant.php

namespace App\Models;

class Participant extends BaseModel
{
    protected $table = 'participants';
    protected $softDeletes = true;
    
    protected $fillable = [
        'team_id', 'first_name', 'last_name', 'grade', 'gender', 
        'date_of_birth', 'phone', 'email', 'address', 'id_number',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
        'medical_conditions', 'dietary_restrictions', 'special_needs', 'consent_status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'team_id' => 'required',
        'first_name' => 'required|max:100',
        'last_name' => 'required|max:100',
        'grade' => 'required',
        'gender' => 'required',
        'date_of_birth' => 'required',
        'phone' => 'max:20',
        'email' => 'email|max:255',
        'emergency_contact_name' => 'required|max:255',
        'emergency_contact_phone' => 'required|max:20',
        'emergency_contact_relationship' => 'required|max:100'
    ];
    
    protected $messages = [
        'team_id.required' => 'Team is required.',
        'first_name.required' => 'First name is required.',
        'last_name.required' => 'Last name is required.',
        'grade.required' => 'Grade is required.',
        'gender.required' => 'Gender is required.',
        'date_of_birth.required' => 'Date of birth is required.',
        'email.email' => 'Please provide a valid email address.',
        'emergency_contact_name.required' => 'Emergency contact name is required.',
        'emergency_contact_phone.required' => 'Emergency contact phone is required.',
        'emergency_contact_relationship.required' => 'Emergency contact relationship is required.'
    ];
    
    // Gender constants
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_OTHER = 'other';
    
    // Consent status constants
    const CONSENT_PENDING = 'pending';
    const CONSENT_APPROVED = 'approved';
    const CONSENT_REJECTED = 'rejected';
    
    // Grade constants
    const GRADES = [
        'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 
        'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
    ];

    protected $belongsTo = [
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id']
    ];
    
    protected $hasMany = [
        'consentForms' => ['model' => ConsentForm::class, 'foreign_key' => 'participant_id']
    ];

    /**
     * Get participant summary data (replacement for participant_summary view)
     * 
     * @param int|null $participantId Specific participant ID or null for all
     * @param int|null $teamId Filter by team ID
     * @return array
     */
    public function getParticipantSummary($participantId = null, $teamId = null)
    {
        $query = "
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.grade,
                p.gender,
                p.date_of_birth,
                YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d')) as age,
                t.name as team_name,
                t.team_code,
                s.name as school_name,
                c.name as category_name,
                p.consent_form_signed,
                p.created_at
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
        ";

        $params = [];
        $conditions = [];

        if ($participantId) {
            $conditions[] = "p.id = ?";
            $params[] = $participantId;
        }

        if ($teamId) {
            $conditions[] = "p.team_id = ?";
            $params[] = $teamId;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY p.last_name, p.first_name";

        $results = $this->db->query($query, $params);
        
        return $participantId && !empty($results) ? $results[0] : $results;
    }

    /**
     * Get participants by team
     */
    public function getByTeam($teamId)
    {
        return $this->db->table($this->table)
            ->where('team_id', $teamId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get participants by school (through team relationship)
     */
    public function getBySchool($schoolId)
    {
        $query = "
            SELECT p.*
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            WHERE t.school_id = ?
            ORDER BY p.last_name, p.first_name
        ";
        
        return $this->db->query($query, [$schoolId]);
    }

    /**
     * Get participants who haven't signed consent forms
     */
    public function getMissingConsent($teamId = null)
    {
        $query = $this->db->table($this->table)
            ->where('consent_form_signed', 0);
            
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * Calculate age from date of birth
     */
    public function calculateAge($dateOfBirth)
    {
        $today = new \DateTime();
        $birthDate = new \DateTime($dateOfBirth);
        return $today->diff($birthDate)->y;
    }

    /**
     * Get participants by age range
     */
    public function getByAgeRange($minAge, $maxAge)
    {
        $query = "
            SELECT *,
            YEAR(CURDATE()) - YEAR(date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(date_of_birth, '%m%d')) as age
            FROM participants
            HAVING age BETWEEN ? AND ?
            ORDER BY age, last_name, first_name
        ";
        
        return $this->db->query($query, [$minAge, $maxAge]);
    }
    
    /**
     * Get school relationship through team
     */
    public function school()
    {
        $team = $this->belongsTo('App\Models\Team', 'team_id');
        if ($team) {
            return $team->belongsTo('App\Models\School', 'school_id');
        }
        return null;
    }
    
    /**
     * Calculate current age
     */
    public function getAge()
    {
        if (!$this->date_of_birth) return null;
        return $this->calculateAge($this->date_of_birth);
    }
    
    /**
     * Validate age limits for category
     */
    public function validateAgeForCategory($categoryId = null)
    {
        if (!$categoryId) {
            // Get category from team
            $team = $this->belongsTo('App\Models\Team', 'team_id');
            if (!$team) {
                return ['valid' => false, 'message' => 'No team assigned to validate category.'];
            }
            $categoryId = $team->category_id;
        }
        
        $category = $this->db->table('categories')->find($categoryId);
        if (!$category) {
            return ['valid' => false, 'message' => 'Invalid category.'];
        }
        
        $age = $this->getAge();
        if (!$age) {
            return ['valid' => false, 'message' => 'Date of birth required for age validation.'];
        }
        
        // Check age limits for category
        if (isset($category['min_age']) && $age < $category['min_age']) {
            return [
                'valid' => false, 
                'message' => "Participant is too young for {$category['name']} category. Minimum age: {$category['min_age']}"
            ];
        }
        
        if (isset($category['max_age']) && $age > $category['max_age']) {
            return [
                'valid' => false, 
                'message' => "Participant is too old for {$category['name']} category. Maximum age: {$category['max_age']}"
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check if participant has valid consent forms
     */
    public function hasValidConsent()
    {
        return $this->db->table('consent_forms')
            ->where('participant_id', $this->id)
            ->where('status', self::CONSENT_APPROVED)
            ->exists();
    }
    
    /**
     * Get consent form status
     */
    public function getConsentStatus()
    {
        $consentForm = $this->db->table('consent_forms')
            ->where('participant_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->first();
            
        return $consentForm ? $consentForm['status'] : self::CONSENT_PENDING;
    }
    
    /**
     * Check for duplicate participants across teams
     */
    public function checkForDuplicates()
    {
        $query = $this->db->table('participants')
            ->where('first_name', $this->first_name)
            ->where('last_name', $this->last_name)
            ->where('date_of_birth', $this->date_of_birth)
            ->where('id', '!=', $this->id ?? 0)
            ->whereNull('deleted_at');
            
        // Add ID number check if available
        if ($this->id_number) {
            $query->orWhere('id_number', $this->id_number);
        }
        
        $duplicates = $query->get();
        
        if (!empty($duplicates)) {
            $teamIds = array_column($duplicates, 'team_id');
            $teams = $this->db->table('teams')
                ->whereIn('id', $teamIds)
                ->get();
                
            return [
                'has_duplicates' => true,
                'duplicates' => $duplicates,
                'teams' => $teams,
                'message' => 'Participant appears to be registered in multiple teams.'
            ];
        }
        
        return ['has_duplicates' => false];
    }
    
    /**
     * Get full name
     */
    public function getFullName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    
    /**
     * Check medical conditions and special requirements
     */
    public function hasSpecialRequirements()
    {
        return !empty($this->medical_conditions) || 
               !empty($this->dietary_restrictions) || 
               !empty($this->special_needs);
    }
    
    /**
     * Get participant's document requirements
     */
    public function getDocumentRequirements()
    {
        $requirements = [];
        
        // Check consent form
        if (!$this->hasValidConsent()) {
            $requirements[] = 'Signed consent form required';
        }
        
        // Check ID document if ID number is provided
        if ($this->id_number && !$this->hasIdDocument()) {
            $requirements[] = 'Copy of ID document required';
        }
        
        // Check medical certificate if has medical conditions
        if ($this->medical_conditions && !$this->hasMedicalCertificate()) {
            $requirements[] = 'Medical certificate required due to medical conditions';
        }
        
        return $requirements;
    }
    
    /**
     * Check if ID document is uploaded
     */
    private function hasIdDocument()
    {
        // This would check for uploaded documents in the system
        return $this->db->table('participant_documents')
            ->where('participant_id', $this->id)
            ->where('document_type', 'id_document')
            ->where('status', 'approved')
            ->exists();
    }
    
    /**
     * Check if medical certificate is uploaded
     */
    private function hasMedicalCertificate()
    {
        return $this->db->table('participant_documents')
            ->where('participant_id', $this->id)
            ->where('document_type', 'medical_certificate')
            ->where('status', 'approved')
            ->exists();
    }
    
    /**
     * Search participants with privacy considerations
     */
    public static function searchWithPrivacy($criteria = [], $userRole = 'guest')
    {
        $model = new static();
        $query = $model->db->table($model->table);
        
        // Apply soft delete filter
        $query->whereNull('deleted_at');
        
        // Privacy filters based on user role
        if ($userRole === 'guest' || $userRole === 'public') {
            // Very limited search for public users
            if (!empty($criteria['team_id'])) {
                $query->where('team_id', $criteria['team_id']);
            }
            // Only return basic info
            $query->select(['id', 'first_name', 'last_name', 'team_id']);
        } else {
            // Full search for authorized users
            if (!empty($criteria['name'])) {
                $query->where(function($q) use ($criteria) {
                    $q->where('first_name', 'LIKE', '%' . $criteria['name'] . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $criteria['name'] . '%');
                });
            }
            
            if (!empty($criteria['team_id'])) {
                $query->where('team_id', $criteria['team_id']);
            }
            
            if (!empty($criteria['grade'])) {
                $query->where('grade', $criteria['grade']);
            }
            
            if (!empty($criteria['gender'])) {
                $query->where('gender', $criteria['gender']);
            }
            
            if (!empty($criteria['consent_status'])) {
                $query->where('consent_status', $criteria['consent_status']);
            }
        }
        
        $results = $query->orderBy('last_name')->orderBy('first_name')->get();
        return $model->collection($results);
    }
    
    /**
     * Get available genders
     */
    public static function getAvailableGenders()
    {
        return [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
            self::GENDER_OTHER => 'Other'
        ];
    }
    
    /**
     * Get available consent statuses
     */
    public static function getAvailableConsentStatuses()
    {
        return [
            self::CONSENT_PENDING => 'Pending',
            self::CONSENT_APPROVED => 'Approved',
            self::CONSENT_REJECTED => 'Rejected'
        ];
    }
    
    /**
     * Scope: Participants by consent status
     */
    public function scopeByConsentStatus($query, $status)
    {
        return $query->where('consent_status', $status);
    }
    
    /**
     * Scope: Participants by grade
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->where('grade', $grade);
    }
    
    /**
     * Scope: Participants with special requirements
     */
    public function scopeWithSpecialRequirements($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('medical_conditions')
              ->orWhereNotNull('dietary_restrictions')
              ->orWhereNotNull('special_needs');
        });
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['full_name'] = $this->getFullName();
        $attributes['age'] = $this->getAge();
        $attributes['has_valid_consent'] = $this->hasValidConsent();
        $attributes['has_special_requirements'] = $this->hasSpecialRequirements();
        $attributes['document_requirements'] = $this->getDocumentRequirements();
        $attributes['consent_status_label'] = self::getAvailableConsentStatuses()[$this->consent_status] ?? $this->consent_status;
        $attributes['gender_label'] = self::getAvailableGenders()[$this->gender] ?? $this->gender;
        
        return $attributes;
    }
}