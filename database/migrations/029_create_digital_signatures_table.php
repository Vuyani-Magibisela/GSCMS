<?php
// database/migrations/029_create_digital_signatures_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateDigitalSignaturesTable extends Migration
{
    public function up()
    {
        $columns = [
            'document_id' => 'INT UNSIGNED NOT NULL COMMENT "ID of the document being signed"',
            'document_type' => "ENUM('consent_form', 'medical_form', 'contract', 'agreement', 'waiver') NOT NULL COMMENT 'Type of document being signed'",
            'signature_data_encrypted' => 'TEXT NOT NULL COMMENT "Encrypted signature data (drawing strokes, image, etc.)"',
            'signature_method' => "ENUM('web_capture', 'image_upload', 'docusign', 'adobe_sign', 'biometric', 'digital_certificate') NOT NULL COMMENT 'Method used to capture signature'",
            'signer_name' => 'VARCHAR(255) NOT NULL COMMENT "Full name of the person signing"',
            'signer_email' => 'VARCHAR(255) NULL COMMENT "Email address of signer"',
            'signer_phone' => 'VARCHAR(20) NULL COMMENT "Phone number of signer"',
            'signer_role' => "ENUM('parent', 'guardian', 'participant', 'witness', 'official', 'legal_representative') NOT NULL COMMENT 'Role of the signer"',
            'signer_ip_address' => 'VARCHAR(45) NOT NULL COMMENT "IP address when signature was captured"',
            'signer_user_agent' => 'TEXT NULL COMMENT "Browser/device user agent"',
            'signer_device_fingerprint' => 'VARCHAR(255) NULL COMMENT "Device fingerprint for additional security"',
            'signature_timestamp' => 'DATETIME NOT NULL COMMENT "Exact timestamp when signature was captured"',
            'intent_statement' => 'TEXT NULL COMMENT "Statement of intent to sign document"',
            'witness_name' => 'VARCHAR(255) NULL COMMENT "Name of witness (if applicable)"',
            'witness_email' => 'VARCHAR(255) NULL COMMENT "Email of witness (if applicable)"',
            'witness_signature_data' => 'TEXT NULL COMMENT "Witness signature data (encrypted)"',
            'biometric_data_encrypted' => 'TEXT NULL COMMENT "Encrypted biometric data (if applicable)"',
            'signature_features' => 'JSON NULL COMMENT "Extracted signature features for verification"',
            'signature_quality_score' => 'DECIMAL(3,2) NULL COMMENT "Quality score of signature (0.00-1.00)"',
            'signature_bounds' => 'JSON NULL COMMENT "Bounding box and dimensions of signature"',
            'verification_status' => "ENUM('pending', 'verified', 'failed', 'expired', 'revoked') DEFAULT 'pending' COMMENT 'Signature verification status'",
            'verification_method' => 'VARCHAR(100) NULL COMMENT "Method used for verification"',
            'verification_details' => 'JSON NULL COMMENT "Detailed verification results"',
            'verification_hash' => 'VARCHAR(64) NOT NULL COMMENT "SHA-256 hash for signature integrity"',
            'non_repudiation_hash' => 'VARCHAR(64) NOT NULL COMMENT "Non-repudiation hash for legal compliance"',
            'verified_at' => 'DATETIME NULL COMMENT "When signature was verified"',
            'verified_by' => 'INT UNSIGNED NULL COMMENT "User who verified the signature"',
            'legal_binding_confirmed' => 'BOOLEAN DEFAULT FALSE COMMENT "Legal binding status confirmed"',
            'electronic_signature_act_compliance' => 'BOOLEAN DEFAULT TRUE COMMENT "Complies with Electronic Signature Act"',
            'popia_compliance' => 'BOOLEAN DEFAULT TRUE COMMENT "Complies with POPIA requirements"',
            'audit_trail' => 'JSON NULL COMMENT "Complete audit trail of signature process"',
            'certificate_chain' => 'TEXT NULL COMMENT "Digital certificate chain (if applicable)"',
            'timestamp_authority' => 'VARCHAR(255) NULL COMMENT "Trusted timestamp authority used"',
            'docusign_envelope_id' => 'VARCHAR(255) NULL COMMENT "DocuSign envelope ID (if applicable)"',
            'docusign_status' => 'VARCHAR(50) NULL COMMENT "DocuSign status (if applicable)"',
            'adobe_sign_agreement_id' => 'VARCHAR(255) NULL COMMENT "Adobe Sign agreement ID (if applicable)"',
            'external_signature_id' => 'VARCHAR(255) NULL COMMENT "External signature service ID"',
            'original_filename' => 'VARCHAR(255) NULL COMMENT "Original filename (for uploaded signatures)"',
            'signature_image_path' => 'VARCHAR(500) NULL COMMENT "Path to signature image file"',
            'signed_document_hash' => 'VARCHAR(64) NULL COMMENT "Hash of the document at time of signing"',
            'signing_session_id' => 'VARCHAR(255) NULL COMMENT "Unique signing session identifier"',
            'geolocation' => 'JSON NULL COMMENT "GPS coordinates where signature was captured"',
            'is_valid' => 'BOOLEAN DEFAULT TRUE COMMENT "Is signature currently valid"',
            'invalidation_reason' => 'TEXT NULL COMMENT "Reason for signature invalidation (if applicable)"',
            'invalidated_at' => 'DATETIME NULL COMMENT "When signature was invalidated"',
            'invalidated_by' => 'INT UNSIGNED NULL COMMENT "User who invalidated signature"',
            'retention_date' => 'DATE NULL COMMENT "Date when signature data should be reviewed for retention"',
            'legal_hold' => 'BOOLEAN DEFAULT FALSE COMMENT "Legal hold flag to prevent deletion"',
            'encryption_key_version' => 'INT UNSIGNED DEFAULT 1 COMMENT "Version of encryption key used"',
            'compliance_metadata' => 'JSON NULL COMMENT "Additional compliance and regulatory metadata"'
        ];
        
        $this->createTable('digital_signatures', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Digital signatures with legal compliance, encryption, and comprehensive audit trails'
        ]);
        
        // Add indexes for performance and security
        $this->addIndex('digital_signatures', 'idx_digital_sig_document', 'document_id, document_type');
        $this->addIndex('digital_signatures', 'idx_digital_sig_signer_name', 'signer_name');
        $this->addIndex('digital_signatures', 'idx_digital_sig_signer_email', 'signer_email');
        $this->addIndex('digital_signatures', 'idx_digital_sig_verification_status', 'verification_status');
        $this->addIndex('digital_signatures', 'idx_digital_sig_verified_by', 'verified_by');
        $this->addIndex('digital_signatures', 'idx_digital_sig_timestamp', 'signature_timestamp');
        $this->addIndex('digital_signatures', 'idx_digital_sig_method', 'signature_method');
        $this->addIndex('digital_signatures', 'idx_digital_sig_verification_hash', 'verification_hash');
        $this->addIndex('digital_signatures', 'idx_digital_sig_nonrepudiation_hash', 'non_repudiation_hash');
        $this->addIndex('digital_signatures', 'idx_digital_sig_docusign_envelope', 'docusign_envelope_id');
        $this->addIndex('digital_signatures', 'idx_digital_sig_adobe_agreement', 'adobe_sign_agreement_id');
        $this->addIndex('digital_signatures', 'idx_digital_sig_external_id', 'external_signature_id');
        $this->addIndex('digital_signatures', 'idx_digital_sig_signing_session', 'signing_session_id');
        $this->addIndex('digital_signatures', 'idx_digital_sig_valid', 'is_valid');
        $this->addIndex('digital_signatures', 'idx_digital_sig_legal_hold', 'legal_hold');
        $this->addIndex('digital_signatures', 'idx_digital_sig_retention_date', 'retention_date');
        $this->addIndex('digital_signatures', 'idx_digital_sig_ip_address', 'signer_ip_address');
        
        // Composite indexes for common queries
        $this->addIndex('digital_signatures', 'idx_digital_sig_document_status', 'document_id, document_type, verification_status');
        $this->addIndex('digital_signatures', 'idx_digital_sig_signer_timestamp', 'signer_email, signature_timestamp');
        $this->addIndex('digital_signatures', 'idx_digital_sig_valid_verified', 'is_valid, verification_status');
        $this->addIndex('digital_signatures', 'idx_digital_sig_method_timestamp', 'signature_method, signature_timestamp');
        
        // Add foreign key constraints
        $this->addForeignKey('digital_signatures', 'fk_digital_sig_verified_by', 'verified_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->addForeignKey('digital_signatures', 'fk_digital_sig_invalidated_by', 'invalidated_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        
        echo "Created digital_signatures table with comprehensive legal compliance and security features.\n";
    }
    
    public function down()
    {
        $this->dropTable('digital_signatures');
        echo "Dropped digital_signatures table.\n";
    }
}