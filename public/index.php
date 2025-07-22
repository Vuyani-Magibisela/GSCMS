<?php
// public/index.php - Application Entry Point

// Load bootstrap
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

try {
    // Create router instance
    $router = new Router();
    
    // Load routes
    require_once __DIR__ . '/../routes/web.php';
    
    // Get current request
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Dispatch route
    $response = $router->dispatch($uri, $method);
    
    // Output response
    echo $response;
    
} catch (Exception $e) {
    // Let the error handler deal with it
    throw $e;
}