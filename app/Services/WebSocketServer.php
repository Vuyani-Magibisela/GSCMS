<?php
// app/Services/WebSocketServer.php

namespace App\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Core\Database;
use App\Models\LiveScoringSession;
use App\Models\LiveScoreUpdate;
use App\Models\WebSocketConnection;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $sessions;
    protected $judges;
    protected $subscribers;
    protected $db;
    
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->sessions = [];
        $this->judges = [];
        $this->subscribers = [];
        $this->db = Database::getInstance();
        
        echo "WebSocket Server initialized\n";
    }
    
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        
        // Parse connection parameters
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);
        
        echo "New connection attempt: {$conn->resourceId}\n";
        
        // Authenticate connection
        $authToken = $params['token'] ?? null;
        $sessionId = $params['session'] ?? null;
        $userType = $params['type'] ?? 'spectator';
        
        if ($this->authenticateConnection($conn, $authToken, $sessionId, $userType)) {
            $this->registerConnection($conn, $params);
            $this->sendInitialState($conn, $sessionId);
            
            echo "Connection authenticated: {$conn->resourceId} as {$userType}\n";
        } else {
            echo "Authentication failed for connection: {$conn->resourceId}\n";
            $conn->close();
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError($from, 'Invalid JSON format');
            return;
        }
        
        $messageType = $data['type'] ?? 'unknown';
        
        echo "Received message type: {$messageType} from {$from->resourceId}\n";
        
        switch ($messageType) {
            case 'score_update':
                $this->handleScoreUpdate($from, $data);
                break;
                
            case 'judge_ready':
                $this->handleJudgeReady($from, $data);
                break;
                
            case 'request_sync':
                $this->handleSyncRequest($from, $data);
                break;
                
            case 'conflict_detected':
                $this->handleConflict($from, $data);
                break;
                
            case 'subscribe_scoreboard':
                $this->handleScoreboardSubscription($from, $data);
                break;
                
            case 'ping':
                $this->handlePing($from);
                break;
                
            case 'team_change':
                $this->handleTeamChange($from, $data);
                break;
                
            default:
                $this->sendError($from, "Unknown message type: {$messageType}");
        }
        
        // Update connection stats
        $this->updateConnectionStats($from, 'received');
    }
    
    private function authenticateConnection($conn, $authToken, $sessionId, $userType)
    {
        if (!$sessionId) {
            return false;
        }
        
        // Check if session exists and is active
        $session = $this->db->query(
            "SELECT * FROM live_scoring_sessions WHERE id = ? AND status IN ('active', 'scheduled')",
            [$sessionId]
        );
        
        if (empty($session)) {
            echo "Session not found or inactive: {$sessionId}\n";
            return false;
        }
        
        // For spectators, allow without token
        if ($userType === 'spectator') {
            return true;
        }
        
        // For judges and admins, validate token
        if (!$authToken) {
            return false;
        }
        
        // Validate auth token (this would integrate with your existing auth system)
        $user = $this->validateAuthToken($authToken);
        if (!$user) {
            return false;
        }
        
        // Store user info with connection
        $conn->user = $user;
        $conn->userType = $userType;
        $conn->sessionId = $sessionId;
        
        return true;
    }
    
    private function validateAuthToken($token)
    {
        // This would integrate with your existing authentication
        // For now, basic validation
        if (!$token || strlen($token) < 10) {
            return false;
        }
        
        // In a real implementation, you'd validate the JWT token or session
        // and return user information
        return ['id' => 1, 'role' => 'judge']; // Placeholder
    }
    
    private function registerConnection($conn, $params)
    {
        $connectionId = $this->generateConnectionId();
        $conn->connectionId = $connectionId;
        
        // Store connection in database
        $connectionData = [
            'connection_id' => $connectionId,
            'user_id' => $conn->user['id'] ?? null,
            'user_type' => $conn->userType,
            'session_id' => $conn->sessionId,
            'ip_address' => $this->getClientIP($conn),
            'user_agent' => $this->getClientUserAgent($conn),
            'device_type' => $this->detectDeviceType($params),
            'browser_type' => $this->detectBrowserType($conn),
            'connection_metadata' => json_encode($params)
        ];
        
        try {
            $this->db->insert('websocket_connections', $connectionData);
            echo "Connection registered in database: {$connectionId}\n";
        } catch (\Exception $e) {
            echo "Failed to register connection: " . $e->getMessage() . "\n";
        }
        
        // Add to session subscribers
        if (!isset($this->subscribers[$conn->sessionId])) {
            $this->subscribers[$conn->sessionId] = [];
        }
        $this->subscribers[$conn->sessionId][] = $conn;
        
        // Add to judges list if judge
        if ($conn->userType === 'judge') {
            $this->judges[$conn->sessionId][$conn->user['id']] = $conn;
        }
    }
    
    private function sendInitialState($conn, $sessionId)
    {
        // Get current session state
        $sessionData = $this->getSessionState($sessionId);
        
        $initialState = [
            'type' => 'initial_state',
            'data' => [
                'session' => $sessionData,
                'current_scores' => $this->getCurrentScores($sessionId),
                'active_judges' => $this->getActiveJudges($sessionId),
                'connection_id' => $conn->connectionId,
                'server_time' => microtime(true)
            ]
        ];
        
        $conn->send(json_encode($initialState));
        echo "Initial state sent to connection: {$conn->resourceId}\n";
    }
    
    private function handleScoreUpdate($conn, $data)
    {
        // Validate score update
        if (!$this->validateScoreUpdate($data)) {
            $this->sendError($conn, 'Invalid score data');
            return;
        }
        
        echo "Processing score update for team {$data['team_id']}, criteria {$data['criteria_id']}\n";
        
        // Check for conflicts with other judges
        $conflicts = $this->detectConflicts($data);
        
        if (!empty($conflicts)) {
            echo "Conflict detected for score update\n";
            $this->broadcastConflict($data['session_id'], $conflicts, $data);
            return;
        }
        
        // Store update
        $updateId = $this->storeScoreUpdate($data, $conn);
        
        if ($updateId) {
            // Broadcast to all subscribers
            $this->broadcastScoreUpdate($data['session_id'], [
                'update_id' => $updateId,
                'team_id' => $data['team_id'],
                'judge_id' => $conn->user['id'],
                'criteria_id' => $data['criteria_id'],
                'score' => $data['score'],
                'timestamp' => microtime(true),
                'judge_name' => $conn->user['name'] ?? 'Judge'
            ]);
            
            // Update aggregated scores
            $this->updateAggregatedScores($data['team_id'], $data['session_id']);
            
            // Send confirmation to sender
            $conn->send(json_encode([
                'type' => 'score_confirmed',
                'data' => ['update_id' => $updateId, 'status' => 'synced']
            ]));
        } else {
            $this->sendError($conn, 'Failed to store score update');
        }
    }
    
    private function validateScoreUpdate($data)
    {
        $required = ['session_id', 'team_id', 'criteria_id', 'score'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                echo "Missing required field: {$field}\n";
                return false;
            }
        }
        
        // Validate score range
        if (!is_numeric($data['score']) || $data['score'] < 0 || $data['score'] > 100) {
            echo "Invalid score value: {$data['score']}\n";
            return false;
        }
        
        return true;
    }
    
    private function detectConflicts($data)
    {
        $conflicts = [];
        
        // Get other judges' scores for same criteria
        $otherScores = $this->db->query("
            SELECT lsu.*, jp.id as judge_id
            FROM live_score_updates lsu
            JOIN judge_profiles jp ON lsu.judge_id = jp.id
            WHERE lsu.session_id = ? 
            AND lsu.team_id = ? 
            AND lsu.criteria_id = ? 
            AND lsu.judge_id != ?
            AND lsu.sync_status != 'resolved'
            ORDER BY lsu.server_timestamp DESC
        ", [
            $data['session_id'],
            $data['team_id'], 
            $data['criteria_id'],
            $data['judge_id'] ?? 0
        ]);
        
        foreach ($otherScores as $otherScore) {
            $deviation = abs($data['score'] - $otherScore['score_value']);
            $maxDeviation = $this->getMaxAllowedDeviation($data['criteria_id']);
            
            if ($deviation > $maxDeviation) {
                $conflicts[] = [
                    'judge_id' => $otherScore['judge_id'],
                    'their_score' => $otherScore['score_value'],
                    'your_score' => $data['score'],
                    'deviation' => $deviation,
                    'max_allowed' => $maxDeviation,
                    'update_time' => $otherScore['server_timestamp']
                ];
            }
        }
        
        return $conflicts;
    }
    
    private function getMaxAllowedDeviation($criteriaId)
    {
        // Get deviation threshold from criteria or use default
        $criteria = $this->db->query(
            "SELECT max_deviation FROM rubric_criteria WHERE id = ?",
            [$criteriaId]
        );
        
        return $criteria[0]['max_deviation'] ?? 15; // Default 15% deviation
    }
    
    private function storeScoreUpdate($data, $conn)
    {
        try {
            $updateData = [
                'session_id' => $data['session_id'],
                'team_id' => $data['team_id'],
                'judge_id' => $conn->user['id'],
                'criteria_id' => $data['criteria_id'],
                'score_value' => $data['score'],
                'previous_value' => $data['previous_value'] ?? null,
                'update_type' => $data['update_type'] ?? 'initial',
                'client_timestamp' => date('Y-m-d H:i:s', $data['timestamp'] / 1000),
                'device_info' => json_encode([
                    'connection_id' => $conn->connectionId,
                    'device_type' => $conn->deviceType ?? 'unknown'
                ]),
                'connection_quality' => $this->assessConnectionQuality($conn)
            ];
            
            return $this->db->insert('live_score_updates', $updateData);
        } catch (\Exception $e) {
            echo "Error storing score update: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function broadcastScoreUpdate($sessionId, $update)
    {
        $message = json_encode([
            'type' => 'score_update',
            'data' => $update
        ]);
        
        // Send to all subscribers of this session
        $subscriberCount = 0;
        if (isset($this->subscribers[$sessionId])) {
            foreach ($this->subscribers[$sessionId] as $client) {
                if ($client->getState() === \Ratchet\RFC6455\Messaging\MessageInterface::TYPE_TEXT) {
                    $client->send($message);
                    $subscriberCount++;
                }
            }
        }
        
        echo "Score update broadcast to {$subscriberCount} subscribers\n";
        
        // Log broadcast
        $this->logBroadcast($sessionId, 'score_update', $subscriberCount);
    }
    
    private function broadcastConflict($sessionId, $conflicts, $originalData)
    {
        $message = json_encode([
            'type' => 'conflict_detected',
            'data' => [
                'team_id' => $originalData['team_id'],
                'criteria_id' => $originalData['criteria_id'],
                'conflicts' => $conflicts,
                'timestamp' => microtime(true)
            ]
        ]);
        
        // Send only to judges in this session
        if (isset($this->judges[$sessionId])) {
            foreach ($this->judges[$sessionId] as $judgeConn) {
                $judgeConn->send($message);
            }
        }
        
        echo "Conflict broadcast to judges in session {$sessionId}\n";
    }
    
    private function handlePing($conn)
    {
        // Update last ping time
        $this->db->query(
            "UPDATE websocket_connections SET last_ping = NOW() WHERE connection_id = ?",
            [$conn->connectionId]
        );
        
        // Send pong response
        $conn->send(json_encode([
            'type' => 'pong',
            'server_time' => microtime(true)
        ]));
    }
    
    private function generateConnectionId()
    {
        return 'ws_' . uniqid() . '_' . mt_rand(1000, 9999);
    }
    
    private function getClientIP($conn)
    {
        // In a real implementation, you'd extract this from headers
        return '127.0.0.1'; // Placeholder
    }
    
    private function getClientUserAgent($conn)
    {
        // Extract from request headers
        return $conn->httpRequest->getHeader('User-Agent')[0] ?? 'Unknown';
    }
    
    private function detectDeviceType($params)
    {
        $userAgent = $params['user_agent'] ?? '';
        
        if (stripos($userAgent, 'mobile') !== false) return 'mobile';
        if (stripos($userAgent, 'tablet') !== false) return 'tablet';
        if (stripos($userAgent, 'tv') !== false) return 'tv';
        
        return 'desktop';
    }
    
    private function detectBrowserType($conn)
    {
        $userAgent = $this->getClientUserAgent($conn);
        
        if (stripos($userAgent, 'chrome') !== false) return 'chrome';
        if (stripos($userAgent, 'firefox') !== false) return 'firefox';
        if (stripos($userAgent, 'safari') !== false) return 'safari';
        if (stripos($userAgent, 'edge') !== false) return 'edge';
        
        return 'unknown';
    }
    
    private function sendError($conn, $message)
    {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message,
            'timestamp' => microtime(true)
        ]));
    }
    
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Update database
        if (isset($conn->connectionId)) {
            $this->db->query(
                "UPDATE websocket_connections SET disconnected_at = NOW() WHERE connection_id = ?",
                [$conn->connectionId]
            );
        }
        
        // Remove from subscribers
        if (isset($conn->sessionId, $this->subscribers[$conn->sessionId])) {
            $this->subscribers[$conn->sessionId] = array_filter(
                $this->subscribers[$conn->sessionId],
                function($client) use ($conn) {
                    return $client !== $conn;
                }
            );
        }
        
        echo "Connection closed: {$conn->resourceId}\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Connection error: {$e->getMessage()}\n";
        
        // Log error
        if (isset($conn->connectionId)) {
            $this->db->query(
                "UPDATE websocket_connections SET error_count = error_count + 1, last_error = ? WHERE connection_id = ?",
                [$e->getMessage(), $conn->connectionId]
            );
        }
        
        $conn->close();
    }
    
    // Additional helper methods
    private function getSessionState($sessionId) { return []; }
    private function getCurrentScores($sessionId) { return []; }
    private function getActiveJudges($sessionId) { return []; }
    private function updateAggregatedScores($teamId, $sessionId) { return true; }
    private function assessConnectionQuality($conn) { return 'good'; }
    private function updateConnectionStats($conn, $type) { return true; }
    private function logBroadcast($sessionId, $type, $count) { return true; }
}