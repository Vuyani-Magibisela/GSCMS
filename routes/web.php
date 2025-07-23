<?php
// routes/web.php - Web Routes

// Home route
$router->get('/', 'HomeController@index', 'home');

// Dashboard route (requires authentication)
$router->get('/dashboard', 'HomeController@dashboard', 'dashboard');

// Admin routes
$router->group(['prefix' => '/admin', 'namespace' => 'Admin'], function($router) {
    $router->get('/dashboard', 'DashboardController@index', 'admin.dashboard');
    $router->get('/users', 'UserController@index', 'admin.users');
    $router->get('/schools', 'SchoolController@index', 'admin.schools');
});

// Auth routes
$router->group(['prefix' => '/auth'], function($router) {
    // Login routes
    $router->get('/login', 'AuthController@showLogin', 'auth.login');
    $router->post('/login', 'AuthController@login', 'auth.login.post');
    $router->post('/logout', 'AuthController@logout', 'auth.logout');
    
    // Registration routes
    $router->get('/register', 'AuthController@showRegister', 'auth.register');
    $router->post('/register', 'AuthController@register', 'auth.register.post');
    
    // Password reset routes
    $router->get('/forgot-password', 'AuthController@showForgotPassword', 'auth.forgot-password');
    $router->post('/forgot-password', 'AuthController@forgotPassword', 'auth.forgot-password.post');
    $router->get('/reset-password', 'AuthController@showResetPassword', 'auth.reset-password');
    $router->post('/reset-password', 'AuthController@resetPassword', 'auth.reset-password.post');
    
    // Password change routes (for authenticated users)
    $router->get('/change-password', 'AuthController@showChangePassword', 'auth.change-password');
    $router->post('/change-password', 'AuthController@changePassword', 'auth.change-password.post');
    
    // Email verification routes
    $router->get('/verify-email', 'AuthController@verifyEmail', 'auth.verify-email');
    $router->post('/resend-verification', 'AuthController@resendVerification', 'auth.resend-verification');
});

// API routes
$router->group(['prefix' => '/api'], function($router) {
    $router->get('/users', 'Api\\UserController@index', 'api.users');
    $router->get('/users/{id}', 'Api\\UserController@show', 'api.users.show');
    $router->post('/users', 'Api\\UserController@store', 'api.users.store');
    $router->put('/users/{id}', 'Api\\UserController@update', 'api.users.update');
    $router->delete('/users/{id}', 'Api\\UserController@destroy', 'api.users.destroy');
});

// Test route for framework verification
$router->get('/test', function($request, $response) {
    return json_encode([
        'message' => 'MVC Framework is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);
}, 'test');

// Simple test route for auth links
$router->get('/test-auth', function() {
    return '<h1>Auth Test Route Working!</h1><p><a href="/auth/login">Login</a> | <a href="/auth/register">Register</a></p>';
}, 'test-auth');

// Test dashboard route
$router->get('/test-dashboard', function() {
    return '<h1>Dashboard Test Route Working!</h1><p>This confirms the dashboard route should work.</p>';
}, 'test-dashboard');

// Test dashboard controller (without auth for testing)
$router->get('/test-dashboard-controller', function() {
    $controller = new \App\Controllers\HomeController();
    
    // Create mock request and response
    $request = new \App\Core\Request();
    $response = new \App\Core\Response();
    
    // Temporarily return a simple test
    return '<h1>Dashboard Controller Test</h1><p>HomeController exists and can be instantiated.</p>';
}, 'test-dashboard-controller');

// Test database connection
$router->get('/test/db', 'TestController@database', 'test.db');

// Create test user route (for development only)
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