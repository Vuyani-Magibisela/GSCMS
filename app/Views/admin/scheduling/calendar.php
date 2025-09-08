<?php
$layout = 'admin';
ob_start();
?>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Competition Calendar</h2>
                    <p class="text-muted">Manage events, competitions, and training sessions</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                        <i class="fas fa-plus"></i> Create Event
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="detectConflicts()">
                        <i class="fas fa-exclamation-triangle"></i> Detect Conflicts
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="scheduleNotifications()">
                        <i class="fas fa-bell"></i> Schedule Notifications
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <select id="eventTypeFilter" class="form-select form-select-sm">
                                <option value="">All Event Types</option>
                                <option value="training">Training Sessions</option>
                                <option value="competition">Competitions</option>
                                <option value="meeting">Meetings</option>
                                <option value="deadline">Deadlines</option>
                                <option value="announcement">Announcements</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="categoryFilter" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <option value="1">Junior Primary</option>
                                <option value="2">Senior Primary</option>
                                <option value="3">Secondary</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="phaseFilter" class="form-select form-select-sm">
                                <option value="">All Phases</option>
                                <option value="1">Phase 1 - School Based</option>
                                <option value="2">Phase 2 - District</option>
                                <option value="3">Phase 3 - Provincial</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <input type="checkbox" class="btn-check" id="showConflicts" autocomplete="off">
                                <label class="btn btn-outline-danger" for="showConflicts">
                                    <i class="fas fa-exclamation-triangle"></i> Conflicts
                                </label>
                                <button type="button" class="btn btn-outline-secondary" onclick="refreshCalendar()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Container -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="competition-calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Legend -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <h6 class="mb-2">Event Types:</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <span><i class="fas fa-square text-success"></i> Training Sessions</span>
                        <span><i class="fas fa-square text-danger"></i> Competitions</span>
                        <span><i class="fas fa-square text-warning"></i> Meetings</span>
                        <span><i class="fas fa-square text-purple"></i> Deadlines</span>
                        <span><i class="fas fa-square text-info"></i> Announcements</span>
                        <span><i class="fas fa-exclamation-triangle text-danger"></i> Conflicts Detected</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createEventForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_name" class="form-label">Event Name *</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_type" class="form-label">Event Type *</label>
                                <select class="form-select" id="event_type" name="event_type" required>
                                    <option value="">Select Type</option>
                                    <option value="training">Training Session</option>
                                    <option value="competition">Competition</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="announcement">Announcement</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phase_id" class="form-label">Competition Phase *</label>
                                <select class="form-select" id="phase_id" name="phase_id" required>
                                    <option value="">Select Phase</option>
                                    <option value="1">Phase 1 - School Based</option>
                                    <option value="2">Phase 2 - District</option>
                                    <option value="3">Phase 3 - Provincial</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">All Categories</option>
                                    <option value="1">Junior Primary</option>
                                    <option value="2">Senior Primary</option>
                                    <option value="3">Secondary</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_datetime" class="form-label">Start Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_datetime" class="form-label">End Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="venue_id" class="form-label">Venue</label>
                                <select class="form-select" id="venue_id" name="venue_id">
                                    <option value="">Select Venue</option>
                                    <option value="1">Main Competition Hall</option>
                                    <option value="2">Training Room A</option>
                                    <option value="3">Conference Room</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_participants" class="form-label">Max Participants</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="event_description" class="form-label">Description</label>
                        <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory">
                                <label class="form-check-label" for="is_mandatory">
                                    Mandatory Event
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="generate_slots" name="generate_slots">
                                <label class="form-check-label" for="generate_slots">
                                    Auto-generate Time Slots
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Time Slot Generation Options (shown when generate_slots is checked) -->
                    <div id="slotGenerationOptions" style="display: none;" class="mt-3 p-3 border rounded">
                        <h6>Time Slot Configuration</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="slot_duration" class="form-label">Duration (min)</label>
                                <input type="number" class="form-control" id="slot_duration" name="slot_duration" value="30" min="5">
                            </div>
                            <div class="col-md-3">
                                <label for="buffer_time" class="form-label">Buffer (min)</label>
                                <input type="number" class="form-control" id="buffer_time" name="buffer_time" value="15" min="0">
                            </div>
                            <div class="col-md-3">
                                <label for="tables_count" class="form-label">Tables</label>
                                <input type="number" class="form-control" id="tables_count" name="tables_count" value="2" min="1">
                            </div>
                            <div class="col-md-3">
                                <label for="color_code" class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color" id="color_code" name="color_code" value="#0066CC">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="editEvent()">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button type="button" class="btn btn-outline-info" onclick="manageTimeSlots()">
                    <i class="fas fa-clock"></i> Time Slots
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="scheduleEventNotifications()">
                    <i class="fas fa-bell"></i> Notifications
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let calendar;
    let currentEvent = null;
    
    // Initialize calendar
    initializeCalendar();
    
    // Event listeners
    setupEventListeners();
    
    function initializeCalendar() {
        const calendarEl = document.getElementById('competition-calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            height: 'auto',
            events: {
                url: '/admin/scheduling/calendar-events',
                method: 'GET',
                failure: function() {
                    alert('There was an error loading calendar events!');
                }
            },
            eventClick: function(info) {
                showEventDetails(info.event);
            },
            dateClick: function(info) {
                createEventAtDate(info.date);
            },
            eventDidMount: function(info) {
                // Add tooltip
                info.el.setAttribute('title', info.event.extendedProps.description || '');
                
                // Add conflict indicators
                if (info.event.extendedProps.hasConflicts) {
                    info.el.classList.add('event-conflict');
                    info.el.innerHTML += '<i class="fas fa-exclamation-triangle conflict-indicator"></i>';
                }
                
                // Add capacity indicators
                if (info.event.extendedProps.is_full) {
                    info.el.classList.add('event-full');
                    info.el.innerHTML += '<i class="fas fa-users full-indicator"></i>';
                }
            },
            businessHours: <?= json_encode($calendar_config['businessHours']) ?>,
            validRange: <?= json_encode($calendar_config['validRange']) ?>,
            editable: true,
            eventResize: function(info) {
                updateEventTime(info.event);
            },
            eventDrop: function(info) {
                updateEventTime(info.event);
            }
        });
        
        calendar.render();
    }
    
    function setupEventListeners() {
        // Filter changes
        document.getElementById('eventTypeFilter').addEventListener('change', applyFilters);
        document.getElementById('categoryFilter').addEventListener('change', applyFilters);
        document.getElementById('phaseFilter').addEventListener('change', applyFilters);
        document.getElementById('showConflicts').addEventListener('change', applyFilters);
        
        // Create event form
        document.getElementById('createEventForm').addEventListener('submit', handleCreateEvent);
        
        // Generate slots checkbox
        document.getElementById('generate_slots').addEventListener('change', function() {
            const options = document.getElementById('slotGenerationOptions');
            options.style.display = this.checked ? 'block' : 'none';
        });
        
        // Start/end time validation
        document.getElementById('start_datetime').addEventListener('change', validateEventTimes);
        document.getElementById('end_datetime').addEventListener('change', validateEventTimes);
    }
    
    function applyFilters() {
        const filters = {
            event_type: document.getElementById('eventTypeFilter').value,
            category_id: document.getElementById('categoryFilter').value,
            phase_id: document.getElementById('phaseFilter').value,
            show_conflicts: document.getElementById('showConflicts').checked
        };
        
        // Reload calendar with filters
        calendar.removeAllEventSources();
        calendar.addEventSource({
            url: '/admin/scheduling/calendar-events',
            method: 'GET',
            extraParams: filters
        });
    }
    
    function showEventDetails(event) {
        currentEvent = event;
        
        const modalTitle = document.getElementById('eventDetailsModalLabel');
        const modalContent = document.getElementById('eventDetailsContent');
        
        modalTitle.textContent = event.title;
        
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Event Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Type:</strong></td><td>${event.extendedProps.type_label || event.extendedProps.event_type}</td></tr>
                        <tr><td><strong>Status:</strong></td><td><span class="badge bg-${getStatusColor(event.extendedProps.status)}">${event.extendedProps.status}</span></td></tr>
                        <tr><td><strong>Start:</strong></td><td>${formatDateTime(event.start)}</td></tr>
                        <tr><td><strong>End:</strong></td><td>${formatDateTime(event.end)}</td></tr>
                        <tr><td><strong>Duration:</strong></td><td>${calculateDuration(event.start, event.end)}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Participation</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Venue:</strong></td><td>${event.extendedProps.venue || 'TBD'}</td></tr>
                        <tr><td><strong>Category:</strong></td><td>${event.extendedProps.category || 'All'}</td></tr>
                        <tr><td><strong>Participants:</strong></td><td>${event.extendedProps.participants || '0/0'}</td></tr>
                        <tr><td><strong>Mandatory:</strong></td><td>${event.extendedProps.is_mandatory ? 'Yes' : 'No'}</td></tr>
                    </table>
                </div>
            </div>
            
            ${event.extendedProps.description ? `
            <div class="mt-3">
                <h6>Description</h6>
                <p>${event.extendedProps.description}</p>
            </div>
            ` : ''}
            
            ${event.extendedProps.hasConflicts ? `
            <div class="mt-3">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Conflicts Detected</strong>
                    <p class="mb-0">This event has scheduling conflicts. Click "Detect Conflicts" to view details.</p>
                </div>
            </div>
            ` : ''}
        `;
        
        new bootstrap.Modal(document.getElementById('eventDetailsModal')).show();
    }
    
    function createEventAtDate(date) {
        // Pre-fill the form with selected date
        const startInput = document.getElementById('start_datetime');
        const endInput = document.getElementById('end_datetime');
        
        const startTime = new Date(date);
        startTime.setHours(9, 0, 0, 0);
        const endTime = new Date(date);
        endTime.setHours(17, 0, 0, 0);
        
        startInput.value = formatDateTimeLocal(startTime);
        endInput.value = formatDateTimeLocal(endTime);
        
        new bootstrap.Modal(document.getElementById('createEventModal')).show();
    }
    
    function handleCreateEvent(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const eventData = Object.fromEntries(formData);
        
        // Convert checkbox values
        eventData.is_mandatory = formData.has('is_mandatory');
        eventData.generate_slots = formData.has('generate_slots');
        
        fetch('/admin/scheduling/create-event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(eventData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                calendar.refetchEvents();
                bootstrap.Modal.getInstance(document.getElementById('createEventModal')).hide();
                e.target.reset();
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message || 'Error creating event');
                if (data.errors) {
                    displayFormErrors(data.errors);
                }
            }
        })
        .catch(error => {
            showAlert('danger', 'Network error: ' + error.message);
        });
    }
    
    function updateEventTime(event) {
        const updateData = {
            event_id: event.id,
            start_datetime: event.start.toISOString(),
            end_datetime: event.end.toISOString()
        };
        
        fetch('/admin/scheduling/update-event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(updateData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Event updated successfully');
            } else {
                showAlert('danger', data.message);
                // Revert the change
                calendar.refetchEvents();
            }
        });
    }
    
    function validateEventTimes() {
        const startInput = document.getElementById('start_datetime');
        const endInput = document.getElementById('end_datetime');
        
        if (startInput.value && endInput.value) {
            const startTime = new Date(startInput.value);
            const endTime = new Date(endInput.value);
            
            if (endTime <= startTime) {
                endInput.setCustomValidity('End time must be after start time');
            } else {
                endInput.setCustomValidity('');
            }
        }
    }
    
    // Global functions
    window.refreshCalendar = function() {
        calendar.refetchEvents();
        showAlert('info', 'Calendar refreshed');
    };
    
    window.detectConflicts = function() {
        showAlert('info', 'Detecting conflicts...');
        // Implementation would call conflict detection API
    };
    
    window.scheduleNotifications = function() {
        showAlert('info', 'Scheduling notifications...');
        // Implementation would call notification scheduling API
    };
    
    window.editEvent = function() {
        if (currentEvent) {
            // Implementation for editing event
            showAlert('info', 'Edit functionality not yet implemented');
        }
    };
    
    window.manageTimeSlots = function() {
        if (currentEvent) {
            window.location.href = `/admin/scheduling/time-slots?event_id=${currentEvent.id}`;
        }
    };
    
    window.scheduleEventNotifications = function() {
        if (currentEvent) {
            fetch('/admin/scheduling/schedule-notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_id: currentEvent.id,
                    type: 'competition'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message);
                }
            });
        }
    };
    
    // Helper functions
    function formatDateTime(date) {
        return new Date(date).toLocaleString();
    }
    
    function formatDateTimeLocal(date) {
        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - (offset * 60 * 1000));
        return localDate.toISOString().slice(0, 16);
    }
    
    function calculateDuration(start, end) {
        const diff = new Date(end) - new Date(start);
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        return `${hours}h ${minutes}m`;
    }
    
    function getStatusColor(status) {
        const colors = {
            'scheduled': 'primary',
            'in_progress': 'warning',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    function showAlert(type, message) {
        const alertsContainer = document.getElementById('alerts-container') || createAlertsContainer();
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertsContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
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
    
    function displayFormErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = errors[field];
                } else {
                    const div = document.createElement('div');
                    div.className = 'invalid-feedback';
                    div.textContent = errors[field];
                    input.parentNode.appendChild(div);
                }
            }
        });
    }
});
</script>

<style>
.event-conflict {
    border-left: 4px solid #dc3545 !important;
}

.event-full {
    opacity: 0.7;
}

.conflict-indicator {
    color: #dc3545;
    margin-left: 5px;
}

.full-indicator {
    color: #6c757d;
    margin-left: 5px;
}

.fc-event:hover {
    opacity: 0.8;
    cursor: pointer;
}

#alerts-container .alert {
    margin-bottom: 10px;
}

.text-purple {
    color: #6f42c1 !important;
}

.form-control-color {
    width: 100%;
    height: 38px;
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>