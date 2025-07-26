<?php
// app/Models/Team.php

namespace App\Models;

class Team extends BaseModel
{
    protected $table = 'teams';
    
    protected $fillable = [
        'school_id', 'competition_id', 'category_id', 'name', 'team_code',
        'coach1_id', 'coach2_id', 'team_size', 'status', 'current_phase',
        'qualification_score', 'special_requirements', 'notes'
    ];

    protected $belongsTo = [
        'school' => ['model' => School::class, 'foreign_key' => 'school_id'],
        'competition' => ['model' => Competition::class, 'foreign_key' => 'competition_id'],
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id'],
        'coach1' => ['model' => User::class, 'foreign_key' => 'coach1_id'],
        'coach2' => ['model' => User::class, 'foreign_key' => 'coach2_id']
    ];

    protected $hasMany = [
        'participants' => ['model' => Participant::class, 'foreign_key' => 'team_id']
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
                SUM(t.team_size) as total_participants
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            GROUP BY s.id, c.id
            ORDER BY s.name, c.name
        ";

        return $this->db->query($query);
    }
}