<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Competition Setup Dashboard Header -->
<div class="dashboard-header">
    <div class="dashboard-welcome">
        <h2 class="welcome-title">Competition Setup Interface</h2>
        <p class="welcome-subtitle">Comprehensive competition configuration and management system.</p>
    </div>
    
    <!-- Quick Actions Panel -->
    <div class="quick-actions-panel">
        <h3 class="quick-actions-title">Quick Setup Actions</h3>
        <div class="quick-actions-grid">
            <a href="<?= url('/admin/competition-setup/wizard/start') ?>" class="quick-action-btn primary">
                <i class="fas fa-magic"></i>
                <span>New Competition Wizard</span>
            </a>
            <a href="<?= url('/admin/phase-scheduler') ?>" class="quick-action-btn info">
                <i class="fas fa-calendar-alt"></i>
                <span>Phase Scheduler</span>
            </a>
            <a href="<?= url('/admin/category-manager') ?>" class="quick-action-btn success">
                <i class="fas fa-cogs"></i>
                <span>Category Manager</span>
            </a>
            <a href="<?= url('/admin/competition-setup/wizard') ?>" class="quick-action-btn warning">
                <i class="fas fa-list"></i>
                <span>View All Competitions</span>
            </a>
        </div>
    </div>
</div>

<!-- Competition Statistics Cards -->
<div class="stats-grid">
    <div class="stats-row">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($statistics['total_competitions'] ?? 0) ?></div>
                <div class="stat-label">Total Competitions</div>
                <div class="stat-change">
                    <i class="fas fa-info-circle"></i>
                    <span><?= $statistics['active_competitions'] ?? 0 ?> active</span>
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($statistics['draft_competitions'] ?? 0) ?></div>
                <div class="stat-label">Draft Competitions</div>
                <div class="stat-change">
                    <i class="fas fa-edit"></i>
                    <span>In development</span>
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-flask"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($statistics['pilot_competitions'] ?? 0) ?></div>
                <div class="stat-label">Pilot Programs</div>
                <div class="stat-change">
                    <i class="fas fa-chart-line"></i>
                    <span>Testing phase</span>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= date('Y') ?></div>
                <div class="stat-label">Current Year</div>
                <div class="stat-change">
                    <i class="fas fa-calendar"></i>
                    <span>Competition season</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Content Grid -->
<div class="dashboard-content-grid">
    <!-- Left Column -->
    <div class="dashboard-left-column">
        <!-- Recent Competitions Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-trophy"></i>
                    Recent Competitions
                </h3>
                <a href="<?= url('/admin/competition-setup/wizard') ?>" class="widget-action">View All</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($recent_competitions)): ?>
                    <div class="competition-list">
                        <?php foreach ($recent_competitions as $competition): ?>
                            <div class="competition-item">
                                <div class="competition-icon <?= $competition['status'] ?? 'draft' ?>">
                                    <i class="fas fa-<?= $competition['status'] === 'active' ? 'play-circle' : ($competition['status'] === 'draft' ? 'edit' : 'pause-circle') ?>"></i>
                                </div>
                                <div class="competition-content">
                                    <h4 class="competition-name"><?= htmlspecialchars($competition['name']) ?></h4>
                                    <p class="competition-type"><?= htmlspecialchars(ucfirst($competition['type'])) ?> Competition</p>
                                    <div class="competition-meta">
                                        <span class="competition-year"><?= $competition['year'] ?></span>
                                        <span class="competition-status status-<?= $competition['status'] ?>"><?= ucfirst($competition['status']) ?></span>
                                    </div>
                                </div>
                                <a href="<?= url('/admin/competition-setup/view/' . $competition['id']) ?>" class="competition-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <p>No competitions yet</p>
                        <small>Start by creating your first competition using the wizard.</small>
                        <a href="<?= url('/admin/competition-setup/wizard/start') ?>" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-magic"></i> Start Wizard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Competition Setup Guide Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-graduation-cap"></i>
                    Setup Guide
                </h3>
            </div>
            <div class="widget-content">
                <div class="guide-steps">
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5>Competition Wizard</h5>
                            <p>Use the 6-step wizard to create a new competition with all required settings.</p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5>Phase Scheduler</h5>
                            <p>Configure competition phases, timelines, and advancement criteria.</p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5>Category Manager</h5>
                            <p>Set up category rules, scoring rubrics, and equipment requirements.</p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h5>Deploy & Test</h5>
                            <p>Review configuration and deploy competition for registration.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="dashboard-right-column">
        <!-- System Features Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-tools"></i>
                    Available Features
                </h3>
            </div>
            <div class="widget-content">
                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="feature-content">
                            <h5>6-Step Competition Wizard</h5>
                            <p>Guided setup for comprehensive competition configuration</p>
                        </div>
                        <div class="feature-status available">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="feature-content">
                            <h5>Advanced Phase Scheduling</h5>
                            <p>Timeline management with conflict detection</p>
                        </div>
                        <div class="feature-status available">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="feature-content">
                            <h5>Dynamic Category Configuration</h5>
                            <p>Flexible rule and scoring system setup</p>
                        </div>
                        <div class="feature-status available">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-copy"></i>
                        </div>
                        <div class="feature-content">
                            <h5>Competition Cloning</h5>
                            <p>Duplicate existing competitions for new years</p>
                        </div>
                        <div class="feature-status available">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <div class="feature-content">
                            <h5>Configuration Export/Import</h5>
                            <p>Backup and share competition configurations</p>
                        </div>
                        <div class="feature-status available">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="widget-content">
                <div class="action-buttons">
                    <a href="<?= url('/admin/competition-setup/wizard/start') ?>" class="action-btn primary">
                        <i class="fas fa-magic"></i>
                        <span>Create New Competition</span>
                        <small>Start the step-by-step wizard</small>
                    </a>
                    
                    <button onclick="cloneLatestCompetition()" class="action-btn info">
                        <i class="fas fa-copy"></i>
                        <span>Clone Last Competition</span>
                        <small>Duplicate for new year</small>
                    </button>
                    
                    <a href="<?= url('/admin/phase-scheduler') ?>" class="action-btn success">
                        <i class="fas fa-calendar-check"></i>
                        <span>Manage Phases</span>
                        <small>Schedule and timeline</small>
                    </a>
                    
                    <a href="<?= url('/admin/category-manager') ?>" class="action-btn warning">
                        <i class="fas fa-sliders-h"></i>
                        <span>Configure Categories</span>
                        <small>Rules and scoring</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cloneLatestCompetition() {
    // Implementation for cloning functionality
    alert('Clone functionality will be implemented');
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>