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

    // Strip the subfolder prefix if present (e.g., /GSCMS/public/ or /GSCMS/)
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

    // Check for different patterns:
    // 1. /GSCMS/public/path -> /path (direct public access)
    // 2. /GSCMS/path -> /path (htaccess redirected)
    if (preg_match('#^(/[^/]+/public)(.*)$#', $uri, $matches)) {
        // Pattern: /GSCMS/public/something
        $remainingPath = $matches[2];
        $uri = empty($remainingPath) ? '/' : $remainingPath;
    } elseif (preg_match('#^(/[^/]+)(.*)$#', $uri, $matches) && $matches[1] !== '/') {
        // Pattern: /GSCMS/something (after htaccess redirect)
        $potentialSubdir = $matches[1];
        $remainingPath = $matches[2];
        
        // Check if this looks like a GSCMS subdirectory
        if (strpos($_SERVER['SCRIPT_NAME'], $potentialSubdir) !== false) {
            $uri = empty($remainingPath) ? '/' : $remainingPath;
        }
    } elseif ($scriptPath !== '/' && strpos($uri, $scriptPath) === 0) {
        // Original logic for other cases
        $uri = substr($uri, strlen($scriptPath));
    }
    
    // Ensure URI starts with /
    if (empty($uri) || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // Handle method override for forms (e.g., _method=PUT, _method=DELETE)
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }

    // Dispatch route
    $response = $router->dispatch($uri, $method);
    
    // Output response
    echo $response;
    
} catch (Exception $e) {
    // Let the error handler deal with it
    throw $e;
}