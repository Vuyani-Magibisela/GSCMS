<?php
// app/Controllers/JudgeAssignmentController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\EnhancedJudgeAssignmentService;
use App\Models\EnhancedJudgeProfile;

class JudgeAssignmentController extends Controller
{
    private $assignmentService;
    
    public function __construct()
    {
        parent::__construct();
        $this->assignmentService = new EnhancedJudgeAssignmentService();
    }
    
    /**
     * Admin: View assignment dashboard
     */
    public function index()
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        $competitions = $this->getUpcomingCompetitions();
        $recentAssignments = $this->getRecentAssignments();
        $assignmentStats = $this->getOverallAssignmentStats();
        
        $data = [
            'competitions' => $competitions,
            'recent_assignments' => $recentAssignments,
            'stats' => $assignmentStats,
            'judge_availability' => $this->getJudgeAvailabilityOverview()
        ];
        
        return $this->view('admin/judges/assignments/index', $data);
    }
    
    /**
     * Admin: Show assignment interface for specific competition
     */
    public function showCompetition($competitionId)
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        $competition = $this->db->query("SELECT * FROM competitions WHERE id = ?", [$competitionId]);
        if (empty($competition)) {
            $this->session->setFlash('error', 'Competition not found.');
            return $this->redirect('/admin/judges/assignments');
        }
        
        $competition = $competition[0];
        
        // Get competition categories and teams
        $categories = $this->db->query("
            SELECT c.*, COUNT(DISTINCT t.id) as team_count
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id
            LEFT JOIN team_registrations tr ON t.id = tr.team_id
                AND tr.competition_id = ?
            GROUP BY c.id
            HAVING team_count > 0
            ORDER BY c.category_name
        ", [$competitionId]);
        
        // Get current assignments
        $currentAssignments = $this->db->query("
            SELECT 
                jca.*,
                jp.judge_code,
                u.first_name, u.last_name,
                c.category_name,
                o.organization_name
            FROM judge_competition_assignments jca
            INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
            INNER JOIN users u ON jp.user_id = u.id
            INNER JOIN categories c ON jca.category_id = c.id
            LEFT JOIN organizations o ON jp.organization_id = o.id
            WHERE jca.competition_id = ?
            ORDER BY c.category_name, jca.assignment_role, u.last_name
        ", [$competitionId]);
        
        // Get available judges
        $availableJudges = $this->getAvailableJudgesForCompetition($competitionId);
        
        // Get assignment statistics
        $stats = $this->assignmentService->getAssignmentStatistics($competitionId);
        
        $data = [
            'competition' => $competition,
            'categories' => $categories,
            'current_assignments' => $currentAssignments,
            'available_judges' => $availableJudges,
            'stats' => $stats,
            'phases' => $this->getCompetitionPhases($competitionId)
        ];
        
        return $this->view('admin/judges/assignments/competition', $data);
    }
    
    /**
     * Admin: Auto-assign judges to competition
     */
    public function autoAssign()
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $competitionId = $this->request->input('competition_id');
        $phaseId = $this->request->input('phase_id');
        $options = $this->request->input('options', []);
        
        if (!$competitionId || !$phaseId) {
            return $this->json(['success' => false, 'message' => 'Competition ID and Phase ID are required'], 400);
        }
        
        try {
            $result = $this->assignmentService->assignJudges($competitionId, $phaseId, $options);
            
            // Log the assignment action
            $this->logAssignmentAction('auto_assign', $competitionId, [
                'phase_id' => $phaseId,
                'assignments_created' => $result['total_assignments'],
                'judges_assigned' => $result['judges_assigned']
            ]);
            
            return $this->json([
                'success' => true,
                'message' => 'Judges assigned successfully',
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Assignment failed: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 500);
        }
    }
    
    /**
     * Admin: Manual judge assignment
     */
    public function manualAssign()
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $data = $this->request->all();
        
        $requiredFields = ['judge_id', 'competition_id', 'phase_id', 'category_id', 'assignment_role'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['success' => false, 'message' => "Field '{$field}' is required"], 400);
            }
        }
        
        try {
            // Check if judge is available
            $judge = EnhancedJudgeProfile::find($data['judge_id']);
            if (!$judge || !$judge->isAvailableForAssignment($data['session_date'] ?? date('Y-m-d'))) {
                throw new \Exception('Judge is not available for assignment');
            }
            
            // Check for conflicts
            $conflicts = $this->checkAssignmentConflicts($data);
            if (!empty($conflicts)) {
                throw new \Exception('Assignment conflicts detected: ' . implode(', ', $conflicts));
            }
            
            // Create assignment
            $assignmentId = $this->db->insert('judge_competition_assignments', [
                'judge_id' => $data['judge_id'],
                'competition_id' => $data['competition_id'],
                'phase_id' => $data['phase_id'],
                'category_id' => $data['category_id'],
                'assignment_role' => $data['assignment_role'],
                'table_numbers' => isset($data['table_numbers']) ? json_encode($data['table_numbers']) : null,
                'session_date' => $data['session_date'] ?? date('Y-m-d'),
                'session_time' => $data['session_time'] ?? null,
                'teams_assigned' => $data['teams_assigned'] ?? 0,
                'assignment_status' => 'assigned',
                'auto_assigned' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log the assignment
            $this->logAssignmentAction('manual_assign', $data['competition_id'], [
                'judge_id' => $data['judge_id'],
                'category_id' => $data['category_id'],
                'assignment_id' => $assignmentId
            ]);
            
            return $this->json([
                'success' => true,
                'message' => 'Judge assigned successfully',
                'assignment_id' => $assignmentId
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Assignment failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Admin: Remove judge assignment
     */
    public function removeAssignment($assignmentId)
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $reason = $this->request->input('reason', 'Manual removal');
        
        try {
            // Get assignment details before removal
            $assignment = $this->db->query("
                SELECT jca.*, jp.judge_code, u.first_name, u.last_name
                FROM judge_competition_assignments jca
                INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
                INNER JOIN users u ON jp.user_id = u.id
                WHERE jca.id = ?
            ", [$assignmentId]);
            
            if (empty($assignment)) {
                throw new \Exception('Assignment not found');
            }
            
            $assignment = $assignment[0];
            
            // Update assignment status instead of deleting
            $this->db->query("
                UPDATE judge_competition_assignments 
                SET assignment_status = 'cancelled',
                    declined_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [$reason, $assignmentId]);
            
            // Log the removal
            $this->logAssignmentAction('remove_assignment', $assignment['competition_id'], [
                'assignment_id' => $assignmentId,
                'judge_id' => $assignment['judge_id'],
                'reason' => $reason
            ]);
            
            return $this->json([
                'success' => true,
                'message' => 'Assignment removed successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Removal failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Admin: Get alternative judges for assignment
     */
    public function getAlternatives()
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        $competitionId = $this->request->input('competition_id');
        $categoryId = $this->request->input('category_id');
        $excludeIds = $this->request->input('exclude_ids', []);
        
        if (!$competitionId || !$categoryId) {
            return $this->json(['success' => false, 'message' => 'Competition ID and Category ID are required'], 400);
        }
        
        try {
            $alternatives = $this->assignmentService->suggestAlternativeJudges($competitionId, $categoryId, $excludeIds);
            
            return $this->json([
                'success' => true,
                'alternatives' => $alternatives
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to get alternatives: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Admin: Bulk assignment operations
     */
    public function bulkAssign()
    {
        $this->requireRole(['super_admin', 'competition_admin']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $assignments = $this->request->input('assignments', []);
        
        if (empty($assignments)) {
            return $this->json(['success' => false, 'message' => 'No assignments provided'], 400);
        }
        
        $this->db->beginTransaction();
        
        try {
            $created = 0;
            $failed = 0;
            $errors = [];
            
            foreach ($assignments as $assignmentData) {
                try {
                    // Validate and create assignment
                    if ($this->validateAssignmentData($assignmentData)) {
                        $this->db->insert('judge_competition_assignments', array_merge($assignmentData, [
                            'assignment_status' => 'assigned',
                            'auto_assigned' => false,
                            'created_at' => date('Y-m-d H:i:s')
                        ]));
                        $created++;
                    } else {
                        $failed++;
                        $errors[] = "Invalid data for judge {$assignmentData['judge_id']}";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Failed to assign judge {$assignmentData['judge_id']}: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            return $this->json([
                'success' => true,
                'message' => "Bulk assignment completed. Created: {$created}, Failed: {$failed}",
                'created' => $created,
                'failed' => $failed,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return $this->json([
                'success' => false,
                'message' => 'Bulk assignment failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Judge: View my assignments
     */
    public function myAssignments()
    {
        $this->requireRole(['judge']);
        
        $user = $this->getCurrentUser();
        $judgeProfile = $this->db->query("
            SELECT * FROM judge_profiles WHERE user_id = ?
        ", [$user['id']]);
        
        if (empty($judgeProfile)) {
            $this->session->setFlash('error', 'Judge profile not found.');
            return $this->redirect('/judge/dashboard');
        }
        
        $judgeId = $judgeProfile[0]['id'];
        
        // Get upcoming assignments
        $upcomingAssignments = $this->db->query("
            SELECT 
                jca.*,
                c.name as competition_name,
                cat.category_name,
                v.name as venue_name, v.location as venue_location,
                COUNT(t.id) as team_count
            FROM judge_competition_assignments jca
            INNER JOIN competitions c ON jca.competition_id = c.id
            INNER JOIN categories cat ON jca.category_id = cat.id
            LEFT JOIN venues v ON c.venue_id = v.id
            LEFT JOIN teams t ON cat.id = t.category_id
            WHERE jca.judge_id = ?
            AND jca.session_date >= CURDATE()
            AND jca.assignment_status IN ('assigned', 'confirmed')
            GROUP BY jca.id
            ORDER BY jca.session_date ASC, jca.session_time ASC
        ", [$judgeId]);
        
        // Get past assignments
        $pastAssignments = $this->db->query("
            SELECT 
                jca.*,
                c.name as competition_name,
                cat.category_name,
                v.name as venue_name,
                jca.performance_rating
            FROM judge_competition_assignments jca
            INNER JOIN competitions c ON jca.competition_id = c.id
            INNER JOIN categories cat ON jca.category_id = cat.id
            LEFT JOIN venues v ON c.venue_id = v.id
            WHERE jca.judge_id = ?
            AND jca.session_date < CURDATE()
            AND jca.assignment_status = 'completed'
            ORDER BY jca.session_date DESC
            LIMIT 10
        ", [$judgeId]);
        
        $data = [
            'upcoming_assignments' => $upcomingAssignments,
            'past_assignments' => $pastAssignments,
            'judge_profile' => $judgeProfile[0]
        ];
        
        return $this->view('judge/assignments/index', $data);
    }
    
    /**
     * Judge: Confirm assignment
     */
    public function confirmAssignment($assignmentId)
    {
        $this->requireRole(['judge']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $user = $this->getCurrentUser();
        $token = $this->request->input('confirmation_token');
        
        try {
            // Verify assignment belongs to this judge and token matches
            $assignment = $this->db->query("
                SELECT jca.*, jp.user_id
                FROM judge_competition_assignments jca
                INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
                WHERE jca.id = ? AND jp.user_id = ? AND jca.confirmation_token = ?
            ", [$assignmentId, $user['id'], $token]);
            
            if (empty($assignment)) {
                throw new \Exception('Invalid assignment or confirmation token');
            }
            
            // Update assignment status
            $this->db->query("
                UPDATE judge_competition_assignments 
                SET assignment_status = 'confirmed',
                    confirmed_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ", [$assignmentId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Assignment confirmed successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Confirmation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Judge: Decline assignment
     */
    public function declineAssignment($assignmentId)
    {
        $this->requireRole(['judge']);
        
        if ($this->request->getMethod() !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $user = $this->getCurrentUser();
        $reason = $this->request->input('reason', 'No reason provided');
        
        try {
            // Verify assignment belongs to this judge
            $assignment = $this->db->query("
                SELECT jca.*, jp.user_id
                FROM judge_competition_assignments jca
                INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
                WHERE jca.id = ? AND jp.user_id = ?
            ", [$assignmentId, $user['id']]);
            
            if (empty($assignment)) {
                throw new \Exception('Assignment not found or not authorized');
            }
            
            // Update assignment status
            $this->db->query("
                UPDATE judge_competition_assignments 
                SET assignment_status = 'declined',
                    declined_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [$reason, $assignmentId]);
            
            // Notify administrators of the decline
            $this->notifyAdminOfDecline($assignment[0], $reason);
            
            return $this->json([
                'success' => true,
                'message' => 'Assignment declined. Administrators have been notified.'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Decline failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper methods
     */
    
    private function getUpcomingCompetitions()
    {
        return $this->db->query("
            SELECT c.*, v.name as venue_name, COUNT(tr.id) as registered_teams
            FROM competitions c
            LEFT JOIN venues v ON c.venue_id = v.id
            LEFT JOIN team_registrations tr ON c.id = tr.competition_id
            WHERE c.start_date >= CURDATE()
            GROUP BY c.id
            ORDER BY c.start_date ASC
            LIMIT 10
        ");
    }
    
    private function getRecentAssignments()
    {
        return $this->db->query("
            SELECT 
                jca.*,
                u.first_name, u.last_name,
                c.name as competition_name,
                cat.category_name
            FROM judge_competition_assignments jca
            INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
            INNER JOIN users u ON jp.user_id = u.id
            INNER JOIN competitions c ON jca.competition_id = c.id
            INNER JOIN categories cat ON jca.category_id = cat.id
            ORDER BY jca.created_at DESC
            LIMIT 20
        ");
    }
    
    private function getOverallAssignmentStats()
    {
        return $this->db->query("
            SELECT 
                COUNT(DISTINCT jca.judge_id) as total_judges_assigned,
                COUNT(jca.id) as total_assignments,
                COUNT(CASE WHEN jca.assignment_status = 'confirmed' THEN 1 END) as confirmed_assignments,
                COUNT(CASE WHEN jca.assignment_status = 'declined' THEN 1 END) as declined_assignments,
                COUNT(CASE WHEN jca.assignment_status = 'pending' THEN 1 END) as pending_assignments
            FROM judge_competition_assignments jca
            WHERE jca.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")[0];
    }
    
    private function getJudgeAvailabilityOverview()
    {
        return $this->db->query("
            SELECT 
                jp.experience_level,
                COUNT(*) as judge_count,
                COUNT(CASE WHEN jp.status = 'active' THEN 1 END) as active_count,
                AVG(jp.max_assignments_per_day) as avg_capacity
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE u.status = 'active'
            GROUP BY jp.experience_level
            ORDER BY 
                FIELD(jp.experience_level, 'expert', 'advanced', 'intermediate', 'novice')
        ");
    }
    
    private function getAvailableJudgesForCompetition($competitionId)
    {
        $competition = $this->db->query("SELECT start_date FROM competitions WHERE id = ?", [$competitionId]);
        $competitionDate = $competition[0]['start_date'] ?? date('Y-m-d');
        
        return $this->db->query("
            SELECT 
                jp.*,
                u.first_name, u.last_name, u.email,
                o.organization_name,
                COUNT(jca.id) as current_assignments
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN organizations o ON jp.organization_id = o.id
            LEFT JOIN judge_competition_assignments jca ON jp.id = jca.judge_id
                AND jca.session_date = ?
                AND jca.assignment_status IN ('assigned', 'confirmed')
            WHERE jp.status = 'active'
            AND u.status = 'active'
            AND jp.onboarding_completed = 1
            GROUP BY jp.id
            HAVING current_assignments < jp.max_assignments_per_day
            ORDER BY jp.experience_level DESC, u.last_name ASC
        ", [$competitionDate]);
    }
    
    private function getCompetitionPhases($competitionId)
    {
        return $this->db->query("
            SELECT cp.*
            FROM competition_phases cp
            INNER JOIN competitions c ON cp.competition_id = c.id
            WHERE c.id = ?
            ORDER BY cp.phase_order ASC
        ", [$competitionId]);
    }
    
    private function checkAssignmentConflicts($assignmentData)
    {
        $conflicts = [];
        
        // Check for time conflicts
        $timeConflicts = $this->db->query("
            SELECT COUNT(*) as conflict_count
            FROM judge_competition_assignments
            WHERE judge_id = ?
            AND session_date = ?
            AND session_time = ?
            AND assignment_status IN ('assigned', 'confirmed')
        ", [$assignmentData['judge_id'], $assignmentData['session_date'], $assignmentData['session_time']]);
        
        if ($timeConflicts[0]['conflict_count'] > 0) {
            $conflicts[] = 'Time conflict detected';
        }
        
        // Check for workload limits
        $workloadCheck = $this->db->query("
            SELECT COUNT(*) as assignment_count, jp.max_assignments_per_day
            FROM judge_competition_assignments jca
            INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
            WHERE jca.judge_id = ?
            AND jca.session_date = ?
            AND jca.assignment_status IN ('assigned', 'confirmed')
            GROUP BY jp.id
        ", [$assignmentData['judge_id'], $assignmentData['session_date']]);
        
        if (!empty($workloadCheck) && $workloadCheck[0]['assignment_count'] >= $workloadCheck[0]['max_assignments_per_day']) {
            $conflicts[] = 'Judge at maximum capacity for this date';
        }
        
        return $conflicts;
    }
    
    private function validateAssignmentData($data)
    {
        $requiredFields = ['judge_id', 'competition_id', 'phase_id', 'category_id', 'assignment_role'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    private function logAssignmentAction($action, $competitionId, $details = [])
    {
        $user = $this->getCurrentUser();
        
        $this->db->insert('judge_assignment_log', [
            'action' => $action,
            'competition_id' => $competitionId,
            'performed_by' => $user['id'],
            'details' => json_encode($details),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function notifyAdminOfDecline($assignment, $reason)
    {
        // Implementation would send notification to administrators
        error_log("Judge assignment declined: Assignment ID {$assignment['id']}, Reason: {$reason}");
    }
}