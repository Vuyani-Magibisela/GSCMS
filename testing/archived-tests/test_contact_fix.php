<?php
// test_contact_fix.php - Test contact controller after fixing static method calls

require_once 'app/bootstrap.php';

// Simulate admin authentication (session already started by bootstrap)
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'super_admin';
$_SESSION['authenticated'] = true;

// Test the controller directly
try {
    echo "Testing ContactController after fixes...\n\n";
    
    // Test 1: Check if we can instantiate models properly
    echo "1. Testing model instantiation:\n";
    
    $contactModel = new \App\Models\Contact();
    echo "   ✓ Contact model created\n";
    
    $schoolModel = new \App\Models\School();
    echo "   ✓ School model created\n";
    
    // Test 2: Test finding school ID 3
    echo "\n2. Testing School::find(3):\n";
    $school = $schoolModel->find(3);
    if ($school) {
        echo "   ✓ School found: " . $school->name . "\n";
    } else {
        echo "   ✗ School ID 3 not found\n";
    }
    
    // Test 3: Test all schools query
    echo "\n3. Testing School::all():\n";
    $schools = $schoolModel->all();
    echo "   ✓ Found " . count($schools) . " schools\n";
    
    // Test 4: Test contact types
    echo "\n4. Testing Contact static methods:\n";
    $types = \App\Models\Contact::getAvailableTypes();
    echo "   ✓ Found " . count($types) . " contact types\n";
    
    // Test 5: Test controller instantiation
    echo "\n5. Testing ContactController:\n";
    $_GET['school_id'] = '3';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['QUERY_STRING'] = 'school_id=3';
    
    $controller = new \App\Controllers\Admin\ContactController();
    echo "   ✓ ContactController instantiated\n";
    
    // Test 6: Try the create method
    echo "\n6. Testing create method:\n";
    ob_start();
    $result = $controller->create();
    ob_end_clean();
    
    if (strpos($result, 'Add New School Contact') !== false) {
        echo "   ✓ Create method works - form loaded successfully!\n";
    } elseif (strpos($result, 'Error') !== false) {
        echo "   ✗ Create method has errors\n";
        echo "   Error preview: " . substr($result, 0, 500) . "\n";
    } else {
        echo "   ? Unexpected response from create method\n";
        echo "   Response preview: " . substr($result, 0, 200) . "\n";
    }
    
    echo "\n=== TEST RESULTS ===\n";
    echo "If you see mostly ✓ marks above, the contact system should work!\n";
    echo "Now try accessing: http://localhost/GSCMS/dev-login-admin\n";
    echo "Then go to: http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}