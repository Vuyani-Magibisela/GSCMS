<?php

namespace App\Models;

class TournamentBracket extends BaseModel
{
    protected $table = 'tournament_brackets';
    protected $softDeletes = false;
    
    protected $fillable = [
        'tournament_id', 'bracket_type', 'round_number', 'round_name',
        'matches_in_round', 'start_datetime', 'end_datetime', 'status'
    ];
    
    protected $guarded = ['id', 'created_at'];
    
    // Bracket type constants
    const TYPE_WINNERS = 'winners';
    const TYPE_LOSERS = 'losers';
    const TYPE_CONSOLATION = 'consolation';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    
    protected $belongsTo = [
        'tournament' => ['model' => Tournament::class, 'foreign_key' => 'tournament_id']
    ];

    protected $hasMany = [
        'matches' => ['model' => TournamentMatch::class, 'foreign_key' => 'bracket_id']
    ];
    
    /**
     * Get matches in this bracket
     */
    public function getMatches()
    {
        return $this->db->query("
            SELECT tm.*, 
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   w.name as winner_name
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            WHERE tm.bracket_id = ?
            ORDER BY tm.match_number
        ", [$this->id]);
    }
    
    /**
     * Check if bracket is completed
     */
    public function isCompleted()
    {
        $incompleteMatches = $this->db->table('tournament_matches')
            ->where('bracket_id', $this->id)
            ->whereNotIn('match_status', ['completed', 'bye'])
            ->count();
            
        return $incompleteMatches === 0;
    }
    
    /**
     * Get bracket progress
     */
    public function getProgress()
    {
        $totalMatches = $this->matches_in_round;
        $completedMatches = $this->db->table('tournament_matches')
            ->where('bracket_id', $this->id)
            ->whereIn('match_status', ['completed', 'bye'])
            ->count();
            
        return [
            'total' => $totalMatches,
            'completed' => $completedMatches,
            'percentage' => $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 2) : 0
        ];
    }
    
    /**
     * Get available bracket types
     */
    public static function getAvailableBracketTypes()
    {
        return [
            self::TYPE_WINNERS => 'Winners Bracket',
            self::TYPE_LOSERS => 'Losers Bracket',
            self::TYPE_CONSOLATION => 'Consolation Bracket'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        $attributes['is_completed'] = $this->isCompleted();
        $attributes['progress'] = $this->getProgress();
        $attributes['bracket_type_label'] = self::getAvailableBracketTypes()[$this->bracket_type] ?? $this->bracket_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}