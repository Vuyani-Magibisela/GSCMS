<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * Team Coach Model
 * Handles coach assignment and qualification management
 */
class TeamCoach extends BaseModel
{
    protected $table = 'team_coaches';
    protected $fillable = [
        'team_id',
        'coach_user_id',
        'coach_role',
        'status',
        'qualification_status',
        'training_completed',
        'training_completion_date',
        'certification_expiry',
        'background_check_status',
        'assigned_date',
        'removed_date',
        'removal_reason',
        'specialization',
        'experience_years',
        'previous_competitions',
        'performance_rating'
    ];
    
    protected $casts = [
        'training_completed' => 'boolean',
        'training_completion_date' => 'date',
        'certification_expiry' => 'date',
        'assigned_date' => 'date',
        'removed_date' => 'date',
        'experience_years' => 'integer',
        'previous_competitions' => 'integer',
        'performance_rating' => 'float'
    ];
    
    /**
     * Valid coach roles
     */
    const ROLES = [
        'primary' => 'Primary Coach',
        'secondary' => 'Secondary Coach',
        'assistant' => 'Assistant Coach',
        'mentor' => 'Mentor'
    ];
    
    /**
     * Valid coach statuses
     */
    const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'removed' => 'Removed',
        'pending_approval' => 'Pending Approval'
    ];
    
    /**
     * Valid qualification statuses
     */
    const QUALIFICATION_STATUSES = [
        'qualified' => 'Qualified',
        'pending' => 'Pending Verification',
        'unqualified' => 'Unqualified',
        'expired' => 'Expired'
    ];
    
    /**
     * Valid background check statuses
     */
    const BACKGROUND_CHECK_STATUSES = [
        'verified' => 'Verified',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'expired' => 'Expired'
    ];
    
    /**
     * Get team associated with this coach
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
    
    /**
     * Get user details for this coach
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }
    
    /**
     * Validate coach qualifications
     */
    public function validateQualifications()
    {
        $validationErrors = [];
        $user = $this->user();
        
        if (!$user) {
            return ['valid' => false, 'errors' => ['Coach user not found']];
        }
        
        // Check age requirement (minimum 21)
        $coachAge = $this->calculateAge($user->date_of_birth);
        if ($coachAge && $coachAge < 21) {
            $validationErrors[] = 'Coach must be at least 21 years old';
        }
        
        // Check background check status
        if ($this->background_check_status !== 'verified') {
            $validationErrors[] = 'Background check not verified';
        }
        
        // Check training completion
        if (!$this->training_completed) {
            $validationErrors[] = 'SciBOTICS coach certification not completed';
        }
        
        // Check certification expiry
        if ($this->certification_expiry && $this->certification_expiry < date('Y-m-d')) {
            $validationErrors[] = 'Coach certification has expired';
        }
        
        // Check school affiliation for primary coaches
        if ($this->coach_role === 'primary') {
            $team = $this->team();
            if ($team && $user->school_id !== $team->school_id) {
                $validationErrors[] = 'Primary coach must be affiliated with the same school as the team';
            }
        }
        
        // Check for conflicts (coach can't coach multiple teams in same category)
        $conflicts = $this->checkForConflicts();
        if (!empty($conflicts)) {
            $validationErrors = array_merge($validationErrors, $conflicts);
        }
        
        $isValid = empty($validationErrors);
        
        // Update qualification status
        $this->qualification_status = $isValid ? 'qualified' : 'unqualified';
        $this->save();
        
        return [
            'valid' => $isValid,
            'errors' => $validationErrors,
            'coach_id' => $this->id,
            'user_id' => $this->coach_user_id,
            'team_id' => $this->team_id
        ];
    }
    
    /**
     * Check for coaching conflicts
     */
    public function checkForConflicts()
    {
        $conflicts = [];
        $team = $this->team();
        
        if (!$team) {
            return $conflicts;
        }
        
        // Check if coaching multiple teams in same category
        $otherTeams = static::join('teams', 'team_coaches.team_id', '=', 'teams.id')
                           ->where('team_coaches.coach_user_id', $this->coach_user_id)
                           ->where('teams.category_id', $team->category_id)
                           ->where('team_coaches.status', 'active')
                           ->where('team_coaches.id', '!=', $this->id)
                           ->count();
        
        if ($otherTeams > 0) {
            $conflicts[] = 'Coach is already assigned to another team in the same category';
        }
        
        // Check if coach is also a judge (would be implemented with Judge model)
        // For now, we'll skip this check
        
        return $conflicts;
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
     * Assign coach to team with validation
     */
    public static function assignCoach($teamId, $userId, $role = 'primary')
    {
        // Validate role
        if (!array_key_exists($role, self::ROLES)) {
            throw new \Exception('Invalid coach role');
        }
        
        // Check if role is already filled
        if ($role === 'primary') {
            $existingPrimary = static::where('team_id', $teamId)
                                    ->where('coach_role', 'primary')
                                    ->where('status', 'active')
                                    ->first();
            
            if ($existingPrimary) {
                throw new \Exception('Team already has a primary coach');
            }
        }
        
        // Check maximum coach limit (2 coaches per team)
        $coachCount = static::where('team_id', $teamId)
                           ->where('status', 'active')
                           ->count();
        
        if ($coachCount >= 2) {
            throw new \Exception('Team already has maximum number of coaches');
        }
        
        // Check if user is already coaching this team
        $existingAssignment = static::where('team_id', $teamId)
                                   ->where('coach_user_id', $userId)
                                   ->where('status', 'active')
                                   ->first();
        
        if ($existingAssignment) {
            throw new \Exception('User is already assigned as a coach for this team');
        }
        
        // Create coach assignment
        $coach = new static([
            'team_id' => $teamId,
            'coach_user_id' => $userId,
            'coach_role' => $role,
            'status' => 'pending_approval',
            'qualification_status' => 'pending',
            'training_completed' => false,
            'background_check_status' => 'pending',
            'assigned_date' => date('Y-m-d'),
            'experience_years' => 0,
            'previous_competitions' => 0
        ]);
        
        $coach->save();
        
        // Validate qualifications
        $coach->validateQualifications();
        
        return $coach;
    }
    
    /**
     * Remove coach from team
     */
    public function removeFromTeam($reason = null)
    {
        $this->status = 'removed';
        $this->removed_date = date('Y-m-d');
        $this->removal_reason = $reason;
        $this->save();
        
        return $this;
    }
    
    /**
     * Update training status
     */
    public function completeTraining($completionDate = null)
    {
        $this->training_completed = true;
        $this->training_completion_date = $completionDate ?: date('Y-m-d');
        
        // Set certification expiry (1 year from completion)
        $this->certification_expiry = date('Y-m-d', strtotime('+1 year', strtotime($this->training_completion_date)));
        
        $this->save();
        
        // Re-validate qualifications
        $this->validateQualifications();
        
        return $this;
    }
    
    /**
     * Update background check status
     */
    public function updateBackgroundCheck($status)
    {
        if (!array_key_exists($status, self::BACKGROUND_CHECK_STATUSES)) {
            throw new \Exception('Invalid background check status');
        }
        
        $this->background_check_status = $status;
        $this->save();
        
        // Re-validate qualifications
        $this->validateQualifications();
        
        return $this;
    }
    
    /**
     * Approve coach assignment
     */
    public function approve()
    {
        // Validate qualifications before approval
        $validation = $this->validateQualifications();
        
        if (!$validation['valid']) {
            throw new \Exception('Cannot approve coach: ' . implode(', ', $validation['errors']));
        }
        
        $this->status = 'active';
        $this->save();
        
        return $this;
    }
    
    /**
     * Get coach performance summary
     */
    public function getPerformanceSummary()
    {
        $user = $this->user();
        
        return [
            'coach_id' => $this->id,
            'user_id' => $this->coach_user_id,
            'name' => $user ? $user->name : 'Unknown',
            'email' => $user ? $user->email : 'Unknown',
            'team_id' => $this->team_id,
            'role' => $this->coach_role,
            'status' => $this->status,
            'qualification_status' => $this->qualification_status,
            'training_completed' => $this->training_completed,
            'background_check_status' => $this->background_check_status,
            'assigned_date' => $this->assigned_date,
            'experience_years' => $this->experience_years,
            'previous_competitions' => $this->previous_competitions,
            'performance_rating' => $this->performance_rating,
            'specialization' => $this->specialization,
            'days_coaching' => $this->getDaysCoaching(),
            'certification_status' => $this->getCertificationStatus()
        ];
    }
    
    /**
     * Calculate days coaching
     */
    public function getDaysCoaching()
    {
        $startDate = new \DateTime($this->assigned_date);
        $endDate = $this->removed_date ? new \DateTime($this->removed_date) : new \DateTime();
        
        return $startDate->diff($endDate)->days;
    }
    
    /**
     * Get certification status
     */
    public function getCertificationStatus()
    {
        if (!$this->training_completed) {
            return 'not_completed';
        }
        
        if ($this->certification_expiry && $this->certification_expiry < date('Y-m-d')) {
            return 'expired';
        }
        
        return 'valid';
    }
    
    /**
     * Get active coaches for a team
     */
    public static function getActiveForTeam($teamId)
    {
        return static::where('team_id', $teamId)
                    ->where('status', 'active')
                    ->orderBy('coach_role', 'asc')
                    ->get();
    }
    
    /**
     * Get coaches needing training
     */
    public static function getNeedingTraining()
    {
        return static::where('training_completed', false)
                    ->where('status', '!=', 'removed')
                    ->get();
    }
    
    /**
     * Get coaches with expired certifications
     */
    public static function getExpiredCertifications()
    {
        return static::where('certification_expiry', '<', date('Y-m-d'))
                    ->where('training_completed', true)
                    ->where('status', 'active')
                    ->get();
    }
    
    /**
     * Get coaches pending approval
     */
    public static function getPendingApproval()
    {
        return static::where('status', 'pending_approval')
                    ->orderBy('assigned_date', 'asc')
                    ->get();
    }
    
    /**
     * Bulk validate qualifications for all coaches
     */
    public static function bulkValidateQualifications()
    {
        $coaches = static::where('status', '!=', 'removed')->get();
        $results = [];
        
        foreach ($coaches as $coach) {
            $results[] = $coach->validateQualifications();
        }
        
        return $results;
    }
    
    /**
     * Check if user can be assigned as coach
     */
    public static function canAssignUser($userId, $teamId, $role)
    {
        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            return ['can_assign' => false, 'reason' => 'User not found'];
        }
        
        // Check if already coaching this team
        $existing = static::where('team_id', $teamId)
                         ->where('coach_user_id', $userId)
                         ->where('status', 'active')
                         ->exists();
        
        if ($existing) {
            return ['can_assign' => false, 'reason' => 'User is already coaching this team'];
        }
        
        // Check role availability
        if ($role === 'primary') {
            $primaryExists = static::where('team_id', $teamId)
                                  ->where('coach_role', 'primary')
                                  ->where('status', 'active')
                                  ->exists();
            
            if ($primaryExists) {
                return ['can_assign' => false, 'reason' => 'Team already has a primary coach'];
            }
        }
        
        // Check maximum coaches limit
        $coachCount = static::where('team_id', $teamId)
                           ->where('status', 'active')
                           ->count();
        
        if ($coachCount >= 2) {
            return ['can_assign' => false, 'reason' => 'Team already has maximum number of coaches'];
        }
        
        return ['can_assign' => true, 'reason' => null];
    }
}