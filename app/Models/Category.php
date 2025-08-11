<?php

namespace App\Models;

class Category extends BaseModel
{
    protected $table = 'categories';
    protected $softDeletes = true;
    
    protected $fillable = [
        'name', 'code', 'description', 'min_age', 'max_age', 'min_grade', 'max_grade',
        'equipment_requirements', 'scoring_rubric', 'max_teams_per_school', 
        'competition_duration', 'status', 'rules_document', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'name' => 'required|max:255|unique',
        'code' => 'required|max:20|unique',
        'description' => 'max:1000',
        'min_age' => 'required|min:1|max:25',
        'max_age' => 'required|min:1|max:25',
        'min_grade' => 'required',
        'max_grade' => 'required',
        'status' => 'required'
    ];
    
    protected $messages = [
        'name.required' => 'Category name is required.',
        'name.unique' => 'Category name must be unique.',
        'code.required' => 'Category code is required.',
        'code.unique' => 'Category code must be unique.',
        'min_age.required' => 'Minimum age is required.',
        'max_age.required' => 'Maximum age is required.',
        'min_grade.required' => 'Minimum grade is required.',
        'max_grade.required' => 'Maximum grade is required.',
        'status.required' => 'Category status is required.'
    ];
    
    // Category constants based on GDE SciBOTICS 2025 Competition
    const CATEGORY_JUNIOR = 'JUNIOR';
    const CATEGORY_EXPLORER = 'EXPLORER';
    const CATEGORY_ARDUINO = 'ARDUINO';
    const CATEGORY_INVENTOR = 'INVENTOR';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT = 'draft';
    
    // Equipment types for 2025 Competition
    const EQUIPMENT_CUBROID = 'Cubroid';
    const EQUIPMENT_LEGO_SPIKE = 'LEGO Spike Prime';
    const EQUIPMENT_ARDUINO = 'Arduino/Open Hardware';
    const EQUIPMENT_MIXED = 'Any Technology';
    
    protected $hasMany = [
        'teams' => ['model' => Team::class, 'foreign_key' => 'category_id']
    ];
    
    /**
     * Get default categories for 2025 GDE SciBOTICS Competition
     */
    public static function getDefaultCategories()
    {
        return [
            [
                'name' => 'Junior Category',
                'code' => self::CATEGORY_JUNIOR,
                'description' => 'Life on the Red Planet - For Grade R-3 using Cubroid robotics kits',
                'min_age' => 5,
                'max_age' => 9,
                'min_grade' => 'Grade R',
                'max_grade' => 'Grade 3',
                'equipment_requirements' => self::EQUIPMENT_CUBROID,
                'competition_duration' => 120, // minutes
                'max_teams_per_school' => 1,
                'status' => self::STATUS_ACTIVE
            ],
            [
                'name' => 'Explorer Category',
                'code' => self::CATEGORY_EXPLORER,
                'description' => 'Cosmic Cargo (Grade 4-7) or Lost in Space (Grade 8-9) using LEGO Spike Prime',
                'min_age' => 9,
                'max_age' => 15,
                'min_grade' => 'Grade 4',
                'max_grade' => 'Grade 9',
                'equipment_requirements' => self::EQUIPMENT_LEGO_SPIKE,
                'competition_duration' => 150,
                'max_teams_per_school' => 1,
                'status' => self::STATUS_ACTIVE
            ],
            [
                'name' => 'Arduino Category',
                'code' => self::CATEGORY_ARDUINO,
                'description' => 'Thunderdrome (Grade 8-9) or Yellow Planet (Grade 10-11) using Arduino/Open Hardware',
                'min_age' => 13,
                'max_age' => 17,
                'min_grade' => 'Grade 8',
                'max_grade' => 'Grade 11',
                'equipment_requirements' => self::EQUIPMENT_ARDUINO,
                'competition_duration' => 180,
                'max_teams_per_school' => 1,
                'status' => self::STATUS_ACTIVE
            ],
            [
                'name' => 'Inventor Category',
                'code' => self::CATEGORY_INVENTOR,
                'description' => 'Open innovation category for all grades using any technology',
                'min_age' => 5,
                'max_age' => 17,
                'min_grade' => 'Grade R',
                'max_grade' => 'Grade 11',
                'equipment_requirements' => self::EQUIPMENT_MIXED,
                'competition_duration' => 200,
                'max_teams_per_school' => 1,
                'status' => self::STATUS_ACTIVE
            ]
        ];
    }
    
    /**
     * Get category teams
     */
    public function teams()
    {
        return $this->hasMany('App\Models\Team', 'category_id', 'id');
    }
    
    /**
     * Get active teams for this category
     */
    public function activeTeams()
    {
        return $this->db->table('teams')
            ->where('category_id', $this->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();
    }
    
    /**
     * Get team count for this category
     */
    public function getTeamCount()
    {
        return $this->db->table('teams')
            ->where('category_id', $this->id)
            ->whereNull('deleted_at')
            ->count();
    }
    
    /**
     * Get participant count for this category
     */
    public function getParticipantCount()
    {
        $query = "
            SELECT COUNT(p.id) as participant_count
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            WHERE t.category_id = ?
            AND p.deleted_at IS NULL
            AND t.deleted_at IS NULL
        ";
        
        $result = $this->db->query($query, [$this->id]);
        return $result[0]['participant_count'] ?? 0;
    }
    
    /**
     * Validate participant age for this category
     */
    public function validateParticipantAge($age)
    {
        if ($age < $this->min_age) {
            return [
                'valid' => false,
                'message' => "Participant is too young for {$this->name}. Minimum age: {$this->min_age}"
            ];
        }
        
        if ($age > $this->max_age) {
            return [
                'valid' => false,
                'message' => "Participant is too old for {$this->name}. Maximum age: {$this->max_age}"
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate participant grade for this category (2025 Competition)
     */
    public function validateParticipantGrade($grade)
    {
        $gradeNumbers = [
            'Grade R' => 0, 'Grade 1' => 1, 'Grade 2' => 2, 'Grade 3' => 3,
            'Grade 4' => 4, 'Grade 5' => 5, 'Grade 6' => 6, 'Grade 7' => 7,
            'Grade 8' => 8, 'Grade 9' => 9, 'Grade 10' => 10, 'Grade 11' => 11, 'Grade 12' => 12
        ];
        
        $participantGradeNum = $gradeNumbers[$grade] ?? -1;
        $minGradeNum = $gradeNumbers[$this->min_grade] ?? -1;
        $maxGradeNum = $gradeNumbers[$this->max_grade] ?? -1;
        
        if ($participantGradeNum === -1) {
            return [
                'valid' => false,
                'message' => "Invalid grade format: {$grade}. Expected format: 'Grade R', 'Grade 1', etc."
            ];
        }
        
        if ($participantGradeNum < $minGradeNum) {
            return [
                'valid' => false,
                'message' => "Participant grade is too low for {$this->name}. Minimum grade: {$this->min_grade}"
            ];
        }
        
        if ($participantGradeNum > $maxGradeNum) {
            return [
                'valid' => false,
                'message' => "Participant grade is too high for {$this->name}. Maximum grade: {$this->max_grade}"
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get scoring rubric as array
     */
    public function getScoringRubric()
    {
        if (!$this->scoring_rubric) {
            return $this->getDefaultScoringRubric();
        }
        
        return json_decode($this->scoring_rubric, true) ?? $this->getDefaultScoringRubric();
    }
    
    /**
     * Get default scoring rubric for 2025 competition category
     */
    public function getDefaultScoringRubric()
    {
        switch ($this->code) {
            case self::CATEGORY_JUNIOR:
                // Junior (Grade R-3): Life on the Red Planet
                return [
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ];
                
            case self::CATEGORY_EXPLORER:
                // Explorer: Cosmic Cargo (4-7) or Lost in Space (8-9)
                return [
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ];
                
            case self::CATEGORY_ARDUINO:
                // Arduino: Thunderdrome (8-9) or Yellow Planet (10-11)
                return [
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ];
                
            case self::CATEGORY_INVENTOR:
                // Inventor: All grades, any technology
                return [
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'innovation_creativity' => ['weight' => 25, 'max_score' => 25],
                    'feasibility_impact' => ['weight' => 20, 'max_score' => 20],
                    'presentation' => ['weight' => 15, 'max_score' => 15]
                ];
                
            default:
                return [
                    'overall_performance' => ['weight' => 100, 'max_score' => 100]
                ];
        }
    }
    
    /**
     * Get equipment requirements as array
     */
    public function getEquipmentRequirements()
    {
        if (!$this->equipment_requirements) {
            return [];
        }
        
        if (is_string($this->equipment_requirements)) {
            return [$this->equipment_requirements];
        }
        
        return json_decode($this->equipment_requirements, true) ?? [];
    }
    
    /**
     * Get category statistics
     */
    public function getStatistics()
    {
        $teamCount = $this->getTeamCount();
        $participantCount = $this->getParticipantCount();
        
        $schoolCount = $this->db->query("
            SELECT COUNT(DISTINCT t.school_id) as school_count
            FROM teams t
            WHERE t.category_id = ?
            AND t.deleted_at IS NULL
        ", [$this->id])[0]['school_count'] ?? 0;
        
        return [
            'team_count' => $teamCount,
            'participant_count' => $participantCount,
            'school_count' => $schoolCount,
            'avg_participants_per_team' => $teamCount > 0 ? round($participantCount / $teamCount, 2) : 0
        ];
    }
    
    /**
     * Check if category is available for registration
     */
    public function isAvailableForRegistration()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        
        // Check if registration deadline has passed
        $registrationDeadline = $this->db->table('settings')
            ->where('key', 'registration_deadline')
            ->value('value');
            
        if ($registrationDeadline && strtotime($registrationDeadline) < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get teams by phase for this category
     */
    public function getTeamsByPhase($phaseId)
    {
        return $this->db->table('teams')
            ->where('category_id', $this->id)
            ->where('phase_id', $phaseId)
            ->whereNull('deleted_at')
            ->get();
    }
    
    /**
     * Get available equipment types for 2025 Competition
     */
    public static function getAvailableEquipmentTypes()
    {
        return [
            self::EQUIPMENT_CUBROID => 'Cubroid Robotics Kits',
            self::EQUIPMENT_LEGO_SPIKE => 'LEGO Spike Prime',
            self::EQUIPMENT_ARDUINO => 'Arduino/Open Hardware (SCIBOT, PRIMO, etc.)',
            self::EQUIPMENT_MIXED => 'Any Technology (Open Innovation)'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_DRAFT => 'Draft'
        ];
    }
    
    /**
     * Scope: Active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Categories by equipment type
     */
    public function scopeByEquipment($query, $equipment)
    {
        return $query->where('equipment_requirements', 'LIKE', '%' . $equipment . '%');
    }
    
    /**
     * Scope: Categories by age range
     */
    public function scopeByAgeRange($query, $age)
    {
        return $query->where('min_age', '<=', $age)->where('max_age', '>=', $age);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['team_count'] = $this->getTeamCount();
        $attributes['participant_count'] = $this->getParticipantCount();
        $attributes['statistics'] = $this->getStatistics();
        $attributes['scoring_rubric_parsed'] = $this->getScoringRubric();
        $attributes['equipment_requirements_parsed'] = $this->getEquipmentRequirements();
        $attributes['available_for_registration'] = $this->isAvailableForRegistration();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}