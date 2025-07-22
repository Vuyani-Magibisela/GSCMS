<?php
// database/factories/UserFactory.php

require_once __DIR__ . '/../../app/Core/Factory.php';

class UserFactory extends Factory
{
    public function make($attributes = [])
    {
        $faker = $this->faker();
        
        $firstName = $faker->firstName();
        $lastName = $faker->lastName();
        $uniqueId = uniqid();
        $email = $attributes['email'] ?? strtolower($firstName . '.' . $lastName . '.' . $uniqueId) . '@example.com';
        $username = $attributes['username'] ?? strtolower($firstName . $lastName . rand(100, 9999));
        
        $defaults = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $faker->phone(),
            'role' => $faker->randomElement(['school_coordinator', 'team_coach', 'judge', 'participant']),
            'status' => 'active',
            'email_verified' => $faker->boolean(0.8) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->mergeAttributes($defaults, $attributes);
    }
}