<?php
// database/seeds/production/DefaultPhasesSeeder.php

require_once __DIR__ . '/../../console/Seeder.php';

class DefaultPhasesSeeder extends Seeder
{
    public function run()
    {
        $this->info('Seeding default GDE SciBOTICS competition phases...');
        
        $phases = [
            [
                'name' => 'School Phase',
                'code' => 'SCHOOL',
                'description' => 'Initial school-level competition and team formation. Schools organize internal competitions to select their best teams.',
                'order_sequence' => 1,
                'status' => 'draft',
                'qualification_criteria' => json_encode([
                    'description' => 'All registered teams participate',
                    'requirements' => [
                        'Complete team registration',
                        'Submit all required consent forms',
                        'Meet category age/grade requirements'
                    ]
                ]),
                'max_teams' => null,
                'venue_requirements' => json_encode([
                    'type' => 'school_facility',
                    'requirements' => [
                        'Adequate space for robot testing',
                        'Power outlets for equipment',
                        'Tables and chairs for teams',
                        'Display area for presentations'
                    ]
                ]),
                'requires_qualification' => false,
                'advancement_percentage' => 100.00,
                'notes' => 'This is the entry phase where all teams can participate. Schools should organize internal competitions to prepare teams for district level.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'District Phase',
                'code' => 'DISTRICT',
                'description' => 'District-level competition between schools. Teams compete against other schools in their district.',
                'order_sequence' => 2,
                'status' => 'draft',
                'qualification_criteria' => json_encode([
                    'description' => 'Top performing teams from school phase',
                    'requirements' => [
                        'Successfully completed school phase',
                        'Meet all documentation requirements',
                        'Pass technical inspection',
                        'Submit project documentation'
                    ]
                ]),
                'max_teams' => 200,
                'venue_requirements' => json_encode([
                    'type' => 'district_venue',
                    'requirements' => [
                        'Large competition area',
                        'Multiple testing stations',
                        'Spectator seating',
                        'Audio-visual equipment',
                        'Secure storage for equipment',
                        'First aid facilities'
                    ]
                ]),
                'requires_qualification' => true,
                'advancement_percentage' => 30.00,
                'notes' => 'District competition brings together the best teams from multiple schools. Top 30% advance to provincial level.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Provincial Phase',
                'code' => 'PROVINCIAL',
                'description' => 'Provincial-level competition featuring the best teams from each district in the province.',
                'order_sequence' => 3,
                'status' => 'draft',
                'qualification_criteria' => json_encode([
                    'description' => 'Top 30% of teams from district phase',
                    'requirements' => [
                        'Qualified from district competition',
                        'Enhanced project documentation',
                        'Presentation materials',
                        'Complete technical portfolio',
                        'Media consent forms'
                    ]
                ]),
                'max_teams' => 100,
                'venue_requirements' => json_encode([
                    'type' => 'provincial_venue',
                    'requirements' => [
                        'Professional competition facility',
                        'Live streaming capabilities',
                        'Media interview areas',
                        'Exhibition space',
                        'Catering facilities',
                        'Accommodation coordination',
                        'Transportation logistics'
                    ]
                ]),
                'requires_qualification' => true,
                'advancement_percentage' => 20.00,
                'notes' => 'Provincial championship showcases the best talent in the province. Top 20% qualify for national finals.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'National Phase',
                'code' => 'NATIONAL',
                'description' => 'National championship finals bringing together the best teams from all provinces.',
                'order_sequence' => 4,
                'status' => 'draft',
                'qualification_criteria' => json_encode([
                    'description' => 'Top 20% of teams from provincial phase',
                    'requirements' => [
                        'Provincial championship qualification',
                        'Complete technical documentation',
                        'Innovation showcase materials',
                        'Sustainability impact report',
                        'International participation forms'
                    ]
                ]),
                'max_teams' => 50,
                'venue_requirements' => json_encode([
                    'type' => 'national_venue',
                    'requirements' => [
                        'World-class competition facility',
                        'International broadcast capabilities',
                        'VIP areas for dignitaries',
                        'Innovation exhibition halls',
                        'Award ceremony facilities',
                        'International judge facilities',
                        'Full hospitality services',
                        'Security coordination'
                    ]
                ]),
                'requires_qualification' => true,
                'advancement_percentage' => 0.00,
                'notes' => 'The pinnacle of the GDE SciBOTICS competition. National champions may represent South Africa in international competitions.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        foreach ($phases as $phase) {
            $exists = $this->db->query(
                "SELECT id FROM phases WHERE code = ?", 
                [$phase['code']]
            );
            
            if (empty($exists)) {
                $this->db->query(
                    "INSERT INTO phases (name, code, description, order_sequence, status, qualification_criteria, max_teams, venue_requirements, requires_qualification, advancement_percentage, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $phase['name'],
                        $phase['code'],
                        $phase['description'],
                        $phase['order_sequence'],
                        $phase['status'],
                        $phase['qualification_criteria'],
                        $phase['max_teams'],
                        $phase['venue_requirements'],
                        $phase['requires_qualification'],
                        $phase['advancement_percentage'],
                        $phase['notes'],
                        $phase['created_at'],
                        $phase['updated_at']
                    ]
                );
                $this->info("Created phase: {$phase['name']} ({$phase['code']})");
            } else {
                $this->info("Phase already exists: {$phase['name']} ({$phase['code']})");
            }
        }
        
        $this->success('Default phases seeded successfully!');
    }
}