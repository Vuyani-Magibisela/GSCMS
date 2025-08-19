<?php

namespace App\Core;

use App\Models\Team;
use App\Models\Category;
use App\Models\PhaseProgression;

class PilotPhaseProgression
{
    private $db;
    
    public function __construct()
    {
        $this->db = new Database();
    }
    
    /**
     * Get active categories for pilot programme (9 categories)
     */
    public function getActiveCategories()
    {
        return $this->db->query("
            SELECT * FROM categories 
            WHERE status = 'active' 
            AND deleted_at IS NULL 
            ORDER BY name
        ");
    }
    
    /**
     * Advance teams from Phase 1 to Phase 3 (skip Phase 2 for pilot)
     */
    public function advanceFromPhase1ToPhase3($teams = null)
    {
        if ($teams === null) {
            $teams = $this->getPhase1Teams();
        }
        
        $advancingTeams = [];
        $categories = $this->getActiveCategories();
        
        foreach ($categories as $category) {
            // Get top 6 teams per category from Phase 1
            $categoryTeams = $this->getTopTeamsByCategory($category['id'], 1, 6);
            
            foreach ($categoryTeams as $index => $team) {
                $rank = $index + 1;
                
                // Create new team entry for Phase 3
                $newTeamData = [
                    'school_id' => $team['school_id'],
                    'category_id' => $team['category_id'],
                    'phase_id' => 3, // Phase 3 (Finals)
                    'name' => $team['name'],
                    'team_code' => $team['team_code'] . '-FINAL',
                    'coach1_id' => $team['coach1_id'],
                    'coach2_id' => $team['coach2_id'],
                    'status' => 'approved',
                    'qualification_score' => $team['total_score'] ?? 0,
                    'notes' => "Qualified for SciBOTICS Final @ Sci-Bono - Rank {$rank} in {$category['name']}"
                ];
                
                $newTeamId = $this->db->table('teams')->insertGetId($newTeamData);
                
                // Copy participants to new team
                $this->copyParticipantsToPhase3Team($team['id'], $newTeamId);
                
                // Record progression (Phase 1 -> Phase 3)
                $progressionData = [
                    'team_id' => $newTeamId,
                    'from_phase_id' => 1,
                    'to_phase_id' => 3,
                    'progression_date' => date('Y-m-d H:i:s'),
                    'score' => $team['total_score'] ?? 0,
                    'rank_in_category' => $rank,
                    'qualified' => true,
                    'advancement_reason' => "Qualified as rank {$rank} in {$category['name']} for SciBOTICS Final",
                    'competition_type' => 'pilot',
                    'notes' => 'Advanced directly from Phase 1 to Phase 3 (Phase 2 skipped for pilot programme)'
                ];
                
                $this->db->table('phase_progressions')->insert($progressionData);
                
                $advancingTeams[] = [
                    'original_team_id' => $team['id'],
                    'final_team_id' => $newTeamId,
                    'category' => $category['name'],
                    'category_id' => $category['id'],
                    'rank' => $rank,
                    'score' => $team['total_score'] ?? 0,
                    'school_name' => $team['school_name']
                ];
            }
        }
        
        return [
            'success' => true,
            'advancing_teams' => $advancingTeams,
            'total_teams' => count($advancingTeams),
            'categories_processed' => count($categories),
            'expected_participants' => count($advancingTeams) * 4, // 4 members per team
            'venue' => 'Sci-Bono Discovery Centre',
            'date' => '2025-09-27'
        ];
    }
    
    /**
     * Get Phase 1 teams with scores
     */
    private function getPhase1Teams()
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
            WHERE t.phase_id = 1
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.id, total_score DESC
        ");
    }
    
    /**
     * Get top teams by category from specific phase
     */
    private function getTopTeamsByCategory($categoryId, $phaseId, $limit = 6)
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
            WHERE t.category_id = ?
            AND t.phase_id = ?
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY total_score DESC
            LIMIT ?
        ", [$categoryId, $phaseId, $limit]);
    }
    
    /**
     * Copy participants from Phase 1 team to Phase 3 team
     */
    private function copyParticipantsToPhase3Team($sourceTeamId, $targetTeamId)
    {
        $participants = $this->db->table('participants')
            ->where('team_id', $sourceTeamId)
            ->whereNull('deleted_at')
            ->get();
            
        foreach ($participants as $participant) {
            $newParticipantData = $participant;
            unset($newParticipantData['id'], $newParticipantData['created_at'], $newParticipantData['updated_at']);
            $newParticipantData['team_id'] = $targetTeamId;
            $newParticipantData['notes'] = ($participant['notes'] ?? '') . ' | Advanced to SciBOTICS Final';
            
            $this->db->table('participants')->insert($newParticipantData);
        }
    }
    
    /**
     * Calculate final results and determine winners for each category
     */
    public function calculateFinalResults($phase3Teams = null)
    {
        if ($phase3Teams === null) {
            $phase3Teams = $this->getPhase3Teams();
        }
        
        $winners = [];
        $categories = $this->getActiveCategories();
        
        foreach ($categories as $category) {
            // Get teams for this category in Phase 3, ordered by score
            $categoryTeams = array_filter($phase3Teams, function($team) use ($category) {
                return $team['category_id'] == $category['id'];
            });
            
            // Sort by score (descending)
            usort($categoryTeams, function($a, $b) {
                return ($b['total_score'] ?? 0) <=> ($a['total_score'] ?? 0);
            });
            
            $categoryWinners = array_slice($categoryTeams, 0, 3); // Top 3 teams
            
            $winners[$category['name']] = [
                'category_id' => $category['id'],
                'gold' => $categoryWinners[0] ?? null,
                'silver' => $categoryWinners[1] ?? null,
                'bronze' => $categoryWinners[2] ?? null,
                'total_participants' => count($categoryWinners) * 4,
                'trophy_winner' => $categoryWinners[0]['name'] ?? null,
                'medal_count' => min(count($categoryWinners), 3) * 4 // 4 medals per team
            ];
        }
        
        // Calculate total awards
        $totalMedals = 0;
        $totalTrophies = 0;
        
        foreach ($winners as $categoryName => $categoryResults) {
            $totalMedals += $categoryResults['medal_count'];
            if ($categoryResults['gold']) {
                $totalTrophies++;
            }
        }
        
        return [
            'winners' => $winners,
            'summary' => [
                'total_categories' => count($categories),
                'total_final_teams' => count($phase3Teams),
                'total_final_participants' => count($phase3Teams) * 4,
                'total_medals' => $totalMedals,
                'total_trophies' => $totalTrophies,
                'venue' => 'Sci-Bono Discovery Centre',
                'competition_date' => '2025-09-27',
                'awards_ceremony' => '2025-09-27'
            ]
        ];
    }
    
    /**
     * Get Phase 3 teams with scores
     */
    private function getPhase3Teams()
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
            WHERE t.phase_id = 3
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.id, total_score DESC
        ");
    }
    
    /**
     * Generate qualification announcement data
     */
    public function generateQualificationAnnouncement()
    {
        $qualifiedTeams = $this->db->query("
            SELECT 
                t.name as team_name,
                t.team_code,
                c.name as category_name,
                s.name as school_name,
                s.address as school_address,
                pp.score,
                pp.rank_in_category,
                COUNT(p.id) as participant_count
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            JOIN phase_progressions pp ON t.id = pp.team_id
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE t.phase_id = 3
            AND pp.to_phase_id = 3
            AND pp.competition_type = 'pilot'
            AND t.deleted_at IS NULL
            AND pp.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.name, pp.rank_in_category
        ");
        
        return [
            'qualified_teams' => $qualifiedTeams,
            'announcement_date' => '2025-09-18',
            'final_date' => '2025-09-27',
            'venue' => 'Sci-Bono Discovery Centre',
            'total_teams' => count($qualifiedTeams),
            'total_participants' => array_sum(array_column($qualifiedTeams, 'participant_count')),
            'categories_count' => count(array_unique(array_column($qualifiedTeams, 'category_name')))
        ];
    }
    
    /**
     * Get pilot programme statistics
     */
    public function getPilotStatistics()
    {
        $phase1Stats = $this->db->query("
            SELECT 
                c.name as category_name,
                COUNT(t.id) as registered_teams,
                COUNT(p.id) as total_participants,
                COUNT(DISTINCT t.school_id) as participating_schools
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id AND t.phase_id = 1 AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY c.name
        ");
        
        $phase3Stats = $this->db->query("
            SELECT 
                c.name as category_name,
                COUNT(t.id) as qualified_teams,
                COUNT(p.id) as finalist_participants
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id AND t.phase_id = 3 AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY c.name
        ");
        
        return [
            'phase_1_statistics' => $phase1Stats,
            'phase_3_statistics' => $phase3Stats,
            'pilot_timeline' => [
                'registration_period' => '2025-08-01 to 2025-09-05',
                'school_competitions' => '2025-09-12',
                'results_due' => '2025-09-15',
                'qualification_announcement' => '2025-09-18',
                'finals_at_sci_bono' => '2025-09-27'
            ],
            'competition_scope' => [
                'province' => 'Gauteng only',
                'phases' => '2 phases (Phase 2 skipped)',
                'categories' => '9 categories',
                'team_size' => '4 members per team',
                'finalist_capacity' => '6 teams per category (54 total teams, 216 participants)'
            ]
        ];
    }
    
    /**
     * Validate team eligibility for Phase 3 advancement
     */
    public function validatePhase3Eligibility($teamId)
    {
        $team = $this->db->query("
            SELECT t.*, c.name as category_name, s.name as school_name
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            WHERE t.id = ? AND t.deleted_at IS NULL
        ", [$teamId])[0] ?? null;
        
        if (!$team) {
            return ['eligible' => false, 'reason' => 'Team not found'];
        }
        
        if ($team['phase_id'] != 1) {
            return ['eligible' => false, 'reason' => 'Team is not in Phase 1'];
        }
        
        if ($team['status'] != 'approved') {
            return ['eligible' => false, 'reason' => 'Team status is not approved'];
        }
        
        // Check if already advanced
        $alreadyAdvanced = $this->db->table('phase_progressions')
            ->where('team_id', $teamId)
            ->where('to_phase_id', 3)
            ->whereNull('deleted_at')
            ->exists();
            
        if ($alreadyAdvanced) {
            return ['eligible' => false, 'reason' => 'Team has already advanced to Phase 3'];
        }
        
        return ['eligible' => true, 'team' => $team];
    }
}