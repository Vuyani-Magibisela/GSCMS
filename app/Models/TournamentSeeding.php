<?php

namespace App\Models;

class TournamentSeeding extends BaseModel
{
    protected $table = 'tournament_seedings';
    protected $softDeletes = false;
    
    protected $fillable = [
        'tournament_id', 'team_id', 'seed_number', 'seeding_score',
        'previous_phase_rank', 'district_rank', 'elo_rating',
        'matches_played', 'matches_won', 'matches_lost',
        'points_for', 'points_against'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    // Validation rules
    protected $rules = [
        'tournament_id' => 'required|numeric',
        'team_id' => 'required|numeric',
        'seed_number' => 'required|numeric|min:1',
        'seeding_score' => 'numeric|min:0',
        'elo_rating' => 'numeric|min:0|max:3000'
    ];
    
    protected $belongsTo = [
        'tournament' => ['model' => Tournament::class, 'foreign_key' => 'tournament_id'],
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id']
    ];
    
    /**
     * Get seeding with team information
     */
    public function getSeedingDetails()
    {
        return $this->db->query("
            SELECT ts.*, 
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district,
                   c.name as category_name
            FROM tournament_seedings ts
            JOIN teams t ON ts.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE ts.id = ?
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
        
        return round(($this->matches_won / $this->matches_played) * 100, 2);
    }
    
    /**
     * Calculate average points per game
     */
    public function getAveragePointsFor()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round($this->points_for / $this->matches_played, 2);
    }
    
    /**
     * Calculate average points against per game
     */
    public function getAveragePointsAgainst()
    {
        if ($this->matches_played == 0) {
            return 0;
        }
        
        return round($this->points_against / $this->matches_played, 2);
    }
    
    /**
     * Calculate point differential
     */
    public function getPointDifferential()
    {
        return $this->points_for - $this->points_against;
    }
    
    /**
     * Update statistics after a match
     */
    public function updateMatchStatistics($pointsFor, $pointsAgainst, $won)
    {
        return $this->update([
            'matches_played' => $this->matches_played + 1,
            'matches_won' => $this->matches_won + ($won ? 1 : 0),
            'matches_lost' => $this->matches_lost + ($won ? 0 : 1),
            'points_for' => $this->points_for + $pointsFor,
            'points_against' => $this->points_against + $pointsAgainst
        ]);
    }
    
    /**
     * Update ELO rating after a match
     */
    public function updateEloRating($opponentElo, $actualScore, $kFactor = 32)
    {
        // Calculate expected score using ELO formula
        $expectedScore = 1 / (1 + pow(10, ($opponentElo - $this->elo_rating) / 400));
        
        // Update ELO rating
        $newElo = $this->elo_rating + $kFactor * ($actualScore - $expectedScore);
        
        return $this->update([
            'elo_rating' => round($newElo)
        ]);
    }
    
    /**
     * Get ELO rating classification
     */
    public function getEloClassification()
    {
        if ($this->elo_rating >= 2000) return 'Expert';
        if ($this->elo_rating >= 1800) return 'Advanced';
        if ($this->elo_rating >= 1600) return 'Intermediate';
        if ($this->elo_rating >= 1400) return 'Developing';
        return 'Beginner';
    }
    
    /**
     * Compare seeding with another team
     */
    public function compareWith($otherSeeding)
    {
        if ($this->seeding_score != $otherSeeding['seeding_score']) {
            return $this->seeding_score > $otherSeeding['seeding_score'] ? 1 : -1;
        }
        
        // Tiebreaker 1: Point differential
        $myDiff = $this->getPointDifferential();
        $otherDiff = $otherSeeding['points_for'] - $otherSeeding['points_against'];
        
        if ($myDiff != $otherDiff) {
            return $myDiff > $otherDiff ? 1 : -1;
        }
        
        // Tiebreaker 2: Points for
        if ($this->points_for != $otherSeeding['points_for']) {
            return $this->points_for > $otherSeeding['points_for'] ? 1 : -1;
        }
        
        // Tiebreaker 3: ELO rating
        return $this->elo_rating > $otherSeeding['elo_rating'] ? 1 : -1;
    }
    
    /**
     * Get seeding by tournament
     */
    public static function getByTournament($tournamentId)
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT ts.*, 
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district,
                   c.name as category_name
            FROM tournament_seedings ts
            JOIN teams t ON ts.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE ts.tournament_id = ?
            ORDER BY ts.seed_number ASC
        ", [$tournamentId]);
    }
    
    /**
     * Get seeding statistics for tournament
     */
    public static function getTournamentSeedingStats($tournamentId)
    {
        $db = Database::getInstance();
        $stats = $db->query("
            SELECT 
                COUNT(*) as total_teams,
                AVG(seeding_score) as avg_seeding_score,
                MIN(seeding_score) as min_seeding_score,
                MAX(seeding_score) as max_seeding_score,
                AVG(elo_rating) as avg_elo_rating,
                MIN(elo_rating) as min_elo_rating,
                MAX(elo_rating) as max_elo_rating
            FROM tournament_seedings
            WHERE tournament_id = ?
        ", [$tournamentId])[0];
        
        return [
            'total_teams' => $stats['total_teams'],
            'seeding_score' => [
                'average' => round($stats['avg_seeding_score'], 2),
                'min' => $stats['min_seeding_score'],
                'max' => $stats['max_seeding_score']
            ],
            'elo_rating' => [
                'average' => round($stats['avg_elo_rating']),
                'min' => $stats['min_elo_rating'],
                'max' => $stats['max_elo_rating']
            ]
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        $attributes['win_percentage'] = $this->getWinPercentage();
        $attributes['average_points_for'] = $this->getAveragePointsFor();
        $attributes['average_points_against'] = $this->getAveragePointsAgainst();
        $attributes['point_differential'] = $this->getPointDifferential();
        $attributes['elo_classification'] = $this->getEloClassification();
        $attributes['seeding_details'] = $this->getSeedingDetails();
        
        return $attributes;
    }
}