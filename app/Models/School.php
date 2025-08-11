<?php

namespace App\Models;

class School extends BaseModel
{
    protected $table = 'schools';
    protected $fillable = [
        'name', 'emis_number', 'registration_number', 'school_type', 'quintile',
        'district_id', 'province', 'address_line1', 'address_line2', 'city', 'postal_code',
        'phone', 'fax', 'email', 'website', 'gps_coordinates',
        'principal_name', 'principal_email', 'principal_phone',
        'coordinator_id', 'establishment_date', 'total_learners',
        'facilities', 'computer_lab', 'internet_status', 'accessibility_features',
        'previous_participation', 'communication_preference', 'logo_path',
        'status', 'registration_date', 'approval_date', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $softDeletes = true;
    
    // Validation rules
    protected $rules = [
        'name' => 'required|max:100|min:5|unique',
        'emis_number' => 'unique|regex:/^[0-9]{8,12}$/',
        'registration_number' => 'required|unique|regex:/^[0-9]{8,12}$/',
        'school_type' => 'required',
        'district_id' => 'required|exists:districts,id',
        'province' => 'required|max:50',
        'address_line1' => 'required|min:20|max:200',
        'city' => 'required|max:100',
        'postal_code' => 'required|regex:/^[0-9]{4}$/',
        'email' => 'required|email|unique|max:255',
        'phone' => 'required|regex:/^[0-9\-\+\(\)\s]{10,15}$/',
        'principal_name' => 'required|alpha_spaces|max:100',
        'principal_email' => 'required|email|different:email|max:255',
        'total_learners' => 'required|integer|min:50|max:5000',
        'status' => 'required'
    ];
    
    protected $messages = [
        'name.required' => 'School name is required.',
        'name.unique' => 'A school with this name already exists.',
        'name.min' => 'School name must be at least 5 characters.',
        'emis_number.unique' => 'A school with this EMIS number already exists.',
        'emis_number.regex' => 'EMIS number must be 8-12 digits.',
        'registration_number.required' => 'Registration number is required.',
        'registration_number.unique' => 'A school with this registration number already exists.',
        'registration_number.regex' => 'Registration number must be 8-12 digits.',
        'school_type.required' => 'School type is required.',
        'district_id.required' => 'District is required.',
        'district_id.exists' => 'Selected district does not exist.',
        'province.required' => 'Province is required.',
        'address_line1.required' => 'Address is required.',
        'address_line1.min' => 'Address must be at least 20 characters.',
        'city.required' => 'City is required.',
        'postal_code.required' => 'Postal code is required.',
        'postal_code.regex' => 'Postal code must be 4 digits.',
        'email.required' => 'Email address is required.',
        'email.email' => 'Please provide a valid email address.',
        'email.unique' => 'A school with this email already exists.',
        'phone.required' => 'Phone number is required.',
        'phone.regex' => 'Please provide a valid phone number.',
        'principal_name.required' => 'Principal name is required.',
        'principal_name.alpha_spaces' => 'Principal name may only contain letters and spaces.',
        'principal_email.required' => 'Principal email is required.',
        'principal_email.email' => 'Please provide a valid principal email address.',
        'principal_email.different' => 'Principal email must be different from school email.',
        'total_learners.required' => 'Total number of learners is required.',
        'total_learners.integer' => 'Total learners must be a number.',
        'total_learners.min' => 'School must have at least 50 learners.',
        'total_learners.max' => 'Total learners cannot exceed 5000.',
        'status.required' => 'School status is required.'
    ];
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_ARCHIVED = 'archived';
    
    // School type constants
    const TYPE_PRIMARY = 'primary';
    const TYPE_SECONDARY = 'secondary';
    const TYPE_COMBINED = 'combined';
    const TYPE_SPECIAL = 'special';
    
    // Quintile constants (South African school classification)
    const QUINTILE_1 = 1; // No fees
    const QUINTILE_2 = 2; // No fees
    const QUINTILE_3 = 3; // No fees
    const QUINTILE_4 = 4; // Fees
    const QUINTILE_5 = 5; // Fees
    
    // Communication preference constants
    const COMM_EMAIL = 'email';
    const COMM_PHONE = 'phone';
    const COMM_SMS = 'sms';
    const COMM_POSTAL = 'postal';
    
    // South African provinces
    const PROVINCES = [
        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
        'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'
    ];
    
    /**
     * Relationship: School belongs to a district
     */
    public function district()
    {
        return $this->belongsTo('App\Models\District', 'district_id', 'id');
    }
    
    /**
     * Relationship: School has many contacts
     */
    public function contacts()
    {
        return $this->hasMany('App\Models\Contact', 'school_id', 'id');
    }
    
    /**
     * Relationship: School has a primary coordinator
     */
    public function coordinator()
    {
        return $this->belongsTo('App\Models\User', 'coordinator_id', 'id');
    }
    
    /**
     * Relationship: School has many teams
     */
    public function teams()
    {
        return $this->hasMany('App\Models\Team', 'school_id', 'id');
    }
    
    /**
     * Relationship: School has many coordinators (users)
     */
    public function coordinators()
    {
        return $this->hasMany('App\Models\User', 'school_id', 'id')
                    ->where('role', 'school_coordinator');
    }
    
    /**
     * Get primary contact
     */
    public function primaryContact()
    {
        return $this->hasOne('App\Models\Contact', 'school_id', 'id')
                    ->where('is_primary', 1)
                    ->where('status', 'active');
    }
    
    /**
     * Get emergency contacts
     */
    public function emergencyContacts()
    {
        return $this->hasMany('App\Models\Contact', 'school_id', 'id')
                    ->where('is_emergency', 1)
                    ->where('status', 'active');
    }
    
    /**
     * Relationship: School has many participants through teams
     */
    public function participants()
    {
        // Get all participants from all teams belonging to this school
        $teams = $this->db->table('teams')
            ->select('id')
            ->where('school_id', $this->id)
            ->whereNull('deleted_at')
            ->get();
            
        $teamIds = array_column($teams, 'id');
            
        if (empty($teamIds)) {
            return [];
        }
        
        $participants = $this->db->table('participants')
            ->whereIn('team_id', $teamIds)
            ->whereNull('deleted_at')
            ->get();
            
        return array_map(function($participant) {
            $participantModel = new \App\Models\Participant();
            return $participantModel->newInstance($participant);
        }, $participants);
    }
    
    /**
     * Get active teams for this school
     */
    public function activeTeams()
    {
        return $this->hasMany('App\Models\Team', 'school_id', 'id')
                    ->where('status', 'active');
    }
    
    /**
     * Get teams by category
     */
    public function teamsByCategory($categoryId)
    {
        return $this->db->table('teams')
            ->where('school_id', $this->id)
            ->where('category_id', $categoryId)
            ->whereNull('deleted_at')
            ->get();
    }
    
    /**
     * Get team count by category
     */
    public function getTeamCountByCategory($categoryId)
    {
        return $this->db->table('teams')
            ->where('school_id', $this->id)
            ->where('category_id', $categoryId)
            ->whereNull('deleted_at')
            ->count();
    }
    
    /**
     * Check if school can register for category (max 1 team per category)
     */
    public function canRegisterForCategory($categoryId)
    {
        $existingTeams = $this->getTeamCountByCategory($categoryId);
        return $existingTeams === 0;
    }
    
    /**
     * Get total participant count
     */
    public function getTotalParticipantCount()
    {
        return count($this->participants());
    }
    
    /**
     * Check registration status
     */
    public function isRegistrationOpen()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        
        // Use deadline manager for accurate deadline checking
        $deadlineManager = new \App\Core\RegistrationDeadlineManager();
        return $deadlineManager->isSchoolRegistrationOpen();
    }
    
    /**
     * Get school's competition history
     */
    public function getCompetitionHistory()
    {
        return $this->db->table('teams')
            ->select(['teams.*', 'categories.name as category_name', 'phases.name as phase_name'])
            ->leftJoin('categories', 'teams.category_id', '=', 'categories.id')
            ->leftJoin('phases', 'teams.phase_id', '=', 'phases.id')
            ->where('teams.school_id', $this->id)
            ->orderBy('teams.created_at', 'DESC')
            ->get();
    }
    
    /**
     * Get full address
     */
    public function getFullAddress()
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->postal_code
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Get district name
     */
    public function getDistrictName()
    {
        if ($this->district_id) {
            $district = $this->district();
            return $district ? $district['name'] : 'Unknown District';
        }
        return null;
    }
    
    /**
     * Get school type label
     */
    public function getSchoolTypeLabel()
    {
        return self::getAvailableSchoolTypes()[$this->school_type] ?? $this->school_type;
    }
    
    /**
     * Get status label with color
     */
    public function getStatusInfo()
    {
        $statuses = [
            self::STATUS_PENDING => ['label' => 'Pending Approval', 'color' => 'warning'],
            self::STATUS_ACTIVE => ['label' => 'Active', 'color' => 'success'],
            self::STATUS_INACTIVE => ['label' => 'Inactive', 'color' => 'secondary'],
            self::STATUS_SUSPENDED => ['label' => 'Suspended', 'color' => 'danger'],
            self::STATUS_ARCHIVED => ['label' => 'Archived', 'color' => 'dark']
        ];
        
        return $statuses[$this->status] ?? ['label' => $this->status, 'color' => 'secondary'];
    }
    
    /**
     * Check if school can register new teams
     */
    public function canRegisterTeams()
    {
        return in_array($this->status, [self::STATUS_ACTIVE]) && 
               $this->isRegistrationOpen();
    }
    
    /**
     * Get contact by type
     */
    public function getContactByType($type)
    {
        return $this->db->table('contacts')
            ->where('school_id', $this->id)
            ->where('contact_type', $type)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('is_primary', 'DESC')
            ->first();
    }
    
    /**
     * Update or create contact
     */
    public function updateContact($type, $data)
    {
        $data['school_id'] = $this->id;
        $data['contact_type'] = $type;
        
        $existing = $this->getContactByType($type);
        
        if ($existing) {
            return $this->db->table('contacts')
                ->where('id', $existing['id'])
                ->update($data);
        } else {
            return $this->db->table('contacts')->insert($data);
        }
    }
    
    /**
     * Search schools by various criteria
     */
    public static function search($criteria = [])
    {
        $model = new static();
        $query = $model->db->table($model->table . ' s')
            ->leftJoin('districts d', 's.district_id', '=', 'd.id')
            ->leftJoin('users u', 's.coordinator_id', '=', 'u.id')
            ->select([
                's.*', 
                'd.name as district_name', 
                'd.province as district_province',
                'u.first_name as coordinator_first_name',
                'u.last_name as coordinator_last_name',
                'u.email as coordinator_email'
            ]);
        
        // Apply soft delete filter
        $query->whereNull('s.deleted_at');
        
        if (!empty($criteria['name'])) {
            $query->where('s.name', 'LIKE', '%' . $criteria['name'] . '%');
        }
        
        if (!empty($criteria['district_id'])) {
            $query->where('s.district_id', $criteria['district_id']);
        }
        
        if (!empty($criteria['province'])) {
            $query->where('s.province', $criteria['province']);
        }
        
        if (!empty($criteria['school_type'])) {
            $query->where('s.school_type', $criteria['school_type']);
        }
        
        if (!empty($criteria['quintile'])) {
            $query->where('s.quintile', $criteria['quintile']);
        }
        
        if (!empty($criteria['status'])) {
            if (is_array($criteria['status'])) {
                $query->whereIn('s.status', $criteria['status']);
            } else {
                $query->where('s.status', $criteria['status']);
            }
        }
        
        if (!empty($criteria['email'])) {
            $query->where('s.email', 'LIKE', '%' . $criteria['email'] . '%');
        }
        
        if (!empty($criteria['phone'])) {
            $query->where('s.phone', 'LIKE', '%' . $criteria['phone'] . '%');
        }
        
        if (!empty($criteria['emis_number'])) {
            $query->where('s.emis_number', 'LIKE', '%' . $criteria['emis_number'] . '%');
        }
        
        if (!empty($criteria['registration_number'])) {
            $query->where('s.registration_number', 'LIKE', '%' . $criteria['registration_number'] . '%');
        }
        
        // Date range filters
        if (!empty($criteria['registered_from'])) {
            $query->where('s.registration_date', '>=', $criteria['registered_from']);
        }
        
        if (!empty($criteria['registered_to'])) {
            $query->where('s.registration_date', '<=', $criteria['registered_to']);
        }
        
        // Participation history filter
        if (!empty($criteria['has_teams'])) {
            $schools = $model->db->table('teams')
                ->select('school_id')
                ->distinct()
                ->whereNull('deleted_at')
                ->get();
            
            $schoolIds = array_column($schools, 'school_id');
            
            if ($criteria['has_teams'] === 'yes') {
                $query->whereIn('s.id', $schoolIds);
            } else {
                $query->whereNotIn('s.id', $schoolIds);
            }
        }
        
        // Learner count range
        if (!empty($criteria['min_learners'])) {
            $query->where('s.total_learners', '>=', $criteria['min_learners']);
        }
        
        if (!empty($criteria['max_learners'])) {
            $query->where('s.total_learners', '<=', $criteria['max_learners']);
        }
        
        // Search in principal name
        if (!empty($criteria['principal_name'])) {
            $query->where('s.principal_name', 'LIKE', '%' . $criteria['principal_name'] . '%');
        }
        
        // Sorting
        $sortBy = isset($criteria['sort_by']) ? $criteria['sort_by'] : 'name';
        $sortOrder = isset($criteria['sort_order']) && $criteria['sort_order'] === 'desc' ? 'DESC' : 'ASC';
        
        switch ($sortBy) {
            case 'district':
                $query->orderBy('d.name', $sortOrder);
                break;
            case 'status':
                $query->orderBy('s.status', $sortOrder);
                break;
            case 'registration_date':
                $query->orderBy('s.registration_date', $sortOrder);
                break;
            case 'total_learners':
                $query->orderBy('s.total_learners', $sortOrder);
                break;
            case 'school_type':
                $query->orderBy('s.school_type', $sortOrder);
                break;
            default:
                $query->orderBy('s.name', $sortOrder);
        }
        
        $results = $query->get();
        return $model->collection($results);
    }
    
    /**
     * Get schools by district with team counts
     */
    public static function getByDistrictWithStats()
    {
        $model = new static();
        $results = $model->db->query("
            SELECT 
                s.*,
                COUNT(DISTINCT t.id) as team_count,
                COUNT(DISTINCT p.id) as participant_count
            FROM schools s
            LEFT JOIN teams t ON s.id = t.school_id AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE s.deleted_at IS NULL
            GROUP BY s.id
            ORDER BY s.district, s.name
        ");
        
        return $model->collection($results);
    }
    
    /**
     * Get district statistics
     */
    public static function getDistrictStats()
    {
        $model = new static();
        return $model->db->query("
            SELECT 
                district,
                COUNT(*) as school_count,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_schools,
                COUNT(DISTINCT t.id) as total_teams,
                COUNT(DISTINCT p.id) as total_participants
            FROM schools s
            LEFT JOIN teams t ON s.id = t.school_id AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE s.deleted_at IS NULL
            GROUP BY district
            ORDER BY district
        ");
    }
    
    /**
     * Scope: Active schools
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Schools by district
     */
    public function scopeByDistrict($query, $district)
    {
        return $query->where('district', $district);
    }
    
    /**
     * Scope: Schools with teams
     */
    public function scopeWithTeams($query)
    {
        return $query->whereExists(function($subquery) {
            $subquery->select(1)
                    ->from('teams')
                    ->where('teams.school_id', '=', 'schools.id')
                    ->whereNull('teams.deleted_at');
        });
    }
    
    /**
     * Scope: Schools without teams
     */
    public function scopeWithoutTeams($query)
    {
        return $query->whereNotExists(function($subquery) {
            $subquery->select(1)
                    ->from('teams')
                    ->where('teams.school_id', '=', 'schools.id')
                    ->whereNull('teams.deleted_at');
        });
    }
    
    /**
     * Business logic: Validate team limits per category
     */
    public function validateTeamLimits($categoryId)
    {
        $teamCount = $this->getTeamCountByCategory($categoryId);
        if ($teamCount >= 1) {
            return [
                'valid' => false,
                'message' => 'School can only have one team per category. This school already has a team in this category.'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Business logic: Check document requirements
     */
    public function checkDocumentRequirements()
    {
        $requirements = [];
        
        // Check if school has active teams
        $activeTeams = $this->activeTeams();
        
        if (!empty($activeTeams)) {
            foreach ($activeTeams as $team) {
                // Check participant consent forms
                $participants = $this->db->table('participants')
                    ->where('team_id', $team['id'])
                    ->whereNull('deleted_at')
                    ->get();
                
                foreach ($participants as $participant) {
                    $consentForms = $this->db->table('consent_forms')
                        ->where('participant_id', $participant['id'])
                        ->where('status', 'approved')
                        ->count();
                    
                    if ($consentForms === 0) {
                        $requirements[] = "Missing consent form for participant: {$participant['first_name']} {$participant['last_name']}";
                    }
                }
            }
        }
        
        return $requirements;
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_ARCHIVED => 'Archived'
        ];
    }
    
    /**
     * Get available school types
     */
    public static function getAvailableSchoolTypes()
    {
        return [
            self::TYPE_PRIMARY => 'Primary School',
            self::TYPE_SECONDARY => 'Secondary School',
            self::TYPE_COMBINED => 'Combined School',
            self::TYPE_SPECIAL => 'Special Needs School'
        ];
    }
    
    /**
     * Get available quintiles
     */
    public static function getAvailableQuintiles()
    {
        return [
            self::QUINTILE_1 => 'Quintile 1 (No fees)',
            self::QUINTILE_2 => 'Quintile 2 (No fees)',
            self::QUINTILE_3 => 'Quintile 3 (No fees)',
            self::QUINTILE_4 => 'Quintile 4 (Fees)',
            self::QUINTILE_5 => 'Quintile 5 (Fees)'
        ];
    }
    
    /**
     * Get communication preferences
     */
    public static function getCommunicationPreferences()
    {
        return [
            self::COMM_EMAIL => 'Email',
            self::COMM_PHONE => 'Phone',
            self::COMM_SMS => 'SMS',
            self::COMM_POSTAL => 'Postal Mail'
        ];
    }
    
    /**
     * Bulk operations for school management
     */
    public static function bulkUpdateStatus($schoolIds, $status, $reason = null)
    {
        $model = new static();
        $updates = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === self::STATUS_ACTIVE) {
            $updates['approval_date'] = date('Y-m-d H:i:s');
        }
        
        if ($reason) {
            $updates['notes'] = $reason;
        }
        
        return $model->db->table($model->table)
            ->whereIn('id', $schoolIds)
            ->update($updates);
    }
    
    /**
     * Export schools data for reporting
     */
    public static function exportData($criteria = [], $format = 'array')
    {
        $schools = self::search($criteria);
        
        $data = [];
        foreach ($schools as $school) {
            $row = [
                'ID' => $school['id'],
                'Name' => $school['name'],
                'EMIS Number' => $school['emis_number'],
                'Registration Number' => $school['registration_number'],
                'School Type' => $school->getSchoolTypeLabel(),
                'Quintile' => $school['quintile'],
                'District' => $school['district_name'] ?? 'Unknown',
                'Province' => $school['province'],
                'Address' => $school->getFullAddress(),
                'Phone' => $school['phone'],
                'Email' => $school['email'],
                'Principal' => $school['principal_name'],
                'Principal Email' => $school['principal_email'],
                'Total Learners' => $school['total_learners'],
                'Status' => $school->getStatusInfo()['label'],
                'Registration Date' => $school['registration_date'],
                'Team Count' => count($school->teams()),
                'Participant Count' => $school->getTotalParticipantCount()
            ];
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Get schools requiring attention (pending approvals, missing documents, etc.)
     */
    public static function getSchoolsRequiringAttention()
    {
        $model = new static();
        $results = [];
        
        // Pending approvals
        $pendingSchools = $model->db->table($model->table)
            ->where('status', self::STATUS_PENDING)
            ->whereNull('deleted_at')
            ->count();
        
        // Schools with missing coordinator
        $missingCoordinator = $model->db->table($model->table)
            ->whereNull('coordinator_id')
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->count();
        
        // Schools with no teams registered
        $noTeams = $model->db->query("
            SELECT COUNT(*) as count
            FROM schools s
            LEFT JOIN teams t ON s.id = t.school_id AND t.deleted_at IS NULL
            WHERE s.status = ? AND s.deleted_at IS NULL AND t.id IS NULL
        ", [self::STATUS_ACTIVE]);
        
        return [
            'pending_approvals' => $pendingSchools,
            'missing_coordinator' => $missingCoordinator,
            'no_teams' => $noTeams[0]['count'] ?? 0
        ];
    }
    
    /**
     * Get schools by status
     */
    public function getByStatus($status)
    {
        return $this->db->table($this->table)
            ->where('status', $status)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['team_count'] = count($this->teams());
        $attributes['participant_count'] = $this->getTotalParticipantCount();
        $attributes['can_register'] = $this->canRegisterTeams();
        $attributes['full_address'] = $this->getFullAddress();
        $attributes['district_name'] = $this->getDistrictName();
        $attributes['school_type_label'] = $this->getSchoolTypeLabel();
        $attributes['status_info'] = $this->getStatusInfo();
        $attributes['status_label'] = $this->getStatusInfo()['label'];
        $attributes['quintile_label'] = self::getAvailableQuintiles()[$this->quintile] ?? "Quintile {$this->quintile}";
        $attributes['communication_preference_label'] = self::getCommunicationPreferences()[$this->communication_preference] ?? $this->communication_preference;
        
        return $attributes;
    }
}