<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Dashboard Header with Quick Actions -->
<div class="dashboard-header">
    <div class="dashboard-welcome">
        <h2 class="welcome-title">Welcome back, <?= htmlspecialchars($_SESSION['user']['first_name'] ?? 'Admin') ?>!</h2>
        <p class="welcome-subtitle">Here's what's happening with your competition management system today.</p>
    </div>
    
    <!-- Quick Actions Panel -->
    <div class="quick-actions-panel">
        <h3 class="quick-actions-title">Quick Actions</h3>
        <div class="quick-actions-grid">
            <a href="/admin/schools/create" class="quick-action-btn">
                <i class="fas fa-school"></i>
                <span>Add School</span>
            </a>
            <a href="/admin/teams" class="quick-action-btn">
                <i class="fas fa-users"></i>
                <span>Manage Teams</span>
            </a>
            <a href="/admin/announcements/create" class="quick-action-btn">
                <i class="fas fa-bullhorn"></i>
                <span>Send Notice</span>
            </a>
            <a href="/admin/reports" class="quick-action-btn">
                <i class="fas fa-chart-bar"></i>
                <span>View Reports</span>
            </a>
            <a href="/admin/competitions" class="quick-action-btn">
                <i class="fas fa-trophy"></i>
                <span>Competitions</span>
            </a>
            <a href="/admin/system" class="quick-action-btn">
                <i class="fas fa-cog"></i>
                <span>System Status</span>
            </a>
        </div>
    </div>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Statistics Cards Grid -->
<div class="stats-grid">
    <!-- Core Statistics -->
    <div class="stats-row">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-school"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_schools'] ?? 0) ?></div>
                <div class="stat-label">Active Schools</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?= $stats['recent_registrations'] ?? 0 ?> this month</span>
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_teams'] ?? 0) ?></div>
                <div class="stat-label">Approved Teams</div>
                <div class="stat-change">
                    <i class="fas fa-info-circle"></i>
                    <span><?= $stats['pending_approvals'] ?? 0 ?> pending approval</span>
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_participants'] ?? 0) ?></div>
                <div class="stat-label">Total Participants</div>
                <div class="stat-change">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $stats['consent_completion_rate'] ?? 0 ?>% forms complete</span>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['active_competitions'] ?? 0) ?></div>
                <div class="stat-label">Active Competitions</div>
                <div class="stat-change">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?= count($upcomingDeadlines ?? []) ?> upcoming deadlines</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Statistics -->
    <div class="stats-row">
        <div class="stat-card secondary">
            <div class="stat-icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_users'] ?? 0) ?></div>
                <div class="stat-label">System Users</div>
                <div class="stat-change">
                    <i class="fas fa-sign-in-alt"></i>
                    <span><?= $stats['recent_logins'] ?? 0 ?> recent logins</span>
                </div>
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format(($pendingApprovals['teams'] ?? 0) + ($pendingApprovals['participants'] ?? 0) + ($pendingApprovals['consent_forms'] ?? 0)) ?></div>
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-change">
                    <i class="fas fa-clock"></i>
                    <span>Requires attention</span>
                </div>
            </div>
        </div>

        <div class="stat-card accent">
            <div class="stat-icon">
                <i class="fas fa-file-upload"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['submission_completion_rate'] ?? 0 ?>%</div>
                <div class="stat-label">Submissions Complete</div>
                <div class="stat-change">
                    <i class="fas fa-chart-line"></i>
                    <span>Track progress</span>
                </div>
            </div>
        </div>

        <div class="stat-card <?= ($systemHealth['status'] ?? 'unknown') === 'healthy' ? 'success' : 'warning' ?>">
            <div class="stat-icon">
                <i class="fas fa-server"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= ucfirst($systemHealth['status'] ?? 'Unknown') ?></div>
                <div class="stat-label">System Health</div>
                <div class="stat-change">
                    <i class="fas fa-memory"></i>
                    <span><?= $systemHealth['memory_usage'] ?? 0 ?>MB RAM</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Content Grid -->
<div class="dashboard-content-grid">
    <!-- Left Column -->
    <div class="dashboard-left-column">
        <!-- Pending Approvals Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-clock"></i>
                    Pending Approvals
                </h3>
                <a href="/admin/approvals" class="widget-action">View All</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($pendingApprovals) && array_sum($pendingApprovals) > 0): ?>
                    <div class="approval-list">
                        <?php if ($pendingApprovals['teams'] > 0): ?>
                            <div class="approval-item">
                                <div class="approval-icon teams">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="approval-content">
                                    <span class="approval-count"><?= $pendingApprovals['teams'] ?></span>
                                    <span class="approval-label">Team Registrations</span>
                                </div>
                                <a href="/admin/teams?status=pending" class="approval-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($pendingApprovals['consent_forms'] > 0): ?>
                            <div class="approval-item">
                                <div class="approval-icon consent">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                                <div class="approval-content">
                                    <span class="approval-count"><?= $pendingApprovals['consent_forms'] ?></span>
                                    <span class="approval-label">Consent Forms</span>
                                </div>
                                <a href="/admin/consent-forms?status=pending" class="approval-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($pendingApprovals['submissions'] > 0): ?>
                            <div class="approval-item">
                                <div class="approval-icon submissions">
                                    <i class="fas fa-upload"></i>
                                </div>
                                <div class="approval-content">
                                    <span class="approval-count"><?= $pendingApprovals['submissions'] ?></span>
                                    <span class="approval-label">Team Submissions</span>
                                </div>
                                <a href="/admin/submissions?status=pending" class="approval-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>All items approved!</p>
                        <small>No pending approvals at this time.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h3>
                <a href="/admin/activity" class="widget-action">View All</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($recentActivity)): ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $activity['color'] ?? 'text-muted' ?>">
                                    <i class="<?= $activity['icon'] ?? 'fas fa-circle' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-description"><?= htmlspecialchars($activity['description']) ?></p>
                                    <small class="activity-time"><?= date('M j, Y', strtotime($activity['timestamp'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No recent activity</p>
                        <small>Activity will appear here as users interact with the system.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="dashboard-right-column">
        <!-- Upcoming Deadlines Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-calendar-alt"></i>
                    Upcoming Deadlines
                </h3>
                <a href="/admin/schedule" class="widget-action">View Calendar</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($upcomingDeadlines)): ?>
                    <div class="deadline-list">
                        <?php foreach ($upcomingDeadlines as $deadline): ?>
                            <div class="deadline-item <?= $deadline['days_remaining'] <= 3 ? 'urgent' : ($deadline['days_remaining'] <= 7 ? 'soon' : '') ?>">
                                <div class="deadline-icon">
                                    <i class="<?= $deadline['icon'] ?? 'fas fa-calendar' ?>"></i>
                                </div>
                                <div class="deadline-content">
                                    <h4 class="deadline-name"><?= htmlspecialchars($deadline['name']) ?></h4>
                                    <p class="deadline-type"><?= htmlspecialchars($deadline['type']) ?></p>
                                    <div class="deadline-time">
                                        <span class="deadline-days"><?= $deadline['days_remaining'] ?> days remaining</span>
                                        <small class="deadline-date"><?= date('M j, Y', strtotime($deadline['deadline'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>No upcoming deadlines</p>
                        <small>All deadlines are well in the future.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Team Distribution Widget -->
        <?php if (!empty($stats['teams_by_category'])): ?>
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-chart-pie"></i>
                    Team Distribution
                </h3>
                <a href="/admin/analytics" class="widget-action">View Analytics</a>
            </div>
            <div class="widget-content">
                <div class="category-distribution">
                    <?php foreach ($stats['teams_by_category'] as $category): ?>
                        <div class="category-item">
                            <div class="category-info">
                                <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
                                <span class="category-count"><?= $category['count'] ?> teams</span>
                            </div>
                            <div class="category-bar">
                                <div class="category-progress" style="width: <?= $stats['total_teams'] > 0 ? ($category['count'] / $stats['total_teams']) * 100 : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Status Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-server"></i>
                    System Status
                </h3>
                <a href="/admin/system" class="widget-action">System Admin</a>
            </div>
            <div class="widget-content">
                <div class="system-status">
                    <div class="status-item">
                        <div class="status-indicator <?= ($systemHealth['database'] ?? 'unknown') === 'connected' ? 'healthy' : 'error' ?>"></div>
                        <span class="status-label">Database</span>
                        <span class="status-value"><?= ucfirst($systemHealth['database'] ?? 'Unknown') ?></span>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-indicator <?= ($systemHealth['storage'] ?? 'unknown') === 'available' ? 'healthy' : 'error' ?>"></div>
                        <span class="status-label">File Storage</span>
                        <span class="status-value"><?= ucfirst($systemHealth['storage'] ?? 'Unknown') ?></span>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-indicator healthy"></div>
                        <span class="status-label">Memory Usage</span>
                        <span class="status-value"><?= $systemHealth['memory_usage'] ?? 0 ?>MB</span>
                    </div>
                    
                    <?php if (isset($systemHealth['disk_free'])): ?>
                    <div class="status-item">
                        <div class="status-indicator healthy"></div>
                        <span class="status-label">Disk Space</span>
                        <span class="status-value"><?= $systemHealth['disk_free'] ?>GB free</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>