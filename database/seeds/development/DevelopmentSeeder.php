<?php
// database/seeds/development/DevelopmentSeeder.php

require_once __DIR__ . '/../../../app/Core/Seeder.php';

class DevelopmentSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Running development seeders...");
        
        // First run production seeders to get base data
        $this->call('ProductionSeeder');
        
        // Then add development-specific data
        $this->call('TestUsersSeeder');
        $this->call('SampleSchoolsSeeder');
        $this->call('SampleTeamsSeeder');
        
        $this->logger->info("Development seeding completed");
    }
}

// database/seeds/development/TestUsersSeeder.php

require_once __DIR__ . '/../../factories/UserFactory.php';

class TestUsersSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding test users...");
        
        // Create test users for each role
        $testUsers = [
            // School Coordinators
            $this->factory('UserFactory', 1, [
                'username' => 'coord1',
                'email' => 'coordinator1@school.za',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'role' => 'school_coordinator'
            ])[0],
            $this->factory('UserFactory', 1, [
                'username' => 'coord2',
                'email' => 'coordinator2@school.za',
                'first_name' => 'Michael',
                'last_name' => 'Smith',
                'role' => 'school_coordinator'
            ])[0],
            
            // Team Coaches
            $this->factory('UserFactory', 1, [
                'username' => 'coach1',
                'email' => 'coach1@school.za',
                'first_name' => 'Priya',
                'last_name' => 'Patel',
                'role' => 'team_coach'
            ])[0],
            $this->factory('UserFactory', 1, [
                'username' => 'coach2',
                'email' => 'coach2@school.za',
                'first_name' => 'Thabo',
                'last_name' => 'Mthembu',
                'role' => 'team_coach'
            ])[0],
            
            // Judges
            $this->factory('UserFactory', 1, [
                'username' => 'judge1',
                'email' => 'judge1@scibono.co.za',
                'first_name' => 'Dr. Jane',
                'last_name' => 'Williams',
                'role' => 'judge'
            ])[0],
            $this->factory('UserFactory', 1, [
                'username' => 'judge2',
                'email' => 'judge2@scibono.co.za',
                'first_name' => 'Prof. Ahmed',
                'last_name' => 'Hassan',
                'role' => 'judge'
            ])[0],
        ];
        
        // Add random test users
        $randomUsers = $this->factory('UserFactory', 20);
        $testUsers = array_merge($testUsers, $randomUsers);
        
        $this->insertBatch('users', $testUsers);
        $this->logger->info("Test users created successfully");
    }
}

// database/seeds/development/SampleSchoolsSeeder.php

require_once __DIR__ . '/../../factories/SchoolFactory.php';

class SampleSchoolsSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding sample schools...");
        
        // Get coordinator user IDs
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'school_coordinator' LIMIT 10");
        $stmt->execute();
        $coordinatorIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($coordinatorIds)) {
            $this->logger->warning("No school coordinators found, creating schools without coordinators");
        }
        
        // Create sample schools
        $schools = [];
        for ($i = 0; $i < 15; $i++) {
            $coordinatorId = !empty($coordinatorIds) ? $coordinatorIds[array_rand($coordinatorIds)] : null;
            $schools[] = $this->factory('SchoolFactory', 1, [
                'coordinator_id' => $coordinatorId
            ])[0];
        }
        
        $this->insertBatch('schools', $schools);
        $this->logger->info("Sample schools created successfully");
    }
}

// database/seeds/development/SampleTeamsSeeder.php

require_once __DIR__ . '/../../factories/TeamFactory.php';

class SampleTeamsSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding sample teams and participants...");
        
        // Get approved schools
        $stmt = $this->db->prepare("SELECT id FROM schools WHERE status = 'approved' LIMIT 10");
        $stmt->execute();
        $schoolIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($schoolIds)) {
            // Approve some schools first
            $this->execute("UPDATE schools SET status = 'approved' LIMIT 10");
            $stmt->execute();
            $schoolIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Get categories
        $stmt = $this->db->prepare("SELECT id FROM categories");
        $stmt->execute();
        $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get coach user IDs
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role IN ('team_coach', 'school_coordinator')");
        $stmt->execute();
        $coachIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Create teams for each school (one per category)
        foreach ($schoolIds as $schoolId) {
            foreach ($categoryIds as $categoryId) {
                // Create team
                $coach1Id = !empty($coachIds) ? $coachIds[array_rand($coachIds)] : null;
                $coach2Id = !empty($coachIds) && count($coachIds) > 1 ? $coachIds[array_rand($coachIds)] : null;
                
                $team = $this->factory('TeamFactory', 1, [
                    'school_id' => $schoolId,
                    'category_id' => $categoryId,
                    'coach1_id' => $coach1Id,
                    'coach2_id' => $coach2Id
                ])[0];
                
                $this->insertBatch('teams', [$team]);
                
                // Get the team ID
                $teamId = $this->db->lastInsertId();
                
                // Create participants for this team
                $participantCount = rand(3, 6); // 3-6 participants per team
                $participants = [];
                
                for ($i = 0; $i < $participantCount; $i++) {
                    $participants[] = [
                        'team_id' => $teamId,
                        'first_name' => $this->faker()->firstName(),
                        'last_name' => $this->faker()->lastName(),
                        'date_of_birth' => $this->faker()->dateOfBirth(5, 18),
                        'grade' => $this->faker()->grade(),
                        'gender' => $this->faker()->gender(),
                        'parent_guardian_name' => $this->faker()->name(),
                        'parent_guardian_phone' => $this->faker()->phone(),
                        'parent_guardian_email' => $this->faker()->email(),
                        'consent_form_signed' => $this->faker()->boolean(0.8),
                        'photo_permission' => $this->faker()->boolean(0.7),
                        'media_permission' => $this->faker()->boolean(0.6),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                
                $this->insertBatch('participants', $participants);
                
                // Update team size
                $this->execute(
                    "UPDATE teams SET team_size = ? WHERE id = ?",
                    [count($participants), $teamId]
                );
            }
        }
        
        $this->logger->info("Sample teams and participants created successfully");
    }
    
    private function faker()
    {
        return new FakeDataGenerator();
    }
}

// database/factories/UserFactory.php

require_once __DIR__ . '/../../app/Core/Factory.php';

class UserFactory extends Factory
{
    public function make($attributes = [])
    {
        $faker = $this->faker();
        
        $firstName = $faker->firstName();
        $lastName = $faker->lastName();
        $email = $attributes['email'] ?? strtolower($firstName . '.' . $lastName) . '@example.com';
        $username = $attributes['username'] ?? strtolower($firstName . $lastName . rand(10, 99));
        
        $defaults = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $faker->phone(),
            'role' => $faker->randomElement(['school_coordinator', 'team_coach', 'judge', 'participant']),
            'status' => 'active',
            'email_verified' => $faker->boolean(0.8),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->mergeAttributes($defaults, $attributes);
    }
}

// database/factories/SchoolFactory.php

require_once __DIR__ . '/../../app/Core/Factory.php';

class SchoolFactory extends Factory
{
    public function make($attributes = [])
    {
        $faker = $this->faker();
        
        $defaults = [
            'name' => $faker->schoolName(),
            'emis_number' => 'EMIS' . rand(100000, 999999),
            'registration_number' => 'REG' . rand(10000, 99999),
            'district' => $faker->district(),
            'province' => 'Gauteng',
            'address_line1' => $faker->address(),
            'city' => $faker->city(),
            'postal_code' => $faker->postalCode(),
            'phone' => $faker->phone(),
            'email' => strtolower(str_replace(' ', '', $faker->schoolName())) . '@school.za',
            'principal_name' => $faker->name(),
            'principal_email' => 'principal.' . rand(100, 999) . '@school.za',
            'principal_phone' => $faker->phone(),
            'school_type' => $faker->randomElement(['primary', 'secondary', 'combined']),
            'quintile' => rand(1, 5),
            'total_learners' => rand(200, 1500),
            'facilities' => $faker->text(200),
            'status' => $faker->randomElement(['pending', 'approved']),
            'registration_date' => $faker->date('2024-01-01', '2024-12-31'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->mergeAttributes($defaults, $attributes);
    }
}

// database/factories/TeamFactory.php

require_once __DIR__ . '/../../app/Core/Factory.php';

class TeamFactory extends Factory
{
    public function make($attributes = [])
    {
        $faker = $this->faker();
        
        $teamNames = [
            'Tech Titans', 'Robot Rangers', 'Code Crusaders', 'Digital Dragons',
            'Cyber Stars', 'Bot Builders', 'Future Engineers', 'Tech Pioneers',
            'Innovation Squad', 'Robo Warriors', 'Science Seekers', 'Tech Wizards'
        ];
        
        $robotNames = [
            'Lightning Bot', 'Thunder Rover', 'Spark Explorer', 'Nova Navigator',
            'Cyber Cruiser', 'Tech Tracker', 'Robo Ranger', 'Digital Defender'
        ];
        
        $languages = ['Scratch', 'Python', 'C++', 'Arduino IDE', 'Blockly'];
        
        $defaults = [
            'name' => $faker->randomElement($teamNames),
            'team_code' => strtoupper(substr(md5(uniqid()), 0, 8)),
            'team_size' => 0, // Will be updated when participants are added
            'status' => $faker->randomElement(['draft', 'submitted', 'approved']),
            'registration_date' => date('Y-m-d H:i:s'),
            'robot_name' => $faker->randomElement($robotNames),
            'robot_description' => $faker->text(150),
            'programming_language' => $faker->randomElement($languages),
            'emergency_contact_name' => $faker->name(),
            'emergency_contact_phone' => $faker->phone(),
            'emergency_contact_relationship' => $faker->randomElement(['Parent', 'Guardian', 'Teacher']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->mergeAttributes($defaults, $attributes);
    }
}