<?php

/**
 * Pilot Programme Categories Seeder
 * Seeds the 9 categories required for the GDE SciBOTICS Pilot Programme 2025
 */

class PilotCategoriesSeeder extends Seeder
{
    
    public function run()
    {
        echo "Seeding Pilot Programme Categories (9 categories)...\n";
        
        $pilotCategories = [
            [
                'name' => 'Junior Robotics',
                'code' => 'JUNIOR',
                'description' => 'Life on the Red Planet - Grade R-3',
                'grade_range' => 'Grade R-3',
                'hardware_requirements' => 'Cubroid, BEE Bot, etc.',
                'mission_description' => 'Move between Base#1 and Base#2 on the Red Planet',
                'research_topic' => 'Space exploration and robotics',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ]),
                'rules' => 'Teams must use age-appropriate robotics kits. Focus on basic programming and problem-solving.',
                'equipment_list' => 'Cubroid blocks, BEE Bot, tablet/computer for programming',
                'age_restrictions' => 'Ages 5-9 (Grade R-3)'
            ],
            [
                'name' => 'Explorer - Cosmic Cargo',
                'code' => 'EXPLORER_COSMIC',
                'description' => 'LEGO Spike Intermediate Mission - Grade 4-7',
                'grade_range' => 'Grade 4-7',
                'hardware_requirements' => 'LEGO Spike, EV3, etc.',
                'mission_description' => 'Cosmic Cargo Challenge',
                'research_topic' => 'Space logistics and automation',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ]),
                'rules' => 'Teams must use LEGO-based robotics platforms. Intermediate programming required.',
                'equipment_list' => 'LEGO Spike Prime, sensors, motors, programming software',
                'age_restrictions' => 'Ages 9-13 (Grade 4-7)',
            ],
            [
                'name' => 'Explorer - Lost in Space',
                'code' => 'EXPLORER_LOST',
                'description' => 'LEGO Spike Advanced Mission - Grade 8-9',
                'grade_range' => 'Grade 8-9',
                'hardware_requirements' => 'LEGO Spike, EV3, etc.',
                'mission_description' => 'Lost in Space Challenge',
                'research_topic' => 'Space navigation and rescue operations',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ]),
                'rules' => 'Teams must use LEGO-based robotics platforms. Advanced programming and problem-solving required.',
                'equipment_list' => 'LEGO Spike Prime, advanced sensors, programming environment',
                'age_restrictions' => 'Ages 13-15 (Grade 8-9)',
            ],
            [
                'name' => 'Arduino - Thunderdrome',
                'code' => 'ARDUINO_THUNDER',
                'description' => 'Custom Arduino Robot - Grade 8-9',
                'grade_range' => 'Grade 8-9',
                'hardware_requirements' => 'SciBOT, Arduino robots, etc.',
                'mission_description' => 'Thunderdrome Challenge',
                'research_topic' => 'Open-source robotics and competitive programming',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ]),
                'rules' => 'Teams must use Arduino-based or open-source hardware. Custom robot design encouraged.',
                'equipment_list' => 'Arduino microcontroller, sensors, motors, custom chassis materials',
                'age_restrictions' => 'Ages 13-15 (Grade 8-9)',
            ],
            [
                'name' => 'Arduino - Mission to Yellow Planet',
                'code' => 'ARDUINO_YELLOW',
                'description' => 'Machine Learning Mission - Grade 10-11',
                'grade_range' => 'Grade 10-11',
                'hardware_requirements' => 'SciBOT, Arduino robots, etc.',
                'mission_description' => 'Mission to the Yellow Planet',
                'research_topic' => 'Machine learning and artificial intelligence in robotics',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'robot_functionality' => ['weight' => 30, 'max_score' => 30],
                    'presentation' => ['weight' => 30, 'max_score' => 30]
                ]),
                'rules' => 'Teams must incorporate machine learning or AI concepts. Advanced programming required.',
                'equipment_list' => 'Arduino/Raspberry Pi, ML-capable hardware, sensors, advanced programming tools',
                'age_restrictions' => 'Ages 15-17 (Grade 10-11)',
            ],
            [
                'name' => 'Inventor Junior',
                'code' => 'INVENTOR_JUNIOR',
                'description' => 'Young Inventors Challenge - Grade R-3',
                'grade_range' => 'Grade R-3',
                'hardware_requirements' => 'Arduino Inventor Kit, Any Robotics Kits',
                'mission_description' => 'Blue Planet Mission - Life on Earth Solutions',
                'research_topic' => 'Environmental solutions and creative innovation',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'innovation_creativity' => ['weight' => 25, 'max_score' => 25],
                    'feasibility_impact' => ['weight' => 20, 'max_score' => 20],
                    'presentation' => ['weight' => 15, 'max_score' => 15]
                ]),
                'rules' => 'Open innovation category. Teams can use any age-appropriate technology.',
                'equipment_list' => 'Any robotics kits, craft materials, basic electronics',
                'age_restrictions' => 'Ages 5-9 (Grade R-3)',
            ],
            [
                'name' => 'Inventor Intermediate',
                'code' => 'INVENTOR_MID',
                'description' => 'Intermediate Innovation Challenge - Grade 4-7',
                'grade_range' => 'Grade 4-7',
                'hardware_requirements' => 'Arduino Inventor Kit, Any self-designed robots',
                'mission_description' => 'Real World Problem Solutions',
                'research_topic' => 'Engineering design and practical problem solving',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'innovation_creativity' => ['weight' => 25, 'max_score' => 25],
                    'feasibility_impact' => ['weight' => 20, 'max_score' => 20],
                    'presentation' => ['weight' => 15, 'max_score' => 15]
                ]),
                'rules' => 'Teams must address a real-world problem. Original design and innovation encouraged.',
                'equipment_list' => 'Arduino kits, sensors, construction materials, programming tools',
                'age_restrictions' => 'Ages 9-13 (Grade 4-7)',
            ],
            [
                'name' => 'Inventor Senior',
                'code' => 'INVENTOR_SENIOR',
                'description' => 'Advanced Innovation Challenge - Grade 8-11',
                'grade_range' => 'Grade 8-11',
                'hardware_requirements' => 'Any Robotics Kits, Self-designed robots',
                'mission_description' => 'Complex Problem Solutions with Technology',
                'research_topic' => 'Advanced engineering and integrated technology solutions',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => ['weight' => 20, 'max_score' => 20],
                    'solution_development' => ['weight' => 20, 'max_score' => 20],
                    'innovation_creativity' => ['weight' => 25, 'max_score' => 25],
                    'feasibility_impact' => ['weight' => 20, 'max_score' => 20],
                    'presentation' => ['weight' => 15, 'max_score' => 15]
                ]),
                'rules' => 'Teams must develop comprehensive solutions to complex problems. Advanced technical implementation required.',
                'equipment_list' => 'Any technology platform, advanced sensors, custom hardware',
                'age_restrictions' => 'Ages 13-17 (Grade 8-11)',
            ],
            [
                'name' => 'Special Category',
                'code' => 'SPECIAL',
                'description' => 'Special competition category for unique innovations',
                'grade_range' => 'All Grades',
                'hardware_requirements' => 'Any Technology',
                'mission_description' => 'Open Innovation Challenge',
                'research_topic' => 'Breakthrough innovation and emerging technologies',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'innovation_creativity' => ['weight' => 30, 'max_score' => 30],
                    'technical_excellence' => ['weight' => 25, 'max_score' => 25],
                    'impact_potential' => ['weight' => 25, 'max_score' => 25],
                    'presentation' => ['weight' => 20, 'max_score' => 20]
                ]),
                'rules' => 'Open category for exceptional innovations that don\'t fit standard categories. All ages welcome.',
                'equipment_list' => 'Any technology, unrestricted',
                'age_restrictions' => 'All ages (Grade R-11)',
            ]
        ];
        
        $seededCount = 0;
        
        foreach ($pilotCategories as $category) {
            try {
                // Check if category already exists
                $existing = $this->db->prepare("SELECT * FROM categories WHERE code = ?");
                $existing->execute([$category['code']]);
                $existingCategory = $existing->fetch(PDO::FETCH_ASSOC);
                
                if ($existingCategory) {
                    // Update existing category
                    $updateFields = [];
                    $updateValues = [];
                    foreach ($category as $key => $value) {
                        $updateFields[] = "{$key} = ?";
                        $updateValues[] = $value;
                    }
                    $updateValues[] = date('Y-m-d H:i:s');
                    $updateValues[] = $category['code'];
                    
                    $updateSql = "UPDATE categories SET " . implode(', ', $updateFields) . ", updated_at = ? WHERE code = ?";
                    $stmt = $this->db->prepare($updateSql);
                    $stmt->execute($updateValues);
                    echo "  Updated category: {$category['name']}\n";
                } else {
                    // Create new category
                    $fields = array_keys($category);
                    $placeholders = array_fill(0, count($fields), '?');
                    
                    $sql = "INSERT INTO categories (" . implode(', ', $fields) . ", created_at, updated_at) VALUES (" . implode(', ', $placeholders) . ", ?, ?)";
                    $values = array_values($category);
                    $values[] = date('Y-m-d H:i:s');
                    $values[] = date('Y-m-d H:i:s');
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($values);
                    echo "  Created category: {$category['name']}\n";
                }
                
                $seededCount++;
                
            } catch (Exception $e) {
                echo "  Error seeding category {$category['name']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Successfully seeded {$seededCount} pilot programme categories.\n";
        
        // Validate the seeded categories
        $this->validatePilotCategories();
    }
    
    /**
     * Validate that all pilot categories are properly seeded
     */
    private function validatePilotCategories()
    {
        echo "\nValidating pilot programme categories...\n";
        
        $requiredCodes = [
            'JUNIOR', 'EXPLORER_COSMIC', 'EXPLORER_LOST',
            'ARDUINO_THUNDER', 'ARDUINO_YELLOW',
            'INVENTOR_JUNIOR', 'INVENTOR_MID', 'INVENTOR_SENIOR',
            'SPECIAL'
        ];
        
        $stmt = $this->db->prepare("
            SELECT code, name FROM categories 
            WHERE deleted_at IS NULL
            ORDER BY name
        ");
        $stmt->execute();
        $activeCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $activeCodes = array_column($activeCategories, 'code');
        $missingCodes = array_diff($requiredCodes, $activeCodes);
        
        if (empty($missingCodes)) {
            echo "✓ All 9 pilot programme categories are active and available.\n";
            echo "✓ Categories: " . implode(', ', $activeCodes) . "\n";
            
            // Show capacity calculation
            $totalPhase1Capacity = count($activeCodes) * 30; // 30 teams per category
            $totalPhase3Capacity = count($activeCodes) * 6;  // 6 teams per category
            $expectedFinalParticipants = $totalPhase3Capacity * 4; // 4 members per team
            
            echo "\nPilot Programme Capacity:\n";
            echo "  Phase 1 (School Elimination): {$totalPhase1Capacity} teams max\n";
            echo "  Phase 3 (SciBOTICS Final): {$totalPhase3Capacity} teams max\n";
            echo "  Expected Final Participants: {$expectedFinalParticipants} students\n";
            echo "  Medals Available: " . ($totalPhase3Capacity / 2 * 3 * 4) . " (top 3 teams per category)\n";
            echo "  Trophies Available: " . count($activeCodes) . " (1 per category winner)\n";
            
        } else {
            echo "✗ Missing required categories: " . implode(', ', $missingCodes) . "\n";
        }
        
        echo "\n";
    }
}