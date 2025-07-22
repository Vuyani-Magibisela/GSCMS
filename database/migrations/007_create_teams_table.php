<?php
// database/migrations/007_create_teams_table.php - UPDATED

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateTeamsTable extends Migration
{
    public function up()
    {
        $columns = [
            'name' => 'VARCHAR(200) NOT NULL',
            'school_id' => 'INT NOT NULL',
            'category_id' => 'INT NOT NULL',
            'competition_id' => 'INT',
            'team_code' => 'VARCHAR(20) UNIQUE',
            'coach1_id' => 'INT',
            'coach2_id' => 'INT',
            'team_size' => 'INT DEFAULT 0',
            'status' => "ENUM('draft', 'submitted', 'approved', 'rejected', 'qualified', 'eliminated') DEFAULT 'draft'",
            'registration_date' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'qualification_score' => 'DECIMAL(5,2)',
            'current_phase' => 'INT DEFAULT 1',
            'robot_name' => 'VARCHAR(100)',
            'robot_description' => 'TEXT',
            'programming_language' => 'VARCHAR(50)',
            'special_requirements' => 'TEXT',
            'emergency_contact_name' => 'VARCHAR(100)',
            'emergency_contact_phone' => 'VARCHAR(20)',
            'emergency_contact_relationship' => 'VARCHAR(50)'
        ];
        
        $this->createTable('teams', $columns, ['no_timestamps' => true]);
        
        // Add timestamps manually for custom registration_date
        $this->execute("ALTER TABLE teams ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        $this->execute("ALTER TABLE teams ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        
        // Add indexes only (foreign keys added later)
        $this->addIndex('teams', 'idx_school_category', ['school_id', 'category_id']);
        $this->addIndex('teams', 'idx_team_code', 'team_code');
        $this->addIndex('teams', 'idx_status', 'status');
        $this->addIndex('teams', 'idx_competition', 'competition_id');
        
        // Add unique constraint: one team per school per category
        $this->execute("ALTER TABLE teams ADD CONSTRAINT unique_school_category UNIQUE (school_id, category_id)");
    }
    
    public function down()
    {
        $this->dropTable('teams');
    }
}