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
                'title' => 'Competition Scoring System',
                'pageTitle' => 'Scoring Management',
                'pageSubtitle' => 'Admin access to competition scoring system',
                'error_message' => 'Some data could not be loaded. Please check the system logs.'
            ];

            return $this->view('admin/scoring/index', $data);
        }
    }

    public function show($competitionId, $teamId)
    {
        // Admin can access any team/competition without assignment checks

        // Get team details
        $team = $this->getTeamDetails($teamId);
        if (!$team) {
            $this->session->setFlash('error', 'Team not found');
            return $this->redirect('/admin/scoring');
        }

        // Get competition details
        $competition = Competition::find($competitionId);
        if (!$competition) {
            $this->session->setFlash('error', 'Competition not found');
            return $this->redirect('/admin/scoring');
        }

        // Get or create active scoring session
        $scoringSession = $this->getOrCreateScoringSession($competitionId, $team['category_id']);

        // Get rubric for this category
        $rubric = $this->categoryRubricService->getRubricForCategory($team['category_id'], 'final');

        // Get all scores for this team (admin can see all judges' scores)
        $allScores = $this->getAllScoresForTeam($teamId, $competitionId);

        // Generate category-specific scoring interface
        $scoringInterface = $this->categoryRubricService->generateScoringInterface($team['category_id']);

        // Get judge comparison data (admin sees all)
        $judgeComparison = $this->getFullJudgeScoreComparison($teamId, $competitionId);

        $data = [
            'team' => $team,
            'competition' => $competition,
            'rubric' => $rubric,
            'scoring_session' => $scoringSession,
            'scoring_interface' => $scoringInterface,
            'all_scores' => $allScores,
            'judge_comparison' => $judgeComparison,
            'can_edit_all_scores' => true, // Admin privilege
            'title' => 'Score Team: ' . $team['name'],
            'pageTitle' => 'Team Scoring',
            'pageSubtitle' => $team['name'] . ' - ' . $competition['name']
        ];

        return $this->view('admin/scoring/show', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }

        // Admin can create scores on behalf of any judge or as system admin

        try {
            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                parse_str(file_get_contents('php://input'), $input);
            }

            // Validate required fields
            $requiredFields = ['team_id', 'competition_id', 'category_id'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    return $this->json(['success' => false, 'message' => "Missing required field: {$field}"], 400);
                }
            }

            // Use current admin as judge_id or specified judge
            $judgeId = $input['judge_id'] ?? $this->getCurrentUser()['id'];

            // Create score record (matching actual scores table structure)
            $scoreData = [
                'judge_id' => $judgeId,
                'team_id' => $input['team_id'],
                'competition_id' => $input['competition_id'],
                'rubric_template_id' => $input['rubric_template_id'] ?? 1, // Default rubric
                'total_score' => $input['total_score'] ?? 0,
                'game_challenge_score' => $input['game_challenge_score'] ?? 0,
                'research_challenge_score' => $input['research_challenge_score'] ?? 0,
                'bonus_points' => $input['bonus_points'] ?? 0,
                'penalty_points' => $input['penalty_points'] ?? 0,
                'judge_notes' => $input['judge_notes'] ?? '',
                'scoring_status' => $input['scoring_status'] ?? 'draft',
                'device_info' => json_encode(['browser' => 'admin_interface']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $scoreId = Score::create($scoreData);

            return $this->json([
                'success' => true,
                'message' => 'Score saved successfully',
                'score_id' => $scoreId,
                'redirect' => '/admin/scoring'
            ]);

        } catch (\Exception $e) {
            error_log("Admin scoring error: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to save score: ' . $e->getMessage()
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
                             jp.judge_code, jp.specializations, jp.certification_level
                      FROM users u
                      LEFT JOIN judge_assignments ja ON (
                          u.id = ja.judge_id AND
                          ja.status IN ('assigned', 'active')
                      )
                      LEFT JOIN judge_profiles jp ON u.id = jp.user_id
                      WHERE u.role = 'judge' AND u.status = 'active'
                      GROUP BY u.id
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
}