<?php
// test_hardcoded_urls.php - Test hardcoded URL fixes

require_once 'app/bootstrap.php';

echo "Testing Hardcoded URL Fixes...\n\n";

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

// Set baseUrl to empty to test hardcoded paths
$baseUrl = '';

try {
    echo "1. Test setup (baseUrl intentionally empty to test hardcoded paths)\n";
    
    ob_start();
    include '/var/www/html/GSCMS/app/Views/admin/contacts/create.php';
    $viewContent = ob_get_clean();
    
    echo "2. Hardcoded URL verification:\n";
    
    // Check form action URL
    if (strpos($viewContent, 'action="/GSCMS/admin/contacts"') !== false) {
        echo "   ✅ Form action hardcoded correctly: /GSCMS/admin/contacts\n";
    } else {
        echo "   ❌ Form action not hardcoded correctly\n";
        preg_match('/action="([^"]*)"/', $viewContent, $matches);
        echo "      Found: " . ($matches[1] ?? 'none') . "\n";
    }
    
    // Check cancel button URL
    if (strpos($viewContent, 'href="/GSCMS/admin/contacts"') !== false) {
        echo "   ✅ Cancel button hardcoded correctly: /GSCMS/admin/contacts\n";
    } else {
        echo "   ❌ Cancel button not hardcoded correctly\n";
    }
    
    // Check school redirect URL
    if (strpos($viewContent, 'href="/GSCMS/admin/schools/3"') !== false) {
        echo "   ✅ School link hardcoded correctly: /GSCMS/admin/schools/3\n";
    } else {
        echo "   ❌ School link not hardcoded correctly\n";
    }
    
    // Check JavaScript redirect URL
    if (strpos($viewContent, "window.location.href = '/GSCMS/admin/contacts/create") !== false) {
        echo "   ✅ JavaScript redirect hardcoded correctly: /GSCMS/admin/contacts/create\n";
    } else {
        echo "   ❌ JavaScript redirect not hardcoded correctly\n";
    }
    
    // Check for any remaining baseUrl variables that might cause issues
    if (strpos($viewContent, '<?= $baseUrl ?>') !== false) {
        echo "   ⚠️  Warning: Found remaining baseUrl variables in view\n";
    } else {
        echo "   ✅ No remaining baseUrl variables found\n";
    }
    
    echo "\n=== HARDCODED URL TEST RESULTS ===\n";
    
    $allHardcoded = strpos($viewContent, 'action="/GSCMS/admin/contacts"') !== false &&
                    strpos($viewContent, 'href="/GSCMS/admin/contacts"') !== false &&
                    strpos($viewContent, 'href="/GSCMS/admin/schools/3"') !== false &&
                    strpos($viewContent, "window.location.href = '/GSCMS/admin/contacts/create") !== false;
    
    if ($allHardcoded) {
        echo "🎉 SUCCESS: All URLs are now hardcoded correctly!\n\n";
        echo "Fixed URLs (hardcoded):\n";
        echo "✅ Form submission: /GSCMS/admin/contacts (POST)\n";
        echo "✅ Cancel button: /GSCMS/admin/contacts (GET)\n";
        echo "✅ School link: /GSCMS/admin/schools/3 (GET)\n";
        echo "✅ JavaScript redirect: /GSCMS/admin/contacts/create?school_id=X (GET)\n\n";
        
        echo "🚀 The form should now work correctly!\n";
        echo "Test the contact creation at:\n";
        echo "http://localhost/GSCMS/admin/contacts/create?school_id=3\n\n";
        
        echo "The form will submit to the correct URL and should not give 404 errors anymore.\n";
        
    } else {
        echo "❌ Some URLs still not hardcoded properly\n";
        echo "Check the view file for any remaining dynamic URL generation\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}