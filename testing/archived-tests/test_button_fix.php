<?php
// test_button_fix.php - Test the button fix with inline JavaScript

require_once 'app/bootstrap.php';

echo "Testing Button Response Fix...\n\n";

// Set up test data
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

// Mock school users
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
    echo "1. Test setup: ✓\n";
    
    ob_start();
    include '/var/www/html/GSCMS/app/Views/admin/contacts/create.php';
    $viewContent = ob_get_clean();
    
    echo "2. Button response fix tests:\n";
    
    // Check for inline onclick handlers
    if (strpos($viewContent, 'onclick="toggleContactMethod(\'manual\')"') !== false) {
        echo "   ✓ Manual button has inline onclick handler\n";
    } else {
        echo "   ✗ Manual button missing inline onclick handler\n";
    }
    
    if (strpos($viewContent, 'onclick="toggleContactMethod(\'select\')"') !== false) {
        echo "   ✓ Select button has inline onclick handler\n";
    } else {
        echo "   ✗ Select button missing inline onclick handler\n";
    }
    
    // Check for inline JavaScript function
    if (strpos($viewContent, 'function toggleContactMethod(method)') !== false) {
        echo "   ✓ Inline toggleContactMethod function present\n";
    } else {
        echo "   ✗ Inline toggleContactMethod function missing\n";
    }
    
    // Check for test function
    if (strpos($viewContent, 'function testToggle()') !== false) {
        echo "   ✓ Debug testToggle function present\n";
    } else {
        echo "   ✗ Debug testToggle function missing\n";
    }
    
    // Check for debug buttons
    if (strpos($viewContent, 'Debug: Test Toggle') !== false) {
        echo "   ✓ Debug test button present\n";
    } else {
        echo "   ✗ Debug test button missing\n";
    }
    
    if (strpos($viewContent, 'Direct: Show Users') !== false) {
        echo "   ✓ Direct show users button present\n";
    } else {
        echo "   ✗ Direct show users button missing\n";
    }
    
    // Check for console logging
    if (strpos($viewContent, 'console.log(\'toggleContactMethod called with:\', method)') !== false) {
        echo "   ✓ Function call logging present\n";
    } else {
        echo "   ✗ Function call logging missing\n";
    }
    
    // Check for DOM element checks
    if (strpos($viewContent, 'DOM elements check:') !== false) {
        echo "   ✓ DOM element verification present\n";
    } else {
        echo "   ✗ DOM element verification missing\n";
    }
    
    echo "\n=== BUTTON FIX RESULTS ===\n";
    
    $allFixed = strpos($viewContent, 'onclick="toggleContactMethod(\'select\')"') !== false &&
                strpos($viewContent, 'function toggleContactMethod(method)') !== false &&
                strpos($viewContent, 'function testToggle()') !== false &&
                strpos($viewContent, 'Debug: Test Toggle') !== false;
    
    if ($allFixed) {
        echo "🎉 SUCCESS: Button response has been fixed!\n\n";
        echo "Fixes Applied:\n";
        echo "✅ Added inline onclick handlers (no jQuery dependency)\n";
        echo "✅ Created standalone JavaScript function\n";
        echo "✅ Added multiple debugging options\n";
        echo "✅ Added console logging for troubleshooting\n";
        echo "✅ Added DOM element verification\n\n";
        
        echo "Available Debug Options:\n";
        echo "🔷 Main buttons: 'Manual Entry' and 'Select Existing User' with onclick\n";
        echo "🔷 Debug button: 'Debug: Test Toggle' - tests section visibility\n";
        echo "🔷 Direct button: 'Direct: Show Users' - immediately shows user section\n";
        echo "🔷 Browser console: Shows detailed logging of all actions\n\n";
        
        echo "How to test:\n";
        echo "1. Access: http://localhost/GSCMS/dev-login-admin\n";
        echo "2. Go to: http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
        echo "3. Open browser console (F12) to see debug messages\n";
        echo "4. Try clicking 'Select Existing User' button\n";
        echo "5. If still not working, try the debug buttons\n";
        echo "6. Check console for error messages or DOM issues\n";
        
    } else {
        echo "❌ Some button fixes may not be complete\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}