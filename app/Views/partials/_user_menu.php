<?php
use App\Core\Auth;
use App\Models\User;

// Get authentication instance and current user info
$auth = Auth::getInstance();
$isAuthenticated = $auth->check();
$currentUser = $auth->user();

// Only show user menu if authenticated
if (!$isAuthenticated || !$currentUser) {
    return;
}

$userRole = $currentUser->role ?? 'guest';
$userName = $currentUser->name ?? 'User';
$userEmail = $currentUser->email ?? '';
?>

<div class="user-menu-dropdown">
    <button class="user-menu-toggle" id="userMenuToggle" aria-expanded="false" aria-haspopup="true">
        <div class="user-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            <span class="user-role"><?= htmlspecialchars(ucfirst($userRole)) ?></span>
        </div>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    
    <div class="user-menu-content" id="userMenuContent">
        <div class="user-menu-header">
            <div class="user-avatar-large">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <small><?= htmlspecialchars($userEmail) ?></small>
            </div>
        </div>
        
        <div class="user-menu-divider"></div>
        
        <nav class="user-menu-nav">
            <a href="<?= url('/dashboard') ?>" class="user-menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?= url('/profile') ?>" class="user-menu-item">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
            </a>
            
            <a href="<?= url('/settings') ?>" class="user-menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
            <?php if (in_array($userRole, [User::SUPER_ADMIN, User::COMPETITION_ADMIN])): ?>
                <div class="user-menu-divider"></div>
                <a href="<?= url('/admin/dashboard') ?>" class="user-menu-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Admin Panel</span>
                </a>
            <?php endif; ?>
            
            <div class="user-menu-divider"></div>
            
            <a href="<?= url('/auth/logout') ?>" class="user-menu-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenuContent = document.getElementById('userMenuContent');
    
    if (userMenuToggle && userMenuContent) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userMenuContent.classList.contains('active');
            
            // Close all other dropdowns
            document.querySelectorAll('.user-menu-content.active').forEach(menu => {
                menu.classList.remove('active');
            });
            
            if (!isOpen) {
                userMenuContent.classList.add('active');
                userMenuToggle.setAttribute('aria-expanded', 'true');
            } else {
                userMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userMenuContent.classList.remove('active');
            userMenuToggle.setAttribute('aria-expanded', 'false');
        });
        
        // Prevent dropdown from closing when clicking inside
        userMenuContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Keyboard navigation
        userMenuToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                userMenuToggle.click();
            }
        });
    }
});
</script>