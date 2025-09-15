<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LiveScoringSession;
use App\Models\Competition;
use App\Models\Category;
use App\Models\Venue;
use App\Services\LiveScoreboardService;
use App\Services\ConflictResolutionService;

class LiveScoringController extends BaseController
{
    private $scoreboardService;
    private $conflictService;
    
    public function __construct()
    {
        parent::__construct();
        $this->scoreboardService = new LiveScoreboardService();
        $this->conflictService = new ConflictResolutionService();
    }
    
    public function index()
    {
        // Live scoring sessions overview
        $sessions = $this->getAllSessions();
        $stats = $this->getSystemStats();
        $recentActivity = $this->getRecentActivity();
        
        $data = [
            'page_title' => 'Live Scoring Management',
            'page_subtitle' => 'Manage real-time scoring sessions and monitor system performance',
            'sessions' => $sessions,
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'websocket_status' => $this->getWebSocketStatus(),
            'pageCSS' => ['/css/admin/live-scoring.css'],
            'pageJS' => ['/js/admin/live-scoring.js']
        ];
        
        return $this->view('admin/live-scoring/index', $data);
    }
    
    public function create()
    {
        // Create new live scoring session
        $competitions = Competition::where('status', 'active')->get();
        $categories = Category::all();
        $venues = Venue::all();
        
        $data = [
            'page_title' => 'Create Live Scoring Session',
            'competitions' => $competitions,
            'categories' => $categories,
            'venues' => $venues,
            'pageCSS' => ['/css/admin/live-scoring.css'],
            'pageJS' => ['/js/admin/live-scoring-create.js']
        ];
        
        return $this->view('admin/live-scoring/create', $data);
    }
    
    public function store()
    {
        // Handle session creation
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        // Validation
        try {
            $validatedData = $this->validate([
                'competition_id' => 'required|integer',
                'category_id' => 'required|integer', 
                'session_name' => 'required|string|max:200',
                'session_type' => 'required|string',
                'venue_id' => 'integer',
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'spectator_access_code' => 'string|max:50',
                'max_concurrent_judges' => 'integer'
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Validation failed'], 422);
        }
        
        try {
            // Create session
            $sessionData = [
                'competition_id' => $validatedData['competition_id'],
                'category_id' => $validatedData['category_id'],
                'session_name' => $validatedData['session_name'],
                'session_type' => $validatedData['session_type'],
                'venue_id' => $validatedData['venue_id'] ?? null,
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'spectator_access_code' => $validatedData['spectator_access_code'] ?? null,
                'max_concurrent_judges' => $validatedData['max_concurrent_judges'] ?? 10,
                'status' => 'scheduled',
                'created_by' => $_SESSION['user_id'],
                'session_metadata' => json_encode([
                    'auto_start' => $this->input('auto_start', false),
                    'conflict_threshold' => $this->input('conflict_threshold', 15),
                    'allow_late_entries' => $this->input('allow_late_entries', false),
                    'public' => !empty($validatedData['spectator_access_code'])
                ])
            ];
            
            $sessionId = $this->db->insert('live_scoring_sessions', $sessionData);
            
            // Initialize WebSocket room for this session
            $this->initializeWebSocketRoom($sessionId);
            
            // Create audit log
            $this->logActivity('live_session_created', [
                'session_id' => $sessionId,
                'session_name' => $validatedData['session_name'],
                'competition_id' => $validatedData['competition_id']
            ]);
            
            return $this->json([
                'success' => true,
                'message' => 'Live scoring session created successfully',
                'session_id' => $sessionId,
                'redirect' => url("/admin/live-scoring/sessions/{$sessionId}")
            ]);
            
        } catch (\Exception $e) {
            error_log('Live scoring session creation failed: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to create session'], 500);
        }
    }
    
    public function show($sessionId)
    {
        // Show specific session details and monitoring
        $session = $this->getSessionWithDetails($sessionId);
        
        if (!$session) {
            $this->session->setFlash('error', 'Live scoring session not found');
            return $this->redirect('/admin/live-scoring');
        }
        
        $sessionStats = $this->getSessionStats($sessionId);
        $activeJudges = $this->getActiveJudges($sessionId);
        $recentUpdates = $this->getRecentScoreUpdates($sessionId);
        $conflicts = $this->conflictService->getSessionConflicts($sessionId);
        
        $data = [
            'page_title' => $session['session_name'] . ' - Session Management',
            'session' => $session,
            'stats' => $sessionStats,
            'active_judges' => $activeJudges,
            'recent_updates' => $recentUpdates,
            'conflicts' => $conflicts,
            'websocket_url' => $this->getWebSocketURL($sessionId),
            'pageCSS' => ['/css/admin/live-scoring.css'],
            'pageJS' => ['/js/admin/live-scoring-monitor.js']
        ];
        
        return $this->view('admin/live-scoring/show', $data);
    }
    
    public function websocket()
    {
        // WebSocket server monitoring dashboard
        $serverStatus = $this->getWebSocketServerStatus();
        $connections = $this->getActiveConnections();
        $performance = $this->getWebSocketPerformanceMetrics();
        $logs = $this->getWebSocketLogs();
        
        $data = [
            'page_title' => 'WebSocket Server Management',
            'page_subtitle' => 'Monitor and manage real-time communication server',
            'server_status' => $serverStatus,
            'connections' => $connections,
            'performance' => $performance,
            'logs' => $logs,
            'pageCSS' => ['/css/admin/websocket-monitor.css'],
            'pageJS' => ['/js/admin/websocket-monitor.js']
        ];
        
        return $this->view('admin/live-scoring/websocket', $data);
    }
    
    public function conflicts()
    {
        // Conflict resolution dashboard
        $activeConflicts = $this->conflictService->getActiveConflicts();
        $resolvedConflicts = $this->conflictService->getResolvedConflicts();
        $conflictStats = $this->conflictService->getConflictStatistics();
        
        $data = [
            'page_title' => 'Scoring Conflicts Management',
            'page_subtitle' => 'Monitor and resolve scoring discrepancies',
            'active_conflicts' => $activeConflicts,
            'resolved_conflicts' => $resolvedConflicts,
            'stats' => $conflictStats,
            'pageCSS' => ['/css/admin/conflict-resolution.css'],
            'pageJS' => ['/js/admin/conflict-resolution.js']
        ];
        
        return $this->view('admin/live-scoring/conflicts', $data);
    }
    
    public function analytics()
    {
        // Live scoring analytics dashboard
        $dateRange = $this->input('range', '7d');
        $analytics = $this->getAnalyticsData($dateRange);
        
        $data = [
            'page_title' => 'Live Scoring Analytics',
            'page_subtitle' => 'Performance insights and usage statistics',
            'analytics' => $analytics,
            'date_range' => $dateRange,
            'pageCSS' => ['/css/admin/analytics.css'],
            'pageJS' => ['/js/admin/analytics.js', '/js/charts.js']
        ];
        
        return $this->view('admin/live-scoring/analytics', $data);
    }
    
    public function startSession($sessionId)
    {
        // Start a live scoring session
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        try {
            $session = LiveScoringSession::find($sessionId);
            if (!$session || $session['status'] !== 'scheduled') {
                return $this->json(['success' => false, 'message' => 'Session cannot be started'], 400);
            }
            
            // Update session status
            $this->db->update('live_scoring_sessions', $sessionId, [
                'status' => 'active',
                'actual_start_time' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Broadcast session start to all connected clients
            $this->broadcastSessionEvent($sessionId, 'session_started');
            
            $this->logActivity('live_session_started', ['session_id' => $sessionId]);
            
            return $this->json(['success' => true, 'message' => 'Session started successfully']);
            
        } catch (\Exception $e) {
            error_log('Failed to start session: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to start session'], 500);
        }
    }
    
    public function stopSession($sessionId)
    {
        // Stop a live scoring session
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        try {
            $session = LiveScoringSession::find($sessionId);
            if (!$session || !in_array($session['status'], ['active', 'paused'])) {
                return $this->json(['success' => false, 'message' => 'Session cannot be stopped'], 400);
            }
            
            // Update session status
            $this->db->update('live_scoring_sessions', $sessionId, [
                'status' => 'completed',
                'actual_end_time' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Broadcast session end to all connected clients
            $this->broadcastSessionEvent($sessionId, 'session_ended');
            
            $this->logActivity('live_session_stopped', ['session_id' => $sessionId]);
            
            return $this->json(['success' => true, 'message' => 'Session stopped successfully']);
            
        } catch (\Exception $e) {
            error_log('Failed to stop session: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to stop session'], 500);
        }
    }
    
    public function serverControl()
    {
        // WebSocket server control actions
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $action = $this->input('action');
        
        try {
            switch ($action) {
                case 'start':
                    $result = $this->startWebSocketServer();
                    break;
                case 'stop':
                    $result = $this->stopWebSocketServer();
                    break;
                case 'restart':
                    $result = $this->restartWebSocketServer();
                    break;
                case 'status':
                    $result = $this->getWebSocketStatus();
                    break;
                default:
                    return $this->json(['success' => false, 'message' => 'Invalid action'], 400);
            }
            
            return $this->json(['success' => true, 'data' => $result]);
            
        } catch (\Exception $e) {
            error_log('WebSocket server control failed: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Server control action failed'], 500);
        }
    }
    
    private function getAllSessions()
    {
        return $this->db->query("
            SELECT lss.*, c.name as competition_name, cat.name as category_name, v.name as venue_name,
                   COUNT(DISTINCT wc.id) as active_connections,
                   COUNT(DISTINCT lsu.judge_id) as active_judges,
                   COUNT(lsu.id) as total_updates
            FROM live_scoring_sessions lss
            JOIN competitions c ON lss.competition_id = c.id
            JOIN categories cat ON lss.category_id = cat.id
            LEFT JOIN venues v ON lss.venue_id = v.id
            LEFT JOIN websocket_connections wc ON lss.id = wc.session_id AND wc.disconnected_at IS NULL
            LEFT JOIN live_score_updates lsu ON lss.id = lsu.session_id AND lsu.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY lss.id
            ORDER BY lss.created_at DESC
        ");
    }
    
    private function getSystemStats()
    {
        $stats = [];
        
        // Active sessions
        $stats['active_sessions'] = $this->db->query("
            SELECT COUNT(*) as count FROM live_scoring_sessions WHERE status = 'active'
        ")[0]['count'];
        
        // Total connections
        $stats['total_connections'] = $this->db->query("
            SELECT COUNT(*) as count FROM websocket_connections WHERE disconnected_at IS NULL
        ")[0]['count'];
        
        // Active conflicts
        $stats['active_conflicts'] = $this->db->query("
            SELECT COUNT(*) as count FROM live_score_updates WHERE sync_status = 'conflict'
        ")[0]['count'];
        
        // Server uptime
        $stats['server_uptime'] = $this->getServerUptime();
        
        return $stats;
    }
    
    private function getRecentActivity()
    {
        return $this->db->query("
            SELECT 'session' as type, 'Session Started' as activity, lss.session_name as details, 
                   lss.actual_start_time as timestamp
            FROM live_scoring_sessions lss
            WHERE lss.actual_start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            
            UNION ALL
            
            SELECT 'update' as type, 'Score Update' as activity, 
                   CONCAT('Judge ', lsu.judge_id, ' - Team ', lsu.team_id) as details,
                   lsu.created_at as timestamp
            FROM live_score_updates lsu
            WHERE lsu.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            
            ORDER BY timestamp DESC
            LIMIT 20
        ");
    }
    
    private function getWebSocketStatus()
    {
        // Check if WebSocket server is running
        $socket = @fsockopen('localhost', 8080, $errno, $errstr, 1);
        if ($socket) {
            fclose($socket);
            return [
                'status' => 'running',
                'port' => 8080,
                'uptime' => $this->getServerUptime()
            ];
        }
        
        return [
            'status' => 'stopped',
            'port' => 8080,
            'error' => $errstr ?? 'Server not responding'
        ];
    }
    
    private function getServerUptime()
    {
        // Get server uptime from logs or process info
        $uptimeFile = APP_ROOT . '/storage/logs/websocket-uptime.txt';
        if (file_exists($uptimeFile)) {
            $startTime = file_get_contents($uptimeFile);
            return time() - intval($startTime);
        }
        return 0;
    }
    
    private function initializeWebSocketRoom($sessionId)
    {
        // Initialize WebSocket room/namespace for the session
        // This would typically involve registering the session with the WebSocket server
        try {
            $this->db->insert('websocket_rooms', [
                'session_id' => $sessionId,
                'room_name' => "session_{$sessionId}",
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Room table might not exist yet, this is optional
            error_log('Failed to create WebSocket room: ' . $e->getMessage());
        }
    }
    
    private function broadcastSessionEvent($sessionId, $event)
    {
        // Broadcast session event to WebSocket server
        // This would send a message to the WebSocket server to notify all connected clients
        try {
            $message = json_encode([
                'type' => $event,
                'session_id' => $sessionId,
                'timestamp' => time()
            ]);
            
            // Send to WebSocket server via HTTP endpoint or direct socket
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => $message
                ]
            ]);
            
            @file_get_contents('http://localhost:8080/admin-broadcast', false, $context);
        } catch (\Exception $e) {
            error_log('Failed to broadcast session event: ' . $e->getMessage());
        }
    }
    
    private function logActivity($action, $data)
    {
        try {
            $this->db->insert('admin_activity_logs', [
                'user_id' => $_SESSION['user_id'],
                'action' => $action,
                'data' => json_encode($data),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Log table might not exist, this is optional
            error_log('Failed to log admin activity: ' . $e->getMessage());
        }
    }
    
    private function startWebSocketServer()
    {
        $command = 'cd ' . APP_ROOT . ' && ./websocket-manager.sh start';
        $output = shell_exec($command);
        return ['output' => $output, 'status' => 'started'];
    }
    
    private function stopWebSocketServer()
    {
        $command = 'cd ' . APP_ROOT . ' && ./websocket-manager.sh stop';
        $output = shell_exec($command);
        return ['output' => $output, 'status' => 'stopped'];
    }
    
    private function restartWebSocketServer()
    {
        $command = 'cd ' . APP_ROOT . ' && ./websocket-manager.sh restart';
        $output = shell_exec($command);
        return ['output' => $output, 'status' => 'restarted'];
    }
    
    private function getWebSocketServerStatus()
    {
        $command = 'cd ' . APP_ROOT . ' && ./websocket-manager.sh status';
        $output = shell_exec($command);
        
        return [
            'raw_output' => $output,
            'is_running' => strpos($output, 'RUNNING') !== false,
            'connections' => $this->extractConnectionCount($output)
        ];
    }
    
    private function extractConnectionCount($output)
    {
        // Extract connection count from status output
        if (preg_match('/(\d+)\s+connections?/i', $output, $matches)) {
            return intval($matches[1]);
        }
        return 0;
    }
    
    private function getActiveConnections()
    {
        return $this->db->query("
            SELECT wc.*, lss.session_name, u.first_name, u.last_name
            FROM websocket_connections wc
            LEFT JOIN live_scoring_sessions lss ON wc.session_id = lss.id
            LEFT JOIN users u ON wc.user_id = u.id
            WHERE wc.disconnected_at IS NULL
            ORDER BY wc.connected_at DESC
        ");
    }
    
    private function getWebSocketPerformanceMetrics()
    {
        return [
            'avg_response_time' => $this->getAverageResponseTime(),
            'message_throughput' => $this->getMessageThroughput(),
            'error_rate' => $this->getErrorRate(),
            'memory_usage' => $this->getMemoryUsage()
        ];
    }
    
    private function getWebSocketLogs($limit = 100)
    {
        $logFile = APP_ROOT . '/storage/logs/websocket.log';
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        return array_slice(array_reverse($lines), 0, $limit);
    }
    
    private function getSessionWithDetails($sessionId)
    {
        $sessions = $this->db->query("
            SELECT lss.*, c.name as competition_name, cat.name as category_name, v.name as venue_name,
                   u.first_name as creator_first_name, u.last_name as creator_last_name
            FROM live_scoring_sessions lss
            JOIN competitions c ON lss.competition_id = c.id
            JOIN categories cat ON lss.category_id = cat.id
            LEFT JOIN venues v ON lss.venue_id = v.id
            LEFT JOIN users u ON lss.created_by = u.id
            WHERE lss.id = ?
        ", [$sessionId]);
        
        return !empty($sessions) ? $sessions[0] : null;
    }
    
    private function getSessionStats($sessionId)
    {
        $stats = [];
        
        // Total score updates
        $updates = $this->db->query("
            SELECT COUNT(*) as count FROM live_score_updates WHERE session_id = ?
        ", [$sessionId]);
        $stats['total_updates'] = $updates[0]['count'];
        
        // Active judges
        $judges = $this->db->query("
            SELECT COUNT(DISTINCT judge_id) as count FROM live_score_updates WHERE session_id = ?
        ", [$sessionId]);
        $stats['active_judges'] = $judges[0]['count'];
        
        // Spectator connections
        $spectators = $this->db->query("
            SELECT COUNT(*) as count FROM websocket_connections 
            WHERE session_id = ? AND user_type = 'spectator' AND disconnected_at IS NULL
        ", [$sessionId]);
        $stats['spectators'] = $spectators[0]['count'];
        
        return $stats;
    }
    
    private function getActiveJudges($sessionId)
    {
        return $this->db->query("
            SELECT DISTINCT lsu.judge_id, u.first_name, u.last_name, 
                   COUNT(lsu.id) as update_count,
                   MAX(lsu.created_at) as last_update
            FROM live_score_updates lsu
            JOIN users u ON lsu.judge_id = u.id
            WHERE lsu.session_id = ?
            AND lsu.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY lsu.judge_id
            ORDER BY last_update DESC
        ", [$sessionId]);
    }
    
    private function getRecentScoreUpdates($sessionId)
    {
        return $this->db->query("
            SELECT lsu.*, u.first_name, u.last_name, t.team_name
            FROM live_score_updates lsu
            JOIN users u ON lsu.judge_id = u.id
            LEFT JOIN teams t ON lsu.team_id = t.id
            WHERE lsu.session_id = ?
            ORDER BY lsu.created_at DESC
            LIMIT 50
        ", [$sessionId]);
    }
    
    private function getWebSocketURL($sessionId)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'wss:' : 'ws:';
        $host = $_SERVER['HTTP_HOST'];
        return "{$protocol}//{$host}:8080?session={$sessionId}&admin=1";
    }
    
    private function getAnalyticsData($dateRange)
    {
        // Generate analytics data based on date range
        $days = $this->parseDateRange($dateRange);
        
        return [
            'sessions_created' => $this->getSessionsCreatedData($days),
            'score_updates' => $this->getScoreUpdatesData($days),
            'active_users' => $this->getActiveUsersData($days),
            'conflict_resolution' => $this->getConflictResolutionData($days),
            'performance_metrics' => $this->getPerformanceData($days)
        ];
    }
    
    private function parseDateRange($range)
    {
        switch ($range) {
            case '1d': return 1;
            case '7d': return 7;
            case '30d': return 30;
            case '90d': return 90;
            default: return 7;
        }
    }
    
    private function getSessionsCreatedData($days)
    {
        return $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM live_scoring_sessions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$days]);
    }
    
    private function getScoreUpdatesData($days)
    {
        return $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM live_score_updates
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$days]);
    }
    
    private function getActiveUsersData($days)
    {
        return $this->db->query("
            SELECT DATE(connected_at) as date, COUNT(DISTINCT user_id) as count
            FROM websocket_connections
            WHERE connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(connected_at)
            ORDER BY date ASC
        ", [$days]);
    }
    
    private function getConflictResolutionData($days)
    {
        return $this->db->query("
            SELECT DATE(created_at) as date, 
                   COUNT(CASE WHEN sync_status = 'conflict' THEN 1 END) as conflicts,
                   COUNT(CASE WHEN sync_status = 'resolved' THEN 1 END) as resolved
            FROM live_score_updates
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$days]);
    }
    
    private function getPerformanceData($days)
    {
        // Mock performance data - in production, this would come from monitoring systems
        return [
            'avg_response_time' => 150,
            'throughput' => 1200,
            'error_rate' => 0.02,
            'uptime_percentage' => 99.9
        ];
    }
    
    private function getAverageResponseTime()
    {
        // Mock data - would come from WebSocket server metrics
        return rand(100, 200);
    }
    
    private function getMessageThroughput()
    {
        // Mock data - messages per minute
        return rand(800, 1500);
    }
    
    private function getErrorRate()
    {
        // Mock data - percentage
        return rand(1, 5) / 100;
    }
    
    private function getMemoryUsage()
    {
        // Mock data - MB
        return rand(50, 150);
    }
}