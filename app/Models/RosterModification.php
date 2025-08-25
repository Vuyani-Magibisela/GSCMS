<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * Roster Modification Model
 * Handles team roster change workflows and audit tracking
 */
class RosterModification extends BaseModel
{
    protected $table = 'roster_modifications';
    protected $fillable = [
        'team_id',
        'modification_type',
        'requested_by',
        'request_date',
        'current_status',
        'approved_by',
        'approved_date',
        'implemented_date',
        'implemented_by',
        'modification_details',
        'reason',
        'impact_assessment',
        'deadline',
        'priority',
        'conditions',
        'rejection_reason',
        'rollback_data',
        'notification_sent'
    ];
    
    protected $casts = [
        'modification_details' => 'json',
        'rollback_data' => 'json',
        'request_date' => 'datetime',
        'approved_date' => 'datetime',
        'implemented_date' => 'datetime',
        'deadline' => 'datetime',
        'notification_sent' => 'boolean'
    ];
    
    /**
     * Valid modification types
     */
    const MODIFICATION_TYPES = [
        'add_participant' => 'Add Participant',
        'remove_participant' => 'Remove Participant',
        'substitute_participant' => 'Substitute Participant',
        'change_coach' => 'Change Coach',
        'update_role' => 'Update Role'
    ];
    
    /**
     * Valid modification statuses
     */
    const STATUSES = [
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'implemented' => 'Implemented',
        'cancelled' => 'Cancelled'
    ];
    
    /**
     * Valid priority levels
     */
    const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent'
    ];
    
    /**
     * Get team associated with this modification
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
    
    /**
     * Get user who requested this modification
     */
    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    
    /**
     * Get user who approved this modification
     */
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    /**
     * Get user who implemented this modification
     */
    public function implementedByUser()
    {
        return $this->belongsTo(User::class, 'implemented_by');
    }
    
    /**
     * Get associated approvals
     */
    public function approvals()
    {
        return ModificationApproval::where('modification_id', $this->id)->get();
    }
    
    /**
     * Create new roster modification request
     */
    public static function createRequest($teamId, $modificationType, $requestedBy, $modificationDetails, $reason, $priority = 'normal')
    {
        // Validate modification type
        if (!array_key_exists($modificationType, self::MODIFICATION_TYPES)) {
            throw new \Exception('Invalid modification type');
        }
        
        // Validate priority
        if (!array_key_exists($priority, self::PRIORITIES)) {
            $priority = 'normal';
        }
        
        // Create the modification request
        $modification = new static([
            'team_id' => $teamId,
            'modification_type' => $modificationType,
            'requested_by' => $requestedBy,
            'request_date' => date('Y-m-d H:i:s'),
            'current_status' => 'pending',
            'modification_details' => $modificationDetails,
            'reason' => $reason,
            'priority' => $priority,
            'notification_sent' => false
        ]);
        
        // Set deadline based on priority and modification type
        $modification->setDeadline();
        
        // Perform impact assessment
        $modification->performImpactAssessment();
        
        $modification->save();
        
        // Create approval workflow
        $modification->createApprovalWorkflow();
        
        return $modification;
    }
    
    /**
     * Set deadline based on modification type and priority
     */
    private function setDeadline()
    {
        $baseHours = 72; // 3 days default
        
        // Adjust based on modification type
        switch ($this->modification_type) {
            case 'add_participant':
            case 'remove_participant':
                $baseHours = 48; // 2 days for participant changes
                break;
            case 'substitute_participant':
                $baseHours = 24; // 1 day for substitutions
                break;
            case 'change_coach':
                $baseHours = 120; // 5 days for coach changes
                break;
            case 'update_role':
                $baseHours = 24; // 1 day for role updates
                break;
        }
        
        // Adjust based on priority
        switch ($this->priority) {
            case 'urgent':
                $baseHours = min($baseHours / 2, 12); // Minimum 12 hours
                break;
            case 'high':
                $baseHours = $baseHours * 0.75;
                break;
            case 'low':
                $baseHours = $baseHours * 1.5;
                break;
        }
        
        $this->deadline = date('Y-m-d H:i:s', strtotime("+{$baseHours} hours"));
    }
    
    /**
     * Perform impact assessment
     */
    private function performImpactAssessment()
    {
        $team = $this->team();
        if (!$team) {
            $this->impact_assessment = 'Unable to assess impact: Team not found';
            return;
        }
        
        $assessment = [];
        
        switch ($this->modification_type) {
            case 'add_participant':
                $assessment = $this->assessParticipantAddition();
                break;
            case 'remove_participant':
                $assessment = $this->assessParticipantRemoval();
                break;
            case 'substitute_participant':
                $assessment = $this->assessParticipantSubstitution();
                break;
            case 'change_coach':
                $assessment = $this->assessCoachChange();
                break;
            case 'update_role':
                $assessment = $this->assessRoleUpdate();
                break;
        }
        
        $this->impact_assessment = json_encode($assessment);
    }
    
    /**
     * Assess impact of participant addition
     */
    private function assessParticipantAddition()
    {
        $composition = TeamComposition::where('team_id', $this->team_id)->first();
        $validation = $composition ? $composition->validateTeamSize() : null;
        
        return [
            'type' => 'participant_addition',
            'current_size' => $validation['current_count'] ?? 0,
            'max_allowed' => $validation['max_allowed'] ?? 4,
            'can_add' => $validation['can_add_more'] ?? false,
            'impact_level' => $validation['can_add_more'] ? 'low' : 'high',
            'concerns' => $validation['can_add_more'] ? [] : ['Team will exceed maximum size'],
            'requirements' => ['Document verification', 'Eligibility check', 'Parent consent']
        ];
    }
    
    /**
     * Assess impact of participant removal
     */
    private function assessParticipantRemoval()
    {
        $composition = TeamComposition::where('team_id', $this->team_id)->first();
        $validation = $composition ? $composition->validateTeamSize() : null;
        $participantId = $this->modification_details['participant_id'] ?? null;
        
        $concerns = [];
        if ($validation && ($validation['current_count'] - 1) < $validation['min_required']) {
            $concerns[] = 'Team will be below minimum size';
        }
        
        // Check if removing team leader
        if ($participantId) {
            $teamParticipant = TeamParticipant::where('team_id', $this->team_id)
                                             ->where('participant_id', $participantId)
                                             ->first();
            
            if ($teamParticipant && $teamParticipant->role === 'team_leader') {
                $concerns[] = 'Removing team leader - new leader must be appointed';
            }
        }
        
        return [
            'type' => 'participant_removal',
            'current_size' => $validation['current_count'] ?? 0,
            'min_required' => $validation['min_required'] ?? 2,
            'new_size' => ($validation['current_count'] ?? 0) - 1,
            'impact_level' => empty($concerns) ? 'medium' : 'high',
            'concerns' => $concerns,
            'requirements' => ['Documentation of reason', 'Parent notification']
        ];
    }
    
    /**
     * Assess impact of participant substitution
     */
    private function assessParticipantSubstitution()
    {
        return [
            'type' => 'participant_substitution',
            'impact_level' => 'medium',
            'concerns' => ['Substitute eligibility', 'Document completion'],
            'requirements' => ['Substitute verification', 'Document transfer', 'Parent consent']
        ];
    }
    
    /**
     * Assess impact of coach change
     */
    private function assessCoachChange()
    {
        $coachRole = $this->modification_details['coach_role'] ?? 'primary';
        
        $concerns = [];
        if ($coachRole === 'primary') {
            $concerns[] = 'Changing primary coach - continuity impact';
        }
        
        return [
            'type' => 'coach_change',
            'coach_role' => $coachRole,
            'impact_level' => $coachRole === 'primary' ? 'high' : 'medium',
            'concerns' => $concerns,
            'requirements' => ['New coach qualification check', 'Background verification', 'Training requirements']
        ];
    }
    
    /**
     * Assess impact of role update
     */
    private function assessRoleUpdate()
    {
        $newRole = $this->modification_details['new_role'] ?? '';
        
        $concerns = [];
        if ($newRole === 'team_leader') {
            $concerns[] = 'Leadership change - team dynamics impact';
        }
        
        return [
            'type' => 'role_update',
            'new_role' => $newRole,
            'impact_level' => $newRole === 'team_leader' ? 'medium' : 'low',
            'concerns' => $concerns,
            'requirements' => ['Role capability assessment']
        ];
    }
    
    /**
     * Create approval workflow
     */
    private function createApprovalWorkflow()
    {
        $approvalRoles = $this->getRequiredApprovalRoles();
        
        foreach ($approvalRoles as $index => $roleConfig) {
            ModificationApproval::create([
                'modification_id' => $this->id,
                'approver_role' => $roleConfig['role'],
                'approval_status' => 'pending',
                'approval_order' => $index + 1,
                'required_approval' => $roleConfig['required'],
                'automatic_approval' => $roleConfig['automatic'] ?? false,
                'deadline' => $this->deadline,
                'approval_criteria' => $roleConfig['criteria'] ?? null
            ]);
        }
    }
    
    /**
     * Get required approval roles based on modification type
     */
    private function getRequiredApprovalRoles()
    {
        $baseRoles = [
            ['role' => 'school_coordinator', 'required' => true, 'automatic' => false]
        ];
        
        switch ($this->modification_type) {
            case 'add_participant':
            case 'remove_participant':
                $baseRoles[] = ['role' => 'admin', 'required' => true, 'automatic' => false];
                break;
            case 'substitute_participant':
                // Only school coordinator approval needed for substitutions
                break;
            case 'change_coach':
                $baseRoles[] = ['role' => 'admin', 'required' => true, 'automatic' => false];
                $baseRoles[] = ['role' => 'competition_director', 'required' => true, 'automatic' => false];
                break;
            case 'update_role':
                // Only school coordinator approval needed for role updates
                break;
        }
        
        // Add automatic system approval for certain conditions
        if ($this->priority === 'low' && in_array($this->modification_type, ['update_role', 'substitute_participant'])) {
            $baseRoles[] = ['role' => 'system', 'required' => false, 'automatic' => true];
        }
        
        return $baseRoles;
    }
    
    /**
     * Approve modification
     */
    public function approve($approvedBy, $conditions = null)
    {
        // Check if all required approvals are in place
        $pendingApprovals = ModificationApproval::where('modification_id', $this->id)
                                               ->where('approval_status', 'pending')
                                               ->where('required_approval', true)
                                               ->count();
        
        if ($pendingApprovals > 0) {
            throw new \Exception('Cannot approve: pending required approvals remain');
        }
        
        $this->current_status = 'approved';
        $this->approved_by = $approvedBy;
        $this->approved_date = date('Y-m-d H:i:s');
        $this->conditions = $conditions;
        $this->save();
        
        // Trigger implementation if no conditions
        if (!$conditions) {
            $this->implement($approvedBy);
        }
        
        return $this;
    }
    
    /**
     * Reject modification
     */
    public function reject($rejectedBy, $reason)
    {
        $this->current_status = 'rejected';
        $this->approved_by = $rejectedBy; // For audit purposes
        $this->approved_date = date('Y-m-d H:i:s');
        $this->rejection_reason = $reason;
        $this->save();
        
        return $this;
    }
    
    /**
     * Implement approved modification
     */
    public function implement($implementedBy)
    {
        if ($this->current_status !== 'approved') {
            throw new \Exception('Cannot implement: modification not approved');
        }
        
        // Store rollback data before making changes
        $this->createRollbackData();
        
        // Implement the specific modification
        $success = $this->executeModification();
        
        if ($success) {
            $this->current_status = 'implemented';
            $this->implemented_by = $implementedBy;
            $this->implemented_date = date('Y-m-d H:i:s');
            $this->save();
        } else {
            throw new \Exception('Failed to implement modification');
        }
        
        return $this;
    }
    
    /**
     * Create rollback data
     */
    private function createRollbackData()
    {
        $rollbackData = [];
        
        switch ($this->modification_type) {
            case 'add_participant':
                // No rollback data needed for additions
                break;
            case 'remove_participant':
                $participantId = $this->modification_details['participant_id'] ?? null;
                if ($participantId) {
                    $teamParticipant = TeamParticipant::where('team_id', $this->team_id)
                                                     ->where('participant_id', $participantId)
                                                     ->first();
                    if ($teamParticipant) {
                        $rollbackData = $teamParticipant->toArray();
                    }
                }
                break;
            case 'substitute_participant':
                // Store both original and substitute data
                $rollbackData = [
                    'original_participant' => $this->modification_details['original_participant'] ?? null,
                    'substitute_participant' => $this->modification_details['substitute_participant'] ?? null
                ];
                break;
            case 'change_coach':
                $coachId = $this->modification_details['coach_id'] ?? null;
                if ($coachId) {
                    $coach = TeamCoach::find($coachId);
                    if ($coach) {
                        $rollbackData = $coach->toArray();
                    }
                }
                break;
            case 'update_role':
                $participantId = $this->modification_details['participant_id'] ?? null;
                if ($participantId) {
                    $teamParticipant = TeamParticipant::where('team_id', $this->team_id)
                                                     ->where('participant_id', $participantId)
                                                     ->first();
                    if ($teamParticipant) {
                        $rollbackData['original_role'] = $teamParticipant->role;
                    }
                }
                break;
        }
        
        $this->rollback_data = $rollbackData;
    }
    
    /**
     * Execute the specific modification
     */
    private function executeModification()
    {
        try {
            switch ($this->modification_type) {
                case 'add_participant':
                    return $this->executeParticipantAddition();
                case 'remove_participant':
                    return $this->executeParticipantRemoval();
                case 'substitute_participant':
                    return $this->executeParticipantSubstitution();
                case 'change_coach':
                    return $this->executeCoachChange();
                case 'update_role':
                    return $this->executeRoleUpdate();
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Modification execution failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute participant addition
     */
    private function executeParticipantAddition()
    {
        $participantId = $this->modification_details['participant_id'] ?? null;
        $role = $this->modification_details['role'] ?? 'regular';
        
        if (!$participantId) {
            return false;
        }
        
        $composition = TeamComposition::where('team_id', $this->team_id)->first();
        if ($composition) {
            $composition->addParticipant($participantId, $role);
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute participant removal
     */
    private function executeParticipantRemoval()
    {
        $participantId = $this->modification_details['participant_id'] ?? null;
        $reason = $this->modification_details['removal_reason'] ?? 'voluntary';
        
        if (!$participantId) {
            return false;
        }
        
        $composition = TeamComposition::where('team_id', $this->team_id)->first();
        if ($composition) {
            $composition->removeParticipant($participantId, $reason);
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute participant substitution
     */
    private function executeParticipantSubstitution()
    {
        $originalId = $this->modification_details['original_participant_id'] ?? null;
        $substituteId = $this->modification_details['substitute_participant_id'] ?? null;
        
        if (!$originalId || !$substituteId) {
            return false;
        }
        
        // Remove original and add substitute
        $composition = TeamComposition::where('team_id', $this->team_id)->first();
        if ($composition) {
            $originalParticipant = TeamParticipant::where('team_id', $this->team_id)
                                                 ->where('participant_id', $originalId)
                                                 ->first();
            
            if ($originalParticipant) {
                $originalRole = $originalParticipant->role;
                $composition->removeParticipant($originalId, 'substituted');
                $composition->addParticipant($substituteId, $originalRole);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Execute coach change
     */
    private function executeCoachChange()
    {
        $coachId = $this->modification_details['coach_id'] ?? null;
        $newUserId = $this->modification_details['new_user_id'] ?? null;
        $reason = $this->modification_details['change_reason'] ?? null;
        
        if (!$coachId || !$newUserId) {
            return false;
        }
        
        $coach = TeamCoach::find($coachId);
        if ($coach) {
            $role = $coach->coach_role;
            $coach->removeFromTeam($reason);
            
            // Assign new coach
            TeamCoach::assignCoach($this->team_id, $newUserId, $role);
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute role update
     */
    private function executeRoleUpdate()
    {
        $participantId = $this->modification_details['participant_id'] ?? null;
        $newRole = $this->modification_details['new_role'] ?? null;
        
        if (!$participantId || !$newRole) {
            return false;
        }
        
        $teamParticipant = TeamParticipant::where('team_id', $this->team_id)
                                         ->where('participant_id', $participantId)
                                         ->first();
        
        if ($teamParticipant) {
            $teamParticipant->updateRole($newRole);
            return true;
        }
        
        return false;
    }
    
    /**
     * Cancel modification
     */
    public function cancel($cancelledBy, $reason)
    {
        $this->current_status = 'cancelled';
        $this->approved_by = $cancelledBy; // For audit purposes
        $this->approved_date = date('Y-m-d H:i:s');
        $this->rejection_reason = $reason;
        $this->save();
        
        return $this;
    }
    
    /**
     * Get modification summary
     */
    public function getSummary()
    {
        $requester = $this->requestedByUser();
        $approver = $this->approvedByUser();
        $implementer = $this->implementedByUser();
        
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'type' => $this->modification_type,
            'type_name' => self::MODIFICATION_TYPES[$this->modification_type],
            'status' => $this->current_status,
            'status_name' => self::STATUSES[$this->current_status],
            'priority' => $this->priority,
            'priority_name' => self::PRIORITIES[$this->priority],
            'requested_by' => $requester ? $requester->name : 'Unknown',
            'request_date' => $this->request_date,
            'approved_by' => $approver ? $approver->name : null,
            'approved_date' => $this->approved_date,
            'implemented_by' => $implementer ? $implementer->name : null,
            'implemented_date' => $this->implemented_date,
            'reason' => $this->reason,
            'deadline' => $this->deadline,
            'is_overdue' => $this->deadline && $this->deadline < date('Y-m-d H:i:s') && $this->current_status === 'pending',
            'modification_details' => $this->modification_details,
            'impact_assessment' => json_decode($this->impact_assessment, true),
            'conditions' => $this->conditions,
            'rejection_reason' => $this->rejection_reason,
            'approval_summary' => $this->getApprovalSummary()
        ];
    }
    
    /**
     * Get approval workflow summary
     */
    public function getApprovalSummary()
    {
        $approvals = $this->approvals();
        
        $summary = [
            'total_approvals' => $approvals->count(),
            'pending_approvals' => $approvals->where('approval_status', 'pending')->count(),
            'approved_count' => $approvals->where('approval_status', 'approved')->count(),
            'rejected_count' => $approvals->where('approval_status', 'rejected')->count(),
            'required_approvals_remaining' => $approvals->where('approval_status', 'pending')->where('required_approval', true)->count(),
            'approvals' => $approvals->map(function($approval) {
                return [
                    'role' => $approval->approver_role,
                    'status' => $approval->approval_status,
                    'required' => $approval->required_approval,
                    'order' => $approval->approval_order
                ];
            })->toArray()
        ];
        
        return $summary;
    }
    
    /**
     * Get pending modifications
     */
    public static function getPending($teamId = null)
    {
        $query = static::where('current_status', 'pending');
        
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        return $query->orderBy('deadline', 'asc')
                    ->orderBy('priority', 'desc')
                    ->get();
    }
    
    /**
     * Get overdue modifications
     */
    public static function getOverdue()
    {
        return static::where('current_status', 'pending')
                    ->where('deadline', '<', date('Y-m-d H:i:s'))
                    ->orderBy('deadline', 'asc')
                    ->get();
    }
}