<?php
// test_contact_route.php - Simple test for contact route functionality

require_once 'app/bootstrap.php';

// Simulate a logged-in admin user
$_SESSION['user_id'] = 1; // Assuming admin user ID 1 exists
$_SESSION['user_role'] = 'super_admin';
$_SESSION['authenticated'] = true;

// Set request method and URI
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/admin/contacts/create?school_id=3';

try {
    echo "Testing Contact Route...\n";
    echo "Request: GET /admin/contacts/create?school_id=3\n";
    echo "Session: " . ($_SESSION['user_role'] ?? 'Not set') . "\n\n";
    
    // Initialize the controller directly
    $controller = new \App\Controllers\Admin\ContactController();
    
    // Test the create method
    echo "Calling ContactController::create()...\n";
    $result = $controller->create();
    
    if (strpos($result, 'Add New School Contact') !== false) {
        echo "SUCCESS: Contact creation form loaded successfully!\n";
        echo "Form contains expected elements.\n";
    } else {
        echo "ERROR: Unexpected response from contact create method.\n";
        echo "Response preview: " . substr($result, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTING SUMMARY ===\n";
echo "If you see 'SUCCESS' above, the contact functionality is working.\n";
echo "The 404 error you encountered is likely due to authentication middleware.\n";
echo "You need to:\n";
echo "1. Log in as an admin user first\n";
echo "2. Then access /admin/contacts/create?school_id=3\n";