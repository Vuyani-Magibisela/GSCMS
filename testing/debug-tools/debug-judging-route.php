<?php
require_once 'app/bootstrap.php';

// Set proper headers to avoid CSRF issues
header('Content-Type: text/plain');

$session = \App\Core\Session::getInstance();
$session->start();

echo "=== DEBUGGING /judging/dashboard ROUTE ===\n\n";

try {
    $controller = new \App\Controllers\Judge\DashboardController();

    echo "Controller created successfully.\n";
    echo "Session ID: " . session_id() . "\n";

    // Test getCurrentJudge method directly
    $getCurrentJudgeMethod = new ReflectionMethod($controller, 'getCurrentJudge');
    $getCurrentJudgeMethod->setAccessible(true);
    $judge = $getCurrentJudgeMethod->invoke($controller);

    if ($judge) {
        echo "Judge found: " . $judge['first_name'] . " " . $judge['last_name'] . "\n";
        echo "Judge ID: " . $judge['id'] . "\n";
        echo "Judge Code: " . $judge['judge_code'] . "\n";
        echo "Status: " . $judge['status'] . "\n";

        echo "\nCalling index() method...\n";
        $result = $controller->index();
        echo "Index method executed successfully.\n";

    } else {
        echo "No judge profile found - this is why redirect happens!\n";

        // Debug session values
        echo "\nSession values:\n";
        echo "user_id: " . ($session->get('user_id') ?? 'null') . "\n";
        echo "judge_id: " . ($session->get('judge_id') ?? 'null') . "\n";
        echo "user_role: " . ($session->get('user_role') ?? 'null') . "\n";
        echo "user_email: " . ($session->get('user_email') ?? 'null') . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>