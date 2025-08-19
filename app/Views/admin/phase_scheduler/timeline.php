<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Timeline Header -->
<div class="timeline-header">
    <div class="header-content">
        <div class="competition-info">
            <h2 class="competition-name"><?= htmlspecialchars($competition->name) ?></h2>
            <div class="competition-meta">
                <span class="badge badge-<?= $competition->status === 'active' ? 'success' : 'secondary' ?>">
                    <?= ucfirst($competition->status) ?>
                </span>
                <span class="badge badge-info"><?= $competition->year ?></span>
                <span class="competition-dates">
                    <?= date('M j', strtotime($competition->start_date)) ?> - 
                    <?= date('M j, Y', strtotime($competition->end_date)) ?>
                </span>
            </div>
        </div>
        
        <div class="timeline-actions">
            <button onclick="addNewPhase()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Phase
            </button>
            <button onclick="validateTimeline()" class="btn btn-outline">
                <i class="fas fa-check-circle"></i> Validate
            </button>
            <button onclick="exportTimeline()" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>
</div>

<!-- Timeline Stats -->
<div class="timeline-stats">
    <div class="stat-item">
        <div class="stat-icon">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= count($phases) ?></div>
            <div class="stat-label">Total Phases</div>
        </div>
    </div>
    
    <div class="stat-item">
        <div class="stat-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number">
                <?php 
                $totalDays = 0;
                foreach ($phases as $phase) {
                    $start = strtotime($phase['start_date']);
                    $end = strtotime($phase['end_date']);
                    $totalDays += ($end - $start) / (60 * 60 * 24);
                }
                echo round($totalDays);
                ?>
            </div>
            <div class="stat-label">Total Days</div>
        </div>
    </div>
    
    <div class="stat-item">
        <div class="stat-icon conflicts">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= count($conflicts) ?></div>
            <div class="stat-label">Conflicts</div>
        </div>
    </div>
    
    <div class="stat-item">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number">
                <?php 
                $totalRegistrations = 0;
                foreach ($phases as $phase) {
                    $totalRegistrations += $phase['total_registrations'] ?? 0;
                }
                echo $totalRegistrations;
                ?>
            </div>
            <div class="stat-label">Registrations</div>
        </div>
    </div>
</div>

<!-- Conflict Alerts -->
<?php if (!empty($conflicts)): ?>
    <div class="conflicts-section">
        <div class="alert alert-warning">
            <div class="alert-header">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Schedule Conflicts Detected</strong>
            </div>
            <div class="conflicts-list">
                <?php foreach ($conflicts as $conflict): ?>
                    <div class="conflict-item">
                        <span class="conflict-type"><?= ucfirst(str_replace('_', ' ', $conflict['type'])) ?>:</span>
                        <span class="conflict-description"><?= htmlspecialchars($conflict['description']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Timeline Visualization -->
<div class="timeline-container">
    <div class="timeline-controls">
        <div class="view-controls">
            <button class="view-btn active" onclick="setTimelineView('gantt')" data-view="gantt">
                <i class="fas fa-chart-bar"></i> Gantt View
            </button>
            <button class="view-btn" onclick="setTimelineView('calendar')" data-view="calendar">
                <i class="fas fa-calendar"></i> Calendar View
            </button>
            <button class="view-btn" onclick="setTimelineView('list')" data-view="list">
                <i class="fas fa-list"></i> List View
            </button>
        </div>
        
        <div class="zoom-controls">
            <button onclick="zoomTimeline('out')" class="btn btn-sm btn-outline">
                <i class="fas fa-search-minus"></i>
            </button>
            <button onclick="zoomTimeline('in')" class="btn btn-sm btn-outline">
                <i class="fas fa-search-plus"></i>
            </button>
            <button onclick="resetTimelineZoom()" class="btn btn-sm btn-outline">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    </div>
    
    <!-- Gantt Chart View -->
    <div class="timeline-view gantt-view active" id="ganttView">
        <div class="timeline-header-row">
            <div class="phase-labels">
                <div class="label-header">Phases</div>
            </div>
            <div class="timeline-dates" id="timelineDates">
                <!-- Date headers will be generated by JavaScript -->
            </div>
        </div>
        
        <div class="timeline-body">
            <?php foreach ($phases as $index => $phase): ?>
                <div class="timeline-row" data-phase-id="<?= $phase['id'] ?>">
                    <div class="phase-info">
                        <div class="phase-header">
                            <span class="phase-number"><?= $index + 1 ?></span>
                            <h4 class="phase-name"><?= htmlspecialchars($phase['name']) ?></h4>
                            <div class="phase-status status-<?= $phase['is_completed'] ? 'completed' : ($phase['is_active'] ? 'active' : 'pending') ?>">
                                <?= $phase['is_completed'] ? 'Completed' : ($phase['is_active'] ? 'Active' : 'Pending') ?>
                            </div>
                        </div>
                        
                        <div class="phase-details">
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <span><?= $phase['category_count'] ?> categories</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span><?= date('M j', strtotime($phase['start_date'])) ?> - <?= date('M j', strtotime($phase['end_date'])) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="timeline-track">
                        <div class="phase-bar" 
                             data-start="<?= $phase['start_date'] ?>" 
                             data-end="<?= $phase['end_date'] ?>"
                             style="<?= calculatePhaseBarStyle($phase, $phases) ?>">
                            
                            <div class="phase-progress">
                                <?php 
                                $progress = calculatePhaseProgress($phase);
                                ?>
                                <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                            </div>
                            
                            <div class="phase-bar-content">
                                <span class="phase-duration">
                                    <?= ceil((strtotime($phase['end_date']) - strtotime($phase['start_date'])) / (60 * 60 * 24)) ?> days
                                </span>
                            </div>
                            
                            <!-- Phase bar handles for resizing -->
                            <div class="resize-handle start" onmousedown="startResize(event, '<?= $phase['id'] ?>', 'start')"></div>
                            <div class="resize-handle end" onmousedown="startResize(event, '<?= $phase['id'] ?>', 'end')"></div>
                        </div>
                    </div>
                    
                    <div class="phase-actions">
                        <button onclick="editPhase(<?= $phase['id'] ?>)" class="btn-icon" title="Edit Phase">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if (!$phase['is_active'] && !$phase['is_completed']): ?>
                            <button onclick="activatePhase(<?= $phase['id'] ?>)" class="btn-icon success" title="Activate Phase">
                                <i class="fas fa-play"></i>
                            </button>
                        <?php endif; ?>
                        <?php if ($phase['is_active']): ?>
                            <button onclick="completePhase(<?= $phase['id'] ?>)" class="btn-icon warning" title="Complete Phase">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                        <button onclick="deletePhase(<?= $phase['id'] ?>)" class="btn-icon danger" title="Delete Phase">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($phases)): ?>
                <div class="timeline-empty">
                    <i class="fas fa-calendar-plus"></i>
                    <h3>No Phases Configured</h3>
                    <p>Add your first competition phase to get started.</p>
                    <button onclick="addNewPhase()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Phase
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Calendar View -->
    <div class="timeline-view calendar-view" id="calendarView" style="display: none;">
        <div id="competitionCalendar">
            <!-- Calendar integration will be loaded here -->
        </div>
    </div>
    
    <!-- List View -->
    <div class="timeline-view list-view" id="listView" style="display: none;">
        <div class="phases-list">
            <?php foreach ($phases as $phase): ?>
                <div class="phase-list-item">
                    <div class="phase-summary">
                        <h4><?= htmlspecialchars($phase['name']) ?></h4>
                        <div class="phase-timeline">
                            <?= date('M j, Y', strtotime($phase['start_date'])) ?> - 
                            <?= date('M j, Y', strtotime($phase['end_date'])) ?>
                        </div>
                    </div>
                    <div class="phase-metadata">
                        <span class="meta-item">
                            <i class="fas fa-tags"></i> <?= $phase['category_count'] ?> categories
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-users"></i> <?= $phase['total_registrations'] ?? 0 ?> registrations
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Phase Edit Modal -->
<div class="modal" id="phaseEditModal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">Edit Phase</h3>
            <button class="modal-close" onclick="closePhaseEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="phaseEditForm">
                <!-- Phase edit form will be loaded here -->
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePhaseEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="savePhaseChanges()">Save Changes</button>
        </div>
    </div>
</div>

<script>
// Timeline data for JavaScript processing
const timelineData = <?= json_encode($timeline_data) ?>;
const phases = <?= json_encode($phases) ?>;
const conflicts = <?= json_encode($conflicts) ?>;

document.addEventListener('DOMContentLoaded', function() {
    initializeTimeline();
    generateTimelineDates();
    
    // Enable drag and drop for phase reordering
    enablePhaseDragDrop();
});

function initializeTimeline() {
    // Initialize timeline view and interactions
    console.log('Timeline initialized with', phases.length, 'phases');
    
    // Set up event listeners for timeline interactions
    setupTimelineEvents();
}

function generateTimelineDates() {
    const datesContainer = document.getElementById('timelineDates');
    if (!datesContainer || phases.length === 0) return;
    
    // Calculate date range
    const startDates = phases.map(p => new Date(p.start_date));
    const endDates = phases.map(p => new Date(p.end_date));
    const minDate = new Date(Math.min(...startDates));
    const maxDate = new Date(Math.max(...endDates));
    
    // Generate month headers
    let dateHTML = '';
    let currentDate = new Date(minDate.getFullYear(), minDate.getMonth(), 1);
    
    while (currentDate <= maxDate) {
        const monthName = currentDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        dateHTML += `<div class="date-header">${monthName}</div>`;
        currentDate.setMonth(currentDate.getMonth() + 1);
    }
    
    datesContainer.innerHTML = dateHTML;
}

function setTimelineView(viewType) {
    // Hide all views
    document.querySelectorAll('.timeline-view').forEach(view => {
        view.style.display = 'none';
    });
    
    // Show selected view
    document.getElementById(viewType + 'View').style.display = 'block';
    
    // Update button states
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-view="${viewType}"]`).classList.add('active');
    
    // Load view-specific content
    if (viewType === 'calendar') {
        loadCalendarView();
    }
}

function loadCalendarView() {
    const calendar = document.getElementById('competitionCalendar');
    calendar.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading calendar...</div>';
    
    // Implementation for calendar integration
    setTimeout(() => {
        calendar.innerHTML = '<p>Calendar integration will be implemented here</p>';
    }, 1000);
}

function addNewPhase() {
    // Implementation for adding new phase
    alert('Add new phase functionality will be implemented');
}

function editPhase(phaseId) {
    document.getElementById('phaseEditModal').style.display = 'flex';
    loadPhaseEditForm(phaseId);
}

function closePhaseEditModal() {
    document.getElementById('phaseEditModal').style.display = 'none';
}

function loadPhaseEditForm(phaseId) {
    const form = document.getElementById('phaseEditForm');
    form.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading phase details...</div>';
    
    // Implementation for loading phase edit form
    setTimeout(() => {
        form.innerHTML = '<p>Phase edit form will be implemented here</p>';
    }, 1000);
}

function savePhaseChanges() {
    // Implementation for saving phase changes
    alert('Save phase changes functionality will be implemented');
}

function activatePhase(phaseId) {
    if (confirm('Are you sure you want to activate this phase?')) {
        showLoading();
        
        fetch('<?= url('/admin/phase-scheduler/activate-phase') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ phase_id: phaseId })
        })
        .then(response => response.json())
        .then(result => {
            hideLoading();
            
            if (result.success) {
                window.location.reload();
            } else {
                alert('Error activating phase: ' + result.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function completePhase(phaseId) {
    if (confirm('Are you sure you want to mark this phase as completed?')) {
        showLoading();
        
        fetch('<?= url('/admin/phase-scheduler/complete-phase') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ phase_id: phaseId })
        })
        .then(response => response.json())
        .then(result => {
            hideLoading();
            
            if (result.success) {
                window.location.reload();
            } else {
                alert('Error completing phase: ' + result.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function deletePhase(phaseId) {
    if (confirm('Are you sure you want to delete this phase? This action cannot be undone.')) {
        // Implementation for deleting phase
        alert('Delete phase functionality will be implemented');
    }
}

function validateTimeline() {
    showLoading();
    
    fetch('<?= url('/admin/phase-scheduler/validate-schedule') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ competition_id: <?= $competition->id ?> })
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        
        if (result.success) {
            alert('Timeline validation completed successfully!');
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

function exportTimeline() {
    const format = prompt('Export format (pdf, png, csv):', 'pdf');
    if (format) {
        window.location.href = `<?= url('/admin/phase-scheduler/export') ?>?competition_id=<?= $competition->id ?>&format=${format}`;
    }
}

function setupTimelineEvents() {
    // Setup drag and drop, click handlers, etc.
    console.log('Timeline events setup complete');
}

function enablePhaseDragDrop() {
    // Implementation for drag and drop phase reordering
    console.log('Phase drag and drop enabled');
}

function zoomTimeline(direction) {
    // Implementation for zooming timeline
    console.log('Zoom timeline:', direction);
}

function resetTimelineZoom() {
    // Implementation for resetting timeline zoom
    console.log('Reset timeline zoom');
}

function startResize(event, phaseId, handle) {
    // Implementation for resizing phase bars
    console.log('Start resize:', phaseId, handle);
    event.preventDefault();
}
</script>

<?php
// Helper functions for timeline calculations
function calculatePhaseBarStyle($phase, $allPhases) {
    // Calculate position and width based on date range
    return 'left: 0%; width: 100%;'; // Simplified for now
}

function calculatePhaseProgress($phase) {
    if ($phase['is_completed']) return 100;
    
    $now = time();
    $start = strtotime($phase['start_date']);
    $end = strtotime($phase['end_date']);
    
    if ($now < $start) return 0;
    if ($now > $end) return 100;
    
    return floor(($now - $start) / ($end - $start) * 100);
}
?>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>