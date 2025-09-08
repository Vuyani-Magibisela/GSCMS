<?php
$layout = 'admin';
ob_start();
?>

<!-- jQuery UI for drag and drop -->
<link href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Time Slot Management</h2>
                    <p class="text-muted">Assign teams to competition time slots</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" onclick="autoAllocateSlots()">
                        <i class="fas fa-magic"></i> Auto Allocate
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="bulkAssignTeams()">
                        <i class="fas fa-users"></i> Bulk Assign
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="detectConflicts()">
                        <i class="fas fa-exclamation-triangle"></i> Check Conflicts
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="exportSchedule()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Selection -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <select id="eventSelect" class="form-select" onchange="loadEventSlots()">
                                <option value="">Select Competition Event</option>
                                <?php if (isset($available_events)): ?>
                                    <?php foreach ($available_events as $evt): ?>
                                        <option value="<?= $evt->id ?>" <?= ($event && $event->id == $evt->id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($evt->event_name) ?> - <?= date('M j, Y', strtotime($evt->start_datetime)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="categoryFilter" class="form-select" onchange="applyFilters()">
                                <option value="">All Categories</option>
                                <option value="1">Junior Primary</option>
                                <option value="2">Senior Primary</option>
                                <option value="3">Secondary</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="venueFilter" class="form-select" onchange="applyFilters()">
                                <option value="">All Venues</option>
                                <option value="1">Main Hall</option>
                                <option value="2">Training Room A</option>
                                <option value="3">Training Room B</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="statusFilter" class="form-select" onchange="applyFilters()">
                                <option value="">All Statuses</option>
                                <option value="available">Available</option>
                                <option value="reserved">Reserved</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilters()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($event): ?>
    <!-- Event Information -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-0"><?= htmlspecialchars($event->event_name) ?></h5>
                        <small><?= date('F j, Y g:i A', strtotime($event->start_datetime)) ?> - <?= date('g:i A', strtotime($event->end_datetime)) ?></small>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <span class="badge bg-primary"><?= count($time_slots ?? []) ?> Total Slots</span>
                            <span class="badge bg-success"><?= count(array_filter($time_slots ?? [], function($slot) { return $slot['status'] === 'confirmed'; })) ?> Confirmed</span>
                            <span class="badge bg-warning"><?= count(array_filter($time_slots ?? [], function($slot) { return $slot['status'] === 'reserved'; })) ?> Reserved</span>
                            <span class="badge bg-secondary"><?= count(array_filter($time_slots ?? [], function($slot) { return $slot['status'] === 'available'; })) ?> Available</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Unassigned Teams Panel -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Unassigned Teams</h6>
                    <span class="badge bg-warning" id="unassignedCount">0</span>
                </div>
                <div class="card-body p-2" style="max-height: 600px; overflow-y: auto;">
                    <div id="unassignedTeams">
                        <!-- Unassigned teams will be loaded here -->
                    </div>
                </div>
                <div class="card-footer py-2">
                    <button class="btn btn-sm btn-outline-primary w-100" onclick="loadUnassignedTeams()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Time Slots Grid -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Time Slots</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="toggleView('grid')" id="gridViewBtn">
                            <i class="fas fa-th"></i> Grid
                        </button>
                        <button class="btn btn-outline-secondary" onclick="toggleView('timeline')" id="timelineViewBtn">
                            <i class="fas fa-stream"></i> Timeline
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <!-- Grid View -->
                    <div id="gridView" class="view-container">
                        <div id="timeSlotsContainer" class="time-slots-grid">
                            <!-- Time slots will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Timeline View -->
                    <div id="timelineView" class="view-container" style="display: none;">
                        <div id="timelineContainer" class="timeline-view">
                            <!-- Timeline will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- No Event Selected -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Event Selected</h5>
                    <p class="text-muted">Please select a competition event to manage time slots.</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Team Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="assignmentDetails"></div>
                <div id="conflictWarnings" class="mt-3" style="display: none;">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Conflicts Detected</h6>
                        <ul id="conflictList"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssignment()">Confirm Assignment</button>
            </div>
        </div>
    </div>
</div>

<!-- Auto Allocation Modal -->
<div class="modal fade" id="autoAllocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Auto Allocation Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="autoAllocationForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">All Categories</option>
                            <option value="1">Junior Primary</option>
                            <option value="2">Senior Primary</option>
                            <option value="3">Secondary</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Venue</label>
                        <select class="form-select" name="venue_id">
                            <option value="">All Venues</option>
                            <option value="1">Main Hall</option>
                            <option value="2">Training Room A</option>
                            <option value="3">Training Room B</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="respect_preferences" id="respectPreferences" checked>
                        <label class="form-check-label" for="respectPreferences">
                            Respect team scheduling preferences
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="optimize_travel" id="optimizeTravel" checked>
                        <label class="form-check-label" for="optimizeTravel">
                            Optimize for minimal travel time between schools
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Auto Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentEvent = <?= $event ? $event->id : 'null' ?>;
let currentView = 'grid';
let draggedTeam = null;
let pendingAssignment = null;

document.addEventListener('DOMContentLoaded', function() {
    if (currentEvent) {
        loadEventSlots();
        loadUnassignedTeams();
    }
    
    setupEventListeners();
});

function setupEventListeners() {
    // Auto allocation form
    document.getElementById('autoAllocationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        processAutoAllocation(new FormData(this));
    });
}

function loadEventSlots() {
    const eventId = document.getElementById('eventSelect').value;
    if (!eventId) return;
    
    currentEvent = eventId;
    
    fetch(`/admin/scheduling/time-slots-data?event_id=${eventId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTimeSlots(data.time_slots);
                updateSlotStatistics(data.statistics);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            showAlert('danger', 'Error loading time slots: ' + error.message);
        });
}

function loadUnassignedTeams() {
    if (!currentEvent) return;
    
    const categoryId = document.getElementById('categoryFilter').value;
    
    fetch(`/admin/scheduling/unassigned-teams?event_id=${currentEvent}&category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUnassignedTeams(data.teams);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            showAlert('danger', 'Error loading teams: ' + error.message);
        });
}

function renderTimeSlots(slots) {
    const container = document.getElementById('timeSlotsContainer');
    
    if (currentView === 'grid') {
        renderGridView(container, slots);
    } else {
        renderTimelineView(container, slots);
    }
}

function renderGridView(container, slots) {
    // Group slots by date and time
    const groupedSlots = groupSlotsByDateTime(slots);
    
    let html = '<div class="slots-grid">';
    
    Object.keys(groupedSlots).forEach(date => {
        html += `<div class="date-group">
            <h6 class="date-header">${formatDate(date)}</h6>
            <div class="time-rows">`;
        
        Object.keys(groupedSlots[date]).forEach(time => {
            html += `<div class="time-row">
                <div class="time-label">${formatTime(time)}</div>
                <div class="slots-row">`;
            
            groupedSlots[date][time].forEach(slot => {
                html += renderSlotCard(slot);
            });
            
            html += '</div></div>';
        });
        
        html += '</div></div>';
    });
    
    html += '</div>';
    
    container.innerHTML = html;
    
    // Make slots droppable
    $('.slot-card.available').droppable({
        accept: '.team-card',
        hoverClass: 'slot-hover',
        drop: function(event, ui) {
            const slotId = $(this).data('slot-id');
            const teamId = draggedTeam.id;
            showAssignmentModal(teamId, slotId);
        }
    });
}

function renderTimelineView(container, slots) {
    // Timeline view implementation
    let html = '<div class="timeline-container">';
    
    const venues = groupSlotsByVenue(slots);
    
    Object.keys(venues).forEach(venueId => {
        const venueSlots = venues[venueId];
        html += `<div class="venue-timeline">
            <h6 class="venue-header">${venueSlots[0].venue_name}</h6>
            <div class="timeline-track">`;
        
        venueSlots.forEach(slot => {
            html += renderTimelineSlot(slot);
        });
        
        html += '</div></div>';
    });
    
    html += '</div>';
    
    container.innerHTML = html;
}

function renderSlotCard(slot) {
    const statusClass = slot.status;
    const teamInfo = slot.team_name ? `
        <div class="team-info">
            <strong>${slot.team_name}</strong>
            <small class="d-block">${slot.school_name}</small>
        </div>
    ` : '<div class="slot-placeholder">Available</div>';
    
    return `
        <div class="slot-card ${statusClass}" 
             data-slot-id="${slot.id}" 
             data-venue-id="${slot.venue_id}"
             data-table="${slot.table_number}">
            <div class="slot-header">
                <span class="table-number">${slot.table_number}</span>
                <span class="slot-duration">${slot.duration_minutes}min</span>
            </div>
            <div class="slot-body">
                ${teamInfo}
            </div>
            <div class="slot-footer">
                <small class="venue-name">${slot.venue_name}</small>
                ${slot.status !== 'available' ? `
                    <button class="btn btn-sm btn-outline-danger" onclick="releaseSlot(${slot.id})">
                        <i class="fas fa-times"></i>
                    </button>
                ` : ''}
            </div>
        </div>
    `;
}

function renderTimelineSlot(slot) {
    const width = (slot.duration_minutes / 30) * 100; // 30 min = 100px base
    const left = calculateTimelinePosition(slot.start_time);
    
    return `
        <div class="timeline-slot ${slot.status}" 
             style="left: ${left}px; width: ${width}px;"
             data-slot-id="${slot.id}">
            <div class="slot-content">
                <small>${slot.team_name || 'Available'}</small>
            </div>
        </div>
    `;
}

function renderUnassignedTeams(teams) {
    const container = document.getElementById('unassignedTeams');
    const countBadge = document.getElementById('unassignedCount');
    
    countBadge.textContent = teams.length;
    
    let html = '';
    teams.forEach(team => {
        html += `
            <div class="team-card" data-team-id="${team.id}" data-category-id="${team.category_id}">
                <div class="team-header">
                    <strong>${team.name}</strong>
                    <span class="category-badge">${team.category_name}</span>
                </div>
                <div class="team-details">
                    <small>${team.school_name}</small>
                    ${team.preferred_time_slot && team.preferred_time_slot !== 'any' ? 
                        `<span class="preference-indicator" title="Prefers ${team.preferred_time_slot}">
                            <i class="fas fa-clock"></i>
                        </span>` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html || '<p class="text-center text-muted">No unassigned teams</p>';
    
    // Make teams draggable
    $('.team-card').draggable({
        helper: 'clone',
        cursor: 'move',
        start: function(event, ui) {
            const teamId = $(this).data('team-id');
            draggedTeam = teams.find(t => t.id == teamId);
        }
    });
}

function showAssignmentModal(teamId, slotId) {
    const team = draggedTeam;
    
    fetch('/admin/scheduling/check-assignment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_id: teamId,
            slot_id: slotId
        })
    })
    .then(response => response.json())
    .then(data => {
        const modal = document.getElementById('assignmentModal');
        const detailsContainer = document.getElementById('assignmentDetails');
        const conflictsContainer = document.getElementById('conflictWarnings');
        const conflictsList = document.getElementById('conflictList');
        
        detailsContainer.innerHTML = `
            <h6>Assignment Details</h6>
            <table class="table table-sm">
                <tr><td><strong>Team:</strong></td><td>${team.name}</td></tr>
                <tr><td><strong>School:</strong></td><td>${team.school_name}</td></tr>
                <tr><td><strong>Category:</strong></td><td>${team.category_name}</td></tr>
                <tr><td><strong>Time Slot:</strong></td><td>${data.slot_info.start_time} - ${data.slot_info.end_time}</td></tr>
                <tr><td><strong>Venue:</strong></td><td>${data.slot_info.venue_name}</td></tr>
                <tr><td><strong>Table:</strong></td><td>${data.slot_info.table_number}</td></tr>
            </table>
        `;
        
        if (data.conflicts && data.conflicts.length > 0) {
            conflictsList.innerHTML = data.conflicts.map(conflict => 
                `<li>${conflict.message}</li>`
            ).join('');
            conflictsContainer.style.display = 'block';
        } else {
            conflictsContainer.style.display = 'none';
        }
        
        pendingAssignment = { team_id: teamId, slot_id: slotId };
        new bootstrap.Modal(modal).show();
    });
}

function confirmAssignment() {
    if (!pendingAssignment) return;
    
    fetch('/admin/scheduling/assign-team', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(pendingAssignment)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
            loadEventSlots();
            loadUnassignedTeams();
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.message);
        }
        pendingAssignment = null;
    });
}

function releaseSlot(slotId) {
    if (!confirm('Are you sure you want to release this time slot?')) return;
    
    fetch('/admin/scheduling/release-slot', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ slot_id: slotId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadEventSlots();
            loadUnassignedTeams();
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.message);
        }
    });
}

function autoAllocateSlots() {
    new bootstrap.Modal(document.getElementById('autoAllocationModal')).show();
}

function processAutoAllocation(formData) {
    const data = Object.fromEntries(formData);
    data.event_id = currentEvent;
    
    fetch('/admin/scheduling/auto-allocate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        bootstrap.Modal.getInstance(document.getElementById('autoAllocationModal')).hide();
        
        if (result.success) {
            loadEventSlots();
            loadUnassignedTeams();
            showAlert('success', result.message);
            
            if (result.statistics) {
                showAllocationResults(result.statistics);
            }
        } else {
            showAlert('danger', result.message);
        }
    });
}

function bulkAssignTeams() {
    const categoryId = document.getElementById('categoryFilter').value;
    const venueId = document.getElementById('venueFilter').value;
    
    fetch('/admin/scheduling/bulk-assign', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_id: currentEvent,
            category_id: categoryId,
            venue_id: venueId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadEventSlots();
            loadUnassignedTeams();
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.message);
        }
    });
}

function detectConflicts() {
    fetch('/admin/scheduling/detect-conflicts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ event_id: currentEvent })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.conflicts.length > 0) {
                showConflictsModal(data.conflicts, data.summary);
            } else {
                showAlert('success', 'No scheduling conflicts detected');
            }
        } else {
            showAlert('danger', data.message);
        }
    });
}

function toggleView(view) {
    currentView = view;
    
    document.getElementById('gridViewBtn').classList.remove('active');
    document.getElementById('timelineViewBtn').classList.remove('active');
    document.getElementById(view + 'ViewBtn').classList.add('active');
    
    document.getElementById('gridView').style.display = view === 'grid' ? 'block' : 'none';
    document.getElementById('timelineView').style.display = view === 'timeline' ? 'block' : 'none';
    
    loadEventSlots(); // Reload with new view
}

function applyFilters() {
    loadEventSlots();
    loadUnassignedTeams();
}

function resetFilters() {
    document.getElementById('categoryFilter').value = '';
    document.getElementById('venueFilter').value = '';
    document.getElementById('statusFilter').value = '';
    applyFilters();
}

function exportSchedule() {
    if (!currentEvent) return;
    
    window.open(`/admin/scheduling/export?event_id=${currentEvent}&format=pdf`, '_blank');
}

// Helper functions
function groupSlotsByDateTime(slots) {
    const grouped = {};
    slots.forEach(slot => {
        const date = slot.slot_date;
        const time = slot.start_time;
        
        if (!grouped[date]) grouped[date] = {};
        if (!grouped[date][time]) grouped[date][time] = [];
        
        grouped[date][time].push(slot);
    });
    return grouped;
}

function groupSlotsByVenue(slots) {
    const grouped = {};
    slots.forEach(slot => {
        const venueId = slot.venue_id;
        if (!grouped[venueId]) grouped[venueId] = [];
        grouped[venueId].push(slot);
    });
    return grouped;
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('en-US', { 
        weekday: 'short', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(timeStr) {
    return new Date('2000-01-01 ' + timeStr).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function calculateTimelinePosition(timeStr) {
    // Calculate position based on time (simplified)
    const time = new Date('2000-01-01 ' + timeStr);
    const hours = time.getHours();
    const minutes = time.getMinutes();
    return (hours - 8) * 120 + minutes * 2; // 8 AM start, 120px per hour
}

function showAlert(type, message) {
    // Create and show Bootstrap alert
    const alertsContainer = document.getElementById('alerts-container') || createAlertsContainer();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertsContainer.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function createAlertsContainer() {
    const container = document.createElement('div');
    container.id = 'alerts-container';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.width = '300px';
    document.body.appendChild(container);
    return container;
}

function showAllocationResults(statistics) {
    const message = `
        <strong>Allocation Complete!</strong><br>
        Teams Scheduled: ${statistics.teams_scheduled}<br>
        Venues Used: ${statistics.venues_used}<br>
        Time Span: ${statistics.time_span}
    `;
    showAlert('info', message);
}
</script>

<style>
.time-slots-grid {
    max-height: 600px;
    overflow-y: auto;
}

.date-group {
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.date-header {
    background: #f8f9fa;
    padding: 10px 15px;
    margin: 0;
    border-bottom: 1px solid #dee2e6;
    font-weight: bold;
}

.time-row {
    display: flex;
    align-items: flex-start;
    border-bottom: 1px solid #e9ecef;
    min-height: 60px;
}

.time-label {
    width: 100px;
    padding: 10px 15px;
    background: #f8f9fa;
    font-weight: 500;
    border-right: 1px solid #e9ecef;
    display: flex;
    align-items: center;
}

.slots-row {
    flex: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding: 5px;
}

.slot-card {
    width: 180px;
    min-height: 80px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.slot-card.available {
    background: #f8f9fa;
    border-style: dashed;
}

.slot-card.available:hover {
    background: #e9ecef;
}

.slot-card.reserved {
    background: #fff3cd;
    border-color: #ffc107;
}

.slot-card.confirmed {
    background: #d1ecf1;
    border-color: #0dcaf0;
}

.slot-hover {
    background: #d4edda !important;
    border-color: #28a745 !important;
}

.slot-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.8em;
    margin-bottom: 5px;
}

.table-number {
    font-weight: bold;
    color: #6c757d;
}

.slot-duration {
    color: #6c757d;
}

.team-info strong {
    font-size: 0.9em;
    color: #495057;
}

.team-info small {
    color: #6c757d;
}

.slot-placeholder {
    color: #adb5bd;
    font-style: italic;
    text-align: center;
    padding: 10px 0;
}

.slot-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
    font-size: 0.8em;
}

.venue-name {
    color: #6c757d;
}

.team-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 8px;
    cursor: grab;
    transition: all 0.2s;
}

.team-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.team-card.ui-draggable-dragging {
    cursor: grabbing;
    transform: rotate(5deg);
    z-index: 1000;
}

.team-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.team-header strong {
    font-size: 0.9em;
}

.category-badge {
    font-size: 0.7em;
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 10px;
    color: #6c757d;
}

.team-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8em;
    color: #6c757d;
}

.preference-indicator {
    color: #0d6efd;
}

.timeline-container {
    padding: 10px;
}

.venue-timeline {
    margin-bottom: 30px;
}

.venue-header {
    background: #f8f9fa;
    padding: 8px 12px;
    margin-bottom: 10px;
    border-left: 4px solid #0d6efd;
    font-weight: bold;
}

.timeline-track {
    position: relative;
    height: 40px;
    background: #f8f9fa;
    border-radius: 4px;
    margin: 10px 0;
}

.timeline-slot {
    position: absolute;
    height: 30px;
    top: 5px;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 0.8em;
    cursor: pointer;
    transition: all 0.2s;
}

.timeline-slot.available {
    background: #e9ecef;
    border: 1px dashed #6c757d;
}

.timeline-slot.reserved {
    background: #ffc107;
    color: white;
}

.timeline-slot.confirmed {
    background: #0dcaf0;
    color: white;
}

.timeline-slot:hover {
    opacity: 0.8;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .slots-row {
        flex-direction: column;
    }
    
    .slot-card {
        width: 100%;
    }
    
    .time-row {
        flex-direction: column;
    }
    
    .time-label {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e9ecef;
    }
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>