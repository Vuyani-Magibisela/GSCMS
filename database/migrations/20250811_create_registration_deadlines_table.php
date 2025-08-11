<?php

/**
 * Migration: Create Registration Deadlines Table
 * Description: Create table for managing registration deadlines and phase-based enforcement
 * Date: 2025-08-11
 */

class CreateRegistrationDeadlinesTable
{
    private $db;

    public function __construct()
    {
        $this->db = new App\Core\Database();
    }

    /**
     * Run the migration
     */
    public function up()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS registration_deadlines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phase_name VARCHAR(100) NOT NULL COMMENT 'Registration phase (school_registration, team_registration, etc.)',
            category_id INT NULL COMMENT 'Specific category (NULL for all categories)',
            deadline_type VARCHAR(50) NOT NULL DEFAULT 'final' COMMENT 'Type of deadline (warning, final, etc.)',
            deadline_date DATETIME NOT NULL COMMENT 'Deadline date and time',
            notification_sent BOOLEAN DEFAULT FALSE COMMENT 'Whether notification has been sent',
            enforcement_active BOOLEAN DEFAULT TRUE COMMENT 'Whether deadline enforcement is active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_phase_category (phase_name, category_id),
            INDEX idx_deadline_date (deadline_date),
            INDEX idx_enforcement (enforcement_active),
            
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Registration deadlines and phase management for GDE SciBOTICS Competition';
        ";

        $this->db->exec($sql);

        // Insert default deadlines for 2025 competition
        $this->seedDefaultDeadlines();
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS registration_deadlines");
    }

    /**
     * Seed default deadlines
     */
    private function seedDefaultDeadlines()
    {
        $deadlineManager = new App\Core\RegistrationDeadlineManager();
        $defaultDeadlines = $deadlineManager::getDefaultDeadlines();

        foreach ($defaultDeadlines as $deadline) {
            $deadlineManager->setRegistrationDeadline(
                $deadline['phase_name'],
                $deadline['deadline_type'],
                $deadline['deadline_date'],
                $deadline['category_id']
            );
        }
    }
}