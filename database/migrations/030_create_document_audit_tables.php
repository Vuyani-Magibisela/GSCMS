<?php
// database/migrations/030_create_document_audit_tables.php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateDocumentAuditTables extends Migration
{
    public function up()
    {
        // Medical Access Logs Table
        $medicalAccessColumns = [
            'participant_id' => 'INT UNSIGNED NOT NULL COMMENT "Participant whose medical data was accessed"',
            'accessed_by' => 'INT UNSIGNED NOT NULL COMMENT "User who accessed the data"',
            'access_type' => "ENUM('view', 'edit', 'export', 'emergency_access', 'bulk_export') NOT NULL COMMENT 'Type of access performed'",
            'access_reason' => 'TEXT NULL COMMENT "Reason for accessing medical data"',
            'data_fields_accessed' => 'JSON NULL COMMENT "Specific medical data fields accessed"',
            'access_timestamp' => 'DATETIME NOT NULL COMMENT "Exact timestamp of access"',
            'session_id' => 'VARCHAR(255) NULL COMMENT "User session ID"',
            'ip_address' => 'VARCHAR(45) NOT NULL COMMENT "IP address of accessor"',
            'user_agent' => 'TEXT NULL COMMENT "Browser/device user agent"',
            'access_duration' => 'INT UNSIGNED NULL COMMENT "Duration of access in seconds"',
            'data_exported' => 'BOOLEAN DEFAULT FALSE COMMENT "Was data exported/downloaded"',
            'export_format' => 'VARCHAR(50) NULL COMMENT "Format of exported data (if applicable)"',
            'access_approved_by' => 'INT UNSIGNED NULL COMMENT "Supervisor who approved access (if required)"',
            'emergency_justification' => 'TEXT NULL COMMENT "Justification for emergency access"',
            'compliance_notes' => 'TEXT NULL COMMENT "POPIA compliance notes"',
            'risk_level' => "ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low' COMMENT 'Risk level of this access"'
        ];
        
        $this->createTable('medical_access_logs', $medicalAccessColumns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Comprehensive audit log for medical data access (POPIA compliance)'
        ]);
        
        // Document Access Logs Table
        $documentAccessColumns = [
            'document_id' => 'INT UNSIGNED NOT NULL COMMENT "Document that was accessed"',
            'document_type' => "ENUM('consent_form', 'medical_form', 'student_document', 'signature', 'emergency_contact') NOT NULL COMMENT 'Type of document accessed'",
            'accessed_by' => 'INT UNSIGNED NOT NULL COMMENT "User who accessed the document"',
            'access_type' => "ENUM('view', 'download', 'edit', 'delete', 'share', 'print', 'email') NOT NULL COMMENT 'Type of access performed'",
            'access_granted' => 'BOOLEAN NOT NULL DEFAULT TRUE COMMENT "Was access granted or denied"',
            'denial_reason' => 'TEXT NULL COMMENT "Reason for access denial (if applicable)"',
            'access_timestamp' => 'DATETIME NOT NULL COMMENT "Timestamp of access attempt"',
            'ip_address' => 'VARCHAR(45) NOT NULL COMMENT "IP address of accessor"',
            'user_agent' => 'TEXT NULL COMMENT "Browser/device user agent"',
            'referrer_url' => 'VARCHAR(500) NULL COMMENT "Referrer URL"',
            'session_id' => 'VARCHAR(255) NULL COMMENT "User session ID"',
            'document_version' => 'VARCHAR(50) NULL COMMENT "Version of document accessed"',
            'file_hash_at_access' => 'VARCHAR(64) NULL COMMENT "File hash at time of access for integrity"',
            'access_context' => 'TEXT NULL COMMENT "Context/reason for access"',
            'shared_with' => 'JSON NULL COMMENT "List of people document was shared with"',
            'retention_reviewed' => 'BOOLEAN DEFAULT FALSE COMMENT "Was data retention policy reviewed during access"',
            'compliance_check' => 'JSON NULL COMMENT "Compliance checks performed during access"'
        ];
        
        $this->createTable('document_access_logs', $documentAccessColumns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Audit log for all document access activities'
        ]);
        
        // Security Incident Logs Table
        $securityIncidentColumns = [
            'incident_type' => "ENUM('unauthorized_access', 'data_breach', 'suspicious_activity', 'malware_detected', 'integrity_violation', 'privacy_violation') NOT NULL COMMENT 'Type of security incident'",
            'severity_level' => "ENUM('low', 'medium', 'high', 'critical') NOT NULL COMMENT 'Severity of the incident'",
            'description' => 'TEXT NOT NULL COMMENT "Detailed description of the incident"',
            'affected_documents' => 'JSON NULL COMMENT "List of affected documents/data"',
            'affected_participants' => 'JSON NULL COMMENT "List of affected participants"',
            'user_involved' => 'INT UNSIGNED NULL COMMENT "User involved in incident (if applicable)"',
            'ip_address' => 'VARCHAR(45) NULL COMMENT "IP address involved in incident"',
            'detection_timestamp' => 'DATETIME NOT NULL COMMENT "When incident was detected"',
            'detection_method' => 'VARCHAR(100) NULL COMMENT "How incident was detected"',
            'response_actions' => 'JSON NULL COMMENT "Actions taken in response to incident"',
            'resolved_timestamp' => 'DATETIME NULL COMMENT "When incident was resolved"',
            'resolved_by' => 'INT UNSIGNED NULL COMMENT "User who resolved the incident"',
            'investigation_notes' => 'TEXT NULL COMMENT "Investigation notes and findings"',
            'regulatory_reported' => 'BOOLEAN DEFAULT FALSE COMMENT "Was incident reported to regulators"',
            'reporting_details' => 'JSON NULL COMMENT "Details of regulatory reporting"',
            'lessons_learned' => 'TEXT NULL COMMENT "Lessons learned from incident"',
            'prevention_measures' => 'TEXT NULL COMMENT "Measures implemented to prevent recurrence"',
            'incident_status' => "ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open' COMMENT 'Current status of incident"'
        ];
        
        $this->createTable('security_incident_logs', $securityIncidentColumns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'Security incident tracking and management'
        ]);
        
        // POPIA Compliance Logs Table
        $popiaComplianceColumns = [
            'compliance_type' => "ENUM('consent_given', 'consent_withdrawn', 'data_access_request', 'data_correction_request', 'data_deletion_request', 'data_portability_request', 'breach_notification', 'retention_review') NOT NULL COMMENT 'Type of POPIA compliance activity'",
            'participant_id' => 'INT UNSIGNED NULL COMMENT "Participant involved (if applicable)"',
            'request_id' => 'VARCHAR(100) NULL COMMENT "Unique identifier for the request"',
            'requested_by' => 'VARCHAR(255) NULL COMMENT "Person who made the request"',
            'request_email' => 'VARCHAR(255) NULL COMMENT "Email of person who made the request"',
            'request_details' => 'TEXT NULL COMMENT "Details of the compliance request"',
            'data_subject_verified' => 'BOOLEAN NULL COMMENT "Was data subject identity verified"',
            'verification_method' => 'VARCHAR(100) NULL COMMENT "Method used to verify identity"',
            'request_timestamp' => 'DATETIME NOT NULL COMMENT "When request was received"',
            'processing_started' => 'DATETIME NULL COMMENT "When processing started"',
            'processing_completed' => 'DATETIME NULL COMMENT "When processing was completed"',
            'processed_by' => 'INT UNSIGNED NULL COMMENT "User who processed the request"',
            'response_method' => 'VARCHAR(100) NULL COMMENT "How response was delivered"',
            'data_provided' => 'JSON NULL COMMENT "Summary of data provided (for access requests)"',
            'data_corrected' => 'JSON NULL COMMENT "Summary of data corrections made"',
            'data_deleted' => 'JSON NULL COMMENT "Summary of data deleted"',
            'deletion_confirmation' => 'VARCHAR(255) NULL COMMENT "Deletion confirmation reference"',
            'compliance_status' => "ENUM('pending', 'in_progress', 'completed', 'rejected', 'partially_completed') DEFAULT 'pending' COMMENT 'Status of compliance request'",
            'rejection_reason' => 'TEXT NULL COMMENT "Reason for rejection (if applicable)"',
            'response_time_days' => 'INT UNSIGNED NULL COMMENT "Number of days to respond"',
            'regulatory_timeline_met' => 'BOOLEAN NULL COMMENT "Was regulatory timeline met"',
            'audit_notes' => 'TEXT NULL COMMENT "Notes for compliance audit purposes"'
        ];
        
        $this->createTable('popia_compliance_logs', $popiaComplianceColumns, [
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'comment' => 'POPIA compliance activity tracking and audit trail'
        ]);
        
        // Add indexes for all tables
        
        // Medical Access Logs indexes
        $this->addIndex('medical_access_logs', 'idx_medical_access_participant_id', 'participant_id');
        $this->addIndex('medical_access_logs', 'idx_medical_access_accessed_by', 'accessed_by');
        $this->addIndex('medical_access_logs', 'idx_medical_access_timestamp', 'access_timestamp');
        $this->addIndex('medical_access_logs', 'idx_medical_access_type', 'access_type');
        $this->addIndex('medical_access_logs', 'idx_medical_access_ip', 'ip_address');
        $this->addIndex('medical_access_logs', 'idx_medical_access_risk', 'risk_level');
        $this->addIndex('medical_access_logs', 'idx_medical_access_emergency', 'access_type, access_timestamp');
        
        // Document Access Logs indexes
        $this->addIndex('document_access_logs', 'idx_doc_access_document', 'document_id, document_type');
        $this->addIndex('document_access_logs', 'idx_doc_access_accessed_by', 'accessed_by');
        $this->addIndex('document_access_logs', 'idx_doc_access_timestamp', 'access_timestamp');
        $this->addIndex('document_access_logs', 'idx_doc_access_type', 'access_type');
        $this->addIndex('document_access_logs', 'idx_doc_access_granted', 'access_granted');
        $this->addIndex('document_access_logs', 'idx_doc_access_ip', 'ip_address');
        
        // Security Incident Logs indexes
        $this->addIndex('security_incident_logs', 'idx_security_incident_type', 'incident_type');
        $this->addIndex('security_incident_logs', 'idx_security_severity', 'severity_level');
        $this->addIndex('security_incident_logs', 'idx_security_detection_time', 'detection_timestamp');
        $this->addIndex('security_incident_logs', 'idx_security_status', 'incident_status');
        $this->addIndex('security_incident_logs', 'idx_security_user_involved', 'user_involved');
        $this->addIndex('security_incident_logs', 'idx_security_resolved_by', 'resolved_by');
        
        // POPIA Compliance Logs indexes
        $this->addIndex('popia_compliance_logs', 'idx_popia_compliance_type', 'compliance_type');
        $this->addIndex('popia_compliance_logs', 'idx_popia_participant_id', 'participant_id');
        $this->addIndex('popia_compliance_logs', 'idx_popia_request_timestamp', 'request_timestamp');
        $this->addIndex('popia_compliance_logs', 'idx_popia_status', 'compliance_status');
        $this->addIndex('popia_compliance_logs', 'idx_popia_processed_by', 'processed_by');
        $this->addIndex('popia_compliance_logs', 'idx_popia_request_id', 'request_id');
        
        // Add foreign key constraints
        $this->addForeignKey('medical_access_logs', 'fk_medical_access_participant', 'participant_id', 'participants', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('medical_access_logs', 'fk_medical_access_user', 'accessed_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('medical_access_logs', 'fk_medical_access_approved_by', 'access_approved_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        
        $this->addForeignKey('document_access_logs', 'fk_doc_access_user', 'accessed_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        
        $this->addForeignKey('security_incident_logs', 'fk_security_user_involved', 'user_involved', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->addForeignKey('security_incident_logs', 'fk_security_resolved_by', 'resolved_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        
        $this->addForeignKey('popia_compliance_logs', 'fk_popia_participant', 'participant_id', 'participants', 'id', 'SET NULL', 'RESTRICT');
        $this->addForeignKey('popia_compliance_logs', 'fk_popia_processed_by', 'processed_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        
        echo "Created comprehensive audit and compliance logging tables for POPIA compliance.\n";
    }
    
    public function down()
    {
        $this->dropTable('medical_access_logs');
        $this->dropTable('document_access_logs');
        $this->dropTable('security_incident_logs');
        $this->dropTable('popia_compliance_logs');
        echo "Dropped all audit and compliance logging tables.\n";
    }
}