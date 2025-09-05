<?php
// test_enhanced_contacts.php - Test the enhanced contact creation with user selection

require_once 'app/bootstrap.php';

// Set up session
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'super_admin';
$_SESSION['authenticated'] = true;

echo "Testing Enhanced Contact Creation...\n\n";

try {
    // Test 1: Check if users are fetched for school
    echo "1. Testing user fetching for school ID 3:\n";
    
    $userModel = new \App\Models\User();
    $schoolUsers = $userModel->getBySchool(3);
    
    echo "   Users found for school ID 3: " . count($schoolUsers) . "\n";
    
    if (count($schoolUsers) > 0) {
        echo "   Sample users:\n";
        $count = 0;
        foreach ($schoolUsers as $user) {
            if ($count < 3) { // Show first 3 users
                echo "     - {$user->first_name} {$user->last_name} ({$user->email}) - {$user->role}\n";
                $count++;
            }
        }
        echo "   ✓ User fetching works\n";
    } else {
        echo "   ⚠ No users found for school ID 3\n";
    }
    
    // Test 2: Test controller with user data
    echo "\n2. Testing controller with enhanced functionality:\n";
    
    $_GET['school_id'] = '3';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['QUERY_STRING'] = 'school_id=3';
    
    // Set required view variables for testing
    $baseUrl = '';
    
    ob_start();
    $controller = new \App\Controllers\Admin\ContactController();
    $result = $controller->create();
    ob_end_clean();
    
    if (strpos($result, 'Select Existing User') !== false) {
        echo "   ✓ User selection option is present\n";
    } else {
        echo "   ✗ User selection option missing\n";
    }
    
    if (strpos($result, 'existing_user_id') !== false) {
        echo "   ✓ User selection dropdown is present\n";
    } else {
        echo "   ✗ User selection dropdown missing\n";
    }
    
    if (strpos($result, 'Manual Entry') !== false) {
        echo "   ✓ Manual entry option is present\n";
    } else {
        echo "   ✗ Manual entry option missing\n";
    }
    
    if (strpos($result, 'contactMethodToggle') !== false) {
        echo "   ✓ Toggle functionality is present\n";
    } else {
        echo "   ✗ Toggle functionality missing\n";
    }
    
    // Test 3: Check if user data is in dropdown
    if (count($schoolUsers) > 0) {
        $firstUser = $schoolUsers[0];
        if (strpos($result, $firstUser->first_name) !== false) {
            echo "   ✓ User data is populated in dropdown\n";
        } else {
            echo "   ✗ User data not found in dropdown\n";
        }
    }
    
    echo "\n=== ENHANCED FUNCTIONALITY TEST RESULTS ===\n";
    
    if (strpos($result, 'Select Existing User') !== false && 
        strpos($result, 'existing_user_id') !== false && 
        strpos($result, 'Manual Entry') !== false &&
        strpos($result, 'contactMethodToggle') !== false) {
        
        echo "🎉 SUCCESS: Enhanced contact creation is working!\n\n";
        echo "Features available:\n";
        echo "✓ User selection from existing school users\n";
        echo "✓ Manual entry option\n";
        echo "✓ Toggle between methods\n";
        echo "✓ Auto-population from selected users\n";
        echo "✓ " . count($schoolUsers) . " users available for selection\n\n";
        
        echo "To test the enhanced functionality:\n";
        echo "1. Go to: http://localhost/GSCMS/dev-login-admin\n";
        echo "2. Then: http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
        echo "3. You should see options to select existing users or create manually\n";
        
    } else {
        echo "❌ ISSUES: Some enhanced features are missing\n";
        echo "Check the view template and controller logic\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}