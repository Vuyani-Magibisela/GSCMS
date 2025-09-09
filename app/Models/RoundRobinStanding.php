<?php

namespace App\Models;

class RoundRobinStanding extends BaseModel
{
    protected $table = 'round_robin_standings';
    protected $softDeletes = false;
    
    protected $fillable = [
        'tournament_id', 'team_id', 'matches_played', 'wins', 'draws', 'losses',
        'points_for', 'points_against', 'league_points', 'head_to_head',
        'ranking', 'qualified'
    ];
    
    protected $guarded = ['id', 'point_differential', 'created_at', 'updated_at'];
    
    // League points system: 3 for win, 1 for draw, 0 for loss
    const POINTS_WIN = 3;
    const POINTS_DRAW = 1;
    const POINTS_LOSS = 0;
    
    protected $belongsTo = [
        'tournament' => ['model' => Tournament::class, 'foreign_key' => 'tournament_id'],
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id']
    ];
    
    /**
     * Get standing with team information
     */
    public function getStandingDetails()
    {
        return $this->db->query("
            SELECT rrs.*,
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district,
                   c.name as category_name
            FROM round_robin_standings rrs
            JOIN teams t ON rrs.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE rrs.id = ?
        ", [$this->id])[0] ?? null;
    }
    
    /**
     * Calculate win percentage
     */
    public function getWinPercentage()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round(($this->wins / $this->matches_played) * 100, 2);
    }
    
    /**
     * Calculate draw percentage
     */
    public function getDrawPercentage()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round(($this->draws / $this->matches_played) * 100, 2);
    }
    
    /**
     * Calculate loss percentage
     */
    public function getLossPercentage()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round(($this->losses / $this->matches_played) * 100, 2);
    }
    
    /**
     * Calculate average points scored per match
     */
    public function getAveragePointsFor()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round($this->points_for / $this->matches_played, 2);
    }
    
    /**
     * Calculate average points conceded per match
     */
    public function getAveragePointsAgainst()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round($this->points_against / $this->matches_played, 2);
    }
    
    /**
     * Get point differential (calculated field from database)
     */
    public function getPointDifferential()
    {
        return $this->points_for - $this->points_against;
    }
    
    /**
     * Calculate points per match average
     */
    public function getPointsPerMatch()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round($this->league_points / $this->matches_played, 2);
    }
    
    /**
     * Get form/streak information
     */
    public function getForm($limit = 5)
    {
        // This would require match history - simplified implementation
        $matches = $this->db->query("
            SELECT tm.*, 
                   CASE 
                       WHEN tm.winner_team_id = ? THEN 'W'
                       WHEN tm.team1_score = tm.team2_score THEN 'D'
                       ELSE 'L'
                   END as result
            FROM tournament_matches tm
            WHERE (tm.team1_id = ? OR tm.team2_id = ?)
            AND tm.tournament_id = ?
            AND tm.match_status = 'completed'
            ORDER BY tm.updated_at DESC
            LIMIT ?
        ", [$this->team_id, $this->team_id, $this->team_id, $this->tournament_id, $limit]);
        
        return array_column($matches, 'result');
    }
    
    /**
     * Check if team is qualified for next stage
     */
    public function isQualified()
    {
        return $this->qualified;
    }
    
    /**
     * Check if team is in top 3
     */
    public function isInTopThree()
    {
        return $this->ranking <= 3;
    }
    
    /**
     * Get qualification status description
     */
    public function getQualificationStatus()
    {
        if ($this->ranking <= 3) {
            return match($this->ranking) {
                1 => 'Champion - Gold Medal',
                2 => 'Runner-up - Silver Medal',
                3 => 'Third Place - Bronze Medal'
            };
        }
        
        return 'Eliminated';
    }
    
    /**
     * Get head-to-head record as array
     */
    public function getHeadToHeadRecord()
    {
        if (!$this->head_to_head) {
            return [];
        }
        
        return json_decode($this->head_to_head, true) ?? [];
    }
    
    /**
     * Get head-to-head against specific team
     */
    public function getHeadToHeadVs($teamId)
    {
        $h2h = $this->getHeadToHeadRecord();
        
        return $h2h[$teamId] ?? null;
    }
    
    /**
     * Update standing after a match
     */
    public function recordMatch($pointsFor, $pointsAgainst, $result)
    {
        $updates = [
            'matches_played' => $this->matches_played + 1,
            'points_for' => $this->points_for + $pointsFor,
            'points_against' => $this->points_against + $pointsAgainst
        ];
        
        switch ($result) {
            case 'win':
                $updates['wins'] = $this->wins + 1;
                $updates['league_points'] = $this->league_points + self::POINTS_WIN;
                break;
                
            case 'draw':
                $updates['draws'] = $this->draws + 1;
                $updates['league_points'] = $this->league_points + self::POINTS_DRAW;
                break;
                
            case 'loss':
                $updates['losses'] = $this->losses + 1;
                $updates['league_points'] = $this->league_points + self::POINTS_LOSS;
                break;
        }
        
        return $this->update($updates);
    }
    
    /**
     * Get standings by tournament
     */
    public static function getByTournament($tournamentId)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT rrs.*,
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district
            FROM round_robin_standings rrs
            JOIN teams t ON rrs.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE rrs.tournament_id = ?
            ORDER BY rrs.ranking ASC, rrs.league_points DESC, 
                     (rrs.points_for - rrs.points_against) DESC
        ", [$tournamentId]);
    }
    
    /**
     * Get qualified teams (top 3)
     */
    public static function getQualified($tournamentId)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT rrs.*,
                   t.name as team_name, t.team_code,
                   s.name as school_name
            FROM round_robin_standings rrs
            JOIN teams t ON rrs.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE rrs.tournament_id = ? AND rrs.qualified = 1
            ORDER BY rrs.ranking ASC
        ", [$tournamentId]);
    }
    
    /**
     * Get tournament statistics
     */
    public static function getTournamentStats($tournamentId)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT 
                COUNT(*) as total_teams,
                SUM(matches_played) / 2 as total_matches,
                AVG(points_for) as avg_points_per_team,
                MAX(points_for) as highest_points,
                MIN(points_for) as lowest_points,
                COUNT(CASE WHEN qualified = 1 THEN 1 END) as qualified_teams
            FROM round_robin_standings
            WHERE tournament_id = ?
        ", [$tournamentId])[0];
    }
    
    /**
     * Scope: Qualified teams
     */
    public function scopeQualified($query)
    {
        return $query->where('qualified', true);
    }
    
    /**
     * Scope: Teams by ranking range
     */
    public function scopeByRanking($query, $from, $to = null)
    {
        if ($to === null) {
            return $query->where('ranking', $from);
        }
        
        return $query->whereBetween('ranking', [$from, $to]);
    }
    
    /**
     * Scope: Active standings (teams that played matches)
     */
    public function scopeActive($query)
    {
        return $query->where('matches_played', '>', 0);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        $attributes['win_percentage'] = $this->getWinPercentage();
        $attributes['draw_percentage'] = $this->getDrawPercentage();
        $attributes['loss_percentage'] = $this->getLossPercentage();
        $attributes['average_points_for'] = $this->getAveragePointsFor();
        $attributes['average_points_against'] = $this->getAveragePointsAgainst();
        $attributes['point_differential'] = $this->getPointDifferential();
        $attributes['points_per_match'] = $this->getPointsPerMatch();
        $attributes['is_qualified'] = $this->isQualified();
        $attributes['is_in_top_three'] = $this->isInTopThree();
        $attributes['qualification_status'] = $this->getQualificationStatus();
        $attributes['head_to_head_record'] = $this->getHeadToHeadRecord();
        $attributes['form'] = $this->getForm();
        $attributes['standing_details'] = $this->getStandingDetails();
        
        return $attributes;
    }
}