<?php

/**
 * Migration: Create Team Registrations Table
 * Description: Create table for team registration with category limit enforcement
 * Date: 2025-01-19
 */

class CreateTeamRegistrationsTable
{
    /**
     * Run the migration
     */
    public function up($pdo)
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS team_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL COMMENT 'Registered school ID',
            category_id INT NOT NULL COMMENT 'Competition category',
            team_name VARCHAR(255) NOT NULL COMMENT 'Team name unique within school',
            team_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'System generated team code',
            
            -- Coach Information
            coach_primary_id INT NOT NULL COMMENT 'Primary coach user ID',
            coach_secondary_id INT NULL COMMENT 'Secondary coach user ID',
            coach_qualifications_verified BOOLEAN DEFAULT FALSE COMMENT 'Coach qualifications confirmed',
            
            -- Team Composition
            participant_count INT DEFAULT 0 COMMENT 'Current number of participants',
            min_participants INT DEFAULT 2 COMMENT 'Minimum participants required',
            max_participants INT DEFAULT 4 COMMENT 'Maximum participants allowed',
            team_captain_id INT NULL COMMENT 'Designated team captain',
            
            -- Registration Status and Workflow
            registration_status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'active', 'withdrawn') DEFAULT 'draft',
            submitted_at TIMESTAMP NULL COMMENT 'Team registration submission time',
            reviewed_at TIMESTAMP NULL COMMENT 'Review completion time',
            approved_at TIMESTAMP NULL COMMENT 'Team approval timestamp',
            approved_by INT NULL COMMENT 'Admin user who approved team',
            rejection_reason TEXT NULL COMMENT 'Reason for rejection if applicable',
            
            -- Document and Compliance Status
            documents_complete BOOLEAN DEFAULT FALSE COMMENT 'All team documents submitted',
            consent_forms_complete BOOLEAN DEFAULT FALSE COMMENT 'All participant consents collected',
            medical_forms_complete BOOLEAN DEFAULT FALSE COMMENT 'Medical information complete',
            eligibility_verified BOOLEAN DEFAULT FALSE COMMENT 'Participant eligibility confirmed',
            
            -- Category Limit Validation
            category_limit_validated BOOLEAN DEFAULT FALSE COMMENT 'Category limit compliance verified',
            duplicate_check_complete BOOLEAN DEFAULT FALSE COMMENT 'No duplicate participants confirmed',
            
            -- Competition Participation
            phase_1_eligible BOOLEAN DEFAULT FALSE COMMENT 'Eligible for Phase 1 (school level)',
            phase_3_qualified BOOLEAN DEFAULT FALSE COMMENT 'Qualified for Phase 3 (regional)',
            equipment_confirmed BOOLEAN DEFAULT FALSE COMMENT 'Required equipment availability confirmed',
            
            -- Team Goals and Expectations
            competition_objectives TEXT NULL COMMENT 'Team goals and expectations',
            previous_experience TEXT NULL COMMENT 'Team members previous robotics experience',
            special_requirements TEXT NULL COMMENT 'Special accommodations needed',
            
            -- Modification Tracking
            last_modified_at TIMESTAMP NULL COMMENT 'Last roster modification time',
            last_modified_by INT NULL COMMENT 'User who made last modification',
            modification_count INT DEFAULT 0 COMMENT 'Number of roster modifications',
            locked_for_modifications BOOLEAN DEFAULT FALSE COMMENT 'Roster locked from changes',
            
            -- Communication Preferences
            notification_email VARCHAR(255) NULL COMMENT 'Team notification email',
            contact_phone VARCHAR(20) NULL COMMENT 'Team contact phone number',
            preferred_communication ENUM('email', 'sms', 'both') DEFAULT 'email',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_school_category (school_id, category_id),
            INDEX idx_registration_status (registration_status),
            INDEX idx_team_name (team_name),
            INDEX idx_team_code (team_code),
            INDEX idx_submitted_at (submitted_at),
            INDEX idx_coach_primary (coach_primary_id),
            INDEX idx_phase_eligibility (phase_1_eligible, phase_3_qualified),
            
            -- Unique Constraints
            UNIQUE KEY unique_school_category_team (school_id, category_id, team_name),
            
            -- Foreign Keys
            FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (coach_primary_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (coach_secondary_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (team_captain_id) REFERENCES participants(id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (last_modified_by) REFERENCES users(id) ON DELETE SET NULL
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Team registration system with category limit enforcement and comprehensive tracking';
        ";

        $pdo->exec($sql);
        
        echo "Created team_registrations table\n";

        // Create team code generation trigger
        $triggerSql = "
        CREATE TRIGGER generate_team_code 
        BEFORE INSERT ON team_registrations 
        FOR EACH ROW 
        BEGIN 
            IF NEW.team_code IS NULL OR NEW.team_code = '' THEN
                SET NEW.team_code = CONCAT('T', LPAD(NEW.school_id, 3, '0'), LPAD(NEW.category_id, 2, '0'), LPAD((SELECT COALESCE(MAX(id), 0) + 1 FROM team_registrations), 4, '0'));
            END IF;
        END;
        ";
        
        $pdo->exec($triggerSql);
        echo "Created team code generation trigger\n";
    }

    /**
     * Reverse the migration
     */
    public function down($pdo)
    {
        $pdo->exec("DROP TRIGGER IF EXISTS generate_team_code");
        $pdo->exec("DROP TABLE IF EXISTS team_registrations");
        echo "Dropped team_registrations table and trigger\n";
    }
}