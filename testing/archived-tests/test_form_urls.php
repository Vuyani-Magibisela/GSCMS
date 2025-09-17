<?php
// test_form_urls.php - Test the fixed form URLs

require_once 'app/bootstrap.php';

echo "Testing Fixed Form URLs...\n\n";

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

// Simulate the baseUrl that would be provided by the controller
$baseUrl = '/GSCMS';

try {
    echo "1. Test setup with baseUrl: $baseUrl\n";
    
    ob_start();
    include '/var/www/html/GSCMS/app/Views/admin/contacts/create.php';
    $viewContent = ob_get_clean();
    
    echo "2. URL fix verification:\n";
    
    // Check form action URL
    if (strpos($viewContent, 'action="/GSCMS/admin/contacts"') !== false) {
        echo "   ✅ Form action URL fixed: /GSCMS/admin/contacts\n";
    } elseif (strpos($viewContent, 'action="/admin/contacts"') !== false) {
        echo "   ❌ Form action URL still hardcoded: /admin/contacts\n";
    } else {
        echo "   ⚠️  Form action URL pattern not found\n";
    }
    
    // Check cancel button URL
    if (strpos($viewContent, 'href="/GSCMS/admin/contacts"') !== false) {
        echo "   ✅ Cancel button URL fixed: /GSCMS/admin/contacts\n";
    } elseif (strpos($viewContent, 'href="/admin/contacts"') !== false) {
        echo "   ❌ Cancel button URL still hardcoded: /admin/contacts\n";
    } else {
        echo "   ⚠️  Cancel button URL pattern not found\n";
    }
    
    // Check school redirect URL
    if (strpos($viewContent, 'href="/GSCMS/admin/schools/3"') !== false) {
        echo "   ✅ School link URL fixed: /GSCMS/admin/schools/3\n";
    } elseif (strpos($viewContent, 'href="/admin/schools/3"') !== false) {
        echo "   ❌ School link URL still hardcoded: /admin/schools/3\n";
    } else {
        echo "   ⚠️  School link URL pattern not found\n";
    }
    
    // Check JavaScript redirect URL
    if (strpos($viewContent, "window.location.href = '/GSCMS/admin/contacts/create") !== false) {
        echo "   ✅ JavaScript redirect URL fixed: /GSCMS/admin/contacts/create\n";
    } elseif (strpos($viewContent, "window.location.href = '/admin/contacts/create") !== false) {
        echo "   ❌ JavaScript redirect URL still hardcoded: /admin/contacts/create\n";
    } else {
        echo "   ⚠️  JavaScript redirect URL pattern not found\n";
    }
    
    echo "\n=== URL FIX RESULTS ===\n";
    
    $allFixed = strpos($viewContent, 'action="/GSCMS/admin/contacts"') !== false &&
                strpos($viewContent, 'href="/GSCMS/admin/contacts"') !== false &&
                strpos($viewContent, 'href="/GSCMS/admin/schools/3"') !== false &&
                strpos($viewContent, "window.location.href = '/GSCMS/admin/contacts/create") !== false;
    
    if ($allFixed) {
        echo "🎉 SUCCESS: All URLs have been fixed!\n\n";
        echo "Fixed URLs:\n";
        echo "✅ Form submission: /GSCMS/admin/contacts (POST)\n";
        echo "✅ Cancel button: /GSCMS/admin/contacts (GET)\n";
        echo "✅ School link: /GSCMS/admin/schools/{id} (GET)\n";
        echo "✅ JavaScript redirect: /GSCMS/admin/contacts/create (GET)\n\n";
        
        echo "The form should now submit correctly!\n";
        echo "Test by creating a contact at:\n";
        echo "http://localhost/GSCMS/admin/contacts/create?school_id=3\n";
        
    } else {
        echo "❌ Some URLs may not be fixed properly\n";
        
        // Show what we found for debugging
        if (strpos($viewContent, 'action=') !== false) {
            preg_match('/action="([^"]*)"/', $viewContent, $matches);
            echo "Form action found: " . ($matches[1] ?? 'none') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}