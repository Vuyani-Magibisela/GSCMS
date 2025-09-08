<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\TimeSlot;
use App\Models\Team;

class NotificationScheduler
{
    private $db;
    private $templates = [];
    private $queue = [];
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
        $this->loadNotificationTemplates();
    }
    
    /**
     * Schedule competition reminders for an event
     */
    public function scheduleCompetitionReminders($eventId)
    {
        try {
            $event = (new CalendarEvent())->find($eventId);
            if (!$event) {
                return ['success' => false, 'message' => 'Event not found'];
            }
            
            $participants = $this->getEventParticipants($eventId);
            if (empty($participants)) {
                return ['success' => false, 'message' => 'No participants found for event'];
            }
            
            // Schedule different reminder types
            $scheduledCount = 0;
            $scheduledCount += $this->scheduleReminder($event, $participants, '1_week_before');
            $scheduledCount += $this->scheduleReminder($event, $participants, '3_days_before');
            $scheduledCount += $this->scheduleReminder($event, $participants, '1_day_before');
            $scheduledCount += $this->scheduleReminder($event, $participants, 'morning_of');
            
            // Save queued notifications
            $this->saveQueuedNotifications();
            
            return [
                'success' => true,
                'message' => "Scheduled {$scheduledCount} notifications for {$event->event_name}",
                'scheduled_count' => $scheduledCount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error scheduling reminders: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Schedule specific reminder type
     */
    private function scheduleReminder($event, $participants, $timing)
    {
        $sendTime = $this->calculateSendTime($event->start_datetime, $timing);
        if (!$sendTime || strtotime($sendTime) <= time()) {
            return 0; // Don't schedule past notifications
        }
        
        $template = $this->getTemplate("competition_reminder_{$timing}");
        if (!$template) {
            return 0; // Template not found
        }
        
        $scheduledCount = 0;
        
        foreach ($participants as $participant) {
            $notification = [
                'template_id' => $template['id'],
                'recipient_type' => $participant['type'],
                'recipient_id' => $participant['id'],
                'recipient_email' => $participant['email'],
                'recipient_phone' => $participant['phone'],
                'recipient_whatsapp' => $participant['whatsapp'],
                'scheduled_for' => $sendTime,
                'data' => json_encode([
                    'event_name' => $event->event_name,
                    'event_date' => date('F j, Y', strtotime($event->start_datetime)),
                    'event_time' => date('g:i A', strtotime($event->start_datetime)),
                    'venue' => $participant['venue_name'] ?? 'TBD',
                    'team_name' => $participant['team_name'],
                    'time_slot' => $participant['time_slot'] ?? 'TBD',
                    'table_number' => $participant['table_number'] ?? 'TBD',
                    'contact_info' => $participant['contact_info'] ?? '',
                    'special_requirements' => $participant['special_requirements'] ?? ''
                ]),
                'status' => 'pending',
                'priority' => $this->getPriorityByTiming($timing),
                'batch_id' => "event_{$event->id}_{$timing}_" . date('YmdHis')
            ];
            
            $this->queue[] = $notification;
            $scheduledCount++;
        }
        
        return $scheduledCount;
    }
    
    /**
     * Calculate send time based on event time and timing
     */
    private function calculateSendTime($eventTime, $timing)
    {
        $mappings = [
            '1_week_before' => '-7 days',
            '3_days_before' => '-3 days',
            '1_day_before' => '-1 day',
            'morning_of' => 'today 07:00',
            '2_hours_before' => '-2 hours',
            '30_minutes_before' => '-30 minutes'
        ];
        
        if (!isset($mappings[$timing])) {
            return null;
        }
        
        $eventTimestamp = strtotime($eventTime);
        
        if ($timing === 'morning_of') {
            $eventDate = date('Y-m-d', $eventTimestamp);
            return $eventDate . ' 07:00:00';
        }
        
        return date('Y-m-d H:i:s', strtotime($mappings[$timing], $eventTimestamp));
    }
    
    /**
     * Get event participants with contact information
     */
    private function getEventParticipants($eventId)
    {
        return $this->db->query("
            SELECT 
                'team' as type,
                t.id,
                t.name as team_name,
                s.name as school_name,
                c.name as category_name,
                u.email,
                u.phone,
                sp.contact_preference,
                sp.emergency_contact_number as whatsapp,
                ts.start_time as time_slot,
                ts.table_number,
                v.name as venue_name,
                sp.special_requirements,
                CONCAT('Coach: ', coach.name, ' (', coach.email, ')') as contact_info
            FROM time_slots ts
            JOIN teams t ON ts.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u ON t.coach_user_id = u.id
            LEFT JOIN users coach ON t.coach_user_id = coach.id
            LEFT JOIN venues v ON ts.venue_id = v.id
            LEFT JOIN scheduling_preferences sp ON t.id = sp.team_id
            WHERE ts.event_id = ?
            AND ts.status IN ('reserved', 'confirmed')
            AND t.deleted_at IS NULL
            
            UNION ALL
            
            SELECT 
                'school' as type,
                s.id,
                NULL as team_name,
                s.name as school_name,
                NULL as category_name,
                coord.email,
                coord.phone,
                'email' as contact_preference,
                NULL as whatsapp,
                NULL as time_slot,
                NULL as table_number,
                NULL as venue_name,
                NULL as special_requirements,
                CONCAT('School Coordinator: ', coord.name) as contact_info
            FROM schools s
            JOIN users coord ON s.coordinator_user_id = coord.id
            WHERE s.id IN (
                SELECT DISTINCT t.school_id 
                FROM time_slots ts 
                JOIN teams t ON ts.team_id = t.id 
                WHERE ts.event_id = ?
                AND ts.status IN ('reserved', 'confirmed')
            )
            AND s.deleted_at IS NULL
        ", [$eventId, $eventId]);
    }
    
    /**
     * Get priority level by timing
     */
    private function getPriorityByTiming($timing)
    {
        $priorities = [
            '1_week_before' => 'normal',
            '3_days_before' => 'normal',
            '1_day_before' => 'high',
            'morning_of' => 'high',
            '2_hours_before' => 'urgent',
            '30_minutes_before' => 'urgent'
        ];
        
        return $priorities[$timing] ?? 'normal';
    }
    
    /**
     * Process notification queue
     */
    public function processQueue()
    {
        $pending = $this->getPendingNotifications();
        $processed = 0;
        $failed = 0;
        
        foreach ($pending as $notification) {
            try {
                $result = $this->sendNotification($notification);
                if ($result['success']) {
                    $this->markAsSent($notification['id'], $result['delivery_method']);
                    $processed++;
                } else {
                    $this->handleFailure($notification['id'], $result['message']);
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->handleFailure($notification['id'], $e->getMessage());
                $failed++;
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($pending)
        ];
    }
    
    /**
     * Send individual notification
     */
    private function sendNotification($notification)
    {
        try {
            $template = $this->getTemplate(null, $notification['template_id']);
            if (!$template) {
                return ['success' => false, 'message' => 'Template not found'];
            }
            
            $message = $this->renderTemplate($template, json_decode($notification['data'], true));
            $recipient = $this->prepareRecipient($notification);
            
            // Determine delivery method based on template type and recipient preference
            $deliveryMethod = $this->selectDeliveryMethod($template, $recipient);
            
            $deliveryService = $this->getDeliveryService($deliveryMethod);
            if (!$deliveryService) {
                return ['success' => false, 'message' => 'Delivery service not available'];
            }
            
            $result = $deliveryService->send($recipient, $message, $template);
            
            if ($result['success']) {
                $this->logNotificationDelivery($notification['id'], $deliveryMethod, $recipient, 'delivered');
                return ['success' => true, 'delivery_method' => $deliveryMethod];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Delivery error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Render notification template
     */
    private function renderTemplate($template, $data)
    {
        $subject = $template['subject'] ?? '';
        $body = $template['body_template'] ?? '';
        
        // Replace variables
        foreach ($data as $key => $value) {
            $placeholder = "{{" . $key . "}}";
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }
        
        return [
            'subject' => $subject,
            'body' => $body,
            'type' => $template['type'],
            'priority' => $template['priority'] ?? 'normal'
        ];
    }
    
    /**
     * Prepare recipient information
     */
    private function prepareRecipient($notification)
    {
        return [
            'type' => $notification['recipient_type'],
            'id' => $notification['recipient_id'],
            'email' => $notification['recipient_email'],
            'phone' => $notification['recipient_phone'],
            'whatsapp' => $notification['recipient_whatsapp'],
            'preferred_method' => $this->getRecipientPreference($notification['recipient_id'], $notification['recipient_type'])
        ];
    }
    
    /**
     * Select optimal delivery method
     */
    private function selectDeliveryMethod($template, $recipient)
    {
        // Priority order: recipient preference -> template type -> fallback
        $preferredMethod = $recipient['preferred_method'] ?? 'email';
        $templateType = $template['type'];
        
        // Check if preferred method matches template type
        if ($preferredMethod === $templateType && $this->hasContactInfo($recipient, $preferredMethod)) {
            return $preferredMethod;
        }
        
        // Use template type if contact info available
        if ($this->hasContactInfo($recipient, $templateType)) {
            return $templateType;
        }
        
        // Fallback to email if available
        if ($recipient['email']) {
            return 'email';
        }
        
        // Last resort: SMS if phone available
        if ($recipient['phone']) {
            return 'sms';
        }
        
        return 'email'; // Default fallback
    }
    
    /**
     * Check if recipient has contact info for method
     */
    private function hasContactInfo($recipient, $method)
    {
        switch ($method) {
            case 'email':
                return !empty($recipient['email']);
            case 'sms':
                return !empty($recipient['phone']);
            case 'whatsapp':
                return !empty($recipient['whatsapp']);
            default:
                return false;
        }
    }
    
    /**
     * Get delivery service for method
     */
    private function getDeliveryService($method)
    {
        switch ($method) {
            case 'email':
                return new EmailDeliveryService();
            case 'sms':
                return new SMSDeliveryService();
            case 'whatsapp':
                return new WhatsAppDeliveryService();
            default:
                return null;
        }
    }
    
    /**
     * Schedule training session reminders
     */
    public function scheduleTrainingReminders($sessionId)
    {
        try {
            $session = (new \App\Models\TrainingSession())->find($sessionId);
            if (!$session) {
                return ['success' => false, 'message' => 'Training session not found'];
            }
            
            $registeredTeams = $session->registeredTeams();
            if (empty($registeredTeams)) {
                return ['success' => false, 'message' => 'No teams registered for this session'];
            }
            
            $scheduledCount = 0;
            
            // Schedule 1 day before reminder
            $scheduledCount += $this->scheduleTrainingReminder($session, $registeredTeams, '1_day_before');
            
            // Schedule morning of reminder
            $scheduledCount += $this->scheduleTrainingReminder($session, $registeredTeams, 'morning_of');
            
            $this->saveQueuedNotifications();
            
            return [
                'success' => true,
                'message' => "Scheduled {$scheduledCount} training reminders",
                'scheduled_count' => $scheduledCount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error scheduling training reminders: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Schedule training session reminder
     */
    private function scheduleTrainingReminder($session, $teams, $timing)
    {
        $sendTime = $this->calculateSendTime($session->session_date . ' ' . $session->morning_slot_start, $timing);
        if (!$sendTime || strtotime($sendTime) <= time()) {
            return 0;
        }
        
        $template = $this->getTemplate("training_reminder_{$timing}");
        if (!$template) {
            return 0;
        }
        
        $scheduledCount = 0;
        
        foreach ($teams as $team) {
            $notification = [
                'template_id' => $template['id'],
                'recipient_type' => 'team',
                'recipient_id' => $team['team_id'],
                'recipient_email' => $team['coach_email'] ?? null,
                'recipient_phone' => $team['coach_phone'] ?? null,
                'scheduled_for' => $sendTime,
                'data' => json_encode([
                    'session_date' => date('F j, Y', strtotime($session->session_date)),
                    'day_of_week' => $session->day_of_week,
                    'morning_activity' => $session->morning_activity,
                    'afternoon_activity' => $session->afternoon_activity,
                    'morning_time' => $session->morning_slot_start . ' - ' . $session->morning_slot_end,
                    'afternoon_time' => $session->afternoon_slot_start . ' - ' . $session->afternoon_slot_end,
                    'team_name' => $team['name'],
                    'slot_preference' => $team['slot_preference'],
                    'venue_name' => $session->venue->name ?? 'TBD',
                    'notes' => $session->notes
                ]),
                'status' => 'pending',
                'priority' => $this->getPriorityByTiming($timing),
                'batch_id' => "training_{$session->id}_{$timing}_" . date('YmdHis')
            ];
            
            $this->queue[] = $notification;
            $scheduledCount++;
        }
        
        return $scheduledCount;
    }
    
    /**
     * Load notification templates
     */
    private function loadNotificationTemplates()
    {
        $this->templates = $this->db->query("
            SELECT * FROM notification_templates 
            WHERE active = 1 
            ORDER BY name
        ");
    }
    
    /**
     * Get notification template
     */
    private function getTemplate($name = null, $id = null)
    {
        if ($id) {
            foreach ($this->templates as $template) {
                if ($template['id'] == $id) {
                    return $template;
                }
            }
        }
        
        if ($name) {
            foreach ($this->templates as $template) {
                if ($template['name'] === $name) {
                    return $template;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Save queued notifications to database
     */
    private function saveQueuedNotifications()
    {
        foreach ($this->queue as $notification) {
            $this->db->query("
                INSERT INTO scheduled_notifications 
                (template_id, recipient_type, recipient_id, recipient_email, recipient_phone, recipient_whatsapp, 
                 scheduled_for, data, status, priority, batch_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $notification['template_id'],
                $notification['recipient_type'],
                $notification['recipient_id'],
                $notification['recipient_email'],
                $notification['recipient_phone'],
                $notification['recipient_whatsapp'] ?? null,
                $notification['scheduled_for'],
                $notification['data'],
                $notification['status'],
                $notification['priority'],
                $notification['batch_id']
            ]);
        }
        
        $this->queue = []; // Clear queue after saving
    }
    
    /**
     * Get pending notifications ready to send
     */
    private function getPendingNotifications()
    {
        return $this->db->query("
            SELECT * FROM scheduled_notifications 
            WHERE status = 'pending'
            AND scheduled_for <= NOW()
            AND attempts < max_attempts
            ORDER BY priority DESC, scheduled_for ASC
            LIMIT 100
        ");
    }
    
    /**
     * Mark notification as sent
     */
    private function markAsSent($notificationId, $deliveryMethod)
    {
        $this->db->query("
            UPDATE scheduled_notifications 
            SET status = 'sent', 
                delivery_method = ?, 
                sent_at = NOW(),
                attempts = attempts + 1
            WHERE id = ?
        ", [$deliveryMethod, $notificationId]);
    }
    
    /**
     * Handle notification failure
     */
    private function handleFailure($notificationId, $errorMessage)
    {
        $this->db->query("
            UPDATE scheduled_notifications 
            SET attempts = attempts + 1,
                error_message = ?
            WHERE id = ?
        ", [$errorMessage, $notificationId]);
        
        // Mark as failed if max attempts reached
        $notification = $this->db->query("
            SELECT attempts, max_attempts FROM scheduled_notifications WHERE id = ?
        ", [$notificationId])[0] ?? null;
        
        if ($notification && $notification['attempts'] >= $notification['max_attempts']) {
            $this->db->query("
                UPDATE scheduled_notifications 
                SET status = 'failed'
                WHERE id = ?
            ", [$notificationId]);
        }
    }
    
    /**
     * Log notification delivery
     */
    private function logNotificationDelivery($notificationId, $channel, $recipient, $status)
    {
        $recipientAddress = '';
        switch ($channel) {
            case 'email':
                $recipientAddress = $recipient['email'];
                break;
            case 'sms':
            case 'whatsapp':
                $recipientAddress = $recipient['phone'] ?? $recipient['whatsapp'];
                break;
        }
        
        $this->db->query("
            INSERT INTO notification_log 
            (notification_id, channel, recipient, status, delivered_at, created_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ", [$notificationId, $channel, $recipientAddress, $status]);
    }
    
    /**
     * Get recipient notification preference
     */
    private function getRecipientPreference($recipientId, $recipientType)
    {
        if ($recipientType === 'team') {
            $result = $this->db->query("
                SELECT contact_preference FROM scheduling_preferences 
                WHERE team_id = ?
            ", [$recipientId]);
            
            return $result[0]['contact_preference'] ?? 'email';
        }
        
        return 'email'; // Default for other recipient types
    }
    
    /**
     * Get notification statistics
     */
    public function getStatistics($startDate = null, $endDate = null)
    {
        $whereClause = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = 'WHERE created_at BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        }
        
        // Total notifications
        $total = $this->db->query("SELECT COUNT(*) as count FROM scheduled_notifications $whereClause", $params)[0]['count'];
        
        // By status
        $byStatus = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM scheduled_notifications 
            $whereClause 
            GROUP BY status
        ", $params);
        
        // By delivery method
        $byMethod = $this->db->query("
            SELECT delivery_method, COUNT(*) as count 
            FROM scheduled_notifications 
            WHERE delivery_method IS NOT NULL
            " . ($whereClause ? 'AND created_at BETWEEN ? AND ?' : '') . "
            GROUP BY delivery_method
        ", $params);
        
        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_delivery_method' => $byMethod
        ];
    }
}

/**
 * Email delivery service (simplified interface)
 */
class EmailDeliveryService
{
    public function send($recipient, $message, $template)
    {
        // Implementation would use the existing Mail service
        return ['success' => true, 'method' => 'email'];
    }
}

/**
 * SMS delivery service (simplified interface)
 */
class SMSDeliveryService
{
    public function send($recipient, $message, $template)
    {
        // Implementation would integrate with SMS provider
        return ['success' => true, 'method' => 'sms'];
    }
}

/**
 * WhatsApp delivery service (simplified interface)
 */
class WhatsAppDeliveryService
{
    public function send($recipient, $message, $template)
    {
        // Implementation would integrate with WhatsApp Business API
        return ['success' => true, 'method' => 'whatsapp'];
    }
}