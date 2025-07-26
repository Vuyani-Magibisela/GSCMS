<?php
// app/Models/Participant.php

namespace App\Models;

class Participant extends BaseModel
{
    protected $table = 'participants';
    
    protected $fillable = [
        'team_id', 'first_name', 'last_name', 'grade', 'gender', 
        'date_of_birth', 'consent_form_signed', 'emergency_contact_name',
        'emergency_contact_phone', 'medical_conditions', 'dietary_restrictions'
    ];

    protected $belongsTo = [
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id']
    ];

    /**
     * Get participant summary data (replacement for participant_summary view)
     * 
     * @param int|null $participantId Specific participant ID or null for all
     * @param int|null $teamId Filter by team ID
     * @return array
     */
    public function getParticipantSummary($participantId = null, $teamId = null)
    {
        $query = "
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.grade,
                p.gender,
                p.date_of_birth,
                YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d')) as age,
                t.name as team_name,
                t.team_code,
                s.name as school_name,
                c.name as category_name,
                p.consent_form_signed,
                p.created_at
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
        ";

        $params = [];
        $conditions = [];

        if ($participantId) {
            $conditions[] = "p.id = ?";
            $params[] = $participantId;
        }

        if ($teamId) {
            $conditions[] = "p.team_id = ?";
            $params[] = $teamId;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY p.last_name, p.first_name";

        $results = $this->db->query($query, $params);
        
        return $participantId && !empty($results) ? $results[0] : $results;
    }

    /**
     * Get participants by team
     */
    public function getByTeam($teamId)
    {
        return $this->db->table($this->table)
            ->where('team_id', $teamId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get participants by school (through team relationship)
     */
    public function getBySchool($schoolId)
    {
        $query = "
            SELECT p.*
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            WHERE t.school_id = ?
            ORDER BY p.last_name, p.first_name
        ";
        
        return $this->db->query($query, [$schoolId]);
    }

    /**
     * Get participants who haven't signed consent forms
     */
    public function getMissingConsent($teamId = null)
    {
        $query = $this->db->table($this->table)
            ->where('consent_form_signed', 0);
            
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * Calculate age from date of birth
     */
    public function calculateAge($dateOfBirth)
    {
        $today = new \DateTime();
        $birthDate = new \DateTime($dateOfBirth);
        return $today->diff($birthDate)->y;
    }

    /**
     * Get participants by age range
     */
    public function getByAgeRange($minAge, $maxAge)
    {
        $query = "
            SELECT *,
            YEAR(CURDATE()) - YEAR(date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(date_of_birth, '%m%d')) as age
            FROM participants
            HAVING age BETWEEN ? AND ?
            ORDER BY age, last_name, first_name
        ";
        
        return $this->db->query($query, [$minAge, $maxAge]);
    }
}