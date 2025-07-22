<?php
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