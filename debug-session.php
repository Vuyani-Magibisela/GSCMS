<?php
require_once 'app/bootstrap.php';

$session = \App\Core\Session::getInstance();
$session->start();

echo "=== SESSION DEBUG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($session->get('user_id') ?? 'null') . "\n";
echo "Judge ID: " . ($session->get('judge_id') ?? 'null') . "\n";
echo "User Role: " . ($session->get('user_role') ?? 'null') . "\n";
echo "User Email: " . ($session->get('user_email') ?? 'null') . "\n";

echo "\n=== DATABASE QUERY TEST ===\n";

$database = \App\Core\Database::getInstance();

try {
    $userId = $session->get('user_id');
    echo "Looking for judge profile with user_id: $userId\n";

    $judge = $database->query("
        SELECT jp.*, u.first_name, u.last_name, u.email, u.email_verified,
               o.organization_name, o.organization_type
        FROM judge_profiles jp
        INNER JOIN users u ON jp.user_id = u.id
        LEFT JOIN organizations o ON jp.organization_id = o.id
        WHERE jp.user_id = ? AND jp.status = 'active'
    ", [$userId]);

    if (!empty($judge)) {
        echo "Judge found:\n";
        print_r($judge[0]);
    } else {
        echo "No judge profile found.\n";

        // Check if judge profile exists with different status
        $allJudges = $database->query("
            SELECT jp.*, u.first_name, u.last_name
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE jp.user_id = ?
        ", [$userId]);

        if (!empty($allJudges)) {
            echo "Judge profile exists but with different status:\n";
            print_r($allJudges[0]);
        } else {
            echo "No judge profile exists for this user at all.\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>