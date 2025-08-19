<?php

namespace App\Models;

class CompetitionSetup extends BaseModel
{
    protected $table = 'competition_setups';
    protected $softDeletes = true;
    
    protected $fillable = [
        'name', 'year', 'type', 'status', 'start_date', 'end_date',
        'geographic_scope', 'phase_configuration', 'registration_opening',
        'registration_closing', 'description', 'rules_document', 'contact_email',
        'venue_information', 'awards_ceremony_date', 'created_by', 'updated_by'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'name' => 'required|max:255',
        'year' => 'required|numeric|min:2024|max:2030',
        'type' => 'required',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'geographic_scope' => 'required',
        'created_by' => 'required'
    ];
    
    protected $messages = [
        'name.required' => 'Competition name is required.',
        'year.required' => 'Competition year is required.',
        'year.numeric' => 'Year must be a valid number.',
        'start_date.required' => 'Start date is required.',
        'end_date.required' => 'End date is required.',
        'end_date.after' => 'End date must be after start date.',
        'created_by.required' => 'Creator is required.'
    ];
    
    // Competition type constants
    const TYPE_PILOT = 'pilot';
    const TYPE_FULL_SYSTEM = 'full_system';
    const TYPE_REGIONAL = 'regional';
    const TYPE_NATIONAL = 'national';
    
    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    // Geographic scope constants
    const SCOPE_SINGLE_PROVINCE = 'single_province';
    const SCOPE_MULTI_PROVINCE = 'multi_province';
    const SCOPE_NATIONAL = 'national';
    
    protected $hasMany = [
        'phases' => ['model' => CompetitionPhase::class, 'foreign_key' => 'competition_id'],
        'categories' => ['model' => CompetitionCategory::class, 'foreign_key' => 'competition_id'],
        'configurations' => ['model' => CompetitionConfiguration::class, 'foreign_key' => 'competition_id']
    ];
    
    protected $belongsTo = [
        'creator' => ['model' => User::class, 'foreign_key' => 'created_by'],
        'updater' => ['model' => User::class, 'foreign_key' => 'updated_by']
    ];

    /**
     * Get competition phases
     */
    public function phases()
    {
        return $this->hasMany('App\\Models\\CompetitionPhase', 'competition_id', 'id');
    }
    
    /**
     * Get competition categories
     */
    public function categories()
    {
        return $this->hasMany('App\\Models\\CompetitionCategory', 'competition_id', 'id');
    }
    
    /**
     * Get competition configurations
     */
    public function configurations()
    {
        return $this->hasMany('App\\Models\\CompetitionConfiguration', 'competition_id', 'id');
    }
    
    /**
     * Get creator user
     */
    public function creator()
    {
        return $this->belongsTo('App\\Models\\User', 'created_by');
    }
    
    /**
     * Get phase configuration as array
     */
    public function getPhaseConfiguration()
    {
        if (!$this->phase_configuration) {
            return $this->getDefaultPhaseConfiguration();
        }
        
        return json_decode($this->phase_configuration, true) ?? $this->getDefaultPhaseConfiguration();
    }
    
    /**
     * Get default phase configuration based on type
     */
    private function getDefaultPhaseConfiguration()
    {
        if ($this->type === self::TYPE_PILOT) {
            return [
                'phase_1' => [
                    'name' => 'School-Based Elimination',
                    'enabled' => true,
                    'capacity' => 30,
                    'duration_weeks' => 2
                ],
                'phase_2' => [
                    'name' => 'District Semifinals',
                    'enabled' => false,
                    'note' => 'Disabled for pilot programme'
                ],
                'phase_3' => [
                    'name' => 'Provincial Finals',
                    'enabled' => true,
                    'capacity' => 6,
                    'duration_days' => 1
                ]
            ];
        }
        
        return [
            'phase_1' => [
                'name' => 'School-Based Competition',
                'enabled' => true,
                'capacity' => 'unlimited'
            ],
            'phase_2' => [
                'name' => 'District Semifinals',
                'enabled' => true,
                'capacity' => 15
            ],
            'phase_3' => [
                'name' => 'Provincial Finals',
                'enabled' => true,
                'capacity' => 6
            ]
        ];
    }
    
    /**
     * Check if competition is pilot type
     */
    public function isPilot()
    {
        return $this->type === self::TYPE_PILOT;
    }
    
    /**
     * Check if competition is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * Check if registration is open
     */
    public function isRegistrationOpen()
    {
        $now = date('Y-m-d H:i:s');
        return $this->registration_opening <= $now && 
               $this->registration_closing >= $now &&
               $this->isActive();
    }
    
    /**
     * Get active phases
     */
    public function getActivePhases()
    {
        return $this->db->query("
            SELECT * FROM competition_phases 
            WHERE competition_id = ? 
            AND is_active = 1 
            AND deleted_at IS NULL 
            ORDER BY phase_order
        ", [$this->id]);
    }
    
    /**
     * Get category statistics
     */
    public function getCategoryStatistics()
    {
        return $this->db->query("
            SELECT 
                cc.category_code,
                cc.name,
                cc.registration_count,
                cc.capacity_limit,
                COUNT(t.id) as actual_registrations
            FROM competition_categories cc
            LEFT JOIN teams t ON cc.category_id = t.category_id 
                AND t.deleted_at IS NULL
            WHERE cc.competition_id = ?
            AND cc.deleted_at IS NULL
            GROUP BY cc.id
            ORDER BY cc.name
        ", [$this->id]);
    }
    
    /**
     * Get competition overview statistics
     */
    public function getOverviewStatistics()
    {
        $stats = $this->db->query("
            SELECT 
                COUNT(DISTINCT cc.id) as total_categories,
                COUNT(DISTINCT cp.id) as total_phases,
                SUM(cc.registration_count) as total_registrations,
                COUNT(DISTINCT t.school_id) as participating_schools
            FROM competition_setups cs
            LEFT JOIN competition_categories cc ON cs.id = cc.competition_id AND cc.deleted_at IS NULL
            LEFT JOIN competition_phases cp ON cs.id = cp.competition_id AND cp.deleted_at IS NULL
            LEFT JOIN teams t ON cc.category_id = t.category_id AND t.deleted_at IS NULL
            WHERE cs.id = ?
        ", [$this->id])[0] ?? [];
        
        return [
            'total_categories' => $stats['total_categories'] ?? 0,
            'total_phases' => $stats['total_phases'] ?? 0,
            'total_registrations' => $stats['total_registrations'] ?? 0,
            'participating_schools' => $stats['participating_schools'] ?? 0,
            'registration_open' => $this->isRegistrationOpen(),
            'days_until_start' => $this->start_date ? 
                max(0, floor((strtotime($this->start_date) - time()) / (60 * 60 * 24))) : null
        ];
    }
    
    /**
     * Get competitions by year
     */
    public function getCompetitionsByYear($year)
    {
        return $this->db->table($this->table)
            ->where('year', $year)
            ->whereNull('deleted_at')
            ->orderBy('start_date', 'DESC')
            ->get();
    }
    
    /**
     * Get competitions by type
     */
    public function getCompetitionsByType($type)
    {
        return $this->db->table($this->table)
            ->where('type', $type)
            ->whereNull('deleted_at')
            ->orderBy('year', 'DESC')
            ->orderBy('start_date', 'DESC')
            ->get();
    }
    
    /**
     * Clone competition for new year
     */
    public function cloneForNewYear($newYear, $newName = null)
    {
        $newCompetition = new self();
        
        // Copy basic properties
        $newCompetition->name = $newName ?: str_replace($this->year, $newYear, $this->name);
        $newCompetition->year = $newYear;
        $newCompetition->type = $this->type;
        $newCompetition->geographic_scope = $this->geographic_scope;
        $newCompetition->phase_configuration = $this->phase_configuration;
        $newCompetition->description = $this->description;
        $newCompetition->created_by = $this->created_by;
        $newCompetition->status = self::STATUS_DRAFT;
        
        // Save new competition
        if ($newCompetition->save()) {
            // Clone phases
            $phases = $this->getActivePhases();
            foreach ($phases as $phase) {
                $newPhase = new CompetitionPhase();
                $newPhase->competition_id = $newCompetition->id;
                $newPhase->phase_number = $phase['phase_number'];
                $newPhase->name = $phase['name'];
                $newPhase->description = $phase['description'];
                $newPhase->capacity_per_category = $phase['capacity_per_category'];
                $newPhase->venue_requirements = $phase['venue_requirements'];
                $newPhase->advancement_criteria = $phase['advancement_criteria'];
                $newPhase->phase_order = $phase['phase_order'];
                $newPhase->save();
            }
            
            // Clone categories
            $categories = $this->categories();
            foreach ($categories as $category) {
                $newCategory = new CompetitionCategory();
                $newCategory->competition_id = $newCompetition->id;
                $newCategory->category_id = $category['category_id'];
                $newCategory->category_code = $category['category_code'];
                $newCategory->name = $category['name'];
                $newCategory->grades = $category['grades'];
                $newCategory->team_size = $category['team_size'];
                $newCategory->max_teams_per_school = $category['max_teams_per_school'];
                $newCategory->equipment_requirements = $category['equipment_requirements'];
                $newCategory->mission_template_id = $category['mission_template_id'];
                $newCategory->scoring_rubric = $category['scoring_rubric'];
                $newCategory->save();
            }
            
            return $newCompetition;
        }
        
        return false;
    }
    
    /**
     * Get available competition types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_PILOT => 'Pilot Programme',
            self::TYPE_FULL_SYSTEM => 'Full System',
            self::TYPE_REGIONAL => 'Regional',
            self::TYPE_NATIONAL => 'National'
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
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }
    
    /**
     * Get available geographic scopes
     */
    public static function getAvailableScopes()
    {
        return [
            self::SCOPE_SINGLE_PROVINCE => 'Single Province',
            self::SCOPE_MULTI_PROVINCE => 'Multi Province',
            self::SCOPE_NATIONAL => 'National'
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['phase_configuration_parsed'] = $this->getPhaseConfiguration();
        $attributes['is_pilot'] = $this->isPilot();
        $attributes['is_active'] = $this->isActive();
        $attributes['is_registration_open'] = $this->isRegistrationOpen();
        $attributes['type_label'] = self::getAvailableTypes()[$this->type] ?? $this->type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['scope_label'] = self::getAvailableScopes()[$this->geographic_scope] ?? $this->geographic_scope;
        $attributes['overview_statistics'] = $this->getOverviewStatistics();
        
        return $attributes;
    }
}