<?php
use App\Core\Auth;

// Get authentication status
$auth = Auth::getInstance();
$isAuthenticated = $auth->check();
$currentUser = $auth->user();
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Helper function for active class
function isActiveHeaderNav($path, $currentPath) {
    return strpos($currentPath, $path) === 0 ? 'active' : '';
}
?>

<!-- Navigation -->
<nav id="navbar">
    <div class="nav-container">
        <a href="/" class="logo">
            <i class="fas fa-robot"></i>
            SciBOTICS 2025
        </a>
        <ul class="nav-links">
            <li><a href="/" class="<?= isActiveHeaderNav('/', $currentPath) ?>">Home</a></li>
            <li><a href="/about" class="<?= isActiveHeaderNav('/about', $currentPath) ?>">About</a></li>
            <li><a href="/categories" class="<?= isActiveHeaderNav('/categories', $currentPath) ?>">Categories</a></li>
            <li><a href="/schedule" class="<?= isActiveHeaderNav('/schedule', $currentPath) ?>">Schedule</a></li>
            <?php if ($isAuthenticated): ?>
                <li><a href="/leaderboard" class="<?= isActiveHeaderNav('/leaderboard', $currentPath) ?>">Leaderboard</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-buttons">
            <?php if (!$isAuthenticated): ?>
                <a href="/auth/login" class="nav-btn login-btn">
                    <i class="fas fa-sign-in-alt me-1"></i>Login
                </a>
                <a href="/auth/register" class="nav-btn register-btn">
                    <i class="fas fa-user-plus me-1"></i>Register
                </a>
            <?php else: ?>
                <a href="/dashboard" class="nav-btn dashboard-btn">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle" id="userDropdownToggle" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
                        <?= htmlspecialchars($currentUser->name ?? 'User') ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <a href="/profile" class="dropdown-item">
                            <i class="fas fa-user-edit"></i>Profile
                        </a>
                        <a href="/settings" class="dropdown-item">
                            <i class="fas fa-cog"></i>Settings
                        </a>
                        <?php if (in_array($currentUser->role ?? '', ['super_admin', 'competition_admin'])): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/admin/dashboard" class="dropdown-item">
                                <i class="fas fa-shield-alt"></i>Admin Panel
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/auth/logout" class="dropdown-item logout-item">
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/about">About</a></li>
            <li><a href="/categories">Categories</a></li>
            <li><a href="/schedule">Schedule</a></li>
            <?php if ($isAuthenticated): ?>
                <li><a href="/leaderboard">Leaderboard</a></li>
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/profile">Profile</a></li>
                <li><a href="/settings">Settings</a></li>
                <?php if (in_array($currentUser->role ?? '', ['super_admin', 'competition_admin'])): ?>
                    <li><a href="/admin/dashboard">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="/auth/logout" class="logout-link">Logout</a></li>
            <?php endif; ?>
        </ul>
        <?php if (!$isAuthenticated): ?>
        <div class="mobile-nav-buttons">
            <a href="/auth/login" class="nav-btn login-btn">
                <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
            <a href="/auth/register" class="nav-btn register-btn">
                <i class="fas fa-user-plus me-1"></i>Register
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>