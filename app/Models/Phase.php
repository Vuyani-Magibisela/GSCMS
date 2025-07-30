<?php

namespace App\Models;

class Phase extends BaseModel
{
    protected $table = 'phases';
    protected $softDeletes = true;
    
    protected $fillable = [
        'name', 'code', 'description', 'order_sequence', 'status',
        'registration_start', 'registration_end', 'competition_start', 'competition_end',
        'qualification_criteria', 'max_teams', 'venue_requirements',
        'requires_qualification', 'advancement_percentage', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'name' => 'required|max:255',
        'code' => 'required|max:20|unique',
        'order_sequence' => 'required',
        'status' => 'required',
        'registration_start' => 'required',
        'registration_end' => 'required',
        'competition_start' => 'required',
        'competition_end' => 'required'
    ];
    
    protected $messages = [
        'name.required' => 'Phase name is required.',
        'code.required' => 'Phase code is required.',
        'code.unique' => 'Phase code must be unique.',
        'order_sequence.required' => 'Phase sequence order is required.',
        'status.required' => 'Phase status is required.',
        'registration_start.required' => 'Registration start date is required.',
        'registration_end.required' => 'Registration end date is required.',
        'competition_start.required' => 'Competition start date is required.',
        'competition_end.required' => 'Competition end date is required.'
    ];
    
    // Phase constants based on South African competition structure
    const PHASE_SCHOOL = 'SCHOOL';
    const PHASE_DISTRICT = 'DISTRICT';
    const PHASE_PROVINCIAL = 'PROVINCIAL';
    const PHASE_NATIONAL = 'NATIONAL';
    
    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_REGISTRATION_OPEN = 'registration_open';
    const STATUS_REGISTRATION_CLOSED = 'registration_closed';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    protected $hasMany = [
        'teams' => ['model' => Team::class, 'foreign_key' => 'phase_id'],
        'competitions' => ['model' => Competition::class, 'foreign_key' => 'phase_id']
    ];
    
    /**
     * Get default phases for competition
     */
    public static function getDefaultPhases()
    {
        return [
            [
                'name' => 'School Phase',
                'code' => self::PHASE_SCHOOL,
                'description' => 'Initial school-level competition and team formation',
                'order_sequence' => 1,
                'requires_qualification' => false,
                'advancement_percentage' => 100, // All teams advance initially
                'max_teams' => null,
                'status' => self::STATUS_DRAFT
            ],
            [
                'name' => 'District Phase',
                'code' => self::PHASE_DISTRICT,
                'description' => 'District-level competition between schools',
                'order_sequence' => 2,
                'requires_qualification' => true,
                'advancement_percentage' => 30, // Top 30% advance
                'max_teams' => 200,
                'status' => self::STATUS_DRAFT
            ],
            [
                'name' => 'Provincial Phase',
                'code' => self::PHASE_PROVINCIAL,
                'description' => 'Provincial-level competition',
                'order_sequence' => 3,
                'requires_qualification' => true,
                'advancement_percentage' => 20, // Top 20% advance
                'max_teams' => 100,
                'status' => self::STATUS_DRAFT
            ],
            [
                'name' => 'National Phase',
                'code' => self::PHASE_NATIONAL,
                'description' => 'National championship finals',
                'order_sequence' => 4,
                'requires_qualification' => true,
                'advancement_percentage' => 0, // Final phase
                'max_teams' => 50,
                'status' => self::STATUS_DRAFT
            ]
        ];
    }
    
    /**
     * Get phase teams
     */
    public function teams()
    {
        return $this->hasMany('App\Models\Team', 'phase_id', 'id');
    }
    
    /**
     * Get phase competitions
     */
    public function competitions()
    {
        return $this->hasMany('App\Models\Competition', 'phase_id', 'id');
    }
    
    /**
     * Get next phase in sequence
     */
    public function getNextPhase()
    {
        return $this->db->table('phases')
            ->where('order_sequence', '>', $this->order_sequence)
            ->whereNull('deleted_at')
            ->orderBy('order_sequence')
            ->first();
    }
    
    /**
     * Get previous phase in sequence
     */
    public function getPreviousPhase()
    {
        return $this->db->table('phases')
            ->where('order_sequence', '<', $this->order_sequence)
            ->whereNull('deleted_at')
            ->orderBy('order_sequence', 'DESC')
            ->first();
    }
    
    /**
     * Check if registration is open
     */
    public function isRegistrationOpen()
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->status === self::STATUS_REGISTRATION_OPEN &&
               $this->registration_start <= $now &&
               $this->registration_end >= $now;
    }
    
    /**
     * Check if competition is active
     */
    public function isCompetitionActive()
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->status === self::STATUS_ACTIVE &&
               $this->competition_start <= $now &&
               $this->competition_end >= $now;
    }
    
    /**
     * Get team count for this phase
     */
    public function getTeamCount()
    {
        return $this->db->table('teams')
            ->where('phase_id', $this->id)
            ->whereNull('deleted_at')
            ->count();
    }
    
    /**
     * Get qualified teams from previous phase
     */
    public function getQualifiedTeamsFromPrevious()
    {
        $previousPhase = $this->getPreviousPhase();
        if (!$previousPhase) {
            return [];
        }
        
        $advancementCount = 0;
        if ($previousPhase['advancement_percentage'] > 0) {
            $totalTeams = $this->db->table('teams')
                ->where('phase_id', $previousPhase['id'])
                ->whereNull('deleted_at')
                ->count();
            $advancementCount = ceil($totalTeams * $previousPhase['advancement_percentage'] / 100);
        }
        
        // Get top performing teams from previous phase
        $query = "
            SELECT t.*, 
                   COALESCE(SUM(s.total_score), 0) as total_score,
                   RANK() OVER (PARTITION BY t.category_id ORDER BY COALESCE(SUM(s.total_score), 0) DESC) as ranking
            FROM teams t
            LEFT JOIN scores s ON t.id = s.team_id
            WHERE t.phase_id = ?
            AND t.deleted_at IS NULL
            GROUP BY t.id
            HAVING ranking <= ?
            ORDER BY t.category_id, ranking
        ";
        
        return $this->db->query($query, [$previousPhase['id'], $advancementCount]);
    }
    
    /**
     * Advance teams to this phase
     */
    public function advanceTeamsFromPrevious()
    {
        $qualifiedTeams = $this->getQualifiedTeamsFromPrevious();
        $advancedCount = 0;
        
        foreach ($qualifiedTeams as $team) {
            // Check if team already exists in this phase
            $existingTeam = $this->db->table('teams')
                ->where('school_id', $team['school_id'])
                ->where('category_id', $team['category_id'])
                ->where('phase_id', $this->id)
                ->whereNull('deleted_at')
                ->first();
                
            if (!$existingTeam) {
                // Create new team entry for this phase
                $newTeamData = [
                    'school_id' => $team['school_id'],
                    'category_id' => $team['category_id'],
                    'phase_id' => $this->id,
                    'name' => $team['name'],
                    'team_code' => $team['team_code'] . '-' . $this->code,
                    'coach1_id' => $team['coach1_id'],
                    'coach2_id' => $team['coach2_id'],
                    'status' => Team::STATUS_APPROVED,
                    'qualification_score' => $team['total_score'],
                    'notes' => "Advanced from {$this->getPreviousPhase()['name']} with score: {$team['total_score']}"
                ];
                
                $this->db->table('teams')->insert($newTeamData);
                
                // Copy participants to new team
                $this->copyParticipantsToNewTeam($team['id'], $this->db->lastInsertId());
                $advancedCount++;
            }
        }
        
        return [
            'advanced_count' => $advancedCount,
            'qualified_teams' => $qualifiedTeams
        ];
    }
    
    /**
     * Copy participants from one team to another
     */
    private function copyParticipantsToNewTeam($sourceTeamId, $targetTeamId)
    {
        $participants = $this->db->table('participants')
            ->where('team_id', $sourceTeamId)
            ->whereNull('deleted_at')
            ->get();
            
        foreach ($participants as $participant) {
            $newParticipantData = $participant;
            unset($newParticipantData['id']);
            $newParticipantData['team_id'] = $targetTeamId;
            $newParticipantData['created_at'] = date('Y-m-d H:i:s');
            $newParticipantData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->table('participants')->insert($newParticipantData);
        }
    }
    
    /**
     * Get phase timeline status
     */
    public function getTimelineStatus()
    {
        $now = date('Y-m-d H:i:s');
        
        if ($now < $this->registration_start) {
            return 'upcoming';
        } elseif ($now >= $this->registration_start && $now <= $this->registration_end) {
            return 'registration_open';
        } elseif ($now > $this->registration_end && $now < $this->competition_start) {
            return 'registration_closed';
        } elseif ($now >= $this->competition_start && $now <= $this->competition_end) {
            return 'competition_active';
        } else {
            return 'completed';
        }
    }
    
    /**
     * Get venue requirements as array
     */
    public function getVenueRequirements()
    {
        if (!$this->venue_requirements) {
            return [];
        }
        
        return json_decode($this->venue_requirements, true) ?? [];
    }
    
    /**
     * Get qualification criteria as array
     */
    public function getQualificationCriteria()
    {
        if (!$this->qualification_criteria) {
            return [];
        }
        
        return json_decode($this->qualification_criteria, true) ?? [];
    }
    
    /**
     * Get phase statistics
     */
    public function getStatistics()
    {
        $teamCount = $this->getTeamCount();
        
        $participantCount = $this->db->query("
            SELECT COUNT(p.id) as participant_count
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            WHERE t.phase_id = ?
            AND p.deleted_at IS NULL
            AND t.deleted_at IS NULL
        ", [$this->id])[0]['participant_count'] ?? 0;
        
        $schoolCount = $this->db->query("
            SELECT COUNT(DISTINCT t.school_id) as school_count
            FROM teams t
            WHERE t.phase_id = ?
            AND t.deleted_at IS NULL
        ", [$this->id])[0]['school_count'] ?? 0;
        
        $categoryStats = $this->db->query("
            SELECT 
                c.name as category_name,
                COUNT(t.id) as team_count,
                COUNT(p.id) as participant_count
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id AND t.phase_id = ? AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY c.name
        ", [$this->id]);
        
        return [
            'team_count' => $teamCount,
            'participant_count' => $participantCount,
            'school_count' => $schoolCount,
            'category_statistics' => $categoryStats
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_REGISTRATION_OPEN => 'Registration Open',
            self::STATUS_REGISTRATION_CLOSED => 'Registration Closed',
            self::STATUS_ACTIVE => 'Competition Active', 
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }
    
    /**
     * Scope: Active phases
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_REGISTRATION_OPEN, self::STATUS_ACTIVE]);
    }
    
    /**
     * Scope: Phases by sequence order
     */
    public function scopeBySequence($query)
    {
        return $query->orderBy('order_sequence');
    }
    
    /**
     * Scope: Current phases (registration open or competition active)
     */
    public function scopeCurrent($query)
    {
        $now = date('Y-m-d H:i:s');
        return $query->where(function($q) use ($now) {
            $q->where(function($sq) use ($now) {
                $sq->where('registration_start', '<=', $now)
                   ->where('registration_end', '>=', $now);
            })->orWhere(function($sq) use ($now) {
                $sq->where('competition_start', '<=', $now)
                   ->where('competition_end', '>=', $now);
            });
        });
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['team_count'] = $this->getTeamCount();
        $attributes['statistics'] = $this->getStatistics();
        $attributes['timeline_status'] = $this->getTimelineStatus();
        $attributes['is_registration_open'] = $this->isRegistrationOpen();
        $attributes['is_competition_active'] = $this->isCompetitionActive();
        $attributes['venue_requirements_parsed'] = $this->getVenueRequirements();
        $attributes['qualification_criteria_parsed'] = $this->getQualificationCriteria();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['next_phase'] = $this->getNextPhase();
        $attributes['previous_phase'] = $this->getPreviousPhase();
        
        return $attributes;
    }
}