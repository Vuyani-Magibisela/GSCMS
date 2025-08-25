<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Core\CategoryLimitValidator;
use PDO;

/**
 * Team Registration Model
 * Handles team registration with category limit enforcement
 */
class TeamRegistration extends BaseModel
{
    protected $table = 'team_registrations';
    protected $fillable = [
        'school_id',
        'category_id',
        'team_name',
        'team_code',
        'coach_primary_id',
        'coach_secondary_id',
        'coach_qualifications_verified',
        'participant_count',
        'min_participants',
        'max_participants',
        'team_captain_id',
        'registration_status',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'documents_complete',
        'consent_forms_complete',
        'medical_forms_complete',
        'eligibility_verified',
        'category_limit_validated',
        'duplicate_check_complete',
        'phase_1_eligible',
        'phase_3_qualified',
        'equipment_confirmed',
        'competition_objectives',
        'previous_experience',
        'special_requirements',
        'last_modified_at',
        'last_modified_by',
        'modification_count',
        'locked_for_modifications',
        'notification_email',
        'contact_phone',
        'preferred_communication'
    ];
    
    protected $casts = [
        'coach_qualifications_verified' => 'boolean',
        'documents_complete' => 'boolean',
        'consent_forms_complete' => 'boolean',
        'medical_forms_complete' => 'boolean',
        'eligibility_verified' => 'boolean',
        'category_limit_validated' => 'boolean',
        'duplicate_check_complete' => 'boolean',
        'phase_1_eligible' => 'boolean',
        'phase_3_qualified' => 'boolean',
        'equipment_confirmed' => 'boolean',
        'locked_for_modifications' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'last_modified_at' => 'datetime'
    ];
    
    /**
     * Valid registration statuses
     */
    const STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'active' => 'Active',
        'withdrawn' => 'Withdrawn'
    ];
    
    /**
     * Valid communication preferences
     */
    const COMMUNICATION_PREFERENCES = [
        'email' => 'Email Only',
        'sms' => 'SMS Only',
        'both' => 'Email and SMS'
    ];
    
    /**
     * Get school
     */
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
    
    /**
     * Get category
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    /**
     * Get primary coach
     */
    public function primaryCoach()
    {
        return $this->belongsTo(User::class, 'coach_primary_id');
    }
    
    /**
     * Get secondary coach
     */
    public function secondaryCoach()
    {
        return $this->belongsTo(User::class, 'coach_secondary_id');
    }
    
    /**
     * Get team captain
     */
    public function teamCaptain()
    {
        return $this->belongsTo(Participant::class, 'team_captain_id');
    }
    
    /**
     * Get approving user
     */
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    /**
     * Get last modifier
     */
    public function lastModifiedByUser()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }
    
    /**
     * Get team participants
     */
    public function participants()
    {
        return TeamParticipant::where('team_id', $this->id)
                            ->where('status', 'active')
                            ->with('participant')
                            ->get();
    }
    
    /**
     * Create new team registration
     */
    public static function createRegistration($schoolId, $categoryId, $teamData, $createdBy)
    {
        // Validate category limit
        $validator = new CategoryLimitValidator();
        $validation = $validator->validateNewTeamRegistration($schoolId, $categoryId);
        
        if (!$validation['can_register']) {
            throw new \Exception('Cannot register team: ' . ($validation['violation_reason'] ?? 'Registration not allowed'));
        }
        
        // Validate required data
        $requiredFields = ['team_name', 'coach_primary_id'];
        foreach ($requiredFields as $field) {
            if (empty($teamData[$field])) {
                throw new \Exception("Required field '{$field}' is missing");
            }
        }
        
        // Check for duplicate team name within school
        $existingTeam = static::where('school_id', $schoolId)
                            ->where('team_name', $teamData['team_name'])
                            ->where('registration_status', '!=', 'withdrawn')
                            ->first();
        
        if ($existingTeam) {
            throw new \Exception('Team name already exists for this school');
        }
        
        // Get category details for defaults
        $category = Category::find($categoryId);
        if (!$category) {
            throw new \Exception('Category not found');
        }
        
        $registrationData = array_merge([
            'school_id' => $schoolId,
            'category_id' => $categoryId,
            'registration_status' => 'draft',
            'min_participants' => $category->min_participants ?? 2,
            'max_participants' => $category->max_participants ?? 4,
            'participant_count' => 0,
            'modification_count' => 0,
            'preferred_communication' => 'email'
        ], $teamData);
        
        $registration = new static($registrationData);
        $registration->save();
        
        // Perform initial validations
        $registration->validateCategoryLimit();
        
        return $registration;
    }
    
    /**
     * Submit registration for approval
     */
    public function submitForApproval()
    {
        if ($this->registration_status !== 'draft') {
            throw new \Exception('Only draft registrations can be submitted');
        }
        
        // Validate completeness
        $validation = $this->validateCompleteness();
        if (!$validation['complete']) {
            throw new \Exception('Registration incomplete: ' . implode(', ', $validation['missing_requirements']));
        }
        
        // Perform comprehensive validations
        $this->performPreSubmissionValidation();
        
        $this->registration_status = 'submitted';
        $this->submitted_at = date('Y-m-d H:i:s');
        $this->save();
        
        // Send submission confirmation
        $this->sendSubmissionConfirmation();
        
        return $this;
    }
    
    /**
     * Approve team registration
     */
    public function approve($approvedBy, $notes = null)
    {
        if (!in_array($this->registration_status, ['submitted', 'under_review'])) {
            throw new \Exception('Only submitted or under review registrations can be approved');
        }
        
        // Final validation before approval
        $this->performFinalValidation();
        
        $this->registration_status = 'approved';
        $this->approved_by = $approvedBy;
        $this->approved_at = date('Y-m-d H:i:s');
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->phase_1_eligible = true; // Approved teams are eligible for Phase 1
        $this->save();
        
        // Create actual team record
        $teamId = $this->createTeamRecord();
        
        // Send approval notification
        $this->sendApprovalNotification($teamId);
        
        return $teamId;
    }
    
    /**
     * Reject team registration
     */
    public function reject($rejectedBy, $reason)
    {
        if (!in_array($this->registration_status, ['submitted', 'under_review'])) {
            throw new \Exception('Only submitted or under review registrations can be rejected');
        }
        
        $this->registration_status = 'rejected';
        $this->approved_by = $rejectedBy; // For audit trail
        $this->rejection_reason = $reason;
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->save();
        
        // Send rejection notification
        $this->sendRejectionNotification();
        
        return $this;
    }
    
    /**
     * Add participant to team
     */
    public function addParticipant($participantId, $role = 'regular', $addedBy = null)
    {
        if ($this->locked_for_modifications) {
            throw new \Exception('Team roster is locked for modifications');
        }
        
        // Validate participant eligibility
        $validator = new CategoryLimitValidator();
        $eligibility = $validator->validateParticipantEligibility($participantId, $this->category_id);
        
        if (!$eligibility['eligible']) {
            $reasons = array_keys(array_filter($eligibility['reasons']));
            throw new \Exception('Participant not eligible: ' . implode(', ', $reasons));
        }
        
        // Check team size limits
        if ($this->participant_count >= $this->max_participants) {
            throw new \Exception("Team is full (max {$this->max_participants} participants)");
        }
        
        // Check for duplicate participant
        $existing = TeamParticipant::where('team_id', $this->id)
                                 ->where('participant_id', $participantId)
                                 ->where('status', 'active')
                                 ->first();
        
        if ($existing) {
            throw new \Exception('Participant is already in this team');
        }
        
        // If role is team_leader, remove existing leader
        if ($role === 'team_leader') {
            TeamParticipant::where('team_id', $this->id)
                          ->where('role', 'team_leader')
                          ->update(['role' => 'regular']);
        }
        
        // Create team participant record
        $teamParticipant = TeamParticipant::create([
            'team_id' => $this->id,
            'participant_id' => $participantId,
            'role' => $role,
            'status' => 'active',
            'added_by' => $addedBy,
            'added_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update team stats
        $this->updateParticipantCount();
        $this->recordModification($addedBy, 'add_participant');
        
        // Update team captain if role is team_leader
        if ($role === 'team_leader') {
            $this->team_captain_id = $participantId;
            $this->save();
        }
        
        return $teamParticipant;
    }
    
    /**
     * Remove participant from team
     */
    public function removeParticipant($participantId, $reason = 'voluntary', $removedBy = null)
    {
        if ($this->locked_for_modifications) {
            throw new \Exception('Team roster is locked for modifications');
        }
        
        $teamParticipant = TeamParticipant::where('team_id', $this->id)
                                        ->where('participant_id', $participantId)
                                        ->where('status', 'active')
                                        ->first();
        
        if (!$teamParticipant) {
            throw new \Exception('Participant not found in team');
        }
        
        // Check minimum participant requirement
        if (($this->participant_count - 1) < $this->min_participants) {
            throw new \Exception("Cannot remove participant: team would be below minimum size ({$this->min_participants})");
        }
        
        // Remove participant
        $teamParticipant->status = 'removed';
        $teamParticipant->removal_reason = $reason;
        $teamParticipant->removed_by = $removedBy;
        $teamParticipant->removed_at = date('Y-m-d H:i:s');
        $teamParticipant->save();
        
        // If removing team captain, clear captain designation
        if ($this->team_captain_id == $participantId) {
            $this->team_captain_id = null;
            $this->save();
        }
        
        // Update team stats
        $this->updateParticipantCount();
        $this->recordModification($removedBy, 'remove_participant');
        
        return $teamParticipant;
    }
    
    /**
     * Validate registration completeness
     */
    public function validateCompleteness()
    {
        $missingRequirements = [];
        $warnings = [];
        
        // Required fields
        if (empty($this->team_name)) {
            $missingRequirements[] = 'Team name';
        }
        
        if (empty($this->coach_primary_id)) {
            $missingRequirements[] = 'Primary coach';
        }
        
        if ($this->participant_count < $this->min_participants) {
            $missingRequirements[] = "Minimum {$this->min_participants} participants (currently: {$this->participant_count})";
        }
        
        if (!$this->documents_complete) {
            $missingRequirements[] = 'Required documents';
        }
        
        if (!$this->consent_forms_complete) {
            $missingRequirements[] = 'Participant consent forms';
        }
        
        // Warnings for recommended items
        if (empty($this->coach_secondary_id)) {
            $warnings[] = 'No secondary coach assigned';
        }
        
        if (empty($this->team_captain_id)) {
            $warnings[] = 'No team captain designated';
        }
        
        if (!$this->coach_qualifications_verified) {
            $warnings[] = 'Coach qualifications not verified';
        }
        
        return [
            'complete' => empty($missingRequirements),
            'missing_requirements' => $missingRequirements,
            'warnings' => $warnings,
            'completion_percentage' => $this->calculateCompletionPercentage()
        ];
    }
    
    /**
     * Calculate completion percentage
     */
    public function calculateCompletionPercentage()
    {
        $totalChecks = 8;
        $completedChecks = 0;
        
        if (!empty($this->team_name)) $completedChecks++;
        if (!empty($this->coach_primary_id)) $completedChecks++;
        if ($this->participant_count >= $this->min_participants) $completedChecks++;
        if ($this->documents_complete) $completedChecks++;
        if ($this->consent_forms_complete) $completedChecks++;
        if ($this->medical_forms_complete) $completedChecks++;
        if ($this->eligibility_verified) $completedChecks++;
        if ($this->category_limit_validated) $completedChecks++;
        
        return round(($completedChecks / $totalChecks) * 100);
    }
    
    /**
     * Validate category limit compliance
     */
    public function validateCategoryLimit()
    {
        $validator = new CategoryLimitValidator();
        $validation = $validator->validateNewTeamRegistration($this->school_id, $this->category_id);
        
        if (!$validation['can_register'] && !$this->id) {
            // New registration that violates limit
            $this->category_limit_validated = false;
            return false;
        }
        
        $this->category_limit_validated = true;
        $this->save();
        
        return true;
    }
    
    /**
     * Perform pre-submission validation
     */
    private function performPreSubmissionValidation()
    {
        // Validate category limit
        if (!$this->validateCategoryLimit()) {
            throw new \Exception('Category limit validation failed');
        }
        
        // Validate all participants
        $participants = $this->participants();
        $validator = new CategoryLimitValidator();
        
        foreach ($participants as $teamParticipant) {
            $eligibility = $validator->validateParticipantEligibility(
                $teamParticipant->participant_id, 
                $this->category_id
            );
            
            if (!$eligibility['eligible']) {
                throw new \Exception("Participant {$teamParticipant->participant->name} is not eligible");
            }
        }
        
        $this->eligibility_verified = true;
        $this->duplicate_check_complete = true;
        $this->save();
    }
    
    /**
     * Perform final validation before approval
     */
    private function performFinalValidation()
    {
        // Re-run all validations
        $this->performPreSubmissionValidation();
        
        // Additional final checks
        if (!$this->documents_complete) {
            throw new \Exception('Documents not complete');
        }
        
        if (!$this->consent_forms_complete) {
            throw new \Exception('Consent forms not complete');
        }
    }
    
    /**
     * Update participant count
     */
    private function updateParticipantCount()
    {
        $count = TeamParticipant::where('team_id', $this->id)
                              ->where('status', 'active')
                              ->count();
        
        $this->participant_count = $count;
        $this->save();
    }
    
    /**
     * Record modification
     */
    private function recordModification($modifiedBy, $action)
    {
        $this->modification_count++;
        $this->last_modified_by = $modifiedBy;
        $this->last_modified_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Create actual team record from approved registration
     */
    private function createTeamRecord()
    {
        $teamData = [
            'school_id' => $this->school_id,
            'category_id' => $this->category_id,
            'name' => $this->team_name,
            'code' => $this->team_code,
            'status' => 'active'
        ];
        
        $team = Team::create($teamData);
        
        // Transfer participants to actual team
        $participants = TeamParticipant::where('team_id', $this->id)
                                     ->where('status', 'active')
                                     ->get();
        
        foreach ($participants as $participant) {
            // Create team participant in actual team
            TeamParticipant::create([
                'team_id' => $team->id,
                'participant_id' => $participant->participant_id,
                'role' => $participant->role,
                'status' => 'active'
            ]);
        }
        
        return $team->id;
    }
    
    /**
     * Send submission confirmation
     */
    private function sendSubmissionConfirmation()
    {
        error_log("Team registration submitted: {$this->team_name} (ID: {$this->id})");
    }
    
    /**
     * Send approval notification
     */
    private function sendApprovalNotification($teamId)
    {
        error_log("Team registration approved: {$this->team_name} (Team ID: {$teamId})");
    }
    
    /**
     * Send rejection notification
     */
    private function sendRejectionNotification()
    {
        error_log("Team registration rejected: {$this->team_name} - Reason: {$this->rejection_reason}");
    }
    
    /**
     * Get team registration summary
     */
    public function getSummary()
    {
        return [
            'id' => $this->id,
            'team_name' => $this->team_name,
            'team_code' => $this->team_code,
            'school_name' => $this->school->name ?? 'Unknown',
            'category_name' => $this->category->name ?? 'Unknown',
            'registration_status' => $this->registration_status,
            'status_name' => self::STATUSES[$this->registration_status] ?? 'Unknown',
            'participant_count' => $this->participant_count,
            'min_participants' => $this->min_participants,
            'max_participants' => $this->max_participants,
            'completion_percentage' => $this->calculateCompletionPercentage(),
            'primary_coach' => $this->primaryCoach->name ?? 'Not assigned',
            'team_captain' => $this->teamCaptain->name ?? 'Not designated',
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'documents_complete' => $this->documents_complete,
            'consent_forms_complete' => $this->consent_forms_complete,
            'phase_1_eligible' => $this->phase_1_eligible
        ];
    }
    
    /**
     * Get pending team registrations
     */
    public static function getPending()
    {
        return static::where('registration_status', 'submitted')
                    ->with(['school', 'category', 'primaryCoach'])
                    ->orderBy('submitted_at', 'asc')
                    ->get();
    }
    
    /**
     * Get teams for school
     */
    public static function getForSchool($schoolId)
    {
        return static::where('school_id', $schoolId)
                    ->with(['category', 'primaryCoach', 'teamCaptain'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }
}