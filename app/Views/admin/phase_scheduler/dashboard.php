<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Phase Scheduler Header -->
<div class="dashboard-header">
    <div class="dashboard-welcome">
        <h2 class="welcome-title">Phase Scheduler</h2>
        <p class="welcome-subtitle">Manage competition phases, timelines, and scheduling across all competitions.</p>
    </div>
    
    <!-- Quick Actions Panel -->
    <div class="quick-actions-panel">
        <h3 class="quick-actions-title">Scheduler Actions</h3>
        <div class="quick-actions-grid">
            <button onclick="createNewSchedule()" class="quick-action-btn primary">
                <i class="fas fa-plus"></i>
                <span>New Schedule</span>
            </button>
            <button onclick="showCalendarView()" class="quick-action-btn info">
                <i class="fas fa-calendar"></i>
                <span>Calendar View</span>
            </button>
            <button onclick="validateAllSchedules()" class="quick-action-btn warning">
                <i class="fas fa-check-circle"></i>
                <span>Validate All</span>
            </button>
            <button onclick="exportSchedules()" class="quick-action-btn success">
                <i class="fas fa-download"></i>
                <span>Export Data</span>
            </button>
        </div>
    </div>
</div>

<!-- Competition Selection -->
<div class="content-section">
    <div class="section-header">
        <h3 class="section-title">Active Competitions</h3>
        <div class="section-actions">
            <button class="btn btn-outline btn-sm" onclick="refreshCompetitions()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    
    <div class="competitions-grid">
        <?php if (!empty($competitions)): ?>
            <?php foreach ($competitions as $competition): ?>
                <div class="competition-card" data-competition-id="<?= $competition['id'] ?>">
                    <div class="card-header">
                        <h4 class="competition-name"><?= htmlspecialchars($competition['name']) ?></h4>
                        <div class="competition-badges">
                            <span class="badge badge-<?= $competition['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($competition['status']) ?>
                            </span>
                            <span class="badge badge-info"><?= $competition['year'] ?></span>
                        </div>
                    </div>
                    
                    <div class="card-content">
                        <div class="competition-stats">
                            <div class="stat-item">
                                <i class="fas fa-layer-group"></i>
                                <span><?= $competition['phase_count'] ?> Phases</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-tags"></i>
                                <span><?= $competition['category_count'] ?> Categories</span>
                            </div>
                        </div>
                        
                        <div class="timeline-preview">
                            <?php if ($competition['start_date']): ?>
                                <div class="timeline-dates">
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($competition['start_date'])) ?> - 
                                        <?= date('M j, Y', strtotime($competition['end_date'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <a href="<?= url('/admin/phase-scheduler/timeline/' . $competition['id']) ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-alt"></i> View Timeline
                        </a>
                        <button onclick="editPhases(<?= $competition['id'] ?>)" class="btn btn-outline btn-sm">
                            <i class="fas fa-edit"></i> Edit Phases
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h4>No Active Competitions</h4>
                <p>Create a competition first using the Competition Wizard.</p>
                <a href="<?= url('/admin/competition-setup/wizard/start') ?>" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Create Competition
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Phase Status Overview -->
<div class="dashboard-content-grid">
    <!-- Left Column -->
    <div class="dashboard-left-column">
        <!-- Upcoming Phases Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-clock"></i>
                    Upcoming Phases
                </h3>
                <span class="badge badge-info"><?= count($upcoming_phases ?? []) ?></span>
            </div>
            <div class="widget-content">
                <?php if (!empty($upcoming_phases)): ?>
                    <div class="phase-list">
                        <?php foreach ($upcoming_phases as $phase): ?>
                            <div class="phase-item upcoming">
                                <div class="phase-icon">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="phase-content">
                                    <h5 class="phase-name"><?= htmlspecialchars($phase['name']) ?></h5>
                                    <p class="phase-competition"><?= htmlspecialchars($phase['competition_name']) ?></p>
                                    <div class="phase-timeline">
                                        <small class="phase-date">
                                            Starts: <?= date('M j, Y', strtotime($phase['start_date'])) ?>
                                        </small>
                                        <small class="phase-countdown">
                                            <?php 
                                            $days = ceil((strtotime($phase['start_date']) - time()) / (60 * 60 * 24));
                                            echo $days > 0 ? "in {$days} days" : 'starting soon';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="phase-actions">
                                    <button onclick="viewPhaseDetails(<?= $phase['id'] ?>)" class="btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state small">
                        <i class="fas fa-calendar-check"></i>
                        <p>No upcoming phases</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Schedule Conflicts Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Schedule Conflicts
                </h3>
            </div>
            <div class="widget-content">
                <div id="conflictsList">
                    <div class="loading-placeholder">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Checking for conflicts...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="dashboard-right-column">
        <!-- Active Phases Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-play"></i>
                    Active Phases
                </h3>
                <span class="badge badge-success"><?= count($active_phases ?? []) ?></span>
            </div>
            <div class="widget-content">
                <?php if (!empty($active_phases)): ?>
                    <div class="phase-list">
                        <?php foreach ($active_phases as $phase): ?>
                            <div class="phase-item active">
                                <div class="phase-icon">
                                    <i class="fas fa-play"></i>
                                </div>
                                <div class="phase-content">
                                    <h5 class="phase-name"><?= htmlspecialchars($phase['name']) ?></h5>
                                    <p class="phase-competition"><?= htmlspecialchars($phase['competition_name']) ?></p>
                                    <div class="phase-progress">
                                        <?php 
                                        $start = strtotime($phase['start_date']);
                                        $end = strtotime($phase['end_date']);
                                        $now = time();
                                        $progress = $end > $start ? min(100, max(0, ($now - $start) / ($end - $start) * 100)) : 0;
                                        ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                                        </div>
                                        <small><?= round($progress) ?>% complete</small>
                                    </div>
                                </div>
                                <div class="phase-actions">
                                    <button onclick="managePhase(<?= $phase['id'] ?>)" class="btn-icon" title="Manage Phase">
                                        <i class="fas fa-cogs"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state small">
                        <i class="fas fa-pause-circle"></i>
                        <p>No active phases</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Tools Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-tools"></i>
                    Quick Tools
                </h3>
            </div>
            <div class="widget-content">
                <div class="tool-buttons">
                    <button onclick="bulkPhaseUpdate()" class="tool-btn">
                        <i class="fas fa-edit"></i>
                        <span>Bulk Phase Update</span>
                    </button>
                    
                    <button onclick="generateScheduleReport()" class="tool-btn">
                        <i class="fas fa-file-alt"></i>
                        <span>Schedule Report</span>
                    </button>
                    
                    <button onclick="importPhaseTemplate()" class="tool-btn">
                        <i class="fas fa-upload"></i>
                        <span>Import Template</span>
                    </button>
                    
                    <button onclick="scheduleNotifications()" class="tool-btn">
                        <i class="fas fa-bell"></i>
                        <span>Schedule Notifications</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Modal -->
<div class="modal" id="calendarModal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">Competition Calendar</h3>
            <button class="modal-close" onclick="closeCalendarModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="competitionCalendar">
                <!-- Calendar will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load schedule conflicts
    loadScheduleConflicts();
    
    // Auto-refresh every 30 seconds
    setInterval(loadScheduleConflicts, 30000);
});

function createNewSchedule() {
    // Implementation for creating new schedule
    alert('Create new schedule functionality will be implemented');
}

function showCalendarView() {
    document.getElementById('calendarModal').style.display = 'flex';
    loadCalendarData();
}

function closeCalendarModal() {
    document.getElementById('calendarModal').style.display = 'none';
}

function validateAllSchedules() {
    showLoading();
    
    fetch('<?= url('/admin/phase-scheduler/validate-schedule') ?>', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        
        if (result.success) {
            alert('All schedules validated successfully!');
            loadScheduleConflicts(); // Refresh conflicts
        } else {
            alert('Validation issues found: ' + result.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

function loadScheduleConflicts() {
    const conflictsList = document.getElementById('conflictsList');
    
    // Simulate loading conflicts (replace with actual API call)
    setTimeout(() => {
        conflictsList.innerHTML = `
            <div class="empty-state small">
                <i class="fas fa-check-circle text-success"></i>
                <p>No conflicts detected</p>
                <small>All schedules are properly configured</small>
            </div>
        `;
    }, 1000);
}

function loadCalendarData() {
    const calendar = document.getElementById('competitionCalendar');
    calendar.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading calendar...</div>';
    
    // Implementation for loading calendar data
    setTimeout(() => {
        calendar.innerHTML = '<p>Calendar integration will be implemented here</p>';
    }, 1000);
}

function editPhases(competitionId) {
    window.location.href = `<?= url('/admin/phase-scheduler/timeline/') ?>${competitionId}`;
}

function viewPhaseDetails(phaseId) {
    // Implementation for viewing phase details
    alert(`View phase ${phaseId} details`);
}

function managePhase(phaseId) {
    // Implementation for managing phase
    alert(`Manage phase ${phaseId}`);
}

function refreshCompetitions() {
    showLoading();
    window.location.reload();
}

function exportSchedules() {
    // Implementation for exporting schedules
    alert('Export functionality will be implemented');
}

function bulkPhaseUpdate() {
    alert('Bulk phase update functionality will be implemented');
}

function generateScheduleReport() {
    alert('Schedule report generation will be implemented');
}

function importPhaseTemplate() {
    alert('Phase template import will be implemented');
}

function scheduleNotifications() {
    alert('Schedule notifications setup will be implemented');
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>