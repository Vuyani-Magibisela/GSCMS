<?php
// Get current admin user information
$currentUser = $_SESSION['user'] ?? null;
$userName = $currentUser['name'] ?? 'Admin';
$userEmail = $currentUser['email'] ?? '';
?>

<div class="admin-user-menu-dropdown">
    <button class="admin-user-menu-toggle" id="adminUserMenuToggle" aria-expanded="false" aria-haspopup="true">
        <div class="admin-user-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="admin-user-info">
            <span class="admin-user-name"><?= htmlspecialchars($userName) ?></span>
            <span class="admin-user-role">Administrator</span>
        </div>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    
    <div class="admin-user-menu-content" id="adminUserMenuContent">
        <div class="admin-user-menu-header">
            <div class="admin-user-avatar-large">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="admin-user-details">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <small><?= htmlspecialchars($userEmail) ?></small>
                <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
            </div>
        </div>
        
        <div class="admin-user-menu-divider"></div>
        
        <nav class="admin-user-menu-nav">
            <a href="/admin/profile" class="admin-user-menu-item">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
            </a>
            
            <a href="/admin/settings" class="admin-user-menu-item">
                <i class="fas fa-cog"></i>
                <span>System Settings</span>
            </a>
            
            <a href="/admin/logs" class="admin-user-menu-item">
                <i class="fas fa-file-alt"></i>
                <span>System Logs</span>
            </a>
            
            <div class="admin-user-menu-divider"></div>
            
            <a href="/dashboard" class="admin-user-menu-item">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            
            <div class="admin-user-menu-divider"></div>
            
            <a href="/auth/logout" class="admin-user-menu-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
</div>

<style>
/* Admin User Menu Styles */
.admin-user-menu-dropdown {
    position: relative;
}

.admin-user-menu-toggle {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) var(--space-3);
    background: transparent;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: var(--transition-all);
    color: var(--text-primary);
}

.admin-user-menu-toggle:hover {
    background-color: var(--gray-50);
    border-color: var(--primary-color);
}

.admin-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-full);
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: var(--font-size-sm);
}

.admin-user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.admin-user-name {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--text-primary);
}

.admin-user-role {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
}

.dropdown-arrow {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
    transition: var(--transition-all);
}

.admin-user-menu-toggle[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

.admin-user-menu-content {
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 280px;
    background: var(--bg-primary);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    z-index: var(--z-dropdown);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition-all);
    margin-top: var(--space-2);
}

.admin-user-menu-content.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.admin-user-menu-header {
    padding: var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    background: var(--gradient-primary);
    color: white;
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
}

.admin-user-avatar-large {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-full);
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-lg);
}

.admin-user-details {
    flex: 1;
}

.admin-user-details strong {
    display: block;
    font-size: var(--font-size-base);
    margin-bottom: var(--space-1);
}

.admin-user-details small {
    display: block;
    opacity: 0.8;
    font-size: var(--font-size-xs);
    margin-bottom: var(--space-2);
}

.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-1) var(--space-2);
    background: rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-base);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
}

.admin-user-menu-divider {
    height: 1px;
    background: var(--gray-200);
    margin: var(--space-2) 0;
}

.admin-user-menu-nav {
    padding: var(--space-2);
}

.admin-user-menu-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-lg);
    transition: var(--transition-all);
    font-size: var(--font-size-sm);
}

.admin-user-menu-item:hover {
    background-color: var(--gray-50);
    color: var(--primary-color);
    text-decoration: none;
}

.admin-user-menu-item.logout-item:hover {
    background-color: rgba(255, 107, 107, 0.1);
    color: var(--error-color);
}

.admin-user-menu-item i {
    width: 16px;
    text-align: center;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .admin-user-info {
        display: none;
    }
    
    .admin-user-menu-content {
        right: -10px;
        min-width: 260px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const adminUserMenuToggle = document.getElementById('adminUserMenuToggle');
    const adminUserMenuContent = document.getElementById('adminUserMenuContent');
    
    if (adminUserMenuToggle && adminUserMenuContent) {
        adminUserMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = adminUserMenuContent.classList.contains('active');
            
            // Close all other dropdowns
            document.querySelectorAll('.admin-user-menu-content.active').forEach(menu => {
                menu.classList.remove('active');
            });
            
            if (!isOpen) {
                adminUserMenuContent.classList.add('active');
                adminUserMenuToggle.setAttribute('aria-expanded', 'true');
            } else {
                adminUserMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            adminUserMenuContent.classList.remove('active');
            adminUserMenuToggle.setAttribute('aria-expanded', 'false');
        });
        
        // Prevent dropdown from closing when clicking inside
        adminUserMenuContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Keyboard navigation
        adminUserMenuToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                adminUserMenuToggle.click();
            }
        });
    }
});
</script>