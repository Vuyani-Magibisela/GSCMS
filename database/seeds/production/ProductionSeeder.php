<?php
// database/seeds/production/ProductionSeeder.php

require_once __DIR__ . '/AdminUserSeeder.php';
require_once __DIR__ . '/PhasesSeeder.php';
require_once __DIR__ . '/CategoriesSeeder.php';
require_once __DIR__ . '/DefaultCategoriesSeeder.php';
require_once __DIR__ . '/DefaultPhasesSeeder.php';

class ProductionSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Running production seeders...");
        
        // Only run production-safe seeders
        $this->call('AdminUserSeeder');
        $this->call('DefaultPhasesSeeder');
        $this->call('DefaultCategoriesSeeder');
        
        $this->logger->info("Production seeding completed");
    }
}