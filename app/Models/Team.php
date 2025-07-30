<?php
// app/Models/Team.php

namespace App\Models;

class Team extends BaseModel
{
    protected $table = 'teams';
    protected $softDeletes = true;
    
    protected $fillable = [
        'school_id', 'category_id', 'phase_id', 'name', 'team_code',
        'coach1_id', 'coach2_id', 'status', 'qualification_score', 
        'special_requirements', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'school_id' => 'required',
        'category_id' => 'required',
        'phase_id' => 'required',
        'name' => 'required|max:255',
        'team_code' => 'required|unique|max:20',
        'status' => 'required'
    ];
    
    protected $messages = [
        'school_id.required' => 'School is required.',
        'category_id.required' => 'Category is required.',
        'phase_id.required' => 'Phase is required.',
        'name.required' => 'Team name is required.',
        'team_code.required' => 'Team code is required.',
        'team_code.unique' => 'Team code must be unique.',
        'status.required' => 'Team status is required.'
    ];

    // Status constants
    const STATUS_REGISTERED = 'registered';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPETING = 'competing';
    const STATUS_ELIMINATED = 'eliminated';
    const STATUS_COMPLETED = 'completed';
    
    // Team composition limits
    const MAX_PARTICIPANTS = 6;
    const MAX_COACHES = 2;

    protected $belongsTo = [
        'school' => ['model' => School::class, 'foreign_key' => 'school_id'],
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id'],
        'phase' => ['model' => Phase::class, 'foreign_key' => 'phase_id'],
        'coach1' => ['model' => User::class, 'foreign_key' => 'coach1_id'],
        'coach2' => ['model' => User::class, 'foreign_key' => 'coach2_id']
    ];

    protected $hasMany = [
        'participants' => ['model' => Participant::class, 'foreign_key' => 'team_id'],
        'scores' => ['model' => Score::class, 'foreign_key' => 'team_id']
    ];

    /**
     * Get team summary data (replacement for team_summary view)
     * 
     * @param int|null $teamId Specific team ID or null for all
     * @param int|null $schoolId Filter by school ID
     * @param int|null $categoryId Filter by category ID
     * @return array
     */
    public function getTeamSummary($teamId = null, $schoolId = null, $categoryId = null)
    {
        $query = "
            SELECT 
                t.id,
                t.name as team_name,
                t.team_code,
                s.name as school_name,
                s.district,
                c.name as category_name,
                c.code as category_code,
                t.team_size,
                t.status,
                t.current_phase,
                t.qualification_score,
                CONCAT(u1.first_name, ' ', u1.last_name) as coach1_name,
                CONCAT(u2.first_name, ' ', u2.last_name) as coach2_name,
                t.created_at
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u1 ON t.coach1_id = u1.id
            LEFT JOIN users u2 ON t.coach2_id = u2.id
        ";

        $params = [];
        $conditions = [];

        if ($teamId) {
            $conditions[] = "t.id = ?";
            $params[] = $teamId;
        }

        if ($schoolId) {
            $conditions[] = "t.school_id = ?";
            $params[] = $schoolId;
        }

        if ($categoryId) {
            $conditions[] = "t.category_id = ?";
            $params[] = $categoryId;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY s.name, t.name";

        $results = $this->db->query($query, $params);
        
        return $teamId && !empty($results) ? $results[0] : $results;
    }

    /**
     * Get teams by school
     */
    public function getBySchool($schoolId)
    {
        return $this->db->table($this->table)
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get teams by category
     */
    public function getByCategory($categoryId)
    {
        return $this->db->table($this->table)
            ->where('category_id', $categoryId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get teams by status
     */
    public function getByStatus($status)
    {
        return $this->db->table($this->table)
            ->where('status', $status)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get teams by competition
     */
    public function getByCompetition($competitionId)
    {
        return $this->db->table($this->table)
            ->where('competition_id', $competitionId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get teams with participant count
     */
    public function getTeamsWithParticipantCount($schoolId = null)
    {
        $query = "
            SELECT 
                t.*,
                COUNT(p.id) as participant_count,
                s.name as school_name,
                c.name as category_name
            FROM teams t
            LEFT JOIN participants p ON t.id = p.team_id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
        ";

        $params = [];
        if ($schoolId) {
            $query .= " WHERE t.school_id = ?";
            $params[] = $schoolId;
        }

        $query .= " GROUP BY t.id ORDER BY s.name, t.name";

        return $this->db->query($query, $params);
    }

    /**
     * Generate unique team code
     */
    public function generateTeamCode($schoolId, $categoryId)
    {
        // Get school and category codes
        $school = $this->db->table('schools')->find($schoolId);
        $category = $this->db->table('categories')->find($categoryId);
        
        if (!$school || !$category) {
            throw new \Exception('Invalid school or category');
        }

        // Create base code from school and category
        $baseCode = strtoupper(substr($school['name'], 0, 3) . $category['code']);
        
        // Find next available number
        $counter = 1;
        do {
            $teamCode = $baseCode . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $existing = $this->db->table($this->table)
                ->where('team_code', $teamCode)
                ->first();
            $counter++;
        } while ($existing && $counter < 100);
        
        if ($counter >= 100) {
            throw new \Exception('Unable to generate unique team code');
        }
        
        return $teamCode;
    }

    /**
     * Get team registration statistics
     */
    public function getRegistrationStats()
    {
        $query = "
            SELECT 
                s.name as school_name,
                c.name as category_name,
                COUNT(t.id) as team_count,
                COUNT(p.id) as total_participants
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN participants p ON t.id = p.team_id
            WHERE t.deleted_at IS NULL
            GROUP BY s.id, c.id
            ORDER BY s.name, c.name
        ";

        return $this->db->query($query);
    }
    
    /**
     * Validate team composition (max 6 participants, 2 coaches)
     */
    public function validateTeamComposition()
    {
        $errors = [];
        
        // Check participant count
        $participantCount = $this->getParticipantCount();
        if ($participantCount > self::MAX_PARTICIPANTS) {
            $errors[] = "Team cannot have more than " . self::MAX_PARTICIPANTS . " participants.";
        }
        
        // Check coach count
        $coachCount = 0;
        if ($this->coach1_id) $coachCount++;
        if ($this->coach2_id) $coachCount++;
        
        if ($coachCount > self::MAX_COACHES) {
            $errors[] = "Team cannot have more than " . self::MAX_COACHES . " coaches.";
        }
        
        if ($coachCount === 0) {
            $errors[] = "Team must have at least one coach.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get participant count
     */
    public function getParticipantCount()
    {
        return $this->db->table('participants')
            ->where('team_id', $this->id)
            ->whereNull('deleted_at')
            ->count();
    }
    
    /**
     * Check if team can add more participants
     */
    public function canAddParticipant()
    {
        return $this->getParticipantCount() < self::MAX_PARTICIPANTS;
    }
    
    /**
     * Validate category eligibility (1 team per school per category)
     */
    public function validateCategoryEligibility()
    {
        $existingTeam = $this->db->table('teams')
            ->where('school_id', $this->school_id)
            ->where('category_id', $this->category_id)
            ->where('id', '!=', $this->id ?? 0)
            ->whereNull('deleted_at')
            ->first();
            
        if ($existingTeam) {
            return [
                'valid' => false,
                'message' => 'School already has a team registered for this category.'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get team performance data
     */
    public function getPerformanceData()
    {
        $scores = $this->db->table('scores')
            ->where('team_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
            
        $totalScore = array_sum(array_column($scores, 'total_score'));
        $averageScore = count($scores) > 0 ? $totalScore / count($scores) : 0;
        
        return [
            'scores' => $scores,
            'total_score' => $totalScore,
            'average_score' => $averageScore,
            'ranking' => $this->getTeamRanking()
        ];
    }
    
    /**
     * Get team ranking within category/phase
     */
    public function getTeamRanking()
    {
        $query = "
            SELECT COUNT(*) + 1 as ranking
            FROM teams t
            LEFT JOIN (
                SELECT team_id, SUM(total_score) as team_total
                FROM scores
                GROUP BY team_id
            ) s ON t.id = s.team_id
            WHERE t.category_id = ? 
            AND t.phase_id = ?
            AND t.deleted_at IS NULL
            AND (s.team_total > ? OR (s.team_total IS NULL AND ? > 0))
        ";
        
        $myTotal = $this->db->table('scores')
            ->where('team_id', $this->id)
            ->sum('total_score') ?? 0;
            
        $result = $this->db->query($query, [
            $this->category_id, 
            $this->phase_id, 
            $myTotal, 
            $myTotal
        ]);
        
        return $result[0]['ranking'] ?? null;
    }
    
    /**
     * Check advancement eligibility
     */
    public function checkAdvancementEligibility()
    {
        // Check if all participants have approved consent forms
        $participants = $this->db->table('participants')
            ->where('team_id', $this->id)
            ->whereNull('deleted_at')
            ->get();
            
        $missingConsent = [];
        foreach ($participants as $participant) {
            $hasConsent = $this->db->table('consent_forms')
                ->where('participant_id', $participant['id'])
                ->where('status', 'approved')
                ->exists();
                
            if (!$hasConsent) {
                $missingConsent[] = $participant['first_name'] . ' ' . $participant['last_name'];
            }
        }
        
        $requirements = [];
        if (!empty($missingConsent)) {
            $requirements[] = 'Missing consent forms: ' . implode(', ', $missingConsent);
        }
        
        // Check minimum participants
        if (count($participants) < 2) {
            $requirements[] = 'Team must have at least 2 participants.';
        }
        
        // Check qualification score if required
        $phase = $this->db->table('phases')->find($this->phase_id);
        if ($phase && $phase['requires_qualification'] && !$this->qualification_score) {
            $requirements[] = 'Team must have a qualification score.';
        }
        
        return [
            'eligible' => empty($requirements),
            'requirements' => $requirements
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_REGISTERED => 'Registered',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_COMPETING => 'Competing',
            self::STATUS_ELIMINATED => 'Eliminated',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }
    
    /**
     * Scope: Teams by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope: Teams by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    /**
     * Scope: Teams by phase
     */
    public function scopeByPhase($query, $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }
    
    /**
     * Scope: Teams by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['participant_count'] = $this->getParticipantCount();
        $attributes['can_add_participant'] = $this->canAddParticipant();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['composition_valid'] = $this->validateTeamComposition()['valid'];
        
        return $attributes;
    }
}