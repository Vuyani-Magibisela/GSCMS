<?php
use App\Core\Auth;

// Get authentication instance
$auth = Auth::getInstance();
$isAuthenticated = $auth->check();
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Helper function to check if current page matches navigation item
function isActivePublicNav($path, $currentPath) {
    return strpos($currentPath, $path) === 0 ? 'active' : '';
}
?>

<nav class="public-nav">
    <div class="container">
        <div class="nav-brand">
            <a href="/" class="brand-link">
                <i class="fas fa-robot brand-icon"></i>
                <span class="brand-text">GSCMS</span>
            </a>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="/" class="nav-link <?= isActivePublicNav('/', $currentPath) ?>">
                    <span class="nav-text">Home</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/competitions/public" class="nav-link <?= isActivePublicNav('/competitions/public', $currentPath) ?>">
                    <span class="nav-text">Competitions</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/about" class="nav-link <?= isActivePublicNav('/about', $currentPath) ?>">
                    <span class="nav-text">About</span>
                </a>
            </li>
        </ul>
        
        <div class="nav-actions">
            <?php if (!$isAuthenticated): ?>
                <a href="/auth/login" class="btn btn-outline">Login</a>
                <a href="/auth/register" class="btn btn-primary">Register</a>
            <?php else: ?>
                <a href="/dashboard" class="btn btn-primary">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
/* Public Navigation Styles */
.public-nav {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--gray-200);
    position: sticky;
    top: 0;
    z-index: var(--z-sticky);
}

.public-nav .container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4) var(--space-6);
    height: 64px;
}

.nav-brand .brand-link {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    text-decoration: none;
    color: var(--text-primary);
}

.nav-brand .brand-icon {
    font-size: var(--font-size-xl);
    color: var(--primary-color);
}

.nav-brand .brand-text {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-bold);
    color: var(--text-primary);
}

.public-nav .nav-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: var(--space-6);
}

.public-nav .nav-item {
    margin: 0;
}

.public-nav .nav-link {
    display: flex;
    align-items: center;
    padding: var(--space-2) var(--space-3);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-lg);
    transition: var(--transition-all);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-medium);
}

.public-nav .nav-link:hover {
    background-color: var(--gray-100);
    color: var(--primary-color);
    text-decoration: none;
}

.public-nav .nav-link.active {
    background-color: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .public-nav .container {
        padding: var(--space-3) var(--space-4);
    }
    
    .public-nav .nav-menu {
        display: none;
    }
    
    .nav-brand .brand-text {
        display: none;
    }
    
    .nav-actions .btn {
        padding: var(--space-2) var(--space-4);
        font-size: var(--font-size-sm);
    }
}

@media (max-width: 480px) {
    .nav-actions {
        gap: var(--space-2);
    }
    
    .nav-actions .btn {
        padding: var(--space-2) var(--space-3);
        font-size: var(--font-size-xs);
    }
}
</style>