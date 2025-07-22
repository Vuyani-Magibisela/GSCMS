<?php
// database/seeds/production/PhasesSeeder.php

class PhasesSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding competition phases...");
        
        // Check if phases already exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM phases");
        $stmt->execute();
        $phaseCount = $stmt->fetchColumn();
        
        if ($phaseCount > 0) {
            $this->logger->info("Phases already exist, skipping...");
            return;
        }
        
        $currentYear = date('Y');
        $phases = [
            [
                'name' => 'Phase 1: School-Based Competition',
                'description' => 'School-level competition and team selection process',
                'phase_number' => 1,
                'start_date' => "{$currentYear}-08-01",
                'end_date' => "{$currentYear}-08-31",
                'registration_deadline' => "{$currentYear}-07-25",
                'max_teams_per_category' => 50,
                'location_type' => 'school_based',
                'status' => 'upcoming',
                'requirements' => 'Schools must register teams and conduct internal competitions',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Phase 2: District Semifinals',
                'description' => 'District-level competition with maximum 15 teams per category',
                'phase_number' => 2,
                'start_date' => "{$currentYear}-09-05",
                'end_date' => "{$currentYear}-09-15",
                'registration_deadline' => "{$currentYear}-09-01",
                'max_teams_per_category' => 15,
                'location_type' => 'district_based',
                'status' => 'upcoming',
                'requirements' => 'Teams must qualify from Phase 1 school competitions',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Phase 3: Provincial Finals',
                'description' => 'Provincial finals held at Sci-Bono Discovery Centre',
                'phase_number' => 3,
                'start_date' => "{$currentYear}-09-27",
                'end_date' => "{$currentYear}-09-27",
                'registration_deadline' => "{$currentYear}-09-20",
                'max_teams_per_category' => 8,
                'location_type' => 'provincial',
                'status' => 'upcoming',
                'requirements' => 'Teams must qualify from Phase 2 district competitions',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insertBatch('phases', $phases);
        $this->logger->info("Competition phases created successfully");
    }
}