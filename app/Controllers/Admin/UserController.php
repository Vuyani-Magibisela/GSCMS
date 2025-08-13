<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Models\User;
use Exception;

class UserController extends BaseController
{
    /**
     * Display users management page
     */
    public function index()
    {
        try {
            // Manual auth check to avoid Auth::getInstance() issues
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
                return $this->redirect('/auth/login');
            }
            
            if ($_SESSION['user_role'] !== 'super_admin') {
                return $this->redirect('/auth/login');
            }
            
            // Get user list
            $users = $this->db->query("
                SELECT id, username, email, first_name, last_name, role, status, created_at
                FROM users 
                WHERE deleted_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT 25
            ");
            
            // Get schools for the edit form
            $schools = $this->db->query("SELECT id, name FROM schools ORDER BY name");
            
            // Return complete user management interface
            return $this->renderUserManagementPage($users, $schools ?? []);
            
        } catch (Exception $e) {
            error_log('User management page error: ' . $e->getMessage());
            return $this->view('errors/500', [
                'error' => 'Unable to load user management page: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show user details
     */
    public function show($request = null)
    {
        try {
            // Manual auth check
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
                return $this->redirect('/auth/login');
            }
            
            // Extract ID from URL path - simple approach
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            $id = end($segments);
            
            // Get user details
            $users = $this->db->query("
                SELECT u.*, s.name as school_name 
                FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                WHERE u.id = ? AND u.deleted_at IS NULL
            ", [$id]);
            
            if (empty($users)) {
                return '<h1>User not found</h1><p><a href="' . $this->baseUrl('/admin/users') . '">Back to Users</a></p>';
            }
            
            $user = $users[0];
            
            return $this->renderUserDetailsPage($user);
            
        } catch (Exception $e) {
            error_log('User show error: ' . $e->getMessage());
            return $this->view('errors/500', [
                'error' => 'Unable to load user details: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show edit user form
     */
    public function edit($request = null)
    {
        try {
            // Manual auth check
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
                return $this->redirect('/auth/login');
            }
            
            // Extract ID from URL path
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            // Get the ID (should be before 'edit')
            $id = $segments[count($segments) - 2];
            
            // Get user details
            $users = $this->db->query("
                SELECT u.*, s.name as school_name 
                FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                WHERE u.id = ? AND u.deleted_at IS NULL
            ", [$id]);
            
            if (empty($users)) {
                return '<h1>User not found</h1><p><a href="' . $this->baseUrl('/admin/users') . '">Back to Users</a></p>';
            }
            
            $user = $users[0];
            $schools = $this->db->query("SELECT id, name FROM schools ORDER BY name");
            
            return $this->renderEditUserPage($user, $schools ?: []);
            
        } catch (Exception $e) {
            error_log('User edit form error: ' . $e->getMessage());
            return $this->view('errors/500', [
                'error' => 'Unable to load user edit form: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update user
     */
    public function update($request = null)
    {
        try {
            // Manual auth check
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
                return $this->redirect('/auth/login');
            }
            
            // Extract ID from URL path
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            $id = end($segments);
            
            // Handle method override for forms
            if ($this->input('_method') && $this->input('_method') !== 'PUT' && $this->input('_method') !== 'POST') {
                return $this->redirect('/admin/users/' . $id . '/edit?error=invalid_method');
            }
            
            // Get current user
            $currentUsers = $this->db->query("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);
            if (empty($currentUsers)) {
                return $this->view('errors/404', ['message' => 'User not found']);
            }
            
            // Collect form data
            $data = [
                'first_name' => $this->input('first_name'),
                'last_name' => $this->input('last_name'),
                'email' => $this->input('email'),
                'username' => $this->input('username'),
                'role' => $this->input('role'),
                'status' => $this->input('status'),
                'school_id' => $this->input('school_id') ?: null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update password if provided
            if ($password = $this->input('password')) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            // Build update query
            $setParts = [];
            $bindings = [];
            foreach ($data as $column => $value) {
                $setParts[] = "$column = ?";
                $bindings[] = $value;
            }
            $bindings[] = $id; // for WHERE clause
            
            $this->db->statement("
                UPDATE users SET " . implode(', ', $setParts) . "
                WHERE id = ?
            ", $bindings);
            
            return $this->redirect($this->baseUrl('/admin/users/' . $id . '?success=updated'));
            
        } catch (Exception $e) {
            error_log('User update error: ' . $e->getMessage());
            return $this->redirect($this->baseUrl('/admin/users/' . $id . '/edit?error=update_failed'));
        }
    }
    
    /**
     * Update user status via AJAX
     */
    public function updateStatus()
    {
        // Ensure we're outputting JSON
        header('Content-Type: application/json');
        
        try {
            // Manual auth check
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                exit;
            }
            
            // Handle JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null) {
                // Fallback to regular form input
                $userId = $this->input('user_id');
                $status = $this->input('status');
            } else {
                $userId = $input['user_id'] ?? null;
                $status = $input['status'] ?? null;
            }
            
            if (!$userId || !$status) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID and status are required']);
                exit;
            }
            
            // Update user status
            $this->db->statement("
                UPDATE users 
                SET status = ?, updated_at = ? 
                WHERE id = ?
            ", [$status, date('Y-m-d H:i:s'), $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User status updated successfully'
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log('User status update error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user status']);
            exit;
        }
    }
    
    /**
     * Delete user (soft delete)
     */
    public function destroy($request = null)
    {
        try {
            // Manual auth check
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
                return $this->redirect('/auth/login');
            }
            
            // Extract ID from URL path - handle both /admin/users/{id}/delete and /admin/users/{id}
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            if (end($segments) === 'delete') {
                array_pop($segments); // Remove 'delete'
                $id = end($segments);
            } else {
                $id = end($segments);
            }
            
            // Handle method override for forms
            if ($this->input('_method') && $this->input('_method') !== 'DELETE' && $this->input('_method') !== 'POST') {
                return $this->redirect('/admin/users/' . $id . '?error=invalid_method');
            }
            
            // Prevent admin from deleting themselves
            if ($id == $_SESSION['user_id']) {
                return $this->redirect($this->baseUrl('/admin/users?error=cannot_delete_self'));
            }
            
            // Soft delete user
            $this->db->statement("
                UPDATE users 
                SET deleted_at = ?, status = 'inactive' 
                WHERE id = ?
            ", [date('Y-m-d H:i:s'), $id]);
            
            // Immediate redirect with proper headers
            $redirectUrl = $this->baseUrl('/admin/users?success=user_deleted');
            header('Location: ' . $redirectUrl);
            exit;
            
        } catch (Exception $e) {
            error_log('User delete error: ' . $e->getMessage());
            return $this->redirect($this->baseUrl('/admin/users?error=delete_failed'));
        }
    }
    
    /**
     * Render complete user management page
     */
    private function renderUserManagementPage($users, $schools)
    {
        $baseUrl = $this->baseUrl('');
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - GSCMS Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="' . $baseUrl . '/css/admin-users.css">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8f9fa; }
        .admin-header { background: #343a40; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { margin: 0; font-size: 1.5rem; }
        .admin-nav { display: flex; gap: 1rem; }
        .admin-nav a { color: #adb5bd; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; }
        .admin-nav a:hover { background: #495057; color: white; }
        .admin-content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { margin: 0; color: #495057; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; } .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; } .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; }
        .card-body { padding: 1.5rem; }
        .table { width: 100%; border-collapse: collapse; margin: 0; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table th { background: #f8f9fa; font-weight: 600; color: #495057; }
        .table tbody tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 4px; text-transform: uppercase; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .actions { display: flex; gap: 0.5rem; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1><i class="fas fa-robot"></i> GSCMS Admin</h1>
        <nav class="admin-nav">
            <a href="' . $baseUrl . '/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="' . $baseUrl . '/admin/users" class="active"><i class="fas fa-users"></i> Users</a>
            <a href="' . $baseUrl . '/admin/schools"><i class="fas fa-school"></i> Schools</a>
            <a href="' . $baseUrl . '/admin/teams"><i class="fas fa-user-friends"></i> Teams</a>
            <a href="/auth/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="admin-content">
        <div class="page-header">
            <h2><i class="fas fa-users"></i> User Management</h2>
        </div>';

        // Success/Error messages
        if (isset($_GET['success'])) {
            $messages = [
                'updated' => 'User updated successfully',
                'user_deleted' => 'User deleted successfully'
            ];
            $message = $messages[$_GET['success']] ?? 'Operation completed successfully';
            $html .= '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
        }
        
        if (isset($_GET['error'])) {
            $messages = [
                'update_failed' => 'Failed to update user',
                'delete_failed' => 'Failed to delete user',
                'cannot_delete_self' => 'You cannot delete yourself'
            ];
            $message = $messages[$_GET['error']] ?? 'An error occurred';
            $html .= '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
        }

        $html .= '
        <div class="card">
            <div class="card-header">Users (' . count($users) . ')</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>';

        foreach ($users as $user) {
            $statusClass = [
                'active' => 'badge-success',
                'pending' => 'badge-warning',
                'suspended' => 'badge-danger',
                'inactive' => 'badge-secondary'
            ][$user['status']] ?? 'badge-secondary';
            
            $roleDisplay = [
                'super_admin' => 'Super Admin',
                'competition_admin' => 'Competition Admin',
                'school_coordinator' => 'School Coordinator',
                'team_coach' => 'Team Coach',
                'judge' => 'Judge',
                'participant' => 'Participant'
            ][$user['role']] ?? ucfirst($user['role']);

            $html .= '
                            <tr>
                                <td><strong>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</strong><br>
                                    <small class="text-muted">@' . htmlspecialchars($user['username']) . '</small></td>
                                <td>' . htmlspecialchars($user['email']) . '</td>
                                <td>' . htmlspecialchars($roleDisplay) . '</td>
                                <td><span class="badge ' . $statusClass . '">' . ucfirst($user['status']) . '</span></td>
                                <td>' . date('M j, Y', strtotime($user['created_at'])) . '</td>
                                <td>
                                    <div class="actions">
                                        <a href="' . $baseUrl . '/admin/users/' . $user['id'] . '" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="' . $baseUrl . '/admin/users/' . $user['id'] . '/edit" class="btn btn-sm btn-warning" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>';
            
            if ($user['status'] === 'active') {
                $html .= '
                                        <button onclick="updateStatus(' . $user['id'] . ', \'suspended\')" class="btn btn-sm btn-warning" title="Suspend User">
                                            <i class="fas fa-pause"></i>
                                        </button>';
            } else {
                $html .= '
                                        <button onclick="updateStatus(' . $user['id'] . ', \'active\')" class="btn btn-sm btn-success" title="Activate User">
                                            <i class="fas fa-play"></i>
                                        </button>';
            }
            
            if ($user['id'] != $_SESSION['user_id']) {
                $html .= '
                                        <button onclick="deleteUser(' . $user['id'] . ', \'' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '\')" class="btn btn-sm btn-danger" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>';
            }
            
            $html .= '
                                    </div>
                                </td>
                            </tr>';
        }

        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function updateStatus(userId, status) {
            if (confirm("Are you sure you want to " + status + " this user?")) {
                fetch("' . $baseUrl . '/admin/users/update-status", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "user_id=" + userId + "&status=" + status
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Error: " + (data.error || "Failed to update status"));
                    }
                })
                .catch(error => {
                    alert("Error: " + error.message);
                });
            }
        }

        function deleteUser(userId, userName) {
            if (confirm("Are you sure you want to delete " + userName + "? This action cannot be undone.")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "' . $baseUrl . '/admin/users/" + userId + "/delete";
                
                const methodInput = document.createElement("input");
                methodInput.type = "hidden";
                methodInput.name = "_method";
                methodInput.value = "DELETE";
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>';

        return $html;
    }
    
    /**
     * Render user details page
     */
    private function renderUserDetailsPage($user)
    {
        $baseUrl = $this->baseUrl('');
        
        $roleDisplay = [
            'super_admin' => 'Super Administrator',
            'competition_admin' => 'Competition Administrator',
            'school_coordinator' => 'School Coordinator',
            'team_coach' => 'Team Coach',
            'judge' => 'Judge',
            'participant' => 'Participant'
        ][$user['role']] ?? ucfirst($user['role']);
        
        $statusClass = [
            'active' => 'badge-success',
            'pending' => 'badge-warning',
            'suspended' => 'badge-danger',
            'inactive' => 'badge-secondary'
        ][$user['status']] ?? 'badge-secondary';
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ' - GSCMS Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8f9fa; }
        .admin-header { background: #343a40; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { margin: 0; font-size: 1.5rem; }
        .admin-nav { display: flex; gap: 1rem; }
        .admin-nav a { color: #adb5bd; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; }
        .admin-nav a:hover { background: #495057; color: white; }
        .admin-nav a.active { background: #007bff; color: white; }
        .admin-content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .breadcrumb { margin-bottom: 1.5rem; color: #6c757d; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 0.5rem; }
        .btn:hover { background: #0056b3; }
        .btn-warning { background: #ffc107; color: #212529; } .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; } .btn-secondary:hover { background: #545b62; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; }
        .card-body { padding: 1.5rem; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 4px; text-transform: uppercase; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .detail-row { display: flex; margin-bottom: 1rem; }
        .detail-label { font-weight: 600; min-width: 120px; color: #495057; }
        .detail-value { color: #212529; }
        .user-avatar { width: 80px; height: 80px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 600; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1><i class="fas fa-robot"></i> GSCMS Admin</h1>
        <nav class="admin-nav">
            <a href="' . $baseUrl . '/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="' . $baseUrl . '/admin/users" class="active"><i class="fas fa-users"></i> Users</a>
            <a href="' . $baseUrl . '/admin/schools"><i class="fas fa-school"></i> Schools</a>
            <a href="' . $baseUrl . '/admin/teams"><i class="fas fa-user-friends"></i> Teams</a>
            <a href="/auth/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="admin-content">
        <nav class="breadcrumb">
            <a href="' . $baseUrl . '/admin/dashboard">Dashboard</a> &gt; 
            <a href="' . $baseUrl . '/admin/users">Users</a> &gt; 
            User Details
        </nav>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> User Details: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 2rem;">
                    <div>
                        <div class="user-avatar">' . strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) . '</div>
                    </div>
                    <div style="flex: 1;">
                        <div class="detail-row">
                            <div class="detail-label">Full Name:</div>
                            <div class="detail-value">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Username:</div>
                            <div class="detail-value">@' . htmlspecialchars($user['username']) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email:</div>
                            <div class="detail-value">' . htmlspecialchars($user['email']) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Phone:</div>
                            <div class="detail-value">' . htmlspecialchars($user['phone'] ?? 'Not provided') . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Role:</div>
                            <div class="detail-value">' . htmlspecialchars($roleDisplay) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value"><span class="badge ' . $statusClass . '">' . ucfirst($user['status']) . '</span></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">School:</div>
                            <div class="detail-value">' . htmlspecialchars($user['school_name'] ?? 'No school assigned') . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email Verified:</div>
                            <div class="detail-value">' . ($user['email_verified'] ? '<i class="fas fa-check text-success"></i> Yes' : '<i class="fas fa-times text-danger"></i> No') . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Last Login:</div>
                            <div class="detail-value">' . ($user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'Never') . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Created:</div>
                            <div class="detail-value">' . date('M j, Y H:i', strtotime($user['created_at'])) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Updated:</div>
                            <div class="detail-value">' . ($user['updated_at'] ? date('M j, Y H:i', strtotime($user['updated_at'])) : 'Never') . '</div>
                        </div>
                    </div>
                </div>
                
                <hr style="margin: 2rem 0;">
                
                <div style="display: flex; gap: 1rem;">
                    <a href="' . $baseUrl . '/admin/users/' . $user['id'] . '/edit" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit User
                    </a>';
        
        if ($user['id'] != $_SESSION['user_id']) {
            $html .= '
                    <button onclick="deleteUser(' . $user['id'] . ', \'' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '\')" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete User
                    </button>';
        }
        
        $html .= '
                    <a href="' . $baseUrl . '/admin/users" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function deleteUser(userId, userName) {
            if (confirm("Are you sure you want to delete " + userName + "? This action cannot be undone.")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "' . $baseUrl . '/admin/users/" + userId + "/delete";
                
                const methodInput = document.createElement("input");
                methodInput.type = "hidden";
                methodInput.name = "_method";
                methodInput.value = "DELETE";
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>';
    }
    
    /**
     * Render edit user page
     */
    private function renderEditUserPage($user, $schools)
    {
        $baseUrl = $this->baseUrl('');
        
        $availableRoles = [
            'super_admin' => 'Super Administrator',
            'competition_admin' => 'Competition Administrator',
            'school_coordinator' => 'School Coordinator',
            'team_coach' => 'Team Coach',
            'judge' => 'Judge',
            'participant' => 'Participant'
        ];
        
        $availableStatuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
            'suspended' => 'Suspended'
        ];
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ' - GSCMS Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8f9fa; }
        .admin-header { background: #343a40; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { margin: 0; font-size: 1.5rem; }
        .admin-nav { display: flex; gap: 1rem; }
        .admin-nav a { color: #adb5bd; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; }
        .admin-nav a:hover { background: #495057; color: white; }
        .admin-nav a.active { background: #007bff; color: white; }
        .admin-content { padding: 2rem; max-width: 800px; margin: 0 auto; }
        .breadcrumb { margin-bottom: 1.5rem; color: #6c757d; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 0.5rem; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; } .btn-success:hover { background: #1e7e34; }
        .btn-secondary { background: #6c757d; } .btn-secondary:hover { background: #545b62; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; }
        .card-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; }
        .form-control { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #ced4da; border-radius: 4px; font-size: 1rem; }
        .form-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 2px rgba(0,123,255,.25); }
        .form-select { background: white; background-image: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'%3e%3cpath fill=\'none\' stroke=\'%23343a40\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M2 5l6 6 6-6\'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px; }
        .form-row { display: flex; gap: 1rem; }
        .form-row .form-group { flex: 1; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1><i class="fas fa-robot"></i> GSCMS Admin</h1>
        <nav class="admin-nav">
            <a href="' . $baseUrl . '/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="' . $baseUrl . '/admin/users" class="active"><i class="fas fa-users"></i> Users</a>
            <a href="' . $baseUrl . '/admin/schools"><i class="fas fa-school"></i> Schools</a>
            <a href="' . $baseUrl . '/admin/teams"><i class="fas fa-user-friends"></i> Teams</a>
            <a href="/auth/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="admin-content">
        <nav class="breadcrumb">
            <a href="' . $baseUrl . '/admin/dashboard">Dashboard</a> &gt; 
            <a href="' . $baseUrl . '/admin/users">Users</a> &gt; 
            Edit User
        </nav>';

        // Success/Error messages
        if (isset($_GET['error'])) {
            $messages = [
                'update_failed' => 'Failed to update user'
            ];
            $message = $messages[$_GET['error']] ?? 'An error occurred';
            $html .= '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
        }

        $html .= '
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> Edit User: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '
            </div>
            <div class="card-body">
                <form method="POST" action="' . $baseUrl . '/admin/users/' . $user['id'] . '">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="' . htmlspecialchars($user['first_name']) . '" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="' . htmlspecialchars($user['last_name']) . '" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="username">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" value="' . htmlspecialchars($user['username']) . '" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="' . htmlspecialchars($user['email']) . '" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="' . htmlspecialchars($user['phone'] ?? '') . '">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="role">Role *</label>
                            <select class="form-control form-select" id="role" name="role" required>';
        
        foreach ($availableRoles as $roleValue => $roleName) {
            $selected = ($user['role'] === $roleValue) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($roleValue) . '"' . $selected . '>' . htmlspecialchars($roleName) . '</option>';
        }
        
        $html .= '</select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status *</label>
                            <select class="form-control form-select" id="status" name="status" required>';
        
        foreach ($availableStatuses as $statusValue => $statusName) {
            $selected = ($user['status'] === $statusValue) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($statusValue) . '"' . $selected . '>' . htmlspecialchars($statusName) . '</option>';
        }
        
        $html .= '</select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="school_id">School</label>
                        <select class="form-control form-select" id="school_id" name="school_id">
                            <option value="">No school assigned</option>';
        
        foreach ($schools as $school) {
            $selected = ($user['school_id'] == $school['id']) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($school['id']) . '"' . $selected . '>' . htmlspecialchars($school['name']) . '</option>';
        }
        
        $html .= '</select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                        <small class="text-muted">Only enter a password if you want to change it</small>
                    </div>
                    
                    <hr style="margin: 2rem 0;">
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="' . $baseUrl . '/admin/users/' . $user['id'] . '" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>';

        return $html;
    }
}