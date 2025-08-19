<?php

namespace App\Models;

class PhaseProgression extends BaseModel
{
    protected $table = 'phase_progressions';
    protected $softDeletes = true;
    
    protected $fillable = [
        'team_id', 'from_phase_id', 'to_phase_id', 'progression_date',
        'score', 'rank_in_category', 'qualified', 'advancement_reason',
        'competition_type', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'team_id' => 'required',
        'to_phase_id' => 'required',
        'progression_date' => 'required',
        'competition_type' => 'required'
    ];
    
    protected $messages = [
        'team_id.required' => 'Team is required.',
        'to_phase_id.required' => 'Target phase is required.',
        'progression_date.required' => 'Progression date is required.',
        'competition_type.required' => 'Competition type is required.'
    ];
    
    // Competition type constants
    const TYPE_PILOT = 'pilot';
    const TYPE_FULL = 'full';
    const TYPE_REGIONAL = 'regional';
    const TYPE_NATIONAL = 'national';
    
    protected $belongsTo = [
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id'],
        'fromPhase' => ['model' => Phase::class, 'foreign_key' => 'from_phase_id'],
        'toPhase' => ['model' => Phase::class, 'foreign_key' => 'to_phase_id']
    ];

    /**
     * Get team relation
     */
    public function team()
    {
        return $this->belongsTo('App\Models\Team', 'team_id');
    }
    
    /**
     * Get from phase relation
     */
    public function fromPhase()
    {
        return $this->belongsTo('App\Models\Phase', 'from_phase_id');
    }
    
    /**
     * Get to phase relation
     */
    public function toPhase()
    {
        return $this->belongsTo('App\Models\Phase', 'to_phase_id');
    }
    
    /**
     * Get progression history for a team
     */
    public function getTeamProgressionHistory($teamId)
    {
        return $this->db->query("
            SELECT pp.*, 
                   fp.name as from_phase_name, 
                   tp.name as to_phase_name,
                   t.name as team_name,
                   c.name as category_name
            FROM phase_progressions pp
            LEFT JOIN phases fp ON pp.from_phase_id = fp.id
            JOIN phases tp ON pp.to_phase_id = tp.id
            JOIN teams t ON pp.team_id = t.id
            JOIN categories c ON t.category_id = c.id
            WHERE pp.team_id = ?
            AND pp.deleted_at IS NULL
            ORDER BY pp.progression_date DESC
        ", [$teamId]);
    }
    
    /**
     * Get qualified teams for a phase by category
     */
    public function getQualifiedTeamsByPhaseAndCategory($phaseId, $categoryId = null)
    {
        $query = "
            SELECT pp.*, 
                   t.name as team_name,
                   t.school_id,
                   s.name as school_name,
                   c.name as category_name
            FROM phase_progressions pp
            JOIN teams t ON pp.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE pp.to_phase_id = ?
            AND pp.qualified = 1
            AND pp.deleted_at IS NULL
        ";
        
        $params = [$phaseId];
        
        if ($categoryId) {
            $query .= " AND t.category_id = ?";
            $params[] = $categoryId;
        }
        
        $query .= " ORDER BY c.name, pp.rank_in_category ASC";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Record team progression for pilot programme
     */
    public function recordPilotProgression($teamId, $fromPhaseId, $toPhaseId, $score, $rank, $reason = null)
    {
        $data = [
            'team_id' => $teamId,
            'from_phase_id' => $fromPhaseId,
            'to_phase_id' => $toPhaseId,
            'progression_date' => date('Y-m-d H:i:s'),
            'score' => $score,
            'rank_in_category' => $rank,
            'qualified' => true,
            'advancement_reason' => $reason ?: 'Qualified from pilot Phase 1 to Phase 3',
            'competition_type' => self::TYPE_PILOT
        ];
        
        return $this->create($data);
    }
    
    /**
     * Get advancement statistics by category for a competition type
     */
    public function getAdvancementStatistics($competitionType = self::TYPE_PILOT)
    {
        $query = "
            SELECT 
                c.name as category_name,
                c.id as category_id,
                COUNT(pp.id) as total_progressions,
                COUNT(CASE WHEN pp.qualified = 1 THEN 1 END) as qualified_teams,
                AVG(pp.score) as average_score,
                MIN(pp.score) as min_score,
                MAX(pp.score) as max_score
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id
            LEFT JOIN phase_progressions pp ON t.id = pp.team_id 
                AND pp.competition_type = ?
                AND pp.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY c.name
        ";
        
        return $this->db->query($query, [$competitionType]);
    }
    
    /**
     * Get phase progression summary for all teams
     */
    public function getProgressionSummary($competitionType = self::TYPE_PILOT)
    {
        $query = "
            SELECT 
                fp.name as from_phase,
                tp.name as to_phase,
                COUNT(pp.id) as progression_count,
                COUNT(CASE WHEN pp.qualified = 1 THEN 1 END) as qualified_count,
                AVG(pp.score) as avg_score
            FROM phase_progressions pp
            LEFT JOIN phases fp ON pp.from_phase_id = fp.id
            JOIN phases tp ON pp.to_phase_id = tp.id
            WHERE pp.competition_type = ?
            AND pp.deleted_at IS NULL
            GROUP BY pp.from_phase_id, pp.to_phase_id, fp.name, tp.name
            ORDER BY fp.id, tp.id
        ";
        
        return $this->db->query($query, [$competitionType]);
    }
    
    /**
     * Check if team has already progressed to a specific phase
     */
    public function hasTeamProgressedToPhase($teamId, $phaseId)
    {
        return $this->db->table($this->table)
            ->where('team_id', $teamId)
            ->where('to_phase_id', $phaseId)
            ->whereNull('deleted_at')
            ->exists();
    }
    
    /**
     * Get teams that can advance from one phase to another
     */
    public function getEligibleTeamsForAdvancement($fromPhaseId, $toPhaseId, $maxTeamsPerCategory = 6)
    {
        $query = "
            SELECT 
                t.*,
                c.name as category_name,
                s.name as school_name,
                COALESCE(AVG(sc.total_score), 0) as average_score,
                ROW_NUMBER() OVER (PARTITION BY t.category_id ORDER BY COALESCE(AVG(sc.total_score), 0) DESC) as category_rank
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = ?
            AND t.deleted_at IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM phase_progressions pp 
                WHERE pp.team_id = t.id 
                AND pp.to_phase_id = ?
                AND pp.deleted_at IS NULL
            )
            GROUP BY t.id, c.name, s.name
            HAVING category_rank <= ?
            ORDER BY c.name, category_rank
        ";
        
        return $this->db->query($query, [$fromPhaseId, $toPhaseId, $maxTeamsPerCategory]);
    }
    
    /**
     * Get available competition types
     */
    public static function getAvailableCompetitionTypes()
    {
        return [
            self::TYPE_PILOT => 'Pilot Programme',
            self::TYPE_FULL => 'Full Competition System',
            self::TYPE_REGIONAL => 'Regional Competition',
            self::TYPE_NATIONAL => 'National Competition'
        ];
    }
    
    /**
     * Scope: Qualified progressions
     */
    public function scopeQualified($query)
    {
        return $query->where('qualified', true);
    }
    
    /**
     * Scope: By competition type
     */
    public function scopeByCompetitionType($query, $type)
    {
        return $query->where('competition_type', $type);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['competition_type_label'] = self::getAvailableCompetitionTypes()[$this->competition_type] ?? $this->competition_type;
        
        return $attributes;
    }
}