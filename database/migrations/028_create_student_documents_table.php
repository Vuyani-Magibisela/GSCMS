<?php
// database/migrations/028_create_student_documents_table.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateStudentDocumentsTable extends Migration
{
    public function up()
    {
        $columns = [
            'participant_id' => 'INT UNSIGNED NOT NULL COMMENT "Reference to participants table"',
            'document_type' => "ENUM('birth_certificate', 'id_document', 'passport', 'temporary_id', 'school_id', 'report_card', 'medical_certificate', 'other') NOT NULL COMMENT 'Type of student document'",
            'document_number' => 'VARCHAR(100) NULL COMMENT "Document number (ID number, passport number, etc.)"',
            'document_number_encrypted' => 'VARCHAR(500) NULL COMMENT "Encrypted document number for sensitive IDs"',
            'issuing_authority' => 'VARCHAR(255) NULL COMMENT "Authority that issued the document"',
            'issue_date' => 'DATE NULL COMMENT "Date when document was issued"',
            'expiry_date' => 'DATE NULL COMMENT "Document expiry date (if applicable)"',
            'file_path_encrypted' => 'VARCHAR(500) NOT NULL COMMENT "Encrypted file path to document"',
            'original_filename' => 'VARCHAR(255) NOT NULL COMMENT "Original filename of uploaded document"',
            'file_size' => 'BIGINT UNSIGNED NOT NULL COMMENT "File size in bytes"',
            'mime_type' => 'VARCHAR(100) NOT NULL COMMENT "MIME type of uploaded file"',
            'file_hash_sha256' => 'VARCHAR(64) NOT NULL COMMENT "SHA-256 hash of file for integrity"',
            'extracted_data_encrypted' => 'TEXT NULL COMMENT "OCR extracted data (encrypted)"',
            'extraction_confidence' => 'DECIMAL(3,2) NULL COMMENT "OCR extraction confidence score (0.00-1.00)"',
            'verification_status' => "ENUM('pending', 'verified', 'failed', 'expired', 'flagged') DEFAULT 'pending' COMMENT 'Document verification status'",
            'verification_method' => 'VARCHAR(100) NULL COMMENT "Method used for verification (manual, automated, api)"',
            'verification_details' => 'JSON NULL COMMENT "Detailed verification results"',
            'verified_at' => 'DATETIME NULL COMMENT "When document was verified"',
            'verified_by' => 'INT UNSIGNED NULL COMMENT "User who verified the document"',
            'id_validation_results' => 'JSON NULL COMMENT "SA ID number validation results"',
            'age_verification' => 'JSON NULL COMMENT "Age verification against competition categories"',
            'pii_redaction_status' => "ENUM('pending', 'redacted', 'not_required') DEFAULT 'pending' COMMENT 'PII redaction status'",
            'redacted_file_path' => 'VARCHAR(500) NULL COMMENT "Path to redacted version of document"',
            'access_level' => "ENUM('private', 'admin_only', 'school_staff', 'emergency_access') DEFAULT 'private' COMMENT 'Access level for this document'",
            'audit_trail' => 'JSON NULL COMMENT "Audit trail of all access and modifications"',
            'retention_category' => "ENUM('temporary', 'competition_duration', 'legal_minimum', 'permanent') DEFAULT 'competition_duration' COMMENT 'Data retention category'",
            'scheduled_deletion_date' => 'DATE NULL COMMENT "Date when document should be automatically deleted"',
            'legal_hold' => 'BOOLEAN DEFAULT FALSE COMMENT "Legal hold flag to prevent deletion"',
            'uploaded_by' => 'INT UNSIGNED NOT NULL COMMENT "User who uploaded the document"',
            'uploaded_file_id' => 'INT UNSIGNED NULL COMMENT "Reference to uploaded_files table"',
            'security_scan_status' => "ENUM('pending', 'clean', 'threat_detected', 'quarantined') DEFAULT 'pending' COMMENT 'Security scan status'",
            'security_scan_details' => 'JSON NULL COMMENT "Security scan results and details"',
            'encryption_key_version' => 'INT UNSIGNED DEFAULT 1 COMMENT "Version of encryption key used"',
            'compliance_flags' => 'JSON NULL COMMENT "POPIA and other compliance flags"',
            'processing_notes' => 'TEXT NULL COMMENT "Notes from document processing and verification"'
        ];
        
        $this->createTable('student_documents', $columns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Student documents with encryption, OCR, and POPIA compliance features'
        ]);
        
        // Add indexes for performance and security
        $this->addIndex('student_documents', 'idx_student_docs_participant_id', 'participant_id');
        $this->addIndex('student_documents', 'idx_student_docs_document_type', 'document_type');
        $this->addIndex('student_documents', 'idx_student_docs_verification_status', 'verification_status');
        $this->addIndex('student_documents', 'idx_student_docs_verified_by', 'verified_by');
        $this->addIndex('student_documents', 'idx_student_docs_uploaded_by', 'uploaded_by');
        $this->addIndex('student_documents', 'idx_student_docs_expiry_date', 'expiry_date');
        $this->addIndex('student_documents', 'idx_student_docs_scheduled_deletion', 'scheduled_deletion_date');
        $this->addIndex('student_documents', 'idx_student_docs_access_level', 'access_level');
        $this->addIndex('student_documents', 'idx_student_docs_security_scan', 'security_scan_status');
        $this->addIndex('student_documents', 'idx_student_docs_legal_hold', 'legal_hold');
        $this->addIndex('student_documents', 'idx_student_docs_file_hash', 'file_hash_sha256');
        
        // Composite indexes for common queries
        $this->addIndex('student_documents', 'idx_student_docs_participant_type', 'participant_id, document_type');
        $this->addIndex('student_documents', 'idx_student_docs_verification_pending', 'verification_status, created_at');
        $this->addIndex('student_documents', 'idx_student_docs_retention_review', 'retention_category, scheduled_deletion_date');
        
        // Add foreign key constraints
        $this->addForeignKey('student_documents', 'fk_student_docs_participant_id', 'participant_id', 'participants', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('student_documents', 'fk_student_docs_verified_by', 'verified_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->addForeignKey('student_documents', 'fk_student_docs_uploaded_by', 'uploaded_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('student_documents', 'fk_student_docs_uploaded_file', 'uploaded_file_id', 'uploaded_files', 'id', 'SET NULL', 'RESTRICT');
        
        echo "Created student_documents table with comprehensive security, encryption, and compliance features.\n";
    }
    
    public function down()
    {
        $this->dropTable('student_documents');
        echo "Dropped student_documents table.\n";
    }
}