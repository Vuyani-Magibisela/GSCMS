<?php

namespace App\Core;

use App\Models\TeamComposition;
use App\Models\TeamParticipant;
use App\Models\TeamCoach;
use App\Models\Team;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Competition;

/**
 * Team Composition Validator
 * Comprehensive validation for team composition with business rules
 */
class TeamCompositionValidator
{
    /**
     * Team size validation rules (pilot 2025 rules vs future system rules)
     */
    const TEAM_SIZE_VALIDATION = [
        'pilot_2025_rules' => [
            'max_participants' => 4,
            'min_participants' => 2,
            'team_composition' => '4_participants_plus_1_robot',
            'validation_points' => ['registration', 'modification', 'competition_day']
        ],
        'future_system_rules' => [
            'max_participants' => 6,
            'category_specific_limits' => [
                'junior_categories' => 4,
                'explorer_categories' => 4,
                'arduino_categories' => 4,
                'inventor_categories' => 6
            ],
            'dynamic_configuration' => 'admin_configurable_per_competition'
        ]
    ];
    
    /**
     * Validation contexts
     */
    const VALIDATION_CONTEXTS = [
        'registration' => 'Team Registration',
        'modification' => 'Team Modification',
        'competition_day' => 'Competition Day Check',
        'bulk_import' => 'Bulk Import Verification',
        'real_time' => 'Real-time Validation'
    ];
    
    private $errors = [];
    private $warnings = [];
    
    /**
     * Validate complete team composition
     */
    public function validateTeamComposition($teamId, $context = 'real_time')
    {
        $this->errors = [];
        $this->warnings = [];
        
        // Get team and related data
        $team = Team::find($teamId);
        if (!$team) {
            $this->addError('team', 'Team not found');
            return $this->getValidationResult();
        }
        
        $composition = TeamComposition::where('team_id', $teamId)->first();
        if (!$composition) {
            $this->addError('composition', 'Team composition record not found');
            return $this->getValidationResult();
        }
        
        // Perform all validations
        $this->validateTeamSize($teamId, $team->category_id, $team->competition_id, $context);
        $this->validateParticipantEligibility($teamId, $context);
        $this->validateCoachAssignments($teamId, $context);
        $this->validateDocumentCompletion($teamId, $context);
        $this->validateCategorySpecificRules($teamId, $context);
        $this->validateCompetitionRules($teamId, $context);
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate team size against rules
     */
    public function validateTeamSize($teamId, $categoryId, $competitionId, $context = 'real_time')
    {
        // Get competition and category specific rules
        $competition = Competition::find($competitionId);
        $category = Category::find($categoryId);
        
        // Get current team participants
        $currentParticipants = TeamParticipant::where('team_id', $teamId)
                                             ->where('status', 'active')
                                             ->count();
        
        // Determine applicable rules
        $rules = $this->getApplicableTeamSizeRules($competition, $category, $context);
        
        $maxAllowed = $rules['max_participants'];
        $minRequired = $rules['min_participants'];
        
        // Validate team size
        if ($currentParticipants < $minRequired) {
            $this->addError('team_size', "Team has {$currentParticipants} participants but requires minimum {$minRequired}");
        }
        
        if ($currentParticipants > $maxAllowed) {
            $this->addError('team_size', "Team has {$currentParticipants} participants but maximum allowed is {$maxAllowed}");
        }
        
        // Context-specific validations
        switch ($context) {
            case 'competition_day':
                if ($currentParticipants === 0) {
                    $this->addError('team_size', 'Team has no active participants for competition day');
                }
                break;
            case 'registration':
                if ($currentParticipants < $minRequired) {
                    $this->addWarning('team_size', 'Team registration incomplete - add more participants before deadline');
                }
                break;
        }
        
        return [
            'is_valid' => $currentParticipants >= $minRequired && $currentParticipants <= $maxAllowed,
            'current_count' => $currentParticipants,
            'max_allowed' => $maxAllowed,
            'min_required' => $minRequired,
            'can_add_more' => $currentParticipants < $maxAllowed,
            'slots_available' => $maxAllowed - $currentParticipants
        ];
    }
    
    /**
     * Get applicable team size rules based on competition and category
     */
    private function getApplicableTeamSizeRules($competition, $category, $context)
    {
        // Default to pilot 2025 rules
        $baseRules = self::TEAM_SIZE_VALIDATION['pilot_2025_rules'];
        
        // Check if competition has specific rules
        if ($competition && isset($competition->team_size_rules)) {
            $competitionRules = json_decode($competition->team_size_rules, true);
            if ($competitionRules) {
                $baseRules = array_merge($baseRules, $competitionRules);
            }
        }
        
        // Apply category-specific overrides
        if ($category && $category->team_size) {
            $baseRules['max_participants'] = $category->team_size;
        }
        
        // Apply context-specific adjustments
        if ($context === 'competition_day') {
            // More strict validation for competition day
            $baseRules['min_participants'] = max($baseRules['min_participants'], 2);
        }
        
        return $baseRules;
    }
    
    /**
     * Validate participant eligibility
     */
    public function validateParticipantEligibility($teamId, $context = 'real_time')
    {
        $participants = TeamParticipant::getActiveForTeam($teamId);
        
        foreach ($participants as $teamParticipant) {
            $validation = $teamParticipant->validateEligibility();
            
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $this->addError('participant_eligibility', 
                        "Participant {$teamParticipant->participant_id}: {$error}");
                }
            }
            
            // Additional context-specific checks
            if ($context === 'competition_day') {
                if ($teamParticipant->eligibility_status !== 'eligible') {
                    $this->addError('competition_readiness', 
                        "Participant {$teamParticipant->participant_id} eligibility not confirmed");
                }
            }
        }
    }
    
    /**
     * Validate coach assignments
     */
    public function validateCoachAssignments($teamId, $context = 'real_time')
    {
        $coaches = TeamCoach::getActiveForTeam($teamId);
        
        if ($coaches->isEmpty()) {
            $this->addError('coach_assignment', 'Team has no assigned coaches');
            return;
        }
        
        // Check for primary coach
        $primaryCoach = $coaches->where('coach_role', 'primary')->first();
        if (!$primaryCoach) {
            $this->addError('coach_assignment', 'Team must have a primary coach');
        }
        
        // Validate each coach
        foreach ($coaches as $coach) {
            $validation = $coach->validateQualifications();
            
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $this->addError('coach_qualification', 
                        "Coach {$coach->coach_user_id}: {$error}");
                }
            }
            
            // Context-specific coach validation
            if ($context === 'competition_day') {
                if ($coach->qualification_status !== 'qualified') {
                    $this->addError('competition_readiness', 
                        "Coach {$coach->coach_user_id} qualification not confirmed");
                }
                
                if ($coach->background_check_status !== 'verified') {
                    $this->addError('competition_readiness', 
                        "Coach {$coach->coach_user_id} background check not verified");
                }
            }
        }
        
        // Check coach limits (maximum 2 coaches per team)
        if ($coaches->count() > 2) {
            $this->addError('coach_assignment', 'Team has too many coaches (maximum 2 allowed)');
        }
    }
    
    /**
     * Validate document completion
     */
    public function validateDocumentCompletion($teamId, $context = 'real_time')
    {
        $participants = TeamParticipant::getActiveForTeam($teamId);
        
        foreach ($participants as $teamParticipant) {
            if (!$teamParticipant->documents_complete) {
                if ($context === 'competition_day') {
                    $this->addError('document_completion', 
                        "Participant {$teamParticipant->participant_id} documents not complete");
                } else {
                    $this->addWarning('document_completion', 
                        "Participant {$teamParticipant->participant_id} documents incomplete");
                }
            }
        }
        
        // Check team-level documents (if applicable)
        $team = Team::find($teamId);
        if ($team && isset($team->team_documents_complete) && !$team->team_documents_complete) {
            if ($context === 'competition_day') {
                $this->addError('document_completion', 'Team documents not complete');
            } else {
                $this->addWarning('document_completion', 'Team documents incomplete');
            }
        }
    }
    
    /**
     * Validate category-specific rules
     */
    public function validateCategorySpecificRules($teamId, $context = 'real_time')
    {
        $team = Team::find($teamId);
        if (!$team) {
            return;
        }
        
        $category = Category::find($team->category_id);
        if (!$category) {
            $this->addError('category', 'Team category not found');
            return;
        }
        
        $participants = TeamParticipant::getActiveForTeam($teamId);
        
        foreach ($participants as $teamParticipant) {
            $participant = $teamParticipant->participant();
            if (!$participant) {
                continue;
            }
            
            // Age restrictions
            if ($category->min_age || $category->max_age) {
                $age = $this->calculateAge($participant->date_of_birth);
                
                if ($category->min_age && $age < $category->min_age) {
                    $this->addError('category_rules', 
                        "Participant {$participant->id} is too young for category {$category->name} (min age: {$category->min_age})");
                }
                
                if ($category->max_age && $age > $category->max_age) {
                    $this->addError('category_rules', 
                        "Participant {$participant->id} is too old for category {$category->name} (max age: {$category->max_age})");
                }
            }
            
            // Grade restrictions
            if ($category->min_grade || $category->max_grade) {
                if ($category->min_grade && $this->compareGrades($participant->grade, $category->min_grade) < 0) {
                    $this->addError('category_rules', 
                        "Participant {$participant->id} grade too low for category {$category->name} (min grade: {$category->min_grade})");
                }
                
                if ($category->max_grade && $this->compareGrades($participant->grade, $category->max_grade) > 0) {
                    $this->addError('category_rules', 
                        "Participant {$participant->id} grade too high for category {$category->name} (max grade: {$category->max_grade})");
                }
            }
        }
        
        // Category-specific team composition rules
        if (isset($category->composition_rules)) {
            $compositionRules = json_decode($category->composition_rules, true);
            if ($compositionRules) {
                $this->validateCompositionRules($teamId, $compositionRules, $context);
            }
        }
    }
    
    /**
     * Validate competition-specific rules
     */
    public function validateCompetitionRules($teamId, $context = 'real_time')
    {
        $team = Team::find($teamId);
        if (!$team) {
            return;
        }
        
        $competition = Competition::find($team->competition_id);
        if (!$competition) {
            $this->addError('competition', 'Competition not found');
            return;
        }
        
        // Check registration deadline
        if (isset($competition->registration_deadline)) {
            $deadline = strtotime($competition->registration_deadline);
            if ($context === 'registration' && $deadline < time()) {
                $this->addError('registration_deadline', 'Registration deadline has passed');
            }
        }
        
        // Check competition status
        if (isset($competition->status) && $competition->status === 'cancelled') {
            $this->addError('competition_status', 'Competition has been cancelled');
        }
        
        // Phase-specific validations
        if ($context === 'competition_day') {
            if (isset($competition->status) && !in_array($competition->status, ['active', 'ongoing'])) {
                $this->addError('competition_status', 'Competition is not active');
            }
        }
    }
    
    /**
     * Validate composition rules (role distribution, etc.)
     */
    private function validateCompositionRules($teamId, $compositionRules, $context)
    {
        $participants = TeamParticipant::getActiveForTeam($teamId);
        
        // Validate role requirements
        if (isset($compositionRules['required_roles'])) {
            foreach ($compositionRules['required_roles'] as $role => $count) {
                $roleCount = $participants->where('role', $role)->count();
                if ($roleCount < $count) {
                    $this->addError('composition_rules', 
                        "Team requires at least {$count} {$role}(s), currently has {$roleCount}");
                }
            }
        }
        
        // Validate maximum roles
        if (isset($compositionRules['max_roles'])) {
            foreach ($compositionRules['max_roles'] as $role => $maxCount) {
                $roleCount = $participants->where('role', $role)->count();
                if ($roleCount > $maxCount) {
                    $this->addError('composition_rules', 
                        "Team can have maximum {$maxCount} {$role}(s), currently has {$roleCount}");
                }
            }
        }
        
        // Validate team leader requirement
        if (isset($compositionRules['require_team_leader']) && $compositionRules['require_team_leader']) {
            $leaderCount = $participants->where('role', 'team_leader')->count();
            if ($leaderCount === 0) {
                $this->addError('composition_rules', 'Team must have a team leader');
            } elseif ($leaderCount > 1) {
                $this->addError('composition_rules', 'Team can have only one team leader');
            }
        }
    }
    
    /**
     * Bulk validate multiple teams
     */
    public function bulkValidateTeams($teamIds, $context = 'bulk_import')
    {
        $results = [];
        
        foreach ($teamIds as $teamId) {
            $results[$teamId] = $this->validateTeamComposition($teamId, $context);
        }
        
        return [
            'total_teams' => count($teamIds),
            'valid_teams' => count(array_filter($results, function($result) { return $result['is_valid']; })),
            'invalid_teams' => count(array_filter($results, function($result) { return !$result['is_valid']; })),
            'results' => $results,
            'summary' => $this->getBulkValidationSummary($results)
        ];
    }
    
    /**
     * Real-time validation for team composition changes
     */
    public function validateRealTimeChange($teamId, $changeType, $changeData)
    {
        $this->errors = [];
        $this->warnings = [];
        
        switch ($changeType) {
            case 'add_participant':
                return $this->validateParticipantAddition($teamId, $changeData);
            case 'remove_participant':
                return $this->validateParticipantRemoval($teamId, $changeData);
            case 'change_role':
                return $this->validateRoleChange($teamId, $changeData);
            case 'assign_coach':
                return $this->validateCoachAssignment($teamId, $changeData);
            default:
                $this->addError('change_type', 'Unknown change type');
                return $this->getValidationResult();
        }
    }
    
    /**
     * Validate participant addition
     */
    private function validateParticipantAddition($teamId, $changeData)
    {
        $participantId = $changeData['participant_id'] ?? null;
        $role = $changeData['role'] ?? 'regular';
        
        if (!$participantId) {
            $this->addError('participant_id', 'Participant ID required');
            return $this->getValidationResult();
        }
        
        // Check if participant exists and is available
        $participant = Participant::find($participantId);
        if (!$participant) {
            $this->addError('participant', 'Participant not found');
            return $this->getValidationResult();
        }
        
        // Check team capacity
        $team = Team::find($teamId);
        if ($team) {
            $sizeValidation = $this->validateTeamSize($teamId, $team->category_id, $team->competition_id, 'modification');
            if (!$sizeValidation['can_add_more']) {
                $this->addError('team_size', 'Cannot add participant: team at maximum capacity');
            }
        }
        
        // Check for conflicts
        $canAdd = TeamParticipant::canAddToTeam($participantId, $teamId);
        if (!$canAdd['can_add']) {
            $this->addError('participant_conflict', $canAdd['reason']);
        }
        
        // Validate role
        if ($role === 'team_leader') {
            $existingLeader = TeamParticipant::getByRole($teamId, 'team_leader')->first();
            if ($existingLeader) {
                $this->addError('role_conflict', 'Team already has a team leader');
            }
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate participant removal
     */
    private function validateParticipantRemoval($teamId, $changeData)
    {
        $participantId = $changeData['participant_id'] ?? null;
        
        if (!$participantId) {
            $this->addError('participant_id', 'Participant ID required');
            return $this->getValidationResult();
        }
        
        // Check if participant is in team
        $teamParticipant = TeamParticipant::where('team_id', $teamId)
                                         ->where('participant_id', $participantId)
                                         ->where('status', 'active')
                                         ->first();
        
        if (!$teamParticipant) {
            $this->addError('participant', 'Participant not found in team');
            return $this->getValidationResult();
        }
        
        // Check if removal would violate minimum team size
        $team = Team::find($teamId);
        if ($team) {
            $sizeValidation = $this->validateTeamSize($teamId, $team->category_id, $team->competition_id, 'modification');
            if (($sizeValidation['current_count'] - 1) < $sizeValidation['min_required']) {
                $this->addWarning('team_size', 'Removing participant will put team below minimum size');
            }
        }
        
        // Check if removing team leader
        if ($teamParticipant->role === 'team_leader') {
            $this->addWarning('leadership', 'Removing team leader - new leader should be appointed');
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate role change
     */
    private function validateRoleChange($teamId, $changeData)
    {
        $participantId = $changeData['participant_id'] ?? null;
        $newRole = $changeData['new_role'] ?? null;
        
        if (!$participantId || !$newRole) {
            $this->addError('required_fields', 'Participant ID and new role required');
            return $this->getValidationResult();
        }
        
        // Check if participant is in team
        $teamParticipant = TeamParticipant::where('team_id', $teamId)
                                         ->where('participant_id', $participantId)
                                         ->where('status', 'active')
                                         ->first();
        
        if (!$teamParticipant) {
            $this->addError('participant', 'Participant not found in team');
            return $this->getValidationResult();
        }
        
        // Validate role
        if (!array_key_exists($newRole, TeamParticipant::ROLES)) {
            $this->addError('invalid_role', 'Invalid role specified');
            return $this->getValidationResult();
        }
        
        // Check role conflicts
        if ($newRole === 'team_leader') {
            $existingLeader = TeamParticipant::getByRole($teamId, 'team_leader')->first();
            if ($existingLeader && $existingLeader->id !== $teamParticipant->id) {
                $this->addError('role_conflict', 'Team already has a team leader');
            }
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate coach assignment
     */
    private function validateCoachAssignment($teamId, $changeData)
    {
        $userId = $changeData['user_id'] ?? null;
        $role = $changeData['role'] ?? 'primary';
        
        if (!$userId) {
            $this->addError('user_id', 'User ID required');
            return $this->getValidationResult();
        }
        
        // Check if user can be assigned as coach
        $canAssign = TeamCoach::canAssignUser($userId, $teamId, $role);
        if (!$canAssign['can_assign']) {
            $this->addError('coach_assignment', $canAssign['reason']);
        }
        
        return $this->getValidationResult();
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
            return 0;
        }
        
        return $pos1 - $pos2;
    }
    
    /**
     * Add validation error
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Add validation warning
     */
    private function addWarning($field, $message)
    {
        if (!isset($this->warnings[$field])) {
            $this->warnings[$field] = [];
        }
        $this->warnings[$field][] = $message;
    }
    
    /**
     * Get validation result
     */
    private function getValidationResult()
    {
        return [
            'is_valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'error_count' => array_sum(array_map('count', $this->errors)),
            'warning_count' => array_sum(array_map('count', $this->warnings)),
            'validated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get bulk validation summary
     */
    private function getBulkValidationSummary($results)
    {
        $errorsByField = [];
        $warningsByField = [];
        
        foreach ($results as $teamId => $result) {
            foreach ($result['errors'] as $field => $errors) {
                if (!isset($errorsByField[$field])) {
                    $errorsByField[$field] = 0;
                }
                $errorsByField[$field] += count($errors);
            }
            
            foreach ($result['warnings'] as $field => $warnings) {
                if (!isset($warningsByField[$field])) {
                    $warningsByField[$field] = 0;
                }
                $warningsByField[$field] += count($warnings);
            }
        }
        
        return [
            'common_errors' => $errorsByField,
            'common_warnings' => $warningsByField,
            'most_common_error' => $errorsByField ? array_search(max($errorsByField), $errorsByField) : null,
            'most_common_warning' => $warningsByField ? array_search(max($warningsByField), $warningsByField) : null
        ];
    }
    
    /**
     * Get validation rules summary
     */
    public static function getValidationRules()
    {
        return [
            'team_size_rules' => self::TEAM_SIZE_VALIDATION,
            'validation_contexts' => self::VALIDATION_CONTEXTS,
            'validation_points' => [
                'Team size validation',
                'Participant eligibility',
                'Coach assignments',
                'Document completion',
                'Category-specific rules',
                'Competition rules',
                'Real-time changes',
                'Bulk validation'
            ]
        ];
    }
}