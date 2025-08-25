<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * Modification Approval Model
 * Handles approval workflow tracking for roster modifications
 */
class ModificationApproval extends BaseModel
{
    protected $table = 'modification_approvals';
    protected $fillable = [
        'modification_id',
        'approver_role',
        'approver_user_id',
        'approval_status',
        'approval_date',
        'conditions',
        'comments',
        'approval_order',
        'required_approval',
        'automatic_approval',
        'deadline',
        'notification_sent',
        'reminder_count',
        'last_reminder_sent',
        'approval_criteria'
    ];
    
    protected $casts = [
        'approval_date' => 'datetime',
        'deadline' => 'datetime',
        'last_reminder_sent' => 'datetime',
        'required_approval' => 'boolean',
        'automatic_approval' => 'boolean',
        'notification_sent' => 'boolean',
        'reminder_count' => 'integer',
        'approval_criteria' => 'json'
    ];
    
    /**
     * Valid approver roles
     */
    const APPROVER_ROLES = [
        'school_coordinator' => 'School Coordinator',
        'admin' => 'System Administrator',
        'competition_director' => 'Competition Director',
        'system' => 'Automatic System'
    ];
    
    /**
     * Valid approval statuses
     */
    const APPROVAL_STATUSES = [
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'conditionally_approved' => 'Conditionally Approved'
    ];
    
    /**
     * Get associated roster modification
     */
    public function modification()
    {
        return $this->belongsTo(RosterModification::class, 'modification_id');
    }
    
    /**
     * Get approver user
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
    
    /**
     * Process approval decision
     */
    public function processApproval($userId, $decision, $comments = null, $conditions = null)
    {
        // Validate decision
        if (!array_key_exists($decision, self::APPROVAL_STATUSES)) {
            throw new \Exception('Invalid approval decision');
        }
        
        // Check if user has authority to approve
        if (!$this->canUserApprove($userId)) {
            throw new \Exception('User does not have authority to process this approval');
        }
        
        // Update approval record
        $this->approval_status = $decision;
        $this->approval_date = date('Y-m-d H:i:s');
        $this->approver_user_id = $userId;
        $this->comments = $comments;
        $this->conditions = $conditions;
        $this->save();
        
        // Check if all required approvals are complete
        $this->checkOverallApprovalStatus();
        
        return $this;
    }
    
    /**
     * Check if user can approve this request
     */
    public function canUserApprove($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        
        // Check if already processed
        if ($this->approval_status !== 'pending') {
            return false;
        }
        
        // Check role-based permissions
        switch ($this->approver_role) {
            case 'school_coordinator':
                return $user->role === 'school_coordinator';
            case 'admin':
                return in_array($user->role, ['admin', 'super_admin']);
            case 'competition_director':
                return in_array($user->role, ['admin', 'super_admin', 'competition_director']);
            case 'system':
                return false; // System approvals are automatic
            default:
                return false;
        }
    }
    
    /**
     * Check overall approval status and update modification
     */
    private function checkOverallApprovalStatus()
    {
        $modification = $this->modification();
        if (!$modification) {
            return;
        }
        
        $allApprovals = static::where('modification_id', $this->modification_id)->get();
        
        // Check if any required approval was rejected
        $rejectedRequired = $allApprovals->where('required_approval', true)
                                       ->where('approval_status', 'rejected')
                                       ->count();
        
        if ($rejectedRequired > 0) {
            // If any required approval is rejected, reject the whole modification
            $modification->reject($this->approver_user_id, 'Required approval was rejected');
            return;
        }
        
        // Check if all required approvals are approved or conditionally approved
        $requiredApprovals = $allApprovals->where('required_approval', true);
        $approvedRequired = $requiredApprovals->whereIn('approval_status', ['approved', 'conditionally_approved'])->count();
        
        if ($approvedRequired >= $requiredApprovals->count()) {
            // All required approvals are in place
            $hasConditions = $allApprovals->where('approval_status', 'conditionally_approved')->count() > 0;
            
            $conditions = null;
            if ($hasConditions) {
                $conditions = $allApprovals->where('approval_status', 'conditionally_approved')
                                         ->pluck('conditions')
                                         ->filter()
                                         ->implode('; ');
            }
            
            $modification->approve($this->approver_user_id, $conditions);
        }
    }
    
    /**
     * Process automatic approval
     */
    public function processAutomatic()
    {
        if (!$this->automatic_approval) {
            return false;
        }
        
        // Check approval criteria
        if ($this->approval_criteria) {
            $meetsAllCriteria = $this->evaluateApprovalCriteria();
            if (!$meetsAllCriteria) {
                return false;
            }
        }
        
        $this->approval_status = 'approved';
        $this->approval_date = date('Y-m-d H:i:s');
        $this->comments = 'Automatic approval based on system criteria';
        $this->save();
        
        // Check overall approval status
        $this->checkOverallApprovalStatus();
        
        return true;
    }
    
    /**
     * Evaluate approval criteria for automatic approval
     */
    private function evaluateApprovalCriteria()
    {
        if (!$this->approval_criteria) {
            return true; // No criteria means automatic approval
        }
        
        $modification = $this->modification();
        if (!$modification) {
            return false;
        }
        
        $criteria = $this->approval_criteria;
        
        // Check each criterion
        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'max_team_size':
                    $composition = TeamComposition::where('team_id', $modification->team_id)->first();
                    if ($composition && $composition->current_participant_count > $value) {
                        return false;
                    }
                    break;
                case 'modification_type':
                    if (!in_array($modification->modification_type, (array)$value)) {
                        return false;
                    }
                    break;
                case 'priority_level':
                    if (!in_array($modification->priority, (array)$value)) {
                        return false;
                    }
                    break;
                case 'deadline_hours':
                    $hoursUntilDeadline = (strtotime($modification->deadline) - time()) / 3600;
                    if ($hoursUntilDeadline < $value) {
                        return false;
                    }
                    break;
                default:
                    // Unknown criteria - fail safe
                    return false;
            }
        }
        
        return true;
    }
    
    /**
     * Send approval notification
     */
    public function sendNotification()
    {
        if ($this->notification_sent) {
            return false;
        }
        
        // Get potential approvers based on role
        $potentialApprovers = $this->getPotentialApprovers();
        
        if (empty($potentialApprovers)) {
            return false;
        }
        
        $modification = $this->modification();
        if (!$modification) {
            return false;
        }
        
        // In a real implementation, this would send emails/notifications
        // For now, we'll just log it
        foreach ($potentialApprovers as $approver) {
            error_log("Approval notification sent to {$approver->email} for modification {$this->modification_id}");
        }
        
        $this->notification_sent = true;
        $this->save();
        
        return true;
    }
    
    /**
     * Send reminder notification
     */
    public function sendReminder()
    {
        if ($this->approval_status !== 'pending') {
            return false;
        }
        
        // Check if reminder is needed (every 24 hours, max 3 reminders)
        if ($this->reminder_count >= 3) {
            return false;
        }
        
        $lastReminderTime = $this->last_reminder_sent ? strtotime($this->last_reminder_sent) : 0;
        $hoursSinceLastReminder = (time() - $lastReminderTime) / 3600;
        
        if ($hoursSinceLastReminder < 24) {
            return false;
        }
        
        // Send reminder
        $potentialApprovers = $this->getPotentialApprovers();
        
        foreach ($potentialApprovers as $approver) {
            error_log("Approval reminder sent to {$approver->email} for modification {$this->modification_id}");
        }
        
        $this->reminder_count++;
        $this->last_reminder_sent = date('Y-m-d H:i:s');
        $this->save();
        
        return true;
    }
    
    /**
     * Get potential approvers for this approval
     */
    private function getPotentialApprovers()
    {
        $approvers = [];
        
        switch ($this->approver_role) {
            case 'school_coordinator':
                $modification = $this->modification();
                if ($modification) {
                    $team = $modification->team();
                    if ($team) {
                        $approvers = User::where('role', 'school_coordinator')
                                        ->where('school_id', $team->school_id)
                                        ->get();
                    }
                }
                break;
            case 'admin':
                $approvers = User::whereIn('role', ['admin', 'super_admin'])->get();
                break;
            case 'competition_director':
                $approvers = User::whereIn('role', ['admin', 'super_admin', 'competition_director'])->get();
                break;
        }
        
        return $approvers;
    }
    
    /**
     * Check for overdue approvals and escalate
     */
    public function checkAndEscalate()
    {
        if ($this->approval_status !== 'pending') {
            return false;
        }
        
        if (!$this->deadline || $this->deadline > date('Y-m-d H:i:s')) {
            return false; // Not overdue yet
        }
        
        // Escalate based on role
        $escalated = $this->escalateApproval();
        
        if ($escalated) {
            error_log("Escalated overdue approval {$this->id} for modification {$this->modification_id}");
        }
        
        return $escalated;
    }
    
    /**
     * Escalate approval to higher authority
     */
    private function escalateApproval()
    {
        switch ($this->approver_role) {
            case 'school_coordinator':
                // Escalate to admin
                $this->approver_role = 'admin';
                $this->deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $this->notification_sent = false;
                $this->reminder_count = 0;
                $this->save();
                
                $this->sendNotification();
                return true;
                
            case 'admin':
                // Escalate to competition director
                $this->approver_role = 'competition_director';
                $this->deadline = date('Y-m-d H:i:s', strtotime('+12 hours'));
                $this->notification_sent = false;
                $this->reminder_count = 0;
                $this->save();
                
                $this->sendNotification();
                return true;
                
            case 'competition_director':
                // Auto-approve if overdue at highest level
                $this->approval_status = 'approved';
                $this->approval_date = date('Y-m-d H:i:s');
                $this->comments = 'Auto-approved due to overdue escalation';
                $this->save();
                
                $this->checkOverallApprovalStatus();
                return true;
        }
        
        return false;
    }
    
    /**
     * Get approval summary
     */
    public function getSummary()
    {
        $approver = $this->approver();
        $modification = $this->modification();
        
        return [
            'id' => $this->id,
            'modification_id' => $this->modification_id,
            'modification_type' => $modification ? $modification->modification_type : null,
            'approver_role' => $this->approver_role,
            'approver_role_name' => self::APPROVER_ROLES[$this->approver_role] ?? 'Unknown',
            'approver_user' => $approver ? $approver->name : null,
            'approval_status' => $this->approval_status,
            'approval_status_name' => self::APPROVAL_STATUSES[$this->approval_status] ?? 'Unknown',
            'approval_date' => $this->approval_date,
            'approval_order' => $this->approval_order,
            'required_approval' => $this->required_approval,
            'automatic_approval' => $this->automatic_approval,
            'deadline' => $this->deadline,
            'is_overdue' => $this->deadline && $this->deadline < date('Y-m-d H:i:s') && $this->approval_status === 'pending',
            'conditions' => $this->conditions,
            'comments' => $this->comments,
            'reminder_count' => $this->reminder_count,
            'notification_sent' => $this->notification_sent
        ];
    }
    
    /**
     * Get pending approvals for user
     */
    public static function getPendingForUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return collect([]);
        }
        
        $query = static::where('approval_status', 'pending');
        
        // Filter based on user role
        switch ($user->role) {
            case 'school_coordinator':
                $query->where('approver_role', 'school_coordinator');
                // Additional filtering by school would be done here
                break;
            case 'admin':
            case 'super_admin':
                $query->whereIn('approver_role', ['admin', 'competition_director']);
                break;
            case 'competition_director':
                $query->where('approver_role', 'competition_director');
                break;
            default:
                return collect([]);
        }
        
        return $query->orderBy('deadline', 'asc')->get();
    }
    
    /**
     * Get overdue approvals
     */
    public static function getOverdue()
    {
        return static::where('approval_status', 'pending')
                    ->where('deadline', '<', date('Y-m-d H:i:s'))
                    ->orderBy('deadline', 'asc')
                    ->get();
    }
    
    /**
     * Process automatic approvals
     */
    public static function processAutomaticApprovals()
    {
        $automaticApprovals = static::where('approval_status', 'pending')
                                   ->where('automatic_approval', true)
                                   ->get();
        
        $processed = 0;
        
        foreach ($automaticApprovals as $approval) {
            if ($approval->processAutomatic()) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Send pending notifications
     */
    public static function sendPendingNotifications()
    {
        $pendingApprovals = static::where('approval_status', 'pending')
                                 ->where('notification_sent', false)
                                 ->get();
        
        $sent = 0;
        
        foreach ($pendingApprovals as $approval) {
            if ($approval->sendNotification()) {
                $sent++;
            }
        }
        
        return $sent;
    }
    
    /**
     * Send reminder notifications
     */
    public static function sendReminderNotifications()
    {
        $pendingApprovals = static::where('approval_status', 'pending')
                                 ->where('reminder_count', '<', 3)
                                 ->get();
        
        $sent = 0;
        
        foreach ($pendingApprovals as $approval) {
            if ($approval->sendReminder()) {
                $sent++;
            }
        }
        
        return $sent;
    }
    
    /**
     * Process overdue escalations
     */
    public static function processOverdueEscalations()
    {
        $overdueApprovals = static::getOverdue();
        
        $escalated = 0;
        
        foreach ($overdueApprovals as $approval) {
            if ($approval->checkAndEscalate()) {
                $escalated++;
            }
        }
        
        return $escalated;
    }
}