<?php

namespace App\Controllers\Team;

use App\Controllers\BaseController;
use App\Models\TeamComposition;
use App\Models\TeamParticipant;
use App\Models\TeamCoach;
use App\Models\Team;
use App\Models\Participant;
use App\Models\User;
use App\Core\TeamCompositionValidator;

/**
 * Team Composition Controller
 * Handles team size and composition management with CRUD operations
 */
class TeamCompositionController extends BaseController
{
    private $validator;
    
    public function __construct()
    {
        parent::__construct();
        $this->validator = new TeamCompositionValidator();
    }
    
    /**
     * Display team composition dashboard
     */
    public function index()
    {
        try {
            // Get current user's teams or all teams for admins
            $teams = $this->getAccessibleTeams();
            
            $compositionData = [];
            foreach ($teams as $team) {
                $composition = TeamComposition::where('team_id', $team->id)->first();
                if ($composition) {
                    $compositionData[] = [
                        'team' => $team,
                        'composition' => $composition,
                        'summary' => $composition->getCompositionSummary(),
                        'validation' => $this->validator->validateTeamComposition($team->id, 'real_time')
                    ];
                }
            }
            
            // Get statistics
            $stats = $this->getCompositionStatistics($teams);
            
            return $this->renderView('team/composition/dashboard', [
                'compositions' => $compositionData,
                'statistics' => $stats,
                'user_role' => $_SESSION['user_role'] ?? 'participant'
            ]);
            
        } catch (\Exception $e) {
            error_log("Team composition dashboard error: " . $e->getMessage());
            return $this->errorResponse('Failed to load team composition dashboard', 500);
        }
    }
    
    /**
     * Show specific team composition
     */
    public function show()
    {
        try {
            $teamId = $this->getRouteParam('team_id');
            
            if (!$teamId) {
                return $this->errorResponse('Team ID required', 400);
            }
            
            // Check access permissions
            if (!$this->canAccessTeam($teamId)) {
                return $this->errorResponse('Access denied', 403);
            }
            
            $team = Team::find($teamId);
            if (!$team) {
                return $this->errorResponse('Team not found', 404);
            }
            
            $composition = TeamComposition::where('team_id', $teamId)->first();
            if (!$composition) {
                // Create composition if it doesn't exist
                $composition = TeamComposition::createForTeam($teamId);
            }
            
            // Get detailed composition data
            $compositionData = [
                'team' => $team,
                'composition' => $composition,
                'summary' => $composition->getCompositionSummary(),
                'participants' => TeamParticipant::getActiveForTeam($teamId),
                'coaches' => TeamCoach::getActiveForTeam($teamId),
                'validation' => $this->validator->validateTeamComposition($teamId, 'real_time')
            ];
            
            // Get available participants for adding
            $availableParticipants = $this->getAvailableParticipants($teamId);
            
            return $this->renderView('team/composition/show', [
                'composition_data' => $compositionData,
                'available_participants' => $availableParticipants,
                'can_edit' => $this->canEditTeam($teamId)
            ]);
            
        } catch (\Exception $e) {
            error_log("Show team composition error: " . $e->getMessage());
            return $this->errorResponse('Failed to load team composition', 500);
        }
    }
    
    /**
     * Add participant to team
     */
    public function addParticipant()
    {
        try {
            $teamId = $this->input('team_id');
            $participantId = $this->input('participant_id');
            $role = $this->input('role', 'regular');
            
            if (!$teamId || !$participantId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team ID and Participant ID required'], 400);
            }
            
            // Check permissions
            if (!$this->canEditTeam($teamId)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            // Validate the addition
            $validation = $this->validator->validateRealTimeChange($teamId, 'add_participant', [
                'participant_id' => $participantId,
                'role' => $role
            ]);
            
            if (!$validation['is_valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 400);
            }
            
            // Add participant to team
            $composition = TeamComposition::where('team_id', $teamId)->first();
            if (!$composition) {
                $composition = TeamComposition::createForTeam($teamId);
            }
            
            $teamParticipant = $composition->addParticipant($participantId, $role);
            
            // Get updated composition data
            $updatedSummary = $composition->getCompositionSummary();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Participant added successfully',
                'data' => [
                    'team_participant' => $teamParticipant,
                    'composition_summary' => $updatedSummary
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Add participant error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to add participant: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove participant from team
     */
    public function removeParticipant()
    {
        try {
            $teamId = $this->input('team_id');
            $participantId = $this->input('participant_id');
            $reason = $this->input('reason', 'voluntary');
            
            if (!$teamId || !$participantId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team ID and Participant ID required'], 400);
            }
            
            // Check permissions
            if (!$this->canEditTeam($teamId)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            // Validate the removal
            $validation = $this->validator->validateRealTimeChange($teamId, 'remove_participant', [
                'participant_id' => $participantId
            ]);
            
            if (!$validation['is_valid']) {
                // Show warnings but allow removal (unlike additions)
                if (!empty($validation['warnings'])) {
                    // Log warnings but proceed
                    error_log("Team composition warnings during removal: " . json_encode($validation['warnings']));
                }
            }
            
            // Remove participant from team
            $composition = TeamComposition::where('team_id', $teamId)->first();
            if (!$composition) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team composition not found'], 404);
            }
            
            $composition->removeParticipant($participantId, $reason);
            
            // Get updated composition data
            $updatedSummary = $composition->getCompositionSummary();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Participant removed successfully',
                'warnings' => $validation['warnings'] ?? [],
                'data' => [
                    'composition_summary' => $updatedSummary
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Remove participant error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to remove participant: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update participant role
     */
    public function updateParticipantRole()
    {
        try {
            $teamId = $this->input('team_id');
            $participantId = $this->input('participant_id');
            $newRole = $this->input('new_role');
            
            if (!$teamId || !$participantId || !$newRole) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team ID, Participant ID, and new role required'], 400);
            }
            
            // Check permissions
            if (!$this->canEditTeam($teamId)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            // Validate the role change
            $validation = $this->validator->validateRealTimeChange($teamId, 'change_role', [
                'participant_id' => $participantId,
                'new_role' => $newRole
            ]);
            
            if (!$validation['is_valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 400);
            }
            
            // Update participant role
            $teamParticipant = TeamParticipant::where('team_id', $teamId)
                                             ->where('participant_id', $participantId)
                                             ->where('status', 'active')
                                             ->first();
            
            if (!$teamParticipant) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team participant not found'], 404);
            }
            
            $teamParticipant->updateRole($newRole);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Participant role updated successfully',
                'data' => [
                    'team_participant' => $teamParticipant
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Update participant role error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update participant role: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate team composition
     */
    public function validateComposition()
    {
        try {
            $teamId = $this->input('team_id');
            $context = $this->input('context', 'real_time');
            
            if (!$teamId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team ID required'], 400);
            }
            
            // Check access permissions
            if (!$this->canAccessTeam($teamId)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            $validation = $this->validator->validateTeamComposition($teamId, $context);
            
            // Update composition validation timestamp
            $composition = TeamComposition::where('team_id', $teamId)->first();
            if ($composition) {
                $composition->last_validated_at = date('Y-m-d H:i:s');
                $composition->validation_errors = $validation['is_valid'] ? null : $validation['errors'];
                $composition->save();
            }
            
            return $this->jsonResponse([
                'success' => true,
                'validation' => $validation
            ]);
            
        } catch (\Exception $e) {
            error_log("Validate composition error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk validate team compositions
     */
    public function bulkValidate()
    {
        try {
            $teamIds = $this->input('team_ids', []);
            $context = $this->input('context', 'bulk_import');
            
            if (empty($teamIds)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team IDs required'], 400);
            }
            
            // Check permissions for each team
            $accessibleTeamIds = [];
            foreach ($teamIds as $teamId) {
                if ($this->canAccessTeam($teamId)) {
                    $accessibleTeamIds[] = $teamId;
                }
            }
            
            if (empty($accessibleTeamIds)) {
                return $this->jsonResponse(['success' => false, 'message' => 'No accessible teams'], 403);
            }
            
            $results = $this->validator->bulkValidateTeams($accessibleTeamIds, $context);
            
            return $this->jsonResponse([
                'success' => true,
                'bulk_validation' => $results
            ]);
            
        } catch (\Exception $e) {
            error_log("Bulk validate error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Bulk validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get team composition statistics
     */
    public function getStatistics()
    {
        try {
            $scope = $this->input('scope', 'user'); // user, school, or global
            
            $teams = $this->getAccessibleTeams($scope);
            $stats = $this->getCompositionStatistics($teams);
            
            return $this->jsonResponse([
                'success' => true,
                'statistics' => $stats
            ]);
            
        } catch (\Exception $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update team composition settings
     */
    public function updateSettings()
    {
        try {
            $teamId = $this->input('team_id');
            $maxParticipants = $this->input('max_participants');
            $categoryRules = $this->input('category_rules', []);
            
            if (!$teamId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Team ID required'], 400);
            }
            
            // Check permissions (only admins can update settings)
            if (!$this->canManageTeamSettings($teamId)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            $composition = TeamComposition::where('team_id', $teamId)->first();
            if (!$composition) {
                $composition = TeamComposition::createForTeam($teamId);
            }
            
            // Update settings
            if ($maxParticipants !== null) {
                $composition->max_participants = (int)$maxParticipants;
            }
            
            if (!empty($categoryRules)) {
                $composition->category_specific_rules = $categoryRules;
            }
            
            $composition->save();
            
            // Revalidate after settings change
            $validation = $this->validator->validateTeamComposition($teamId, 'modification');
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team composition settings updated successfully',
                'data' => [
                    'composition' => $composition,
                    'validation' => $validation
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Update settings error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available participants for adding to team
     */
    private function getAvailableParticipants($teamId)
    {
        $team = Team::find($teamId);
        if (!$team) {
            return [];
        }
        
        // Get participants from the same school who are not already in a team for this category
        $query = Participant::where('school_id', $team->school_id);
        
        // Exclude participants already in teams for this category
        $query->whereNotIn('id', function($subQuery) use ($team) {
            $subQuery->select('team_participants.participant_id')
                    ->from('team_participants')
                    ->join('teams', 'team_participants.team_id', '=', 'teams.id')
                    ->where('teams.category_id', $team->category_id)
                    ->where('team_participants.status', 'active');
        });
        
        return $query->orderBy('name')->get();
    }
    
    /**
     * Get accessible teams based on user role
     */
    private function getAccessibleTeams($scope = 'user')
    {
        $userRole = $_SESSION['user_role'] ?? 'participant';
        $userId = $_SESSION['user_id'] ?? null;
        
        $query = Team::query();
        
        switch ($userRole) {
            case 'admin':
            case 'super_admin':
                if ($scope === 'global') {
                    // Admins can access all teams
                } elseif ($scope === 'school') {
                    $user = User::find($userId);
                    if ($user && $user->school_id) {
                        $query->where('school_id', $user->school_id);
                    }
                } else {
                    // Default to user's associated teams
                    $query->whereIn('id', $this->getUserAssociatedTeamIds($userId));
                }
                break;
                
            case 'school_coordinator':
                $user = User::find($userId);
                if ($user && $user->school_id) {
                    $query->where('school_id', $user->school_id);
                }
                break;
                
            case 'team_coach':
                $coachTeamIds = TeamCoach::where('coach_user_id', $userId)
                                       ->where('status', 'active')
                                       ->pluck('team_id');
                $query->whereIn('id', $coachTeamIds);
                break;
                
            default:
                // Participants can only see their own teams
                $query->whereIn('id', $this->getUserAssociatedTeamIds($userId));
        }
        
        return $query->get();
    }
    
    /**
     * Get team IDs associated with a user
     */
    private function getUserAssociatedTeamIds($userId)
    {
        $teamIds = [];
        
        // Teams where user is a participant
        $participantTeamIds = TeamParticipant::join('participants', 'team_participants.participant_id', '=', 'participants.id')
                                            ->where('participants.user_id', $userId)
                                            ->where('team_participants.status', 'active')
                                            ->pluck('team_participants.team_id');
        
        // Teams where user is a coach
        $coachTeamIds = TeamCoach::where('coach_user_id', $userId)
                                ->where('status', 'active')
                                ->pluck('team_id');
        
        return array_unique(array_merge($participantTeamIds->toArray(), $coachTeamIds->toArray()));
    }
    
    /**
     * Check if user can access team
     */
    private function canAccessTeam($teamId)
    {
        $userRole = $_SESSION['user_role'] ?? 'participant';
        $userId = $_SESSION['user_id'] ?? null;
        
        if (in_array($userRole, ['admin', 'super_admin'])) {
            return true;
        }
        
        $accessibleTeamIds = $this->getUserAssociatedTeamIds($userId);
        return in_array($teamId, $accessibleTeamIds);
    }
    
    /**
     * Check if user can edit team
     */
    private function canEditTeam($teamId)
    {
        $userRole = $_SESSION['user_role'] ?? 'participant';
        $userId = $_SESSION['user_id'] ?? null;
        
        if (in_array($userRole, ['admin', 'super_admin', 'school_coordinator'])) {
            return $this->canAccessTeam($teamId);
        }
        
        // Team coaches can edit their teams
        if ($userRole === 'team_coach') {
            $isCoach = TeamCoach::where('team_id', $teamId)
                               ->where('coach_user_id', $userId)
                               ->where('status', 'active')
                               ->exists();
            return $isCoach;
        }
        
        return false;
    }
    
    /**
     * Check if user can manage team settings
     */
    private function canManageTeamSettings($teamId)
    {
        $userRole = $_SESSION['user_role'] ?? 'participant';
        
        return in_array($userRole, ['admin', 'super_admin']);
    }
    
    /**
     * Get composition statistics
     */
    private function getCompositionStatistics($teams)
    {
        $stats = [
            'total_teams' => $teams->count(),
            'complete_teams' => 0,
            'incomplete_teams' => 0,
            'oversize_teams' => 0,
            'invalid_teams' => 0,
            'total_participants' => 0,
            'total_coaches' => 0,
            'average_team_size' => 0,
            'teams_by_status' => [],
            'participants_by_role' => [],
            'coaches_by_role' => []
        ];
        
        if ($teams->isEmpty()) {
            return $stats;
        }
        
        foreach ($teams as $team) {
            $composition = TeamComposition::where('team_id', $team->id)->first();
            if (!$composition) {
                continue;
            }
            
            // Count by status
            $status = $composition->composition_status;
            $stats['teams_by_status'][$status] = ($stats['teams_by_status'][$status] ?? 0) + 1;
            
            switch ($status) {
                case 'complete':
                    $stats['complete_teams']++;
                    break;
                case 'incomplete':
                    $stats['incomplete_teams']++;
                    break;
                case 'oversize':
                    $stats['oversize_teams']++;
                    break;
                case 'invalid':
                    $stats['invalid_teams']++;
                    break;
            }
            
            // Count participants and roles
            $participants = TeamParticipant::getActiveForTeam($team->id);
            $stats['total_participants'] += $participants->count();
            
            foreach ($participants as $participant) {
                $role = $participant->role;
                $stats['participants_by_role'][$role] = ($stats['participants_by_role'][$role] ?? 0) + 1;
            }
            
            // Count coaches and roles
            $coaches = TeamCoach::getActiveForTeam($team->id);
            $stats['total_coaches'] += $coaches->count();
            
            foreach ($coaches as $coach) {
                $role = $coach->coach_role;
                $stats['coaches_by_role'][$role] = ($stats['coaches_by_role'][$role] ?? 0) + 1;
            }
        }
        
        // Calculate average team size
        if ($stats['total_teams'] > 0) {
            $stats['average_team_size'] = round($stats['total_participants'] / $stats['total_teams'], 2);
        }
        
        return $stats;
    }
}