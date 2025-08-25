<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * Team Participant Model
 * Handles individual participant roles and status within teams
 */
class TeamParticipant extends BaseModel
{
    protected $table = 'team_participants';
    protected $fillable = [
        'team_id',
        'participant_id',
        'role',
        'status',
        'joined_date',
        'removed_date',
        'removal_reason',
        'specialization',
        'performance_notes',
        'eligibility_status',
        'documents_complete'
    ];
    
    protected $casts = [
        'joined_date' => 'date',
        'removed_date' => 'date',
        'documents_complete' => 'boolean'
    ];
    
    /**
     * Valid participant roles
     */
    const ROLES = [
        'team_leader' => 'Team Leader',
        'programmer' => 'Programmer',
        'builder' => 'Builder',
        'designer' => 'Designer',
        'researcher' => 'Researcher',
        'regular' => 'Regular Member'
    ];
    
    /**
     * Valid participant statuses
     */
    const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'removed' => 'Removed',
        'suspended' => 'Suspended',
        'substitute' => 'Substitute'
    ];
    
    /**
     * Valid removal reasons
     */
    const REMOVAL_REASONS = [
        'voluntary' => 'Voluntary Withdrawal',
        'academic' => 'Academic Issues',
        'disciplinary' => 'Disciplinary Action',
        'medical' => 'Medical Reasons',
        'transfer' => 'School Transfer',
        'other' => 'Other'
    ];
    
    /**
     * Get team associated with this participant
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
    
    /**
     * Get participant details
     */
    public function participant()
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }
    
    /**
     * Get team composition
     */
    public function teamComposition()
    {
        return TeamComposition::where('team_id', $this->team_id)->first();
    }
    
    /**
     * Check if participant is eligible for category
     */
    public function validateEligibility()
    {
        $participant = $this->participant();
        if (!$participant) {
            return ['valid' => false, 'reason' => 'Participant not found'];
        }
        
        $team = $this->team();
        if (!$team) {
            return ['valid' => false, 'reason' => 'Team not found'];
        }
        
        // Get category rules
        $category = Category::find($team->category_id);
        if (!$category) {
            return ['valid' => false, 'reason' => 'Category not found'];
        }
        
        $validationErrors = [];
        
        // Age validation
        if ($category->min_age || $category->max_age) {
            $participantAge = $this->calculateAge($participant->date_of_birth);
            
            if ($category->min_age && $participantAge < $category->min_age) {
                $validationErrors[] = "Participant is too young (minimum age: {$category->min_age})";
            }
            
            if ($category->max_age && $participantAge > $category->max_age) {
                $validationErrors[] = "Participant is too old (maximum age: {$category->max_age})";
            }
        }
        
        // Grade validation
        if ($category->min_grade || $category->max_grade) {
            if ($category->min_grade && $this->compareGrades($participant->grade, $category->min_grade) < 0) {
                $validationErrors[] = "Participant grade is too low (minimum grade: {$category->min_grade})";
            }
            
            if ($category->max_grade && $this->compareGrades($participant->grade, $category->max_grade) > 0) {
                $validationErrors[] = "Participant grade is too high (maximum grade: {$category->max_grade})";
            }
        }
        
        // Check for duplicate registrations in same category
        $duplicateRegistration = static::join('teams', 'team_participants.team_id', '=', 'teams.id')
                                       ->where('team_participants.participant_id', $this->participant_id)
                                       ->where('teams.category_id', $team->category_id)
                                       ->where('team_participants.status', 'active')
                                       ->where('team_participants.id', '!=', $this->id)
                                       ->exists();
        
        if ($duplicateRegistration) {
            $validationErrors[] = 'Participant is already registered in another team for this category';
        }
        
        $isValid = empty($validationErrors);
        
        // Update eligibility status
        $this->eligibility_status = $isValid ? 'eligible' : 'ineligible';
        $this->save();
        
        return [
            'valid' => $isValid,
            'errors' => $validationErrors,
            'participant_id' => $this->participant_id,
            'team_id' => $this->team_id
        ];
    }
    
    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dateOfBirth)
    {
        if (!$dateOfBirth) {
            return null;
        }
        
        $birthDate = new \DateTime($dateOfBirth);
        $currentDate = new \DateTime();
        
        return $birthDate->diff($currentDate)->y;
    }
    
    /**
     * Compare grades (handles R-12 system)
     */
    private function compareGrades($grade1, $grade2)
    {
        $gradeOrder = ['R', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        
        $pos1 = array_search($grade1, $gradeOrder);
        $pos2 = array_search($grade2, $gradeOrder);
        
        if ($pos1 === false || $pos2 === false) {
            return 0; // Unknown grades are considered equal
        }
        
        return $pos1 - $pos2;
    }
    
    /**
     * Update role with validation
     */
    public function updateRole($newRole)
    {
        if (!array_key_exists($newRole, self::ROLES)) {
            throw new \Exception('Invalid role specified');
        }
        
        // Check if role is already taken by another team member
        if ($newRole === 'team_leader') {
            $existingLeader = static::where('team_id', $this->team_id)
                                   ->where('role', 'team_leader')
                                   ->where('status', 'active')
                                   ->where('id', '!=', $this->id)
                                   ->first();
            
            if ($existingLeader) {
                throw new \Exception('Team already has a team leader');
            }
        }
        
        $this->role = $newRole;
        $this->save();
        
        return $this;
    }
    
    /**
     * Mark participant as removed
     */
    public function markAsRemoved($reason, $notes = null)
    {
        if (!array_key_exists($reason, self::REMOVAL_REASONS)) {
            throw new \Exception('Invalid removal reason');
        }
        
        $this->status = 'removed';
        $this->removed_date = date('Y-m-d');
        $this->removal_reason = $reason;
        
        if ($notes) {
            $this->performance_notes = ($this->performance_notes ? $this->performance_notes . "\n\n" : '') 
                                     . "Removed on " . date('Y-m-d') . ": " . $notes;
        }
        
        $this->save();
        
        // Update team composition validation
        $composition = $this->teamComposition();
        if ($composition) {
            $composition->validateTeamSize();
        }
        
        return $this;
    }
    
    /**
     * Reactivate participant
     */
    public function reactivate()
    {
        // Validate that team can accept this participant back
        $composition = $this->teamComposition();
        if ($composition) {
            $validation = $composition->validateTeamSize();
            if (!$validation['can_add_more']) {
                throw new \Exception('Cannot reactivate participant: team is at maximum capacity');
            }
        }
        
        $this->status = 'active';
        $this->removed_date = null;
        $this->removal_reason = null;
        $this->save();
        
        // Re-validate eligibility
        $this->validateEligibility();
        
        // Update team composition
        if ($composition) {
            $composition->validateTeamSize();
        }
        
        return $this;
    }
    
    /**
     * Update document completion status
     */
    public function updateDocumentStatus($isComplete)
    {
        $this->documents_complete = $isComplete;
        $this->save();
        
        // Update team composition validation
        $composition = $this->teamComposition();
        if ($composition) {
            $composition->validateTeamSize();
        }
        
        return $this;
    }
    
    /**
     * Get participant performance summary
     */
    public function getPerformanceSummary()
    {
        return [
            'participant_id' => $this->participant_id,
            'team_id' => $this->team_id,
            'role' => $this->role,
            'status' => $this->status,
            'joined_date' => $this->joined_date,
            'eligibility_status' => $this->eligibility_status,
            'documents_complete' => $this->documents_complete,
            'specialization' => $this->specialization,
            'performance_notes' => $this->performance_notes,
            'days_in_team' => $this->getDaysInTeam()
        ];
    }
    
    /**
     * Calculate days in team
     */
    public function getDaysInTeam()
    {
        $joinDate = new \DateTime($this->joined_date);
        $endDate = $this->removed_date ? new \DateTime($this->removed_date) : new \DateTime();
        
        return $joinDate->diff($endDate)->days;
    }
    
    /**
     * Get active participants for a team
     */
    public static function getActiveForTeam($teamId)
    {
        return static::where('team_id', $teamId)
                    ->where('status', 'active')
                    ->orderBy('joined_date', 'asc')
                    ->get();
    }
    
    /**
     * Get participants by role
     */
    public static function getByRole($teamId, $role)
    {
        return static::where('team_id', $teamId)
                    ->where('role', $role)
                    ->where('status', 'active')
                    ->get();
    }
    
    /**
     * Check if participant can be added to team
     */
    public static function canAddToTeam($participantId, $teamId)
    {
        // Check if already in team
        $existing = static::where('participant_id', $participantId)
                         ->where('team_id', $teamId)
                         ->where('status', 'active')
                         ->exists();
        
        if ($existing) {
            return ['can_add' => false, 'reason' => 'Participant already in team'];
        }
        
        // Check team capacity
        $composition = TeamComposition::where('team_id', $teamId)->first();
        if ($composition) {
            $validation = $composition->validateTeamSize();
            if (!$validation['can_add_more']) {
                return ['can_add' => false, 'reason' => 'Team is at maximum capacity'];
            }
        }
        
        // Check for conflicts in same category
        $team = Team::find($teamId);
        if ($team) {
            $conflictExists = static::join('teams', 'team_participants.team_id', '=', 'teams.id')
                                   ->where('team_participants.participant_id', $participantId)
                                   ->where('teams.category_id', $team->category_id)
                                   ->where('team_participants.status', 'active')
                                   ->exists();
            
            if ($conflictExists) {
                return ['can_add' => false, 'reason' => 'Participant already in another team for this category'];
            }
        }
        
        return ['can_add' => true, 'reason' => null];
    }
    
    /**
     * Bulk validate eligibility for team
     */
    public static function bulkValidateTeam($teamId)
    {
        $participants = static::getActiveForTeam($teamId);
        $results = [];
        
        foreach ($participants as $participant) {
            $results[] = $participant->validateEligibility();
        }
        
        return $results;
    }
}