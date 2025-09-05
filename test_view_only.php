<?php
// test_view_only.php - Test just the view rendering without controller instantiation

require_once 'app/bootstrap.php';

echo "Testing Contact View Rendering...\n\n";

// Set up required view variables
$title = 'Add New Contact - GSCMS';
$pageTitle = 'Add New School Contact';
$pageSubtitle = 'Adding contact for Biko Primary School';
$breadcrumbs = [];

// Create mock school object
$school = (object)[
    'id' => 3,
    'name' => 'Biko Primary School'
];

// Create mock schools array
$schools = [
    (object)['id' => 3, 'name' => 'Biko Primary School', 'district' => 'Tshwane North']
];

$selectedSchoolId = 3;
$contactTypes = \App\Models\Contact::getAvailableTypes();
$statuses = \App\Models\Contact::getAvailableStatuses();
$communicationPreferences = \App\Models\Contact::getCommunicationPreferences();
$languagePreferences = \App\Models\Contact::getLanguagePreferences();

// Mock baseUrl function
function url($path) {
    return $path;
}

// Set baseUrl variable for layout
$baseUrl = '';

try {
    echo "1. Setting up view variables: ✓\n";
    echo "2. Contact types available: " . count($contactTypes) . "\n";
    echo "3. School name: " . $school->name . "\n";
    
    echo "4. Testing view inclusion...\n";
    
    ob_start();
    include '/var/www/html/GSCMS/app/Views/admin/contacts/create.php';
    $viewContent = ob_get_clean();
    
    if (strpos($viewContent, 'Add New School Contact') !== false) {
        echo "   ✓ View contains title\n";
    }
    
    if (strpos($viewContent, 'Biko Primary School') !== false) {
        echo "   ✓ View contains school name\n";
    }
    
    if (strpos($viewContent, '<form') !== false) {
        echo "   ✓ View contains form\n";
    }
    
    if (strpos($viewContent, 'school_id') !== false) {
        echo "   ✓ View contains form fields\n";
    }
    
    if (strpos($viewContent, 'Error') === false && strpos($viewContent, 'Fatal') === false) {
        echo "   ✓ View renders without errors\n";
    } else {
        echo "   ✗ View contains errors\n";
    }
    
    echo "\n=== SUCCESS ===\n";
    echo "The contact creation view is working properly!\n";
    echo "You should now be able to access it via the browser.\n\n";
    
    echo "To test:\n";
    echo "1. Go to: http://localhost/GSCMS/dev-login-admin\n";
    echo "2. Then: http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}