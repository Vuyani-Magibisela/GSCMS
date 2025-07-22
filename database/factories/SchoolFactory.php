<?php
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