<?php
// app/Controllers/Admin/TeamManagementController.php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Team;
use App\Models\School;
use App\Models\Category;
use App\Models\Participant;
use App\Models\User;
use App\Core\Request;
use Exception;

class TeamManagementController extends BaseController
{
    protected $teamModel;
    protected $schoolModel;
    protected $categoryModel;
    protected $participantModel;
    protected $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->teamModel = new Team();
        $this->schoolModel = new School();
        $this->categoryModel = new Category();
        $this->participantModel = new Participant();
        $this->userModel = new User();
    }
    
    /**
     * Display team management dashboard
     */
    public function index()
    {
        try {
            // Get all teams with summary data
            $teams = $this->teamModel->getTeamsWithParticipantCount();
            
            // Get registration statistics
            $stats = $this->getTeamStatistics();
            
            // Get available categories
            $categories = $this->categoryModel->getAll();
            
            // Get schools for filtering
            $schools = $this->schoolModel->getAll();
            
            $data = [
                'teams' => $teams,
                'stats' => $stats,
                'categories' => $categories,
                'schools' => $schools,
                'title' => 'Team Management',
                'pageTitle' => 'Team Management',
                'pageSubtitle' => 'Manage competition teams, participants, and registrations',
                'pageCSS' => ['/css/admin-teams.css'],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => $this->baseUrl('/admin/dashboard')],
                    ['title' => 'Team Management', 'url' => '']
                ]
            ];
            
            return $this->view('admin/teams/index', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Error in TeamManagementController@index: ' . $e->getMessage());
            return $this->errorResponse('Unable to load team management dashboard', 500);
        }
    }
    
    /**
     * Show team creation form
     */
    public function create()
    {
        try {
            // Get approved schools
            $schools = $this->schoolModel->getByStatus('approved');
            
            // Get active categories with subdivisions
            $categories = $this->getCategoriesWithSubdivisions();
            
            // Get available coaches
            $coaches = $this->getAvailableCoaches();
            
            $data = [
                'schools' => $schools,
                'categories' => $categories,
                'coaches' => $coaches,
                'title' => 'Create New Team',
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => $this->baseUrl('/admin/dashboard')],
                    ['title' => 'Team Management', 'url' => $this->baseUrl('/admin/teams')],
                    ['title' => 'Create Team', 'url' => '']
                ]
            ];
            
            return $this->view('admin/teams/create', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Error in TeamManagementController@create: ' . $e->getMessage());
            return $this->errorResponse('Unable to load team creation form', 500);
        }
    }
    
    /**
     * Store new team
     */
    public function store()
    {
        try {
            $request = new Request();
            
            // Validate team creation
            $validation = $this->validateTeamCreation($request);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 422);
            }
            
            // Start database transaction
            $this->db->beginTransaction();
            
            // Generate unique team code
            $teamCode = $this->teamModel->generateTeamCode(
                $request->post('school_id'), 
                $request->post('category_id')
            );
            
            // Prepare team data
            $teamData = [
                'name' => $request->post('name'),
                'school_id' => $request->post('school_id'),
                'category_id' => $request->post('category_id'),
                'team_code' => $teamCode,
                'coach1_id' => $request->post('coach1_id'),
                'coach2_id' => $request->post('coach2_id'),
                'status' => Team::STATUS_REGISTERED,
                'robot_name' => $request->post('robot_name'),
                'robot_description' => $request->post('robot_description'),
                'programming_language' => $request->post('programming_language'),
                'special_requirements' => $request->post('special_requirements'),
                'emergency_contact_name' => $request->post('emergency_contact_name'),
                'emergency_contact_phone' => $request->post('emergency_contact_phone'),
                'emergency_contact_relationship' => $request->post('emergency_contact_relationship'),
                'phase_id' => 1, // Start with phase 1
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Create team
            $teamId = $this->db->table('teams')->insertGetId($teamData);
            
            // Log team creation
            $this->logger->info("Team created: {$teamData['name']} (ID: {$teamId}) for school: {$teamData['school_id']}");
            
            $this->db->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team created successfully!',
                'team_id' => $teamId,
                'redirect' => $this->baseUrl('/admin/teams/' . $teamId)
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error('Error creating team: ' . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error creating team: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show team details
     */
    public function show($id)
    {
        try {
            // Handle Request object parameter extraction
            if (is_object($id) && method_exists($id, 'getParameter')) {
                $actualId = $id->getParameter('id');
                if (empty($actualId) && method_exists($id, 'getUri')) {
                    $uri = $id->getUri();
                    if (preg_match('/\/admin\/teams\/(\d+)/', $uri, $matches)) {
                        $actualId = $matches[1];
                    }
                }
            } else {
                $actualId = $id;
            }
            
            // Get team with all related data
            $team = $this->getTeamDetails($actualId);
            if (!$team) {
                return $this->errorResponse('Team not found', 404);
            }
            
            // Get team participants
            $participants = $this->participantModel->getByTeam($actualId);
            
            // Get team performance data
            $performance = $this->getTeamPerformance($actualId);
            
            // Get available categories for editing
            $categories = $this->getCategoriesWithSubdivisions();
            
            // Get available coaches
            $coaches = $this->getAvailableCoaches();
            
            $data = [
                'team' => $team,
                'participants' => $participants,
                'performance' => $performance,
                'categories' => $categories,
                'coaches' => $coaches,
                'title' => 'Team Details - ' . $team['name'],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => $this->baseUrl('/admin/dashboard')],
                    ['title' => 'Team Management', 'url' => $this->baseUrl('/admin/teams')],
                    ['title' => $team['name'], 'url' => '']
                ]
            ];
            
            return $this->view('admin/teams/show', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Error in TeamManagementController@show: ' . $e->getMessage());
            return $this->errorResponse('Unable to load team details', 500);
        }
    }
    
    /**
     * Show team edit form
     */
    public function edit($id)
    {
        try {
            $team = $this->getTeamDetails($id);
            if (!$team) {
                return $this->errorResponse('Team not found', 404);
            }
            
            // Get available data for editing
            $schools = $this->schoolModel->getByStatus('approved');
            $categories = $this->getCategoriesWithSubdivisions();
            $coaches = $this->getAvailableCoaches();
            
            $data = [
                'team' => $team,
                'schools' => $schools,
                'categories' => $categories,
                'coaches' => $coaches,
                'title' => 'Edit Team - ' . $team['name'],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => $this->baseUrl('/admin/dashboard')],
                    ['title' => 'Team Management', 'url' => $this->baseUrl('/admin/teams')],
                    ['title' => $team['name'], 'url' => $this->baseUrl('/admin/teams/' . $id)],
                    ['title' => 'Edit', 'url' => '']
                ]
            ];
            
            return $this->view('admin/teams/edit', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Error in TeamManagementController@edit: ' . $e->getMessage());
            return $this->errorResponse('Unable to load team edit form', 500);
        }
    }
    
    /**
     * Update team
     */
    public function update($id)
    {
        try {
            $request = new Request();
            
            $team = $this->teamModel->find($id);
            if (!$team) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Team not found'
                ], 404);
            }
            
            // Validate team update
            $validation = $this->validateTeamUpdate($request, $id);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 422);
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Prepare update data
            $updateData = [
                'name' => $request->post('name'),
                'coach1_id' => $request->post('coach1_id'),
                'coach2_id' => $request->post('coach2_id'),
                'robot_name' => $request->post('robot_name'),
                'robot_description' => $request->post('robot_description'),
                'programming_language' => $request->post('programming_language'),
                'special_requirements' => $request->post('special_requirements'),
                'emergency_contact_name' => $request->post('emergency_contact_name'),
                'emergency_contact_phone' => $request->post('emergency_contact_phone'),
                'emergency_contact_relationship' => $request->post('emergency_contact_relationship'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update team
            $this->db->table('teams')->where('id', $id)->update($updateData);
            
            // Log team update
            $this->logger->info("Team updated: {$updateData['name']} (ID: {$id})");
            
            $this->db->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Team updated successfully!',
                'redirect' => $this->baseUrl('/admin/teams/' . $id)
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error('Error updating team: ' . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error updating team: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add participant to team
     */
    public function addParticipant($teamId)
    {
        try {
            $request = new Request();
            
            // Get team details
            $team = $this->getTeamDetails($teamId);
            if (!$team) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Team not found'
                ], 404);
            }
            
            // Validate participant addition
            $validation = $this->validateParticipantAddition($request, $teamId);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 422);
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Prepare participant data
            $participantData = [
                'team_id' => $teamId,
                'first_name' => $request->post('first_name'),
                'last_name' => $request->post('last_name'),
                'id_number' => $request->post('id_number'),
                'date_of_birth' => $request->post('date_of_birth'),
                'grade' => $request->post('grade'),
                'gender' => $request->post('gender'),
                'email' => $request->post('email'),
                'phone' => $request->post('phone'),
                'parent_guardian_name' => $request->post('parent_guardian_name'),
                'parent_guardian_phone' => $request->post('parent_guardian_phone'),
                'parent_guardian_email' => $request->post('parent_guardian_email'),
                'medical_conditions' => $request->post('medical_conditions'),
                'dietary_requirements' => $request->post('dietary_requirements'),
                'special_needs' => $request->post('special_needs'),
                'emergency_contact_name' => $request->post('emergency_contact_name'),
                'emergency_contact_phone' => $request->post('emergency_contact_phone'),
                'emergency_contact_relationship' => $request->post('emergency_contact_relationship'),
                'previous_experience' => $request->post('previous_experience'),
                'skills_assessment' => $request->post('skills_assessment'),
                't_shirt_size' => $request->post('t_shirt_size'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add participant
            $participantId = $this->db->table('participants')->insertGetId($participantData);
            
            // Update team size
            $this->updateTeamSize($teamId);
            
            // Log participant addition
            $this->logger->info("Participant added to team {$teamId}: {$participantData['first_name']} {$participantData['last_name']}");
            
            $this->db->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Participant added successfully!',
                'participant_id' => $participantId
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error('Error adding participant: ' . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error adding participant: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate team creation
     */
    protected function validateTeamCreation($request)
    {
        $errors = [];
        
        // Basic field validation
        $required = ['name', 'school_id', 'category_id', 'coach1_id'];
        foreach ($required as $field) {
            if (empty($request->post($field))) {
                $errors[$field] = ucfirst($field) . ' is required.';
            }
        }
        
        // Check if school already has team in this category
        if ($request->post('school_id') && $request->post('category_id')) {
            $existingTeam = $this->db->table('teams')
                ->where('school_id', $request->post('school_id'))
                ->where('category_id', $request->post('category_id'))
                ->whereNull('deleted_at')
                ->first();
                
            if ($existingTeam) {
                $errors['category_id'] = 'School already has a team in this category.';
            }
        }
        
        // Validate team name uniqueness within school
        if ($request->post('name') && $request->post('school_id')) {
            $existingName = $this->db->table('teams')
                ->where('name', $request->post('name'))
                ->where('school_id', $request->post('school_id'))
                ->whereNull('deleted_at')
                ->first();
                
            if ($existingName) {
                $errors['name'] = 'Team name already exists for this school.';
            }
        }
        
        // Validate coach availability
        if ($request->post('coach1_id')) {
            $coachTeamCount = $this->db->table('teams')
                ->where(function($query) use ($request) {
                    $query->where('coach1_id', $request->post('coach1_id'))
                          ->orWhere('coach2_id', $request->post('coach1_id'));
                })
                ->whereNull('deleted_at')
                ->count();
                
            if ($coachTeamCount >= 2) { // Max 2 teams per coach
                $errors['coach1_id'] = 'Coach is already assigned to maximum number of teams.';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate team update
     */
    protected function validateTeamUpdate($request, $teamId)
    {
        $errors = [];
        
        // Basic field validation
        $required = ['name'];
        foreach ($required as $field) {
            if (empty($request->post($field))) {
                $errors[$field] = ucfirst($field) . ' is required.';
            }
        }
        
        // Validate team name uniqueness within school (excluding current team)
        if ($request->post('name')) {
            $team = $this->teamModel->find($teamId);
            if ($team) {
                $existingName = $this->db->table('teams')
                    ->where('name', $request->post('name'))
                    ->where('school_id', $team['school_id'])
                    ->where('id', '!=', $teamId)
                    ->whereNull('deleted_at')
                    ->first();
                    
                if ($existingName) {
                    $errors['name'] = 'Team name already exists for this school.';
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate participant addition
     */
    protected function validateParticipantAddition($request, $teamId)
    {
        $errors = [];
        
        // Get team details for category validation
        $team = $this->getTeamDetails($teamId);
        if (!$team) {
            $errors['team'] = 'Team not found.';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Basic field validation
        $required = ['first_name', 'last_name', 'date_of_birth', 'grade', 'gender', 
                    'parent_guardian_name', 'parent_guardian_phone'];
        foreach ($required as $field) {
            if (empty($request->post($field))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Check team composition limits
        $currentParticipants = $this->db->table('participants')
            ->where('team_id', $teamId)
            ->whereNull('deleted_at')
            ->count();
            
        $categoryMaxParticipants = $this->getCategoryMaxParticipants($team['category_id']);
        
        if ($currentParticipants >= $categoryMaxParticipants) {
            $errors['team_size'] = "Team already has maximum participants ($categoryMaxParticipants) for this category.";
        }
        
        // Validate grade eligibility for category
        if ($request->post('grade') && $team['category_id']) {
            $gradeEligible = $this->validateGradeEligibility($request->post('grade'), $team['category_id']);
            if (!$gradeEligible) {
                $errors['grade'] = 'Participant grade is not eligible for this team\'s category.';
            }
        }
        
        // Check for duplicate participants (by ID number if provided)
        if ($request->post('id_number')) {
            $existingParticipant = $this->db->table('participants')
                ->where('id_number', $request->post('id_number'))
                ->whereNull('deleted_at')
                ->first();
                
            if ($existingParticipant) {
                $errors['id_number'] = 'Participant with this ID number already exists.';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get categories with subdivisions
     */
    protected function getCategoriesWithSubdivisions()
    {
        $query = "
            SELECT 
                c.*,
                cs.id as subdivision_id,
                cs.name as subdivision_name,
                cs.code as subdivision_code,
                cs.min_grade,
                cs.max_grade,
                cs.max_participants as subdivision_max_participants
            FROM categories c
            LEFT JOIN category_subdivisions cs ON c.id = cs.category_id AND cs.active = 1
            ORDER BY c.name, cs.min_grade
        ";
        
        $results = $this->db->query($query);
        
        // Group by category
        $categories = [];
        foreach ($results as $row) {
            if (!isset($categories[$row['id']])) {
                $categories[$row['id']] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'grade_range' => $row['grade_range'],
                    'max_team_size' => $row['max_team_size'],
                    'subdivisions' => []
                ];
            }
            
            if ($row['subdivision_id']) {
                $categories[$row['id']]['subdivisions'][] = [
                    'id' => $row['subdivision_id'],
                    'name' => $row['subdivision_name'],
                    'code' => $row['subdivision_code'],
                    'min_grade' => $row['min_grade'],
                    'max_grade' => $row['max_grade'],
                    'max_participants' => $row['subdivision_max_participants']
                ];
            }
        }
        
        return array_values($categories);
    }
    
    /**
     * Get available coaches
     */
    protected function getAvailableCoaches()
    {
        return $this->db->table('users')
            ->where('role', 'coach')
            ->where('status', 'active')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->orderBy('first_name')
            ->get();
    }
    
    /**
     * Get team details with related information
     */
    protected function getTeamDetails($teamId)
    {
        $query = "
            SELECT 
                t.*,
                s.name as school_name,
                s.district as school_district,
                c.name as category_name,
                c.code as category_code,
                c.grade_range,
                CONCAT(u1.first_name, ' ', u1.last_name) as coach1_name,
                CONCAT(u2.first_name, ' ', u2.last_name) as coach2_name,
                COUNT(p.id) as participant_count
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u1 ON t.coach1_id = u1.id
            LEFT JOIN users u2 ON t.coach2_id = u2.id
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE t.id = ? AND t.deleted_at IS NULL
            GROUP BY t.id
        ";
        
        $results = $this->db->query($query, [$teamId]);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Get team statistics
     */
    protected function getTeamStatistics()
    {
        $stats = [
            'total_teams' => $this->db->table('teams')->whereNull('deleted_at')->count(),
            'teams_by_status' => [],
            'teams_by_category' => [],
            'total_participants' => $this->db->table('participants')->whereNull('deleted_at')->count()
        ];
        
        // Teams by status
        $statusStats = $this->db->table('teams')
            ->select(['status', 'COUNT(*) as count'])
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();
            
        foreach ($statusStats as $stat) {
            $stats['teams_by_status'][$stat['status']] = $stat['count'];
        }
        
        // Teams by category
        $categoryStats = $this->db->query("
            SELECT 
                c.name as category_name,
                COUNT(t.id) as team_count,
                COUNT(p.id) as participant_count
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            GROUP BY c.id, c.name
            ORDER BY c.name
        ");
        
        $stats['teams_by_category'] = $categoryStats;
        
        return $stats;
    }
    
    /**
     * Get team performance data
     */
    protected function getTeamPerformance($teamId)
    {
        // Placeholder for performance metrics
        return [
            'total_score' => 0,
            'ranking' => null,
            'completed_challenges' => 0,
            'advancement_eligible' => false
        ];
    }
    
    /**
     * Get category maximum participants
     */
    protected function getCategoryMaxParticipants($categoryId)
    {
        $category = $this->db->table('categories')->find($categoryId);
        return $category['max_team_size'] ?? 4;
    }
    
    /**
     * Validate grade eligibility for category
     */
    protected function validateGradeEligibility($grade, $categoryId)
    {
        // Get category grade range
        $query = "
            SELECT c.grade_range, cs.min_grade, cs.max_grade
            FROM categories c
            LEFT JOIN category_subdivisions cs ON c.id = cs.category_id AND cs.active = 1
            WHERE c.id = ?
        ";
        
        $results = $this->db->query($query, [$categoryId]);
        
        if (empty($results)) {
            return false;
        }
        
        // Check against subdivisions if they exist
        foreach ($results as $result) {
            if ($result['min_grade'] !== null && $result['max_grade'] !== null) {
                $minGrade = ($result['min_grade'] == 0) ? 'R' : $result['min_grade'];
                $maxGrade = $result['max_grade'];
                
                $gradeNum = ($grade == 'R') ? 0 : (int)$grade;
                
                if ($gradeNum >= $result['min_grade'] && $gradeNum <= $result['max_grade']) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Update team size counter
     */
    protected function updateTeamSize($teamId)
    {
        $participantCount = $this->db->table('participants')
            ->where('team_id', $teamId)
            ->whereNull('deleted_at')
            ->count();
            
        $this->db->table('teams')
            ->where('id', $teamId)
            ->update(['team_size' => $participantCount]);
    }
}