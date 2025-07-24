<?php
// database/migrations/002_create_users_table.php - FIXED VERSION

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateUsersTable extends Migration
{
    public function up()
    {
        $columns = [
            'username' => 'VARCHAR(50) UNIQUE NOT NULL',
            'email' => 'VARCHAR(100) UNIQUE NOT NULL',
            'password_hash' => 'VARCHAR(255) NOT NULL',
            'first_name' => 'VARCHAR(50) NOT NULL',
            'last_name' => 'VARCHAR(50) NOT NULL',
            'phone' => 'VARCHAR(20)',
            'role' => "ENUM('super_admin', 'competition_admin', 'school_coordinator', 'team_coach', 'judge', 'public_viewer', 'participant') NOT NULL",
            'status' => "ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active'",
            'email_verified' => 'BOOLEAN DEFAULT FALSE',
            'email_verification_token' => 'VARCHAR(255)',
            'password_reset_token' => 'VARCHAR(255)',
            'password_reset_expires' => 'DATETIME',
            'last_login' => 'DATETIME',
            'login_attempts' => 'INT DEFAULT 0',
            'locked_until' => 'DATETIME',
            'profile_image' => 'VARCHAR(255)'
        ];
        
        $this->createTable('users', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci'
        ]);
        
        // Add indexes - but NOT foreign keys yet (they'll be added later)
        $this->addIndex('users', 'idx_email', 'email');
        $this->addIndex('users', 'idx_username', 'username');
        $this->addIndex('users', 'idx_role', 'role');
        $this->addIndex('users', 'idx_status', 'status');
    }
    
    public function down()
    {
        $this->dropTable('users');
    }
}