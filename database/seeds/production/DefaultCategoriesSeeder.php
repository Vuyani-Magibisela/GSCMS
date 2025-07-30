<?php
// database/seeds/production/DefaultCategoriesSeeder.php

require_once __DIR__ . '/../../console/Seeder.php';

class DefaultCategoriesSeeder extends Seeder
{
    public function run()
    {
        $this->info('Seeding default GDE SciBOTICS categories...');
        
        $categories = [
            [
                'name' => 'Junior Category',
                'code' => 'JUNIOR',
                'description' => 'For younger participants using Cubroid robotics kits. This category focuses on basic robotics concepts and creative problem-solving.',
                'min_age' => 8,
                'max_age' => 12,
                'min_grade' => 'Grade 4',
                'max_grade' => 'Grade 7',
                'equipment_requirements' => 'Cubroid',
                'scoring_rubric' => json_encode([
                    'creativity' => ['weight' => 25, 'max_score' => 100, 'description' => 'Originality and innovation in solution design'],
                    'functionality' => ['weight' => 30, 'max_score' => 100, 'description' => 'How well the robot performs the required tasks'],
                    'presentation' => ['weight' => 20, 'max_score' => 100, 'description' => 'Quality of team presentation and explanation'],
                    'teamwork' => ['weight' => 25, 'max_score' => 100, 'description' => 'Collaboration and team dynamics']
                ]),
                'max_teams_per_school' => 1,
                'competition_duration' => 120,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Spike Category',
                'code' => 'SPIKE',
                'description' => 'For participants using LEGO Spike Prime robotics kits. This category emphasizes programming skills and mechanical design.',
                'min_age' => 10,
                'max_age' => 15,
                'min_grade' => 'Grade 5',
                'max_grade' => 'Grade 10',
                'equipment_requirements' => 'LEGO Spike',
                'scoring_rubric' => json_encode([
                    'programming' => ['weight' => 30, 'max_score' => 100, 'description' => 'Code quality, logic, and efficiency'],
                    'mechanical_design' => ['weight' => 25, 'max_score' => 100, 'description' => 'Robot construction and engineering principles'],
                    'problem_solving' => ['weight' => 25, 'max_score' => 100, 'description' => 'Approach to challenges and adaptability'],
                    'presentation' => ['weight' => 20, 'max_score' => 100, 'description' => 'Communication of solution and process']
                ]),
                'max_teams_per_school' => 1,
                'competition_duration' => 150,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Arduino Category',
                'code' => 'ARDUINO',
                'description' => 'For advanced participants using Arduino microcontrollers. This category requires strong programming and electronics skills.',
                'min_age' => 12,
                'max_age' => 18,
                'min_grade' => 'Grade 7',
                'max_grade' => 'Grade 12',
                'equipment_requirements' => 'Arduino',
                'scoring_rubric' => json_encode([
                    'technical_complexity' => ['weight' => 35, 'max_score' => 100, 'description' => 'Sophistication of technical implementation'],
                    'innovation' => ['weight' => 25, 'max_score' => 100, 'description' => 'Novel approaches and creative solutions'],
                    'code_quality' => ['weight' => 20, 'max_score' => 100, 'description' => 'Programming best practices and documentation'],
                    'documentation' => ['weight' => 20, 'max_score' => 100, 'description' => 'Quality of technical documentation and explanation']
                ]),
                'max_teams_per_school' => 1,
                'competition_duration' => 180,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Inventor Category',
                'code' => 'INVENTOR',
                'description' => 'Open category for innovative solutions using any technology. This category encourages creative problem-solving with mixed technologies.',
                'min_age' => 10,
                'max_age' => 18,
                'min_grade' => 'Grade 5',
                'max_grade' => 'Grade 12',
                'equipment_requirements' => 'Mixed',
                'scoring_rubric' => json_encode([
                    'innovation' => ['weight' => 30, 'max_score' => 100, 'description' => 'Originality and creativity of the solution'],
                    'feasibility' => ['weight' => 25, 'max_score' => 100, 'description' => 'Practicality and implementability of the solution'],
                    'impact' => ['weight' => 25, 'max_score' => 100, 'description' => 'Potential positive impact on society or environment'],
                    'presentation' => ['weight' => 20, 'max_score' => 100, 'description' => 'Quality of presentation and communication']
                ]),
                'max_teams_per_school' => 1,
                'competition_duration' => 200,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        foreach ($categories as $category) {
            $exists = $this->db->query(
                "SELECT id FROM categories WHERE code = ?", 
                [$category['code']]
            );
            
            if (empty($exists)) {
                $this->db->query(
                    "INSERT INTO categories (name, code, description, min_age, max_age, min_grade, max_grade, equipment_requirements, scoring_rubric, max_teams_per_school, competition_duration, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $category['name'],
                        $category['code'],
                        $category['description'],
                        $category['min_age'],
                        $category['max_age'],
                        $category['min_grade'],
                        $category['max_grade'],
                        $category['equipment_requirements'],
                        $category['scoring_rubric'],
                        $category['max_teams_per_school'],
                        $category['competition_duration'],
                        $category['status'],
                        $category['created_at'],
                        $category['updated_at']
                    ]
                );
                $this->info("Created category: {$category['name']} ({$category['code']})");
            } else {
                $this->info("Category already exists: {$category['name']} ({$category['code']})");
            }
        }
        
        $this->success('Default categories seeded successfully!');
    }
}