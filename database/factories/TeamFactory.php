<?php
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