<?php
// database/seeds/development/DevelopmentSeeder.php

require_once __DIR__ . '/../../../app/Core/Seeder.php';
require_once __DIR__ . '/../production/ProductionSeeder.php';
require_once __DIR__ . '/TestUsersSeeder.php';
require_once __DIR__ . '/SampleSchoolsSeeder.php';
require_once __DIR__ . '/SampleTeamsSeeder.php';

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