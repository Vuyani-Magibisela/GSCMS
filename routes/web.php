<?php
// routes/web.php - Web Routes with RBAC Implementation

use App\Core\Auth;

// ============================================================================
// PUBLIC ROUTES (No authentication required)
// ============================================================================

// Home route
$router->get('/', 'HomeController@index', 'home');

// Public competition information
$router->get('/competitions/public', 'CompetitionController@publicIndex', 'competitions.public');

// About and other public pages
$router->get('/about', 'PublicController@about', 'about');
$router->get('/categories', 'PublicController@categories', 'categories');
$router->get('/schedule', 'PublicController@schedule', 'schedule');
$router->get('/leaderboard', 'PublicController@leaderboard', 'leaderboard');
$router->get('/announcements', 'PublicController@announcements', 'announcements');
$router->get('/resources', 'PublicController@resources', 'resources');

// School management redirect route
$router->get('/schools/manage', function() {
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $baseUrl . '/admin/schools');
    exit;
}, 'schools.manage.redirect');

// Test routes for framework verification
$router->get('/test', function($request, $response) {
    return json_encode([
        'message' => 'GSCMS Framework with RBAC is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);
}, 'test');

$router->get('/test-auth', function() {
    return '<h1>Auth Test Route Working!</h1><p><a href="/auth/login">Login</a> | <a href="/auth/register">Register</a></p>';
}, 'test-auth');

$router->get('/test-dashboard', function() {
    return '<h1>Dashboard Test Route Working!</h1><p>This confirms the dashboard route should work.</p>';
}, 'test-dashboard');

$router->get('/test-dashboard-controller', function() {
    $controller = new \App\Controllers\HomeController();
    return '<h1>Dashboard Controller Test</h1><p>HomeController exists and can be instantiated.</p>';
}, 'test-dashboard-controller');

$router->get('/test/db', 'TestController@database', 'test.db');

// Test form submission endpoint (development only)
$router->post('/test/form-submit', function() {
    if (($_ENV['APP_ENV'] ?? 'development') !== 'development') {
        return 'Access denied';
    }
    
    $request = new \App\Core\Request();
    
    return json_encode([
        'success' => true,
        'message' => 'Test form submission received',
        'data' => $request->all(),
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}, 'test.form-submit');

// Debug route for test page
$router->get('/test-admin', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Debug Test</title>
    </head>
    <body>
        <h1>Admin Debug Test</h1>
        
        <h2>Authentication Status</h2>
        <p><strong>Authenticated:</strong> ' . (\App\Core\Auth::getInstance()->check() ? 'YES' : 'NO') . '</p>
        ' . (\App\Core\Auth::getInstance()->check() ? '<p><strong>User ID:</strong> ' . \App\Core\Auth::getInstance()->id() . '</p>' : '') . '
        ' . (\App\Core\Auth::getInstance()->check() ? '<p><strong>User Role:</strong> ' . \App\Core\Auth::getInstance()->user()->role . '</p>' : '') . '
        
        <h2>Quick Links</h2>
        <ul>
            <li><a href="' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/dev-login-admin">Development Login (Admin)</a></li>
            <li><a href="' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/admin/dashboard">Admin Dashboard</a></li>
            <li><a href="' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/admin/schools/create">Create School Form</a></li>
        </ul>
        
        <h2>Test Form</h2>
        <form id="testForm">
            <input type="text" name="test_field" placeholder="Enter test value" required>
            <button type="submit">Test Submit</button>
        </form>
        
        <div id="result"></div>
        
        <script>
        document.getElementById("testForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch("' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/test/form-submit", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("result").innerHTML = "<h3>Test Result:</h3><pre>" + JSON.stringify(data, null, 2) + "</pre>";
            })
            .catch(error => {
                document.getElementById("result").innerHTML = "<h3>Test Error:</h3><p>" + error.message + "</p>";
            });
        });
        </script>
    </body>
    </html>';
}, 'test-admin');

// Development only - Login as admin for testing (remove in production)
$router->get('/dev-login-admin', function() {
    if (($_ENV['APP_ENV'] ?? 'development') !== 'development') {
        return 'Access denied';
    }
    
    // Properly authenticate using Auth class
    $auth = App\Core\Auth::getInstance();
    
    // Find existing admin user first
    $user = App\Models\User::findByEmail('admin@gscms.local');
    
    if (!$user) {
        // Try finding by email from seeding
        $user = App\Models\User::findByEmail('admin@gde.gov.za');
    }
    
    if (!$user) {
        // Create admin user if doesn't exist
        $userData = [
            'username' => 'devadmin',
            'email' => 'admin@gscms.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified' => 1
        ];
        
        try {
            $user = App\Models\User::createUser($userData);
        } catch (Exception $e) {
            return '<h1>Error creating admin user:</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    // Login the user properly through Auth class
    $auth->login($user);
    
    $baseUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    
    // Debug info
    $debugInfo = '<h2>Debug Info:</h2>';
    $debugInfo .= 'Authenticated: ' . ($auth->check() ? 'YES' : 'NO') . '<br>';
    if ($auth->check()) {
        $user = $auth->user();
        $debugInfo .= 'User ID: ' . $user->id . '<br>';
        $debugInfo .= 'User Role: ' . $user->role . '<br>';
        $debugInfo .= 'Is Admin: ' . ($user->isAdmin() ? 'YES' : 'NO') . '<br>';
        $debugInfo .= 'Has super_admin role: ' . ($user->hasRole('super_admin') ? 'YES' : 'NO') . '<br>';
        $session = App\Core\Session::getInstance();
        $debugInfo .= 'Session user_id: ' . ($session->get('user_id') ?? 'NOT SET') . '<br>';
        $debugInfo .= 'Session user_role: ' . ($session->get('user_role') ?? 'NOT SET') . '<br>';
    }
    
    return '<h1>Development Login Complete!</h1>' . $debugInfo . '<p><a href="' . $baseUrl . '/admin/dashboard">Go to Admin Dashboard</a></p>';
}, 'dev-login-admin');

// Development only - Test admin dashboard without middleware
$router->get('/test-admin-dashboard', function() {
    if (($_ENV['APP_ENV'] ?? 'development') !== 'development') {
        return 'Access denied';
    }
    
    $auth = App\Core\Auth::getInstance();
    $debugInfo = '<h2>Debug Info:</h2>';
    $debugInfo .= 'Authenticated: ' . ($auth->check() ? 'YES' : 'NO') . '<br>';
    if ($auth->check()) {
        $user = $auth->user();
        $debugInfo .= 'User ID: ' . $user->id . '<br>';
        $debugInfo .= 'User Role: ' . $user->role . '<br>';
        $debugInfo .= 'Is Admin: ' . ($user->isAdmin() ? 'YES' : 'NO') . '<br>';
        $debugInfo .= 'Has super_admin role: ' . ($user->hasRole('super_admin') ? 'YES' : 'NO') . '<br>';
    }
    
    // Try to instantiate the controller
    try {
        $controller = new \App\Controllers\Admin\DashboardController();
        $result = $controller->index();
        return '<h1>Admin Dashboard Test</h1>' . $debugInfo . '<hr><h2>Controller Output:</h2>' . $result;
    } catch (Exception $e) {
        return '<h1>Admin Dashboard Test - Error</h1>' . $debugInfo . '<hr><h2>Error:</h2>' . $e->getMessage();
    }
}, 'test-admin-dashboard');


// Test admin dashboard without middleware
$router->get('/apache-admin-test', function() {
    try {
        $controller = new \App\Controllers\Admin\DashboardController();
        $result = $controller->index();
        return '<h1>Apache Admin Dashboard Test - SUCCESS</h1><hr>' . $result;
    } catch (Exception $e) {
        return '<h1>Apache Admin Dashboard Test - ERROR</h1><p>Error: ' . $e->getMessage() . '</p>';
    }
}, 'apache-admin-test');

// Simple Apache test route
$router->get('/apache-test', function() {
    $debugInfo = '<h1>Apache Test Route Works!</h1>';
    $debugInfo .= '<p>PHP Version: ' . phpversion() . '</p>';
    $debugInfo .= '<p>Server: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</p>';
    $debugInfo .= '<p>REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . '</p>';
    $debugInfo .= '<p>SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME'] . '</p>';
    return $debugInfo;
}, 'apache-test');

// Simple debug auth route
$router->get('/debug-auth-simple', function() {
    $auth = \App\Core\Auth::getInstance();
    $output = '<h1>Simple Auth Debug</h1>';
    $output .= 'Authenticated: ' . ($auth->check() ? 'YES' : 'NO') . '<br>';
    if ($auth->check()) {
        $user = $auth->user();
        $output .= 'User Role: ' . ($user->role ?? 'NO ROLE') . '<br>';
        $output .= 'Has super_admin: ' . ($user->hasRole('super_admin') ? 'YES' : 'NO') . '<br>';
    }
    return $output;
}, 'debug-auth-simple');

// Development only - Test admin route without middleware
$router->get('/test-admin-direct', function() {
    if (($_ENV['APP_ENV'] ?? 'development') !== 'development') {
        return 'Access denied';
    }
    
    try {
        $controller = new \App\Controllers\Admin\DashboardController();
        return $controller->index();
    } catch (Exception $e) {
        return '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}, 'test-admin-direct');

// Development only - Debug session and role
$router->get('/debug-session', function() {
    if (($_ENV['APP_ENV'] ?? 'development') !== 'development') {
        return 'Access denied';
    }
    
    $auth = \App\Core\Auth::getInstance();
    $output = '<h1>Session & Role Debug</h1>';
    
    $output .= '<h2>Session Data:</h2>';
    $output .= '<pre>' . print_r($_SESSION, true) . '</pre>';
    
    $output .= '<h2>Auth Status:</h2>';
    $output .= 'Authenticated: ' . ($auth->check() ? 'YES' : 'NO') . '<br>';
    
    if ($auth->check()) {
        $user = $auth->user();
        $output .= 'User ID: ' . $user->id . '<br>';
        $output .= 'Username: ' . $user->username . '<br>';
        $output .= 'Role: ' . $user->role . '<br>';
        $output .= 'Is Admin: ' . ($user->isAdmin() ? 'YES' : 'NO') . '<br>';
        $output .= 'Has super_admin role: ' . ($user->hasRole('super_admin') ? 'YES' : 'NO') . '<br>';
        $output .= 'Has any admin roles: ' . ($user->hasAnyRole(['super_admin', 'competition_admin']) ? 'YES' : 'NO') . '<br>';
        
        // Test the middleware logic
        $output .= '<h2>Middleware Test:</h2>';
        try {
            $auth->requireAuth();
            $output .= 'requireAuth(): PASSED<br>';
        } catch (Exception $e) {
            $output .= 'requireAuth(): FAILED - ' . $e->getMessage() . '<br>';
        }
        
        try {
            $auth->requireAnyRole(['super_admin']);
            $output .= 'requireAnyRole([super_admin]): PASSED<br>';
        } catch (Exception $e) {
            $output .= 'requireAnyRole([super_admin]): FAILED - ' . $e->getMessage() . '<br>';
        }
    }
    
    return $output;
}, 'debug-session');

// Development only - Create test user route
$router->get('/create-test-user', function() {
    try {
        $user = \App\Models\User::createUser([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'school_coordinator',
            'status' => 'active',
            'email_verified' => 1
        ]);
        
        return '<h1>Test User Created!</h1><p>Username: testuser<br>Password: Password123!<br>Email: test@example.com</p><p><a href="/auth/login">Login Now</a></p>';
    } catch (Exception $e) {
        return '<h1>Error Creating Test User</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}, 'create-test-user');

// ============================================================================
// AUTHENTICATION ROUTES (Public access for login/register)
// ============================================================================

$router->group(['prefix' => '/auth'], function($router) {
    // Login routes
    $router->get('/login', 'AuthController@showLogin', 'auth.login');
    $router->post('/login', 'AuthController@login', 'auth.login.post');
    $router->post('/logout', 'AuthController@logout', 'auth.logout');
    $router->get('/logout', 'AuthController@logout', 'auth.logout.get');
    
    // Registration routes
    $router->get('/register', 'AuthController@showRegister', 'auth.register');
    $router->post('/register', 'AuthController@register', 'auth.register.post');
    
    // Password reset routes
    $router->get('/forgot-password', 'AuthController@showForgotPassword', 'auth.forgot-password');
    $router->post('/forgot-password', 'AuthController@forgotPassword', 'auth.forgot-password.post');
    $router->get('/reset-password', 'AuthController@showResetPassword', 'auth.reset-password');
    $router->post('/reset-password', 'AuthController@resetPassword', 'auth.reset-password.post');
    
    // Email verification routes
    $router->get('/verify-email', 'AuthController@verifyEmail', 'auth.verify-email');
    $router->post('/resend-verification', 'AuthController@resendVerification', 'auth.resend-verification');
});

// ============================================================================
// AUTHENTICATED ROUTES - RBAC PROTECTED
// ============================================================================

$router->group(['middleware' => 'auth'], function($router) {
    
    // ========================================================================
    // DASHBOARD & PROFILE - Available to all authenticated users
    // ========================================================================
    
    $router->get('/dashboard', 'HomeController@dashboard', 'dashboard');
    $router->get('/profile', 'ProfileController@show', 'profile.show');
    $router->put('/profile', 'ProfileController@update', 'profile.update');
    $router->get('/settings', 'SettingsController@index', 'settings.index');
    
    // Password change routes (for authenticated users)
    $router->get('/auth/change-password', 'AuthController@showChangePassword', 'auth.change-password');
    $router->post('/auth/change-password', 'AuthController@changePassword', 'auth.change-password.post');
    
    // ========================================================================
    // SUPER ADMIN ROUTES - System Administration
    // ========================================================================
    
    $router->group(['middleware' => 'role:super_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
        // System administration
        $router->get('/dashboard', 'DashboardController@index', 'admin.dashboard');
        
        // File management (admin has all permissions)
        $router->post('/upload/bulk', '\\App\\Controllers\\FileUploadController@bulkUpload', 'admin.upload.bulk');
        $router->post('/upload/consent-form', '\\App\\Controllers\\FileUploadController@uploadConsentForm', 'admin.upload.consent');
        $router->post('/upload/team-submission', '\\App\\Controllers\\FileUploadController@uploadTeamSubmission', 'admin.upload.submission');
        $router->post('/upload/profile-photo', '\\App\\Controllers\\FileUploadController@uploadProfilePhoto', 'admin.upload.profile');
        $router->get('/files/{id}/download', '\\App\\Controllers\\FileUploadController@downloadFile', 'admin.files.download');
        $router->delete('/files/{id}', '\\App\\Controllers\\FileUploadController@deleteFile', 'admin.files.delete');
        $router->get('/files/{id}/info', '\\App\\Controllers\\FileUploadController@getFileInfo', 'admin.files.info');
        $router->get('/upload/{id}/progress', '\\App\\Controllers\\FileUploadController@getUploadProgress', 'admin.upload.progress');
        
        $router->get('/system', 'SystemController@index', 'admin.system');
        $router->get('/system/settings', 'SystemController@settings', 'admin.system.settings');
        $router->post('/system/settings', 'SystemController@updateSettings', 'admin.system.settings.update');
        
        // User management
        $router->get('/users', 'UserController@index', 'admin.users');
        $router->get('/users/create', 'UserController@create', 'admin.users.create');
        $router->post('/users', 'UserController@store', 'admin.users.store');
        $router->get('/users/{id}', 'UserController@show', 'admin.users.show');
        $router->get('/users/{id}/edit', 'UserController@edit', 'admin.users.edit');
        $router->put('/users/{id}', 'UserController@update', 'admin.users.update');
        $router->delete('/users/{id}', 'UserController@destroy', 'admin.users.destroy');
        
        // School management (full access)
        $router->get('/schools', 'SchoolManagementController@index', 'admin.schools');
        $router->get('/schools/create', 'SchoolManagementController@create', 'admin.schools.create');
        $router->post('/schools', 'SchoolManagementController@store', 'admin.schools.store');
        $router->get('/schools/{id}', 'SchoolManagementController@show', 'admin.schools.show');
        $router->get('/schools/{id}/edit', 'SchoolManagementController@edit', 'admin.schools.edit');
        $router->put('/schools/{id}', 'SchoolManagementController@update', 'admin.schools.update');
        $router->post('/schools/bulk-action', 'SchoolManagementController@bulkAction', 'admin.schools.bulk');
        $router->get('/schools/export', 'SchoolManagementController@export', 'admin.schools.export');
        $router->delete('/schools/{id}', 'SchoolManagementController@destroy', 'admin.schools.destroy');
        
        // District management
        $router->get('/districts', 'DistrictController@index', 'admin.districts');
        $router->get('/districts/create', 'DistrictController@create', 'admin.districts.create');
        $router->post('/districts', 'DistrictController@store', 'admin.districts.store');
        $router->get('/districts/{id}', 'DistrictController@show', 'admin.districts.show');
        $router->get('/districts/{id}/edit', 'DistrictController@edit', 'admin.districts.edit');
        $router->put('/districts/{id}', 'DistrictController@update', 'admin.districts.update');
        $router->get('/districts/{id}/schools', 'DistrictController@getSchools', 'admin.districts.schools');
        $router->get('/districts/export', 'DistrictController@export', 'admin.districts.export');
        $router->get('/districts/{id}/export', 'DistrictController@export', 'admin.districts.export.single');
        $router->delete('/districts/{id}', 'DistrictController@destroy', 'admin.districts.destroy');
        
        // System logs and monitoring
        $router->get('/logs', 'LogController@index', 'admin.logs');
        $router->get('/logs/{file}', 'LogController@show', 'admin.logs.show');
        
        // AJAX API endpoints for admin dashboard
        $router->get('/api/system-status', 'DashboardController@systemStatus', 'admin.api.system-status');
        $router->get('/api/dashboard-updates', 'DashboardController@dashboardUpdates', 'admin.api.dashboard-updates');
        $router->get('/api/notifications', 'DashboardController@notifications', 'admin.api.notifications');
        $router->post('/api/notifications/mark-read', 'DashboardController@markNotificationsRead', 'admin.api.notifications.mark-read');
    });
    
    // ========================================================================
    // COMPETITION ADMIN OR SUPER ADMIN ROUTES
    // ========================================================================
    
    $router->group(['middleware' => 'role:competition_admin,super_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
        // Competition management
        $router->get('/competitions', 'CompetitionController@index', 'admin.competitions');
        $router->get('/competitions/create', 'CompetitionController@create', 'admin.competitions.create');
        $router->post('/competitions', 'CompetitionController@store', 'admin.competitions.store');
        $router->get('/competitions/{id}', 'CompetitionController@show', 'admin.competitions.show');
        $router->get('/competitions/{id}/edit', 'CompetitionController@edit', 'admin.competitions.edit');
        $router->put('/competitions/{id}', 'CompetitionController@update', 'admin.competitions.update');
        $router->delete('/competitions/{id}', 'CompetitionController@destroy', 'admin.competitions.destroy');
        
        // Judge management
        $router->get('/judges', 'JudgeController@index', 'admin.judges');
        $router->get('/judges/create', 'JudgeController@create', 'admin.judges.create');
        $router->post('/judges', 'JudgeController@store', 'admin.judges.store');
        $router->get('/judges/{id}', 'JudgeController@show', 'admin.judges.show');
        $router->get('/judges/{id}/edit', 'JudgeController@edit', 'admin.judges.edit');
        $router->put('/judges/{id}', 'JudgeController@update', 'admin.judges.update');
        $router->delete('/judges/{id}', 'JudgeController@destroy', 'admin.judges.destroy');
        
        // Judge assignments
        $router->get('/judge-assignments', 'JudgeAssignmentController@index', 'admin.judge.assignments');
        $router->post('/judge-assignments', 'JudgeAssignmentController@store', 'admin.judge.assignments.store');
        $router->delete('/judge-assignments/{id}', 'JudgeAssignmentController@destroy', 'admin.judge.assignments.destroy');
        
        // Competition categories
        $router->get('/categories', 'CategoryController@index', 'admin.categories');
        $router->post('/categories', 'CategoryController@store', 'admin.categories.store');
        $router->put('/categories/{id}', 'CategoryController@update', 'admin.categories.update');
        $router->delete('/categories/{id}', 'CategoryController@destroy', 'admin.categories.destroy');
    });
    
    // ========================================================================
    // SCHOOL COORDINATOR ROUTES (+ higher roles)
    // ========================================================================
    
    $router->group(['middleware' => 'role:school_coordinator,competition_admin,super_admin', 'prefix' => 'coordinator', 'namespace' => 'Coordinator'], function($router) {
        // Dashboard
        $router->get('/dashboard', 'DashboardController@index', 'coordinator.dashboard');
        
        // School management (own school only for coordinators)
        $router->get('/schools', 'SchoolController@index', 'coordinator.schools');
        $router->get('/schools/{id}', 'SchoolController@show', 'coordinator.schools.show');
        $router->put('/schools/{id}', 'SchoolController@update', 'coordinator.schools.update');
        
        // Team management
        $router->get('/teams', 'TeamController@index', 'coordinator.teams');
        $router->get('/teams/create', 'TeamController@create', 'coordinator.teams.create');
        $router->post('/teams', 'TeamController@store', 'coordinator.teams.store');
        $router->get('/teams/{id}', 'TeamController@show', 'coordinator.teams.show');
        $router->get('/teams/{id}/edit', 'TeamController@edit', 'coordinator.teams.edit');
        $router->put('/teams/{id}', 'TeamController@update', 'coordinator.teams.update');
        $router->delete('/teams/{id}', 'TeamController@destroy', 'coordinator.teams.destroy');
        
        // Participant management
        $router->get('/participants', 'ParticipantController@index', 'coordinator.participants');
        $router->get('/participants/create', 'ParticipantController@create', 'coordinator.participants.create');
        $router->post('/participants', 'ParticipantController@store', 'coordinator.participants.store');
        $router->get('/participants/{id}', 'ParticipantController@show', 'coordinator.participants.show');
        $router->get('/participants/{id}/edit', 'ParticipantController@edit', 'coordinator.participants.edit');
        $router->put('/participants/{id}', 'ParticipantController@update', 'coordinator.participants.update');
        $router->delete('/participants/{id}', 'ParticipantController@destroy', 'coordinator.participants.destroy');
        
        // Team registration and submissions
        $router->get('/registrations', 'RegistrationController@index', 'coordinator.registrations');
        $router->post('/registrations', 'RegistrationController@store', 'coordinator.registrations.store');
        $router->get('/submissions', 'SubmissionController@index', 'coordinator.submissions');
        $router->post('/submissions', 'SubmissionController@store', 'coordinator.submissions.store');
        
        // File uploads
        $router->post('/upload/consent-form', '\\App\\Controllers\\FileUploadController@uploadConsentForm', 'coordinator.upload.consent');
        $router->post('/upload/team-submission', '\\App\\Controllers\\FileUploadController@uploadTeamSubmission', 'coordinator.upload.submission');
        $router->post('/upload/profile-photo', '\\App\\Controllers\\FileUploadController@uploadProfilePhoto', 'coordinator.upload.profile');
        $router->get('/files/{id}/download', '\\App\\Controllers\\FileUploadController@downloadFile', 'coordinator.files.download');
        $router->delete('/files/{id}', '\\App\\Controllers\\FileUploadController@deleteFile', 'coordinator.files.delete');
        $router->get('/files/{id}/info', '\\App\\Controllers\\FileUploadController@getFileInfo', 'coordinator.files.info');
    });
    
    // ========================================================================
    // TEAM COACH ROUTES (+ higher roles)
    // ========================================================================
    
    $router->group(['middleware' => 'role:team_coach,school_coordinator,competition_admin,super_admin', 'prefix' => 'coach', 'namespace' => 'Coach'], function($router) {
        // Dashboard
        $router->get('/dashboard', 'DashboardController@index', 'coach.dashboard');
        
        // Team management (own teams only for coaches)
        $router->get('/teams', 'TeamController@index', 'coach.teams');
        $router->get('/teams/{id}', 'TeamController@show', 'coach.teams.show');
        $router->put('/teams/{id}', 'TeamController@update', 'coach.teams.update');
        
        // Team participants
        $router->get('/teams/{id}/participants', 'TeamController@participants', 'coach.teams.participants');
        $router->post('/teams/{id}/participants', 'TeamController@addParticipant', 'coach.teams.participants.add');
        $router->delete('/teams/{teamId}/participants/{participantId}', 'TeamController@removeParticipant', 'coach.teams.participants.remove');
        
        // Team submissions
        $router->get('/teams/{id}/submissions', 'SubmissionController@index', 'coach.submissions');
        $router->post('/teams/{id}/submissions', 'SubmissionController@store', 'coach.submissions.store');
        $router->get('/submissions/{id}', 'SubmissionController@show', 'coach.submissions.show');
        $router->put('/submissions/{id}', 'SubmissionController@update', 'coach.submissions.update');
        
        // File uploads
        $router->post('/upload/consent-form', '\\App\\Controllers\\FileUploadController@uploadConsentForm', 'coach.upload.consent');
        $router->post('/upload/team-submission', '\\App\\Controllers\\FileUploadController@uploadTeamSubmission', 'coach.upload.submission');
        $router->post('/upload/profile-photo', '\\App\\Controllers\\FileUploadController@uploadProfilePhoto', 'coach.upload.profile');
        $router->get('/files/{id}/download', '\\App\\Controllers\\FileUploadController@downloadFile', 'coach.files.download');
        $router->delete('/files/{id}', '\\App\\Controllers\\FileUploadController@deleteFile', 'coach.files.delete');
        $router->get('/files/{id}/info', '\\App\\Controllers\\FileUploadController@getFileInfo', 'coach.files.info');
    });
    
    // ========================================================================
    // JUDGE ROUTES (+ higher roles)
    // ========================================================================
    
    $router->group(['middleware' => 'role:judge,competition_admin,super_admin', 'prefix' => 'judge', 'namespace' => 'Judge'], function($router) {
        // Dashboard
        $router->get('/dashboard', 'DashboardController@index', 'judge.dashboard');
        
        // Judge assignments
        $router->get('/assignments', 'AssignmentController@index', 'judge.assignments');
        $router->get('/assignments/{id}', 'AssignmentController@show', 'judge.assignments.show');
        
        // Scoring interface
        $router->get('/scoring', 'ScoringController@index', 'judge.scoring');
        $router->get('/scoring/{competitionId}/{teamId}', 'ScoringController@show', 'judge.scoring.show');
        $router->post('/scoring', 'ScoringController@store', 'judge.scoring.store');
        $router->put('/scoring/{id}', 'ScoringController@update', 'judge.scoring.update');
        
        // Evaluation criteria
        $router->get('/criteria/{competitionId}', 'CriteriaController@show', 'judge.criteria.show');
        
        // Judge schedule
        $router->get('/schedule', 'ScheduleController@index', 'judge.schedule');
    });
    
    // ========================================================================
    // PARTICIPANT ROUTES (+ higher roles)
    // ========================================================================
    
    $router->group(['middleware' => 'role:participant,team_coach,school_coordinator,competition_admin,super_admin', 'prefix' => 'participant', 'namespace' => 'Participant'], function($router) {
        // Dashboard
        $router->get('/dashboard', 'DashboardController@index', 'participant.dashboard');
        
        // Team information
        $router->get('/team', 'TeamController@show', 'participant.team');
        
        // Competition schedule
        $router->get('/schedule', 'ScheduleController@index', 'participant.schedule');
        
        // Results and scores (if available)
        $router->get('/results', 'ResultController@index', 'participant.results');
    });
    
    // ========================================================================
    // PERMISSION-BASED ROUTES (More granular control)
    // ========================================================================
    
    // User management permission
    $router->group(['middleware' => 'permission:' . Auth::PERM_USER_MANAGE], function($router) {
        $router->get('/users/manage', 'UserManagementController@index', 'user.management');
        $router->get('/users/export', 'UserManagementController@export', 'user.export');
    });
    
    // Report viewing permission
    $router->group(['middleware' => 'permission:' . Auth::PERM_REPORT_VIEW], function($router) {
        $router->get('/reports', 'ReportController@index', 'reports.index');
        $router->get('/reports/competitions', 'ReportController@competitions', 'reports.competitions');
        $router->get('/reports/schools', 'ReportController@schools', 'reports.schools');
        $router->get('/reports/teams', 'ReportController@teams', 'reports.teams');
        $router->get('/reports/participants', 'ReportController@participants', 'reports.participants');
    });
    
    // Report export permission
    $router->group(['middleware' => 'permission:' . Auth::PERM_REPORT_EXPORT], function($router) {
        $router->get('/reports/export', 'ReportController@export', 'reports.export');
        $router->post('/reports/export', 'ReportController@generateExport', 'reports.export.generate');
    });
    
    // Multiple permission requirements (user must have ALL listed permissions)
    $router->group(['middleware' => 'permission:' . Auth::PERM_COMPETITION_MANAGE . ',' . Auth::PERM_JUDGE_MANAGE], function($router) {
        $router->get('/admin/competition-judging', 'Admin\\CompetitionJudgingController@index', 'admin.competition.judging');
        $router->post('/admin/competition-judging/assign', 'Admin\\CompetitionJudgingController@assignJudges', 'admin.competition.judging.assign');
    });
});

// ============================================================================
// API ROUTES (Can be protected with middleware as needed)
// ============================================================================

$router->group(['prefix' => '/api', 'middleware' => 'auth'], function($router) {
    // User API (requires user management permission)
    $router->group(['middleware' => 'permission:' . Auth::PERM_USER_MANAGE], function($router) {
        $router->get('/users', 'Api\\UserController@index', 'api.users');
        $router->get('/users/{id}', 'Api\\UserController@show', 'api.users.show');
        $router->post('/users', 'Api\\UserController@store', 'api.users.store');
        $router->put('/users/{id}', 'Api\\UserController@update', 'api.users.update');
        $router->delete('/users/{id}', 'Api\\UserController@destroy', 'api.users.destroy');
    });
    
    // Competition API (view permission required)
    $router->group(['middleware' => 'permission:' . Auth::PERM_COMPETITION_VIEW], function($router) {
        $router->get('/competitions', 'Api\\CompetitionController@index', 'api.competitions');
        $router->get('/competitions/{id}', 'Api\\CompetitionController@show', 'api.competitions.show');
    });
    
    // Team API (view permission required)
    $router->group(['middleware' => 'permission:' . Auth::PERM_TEAM_VIEW], function($router) {
        $router->get('/teams', 'Api\\TeamController@index', 'api.teams');
        $router->get('/teams/{id}', 'Api\\TeamController@show', 'api.teams.show');
    });
    
    // School API (view permission required)
    $router->group(['middleware' => 'permission:' . Auth::PERM_SCHOOL_VIEW], function($router) {
        $router->get('/schools', 'Api\\SchoolController@index', 'api.schools');
        $router->get('/schools/{id}', 'Api\\SchoolController@show', 'api.schools.show');
    });
});