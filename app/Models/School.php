<?php

namespace App\Models;

class School extends BaseModel
{
    protected $table = 'schools';
    protected $fillable = [
        'name', 'contact_person', 'contact_email', 'contact_phone', 
        'address', 'district', 'province', 'postal_code', 
        'status', 'registration_date', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $softDeletes = true;
    
    // Validation rules
    protected $rules = [
        'name' => 'required|max:255|unique',
        'contact_person' => 'required|max:255',
        'contact_email' => 'required|email|max:255',
        'contact_phone' => 'required|max:20',
        'address' => 'required|max:500',
        'district' => 'required|max:100',
        'province' => 'required|max:100',
        'status' => 'required'
    ];
    
    protected $messages = [
        'name.required' => 'School name is required.',
        'name.unique' => 'A school with this name already exists.',
        'contact_email.email' => 'Please provide a valid email address.',
        'contact_email.required' => 'Contact email is required.',
        'contact_person.required' => 'Contact person name is required.',
        'contact_phone.required' => 'Contact phone number is required.',
        'address.required' => 'School address is required.',
        'district.required' => 'District is required.',
        'province.required' => 'Province is required.',
        'status.required' => 'School status is required.'
    ];
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';
    
    // Districts (South African provinces/districts)
    const DISTRICTS = [
        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
        'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'
    ];
    
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
                    ->where('role', 'coordinator');
    }
    
    /**
     * Relationship: School has many participants through teams
     */
    public function participants()
    {
        // Get all participants from all teams belonging to this school
        $teamIds = $this->db->table('teams')
            ->where('school_id', $this->id)
            ->whereNull('deleted_at')
            ->pluck('id');
            
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
        // Check if registration deadline has passed
        $registrationDeadline = $this->db->table('settings')
            ->where('key', 'registration_deadline')
            ->value('value');
            
        if ($registrationDeadline && strtotime($registrationDeadline) < time()) {
            return false;
        }
        
        return $this->status === self::STATUS_ACTIVE;
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
     * Search schools by various criteria
     */
    public static function search($criteria = [])
    {
        $model = new static();
        $query = $model->db->table($model->table);
        
        // Apply soft delete filter
        $query->whereNull('deleted_at');
        
        if (!empty($criteria['name'])) {
            $query->where('name', 'LIKE', '%' . $criteria['name'] . '%');
        }
        
        if (!empty($criteria['district'])) {
            $query->where('district', $criteria['district']);
        }
        
        if (!empty($criteria['province'])) {
            $query->where('province', $criteria['province']);
        }
        
        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }
        
        if (!empty($criteria['contact_email'])) {
            $query->where('contact_email', 'LIKE', '%' . $criteria['contact_email'] . '%');
        }
        
        // Date range filters
        if (!empty($criteria['registered_from'])) {
            $query->where('registration_date', '>=', $criteria['registered_from']);
        }
        
        if (!empty($criteria['registered_to'])) {
            $query->where('registration_date', '<=', $criteria['registered_to']);
        }
        
        // Participation history filter
        if (!empty($criteria['has_teams'])) {
            $schoolIds = $model->db->table('teams')
                ->select('school_id')
                ->distinct()
                ->whereNull('deleted_at')
                ->pluck('school_id');
            
            if ($criteria['has_teams'] === 'yes') {
                $query->whereIn('id', $schoolIds);
            } else {
                $query->whereNotIn('id', $schoolIds);
            }
        }
        
        $results = $query->orderBy('name')->get();
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
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_SUSPENDED => 'Suspended'
        ];
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
        $attributes['can_register'] = $this->isRegistrationOpen();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}