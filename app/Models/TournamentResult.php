<?php

namespace App\Models;

class TournamentResult extends BaseModel
{
    protected $table = 'tournament_results';
    protected $softDeletes = false;
    
    protected $fillable = [
        'tournament_id', 'category_id', 'placement', 'team_id', 'team_score',
        'medal_type', 'prize_description', 'certificate_number',
        'published', 'published_at', 'verified_by', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at'];
    
    // Medal type constants
    const MEDAL_GOLD = 'gold';
    const MEDAL_SILVER = 'silver';
    const MEDAL_BRONZE = 'bronze';
    const MEDAL_NONE = 'none';
    
    protected $belongsTo = [
        'tournament' => ['model' => Tournament::class, 'foreign_key' => 'tournament_id'],
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id'],
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id'],
        'verifier' => ['model' => User::class, 'foreign_key' => 'verified_by']
    ];
    
    /**
     * Get result with detailed information
     */
    public function getResultDetails()
    {
        return $this->db->query("
            SELECT tr.*,
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district,
                   c.name as category_name, c.code as category_code,
                   tour.tournament_name,
                   u.first_name as verifier_first_name, u.last_name as verifier_last_name
            FROM tournament_results tr
            JOIN teams t ON tr.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON tr.category_id = c.id
            JOIN tournaments tour ON tr.tournament_id = tour.id
            LEFT JOIN users u ON tr.verified_by = u.id
            WHERE tr.id = ?
        ", [$this->id])[0] ?? null;
    }
    
    /**
     * Check if result is published
     */
    public function isPublished()
    {
        return $this->published && $this->published_at !== null;
    }
    
    /**
     * Check if result needs verification
     */
    public function needsVerification()
    {
        return $this->verified_by === null && $this->placement <= 3;
    }
    
    /**
     * Publish result
     */
    public function publishResult($publisherId)
    {
        return $this->update([
            'published' => true,
            'published_at' => date('Y-m-d H:i:s'),
            'verified_by' => $publisherId
        ]);
    }
    
    /**
     * Unpublish result
     */
    public function unpublishResult()
    {
        return $this->update([
            'published' => false,
            'published_at' => null
        ]);
    }
    
    /**
     * Generate certificate number
     */
    public function generateCertificateNumber()
    {
        if ($this->certificate_number) {
            return $this->certificate_number;
        }
        
        $tournament = $this->db->table('tournaments')->find($this->tournament_id);
        $category = $this->db->table('categories')->find($this->category_id);
        
        $certificateNumber = sprintf(
            'GDE2025-%s-%s-%03d',
            strtoupper($category['code'] ?? 'CAT'),
            str_pad($this->placement, 2, '0', STR_PAD_LEFT),
            $this->id
        );
        
        $this->update(['certificate_number' => $certificateNumber]);
        
        return $certificateNumber;
    }
    
    /**
     * Get medal emoji
     */
    public function getMedalEmoji()
    {
        switch ($this->medal_type) {
            case self::MEDAL_GOLD: return '🥇';
            case self::MEDAL_SILVER: return '🥈';
            case self::MEDAL_BRONZE: return '🥉';
            default: return '🏅';
        }
    }
    
    /**
     * Get placement suffix (1st, 2nd, 3rd, etc.)
     */
    public function getPlacementSuffix()
    {
        $placement = $this->placement;
        
        if ($placement % 100 >= 11 && $placement % 100 <= 13) {
            return $placement . 'th';
        }
        
        switch ($placement % 10) {
            case 1: return $placement . 'st';
            case 2: return $placement . 'nd';
            case 3: return $placement . 'rd';
            default: return $placement . 'th';
        }
    }
    
    /**
     * Check if placement qualifies for medal
     */
    public function qualifiesForMedal()
    {
        return $this->placement <= 3;
    }
    
    /**
     * Get results by tournament
     */
    public static function getByTournament($tournamentId, $categoryId = null)
    {
        $db = Database::getInstance();
        
        $query = "
            SELECT tr.*,
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district,
                   c.name as category_name, c.code as category_code
            FROM tournament_results tr
            JOIN teams t ON tr.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON tr.category_id = c.id
            WHERE tr.tournament_id = ?
        ";
        
        $params = [$tournamentId];
        
        if ($categoryId) {
            $query .= " AND tr.category_id = ?";
            $params[] = $categoryId;
        }
        
        $query .= " ORDER BY tr.category_id, tr.placement ASC";
        
        return $db->query($query, $params);
    }
    
    /**
     * Get podium results (top 3) by tournament
     */
    public static function getPodiumResults($tournamentId, $categoryId = null)
    {
        $db = Database::getInstance();
        
        $query = "
            SELECT tr.*,
                   t.name as team_name, t.team_code,
                   s.name as school_name, s.district,
                   c.name as category_name, c.code as category_code
            FROM tournament_results tr
            JOIN teams t ON tr.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON tr.category_id = c.id
            WHERE tr.tournament_id = ? AND tr.placement <= 3
        ";
        
        $params = [$tournamentId];
        
        if ($categoryId) {
            $query .= " AND tr.category_id = ?";
            $params[] = $categoryId;
        }
        
        $query .= " ORDER BY tr.category_id, tr.placement ASC";
        
        return $db->query($query, $params);
    }
    
    /**
     * Get tournament statistics
     */
    public static function getTournamentStats($tournamentId)
    {
        $db = Database::getInstance();
        
        return $db->query("
            SELECT 
                c.name as category_name,
                COUNT(tr.id) as total_results,
                COUNT(CASE WHEN tr.published = 1 THEN 1 END) as published_results,
                COUNT(CASE WHEN tr.verified_by IS NOT NULL THEN 1 END) as verified_results,
                AVG(tr.team_score) as average_score,
                MAX(tr.team_score) as highest_score,
                MIN(tr.team_score) as lowest_score
            FROM tournament_results tr
            JOIN categories c ON tr.category_id = c.id
            WHERE tr.tournament_id = ?
            GROUP BY tr.category_id, c.name
            ORDER BY c.name
        ", [$tournamentId]);
    }
    
    /**
     * Get available medal types
     */
    public static function getAvailableMedalTypes()
    {
        return [
            self::MEDAL_GOLD => 'Gold Medal',
            self::MEDAL_SILVER => 'Silver Medal',
            self::MEDAL_BRONZE => 'Bronze Medal',
            self::MEDAL_NONE => 'No Medal'
        ];
    }
    
    /**
     * Auto-assign medal based on placement
     */
    public function autoAssignMedal()
    {
        $medalType = match($this->placement) {
            1 => self::MEDAL_GOLD,
            2 => self::MEDAL_SILVER,
            3 => self::MEDAL_BRONZE,
            default => self::MEDAL_NONE
        };
        
        return $this->update(['medal_type' => $medalType]);
    }
    
    /**
     * Scope: Published results
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
    
    /**
     * Scope: Podium results (top 3)
     */
    public function scopePodium($query)
    {
        return $query->where('placement', '<=', 3);
    }
    
    /**
     * Scope: Results by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        $attributes['is_published'] = $this->isPublished();
        $attributes['needs_verification'] = $this->needsVerification();
        $attributes['qualifies_for_medal'] = $this->qualifiesForMedal();
        $attributes['medal_emoji'] = $this->getMedalEmoji();
        $attributes['placement_suffix'] = $this->getPlacementSuffix();
        $attributes['certificate_number'] = $this->certificate_number ?: $this->generateCertificateNumber();
        $attributes['medal_type_label'] = self::getAvailableMedalTypes()[$this->medal_type] ?? $this->medal_type;
        $attributes['result_details'] = $this->getResultDetails();
        
        return $attributes;
    }
}