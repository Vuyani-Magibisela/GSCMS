<?php
// database/seeds/production/AdminUserSeeder.php

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $this->logger->info("Seeding admin users...");
        
        // Check if admin user already exists
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount > 0) {
            $this->logger->info("Admin users already exist, skipping...");
            return;
        }
        
        // Create super admin user
        $adminData = [
            [
                'username' => 'admin',
                'email' => 'admin@gde.gov.za',
                'password_hash' => password_hash('admin123!@#', PASSWORD_DEFAULT),
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'role' => 'super_admin',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'comp_admin',
                'email' => 'competition@scibono.co.za',
                'password_hash' => password_hash('comp123!@#', PASSWORD_DEFAULT),
                'first_name' => 'Competition',
                'last_name' => 'Administrator',
                'role' => 'competition_admin',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insertBatch('users', $adminData);
        $this->logger->info("Admin users created successfully");
    }
}