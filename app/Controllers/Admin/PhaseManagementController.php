<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Phase;
use App\Models\Team;
use App\Models\Category;
use App\Core\PhaseManager;
use App\Core\PilotPhaseProgression;

class PhaseManagementController extends BaseController
{
    private $phaseManager;
    private $pilotProgression;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        
        $competitionType = $this->getCompetitionType();
        $this->phaseManager = new PhaseManager(null, $competitionType);
        $this->pilotProgression = new PilotPhaseProgression();
    }
    
    /**
     * Phase management dashboard
     */
    public function index()
    {
        $phases = $this->phaseManager->getActivePhases();
        $currentPhase = $this->phaseManager->getCurrentActivePhase();
        $statistics = $this->phaseManager->getAllPhasesStatistics();
        
        return $this->render('admin/phase-management/index', [
            'phases' => $phases,
            'current_phase' => $currentPhase,
            'statistics' => $statistics,
            'competition_type' => $this->phaseManager->getCompetitionType()
        ]);
    }
    
    /**
     * Create a new phase
     */
    public function create()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $data = [
                    'name' => $this->input('name'),
                    'code' => $this->input('code'),
                    'description' => $this->input('description'),
                    'order_sequence' => (int)$this->input('order_sequence'),
                    'registration_start' => $this->input('registration_start'),
                    'registration_end' => $this->input('registration_end'),
                    'competition_start' => $this->input('competition_start'),
                    'competition_end' => $this->input('competition_end'),
                    'max_teams' => $this->input('max_teams') ?: null,
                    'venue_requirements' => $this->input('venue_requirements') ? json_encode($this->input('venue_requirements')) : null,
                    'qualification_criteria' => $this->input('qualification_criteria') ? json_encode($this->input('qualification_criteria')) : null,
                    'status' => $this->input('status', 'draft')
                ];
                
                $phase = new Phase();
                $phase->create($data);
                
                $this->flash('success', 'Phase created successfully!');
                return $this->redirect('/admin/phase-management');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to create phase: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/phase-management/create', [
            'available_statuses' => Phase::getAvailableStatuses()
        ]);
    }
    
    /**
     * Edit existing phase
     */
    public function edit($id)
    {
        $phase = (new Phase())->find($id);
        
        if (!$phase) {
            $this->flash('error', 'Phase not found');
            return $this->redirect('/admin/phase-management');
        }
        
        if ($this->request->getMethod() === 'POST') {
            try {
                $data = [
                    'name' => $this->input('name'),
                    'code' => $this->input('code'),
                    'description' => $this->input('description'),
                    'order_sequence' => (int)$this->input('order_sequence'),
                    'registration_start' => $this->input('registration_start'),
                    'registration_end' => $this->input('registration_end'),
                    'competition_start' => $this->input('competition_start'),
                    'competition_end' => $this->input('competition_end'),
                    'max_teams' => $this->input('max_teams') ?: null,
                    'venue_requirements' => $this->input('venue_requirements') ? json_encode($this->input('venue_requirements')) : null,
                    'qualification_criteria' => $this->input('qualification_criteria') ? json_encode($this->input('qualification_criteria')) : null,
                    'status' => $this->input('status')
                ];
                
                $phase->update($data);
                
                $this->flash('success', 'Phase updated successfully!');
                return $this->redirect('/admin/phase-management');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to update phase: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/phase-management/edit', [
            'phase' => $phase,
            'available_statuses' => Phase::getAvailableStatuses()
        ]);
    }
    
    /**
     * Activate or deactivate a phase
     */
    public function activatePhase($id)
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $phase = (new Phase())->find($id);
                
                if (!$phase) {
                    $this->flash('error', 'Phase not found');
                    return $this->redirect('/admin/phase-management');
                }
                
                $action = $this->input('action'); // 'activate' or 'deactivate'
                
                if ($action === 'activate') {
                    $phase->update(['status' => 'active']);
                    $message = 'Phase activated successfully!';
                } else if ($action === 'deactivate') {
                    $phase->update(['status' => 'draft']);
                    $message = 'Phase deactivated successfully!';
                } else {
                    throw new \Exception('Invalid action');
                }
                
                $this->flash('success', $message);
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to update phase status: ' . $e->getMessage());
            }
        }
        
        return $this->redirect('/admin/phase-management');
    }
    
    /**
     * Monitor phase progress
     */
    public function monitorPhaseProgress($id = null)
    {
        if ($id) {
            $statistics = $this->phaseManager->getPhaseStatistics($id);
            $phase = $statistics['phase'];
        } else {
            $statistics = $this->phaseManager->getAllPhasesStatistics();
            $phase = null;
        }
        
        return $this->render('admin/phase-management/monitor', [
            'statistics' => $statistics,
            'phase' => $phase,
            'phases' => $this->phaseManager->getActivePhases()
        ]);
    }
    
    /**
     * Generate phase reports
     */
    public function generatePhaseReports()
    {
        $reportType = $this->input('type', 'summary'); // 'summary', 'detailed', 'progression'
        $phaseId = $this->input('phase_id');
        
        try {
            switch ($reportType) {
                case 'summary':
                    $data = $this->generateSummaryReport($phaseId);
                    break;
                case 'detailed':
                    $data = $this->generateDetailedReport($phaseId);
                    break;
                case 'progression':
                    $data = $this->generateProgressionReport();
                    break;
                default:
                    throw new \Exception('Invalid report type');
            }
            
            return $this->render('admin/phase-management/reports', [
                'report_type' => $reportType,
                'report_data' => $data,
                'phases' => $this->phaseManager->getActivePhases()
            ]);
            
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to generate report: ' . $e->getMessage());
            return $this->redirect('/admin/phase-management');
        }
    }
    
    /**
     * Export phase data
     */
    public function exportPhaseData($format = 'csv')
    {
        $phaseId = $this->input('phase_id');
        $includeParticipants = $this->input('include_participants', false);
        
        try {
            $statistics = $this->phaseManager->getPhaseStatistics($phaseId);
            
            if (!$statistics) {
                $this->flash('error', 'Phase not found');
                return $this->redirect('/admin/phase-management');
            }
            
            $exportData = $this->prepareExportData($statistics, $includeParticipants);
            
            if ($format === 'csv') {
                return $this->exportToCsv($exportData, "phase_{$phaseId}_data.csv");
            } else if ($format === 'json') {
                return $this->exportToJson($exportData, "phase_{$phaseId}_data.json");
            }
            
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to export data: ' . $e->getMessage());
            return $this->redirect('/admin/phase-management');
        }
    }
    
    /**
     * Pilot-specific: Advance teams from Phase 1 to Phase 3
     */
    public function advancePilotTeams()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $result = $this->pilotProgression->advanceFromPhase1ToPhase3();
                
                if ($result['success']) {
                    $message = "Successfully advanced {$result['total_teams']} teams to SciBOTICS Final!";
                    $this->flash('success', $message);
                } else {
                    $this->flash('error', 'Failed to advance teams');
                }
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to advance teams: ' . $e->getMessage());
            }
        }
        
        // Get current Phase 1 teams for preview
        $phase1Teams = $this->db->query("
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
        
        return $this->render('admin/phase-management/pilot-advancement', [
            'eligible_teams' => $phase1Teams,
            'total_eligible' => count($phase1Teams),
            'final_date' => '2025-09-27',
            'final_venue' => 'Sci-Bono Discovery Centre'
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
     * Generate summary report
     */
    private function generateSummaryReport($phaseId)
    {
        return $this->phaseManager->getPhaseStatistics($phaseId);
    }
    
    /**
     * Generate detailed report
     */
    private function generateDetailedReport($phaseId)
    {
        $statistics = $this->phaseManager->getPhaseStatistics($phaseId);
        
        // Get detailed team information
        $teams = $this->db->query("
            SELECT 
                t.*,
                c.name as category_name,
                s.name as school_name,
                s.address as school_address,
                COUNT(p.id) as participant_count,
                COALESCE(AVG(sc.total_score), 0) as average_score
            FROM teams t
            JOIN categories c ON t.category_id = c.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.phase_id = ?
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY c.name, average_score DESC
        ", [$phaseId]);
        
        return array_merge($statistics, ['teams' => $teams]);
    }
    
    /**
     * Generate progression report
     */
    private function generateProgressionReport()
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
        ");
    }
    
    /**
     * Prepare data for export
     */
    private function prepareExportData($statistics, $includeParticipants)
    {
        $data = [];
        
        if (isset($statistics['teams'])) {
            foreach ($statistics['teams'] as $team) {
                $row = [
                    'Team Name' => $team['name'],
                    'Team Code' => $team['team_code'],
                    'Category' => $team['category_name'],
                    'School' => $team['school_name'],
                    'Participants' => $team['participant_count'],
                    'Average Score' => $team['average_score'],
                    'Status' => $team['status']
                ];
                
                if ($includeParticipants) {
                    // Add participant details if requested
                    $participants = $this->db->query("
                        SELECT first_name, last_name, grade, age
                        FROM participants 
                        WHERE team_id = ? AND deleted_at IS NULL
                    ", [$team['id']]);
                    
                    foreach ($participants as $i => $participant) {
                        $row["Participant " . ($i + 1)] = $participant['first_name'] . ' ' . $participant['last_name'];
                        $row["Grade " . ($i + 1)] = $participant['grade'];
                        $row["Age " . ($i + 1)] = $participant['age'];
                    }
                }
                
                $data[] = $row;
            }
        }
        
        return $data;
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