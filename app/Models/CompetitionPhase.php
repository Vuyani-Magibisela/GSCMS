<?php

namespace App\Models;

class CompetitionPhase extends BaseModel
{
    protected $table = 'competition_phases';
    protected $softDeletes = true;
    
    protected $fillable = [
        'competition_id', 'phase_number', 'name', 'description', 'start_date',
        'end_date', 'capacity_per_category', 'venue_requirements', 'advancement_criteria',
        'scoring_configuration', 'judge_requirements', 'equipment_allocation',
        'safety_protocols', 'communication_template', 'is_active', 'is_completed',
        'completion_date', 'phase_order'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'competition_id' => 'required',
        'phase_number' => 'required|numeric|min:1|max:3',
        'name' => 'required|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'capacity_per_category' => 'numeric|min:1',
        'phase_order' => 'numeric|min:1'
    ];
    
    protected $messages = [
        'competition_id.required' => 'Competition is required.',
        'phase_number.required' => 'Phase number is required.',
        'phase_number.min' => 'Phase number must be at least 1.',
        'phase_number.max' => 'Phase number cannot exceed 3.',
        'name.required' => 'Phase name is required.',
        'start_date.required' => 'Start date is required.',
        'end_date.required' => 'End date is required.',
        'end_date.after' => 'End date must be after start date.'
    ];
    
    protected $belongsTo = [
        'competition' => ['model' => CompetitionSetup::class, 'foreign_key' => 'competition_id']
    ];

    /**
     * Get competition setup relation
     */
    public function competition()
    {
        return $this->belongsTo('App\\Models\\CompetitionSetup', 'competition_id');
    }
    
    /**
     * Get venue requirements as array
     */
    public function getVenueRequirements()
    {
        if (!$this->venue_requirements) {
            return $this->getDefaultVenueRequirements();
        }
        
        return json_decode($this->venue_requirements, true) ?? $this->getDefaultVenueRequirements();
    }
    
    /**
     * Get default venue requirements based on phase
     */
    private function getDefaultVenueRequirements()
    {
        switch ($this->phase_number) {
            case 1:
                return [
                    'venue_type' => 'school_facilities',
                    'space_required' => 'classroom_or_hall',
                    'equipment_access' => 'basic_electrical',
                    'safety_requirements' => ['fire_safety', 'first_aid_access'],
                    'capacity_minimum' => 50
                ];
            case 2:
                return [
                    'venue_type' => 'district_venue',
                    'space_required' => 'large_hall_or_auditorium',
                    'equipment_access' => ['electrical', 'av_equipment'],
                    'safety_requirements' => ['fire_safety', 'first_aid_station', 'security'],
                    'capacity_minimum' => 200
                ];
            case 3:
                return [
                    'venue_type' => 'central_competition_venue',
                    'space_required' => 'exhibition_hall',
                    'equipment_access' => ['full_electrical', 'av_equipment', 'internet'],
                    'safety_requirements' => ['fire_safety', 'medical_station', 'security', 'crowd_control'],
                    'capacity_minimum' => 500
                ];
            default:
                return [];
        }
    }
    
    /**
     * Get advancement criteria as array
     */
    public function getAdvancementCriteria()
    {
        if (!$this->advancement_criteria) {
            return $this->getDefaultAdvancementCriteria();
        }
        
        return json_decode($this->advancement_criteria, true) ?? $this->getDefaultAdvancementCriteria();
    }
    
    /**
     * Get default advancement criteria based on phase
     */
    private function getDefaultAdvancementCriteria()
    {
        switch ($this->phase_number) {
            case 1:
                return [
                    'advancement_method' => 'top_performers',
                    'teams_to_advance' => 6,
                    'criteria' => ['total_score', 'mission_time'],
                    'minimum_score' => 60,
                    'tiebreaker_rules' => ['fastest_mission_time', 'innovation_score']
                ];
            case 2:
                return [
                    'advancement_method' => 'top_performers',
                    'teams_to_advance' => 6,
                    'criteria' => ['total_score', 'consistency'],
                    'minimum_score' => 70,
                    'tiebreaker_rules' => ['highest_innovation_score', 'presentation_quality']
                ];
            case 3:
                return [
                    'advancement_method' => 'final_ranking',
                    'awards' => ['first_place', 'second_place', 'third_place', 'innovation_award'],
                    'criteria' => ['total_score', 'innovation', 'presentation'],
                    'special_awards' => ['best_teamwork', 'most_creative_solution']
                ];
            default:
                return [];
        }
    }
    
    /**
     * Get scoring configuration as array
     */
    public function getScoringConfiguration()
    {
        if (!$this->scoring_configuration) {
            return [];
        }
        
        return json_decode($this->scoring_configuration, true) ?? [];
    }
    
    /**
     * Get judge requirements as array
     */
    public function getJudgeRequirements()
    {
        if (!$this->judge_requirements) {
            return $this->getDefaultJudgeRequirements();
        }
        
        return json_decode($this->judge_requirements, true) ?? $this->getDefaultJudgeRequirements();
    }
    
    /**
     * Get default judge requirements based on phase
     */
    private function getDefaultJudgeRequirements()
    {
        switch ($this->phase_number) {
            case 1:
                return [
                    'judges_per_category' => 2,
                    'qualifications' => ['teaching_experience', 'basic_robotics_knowledge'],
                    'training_required' => 'basic_judge_training',
                    'time_commitment' => '4_hours'
                ];
            case 2:
                return [
                    'judges_per_category' => 3,
                    'qualifications' => ['education_background', 'robotics_or_engineering_experience'],
                    'training_required' => 'advanced_judge_training',
                    'time_commitment' => '6_hours'
                ];
            case 3:
                return [
                    'judges_per_category' => 4,
                    'qualifications' => ['expert_level_knowledge', 'competition_judging_experience'],
                    'training_required' => 'expert_judge_certification',
                    'time_commitment' => '8_hours'
                ];
            default:
                return [];
        }
    }
    
    /**
     * Get equipment allocation as array
     */
    public function getEquipmentAllocation()
    {
        if (!$this->equipment_allocation) {
            return [];
        }
        
        return json_decode($this->equipment_allocation, true) ?? [];
    }
    
    /**
     * Check if phase is currently active
     */
    public function isCurrentlyActive()
    {
        $now = date('Y-m-d H:i:s');
        return $this->is_active && 
               $this->start_date <= $now && 
               $this->end_date >= $now &&
               !$this->is_completed;
    }
    
    /**
     * Check if phase is upcoming
     */
    public function isUpcoming()
    {
        $now = date('Y-m-d H:i:s');
        return $this->is_active && 
               $this->start_date > $now &&
               !$this->is_completed;
    }
    
    /**
     * Check if phase is past
     */
    public function isPast()
    {
        $now = date('Y-m-d H:i:s');
        return $this->end_date < $now || $this->is_completed;
    }
    
    /**
     * Get phase duration in days
     */
    public function getDurationDays()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        $start = strtotime($this->start_date);
        $end = strtotime($this->end_date);
        
        return floor(($end - $start) / (60 * 60 * 24)) + 1;
    }
    
    /**
     * Get teams participating in this phase
     */
    public function getParticipatingTeams()
    {
        return $this->db->query("
            SELECT 
                t.*,
                s.name as school_name,
                c.name as category_name
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            JOIN competition_categories cc ON c.id = cc.category_id
            WHERE cc.competition_id = ?
            AND t.deleted_at IS NULL
            ORDER BY c.name, s.name, t.name
        ", [$this->competition_id]);
    }
    
    /**
     * Get phase statistics
     */
    public function getPhaseStatistics()
    {
        $teams = $this->getParticipatingTeams();
        $teamsByCategory = [];
        
        foreach ($teams as $team) {
            $category = $team['category_name'];
            if (!isset($teamsByCategory[$category])) {
                $teamsByCategory[$category] = 0;
            }
            $teamsByCategory[$category]++;
        }
        
        return [
            'total_teams' => count($teams),
            'teams_by_category' => $teamsByCategory,
            'capacity_utilization' => $this->capacity_per_category ? 
                (count($teams) / $this->capacity_per_category) * 100 : 0,
            'duration_days' => $this->getDurationDays(),
            'is_currently_active' => $this->isCurrentlyActive(),
            'is_upcoming' => $this->isUpcoming(),
            'is_past' => $this->isPast(),
            'completion_percentage' => $this->getCompletionPercentage()
        ];
    }
    
    /**
     * Get completion percentage
     */
    public function getCompletionPercentage()
    {
        if ($this->is_completed) {
            return 100;
        }
        
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        $now = time();
        $start = strtotime($this->start_date);
        $end = strtotime($this->end_date);
        
        if ($now < $start) {
            return 0;
        }
        
        if ($now > $end) {
            return 100;
        }
        
        $total = $end - $start;
        $elapsed = $now - $start;
        
        return floor(($elapsed / $total) * 100);
    }
    
    /**
     * Mark phase as completed
     */
    public function markCompleted()
    {
        return $this->update([
            'is_completed' => true,
            'completion_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get phases by competition
     */
    public function getPhasesByCompetition($competitionId)
    {
        return $this->db->table($this->table)
            ->where('competition_id', $competitionId)
            ->whereNull('deleted_at')
            ->orderBy('phase_order')
            ->get();
    }
    
    /**
     * Get active phases by competition
     */
    public function getActivePhasesByCompetition($competitionId)
    {
        return $this->db->table($this->table)
            ->where('competition_id', $competitionId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('phase_order')
            ->get();
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['venue_requirements_parsed'] = $this->getVenueRequirements();
        $attributes['advancement_criteria_parsed'] = $this->getAdvancementCriteria();
        $attributes['scoring_configuration_parsed'] = $this->getScoringConfiguration();
        $attributes['judge_requirements_parsed'] = $this->getJudgeRequirements();
        $attributes['equipment_allocation_parsed'] = $this->getEquipmentAllocation();
        $attributes['duration_days'] = $this->getDurationDays();
        $attributes['is_currently_active'] = $this->isCurrentlyActive();
        $attributes['is_upcoming'] = $this->isUpcoming();
        $attributes['is_past'] = $this->isPast();
        $attributes['completion_percentage'] = $this->getCompletionPercentage();
        $attributes['statistics'] = $this->getPhaseStatistics();
        
        return $attributes;
    }
}