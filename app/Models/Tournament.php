<?php

namespace App\Models;

class Tournament extends BaseModel
{
    protected $table = 'tournaments';
    protected $softDeletes = false; // Tournament records should not be soft deleted
    
    protected $fillable = [
        'tournament_name', 'competition_phase_id', 'tournament_type', 'category_id',
        'venue_id', 'start_date', 'end_date', 'max_teams', 'current_teams',
        'rounds_total', 'current_round', 'seeding_method', 'advancement_count',
        'status', 'winner_team_id', 'second_place_id', 'third_place_id', 'created_by'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    // Validation rules
    protected $rules = [
        'tournament_name' => 'required|max:200',
        'competition_phase_id' => 'required|numeric',
        'tournament_type' => 'required',
        'category_id' => 'required|numeric',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'max_teams' => 'required|numeric|min:2',
        'advancement_count' => 'required|numeric|min:1',
        'created_by' => 'required|numeric'
    ];
    
    protected $messages = [
        'tournament_name.required' => 'Tournament name is required.',
        'competition_phase_id.required' => 'Competition phase is required.',
        'tournament_type.required' => 'Tournament type is required.',
        'category_id.required' => 'Category is required.',
        'start_date.required' => 'Start date is required.',
        'end_date.required' => 'End date is required.',
        'end_date.after' => 'End date must be after start date.',
        'max_teams.required' => 'Maximum teams is required.',
        'max_teams.min' => 'Tournament must have at least 2 teams.',
        'advancement_count.required' => 'Advancement count is required.',
        'created_by.required' => 'Creator is required.'
    ];
    
    // Tournament type constants
    const TYPE_ELIMINATION = 'elimination';
    const TYPE_ROUND_ROBIN = 'round_robin';
    const TYPE_SWISS = 'swiss';
    const TYPE_DOUBLE_ELIMINATION = 'double_elimination';
    
    // Tournament status constants
    const STATUS_SETUP = 'setup';
    const STATUS_REGISTRATION = 'registration';
    const STATUS_SEEDING = 'seeding';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    
    // Seeding method constants
    const SEEDING_RANDOM = 'random';
    const SEEDING_PERFORMANCE = 'performance';
    const SEEDING_REGIONAL = 'regional';
    const SEEDING_MANUAL = 'manual';
    
    protected $belongsTo = [
        'competition_phase' => ['model' => CompetitionPhase::class, 'foreign_key' => 'competition_phase_id'],
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id'],
        'venue' => ['model' => Venue::class, 'foreign_key' => 'venue_id'],
        'winner' => ['model' => Team::class, 'foreign_key' => 'winner_team_id'],
        'second_place' => ['model' => Team::class, 'foreign_key' => 'second_place_id'],
        'third_place' => ['model' => Team::class, 'foreign_key' => 'third_place_id'],
        'created_by_user' => ['model' => User::class, 'foreign_key' => 'created_by']
    ];

    protected $hasMany = [
        'brackets' => ['model' => TournamentBracket::class, 'foreign_key' => 'tournament_id'],
        'matches' => ['model' => TournamentMatch::class, 'foreign_key' => 'tournament_id'],
        'seedings' => ['model' => TournamentSeeding::class, 'foreign_key' => 'tournament_id'],
        'results' => ['model' => TournamentResult::class, 'foreign_key' => 'tournament_id']
    ];
    
    /**
     * Get participating teams
     */
    public function getParticipatingTeams()
    {
        return $this->db->query("
            SELECT t.*, ts.seed_number, ts.seeding_score, s.name as school_name
            FROM teams t
            JOIN tournament_seedings ts ON t.id = ts.team_id
            JOIN schools s ON t.school_id = s.id
            WHERE ts.tournament_id = ?
            ORDER BY ts.seed_number ASC
        ", [$this->id]);
    }
    
    /**
     * Get tournament brackets
     */
    public function getBrackets()
    {
        return $this->db->table('tournament_brackets')
            ->where('tournament_id', $this->id)
            ->orderBy('round_number')
            ->get();
    }
    
    /**
     * Get tournament matches
     */
    public function getMatches()
    {
        return $this->db->query("
            SELECT tm.*, 
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   w.name as winner_name,
                   tb.round_name, tb.round_number
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            LEFT JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            WHERE tm.tournament_id = ?
            ORDER BY tb.round_number, tm.match_number
        ", [$this->id]);
    }
    
    /**
     * Check if tournament is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * Check if tournament is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    /**
     * Check if tournament can start
     */
    public function canStart()
    {
        // Must have seeded teams
        $seededCount = $this->db->table('tournament_seedings')
            ->where('tournament_id', $this->id)
            ->count();
            
        return $seededCount >= 2 && $this->status === self::STATUS_SEEDING;
    }
    
    /**
     * Get tournament progress
     */
    public function getProgress()
    {
        $totalMatches = $this->db->table('tournament_matches')
            ->where('tournament_id', $this->id)
            ->count();
            
        $completedMatches = $this->db->table('tournament_matches')
            ->where('tournament_id', $this->id)
            ->whereIn('match_status', ['completed', 'bye'])
            ->count();
            
        return [
            'total_matches' => $totalMatches,
            'completed_matches' => $completedMatches,
            'percentage' => $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 2) : 0,
            'current_round' => $this->current_round,
            'total_rounds' => $this->rounds_total
        ];
    }
    
    /**
     * Get tournament statistics
     */
    public function getStatistics()
    {
        $teams = $this->getParticipatingTeams();
        $matches = $this->getMatches();
        $progress = $this->getProgress();
        
        $schoolCount = count(array_unique(array_column($teams, 'school_name')));
        
        return [
            'team_count' => count($teams),
            'school_count' => $schoolCount,
            'match_count' => count($matches),
            'progress' => $progress,
            'duration_days' => $this->getDurationDays(),
            'advancement_percentage' => ($this->advancement_count / $this->max_teams) * 100
        ];
    }
    
    /**
     * Get tournament duration in days
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
     * Get available tournament types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_ELIMINATION => 'Single Elimination',
            self::TYPE_DOUBLE_ELIMINATION => 'Double Elimination',
            self::TYPE_ROUND_ROBIN => 'Round Robin',
            self::TYPE_SWISS => 'Swiss System'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_SETUP => 'Setup',
            self::STATUS_REGISTRATION => 'Registration',
            self::STATUS_SEEDING => 'Seeding',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }
    
    /**
     * Get available seeding methods
     */
    public static function getAvailableSeedingMethods()
    {
        return [
            self::SEEDING_PERFORMANCE => 'Performance Based',
            self::SEEDING_REGIONAL => 'Regional Seeding',
            self::SEEDING_RANDOM => 'Random',
            self::SEEDING_MANUAL => 'Manual'
        ];
    }
    
    /**
     * Scope: Active tournaments
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Tournaments by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    /**
     * Scope: Tournaments by phase
     */
    public function scopeByPhase($query, $phaseId)
    {
        return $query->where('competition_phase_id', $phaseId);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['is_active'] = $this->isActive();
        $attributes['is_completed'] = $this->isCompleted();
        $attributes['can_start'] = $this->canStart();
        $attributes['progress'] = $this->getProgress();
        $attributes['statistics'] = $this->getStatistics();
        $attributes['duration_days'] = $this->getDurationDays();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['type_label'] = self::getAvailableTypes()[$this->tournament_type] ?? $this->tournament_type;
        $attributes['seeding_method_label'] = self::getAvailableSeedingMethods()[$this->seeding_method] ?? $this->seeding_method;
        
        return $attributes;
    }
}