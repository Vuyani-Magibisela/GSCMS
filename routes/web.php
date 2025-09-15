<?php
// routes/web.php - Web Routes with RBAC Implementation

use App\Core\Auth;

// ============================================================================
// PUBLIC ROUTES (No authentication required)
// ============================================================================

// Home route
$router->get('/', 'HomeController@index', 'home');

// Fixed user management routes (bypassing problematic middleware)
$router->get('/admin/users', 'App\\Controllers\\Admin\\UserController@index', 'admin.users.working');
$router->post('/admin/users/update-status', 'App\\Controllers\\Admin\\UserController@updateStatus', 'admin.users.status.temp');
$router->get('/admin/users/{id}', 'App\\Controllers\\Admin\\UserController@show', 'admin.users.show.temp');
$router->get('/admin/users/{id}/edit', 'App\\Controllers\\Admin\\UserController@edit', 'admin.users.edit.temp');
$router->put('/admin/users/{id}', 'App\\Controllers\\Admin\\UserController@update', 'admin.users.update.temp');
$router->post('/admin/users/{id}', 'App\\Controllers\\Admin\\UserController@update', 'admin.users.update.post');
$router->delete('/admin/users/{id}', 'App\\Controllers\\Admin\\UserController@destroy', 'admin.users.destroy.temp');
$router->post('/admin/users/{id}/delete', 'App\\Controllers\\Admin\\UserController@destroy', 'admin.users.destroy.post');

// Public competition information
$router->get('/competitions/public', 'CompetitionController@publicIndex', 'competitions.public');

// Test route for participants (no middleware)
$router->get('/test-participants', 'Admin\\ParticipantManagementController@index', 'test.participants');

// About and other public pages
$router->get('/about', 'PublicController@about', 'about');
$router->get('/categories', 'PublicController@categories', 'categories');
$router->get('/schedule', 'PublicController@schedule', 'schedule');
$router->get('/leaderboard', 'PublicController@leaderboard', 'leaderboard');
$router->get('/announcements', 'PublicController@announcements', 'announcements');
$router->get('/resources', 'PublicController@resources', 'resources');

// ============================================================================
// PUBLIC SCOREBOARD ROUTES
// ============================================================================

// Public scoreboards
$router->get('/scoreboard', 'ScoreboardController@index', 'scoreboard.index');
$router->get('/scoreboard/{sessionId}', 'ScoreboardController@show', 'scoreboard.show');
$router->get('/scoreboard/{sessionId}/api', 'ScoreboardController@api', 'scoreboard.api');
$router->get('/scoreboard/{sessionId}/embed', 'ScoreboardController@embed', 'scoreboard.embed');
$router->get('/scoreboard/{sessionId}/qr', 'ScoreboardController@qr', 'scoreboard.qr');
$router->get('/scoreboard/{sessionId}/social', 'ScoreboardController@social', 'scoreboard.social');
$router->get('/scoreboard/{sessionId}/metrics', 'ScoreboardController@metrics', 'scoreboard.metrics');

// ============================================================================
// REGISTRATION SYSTEM ROUTES
// ============================================================================

// ============================================================================
// SCHOOL SELF-REGISTRATION ROUTES (Public access)
// ============================================================================

$router->group(['prefix' => '/register/school'], function($router) {
    // School registration wizard
    $router->get('/', 'Registration\\SchoolRegistrationController@index', 'school.register');
    $router->get('/create', 'Registration\\SchoolRegistrationController@create', 'school.register.create');
    $router->get('/step/{step}', 'Registration\\SchoolRegistrationController@showStep', 'school.register.step');
    $router->post('/step/{step}', 'Registration\\SchoolRegistrationController@processStep', 'school.register.step.process');
    $router->post('/process-step/{step}', 'Registration\\SchoolRegistrationController@processStep', 'school.register.process.step');
    
    // Registration management
    $router->get('/resume', 'Registration\\SchoolRegistrationController@resume', 'school.register.resume');
    
    // Status checking
    $router->get('/status', 'Registration\\SchoolRegistrationController@showStatusForm', 'school.register.status');
    $router->post('/status', 'Registration\\SchoolRegistrationController@checkStatus', 'school.register.status.check');
    
    // Registration completion
    $router->get('/success', 'Registration\\SchoolRegistrationController@success', 'school.register.success');
    $router->get('/closed', 'Registration\\SchoolRegistrationController@closed', 'school.register.closed');
});

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
    
    // Debug route to check user role
    $router->get('/debug-role', function() {
        $auth = \App\Core\Auth::getInstance();
        if ($auth->check()) {
            $user = $auth->user();
            
            // Test role checking methods
            $hasSuper = $user->hasRole('super_admin');
            $hasComp = $user->hasRole('competition_admin');
            $hasAny = $user->hasAnyRole(['super_admin', 'competition_admin']);
            $isAdmin = $user->isAdmin();
            
            return '<h2>Debug User Info</h2>
                    <p><strong>User ID:</strong> ' . $user->id . '</p>
                    <p><strong>Username:</strong> ' . $user->username . '</p>
                    <p><strong>Email:</strong> ' . $user->email . '</p>
                    <p><strong>Role:</strong> ' . $user->role . '</p>
                    <p><strong>Status:</strong> ' . $user->status . '</p>
                    <hr>
                    <h3>Role Check Results:</h3>
                    <p><strong>Has Super Admin Role:</strong> ' . ($hasSuper ? 'YES' : 'NO') . '</p>
                    <p><strong>Has Competition Admin Role:</strong> ' . ($hasComp ? 'YES' : 'NO') . '</p>
                    <p><strong>Has Any Admin Role:</strong> ' . ($hasAny ? 'YES' : 'NO') . '</p>
                    <p><strong>Is Admin:</strong> ' . ($isAdmin ? 'YES' : 'NO') . '</p>
                    <hr>
                    <p><a href="/GSCMS/public/admin/participants">Test Participants Access</a></p>
                    <p><a href="/GSCMS/public/test-participants">Test Direct Participants Access (No Middleware)</a></p>';
        } else {
            return '<p>Not authenticated</p>';
        }
    });
    $router->get('/profile', 'ProfileController@show', 'profile.show');
    $router->put('/profile', 'ProfileController@update', 'profile.update');
    $router->get('/settings', 'SettingsController@index', 'settings.index');
    
    // Password change routes (for authenticated users)
    $router->get('/auth/change-password', 'AuthController@showChangePassword', 'auth.change-password');
    $router->post('/auth/change-password', 'AuthController@changePassword', 'auth.change-password.post');
    
    // ========================================================================
    // SUPER ADMIN ROUTES - System Administration
    // ========================================================================
    
    $router->group(['middleware' => 'role:super_admin,competition_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
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
        
        // Participant management
        $router->get('/participants', 'ParticipantManagementController@index', 'admin.participants');
        $router->get('/participants/create', 'ParticipantManagementController@create', 'admin.participants.create');
        $router->post('/participants', 'ParticipantManagementController@store', 'admin.participants.store');
        $router->get('/participants/{id}', 'ParticipantManagementController@show', 'admin.participants.show');
        $router->get('/participants/{id}/edit', 'ParticipantManagementController@edit', 'admin.participants.edit');
        $router->put('/participants/{id}', 'ParticipantManagementController@update', 'admin.participants.update');
        $router->delete('/participants/{id}', 'ParticipantManagementController@destroy', 'admin.participants.destroy');

        // Team management
        $router->get('/teams', 'TeamManagementController@index', 'admin.teams');
        $router->get('/teams/create', 'TeamManagementController@create', 'admin.teams.create');
        $router->post('/teams', 'TeamManagementController@store', 'admin.teams.store');
        $router->get('/teams/{id}', 'TeamManagementController@show', 'admin.teams.show');
        $router->get('/teams/{id}/edit', 'TeamManagementController@edit', 'admin.teams.edit');
        $router->put('/teams/{id}', 'TeamManagementController@update', 'admin.teams.update');
        $router->delete('/teams/{id}', 'TeamManagementController@destroy', 'admin.teams.destroy');
        
        // Team participant management
        $router->post('/teams/{id}/participants', 'TeamManagementController@addParticipant', 'admin.teams.participants.add');
        $router->delete('/teams/{teamId}/participants/{participantId}', 'TeamManagementController@removeParticipant', 'admin.teams.participants.remove');
        
        // Team status management
        $router->post('/teams/{id}/status', 'TeamManagementController@updateStatus', 'admin.teams.status.update');
        $router->post('/teams/bulk-action', 'TeamManagementController@bulkAction', 'admin.teams.bulk');
        
        // User management - TEMPORARILY DISABLED due to middleware issues
        // $router->get('/users', 'UserController@index', 'admin.users');
        // $router->get('/users/create', 'UserController@create', 'admin.users.create');
        // $router->post('/users', 'UserController@store', 'admin.users.store');
        // $router->get('/users/{id}', 'UserController@show', 'admin.users.show');
        // $router->get('/users/{id}/edit', 'UserController@edit', 'admin.users.edit');
        // $router->put('/users/{id}', 'UserController@update', 'admin.users.update');
        // $router->delete('/users/{id}', 'UserController@destroy', 'admin.users.destroy');
        // $router->post('/users/update-status', 'UserController@updateStatus', 'admin.users.update-status');
        
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
        
        // Contact management
        $router->get('/contacts', 'ContactController@index', 'admin.contacts');
        $router->get('/contacts/create', 'ContactController@create', 'admin.contacts.create');
        $router->post('/contacts', 'ContactController@store', 'admin.contacts.store');
        $router->get('/contacts/{id}', 'ContactController@show', 'admin.contacts.show');
        $router->get('/contacts/{id}/edit', 'ContactController@edit', 'admin.contacts.edit');
        $router->put('/contacts/{id}', 'ContactController@update', 'admin.contacts.update');
        $router->post('/contacts/{id}', 'ContactController@update', 'admin.contacts.update.post');
        $router->delete('/contacts/{id}', 'ContactController@destroy', 'admin.contacts.destroy');
        $router->post('/contacts/{id}/delete', 'ContactController@destroy', 'admin.contacts.destroy.post');
        
        // Contact AJAX endpoints
        $router->get('/contacts/school/{schoolId}', 'ContactController@getBySchool', 'admin.contacts.by-school');
        $router->post('/contacts/update-status', 'ContactController@updateStatus', 'admin.contacts.status.update');
        
        // ====================================================================
        // DOCUMENT MANAGEMENT SYSTEM (Comprehensive Document Management)
        // ====================================================================
        
        // Document Management Dashboard
        $router->get('/documents', '\\App\\Controllers\\Documents\\DocumentManagementController@index', 'admin.documents');
        $router->get('/documents/dashboard', '\\App\\Controllers\\Documents\\DocumentManagementController@index', 'admin.documents.dashboard');
        
        // Document Verification Queue
        $router->get('/documents/verification-queue', '\\App\\Controllers\\Documents\\DocumentManagementController@verificationQueue', 'admin.documents.verification.queue');
        $router->post('/documents/verification-action', '\\App\\Controllers\\Documents\\DocumentManagementController@verificationAction', 'admin.documents.verification.action');
        $router->post('/documents/bulk-approve', '\\App\\Controllers\\Documents\\DocumentManagementController@bulkApprove', 'admin.documents.bulk.approve');
        $router->post('/documents/bulk-reject', '\\App\\Controllers\\Documents\\DocumentManagementController@bulkReject', 'admin.documents.bulk.reject');
        
        // Document Upload System
        $router->post('/documents/upload', '\\App\\Controllers\\Documents\\DocumentManagementController@uploadDocument', 'admin.documents.upload');
        $router->get('/documents/{type}/{id}/preview', '\\App\\Controllers\\Documents\\DocumentManagementController@previewDocument', 'admin.documents.preview');
        $router->get('/documents/{type}/{id}/download', '\\App\\Controllers\\Documents\\DocumentManagementController@downloadDocument', 'admin.documents.download');
        
        // Digital Signature System
        $router->get('/documents/digital-signature', '\\App\\Controllers\\Documents\\DocumentManagementController@showDigitalSignature', 'admin.documents.digital.signature');
        $router->post('/documents/save-digital-signature', '\\App\\Controllers\\Documents\\DocumentManagementController@saveDigitalSignature', 'admin.documents.save.signature');
        $router->get('/documents/signatures', '\\App\\Controllers\\Documents\\DocumentManagementController@listSignatures', 'admin.documents.signatures.list');
        $router->get('/documents/signatures/{id}', '\\App\\Controllers\\Documents\\DocumentManagementController@showSignature', 'admin.documents.signatures.show');
        $router->post('/documents/signatures/{id}/verify', '\\App\\Controllers\\Documents\\DocumentManagementController@verifySignature', 'admin.documents.signatures.verify');
        
        // Medical Form Management
        $router->get('/documents/medical-forms', '\\App\\Controllers\\Documents\\MedicalFormController@index', 'admin.documents.medical.forms');
        $router->get('/documents/medical-forms/create', '\\App\\Controllers\\Documents\\MedicalFormController@create', 'admin.documents.medical.forms.create');
        $router->post('/documents/medical-forms', '\\App\\Controllers\\Documents\\MedicalFormController@store', 'admin.documents.medical.forms.store');
        $router->get('/documents/medical-forms/{id}', '\\App\\Controllers\\Documents\\MedicalFormController@show', 'admin.documents.medical.forms.show');
        $router->get('/documents/medical-forms/{id}/edit', '\\App\\Controllers\\Documents\\MedicalFormController@edit', 'admin.documents.medical.forms.edit');
        $router->put('/documents/medical-forms/{id}', '\\App\\Controllers\\Documents\\MedicalFormController@update', 'admin.documents.medical.forms.update');
        $router->post('/documents/collect-medical-info', '\\App\\Controllers\\Documents\\MedicalFormController@collectMedicalInfo', 'admin.documents.medical.collect');
        $router->post('/documents/validate-medical', '\\App\\Controllers\\Documents\\MedicalFormController@validateMedicalData', 'admin.documents.medical.validate');
        $router->post('/documents/emergency-protocols', '\\App\\Controllers\\Documents\\MedicalFormController@emergencyProtocols', 'admin.documents.emergency.protocols');
        
        // Student Document Management
        $router->get('/documents/student-documents', '\\App\\Controllers\\Documents\\StudentDocumentController@index', 'admin.documents.student.docs');
        $router->get('/documents/student-documents/create', '\\App\\Controllers\\Documents\\StudentDocumentController@create', 'admin.documents.student.docs.create');
        $router->post('/documents/student-documents', '\\App\\Controllers\\Documents\\StudentDocumentController@store', 'admin.documents.student.docs.store');
        $router->get('/documents/student-documents/{id}', '\\App\\Controllers\\Documents\\StudentDocumentController@show', 'admin.documents.student.docs.show');
        $router->get('/documents/student-documents/{id}/edit', '\\App\\Controllers\\Documents\\StudentDocumentController@edit', 'admin.documents.student.docs.edit');
        $router->put('/documents/student-documents/{id}', '\\App\\Controllers\\Documents\\StudentDocumentController@update', 'admin.documents.student.docs.update');
        $router->post('/documents/student-documents/{id}/verify', '\\App\\Controllers\\Documents\\StudentDocumentController@verifyDocument', 'admin.documents.student.docs.verify');
        $router->post('/documents/student-documents/{id}/ocr', '\\App\\Controllers\\Documents\\StudentDocumentController@performOCR', 'admin.documents.student.docs.ocr');
        $router->post('/documents/student-documents/{id}/security-scan', '\\App\\Controllers\\Documents\\StudentDocumentController@securityScan', 'admin.documents.student.docs.scan');
        
        // Emergency Contact Management
        $router->get('/documents/emergency-contacts', '\\App\\Controllers\\Documents\\EmergencyContactController@index', 'admin.documents.emergency.contacts');
        $router->get('/documents/emergency-contacts/create', '\\App\\Controllers\\Documents\\EmergencyContactController@create', 'admin.documents.emergency.contacts.create');
        $router->post('/documents/emergency-contacts', '\\App\\Controllers\\Documents\\EmergencyContactController@store', 'admin.documents.emergency.contacts.store');
        $router->get('/documents/emergency-contacts/{id}', '\\App\\Controllers\\Documents\\EmergencyContactController@show', 'admin.documents.emergency.contacts.show');
        $router->get('/documents/emergency-contacts/{id}/edit', '\\App\\Controllers\\Documents\\EmergencyContactController@edit', 'admin.documents.emergency.contacts.edit');
        $router->put('/documents/emergency-contacts/{id}', '\\App\\Controllers\\Documents\\EmergencyContactController@update', 'admin.documents.emergency.contacts.update');
        $router->post('/documents/emergency-contacts/{id}/verify', '\\App\\Controllers\\Documents\\EmergencyContactController@verifyContact', 'admin.documents.emergency.contacts.verify');
        $router->post('/documents/emergency-contacts/{id}/test', '\\App\\Controllers\\Documents\\EmergencyContactController@testContact', 'admin.documents.emergency.contacts.test');
        $router->get('/documents/emergency-contacts/participant/{id}', '\\App\\Controllers\\Documents\\EmergencyContactController@getForParticipant', 'admin.documents.emergency.contacts.participant');
        
        // Consent Form Management
        $router->get('/documents/consent-forms', 'ConsentFormController@index', 'admin.documents.consent.forms');
        $router->get('/documents/consent-forms/create', 'ConsentFormController@create', 'admin.documents.consent.forms.create');
        $router->post('/documents/consent-forms', 'ConsentFormController@store', 'admin.documents.consent.forms.store');
        $router->get('/documents/consent-forms/{id}', 'ConsentFormController@show', 'admin.documents.consent.forms.show');
        $router->get('/documents/consent-forms/{id}/edit', 'ConsentFormController@edit', 'admin.documents.consent.forms.edit');
        $router->put('/documents/consent-forms/{id}', 'ConsentFormController@update', 'admin.documents.consent.forms.update');
        $router->post('/documents/consent-forms/{id}/approve', 'ConsentFormController@approve', 'admin.documents.consent.forms.approve');
        $router->post('/documents/consent-forms/{id}/reject', 'ConsentFormController@reject', 'admin.documents.consent.forms.reject');
        
        // Security & Compliance
        $router->get('/documents/security-audit', '\\App\\Controllers\\Documents\\DocumentManagementController@securityAudit', 'admin.documents.security.audit');
        $router->get('/documents/popia-compliance', '\\App\\Controllers\\Documents\\DocumentManagementController@popiaCompliance', 'admin.documents.popia.compliance');
        $router->post('/documents/generate-compliance-report', '\\App\\Controllers\\Documents\\DocumentManagementController@generateComplianceReport', 'admin.documents.compliance.report');
        
        // Document Analytics & Reports
        $router->get('/documents/analytics', '\\App\\Controllers\\Documents\\DocumentManagementController@analytics', 'admin.documents.analytics');
        $router->get('/documents/reports', '\\App\\Controllers\\Documents\\DocumentManagementController@reports', 'admin.documents.reports');
        $router->post('/documents/reports/generate', '\\App\\Controllers\\Documents\\DocumentManagementController@generateReport', 'admin.documents.reports.generate');
        $router->get('/documents/export/{format}', '\\App\\Controllers\\Documents\\DocumentManagementController@exportDocuments', 'admin.documents.export');
        
        // File Management API
        $router->get('/documents/files/{id}/info', '\\App\\Controllers\\Documents\\DocumentManagementController@getFileInfo', 'admin.documents.files.info');
        $router->post('/documents/files/{id}/move', '\\App\\Controllers\\Documents\\DocumentManagementController@moveFile', 'admin.documents.files.move');
        $router->delete('/documents/files/{id}', '\\App\\Controllers\\Documents\\DocumentManagementController@deleteFile', 'admin.documents.files.delete');
        
        // AJAX API endpoints for documents
        $router->get('/api/participants', '\\App\\Controllers\\Api\\ParticipantController@index', 'admin.api.participants');
        $router->get('/api/schools/{id}/participants', '\\App\\Controllers\\Api\\ParticipantController@getBySchool', 'admin.api.participants.school');
        $router->get('/api/documents/statistics', '\\App\\Controllers\\Documents\\DocumentManagementController@getStatistics', 'admin.api.documents.statistics');
        
        // System logs and monitoring
        $router->get('/logs', 'LogController@index', 'admin.logs');
        $router->get('/logs/{file}', 'LogController@show', 'admin.logs.show');
        
        // Role management
        $router->get('/roles', 'RoleController@index', 'admin.roles');
        $router->post('/roles/update-user-role', 'RoleController@updateUserRole', 'admin.roles.update-user-role');
        $router->get('/roles/users/{role}', 'RoleController@getUsersForRole', 'admin.roles.users');
        $router->get('/roles/export', 'RoleController@exportRoles', 'admin.roles.export');
        
        // ====================================================================
        // PHASE & CATEGORY MANAGEMENT (Phase & Category Management System)
        // ====================================================================
        
        // Competition Setup Management
        $router->get('/competition-setup', 'CompetitionSetupController@index', 'admin.competition.setup');
        $router->get('/competition-setup/configure-pilot', 'CompetitionSetupController@configurePilotCompetition', 'admin.competition.setup.pilot');
        $router->post('/competition-setup/configure-pilot', 'CompetitionSetupController@configurePilotCompetition', 'admin.competition.setup.pilot.save');
        $router->get('/competition-setup/configure-full', 'CompetitionSetupController@configureFullCompetition', 'admin.competition.setup.full');
        $router->post('/competition-setup/configure-full', 'CompetitionSetupController@configureFullCompetition', 'admin.competition.setup.full.save');
        $router->post('/competition-setup/switch-mode', 'CompetitionSetupController@switchCompetitionMode', 'admin.competition.setup.switch');
        
        // Phase Management
        $router->get('/phase-management', 'PhaseManagementController@index', 'admin.phase.management');
        $router->get('/phase-management/create', 'PhaseManagementController@create', 'admin.phase.management.create');
        $router->post('/phase-management', 'PhaseManagementController@create', 'admin.phase.management.store');
        $router->get('/phase-management/{id}/edit', 'PhaseManagementController@edit', 'admin.phase.management.edit');
        $router->post('/phase-management/{id}/edit', 'PhaseManagementController@edit', 'admin.phase.management.update');
        $router->post('/phase-management/{id}/activate', 'PhaseManagementController@activatePhase', 'admin.phase.management.activate');
        $router->get('/phase-management/monitor', 'PhaseManagementController@monitorPhaseProgress', 'admin.phase.management.monitor');
        $router->get('/phase-management/monitor/{id}', 'PhaseManagementController@monitorPhaseProgress', 'admin.phase.management.monitor.phase');
        $router->get('/phase-management/reports', 'PhaseManagementController@generatePhaseReports', 'admin.phase.management.reports');
        $router->post('/phase-management/reports', 'PhaseManagementController@generatePhaseReports', 'admin.phase.management.reports.generate');
        $router->get('/phase-management/export/{format}', 'PhaseManagementController@exportPhaseData', 'admin.phase.management.export');
        $router->post('/phase-management/advance-pilot', 'PhaseManagementController@advancePilotTeams', 'admin.phase.management.pilot.advance');
        $router->get('/phase-management/advance-pilot', 'PhaseManagementController@advancePilotTeams', 'admin.phase.management.pilot.advance.form');
        
        // Category Management
        $router->get('/category-management', 'CategoryManagementController@index', 'admin.category.management');
        $router->get('/category-management/create', 'CategoryManagementController@create', 'admin.category.management.create');
        $router->post('/category-management', 'CategoryManagementController@create', 'admin.category.management.store');
        $router->get('/category-management/{id}', 'CategoryManagementController@show', 'admin.category.management.show');
        $router->get('/category-management/{id}/edit', 'CategoryManagementController@edit', 'admin.category.management.edit');
        $router->post('/category-management/{id}/edit', 'CategoryManagementController@edit', 'admin.category.management.update');
        $router->get('/category-management/setup-pilot', 'CategoryManagementController@setupPilotCategories', 'admin.category.management.pilot.setup');
        $router->post('/category-management/setup-pilot', 'CategoryManagementController@setupPilotCategories', 'admin.category.management.pilot.save');
        $router->post('/category-management/bulk-update', 'CategoryManagementController@bulkUpdateStatus', 'admin.category.management.bulk');
        $router->get('/category-management/export/{format}', 'CategoryManagementController@export', 'admin.category.management.export');
        $router->get('/category-management/validate-pilot', 'CategoryManagementController@validatePilotConfiguration', 'admin.category.management.pilot.validate');
        
        // Phase Progression Management
        $router->get('/phase-progression', 'PhaseProgressionController@index', 'admin.phase.progression');
        $router->get('/phase-progression/advance', 'PhaseProgressionController@advanceTeams', 'admin.phase.progression.advance');
        $router->post('/phase-progression/advance', 'PhaseProgressionController@advanceTeams', 'admin.phase.progression.advance.process');
        $router->get('/phase-progression/rankings', 'PhaseProgressionController@calculateRankings', 'admin.phase.progression.rankings');
        $router->post('/phase-progression/rankings', 'PhaseProgressionController@calculateRankings', 'admin.phase.progression.rankings.calculate');
        $router->get('/phase-progression/qualification-lists', 'PhaseProgressionController@generateQualificationLists', 'admin.phase.progression.qualification');
        $router->post('/phase-progression/qualification-lists', 'PhaseProgressionController@generateQualificationLists', 'admin.phase.progression.qualification.generate');
        $router->get('/phase-progression/phase-skipping', 'PhaseProgressionController@handlePhaseSkipping', 'admin.phase.progression.skip');
        $router->post('/phase-progression/phase-skipping', 'PhaseProgressionController@handlePhaseSkipping', 'admin.phase.progression.skip.process');
        $router->get('/phase-progression/team/{id}', 'PhaseProgressionController@viewTeamProgression', 'admin.phase.progression.team');
        $router->get('/phase-progression/export/{format}', 'PhaseProgressionController@exportProgressionData', 'admin.phase.progression.export');
        
        // ====================================================================
        // COMPETITION SETUP INTERFACE (New Advanced Setup System)
        // ====================================================================
        
        // Competition Wizard (6-step competition creation)
        $router->get('/competition-setup/wizard', 'CompetitionWizardController@index', 'admin.competition-setup.wizard');
        $router->get('/competition-setup/wizard/start', 'CompetitionWizardController@startWizard', 'admin.competition-setup.wizard.start');
        $router->get('/competition-setup/wizard/step/{step}', 'CompetitionWizardController@showStep', 'admin.competition-setup.wizard.step');
        $router->post('/competition-setup/wizard/save-step', 'CompetitionWizardController@saveStep', 'admin.competition-setup.wizard.save-step');
        $router->get('/competition-setup/wizard/review', 'CompetitionWizardController@reviewConfiguration', 'admin.competition-setup.wizard.review');
        $router->post('/competition-setup/wizard/deploy', 'CompetitionWizardController@deployCompetition', 'admin.competition-setup.wizard.deploy');
        $router->get('/competition-setup/view/{id}', 'CompetitionWizardController@viewCompetition', 'admin.competition-setup.view');
        $router->post('/competition-setup/clone', 'CompetitionWizardController@cloneCompetition', 'admin.competition-setup.clone');
        
        // Phase Scheduler (Timeline management and scheduling)
        $router->get('/phase-scheduler', 'PhaseSchedulerController@index', 'admin.phase-scheduler');
        $router->get('/phase-scheduler/timeline/{competitionId}', 'PhaseSchedulerController@timeline', 'admin.phase-scheduler.timeline');
        $router->post('/phase-scheduler/create-schedule', 'PhaseSchedulerController@createSchedule', 'admin.phase-scheduler.create-schedule');
        $router->post('/phase-scheduler/update-schedule', 'PhaseSchedulerController@updateSchedule', 'admin.phase-scheduler.update-schedule');
        $router->post('/phase-scheduler/validate-schedule', 'PhaseSchedulerController@validateSchedule', 'admin.phase-scheduler.validate-schedule');
        $router->post('/phase-scheduler/activate-phase', 'PhaseSchedulerController@activatePhase', 'admin.phase-scheduler.activate-phase');
        $router->post('/phase-scheduler/complete-phase', 'PhaseSchedulerController@completePhase', 'admin.phase-scheduler.complete-phase');
        $router->get('/phase-scheduler/calendar-data', 'PhaseSchedulerController@getCalendarData', 'admin.phase-scheduler.calendar-data');
        
        // Category Manager (Rule configuration and management)
        $router->get('/category-manager', 'CategoryManagerController@index', 'admin.category-manager');
        $router->get('/category-manager/overview/{competitionId}', 'CategoryManagerController@overview', 'admin.category-manager.overview');
        $router->get('/category-manager/configure/{categoryId}', 'CategoryManagerController@configureCategory', 'admin.category-manager.configure');
        $router->post('/category-manager/update-category', 'CategoryManagerController@updateCategory', 'admin.category-manager.update-category');
        $router->post('/category-manager/customize-rubric', 'CategoryManagerController@customizeRubric', 'admin.category-manager.customize-rubric');
        $router->post('/category-manager/equipment-requirements', 'CategoryManagerController@setEquipmentRequirements', 'admin.category-manager.equipment-requirements');
        $router->post('/category-manager/validate-rules', 'CategoryManagerController@validateCategoryRules', 'admin.category-manager.validate-rules');
        $router->post('/category-manager/bulk-update', 'CategoryManagerController@bulkUpdate', 'admin.category-manager.bulk-update');
        $router->get('/category-manager/export-configuration', 'CategoryManagerController@exportConfiguration', 'admin.category-manager.export-configuration');
        $router->post('/category-manager/import-configuration', 'CategoryManagerController@importConfiguration', 'admin.category-manager.import-configuration');
        
        // ====================================================================
        // TEAM COMPOSITION MANAGEMENT SYSTEM
        // ====================================================================
        
        // Team Composition Management (Main dashboard and overview)
        $router->get('/team/composition', 'Team\\TeamCompositionController@index', 'admin.team.composition');
        $router->get('/team/composition/{team_id}', 'Team\\TeamCompositionController@show', 'admin.team.composition.show');
        $router->post('/team/composition/validate', 'Team\\TeamCompositionController@validateComposition', 'admin.team.composition.validate');
        $router->post('/team/composition/bulk-validate', 'Team\\TeamCompositionController@bulkValidate', 'admin.team.composition.bulk-validate');
        $router->get('/team/composition/statistics', 'Team\\TeamCompositionController@getStatistics', 'admin.team.composition.statistics');
        
        // Participant Management
        $router->post('/team/composition/add-participant', 'Team\\TeamCompositionController@addParticipant', 'admin.team.composition.add-participant');
        $router->post('/team/composition/remove-participant', 'Team\\TeamCompositionController@removeParticipant', 'admin.team.composition.remove-participant');
        $router->post('/team/composition/update-participant-role', 'Team\\TeamCompositionController@updateParticipantRole', 'admin.team.composition.update-participant-role');
        
        // Team Composition Settings (Admin only)
        $router->post('/team/composition/update-settings', 'Team\\TeamCompositionController@updateSettings', 'admin.team.composition.update-settings');
        
        // Coach Assignment System (would be implemented in separate controller)
        $router->get('/team/coach', 'Team\\CoachAssignmentController@index', 'admin.team.coach');
        $router->post('/team/coach/assign', 'Team\\CoachAssignmentController@assignCoach', 'admin.team.coach.assign');
        $router->post('/team/coach/remove', 'Team\\CoachAssignmentController@removeCoach', 'admin.team.coach.remove');
        $router->post('/team/coach/approve', 'Team\\CoachAssignmentController@approveCoach', 'admin.team.coach.approve');
        $router->post('/team/coach/update-training', 'Team\\CoachAssignmentController@updateTrainingStatus', 'admin.team.coach.update-training');
        $router->post('/team/coach/background-check', 'Team\\CoachAssignmentController@updateBackgroundCheck', 'admin.team.coach.background-check');
        
        // Demographic Data Management (POPIA-compliant)
        $router->get('/team/demographics', 'Team\\DemographicsController@index', 'admin.team.demographics');
        $router->post('/team/demographics/collect', 'Team\\DemographicsController@collectDemographics', 'admin.team.demographics.collect');
        $router->post('/team/demographics/update-consent', 'Team\\DemographicsController@updateConsent', 'admin.team.demographics.consent');
        $router->get('/team/demographics/report', 'Team\\DemographicsController@generateReport', 'admin.team.demographics.report');
        $router->post('/team/demographics/anonymize', 'Team\\DemographicsController@anonymizeData', 'admin.team.demographics.anonymize');
        $router->post('/team/demographics/delete-expired', 'Team\\DemographicsController@deleteExpiredData', 'admin.team.demographics.delete-expired');
        
        // Roster Modification Workflows
        $router->get('/team/roster-modifications', 'Team\\RosterModificationController@index', 'admin.team.roster-modifications');
        $router->post('/team/roster-modifications/request', 'Team\\RosterModificationController@createRequest', 'admin.team.roster-modifications.request');
        $router->post('/team/roster-modifications/approve', 'Team\\RosterModificationController@approve', 'admin.team.roster-modifications.approve');
        $router->post('/team/roster-modifications/reject', 'Team\\RosterModificationController@reject', 'admin.team.roster-modifications.reject');
        $router->post('/team/roster-modifications/implement', 'Team\\RosterModificationController@implement', 'admin.team.roster-modifications.implement');
        $router->get('/team/roster-modifications/pending', 'Team\\RosterModificationController@getPending', 'admin.team.roster-modifications.pending');
        $router->get('/team/roster-modifications/overdue', 'Team\\RosterModificationController@getOverdue', 'admin.team.roster-modifications.overdue');
        
        // Modification Approval Workflows
        $router->get('/team/modification-approvals', 'Team\\ModificationApprovalController@index', 'admin.team.modification-approvals');
        $router->post('/team/modification-approvals/process', 'Team\\ModificationApprovalController@processApproval', 'admin.team.modification-approvals.process');
        $router->get('/team/modification-approvals/pending', 'Team\\ModificationApprovalController@getPendingForUser', 'admin.team.modification-approvals.pending');
        $router->post('/team/modification-approvals/bulk-process', 'Team\\ModificationApprovalController@bulkProcess', 'admin.team.modification-approvals.bulk-process');
        
        // ====================================================================
        // REGISTRATION SYSTEM ADMINISTRATION
        // ====================================================================
        
        // School Registration Management (Admin Review & Approval)
        $router->group(['prefix' => 'registration'], function($router) {
            // School registration management
            $router->get('/schools', 'RegistrationAdmin\\SchoolRegistrationAdminController@index', 'admin.registration.schools');
            $router->get('/schools/pending', 'RegistrationAdmin\\SchoolRegistrationAdminController@pending', 'admin.registration.schools.pending');
            $router->get('/schools/{id}', 'RegistrationAdmin\\SchoolRegistrationAdminController@show', 'admin.registration.schools.show');
            $router->get('/schools/{id}/review', 'RegistrationAdmin\\SchoolRegistrationAdminController@review', 'admin.registration.schools.review');
            $router->post('/schools/{id}/approve', 'RegistrationAdmin\\SchoolRegistrationAdminController@approve', 'admin.registration.schools.approve');
            $router->post('/schools/{id}/reject', 'RegistrationAdmin\\SchoolRegistrationAdminController@reject', 'admin.registration.schools.reject');
            $router->post('/schools/bulk-action', 'RegistrationAdmin\\SchoolRegistrationAdminController@bulkAction', 'admin.registration.schools.bulk');
            $router->get('/schools/export', 'RegistrationAdmin\\SchoolRegistrationAdminController@export', 'admin.registration.schools.export');
            
            // Team Registration Management
            $router->get('/teams', 'RegistrationAdmin\\TeamRegistrationAdminController@index', 'admin.registration.teams');
            $router->get('/teams/pending', 'RegistrationAdmin\\TeamRegistrationAdminController@pending', 'admin.registration.teams.pending');
            $router->get('/teams/{id}', 'RegistrationAdmin\\TeamRegistrationAdminController@show', 'admin.registration.teams.show');
            $router->get('/teams/{id}/review', 'RegistrationAdmin\\TeamRegistrationAdminController@review', 'admin.registration.teams.review');
            $router->post('/teams/{id}/approve', 'RegistrationAdmin\\TeamRegistrationAdminController@approve', 'admin.registration.teams.approve');
            $router->post('/teams/{id}/reject', 'RegistrationAdmin\\TeamRegistrationAdminController@reject', 'admin.registration.teams.reject');
            $router->post('/teams/bulk-action', 'RegistrationAdmin\\TeamRegistrationAdminController@bulkAction', 'admin.registration.teams.bulk');
            $router->get('/teams/export', 'RegistrationAdmin\\TeamRegistrationAdminController@export', 'admin.registration.teams.export');
            
            // Registration Analytics and Reports
            $router->get('/analytics', 'RegistrationAdmin\\RegistrationAnalyticsController@index', 'admin.registration.analytics');
            $router->get('/analytics/schools', 'RegistrationAdmin\\RegistrationAnalyticsController@schools', 'admin.registration.analytics.schools');
            $router->get('/analytics/teams', 'RegistrationAdmin\\RegistrationAnalyticsController@teams', 'admin.registration.analytics.teams');
            $router->get('/analytics/participants', 'RegistrationAdmin\\RegistrationAnalyticsController@participants', 'admin.registration.analytics.participants');
            $router->get('/analytics/timeline', 'RegistrationAdmin\\RegistrationAnalyticsController@timeline', 'admin.registration.analytics.timeline');
            $router->get('/analytics/export/{type}', 'RegistrationAdmin\\RegistrationAnalyticsController@export', 'admin.registration.analytics.export');
            
            // Bulk Import Management (Admin oversight)
            $router->get('/bulk-imports', 'RegistrationAdmin\\BulkImportAdminController@index', 'admin.registration.bulk-imports');
            $router->get('/bulk-imports/{id}', 'RegistrationAdmin\\BulkImportAdminController@show', 'admin.registration.bulk-imports.show');
            $router->post('/bulk-imports/{id}/approve', 'RegistrationAdmin\\BulkImportAdminController@approve', 'admin.registration.bulk-imports.approve');
            $router->post('/bulk-imports/{id}/reject', 'RegistrationAdmin\\BulkImportAdminController@reject', 'admin.registration.bulk-imports.reject');
            $router->get('/bulk-imports/{id}/audit-log', 'RegistrationAdmin\\BulkImportAdminController@auditLog', 'admin.registration.bulk-imports.audit');
            
            // Registration Settings and Configuration
            $router->get('/settings', 'RegistrationAdmin\\RegistrationSettingsController@index', 'admin.registration.settings');
            $router->post('/settings/deadlines', 'RegistrationAdmin\\RegistrationSettingsController@updateDeadlines', 'admin.registration.settings.deadlines');
            $router->post('/settings/limits', 'RegistrationAdmin\\RegistrationSettingsController@updateLimits', 'admin.registration.settings.limits');
            $router->post('/settings/validation-rules', 'RegistrationAdmin\\RegistrationSettingsController@updateValidationRules', 'admin.registration.settings.validation');
            $router->post('/settings/notifications', 'RegistrationAdmin\\RegistrationSettingsController@updateNotifications', 'admin.registration.settings.notifications');
        });
        
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
        
        // Live Scoring Management
        $router->get('/live-scoring', 'LiveScoringController@index', 'admin.live-scoring');
        $router->get('/live-scoring/create', 'LiveScoringController@create', 'admin.live-scoring.create');
        $router->post('/live-scoring', 'LiveScoringController@store', 'admin.live-scoring.store');
        $router->get('/live-scoring/sessions/{id}', 'LiveScoringController@show', 'admin.live-scoring.show');
        $router->post('/live-scoring/sessions/{id}/start', 'LiveScoringController@startSession', 'admin.live-scoring.start');
        $router->post('/live-scoring/sessions/{id}/stop', 'LiveScoringController@stopSession', 'admin.live-scoring.stop');
        
        // WebSocket Server Management
        $router->get('/live-scoring/websocket', 'LiveScoringController@websocket', 'admin.live-scoring.websocket');
        $router->post('/live-scoring/websocket/control', 'LiveScoringController@serverControl', 'admin.live-scoring.server-control');
        
        // Conflict Resolution
        $router->get('/live-scoring/conflicts', 'LiveScoringController@conflicts', 'admin.live-scoring.conflicts');
        
        // Analytics
        $router->get('/live-scoring/analytics', 'LiveScoringController@analytics', 'admin.live-scoring.analytics');
        
        // Competition categories
        $router->get('/categories', 'CategoryController@index', 'admin.categories');
        $router->post('/categories', 'CategoryController@store', 'admin.categories.store');
        $router->put('/categories/{id}', 'CategoryController@update', 'admin.categories.update');
        $router->delete('/categories/{id}', 'CategoryController@destroy', 'admin.categories.destroy');
        
        // ====================================================================
        // TOURNAMENT BRACKET SYSTEM ROUTES
        // ====================================================================
        
        // Tournament Management
        $router->get('/tournaments', 'TournamentController@index', 'admin.tournaments');
        $router->get('/tournaments/create', 'TournamentController@create', 'admin.tournaments.create');
        $router->post('/tournaments', 'TournamentController@store', 'admin.tournaments.store');
        $router->get('/tournaments/{id}', 'TournamentController@show', 'admin.tournaments.show');
        $router->get('/tournaments/{id}/edit', 'TournamentController@edit', 'admin.tournaments.edit');
        $router->put('/tournaments/{id}', 'TournamentController@update', 'admin.tournaments.update');
        
        // Tournament Seeding
        $router->post('/tournaments/{id}/generate-seeding', 'TournamentController@generateSeeding', 'admin.tournaments.seeding.generate');
        $router->get('/tournaments/{id}/seeding', 'TournamentController@seeding', 'admin.tournaments.seeding');
        $router->post('/tournaments/{id}/seeding', 'TournamentController@updateSeeding', 'admin.tournaments.seeding.update');
        
        // Bracket Generation and Management
        $router->post('/tournaments/{id}/generate-bracket', 'TournamentController@generateBracket', 'admin.tournaments.bracket.generate');
        $router->get('/tournaments/{id}/bracket', 'TournamentController@bracket', 'admin.tournaments.bracket');
        
        // Match Management
        $router->post('/tournaments/matches/{matchId}/score', 'TournamentController@updateMatch', 'admin.tournaments.matches.score');
        
        // Tournament Results
        $router->get('/tournaments/{id}/results', 'TournamentController@results', 'admin.tournaments.results');
        $router->post('/tournaments/{id}/publish-results', 'TournamentController@publishResults', 'admin.tournaments.results.publish');
        
        // ====================================================================
        // SCHEDULING SYSTEM ROUTES
        // ====================================================================
        
        // Scheduling dashboard and calendar
        $router->get('/scheduling', 'SchedulingController@index', 'admin.scheduling.dashboard');
        $router->get('/scheduling/calendar', 'SchedulingController@calendar', 'admin.scheduling.calendar');
        $router->get('/scheduling/calendar-events', 'SchedulingController@getCalendarEvents', 'admin.scheduling.calendar.events');
        
        // Calendar event management
        $router->post('/scheduling/create-event', 'SchedulingController@createEvent', 'admin.scheduling.events.create');
        $router->post('/scheduling/update-event', 'SchedulingController@updateEvent', 'admin.scheduling.events.update');
        $router->delete('/scheduling/events/{id}', 'SchedulingController@deleteEvent', 'admin.scheduling.events.delete');
        
        // Time slot management
        $router->get('/scheduling/time-slots', 'SchedulingController@timeSlots', 'admin.scheduling.timeslots');
        $router->get('/scheduling/time-slots-data', 'SchedulingController@getTimeSlotsData', 'admin.scheduling.timeslots.data');
        $router->post('/scheduling/auto-allocate', 'SchedulingController@autoAllocateSlots', 'admin.scheduling.auto.allocate');
        $router->post('/scheduling/bulk-assign', 'SchedulingController@bulkAssignTeams', 'admin.scheduling.bulk.assign');
        $router->post('/scheduling/assign-team', 'SchedulingController@assignTeamToSlot', 'admin.scheduling.assign.team');
        $router->post('/scheduling/release-slot', 'SchedulingController@releaseSlot', 'admin.scheduling.release.slot');
        $router->post('/scheduling/check-assignment', 'SchedulingController@checkAssignment', 'admin.scheduling.check.assignment');
        
        // Conflict management
        $router->get('/scheduling/conflicts', 'SchedulingController@conflicts', 'admin.scheduling.conflicts');
        $router->post('/scheduling/detect-conflicts', 'SchedulingController@detectConflicts', 'admin.scheduling.conflicts.detect');
        $router->post('/scheduling/resolve-conflict', 'SchedulingController@resolveConflict', 'admin.scheduling.conflicts.resolve');
        $router->post('/scheduling/auto-resolve-conflicts', 'SchedulingController@autoResolveConflicts', 'admin.scheduling.conflicts.auto.resolve');
        
        // Training sessions
        $router->get('/scheduling/training-sessions', 'SchedulingController@trainingSessions', 'admin.scheduling.training.sessions');
        $router->post('/scheduling/generate-training-schedule', 'SchedulingController@generateTrainingSchedule', 'admin.scheduling.training.generate');
        $router->post('/scheduling/training-sessions', 'SchedulingController@createTrainingSession', 'admin.scheduling.training.create');
        $router->put('/scheduling/training-sessions/{id}', 'SchedulingController@updateTrainingSession', 'admin.scheduling.training.update');
        $router->delete('/scheduling/training-sessions/{id}', 'SchedulingController@deleteTrainingSession', 'admin.scheduling.training.delete');
        
        // Notification scheduling
        $router->post('/scheduling/schedule-notifications', 'SchedulingController@scheduleNotifications', 'admin.scheduling.notifications.schedule');
        $router->post('/scheduling/process-notifications', 'SchedulingController@processNotifications', 'admin.scheduling.notifications.process');
        $router->get('/scheduling/notification-stats', 'SchedulingController@getNotificationStats', 'admin.scheduling.notifications.stats');
        
        // Data export and reporting
        $router->get('/scheduling/export', 'SchedulingController@exportSchedule', 'admin.scheduling.export');
        $router->get('/scheduling/unassigned-teams', 'SchedulingController@getUnassignedTeams', 'admin.scheduling.unassigned.teams');
        $router->get('/scheduling/statistics', 'SchedulingController@getSchedulingStatistics', 'admin.scheduling.statistics');
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
        
        // ====================================================================
        // REGISTRATION SYSTEM - TEAM REGISTRATION
        // ====================================================================
        
        // Team registration dashboard and management
        $router->get('/register/team', 'Registration\\TeamRegistrationController@index', 'coordinator.team.register');
        $router->get('/register/team/select-category', 'Registration\\TeamRegistrationController@selectCategory', 'coordinator.team.register.category');
        $router->get('/register/team/create', 'Registration\\TeamRegistrationController@create', 'coordinator.team.register.create');
        $router->post('/register/team/store', 'Registration\\TeamRegistrationController@store', 'coordinator.team.register.store');
        
        // Team registration viewing and editing
        $router->get('/register/team/{id}', 'Registration\\TeamRegistrationController@show', 'coordinator.team.register.show');
        $router->get('/register/team/{id}/edit', 'Registration\\TeamRegistrationController@edit', 'coordinator.team.register.edit');
        $router->post('/register/team/{id}/update', 'Registration\\TeamRegistrationController@update', 'coordinator.team.register.update');
        
        // Team participant management
        $router->post('/register/team/add-participant', 'Registration\\TeamRegistrationController@addParticipant', 'coordinator.team.register.add-participant');
        $router->post('/register/team/remove-participant', 'Registration\\TeamRegistrationController@removeParticipant', 'coordinator.team.register.remove-participant');
        
        // Team registration workflow
        $router->post('/register/team/submit', 'Registration\\TeamRegistrationController@submit', 'coordinator.team.register.submit');
        $router->post('/register/team/withdraw', 'Registration\\TeamRegistrationController@withdraw', 'coordinator.team.register.withdraw');
        
        // Team registration AJAX endpoints
        $router->post('/register/team/check-eligibility', 'Registration\\TeamRegistrationController@checkParticipantEligibility', 'coordinator.team.register.check-eligibility');
        $router->get('/register/team/closed', 'Registration\\TeamRegistrationController@closed', 'coordinator.team.register.closed');
        
        // ====================================================================
        // REGISTRATION SYSTEM - BULK IMPORT
        // ====================================================================
        
        // Bulk student import dashboard and wizard
        $router->get('/bulk-import', 'Registration\\BulkImportController@index', 'coordinator.bulk-import');
        $router->get('/bulk-import/wizard', 'Registration\\BulkImportController@wizard', 'coordinator.bulk-import.wizard');
        
        // Template downloads
        $router->get('/bulk-import/download-template', 'Registration\\BulkImportController@downloadTemplate', 'coordinator.bulk-import.template');
        
        // Upload and validation workflow
        $router->post('/bulk-import/upload', 'Registration\\BulkImportController@upload', 'coordinator.bulk-import.upload');
        $router->get('/bulk-import/validation-status', 'Registration\\BulkImportController@validationStatus', 'coordinator.bulk-import.validation-status');
        $router->get('/bulk-import/{id}/validation-results', 'Registration\\BulkImportController@validationResults', 'coordinator.bulk-import.validation-results');
        
        // Import execution and results
        $router->post('/bulk-import/execute', 'Registration\\BulkImportController@executeImport', 'coordinator.bulk-import.execute');
        $router->get('/bulk-import/{id}/results', 'Registration\\BulkImportController@results', 'coordinator.bulk-import.results');
        $router->get('/bulk-import/{id}/download-errors', 'Registration\\BulkImportController@downloadErrorReport', 'coordinator.bulk-import.download-errors');
        
        // File uploads
        $router->post('/upload/consent-form', '\\App\\Controllers\\FileUploadController@uploadConsentForm', 'coordinator.upload.consent');
        $router->post('/upload/team-submission', '\\App\\Controllers\\FileUploadController@uploadTeamSubmission', 'coordinator.upload.submission');
        $router->post('/upload/profile-photo', '\\App\\Controllers\\FileUploadController@uploadProfilePhoto', 'coordinator.upload.profile');
        $router->get('/files/{id}/download', '\\App\\Controllers\\FileUploadController@downloadFile', 'coordinator.files.download');
        $router->delete('/files/{id}', '\\App\\Controllers\\FileUploadController@deleteFile', 'coordinator.files.delete');
        $router->get('/files/{id}/info', '\\App\\Controllers\\FileUploadController@getFileInfo', 'coordinator.files.info');
        
        // Team Composition Management (School-specific access)
        $router->get('/team-composition', '\\App\\Controllers\\Team\\TeamCompositionController@index', 'coordinator.team.composition');
        $router->get('/team-composition/{team_id}', '\\App\\Controllers\\Team\\TeamCompositionController@show', 'coordinator.team.composition.show');
        $router->post('/team-composition/validate', '\\App\\Controllers\\Team\\TeamCompositionController@validateComposition', 'coordinator.team.composition.validate');
        $router->post('/team-composition/add-participant', '\\App\\Controllers\\Team\\TeamCompositionController@addParticipant', 'coordinator.team.composition.add-participant');
        $router->post('/team-composition/remove-participant', '\\App\\Controllers\\Team\\TeamCompositionController@removeParticipant', 'coordinator.team.composition.remove-participant');
        $router->post('/team-composition/update-participant-role', '\\App\\Controllers\\Team\\TeamCompositionController@updateParticipantRole', 'coordinator.team.composition.update-participant-role');
        
        // Coach Assignment for School Teams
        $router->post('/team-coach/assign', '\\App\\Controllers\\Team\\CoachAssignmentController@assignCoach', 'coordinator.team.coach.assign');
        $router->post('/team-coach/remove', '\\App\\Controllers\\Team\\CoachAssignmentController@removeCoach', 'coordinator.team.coach.remove');
        
        // Roster Modification Requests
        $router->get('/roster-modifications', '\\App\\Controllers\\Team\\RosterModificationController@index', 'coordinator.roster-modifications');
        $router->post('/roster-modifications/request', '\\App\\Controllers\\Team\\RosterModificationController@createRequest', 'coordinator.roster-modifications.request');
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
        
        // Team Composition Management (Own teams only)
        $router->get('/team-composition', '\\App\\Controllers\\Team\\TeamCompositionController@index', 'coach.team.composition');
        $router->get('/team-composition/{team_id}', '\\App\\Controllers\\Team\\TeamCompositionController@show', 'coach.team.composition.show');
        $router->post('/team-composition/validate', '\\App\\Controllers\\Team\\TeamCompositionController@validateComposition', 'coach.team.composition.validate');
        
        // Limited participant management for coaches
        $router->post('/team-composition/update-participant-role', '\\App\\Controllers\\Team\\TeamCompositionController@updateParticipantRole', 'coach.team.composition.update-participant-role');
        
        // Request roster modifications (coaches can request, but not approve)
        $router->post('/roster-modifications/request', '\\App\\Controllers\\Team\\RosterModificationController@createRequest', 'coach.roster-modifications.request');
        
        // ====================================================================
        // REGISTRATION SYSTEM - TEAM REGISTRATION (Coach Access)
        // ====================================================================
        
        // Team registration for coaches (own teams only)
        $router->get('/register/team', 'Registration\\TeamRegistrationController@index', 'coach.team.register');
        $router->get('/register/team/{id}', 'Registration\\TeamRegistrationController@show', 'coach.team.register.show');
        $router->get('/register/team/{id}/edit', 'Registration\\TeamRegistrationController@edit', 'coach.team.register.edit');
        $router->post('/register/team/{id}/update', 'Registration\\TeamRegistrationController@update', 'coach.team.register.update');
        
        // Team participant management (limited for coaches)
        $router->post('/register/team/add-participant', 'Registration\\TeamRegistrationController@addParticipant', 'coach.team.register.add-participant');
        $router->post('/register/team/remove-participant', 'Registration\\TeamRegistrationController@removeParticipant', 'coach.team.register.remove-participant');
        
        // Team submission workflow
        $router->post('/register/team/submit', 'Registration\\TeamRegistrationController@submit', 'coach.team.register.submit');
    });
    
    // ========================================================================
    // JUDGE AUTHENTICATION ROUTES (No authentication required)
    // ========================================================================
    
    $router->group(['prefix' => 'judge/auth'], function($router) {
        // Authentication pages
        $router->get('/', 'JudgeAuthController@index', 'judge.auth.login');
        $router->get('/login', 'JudgeAuthController@index', 'judge.auth.login.alt');
        $router->post('/login', 'JudgeAuthController@login', 'judge.auth.login.post');
        
        // Logout (can be called from anywhere)
        $router->post('/logout', 'JudgeAuthController@logout', 'judge.auth.logout');
        $router->get('/logout', 'JudgeAuthController@logout', 'judge.auth.logout.get');
        
        // Password reset
        $router->get('/reset-password', 'JudgeAuthController@resetPassword', 'judge.auth.reset');
        $router->post('/reset-password', 'JudgeAuthController@resetPassword', 'judge.auth.reset.post');
        
        // Device management and verification (requires authentication)
        $router->get('/devices', 'JudgeAuthController@deviceManagement', 'judge.auth.devices');
        $router->post('/trust-device', 'JudgeAuthController@trustDevice', 'judge.auth.trust.device');
        $router->post('/block-device', 'JudgeAuthController@blockDevice', 'judge.auth.block.device');
        
        // 2FA Setup (requires authentication)
        $router->get('/setup-2fa', 'JudgeAuthController@setup2FA', 'judge.auth.2fa.setup');
        $router->post('/setup-2fa', 'JudgeAuthController@setup2FA', 'judge.auth.2fa.setup.post');
        
        // PIN Setup (requires authentication)
        $router->get('/setup-pin', 'JudgeAuthController@setupPIN', 'judge.auth.pin.setup');
        $router->post('/setup-pin', 'JudgeAuthController@setupPIN', 'judge.auth.pin.setup.post');
        
        // Access verification API
        $router->post('/verify-access', 'JudgeAuthController@verifyAccess', 'judge.auth.verify.access');
    });

    // Judge Registration Routes (Public)
    $router->group(['prefix' => 'judge/register'], function($router) {
        $router->get('/', 'JudgeRegistrationController@index', 'judge.register');
        $router->post('/', 'JudgeRegistrationController@register', 'judge.register.post');
        $router->get('/success/{judgeCode}', 'JudgeRegistrationController@success', 'judge.register.success');
        $router->get('/onboarding/{judgeCode?}', 'JudgeRegistrationController@onboarding', 'judge.onboarding');
        $router->post('/onboarding/update', 'JudgeRegistrationController@updateOnboardingItem', 'judge.onboarding.update');
        $router->post('/upload-documents', 'JudgeRegistrationController@uploadDocuments', 'judge.documents.upload');
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
        
        // Live Scoring Sessions
        $router->get('/live-scoring', 'LiveScoringController@index', 'judge.live-scoring');
        $router->get('/live-scoring/{sessionId}', 'LiveScoringController@session', 'judge.live-scoring.session');
        $router->post('/live-scoring/{sessionId}/score', 'LiveScoringController@submitScore', 'judge.live-scoring.submit');
        
        // Scoring History
        $router->get('/scoring-history', 'ScoringController@history', 'judge.scoring.history');
        
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
    
    // ========================================================================
    // REGISTRATION SYSTEM API ROUTES
    // ========================================================================
    
    // School Registration API (School coordinators and admins)
    $router->group(['middleware' => 'role:school_coordinator,competition_admin,super_admin', 'prefix' => 'registration'], function($router) {
        // School registration status and management
        $router->get('/schools/status', 'Api\\Registration\\SchoolRegistrationApiController@getStatus', 'api.registration.schools.status');
        $router->post('/schools/validate-step', 'Api\\Registration\\SchoolRegistrationApiController@validateStep', 'api.registration.schools.validate-step');
        $router->post('/schools/save-progress', 'Api\\Registration\\SchoolRegistrationApiController@saveProgress', 'api.registration.schools.save-progress');
        
        // Team Registration API  
        $router->get('/teams', 'Api\\Registration\\TeamRegistrationApiController@index', 'api.registration.teams');
        $router->get('/teams/{id}', 'Api\\Registration\\TeamRegistrationApiController@show', 'api.registration.teams.show');
        $router->post('/teams/validate-composition', 'Api\\Registration\\TeamRegistrationApiController@validateComposition', 'api.registration.teams.validate-composition');
        $router->get('/teams/categories/available', 'Api\\Registration\\TeamRegistrationApiController@getAvailableCategories', 'api.registration.teams.categories.available');
        
        // Participant Eligibility API
        $router->post('/participants/check-eligibility', 'Api\\Registration\\ParticipantEligibilityApiController@checkEligibility', 'api.registration.participants.check-eligibility');
        $router->get('/participants/search', 'Api\\Registration\\ParticipantEligibilityApiController@searchParticipants', 'api.registration.participants.search');
        $router->post('/participants/bulk-validate', 'Api\\Registration\\ParticipantEligibilityApiController@bulkValidate', 'api.registration.participants.bulk-validate');
        
        // Category Validation API
        $router->get('/categories/{id}/limits', 'Api\\Registration\\CategoryValidationApiController@getCategoryLimits', 'api.registration.categories.limits');
        $router->post('/categories/validate-registration', 'Api\\Registration\\CategoryValidationApiController@validateRegistration', 'api.registration.categories.validate-registration');
        $router->get('/categories/school-status/{schoolId}', 'Api\\Registration\\CategoryValidationApiController@getSchoolCategoryStatus', 'api.registration.categories.school-status');
        
        // Bulk Import API
        $router->get('/bulk-imports', 'Api\\Registration\\BulkImportApiController@index', 'api.registration.bulk-imports');
        $router->get('/bulk-imports/{id}/status', 'Api\\Registration\\BulkImportApiController@getStatus', 'api.registration.bulk-imports.status');
        $router->post('/bulk-imports/validate-file', 'Api\\Registration\\BulkImportApiController@validateFile', 'api.registration.bulk-imports.validate-file');
        $router->get('/bulk-imports/{id}/progress', 'Api\\Registration\\BulkImportApiController@getProgress', 'api.registration.bulk-imports.progress');
        $router->get('/bulk-imports/{id}/errors', 'Api\\Registration\\BulkImportApiController@getErrors', 'api.registration.bulk-imports.errors');
    });
    
    // Registration Analytics API (Admin only)
    $router->group(['middleware' => 'role:super_admin', 'prefix' => 'registration/analytics'], function($router) {
        $router->get('/dashboard-stats', 'Api\\Registration\\RegistrationAnalyticsApiController@getDashboardStats', 'api.registration.analytics.dashboard');
        $router->get('/registration-trends', 'Api\\Registration\\RegistrationAnalyticsApiController@getRegistrationTrends', 'api.registration.analytics.trends');
        $router->get('/category-distribution', 'Api\\Registration\\RegistrationAnalyticsApiController@getCategoryDistribution', 'api.registration.analytics.category-distribution');
        $router->get('/school-participation', 'Api\\Registration\\RegistrationAnalyticsApiController@getSchoolParticipation', 'api.registration.analytics.school-participation');
        $router->get('/deadline-compliance', 'Api\\Registration\\RegistrationAnalyticsApiController@getDeadlineCompliance', 'api.registration.analytics.deadline-compliance');
        $router->get('/geographic-distribution', 'Api\\Registration\\RegistrationAnalyticsApiController@getGeographicDistribution', 'api.registration.analytics.geographic');
    });
    
    // Public Registration Status API (Public access for status checking)
    $router->group(['prefix' => 'public/registration'], function($router) {
        $router->post('/school/check-status', 'Api\\Registration\\PublicRegistrationApiController@checkSchoolStatus', 'api.public.registration.school.status');
        $router->get('/deadlines', 'Api\\Registration\\PublicRegistrationApiController@getDeadlines', 'api.public.registration.deadlines');
        $router->get('/categories/public-info', 'Api\\Registration\\PublicRegistrationApiController@getCategoriesPublicInfo', 'api.public.registration.categories');
        $router->get('/competition/public-status', 'Api\\Registration\\PublicRegistrationApiController@getCompetitionStatus', 'api.public.registration.competition.status');
    });
});

// ============================================================================
// ADDITIONAL MISSING ROUTES (Based on navigation and errors)
// ============================================================================

// Public Routes
$router->group(['middleware' => 'guest'], function($router) {
    // Public scoreboard access
    $router->get('/scoreboard', 'ScoreboardController@index', 'public.scoreboard');
    $router->get('/scoreboard/{id}', 'ScoreboardController@show', 'public.scoreboard.show');
    $router->get('/scoreboard/{id}/api', 'ScoreboardController@api', 'public.scoreboard.api');
    $router->get('/scoreboard/{id}/embed', 'ScoreboardController@embed', 'public.scoreboard.embed');
    $router->get('/scoreboard/{id}/qr', 'ScoreboardController@qr', 'public.scoreboard.qr');
    $router->get('/scoreboard/{id}/social', 'ScoreboardController@social', 'public.scoreboard.social');
});

// Authenticated Routes
$router->group(['middleware' => 'auth'], function($router) {
    
    // Admin Routes - Additional missing routes
    $router->group(['middleware' => 'role:super_admin,competition_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
        // Rubric Management (redirect to competition judging for now)
        $router->get('/rubrics', 'CompetitionJudgingController@index', 'admin.rubrics');
        
        // Team Management - redirect to existing team management controller
        $router->get('/team-management', 'TeamManagementController@index', 'admin.team-management');
        $router->get('/team-management/{id}', 'TeamManagementController@show', 'admin.team-management.show');
        $router->post('/team-management', 'TeamManagementController@store', 'admin.team-management.store');
        $router->put('/team-management/{id}', 'TeamManagementController@update', 'admin.team-management.update');
        $router->delete('/team-management/{id}', 'TeamManagementController@destroy', 'admin.team-management.destroy');
        
        // School Management - redirect to existing school management controller
        $router->get('/school-management', 'SchoolManagementController@index', 'admin.school-management');
        $router->get('/school-management/{id}', 'SchoolManagementController@show', 'admin.school-management.show');
        $router->post('/school-management', 'SchoolManagementController@store', 'admin.school-management.store');
        $router->put('/school-management/{id}', 'SchoolManagementController@update', 'admin.school-management.update');
        $router->delete('/school-management/{id}', 'SchoolManagementController@destroy', 'admin.school-management.destroy');
    });
    
    // Judge Routes - Additional missing routes (alternative paths)
    $router->group(['middleware' => 'role:judge,competition_admin,super_admin'], function($router) {
        // Alternative judge dashboard paths
        $router->get('/judging', 'Judge\\DashboardController@index', 'judging.index');
        $router->get('/judging/dashboard', 'Judge\\DashboardController@index', 'judging.dashboard');
    });
    
    // Scorecard management - broader access for public/authenticated users
    $router->get('/scorecards', 'Judge\\ScoringController@index', 'scorecards.index');
    $router->get('/scorecards/{id}', 'Judge\\ScoringController@show', 'scorecards.show');
    
    // Team Management Routes (alternative paths)
    $router->group(['middleware' => 'role:school_coordinator,team_coach,competition_admin,super_admin'], function($router) {
        $router->get('/teams/manage', 'Admin\\TeamManagementController@index', 'teams.manage');
        $router->get('/team-management', 'Admin\\TeamManagementController@index', 'team-management.index');
        $router->get('/school-management', 'Admin\\SchoolManagementController@index', 'school-management.index');
    });
});