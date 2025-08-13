<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use Exception;

class RoleController extends BaseController
{
    protected $auth;
    
    public function __construct()
    {
        parent::__construct();
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Display roles management page
     */
    public function index()
    {
        try {
            // Check authentication and admin role
            if (!$this->auth->check() || !$this->auth->hasRole('super_admin')) {
                return $this->redirect('/auth/login');
            }
            
            // Get role statistics (simplified first)
            $roleStats = $this->getRoleStatistics();
            
            // Get users by role (simplified first)
            $usersByRole = $this->getUsersByRole();
            
            return $this->view('admin/roles/index', [
                'pageTitle' => 'Role Management',
                'pageCSS' => ['/css/admin-roles.css'],
                'pageJS' => ['/js/admin-roles.js'],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'Role Management', 'url' => '']
                ],
                'roleStats' => $roleStats,
                'usersByRole' => $usersByRole,
                'availableRoles' => $this->getAvailableRoles(),
                'roleDescriptions' => $this->getRoleDescriptions(),
                'rolePermissions' => $this->getRolePermissions()
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Role management page error: ' . $e->getMessage());
            return $this->view('errors/500', [
                'error' => 'Unable to load role management page: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update user role via AJAX
     */
    public function updateUserRole()
    {
        try {
            // Check authentication and admin role
            if (!$this->auth->check() || !$this->auth->hasRole('super_admin')) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            $userId = Request::input('user_id');
            $newRole = Request::input('role');
            
            if (!$userId || !$newRole) {
                return Response::json(['error' => 'User ID and role are required'], 400);
            }
            
            // Validate role
            if (!in_array($newRole, array_keys($this->getAvailableRoles()))) {
                return Response::json(['error' => 'Invalid role specified'], 400);
            }
            
            // Get user
            $user = User::find($userId);
            if (!$user) {
                return Response::json(['error' => 'User not found'], 404);
            }
            
            // Prevent changing own role
            if ($user->id == $this->auth->id()) {
                return Response::json(['error' => 'Cannot change your own role'], 400);
            }
            
            // Update user role
            $user->role = $newRole;
            $user->updated_at = date('Y-m-d H:i:s');
            
            if ($user->save()) {
                // Log the role change
                $this->logger->info("User role updated", [
                    'user_id' => $userId,
                    'old_role' => $user->getOriginal('role'),
                    'new_role' => $newRole,
                    'updated_by' => $this->auth->id()
                ]);
                
                return Response::json([
                    'success' => true,
                    'message' => 'User role updated successfully',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->getFullName(),
                        'role' => $user->role,
                        'role_label' => $this->getAvailableRoles()[$user->role]
                    ]
                ]);
            } else {
                return Response::json(['error' => 'Failed to update user role'], 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Update user role error: ' . $e->getMessage());
            return Response::json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Get role statistics
     */
    public function getRoleStatistics()
    {
        try {
            $stats = [];
            $availableRoles = $this->getAvailableRoles();
            
            foreach ($availableRoles as $role => $label) {
                $result = $this->db->query("
                    SELECT COUNT(*) as count 
                    FROM users 
                    WHERE role = ? 
                    AND status = 'active' 
                    AND deleted_at IS NULL
                ", [$role]);
                
                $count = !empty($result) ? $result[0]['count'] : 0;
                    
                $stats[$role] = [
                    'count' => $count,
                    'label' => $label,
                    'percentage' => 0 // Will be calculated after getting total
                ];
            }
            
            // Calculate total active users
            $totalActiveUsers = array_sum(array_column($stats, 'count'));
            
            // Calculate percentages
            foreach ($stats as $role => &$stat) {
                if ($totalActiveUsers > 0) {
                    $stat['percentage'] = round(($stat['count'] / $totalActiveUsers) * 100, 1);
                }
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logger->error('Get role statistics error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get users organized by role
     */
    public function getUsersByRole()
    {
        try {
            $usersByRole = [];
            $availableRoles = $this->getAvailableRoles();
            
            foreach ($availableRoles as $role => $label) {
                $users = $this->db->query("
                    SELECT 
                        u.id,
                        u.username,
                        u.email,
                        u.first_name,
                        u.last_name,
                        u.status,
                        u.last_login,
                        u.created_at,
                        s.name as school_name
                    FROM users u
                    LEFT JOIN schools s ON u.school_id = s.id
                    WHERE u.role = ? 
                    AND u.deleted_at IS NULL
                    ORDER BY u.first_name, u.last_name
                    LIMIT 10
                ", [$role]);
                
                $totalResult = $this->db->query("
                    SELECT COUNT(*) as count 
                    FROM users 
                    WHERE role = ? 
                    AND deleted_at IS NULL
                ", [$role]);
                
                $usersByRole[$role] = [
                    'label' => $label,
                    'users' => $users,
                    'total_count' => !empty($totalResult) ? $totalResult[0]['count'] : 0
                ];
            }
            
            return $usersByRole;
            
        } catch (Exception $e) {
            $this->logger->error('Get users by role error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all users for a specific role (AJAX endpoint)
     */
    public function getUsersForRole($role)
    {
        try {
            // Check authentication and admin role
            if (!$this->auth->check() || !$this->auth->hasRole('super_admin')) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            // Validate role
            if (!in_array($role, array_keys($this->getAvailableRoles()))) {
                return Response::json(['error' => 'Invalid role specified'], 400);
            }
            
            $page = (int) Request::input('page', 1);
            $limit = 25;
            $offset = ($page - 1) * $limit;
            
            // Get users for the role
            $users = $this->db->query("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.status,
                    u.last_login,
                    u.created_at,
                    s.name as school_name
                FROM users u
                LEFT JOIN schools s ON u.school_id = s.id
                WHERE u.role = ? 
                AND u.deleted_at IS NULL
                ORDER BY u.first_name, u.last_name
                LIMIT ? OFFSET ?
            ", [$role, $limit, $offset]);
            
            // Get total count
            $totalResult = $this->db->query("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE role = ? 
                AND deleted_at IS NULL
            ", [$role]);
            $totalCount = !empty($totalResult) ? $totalResult[0]['count'] : 0;
            $totalPages = ceil($totalCount / $limit);
            
            return Response::json([
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_count' => $totalCount,
                    'per_page' => $limit
                ],
                'role' => [
                    'key' => $role,
                    'label' => $this->getAvailableRoles()[$role]
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Get users for role error: ' . $e->getMessage());
            return Response::json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Export role data
     */
    public function exportRoles()
    {
        try {
            // Check authentication and admin role
            if (!$this->auth->check() || !$this->auth->hasRole('super_admin')) {
                return Response::json(['error' => 'Permission denied'], 403);
            }
            
            $format = Request::input('format', 'csv');
            
            // Get all users with role information
            $users = $this->db->query("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.role,
                    u.status,
                    u.last_login,
                    u.created_at,
                    s.name as school_name
                FROM users u
                LEFT JOIN schools s ON u.school_id = s.id
                WHERE u.deleted_at IS NULL
                ORDER BY u.role, u.first_name, u.last_name
            ");
            
            if ($format === 'csv') {
                return $this->exportAsCSV($users);
            } else {
                return Response::json(['error' => 'Unsupported export format'], 400);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Export roles error: ' . $e->getMessage());
            return Response::json(['error' => 'Export failed'], 500);
        }
    }
    
    /**
     * Export data as CSV
     */
    protected function exportAsCSV($users)
    {
        $filename = 'roles_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID',
            'Username',
            'Email',
            'First Name',
            'Last Name',
            'Role',
            'Role Label',
            'Status',
            'School',
            'Last Login',
            'Created At'
        ]);
        
        // CSV data
        $availableRoles = $this->getAvailableRoles();
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['email'],
                $user['first_name'],
                $user['last_name'],
                $user['role'],
                $availableRoles[$user['role']] ?? $user['role'],
                $user['status'],
                $user['school_name'] ?? 'N/A',
                $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never',
                date('Y-m-d H:i:s', strtotime($user['created_at']))
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get available roles
     */
    protected function getAvailableRoles()
    {
        return [
            User::SUPER_ADMIN => 'Super Administrator',
            User::COMPETITION_ADMIN => 'Competition Administrator',
            User::SCHOOL_COORDINATOR => 'School Coordinator',
            User::TEAM_COACH => 'Team Coach',
            User::JUDGE => 'Judge',
            User::PARTICIPANT => 'Participant'
        ];
    }
    
    /**
     * Get role descriptions
     */
    protected function getRoleDescriptions()
    {
        return [
            User::SUPER_ADMIN => 'Full system access with all permissions. Can manage users, competitions, and system settings.',
            User::COMPETITION_ADMIN => 'Manages competitions, judges, and categories. Can view all schools and teams.',
            User::SCHOOL_COORDINATOR => 'Manages their school\'s teams and participants. Can register teams and upload documents.',
            User::TEAM_COACH => 'Manages specific teams and their participants. Can submit team entries and materials.',
            User::JUDGE => 'Evaluates team submissions and provides scores during competitions.',
            User::PARTICIPANT => 'Students participating in competitions. Limited to viewing their team information.'
        ];
    }
    
    /**
     * Get role permissions (simplified representation)
     */
    protected function getRolePermissions()
    {
        return [
            User::SUPER_ADMIN => [
                'User Management', 'School Management', 'Competition Management', 
                'System Settings', 'Reports & Analytics', 'Bulk Operations'
            ],
            User::COMPETITION_ADMIN => [
                'Competition Management', 'Judge Management', 'Category Management',
                'Team Oversight', 'Competition Reports'
            ],
            User::SCHOOL_COORDINATOR => [
                'School Management', 'Team Registration', 'Participant Management',
                'Document Upload', 'School Reports'
            ],
            User::TEAM_COACH => [
                'Team Management', 'Participant Management', 'Submission Upload',
                'Team Communication'
            ],
            User::JUDGE => [
                'Score Submissions', 'View Assignments', 'Access Evaluation Criteria',
                'Judge Dashboard'
            ],
            User::PARTICIPANT => [
                'View Team Info', 'View Schedule', 'View Results',
                'Profile Management'
            ]
        ];
    }
}