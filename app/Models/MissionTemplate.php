<?php

namespace App\Models;

class MissionTemplate extends BaseModel
{
    protected $table = 'mission_templates';
    protected $softDeletes = true;
    
    protected $fillable = [
        'category_id', 'mission_name', 'mission_code', 'mission_description',
        'story_context', 'objective', 'difficulty_level', 'time_limit_minutes',
        'max_attempts', 'technical_requirements', 'competition_rules',
        'scoring_rubric', 'mission_stages', 'research_component',
        'deliverables', 'equipment_requirements', 'safety_requirements',
        'status', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'category_id' => 'required',
        'mission_name' => 'required|max:255',
        'mission_code' => 'required|max:50|unique',
        'difficulty_level' => 'required',
        'time_limit_minutes' => 'required|min:1|max:120',
        'max_attempts' => 'required|min:1|max:10',
        'status' => 'required'
    ];
    
    protected $messages = [
        'category_id.required' => 'Category is required.',
        'mission_name.required' => 'Mission name is required.',
        'mission_code.required' => 'Mission code is required.',
        'mission_code.unique' => 'Mission code must be unique.',
        'difficulty_level.required' => 'Difficulty level is required.',
        'time_limit_minutes.required' => 'Time limit is required.',
        'max_attempts.required' => 'Maximum attempts is required.',
        'status.required' => 'Status is required.'
    ];
    
    // Difficulty level constants
    const DIFFICULTY_BEGINNER = 'beginner';
    const DIFFICULTY_INTERMEDIATE = 'intermediate';
    const DIFFICULTY_ADVANCED = 'advanced';
    const DIFFICULTY_EXPERT = 'expert';
    
    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    
    protected $belongsTo = [
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id']
    ];
    
    protected $hasMany = [
        'assets' => ['model' => MissionAsset::class, 'foreign_key' => 'mission_template_id']
    ];

    /**
     * Get category relation
     */
    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }
    
    /**
     * Get mission assets
     */
    public function assets()
    {
        return $this->hasMany('App\Models\MissionAsset', 'mission_template_id', 'id');
    }
    
    /**
     * Get technical requirements as array
     */
    public function getTechnicalRequirements()
    {
        if (!$this->technical_requirements) {
            return [];
        }
        
        return json_decode($this->technical_requirements, true) ?? [];
    }
    
    /**
     * Get competition rules as array
     */
    public function getCompetitionRules()
    {
        if (!$this->competition_rules) {
            return [];
        }
        
        return json_decode($this->competition_rules, true) ?? [];
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
     * Get mission stages as array
     */
    public function getMissionStages()
    {
        if (!$this->mission_stages) {
            return [];
        }
        
        return json_decode($this->mission_stages, true) ?? [];
    }
    
    /**
     * Get research component as array
     */
    public function getResearchComponent()
    {
        if (!$this->research_component) {
            return [];
        }
        
        return json_decode($this->research_component, true) ?? [];
    }
    
    /**
     * Get deliverables as array
     */
    public function getDeliverables()
    {
        if (!$this->deliverables) {
            return [];
        }
        
        return json_decode($this->deliverables, true) ?? [];
    }
    
    /**
     * Get equipment requirements as array
     */
    public function getEquipmentRequirements()
    {
        if (!$this->equipment_requirements) {
            return [];
        }
        
        return json_decode($this->equipment_requirements, true) ?? [];
    }
    
    /**
     * Get safety requirements as array
     */
    public function getSafetyRequirements()
    {
        if (!$this->safety_requirements) {
            return [];
        }
        
        return json_decode($this->safety_requirements, true) ?? [];
    }
    
    /**
     * Get default scoring rubric based on category
     */
    private function getDefaultScoringRubric()
    {
        // Get category to determine rubric type
        $category = $this->category();
        
        if (!$category) {
            return $this->getBaseScoringRubric();
        }
        
        $categoryCode = strtoupper($category->code ?? '');
        
        if (strpos($categoryCode, 'INVENTOR') !== false) {
            return $this->getInventorScoringRubric();
        } else if (in_array($categoryCode, ['JUNIOR', 'EXPLORER_COSMIC', 'EXPLORER_LOST', 'ARDUINO_THUNDER', 'ARDUINO_YELLOW'])) {
            return $this->getRoboticsScoringRubric();
        }
        
        return $this->getBaseScoringRubric();
    }
    
    /**
     * Get base scoring rubric (all categories)
     */
    private function getBaseScoringRubric()
    {
        return [
            'problem_identification' => ['max_points' => 20, 'levels' => 4],
            'solution_development' => ['max_points' => 20, 'levels' => 4],
            'communication_skills' => ['max_points' => 5, 'levels' => 4],
            'teamwork_collaboration' => ['max_points' => 5, 'levels' => 4],
            'creativity_innovation' => ['max_points' => 10, 'levels' => 4]
        ];
    }
    
    /**
     * Get robotics categories scoring rubric
     */
    private function getRoboticsScoringRubric()
    {
        $base = $this->getBaseScoringRubric();
        $base['robot_presentation'] = ['max_points' => 25, 'levels' => 4];
        $base['mission_time_bonus'] = ['fastest_mission_of_three' => true];
        
        return $base;
    }
    
    /**
     * Get inventor categories scoring rubric
     */
    private function getInventorScoringRubric()
    {
        $base = $this->getBaseScoringRubric();
        $base['robot_presentation'] = ['max_points' => 40, 'levels' => 4];
        $base['model_presentation'] = ['max_points' => 15, 'levels' => 4]; // Intermediate/Senior only
        
        return $base;
    }
    
    /**
     * Get missions by category
     */
    public function getMissionsByCategory($categoryId)
    {
        return $this->db->table($this->table)
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('difficulty_level')
            ->orderBy('mission_name')
            ->get();
    }
    
    /**
     * Get missions by difficulty level
     */
    public function getMissionsByDifficulty($difficultyLevel)
    {
        return $this->db->table($this->table)
            ->where('difficulty_level', $difficultyLevel)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('mission_name')
            ->get();
    }
    
    /**
     * Calculate total possible score for mission
     */
    public function getTotalPossibleScore()
    {
        $rubric = $this->getScoringRubric();
        $totalScore = 0;
        
        foreach ($rubric as $criteria => $config) {
            if (isset($config['max_points'])) {
                $totalScore += $config['max_points'];
            }
        }
        
        return $totalScore;
    }
    
    /**
     * Check if mission requires research component
     */
    public function hasResearchComponent()
    {
        $research = $this->getResearchComponent();
        return !empty($research);
    }
    
    /**
     * Check if mission has deliverables
     */
    public function hasDeliverables()
    {
        $deliverables = $this->getDeliverables();
        return !empty($deliverables);
    }
    
    /**
     * Get mission statistics
     */
    public function getMissionStatistics()
    {
        // Get usage statistics from teams
        $usageStats = $this->db->query("
            SELECT 
                COUNT(DISTINCT t.id) as teams_using_mission,
                COUNT(DISTINCT t.school_id) as schools_using_mission,
                AVG(s.total_score) as average_score,
                MIN(s.total_score) as min_score,
                MAX(s.total_score) as max_score
            FROM teams t
            LEFT JOIN scores s ON t.id = s.team_id
            WHERE t.category_id = ?
            AND t.deleted_at IS NULL
        ", [$this->category_id])[0] ?? [];
        
        return [
            'total_possible_score' => $this->getTotalPossibleScore(),
            'has_research_component' => $this->hasResearchComponent(),
            'has_deliverables' => $this->hasDeliverables(),
            'time_limit_minutes' => $this->time_limit_minutes,
            'max_attempts' => $this->max_attempts,
            'difficulty_level' => $this->difficulty_level,
            'usage_statistics' => $usageStats
        ];
    }
    
    /**
     * Get available difficulty levels
     */
    public static function getAvailableDifficultyLevels()
    {
        return [
            self::DIFFICULTY_BEGINNER => 'Beginner',
            self::DIFFICULTY_INTERMEDIATE => 'Intermediate',
            self::DIFFICULTY_ADVANCED => 'Advanced',
            self::DIFFICULTY_EXPERT => 'Expert'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived'
        ];
    }
    
    /**
     * Scope: Active missions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: By difficulty
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }
    
    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['technical_requirements_parsed'] = $this->getTechnicalRequirements();
        $attributes['competition_rules_parsed'] = $this->getCompetitionRules();
        $attributes['scoring_rubric_parsed'] = $this->getScoringRubric();
        $attributes['mission_stages_parsed'] = $this->getMissionStages();
        $attributes['research_component_parsed'] = $this->getResearchComponent();
        $attributes['deliverables_parsed'] = $this->getDeliverables();
        $attributes['equipment_requirements_parsed'] = $this->getEquipmentRequirements();
        $attributes['safety_requirements_parsed'] = $this->getSafetyRequirements();
        $attributes['total_possible_score'] = $this->getTotalPossibleScore();
        $attributes['has_research_component'] = $this->hasResearchComponent();
        $attributes['has_deliverables'] = $this->hasDeliverables();
        $attributes['difficulty_label'] = self::getAvailableDifficultyLevels()[$this->difficulty_level] ?? $this->difficulty_level;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['statistics'] = $this->getMissionStatistics();
        
        return $attributes;
    }
}