<?php
// database/console/drop_views.php
// Script to drop problematic MySQL views

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance();
    
    echo "Checking for and dropping problematic views...\n";
    
    $views = [
        'competition_overview',
        'participant_summary', 
        'team_summary'
    ];
    
    foreach ($views as $view) {
        try {
            // Check if view exists using INFORMATION_SCHEMA
            $result = $db->query("SELECT * FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_NAME = ?", [$view]);
            
            if (!empty($result)) {
                echo "Found view: $view - Dropping...\n";
                $db->query("DROP VIEW IF EXISTS `$view`");
                echo "✓ Dropped view: $view\n";
            } else {
                echo "✓ View $view does not exist\n";
            }
        } catch (Exception $e) {
            echo "Error with view $view: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nView cleanup completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}