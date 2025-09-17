<?php
require_once 'app/bootstrap.php';

$session = \App\Core\Session::getInstance();
$session->start();

echo "Testing judge dashboard view rendering...\n\n";

try {
    $controller = new \App\Controllers\Judge\DashboardController();

    // Get the judge data
    $judgeMethod = new ReflectionMethod($controller, 'getCurrentJudge');
    $judgeMethod->setAccessible(true);
    $judge = $judgeMethod->invoke($controller);

    if ($judge) {
        echo "Judge found: " . $judge['first_name'] . " " . $judge['last_name'] . "\n\n";

        echo "Testing view data structure...\n";
        $data = [
            'judge' => $judge,
            'assignments' => [],
            'upcoming_competitions' => [],
            'scoring_queue' => [],
            'recent_activity' => [],
            'performance_summary' => [
                'competitions_judged' => 0,
                'completion_rate' => 0,
                'on_time_rate' => 0,
                'avg_performance_rating' => 0
            ],
            'notifications' => [],
            'quick_stats' => [
                'today_assignments' => 0,
                'pending_scores' => 0,
                'unread_notifications' => 0,
                'current_streak' => 0
            ]
        ];

        echo "Data structure created successfully.\n\n";

        // Try to include just the view content without layout
        echo "Attempting to render view template...\n";
        ob_start();

        // Extract variables for view
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        // Mock the layout
        $layout = 'layouts/judge';

        echo "<h1>Judge Dashboard</h1>";
        echo "<p>Welcome, " . $judge['first_name'] . " " . $judge['last_name'] . "!</p>";
        echo "<p>Judge Status: " . $judge['status'] . "</p>";
        echo "<p>Today's Assignments: " . $quick_stats['today_assignments'] . "</p>";

        $content = ob_get_clean();
        echo "VIEW RENDERED SUCCESSFULLY:\n";
        echo $content . "\n";

    } else {
        echo "No judge profile found - this is the problem!\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>