<?php
// database/seeds/production/ProductionSeeder.php

require_once __DIR__ . '/AdminUserSeeder.php';
require_once __DIR__ . '/PhasesSeeder.php';
require_once __DIR__ . '/CategoriesSeeder.php';

class ProductionSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Running production seeders...");
        
        // Only run production-safe seeders
        $this->call('AdminUserSeeder');
        $this->call('PhasesSeeder');
        $this->call('CategoriesSeeder');
        
        $this->logger->info("Production seeding completed");
    }
}