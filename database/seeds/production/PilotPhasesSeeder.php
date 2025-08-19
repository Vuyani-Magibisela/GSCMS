<?php

/**
 * Pilot Programme Phases Seeder
 * Seeds the phases required for the GDE SciBOTICS Pilot Programme 2025
 * Phase 1: School Elimination, Phase 3: SciBOTICS Final (Phase 2 skipped)
 */

class PilotPhasesSeeder extends Seeder
{
    
    public function run()
    {
        echo "Seeding Pilot Programme Phases...\n";
        
        $pilotPhases = [
            [
                'name' => 'Phase 1 - Elimination @ Schools',
                'description' => 'School-based competitions and team selection for pilot programme',
                'phase_number' => 1,
                'start_date' => '2025-09-12',
                'end_date' => '2025-09-12',
                'registration_deadline' => '2025-09-05',
                'max_teams_per_category' => 30, // 30 teams per category
                'location_type' => 'school_based',
                'status' => 'active',
                'requirements' => 'All registered teams participate. Top 6 teams per category advance to Phase 3 (Phase 2 skipped for pilot).'
            ],
            [
                'name' => 'SciBOTICS Final @ Sci-Bono',
                'description' => 'Provincial finals at Sci-Bono Discovery Centre - Grand finale of pilot programme',
                'phase_number' => 3,
                'start_date' => '2025-09-27',
                'end_date' => '2025-09-27',
                'registration_deadline' => '2025-09-25',
                'max_teams_per_category' => 6, // 6 teams per category
                'location_type' => 'provincial',
                'status' => 'upcoming', // Will be activated closer to the event
                'requirements' => 'Grand finale with 54 teams (6 per category × 9 categories) = 216 participants. Awards ceremony included.'
            ]
        ];
        
        $seededCount = 0;
        
        foreach ($pilotPhases as $phase) {
            try {
                // Check if phase already exists
                $existing = $this->db->prepare("SELECT * FROM phases WHERE phase_number = ?");
                $existing->execute([$phase['phase_number']]);
                $existingPhase = $existing->fetch(PDO::FETCH_ASSOC);
                
                if ($existingPhase) {
                    // Update existing phase
                    $updateFields = [];
                    $updateValues = [];
                    foreach ($phase as $key => $value) {
                        $updateFields[] = "{$key} = ?";
                        $updateValues[] = $value;
                    }
                    $updateValues[] = date('Y-m-d H:i:s');
                    $updateValues[] = $phase['phase_number'];
                    
                    $updateSql = "UPDATE phases SET " . implode(', ', $updateFields) . ", updated_at = ? WHERE phase_number = ?";
                    $stmt = $this->db->prepare($updateSql);
                    $stmt->execute($updateValues);
                    echo "  Updated phase: {$phase['name']}\n";
                } else {
                    // Create new phase
                    $fields = array_keys($phase);
                    $placeholders = array_fill(0, count($fields), '?');
                    
                    $sql = "INSERT INTO phases (" . implode(', ', $fields) . ", created_at, updated_at) VALUES (" . implode(', ', $placeholders) . ", ?, ?)";
                    $values = array_values($phase);
                    $values[] = date('Y-m-d H:i:s');
                    $values[] = date('Y-m-d H:i:s');
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($values);
                    echo "  Created phase: {$phase['name']}\n";
                }
                
                $seededCount++;
                
            } catch (Exception $e) {
                echo "  Error seeding phase {$phase['name']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Successfully seeded {$seededCount} pilot programme phases.\n";
        
        // Validate the seeded phases
        $this->validatePilotPhases();
    }
    
    /**
     * Set pilot competition configuration in settings
     */
    private function setPilotConfiguration()
    {
        echo "\nConfiguring pilot programme settings...\n";
        
        $pilotSettings = [
            'competition_type' => 'pilot',
            'pilot_active_phases' => json_encode([1, 3]), // Only phases 1 and 3
            'pilot_skipped_phases' => json_encode([2]), // Phase 2 is skipped
            'pilot_timeline' => json_encode([
                'registration_open' => '2025-08-01',
                'registration_close' => '2025-09-05',
                'phase_1_competitions' => '2025-09-12',
                'phase_1_results_submission' => '2025-09-15',
                'qualification_announcement' => '2025-09-18',
                'phase_3_finals' => '2025-09-27',
                'awards_ceremony' => '2025-09-27',
                'post_competition_analysis' => '2025-10-15'
            ]),
            'pilot_capacity_phase_1' => 270, // 30 teams × 9 categories
            'pilot_capacity_phase_3' => 54,  // 6 teams × 9 categories
            'pilot_expected_participants' => 216, // 54 teams × 4 members
            'pilot_medal_count' => 108, // 3 teams × 9 categories × 4 members
            'pilot_trophy_count' => 9   // 1 per category
        ];
        
        foreach ($pilotSettings as $key => $value) {
            try {
                // Check if setting exists
                $existing = $this->db->prepare("SELECT * FROM settings WHERE `key` = ?");
                $existing->execute([$key]);
                $existingSetting = $existing->fetch(PDO::FETCH_ASSOC);
                
                if ($existingSetting) {
                    // Update existing setting
                    $stmt = $this->db->prepare("UPDATE settings SET value = ?, updated_at = ? WHERE `key` = ?");
                    $stmt->execute([$value, date('Y-m-d H:i:s'), $key]);
                } else {
                    // Create new setting
                    $stmt = $this->db->prepare("INSERT INTO settings (`key`, value, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $key,
                        $value,
                        "Pilot programme setting: {$key}",
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s')
                    ]);
                }
                echo "  Set setting: {$key}\n";
            } catch (Exception $e) {
                echo "  Error setting {$key}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Validate that pilot phases are properly configured
     */
    private function validatePilotPhases()
    {
        echo "\nValidating pilot programme phase configuration...\n";
        
        // Check that we have phases 1 and 3
        $requiredPhases = [1, 3];
        
        $validationPassed = true;
        
        foreach ($requiredPhases as $phaseNumber) {
            $stmt = $this->db->prepare("
                SELECT * FROM phases 
                WHERE phase_number = ?
                AND deleted_at IS NULL
            ");
            $stmt->execute([$phaseNumber]);
            $phase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($phase) {
                echo "  ✓ Phase {$phaseNumber}: {$phase['name']} - Status: {$phase['status']}\n";
            } else {
                echo "  ✗ Missing Phase {$phaseNumber}\n";
                $validationPassed = false;
            }
        }
        
        // Check that Phase 2 is not active for pilot
        $stmt = $this->db->prepare("
            SELECT * FROM phases 
            WHERE phase_number = 2 
            AND status = 'active'
            AND deleted_at IS NULL
        ");
        $stmt->execute();
        $phase2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($phase2)) {
            echo "  ✓ Phase 2 is properly disabled for pilot programme\n";
        } else {
            echo "  ⚠ Warning: Phase 2 is active but should be disabled for pilot programme\n";
        }
        
        // Show progression flow
        echo "\nPilot Programme Progression Flow:\n";
        echo "  Phase 1 (School Elimination) → Phase 3 (SciBOTICS Final)\n";
        echo "  Phase 2 (District/Semifinals) = SKIPPED for pilot\n";
        
        // Show timeline
        echo "\nPilot Programme Timeline:\n";
        echo "  Registration: 2025-08-01 to 2025-09-05\n";
        echo "  School Competitions: 2025-09-12\n";
        echo "  Results Submission: by 2025-09-15\n";
        echo "  Qualification Announcement: 2025-09-18\n";
        echo "  SciBOTICS Final @ Sci-Bono: 2025-09-27\n";
        
        if ($validationPassed) {
            echo "\n✓ Pilot programme phase configuration is valid!\n";
        } else {
            echo "\n✗ Pilot programme phase configuration has issues that need to be resolved.\n";
        }
        
        echo "\n";
    }
}