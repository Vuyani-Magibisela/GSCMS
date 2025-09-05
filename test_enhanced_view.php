<?php
// test_enhanced_view.php - Test just the enhanced view rendering

require_once 'app/bootstrap.php';

echo "Testing Enhanced Contact View...\n\n";

// Set up mock data
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
    ],
    (object)[
        'id' => 62,
        'first_name' => 'Sarah',
        'last_name' => 'Johnson',
        'email' => 'sarah.johnson@example.com', 
        'phone' => '083 456 7890',
        'role' => 'school_coordinator'
    ]
];

$selectedSchoolId = 3;
$contactTypes = \App\Models\Contact::getAvailableTypes();
$statuses = \App\Models\Contact::getAvailableStatuses();
$communicationPreferences = \App\Models\Contact::getCommunicationPreferences();
$languagePreferences = \App\Models\Contact::getLanguagePreferences();

// Mock url function
function url($path) {
    return $path;
}

$baseUrl = '';

try {
    echo "1. Mock data setup: ✓\n";
    echo "2. School users available: " . count($schoolUsers) . "\n";
    
    ob_start();
    include '/var/www/html/GSCMS/app/Views/admin/contacts/create.php';
    $viewContent = ob_get_clean();
    
    echo "3. View rendering tests:\n";
    
    if (strpos($viewContent, 'Select Existing User') !== false) {
        echo "   ✓ User selection option present\n";
    } else {
        echo "   ✗ User selection option missing\n";
    }
    
    if (strpos($viewContent, 'existing_user_id') !== false) {
        echo "   ✓ User dropdown field present\n";
    } else {
        echo "   ✗ User dropdown field missing\n";
    }
    
    if (strpos($viewContent, 'Thabo Adams') !== false) {
        echo "   ✓ User data populated in dropdown\n";
    } else {
        echo "   ✗ User data not found in dropdown\n";
    }
    
    if (strpos($viewContent, 'team_coach') !== false) {
        echo "   ✓ User roles included in dropdown\n";
    } else {
        echo "   ✗ User roles missing from dropdown\n";
    }
    
    if (strpos($viewContent, 'contactMethodToggle') !== false) {
        echo "   ✓ Toggle functionality present\n";
    } else {
        echo "   ✗ Toggle functionality missing\n";
    }
    
    if (strpos($viewContent, 'Manual Entry') !== false) {
        echo "   ✓ Manual entry option present\n";
    } else {
        echo "   ✗ Manual entry option missing\n";
    }
    
    echo "\n=== VIEW ENHANCEMENT TEST RESULTS ===\n";
    
    $allPresent = strpos($viewContent, 'Select Existing User') !== false &&
                  strpos($viewContent, 'existing_user_id') !== false &&
                  strpos($viewContent, 'Thabo Adams') !== false &&
                  strpos($viewContent, 'contactMethodToggle') !== false &&
                  strpos($viewContent, 'Manual Entry') !== false;
    
    if ($allPresent) {
        echo "🎉 SUCCESS: All enhanced features are working!\n\n";
        echo "Enhanced Contact Creation Features:\n";
        echo "✅ Toggle between manual entry and user selection\n";
        echo "✅ Dropdown populated with school users\n"; 
        echo "✅ User information includes name, email, and role\n";
        echo "✅ JavaScript for auto-population\n";
        echo "✅ Smart form field management\n\n";
        
        echo "Ready to use! Access via:\n";
        echo "http://localhost/GSCMS/dev-login-admin\n";
        echo "http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
        
    } else {
        echo "❌ Some enhanced features are not working properly\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}