<?php
// database/seeds/production/CategoriesSeeder.php

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding competition categories...");
        
        // Check if categories already exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories");
        $stmt->execute();
        $categoryCount = $stmt->fetchColumn();
        
        if ($categoryCount > 0) {
            $this->logger->info("Categories already exist, skipping...");
            return;
        }
        
        $categories = [
            [
                'name' => 'Junior',
                'code' => 'JUNIOR',
                'description' => 'Life on the Red Planet challenge for youngest participants',
                'grade_range' => 'Grade R - Grade 3',
                'hardware_requirements' => 'Cubroid, BEE Bot, or similar educational robots',
                'mission_description' => 'Program a robot to move from BASE 1 to BASE 2 on the red planet through three challenging stages',
                'research_topic' => 'Life on the Red Planet - Research spaceships and space survival',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'robot_design' => 25,
                    'robot_performance' => 25,
                    'missions_completed' => 25,
                    'research_presentation' => 25
                ]),
                'rules' => 'Teams can restart within 15 minutes. No touching robot while driving. Restarts allowed at staging points.',
                'equipment_list' => 'Cubroid blocks, BEE Bot, or similar programmable robots',
                'age_restrictions' => 'Grade R to Grade 3 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Explorers (Grade 4-7)',
                'code' => 'SPIKE_47',
                'description' => 'Lost in Space challenge for intermediate level participants',
                'grade_range' => 'Grade 4 - Grade 7',
                'hardware_requirements' => 'LEGO Spike Prime or similar',
                'mission_description' => 'Navigate from START to END while collecting space capsule to rescue fellow astronauts',
                'research_topic' => 'Can robots think? Understanding binary systems and basic AI concepts',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'technical_challenge' => 60,
                    'research_presentation' => 40
                ]),
                'rules' => 'Electronic timing, fastest run used for scoring. One restart per stage allowed. Must collect space capsule.',
                'equipment_list' => 'LEGO Spike Prime kit with sensors',
                'age_restrictions' => 'Grade 4 to Grade 7 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Explorers (Grade 8-9)',
                'code' => 'SPIKE_89',
                'description' => 'Cosmic Cargo challenge for advanced intermediate participants',
                'grade_range' => 'Grade 8 - Grade 9',
                'hardware_requirements' => 'LEGO Spike Prime or similar advanced robotics kit',
                'mission_description' => 'Transport urgent cargo to save Outpost 10 of the Intergalactic Federation',
                'research_topic' => 'Digital systems: inputs, outputs, and analogue vs digital conversion',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'technical_challenge' => 60,
                    'research_presentation' => 40
                ]),
                'rules' => 'Autonomous robot only. Electronic timing with laser beams. Best of three runs.',
                'equipment_list' => 'LEGO Spike Prime with advanced sensors and actuators',
                'age_restrictions' => 'Grade 8 to Grade 9 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Open Hardware (Grade 8-9)',
                'code' => 'ARDUINO_89',
                'description' => 'Thunderdrome challenge with custom-built robots',
                'grade_range' => 'Grade 8 - Grade 9',
                'hardware_requirements' => 'SciBOT, Arduino robots, maximum 80mm wheel diameter',
                'mission_description' => 'Navigate from CRASH ZONE to THUNDERDROME following white line navigation',
                'research_topic' => 'Computer algorithms and their practical applications in robotics',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'technical_challenge' => 60,
                    'research_presentation' => 40
                ]),
                'rules' => 'Line following with 135-degree maximum turns. Wheel diameter limit 80mm. Three attempts allowed.',
                'equipment_list' => 'Arduino-compatible microcontrollers, sensors, motors, wheels (max 80mm)',
                'age_restrictions' => 'Grade 8 to Grade 9 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Open Hardware (Grade 10-11)',
                'code' => 'ARDUINO_1011',
                'description' => 'Mission to Yellow Planet rescue challenge',
                'grade_range' => 'Grade 10 - Grade 11',
                'hardware_requirements' => 'SciBOT, Arduino robots, maximum 80mm wheel diameter',
                'mission_description' => 'Rescue Princess Alesia from the forbidden Yellow Planet prison',
                'research_topic' => 'Advanced algorithms, machine learning, and AI applications',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'technical_challenge' => 60,
                    'research_presentation' => 40
                ]),
                'rules' => 'Two-stage mission: fetch and return. Avoid poisonous mineral outcrops. Best of three runs.',
                'equipment_list' => 'Advanced Arduino platforms, sophisticated sensors, custom chassis',
                'age_restrictions' => 'Grade 10 to Grade 11 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Inventor (Grade R-3)',
                'code' => 'INVENTOR_R3',
                'description' => 'Innovation challenge for young inventors',
                'grade_range' => 'Grade R - Grade 3',
                'hardware_requirements' => 'Any materials, recycled materials strongly encouraged',
                'mission_description' => 'Identify community problems and create innovative robot solutions',
                'research_topic' => 'Problem identification and creative solution development',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => 20,
                    'solution_development' => 20,
                    'prototype_presentation' => 25,
                    'communication_skills' => 15,
                    'teamwork_collaboration' => 10,
                    'creativity_innovation' => 10
                ]),
                'rules' => 'Open innovation format. Prototype required. Marketing poster presentation.',
                'equipment_list' => 'Craft materials, recycled items, basic construction materials',
                'age_restrictions' => 'Grade R to Grade 3 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Inventor (Grade 4-7)',
                'code' => 'INVENTOR_47',
                'description' => 'Innovation challenge for intermediate inventors',
                'grade_range' => 'Grade 4 - Grade 7',
                'hardware_requirements' => 'Any materials, advanced construction techniques allowed',
                'mission_description' => 'Build working robots to solve real-world community problems',
                'research_topic' => 'Community problem solving and technological innovation',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => 20,
                    'solution_development' => 20,
                    'prototype_presentation' => 25,
                    'communication_skills' => 15,
                    'teamwork_collaboration' => 10,
                    'creativity_innovation' => 10
                ]),
                'rules' => 'Working prototype demonstration required. Cost analysis needed.',
                'equipment_list' => 'Electronics, motors, sensors, construction materials, tools',
                'age_restrictions' => 'Grade 4 to Grade 7 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Inventor (Grade 8-11)',
                'code' => 'INVENTOR_811',
                'description' => 'Innovation challenge for advanced inventors',
                'grade_range' => 'Grade 8 - Grade 11',
                'hardware_requirements' => 'Any materials, professional-grade construction encouraged',
                'mission_description' => 'Develop sophisticated solutions for complex societal challenges',
                'research_topic' => 'Advanced problem solving and social impact assessment',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'scoring_criteria' => json_encode([
                    'problem_identification' => 20,
                    'solution_development' => 20,
                    'prototype_presentation' => 25,
                    'communication_skills' => 15,
                    'teamwork_collaboration' => 10,
                    'creativity_innovation' => 10
                ]),
                'rules' => 'Advanced prototype required. Business case presentation. Impact assessment.',
                'equipment_list' => 'Advanced electronics, microcontrollers, sensors, professional tools',
                'age_restrictions' => 'Grade 8 to Grade 11 learners only',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insertBatch('categories', $categories);
        $this->logger->info("Competition categories created successfully");
    }
}