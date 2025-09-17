<?php
require_once 'app/bootstrap.php';

header('Content-Type: text/plain');

$session = \App\Core\Session::getInstance();
$session->start();

echo "=== AUTH DEBUG ===\n";

try {
    $auth = \App\Core\Auth::getInstance();

    echo "Auth instance created successfully.\n";
    echo "Is authenticated: " . ($auth->check() ? 'YES' : 'NO') . "\n";

    if ($auth->check()) {
        $user = $auth->user();
        echo "User ID: " . $user->id . "\n";
        echo "User Email: " . $user->email . "\n";
        echo "User Role: " . $user->role . "\n";
        echo "User Status: " . $user->status . "\n";

        // Test role checking
        echo "\n=== ROLE TESTING ===\n";
        echo "Has 'judge' role: " . ($user->hasRole('judge') ? 'YES' : 'NO') . "\n";
        echo "Has any of ['judge','competition_admin','super_admin']: " . ($user->hasAnyRole(['judge','competition_admin','super_admin']) ? 'YES' : 'NO') . "\n";

        // Test middleware authentication
        echo "\n=== MIDDLEWARE TEST ===\n";
        $middleware = new \App\Core\Middleware\RoleMiddleware();

        // Create a mock request
        $mockRequest = new stdClass();
        $mockRequest->getUri = function() { return '/judging/dashboard'; };
        $mockRequest->expectsJson = function() { return false; };

        try {
            $result = $middleware->handle($mockRequest, function($req) { return 'SUCCESS'; }, 'judge,competition_admin,super_admin');
            echo "Middleware result: " . $result . "\n";
        } catch (Exception $e) {
            echo "Middleware FAILED: " . $e->getMessage() . "\n";
            echo "Error code: " . $e->getCode() . "\n";
        }

    } else {
        echo "User is not authenticated.\n";
        echo "Session user_id: " . ($session->get('user_id') ?? 'null') . "\n";
        echo "Session user_role: " . ($session->get('user_role') ?? 'null') . "\n";
        echo "Session user_email: " . ($session->get('user_email') ?? 'null') . "\n";
    }

} catch (Exception $e) {
    echo "Auth ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>