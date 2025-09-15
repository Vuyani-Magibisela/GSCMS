<?php 
$layout = 'layouts/judge';
ob_start(); 
?>

<div class="judge-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="dashboard-title">
                        <i class="fas fa-gavel me-2"></i>
                        Welcome back, <?= htmlspecialchars($judge['first_name']) ?>
                    </h1>
                    <p class="dashboard-subtitle text-muted">
                        <?= htmlspecialchars($judge['judge_type']) ?> Judge • 
                        <?= htmlspecialchars($judge['judge_code']) ?>
                        <?php if ($judge['organization_name']): ?>
                            • <?= htmlspecialchars($judge['organization_name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="quick-actions">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" id="start-scoring">
                                <i class="fas fa-play-circle me-1"></i> Start Scoring
                            </button>
                            <button type="button" class="btn btn-outline-success" id="check-in">
                                <i class="fas fa-check-circle me-1"></i> Check In
                            </button>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog me-1"></i> Settings
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/judge/profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                                    <li><a class="dropdown-item" href="/judge/auth/devices"><i class="fas fa-mobile-alt me-2"></i> Devices</a></li>
                                    <li><a class="dropdown-item" href="/judge/auth/setup-2fa"><i class="fas fa-shield-alt me-2"></i> Security</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/judge/auth/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card primary">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number"><?= $quick_stats['today_assignments'] ?></h3>
                        <p class="stats-label">Today's Assignments</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card warning">
                    <div class="stats-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number"><?= $quick_stats['pending_scores'] ?></h3>
                        <p class="stats-label">Pending Scores</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card info">
                    <div class="stats-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number"><?= $quick_stats['unread_notifications'] ?></h3>
                        <p class="stats-label">New Notifications</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card success">
                    <div class="stats-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number"><?= $quick_stats['current_streak'] ?></h3>
                        <p class="stats-label">Day Streak</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content Column -->
            <div class="col-lg-8">
                <!-- Today's Assignments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tasks me-2"></i>Today's Assignments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                            <div class="empty-state text-center py-4">
                                <i class="fas fa-calendar-check text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted">No assignments for today</h5>
                                <p class="text-muted">Take a well-deserved break or check upcoming competitions.</p>
                            </div>
                        <?php else: ?>
                            <div class="assignments-list">
                                <?php foreach ($assignments as $assignment): ?>
                                    <div class="assignment-card">
                                        <div class="assignment-header">
                                            <div class="assignment-info">
                                                <h6 class="assignment-title">
                                                    <?= htmlspecialchars($assignment['competition_name']) ?>
                                                </h6>
                                                <div class="assignment-details">
                                                    <span class="badge bg-primary me-2">
                                                        <i class="fas fa-tag me-1"></i>
                                                        <?= htmlspecialchars($assignment['category_name']) ?>
                                                    </span>
                                                    <span class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= date('H:i', strtotime($assignment['session_time'])) ?>
                                                    </span>
                                                    <span class="text-muted ms-3">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?= htmlspecialchars($assignment['venue_name']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="assignment-status">
                                                <span class="badge bg-<?= $assignment['assignment_status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($assignment['assignment_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="assignment-progress">
                                            <div class="progress mb-2">
                                                <?php 
                                                $progress = $assignment['total_matches'] > 0 
                                                    ? ($assignment['completed_matches'] / $assignment['total_matches']) * 100 
                                                    : 0;
                                                ?>
                                                <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                            </div>
                                            <small class="text-muted">
                                                <?= $assignment['completed_matches'] ?> of <?= $assignment['total_matches'] ?> matches completed
                                            </small>
                                        </div>
                                        <div class="assignment-actions">
                                            <?php if ($assignment['assignment_status'] === 'assigned'): ?>
                                                <button class="btn btn-success btn-sm confirm-assignment" 
                                                        data-assignment-id="<?= $assignment['id'] ?>">
                                                    <i class="fas fa-check me-1"></i>Confirm
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($assignment['check_in_time']): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>Checked In
                                                </span>
                                            <?php else: ?>
                                                <button class="btn btn-outline-primary btn-sm check-in-btn" 
                                                        data-assignment-id="<?= $assignment['id'] ?>">
                                                    <i class="fas fa-sign-in-alt me-1"></i>Check In
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-primary btn-sm start-scoring-btn" 
                                                    data-assignment-id="<?= $assignment['id'] ?>">
                                                <i class="fas fa-play-circle me-1"></i>Start Scoring
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Scoring Queue -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Scoring Queue
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($scoring_queue)): ?>
                            <div class="empty-state text-center py-4">
                                <i class="fas fa-clipboard-check text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted">No pending scores</h5>
                                <p class="text-muted">All caught up! Great work.</p>
                            </div>
                        <?php else: ?>
                            <div class="scoring-queue-list">
                                <?php foreach (array_slice($scoring_queue, 0, 5) as $match): ?>
                                    <div class="queue-item">
                                        <div class="queue-info">
                                            <h6 class="queue-title">
                                                <?= htmlspecialchars($match['match_name']) ?>
                                            </h6>
                                            <div class="queue-details">
                                                <span class="badge bg-secondary me-2">
                                                    <?= htmlspecialchars($match['category_name']) ?>
                                                </span>
                                                <span class="text-muted">
                                                    <?= htmlspecialchars($match['team1_name']) ?> vs 
                                                    <?= htmlspecialchars($match['team2_name']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="queue-status">
                                            <?php if ($match['scoring_status'] === 'scored'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Scored
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                                <button class="btn btn-primary btn-sm ms-2 score-match-btn" 
                                                        data-match-id="<?= $match['match_id'] ?>">
                                                    <i class="fas fa-edit me-1"></i>Score
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($scoring_queue) > 5): ?>
                                    <div class="text-center mt-3">
                                        <a href="/judge/scoring" class="btn btn-outline-primary">
                                            View All Pending Scores (<?= count($scoring_queue) ?>)
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activity)): ?>
                            <p class="text-muted">No recent activity to display.</p>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-<?= $this->getActivityIcon($activity['action']) ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p class="activity-description mb-1">
                                                <?= htmlspecialchars($activity['activity_description']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= $this->timeAgo($activity['created_at']) ?>
                                                <?php if ($activity['device_type']): ?>
                                                    • <?= ucfirst($activity['device_type']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="col-lg-4">
                <!-- Performance Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Performance (30 Days)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="performance-metrics">
                            <div class="metric-item">
                                <div class="metric-label">Competitions</div>
                                <div class="metric-value"><?= $performance_summary['competitions_judged'] ?></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Completion Rate</div>
                                <div class="metric-value"><?= $performance_summary['completion_rate'] ?>%</div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">On-Time Rate</div>
                                <div class="metric-value"><?= $performance_summary['on_time_rate'] ?>%</div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Avg. Rating</div>
                                <div class="metric-value">
                                    <?php if ($performance_summary['avg_performance_rating']): ?>
                                        <?= number_format($performance_summary['avg_performance_rating'], 1) ?>/5
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = $performance_summary['avg_performance_rating'];
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <i class="fas fa-star <?= $i <= $rating ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/judge/performance" class="btn btn-outline-primary btn-sm">
                                View Detailed Report
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Competitions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Upcoming Competitions
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_competitions)): ?>
                            <p class="text-muted">No upcoming competitions assigned.</p>
                        <?php else: ?>
                            <div class="upcoming-list">
                                <?php foreach ($upcoming_competitions as $competition): ?>
                                    <div class="upcoming-item">
                                        <div class="upcoming-date">
                                            <div class="date-month"><?= date('M', strtotime($competition['session_date'])) ?></div>
                                            <div class="date-day"><?= date('d', strtotime($competition['session_date'])) ?></div>
                                        </div>
                                        <div class="upcoming-details">
                                            <h6 class="upcoming-title">
                                                <?= htmlspecialchars($competition['competition_name']) ?>
                                            </h6>
                                            <div class="upcoming-info">
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($competition['category_name']) ?>
                                                </span>
                                                <span class="text-muted">
                                                    <?= date('H:i', strtotime($competition['session_time'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </h5>
                        <a href="/judge/notifications" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted">No new notifications.</p>
                        <?php else: ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>"
                                         data-notification-id="<?= $notification['id'] ?>">
                                        <div class="notification-content">
                                            <h6 class="notification-title">
                                                <?= htmlspecialchars($notification['title']) ?>
                                            </h6>
                                            <p class="notification-message">
                                                <?= htmlspecialchars($notification['message']) ?>
                                            </p>
                                            <small class="notification-time text-muted">
                                                <?= $this->timeAgo($notification['created_at']) ?>
                                            </small>
                                        </div>
                                        <?php if ($notification['action_url']): ?>
                                            <div class="notification-action">
                                                <a href="<?= htmlspecialchars($notification['action_url']) ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <?= htmlspecialchars($notification['action_text'] ?? 'View') ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const dashboard = new JudgeDashboard();
    dashboard.init();
});

class JudgeDashboard {
    constructor() {
        this.refreshInterval = 30000; // 30 seconds
        this.intervalId = null;
    }
    
    init() {
        this.bindEvents();
        this.startAutoRefresh();
        this.markNotificationsAsRead();
    }
    
    bindEvents() {
        // Check-in buttons
        document.querySelectorAll('.check-in-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCheckIn(e));
        });
        
        // Confirm assignment buttons
        document.querySelectorAll('.confirm-assignment').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleConfirmAssignment(e));
        });
        
        // Start scoring buttons
        document.querySelectorAll('.start-scoring-btn, .score-match-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleStartScoring(e));
        });
        
        // Notification click handlers
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', (e) => this.handleNotificationClick(e));
        });
        
        // Header quick actions
        document.getElementById('start-scoring')?.addEventListener('click', () => {
            window.location.href = '/judge/scoring';
        });
        
        document.getElementById('check-in')?.addEventListener('click', () => {
            this.handleQuickCheckIn();
        });
    }
    
    async handleCheckIn(e) {
        const button = e.target.closest('.check-in-btn');
        const assignmentId = button.dataset.assignmentId;
        
        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking In...';
            
            const response = await fetch('/judge/assignments/check-in', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ assignment_id: assignmentId }),
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                button.outerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Checked In</span>';
                this.showSuccess('Successfully checked in!');
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-sign-in-alt me-1"></i>Check In';
            this.showError(error.message);
        }
    }
    
    async handleConfirmAssignment(e) {
        const button = e.target.closest('.confirm-assignment');
        const assignmentId = button.dataset.assignmentId;
        
        try {
            const response = await fetch('/judge/assignments/confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ assignment_id: assignmentId }),
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                button.remove();
                const statusBadge = button.closest('.assignment-card').querySelector('.assignment-status .badge');
                statusBadge.className = 'badge bg-success';
                statusBadge.textContent = 'Confirmed';
                this.showSuccess('Assignment confirmed!');
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            this.showError(error.message);
        }
    }
    
    handleStartScoring(e) {
        const button = e.target.closest('button');
        const assignmentId = button.dataset.assignmentId;
        const matchId = button.dataset.matchId;
        
        if (assignmentId) {
            window.location.href = `/judge/scoring?assignment=${assignmentId}`;
        } else if (matchId) {
            window.location.href = `/judge/scoring?match=${matchId}`;
        }
    }
    
    async handleNotificationClick(e) {
        const notification = e.target.closest('.notification-item');
        const notificationId = notification.dataset.notificationId;
        
        if (notification.classList.contains('unread')) {
            try {
                await fetch('/judge/dashboard/mark-notification-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ notification_id: notificationId }),
                    credentials: 'same-origin'
                });
                
                notification.classList.remove('unread');
                this.updateNotificationCount();
                
            } catch (error) {
                console.error('Failed to mark notification as read:', error);
            }
        }
    }
    
    async handleQuickCheckIn() {
        try {
            const response = await fetch('/judge/assignments/check-in-next', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Successfully checked in to next assignment!');
                this.refreshDashboard();
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            this.showError(error.message);
        }
    }
    
    startAutoRefresh() {
        this.intervalId = setInterval(() => {
            this.refreshDashboard();
        }, this.refreshInterval);
    }
    
    async refreshDashboard() {
        try {
            const response = await fetch('/judge/dashboard/refresh', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateQuickStats(data.quick_stats);
                this.updateNotifications(data.notifications);
            }
            
        } catch (error) {
            console.error('Dashboard refresh failed:', error);
        }
    }
    
    updateQuickStats(stats) {
        Object.entries(stats).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stat="${key}"] .stats-number`);
            if (element) {
                element.textContent = value;
            }
        });
    }
    
    updateNotifications(notifications) {
        const unreadCount = notifications.filter(n => !n.is_read).length;
        const badge = document.querySelector('[data-stat="unread_notifications"] .stats-number');
        if (badge) {
            badge.textContent = unreadCount;
        }
    }
    
    updateNotificationCount() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        const badge = document.querySelector('[data-stat="unread_notifications"] .stats-number');
        if (badge) {
            badge.textContent = unreadCount;
        }
    }
    
    markNotificationsAsRead() {
        // Mark notifications as read after viewing for 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.notification-item.unread').forEach(notification => {
                const notificationId = notification.dataset.notificationId;
                fetch('/judge/dashboard/mark-notification-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ notification_id: notificationId }),
                    credentials: 'same-origin'
                });
                notification.classList.remove('unread');
            });
            this.updateNotificationCount();
        }, 3000);
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'error');
    }
    
    showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            document.body.removeChild(toast);
        });
    }
}
</script>

<?php 
// Helper functions for the view
function getActivityIcon($action) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'score_submit' => 'clipboard-check',
        'score_edit' => 'edit',
        'profile_update' => 'user-edit'
    ];
    return $icons[$action] ?? 'circle';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return date('M j, Y', strtotime($datetime));
}

$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>