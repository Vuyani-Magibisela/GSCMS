<?php

namespace App\Core;

use App\Models\Team;
use App\Models\Category;
use App\Models\TeamParticipant;
use App\Models\Participant;

/**
 * Category Limit Validator
 * Enforces strict 1 team per category rule and participant eligibility
 */
class CategoryLimitValidator
{
    /**
     * Validate new team registration for school/category combination
     */
    public function validateNewTeamRegistration($schoolId, $categoryId)
    {
        // Check existing team registrations for school in category
        $existingTeams = Team::where('school_id', $schoolId)
                            ->where('category_id', $categoryId)
                            ->where('status', '!=', 'cancelled')
                            ->count();
        
        $categoryLimit = $this->getCategoryLimit($categoryId);
        
        return [
            'can_register' => $existingTeams < $categoryLimit,
            'existing_teams' => $existingTeams,
            'limit' => $categoryLimit,
            'remaining_slots' => max(0, $categoryLimit - $existingTeams),
            'violation_reason' => $existingTeams >= $categoryLimit ? 'category_limit_exceeded' : null,
            'existing_team_details' => $this->getExistingTeamDetails($schoolId, $categoryId)
        ];
    }
    
    /**
     * Get available categories for a school
     */
    public function getAvailableCategories($schoolId)
    {
        $allCategories = Category::where('is_active', true)->get();
        $availableCategories = [];
        
        foreach ($allCategories as $category) {
            $validation = $this->validateNewTeamRegistration($schoolId, $category->id);
            
            $categoryData = [
                'category' => $category,
                'remaining_slots' => $validation['remaining_slots'],
                'can_register' => $validation['can_register'],
                'existing_teams' => $validation['existing_teams'],
                'reason_unavailable' => $validation['violation_reason']
            ];
            
            if ($validation['can_register']) {
                $availableCategories[] = $categoryData;
            } else {
                // Still include unavailable categories for display purposes
                $categoryData['status'] = 'unavailable';
                $availableCategories[] = $categoryData;
            }
        }
        
        // Sort by availability first, then by category order
        usort($availableCategories, function($a, $b) {
            if ($a['can_register'] != $b['can_register']) {
                return $b['can_register'] - $a['can_register']; // Available first
            }
            return $a['category']->display_order <=> $b['category']->display_order;
        });
        
        return $availableCategories;
    }
    
    /**
     * Validate participant eligibility for category
     */
    public function validateParticipantEligibility($participantId, $categoryId)
    {
        // Check participant not in another team for same category
        $existingTeamMembership = TeamParticipant::join('teams', 'team_participants.team_id', '=', 'teams.id')
                                                ->where('team_participants.participant_id', $participantId)
                                                ->where('teams.category_id', $categoryId)
                                                ->where('teams.status', '!=', 'cancelled')
                                                ->where('team_participants.status', 'active')
                                                ->exists();
        
        // Get participant and category details
        $participant = Participant::find($participantId);
        $category = Category::find($categoryId);
        
        if (!$participant || !$category) {
            return [
                'eligible' => false,
                'reasons' => ['participant_or_category_not_found' => true]
            ];
        }
        
        // Check grade eligibility for category
        $gradeEligible = $this->isGradeEligibleForCategory($participant->grade, $category);
        
        // Check age eligibility
        $ageEligible = $this->isAgeEligibleForCategory($participant->date_of_birth, $category);
        
        // Check school association
        $schoolAssociationValid = $this->validateSchoolAssociation($participant, $categoryId);
        
        $reasons = [];
        if ($existingTeamMembership) {
            $reasons['duplicate_registration'] = true;
        }
        if (!$gradeEligible) {
            $reasons['grade_ineligible'] = true;
        }
        if (!$ageEligible) {
            $reasons['age_ineligible'] = true;
        }
        if (!$schoolAssociationValid) {
            $reasons['school_association_invalid'] = true;
        }
        
        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
            'participant_details' => [
                'name' => $participant->name,
                'grade' => $participant->grade,
                'age' => $this->calculateAge($participant->date_of_birth),
                'school_id' => $participant->school_id
            ],
            'category_requirements' => [
                'grade_range' => $category->grade_range,
                'age_range' => $category->age_range ?? 'Not specified'
            ]
        ];
    }
    
    /**
     * Validate team composition against category limits
     */
    public function validateTeamComposition($teamId)
    {
        $team = Team::find($teamId);
        if (!$team) {
            return [
                'valid' => false,
                'errors' => ['team_not_found']
            ];
        }
        
        $category = Category::find($team->category_id);
        $participants = TeamParticipant::where('team_id', $teamId)
                                      ->where('status', 'active')
                                      ->with('participant')
                                      ->get();
        
        $errors = [];
        $warnings = [];
        
        // Check team size limits
        $participantCount = $participants->count();
        $minParticipants = $category->min_participants ?? 2;
        $maxParticipants = $category->max_participants ?? 4;
        
        if ($participantCount < $minParticipants) {
            $errors[] = "Team has {$participantCount} participants, minimum required: {$minParticipants}";
        }
        
        if ($participantCount > $maxParticipants) {
            $errors[] = "Team has {$participantCount} participants, maximum allowed: {$maxParticipants}";
        }
        
        // Validate each participant
        foreach ($participants as $teamParticipant) {
            $participant = $teamParticipant->participant;
            if (!$participant) continue;
            
            $eligibility = $this->validateParticipantEligibility($participant->id, $team->category_id);
            if (!$eligibility['eligible']) {
                foreach ($eligibility['reasons'] as $reason => $value) {
                    if ($value) {
                        $errors[] = "Participant {$participant->name}: " . $this->formatEligibilityReason($reason);
                    }
                }
            }
        }
        
        // Check for team leader
        $hasTeamLeader = $participants->where('role', 'team_leader')->count() > 0;
        if (!$hasTeamLeader && $participantCount > 0) {
            $warnings[] = 'No team leader designated';
        }
        
        // Check for duplicate roles (only one team leader allowed)
        $teamLeaderCount = $participants->where('role', 'team_leader')->count();
        if ($teamLeaderCount > 1) {
            $errors[] = 'Multiple team leaders assigned - only one allowed';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'statistics' => [
                'participant_count' => $participantCount,
                'min_required' => $minParticipants,
                'max_allowed' => $maxParticipants,
                'team_leader_count' => $teamLeaderCount
            ]
        ];
    }
    
    /**
     * Get category limit for school registrations
     */
    private function getCategoryLimit($categoryId)
    {
        // For pilot 2025, limit is 1 team per category per school
        // This could be made configurable in the future
        return 1;
    }
    
    /**
     * Get details of existing teams for school/category
     */
    private function getExistingTeamDetails($schoolId, $categoryId)
    {
        return Team::where('school_id', $schoolId)
                  ->where('category_id', $categoryId)
                  ->where('status', '!=', 'cancelled')
                  ->with(['participants.participant', 'coaches'])
                  ->get()
                  ->map(function($team) {
                      return [
                          'id' => $team->id,
                          'name' => $team->name,
                          'status' => $team->status,
                          'participant_count' => $team->participants->where('status', 'active')->count(),
                          'created_at' => $team->created_at
                      ];
                  });
    }
    
    /**
     * Check if participant's grade is eligible for category
     */
    private function isGradeEligibleForCategory($participantGrade, $category)
    {
        if (!$category->grade_range) {
            return true; // No restrictions
        }
        
        // Parse grade range (e.g., "R-3", "4-7", "8-11")
        $gradeRange = explode('-', $category->grade_range);
        if (count($gradeRange) != 2) {
            return true; // Invalid range format, allow
        }
        
        $minGrade = $this->normalizeGrade($gradeRange[0]);
        $maxGrade = $this->normalizeGrade($gradeRange[1]);
        $normalizedParticipantGrade = $this->normalizeGrade($participantGrade);
        
        return $normalizedParticipantGrade >= $minGrade && $normalizedParticipantGrade <= $maxGrade;
    }
    
    /**
     * Check if participant's age is eligible for category
     */
    private function isAgeEligibleForCategory($dateOfBirth, $category)
    {
        if (!$category->age_range || !$dateOfBirth) {
            return true; // No age restrictions or no DOB provided
        }
        
        $age = $this->calculateAge($dateOfBirth);
        
        // Parse age range (e.g., "6-9", "10-13")
        $ageRange = explode('-', $category->age_range);
        if (count($ageRange) != 2) {
            return true; // Invalid range format, allow
        }
        
        $minAge = (int)$ageRange[0];
        $maxAge = (int)$ageRange[1];
        
        return $age >= $minAge && $age <= $maxAge;
    }
    
    /**
     * Validate school association for participant
     */
    private function validateSchoolAssociation($participant, $categoryId)
    {
        // For now, just check that participant has a school
        // In future, could check if participant's school is registered for this category
        return !empty($participant->school_id);
    }
    
    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dateOfBirth)
    {
        if (!$dateOfBirth) return null;
        
        $dob = new \DateTime($dateOfBirth);
        $now = new \DateTime();
        return $now->diff($dob)->y;
    }
    
    /**
     * Normalize grade for comparison (handle R, N, etc.)
     */
    private function normalizeGrade($grade)
    {
        $grade = strtoupper(trim($grade));
        
        switch ($grade) {
            case 'R':
            case 'GRADE R':
                return 0;
            case 'N':
            case 'NURSERY':
                return -1;
            default:
                // Extract numeric part
                preg_match('/(\d+)/', $grade, $matches);
                return isset($matches[1]) ? (int)$matches[1] : 0;
        }
    }
    
    /**
     * Format eligibility reason for display
     */
    private function formatEligibilityReason($reason)
    {
        $messages = [
            'duplicate_registration' => 'already registered for this category',
            'grade_ineligible' => 'grade not eligible for this category',
            'age_ineligible' => 'age not eligible for this category',
            'school_association_invalid' => 'school association invalid',
            'participant_or_category_not_found' => 'participant or category not found'
        ];
        
        return $messages[$reason] ?? $reason;
    }
    
    /**
     * Get comprehensive validation summary for school
     */
    public function getSchoolValidationSummary($schoolId)
    {
        $categories = Category::where('is_active', true)->get();
        $summary = [
            'school_id' => $schoolId,
            'total_categories' => $categories->count(),
            'available_categories' => 0,
            'registered_categories' => 0,
            'unavailable_categories' => 0,
            'category_details' => []
        ];
        
        foreach ($categories as $category) {
            $validation = $this->validateNewTeamRegistration($schoolId, $category->id);
            
            $categoryDetail = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'can_register' => $validation['can_register'],
                'existing_teams' => $validation['existing_teams'],
                'remaining_slots' => $validation['remaining_slots']
            ];
            
            if ($validation['can_register']) {
                $summary['available_categories']++;
            } elseif ($validation['existing_teams'] > 0) {
                $summary['registered_categories']++;
            } else {
                $summary['unavailable_categories']++;
            }
            
            $summary['category_details'][] = $categoryDetail;
        }
        
        return $summary;
    }
}