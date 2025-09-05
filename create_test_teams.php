<?php
// create_test_teams.php - Script to create test teams for system testing

require_once 'app/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Creating test teams...\n\n";
    
    // Sample teams to create
    $testTeams = [
        [
            'school_id' => 1,
            'category_id' => 1,
            'team_name' => 'Luthuli Robotics Alpha',
            'team_code' => 'LUT-ROB-001',
            'coach_primary_id' => 57,
            'coach_secondary_id' => 78,
            'participant_count' => 4,
            'min_participants' => 3,
            'max_participants' => 5,
            'registration_status' => 'draft'
        ],
        [
            'school_id' => 2,
            'category_id' => 2,
            'team_name' => 'Luthuli Explorers Beta',
            'team_code' => 'LUT-EXP-001',
            'coach_primary_id' => 58,
            'coach_secondary_id' => 58,
            'participant_count' => 3,
            'min_participants' => 3,
            'max_participants' => 4,
            'registration_status' => 'draft'
        ],
        [
            'school_id' => 3,
            'category_id' => 1,
            'team_name' => 'Biko Tech Warriors',
            'team_code' => 'BIK-TEC-001',
            'coach_primary_id' => 61,
            'coach_secondary_id' => 61,
            'participant_count' => 4,
            'min_participants' => 3,
            'max_participants' => 5,
            'registration_status' => 'submitted'
        ],
        [
            'school_id' => 4,
            'category_id' => 3,
            'team_name' => 'Mandela Innovation Squad',
            'team_code' => 'MAN-INN-001',
            'coach_primary_id' => 62,
            'coach_secondary_id' => 62,
            'participant_count' => 3,
            'min_participants' => 3,
            'max_participants' => 4,
            'registration_status' => 'approved'
        ],
        [
            'school_id' => 5,
            'category_id' => 4,
            'team_name' => 'Sisulu Hardware Heroes',
            'team_code' => 'SIS-HAR-001',
            'coach_primary_id' => 65,
            'coach_secondary_id' => 65,
            'participant_count' => 4,
            'min_participants' => 3,
            'max_participants' => 5,
            'registration_status' => 'draft'
        ]
    ];
    
    $createdTeams = [];
    
    foreach ($testTeams as $team) {
        // Check if team_registrations table exists, if not use teams table
        $tableExists = $pdo->query("SHOW TABLES LIKE 'team_registrations'")->fetch();
        
        if ($tableExists) {
            // Use team_registrations table
            $sql = "INSERT INTO team_registrations 
                    (school_id, category_id, team_name, team_code, coach_primary_id, coach_secondary_id, 
                     participant_count, min_participants, max_participants, registration_status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $team['school_id'],
                $team['category_id'],
                $team['team_name'],
                $team['team_code'],
                $team['coach_primary_id'],
                $team['coach_secondary_id'],
                $team['participant_count'],
                $team['min_participants'],
                $team['max_participants'],
                $team['registration_status']
            ]);
            
        } else {
            // Fallback to teams table
            $sql = "INSERT INTO teams 
                    (school_id, category_id, name, registration_code, coach_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $team['school_id'],
                $team['category_id'],
                $team['team_name'],
                $team['team_code'],
                $team['coach_primary_id']
            ]);
        }
        
        $teamId = $pdo->lastInsertId();
        
        $createdTeams[] = [
            'id' => $teamId,
            'name' => $team['team_name'],
            'school_id' => $team['school_id'],
            'category_id' => $team['category_id'],
            'primary_coach_id' => $team['coach_primary_id'],
            'secondary_coach_id' => $team['coach_secondary_id'],
            'status' => $team['registration_status']
        ];
        
        echo "✓ Created team: {$team['team_name']} (ID: $teamId) for School ID: {$team['school_id']}\n";
        echo "  Primary Coach ID: {$team['coach_primary_id']}, Secondary Coach ID: {$team['coach_secondary_id']}\n";
        echo "  Category: {$team['category_id']}, Status: {$team['registration_status']}\n\n";
    }
    
    echo "=== TEAM CREATION SUMMARY ===\n";
    echo count($createdTeams) . " test teams successfully created!\n\n";
    
    echo "=== TEAMS BY SCHOOL ===\n";
    foreach ($createdTeams as $team) {
        echo "Team: {$team['name']} (ID: {$team['id']})\n";
        echo "  School ID: {$team['school_id']}\n";
        echo "  Category ID: {$team['category_id']}\n";
        echo "  Primary Coach ID: {$team['primary_coach_id']}\n";
        echo "  Secondary Coach ID: {$team['secondary_coach_id']}\n";
        echo "  Status: {$team['status']}\n\n";
    }
    
    // Get school and category names for better display
    echo "=== DETAILED TEAM INFORMATION ===\n";
    foreach ($createdTeams as $team) {
        $schoolStmt = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
        $schoolStmt->execute([$team['school_id']]);
        $school = $schoolStmt->fetch();
        
        $categoryStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $categoryStmt->execute([$team['category_id']]);
        $category = $categoryStmt->fetch();
        
        $coachStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $coachStmt->execute([$team['primary_coach_id']]);
        $coach = $coachStmt->fetch();
        
        echo "Team: {$team['name']}\n";
        echo "  School: " . ($school['name'] ?? 'Unknown') . "\n";
        echo "  Category: " . ($category['name'] ?? 'Unknown') . "\n";
        echo "  Coach: " . ($coach ? $coach['first_name'] . ' ' . $coach['last_name'] : 'Unknown') . "\n";
        echo "  Status: {$team['status']}\n\n";
    }
    
    echo "=== TESTING TIPS ===\n";
    echo "1. You can now test team editing by going to the team management section\n";
    echo "2. Try adding participants to these teams\n";
    echo "3. Test status changes (draft -> submitted -> approved)\n";
    echo "4. Test the coach assignment functionality\n";
    echo "5. Try bulk operations on multiple teams\n\n";
    
    echo "Happy testing! 🚀\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}