<?php
// app/Controllers/Judge/ScoringController.php

namespace App\Controllers\Judge;

use App\Controllers\BaseController;
use App\Models\Score;
use App\Models\Team;
use App\Models\RubricTemplate;
use App\Models\LiveScoringSession;
use App\Models\Competition;
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
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            $this->session->setFlash('error', 'Judge profile not found');
            return $this->redirect('/judge/auth');
        }
        
        // Get active scoring sessions for this judge
        $activeSessions = LiveScoringSession::getActiveSessionsForUser($judge['id'], 'judge');
        
        // Get teams assigned to this judge for scoring
        $assignedTeams = $this->getAssignedTeams($judge['id']);
        
        // Get pending scores that need completion
        $pendingScores = $this->getPendingScores($judge['id']);
        
        // Get recent scoring activity
        $recentActivity = $this->getRecentScoringActivity($judge['id']);
        
        $data = [
            'judge' => $judge,
            'active_sessions' => $activeSessions,
            'assigned_teams' => $assignedTeams,
            'pending_scores' => $pendingScores,
            'recent_activity' => $recentActivity,
            'total_teams_today' => count($assignedTeams),
            'completed_today' => $this->getCompletedScoresToday($judge['id']),
            'average_scoring_time' => $this->getAverageScoringTime($judge['id'])
        ];
        
        return $this->view('judge/scoring/index', $data);
    }
    
    public function show($competitionId, $teamId)
    {
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            return $this->redirect('/judge/auth');
        }
        
        // Verify judge is assigned to this competition/team
        if (!$this->verifyJudgeAssignment($judge['id'], $competitionId, $teamId)) {
            $this->session->setFlash('error', 'You are not assigned to score this team');
            return $this->redirect('/judge/scoring');
        }
        
        // Get team details
        $team = $this->getTeamDetails($teamId);
        if (!$team) {
            $this->session->setFlash('error', 'Team not found');
            return $this->redirect('/judge/scoring');
        }
        
        // Get competition details
        $competition = Competition::find($competitionId);
        if (!$competition) {
            $this->session->setFlash('error', 'Competition not found');
            return $this->redirect('/judge/scoring');
        }
        
        // Get or create active scoring session
        $scoringSession = $this->getOrCreateScoringSession($competitionId, $team['category_id']);
        
        // Get rubric for this category
        $rubric = $this->categoryRubricService->getRubricForCategory($team['category_id'], 'final');
        
        // Get existing score if any
        $existingScore = $this->getExistingScore($judge['id'], $teamId, $competitionId);
        
        // Generate category-specific scoring interface
        $scoringInterface = $this->categoryRubricService->generateScoringInterface($team['category_id']);
        
        // Get other judges' scores for comparison (without revealing individual scores)
        $judgeComparison = $this->getJudgeScoreComparison($teamId, $competitionId, $judge['id']);
        
        $data = [
            'judge' => $judge,
            'team' => $team,
            'competition' => $competition,
            'rubric' => $rubric,
            'existing_score' => $existingScore,
            'scoring_interface' => $scoringInterface,
            'scoring_session' => $scoringSession,
            'judge_comparison' => $judgeComparison,
            'websocket_token' => $this->generateWebSocketToken($judge),
            'auto_save_interval' => 30000, // 30 seconds
            'max_scoring_time' => $scoringSession['scoring_duration_minutes'] ?? 45
        ];
        
        return $this->view('judge/scoring/show', $data);
    }
    
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        try {
            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                $input = $_POST;
            }
            
            // Validate required fields
            $required = ['team_id', 'competition_id', 'criteria_scores'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    return $this->json(['success' => false, 'message' => "Missing field: {$field}"], 400);
                }
            }
            
            // Verify judge assignment
            if (!$this->verifyJudgeAssignment($judge['id'], $input['competition_id'], $input['team_id'])) {
                return $this->json(['success' => false, 'message' => 'Not authorized to score this team'], 403);
            }
            
            // Get rubric template
            $team = Team::find($input['team_id']);
            $rubricTemplate = RubricTemplate::where('category_id', $team->category_id)
                                           ->where('is_active', true)
                                           ->first();
            
            if (!$rubricTemplate) {
                return $this->json(['success' => false, 'message' => 'No active rubric found for this category'], 400);
            }
            
            // Prepare score data
            $scoreData = [
                'team_id' => $input['team_id'],
                'competition_id' => $input['competition_id'],
                'rubric_template_id' => $rubricTemplate->id,
                'judge_id' => $judge['id'],
                'criteria_scores' => $input['criteria_scores'],
                'judge_notes' => $input['judge_notes'] ?? '',
                'scoring_duration_minutes' => $input['duration_minutes'] ?? null,
                'status' => $input['status'] ?? 'in_progress',
                'device_info' => [
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'timestamp' => time()
                ]
            ];
            
            // Create or update score
            $score = new Score();
            $result = $score->recordScore($scoreData);
            
            // If this is a final submission, check for conflicts
            if ($input['status'] === 'submitted') {
                $conflicts = $score->checkJudgeConsistency($input['team_id'], $input['competition_id']);
                
                if (!$conflicts['consistent']) {
                    // Log the conflict but still save the score
                    error_log("Score conflict detected for team {$input['team_id']}: " . json_encode($conflicts));
                }
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Score saved successfully',
                'score_id' => $result['id'],
                'total_score' => $result['total_score'],
                'normalized_score' => $result['normalized_score'],
                'status' => $result['scoring_status'],
                'conflicts' => $conflicts ?? null
            ]);
            
        } catch (\Exception $e) {
            error_log("Scoring error: " . $e->getMessage());
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
        
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        try {
            // Find existing score
            $score = Score::find($scoreId);
            if (!$score) {
                return $this->json(['success' => false, 'message' => 'Score not found'], 404);
            }
            
            // Verify ownership
            if ($score['judge_id'] != $judge['id']) {
                return $this->json(['success' => false, 'message' => 'Not authorized to update this score'], 403);
            }
            
            // Check if score is already finalized
            if (in_array($score['scoring_status'], ['final', 'validated'])) {
                return $this->json(['success' => false, 'message' => 'Cannot update finalized score'], 400);
            }
            
            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            // Update score data
            $scoreData = [
                'team_id' => $score['team_id'],
                'competition_id' => $score['competition_id'],
                'rubric_template_id' => $score['rubric_template_id'],
                'judge_id' => $judge['id'],
                'criteria_scores' => $input['criteria_scores'] ?? [],
                'judge_notes' => $input['judge_notes'] ?? $score['judge_notes'],
                'scoring_duration_minutes' => $input['duration_minutes'] ?? $score['scoring_duration_minutes'],
                'status' => $input['status'] ?? $score['scoring_status']
            ];
            
            $scoreModel = new Score();
            $scoreModel->id = $scoreId;
            $result = $scoreModel->recordScore($scoreData);
            
            return $this->json([
                'success' => true,
                'message' => 'Score updated successfully',
                'score' => $result
            ]);
            
        } catch (\Exception $e) {
            error_log("Score update error: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to update score: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function submitScore()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        try {
            $scoreId = $this->input('score_id');
            
            if (!$scoreId) {
                return $this->json(['success' => false, 'message' => 'Score ID required'], 400);
            }
            
            $score = Score::find($scoreId);
            if (!$score || $score['judge_id'] != $judge['id']) {
                return $this->json(['success' => false, 'message' => 'Score not found or unauthorized'], 404);
            }
            
            $scoreModel = new Score();
            $scoreModel->id = $scoreId;
            foreach ($score as $key => $value) {
                $scoreModel->$key = $value;
            }
            
            $result = $scoreModel->submitScore();
            
            return $this->json([
                'success' => true,
                'message' => 'Score submitted successfully',
                'score' => $result
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to submit score: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function getCurrentJudge()
    {
        $judgeId = $_SESSION['judge_id'] ?? null;
        
        if (!$judgeId) {
            return null;
        }
        
        $judge = $this->db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE jp.id = ? AND jp.status = 'active'
        ", [$judgeId]);
        
        return !empty($judge) ? $judge[0] : null;
    }
    
    private function verifyJudgeAssignment($judgeId, $competitionId, $teamId)
    {
        // Check if judge is assigned to this competition
        $assignment = $this->db->query("
            SELECT 1 FROM judge_competition_assignments 
            WHERE judge_id = ? AND competition_id = ?
            AND assignment_status = 'confirmed'
        ", [$judgeId, $competitionId]);
        
        if (empty($assignment)) {
            return false;
        }
        
        // Check if team is in this competition
        $team = $this->db->query("
            SELECT 1 FROM teams 
            WHERE id = ? AND competition_id = ?
        ", [$teamId, $competitionId]);
        
        return !empty($team);
    }
    
    private function getAssignedTeams($judgeId)
    {
        return $this->db->query("
            SELECT t.id, t.name as team_name, s.name as school_name, c.name as competition_name,
                   cat.name as category_name, jca.session_date, jca.session_time,
                   CASE 
                       WHEN EXISTS(SELECT 1 FROM scores sc WHERE sc.team_id = t.id AND sc.judge_id = ?) 
                       THEN 'scored' 
                       ELSE 'pending' 
                   END as scoring_status
            FROM judge_competition_assignments jca
            INNER JOIN competitions c ON jca.competition_id = c.id
            INNER JOIN teams t ON t.competition_id = c.id
            INNER JOIN schools s ON t.school_id = s.id
            INNER JOIN categories cat ON t.category_id = cat.id
            WHERE jca.judge_id = ?
            AND jca.assignment_status = 'confirmed'
            ORDER BY jca.session_date ASC, jca.session_time ASC
        ", [$judgeId, $judgeId]);
    }
    
    private function getPendingScores($judgeId)
    {
        return $this->db->query("
            SELECT s.id, s.team_id, s.total_score, s.scoring_status, s.updated_at,
                   t.name as team_name, sch.name as school_name
            FROM scores s
            INNER JOIN teams t ON s.team_id = t.id
            INNER JOIN schools sch ON t.school_id = sch.id
            WHERE s.judge_id = ?
            AND s.scoring_status IN ('draft', 'in_progress')
            ORDER BY s.updated_at DESC
        ", [$judgeId]);
    }
    
    private function getTeamDetails($teamId)
    {
        $team = $this->db->query("
            SELECT t.*, s.name as school_name, c.name as category_name,
                   comp.name as competition_name
            FROM teams t
            INNER JOIN schools s ON t.school_id = s.id
            INNER JOIN categories c ON t.category_id = c.id
            INNER JOIN competitions comp ON t.competition_id = comp.id
            WHERE t.id = ?
        ", [$teamId]);
        
        return !empty($team) ? $team[0] : null;
    }
    
    private function getOrCreateScoringSession($competitionId, $categoryId)
    {
        // Try to find an active session
        $session = $this->db->query("
            SELECT * FROM live_scoring_sessions 
            WHERE competition_id = ? AND category_id = ?
            AND status IN ('active', 'scheduled')
            ORDER BY start_time DESC
            LIMIT 1
        ", [$competitionId, $categoryId]);
        
        if (!empty($session)) {
            return $session[0];
        }
        
        // Create a new session if none exists
        $sessionData = [
            'competition_id' => $competitionId,
            'category_id' => $categoryId,
            'session_name' => "Scoring Session - " . date('Y-m-d H:i'),
            'session_type' => 'final',
            'start_time' => date('Y-m-d H:i:s'),
            'status' => 'active',
            'scoring_duration_minutes' => 45,
            'conflict_threshold_percent' => 15.00
        ];
        
        $sessionId = $this->db->insert('live_scoring_sessions', $sessionData);
        $sessionData['id'] = $sessionId;
        
        return $sessionData;
    }
    
    private function getExistingScore($judgeId, $teamId, $competitionId)
    {
        $score = $this->db->query("
            SELECT s.*, sd.criteria_id, sd.points_awarded, sd.judge_comment
            FROM scores s
            LEFT JOIN score_details sd ON s.id = sd.score_id
            WHERE s.judge_id = ? AND s.team_id = ? AND s.competition_id = ?
        ", [$judgeId, $teamId, $competitionId]);
        
        if (empty($score)) {
            return null;
        }
        
        // Group score details by criteria
        $scoreData = $score[0];
        $scoreData['criteria_scores'] = [];
        
        foreach ($score as $detail) {
            if ($detail['criteria_id']) {
                $scoreData['criteria_scores'][$detail['criteria_id']] = [
                    'points_awarded' => $detail['points_awarded'],
                    'comment' => $detail['judge_comment']
                ];
            }
        }
        
        return $scoreData;
    }
    
    private function getJudgeScoreComparison($teamId, $competitionId, $excludeJudgeId)
    {
        $scores = $this->db->query("
            SELECT AVG(total_score) as avg_score, COUNT(*) as judge_count,
                   MIN(total_score) as min_score, MAX(total_score) as max_score
            FROM scores 
            WHERE team_id = ? AND competition_id = ? AND judge_id != ?
            AND scoring_status IN ('submitted', 'validated', 'final')
        ", [$teamId, $competitionId, $excludeJudgeId]);
        
        return !empty($scores) ? $scores[0] : null;
    }
    
    private function generateWebSocketToken($judge)
    {
        // Generate a temporary token for WebSocket authentication
        $tokenData = [
            'judge_id' => $judge['id'],
            'user_id' => $judge['user_id'],
            'expires' => time() + 3600, // 1 hour
            'random' => mt_rand(100000, 999999)
        ];
        
        return base64_encode(json_encode($tokenData));
    }
    
    private function getRecentScoringActivity($judgeId)
    {
        return $this->db->query("
            SELECT s.id, s.team_id, s.total_score, s.scoring_status, s.submitted_at,
                   t.name as team_name, c.name as competition_name
            FROM scores s
            INNER JOIN teams t ON s.team_id = t.id
            INNER JOIN competitions c ON t.competition_id = c.id
            WHERE s.judge_id = ?
            ORDER BY s.updated_at DESC
            LIMIT 10
        ", [$judgeId]);
    }
    
    private function getCompletedScoresToday($judgeId)
    {
        $today = date('Y-m-d');
        $count = $this->db->query("
            SELECT COUNT(*) as count
            FROM scores
            WHERE judge_id = ? 
            AND DATE(submitted_at) = ?
            AND scoring_status IN ('submitted', 'validated', 'final')
        ", [$judgeId, $today]);
        
        return $count[0]['count'] ?? 0;
    }
    
    private function getAverageScoringTime($judgeId)
    {
        $avg = $this->db->query("
            SELECT AVG(scoring_duration_minutes) as avg_time
            FROM scores
            WHERE judge_id = ? 
            AND scoring_duration_minutes IS NOT NULL
            AND scoring_duration_minutes > 0
        ", [$judgeId]);
        
        return round($avg[0]['avg_time'] ?? 0, 1);
    }
}