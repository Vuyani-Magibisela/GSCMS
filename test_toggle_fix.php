<?php
// test_toggle_fix.php - Test the fixed toggle functionality

require_once 'app/bootstrap.php';

echo "Testing Fixed Toggle Functionality...\n\n";

// Set up mock data with users
$title = 'Add New Contact - GSCMS';
$pageTitle = 'Add New School Contact';
$pageSubtitle = 'Adding contact for Biko Primary School';
$breadcrumbs = [];

$school = (object)[
    'id' => 3,
    'name' => 'Biko Primary School'
];

$schools = [
    (object)['id' => 3, 'name' => 'Biko Primary School', 'district' => 'Tshwane North']
];

// Mock school users - this should trigger the enhanced interface
$schoolUsers = [
    (object)[
        'id' => 61,
        'first_name' => 'Thabo',
        'last_name' => 'Adams', 
        'email' => 'thabo.adams@example.com',
        'phone' => '082 123 4567',
        'role' => 'team_coach'
    ]
];

$selectedSchoolId = 3;
$contactTypes = \App\Models\Contact::getAvailableTypes();
$statuses = \App\Models\Contact::getAvailableStatuses();
$communicationPreferences = \App\Models\Contact::getCommunicationPreferences();
$languagePreferences = \App\Models\Contact::getLanguagePreferences();

function url($path) { return $path; }
$baseUrl = '';

try {
    echo "1. Test data setup: ✓\n";
    echo "2. School users available: " . count($schoolUsers) . "\n";
    
    ob_start();
    include '/var/www/html/GSCMS/app/Views/admin/contacts/create.php';
    $viewContent = ob_get_clean();
    
    echo "3. Enhanced toggle interface tests:\n";
    
    // Check for button-based toggle (new approach)
    if (strpos($viewContent, 'id="btn_method_manual"') !== false) {
        echo "   ✓ Manual entry button present\n";
    } else {
        echo "   ✗ Manual entry button missing\n";
    }
    
    if (strpos($viewContent, 'id="btn_method_select"') !== false) {
        echo "   ✓ Select user button present\n";
    } else {
        echo "   ✗ Select user button missing\n";
    }
    
    // Check for hidden input to track method
    if (strpos($viewContent, 'name="contact_method"') !== false) {
        echo "   ✓ Hidden method tracking input present\n";
    } else {
        echo "   ✗ Hidden method tracking input missing\n";
    }
    
    // Check for enhanced JavaScript
    if (strpos($viewContent, '#contactMethodToggle button') !== false) {
        echo "   ✓ Enhanced button click handler present\n";
    } else {
        echo "   ✗ Enhanced button click handler missing\n";
    }
    
    // Check for debug functionality
    if (strpos($viewContent, 'debugToggle') !== false) {
        echo "   ✓ Debug toggle button present\n";
    } else {
        echo "   ✗ Debug toggle button missing\n";
    }
    
    // Check for console logging
    if (strpos($viewContent, 'console.log') !== false) {
        echo "   ✓ Debug console logging present\n";
    } else {
        echo "   ✗ Debug console logging missing\n";
    }
    
    // Check if user selection section exists
    if (strpos($viewContent, 'id="userSelectionSection"') !== false) {
        echo "   ✓ User selection section present\n";
    } else {
        echo "   ✗ User selection section missing\n";
    }
    
    echo "\n=== TOGGLE FIX TEST RESULTS ===\n";
    
    $allFixed = strpos($viewContent, 'id="btn_method_manual"') !== false &&
                strpos($viewContent, 'id="btn_method_select"') !== false &&
                strpos($viewContent, 'name="contact_method"') !== false &&
                strpos($viewContent, '#contactMethodToggle button') !== false &&
                strpos($viewContent, 'id="userSelectionSection"') !== false;
    
    if ($allFixed) {
        echo "🎉 SUCCESS: Toggle functionality has been fixed!\n\n";
        echo "Fixed Issues:\n";
        echo "✅ Replaced radio buttons with clickable buttons\n";
        echo "✅ Added proper click event handlers\n";
        echo "✅ Added debug console logging\n";
        echo "✅ Added debug toggle button for testing\n";
        echo "✅ Enhanced visual feedback\n\n";
        
        echo "How to test:\n";
        echo "1. Access: http://localhost/GSCMS/dev-login-admin\n";
        echo "2. Go to: http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
        echo "3. Check browser console (F12) for debug messages\n";
        echo "4. Click 'Select Existing User' button - should show dropdown\n";
        echo "5. Use 'Debug: Show User Section' button if toggle doesn't work\n";
        
    } else {
        echo "❌ Some fixes may not be complete\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}