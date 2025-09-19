<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CompetitionSetup;
use App\Models\CompetitionPhase;
use App\Models\CompetitionCategory;

class PhaseSchedulerController extends BaseController
{
    private $competitionSetup;
    private $competitionPhase;
    private $competitionCategory;
    
    public function __construct()
    {
        parent::__construct();
        $this->competitionSetup = new CompetitionSetup();
        $this->competitionPhase = new CompetitionPhase();
        $this->competitionCategory = new CompetitionCategory();
    }
    
    /**
     * Display phase scheduler dashboard
     */
    public function index()
    {
        try {
            // Check admin permissions
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            // Get active competitions
            $competitions = $this->competitionSetup->db->query("
                SELECT cs.*, 
                       COUNT(DISTINCT cp.id) as phase_count,
                       COUNT(DISTINCT cc.id) as category_count
                FROM competition_setups cs
                LEFT JOIN competition_phases cp ON cs.id = cp.competition_id AND cp.deleted_at IS NULL
                LEFT JOIN competition_categories cc ON cs.id = cc.competition_id AND cc.deleted_at IS NULL
                WHERE cs.deleted_at IS NULL
                GROUP BY cs.id
                ORDER BY cs.year DESC, cs.start_date DESC
            ");
            
            // Get upcoming phases
            $upcomingPhases = $this->competitionPhase->db->query("
                SELECT cp.*, cs.name as competition_name
                FROM competition_phases cp
                JOIN competition_setups cs ON cp.competition_id = cs.id
                WHERE cp.start_date > NOW()
                AND cp.is_active = 1
                AND cp.deleted_at IS NULL
                AND cs.deleted_at IS NULL
                ORDER BY cp.start_date
                LIMIT 10
            ");
            
            // Get active phases
            $activePhases = $this->competitionPhase->db->query("
                SELECT cp.*, cs.name as competition_name
                FROM competition_phases cp
                JOIN competition_setups cs ON cp.competition_id = cs.id
                WHERE cp.start_date <= NOW()
                AND cp.end_date >= NOW()
                AND cp.is_active = 1
                AND cp.is_completed = 0
                AND cp.deleted_at IS NULL
                AND cs.deleted_at IS NULL
                ORDER BY cp.end_date
            ");
            
            $data = [
                'competitions' => $competitions,
                'upcoming_phases' => $upcomingPhases,
                'active_phases' => $activePhases,
                'page_title' => 'Phase Scheduler'
            ];
            
            return $this->render('admin/phase_scheduler/dashboard', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading phase scheduler dashboard');
            return $this->redirect('/admin/dashboard');
        }
    }
    
    /**
     * Display timeline view for competition
     */
    public function timeline($competitionId)
    {
        try {
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                $this->flashMessage('error', 'Competition not found.');
                return $this->redirect('/admin/phase-scheduler');
            }
            
            // Get phases with statistics
            $phases = $this->competitionPhase->db->query("
                SELECT cp.*, 
                       COUNT(DISTINCT cc.id) as category_count,
                       SUM(cc.registration_count) as total_registrations
                FROM competition_phases cp
                LEFT JOIN competition_categories cc ON cp.competition_id = cc.competition_id AND cc.deleted_at IS NULL
                WHERE cp.competition_id = ?
                AND cp.deleted_at IS NULL
                GROUP BY cp.id
                ORDER BY cp.phase_order
            ", [$competitionId]);
            
            // Calculate timeline data
            $timelineData = $this->calculateTimelineData($phases);
            
            // Get conflict warnings
            $conflicts = $this->detectSchedulingConflicts($phases);
            
            $data = [
                'competition' => $competition,
                'phases' => $phases,
                'timeline_data' => $timelineData,
                'conflicts' => $conflicts,
                'page_title' => "Timeline - {$competition->name}"
            ];
            
            return $this->render('admin/phase_scheduler/timeline', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading timeline');
            return $this->redirect('/admin/phase-scheduler');
        }
    }
    
    /**
     * Create new phase schedule
     */
    public function createSchedule()
    {
        try {
            $competitionId = $this->input('competition_id');
            $phaseData = $this->input('phase_data', []);
            
            // Validate input
            if (!$competitionId || empty($phaseData)) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition ID and phase data are required'
                ]);
            }
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition not found'
                ]);
            }
            
            // Validate phase schedule
            $validation = $this->validatePhaseSchedule($phaseData);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ]);
            }
            
            // Create phases
            $createdPhases = [];
            foreach ($phaseData as $phase) {
                $competitionPhase = new CompetitionPhase();
                $competitionPhase->competition_id = $competitionId;
                $competitionPhase->phase_number = $phase['phase_number'];
                $competitionPhase->name = $phase['name'];
                $competitionPhase->description = $phase['description'] ?? '';
                $competitionPhase->start_date = $phase['start_date'];
                $competitionPhase->end_date = $phase['end_date'];
                $competitionPhase->capacity_per_category = $phase['capacity_per_category'] ?? 30;
                $competitionPhase->venue_requirements = json_encode($phase['venue_requirements'] ?? []);
                $competitionPhase->advancement_criteria = json_encode($phase['advancement_criteria'] ?? []);
                $competitionPhase->phase_order = $phase['phase_order'] ?? $phase['phase_number'];
                $competitionPhase->is_active = $phase['is_active'] ?? true;
                
                if ($competitionPhase->save()) {
                    $createdPhases[] = $competitionPhase->id;
                }
            }
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Phase schedule created successfully',
                'created_phases' => $createdPhases
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error creating phase schedule: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update existing phase schedule
     */
    public function updateSchedule()
    {
        try {
            $phaseId = $this->input('phase_id');
            $updateData = $this->input('update_data', []);
            
            $phase = $this->competitionPhase->find($phaseId);
            if (!$phase) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Phase not found'
                ]);
            }
            
            // Validate update data
            $validation = $this->validatePhaseUpdate($updateData);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ]);
            }
            
            // Check for conflicts with updated schedule
            $conflicts = $this->checkPhaseConflicts($phase, $updateData);
            if (!empty($conflicts)) {
                return $this->jsonResponse([
                    'success' => false,
                    'conflicts' => $conflicts,
                    'message' => 'Schedule conflicts detected'
                ]);
            }
            
            // Update phase
            foreach ($updateData as $field => $value) {
                if (in_array($field, $phase->fillable)) {
                    if (in_array($field, ['venue_requirements', 'advancement_criteria', 'scoring_configuration'])) {
                        $phase->$field = json_encode($value);
                    } else {
                        $phase->$field = $value;
                    }
                }
            }
            
            if ($phase->save()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Phase schedule updated successfully'
                ]);
            }
            
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to update phase schedule'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error updating phase schedule: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validate complete schedule
     */
    public function validateSchedule()
    {
        try {
            $competitionId = $this->input('competition_id');
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition not found'
                ]);
            }
            
            $phases = $this->competitionPhase->getPhasesByCompetition($competitionId);
            
            // Perform comprehensive validation
            $validation = $this->performComprehensiveValidation($competition, $phases);
            
            return $this->jsonResponse([
                'success' => true,
                'validation_results' => $validation
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error validating schedule: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Activate specific phase
     */
    public function activatePhase()
    {
        try {
            $phaseId = $this->input('phase_id');
            
            $phase = $this->competitionPhase->find($phaseId);
            if (!$phase) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Phase not found'
                ]);
            }
            
            // Check prerequisites
            $prerequisiteCheck = $this->checkPhasePrerequisites($phase);
            if (!$prerequisiteCheck['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Prerequisites not met',
                    'prerequisites' => $prerequisiteCheck['missing']
                ]);
            }
            
            // Activate phase
            $phase->is_active = true;
            $phase->updated_at = date('Y-m-d H:i:s');
            
            if ($phase->save()) {
                // Send notifications
                $this->sendPhaseActivationNotifications($phase);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Phase activated successfully'
                ]);
            }
            
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to activate phase'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error activating phase: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Complete phase
     */
    public function completePhase()
    {
        try {
            $phaseId = $this->input('phase_id');
            $completionData = $this->input('completion_data', []);
            
            $phase = $this->competitionPhase->find($phaseId);
            if (!$phase) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Phase not found'
                ]);
            }
            
            // Validate completion requirements
            $completionCheck = $this->checkCompletionRequirements($phase);
            if (!$completionCheck['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Completion requirements not met',
                    'requirements' => $completionCheck['missing']
                ]);
            }
            
            // Mark phase as completed
            $phase->is_completed = true;
            $phase->completion_date = date('Y-m-d H:i:s');
            $phase->updated_at = date('Y-m-d H:i:s');
            
            if ($phase->save()) {
                // Process advancement to next phase
                $this->processPhaseAdvancement($phase, $completionData);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Phase completed successfully'
                ]);
            }
            
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to complete phase'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error completing phase: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get calendar data for timeline view
     */
    public function getCalendarData()
    {
        try {
            $competitionId = $this->input('competition_id');
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            
            $phases = $this->competitionPhase->db->query("
                SELECT cp.*, cs.name as competition_name, cs.type as competition_type
                FROM competition_phases cp
                JOIN competition_setups cs ON cp.competition_id = cs.id
                WHERE (?  IS NULL OR cp.competition_id = ?)
                AND cp.start_date >= ?
                AND cp.end_date <= ?
                AND cp.deleted_at IS NULL
                AND cs.deleted_at IS NULL
                ORDER BY cp.start_date
            ", [$competitionId, $competitionId, $startDate, $endDate]);
            
            // Format for calendar display
            $calendarEvents = [];
            foreach ($phases as $phase) {
                $calendarEvents[] = [
                    'id' => $phase['id'],
                    'title' => $phase['name'],
                    'start' => $phase['start_date'],
                    'end' => $phase['end_date'],
                    'competition' => $phase['competition_name'],
                    'type' => $phase['competition_type'],
                    'phase_number' => $phase['phase_number'],
                    'is_active' => $phase['is_active'],
                    'is_completed' => $phase['is_completed']
                ];
            }
            
            return $this->jsonResponse([
                'success' => true,
                'events' => $calendarEvents
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error loading calendar data: ' . $e->getMessage()
            ]);
        }
    }
    
    // PRIVATE HELPER METHODS
    
    /**
     * Calculate timeline data for visualization
     */
    private function calculateTimelineData($phases)
    {
        $timelineData = [
            'total_duration' => 0,
            'phases' => [],
            'milestones' => [],
            'critical_path' => []
        ];
        
        foreach ($phases as $phase) {
            $startTime = strtotime($phase['start_date']);
            $endTime = strtotime($phase['end_date']);
            $duration = $endTime - $startTime;
            
            $timelineData['phases'][] = [
                'id' => $phase['id'],
                'name' => $phase['name'],
                'start' => $phase['start_date'],
                'end' => $phase['end_date'],
                'duration_days' => floor($duration / (60 * 60 * 24)),
                'progress' => $this->calculatePhaseProgress($phase),
                'status' => $this->getPhaseStatus($phase)
            ];
        }
        
        return $timelineData;
    }
    
    /**
     * Detect scheduling conflicts
     */
    private function detectSchedulingConflicts($phases)
    {
        $conflicts = [];
        
        for ($i = 0; $i < count($phases); $i++) {
            for ($j = $i + 1; $j < count($phases); $j++) {
                $phase1 = $phases[$i];
                $phase2 = $phases[$j];
                
                // Check for overlapping dates
                if ($this->datesOverlap($phase1['start_date'], $phase1['end_date'], 
                                       $phase2['start_date'], $phase2['end_date'])) {
                    $conflicts[] = [
                        'type' => 'date_overlap',
                        'phase_1' => $phase1['name'],
                        'phase_2' => $phase2['name'],
                        'description' => "Phases '{$phase1['name']}' and '{$phase2['name']}' have overlapping dates"
                    ];
                }
                
                // Check for capacity conflicts
                if ($this->hasCapacityConflict($phase1, $phase2)) {
                    $conflicts[] = [
                        'type' => 'capacity_conflict',
                        'phase_1' => $phase1['name'],
                        'phase_2' => $phase2['name'],
                        'description' => "Capacity conflict between phases"
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Validate phase schedule
     */
    private function validatePhaseSchedule($phaseData)
    {
        $errors = [];
        
        foreach ($phaseData as $index => $phase) {
            if (empty($phase['name'])) {
                $errors["phase_{$index}_name"] = 'Phase name is required';
            }
            if (empty($phase['start_date'])) {
                $errors["phase_{$index}_start_date"] = 'Start date is required';
            }
            if (empty($phase['end_date'])) {
                $errors["phase_{$index}_end_date"] = 'End date is required';
            }
            if (!empty($phase['start_date']) && !empty($phase['end_date']) && 
                strtotime($phase['end_date']) <= strtotime($phase['start_date'])) {
                $errors["phase_{$index}_dates"] = 'End date must be after start date';
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Check if dates overlap
     */
    private function datesOverlap($start1, $end1, $start2, $end2)
    {
        return (strtotime($start1) <= strtotime($end2)) && (strtotime($end1) >= strtotime($start2));
    }
    
    /**
     * Check for capacity conflicts
     */
    private function hasCapacityConflict($phase1, $phase2)
    {
        // Implementation depends on venue and resource constraints
        return false; // Simplified for now
    }
    
    /**
     * Calculate phase progress
     */
    private function calculatePhaseProgress($phase)
    {
        if ($phase['is_completed']) {
            return 100;
        }
        
        $now = time();
        $start = strtotime($phase['start_date']);
        $end = strtotime($phase['end_date']);
        
        if ($now < $start) {
            return 0;
        }
        
        if ($now > $end) {
            return 100;
        }
        
        return floor(($now - $start) / ($end - $start) * 100);
    }
    
    /**
     * Get phase status
     */
    private function getPhaseStatus($phase)
    {
        if ($phase['is_completed']) {
            return 'completed';
        }
        
        $now = time();
        $start = strtotime($phase['start_date']);
        $end = strtotime($phase['end_date']);
        
        if ($now < $start) {
            return 'upcoming';
        }
        
        if ($now > $end) {
            return 'overdue';
        }
        
        return 'active';
    }
    
    /**
     * Validate phase update
     */
    private function validatePhaseUpdate($updateData)
    {
        $errors = [];
        
        if (isset($updateData['start_date']) && isset($updateData['end_date'])) {
            if (strtotime($updateData['end_date']) <= strtotime($updateData['start_date'])) {
                $errors['dates'] = 'End date must be after start date';
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Check phase conflicts for updates
     */
    private function checkPhaseConflicts($phase, $updateData)
    {
        // Implementation for checking conflicts when updating phase
        return [];
    }
    
    /**
     * Perform comprehensive validation
     */
    private function performComprehensiveValidation($competition, $phases)
    {
        return [
            'schedule_conflicts' => $this->detectSchedulingConflicts($phases),
            'capacity_issues' => [],
            'dependency_problems' => [],
            'resource_conflicts' => []
        ];
    }
    
    /**
     * Check phase prerequisites
     */
    private function checkPhasePrerequisites($phase)
    {
        return ['valid' => true, 'missing' => []];
    }
    
    /**
     * Check completion requirements
     */
    private function checkCompletionRequirements($phase)
    {
        return ['valid' => true, 'missing' => []];
    }
    
    /**
     * Send phase activation notifications
     */
    private function sendPhaseActivationNotifications($phase)
    {
        // Implementation for sending notifications
    }
    
    /**
     * Process phase advancement
     */
    private function processPhaseAdvancement($phase, $completionData)
    {
        // Implementation for processing team advancement to next phase
    }
    
    /**
     * Check admin access
     */
    private function hasAdminAccess()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}