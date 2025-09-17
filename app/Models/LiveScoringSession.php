<?php
// app/Models/LiveScoringSession.php

namespace App\Models;

use App\Core\Database;

class LiveScoringSession extends BaseModel
{
    protected $table = 'live_scoring_sessions';
    
    protected $fillable = [
        'competition_id', 'session_name', 'session_type', 'category_id',
        'venue_id', 'start_time', 'end_time', 'status', 'live_stream_url',
        'spectator_access_code', 'max_concurrent_judges', 'scoring_duration_minutes',
        'conflict_threshold_percent', 'auto_resolve_conflicts', 'head_judge_id',
        'session_metadata'
    ];
    
    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    
    // Session type constants
    const TYPE_PRACTICE = 'practice';
    const TYPE_QUALIFYING = 'qualifying';
    const TYPE_SEMIFINAL = 'semifinal';
    const TYPE_FINAL = 'final';
    
    public function competition()
    {
        $db = Database::getInstance();
        $competition = $db->query('SELECT * FROM competitions WHERE id = ?', [$this->competition_id]);
        return !empty($competition) ? $competition[0] : null;
    }
    
    public function category()
    {
        $db = Database::getInstance();
        $category = $db->query('SELECT * FROM categories WHERE id = ?', [$this->category_id]);
        return !empty($category) ? $category[0] : null;
    }
    
    public function venue()
    {
        if (!$this->venue_id) return null;
        
        $db = Database::getInstance();
        $venue = $db->query('SELECT * FROM venues WHERE id = ?', [$this->venue_id]);
        return !empty($venue) ? $venue[0] : null;
    }
    
    public function headJudge()
    {
        if (!$this->head_judge_id) return null;
        
        $db = Database::getInstance();
        $judge = $db->query('
            SELECT jp.*, u.first_name, u.last_name, u.email 
            FROM judge_profiles jp
            JOIN users u ON jp.user_id = u.id
            WHERE jp.id = ?
        ', [$this->head_judge_id]);
        return !empty($judge) ? $judge[0] : null;
    }
    
    public function getActiveConnections()
    {
        $db = Database::getInstance();
        return $db->query('
            SELECT wc.*, u.first_name, u.last_name
            FROM websocket_connections wc
            LEFT JOIN users u ON wc.user_id = u.id
            WHERE wc.session_id = ? AND wc.disconnected_at IS NULL
            ORDER BY wc.connected_at DESC
        ', [$this->id]);
    }
    
    public function getLiveScoreUpdates($teamId = null, $limit = 100)
    {
        $db = Database::getInstance();
        
        $sql = '
            SELECT lsu.*, jp.judge_code, u.first_name as judge_first_name, u.last_name as judge_last_name,
                   t.name as team_name, rc.criteria_name
            FROM live_score_updates lsu
            JOIN judge_profiles jp ON lsu.judge_id = jp.id
            JOIN users u ON jp.user_id = u.id
            JOIN teams t ON lsu.team_id = t.id
            JOIN rubric_criteria rc ON lsu.criteria_id = rc.id
            WHERE lsu.session_id = ?
        ';
        
        $params = [$this->id];
        
        if ($teamId) {
            $sql .= ' AND lsu.team_id = ?';
            $params[] = $teamId;
        }
        
        $sql .= ' ORDER BY lsu.server_timestamp DESC LIMIT ?';
        $params[] = $limit;
        
        return $db->query($sql, $params);
    }
    
    public function getConflicts($resolved = false)
    {
        $db = Database::getInstance();
        
        $sql = '
            SELECT lsu.*, jp.judge_code, u.first_name, u.last_name, t.name as team_name, rc.criteria_name
            FROM live_score_updates lsu
            JOIN judge_profiles jp ON lsu.judge_id = jp.id
            JOIN users u ON jp.user_id = u.id
            JOIN teams t ON lsu.team_id = t.id
            JOIN rubric_criteria rc ON lsu.criteria_id = rc.id
            WHERE lsu.session_id = ? AND lsu.sync_status = ?
            ORDER BY lsu.server_timestamp DESC
        ';
        
        $status = $resolved ? 'resolved' : 'conflict';
        
        return $db->query($sql, [$this->id, $status]);
    }
    
    public function getCurrentScoreboard()
    {
        $db = Database::getInstance();
        
        return $db->query('
            SELECT 
                t.id as team_id,
                t.name as team_name,
                s.name as school_name,
                COUNT(DISTINCT lsu.judge_id) as judges_scored,
                AVG(lsu.score_value) as average_score,
                MAX(lsu.server_timestamp) as last_update,
                RANK() OVER (ORDER BY AVG(lsu.score_value) DESC) as current_rank
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN live_score_updates lsu ON t.id = lsu.team_id AND lsu.session_id = ?
            WHERE t.category_id = ?
            GROUP BY t.id, t.name, s.name
            ORDER BY current_rank
        ', [$this->id, $this->category_id]);
    }
    
    public function startSession()
    {
        if ($this->status !== self::STATUS_SCHEDULED) {
            throw new \Exception('Session must be scheduled to start');
        }
        
        $db = Database::getInstance();
        $db->query('
            UPDATE live_scoring_sessions 
            SET status = ?, start_time = NOW() 
            WHERE id = ?
        ', [self::STATUS_ACTIVE, $this->id]);
        
        $this->status = self::STATUS_ACTIVE;
        $this->start_time = date('Y-m-d H:i:s');
        
        return $this;
    }
    
    public function pauseSession()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            throw new \Exception('Only active sessions can be paused');
        }
        
        $db = Database::getInstance();
        $db->query('
            UPDATE live_scoring_sessions 
            SET status = ?
            WHERE id = ?
        ', [self::STATUS_PAUSED, $this->id]);
        
        $this->status = self::STATUS_PAUSED;
        
        return $this;
    }
    
    public function resumeSession()
    {
        if ($this->status !== self::STATUS_PAUSED) {
            throw new \Exception('Only paused sessions can be resumed');
        }
        
        $db = Database::getInstance();
        $db->query('
            UPDATE live_scoring_sessions 
            SET status = ?
            WHERE id = ?
        ', [self::STATUS_ACTIVE, $this->id]);
        
        $this->status = self::STATUS_ACTIVE;
        
        return $this;
    }
    
    public function completeSession()
    {
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PAUSED])) {
            throw new \Exception('Session must be active or paused to complete');
        }
        
        $db = Database::getInstance();
        $db->query('
            UPDATE live_scoring_sessions 
            SET status = ?, end_time = NOW()
            WHERE id = ?
        ', [self::STATUS_COMPLETED, $this->id]);
        
        $this->status = self::STATUS_COMPLETED;
        $this->end_time = date('Y-m-d H:i:s');
        
        // Finalize all scores
        $this->finalizeScores();
        
        return $this;
    }
    
    public function generateSpectatorAccessCode()
    {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        
        $db = Database::getInstance();
        $db->query('
            UPDATE live_scoring_sessions 
            SET spectator_access_code = ?
            WHERE id = ?
        ', [$code, $this->id]);
        
        $this->spectator_access_code = $code;
        
        return $code;
    }
    
    public function getSessionStatistics()
    {
        $db = Database::getInstance();
        
        // Get connection stats
        $connectionStats = $db->query('
            SELECT 
                user_type,
                COUNT(*) as total_connections,
                AVG(TIMESTAMPDIFF(SECOND, connected_at, COALESCE(disconnected_at, NOW()))) as avg_duration_seconds,
                AVG(total_messages_sent + total_messages_received) as avg_messages,
                AVG(bandwidth_usage_mb) as avg_bandwidth_mb
            FROM websocket_connections
            WHERE session_id = ?
            GROUP BY user_type
        ', [$this->id]);
        
        // Get scoring stats
        $scoringStats = $db->query('
            SELECT 
                COUNT(DISTINCT team_id) as teams_scored,
                COUNT(DISTINCT judge_id) as active_judges,
                COUNT(*) as total_score_updates,
                COUNT(CASE WHEN sync_status = "conflict" THEN 1 END) as conflicts,
                COUNT(CASE WHEN sync_status = "resolved" THEN 1 END) as resolved_conflicts,
                AVG(TIMESTAMPDIFF(SECOND, client_timestamp, server_timestamp)) as avg_sync_delay_seconds
            FROM live_score_updates
            WHERE session_id = ?
        ', [$this->id]);
        
        return [
            'session_id' => $this->id,
            'status' => $this->status,
            'duration_minutes' => $this->getSessionDurationMinutes(),
            'connections' => $connectionStats,
            'scoring' => $scoringStats[0] ?? [],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    public function getSessionDurationMinutes()
    {
        if (!$this->start_time) return 0;
        
        $endTime = $this->end_time ?: date('Y-m-d H:i:s');
        return round((strtotime($endTime) - strtotime($this->start_time)) / 60, 1);
    }
    
    private function finalizeScores()
    {
        // This would integrate with the existing Score model to create final scores
        // from the live score updates
        $db = Database::getInstance();
        
        // Get all teams that were scored in this session
        $teams = $db->query('
            SELECT DISTINCT team_id 
            FROM live_score_updates 
            WHERE session_id = ?
        ', [$this->id]);
        
        foreach ($teams as $team) {
            $this->finalizeTeamScore($team['team_id']);
        }
    }
    
    private function finalizeTeamScore($teamId)
    {
        // Calculate final scores from live updates
        // This would create or update records in the main scores table
        $db = Database::getInstance();
        
        // Get all score updates for this team
        $updates = $db->query('
            SELECT lsu.*, rc.max_points, rs.section_type, rs.multiplier
            FROM live_score_updates lsu
            JOIN rubric_criteria rc ON lsu.criteria_id = rc.id
            JOIN rubric_sections rs ON rc.section_id = rs.id
            WHERE lsu.session_id = ? AND lsu.team_id = ?
            AND lsu.sync_status IN ("synced", "resolved")
            ORDER BY lsu.server_timestamp DESC
        ', [$this->id, $teamId]);
        
        // Process into final scores (this would integrate with Score model)
        // For now, just log that we would do this
        echo "Would finalize scores for team {$teamId} with " . count($updates) . " updates\n";
    }
    
    public function getMetadata($key = null)
    {
        $metadata = $this->session_metadata ? json_decode($this->session_metadata, true) : [];
        
        if ($key) {
            return $metadata[$key] ?? null;
        }
        
        return $metadata;
    }
    
    public function setMetadata($key, $value = null)
    {
        $metadata = $this->getMetadata();
        
        if (is_array($key)) {
            $metadata = array_merge($metadata, $key);
        } else {
            $metadata[$key] = $value;
        }
        
        $db = Database::getInstance();
        $db->query('
            UPDATE live_scoring_sessions 
            SET session_metadata = ?
            WHERE id = ?
        ', [json_encode($metadata), $this->id]);
        
        $this->session_metadata = json_encode($metadata);
        
        return $this;
    }
    
    public static function getActiveSessionsForUser($userId, $userType = 'judge')
    {
        $db = Database::getInstance();
        
        $sql = '
            SELECT lss.*, c.name as competition_name, cat.name as category_name, v.name as venue_name
            FROM live_scoring_sessions lss
            JOIN competitions c ON lss.competition_id = c.id
            JOIN categories cat ON lss.category_id = cat.id
            LEFT JOIN venues v ON lss.venue_id = v.id
            WHERE lss.status IN ("scheduled", "active", "paused")
        ';
        
        if ($userType === 'judge') {
            // Only show sessions where this judge is assigned
            $sql .= ' AND EXISTS (
                SELECT 1 FROM judge_competition_assignments jca
                WHERE jca.judge_id = ? 
                AND jca.competition_id = lss.competition_id
                AND jca.assignment_status = "confirmed"
            )';
            $params = [$userId];
        } else {
            $params = [];
        }

        $sql .= ' ORDER BY lss.start_time ASC';

        return $db->query($sql, $params);
    }

    /**
     * Get all active scoring sessions (for admin use)
     */
    public static function getActiveSessions()
    {
        $db = Database::getInstance();

        $sql = '
            SELECT lss.*, c.name as competition_name, cat.name as category_name, v.name as venue_name
            FROM live_scoring_sessions lss
            JOIN competitions c ON lss.competition_id = c.id
            LEFT JOIN categories cat ON lss.category_id = cat.id
            LEFT JOIN venues v ON lss.venue_id = v.id
            WHERE lss.status IN ("scheduled", "active", "paused")
            ORDER BY lss.start_time ASC
        ';

        try {
            return $db->query($sql);
        } catch (\Exception $e) {
            error_log("Error getting active sessions: " . $e->getMessage());
            return [];
        }
    }
}