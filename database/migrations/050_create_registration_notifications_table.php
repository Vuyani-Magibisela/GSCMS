<?php

/**
 * Migration: Create Registration Notifications Table
 * Description: Create table for comprehensive notification tracking in registration system
 * Date: 2025-01-19
 */

class CreateRegistrationNotificationsTable
{
    /**
     * Run the migration
     */
    public function up($pdo)
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS registration_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            
            -- Recipient Information
            recipient_type ENUM('user', 'school', 'team', 'admin', 'system') NOT NULL COMMENT 'Type of notification recipient',
            recipient_id INT NOT NULL COMMENT 'ID of the recipient entity',
            recipient_email VARCHAR(255) NOT NULL COMMENT 'Email address for delivery',
            recipient_phone VARCHAR(20) NULL COMMENT 'Phone number for SMS delivery',
            recipient_name VARCHAR(255) NULL COMMENT 'Recipient display name',
            
            -- Notification Details
            notification_type ENUM(
                'school_registration_confirmation',
                'school_registration_approved',
                'school_registration_rejected',
                'team_registration_confirmation',
                'team_registration_approved', 
                'team_registration_rejected',
                'deadline_reminder_30_days',
                'deadline_reminder_14_days',
                'deadline_reminder_7_days',
                'deadline_reminder_24_hours',
                'deadline_passed',
                'document_missing_reminder',
                'bulk_import_completed',
                'bulk_import_failed',
                'validation_errors_found',
                'approval_required',
                'status_update',
                'system_maintenance',
                'welcome_message',
                'password_reset',
                'account_activated'
            ) NOT NULL COMMENT 'Type of notification being sent',
            
            notification_category ENUM('registration', 'deadline', 'approval', 'system', 'communication') NOT NULL COMMENT 'Notification category',
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal' COMMENT 'Notification priority level',
            
            -- Content Information
            subject VARCHAR(500) NOT NULL COMMENT 'Notification subject line',
            content TEXT NOT NULL COMMENT 'Notification message content',
            content_html TEXT NULL COMMENT 'HTML version of content',
            template_used VARCHAR(100) NULL COMMENT 'Email template identifier',
            personalization_data JSON NULL COMMENT 'Data used for personalization',
            
            -- Delivery Channels
            delivery_method SET('email', 'sms', 'push', 'dashboard') NOT NULL DEFAULT 'email' COMMENT 'Delivery channels to use',
            email_delivery_attempted BOOLEAN DEFAULT FALSE COMMENT 'Email delivery was attempted',
            sms_delivery_attempted BOOLEAN DEFAULT FALSE COMMENT 'SMS delivery was attempted',
            push_delivery_attempted BOOLEAN DEFAULT FALSE COMMENT 'Push notification attempted',
            dashboard_delivery_attempted BOOLEAN DEFAULT FALSE COMMENT 'Dashboard notification attempted',
            
            -- Delivery Status
            delivery_status ENUM('pending', 'queued', 'sending', 'sent', 'delivered', 'failed', 'cancelled') DEFAULT 'pending',
            email_status ENUM('pending', 'sent', 'delivered', 'bounced', 'failed') NULL COMMENT 'Email specific status',
            sms_status ENUM('pending', 'sent', 'delivered', 'failed') NULL COMMENT 'SMS specific status',
            delivery_attempts INT DEFAULT 0 COMMENT 'Number of delivery attempts made',
            max_delivery_attempts INT DEFAULT 3 COMMENT 'Maximum delivery attempts allowed',
            
            -- Scheduling
            scheduled_for TIMESTAMP NULL COMMENT 'When notification should be sent',
            sent_at TIMESTAMP NULL COMMENT 'When notification was actually sent',
            delivered_at TIMESTAMP NULL COMMENT 'When notification was delivered',
            opened_at TIMESTAMP NULL COMMENT 'When recipient opened notification',
            clicked_at TIMESTAMP NULL COMMENT 'When recipient clicked links in notification',
            
            -- Response Tracking
            response_required BOOLEAN DEFAULT FALSE COMMENT 'Notification requires recipient response',
            response_received BOOLEAN DEFAULT FALSE COMMENT 'Response has been received',
            response_deadline TIMESTAMP NULL COMMENT 'Deadline for response',
            response_content TEXT NULL COMMENT 'Recipient response content',
            response_at TIMESTAMP NULL COMMENT 'When response was received',
            
            -- Error Handling
            error_message TEXT NULL COMMENT 'Delivery error details',
            error_code VARCHAR(50) NULL COMMENT 'System error code',
            retry_count INT DEFAULT 0 COMMENT 'Number of retry attempts',
            next_retry_at TIMESTAMP NULL COMMENT 'Next retry attempt time',
            
            -- Related Entities
            related_entity_type VARCHAR(50) NULL COMMENT 'Type of related entity (school_registration, team_registration, etc.)',
            related_entity_id INT NULL COMMENT 'ID of related entity',
            trigger_event VARCHAR(100) NULL COMMENT 'Event that triggered notification',
            
            -- Notification Context
            context_data JSON NULL COMMENT 'Additional context information',
            metadata JSON NULL COMMENT 'System metadata and tracking info',
            tags VARCHAR(500) NULL COMMENT 'Comma-separated tags for categorization',
            
            -- User Interaction
            is_read BOOLEAN DEFAULT FALSE COMMENT 'Notification has been read',
            read_at TIMESTAMP NULL COMMENT 'When notification was read',
            is_archived BOOLEAN DEFAULT FALSE COMMENT 'Notification archived by recipient',
            archived_at TIMESTAMP NULL COMMENT 'When notification was archived',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_recipient (recipient_type, recipient_id),
            INDEX idx_recipient_email (recipient_email),
            INDEX idx_notification_type (notification_type),
            INDEX idx_delivery_status (delivery_status),
            INDEX idx_scheduled_delivery (scheduled_for),
            INDEX idx_sent_notifications (sent_at),
            INDEX idx_priority (priority),
            INDEX idx_category (notification_category),
            INDEX idx_related_entity (related_entity_type, related_entity_id),
            INDEX idx_response_required (response_required, response_deadline),
            INDEX idx_read_status (is_read, read_at),
            INDEX idx_failed_deliveries (delivery_status, retry_count),
            
            -- Foreign Keys (flexible design - no strict FK constraints due to polymorphic relations)
            INDEX idx_recipient_relations (recipient_type, recipient_id)
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Comprehensive notification system for registration workflows with multi-channel delivery';
        ";

        $pdo->exec($sql);
        
        echo "Created registration_notifications table\n";
    }

    /**
     * Reverse the migration
     */
    public function down($pdo)
    {
        $pdo->exec("DROP TABLE IF EXISTS registration_notifications");
        echo "Dropped registration_notifications table\n";
    }
}