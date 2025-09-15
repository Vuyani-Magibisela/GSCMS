<?php

namespace App\Controllers\Judge;

use App\Controllers\BaseController;
use App\Models\EnhancedJudgeProfile;
use App\Models\User;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            $this->session->setFlash('error', 'Judge profile not found. Please contact support.');
            return $this->redirect('/auth/login');
        }
        
        $data = [
            'judge' => $judge,
            'assignments' => [], // Temporarily disabled: $this->getTodaysAssignments($judge['id']),
            'upcoming_competitions' => [], // Temporarily disabled: $this->getUpcomingCompetitions($judge['id']),
            'scoring_queue' => [], // Temporarily disabled: $this->getScoringQueue($judge['id']),
            'recent_activity' => [], // Temporarily disabled: $this->getRecentActivity($judge['id']),
            'performance_summary' => [
                'competitions_judged' => 0,
                'completion_rate' => 0,
                'on_time_rate' => 0,
                'avg_performance_rating' => 0
            ], // Temporarily disabled: $this->getPerformanceSummary($judge['id']),
            'notifications' => [], // Temporarily disabled: $this->getNotifications($judge['id']),
            'quick_stats' => [
                'today_assignments' => 0,
                'pending_scores' => 0,
                'unread_notifications' => 0,
                'current_streak' => 0
            ] // Temporarily disabled: $this->getQuickStats($judge['id'])
        ];
        
        return $this->view('judge/dashboard/index', $data);
    }
    
    public function profile()
    {
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            return $this->redirect('/judge/auth');
        }
        
        $data = [
            'judge' => $judge,
            'qualifications' => $this->getJudgeQualifications($judge['id']),
            'auth_methods' => $this->getAuthMethods($judge['id']),
            'devices' => $this->getTrustedDevices($judge['id']),
            'activity_log' => $this->getRecentActivityLog($judge['id'])
        ];
        
        return $this->view('judge/dashboard/profile', $data);
    }
    
    public function notifications()
    {
        $judge = $this->getCurrentJudge();
        
        if (!$judge) {
            return $this->redirect('/judge/auth');
        }
        
        $notifications = $this->getAllNotifications($judge['id']);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            return $this->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        }
        
        $data = [
            'judge' => $judge,
            'notifications' => $notifications
        ];
        
        return $this->view('judge/dashboard/notifications', $data);
    }
    
    public function markNotificationRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
        
        $notificationId = $this->input('notification_id');
        $judge = $this->getCurrentJudge();
        
        if (!$judge || !$notificationId) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }
        
        try {
            $this->db->query("
                UPDATE judge_notifications 
                SET read_at = NOW(), is_read = 1 
                WHERE id = ? AND judge_id = ?
            ", [$notificationId, $judge['id']]);
            
            return $this->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }
    
    private function getCurrentJudge()
    {
        // First try to get judge by judge_id from session (for dedicated judge login)
        $judgeId = $this->session->get('judge_id');

        if ($judgeId) {
            $judge = $this->db->query("
                SELECT jp.*, u.first_name, u.last_name, u.email, u.email_verified,
                       o.organization_name, o.organization_type
                FROM judge_profiles jp
                INNER JOIN users u ON jp.user_id = u.id
                LEFT JOIN organizations o ON jp.organization_id = o.id
                WHERE jp.id = ? AND jp.status = 'active'
            ", [$judgeId]);

            if (!empty($judge)) {
                return $judge[0];
            }
        }

        // Fallback: get judge by user_id for regular user login with judge role
        $userId = $this->session->get('user_id');
        $userRole = $this->session->get('user_role');

        if ($userId && $userRole === 'judge') {
            $judge = $this->db->query("
                SELECT jp.*, u.first_name, u.last_name, u.email, u.email_verified,
                       o.organization_name, o.organization_type
                FROM judge_profiles jp
                INNER JOIN users u ON jp.user_id = u.id
                LEFT JOIN organizations o ON jp.organization_id = o.id
                WHERE jp.user_id = ? AND jp.status = 'active'
            ", [$userId]);

            if (!empty($judge)) {
                // Set judge_id in session for future requests
                $this->session->set('judge_id', $judge[0]['id']);
                return $judge[0];
            }
        }

        return null;
    }
    
    private function getTodaysAssignments($judgeId)
    {
        return $this->db->query("
            SELECT jca.*, c.name as competition_name, c.start_date, c.end_date,
                   cat.category_name, v.name as venue_name, v.location as venue_location,
                   COUNT(tm.id) as total_matches,
                   COUNT(CASE WHEN tm.match_status = 'completed' THEN 1 END) as completed_matches
            FROM judge_competition_assignments jca
            INNER JOIN competitions c ON jca.competition_id = c.id
            LEFT JOIN categories cat ON jca.category_id = cat.id
            LEFT JOIN venues v ON jca.venue_id = v.id
            LEFT JOIN tournament_matches tm ON c.id = tm.tournament_id 
                AND jca.category_id = tm.bracket_category_id
            WHERE jca.judge_id = ?
            AND jca.session_date = CURDATE()
            AND jca.assignment_status IN ('assigned', 'confirmed')
            GROUP BY jca.id
            ORDER BY jca.session_time ASC
        ", [$judgeId]);
    }
    
    private function getUpcomingCompetitions($judgeId)
    {
        return $this->db->query("
            SELECT jca.*, c.name as competition_name, c.start_date, c.end_date,
                   cat.category_name, v.name as venue_name
            FROM judge_competition_assignments jca
            INNER JOIN competitions c ON jca.competition_id = c.id
            LEFT JOIN categories cat ON jca.category_id = cat.id
            LEFT JOIN venues v ON jca.venue_id = v.id
            WHERE jca.judge_id = ?
            AND jca.session_date > CURDATE()
            AND jca.assignment_status IN ('assigned', 'confirmed')
            ORDER BY jca.session_date ASC, jca.session_time ASC
            LIMIT 5
        ", [$judgeId]);
    }
    
    private function getScoringQueue($judgeId)
    {
        return $this->db->query("
            SELECT tm.id as match_id, tm.match_name, tm.scheduled_time,
                   t1.team_name as team1_name, t2.team_name as team2_name,
                   c.name as competition_name, cat.category_name,
                   CASE 
                       WHEN EXISTS(SELECT 1 FROM scores s WHERE s.match_id = tm.id AND s.judge_id = ?) 
                       THEN 'scored' 
                       ELSE 'pending' 
                   END as scoring_status
            FROM tournament_matches tm
            INNER JOIN judge_competition_assignments jca ON tm.tournament_id = jca.competition_id
            INNER JOIN competitions c ON tm.tournament_id = c.id
            INNER JOIN categories cat ON tm.bracket_category_id = cat.id
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            WHERE jca.judge_id = ?
            AND tm.match_status IN ('scheduled', 'in_progress')
            AND jca.assignment_status = 'confirmed'
            ORDER BY tm.scheduled_time ASC
            LIMIT 10
        ", [$judgeId, $judgeId]);
    }
    
    private function getRecentActivity($judgeId)
    {
        return $this->db->query("
            SELECT action, ip_address, device_type, success, failure_reason,
                   created_at, 
                   CASE action
                       WHEN 'login' THEN 'Logged into system'
                       WHEN 'logout' THEN 'Logged out of system'
                       WHEN 'score_submit' THEN 'Submitted scores'
                       WHEN 'score_edit' THEN 'Modified scores'
                       WHEN 'profile_update' THEN 'Updated profile'
                       ELSE action
                   END as activity_description
            FROM judge_access_logs
            WHERE judge_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ", [$judgeId]);
    }
    
    private function getPerformanceSummary($judgeId)
    {
        $summary = $this->db->query("
            SELECT 
                COUNT(DISTINCT jca.competition_id) as competitions_judged,
                COUNT(jca.id) as total_assignments,
                COUNT(CASE WHEN jca.assignment_status = 'completed' THEN 1 END) as completed_assignments,
                AVG(jca.performance_rating) as avg_performance_rating,
                COUNT(CASE WHEN jca.check_in_time IS NOT NULL THEN 1 END) as on_time_checkins
            FROM judge_competition_assignments jca
            WHERE jca.judge_id = ?
            AND jca.session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$judgeId]);
        
        $baseStats = !empty($summary) ? $summary[0] : [
            'competitions_judged' => 0,
            'total_assignments' => 0,
            'completed_assignments' => 0,
            'avg_performance_rating' => 0,
            'on_time_checkins' => 0
        ];
        
        // Calculate completion rate
        $baseStats['completion_rate'] = $baseStats['total_assignments'] > 0 
            ? round(($baseStats['completed_assignments'] / $baseStats['total_assignments']) * 100, 1)
            : 0;
            
        // Calculate on-time rate
        $baseStats['on_time_rate'] = $baseStats['total_assignments'] > 0 
            ? round(($baseStats['on_time_checkins'] / $baseStats['total_assignments']) * 100, 1)
            : 0;
        
        return $baseStats;
    }
    
    private function getNotifications($judgeId)
    {
        return $this->db->query("
            SELECT * FROM judge_notifications
            WHERE judge_id = ?
            AND (is_read = 0 OR created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))
            ORDER BY created_at DESC
            LIMIT 5
        ", [$judgeId]);
    }
    
    private function getAllNotifications($judgeId)
    {
        return $this->db->query("
            SELECT * FROM judge_notifications
            WHERE judge_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$judgeId]);
    }
    
    private function getQuickStats($judgeId)
    {
        $stats = [];
        
        // Today's assignments
        $todayAssignments = $this->db->query("
            SELECT COUNT(*) as count
            FROM judge_competition_assignments
            WHERE judge_id = ? AND session_date = CURDATE()
            AND assignment_status IN ('assigned', 'confirmed')
        ", [$judgeId]);
        $stats['today_assignments'] = $todayAssignments[0]['count'] ?? 0;
        
        // Unread notifications
        $unreadNotifications = $this->db->query("
            SELECT COUNT(*) as count
            FROM judge_notifications
            WHERE judge_id = ? AND is_read = 0
        ", [$judgeId]);
        $stats['unread_notifications'] = $unreadNotifications[0]['count'] ?? 0;
        
        // Pending scores
        $pendingScores = $this->db->query("
            SELECT COUNT(DISTINCT tm.id) as count
            FROM tournament_matches tm
            INNER JOIN judge_competition_assignments jca ON tm.tournament_id = jca.competition_id
            WHERE jca.judge_id = ?
            AND tm.match_status IN ('completed', 'pending_scores')
            AND jca.assignment_status = 'confirmed'
            AND NOT EXISTS(
                SELECT 1 FROM scores s 
                WHERE s.match_id = tm.id AND s.judge_id = ?
            )
        ", [$judgeId, $judgeId]);
        $stats['pending_scores'] = $pendingScores[0]['count'] ?? 0;
        
        // Current streak (consecutive days with assignments)
        $streak = $this->calculateCurrentStreak($judgeId);
        $stats['current_streak'] = $streak;
        
        return $stats;
    }
    
    private function calculateCurrentStreak($judgeId)
    {
        $dates = $this->db->query("
            SELECT DISTINCT session_date
            FROM judge_competition_assignments
            WHERE judge_id = ?
            AND assignment_status = 'completed'
            AND session_date <= CURDATE()
            ORDER BY session_date DESC
            LIMIT 30
        ", [$judgeId]);
        
        if (empty($dates)) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = new \DateTime();
        
        foreach ($dates as $date) {
            $assignmentDate = new \DateTime($date['session_date']);
            $daysDiff = $currentDate->diff($assignmentDate)->days;
            
            if ($daysDiff === $streak) {
                $streak++;
                $currentDate = $assignmentDate;
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    private function getJudgeQualifications($judgeId)
    {
        return $this->db->query("
            SELECT * FROM judge_qualifications
            WHERE judge_id = ?
            ORDER BY issue_date DESC
        ", [$judgeId]);
    }
    
    private function getAuthMethods($judgeId)
    {
        return $this->db->query("
            SELECT auth_method, two_factor_enabled, pin_code IS NOT NULL as has_pin,
                   last_login, password_changed_at
            FROM judge_auth
            WHERE judge_id = ?
        ", [$judgeId]);
    }
    
    private function getTrustedDevices($judgeId)
    {
        return $this->db->query("
            SELECT device_name, device_type, browser, os, last_used, trusted, created_at
            FROM judge_devices
            WHERE judge_id = ? AND blocked = 0
            ORDER BY last_used DESC
            LIMIT 10
        ", [$judgeId]);
    }
    
    private function getRecentActivityLog($judgeId)
    {
        return $this->db->query("
            SELECT action, ip_address, device_type, success, failure_reason, created_at
            FROM judge_access_logs
            WHERE judge_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ", [$judgeId]);
    }
}