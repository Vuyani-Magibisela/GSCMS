<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PhaseProgression;
use App\Models\Team;
use App\Models\Phase;
use App\Models\Category;
use App\Core\PhaseManager;
use App\Core\PilotPhaseProgression;
use App\Core\FullSystemPhaseProgression;

class PhaseProgressionController extends BaseController
{
    private $phaseManager;
    private $pilotProgression;
    private $fullProgression;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        
        $competitionType = $this->getCompetitionType();
        $this->phaseManager = new PhaseManager(null, $competitionType);
        $this->pilotProgression = new PilotPhaseProgression();
        $this->fullProgression = new FullSystemPhaseProgression();
    }
    
    /**
     * Phase progression dashboard
     */
    public function index()
    {
        $competitionType = $this->phaseManager->getCompetitionType();
        $progressions = $this->getRecentProgressions();
        $advancementStats = (new PhaseProgression())->getAdvancementStatistics($competitionType);
        $progressionSummary = (new PhaseProgression())->getProgressionSummary($competitionType);
        
        return $this->render('admin/phase-progression/index', [
            'competition_type' => $competitionType,
            'recent_progressions' => $progressions,
            'advancement_statistics' => $advancementStats,
            'progression_summary' => $progressionSummary,
            'phases' => $this->phaseManager->getActivePhases()
        ]);
    }
    
    /**
     * Advance teams between phases
     */
    public function advanceTeams()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $fromPhaseId = (int)$this->input('from_phase_id');
                $toPhaseId = (int)$this->input('to_phase_id');
                $selectedTeamIds = $this->input('team_ids', []);
                
                // Validate phase progression is allowed
                if (!$this->phaseManager->isProgressionAllowed($fromPhaseId, $toPhaseId)) {
                    throw new \Exception('Phase progression not allowed');
                }
                
                if (empty($selectedTeamIds)) {
                    throw new \Exception('No teams selected for advancement');
                }
                
                // Get selected teams
                $teams = $this->getTeamsByIds($selectedTeamIds);
                
                if ($this->phaseManager->getCompetitionType() === 'pilot') {
                    // Use pilot progression logic
                    $result = $this->pilotProgression->advanceFromPhase1ToPhase3($teams);
                } else {
                    // Use full system progression logic
                    $result = $this->phaseManager->advanceTeamsToNextPhase($fromPhaseId, $teams);
                }
                
                if ($result['success']) {
                    $message = "Successfully advanced {$result['total_teams']} teams";
                    $this->flash('success', $message);
                } else {
                    $this->flash('error', $result['message'] ?? 'Failed to advance teams');
                }
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to advance teams: ' . $e->getMessage());
            }
            
            return $this->redirect('/admin/phase-progression');
        }
        
        // Get phases and teams for form
        $phases = $this->phaseManager->getActivePhases();
        $fromPhaseId = (int)$this->input('from_phase');
        $eligibleTeams = [];
        
        if ($fromPhaseId) {
            $eligibleTeams = $this->getEligibleTeamsForPhase($fromPhaseId);
        }
        
        return $this->render('admin/phase-progression/advance', [
            'phases' => $phases,
            'from_phase_id' => $fromPhaseId,
            'eligible_teams' => $eligibleTeams,
            'competition_type' => $this->phaseManager->getCompetitionType()
        ]);
    }
    
    /**
     * Calculate team rankings within categories
     */
    public function calculateRankings()
    {
        $phaseId = (int)$this->input('phase_id');
        $categoryId = (int)$this->input('category_id');
        
        try {
            $rankings = $this->calculateCategoryRankings($phaseId, $categoryId);
            
            return $this->render('admin/phase-progression/rankings', [
                'rankings' => $rankings,
                'phase_id' => $phaseId,
                'category_id' => $categoryId,
                'phases' => $this->phaseManager->getActivePhases(),
                'categories' => (new Category())->where('status', 'active')->get()
            ]);
            
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to calculate rankings: ' . $e->getMessage());
            return $this->redirect('/admin/phase-progression');
        }
    }
    
    /**
     * Generate qualification lists for next phase
     */
    public function generateQualificationLists()
    {
        $phaseId = (int)$this->input('phase_id');
        $nextPhase = $this->phaseManager->getNextPhase($phaseId);
        
        if (!$nextPhase) {
            $this->flash('error', 'No next phase available');
            return $this->redirect('/admin/phase-progression');
        }
        
        try {
            $qualificationLists = [];
            $categories = (new Category())->where('status', 'active')->get();
            $nextPhaseCapacity = $this->phaseManager->getPhaseCapacity($nextPhase['id']);
            
            foreach ($categories as $category) {
                $categoryTeams = $this->getCategoryTeamRankings($phaseId, $category->id);
                $qualifiedTeams = array_slice($categoryTeams, 0, $nextPhaseCapacity);
                
                $qualificationLists[$category->name] = [
                    'category_id' => $category->id,
                    'qualified_teams' => $qualifiedTeams,
                    'total_teams' => count($categoryTeams),
                    'qualification_cutoff' => $nextPhaseCapacity
                ];
            }
            
            return $this->render('admin/phase-progression/qualification-lists', [
                'qualification_lists' => $qualificationLists,
                'current_phase' => $this->db->table('phases')->where('id', $phaseId)->first(),
                'next_phase' => $nextPhase,
                'total_qualified' => array_sum(array_map(function($list) { 
                    return count($list['qualified_teams']); 
                }, $qualificationLists))
            ]);
            
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to generate qualification lists: ' . $e->getMessage());
            return $this->redirect('/admin/phase-progression');
        }
    }
    
    /**
     * Handle pilot-specific phase skipping (Phase 1 -> Phase 3)
     */
    public function handlePhaseSkipping()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                if ($this->phaseManager->getCompetitionType() !== 'pilot') {
                    throw new \Exception('Phase skipping is only available for pilot programme');
                }
                
                $result = $this->pilotProgression->advanceFromPhase1ToPhase3();
                
                if ($result['success']) {
                    $message = "Successfully advanced {$result['total_teams']} teams to SciBOTICS Final (Phase 2 skipped)";
                    $this->flash('success', $message);
                    
                    // Log the phase skip event
                    $this->logPhaseSkip($result);
                } else {
                    $this->flash('error', 'Failed to advance teams');
                }
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to handle phase skipping: ' . $e->getMessage());
            }
        }
        
        // Get preview of teams that would advance
        $eligibleTeams = $this->db->query("
            SELECT 
                t.name as team_name,
                c.name as category_name,
                s.name as school_name,
                COALESCE(AVG(sc.total_score), 0) as score,
                ROW_NUMBER() OVER (PARTITION BY t.category_id ORDER BY COALESCE(AVG(sc.total_score), 0) DESC) as category_rank
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = 1 
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            HAVING category_rank <= 6
            ORDER BY c.name, category_rank
        ");
        
        return $this->render('admin/phase-progression/phase-skipping', [
            'eligible_teams' => $eligibleTeams,
            'total_eligible' => count($eligibleTeams),
            'pilot_config' => [
                'skip_from' => 'Phase 1',
                'skip_to' => 'Phase 3',
                'skipped_phase' => 'Phase 2',
                'final_venue' => 'Sci-Bono Discovery Centre',
                'final_date' => '2025-09-27'
            ]
        ]);
    }
    
    /**
     * View progression history for a specific team
     */
    public function viewTeamProgression($teamId)
    {
        $team = (new Team())->find($teamId);
        
        if (!$team) {
            $this->flash('error', 'Team not found');
            return $this->redirect('/admin/phase-progression');
        }
        
        $progressionHistory = (new PhaseProgression())->getTeamProgressionHistory($teamId);
        
        return $this->render('admin/phase-progression/team-history', [
            'team' => $team,
            'progression_history' => $progressionHistory
        ]);
    }
    
    /**
     * Export progression data
     */
    public function exportProgressionData($format = 'csv')
    {
        try {
            $competitionType = $this->input('competition_type', $this->phaseManager->getCompetitionType());
            $includeTeamDetails = $this->input('include_team_details', false);
            
            $progressions = $this->db->query("
                SELECT 
                    pp.*,
                    fp.name as from_phase_name,
                    tp.name as to_phase_name,
                    t.name as team_name,
                    t.team_code,
                    c.name as category_name,
                    s.name as school_name
                FROM phase_progressions pp
                LEFT JOIN phases fp ON pp.from_phase_id = fp.id
                JOIN phases tp ON pp.to_phase_id = tp.id
                JOIN teams t ON pp.team_id = t.id
                JOIN categories c ON t.category_id = c.id
                JOIN schools s ON t.school_id = s.id
                WHERE pp.competition_type = ?
                AND pp.deleted_at IS NULL
                ORDER BY pp.progression_date DESC
            ", [$competitionType]);
            
            $exportData = [];
            
            foreach ($progressions as $progression) {
                $row = [
                    'Team Name' => $progression['team_name'],
                    'Team Code' => $progression['team_code'],
                    'Category' => $progression['category_name'],
                    'School' => $progression['school_name'],
                    'From Phase' => $progression['from_phase_name'] ?: 'Initial',
                    'To Phase' => $progression['to_phase_name'],
                    'Progression Date' => $progression['progression_date'],
                    'Score' => $progression['score'],
                    'Rank in Category' => $progression['rank_in_category'],
                    'Qualified' => $progression['qualified'] ? 'Yes' : 'No',
                    'Advancement Reason' => $progression['advancement_reason'],
                    'Competition Type' => ucfirst($progression['competition_type'])
                ];
                
                $exportData[] = $row;
            }
            
            if ($format === 'csv') {
                return $this->exportToCsv($exportData, "phase_progressions_{$competitionType}.csv");
            } else if ($format === 'json') {
                return $this->exportToJson($exportData, "phase_progressions_{$competitionType}.json");
            }
            
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to export progression data: ' . $e->getMessage());
            return $this->redirect('/admin/phase-progression');
        }
    }
    
    /**
     * Get recent progressions
     */
    private function getRecentProgressions($limit = 20)
    {
        return $this->db->query("
            SELECT 
                pp.*,
                fp.name as from_phase_name,
                tp.name as to_phase_name,
                t.name as team_name,
                c.name as category_name,
                s.name as school_name
            FROM phase_progressions pp
            LEFT JOIN phases fp ON pp.from_phase_id = fp.id
            JOIN phases tp ON pp.to_phase_id = tp.id
            JOIN teams t ON pp.team_id = t.id
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            WHERE pp.deleted_at IS NULL
            ORDER BY pp.progression_date DESC
            LIMIT ?
        ", [$limit]);
    }
    
    /**
     * Get teams by IDs
     */
    private function getTeamsByIds($teamIds)
    {
        $placeholders = str_repeat('?,', count($teamIds) - 1) . '?';
        
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
            WHERE t.id IN ({$placeholders})
            AND t.deleted_at IS NULL
            GROUP BY t.id
        ", $teamIds);
    }
    
    /**
     * Get eligible teams for a phase
     */
    private function getEligibleTeamsForPhase($phaseId)
    {
        return $this->db->query("
            SELECT 
                t.*,
                c.name as category_name,
                s.name as school_name,
                COALESCE(AVG(sc.total_score), 0) as average_score,
                ROW_NUMBER() OVER (PARTITION BY t.category_id ORDER BY COALESCE(AVG(sc.total_score), 0) DESC) as category_rank
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = ?
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.name, average_score DESC
        ", [$phaseId]);
    }
    
    /**
     * Calculate category rankings
     */
    private function calculateCategoryRankings($phaseId, $categoryId)
    {
        return $this->db->query("
            SELECT 
                t.*,
                s.name as school_name,
                COALESCE(AVG(sc.total_score), 0) as average_score,
                ROW_NUMBER() OVER (ORDER BY COALESCE(AVG(sc.total_score), 0) DESC) as ranking
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = ? 
            AND t.category_id = ?
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY ranking
        ", [$phaseId, $categoryId]);
    }
    
    /**
     * Get category team rankings
     */
    private function getCategoryTeamRankings($phaseId, $categoryId)
    {
        return $this->db->query("
            SELECT 
                t.*,
                s.name as school_name,
                COALESCE(AVG(sc.total_score), 0) as score
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = ? 
            AND t.category_id = ?
            AND t.status = 'approved'
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY score DESC
        ", [$phaseId, $categoryId]);
    }
    
    /**
     * Log phase skip event
     */
    private function logPhaseSkip($result)
    {
        $logData = [
            'event' => 'phase_skip',
            'competition_type' => 'pilot',
            'from_phase' => 1,
            'to_phase' => 3,
            'skipped_phase' => 2,
            'teams_advanced' => $result['total_teams'],
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => json_encode($result)
        ];
        
        // Log to audit table or file
        $this->db->table('audit_logs')->insert([
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => 'phase_skip_pilot',
            'table_name' => 'phase_progressions',
            'details' => json_encode($logData),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get competition type from settings
     */
    private function getCompetitionType()
    {
        $setting = $this->db->table('settings')
            ->where('key', 'competition_type')
            ->first();
            
        return $setting['value'] ?? 'pilot';
    }
    
    /**
     * Export data to CSV
     */
    private function exportToCsv($data, $filename)
    {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        if (!empty($data)) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
        }
        
        exit;
    }
    
    /**
     * Export data to JSON
     */
    private function exportToJson($data, $filename)
    {
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}