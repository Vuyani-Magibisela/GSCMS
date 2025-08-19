<?php

namespace App\Models;

class CompetitionCategory extends BaseModel
{
    protected $table = 'competition_categories';
    protected $softDeletes = true;
    
    protected $fillable = [
        'competition_id', 'category_id', 'category_code', 'name', 'grades',
        'team_size', 'max_teams_per_school', 'equipment_requirements',
        'mission_template_id', 'scoring_rubric', 'registration_rules',
        'special_requirements', 'safety_protocols', 'time_limit_minutes',
        'max_attempts', 'is_active', 'registration_count', 'capacity_limit',
        'custom_rules'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'competition_id' => 'required',
        'category_id' => 'required',
        'category_code' => 'required|max:50',
        'name' => 'required|max:255',
        'grades' => 'required',
        'team_size' => 'numeric|min:1|max:10',
        'max_teams_per_school' => 'numeric|min:1',
        'time_limit_minutes' => 'numeric|min:5|max:120',
        'max_attempts' => 'numeric|min:1|max:10'
    ];
    
    protected $messages = [
        'competition_id.required' => 'Competition is required.',
        'category_id.required' => 'Category is required.',
        'category_code.required' => 'Category code is required.',
        'name.required' => 'Category name is required.',
        'grades.required' => 'Grade requirements are required.',
        'team_size.min' => 'Team size must be at least 1.',
        'team_size.max' => 'Team size cannot exceed 10.'
    ];
    
    protected $belongsTo = [
        'competition' => ['model' => CompetitionSetup::class, 'foreign_key' => 'competition_id'],
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id'],
        'missionTemplate' => ['model' => MissionTemplate::class, 'foreign_key' => 'mission_template_id']
    ];

    /**
     * Get competition setup relation
     */
    public function competition()
    {
        return $this->belongsTo('App\\Models\\CompetitionSetup', 'competition_id');
    }
    
    /**
     * Get category relation
     */
    public function category()
    {
        return $this->belongsTo('App\\Models\\Category', 'category_id');
    }
    
    /**
     * Get mission template relation
     */
    public function missionTemplate()
    {
        return $this->belongsTo('App\\Models\\MissionTemplate', 'mission_template_id');
    }
    
    /**
     * Get grades as array
     */
    public function getGrades()
    {
        if (!$this->grades) {
            return [];
        }
        
        return json_decode($this->grades, true) ?? [];
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
     * Get default scoring rubric based on category type
     */
    private function getDefaultScoringRubric()
    {
        // Get category to determine rubric type
        $categoryData = $this->db->query("SELECT * FROM categories WHERE id = ?", [$this->category_id])[0] ?? null;
        
        if (!$categoryData) {
            return $this->getBaseScoringRubric();
        }
        
        $categoryCode = strtoupper($categoryData['code'] ?? '');
        
        if (strpos($categoryCode, 'INVENTOR') !== false) {
            return $this->getInventorScoringRubric();
        } else {
            return $this->getRoboticsScoringRubric();
        }
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
        
        // Add model presentation for intermediate/senior
        $categoryCode = strtoupper($this->category_code ?? '');
        if (strpos($categoryCode, 'INTERMEDIATE') !== false || strpos($categoryCode, 'SENIOR') !== false) {
            $base['model_presentation'] = ['max_points' => 15, 'levels' => 4];
        }
        
        return $base;
    }
    
    /**
     * Get registration rules as array
     */
    public function getRegistrationRules()
    {
        if (!$this->registration_rules) {
            return $this->getDefaultRegistrationRules();
        }
        
        return json_decode($this->registration_rules, true) ?? $this->getDefaultRegistrationRules();
    }
    
    /**
     * Get default registration rules
     */
    private function getDefaultRegistrationRules()
    {
        return [
            'max_teams_per_school' => $this->max_teams_per_school,
            'team_size' => $this->team_size,
            'grade_requirements' => $this->getGrades(),
            'registration_deadline' => null,
            'document_requirements' => ['consent_forms', 'participant_details'],
            'coach_requirements' => ['adult_supervision', 'safety_training']
        ];
    }
    
    /**
     * Get custom rules as array
     */
    public function getCustomRules()
    {
        if (!$this->custom_rules) {
            return [];
        }
        
        return json_decode($this->custom_rules, true) ?? [];
    }
    
    /**
     * Calculate total possible score
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
     * Check if registration is at capacity
     */
    public function isAtCapacity()
    {
        return $this->capacity_limit && $this->registration_count >= $this->capacity_limit;
    }
    
    /**
     * Check if category allows more teams from school
     */
    public function allowsMoreTeamsFromSchool($schoolId)
    {
        $currentTeams = $this->db->query("
            SELECT COUNT(*) as count 
            FROM teams t
            JOIN competition_categories cc ON t.category_id = cc.category_id
            WHERE cc.id = ? 
            AND t.school_id = ? 
            AND t.deleted_at IS NULL
        ", [$this->id, $schoolId])[0]['count'] ?? 0;
        
        return $currentTeams < $this->max_teams_per_school;
    }
    
    /**
     * Get registered teams for this category
     */
    public function getRegisteredTeams()
    {
        return $this->db->query("
            SELECT 
                t.*,
                s.name as school_name,
                s.address as school_address,
                COUNT(p.id) as participant_count
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN competition_categories cc ON t.category_id = cc.category_id
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE cc.id = ?
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY s.name, t.name
        ", [$this->id]);
    }
    
    /**
     * Get category statistics
     */
    public function getCategoryStatistics()
    {
        $teams = $this->getRegisteredTeams();
        
        $schoolCount = count(array_unique(array_column($teams, 'school_id')));
        $participantCount = array_sum(array_column($teams, 'participant_count'));
        
        return [
            'total_teams' => count($teams),
            'participating_schools' => $schoolCount,
            'total_participants' => $participantCount,
            'capacity_utilization' => $this->capacity_limit ? 
                (count($teams) / $this->capacity_limit) * 100 : 0,
            'average_team_size' => count($teams) > 0 ? $participantCount / count($teams) : 0,
            'is_at_capacity' => $this->isAtCapacity(),
            'spots_remaining' => $this->capacity_limit ? 
                max(0, $this->capacity_limit - count($teams)) : null
        ];
    }
    
    /**
     * Update registration count
     */
    public function updateRegistrationCount()
    {
        $count = $this->db->query("
            SELECT COUNT(*) as count 
            FROM teams t
            JOIN competition_categories cc ON t.category_id = cc.category_id
            WHERE cc.id = ? 
            AND t.deleted_at IS NULL
        ", [$this->id])[0]['count'] ?? 0;
        
        return $this->update([
            'registration_count' => $count,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get categories by competition
     */
    public function getCategoriesByCompetition($competitionId)
    {
        return $this->db->query("
            SELECT 
                cc.*,
                c.name as category_name,
                c.code as category_code_original,
                mt.mission_name
            FROM competition_categories cc
            JOIN categories c ON cc.category_id = c.id
            LEFT JOIN mission_templates mt ON cc.mission_template_id = mt.id
            WHERE cc.competition_id = ?
            AND cc.deleted_at IS NULL
            ORDER BY c.name
        ", [$competitionId]);
    }
    
    /**
     * Get active categories by competition
     */
    public function getActiveCategoriesByCompetition($competitionId)
    {
        return $this->db->query("
            SELECT 
                cc.*,
                c.name as category_name,
                c.code as category_code_original,
                mt.mission_name
            FROM competition_categories cc
            JOIN categories c ON cc.category_id = c.id
            LEFT JOIN mission_templates mt ON cc.mission_template_id = mt.id
            WHERE cc.competition_id = ?
            AND cc.is_active = 1
            AND cc.deleted_at IS NULL
            ORDER BY c.name
        ", [$competitionId]);
    }
    
    /**
     * Clone category for new competition
     */
    public function cloneForCompetition($newCompetitionId)
    {
        $newCategory = new self();
        
        // Copy all properties except ID and competition_id
        $newCategory->competition_id = $newCompetitionId;
        $newCategory->category_id = $this->category_id;
        $newCategory->category_code = $this->category_code;
        $newCategory->name = $this->name;
        $newCategory->grades = $this->grades;
        $newCategory->team_size = $this->team_size;
        $newCategory->max_teams_per_school = $this->max_teams_per_school;
        $newCategory->equipment_requirements = $this->equipment_requirements;
        $newCategory->mission_template_id = $this->mission_template_id;
        $newCategory->scoring_rubric = $this->scoring_rubric;
        $newCategory->registration_rules = $this->registration_rules;
        $newCategory->special_requirements = $this->special_requirements;
        $newCategory->safety_protocols = $this->safety_protocols;
        $newCategory->time_limit_minutes = $this->time_limit_minutes;
        $newCategory->max_attempts = $this->max_attempts;
        $newCategory->custom_rules = $this->custom_rules;
        $newCategory->registration_count = 0; // Reset count for new competition
        
        return $newCategory->save() ? $newCategory : false;
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['grades_parsed'] = $this->getGrades();
        $attributes['equipment_requirements_parsed'] = $this->getEquipmentRequirements();
        $attributes['scoring_rubric_parsed'] = $this->getScoringRubric();
        $attributes['registration_rules_parsed'] = $this->getRegistrationRules();
        $attributes['custom_rules_parsed'] = $this->getCustomRules();
        $attributes['total_possible_score'] = $this->getTotalPossibleScore();
        $attributes['is_at_capacity'] = $this->isAtCapacity();
        $attributes['statistics'] = $this->getCategoryStatistics();
        
        return $attributes;
    }
}