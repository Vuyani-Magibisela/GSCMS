<?php
require_once 'app/bootstrap.php';

header('Content-Type: text/plain');

echo "=== ROUTE RESOLUTION TEST ===\n";

// Set up the environment to match the actual request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/GSCMS/judging/dashboard';

try {
    // Get router instance
    $router = new \App\Core\Router();

    // Load routes
    include 'routes/web.php';

    echo "Routes loaded successfully.\n";
    echo "Testing route resolution for: " . $_SERVER['REQUEST_URI'] . "\n";

    // Check if route exists
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    echo "Method: $method\n";
    echo "URI Path: $uri\n";

    // Try to resolve route manually
    $reflection = new ReflectionClass($router);
    $routesProperty = $reflection->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routes = $routesProperty->getValue($router);

    echo "\nAvailable routes:\n";
    foreach ($routes as $route) {
        if (strpos($route['pattern'], 'judging') !== false) {
            echo "- " . $route['method'] . " " . $route['pattern'] . " -> " . $route['action'] . "\n";
        }
    }

    echo "\nTesting dispatch...\n";

    // Test dispatch (but catch any redirects)
    ob_start();
    try {
        $router->dispatch();
        $output = ob_get_clean();
        echo "Dispatch completed successfully.\n";
        echo "Output length: " . strlen($output) . " bytes\n";
    } catch (Exception $e) {
        ob_end_clean();
        echo "Dispatch failed: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";

        if (strpos($e->getMessage(), 'headers already sent') !== false) {
            echo "This is likely a redirect attempt.\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>