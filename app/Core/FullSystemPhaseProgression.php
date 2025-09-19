<?php

namespace App\Core;

use App\Models\Team;
use App\Models\Category;
use App\Models\PhaseProgression;

class FullSystemPhaseProgression
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Advance teams from Phase 1 (School-based) to Phase 2 (District)
     */
    public function advanceFromPhase1ToPhase2($schoolResults = null)
    {
        if ($schoolResults === null) {
            $schoolResults = $this->getSchoolPhaseResults();
        }
        
        $advancingTeams = [];
        $categories = $this->getActiveCategories();
        
        foreach ($categories as $category) {
            // Apply district capacity limits (max 15 teams per category)
            $categoryTeams = $this->selectTeamsForDistrict($category['id'], $schoolResults, 15);
            
            foreach ($categoryTeams as $index => $team) {
                $rank = $index + 1;
                
                // Create new team entry for Phase 2
                $newTeamData = [
                    'school_id' => $team['school_id'],
                    'category_id' => $team['category_id'],
                    'phase_id' => 2, // Phase 2 (District)
                    'name' => $team['name'],
                    'team_code' => $team['team_code'] . '-DIST',
                    'coach1_id' => $team['coach1_id'],
                    'coach2_id' => $team['coach2_id'],
                    'status' => 'approved',
                    'qualification_score' => $team['school_score'] ?? 0,
                    'notes' => "Advanced from School Phase - Rank {$rank} in district for {$category['name']}"
                ];
                
                $newTeamId = $this->db->table('teams')->insertGetId($newTeamData);
                
                // Copy participants to new team
                $this->copyParticipantsToNextPhase($team['id'], $newTeamId);
                
                // Record progression
                $this->recordProgression(
                    $newTeamId,
                    1, // from Phase 1
                    2, // to Phase 2
                    $team['school_score'] ?? 0,
                    $rank,
                    "Qualified for district competition as rank {$rank}"
                );
                
                $advancingTeams[] = [
                    'original_team_id' => $team['id'],
                    'district_team_id' => $newTeamId,
                    'category' => $category['name'],
                    'district_rank' => $rank,
                    'school_score' => $team['school_score'] ?? 0
                ];
            }
        }
        
        return [
            'success' => true,
            'advancing_teams' => $advancingTeams,
            'total_teams' => count($advancingTeams),
            'phase' => 'School to District Advancement'
        ];
    }
    
    /**
     * Advance teams from Phase 2 (District) to Phase 3 (Provincial Finals)
     */
    public function advanceFromPhase2ToPhase3($districtResults = null)
    {
        if ($districtResults === null) {
            $districtResults = $this->getDistrictPhaseResults();
        }
        
        $advancingTeams = [];
        $categories = $this->getActiveCategories();
        
        foreach ($categories as $category) {
            // Select top 6 teams per category from district competitions
            $categoryTeams = $this->selectTopDistrictTeams($category['id'], $districtResults, 6);
            
            foreach ($categoryTeams as $index => $team) {
                $rank = $index + 1;
                
                // Create new team entry for Phase 3
                $newTeamData = [
                    'school_id' => $team['school_id'],
                    'category_id' => $team['category_id'],
                    'phase_id' => 3, // Phase 3 (Provincial Finals)
                    'name' => $team['name'],
                    'team_code' => $team['team_code'] . '-PROV',
                    'coach1_id' => $team['coach1_id'],
                    'coach2_id' => $team['coach2_id'],
                    'status' => 'approved',
                    'qualification_score' => $team['district_score'] ?? 0,
                    'notes' => "Provincial Finalist - Rank {$rank} in {$category['name']}"
                ];
                
                $newTeamId = $this->db->table('teams')->insertGetId($newTeamData);
                
                // Copy participants to new team
                $this->copyParticipantsToNextPhase($team['id'], $newTeamId);
                
                // Record progression
                $this->recordProgression(
                    $newTeamId,
                    2, // from Phase 2
                    3, // to Phase 3
                    $team['district_score'] ?? 0,
                    $rank,
                    "Qualified for provincial finals as rank {$rank}"
                );
                
                $advancingTeams[] = [
                    'original_team_id' => $team['id'],
                    'provincial_team_id' => $newTeamId,
                    'category' => $category['name'],
                    'provincial_rank' => $rank,
                    'district_score' => $team['district_score'] ?? 0
                ];
            }
        }
        
        return [
            'success' => true,
            'advancing_teams' => $advancingTeams,
            'total_teams' => count($advancingTeams),
            'phase' => 'District to Provincial Advancement'
        ];
    }
    
    /**
     * Handle phase bypass (used for custom progressions)
     */
    public function handlePhaseBypass($fromPhase, $toPhase, $teams, $reason = 'Phase bypass')
    {
        $bypassedTeams = [];
        
        foreach ($teams as $team) {
            // Create new team entry for target phase
            $newTeamData = [
                'school_id' => $team['school_id'],
                'category_id' => $team['category_id'],
                'phase_id' => $toPhase,
                'name' => $team['name'],
                'team_code' => $team['team_code'] . '-BYPASS',
                'coach1_id' => $team['coach1_id'],
                'coach2_id' => $team['coach2_id'],
                'status' => 'approved',
                'qualification_score' => $team['score'] ?? 0,
                'notes' => "Advanced via phase bypass: {$reason}"
            ];
            
            $newTeamId = $this->db->table('teams')->insertGetId($newTeamData);
            
            // Copy participants
            $this->copyParticipantsToNextPhase($team['id'], $newTeamId);
            
            // Record progression
            $this->recordProgression(
                $newTeamId,
                $fromPhase,
                $toPhase,
                $team['score'] ?? 0,
                null,
                $reason
            );
            
            $bypassedTeams[] = [
                'original_team_id' => $team['id'],
                'new_team_id' => $newTeamId,
                'from_phase' => $fromPhase,
                'to_phase' => $toPhase
            ];
        }
        
        return [
            'success' => true,
            'bypassed_teams' => $bypassedTeams,
            'total_teams' => count($bypassedTeams)
        ];
    }
    
    /**
     * Get active categories
     */
    private function getActiveCategories()
    {
        return $this->db->query("
            SELECT * FROM categories 
            WHERE status = 'active' 
            AND deleted_at IS NULL 
            ORDER BY name
        ");
    }
    
    /**
     * Get school phase results
     */
    private function getSchoolPhaseResults()
    {
        return $this->db->query("
            SELECT 
                t.*,
                c.name as category_name,
                s.name as school_name,
                s.district_id,
                COALESCE(AVG(sc.total_score), 0) as school_score
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = 1
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.id, s.district_id, school_score DESC
        ");
    }
    
    /**
     * Get district phase results
     */
    private function getDistrictPhaseResults()
    {
        return $this->db->query("
            SELECT 
                t.*,
                c.name as category_name,
                s.name as school_name,
                s.district_id,
                COALESCE(AVG(sc.total_score), 0) as district_score
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = 2
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.id, district_score DESC
        ");
    }
    
    /**
     * Select teams for district phase with geographic distribution
     */
    private function selectTeamsForDistrict($categoryId, $schoolResults, $maxTeams = 15)
    {
        // Filter teams for this category
        $categoryTeams = array_filter($schoolResults, function($team) use ($categoryId) {
            return $team['category_id'] == $categoryId;
        });
        
        // Group by district for geographic distribution
        $districtGroups = [];
        foreach ($categoryTeams as $team) {
            $districtGroups[$team['district_id']][] = $team;
        }
        
        // Sort teams within each district by score
        foreach ($districtGroups as $districtId => $teams) {
            usort($districtGroups[$districtId], function($a, $b) {
                return ($b['school_score'] ?? 0) <=> ($a['school_score'] ?? 0);
            });
        }
        
        // Select teams ensuring geographic distribution
        $selectedTeams = [];
        $teamsPerDistrict = max(1, floor($maxTeams / count($districtGroups)));
        
        foreach ($districtGroups as $districtId => $teams) {
            $districtSelection = array_slice($teams, 0, $teamsPerDistrict);
            $selectedTeams = array_merge($selectedTeams, $districtSelection);
        }
        
        // Fill remaining slots with best teams regardless of district
        if (count($selectedTeams) < $maxTeams) {
            $remaining = $maxTeams - count($selectedTeams);
            $allTeams = array_merge(...array_values($districtGroups));
            
            // Remove already selected teams
            $selectedIds = array_column($selectedTeams, 'id');
            $availableTeams = array_filter($allTeams, function($team) use ($selectedIds) {
                return !in_array($team['id'], $selectedIds);
            });
            
            // Sort by score and take best remaining teams
            usort($availableTeams, function($a, $b) {
                return ($b['school_score'] ?? 0) <=> ($a['school_score'] ?? 0);
            });
            
            $additionalTeams = array_slice($availableTeams, 0, $remaining);
            $selectedTeams = array_merge($selectedTeams, $additionalTeams);
        }
        
        return array_slice($selectedTeams, 0, $maxTeams);
    }
    
    /**
     * Select top district teams for provincial finals
     */
    private function selectTopDistrictTeams($categoryId, $districtResults, $maxTeams = 6)
    {
        // Filter teams for this category
        $categoryTeams = array_filter($districtResults, function($team) use ($categoryId) {
            return $team['category_id'] == $categoryId;
        });
        
        // Sort by district score (highest first)
        usort($categoryTeams, function($a, $b) {
            return ($b['district_score'] ?? 0) <=> ($a['district_score'] ?? 0);
        });
        
        return array_slice($categoryTeams, 0, $maxTeams);
    }
    
    /**
     * Copy participants to next phase team
     */
    private function copyParticipantsToNextPhase($sourceTeamId, $targetTeamId)
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
     * Record progression in phase_progressions table
     */
    private function recordProgression($teamId, $fromPhase, $toPhase, $score, $rank, $reason)
    {
        $progressionData = [
            'team_id' => $teamId,
            'from_phase_id' => $fromPhase,
            'to_phase_id' => $toPhase,
            'progression_date' => date('Y-m-d H:i:s'),
            'score' => $score,
            'rank_in_category' => $rank,
            'qualified' => true,
            'advancement_reason' => $reason,
            'competition_type' => 'full'
        ];
        
        $this->db->table('phase_progressions')->insert($progressionData);
    }
    
    /**
     * Get full system statistics
     */
    public function getFullSystemStatistics()
    {
        $phaseStats = [];
        
        for ($phase = 1; $phase <= 3; $phase++) {
            $stats = $this->db->query("
                SELECT 
                    c.name as category_name,
                    COUNT(t.id) as team_count,
                    COUNT(p.id) as participant_count,
                    COUNT(DISTINCT t.school_id) as school_count
                FROM categories c
                LEFT JOIN teams t ON c.id = t.category_id AND t.phase_id = ? AND t.deleted_at IS NULL
                LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
                WHERE c.deleted_at IS NULL
                GROUP BY c.id, c.name
                ORDER BY c.name
            ", [$phase]);
            
            $phaseStats["phase_{$phase}"] = $stats;
        }
        
        return [
            'phase_statistics' => $phaseStats,
            'system_type' => 'Full Three-Phase System',
            'phases' => [
                'phase_1' => 'School-Based Competition',
                'phase_2' => 'District/Regional Competition', 
                'phase_3' => 'Provincial Finals'
            ],
            'capacity_limits' => [
                'phase_1' => 'Unlimited',
                'phase_2' => '15 teams per category',
                'phase_3' => '6 teams per category'
            ]
        ];
    }
    
    /**
     * Validate geographic distribution requirements
     */
    public function validateGeographicDistribution($teams)
    {
        $districtCounts = [];
        
        foreach ($teams as $team) {
            $districtId = $team['district_id'] ?? 'unknown';
            $districtCounts[$districtId] = ($districtCounts[$districtId] ?? 0) + 1;
        }
        
        $totalDistricts = count($districtCounts);
        $avgTeamsPerDistrict = count($teams) / max(1, $totalDistricts);
        
        return [
            'valid' => $totalDistricts > 1, // Ensure multiple districts represented
            'district_counts' => $districtCounts,
            'total_districts' => $totalDistricts,
            'average_teams_per_district' => $avgTeamsPerDistrict,
            'geographic_diversity' => $totalDistricts >= 3 ? 'Good' : 'Limited'
        ];
    }
}