<?php
// app/Models/Competition.php

namespace App\Models;

class Competition extends BaseModel
{
    protected $table = 'competitions';
    
    protected $fillable = [
        'name', 'year', 'phase_id', 'category_id', 'venue_name', 'venue_address', 
        'venue_capacity', 'date', 'start_time', 'end_time', 'registration_deadline',
        'max_participants', 'current_participants', 'status', 'entry_requirements',
        'competition_rules', 'prizes', 'contact_person', 'contact_email', 'contact_phone'
    ];

    protected $belongsTo = [
        'phase' => ['model' => Phase::class, 'foreign_key' => 'phase_id'],
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id']
    ];

    protected $hasMany = [
        'teams' => ['model' => Team::class, 'foreign_key' => 'competition_id']
    ];

    /**
     * Get competition overview data (replacement for competition_overview view)
     * 
     * @param int|null $competitionId Specific competition ID or null for all
     * @return array
     */
    public function getCompetitionOverview($competitionId = null)
    {
        $query = "
            SELECT 
                comp.id,
                comp.name as competition_name,
                comp.year,
                p.name as phase_name,
                cat.name as category_name,
                comp.venue_name,
                comp.date,
                comp.status,
                comp.current_participants,
                comp.max_participants,
                COUNT(t.id) as registered_teams
            FROM competitions comp
            JOIN phases p ON comp.phase_id = p.id
            JOIN categories cat ON comp.category_id = cat.id
            LEFT JOIN teams t ON comp.id = t.competition_id
        ";

        $params = [];
        if ($competitionId) {
            $query .= " WHERE comp.id = ?";
            $params[] = $competitionId;
        }

        $query .= " GROUP BY comp.id, comp.name, comp.year, p.name, cat.name, comp.venue_name, comp.date, comp.status, comp.current_participants, comp.max_participants";
        $query .= " ORDER BY comp.date DESC";

        $results = $this->db->query($query, $params);
        
        return $competitionId && !empty($results) ? $results[0] : $results;
    }

    /**
     * Get competitions by year
     */
    public function getByYear($year)
    {
        return $this->db->table($this->table)
            ->where('year', $year)
            ->orderBy('date', 'DESC')
            ->get();
    }

    /**
     * Get competitions by status
     */
    public function getByStatus($status)
    {
        return $this->db->table($this->table)
            ->where('status', $status)
            ->orderBy('date', 'ASC')
            ->get();
    }

    /**
     * Get upcoming competitions
     */
    public function getUpcoming($limit = null)
    {
        $query = $this->db->table($this->table)
            ->where('date', '>=', date('Y-m-d'))
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'ASC');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
}