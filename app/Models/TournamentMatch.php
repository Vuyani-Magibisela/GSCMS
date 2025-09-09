<?php

namespace App\Models;

class TournamentMatch extends BaseModel
{
    protected $table = 'tournament_matches';
    protected $softDeletes = false;
    
    protected $fillable = [
        'tournament_id', 'bracket_id', 'match_number', 'match_position',
        'team1_id', 'team2_id', 'team1_seed', 'team2_seed',
        'team1_score', 'team2_score', 'winner_team_id', 'loser_team_id',
        'next_match_id', 'consolation_match_id', 'venue_id', 'table_number',
        'scheduled_time', 'actual_start_time', 'actual_end_time',
        'match_status', 'forfeit_reason', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    // Match status constants
    const STATUS_PENDING = 'pending';
    const STATUS_READY = 'ready';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FORFEIT = 'forfeit';
    const STATUS_BYE = 'bye';
    
    protected $belongsTo = [
        'tournament' => ['model' => Tournament::class, 'foreign_key' => 'tournament_id'],
        'bracket' => ['model' => TournamentBracket::class, 'foreign_key' => 'bracket_id'],
        'team1' => ['model' => Team::class, 'foreign_key' => 'team1_id'],
        'team2' => ['model' => Team::class, 'foreign_key' => 'team2_id'],
        'winner' => ['model' => Team::class, 'foreign_key' => 'winner_team_id'],
        'loser' => ['model' => Team::class, 'foreign_key' => 'loser_team_id'],
        'next_match' => ['model' => TournamentMatch::class, 'foreign_key' => 'next_match_id'],
        'consolation_match' => ['model' => TournamentMatch::class, 'foreign_key' => 'consolation_match_id'],
        'venue' => ['model' => Venue::class, 'foreign_key' => 'venue_id']
    ];
    
    /**
     * Get match details with team information
     */
    public function getMatchDetails()
    {
        return $this->db->query("
            SELECT tm.*,
                   t1.name as team1_name, t1.team_code as team1_code,
                   s1.name as team1_school,
                   t2.name as team2_name, t2.team_code as team2_code,
                   s2.name as team2_school,
                   w.name as winner_name, w.team_code as winner_code,
                   tb.round_name, tb.round_number,
                   v.name as venue_name
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN schools s1 ON t1.school_id = s1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN schools s2 ON t2.school_id = s2.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            LEFT JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            LEFT JOIN venues v ON tm.venue_id = v.id
            WHERE tm.id = ?
        ", [$this->id])[0] ?? null;
    }
    
    /**
     * Check if match is ready to be played
     */
    public function isReady()
    {
        return $this->match_status === self::STATUS_READY && 
               $this->team1_id && $this->team2_id;
    }
    
    /**
     * Check if match is completed
     */
    public function isCompleted()
    {
        return in_array($this->match_status, [self::STATUS_COMPLETED, self::STATUS_BYE]);
    }
    
    /**
     * Check if match has started
     */
    public function hasStarted()
    {
        return in_array($this->match_status, [
            self::STATUS_IN_PROGRESS, 
            self::STATUS_COMPLETED, 
            self::STATUS_FORFEIT
        ]);
    }
    
    /**
     * Start the match
     */
    public function startMatch()
    {
        if (!$this->isReady()) {
            throw new \Exception("Match is not ready to start");
        }
        
        return $this->update([
            'match_status' => self::STATUS_IN_PROGRESS,
            'actual_start_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Complete the match with scores
     */
    public function completeMatch($team1Score, $team2Score, $forfeit = false, $forfeitReason = null)
    {
        if (!$this->hasStarted() && !$forfeit) {
            throw new \Exception("Match must be started before completion");
        }
        
        // Determine winner
        $winnerId = null;
        $loserId = null;
        
        if ($forfeit) {
            // Handle forfeit case
            $status = self::STATUS_FORFEIT;
        } else {
            $status = self::STATUS_COMPLETED;
            
            if ($team1Score > $team2Score) {
                $winnerId = $this->team1_id;
                $loserId = $this->team2_id;
            } elseif ($team2Score > $team1Score) {
                $winnerId = $this->team2_id;
                $loserId = $this->team1_id;
            }
            // Draws keep both winner_team_id and loser_team_id as null
        }
        
        return $this->update([
            'team1_score' => $team1Score,
            'team2_score' => $team2Score,
            'winner_team_id' => $winnerId,
            'loser_team_id' => $loserId,
            'match_status' => $status,
            'actual_end_time' => date('Y-m-d H:i:s'),
            'forfeit_reason' => $forfeitReason
        ]);
    }
    
    /**
     * Get match duration in minutes
     */
    public function getMatchDuration()
    {
        if (!$this->actual_start_time || !$this->actual_end_time) {
            return null;
        }
        
        $start = strtotime($this->actual_start_time);
        $end = strtotime($this->actual_end_time);
        
        return round(($end - $start) / 60);
    }
    
    /**
     * Get score differential
     */
    public function getScoreDifferential()
    {
        if ($this->team1_score === null || $this->team2_score === null) {
            return null;
        }
        
        return abs($this->team1_score - $this->team2_score);
    }
    
    /**
     * Check if match was a close game (within 5 points)
     */
    public function isCloseGame()
    {
        $differential = $this->getScoreDifferential();
        return $differential !== null && $differential <= 5;
    }
    
    /**
     * Get available match statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_READY => 'Ready',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FORFEIT => 'Forfeit',
            self::STATUS_BYE => 'Bye'
        ];
    }
    
    /**
     * Scope: Completed matches
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('match_status', [self::STATUS_COMPLETED, self::STATUS_BYE]);
    }
    
    /**
     * Scope: Active matches
     */
    public function scopeActive($query)
    {
        return $query->whereIn('match_status', [self::STATUS_READY, self::STATUS_IN_PROGRESS]);
    }
    
    /**
     * Scope: Matches by bracket
     */
    public function scopeByBracket($query, $bracketId)
    {
        return $query->where('bracket_id', $bracketId);
    }
    
    /**
     * Scope: Scheduled matches
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_time');
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        $attributes['is_ready'] = $this->isReady();
        $attributes['is_completed'] = $this->isCompleted();
        $attributes['has_started'] = $this->hasStarted();
        $attributes['match_duration'] = $this->getMatchDuration();
        $attributes['score_differential'] = $this->getScoreDifferential();
        $attributes['is_close_game'] = $this->isCloseGame();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->match_status] ?? $this->match_status;
        $attributes['match_details'] = $this->getMatchDetails();
        
        return $attributes;
    }
}