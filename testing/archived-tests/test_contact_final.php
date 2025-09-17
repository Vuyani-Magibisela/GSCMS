<?php
// test_contact_final.php - Final test of contact creation functionality

require_once 'app/bootstrap.php';

// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'super_admin';
$_SESSION['authenticated'] = true;

// Set up request simulation
$_GET['school_id'] = '3';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = 'school_id=3';
$_SERVER['REQUEST_URI'] = '/admin/contacts/create?school_id=3';

try {
    echo "Testing Contact Creation Final Fix...\n\n";
    
    // Test the controller create method
    $controller = new \App\Controllers\Admin\ContactController();
    
    echo "1. Testing controller instantiation: ✓\n";
    
    // Capture the output
    ob_start();
    $result = $controller->create();
    $output = ob_get_clean();
    
    if (is_string($result)) {
        echo "2. Method returned string content: ✓\n";
        
        if (strpos($result, 'Add New School Contact') !== false) {
            echo "3. Content contains expected title: ✓\n";
        } else {
            echo "3. Content missing expected title: ✗\n";
            echo "   Title search result: " . (strpos($result, 'Add New School Contact') !== false ? 'FOUND' : 'NOT FOUND') . "\n";
        }
        
        if (strpos($result, 'Biko Primary School') !== false) {
            echo "4. Content contains school name: ✓\n";
        } else {
            echo "4. Content missing school name: ✗\n";
        }
        
        if (strpos($result, '<form') !== false) {
            echo "5. Content contains form: ✓\n";
        } else {
            echo "5. Content missing form: ✗\n";
        }
        
        if (strpos($result, 'Error') !== false || strpos($result, 'Fatal') !== false) {
            echo "6. Content contains errors: ✗\n";
            echo "   Error preview: " . substr($result, strpos($result, 'Error'), 200) . "\n";
        } else {
            echo "6. No visible errors in content: ✓\n";
        }
        
        echo "\n=== CONTENT PREVIEW ===\n";
        echo substr(strip_tags($result), 0, 300) . "...\n\n";
        
        if (strpos($result, 'Add New School Contact') !== false && 
            strpos($result, 'Biko Primary School') !== false && 
            strpos($result, '<form') !== false) {
            echo "🎉 SUCCESS: Contact creation form is working properly!\n";
            echo "You can now access: http://localhost/GSCMS/dev-login-admin\n";
            echo "Then go to: http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
        } else {
            echo "❌ ISSUES: Contact form has some problems that need fixing.\n";
        }
        
    } else {
        echo "2. Method did not return string content: ✗\n";
        echo "   Return type: " . gettype($result) . "\n";
        echo "   Return value: " . print_r($result, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}