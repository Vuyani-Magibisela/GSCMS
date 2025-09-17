<?php
// assign_coaches_for_testing.php - Script to assign coaches to schools for testing

require_once 'app/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get coaches without schools
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'team_coach' AND school_id IS NULL");
    $stmt->execute();
    $coaches = $stmt->fetchAll();
    
    // Get available schools
    $stmt = $pdo->prepare("SELECT id, name FROM schools ORDER BY id LIMIT 10");
    $stmt->execute();
    $schools = $stmt->fetchAll();
    
    if (empty($coaches)) {
        echo "No coaches found without school assignment.\n";
        exit;
    }
    
    if (empty($schools)) {
        echo "No schools found in the system.\n";
        exit;
    }
    
    echo "Assigning coaches to schools for testing...\n\n";
    
    $assignments = [];
    $schoolIndex = 0;
    
    // Assign each coach to a school (cycling through schools if more coaches than schools)
    foreach ($coaches as $coach) {
        $school = $schools[$schoolIndex % count($schools)];
        
        // Update coach with school assignment
        $stmt = $pdo->prepare("UPDATE users SET school_id = ? WHERE id = ?");
        $stmt->execute([$school['id'], $coach['id']]);
        
        $assignments[] = [
            'coach_id' => $coach['id'],
            'coach_name' => $coach['first_name'] . ' ' . $coach['last_name'],
            'school_id' => $school['id'],
            'school_name' => $school['name']
        ];
        
        echo "✓ Assigned {$coach['first_name']} {$coach['last_name']} (ID: {$coach['id']}) to {$school['name']} (ID: {$school['id']})\n";
        
        $schoolIndex++;
    }
    
    echo "\n" . count($assignments) . " coaches successfully assigned!\n\n";
    
    // Display summary for easy reference
    echo "=== COACHING ASSIGNMENTS SUMMARY ===\n";
    echo "Format: Coach Name (Coach ID) -> School Name (School ID)\n\n";
    
    foreach ($assignments as $assignment) {
        echo "{$assignment['coach_name']} (ID: {$assignment['coach_id']}) -> {$assignment['school_name']} (ID: {$assignment['school_id']})\n";
    }
    
    echo "\n=== FOR TEAM CREATION TESTING ===\n";
    echo "You can now use these coach IDs as primary_coach_id and secondary_coach_id when creating teams:\n\n";
    
    // Group coaches by school for easier team creation
    $coachesBySchool = [];
    foreach ($assignments as $assignment) {
        $coachesBySchool[$assignment['school_id']][] = $assignment;
    }
    
    foreach ($coachesBySchool as $schoolId => $schoolCoaches) {
        if (count($schoolCoaches) >= 2) {
            $primary = $schoolCoaches[0];
            $secondary = $schoolCoaches[1];
            echo "School: {$primary['school_name']} (ID: $schoolId)\n";
            echo "  Primary Coach: {$primary['coach_name']} (ID: {$primary['coach_id']})\n";
            echo "  Secondary Coach: {$secondary['coach_name']} (ID: {$secondary['coach_id']})\n\n";
        } else {
            $coach = $schoolCoaches[0];
            echo "School: {$coach['school_name']} (ID: $schoolId)\n";
            echo "  Coach: {$coach['coach_name']} (ID: {$coach['coach_id']}) [Use as both primary and secondary]\n\n";
        }
    }
    
    echo "=== QUICK TEAM CREATION REFERENCE ===\n";
    echo "Sample team creation data:\n";
    
    $sampleTeam = $assignments[0];
    $sampleSchool = $schools[0];
    
    echo "{\n";
    echo "  'school_id': {$sampleSchool['id']},\n";
    echo "  'category_id': 1, // Junior Robotics\n";
    echo "  'team_name': 'Test Team Alpha',\n";
    echo "  'coach_primary_id': {$sampleTeam['coach_id']},\n";
    echo "  'coach_secondary_id': {$sampleTeam['coach_id']}, // Same coach for testing\n";
    echo "  'participant_count': 3,\n";
    echo "  'registration_status': 'draft'\n";
    echo "}\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}