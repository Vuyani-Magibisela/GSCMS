<?php

/**
 * Migration: Create School Registrations Table
 * Description: Create table for comprehensive school self-registration system
 * Date: 2025-01-19
 */

class CreateSchoolRegistrationsTable
{
    /**
     * Run the migration
     */
    public function up($pdo)
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS school_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_name VARCHAR(255) NOT NULL COMMENT 'Official registered school name',
            emis_number VARCHAR(20) UNIQUE NULL COMMENT 'Education department identifier',
            school_type ENUM('primary', 'secondary', 'combined') NOT NULL COMMENT 'Type of school',
            establishment_date YEAR NULL COMMENT 'Year school was founded',
            current_enrollment INT NULL COMMENT 'Total student count',
            
            -- Address Information
            physical_address TEXT NOT NULL COMMENT 'Complete street address',
            postal_address TEXT NULL COMMENT 'Mailing address if different',
            gps_coordinates VARCHAR(50) NULL COMMENT 'Latitude,Longitude for location',
            district_id INT NULL COMMENT 'Assigned education district',
            
            -- Contact Details
            main_phone VARCHAR(20) NOT NULL COMMENT 'School telephone number',
            main_email VARCHAR(255) NOT NULL COMMENT 'Official school email',
            website_url VARCHAR(255) NULL COMMENT 'School website if available',
            social_media JSON NULL COMMENT 'Official social media accounts',
            
            -- Administrative Contacts
            principal_name VARCHAR(255) NOT NULL COMMENT 'Principal full name and title',
            principal_email VARCHAR(255) NOT NULL COMMENT 'Principal direct email',
            principal_phone VARCHAR(20) NOT NULL COMMENT 'Principal direct phone',
            principal_appointment_date DATE NULL COMMENT 'Principal tenure tracking',
            
            -- Coordinator Information
            coordinator_user_id INT NULL COMMENT 'Assigned coordinator user ID',
            coordinator_qualifications TEXT NULL COMMENT 'Coordinator experience and training',
            coordinator_availability TEXT NULL COMMENT 'Time commitment confirmation',
            backup_coordinator_user_id INT NULL COMMENT 'Secondary coordinator user ID',
            
            -- Facilities and Resources
            computer_lab_available BOOLEAN DEFAULT FALSE COMMENT 'Dedicated computer space available',
            internet_connectivity ENUM('none', 'basic', 'good', 'excellent') DEFAULT 'basic' COMMENT 'Internet quality',
            classroom_space_available BOOLEAN DEFAULT FALSE COMMENT 'Training space available',
            storage_facilities BOOLEAN DEFAULT FALSE COMMENT 'Equipment storage capacity',
            
            -- Existing Equipment
            existing_robotics_kits TEXT NULL COMMENT 'Current LEGO/Arduino equipment',
            computer_count INT DEFAULT 0 COMMENT 'Programming capable devices',
            software_access TEXT NULL COMMENT 'Available programming software',
            previous_robotics_experience TEXT NULL COMMENT 'Historical robotics programs',
            
            -- Participation Commitment
            intended_categories JSON NULL COMMENT 'Planned team registrations',
            estimated_participants INT NULL COMMENT 'Expected student numbers',
            coach_availability TEXT NULL COMMENT 'Qualified adult supervision',
            training_commitment_hours INT NULL COMMENT 'Weekly preparation time',
            
            -- Support Requirements
            training_needs TEXT NULL COMMENT 'Educator workshop requirements',
            equipment_needs TEXT NULL COMMENT 'Additional equipment needed',
            transport_arrangements TEXT NULL COMMENT 'Competition day logistics',
            special_accommodations TEXT NULL COMMENT 'Accessibility requirements',
            
            -- Registration Status
            registration_status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected') DEFAULT 'draft',
            submitted_at TIMESTAMP NULL COMMENT 'Registration submission time',
            reviewed_at TIMESTAMP NULL COMMENT 'Review completion time',
            approved_at TIMESTAMP NULL COMMENT 'Approval timestamp',
            approved_by INT NULL COMMENT 'Admin user who approved',
            rejection_reason TEXT NULL COMMENT 'Reason for rejection if applicable',
            
            -- Document Status
            documents_complete BOOLEAN DEFAULT FALSE COMMENT 'All required documents submitted',
            verification_complete BOOLEAN DEFAULT FALSE COMMENT 'EMIS and contact verification done',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_registration_status (registration_status),
            INDEX idx_school_name (school_name),
            INDEX idx_emis_number (emis_number),
            INDEX idx_submitted_at (submitted_at),
            INDEX idx_coordinator (coordinator_user_id),
            
            -- Foreign Keys
            FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
            FOREIGN KEY (coordinator_user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (backup_coordinator_user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Comprehensive school self-registration system for GDE SciBOTICS Competition';
        ";

        $pdo->exec($sql);
        
        echo "Created school_registrations table\n";
    }

    /**
     * Reverse the migration
     */
    public function down($pdo)
    {
        $pdo->exec("DROP TABLE IF EXISTS school_registrations");
        echo "Dropped school_registrations table\n";
    }
}