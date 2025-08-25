<?php

/**
 * Migration: Create participant_demographics table
 * POPIA-compliant demographic data tracking
 */

class CreateParticipantDemographicsTable 
{
    public function up($pdo) 
    {
        $sql = "CREATE TABLE participant_demographics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            participant_id INT NOT NULL,
            data_category ENUM('personal', 'educational', 'socioeconomic', 'accessibility', 'support_needs') NOT NULL,
            data_key VARCHAR(100) NOT NULL,
            data_value_encrypted TEXT NOT NULL,
            data_type ENUM('string', 'number', 'boolean', 'date', 'json') NOT NULL DEFAULT 'string',
            consent_given BOOLEAN NOT NULL DEFAULT FALSE,
            consent_date TIMESTAMP NULL,
            consent_by_user_id INT NULL,
            collection_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            data_source ENUM('registration', 'import', 'manual', 'survey') NOT NULL DEFAULT 'registration',
            verification_status ENUM('unverified', 'verified', 'disputed', 'corrected') NOT NULL DEFAULT 'unverified',
            access_level ENUM('public', 'internal', 'restricted', 'confidential') NOT NULL DEFAULT 'internal',
            anonymized BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            
            FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
            FOREIGN KEY (consent_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY uk_participant_demographic (participant_id, data_category, data_key),
            INDEX idx_demographics_participant (participant_id),
            INDEX idx_demographics_category (data_category),
            INDEX idx_demographics_consent (consent_given),
            INDEX idx_demographics_expires (expires_at),
            INDEX idx_demographics_access (access_level)
        ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "Created participant_demographics table\n";
    }
    
    public function down($pdo) 
    {
        $pdo->exec("DROP TABLE IF EXISTS participant_demographics");
        echo "Dropped participant_demographics table\n";
    }
}