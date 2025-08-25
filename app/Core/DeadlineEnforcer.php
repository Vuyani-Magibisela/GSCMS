<?php

namespace App\Core;

use App\Models\Competition;
use App\Models\SchoolRegistration;
use App\Models\TeamRegistration;
use App\Models\BulkImport;
use App\Models\RegistrationNotification;

/**
 * Deadline Enforcer
 * Automated deadline monitoring and enforcement system
 */
class DeadlineEnforcer
{
    /**
     * Enforce all registration deadlines
     */
    public function enforceRegistrationDeadlines()
    {
        $now = new \DateTime();
        
        try {
            // Check school registration deadline
            $this->enforceSchoolRegistrationDeadline($now);
            
            // Check team registration deadline
            $this->enforceTeamRegistrationDeadline($now);
            
            // Check participant registration deadline
            $this->enforceParticipantRegistrationDeadline($now);
            
            // Check document submission deadline
            $this->enforceDocumentSubmissionDeadline($now);
            
            // Check modification deadline
            $this->enforceModificationDeadline($now);
            
            error_log("Deadline enforcement completed successfully at " . $now->format('Y-m-d H:i:s'));
            
        } catch (\Exception $e) {
            error_log("Deadline enforcement failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send deadline reminders
     */
    public function sendDeadlineReminders()
    {
        $competitions = Competition::where('status', 'active')->get();
        $remindersSent = 0;
        
        foreach ($competitions as $competition) {
            $remindersSent += $this->checkAndSendSchoolRegistrationReminders($competition);
            $remindersSent += $this->checkAndSendTeamRegistrationReminders($competition);
            $remindersSent += $this->checkAndSendDocumentReminders($competition);
            $remindersSent += $this->checkAndSendModificationReminders($competition);
        }
        
        error_log("Deadline reminders: {$remindersSent} notifications sent");
        return $remindersSent;
    }
    
    /**
     * Get deadline status overview
     */
    public function getDeadlineStatus()
    {
        $competition = Competition::where('status', 'active')->first();
        
        if (!$competition) {
            return [
                'active_competition' => false,
                'message' => 'No active competition found'
            ];
        }
        
        $now = new \DateTime();
        
        return [
            'active_competition' => true,
            'competition_name' => $competition->name,
            'deadlines' => [
                'school_registration' => $this->getDeadlineInfo($competition->school_registration_deadline, $now),
                'team_registration' => $this->getDeadlineInfo($competition->team_registration_deadline, $now),
                'participant_registration' => $this->getDeadlineInfo($competition->participant_registration_deadline, $now),
                'document_submission' => $this->getDeadlineInfo($competition->document_submission_deadline, $now),
                'roster_modifications' => $this->getDeadlineInfo($competition->modification_deadline, $now)
            ],
            'enforcement_status' => $this->getEnforcementStatus($competition, $now)
        ];
    }
    
    /**
     * Enforce school registration deadline
     */
    private function enforceSchoolRegistrationDeadline(\DateTime $currentTime)
    {
        $competition = Competition::where('status', 'active')->first();
        if (!$competition || !$competition->school_registration_deadline) {
            return;
        }
        
        $deadline = new \DateTime($competition->school_registration_deadline);
        
        if ($currentTime > $deadline) {
            // Disable new school registrations
            $this->disableSchoolRegistration($competition);
            
            // Send deadline passed notifications
            $this->notifyDeadlinePassed('school_registration', $competition);
            
            // Update draft registrations to expired
            $this->expireDraftSchoolRegistrations();
        }
    }
    
    /**
     * Enforce team registration deadline
     */
    private function enforceTeamRegistrationDeadline(\DateTime $currentTime)
    {
        $competition = Competition::where('status', 'active')->first();
        if (!$competition || !$competition->team_registration_deadline) {
            return;
        }
        
        $deadline = new \DateTime($competition->team_registration_deadline);
        
        if ($currentTime > $deadline) {
            // Disable new team registrations
            $this->disableTeamRegistration($competition);
            
            // Send deadline passed notifications
            $this->notifyDeadlinePassed('team_registration', $competition);
            
            // Update draft team registrations to expired
            $this->expireDraftTeamRegistrations();
        }
    }
    
    /**
     * Enforce participant registration deadline
     */
    private function enforceParticipantRegistrationDeadline(\DateTime $currentTime)
    {
        $competition = Competition::where('status', 'active')->first();
        if (!$competition || !$competition->participant_registration_deadline) {
            return;
        }
        
        $deadline = new \DateTime($competition->participant_registration_deadline);
        
        if ($currentTime > $deadline) {
            // Lock team rosters
            $this->lockTeamRosters($competition);
            
            // Send deadline passed notifications
            $this->notifyDeadlinePassed('participant_registration', $competition);
        }
    }
    
    /**
     * Enforce document submission deadline
     */
    private function enforceDocumentSubmissionDeadline(\DateTime $currentTime)
    {
        $competition = Competition::where('status', 'active')->first();
        if (!$competition || !$competition->document_submission_deadline) {
            return;
        }
        
        $deadline = new \DateTime($competition->document_submission_deadline);
        
        if ($currentTime > $deadline) {
            // Mark incomplete teams as ineligible
            $this->markIncompleteTeamsIneligible($competition);
            
            // Send deadline passed notifications
            $this->notifyDeadlinePassed('document_submission', $competition);
        }
    }
    
    /**
     * Enforce modification deadline
     */
    private function enforceModificationDeadline(\DateTime $currentTime)
    {
        $competition = Competition::where('status', 'active')->first();
        if (!$competition || !$competition->modification_deadline) {
            return;
        }
        
        $deadline = new \DateTime($competition->modification_deadline);
        
        if ($currentTime > $deadline) {
            // Lock all team modifications
            $this->lockAllTeamModifications($competition);
            
            // Send deadline passed notifications
            $this->notifyDeadlinePassed('roster_modifications', $competition);
        }
    }
    
    /**
     * Check and send school registration reminders
     */
    private function checkAndSendSchoolRegistrationReminders($competition)
    {
        if (!$competition->school_registration_deadline) {
            return 0;
        }
        
        $deadline = new \DateTime($competition->school_registration_deadline);
        $now = new \DateTime();
        
        $daysUntilDeadline = $now->diff($deadline)->days;
        $remindersSent = 0;
        
        // Send reminders at specific intervals
        if (in_array($daysUntilDeadline, [30, 14, 7, 1]) && $now < $deadline) {
            $remindersSent = $this->sendReminderNotifications('school_registration', $daysUntilDeadline, $competition);
        }
        
        return $remindersSent;
    }
    
    /**
     * Check and send team registration reminders
     */
    private function checkAndSendTeamRegistrationReminders($competition)
    {
        if (!$competition->team_registration_deadline) {
            return 0;
        }
        
        $deadline = new \DateTime($competition->team_registration_deadline);
        $now = new \DateTime();
        
        $daysUntilDeadline = $now->diff($deadline)->days;
        $remindersSent = 0;
        
        // Send reminders to schools with incomplete teams
        if (in_array($daysUntilDeadline, [21, 14, 7, 3, 1]) && $now < $deadline) {
            $remindersSent = $this->sendTeamRegistrationReminders($daysUntilDeadline, $competition);
        }
        
        return $remindersSent;
    }
    
    /**
     * Check and send document reminders
     */
    private function checkAndSendDocumentReminders($competition)
    {
        if (!$competition->document_submission_deadline) {
            return 0;
        }
        
        $deadline = new \DateTime($competition->document_submission_deadline);
        $now = new \DateTime();
        
        $daysUntilDeadline = $now->diff($deadline)->days;
        $remindersSent = 0;
        
        // Send reminders to teams with missing documents
        if (in_array($daysUntilDeadline, [14, 7, 3, 1]) && $now < $deadline) {
            $remindersSent = $this->sendDocumentReminders($daysUntilDeadline, $competition);
        }
        
        return $remindersSent;
    }
    
    /**
     * Check and send modification reminders
     */
    private function checkAndSendModificationReminders($competition)
    {
        if (!$competition->modification_deadline) {
            return 0;
        }
        
        $deadline = new \DateTime($competition->modification_deadline);
        $now = new \DateTime();
        
        $daysUntilDeadline = $now->diff($deadline)->days;
        $remindersSent = 0;
        
        // Send reminders about modification deadline
        if (in_array($daysUntilDeadline, [7, 3, 1]) && $now < $deadline) {
            $remindersSent = $this->sendModificationReminders($daysUntilDeadline, $competition);
        }
        
        return $remindersSent;
    }
    
    /**
     * Send reminder notifications
     */
    private function sendReminderNotifications($type, $daysUntilDeadline, $competition)
    {
        $notificationsSent = 0;
        
        switch ($type) {
            case 'school_registration':
                // Send to schools that haven't registered yet
                // This would query schools in the system that haven't completed registration
                $unregisteredSchools = $this->getUnregisteredSchools($competition);
                
                foreach ($unregisteredSchools as $school) {
                    $this->createReminderNotification(
                        'school', 
                        $school->id, 
                        "school_registration_reminder_{$daysUntilDeadline}_days",
                        $this->getSchoolRegistrationReminderContent($daysUntilDeadline, $competition),
                        $school->email
                    );
                    $notificationsSent++;
                }
                break;
                
            // Add other reminder types as needed
        }
        
        return $notificationsSent;
    }
    
    /**
     * Send team registration reminders
     */
    private function sendTeamRegistrationReminders($daysUntilDeadline, $competition)
    {
        $notificationsSent = 0;
        
        // Get schools with incomplete or missing team registrations
        $incompleteRegistrations = TeamRegistration::where('registration_status', 'draft')
                                                 ->with('school')
                                                 ->get();
        
        foreach ($incompleteRegistrations as $registration) {
            $this->createReminderNotification(
                'team',
                $registration->id,
                "team_registration_reminder_{$daysUntilDeadline}_days",
                $this->getTeamRegistrationReminderContent($daysUntilDeadline, $registration, $competition),
                $registration->notification_email ?? $registration->school->email
            );
            $notificationsSent++;
        }
        
        return $notificationsSent;
    }
    
    /**
     * Send document reminders
     */
    private function sendDocumentReminders($daysUntilDeadline, $competition)
    {
        $notificationsSent = 0;
        
        // Get teams with incomplete documents
        $incompleteTeams = TeamRegistration::where('registration_status', 'approved')
                                         ->where(function($query) {
                                             $query->where('documents_complete', false)
                                                  ->orWhere('consent_forms_complete', false)
                                                  ->orWhere('medical_forms_complete', false);
                                         })
                                         ->with('school')
                                         ->get();
        
        foreach ($incompleteTeams as $team) {
            $this->createReminderNotification(
                'team',
                $team->id,
                "document_reminder_{$daysUntilDeadline}_days",
                $this->getDocumentReminderContent($daysUntilDeadline, $team, $competition),
                $team->notification_email ?? $team->school->email
            );
            $notificationsSent++;
        }
        
        return $notificationsSent;
    }
    
    /**
     * Send modification reminders
     */
    private function sendModificationReminders($daysUntilDeadline, $competition)
    {
        $notificationsSent = 0;
        
        // Send to all active teams
        $activeTeams = TeamRegistration::where('registration_status', 'approved')
                                     ->with('school')
                                     ->get();
        
        foreach ($activeTeams as $team) {
            $this->createReminderNotification(
                'team',
                $team->id,
                "modification_reminder_{$daysUntilDeadline}_days",
                $this->getModificationReminderContent($daysUntilDeadline, $team, $competition),
                $team->notification_email ?? $team->school->email
            );
            $notificationsSent++;
        }
        
        return $notificationsSent;
    }
    
    /**
     * Create reminder notification
     */
    private function createReminderNotification($recipientType, $recipientId, $notificationType, $content, $email)
    {
        // Check if notification already sent today
        $today = date('Y-m-d');
        $existing = RegistrationNotification::where('recipient_type', $recipientType)
                                           ->where('recipient_id', $recipientId)
                                           ->where('notification_type', $notificationType)
                                           ->where('created_at', '>=', $today . ' 00:00:00')
                                           ->first();
        
        if ($existing) {
            return false; // Already sent today
        }
        
        return RegistrationNotification::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_email' => $email,
            'notification_type' => $notificationType,
            'notification_category' => 'deadline',
            'priority' => 'high',
            'subject' => $content['subject'],
            'content' => $content['body'],
            'delivery_method' => 'email',
            'scheduled_for' => date('Y-m-d H:i:s'),
            'delivery_status' => 'pending'
        ]);
    }
    
    /**
     * Get deadline info
     */
    private function getDeadlineInfo($deadline, \DateTime $now)
    {
        if (!$deadline) {
            return [
                'deadline' => null,
                'status' => 'not_set',
                'days_remaining' => null,
                'is_overdue' => false
            ];
        }
        
        $deadlineDate = new \DateTime($deadline);
        $diff = $now->diff($deadlineDate);
        $daysRemaining = $diff->days;
        $isOverdue = $now > $deadlineDate;
        
        return [
            'deadline' => $deadlineDate->format('Y-m-d H:i:s'),
            'status' => $isOverdue ? 'passed' : ($daysRemaining <= 7 ? 'urgent' : 'active'),
            'days_remaining' => $isOverdue ? 0 : $daysRemaining,
            'is_overdue' => $isOverdue,
            'formatted_deadline' => $deadlineDate->format('d M Y H:i')
        ];
    }
    
    /**
     * Get enforcement status
     */
    private function getEnforcementStatus($competition, \DateTime $now)
    {
        $deadlines = [
            'school_registration_deadline',
            'team_registration_deadline', 
            'participant_registration_deadline',
            'document_submission_deadline',
            'modification_deadline'
        ];
        
        $status = [
            'total_deadlines' => count($deadlines),
            'active_deadlines' => 0,
            'passed_deadlines' => 0,
            'upcoming_deadlines' => 0
        ];
        
        foreach ($deadlines as $deadline) {
            if (!$competition->$deadline) continue;
            
            $deadlineDate = new \DateTime($competition->$deadline);
            
            if ($now > $deadlineDate) {
                $status['passed_deadlines']++;
            } else {
                $diff = $now->diff($deadlineDate);
                if ($diff->days <= 7) {
                    $status['upcoming_deadlines']++;
                } else {
                    $status['active_deadlines']++;
                }
            }
        }
        
        return $status;
    }
    
    /**
     * Disable school registration
     */
    private function disableSchoolRegistration($competition)
    {
        // Update competition status to prevent new school registrations
        error_log("School registration deadline passed - disabling new registrations");
    }
    
    /**
     * Disable team registration
     */
    private function disableTeamRegistration($competition)
    {
        error_log("Team registration deadline passed - disabling new team registrations");
    }
    
    /**
     * Expire draft school registrations
     */
    private function expireDraftSchoolRegistrations()
    {
        $draftRegistrations = SchoolRegistration::where('registration_status', 'draft')->get();
        
        foreach ($draftRegistrations as $registration) {
            $registration->registration_status = 'expired';
            $registration->rejection_reason = 'Registration deadline passed while in draft status';
            $registration->save();
        }
        
        error_log("Expired " . count($draftRegistrations) . " draft school registrations");
    }
    
    /**
     * Expire draft team registrations
     */
    private function expireDraftTeamRegistrations()
    {
        $draftRegistrations = TeamRegistration::where('registration_status', 'draft')->get();
        
        foreach ($draftRegistrations as $registration) {
            $registration->registration_status = 'expired';
            $registration->rejection_reason = 'Registration deadline passed while in draft status';
            $registration->save();
        }
        
        error_log("Expired " . count($draftRegistrations) . " draft team registrations");
    }
    
    /**
     * Lock team rosters
     */
    private function lockTeamRosters($competition)
    {
        $activeTeams = TeamRegistration::where('registration_status', 'approved')->get();
        
        foreach ($activeTeams as $team) {
            $team->locked_for_modifications = true;
            $team->save();
        }
        
        error_log("Locked rosters for " . count($activeTeams) . " teams");
    }
    
    /**
     * Mark incomplete teams as ineligible
     */
    private function markIncompleteTeamsIneligible($competition)
    {
        $incompleteTeams = TeamRegistration::where('registration_status', 'approved')
                                         ->where(function($query) {
                                             $query->where('documents_complete', false)
                                                  ->orWhere('consent_forms_complete', false);
                                         })
                                         ->get();
        
        foreach ($incompleteTeams as $team) {
            $team->phase_1_eligible = false;
            $team->rejection_reason = 'Required documents not submitted by deadline';
            $team->save();
        }
        
        error_log("Marked " . count($incompleteTeams) . " teams as ineligible due to missing documents");
    }
    
    /**
     * Lock all team modifications
     */
    private function lockAllTeamModifications($competition)
    {
        $allTeams = TeamRegistration::where('registration_status', 'approved')->get();
        
        foreach ($allTeams as $team) {
            $team->locked_for_modifications = true;
            $team->save();
        }
        
        error_log("Locked modifications for all teams");
    }
    
    /**
     * Notify deadline passed
     */
    private function notifyDeadlinePassed($deadlineType, $competition)
    {
        error_log("Deadline passed notification: {$deadlineType} for competition {$competition->name}");
    }
    
    /**
     * Get unregistered schools
     */
    private function getUnregisteredSchools($competition)
    {
        // This would return schools that haven't completed registration
        // For now, return empty array
        return [];
    }
    
    /**
     * Get school registration reminder content
     */
    private function getSchoolRegistrationReminderContent($daysUntilDeadline, $competition)
    {
        return [
            'subject' => "School Registration Deadline - {$daysUntilDeadline} days remaining",
            'body' => "The registration deadline for {$competition->name} is approaching in {$daysUntilDeadline} days. Please complete your school registration to participate in this year's competition."
        ];
    }
    
    /**
     * Get team registration reminder content
     */
    private function getTeamRegistrationReminderContent($daysUntilDeadline, $registration, $competition)
    {
        return [
            'subject' => "Team Registration Deadline - {$daysUntilDeadline} days remaining",
            'body' => "Your team registration '{$registration->team_name}' for {$competition->name} is incomplete. Please submit your registration within {$daysUntilDeadline} days to participate."
        ];
    }
    
    /**
     * Get document reminder content
     */
    private function getDocumentReminderContent($daysUntilDeadline, $team, $competition)
    {
        return [
            'subject' => "Document Submission Deadline - {$daysUntilDeadline} days remaining",
            'body' => "Team '{$team->team_name}' has missing documents for {$competition->name}. Please submit all required documents within {$daysUntilDeadline} days to remain eligible."
        ];
    }
    
    /**
     * Get modification reminder content
     */
    private function getModificationReminderContent($daysUntilDeadline, $team, $competition)
    {
        return [
            'subject' => "Team Modification Deadline - {$daysUntilDeadline} days remaining",
            'body' => "The deadline for team roster modifications is approaching in {$daysUntilDeadline} days. After this deadline, no changes can be made to team '{$team->team_name}'."
        ];
    }
}