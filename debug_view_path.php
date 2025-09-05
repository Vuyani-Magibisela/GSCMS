<?php
// debug_view_path.php - Debug view path and layout system

require_once 'app/bootstrap.php';

echo "=== VIEW PATH DEBUG ===\n";
echo "VIEW_PATH constant: " . (defined('VIEW_PATH') ? VIEW_PATH : 'NOT DEFINED') . "\n";

if (defined('VIEW_PATH')) {
    echo "Admin layout exists: " . (file_exists(VIEW_PATH . '/layouts/admin.php') ? 'YES' : 'NO') . "\n";
    echo "Admin layout path: " . VIEW_PATH . '/layouts/admin.php' . "\n";
    
    // Check if the layout file is readable
    if (file_exists(VIEW_PATH . '/layouts/admin.php')) {
        echo "Admin layout readable: " . (is_readable(VIEW_PATH . '/layouts/admin.php') ? 'YES' : 'NO') . "\n";
        echo "Admin layout size: " . filesize(VIEW_PATH . '/layouts/admin.php') . " bytes\n";
    }
}

// Check contact view
$contactViewPath = VIEW_PATH . '/admin/contacts/create.php';
echo "\nContact view exists: " . (file_exists($contactViewPath) ? 'YES' : 'NO') . "\n";
echo "Contact view path: $contactViewPath\n";

// Test direct include simulation
echo "\n=== TESTING DIRECT VIEW INCLUDE ===\n";
try {
    // Simulate view variables
    $title = 'Test Title';
    $pageTitle = 'Test Page Title';
    $pageSubtitle = 'Test Subtitle';
    $breadcrumbs = [];
    $school = null;
    $schools = [];
    $selectedSchoolId = 3;
    $contactTypes = ['principal' => 'Principal'];
    $statuses = ['active' => 'Active'];
    $communicationPreferences = ['email' => 'Email'];
    $languagePreferences = ['english' => 'English'];
    
    echo "Testing view rendering...\n";
    ob_start();
    include $contactViewPath;
    $viewContent = ob_get_clean();
    
    if (strpos($viewContent, 'Add New School Contact') !== false) {
        echo "✓ View rendered successfully!\n";
        echo "Content preview: " . substr(strip_tags($viewContent), 0, 100) . "...\n";
    } else {
        echo "✗ View rendering failed or unexpected content\n";
        echo "Content preview: " . substr($viewContent, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "✗ View rendering error: " . $e->getMessage() . "\n";
}