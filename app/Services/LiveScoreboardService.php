<?php
// app/Services/LiveScoreboardService.php

namespace App\Services;

use App\Core\Database;
use App\Models\LiveScoringSession;

class LiveScoreboardService
{
    private $db;
    private $updateInterval = 2; // seconds
    private $redis = null;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Initialize Redis if available
        if (extension_loaded('redis')) {
            try {
                $this->redis = new \Redis();
                $this->redis->connect('127.0.0.1', 6379);
            } catch (\Exception $e) {
                error_log('Redis connection failed: ' . $e->getMessage());
                $this->redis = null;
            }
        }
    }
    
    public function updateScoreboard($sessionId)
    {
        // Get current standings
        $standings = $this->calculateStandings($sessionId);
        
        // Store in Redis for fast access (if available)
        if ($this->redis) {
            try {
                $this->redis->setex(
                    "scoreboard:{$sessionId}",
                    60,
                    json_encode($standings)
                );
            } catch (\Exception $e) {
                error_log('Redis cache failed: ' . $e->getMessage());
            }
        }
        
        // Broadcast update via WebSocket (would integrate with WebSocketServer)
        $this->broadcastUpdate($sessionId, $standings);
        
        // Update social media if configured
        if ($this->shouldUpdateSocialMedia($standings)) {
            $this->updateSocialMedia($standings);
        }
        
        return $standings;
    }
    
    private function calculateStandings($sessionId)
    {
        // Get session info
        $session = $this->db->query("
            SELECT lss.*, c.name as competition_name, cat.name as category_name
            FROM live_scoring_sessions lss
            JOIN competitions c ON lss.competition_id = c.id
            JOIN categories cat ON lss.category_id = cat.id
            WHERE lss.id = ?
        ", [$sessionId]);
        
        if (empty($session)) {
            return ['error' => 'Session not found'];
        }
        
        $sessionData = $session[0];
        
        // Get teams with their current scores
        $teams = $this->db->query("
            SELECT 
                t.id,
                t.name as team_name,
                s.name as school_name,
                c.name as category,
                COALESCE(agg.total_score, 0) as total_score,
                COALESCE(agg.game_challenge_score, 0) as game_score,
                COALESCE(agg.research_challenge_score, 0) as research_score,
                agg.num_judges,
                agg.finalized,
                RANK() OVER (ORDER BY COALESCE(agg.total_score, 0) DESC) as current_rank,
                LAG(COALESCE(agg.total_score, 0), 1) OVER (ORDER BY COALESCE(agg.total_score, 0) DESC) - COALESCE(agg.total_score, 0) as points_behind,
                -- Get live score updates count
                (SELECT COUNT(*) FROM live_score_updates lsu 
                 WHERE lsu.team_id = t.id AND lsu.session_id = ? AND lsu.sync_status = 'synced') as live_updates,
                -- Get last update time
                (SELECT MAX(server_timestamp) FROM live_score_updates lsu 
                 WHERE lsu.team_id = t.id AND lsu.session_id = ?) as last_update
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN aggregated_scores agg ON t.id = agg.team_id
            WHERE t.competition_id = ?
            AND t.category_id = ?
            ORDER BY current_rank
        ", [$sessionId, $sessionId, $sessionData['competition_id'], $sessionData['category_id']]);
        
        // Get live scoring statistics
        $liveStats = $this->db->query("
            SELECT 
                COUNT(DISTINCT judge_id) as active_judges,
                COUNT(DISTINCT team_id) as teams_with_scores,
                COUNT(*) as total_score_updates,
                COUNT(CASE WHEN sync_status = 'conflict' THEN 1 END) as conflicts,
                AVG(TIMESTAMPDIFF(SECOND, client_timestamp, server_timestamp)) as avg_sync_delay
            FROM live_score_updates
            WHERE session_id = ?
        ", [$sessionId]);
        
        return $this->formatStandings([
            'session' => $sessionData,
            'teams' => $teams,
            'live_stats' => $liveStats[0] ?? [],
            'last_updated' => date('Y-m-d H:i:s'),
            'update_interval' => $this->updateInterval
        ]);
    }
    
    private function formatStandings($data)
    {
        $formatted = [
            'session' => [
                'id' => $data['session']['id'],
                'name' => $data['session']['session_name'],
                'competition' => $data['session']['competition_name'],
                'category' => $data['session']['category_name'],
                'status' => $data['session']['status'],
                'start_time' => $data['session']['start_time']
            ],
            'standings' => [],
            'statistics' => $data['live_stats'],
            'metadata' => [
                'last_updated' => $data['last_updated'],
                'update_interval' => $data['update_interval'],
                'total_teams' => count($data['teams'])
            ]
        ];
        
        // Process team standings
        foreach ($data['teams'] as $team) {
            $formatted['standings'][] = [
                'team_id' => $team['id'],
                'rank' => $team['current_rank'],
                'team_name' => $team['team_name'],
                'school_name' => $team['school_name'],
                'total_score' => floatval($team['total_score']),
                'game_score' => floatval($team['game_score']),
                'research_score' => floatval($team['research_score']),
                'points_behind' => $team['points_behind'] ? floatval($team['points_behind']) : 0,
                'judges_completed' => $team['num_judges'] ?? 0,
                'is_finalized' => (bool)$team['finalized'],
                'live_updates' => $team['live_updates'] ?? 0,
                'last_update' => $team['last_update'],
                'trend' => $this->calculateTrend($team['id'], $data['session']['id'])
            ];
        }
        
        return $formatted;
    }
    
    public function getPublicScoreboard($sessionId, $displayMode = 'standard')
    {
        // Check cache first
        if ($this->redis) {
            try {
                $cached = $this->redis->get("scoreboard:{$sessionId}");
                if ($cached) {
                    $standings = json_decode($cached, true);
                } else {
                    $standings = $this->updateScoreboard($sessionId);
                }
            } catch (\Exception $e) {
                $standings = $this->updateScoreboard($sessionId);
            }
        } else {
            $standings = $this->updateScoreboard($sessionId);
        }
        
        // Format for display mode
        switch ($displayMode) {
            case 'tv':
                return $this->formatForTV($standings);
            case 'mobile':
                return $this->formatForMobile($standings);
            case 'social':
                return $this->formatForSocial($standings);
            default:
                return $standings;
        }
    }
    
    private function formatForTV($standings)
    {
        // TV format shows top 3 prominently with podium display
        $tvData = $standings;
        $tvData['display_mode'] = 'tv';
        
        if (!empty($standings['standings'])) {
            // Separate top 3 and remaining teams
            $top3 = array_slice($standings['standings'], 0, 3);
            $remaining = array_slice($standings['standings'], 3);
            
            // Reorder top 3 for podium display (2nd, 1st, 3rd)
            if (count($top3) >= 3) {
                $tvData['podium'] = [
                    'second' => $top3[1], // 2nd place
                    'first' => $top3[0],  // 1st place
                    'third' => $top3[2]   // 3rd place
                ];
            } else {
                $tvData['podium'] = [];
                foreach ($top3 as $i => $team) {
                    $positions = ['first', 'second', 'third'];
                    $tvData['podium'][$positions[$i]] = $team;
                }
            }
            
            $tvData['remaining_teams'] = $remaining;
        }
        
        // Add TV-specific display settings
        $tvData['tv_settings'] = [
            'auto_rotate_interval' => 30, // seconds
            'show_sponsor_logos' => true,
            'ticker_messages' => $this->generateTickerMessages($standings)
        ];
        
        return $tvData;
    }
    
    private function formatForMobile($standings)
    {
        $mobileData = $standings;
        $mobileData['display_mode'] = 'mobile';
        
        // Simplify data for mobile
        if (!empty($standings['standings'])) {
            foreach ($mobileData['standings'] as &$team) {
                // Remove less important fields for mobile
                unset($team['live_updates'], $team['trend']);
                
                // Round scores for cleaner display
                $team['total_score'] = round($team['total_score'], 1);
                $team['game_score'] = round($team['game_score'], 1);
                $team['research_score'] = round($team['research_score'], 1);
            }
        }
        
        $mobileData['mobile_settings'] = [
            'compact_view' => true,
            'touch_optimized' => true,
            'reduced_animations' => true
        ];
        
        return $mobileData;
    }
    
    private function formatForSocial($standings)
    {
        $socialData = [
            'session_name' => $standings['session']['name'],
            'competition' => $standings['session']['competition'],
            'category' => $standings['session']['category'],
            'top_teams' => array_slice($standings['standings'], 0, 5),
            'total_participants' => $standings['metadata']['total_teams'],
            'hashtags' => ['#SciBOTICS2025', '#GDECompetition', '#STEM'],
            'generated_at' => date('c')
        ];
        
        return $socialData;
    }
    
    private function calculateTrend($teamId, $sessionId)
    {
        // Get recent score changes to determine trend
        $recentUpdates = $this->db->query("
            SELECT score_value, server_timestamp
            FROM live_score_updates
            WHERE team_id = ? AND session_id = ?
            ORDER BY server_timestamp DESC
            LIMIT 5
        ", [$teamId, $sessionId]);
        
        if (count($recentUpdates) < 2) {
            return 'stable';
        }
        
        $latest = floatval($recentUpdates[0]['score_value']);
        $previous = floatval($recentUpdates[1]['score_value']);
        
        if ($latest > $previous) return 'up';
        if ($latest < $previous) return 'down';
        return 'stable';
    }
    
    private function generateTickerMessages($standings)
    {
        $messages = [];
        
        if (!empty($standings['standings'])) {
            $leader = $standings['standings'][0];
            $messages[] = "🏆 {$leader['team_name']} leads with {$leader['total_score']} points!";
            
            // Find biggest movers
            $upTrends = array_filter($standings['standings'], function($team) {
                return $team['trend'] === 'up';
            });
            
            if (!empty($upTrends)) {
                $mover = array_slice($upTrends, 0, 1)[0];
                $messages[] = "📈 {$mover['team_name']} is moving up the leaderboard!";
            }
        }
        
        $messages[] = "⏱️ Live scoring in progress - SciBOTICS Finals 2025";
        $messages[] = "🚀 Follow @GDEEducation for live updates #SciBOTICS2025";
        
        return $messages;
    }
    
    private function broadcastUpdate($sessionId, $standings)
    {
        // This would integrate with the WebSocketServer to broadcast updates
        // For now, we'll log the broadcast
        error_log("Broadcasting scoreboard update for session {$sessionId}");
        
        // In a real implementation, you'd send to all connected scoreboard viewers
        /*
        $message = [
            'type' => 'scoreboard_update',
            'session_id' => $sessionId,
            'data' => $standings
        ];
        
        $this->webSocketServer->broadcastToScoreboardViewers($sessionId, $message);
        */
    }
    
    private function shouldUpdateSocialMedia($standings)
    {
        // Only update social media for significant changes
        if (empty($standings['standings'])) {
            return false;
        }
        
        // Check if leader changed or significant score changes
        $leader = $standings['standings'][0];
        
        // Get cached leader
        if ($this->redis) {
            try {
                $lastLeader = $this->redis->get("leader:{$standings['session']['id']}");
                
                if ($lastLeader && $lastLeader !== $leader['team_id']) {
                    // Leader changed!
                    $this->redis->set("leader:{$standings['session']['id']}", $leader['team_id']);
                    return true;
                }
                
                if (!$lastLeader) {
                    $this->redis->set("leader:{$standings['session']['id']}", $leader['team_id']);
                }
            } catch (\Exception $e) {
                error_log('Redis error: ' . $e->getMessage());
            }
        }
        
        return false;
    }
    
    private function updateSocialMedia($standings)
    {
        // This would integrate with social media APIs
        error_log("Updating social media for session " . $standings['session']['id']);
        
        // Example Twitter update
        /*
        $leader = $standings['standings'][0];
        $tweet = "🏆 NEW LEADER! {$leader['team_name']} from {$leader['school_name']} takes the lead with {$leader['total_score']} points! #SciBOTICS2025 #STEMEducation";
        
        $this->twitterService->tweet($tweet);
        */
    }
    
    public function generateQRCode($sessionId)
    {
        // Generate QR code for mobile access
        $url = "https://{$_SERVER['HTTP_HOST']}/scoreboard/{$sessionId}?mode=mobile";
        
        // In a real implementation, you'd use a QR code library
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
    }
    
    public function getScoreboardHistory($sessionId, $hours = 2)
    {
        // Get historical data for trend analysis
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $history = $this->db->query("
            SELECT 
                lsu.team_id,
                lsu.score_value,
                lsu.server_timestamp,
                t.name as team_name,
                s.name as school_name
            FROM live_score_updates lsu
            JOIN teams t ON lsu.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE lsu.session_id = ?
            AND lsu.server_timestamp >= ?
            AND lsu.sync_status = 'synced'
            ORDER BY lsu.server_timestamp ASC
        ", [$sessionId, $cutoffTime]);
        
        // Group by team and create timeline
        $timeline = [];
        foreach ($history as $update) {
            $teamId = $update['team_id'];
            if (!isset($timeline[$teamId])) {
                $timeline[$teamId] = [
                    'team_name' => $update['team_name'],
                    'school_name' => $update['school_name'],
                    'updates' => []
                ];
            }
            
            $timeline[$teamId]['updates'][] = [
                'score' => floatval($update['score_value']),
                'timestamp' => $update['server_timestamp']
            ];
        }
        
        return $timeline;
    }
    
    public function getSessionPerformanceMetrics($sessionId)
    {
        // Get detailed performance metrics for the session
        $metrics = $this->db->query("
            SELECT 
                COUNT(DISTINCT wc.connection_id) as total_connections,
                COUNT(DISTINCT CASE WHEN wc.user_type = 'judge' THEN wc.user_id END) as active_judges,
                COUNT(DISTINCT CASE WHEN wc.user_type = 'spectator' THEN wc.connection_id END) as spectators,
                AVG(wc.total_messages_sent + wc.total_messages_received) as avg_messages,
                AVG(lsu.score_value) as avg_score,
                COUNT(lsu.id) as total_score_updates,
                COUNT(CASE WHEN lsu.sync_status = 'conflict' THEN 1 END) as conflicts,
                AVG(TIMESTAMPDIFF(SECOND, lsu.client_timestamp, lsu.server_timestamp)) as avg_sync_delay
            FROM live_scoring_sessions lss
            LEFT JOIN websocket_connections wc ON lss.id = wc.session_id
            LEFT JOIN live_score_updates lsu ON lss.id = lsu.session_id
            WHERE lss.id = ?
            AND wc.disconnected_at IS NULL
        ", [$sessionId]);
        
        return $metrics[0] ?? [];
    }
}