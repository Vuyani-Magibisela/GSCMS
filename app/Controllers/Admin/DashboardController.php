<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Models\User;
use App\Models\School;
use App\Models\Team;
use App\Models\Participant;
use App\Models\Competition;
use App\Models\Announcement;
use App\Models\ConsentForm;
use App\Models\TeamSubmission;

class DashboardController extends BaseController
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function index()
    {
        try {
            // Gather all dashboard statistics
            $stats = $this->gatherStatistics();
            $recentActivity = $this->getRecentActivity();
            $systemHealth = $this->getSystemHealth();
            $upcomingDeadlines = $this->getUpcomingDeadlines();
            $pendingApprovals = $this->getPendingApprovals();
            
            // Set up breadcrumbs
            $breadcrumbs = [
                ['title' => 'Admin', 'url' => '/admin/dashboard', 'icon' => 'fas fa-shield-alt'],
                ['title' => 'Dashboard', 'url' => '/admin/dashboard']
            ];

            // Render dashboard view
            return $this->view('admin/dashboard', [
                'title' => 'Admin Dashboard - GSCMS',
                'pageTitle' => 'Administrative Dashboard',
                'pageSubtitle' => 'Competition management system overview',
                'breadcrumbs' => $breadcrumbs,
                'stats' => $stats,
                'recentActivity' => $recentActivity,
                'systemHealth' => $systemHealth,
                'upcomingDeadlines' => $upcomingDeadlines,
                'pendingApprovals' => $pendingApprovals,
                'pageJS' => ['/js/admin-dashboard.js'],
                'pageCSS' => ['/css/admin-dashboard.css']
            ]);
            
        } catch (\Exception $e) {
            error_log("Dashboard Error: " . $e->getMessage());
            
            // Fallback with basic stats
            return $this->view('admin/dashboard', [
                'title' => 'Admin Dashboard - GSCMS',
                'pageTitle' => 'Administrative Dashboard',
                'error' => 'Some dashboard data could not be loaded. Please try again later.',
                'stats' => $this->getBasicStats(),
                'recentActivity' => [],
                'systemHealth' => ['status' => 'unknown'],
                'upcomingDeadlines' => [],
                'pendingApprovals' => []
            ]);
        }
    }

    private function gatherStatistics()
    {
        $stats = [];

        try {
            // Core statistics using direct SQL with proper soft delete handling
            $stats['total_schools'] = $this->getCountWithSoftDelete('schools');
            $stats['total_teams'] = $this->getCountWithSoftDelete('teams');
            $stats['total_participants'] = $this->getCountWithSoftDelete('participants');
            $stats['total_users'] = $this->getCountWithSoftDelete('users');
            
            // Competition statistics
            $stats['active_competitions'] = $this->getCount('competitions', ['status' => 'active']);
            $stats['completed_competitions'] = $this->getCount('competitions', ['status' => 'completed']);
            
            // Pending items requiring attention
            $stats['pending_approvals'] = $this->getCount('teams', ['status' => 'pending']);
            $stats['pending_consent_forms'] = $this->getCount('consent_forms', ['status' => 'pending']);
            // Check if team_submissions table exists before querying
            try {
                $stats['pending_submissions'] = $this->getCount('team_submissions', ['status' => 'pending']);
            } catch (\Exception $e) {
                error_log("Team submissions table not found: " . $e->getMessage());
                $stats['pending_submissions'] = 0;
            }
            
            // Registration trends (last 30 days)
            $stats['recent_registrations'] = $this->getRecentRegistrationCount();
            $stats['recent_logins'] = $this->getRecentLoginCount();
            
            // Document completion rates
            $stats['consent_completion_rate'] = $this->getConsentCompletionRate();
            // Check if team_submissions table exists for completion rate
            try {
                $stats['submission_completion_rate'] = $this->getSubmissionCompletionRate();
            } catch (\Exception $e) {
                error_log("Team submissions table not found for completion rate: " . $e->getMessage());
                $stats['submission_completion_rate'] = 0;
            }
            
            // Category breakdown
            $stats['teams_by_category'] = $this->getTeamsByCategory();
            
            // Geographic distribution
            $stats['schools_by_region'] = $this->getSchoolsByRegion();
            
        } catch (\Exception $e) {
            error_log("Statistics gathering error: " . $e->getMessage());
            $stats = $this->getBasicStats();
        }

        return $stats;
    }

    private function getCount($table, $conditions = [])
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    $whereClause[] = "{$field} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
            
        } catch (\Exception $e) {
            error_log("Count query error for {$table}: " . $e->getMessage());
            return 0;
        }
    }

    private function getCountWithSoftDelete($table, $conditions = [])
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE deleted_at IS NULL";
            $params = [];
            
            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $sql .= " AND {$field} = ?";
                    $params[] = $value;
                }
            }
            
            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
            
        } catch (\Exception $e) {
            error_log("Count query error for {$table}: " . $e->getMessage());
            return 0;
        }
    }

    private function getRecentActivity($limit = 10)
    {
        try {
            $activities = [];
            
            // Recent user registrations
            $recentUsers = $this->db->query("
                SELECT 'user_registered' as type, 
                       CONCAT(first_name, ' ', last_name) as description,
                       created_at as timestamp,
                       'fas fa-user-plus' as icon,
                       'text-success' as color
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            
            // Recent team registrations
            $recentTeams = $this->db->query("
                SELECT 'team_registered' as type,
                       CONCAT('Team \"', team_name, '\" registered') as description,
                       created_at as timestamp,
                       'fas fa-users' as icon,
                       'text-primary' as color
                FROM teams 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            
            // Recent announcements - check if table exists
            $recentAnnouncements = [];
            try {
                $recentAnnouncements = $this->db->query("
                    SELECT 'announcement_posted' as type,
                           CONCAT('Announcement: ', title) as description,
                           created_at as timestamp,
                           'fas fa-bullhorn' as icon,
                           'text-info' as color
                    FROM announcements 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                    ORDER BY created_at DESC 
                    LIMIT 3
                ");
            } catch (\Exception $e) {
                error_log("Announcements table not found: " . $e->getMessage());
                $recentAnnouncements = [];
            }
            
            // Merge and sort activities
            $activities = array_merge($recentUsers, $recentTeams, $recentAnnouncements);
            
            // Sort by timestamp descending
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            return array_slice($activities, 0, $limit);
            
        } catch (\Exception $e) {
            error_log("Recent activity error: " . $e->getMessage());
            return [];
        }
    }

    private function getSystemHealth()
    {
        try {
            $health = [
                'status' => 'healthy',
                'database' => 'connected',
                'storage' => 'available',
                'memory_usage' => 0,
                'disk_usage' => 0
            ];
            
            // Test database connection
            try {
                $this->db->query("SELECT 1");
                $health['database'] = 'connected';
            } catch (\Exception $e) {
                $health['database'] = 'error';
                $health['status'] = 'warning';
            }
            
            // Check storage directory
            $uploadsDir = APP_ROOT . '/public/uploads';
            if (!is_dir($uploadsDir) || !is_writable($uploadsDir)) {
                $health['storage'] = 'error';
                $health['status'] = 'warning';
            }
            
            // Memory usage
            $health['memory_usage'] = round(memory_get_usage(true) / 1024 / 1024, 2);
            
            // Disk usage (if possible)
            if (function_exists('disk_free_space')) {
                $bytes = disk_free_space(APP_ROOT);
                $health['disk_free'] = round($bytes / 1024 / 1024 / 1024, 2); // GB
            }
            
            return $health;
            
        } catch (\Exception $e) {
            error_log("System health check error: " . $e->getMessage());
            return ['status' => 'unknown'];
        }
    }

    private function getUpcomingDeadlines($limit = 5)
    {
        try {
            return $this->db->query("
                SELECT 
                    name as name,
                    registration_deadline as deadline,
                    'Registration Deadline' as type,
                    'fas fa-calendar-alt' as icon,
                    DATEDIFF(registration_deadline, NOW()) as days_remaining
                FROM competitions 
                WHERE registration_deadline > NOW() 
                  AND registration_deadline <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                  AND status = 'active'
                
                UNION ALL
                
                SELECT 
                    name as name,
                    date as deadline,
                    'Competition Date' as type,
                    'fas fa-trophy' as icon,
                    DATEDIFF(date, NOW()) as days_remaining
                FROM competitions 
                WHERE date > NOW() 
                  AND date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                  AND status = 'active'
                
                ORDER BY deadline ASC 
                LIMIT ?
            ", [$limit]);
            
        } catch (\Exception $e) {
            error_log("Upcoming deadlines error: " . $e->getMessage());
            return [];
        }
    }

    private function getPendingApprovals()
    {
        try {
            $submissions = 0;
            try {
                $submissions = $this->getCount('team_submissions', ['status' => 'pending']);
            } catch (\Exception $e) {
                error_log("Team submissions table not found in getPendingApprovals: " . $e->getMessage());
            }
            
            return [
                'teams' => $this->getCount('teams', ['status' => 'pending']),
                'participants' => $this->getCount('participants', ['status' => 'pending']),
                'consent_forms' => $this->getCount('consent_forms', ['status' => 'pending']),
                'submissions' => $submissions,
                'schools' => $this->getCount('schools', ['status' => 'pending'])
            ];
            
        } catch (\Exception $e) {
            error_log("Pending approvals error: " . $e->getMessage());
            return [
                'teams' => 0,
                'participants' => 0,
                'consent_forms' => 0,
                'submissions' => 0,
                'schools' => 0
            ];
        }
    }

    private function getRecentRegistrationCount()
    {
        try {
            return $this->db->query("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ")[0]['count'] ?? 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRecentLoginCount()
    {
        try {
            return $this->db->query("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ")[0]['count'] ?? 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getConsentCompletionRate()
    {
        try {
            $total = $this->getCount('participants');
            $completed = $this->getCount('consent_forms', ['status' => 'approved']);
            
            return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getSubmissionCompletionRate()
    {
        try {
            $total = $this->getCount('teams', ['status' => 'approved']);
            
            // Check if team_submissions table exists
            try {
                $completed = $this->getCount('team_submissions', ['status' => 'submitted']);
            } catch (\Exception $e) {
                error_log("Team submissions table not found in getSubmissionCompletionRate: " . $e->getMessage());
                return 0; // Return 0% completion if table doesn't exist
            }
            
            return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTeamsByCategory()
    {
        try {
            return $this->db->query("
                SELECT 
                    c.category_name as name,
                    COUNT(t.team_id) as count
                FROM categories c
                LEFT JOIN teams t ON c.category_id = t.category_id AND t.status = 'approved'
                GROUP BY c.category_id, c.category_name
                ORDER BY count DESC
            ");
            
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getSchoolsByRegion()
    {
        try {
            return $this->db->query("
                SELECT 
                    COALESCE(district, 'Unknown') as region,
                    COUNT(*) as count
                FROM schools 
                WHERE status = 'active'
                GROUP BY district
                ORDER BY count DESC
                LIMIT 10
            ");
            
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getBasicStats()
    {
        try {
            return [
                'total_schools' => $this->getCountWithSoftDelete('schools'),
                'total_teams' => $this->getCountWithSoftDelete('teams'),
                'total_participants' => $this->getCountWithSoftDelete('participants'),
                'total_users' => $this->getCountWithSoftDelete('users'),
                'active_competitions' => $this->getCountWithSoftDelete('competitions'),
                'pending_approvals' => 0
            ];
        } catch (\Exception $e) {
            return [
                'total_schools' => 0,
                'total_teams' => 0,
                'total_participants' => 0,
                'total_users' => 0,
                'active_competitions' => 0,
                'pending_approvals' => 0
            ];
        }
    }

    /**
     * Helper method to format time ago
     */
    public function formatTimeAgo($timestamp)
    {
        $now = new \DateTime();
        $time = new \DateTime($timestamp);
        $diff = $now->diff($time);
        
        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    /**
     * AJAX endpoint for system status
     */
    public function systemStatus()
    {
        try {
            $health = $this->getSystemHealth();
            $stats = $this->getBasicStats();
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'health' => $health,
                    'stats' => $stats,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error fetching system status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX endpoint for dashboard updates
     */
    public function dashboardUpdates()
    {
        try {
            $activity = $this->getRecentActivity(10);
            $deadlines = $this->getUpcomingDeadlines(5);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'recent_activity' => $activity,
                    'upcoming_deadlines' => $deadlines,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error fetching dashboard updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX endpoint for notifications
     */
    public function notifications()
    {
        try {
            $notifications = [
                [
                    'id' => 1,
                    'title' => 'New School Registration',
                    'message' => 'Westside High School has registered for the competition',
                    'type' => 'info',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'read' => false
                ],
                [
                    'id' => 2,
                    'title' => 'Team Submission',
                    'message' => 'Team Alpha submitted their project for review',
                    'type' => 'success',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                    'read' => false
                ],
                [
                    'id' => 3,
                    'title' => 'Competition Deadline',
                    'message' => 'Registration deadline in 5 days',
                    'type' => 'warning',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'read' => true
                ]
            ];
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $notifications,
                'unread_count' => count(array_filter($notifications, function($n) { return !$n['read']; }))
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error fetching notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX endpoint to mark notifications as read
     */
    public function markNotificationsRead()
    {
        try {
            // In a real implementation, this would update the database
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error marking notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }
}