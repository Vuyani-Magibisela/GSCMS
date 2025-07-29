<?php
// Get current user for admin sidebar permissions
$userRole = $_SESSION['user']['role'] ?? 'guest';
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Helper function to check if current page matches navigation item
function isActiveAdminNav($path, $currentPath) {
    return strpos($currentPath, $path) === 0 ? 'active' : '';
}
?>

<ul class="admin-nav-menu">
    <!-- Dashboard -->
    <li class="nav-item">
        <a href="/admin/dashboard" class="nav-link <?= isActiveAdminNav('/admin/dashboard', $currentPath) ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>
    
    <!-- User Management -->
    <li class="nav-section-title">
        <span>User Management</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/users" class="nav-link <?= isActiveAdminNav('/admin/users', $currentPath) ?>">
            <i class="nav-icon fas fa-users"></i>
            <span class="nav-text">Users</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/roles" class="nav-link <?= isActiveAdminNav('/admin/roles', $currentPath) ?>">
            <i class="nav-icon fas fa-user-shield"></i>
            <span class="nav-text">Roles & Permissions</span>
        </a>
    </li>
    
    <!-- School & Team Management -->
    <li class="nav-section-title">
        <span>School & Teams</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/schools" class="nav-link <?= isActiveAdminNav('/admin/schools', $currentPath) ?>">
            <i class="nav-icon fas fa-school"></i>
            <span class="nav-text">Schools</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/teams" class="nav-link <?= isActiveAdminNav('/admin/teams', $currentPath) ?>">
            <i class="nav-icon fas fa-users"></i>
            <span class="nav-text">Teams</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/participants" class="nav-link <?= isActiveAdminNav('/admin/participants', $currentPath) ?>">
            <i class="nav-icon fas fa-user-friends"></i>
            <span class="nav-text">Participants</span>
        </a>
    </li>
    
    <!-- Competition Management -->
    <li class="nav-section-title">
        <span>Competition</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/competitions" class="nav-link <?= isActiveAdminNav('/admin/competitions', $currentPath) ?>">
            <i class="nav-icon fas fa-trophy"></i>
            <span class="nav-text">Competitions</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/categories" class="nav-link <?= isActiveAdminNav('/admin/categories', $currentPath) ?>">
            <i class="nav-icon fas fa-list"></i>
            <span class="nav-text">Categories</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/schedule" class="nav-link <?= isActiveAdminNav('/admin/schedule', $currentPath) ?>">
            <i class="nav-icon fas fa-calendar-alt"></i>
            <span class="nav-text">Schedule</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/venues" class="nav-link <?= isActiveAdminNav('/admin/venues', $currentPath) ?>">
            <i class="nav-icon fas fa-map-marker-alt"></i>
            <span class="nav-text">Venues</span>
        </a>
    </li>
    
    <!-- Judging System -->
    <li class="nav-section-title">
        <span>Judging</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/judges" class="nav-link <?= isActiveAdminNav('/admin/judges', $currentPath) ?>">
            <i class="nav-icon fas fa-gavel"></i>
            <span class="nav-text">Judges</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/scoring" class="nav-link <?= isActiveAdminNav('/admin/scoring', $currentPath) ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <span class="nav-text">Scoring System</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/rubrics" class="nav-link <?= isActiveAdminNav('/admin/rubrics', $currentPath) ?>">
            <i class="nav-icon fas fa-tasks"></i>
            <span class="nav-text">Rubrics</span>
        </a>
    </li>
    
    <!-- Resources & Communication -->
    <li class="nav-section-title">
        <span>Resources</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/resources" class="nav-link <?= isActiveAdminNav('/admin/resources', $currentPath) ?>">
            <i class="nav-icon fas fa-download"></i>
            <span class="nav-text">Resources</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/announcements" class="nav-link <?= isActiveAdminNav('/admin/announcements', $currentPath) ?>">
            <i class="nav-icon fas fa-bullhorn"></i>
            <span class="nav-text">Announcements</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/communications" class="nav-link <?= isActiveAdminNav('/admin/communications', $currentPath) ?>">
            <i class="nav-icon fas fa-envelope"></i>
            <span class="nav-text">Communications</span>
        </a>
    </li>
    
    <!-- Reports & Analytics -->
    <li class="nav-section-title">
        <span>Reports</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/reports" class="nav-link <?= isActiveAdminNav('/admin/reports', $currentPath) ?>">
            <i class="nav-icon fas fa-chart-bar"></i>
            <span class="nav-text">Reports</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/analytics" class="nav-link <?= isActiveAdminNav('/admin/analytics', $currentPath) ?>">
            <i class="nav-icon fas fa-chart-line"></i>
            <span class="nav-text">Analytics</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/exports" class="nav-link <?= isActiveAdminNav('/admin/exports', $currentPath) ?>">
            <i class="nav-icon fas fa-file-export"></i>
            <span class="nav-text">Data Export</span>
        </a>
    </li>
    
    <!-- System Administration -->
    <li class="nav-section-title">
        <span>System</span>
    </li>
    
    <li class="nav-item">
        <a href="/admin/settings" class="nav-link <?= isActiveAdminNav('/admin/settings', $currentPath) ?>">
            <i class="nav-icon fas fa-cog"></i>
            <span class="nav-text">Settings</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/logs" class="nav-link <?= isActiveAdminNav('/admin/logs', $currentPath) ?>">
            <i class="nav-icon fas fa-file-alt"></i>
            <span class="nav-text">System Logs</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/backup" class="nav-link <?= isActiveAdminNav('/admin/backup', $currentPath) ?>">
            <i class="nav-icon fas fa-database"></i>
            <span class="nav-text">Backup</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="/admin/maintenance" class="nav-link <?= isActiveAdminNav('/admin/maintenance', $currentPath) ?>">
            <i class="nav-icon fas fa-tools"></i>
            <span class="nav-text">Maintenance</span>
        </a>
    </li>
    
    <!-- Quick Actions -->
    <li class="nav-section-title">
        <span>Quick Actions</span>
    </li>
    
    <li class="nav-item">
        <a href="/dashboard" class="nav-link">
            <i class="nav-icon fas fa-arrow-left"></i>
            <span class="nav-text">Back to Dashboard</span>
        </a>
    </li>
</ul>

<style>
/* Admin Navigation Styles */
.admin-nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-nav-menu .nav-section-title {
    padding: var(--space-6) var(--space-4) var(--space-2);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-bold);
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.5px;
    border-top: 1px solid var(--gray-200);
    margin-top: var(--space-4);
}

.admin-nav-menu .nav-section-title:first-child {
    border-top: none;
    margin-top: 0;
}

.admin-nav-menu .nav-item {
    margin-bottom: var(--space-1);
}

.admin-nav-menu .nav-link {
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

.admin-nav-menu .nav-link:hover {
    background-color: var(--gray-100);
    color: var(--primary-color);
    text-decoration: none;
}

.admin-nav-menu .nav-link.active {
    background-color: rgba(255, 107, 107, 0.1);
    color: var(--error-color);
    font-weight: var(--font-weight-medium);
}

.admin-nav-menu .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background-color: var(--error-color);
    border-radius: 0 2px 2px 0;
}

.admin-nav-menu .nav-icon {
    width: 20px;
    text-align: center;
    margin-right: var(--space-3);
    font-size: var(--font-size-base);
}

.admin-nav-menu .nav-text {
    flex: 1;
    font-size: var(--font-size-sm);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .admin-nav-menu .nav-link {
        padding: var(--space-4);
    }
    
    .admin-nav-menu .nav-icon {
        margin-right: var(--space-4);
    }
    
    .admin-nav-menu .nav-text {
        font-size: var(--font-size-base);
    }
}
</style>