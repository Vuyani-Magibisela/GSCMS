<?php

namespace App\Controllers\Registration;

use App\Controllers\BaseController;
use App\Models\TeamRegistration;
use App\Models\Team;
use App\Models\School;
use App\Models\Category;
use App\Models\User;
use App\Models\Participant;
use App\Models\TeamParticipant;
use App\Core\CategoryLimitValidator;
use App\Core\DeadlineEnforcer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Team Registration Controller
 * Handles team registration with comprehensive category validation and deadline enforcement
 */
class TeamRegistrationController extends BaseController
{
    protected $session;
    private $deadlineEnforcer;
    private $categoryValidator;
    
    public function __construct()
    {
        parent::__construct();
        $this->session = Session::getInstance();
        $this->deadlineEnforcer = new DeadlineEnforcer();
        $this->categoryValidator = new CategoryLimitValidator();
    }
    
    /**
     * Show team registration dashboard
     */
    public function index()
    {
        $schoolId = $this->getUserSchoolId();
        
        if (!$schoolId) {
            return $this->redirect('/auth/login')->with('error', 'School access required for team registration');
        }
        
        // Check team registration deadline
        $deadlineStatus = $this->deadlineEnforcer->getDeadlineStatus();
        
        if ($deadlineStatus['active_competition'] && 
            $deadlineStatus['deadlines']['team_registration']['is_overdue']) {
            return $this->renderView('registration/team/closed', [
                'deadline_info' => $deadlineStatus['deadlines']['team_registration']
            ]);
        }
        
        // Get existing team registrations
        $teamRegistrations = TeamRegistration::getForSchool($schoolId);
        
        // Get available categories with registration status
        $categories = $this->getCategoriesWithRegistrationStatus($schoolId);
        
        return $this->renderView('registration/team/index', [
            'team_registrations' => $teamRegistrations,
            'categories' => $categories,
            'school_id' => $schoolId,
            'deadline_info' => $deadlineStatus['deadlines']['team_registration'] ?? null,
            'can_register_new' => $this->canRegisterNewTeam($schoolId)
        ]);
    }
    
    /**
     * Show category selection for new team registration
     */
    public function selectCategory()
    {
        $schoolId = $this->getUserSchoolId();
        
        if (!$schoolId) {
            return $this->redirect('/auth/login')->with('error', 'School access required');
        }
        
        // Check deadline
        $deadlineStatus = $this->deadlineEnforcer->getDeadlineStatus();
        
        if ($deadlineStatus['active_competition'] && 
            $deadlineStatus['deadlines']['team_registration']['is_overdue']) {
            return $this->redirect('/register/team')->with('error', 'Team registration deadline has passed');
        }
        
        if (!$this->canRegisterNewTeam($schoolId)) {
            return $this->redirect('/register/team')->with('error', 'Maximum team registrations reached');
        }
        
        $categories = $this->getAvailableCategories($schoolId);
        
        return $this->renderView('registration/team/select_category', [
            'categories' => $categories,
            'school_id' => $schoolId,
            'deadline_info' => $deadlineStatus['deadlines']['team_registration'] ?? null
        ]);
    }
    
    /**
     * Create new team registration
     */
    public function create()
    {
        $categoryId = $this->input('category_id');
        $schoolId = $this->getUserSchoolId();
        
        if (!$schoolId) {
            return $this->redirect('/auth/login')->with('error', 'School access required');
        }
        
        if (!$categoryId) {
            return $this->redirect('/register/team/select-category')->with('error', 'Please select a category');
        }
        
        // Validate category selection
        $validation = $this->categoryValidator->validateNewTeamRegistration($schoolId, $categoryId);
        
        if (!$validation['can_register']) {
            return $this->redirect('/register/team/select-category')->with('error', 
                $validation['violation_reason'] ?? 'Cannot register team in this category');
        }
        
        // Get category details
        $category = Category::find($categoryId);
        if (!$category) {
            return $this->redirect('/register/team/select-category')->with('error', 'Invalid category selected');
        }
        
        // Get available coaches for this school
        $coaches = $this->getAvailableCoaches($schoolId);
        $participants = $this->getAvailableParticipants($schoolId, $categoryId);
        
        return $this->renderView('registration/team/create', [
            'category' => $category,
            'coaches' => $coaches,
            'participants' => $participants,
            'school_id' => $schoolId,
            'validation_info' => $validation
        ]);
    }
    
    /**
     * Store new team registration
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $schoolId = $this->getUserSchoolId();
        $userId = $_SESSION['user_id'];
        
        if (!$schoolId) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'School access required'
            ], 403);
        }
        
        try {
            // Validate input
            $this->validateTeamRegistrationData($data);
            
            // Create team registration
            $teamRegistration = TeamRegistration::createRegistration(
                $schoolId,
                $data['category_id'],
                $data,
                $userId
            );
            
            // Add initial participants if provided
            if (!empty($data['participant_ids'])) {
                $this->addParticipantsToTeam($teamRegistration, $data['participant_ids'], $userId);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team registration created successfully',
                'team_registration_id' => $teamRegistration->id,
                'redirect' => "/register/team/{$teamRegistration->id}/edit"
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Show team registration details
     */
    public function show($id)
    {
        $teamRegistration = TeamRegistration::find($id);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->redirect('/register/team')->with('error', 'Team registration not found');
        }
        
        // Get team participants
        $participants = $teamRegistration->participants();
        
        // Get completion status
        $completeness = $teamRegistration->validateCompleteness();
        
        return $this->renderView('registration/team/show', [
            'team_registration' => $teamRegistration,
            'participants' => $participants,
            'completeness' => $completeness,
            'can_modify' => $this->canModifyTeamRegistration($teamRegistration)
        ]);
    }
    
    /**
     * Edit team registration
     */
    public function edit($id)
    {
        $teamRegistration = TeamRegistration::find($id);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->redirect('/register/team')->with('error', 'Team registration not found');
        }
        
        if (!$this->canModifyTeamRegistration($teamRegistration)) {
            return $this->redirect("/register/team/{$id}")->with('error', 'Team registration cannot be modified');
        }
        
        // Get available data for editing
        $coaches = $this->getAvailableCoaches($teamRegistration->school_id);
        $participants = $this->getAvailableParticipants($teamRegistration->school_id, $teamRegistration->category_id);
        $currentParticipants = $teamRegistration->participants();
        
        return $this->renderView('registration/team/edit', [
            'team_registration' => $teamRegistration,
            'coaches' => $coaches,
            'participants' => $participants,
            'current_participants' => $currentParticipants,
            'completeness' => $teamRegistration->validateCompleteness()
        ]);
    }
    
    /**
     * Update team registration
     */
    public function update(Request $request, $id)
    {
        $teamRegistration = TeamRegistration::find($id);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team registration not found'
            ], 404);
        }
        
        if (!$this->canModifyTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team registration cannot be modified'
            ], 403);
        }
        
        try {
            $data = $request->all();
            $userId = $_SESSION['user_id'];
            
            // Update basic team info
            $updateData = [
                'team_name' => $data['team_name'] ?? $teamRegistration->team_name,
                'coach_primary_id' => $data['coach_primary_id'] ?? $teamRegistration->coach_primary_id,
                'coach_secondary_id' => $data['coach_secondary_id'] ?? $teamRegistration->coach_secondary_id,
                'notification_email' => $data['notification_email'] ?? $teamRegistration->notification_email,
                'contact_phone' => $data['contact_phone'] ?? $teamRegistration->contact_phone,
                'competition_objectives' => $data['competition_objectives'] ?? $teamRegistration->competition_objectives,
                'previous_experience' => $data['previous_experience'] ?? $teamRegistration->previous_experience,
                'special_requirements' => $data['special_requirements'] ?? $teamRegistration->special_requirements
            ];
            
            // Check for duplicate team name within school
            if ($data['team_name'] !== $teamRegistration->team_name) {
                $existingTeam = TeamRegistration::where('school_id', $teamRegistration->school_id)
                                             ->where('team_name', $data['team_name'])
                                             ->where('id', '!=', $id)
                                             ->where('registration_status', '!=', 'withdrawn')
                                             ->first();
                
                if ($existingTeam) {
                    throw new \Exception('Team name already exists for this school');
                }
            }
            
            foreach ($updateData as $field => $value) {
                $teamRegistration->$field = $value;
            }
            
            $teamRegistration->recordModification($userId, 'update_basic_info');
            $teamRegistration->save();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team registration updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Add participant to team
     */
    public function addParticipant()
    {
        $teamId = $this->input('team_id');
        $participantId = $this->input('participant_id');
        $role = $this->input('role', 'regular');
        $userId = $_SESSION['user_id'];
        
        $teamRegistration = TeamRegistration::find($teamId);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team registration not found'
            ], 404);
        }
        
        if (!$this->canModifyTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team roster is locked for modifications'
            ], 403);
        }
        
        try {
            $teamParticipant = $teamRegistration->addParticipant($participantId, $role, $userId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Participant added to team successfully',
                'participant' => $teamParticipant
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Remove participant from team
     */
    public function removeParticipant()
    {
        $teamId = $this->input('team_id');
        $participantId = $this->input('participant_id');
        $reason = $this->input('reason', 'voluntary');
        $userId = $_SESSION['user_id'];
        
        $teamRegistration = TeamRegistration::find($teamId);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team registration not found'
            ], 404);
        }
        
        if (!$this->canModifyTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team roster is locked for modifications'
            ], 403);
        }
        
        try {
            $teamRegistration->removeParticipant($participantId, $reason, $userId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Participant removed from team successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Submit team registration for approval
     */
    public function submit()
    {
        $teamId = $this->input('team_id');
        
        $teamRegistration = TeamRegistration::find($teamId);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team registration not found'
            ], 404);
        }
        
        try {
            $teamRegistration->submitForApproval();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team registration submitted for approval successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Withdraw team registration
     */
    public function withdraw()
    {
        $teamId = $this->input('team_id');
        $reason = $this->input('reason', 'Voluntary withdrawal');
        
        $teamRegistration = TeamRegistration::find($teamId);
        
        if (!$teamRegistration || !$this->canAccessTeamRegistration($teamRegistration)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Team registration not found'
            ], 404);
        }
        
        if (!in_array($teamRegistration->registration_status, ['draft', 'submitted', 'under_review'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Only draft, submitted, or under review registrations can be withdrawn'
            ], 400);
        }
        
        try {
            $teamRegistration->registration_status = 'withdrawn';
            $teamRegistration->rejection_reason = $reason;
            $teamRegistration->save();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team registration withdrawn successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Check participant eligibility for category
     */
    public function checkParticipantEligibility()
    {
        $participantId = $this->input('participant_id');
        $categoryId = $this->input('category_id');
        
        if (!$participantId || !$categoryId) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Participant ID and category ID required'
            ], 400);
        }
        
        try {
            $eligibility = $this->categoryValidator->validateParticipantEligibility($participantId, $categoryId);
            
            return $this->jsonResponse([
                'success' => true,
                'eligibility' => $eligibility
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Get categories with registration status for school
     */
    private function getCategoriesWithRegistrationStatus($schoolId)
    {
        $categories = Category::where('is_active', true)->orderBy('display_order')->get();
        
        foreach ($categories as $category) {
            $validation = $this->categoryValidator->validateNewTeamRegistration($schoolId, $category->id);
            $category->can_register = $validation['can_register'];
            $category->violation_reason = $validation['violation_reason'] ?? null;
            
            // Get existing registration for this category
            $existingRegistration = TeamRegistration::where('school_id', $schoolId)
                                                 ->where('category_id', $category->id)
                                                 ->where('registration_status', '!=', 'withdrawn')
                                                 ->first();
            
            $category->existing_registration = $existingRegistration;
        }
        
        return $categories;
    }
    
    /**
     * Get available categories for new registration
     */
    private function getAvailableCategories($schoolId)
    {
        $categories = Category::where('is_active', true)->orderBy('display_order')->get();
        $availableCategories = [];
        
        foreach ($categories as $category) {
            $validation = $this->categoryValidator->validateNewTeamRegistration($schoolId, $category->id);
            
            if ($validation['can_register']) {
                $availableCategories[] = $category;
            }
        }
        
        return $availableCategories;
    }
    
    /**
     * Get available coaches for school
     */
    private function getAvailableCoaches($schoolId)
    {
        return User::where('school_id', $schoolId)
                  ->where('role', 'coach')
                  ->where('status', 'active')
                  ->orderBy('name')
                  ->get();
    }
    
    /**
     * Get available participants for school and category
     */
    private function getAvailableParticipants($schoolId, $categoryId)
    {
        $participants = Participant::where('school_id', $schoolId)
                                 ->where('status', 'active')
                                 ->orderBy('name')
                                 ->get();
        
        // Filter by category eligibility
        $eligibleParticipants = [];
        
        foreach ($participants as $participant) {
            $eligibility = $this->categoryValidator->validateParticipantEligibility($participant->id, $categoryId);
            
            if ($eligibility['eligible']) {
                $eligibleParticipants[] = $participant;
            }
        }
        
        return $eligibleParticipants;
    }
    
    /**
     * Validate team registration data
     */
    private function validateTeamRegistrationData($data)
    {
        $required = ['category_id', 'team_name', 'coach_primary_id'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        // Validate team name
        if (strlen($data['team_name']) < 3) {
            throw new \Exception('Team name must be at least 3 characters long');
        }
        
        if (strlen($data['team_name']) > 100) {
            throw new \Exception('Team name must be less than 100 characters');
        }
        
        // Validate category exists
        $category = Category::find($data['category_id']);
        if (!$category || !$category->is_active) {
            throw new \Exception('Invalid category selected');
        }
        
        // Validate coach exists
        $coach = User::find($data['coach_primary_id']);
        if (!$coach || $coach->status !== 'active') {
            throw new \Exception('Invalid primary coach selected');
        }
        
        // Validate secondary coach if provided
        if (!empty($data['coach_secondary_id'])) {
            $secondaryCoach = User::find($data['coach_secondary_id']);
            if (!$secondaryCoach || $secondaryCoach->status !== 'active') {
                throw new \Exception('Invalid secondary coach selected');
            }
        }
    }
    
    /**
     * Add participants to team
     */
    private function addParticipantsToTeam($teamRegistration, $participantIds, $userId)
    {
        foreach ($participantIds as $participantId) {
            try {
                $teamRegistration->addParticipant($participantId, 'regular', $userId);
            } catch (\Exception $e) {
                // Log error but continue with other participants
                error_log("Failed to add participant {$participantId} to team {$teamRegistration->id}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Check if user can register new team
     */
    private function canRegisterNewTeam($schoolId)
    {
        // Check if school has reached maximum team limit
        $activeRegistrations = TeamRegistration::where('school_id', $schoolId)
                                             ->whereIn('registration_status', ['draft', 'submitted', 'under_review', 'approved'])
                                             ->count();
        
        // Assume maximum of 5 teams per school (configurable)
        $maxTeamsPerSchool = 5;
        
        return $activeRegistrations < $maxTeamsPerSchool;
    }
    
    /**
     * Check if user can access team registration
     */
    private function canAccessTeamRegistration($teamRegistration)
    {
        $userRole = $_SESSION['user_role'] ?? 'participant';
        $userId = $_SESSION['user_id'] ?? null;
        $schoolId = $_SESSION['school_id'] ?? null;
        
        // Admin access
        if (in_array($userRole, ['admin', 'super_admin'])) {
            return true;
        }
        
        // School coordinator access
        if ($userRole === 'school_coordinator' && $teamRegistration->school_id == $schoolId) {
            return true;
        }
        
        // Coach access (if they are assigned to the team)
        if ($userRole === 'coach' && 
            ($teamRegistration->coach_primary_id == $userId || $teamRegistration->coach_secondary_id == $userId)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if team registration can be modified
     */
    private function canModifyTeamRegistration($teamRegistration)
    {
        // Cannot modify if locked
        if ($teamRegistration->locked_for_modifications) {
            return false;
        }
        
        // Cannot modify if approved or rejected
        if (in_array($teamRegistration->registration_status, ['approved', 'rejected', 'withdrawn'])) {
            return false;
        }
        
        // Check deadline
        $deadlineStatus = $this->deadlineEnforcer->getDeadlineStatus();
        
        if ($deadlineStatus['active_competition'] && 
            $deadlineStatus['deadlines']['team_registration']['is_overdue']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user's school ID
     */
    private function getUserSchoolId()
    {
        return $_SESSION['school_id'] ?? null;
    }
}