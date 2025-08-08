<?php
// database/migrations/030_create_category_subdivisions_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateCategorySubdivisionsTable extends Migration
{
    public function up()
    {
        // Create category subdivisions table for complex categories
        $columns = [
            'category_id' => 'INT NOT NULL',
            'name' => 'VARCHAR(100) NOT NULL',
            'code' => 'VARCHAR(20) NOT NULL',
            'description' => 'TEXT',
            'min_grade' => 'INT NOT NULL',
            'max_grade' => 'INT NOT NULL',
            'specific_challenge' => 'VARCHAR(200)',
            'equipment_details' => 'TEXT',
            'mission_details' => 'TEXT',
            'max_participants' => 'INT DEFAULT 4',
            'rules_override' => 'TEXT',
            'active' => 'BOOLEAN DEFAULT TRUE'
        ];
        
        $this->createTable('category_subdivisions', $columns);
        
        // Add indexes
        $this->addIndex('category_subdivisions', 'idx_category_subdivision', ['category_id']);
        $this->addIndex('category_subdivisions', 'idx_code', 'code');
        $this->addIndex('category_subdivisions', 'idx_grade_range', ['min_grade', 'max_grade']);
        $this->addIndex('category_subdivisions', 'idx_active', 'active');
        
        // Add unique constraint for code
        $this->execute("ALTER TABLE category_subdivisions ADD CONSTRAINT unique_subdivision_code UNIQUE (code)");
        
        // Insert subdivision data based on competition requirements
        $subdivisions = [
            // EXPLORER Category Subdivisions
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "SPIKE_47" LIMIT 1)',
                'name' => 'Cosmic Cargo',
                'code' => 'EXPLORER_COSMIC',
                'description' => 'Cosmic Cargo challenge for Grade 4-7',
                'min_grade' => 4,
                'max_grade' => 7,
                'specific_challenge' => 'Cosmic Cargo Collection and Transport',
                'equipment_details' => 'LEGO Spike Prime kit with sensors',
                'mission_details' => 'Navigate from START to END while collecting space capsule to rescue fellow astronauts',
                'max_participants' => 4,
                'rules_override' => 'Electronic timing, fastest run used for scoring. One restart per stage allowed.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ],
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "SPIKE_89" LIMIT 1)', 
                'name' => 'Lost in Space',
                'code' => 'EXPLORER_LOST',
                'description' => 'Lost in Space challenge for Grade 8-9',
                'min_grade' => 8,
                'max_grade' => 9,
                'specific_challenge' => 'Space Navigation and Rescue Mission',
                'equipment_details' => 'LEGO Spike Prime with advanced sensors and actuators',
                'mission_details' => 'Transport urgent cargo to save Outpost 10 of the Intergalactic Federation',
                'max_participants' => 4,
                'rules_override' => 'Autonomous robot only. Electronic timing with laser beams. Best of three runs.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ],
            
            // ARDUINO Category Subdivisions  
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "ARDUINO_89" LIMIT 1)',
                'name' => 'Thunderdrome',
                'code' => 'ARDUINO_THUNDER',
                'description' => 'Thunderdrome challenge for Grade 8-9',
                'min_grade' => 8,
                'max_grade' => 9,
                'specific_challenge' => 'Line Following Navigation Challenge',
                'equipment_details' => 'SciBOT, Arduino robots, maximum 80mm wheel diameter',
                'mission_details' => 'Navigate from CRASH ZONE to THUNDERDROME following white line navigation',
                'max_participants' => 4,
                'rules_override' => 'Line following with 135-degree maximum turns. Wheel diameter limit 80mm. Three attempts allowed.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ],
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "ARDUINO_1011" LIMIT 1)',
                'name' => 'Mission to Yellow Planet', 
                'code' => 'ARDUINO_YELLOW',
                'description' => 'Yellow Planet rescue mission for Grade 10-11',
                'min_grade' => 10,
                'max_grade' => 11,
                'specific_challenge' => 'Complex Autonomous Navigation and Rescue',
                'equipment_details' => 'Advanced Arduino platforms, sophisticated sensors, custom chassis',
                'mission_details' => 'Rescue Princess Alesia from the forbidden Yellow Planet prison',
                'max_participants' => 4,
                'rules_override' => 'Two-stage mission: fetch and return. Avoid poisonous mineral outcrops. Best of three runs.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ],
            
            // INVENTOR Category Subdivisions (by grade groups)
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "INVENTOR_R3" LIMIT 1)',
                'name' => 'Junior Inventors',
                'code' => 'INVENTOR_JUNIOR',
                'description' => 'Innovation challenge for young inventors (Grade R-3)',
                'min_grade' => 0, // Grade R
                'max_grade' => 3,
                'specific_challenge' => 'Community Problem Solving with Creative Solutions',
                'equipment_details' => 'Craft materials, recycled items, basic construction materials',
                'mission_details' => 'Identify community problems and create innovative robot solutions',
                'max_participants' => 6, // INVENTOR allows 6 participants
                'rules_override' => 'Open innovation format. Prototype required. Marketing poster presentation.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ],
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "INVENTOR_47" LIMIT 1)',
                'name' => 'Intermediate Inventors', 
                'code' => 'INVENTOR_INTERMEDIATE',
                'description' => 'Innovation challenge for intermediate inventors (Grade 4-7)',
                'min_grade' => 4,
                'max_grade' => 7,
                'specific_challenge' => 'Working Robot Solutions for Community Problems',
                'equipment_details' => 'Electronics, motors, sensors, construction materials, tools',
                'mission_details' => 'Build working robots to solve real-world community problems',
                'max_participants' => 6,
                'rules_override' => 'Working prototype demonstration required. Cost analysis needed.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ],
            [
                'category_id' => '(SELECT id FROM categories WHERE code = "INVENTOR_811" LIMIT 1)',
                'name' => 'Senior Inventors',
                'code' => 'INVENTOR_SENIOR', 
                'description' => 'Innovation challenge for advanced inventors (Grade 8-11)',
                'min_grade' => 8,
                'max_grade' => 11,
                'specific_challenge' => 'Sophisticated Solutions for Complex Societal Challenges',
                'equipment_details' => 'Advanced electronics, microcontrollers, sensors, professional tools',
                'mission_details' => 'Develop sophisticated solutions for complex societal challenges',
                'max_participants' => 6,
                'rules_override' => 'Advanced prototype required. Business case presentation. Impact assessment.',
                'active' => true,
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ]
        ];
        
        // Insert subdivisions using subqueries to get category IDs
        foreach ($subdivisions as $subdivision) {
            $categoryIdQuery = str_replace("'", "", $subdivision['category_id']);
            $stmt = $this->db->query($categoryIdQuery);
            $categoryId = $stmt[0]['id'] ?? null;
            
            if ($categoryId) {
                $subdivision['category_id'] = $categoryId;
                $this->db->table('category_subdivisions')->insert($subdivision);
            }
        }
        
        $this->logger->info("Category subdivisions table created with competition-specific subdivisions");
    }
    
    public function down()
    {
        $this->dropTable('category_subdivisions');
        $this->logger->info("Category subdivisions table dropped");
    }
}