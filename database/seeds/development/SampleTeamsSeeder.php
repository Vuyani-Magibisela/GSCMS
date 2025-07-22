<?php
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
                        'consent_form_signed' => $this->faker()->boolean(0.8) ? 1 : 0,
                        'photo_permission' => $this->faker()->boolean(0.7) ? 1 : 0,
                        'media_permission' => $this->faker()->boolean(0.6) ? 1 : 0,
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