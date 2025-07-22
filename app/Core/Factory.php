<?php
// app/Core/Factory.php

if (!class_exists('Factory')) {
    abstract class Factory
{
    protected $count = 1;
    protected $attributes = [];
    
    public function __construct()
    {
        // Initialize factory
    }
    
    /**
     * Create the factory data
     */
    abstract public function make($attributes = []);
    
    /**
     * Create multiple instances
     */
    public function makeMany($count, $attributes = [])
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->make($attributes);
        }
        return $results;
    }
    
    /**
     * Merge attributes with defaults
     */
    protected function mergeAttributes($defaults, $overrides)
    {
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Get faker instance
     */
    protected function faker()
    {
        return new FakeDataGenerator();
    }
}

}

/**
 * Fake Data Generator for testing
 */
if (!class_exists('FakeDataGenerator')) {
    class FakeDataGenerator
{
    private $firstNames = [
        'Sipho', 'Nomsa', 'Thabo', 'Priya', 'Ahmed', 'Sarah', 'Michael', 'Fatima',
        'Lerato', 'Andile', 'Keagan', 'Aisha', 'Jason', 'Zinhle', 'Ryan', 'Naledi',
        'David', 'Kgotso', 'Lisa', 'Mandla', 'Tebogo', 'Lebohang', 'Mpho', 'Nokuthula'
    ];
    
    private $lastNames = [
        'Mthembu', 'Patel', 'Johnson', 'Smith', 'Nkomo', 'Hassan', 'Williams', 'Dlamini',
        'Molefe', 'Van Der Merwe', 'Singh', 'Mabaso', 'Ndlovu', 'Khumalo', 'Mahlangu',
        'Botha', 'Naidoo', 'Pillay', 'Mokoena', 'Tshabalala', 'Zungu', 'Radebe'
    ];
    
    private $schoolNames = [
        'Greenwood High School', 'Oakville Primary', 'Riverside Academy', 'Mountain View School',
        'Golden Valley High', 'Sunrise Elementary', 'Cedar Park School', 'Hillcrest Academy',
        'Valley View Primary', 'Meadowbrook High', 'Pinehurst School', 'Lakeside Academy'
    ];
    
    private $districts = [
        'Johannesburg North', 'Johannesburg South', 'Johannesburg East', 'Johannesburg West',
        'Tshwane North', 'Tshwane South', 'Ekurhuleni North', 'Ekurhuleni South',
        'Sedibeng East', 'Sedibeng West'
    ];
    
    private $cities = [
        'Johannesburg', 'Pretoria', 'Centurion', 'Sandton', 'Randburg', 'Roodepoort',
        'Kempton Park', 'Benoni', 'Boksburg', 'Germiston', 'Alberton', 'Vereeniging'
    ];
    
    private $addresses = [
        '123 Main Street', '456 Oak Avenue', '789 Pine Road', '321 Elm Drive',
        '654 Maple Lane', '987 Cedar Street', '147 Birch Avenue', '258 Willow Road'
    ];
    
    public function firstName()
    {
        return $this->firstNames[array_rand($this->firstNames)];
    }
    
    public function lastName()
    {
        return $this->lastNames[array_rand($this->lastNames)];
    }
    
    public function name()
    {
        return $this->firstName() . ' ' . $this->lastName();
    }
    
    public function email()
    {
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'school.za', 'example.com'];
        return strtolower($this->firstName() . '.' . $this->lastName() . '@' . $domains[array_rand($domains)]);
    }
    
    public function phone()
    {
        $prefixes = ['011', '012', '010', '021', '031', '041'];
        return $prefixes[array_rand($prefixes)] . ' ' . rand(100, 999) . ' ' . rand(1000, 9999);
    }
    
    public function address()
    {
        return $this->addresses[array_rand($this->addresses)];
    }
    
    public function city()
    {
        return $this->cities[array_rand($this->cities)];
    }
    
    public function district()
    {
        return $this->districts[array_rand($this->districts)];
    }
    
    public function schoolName()
    {
        return $this->schoolNames[array_rand($this->schoolNames)];
    }
    
    public function postalCode()
    {
        return rand(1000, 9999);
    }
    
    public function date($from = '2020-01-01', $to = '2024-12-31')
    {
        $fromTime = strtotime($from);
        $toTime = strtotime($to);
        $randomTime = mt_rand($fromTime, $toTime);
        return date('Y-m-d', $randomTime);
    }
    
    public function dateOfBirth($minAge = 5, $maxAge = 18)
    {
        $maxBirthDate = date('Y-m-d', strtotime("-{$minAge} years"));
        $minBirthDate = date('Y-m-d', strtotime("-{$maxAge} years"));
        return $this->date($minBirthDate, $maxBirthDate);
    }
    
    public function grade()
    {
        return 'Grade ' . rand(4, 12);
    }
    
    public function gender()
    {
        return ['Male', 'Female'][array_rand(['Male', 'Female'])];
    }
    
    public function boolean($trueChance = 0.5)
    {
        return mt_rand() / mt_getrandmax() < $trueChance;
    }
    
    public function randomElement($array)
    {
        return $array[array_rand($array)];
    }
    
    public function text($maxLength = 100)
    {
        $words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 
                  'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore'];
        
        $text = '';
        while (strlen($text) < $maxLength - 20) {
            $text .= $words[array_rand($words)] . ' ';
        }
        
        return trim($text);
    }
}
}