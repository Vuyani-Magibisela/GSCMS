<?php
// database/seeds/development/TestUsersSeeder.php

require_once __DIR__ . '/../../factories/UserFactory.php';

class TestUsersSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding test users...");
        
        // Check if test users already exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username IN ('coord1', 'coord2', 'coach1', 'coach2', 'judge1', 'judge2')");
        $stmt->execute();
        $testUserCount = $stmt->fetchColumn();
        
        if ($testUserCount > 0) {
            $this->logger->info("Test users already exist, skipping...");
            return;
        }
        
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