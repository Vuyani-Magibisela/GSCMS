<?php
use App\Core\Auth;

// Get authentication instance
$auth = Auth::getInstance();
$isAuthenticated = $auth->check();

// Helper function to check if current page matches navigation item
function isActivePublicNav($path) {
    return isActivePage($path) ? 'active' : '';
}
?>

<nav class="public-nav">
    <div class="container">
        <div class="nav-brand">
            <a href="<?= url('/') ?>" class="brand-link">
                <i class="fas fa-robot brand-icon"></i>
                <span class="brand-text">GSCMS</span>
            </a>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="<?= url('/') ?>" class="nav-link <?= isActivePublicNav('/') ?>">
                    <span class="nav-text">Home</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?= url('/competitions/public') ?>" class="nav-link <?= isActivePublicNav('/competitions/public') ?>">
                    <span class="nav-text">Competitions</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?= url('/scoreboard') ?>" class="nav-link <?= isActivePublicNav('/scoreboard') ?> live-scores-link">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span class="nav-text">Live Scores</span>
                    <span class="live-indicator" id="live-indicator" title="Live sessions active"></span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?= url('/about') ?>" class="nav-link <?= isActivePublicNav('/about') ?>">
                    <span class="nav-text">About</span>
                </a>
            </li>
        </ul>
        
        <div class="nav-actions">
            <?php if (!$isAuthenticated): ?>
                <a href="<?= url('/auth/login') ?>" class="btn btn-outline">Login</a>
                <a href="<?= url('/auth/register') ?>" class="btn btn-primary">Register</a>
            <?php else: ?>
                <a href="<?= url('/dashboard') ?>" class="btn btn-primary">Dashboard</a>
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

/* Live scores navigation styling */
.public-nav .live-scores-link {
    position: relative;
}

.public-nav .nav-icon {
    margin-right: var(--space-2);
    font-size: var(--font-size-sm);
}

.live-indicator {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 8px;
    height: 8px;
    background-color: var(--error-color);
    border-radius: 50%;
    display: none;
    animation: pulse 2s infinite;
}

.live-indicator.active {
    display: block;
}

@keyframes pulse {
    0% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.2);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
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