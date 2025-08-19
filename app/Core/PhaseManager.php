<?php

namespace App\Core;

use App\Models\Competition;
use App\Models\Phase;
use App\Models\Category;
use App\Models\Team;
use App\Models\PhaseProgression;

class PhaseManager
{
    private $db;
    private $competitionId;
    private $competitionType;
    
    public function __construct($competitionId = null, $competitionType = 'pilot')
    {
        $this->db = new Database();
        $this->competitionId = $competitionId;
        $this->competitionType = $competitionType;
    }
    
    /**
     * Get active phases for specific competition
     */
    public function getActivePhases($competitionId = null)
    {
        $compId = $competitionId ?: $this->competitionId;
        
        if ($this->competitionType === 'pilot') {
            // For pilot: return phases 1 and 3 (skip phase 2)
            return $this->db->query("
                SELECT * FROM phases 
                WHERE id IN (1, 3) 
                AND deleted_at IS NULL 
                ORDER BY order_sequence
            ");
        }
        
        // For full system: return all phases
        return $this->db->query("
            SELECT * FROM phases 
            WHERE deleted_at IS NULL 
            ORDER BY order_sequence
        ");
    }
    
    /**
     * Get phase capacity for specific phase/category combination
     */
    public function getPhaseCapacity($phaseId, $categoryId = null)
    {
        $phase = $this->db->table('phases')->where('id', $phaseId)->first();
        
        if (!$phase) {
            return 0;
        }
        
        if ($this->competitionType === 'pilot') {
            switch ($phase['order_sequence']) {
                case 1: // Phase 1 - School elimination
                    return 30; // 30 teams per category
                case 3: // Phase 3 - Finals at Sci-Bono
                    return 6;  // 6 teams per category
                default:
                    return 0;
            }
        }
        
        // Full system capacities
        switch ($phase['order_sequence']) {
            case 1: // Phase 1 - School-based
                return null; // Unlimited
            case 2: // Phase 2 - District/Semifinals
                return 15;   // 15 teams per category
            case 3: // Phase 3 - Provincial Finals
                return 6;    // 6 teams per category
            default:
                return 0;
        }
    }
    
    /**
     * Get current active phase based on dates
     */
    public function getCurrentActivePhase()
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->db->query("
            SELECT * FROM phases 
            WHERE competition_start <= ? 
            AND competition_end >= ?
            AND status = 'active'
            AND deleted_at IS NULL
            ORDER BY order_sequence
            LIMIT 1
        ", [$now, $now])[0] ?? null;
    }
    
    /**
     * Get next phase in sequence
     */
    public function getNextPhase($currentPhaseId)
    {
        $currentPhase = $this->db->table('phases')->where('id', $currentPhaseId)->first();
        
        if (!$currentPhase) {
            return null;
        }
        
        if ($this->competitionType === 'pilot') {
            // Pilot progression: 1 -> 3 (skip 2)
            if ($currentPhase['order_sequence'] == 1) {
                return $this->db->table('phases')
                    ->where('order_sequence', 3)
                    ->whereNull('deleted_at')
                    ->first();
            }
        } else {
            // Full system progression: 1 -> 2 -> 3
            return $this->db->table('phases')
                ->where('order_sequence', '>', $currentPhase['order_sequence'])
                ->whereNull('deleted_at')
                ->orderBy('order_sequence')
                ->first();
        }
        
        return null;
    }
    
    /**
     * Advance teams to next phase with scoring and ranking
     */
    public function advanceTeamsToNextPhase($currentPhaseId, $teams = null)
    {
        $nextPhase = $this->getNextPhase($currentPhaseId);
        
        if (!$nextPhase) {
            return ['success' => false, 'message' => 'No next phase available'];
        }
        
        $nextPhaseCapacity = $this->getPhaseCapacity($nextPhase['id']);
        
        if ($teams === null) {
            $teams = $this->getEligibleTeamsForAdvancement($currentPhaseId);
        }
        
        $advancedTeams = [];
        $categories = [];
        
        // Group teams by category
        foreach ($teams as $team) {
            $categories[$team['category_id']][] = $team;
        }
        
        foreach ($categories as $categoryId => $categoryTeams) {
            // Sort teams by score (highest first)
            usort($categoryTeams, function($a, $b) {
                return ($b['total_score'] ?? 0) <=> ($a['total_score'] ?? 0);
            });
            
            // Take top teams based on capacity
            $teamsToAdvance = array_slice($categoryTeams, 0, $nextPhaseCapacity);
            
            foreach ($teamsToAdvance as $index => $team) {
                $rank = $index + 1;
                
                // Create new team entry for next phase
                $newTeamData = [
                    'school_id' => $team['school_id'],
                    'category_id' => $team['category_id'],
                    'phase_id' => $nextPhase['id'],
                    'name' => $team['name'],
                    'team_code' => $team['team_code'] . '-P' . $nextPhase['order_sequence'],
                    'coach1_id' => $team['coach1_id'],
                    'coach2_id' => $team['coach2_id'],
                    'status' => 'approved',
                    'qualification_score' => $team['total_score'] ?? 0,
                    'notes' => "Advanced from Phase {$currentPhaseId} with rank {$rank}"
                ];
                
                $newTeamId = $this->db->table('teams')->insertGetId($newTeamData);
                
                // Copy participants to new team
                $this->copyParticipantsToNewTeam($team['id'], $newTeamId);
                
                // Record progression
                $progressionModel = new PhaseProgression();
                $progressionModel->recordPilotProgression(
                    $newTeamId,
                    $currentPhaseId,
                    $nextPhase['id'],
                    $team['total_score'] ?? 0,
                    $rank,
                    "Qualified as rank {$rank} in category"
                );
                
                $advancedTeams[] = [
                    'original_team_id' => $team['id'],
                    'new_team_id' => $newTeamId,
                    'category_id' => $categoryId,
                    'rank' => $rank,
                    'score' => $team['total_score'] ?? 0
                ];
            }
        }
        
        return [
            'success' => true,
            'advanced_teams' => $advancedTeams,
            'next_phase' => $nextPhase,
            'total_advanced' => count($advancedTeams)
        ];
    }
    
    /**
     * Get eligible teams for advancement from a phase
     */
    private function getEligibleTeamsForAdvancement($phaseId)
    {
        return $this->db->query("
            SELECT 
                t.*,
                c.name as category_name,
                s.name as school_name,
                COALESCE(AVG(sc.total_score), 0) as total_score
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = ?
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.id, total_score DESC
        ", [$phaseId]);
    }
    
    /**
     * Copy participants from one team to another
     */
    private function copyParticipantsToNewTeam($sourceTeamId, $targetTeamId)
    {
        $participants = $this->db->table('participants')
            ->where('team_id', $sourceTeamId)
            ->whereNull('deleted_at')
            ->get();
            
        foreach ($participants as $participant) {
            $newParticipantData = $participant;
            unset($newParticipantData['id'], $newParticipantData['created_at'], $newParticipantData['updated_at']);
            $newParticipantData['team_id'] = $targetTeamId;
            
            $this->db->table('participants')->insert($newParticipantData);
        }
    }
    
    /**
     * Get phase progression statistics
     */
    public function getPhaseStatistics($phaseId = null)
    {
        if ($phaseId) {
            return $this->getSpecificPhaseStatistics($phaseId);
        }
        
        return $this->getAllPhasesStatistics();
    }
    
    /**
     * Get statistics for a specific phase
     */
    private function getSpecificPhaseStatistics($phaseId)
    {
        $phase = $this->db->table('phases')->where('id', $phaseId)->first();
        
        if (!$phase) {
            return null;
        }
        
        $teamCount = $this->db->table('teams')
            ->where('phase_id', $phaseId)
            ->whereNull('deleted_at')
            ->count();
            
        $participantCount = $this->db->query("
            SELECT COUNT(p.id) as count
            FROM participants p
            JOIN teams t ON p.team_id = t.id
            WHERE t.phase_id = ?
            AND p.deleted_at IS NULL
            AND t.deleted_at IS NULL
        ", [$phaseId])[0]['count'] ?? 0;
        
        $schoolCount = $this->db->query("
            SELECT COUNT(DISTINCT t.school_id) as count
            FROM teams t
            WHERE t.phase_id = ?
            AND t.deleted_at IS NULL
        ", [$phaseId])[0]['count'] ?? 0;
        
        $categoryStats = $this->db->query("
            SELECT 
                c.name as category_name,
                COUNT(t.id) as team_count,
                COUNT(p.id) as participant_count
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id AND t.phase_id = ? AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY c.name
        ", [$phaseId]);
        
        return [
            'phase' => $phase,
            'team_count' => $teamCount,
            'participant_count' => $participantCount,
            'school_count' => $schoolCount,
            'category_statistics' => $categoryStats
        ];
    }
    
    /**
     * Get statistics for all phases
     */
    private function getAllPhasesStatistics()
    {
        $phases = $this->getActivePhases();
        $statistics = [];
        
        foreach ($phases as $phase) {
            $statistics[] = $this->getSpecificPhaseStatistics($phase['id']);
        }
        
        return $statistics;
    }
    
    /**
     * Check if phase progression is allowed
     */
    public function isProgressionAllowed($fromPhaseId, $toPhaseId)
    {
        $fromPhase = $this->db->table('phases')->where('id', $fromPhaseId)->first();
        $toPhase = $this->db->table('phases')->where('id', $toPhaseId)->first();
        
        if (!$fromPhase || !$toPhase) {
            return false;
        }
        
        if ($this->competitionType === 'pilot') {
            // Pilot allows 1->3 progression
            return ($fromPhase['order_sequence'] == 1 && $toPhase['order_sequence'] == 3) ||
                   ($fromPhase['order_sequence'] < $toPhase['order_sequence']);
        }
        
        // Full system requires sequential progression
        return $toPhase['order_sequence'] == ($fromPhase['order_sequence'] + 1);
    }
    
    /**
     * Get competition timeline for pilot programme
     */
    public function getPilotTimeline()
    {
        return [
            'registration_open' => '2025-08-01',
            'registration_close' => '2025-09-05',
            'phase_1_competitions' => '2025-09-12',
            'phase_1_results_submission' => '2025-09-15',
            'qualification_announcement' => '2025-09-18',
            'phase_3_finals' => '2025-09-27',
            'awards_ceremony' => '2025-09-27',
            'post_competition_analysis' => '2025-10-15'
        ];
    }
    
    /**
     * Set competition type
     */
    public function setCompetitionType($type)
    {
        $this->competitionType = $type;
        return $this;
    }
    
    /**
     * Get competition type
     */
    public function getCompetitionType()
    {
        return $this->competitionType;
    }
}