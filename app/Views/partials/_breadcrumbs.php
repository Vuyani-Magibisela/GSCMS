<?php
// Breadcrumbs data should be passed as $breadcrumbs array
// Format: [['title' => 'Home', 'url' => '/'], ['title' => 'Admin', 'url' => '/admin'], ['title' => 'Users']]
if (!isset($breadcrumbs) || empty($breadcrumbs)) {
    return;
}
?>

<nav class="breadcrumb-nav" aria-label="Breadcrumb navigation">
    <ol class="breadcrumb">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php $isLast = ($index === count($breadcrumbs) - 1); ?>
            
            <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
                <?php if (!$isLast && isset($crumb['url'])): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-link">
                        <?php if (isset($crumb['icon'])): ?>
                            <i class="<?= htmlspecialchars($crumb['icon']) ?>"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($crumb['title']) ?>
                    </a>
                <?php else: ?>
                    <?php if (isset($crumb['icon'])): ?>
                        <i class="<?= htmlspecialchars($crumb['icon']) ?>"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($crumb['title']) ?>
                <?php endif; ?>
                
                <?php if (!$isLast): ?>
                    <i class="breadcrumb-separator fas fa-chevron-right"></i>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

<style>
/* Breadcrumb Styles */
.breadcrumb-nav {
    margin-bottom: var(--space-6);
}

.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    list-style: none;
    margin: 0;
    padding: var(--space-4) 0;
    font-size: var(--font-size-sm);
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    color: var(--text-muted);
}

.breadcrumb-item:not(:last-child) {
    margin-right: var(--space-3);
}

.breadcrumb-item.active {
    color: var(--text-primary);
    font-weight: var(--font-weight-medium);
}

.breadcrumb-link {
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--transition-all);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.breadcrumb-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.breadcrumb-separator {
    margin: 0 var(--space-3);
    font-size: var(--font-size-xs);
    color: var(--text-muted);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .breadcrumb {
        font-size: var(--font-size-xs);
    }
    
    .breadcrumb-item:not(:last-child) {
        margin-right: var(--space-2);
    }
    
    .breadcrumb-separator {
        margin: 0 var(--space-2);
    }
    
    /* Hide middle breadcrumbs on very small screens */
    @media (max-width: 480px) {
        .breadcrumb-item:not(:first-child):not(:last-child) {
            display: none;
        }
        
        .breadcrumb-item:first-child:not(:last-child)::after {
            content: '...';
            margin: 0 var(--space-2);
            color: var(--text-muted);
        }
    }
}

/* Accessibility enhancements */
.breadcrumb-link:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
    border-radius: var(--radius-base);
}

/* Dark theme support preparation */
@media (prefers-color-scheme: dark) {
    .breadcrumb-item {
        color: var(--text-muted, #9ca3af);
    }
    
    .breadcrumb-item.active {
        color: var(--text-primary, #f3f4f6);
    }
    
    .breadcrumb-link {
        color: var(--primary-color, #60a5fa);
    }
    
    .breadcrumb-link:hover {
        color: var(--primary-light, #93c5fd);
    }
}
</style>