<?php

namespace App\Models;

class RoundRobinSchedule extends BaseModel
{
    protected $table = 'round_robin_schedule';
    protected $softDeletes = false;
    
    protected $fillable = [
        'tournament_id', 'round_number', 'match_day', 'team1_id', 'team2_id',
        'venue_id', 'time_slot', 'match_id', 'is_played'
    ];
    
    protected $guarded = ['id', 'created_at'];
    
    protected $belongsTo = [
        'tournament' => ['model' => Tournament::class, 'foreign_key' => 'tournament_id'],
        'team1' => ['model' => Team::class, 'foreign_key' => 'team1_id'],
        'team2' => ['model' => Team::class, 'foreign_key' => 'team2_id'],
        'venue' => ['model' => Venue::class, 'foreign_key' => 'venue_id'],
        'match' => ['model' => TournamentMatch::class, 'foreign_key' => 'match_id']
    ];
    
    /**
     * Get schedule entry with detailed information
     */
    public function getScheduleDetails()
    {
        return $this->db->query("
            SELECT rrs.*,
                   t1.name as team1_name, t1.team_code as team1_code,
                   s1.name as team1_school,
                   t2.name as team2_name, t2.team_code as team2_code,
                   s2.name as team2_school,
                   v.name as venue_name, v.address as venue_address,
                   tm.team1_score, tm.team2_score, tm.match_status,
                   tm.winner_team_id, tm.actual_start_time, tm.actual_end_time,
                   w.name as winner_name
            FROM round_robin_schedule rrs
            JOIN teams t1 ON rrs.team1_id = t1.id
            JOIN schools s1 ON t1.school_id = s1.id
            JOIN teams t2 ON rrs.team2_id = t2.id
            JOIN schools s2 ON t2.school_id = s2.id
            LEFT JOIN venues v ON rrs.venue_id = v.id
            LEFT JOIN tournament_matches tm ON rrs.match_id = tm.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            WHERE rrs.id = ?
        ", [$this->id])[0] ?? null;
    }
    
    /**
     * Check if match is scheduled (has date and time)
     */
    public function isScheduled()
    {
        return $this->match_day !== null && $this->time_slot !== null;
    }
    
    /**
     * Check if match has been played
     */
    public function isPlayed()
    {
        return $this->is_played;
    }
    
    /**
     * Check if match is today
     */
    public function isToday()
    {
        return $this->match_day === date('Y-m-d');
    }
    
    /**
     * Check if match is upcoming
     */
    public function isUpcoming()
    {
        if (!$this->match_day) {
            return false;
        }
        
        $matchDateTime = $this->match_day;
        if ($this->time_slot) {
            $matchDateTime .= ' ' . $this->time_slot;
        }
        
        return strtotime($matchDateTime) > time() && !$this->is_played;
    }
    
    /**
     * Check if match is overdue
     */
    public function isOverdue()
    {
        if (!$this->match_day || $this->is_played) {
            return false;
        }
        
        $matchDateTime = $this->match_day;
        if ($this->time_slot) {
            $matchDateTime .= ' ' . $this->time_slot;
        } else {
            $matchDateTime .= ' 23:59:59'; // End of day if no time specified
        }
        
        return strtotime($matchDateTime) < time();
    }
    
    /**
     * Get match result
     */
    public function getMatchResult()
    {
        if (!$this->match_id) {
            return null;
        }
        
        $match = $this->db->table('tournament_matches')->find($this->match_id);
        
        if (!$match || $match['match_status'] !== 'completed') {
            return null;
        }
        
        return [
            'team1_score' => $match['team1_score'],
            'team2_score' => $match['team2_score'],
            'winner_id' => $match['winner_team_id'],
            'result' => $match['team1_score'] > $match['team2_score'] ? 'team1' : 
                       ($match['team2_score'] > $match['team1_score'] ? 'team2' : 'draw')
        ];
    }
    
    /**
     * Schedule the match
     */
    public function scheduleMatch($date, $time = null, $venueId = null)
    {
        $updates = [
            'match_day' => $date,
            'time_slot' => $time,
            'venue_id' => $venueId
        ];
        
        // Also update the associated match if it exists
        if ($this->match_id) {
            $scheduledDateTime = $date . ($time ? ' ' . $time : '');
            
            $this->db->table('tournament_matches')
                ->where('id', $this->match_id)
                ->update([
                    'scheduled_time' => $scheduledDateTime,
                    'venue_id' => $venueId
                ]);
        }
        
        return $this->update($updates);
    }
    
    /**
     * Mark match as played
     */
    public function markAsPlayed()
    {
        return $this->update(['is_played' => true]);
    }
    
    /**
     * Get formatted match time
     */
    public function getFormattedMatchTime()
    {
        if (!$this->match_day) {
            return 'Not scheduled';
        }
        
        $formatted = date('D, M j, Y', strtotime($this->match_day));
        
        if ($this->time_slot) {
            $formatted .= ' at ' . date('g:i A', strtotime($this->time_slot));
        }
        
        return $formatted;
    }
    
    /**
     * Get days until match
     */
    public function getDaysUntilMatch()
    {
        if (!$this->match_day) {
            return null;
        }
        
        $today = strtotime('today');
        $matchDay = strtotime($this->match_day);
        
        return round(($matchDay - $today) / (60 * 60 * 24));
    }
    
    /**
     * Get schedule by tournament
     */
    public static function getByTournament($tournamentId)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT rrs.*,
                   t1.name as team1_name, t1.team_code as team1_code,
                   s1.name as team1_school,
                   t2.name as team2_name, t2.team_code as team2_code,
                   s2.name as team2_school,
                   v.name as venue_name,
                   tm.team1_score, tm.team2_score, tm.match_status
            FROM round_robin_schedule rrs
            JOIN teams t1 ON rrs.team1_id = t1.id
            JOIN schools s1 ON t1.school_id = s1.id
            JOIN teams t2 ON rrs.team2_id = t2.id
            JOIN schools s2 ON t2.school_id = s2.id
            LEFT JOIN venues v ON rrs.venue_id = v.id
            LEFT JOIN tournament_matches tm ON rrs.match_id = tm.id
            WHERE rrs.tournament_id = ?
            ORDER BY rrs.round_number, rrs.match_day, rrs.time_slot
        ", [$tournamentId]);
    }
    
    /**
     * Get schedule by round
     */
    public static function getByRound($tournamentId, $roundNumber)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT rrs.*,
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   v.name as venue_name,
                   tm.team1_score, tm.team2_score, tm.match_status
            FROM round_robin_schedule rrs
            JOIN teams t1 ON rrs.team1_id = t1.id
            JOIN teams t2 ON rrs.team2_id = t2.id
            LEFT JOIN venues v ON rrs.venue_id = v.id
            LEFT JOIN tournament_matches tm ON rrs.match_id = tm.id
            WHERE rrs.tournament_id = ? AND rrs.round_number = ?
            ORDER BY rrs.match_day, rrs.time_slot
        ", [$tournamentId, $roundNumber]);
    }
    
    /**
     * Get today's matches
     */
    public static function getTodaysMatches($tournamentId = null)
    {
        $db = Database::getInstance();
        
        $query = "
            SELECT rrs.*,
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   v.name as venue_name,
                   tm.team1_score, tm.team2_score, tm.match_status,
                   tour.tournament_name
            FROM round_robin_schedule rrs
            JOIN teams t1 ON rrs.team1_id = t1.id
            JOIN teams t2 ON rrs.team2_id = t2.id
            JOIN tournaments tour ON rrs.tournament_id = tour.id
            LEFT JOIN venues v ON rrs.venue_id = v.id
            LEFT JOIN tournament_matches tm ON rrs.match_id = tm.id
            WHERE rrs.match_day = ?
        ";
        
        $params = [date('Y-m-d')];
        
        if ($tournamentId) {
            $query .= " AND rrs.tournament_id = ?";
            $params[] = $tournamentId;
        }
        
        $query .= " ORDER BY rrs.time_slot";
        
        return $db->query($query, $params);
    }
    
    /**
     * Get upcoming matches
     */
    public static function getUpcomingMatches($tournamentId = null, $days = 7)
    {
        $db = Database::getInstance();
        
        $query = "
            SELECT rrs.*,
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   v.name as venue_name,
                   tm.match_status,
                   tour.tournament_name
            FROM round_robin_schedule rrs
            JOIN teams t1 ON rrs.team1_id = t1.id
            JOIN teams t2 ON rrs.team2_id = t2.id
            JOIN tournaments tour ON rrs.tournament_id = tour.id
            LEFT JOIN venues v ON rrs.venue_id = v.id
            LEFT JOIN tournament_matches tm ON rrs.match_id = tm.id
            WHERE rrs.match_day BETWEEN ? AND ?
            AND rrs.is_played = 0
        ";
        
        $params = [date('Y-m-d'), date('Y-m-d', strtotime("+{$days} days"))];
        
        if ($tournamentId) {
            $query .= " AND rrs.tournament_id = ?";
            $params[] = $tournamentId;
        }
        
        $query .= " ORDER BY rrs.match_day, rrs.time_slot";
        
        return $db->query($query, $params);
    }
    
    /**
     * Get tournament schedule statistics
     */
    public static function getTournamentScheduleStats($tournamentId)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT 
                COUNT(*) as total_matches,
                COUNT(CASE WHEN match_day IS NOT NULL THEN 1 END) as scheduled_matches,
                COUNT(CASE WHEN is_played = 1 THEN 1 END) as completed_matches,
                COUNT(CASE WHEN match_day = ? THEN 1 END) as todays_matches,
                COUNT(CASE WHEN match_day > ? AND is_played = 0 THEN 1 END) as upcoming_matches,
                COUNT(CASE WHEN match_day < ? AND is_played = 0 THEN 1 END) as overdue_matches,
                MAX(round_number) as total_rounds
            FROM round_robin_schedule
            WHERE tournament_id = ?
        ", [date('Y-m-d'), date('Y-m-d'), date('Y-m-d'), $tournamentId])[0];
    }
    
    /**
     * Scope: Scheduled matches
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('match_day');
    }
    
    /**
     * Scope: Played matches
     */
    public function scopePlayed($query)
    {
        return $query->where('is_played', true);
    }
    
    /**
     * Scope: Unplayed matches
     */
    public function scopeUnplayed($query)
    {
        return $query->where('is_played', false);
    }
    
    /**
     * Scope: Matches by round
     */
    public function scopeByRound($query, $round)
    {
        return $query->where('round_number', $round);
    }
    
    /**
     * Scope: Today's matches
     */
    public function scopeToday($query)
    {
        return $query->where('match_day', date('Y-m-d'));
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        $attributes['is_scheduled'] = $this->isScheduled();
        $attributes['is_played'] = $this->isPlayed();
        $attributes['is_today'] = $this->isToday();
        $attributes['is_upcoming'] = $this->isUpcoming();
        $attributes['is_overdue'] = $this->isOverdue();
        $attributes['match_result'] = $this->getMatchResult();
        $attributes['formatted_match_time'] = $this->getFormattedMatchTime();
        $attributes['days_until_match'] = $this->getDaysUntilMatch();
        $attributes['schedule_details'] = $this->getScheduleDetails();
        
        return $attributes;
    }
}