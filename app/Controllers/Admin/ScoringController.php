<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Score;
use App\Models\Team;
use App\Models\RubricTemplate;
use App\Models\LiveScoringSession;
use App\Models\Competition;
use App\Models\User;
use App\Services\CategoryRubricService;

class ScoringController extends BaseController
{
    private $categoryRubricService;

    public function __construct()
    {
        parent::__construct();
        $this->categoryRubricService = new CategoryRubricService();
    }

    public function index()
    {
        try {
            // Get all active competitions for admin overview
            $competitions = $this->getActiveCompetitions();

            // Get all teams across all competitions (admin has access to all)
            $allTeams = $this->getAllTeams();

            // Get all active scoring sessions
            $activeSessions = LiveScoringSession::getActiveSessions();

            // Get recent scoring activity system-wide
            $recentActivity = $this->getAllRecentScoringActivity();

            // Get scoring statistics for admin dashboard
            $scoringStats = $this->getScoringStatistics();

            // Get judge assignment data
            $judgeAssignments = $this->getJudgeAssignments();
            $availableJudges = $this->getAvailableJudges();
            $unassignedSessions = $this->getUnassignedSessions();

            $data = [
                'competitions' => $competitions,
                'teams' => $allTeams,
                'active_sessions' => $activeSessions,
                'recent_activity' => $recentActivity,
                'scoring_stats' => $scoringStats,
                'total_teams' => count($allTeams),
                'pending_scores' => $this->getPendingScoresSystemWide(),
                'judge_assignments' => $judgeAssignments,
                'available_judges' => $availableJudges,
                'unassigned_sessions' => $unassignedSessions,
                'title' => 'Competition Scoring System',
                'pageTitle' => 'Scoring Management',
                'pageSubtitle' => 'Admin access to competition scoring system'
            ];

            return $this->view('admin/scoring/index', $data);

        } catch (\Exception $e) {
            error_log("Admin scoring index error: " . $e->getMessage());

            // Provide fallback data to prevent complete failure
            $data = [
                'competitions' => [],
                'teams' => [],
                'active_sessions' => [],
                'recent_activity' => [],
                'scoring_stats' => ['today_scores' => 0, 'pending_scores' => 0, 'completed_scores' => 0],
                'total_teams' => 0,
                'pending_scores' => [],
                'judge_assignments' => [],
                'available_judges' => [],
                'unassigned_sessions' => [],
                'title' => 'Competition Scoring System',
                'pageTitle' => 'Scoring Management',
                'pageSubtitle' => 'Admin access to competition scoring system',
                'error_message' => 'Some data could not be loaded. Please check the system logs.'
            ];

            return $this->view('admin/scoring/index', $data);
        }
    }

    public function show($competitionId, $teamId, $judgingMode = null)
    {
        // Admin can access any team/competition without assignment checks

        // Get team details
        $team = $this->getTeamDetails($teamId);
        if (!$team) {
            $this->flash('error', 'Team not found');
            return $this->redirect('/admin/scoring');
        }

        // Get competition details
        $competition = Competition::find($competitionId);
        if (!$competition) {
            $this->flash('error', 'Competition not found');
            return $this->redirect('/admin/scoring');
        }

        // Determine judging mode - check URL parameter or session configuration
        if (!$judgingMode) {
            $judgingMode = $this->input('mode') ?? 'presentation'; // Default to presentation
        }

        // Validate judging mode
        $validModes = ['presentation', 'gameplay', 'hybrid'];
        if (!in_array($judgingMode, $validModes)) {
            $this->flash('error', 'Invalid judging mode specified');
            return $this->redirect('/admin/scoring');
        }

        // Get or create active scoring session with judging mode
        $scoringSession = $this->getOrCreateDualModeScoringSession($competitionId, $team['category_id'], $judgingMode);

        // Get appropriate rubric and interface based on judging mode
        $scoringInterface = null;
        $rubric = null;

        switch ($judgingMode) {
            case 'presentation':
                $rubric = $this->categoryRubricService->getPresentationRubric($team['category_id']);
                $scoringInterface = $this->categoryRubricService->generatePresentationInterface($team['category_id']);
                break;

            case 'gameplay':
                $rubric = $this->categoryRubricService->getGameplayRubric($team['category_id']);
                $scoringInterface = $this->categoryRubricService->generateGameplayInterface($team['category_id']);
                break;

            case 'hybrid':
                // For hybrid mode, we might show both interfaces or a combined one
                $rubric = [
                    'presentation' => $this->categoryRubricService->getPresentationRubric($team['category_id']),
                    'gameplay' => $this->categoryRubricService->getGameplayRubric($team['category_id'])
                ];
                $scoringInterface = $this->generateHybridInterface($team['category_id']);
                break;
        }

        // Get current judge information
        $judge = $this->getCurrentUser();

        // Get existing scores for this team with current judging mode
        $existingScores = $this->getExistingScoresForTeamAndMode($teamId, $competitionId, $judgingMode);

        // Get gameplay runs if in gameplay mode
        $gameplayRuns = [];
        if ($judgingMode === 'gameplay' || $judgingMode === 'hybrid') {
            $gameplayRuns = $this->getGameplayRuns($teamId, $scoringSession['id']);
        }

        // Prepare data for the view
        $data = [
            'team' => $team,
            'competition' => $competition,
            'judge' => $judge,
            'rubric' => $rubric,
            'judging_mode' => $judgingMode,
            'scoring_session' => $scoringSession,
            'scoring_interface' => $scoringInterface,
            'existing_scores' => $existingScores,
            'gameplay_runs' => $gameplayRuns,
            'can_edit_all_scores' => true, // Admin privilege
            'title' => ucfirst($judgingMode) . ' Judging: ' . $team['name'],
            'pageTitle' => ucfirst($judgingMode) . ' Judging',
            'pageSubtitle' => $team['name'] . ' - ' . $competition['name']
        ];

        // Route to appropriate view based on judging mode
        switch ($judgingMode) {
            case 'presentation':
                return $this->view('admin/scoring/presentation_interface', $data);

            case 'gameplay':
                return $this->view('admin/scoring/gameplay_interface', $data);

            case 'hybrid':
                return $this->view('admin/scoring/hybrid_interface', $data);

            default:
                return $this->view('admin/scoring/show', $data);
        }
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        try {
            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                parse_str(file_get_contents('php://input'), $input);
            }

            // Validate required fields
            $requiredFields = ['team_id', 'competition_id', 'judging_mode', 'scoring_data'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    return $this->json(['success' => false, 'message' => "Missing required field: {$field}"], 400);
                }
            }

            $judgeId = $this->getCurrentUser()['id'];
            $teamId = $input['team_id'];
            $competitionId = $input['competition_id'];
            $judgingMode = $input['judging_mode'];
            $scoringData = $input['scoring_data'];

            // Process scores based on judging mode
            switch ($judgingMode) {
                case 'presentation':
                    $result = $this->storePresentationScores($judgeId, $teamId, $competitionId, $scoringData, $input);
                    break;

                case 'gameplay':
                    $result = $this->storeGameplayResults($judgeId, $teamId, $competitionId, $scoringData, $input);
                    break;

                case 'hybrid':
                    $result = $this->storeHybridScores($judgeId, $teamId, $competitionId, $scoringData, $input);
                    break;

                default:
                    return $this->json(['success' => false, 'message' => 'Invalid judging mode'], 400);
            }

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => ucfirst($judgingMode) . ' scores submitted successfully',
                    'score_id' => $result['score_id'],
                    'redirect_url' => '/admin/scoring'
                ]);
            } else {
                return $this->json($result, 400);
            }

        } catch (\Exception $e) {
            error_log("Admin scoring submission error: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to submit scores: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update($scoreId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        // Admin can update any score (override normal restrictions)

        try {
            // Find existing score
            $score = Score::find($scoreId);
            if (!$score) {
                return $this->json(['success' => false, 'message' => 'Score not found'], 404);
            }

            // Admin can update even finalized scores (override restriction)

            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                parse_str(file_get_contents('php://input'), $input);
            }

            // Update score data (matching actual scores table structure)
            $scoreData = [
                'team_id' => $score['team_id'],
                'competition_id' => $score['competition_id'],
                'rubric_template_id' => $input['rubric_template_id'] ?? $score['rubric_template_id'],
                'total_score' => $input['total_score'] ?? $score['total_score'],
                'game_challenge_score' => $input['game_challenge_score'] ?? $score['game_challenge_score'],
                'research_challenge_score' => $input['research_challenge_score'] ?? $score['research_challenge_score'],
                'bonus_points' => $input['bonus_points'] ?? $score['bonus_points'],
                'penalty_points' => $input['penalty_points'] ?? $score['penalty_points'],
                'judge_notes' => $input['judge_notes'] ?? $score['judge_notes'],
                'scoring_status' => $input['scoring_status'] ?? $score['scoring_status'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = Score::update($scoreId, $scoreData);

            if ($updated) {
                return $this->json([
                    'success' => true,
                    'message' => 'Score updated successfully',
                    'redirect' => '/admin/scoring'
                ]);
            } else {
                return $this->json(['success' => false, 'message' => 'Failed to update score'], 500);
            }

        } catch (\Exception $e) {
            error_log("Admin score update error: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to update score: ' . $e->getMessage()
            ], 500);
        }
    }

    // Admin-specific helper methods

    private function getActiveCompetitions()
    {
        try {
            $query = "SELECT c.*,
                             COUNT(DISTINCT t.id) as team_count,
                             COUNT(DISTINCT s.id) as score_count
                      FROM competitions c
                      LEFT JOIN teams t ON c.id = t.competition_id
                      LEFT JOIN scores s ON c.id = s.competition_id
                      WHERE c.status IN ('open_registration', 'registration_closed', 'in_progress')
                      GROUP BY c.id
                      ORDER BY c.date DESC";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting active competitions: " . $e->getMessage());
            return [];
        }
    }

    private function getAllTeams()
    {
        try {
            $query = "SELECT t.*,
                             sch.name as school_name,
                             c.name as competition_name,
                             cat.name as category_name,
                             COUNT(s.id) as score_count,
                             AVG(s.total_score) as average_score
                      FROM teams t
                      LEFT JOIN schools sch ON t.school_id = sch.id
                      LEFT JOIN competitions c ON t.competition_id = c.id
                      LEFT JOIN categories cat ON t.category_id = cat.id
                      LEFT JOIN scores s ON t.id = s.team_id
                      WHERE t.status IN ('approved', 'competing', 'registered')
                      GROUP BY t.id
                      ORDER BY c.name, cat.name, t.name";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting all teams: " . $e->getMessage());
            return [];
        }
    }

    private function getAllRecentScoringActivity()
    {
        try {
            $query = "SELECT s.*,
                             t.name as team_name,
                             c.name as competition_name,
                             CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                             cat.name as category_name
                      FROM scores s
                      JOIN teams t ON s.team_id = t.id
                      LEFT JOIN competitions c ON s.competition_id = c.id
                      JOIN users u ON s.judge_id = u.id
                      LEFT JOIN categories cat ON t.category_id = cat.id
                      WHERE DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      ORDER BY s.created_at DESC
                      LIMIT 20";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting recent scoring activity: " . $e->getMessage());
            return [];
        }
    }

    private function getScoringStatistics()
    {
        $stats = [];

        try {
            // Total scores today
            $todayResult = $this->db->query(
                "SELECT COUNT(*) as count FROM scores WHERE DATE(created_at) = CURDATE()"
            );
            $stats['today_scores'] = ($todayResult[0]['count'] ?? 0);

            // Average scoring time (if tracked)
            $stats['avg_scoring_time'] = '15 minutes'; // Placeholder

            // Pending scores
            $pendingResult = $this->db->query(
                "SELECT COUNT(*) as count FROM scores WHERE scoring_status IN ('draft', 'in_progress')"
            );
            $stats['pending_scores'] = ($pendingResult[0]['count'] ?? 0);

            // Completed scores
            $completedResult = $this->db->query(
                "SELECT COUNT(*) as count FROM scores WHERE scoring_status IN ('final', 'validated')"
            );
            $stats['completed_scores'] = ($completedResult[0]['count'] ?? 0);

        } catch (\Exception $e) {
            error_log("Error getting scoring statistics: " . $e->getMessage());
            $stats = [
                'today_scores' => 0,
                'avg_scoring_time' => 'N/A',
                'pending_scores' => 0,
                'completed_scores' => 0
            ];
        }

        return $stats;
    }

    private function getPendingScoresSystemWide()
    {
        try {
            $query = "SELECT s.*,
                             t.name as team_name,
                             c.name as competition_name,
                             CONCAT(u.first_name, ' ', u.last_name) as judge_name
                      FROM scores s
                      JOIN teams t ON s.team_id = t.id
                      LEFT JOIN competitions c ON s.competition_id = c.id
                      JOIN users u ON s.judge_id = u.id
                      WHERE s.scoring_status IN ('draft', 'in_progress')
                      ORDER BY s.updated_at ASC";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting pending scores: " . $e->getMessage());
            return [];
        }
    }

    private function getTeamDetails($teamId)
    {
        $query = "SELECT t.*,
                         sch.name as school_name,
                         c.name as competition_name,
                         cat.name as category_name
                  FROM teams t
                  LEFT JOIN schools sch ON t.school_id = sch.id
                  LEFT JOIN competitions c ON t.competition_id = c.id
                  LEFT JOIN categories cat ON t.category_id = cat.id
                  WHERE t.id = ?";

        return $this->db->queryOne($query, [$teamId]);
    }

    private function getOrCreateScoringSession($competitionId, $categoryId)
    {
        // For admin, we can create system-wide scoring sessions
        return LiveScoringSession::getOrCreateSession($competitionId, $categoryId, 'admin');
    }

    private function getAllScoresForTeam($teamId, $competitionId)
    {
        try {
            $query = "SELECT s.*,
                             CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                             u.role as judge_role
                      FROM scores s
                      JOIN users u ON s.judge_id = u.id
                      WHERE s.team_id = ? AND (s.competition_id = ? OR ? IS NULL)
                      ORDER BY s.created_at DESC";

            return $this->db->query($query, [$teamId, $competitionId, $competitionId]);
        } catch (\Exception $e) {
            error_log("Error getting scores for team: " . $e->getMessage());
            return [];
        }
    }

    private function getFullJudgeScoreComparison($teamId, $competitionId)
    {
        try {
            // Admin gets full comparison data (no privacy restrictions)
            $query = "SELECT s.judge_id,
                             CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                             s.total_score,
                             s.game_challenge_score,
                             s.research_challenge_score,
                             s.bonus_points,
                             s.penalty_points,
                             s.scoring_status,
                             s.created_at
                      FROM scores s
                      JOIN users u ON s.judge_id = u.id
                      WHERE s.team_id = ? AND (s.competition_id = ? OR ? IS NULL)
                      ORDER BY s.created_at DESC";

            return $this->db->query($query, [$teamId, $competitionId, $competitionId]);
        } catch (\Exception $e) {
            error_log("Error getting judge score comparison: " . $e->getMessage());
            return [];
        }
    }

    // Judge Assignment Methods

    private function getJudgeAssignments()
    {
        try {
            $query = "SELECT ja.*,
                             CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                             u.email as judge_email,
                             c.name as competition_name,
                             cat.name as category_name,
                             lss.session_name,
                             lss.start_time,
                             lss.status as session_status
                      FROM judge_assignments ja
                      JOIN users u ON ja.judge_id = u.id
                      LEFT JOIN competitions c ON ja.competition_id = c.id
                      LEFT JOIN categories cat ON ja.category_id = cat.id
                      LEFT JOIN live_scoring_sessions lss ON (
                          lss.competition_id = ja.competition_id AND
                          lss.category_id = ja.category_id
                      )
                      WHERE ja.status IN ('assigned', 'active')
                      ORDER BY ja.created_at DESC
                      LIMIT 20";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting judge assignments: " . $e->getMessage());
            return [];
        }
    }

    private function getAvailableJudges()
    {
        try {
            $query = "SELECT u.id, u.first_name, u.last_name, u.email,
                             u.phone, u.status,
                             COUNT(ja.id) as current_assignments,
                             jp.judge_code, jp.expertise_areas, jp.experience_level
                      FROM users u
                      LEFT JOIN judge_assignments ja ON (
                          u.id = ja.judge_id AND
                          ja.status IN ('assigned', 'active')
                      )
                      LEFT JOIN judge_profiles jp ON u.id = jp.user_id
                      WHERE u.role = 'judge' AND u.status = 'active'
                      GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.status, jp.judge_code, jp.expertise_areas, jp.experience_level
                      ORDER BY current_assignments ASC, u.first_name ASC";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting available judges: " . $e->getMessage());
            return [];
        }
    }

    private function getUnassignedSessions()
    {
        try {
            $query = "SELECT lss.*,
                             c.name as competition_name,
                             cat.name as category_name,
                             COUNT(ja.id) as assigned_judges_count
                      FROM live_scoring_sessions lss
                      JOIN competitions c ON lss.competition_id = c.id
                      LEFT JOIN categories cat ON lss.category_id = cat.id
                      LEFT JOIN judge_assignments ja ON (
                          ja.competition_id = lss.competition_id AND
                          ja.category_id = lss.category_id AND
                          ja.status IN ('assigned', 'active')
                      )
                      WHERE lss.status IN ('scheduled', 'active')
                      GROUP BY lss.id
                      HAVING assigned_judges_count < lss.max_concurrent_judges
                      ORDER BY lss.start_time ASC";

            return $this->db->query($query);
        } catch (\Exception $e) {
            error_log("Error getting unassigned sessions: " . $e->getMessage());
            return [];
        }
    }

    public function assignJudgeToSession()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                parse_str(file_get_contents('php://input'), $input);
            }

            // Validate required fields
            $requiredFields = ['judge_id', 'competition_id', 'category_id'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    return $this->json(['success' => false, 'message' => "Missing required field: {$field}"], 400);
                }
            }

            // Check if judge is already assigned to this competition/category
            $existingAssignment = $this->db->query(
                "SELECT id FROM judge_assignments
                 WHERE judge_id = ? AND competition_id = ? AND category_id = ? AND status IN ('assigned', 'active')",
                [$input['judge_id'], $input['competition_id'], $input['category_id']]
            );

            if (!empty($existingAssignment)) {
                return $this->json(['success' => false, 'message' => 'Judge is already assigned to this competition/category'], 400);
            }

            // Create the assignment
            $assignmentData = [
                'judge_id' => $input['judge_id'],
                'competition_id' => $input['competition_id'],
                'category_id' => $input['category_id'],
                'judge_type' => $input['judge_type'] ?? 'primary',
                'table_number' => $input['table_number'] ?? null,
                'phase' => $input['phase'] ?? 'preliminary',
                'status' => 'assigned',
                'assigned_by' => $this->getCurrentUser()['id'],
                'special_instructions' => $input['special_instructions'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $assignmentId = $this->db->query(
                "INSERT INTO judge_assignments (judge_id, competition_id, category_id, judge_type, table_number, phase, status, assigned_by, special_instructions, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array_values($assignmentData)
            );

            return $this->json([
                'success' => true,
                'message' => 'Judge assigned successfully',
                'assignment_id' => $assignmentId
            ]);

        } catch (\Exception $e) {
            error_log("Error assigning judge: " . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to assign judge'], 500);
        }
    }

    public function removeJudgeAssignment($assignmentId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        try {
            // Check if assignment exists
            $assignment = $this->db->query("SELECT * FROM judge_assignments WHERE id = ?", [$assignmentId]);
            if (empty($assignment)) {
                return $this->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            // Update assignment status to 'replaced' instead of deleting
            $updated = $this->db->query(
                "UPDATE judge_assignments SET status = 'replaced', updated_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $assignmentId]
            );

            if ($updated) {
                return $this->json(['success' => true, 'message' => 'Judge assignment removed successfully']);
            } else {
                return $this->json(['success' => false, 'message' => 'Failed to remove assignment'], 500);
            }

        } catch (\Exception $e) {
            error_log("Error removing judge assignment: " . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to remove assignment'], 500);
        }
    }

    public function getJudgeAvailability($judgeId)
    {
        try {
            $query = "SELECT ja.*,
                             c.name as competition_name,
                             c.date as competition_date,
                             lss.start_time, lss.end_time,
                             lss.session_name
                      FROM judge_assignments ja
                      JOIN competitions c ON ja.competition_id = c.id
                      LEFT JOIN live_scoring_sessions lss ON (
                          lss.competition_id = ja.competition_id AND
                          lss.category_id = ja.category_id
                      )
                      WHERE ja.judge_id = ? AND ja.status IN ('assigned', 'active')
                      ORDER BY c.date ASC, lss.start_time ASC";

            return $this->db->query($query, [$judgeId]);
        } catch (\Exception $e) {
            error_log("Error getting judge availability: " . $e->getMessage());
            return [];
        }
    }

    public function getCompetitionCategories($competitionId)
    {
        header('Content-Type: application/json');

        try {
            $query = "SELECT c.* FROM categories c
                      WHERE c.competition_id = ? OR c.competition_id IS NULL
                      ORDER BY c.name ASC";

            $categories = $this->db->query($query, [$competitionId]);

            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            error_log("Error getting competition categories: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error loading categories: ' . $e->getMessage(),
                'categories' => []
            ]);
        }
        exit;
    }

    // ========================================
    // Dual Judging System Helper Methods
    // ========================================

    /**
     * Get or create a scoring session that supports dual judging modes
     */
    private function getOrCreateDualModeScoringSession($competitionId, $categoryId, $judgingMode)
    {
        try {
            // Look for existing session with matching judging mode
            $query = "SELECT * FROM live_scoring_sessions
                      WHERE competition_id = ? AND category_id = ? AND judging_mode = ? AND status = 'active'
                      ORDER BY created_at DESC LIMIT 1";

            $existingSession = $this->db->query($query, [$competitionId, $categoryId, $judgingMode]);

            if (!empty($existingSession)) {
                return $existingSession[0];
            }

            // Create new session with judging mode
            $sessionData = [
                'name' => "Auto-generated {$judgingMode} session - " . date('Y-m-d H:i:s'),
                'competition_id' => $competitionId,
                'category_id' => $categoryId,
                'judging_mode' => $judgingMode,
                'status' => 'active',
                'start_time' => date('Y-m-d H:i:s'),
                'max_presentation_time_minutes' => $judgingMode === 'presentation' ? 10 : null,
                'max_gameplay_runs' => $judgingMode === 'gameplay' ? 3 : null,
                'auto_select_fastest_run' => $judgingMode === 'gameplay' ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $sessionId = LiveScoringSession::create($sessionData);

            return $this->db->query("SELECT * FROM live_scoring_sessions WHERE id = ?", [$sessionId])[0];

        } catch (\Exception $e) {
            error_log("Error creating dual mode scoring session: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get existing scores for a team filtered by judging mode
     */
    private function getExistingScoresForTeamAndMode($teamId, $competitionId, $judgingMode)
    {
        try {
            $query = "SELECT s.*, u.name as judge_name
                      FROM scores s
                      LEFT JOIN users u ON s.judge_id = u.id
                      WHERE s.team_id = ? AND s.competition_id = ? AND s.judging_mode = ?
                      ORDER BY s.created_at DESC";

            return $this->db->query($query, [$teamId, $competitionId, $judgingMode]);
        } catch (\Exception $e) {
            error_log("Error getting existing scores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get gameplay runs for a team and session
     */
    private function getGameplayRuns($teamId, $sessionId)
    {
        try {
            $query = "SELECT * FROM gameplay_runs
                      WHERE team_id = ? AND session_id = ?
                      ORDER BY run_number ASC";

            return $this->db->query($query, [$teamId, $sessionId]);
        } catch (\Exception $e) {
            error_log("Error getting gameplay runs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Store presentation scores
     */
    private function storePresentationScores($judgeId, $teamId, $competitionId, $scoringData, $input)
    {
        try {
            // Extract presentation scoring data
            $presentationScores = $scoringData['presentation_scores'] ?? [];
            $totalScore = $scoringData['total_score'] ?? 0;
            $presentationDuration = $scoringData['presentation_duration_minutes'] ?? 0;
            $sectionNotes = $scoringData['section_notes'] ?? [];

            // Create main score record with dual judging extensions
            $scoreData = [
                'judge_id' => $judgeId,
                'team_id' => $teamId,
                'competition_id' => $competitionId,
                'judging_mode' => 'presentation',
                'total_score' => $totalScore,
                'presentation_breakdown' => json_encode($presentationScores),
                'presentation_duration_minutes' => $presentationDuration,
                'problem_research_score' => $presentationScores['problem_research'] ?? 0,
                'robot_presentation_score' => $presentationScores['robot_presentation'] ?? 0,
                'model_presentation_score' => $presentationScores['model_presentation'] ?? 0,
                'communication_skills_score' => $presentationScores['communication_skills'] ?? 0,
                'teamwork_collaboration_score' => $presentationScores['teamwork_collaboration'] ?? 0,
                'judge_notes' => json_encode($sectionNotes),
                'scoring_status' => 'completed',
                'device_info' => json_encode(['interface' => 'presentation', 'browser' => 'admin']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $scoreId = Score::create($scoreData);

            return ['success' => true, 'score_id' => $scoreId];

        } catch (\Exception $e) {
            error_log("Error storing presentation scores: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to store presentation scores: ' . $e->getMessage()];
        }
    }

    /**
     * Store gameplay results including runs data
     */
    private function storeGameplayResults($judgeId, $teamId, $competitionId, $scoringData, $input)
    {
        try {
            $runsData = $scoringData['runs_data'] ?? [];
            $bestRunNumber = $scoringData['best_run_number'] ?? null;
            $bestRunTime = $scoringData['best_run_time_seconds'] ?? null;
            $finalScore = $scoringData['final_score'] ?? 0;
            $technicalNotes = $input['technical_notes'] ?? '';

            // Get session ID
            $sessionId = $input['session_id'] ?? null;
            if (!$sessionId) {
                // Try to find or create session
                $session = $this->getOrCreateDualModeScoringSession($competitionId, null, 'gameplay');
                $sessionId = $session['id'];
            }

            // Store individual gameplay runs
            $bestRunId = null;
            foreach ($runsData as $runNumber => $runData) {
                if ($runData['status'] === 'completed') {
                    $runRecord = [
                        'team_id' => $teamId,
                        'session_id' => $sessionId,
                        'judge_id' => $judgeId,
                        'run_number' => $runNumber,
                        'start_time' => date('Y-m-d H:i:s'),
                        'end_time' => date('Y-m-d H:i:s'),
                        'completion_time_seconds' => floor($runData['time'] / 1000),
                        'run_status' => 'completed',
                        'mission_completion_data' => json_encode($runData['missions']),
                        'is_fastest_run' => ($runNumber == $bestRunNumber) ? 1 : 0,
                        'mission_score' => $runData['score'],
                        'total_run_score' => $runData['score'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $runId = $this->db->insert('gameplay_runs', $runRecord);

                    if ($runNumber == $bestRunNumber) {
                        $bestRunId = $runId;
                    }
                }
            }

            // Create main score record
            $scoreData = [
                'judge_id' => $judgeId,
                'team_id' => $teamId,
                'competition_id' => $competitionId,
                'judging_mode' => 'gameplay',
                'total_score' => $finalScore,
                'gameplay_breakdown' => json_encode($runsData),
                'best_gameplay_run_id' => $bestRunId,
                'fastest_run_time_seconds' => $bestRunTime,
                'mission_completion_percentage' => $this->calculateMissionCompletionPercentage($runsData, $bestRunNumber),
                'judge_notes' => $technicalNotes,
                'scoring_status' => 'completed',
                'device_info' => json_encode(['interface' => 'gameplay', 'browser' => 'admin']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $scoreId = Score::create($scoreData);

            return ['success' => true, 'score_id' => $scoreId];

        } catch (\Exception $e) {
            error_log("Error storing gameplay results: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to store gameplay results: ' . $e->getMessage()];
        }
    }

    /**
     * Store hybrid scores (both presentation and gameplay)
     */
    private function storeHybridScores($judgeId, $teamId, $competitionId, $scoringData, $input)
    {
        try {
            // This is a placeholder for hybrid scoring
            // Could store both presentation and gameplay data in separate score records
            // or combine them into a single record with both breakdowns

            $totalScore = $scoringData['total_score'] ?? 0;

            $scoreData = [
                'judge_id' => $judgeId,
                'team_id' => $teamId,
                'competition_id' => $competitionId,
                'judging_mode' => 'hybrid',
                'total_score' => $totalScore,
                'presentation_breakdown' => json_encode($scoringData['presentation_data'] ?? []),
                'gameplay_breakdown' => json_encode($scoringData['gameplay_data'] ?? []),
                'judge_notes' => $input['notes'] ?? '',
                'scoring_status' => 'completed',
                'device_info' => json_encode(['interface' => 'hybrid', 'browser' => 'admin']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $scoreId = Score::create($scoreData);

            return ['success' => true, 'score_id' => $scoreId];

        } catch (\Exception $e) {
            error_log("Error storing hybrid scores: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to store hybrid scores: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate mission completion percentage for gameplay judging
     */
    private function calculateMissionCompletionPercentage($runsData, $bestRunNumber)
    {
        if (!$bestRunNumber || !isset($runsData[$bestRunNumber])) {
            return 0;
        }

        $bestRun = $runsData[$bestRunNumber];
        $missions = $bestRun['missions'] ?? [];

        $totalMissions = count($missions);
        $completedMissions = count(array_filter($missions, function($points) {
            return $points > 0;
        }));

        return $totalMissions > 0 ? round(($completedMissions / $totalMissions) * 100, 2) : 0;
    }

    /**
     * Generate hybrid interface (placeholder)
     */
    private function generateHybridInterface($categoryId)
    {
        // For now, return a simple combined interface
        // This could be expanded to show both presentation and gameplay interfaces
        return [
            'html' => '<div class="hybrid-interface"><p>Hybrid judging interface coming soon...</p></div>',
            'javascript' => '// Hybrid interface JS',
            'css' => '// Hybrid interface CSS'
        ];
    }
}