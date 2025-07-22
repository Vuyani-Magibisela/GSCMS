<?php
// routes/web.php - Web Routes

// Home route
$router->get('/', 'HomeController@index', 'home');

// Admin routes
$router->group(['prefix' => '/admin', 'namespace' => 'Admin'], function($router) {
    $router->get('/dashboard', 'DashboardController@index', 'admin.dashboard');
    $router->get('/users', 'UserController@index', 'admin.users');
    $router->get('/schools', 'SchoolController@index', 'admin.schools');
});

// Auth routes
$router->group(['prefix' => '/auth'], function($router) {
    $router->get('/login', 'AuthController@showLogin', 'auth.login');
    $router->post('/login', 'AuthController@login', 'auth.login.post');
    $router->post('/logout', 'AuthController@logout', 'auth.logout');
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
        'request_uri' => $request->getUri(),
        'method' => $request->getMethod()
    ]);
}, 'test');

// Test database connection
$router->get('/test/db', 'TestController@database', 'test.db');