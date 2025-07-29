<?php
// routes/rbac_examples.php - Examples of how to use the RBAC system

// This file shows examples of how to protect routes with the new RBAC system
// Copy these patterns to your actual routes/web.php file

use App\Core\Auth;

// Basic authentication middleware
$router->group(['middleware' => 'auth'], function($router) {
    
    // Dashboard - available to all authenticated users
    $router->get('/dashboard', 'DashboardController@index', 'dashboard');
    
    // Super Admin only routes
    $router->group(['middleware' => 'role:super_admin', 'prefix' => 'admin'], function($router) {
        $router->get('/system', 'Admin\SystemController@index', 'admin.system');
        $router->get('/users', 'Admin\UserController@index', 'admin.users');
        $router->post('/users', 'Admin\UserController@store', 'admin.users.store');
        $router->get('/users/{id}/edit', 'Admin\UserController@edit', 'admin.users.edit');
        $router->put('/users/{id}', 'Admin\UserController@update', 'admin.users.update');
        $router->delete('/users/{id}', 'Admin\UserController@destroy', 'admin.users.destroy');
    });
    
    // Competition Admin or Super Admin routes
    $router->group(['middleware' => 'role:competition_admin,super_admin', 'prefix' => 'admin'], function($router) {
        $router->get('/competitions', 'Admin\CompetitionController@index', 'admin.competitions');
        $router->post('/competitions', 'Admin\CompetitionController@store', 'admin.competitions.store');
        $router->get('/judges', 'Admin\JudgeController@index', 'admin.judges');
        $router->post('/judges', 'Admin\JudgeController@store', 'admin.judges.store');
    });
    
    // School Coordinator routes (can also be accessed by admins)
    $router->group(['middleware' => 'role:school_coordinator,competition_admin,super_admin', 'prefix' => 'coordinator'], function($router) {
        $router->get('/schools', 'Coordinator\SchoolController@index', 'coordinator.schools');
        $router->get('/teams', 'Coordinator\TeamController@index', 'coordinator.teams');
        $router->post('/teams', 'Coordinator\TeamController@store', 'coordinator.teams.store');
        $router->get('/participants', 'Coordinator\ParticipantController@index', 'coordinator.participants');
    });
    
    // Team Coach routes
    $router->group(['middleware' => 'role:team_coach,school_coordinator,competition_admin,super_admin', 'prefix' => 'coach'], function($router) {
        $router->get('/teams', 'Coach\TeamController@index', 'coach.teams');
        $router->get('/teams/{id}', 'Coach\TeamController@show', 'coach.teams.show');
        $router->put('/teams/{id}', 'Coach\TeamController@update', 'coach.teams.update');
    });
    
    // Judge routes
    $router->group(['middleware' => 'role:judge,competition_admin,super_admin', 'prefix' => 'judge'], function($router) {
        $router->get('/scoring', 'Judge\ScoringController@index', 'judge.scoring');
        $router->post('/scoring', 'Judge\ScoringController@store', 'judge.scoring.store');
        $router->get('/assignments', 'Judge\AssignmentController@index', 'judge.assignments');
    });
    
    // Permission-based routes (more granular control)
    $router->group(['middleware' => 'permission:' . Auth::PERM_USER_MANAGE], function($router) {
        $router->get('/users/manage', 'UserManagementController@index', 'user.management');
    });
    
    $router->group(['middleware' => 'permission:' . Auth::PERM_REPORT_VIEW], function($router) {
        $router->get('/reports', 'ReportController@index', 'reports.index');
    });
    
    $router->group(['middleware' => 'permission:' . Auth::PERM_REPORT_EXPORT], function($router) {
        $router->get('/reports/export', 'ReportController@export', 'reports.export');
    });
    
    // Multiple permission requirements (user must have ALL listed permissions)
    $router->group(['middleware' => 'permission:' . Auth::PERM_COMPETITION_MANAGE . ',' . Auth::PERM_JUDGE_MANAGE], function($router) {
        $router->get('/admin/competition-judging', 'Admin\CompetitionJudgingController@index', 'admin.competition.judging');
    });
    
    // Profile routes - available to all authenticated users
    $router->get('/profile', 'ProfileController@show', 'profile.show');
    $router->put('/profile', 'ProfileController@update', 'profile.update');
});

// Public routes (no authentication required)
$router->get('/', 'HomeController@index', 'home');
$router->get('/competitions/public', 'CompetitionController@publicIndex', 'competitions.public');
$router->get('/about', 'PageController@about', 'about');

// Authentication routes
$router->get('/auth/login', 'Auth\LoginController@showLoginForm', 'login');
$router->post('/auth/login', 'Auth\LoginController@login', 'login.post');
$router->post('/auth/logout', 'Auth\LoginController@logout', 'logout');
$router->get('/auth/register', 'Auth\RegisterController@showRegistrationForm', 'register');
$router->post('/auth/register', 'Auth\RegisterController@register', 'register.post');

/* CONTROLLER EXAMPLES:

class UserManagementController extends BaseController
{
    public function index()
    {
        // Check permissions in controller method
        $this->requirePermission(Auth::PERM_USER_MANAGE);
        
        // Or check multiple permissions
        $this->requireAnyPermission([Auth::PERM_USER_MANAGE, Auth::PERM_SYSTEM_ADMIN]);
        
        // Check resource ownership
        if (!$this->ownsResource('school', $schoolId) && !$this->isAdmin()) {
            $this->flash('error', 'Access denied');
            return $this->redirect('/dashboard');
        }
        
        return $this->view('admin.users.index');
    }
}

class TeamController extends BaseController
{
    public function show($id)
    {
        // Ensure user can view teams
        $this->requirePermission(Auth::PERM_TEAM_VIEW);
        
        // Check if user owns this specific team resource
        $this->requireResourceOwnership('team', $id);
        
        $team = Team::find($id);
        return $this->view('team.show', compact('team'));
    }
}

VIEW EXAMPLES:

<!-- In your view templates, you can use the helper functions -->

<?php if (hasRole('super_admin')): ?>
    <a href="/admin/system" class="btn btn-primary">System Admin</a>
<?php endif; ?>

<?php if (hasPermission(Auth::PERM_USER_MANAGE)): ?>
    <button onclick="manageUsers()">Manage Users</button>
<?php endif; ?>

<?php if (hasAnyRole(['school_coordinator', 'super_admin'])): ?>
    <div class="coordinator-panel">
        <h3>School Coordination</h3>
        <!-- School coordinator content -->
    </div>
<?php endif; ?>

<!-- Using inline helpers -->
<?= ifRole('competition_admin', '<a href="/admin/competitions">Manage Competitions</a>') ?>

<?= ifPermission(Auth::PERM_REPORT_EXPORT, '<button>Export Reports</button>') ?>

<!-- Display user info -->
<p>Welcome, <?= userName() ?></p>
<p>Your role: <?= roleDisplayName() ?></p>

<!-- Generate navigation based on permissions -->
<?php
$navigation = generateNavigation();
foreach ($navigation as $navItem): ?>
    <a href="<?= $navItem['url'] ?>" class="<?= activeClass($navItem['url']) ?>">
        <?= $navItem['title'] ?>
    </a>
<?php endforeach; ?>

*/