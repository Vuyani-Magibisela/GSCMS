<?php
// app/Controllers/ScoreboardController.php

namespace App\Controllers;

use App\Services\LiveScoreboardService;
use App\Models\LiveScoringSession;

class ScoreboardController extends BaseController
{
    private $scoreboardService;
    
    public function __construct()
    {
        parent::__construct();
        $this->scoreboardService = new LiveScoreboardService();
    }
    
    public function index()
    {
        // Show list of available scoreboards
        $activeSessions = $this->getActiveSessions();
        
        $data = [
            'active_sessions' => $activeSessions,
            'page_title' => 'Live Scoreboards - SciBOTICS 2025'
        ];
        
        return $this->view('public/scoreboard/index', $data);
    }
    
    public function show($sessionId)
    {
        $displayMode = $this->request->input('mode', 'standard');
        
        // Validate session
        $session = LiveScoringSession::find($sessionId);
        if (!$session) {
            $this->session->setFlash('error', 'Scoreboard not found');
            return $this->redirect('/scoreboard');
        }
        
        // Check if session is public
        if (!$this->isSessionPublic($session)) {
            $this->session->setFlash('error', 'This scoreboard is not publicly available');
            return $this->redirect('/scoreboard');
        }
        
        // Get scoreboard data
        try {
            $scoreboard = $this->scoreboardService->getPublicScoreboard($sessionId, $displayMode);
            
            if (isset($scoreboard['error'])) {
                $this->session->setFlash('error', $scoreboard['error']);
                return $this->redirect('/scoreboard');
            }
        } catch (\Exception $e) {
            error_log('Scoreboard error: ' . $e->getMessage());
            $this->session->setFlash('error', 'Failed to load scoreboard data');
            return $this->redirect('/scoreboard');
        }
        
        $data = [
            'session' => $session,
            'scoreboard' => $scoreboard,
            'display_mode' => $displayMode,
            'websocket_url' => $this->getWebSocketURL($sessionId),
            'qr_code' => $this->scoreboardService->generateQRCode($sessionId),
            'page_title' => $scoreboard['session']['name'] . ' - Live Scoreboard'
        ];
        
        // Choose view based on display mode
        switch ($displayMode) {
            case 'tv':
                return $this->view('public/scoreboard/tv', $data);
            case 'mobile':
                return $this->view('public/scoreboard/mobile', $data);
            default:
                return $this->view('public/scoreboard/standard', $data);
        }
    }
    
    public function api($sessionId)
    {
        // API endpoint for getting scoreboard data
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        
        $displayMode = $this->request->input('mode', 'standard');
        $includeHistory = $this->request->input('history', false);
        
        try {
            $session = LiveScoringSession::find($sessionId);
            if (!$session || !$this->isSessionPublic($session)) {
                http_response_code(404);
                echo json_encode(['error' => 'Session not found or not public']);
                return;
            }
            
            $scoreboard = $this->scoreboardService->getPublicScoreboard($sessionId, $displayMode);
            
            if ($includeHistory) {
                $scoreboard['history'] = $this->scoreboardService->getScoreboardHistory($sessionId);
            }
            
            // Add cache headers
            header('Cache-Control: public, max-age=5'); // 5 second cache
            header('ETag: "' . md5(serialize($scoreboard)) . '"');
            
            echo json_encode($scoreboard);
            
        } catch (\Exception $e) {
            error_log('Scoreboard API error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    public function embed($sessionId)
    {
        // Embeddable scoreboard widget
        $displayMode = $this->request->input('mode', 'standard');
        $theme = $this->request->input('theme', 'default');
        
        $session = LiveScoringSession::find($sessionId);
        if (!$session || !$this->isSessionPublic($session)) {
            http_response_code(404);
            echo '<div class="error">Scoreboard not found</div>';
            return;
        }
        
        $scoreboard = $this->scoreboardService->getPublicScoreboard($sessionId, $displayMode);
        
        $data = [
            'session' => $session,
            'scoreboard' => $scoreboard,
            'display_mode' => $displayMode,
            'theme' => $theme,
            'embed_mode' => true
        ];
        
        // Set headers for embedding
        header('X-Frame-Options: ALLOWALL');
        header('Content-Security-Policy: frame-ancestors *;');
        
        return $this->view('public/scoreboard/embed', $data);
    }
    
    public function qr($sessionId)
    {
        // Generate and serve QR code
        $session = LiveScoringSession::find($sessionId);
        if (!$session || !$this->isSessionPublic($session)) {
            http_response_code(404);
            return;
        }
        
        $qrUrl = $this->scoreboardService->generateQRCode($sessionId);
        
        // Redirect to QR code image
        header("Location: $qrUrl");
        exit;
    }
    
    public function social($sessionId)
    {
        // Social media optimized data
        header('Content-Type: application/json');
        
        $session = LiveScoringSession::find($sessionId);
        if (!$session || !$this->isSessionPublic($session)) {
            http_response_code(404);
            echo json_encode(['error' => 'Session not found']);
            return;
        }
        
        $socialData = $this->scoreboardService->getPublicScoreboard($sessionId, 'social');
        
        // Add social media meta tags data
        $socialData['meta'] = [
            'title' => "Live Results: {$socialData['session_name']}",
            'description' => "Follow live scoring for {$socialData['competition']} - {$socialData['category']} category",
            'image' => $this->generateSocialImage($sessionId),
            'url' => "https://{$_SERVER['HTTP_HOST']}/scoreboard/{$sessionId}"
        ];
        
        echo json_encode($socialData);
    }
    
    public function metrics($sessionId)
    {
        // Admin-only endpoint for session metrics
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $metrics = $this->scoreboardService->getSessionPerformanceMetrics($sessionId);
            echo json_encode($metrics);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get metrics']);
        }
    }
    
    private function getActiveSessions()
    {
        return $this->db->query("
            SELECT lss.*, c.name as competition_name, cat.name as category_name, v.name as venue_name,
                   COUNT(wc.id) as viewer_count,
                   (SELECT COUNT(DISTINCT team_id) FROM live_score_updates WHERE session_id = lss.id) as teams_scored
            FROM live_scoring_sessions lss
            JOIN competitions c ON lss.competition_id = c.id
            JOIN categories cat ON lss.category_id = cat.id
            LEFT JOIN venues v ON lss.venue_id = v.id
            LEFT JOIN websocket_connections wc ON lss.id = wc.session_id AND wc.user_type = 'spectator' AND wc.is_active = 1
            WHERE lss.status IN ('active', 'scheduled')
            AND lss.spectator_access_code IS NOT NULL
            GROUP BY lss.id
            ORDER BY lss.start_time ASC
        ");
    }
    
    private function isSessionPublic($session)
    {
        // Session is public if it has a spectator access code or is explicitly public
        return !empty($session['spectator_access_code']) || 
               ($session['session_metadata'] && 
                json_decode($session['session_metadata'], true)['public'] ?? false);
    }
    
    private function getWebSocketURL($sessionId)
    {
        $protocol = $this->request->isSecure() ? 'wss:' : 'ws:';
        $host = $_SERVER['HTTP_HOST'];
        return "{$protocol}//{$host}/scoreboard?session={$sessionId}";
    }
    
    private function generateSocialImage($sessionId)
    {
        // This would generate or return a URL to a dynamic social media image
        // For now, return a placeholder
        return "https://{$_SERVER['HTTP_HOST']}/images/social/scoreboard-{$sessionId}.png";
    }
    
    protected function isAdmin()
    {
        return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin', 'competition_admin']);
    }
}