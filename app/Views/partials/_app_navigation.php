<?php
use App\Core\Auth;
use App\Models\User;

// Get authentication instance and current user info
$auth = Auth::getInstance();
$isAuthenticated = $auth->check();
$currentUser = $auth->user();
$userRole = $currentUser ? $currentUser->role : 'guest';
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Helper function to check if current page matches navigation item
function isActiveNav($path, $currentPath) {
    return strpos($currentPath, $path) === 0 ? 'active' : '';
}

// Only show navigation if user is authenticated
if (!$isAuthenticated) {
    return;
}
?>

<ul class="nav-menu">
    <!-- Dashboard -->
    <li class="nav-item">
        <a href="/dashboard" class="nav-link <?= isActiveNav('/dashboard', $currentPath) ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>
    
    <!-- My Teams (for School Coordinators and Team Coaches) -->
    <?php if (in_array($userRole, [User::SCHOOL_COORDINATOR, User::TEAM_COACH])): ?>
        <li class="nav-item">
            <a href="/teams" class="nav-link <?= isActiveNav('/teams', $currentPath) ?>">
                <i class="nav-icon fas fa-users"></i>
                <span class="nav-text">My Teams</span>
            </a>
        </li>
    <?php endif; ?>
    
    <!-- Team Management (for Team Coaches) -->
    <?php if ($userRole === User::TEAM_COACH): ?>
        <li class="nav-item">
            <a href="/team-management" class="nav-link <?= isActiveNav('/team-management', $currentPath) ?>">
                <i class="nav-icon fas fa-user-friends"></i>
                <span class="nav-text">Team Management</span>
            </a>
        </li>
    <?php endif; ?>
    
    <!-- School Management (for School Coordinators) -->
    <?php if ($userRole === User::SCHOOL_COORDINATOR): ?>
        <li class="nav-item">
            <a href="/school-management" class="nav-link <?= isActiveNav('/school-management', $currentPath) ?>">
                <i class="nav-icon fas fa-school"></i>
                <span class="nav-text">School Management</span>
            </a>
        </li>
    <?php endif; ?>
    
    <!-- Judging (for Judges) -->
    <?php if ($userRole === User::JUDGE): ?>
        <li class="nav-item">
            <a href="/judging" class="nav-link <?= isActiveNav('/judging', $currentPath) ?>">
                <i class="nav-icon fas fa-gavel"></i>
                <span class="nav-text">Judging</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="/scorecards" class="nav-link <?= isActiveNav('/scorecards', $currentPath) ?>">
                <i class="nav-icon fas fa-clipboard-list"></i>
                <span class="nav-text">Scorecards</span>
            </a>
        </li>
    <?php endif; ?>
    
    <!-- Competition Information -->
    <li class="nav-section-title">
        <span>Competition</span>
    </li>
    
    <li class="nav-item">
        <a href="/categories" class="nav-link <?= isActiveNav('/categories', $currentPath) ?>">
            <i class="nav-icon fas fa-list"></i>
            <span class="nav-text">Categories</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/schedule" class="nav-link <?= isActiveNav('/schedule', $currentPath) ?>">
            <i class="nav-icon fas fa-calendar-alt"></i>
            <span class="nav-text">Schedule</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/resources" class="nav-link <?= isActiveNav('/resources', $currentPath) ?>">
            <i class="nav-icon fas fa-download"></i>
            <span class="nav-text">Resources</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/announcements" class="nav-link <?= isActiveNav('/announcements', $currentPath) ?>">
            <i class="nav-icon fas fa-bullhorn"></i>
            <span class="nav-text">Announcements</span>
        </a>
    </li>
    
    <!-- Results & Leaderboard -->
    <li class="nav-section-title">
        <span>Results</span>
    </li>
    
    <li class="nav-item">
        <a href="/leaderboard" class="nav-link <?= isActiveNav('/leaderboard', $currentPath) ?>">
            <i class="nav-icon fas fa-trophy"></i>
            <span class="nav-text">Leaderboard</span>
        </a>
    </li>
    
    <!-- Account -->
    <li class="nav-section-title">
        <span>Account</span>
    </li>
    
    <li class="nav-item">
        <a href="/profile" class="nav-link <?= isActiveNav('/profile', $currentPath) ?>">
            <i class="nav-icon fas fa-user-edit"></i>
            <span class="nav-text">Profile</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/settings" class="nav-link <?= isActiveNav('/settings', $currentPath) ?>">
            <i class="nav-icon fas fa-cog"></i>
            <span class="nav-text">Settings</span>
        </a>
    </li>
    
    <!-- Admin Panel (for Admins) -->
    <?php if (in_array($userRole, [User::SUPER_ADMIN, User::COMPETITION_ADMIN])): ?>
        <li class="nav-section-title">
            <span>Administration</span>
        </li>
        
        <li class="nav-item">
            <a href="/admin/dashboard" class="nav-link">
                <i class="nav-icon fas fa-shield-alt"></i>
                <span class="nav-text">Admin Panel</span>
            </a>
        </li>
    <?php endif; ?>
</ul>

<style>
/* App Navigation Styles */
.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-section-title {
    padding: var(--space-4) var(--space-4) var(--space-2);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-bold);
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}

.nav-item {
    margin-bottom: var(--space-1);
}

.nav-link {
    display: flex;
    align-items: center;
    padding: var(--space-3) var(--space-4);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-lg);
    margin: 0 var(--space-2);
    transition: var(--transition-all);
    position: relative;
}

.nav-link:hover {
    background-color: var(--gray-100);
    color: var(--primary-color);
    text-decoration: none;
}

.nav-link.active {
    background-color: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
    font-weight: var(--font-weight-medium);
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background-color: var(--primary-color);
    border-radius: 0 2px 2px 0;
}

.nav-icon {
    width: 20px;
    text-align: center;
    margin-right: var(--space-3);
    font-size: var(--font-size-base);
}

.nav-text {
    flex: 1;
    font-size: var(--font-size-sm);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .nav-link {
        padding: var(--space-4);
    }
    
    .nav-icon {
        margin-right: var(--space-4);
    }
    
    .nav-text {
        font-size: var(--font-size-base);
    }
}
</style>