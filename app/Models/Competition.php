<?php
// app/Models/Competition.php

namespace App\Models;

class Competition extends BaseModel
{
    protected $table = 'competitions';
    protected $softDeletes = true;
    
    protected $fillable = [
        'phase_id', 'name', 'description', 'venue_id', 'start_date', 'end_date',
        'registration_deadline', 'max_teams', 'status', 'competition_format',
        'rules_document', 'requirements', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'phase_id' => 'required',
        'name' => 'required|max:255',
        'start_date' => 'required',
        'end_date' => 'required',
        'status' => 'required'
    ];
    
    protected $messages = [
        'phase_id.required' => 'Competition phase is required.',
        'name.required' => 'Competition name is required.',
        'start_date.required' => 'Start date is required.',
        'end_date.required' => 'End date is required.',
        'status.required' => 'Competition status is required.'
    ];
    
    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    // Competition format constants
    const FORMAT_SINGLE_ELIMINATION = 'single_elimination';
    const FORMAT_DOUBLE_ELIMINATION = 'double_elimination';
    const FORMAT_ROUND_ROBIN = 'round_robin';
    const FORMAT_SWISS = 'swiss';
    const FORMAT_TIME_TRIAL = 'time_trial';

    protected $belongsTo = [
        'phase' => ['model' => Phase::class, 'foreign_key' => 'phase_id'],
        // 'venue' => ['model' => Venue::class, 'foreign_key' => 'venue_id']
    ];

    protected $hasMany = [
        'teams' => ['model' => Team::class, 'foreign_key' => 'competition_id']
    ];

    /**
     * Get competition overview data (replacement for competition_overview view)
     * 
     * @param int|null $competitionId Specific competition ID or null for all
     * @return array
     */
    public function getCompetitionOverview($competitionId = null)
    {
        $query = "
            SELECT 
                comp.id,
                comp.name as competition_name,
                comp.year,
                p.name as phase_name,
                cat.name as category_name,
                comp.venue_name,
                comp.date,
                comp.status,
                comp.current_participants,
                comp.max_participants,
                COUNT(t.id) as registered_teams
            FROM competitions comp
            JOIN phases p ON comp.phase_id = p.id
            JOIN categories cat ON comp.category_id = cat.id
            LEFT JOIN teams t ON comp.id = t.competition_id
        ";

        $params = [];
        if ($competitionId) {
            $query .= " WHERE comp.id = ?";
            $params[] = $competitionId;
        }

        $query .= " GROUP BY comp.id, comp.name, comp.year, p.name, cat.name, comp.venue_name, comp.date, comp.status, comp.current_participants, comp.max_participants";
        $query .= " ORDER BY comp.date DESC";

        $results = $this->db->query($query, $params);
        
        return $competitionId && !empty($results) ? $results[0] : $results;
    }

    /**
     * Get competitions by year
     */
    public function getByYear($year)
    {
        return $this->db->table($this->table)
            ->where('year', $year)
            ->orderBy('date', 'DESC')
            ->get();
    }

    /**
     * Get competitions by status
     */
    public function getByStatus($status)
    {
        return $this->db->table($this->table)
            ->where('status', $status)
            ->orderBy('date', 'ASC')
            ->get();
    }

    /**
     * Get upcoming competitions
     */
    public function getUpcoming($limit = null)
    {
        $query = $this->db->table($this->table)
            ->where('date', '>=', date('Y-m-d'))
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'ASC');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    /**
     * Get competition phase
     */
    public function phase()
    {
        return $this->belongsTo('App\Models\Phase', 'phase_id');
    }
    
    /**
     * Get competition venue
     */
    public function venue()
    {
        return $this->belongsTo('App\Models\Venue', 'venue_id');
    }
    
    /**
     * Get participating teams
     */
    public function teams()
    {
        return $this->hasMany('App\Models\Team', 'competition_id', 'id');
    }
    
    /**
     * Check if competition is active
     */
    public function isActive()
    {
        $now = date('Y-m-d H:i:s');
        return $this->status === self::STATUS_ACTIVE &&
               $this->start_date <= $now &&
               $this->end_date >= $now;
    }
    
    /**
     * Check if registration is open
     */
    public function isRegistrationOpen()
    {
        if ($this->status !== self::STATUS_SCHEDULED) {
            return false;
        }
        
        $now = date('Y-m-d H:i:s');
        return $this->registration_deadline >= $now;
    }
    
    /**
     * Get team count
     */
    public function getTeamCount()
    {
        return $this->db->table('teams')
            ->where('competition_id', $this->id)
            ->whereNull('deleted_at')
            ->count();
    }
    
    /**
     * Check if competition is full
     */
    public function isFull()
    {
        if (!$this->max_teams) {
            return false;
        }
        
        return $this->getTeamCount() >= $this->max_teams;
    }
    
    /**
     * Get competition statistics
     */
    public function getStatistics()
    {
        $teamCount = $this->getTeamCount();
        
        $participantCount = $this->db->query("
            SELECT COUNT(p.id) as participant_count
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            WHERE t.competition_id = ?
            AND p.deleted_at IS NULL
            AND t.deleted_at IS NULL
        ", [$this->id])[0]['participant_count'] ?? 0;
        
        $schoolCount = $this->db->query("
            SELECT COUNT(DISTINCT t.school_id) as school_count
            FROM teams t
            WHERE t.competition_id = ?
            AND t.deleted_at IS NULL
        ", [$this->id])[0]['school_count'] ?? 0;
        
        return [
            'team_count' => $teamCount,
            'participant_count' => $participantCount,
            'school_count' => $schoolCount,
            'capacity_percentage' => $this->max_teams ? round(($teamCount / $this->max_teams) * 100, 2) : 0
        ];
    }
    
    /**
     * Get requirements as array
     */
    public function getRequirements()
    {
        if (!$this->requirements) {
            return [];
        }
        
        return json_decode($this->requirements, true) ?? [];
    }
    
    /**
     * Get available competition formats
     */
    public static function getAvailableFormats()
    {
        return [
            self::FORMAT_SINGLE_ELIMINATION => 'Single Elimination',
            self::FORMAT_DOUBLE_ELIMINATION => 'Double Elimination',
            self::FORMAT_ROUND_ROBIN => 'Round Robin',
            self::FORMAT_SWISS => 'Swiss System',
            self::FORMAT_TIME_TRIAL => 'Time Trial'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }
    
    /**
     * Scope: Active competitions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Scheduled competitions
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }
    
    /**
     * Scope: Competitions by phase
     */
    public function scopeByPhase($query, $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }
    
    /**
     * Scope: Upcoming competitions (updated method)
     */
    public function scopeUpcoming($query)
    {
        $now = date('Y-m-d H:i:s');
        return $query->where('start_date', '>', $now)
                    ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_ACTIVE]);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['is_active'] = $this->isActive();
        $attributes['is_registration_open'] = $this->isRegistrationOpen();
        $attributes['is_full'] = $this->isFull();
        $attributes['team_count'] = $this->getTeamCount();
        $attributes['statistics'] = $this->getStatistics();
        $attributes['requirements_parsed'] = $this->getRequirements();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['format_label'] = self::getAvailableFormats()[$this->competition_format] ?? $this->competition_format;
        
        return $attributes;
    }
}