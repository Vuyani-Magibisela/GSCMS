# Week 7: Scheduling & Venue Management - Detailed Execution Plan
## Overview

The scheduling system is critical for coordinating the multi-phase GDE SciBOTICS Competition across multiple venues, categories, and timeframes. Based on the competition timeline (July-September 2025), this system needs to handle complex scheduling requirements including training sessions, elimination rounds, and finals.


# TASK 1: SCHEDULING SYSTEM
## 1. MULTI-PHASE COMPETITION CALENDARS
### 1.1 Database Schema Design
```
sql
-- Competition phases table
CREATE TABLE competition_phases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phase_number INT NOT NULL,
    phase_name VARCHAR(100) NOT NULL,
    phase_type ENUM('school_based', 'district_semifinals', 'provincial_finals') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    registration_deadline DATE NOT NULL,
    max_teams_per_category INT DEFAULT NULL,
    status ENUM('planning', 'registration', 'active', 'completed') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dates (start_date, end_date)
);

-- Competition calendar events
CREATE TABLE calendar_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phase_id INT NOT NULL,
    event_type ENUM('training', 'competition', 'meeting', 'deadline', 'announcement') NOT NULL,
    event_name VARCHAR(200) NOT NULL,
    event_description TEXT,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    venue_id INT NULL,
    category_id INT NULL,
    district_id INT NULL,
    recurrence_rule VARCHAR(255) NULL,
    color_code VARCHAR(7) DEFAULT '#0066CC',
    is_mandatory BOOLEAN DEFAULT FALSE,
    max_participants INT NULL,
    current_participants INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phase_id) REFERENCES competition_phases(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_datetime (start_datetime, end_datetime)
);

-- Training sessions tracking (based on PDF schedule)
CREATE TABLE training_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_date DATE NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    morning_slot_start TIME DEFAULT '08:30:00',
    morning_slot_end TIME DEFAULT '12:30:00',
    afternoon_slot_start TIME DEFAULT '13:00:00',
    afternoon_slot_end TIME DEFAULT '16:30:00',
    morning_activity VARCHAR(100),
    afternoon_activity VARCHAR(100),
    venue_id INT NULL,
    max_capacity INT DEFAULT 50,
    registered_teams INT DEFAULT 0,
    status ENUM('available', 'full', 'cancelled') DEFAULT 'available',
    notes TEXT,
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    UNIQUE KEY unique_session (session_date, venue_id)
);
```

## 1.2 Phase-Based Calendar Implementation
**Phase Configuration:**
```php
// app/Models/CompetitionPhase.php
class CompetitionPhase extends Model {
    
    private $phases = [
        1 => [
            'name' => 'School-Based Selection',
            'duration' => '4 weeks',
            'participants' => 'All registered schools',
            'dates' => ['start' => '2025-07-19', 'end' => '2025-08-15']
        ],
        2 => [
            'name' => 'District/Semifinals',
            'duration' => '3 weeks',
            'max_teams' => 15, // per category
            'dates' => ['start' => '2025-08-16', 'end' => '2025-09-05']
        ],
        3 => [
            'name' => 'Provincial Finals',
            'duration' => '1 week',
            'date' => '2025-09-27',
            'venue' => 'Sci-Bono Discovery Centre'
        ]
    ];
    
    public function generatePhaseCalendar($phaseId) {
        // Generate calendar events for each phase
        $phase = $this->phases[$phaseId];
        $events = [];
        
        // Auto-generate training sessions
        if ($phaseId == 1) {
            $events = $this->generateTrainingSchedule();
        }
        
        // Generate competition rounds
        if ($phaseId == 2) {
            $events = $this->generateDistrictRounds();
        }
        
        return $events;
    }
    
    private function generateTrainingSchedule() {
        // Based on PDF training schedule
        $schedule = [
            ['date' => '2025-07-19', 'day' => 'Saturday', 'slots' => ['training', 'training']],
            ['date' => '2025-07-23', 'day' => 'Wednesday', 'slots' => ['training', 'training']],
            ['date' => '2025-07-24', 'day' => 'Thursday', 'slots' => ['training', 'training']],
            // ... continue with all dates from PDF
        ];
        
        return $this->createTrainingEvents($schedule);
    }
}
```

### 1.3 Calendar UI Components
**FullCalendar Integration:**
```javascript
// public/js/competition-calendar.js
class CompetitionCalendar {
    constructor() {
        this.calendar = null;
        this.currentPhase = 1;
        this.filters = {
            category: 'all',
            district: 'all',
            eventType: 'all'
        };
    }
    
    init() {
        const calendarEl = document.getElementById('competition-calendar');
        
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            events: '/api/calendar/events',
            eventColor: this.getEventColor,
            eventClick: this.handleEventClick,
            dateClick: this.handleDateClick,
            businessHours: {
                daysOfWeek: [1,2,3,4,5,6], // Mon-Sat
                startTime: '08:00',
                endTime: '17:00'
            },
            validRange: {
                start: '2025-07-01',
                end: '2025-10-31'
            }
        });
        
        this.calendar.render();
    }
    
    getEventColor(event) {
        const colors = {
            'training': '#28a745',      // Green
            'competition': '#dc3545',   // Red
            'meeting': '#ffc107',       // Yellow
            'deadline': '#6f42c1',      // Purple
            'announcement': '#17a2b8'   // Cyan
        };
        return colors[event.extendedProps.type] || '#007bff';
    }
    
    handleEventClick(info) {
        // Show event details modal
        const event = info.event;
        $('#eventModal').modal('show');
        $('#eventTitle').text(event.title);
        $('#eventDescription').html(event.extendedProps.description);
        $('#eventVenue').text(event.extendedProps.venue);
        $('#eventCapacity').text(`${event.extendedProps.current}/${event.extendedProps.max}`);
    }
}
```
## 2. TIME SLOT ALLOCATION SYSTEM
### 2.1 Database Design
```sql
-- Time slots management
CREATE TABLE time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    venue_id INT NOT NULL,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_type ENUM('competition', 'practice', 'judging', 'break', 'setup') NOT NULL,
    category_id INT NULL,
    team_id INT NULL,
    judge_panel_id INT NULL,
    table_number VARCHAR(10) NULL,
    duration_minutes INT NOT NULL,
    buffer_minutes INT DEFAULT 15,
    status ENUM('available', 'reserved', 'confirmed', 'completed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES calendar_events(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    INDEX idx_datetime (slot_date, start_time),
    UNIQUE KEY unique_slot (venue_id, slot_date, start_time, table_number)
);

-- Team scheduling preferences
CREATE TABLE scheduling_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_id INT NOT NULL,
    preferred_date DATE NULL,
    preferred_time_slot ENUM('morning', 'afternoon', 'any') DEFAULT 'any',
    avoid_dates TEXT NULL,
    special_requirements TEXT NULL,
    transportation_needed BOOLEAN DEFAULT FALSE,
    accommodation_needed BOOLEAN DEFAULT FALSE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id)
);
```
### 2.2 Smart Allocation Algorithm
```php
// app/Services/TimeSlotAllocator.php
class TimeSlotAllocator {
    
    private $slotDuration = 30; // minutes per team
    private $bufferTime = 15;   // minutes between slots
    private $judgeRotation = 3;  // teams per judge panel
    
    public function allocateCompetitionSlots($eventId, $categoryId) {
        $teams = $this->getRegisteredTeams($categoryId);
        $venues = $this->getAvailableVenues($eventId);
        $judges = $this->getAvailableJudges($categoryId);
        
        $schedule = [];
        
        foreach ($venues as $venue) {
            $tables = $venue->competition_tables;
            $slots = $this->generateTimeSlots($venue->operating_hours);
            
            $teamIndex = 0;
            foreach ($slots as $slot) {
                for ($table = 1; $table <= $tables; $table++) {
                    if ($teamIndex < count($teams)) {
                        $schedule[] = [
                            'team_id' => $teams[$teamIndex]->id,
                            'venue_id' => $venue->id,
                            'table' => "T{$table}",
                            'start_time' => $slot['start'],
                            'end_time' => $slot['end'],
                            'judge_panel' => $this->assignJudgePanel($judges, $teamIndex)
                        ];
                        $teamIndex++;
                    }
                }
            }
        }
        
        return $this->optimizeSchedule($schedule);
    }
    
    private function generateTimeSlots($operatingHours) {
        $slots = [];
        $current = strtotime($operatingHours['start']);
        $end = strtotime($operatingHours['end']);
        
        while ($current < $end) {
            $slotEnd = $current + ($this->slotDuration * 60);
            $slots[] = [
                'start' => date('H:i', $current),
                'end' => date('H:i', $slotEnd)
            ];
            $current = $slotEnd + ($this->bufferTime * 60);
        }
        
        return $slots;
    }
    
    private function optimizeSchedule($schedule) {
        // Minimize travel time for teams from same school
        // Balance judge workload
        // Respect team preferences
        // Avoid conflicts
        
        usort($schedule, function($a, $b) {
            // Sort by school, then by time
            $schoolA = $this->getTeamSchool($a['team_id']);
            $schoolB = $this->getTeamSchool($b['team_id']);
            
            if ($schoolA == $schoolB) {
                return strcmp($a['start_time'], $b['start_time']);
            }
            return strcmp($schoolA, $schoolB);
        });
        
        return $schedule;
    }
}
```

## 2.3 Interactive Scheduling Interface
```javascript
// public/js/schedule-builder.js
class ScheduleBuilder {
    constructor() {
        this.draggedTeam = null;
        this.timeSlots = [];
    }
    
    initDragAndDrop() {
        // Make teams draggable
        $('.team-card').draggable({
            helper: 'clone',
            cursor: 'move',
            start: (event, ui) => {
                this.draggedTeam = $(event.target).data('team-id');
            }
        });
        
        // Make time slots droppable
        $('.time-slot').droppable({
            accept: '.team-card',
            hoverClass: 'slot-hover',
            drop: (event, ui) => {
                const slotId = $(event.target).data('slot-id');
                this.assignTeamToSlot(this.draggedTeam, slotId);
            }
        });
    }
    
    assignTeamToSlot(teamId, slotId) {
        $.ajax({
            url: '/api/schedule/assign',
            method: 'POST',
            data: {
                team_id: teamId,
                slot_id: slotId
            },
            success: (response) => {
                if (response.success) {
                    this.updateScheduleDisplay();
                    toastr.success('Team scheduled successfully');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    }
    
    bulkSchedule() {
        // Auto-schedule all unassigned teams
        $.ajax({
            url: '/api/schedule/bulk-assign',
            method: 'POST',
            data: {
                category_id: $('#category').val(),
                venue_id: $('#venue').val(),
                date: $('#competition-date').val()
            },
            success: (response) => {
                this.renderSchedule(response.schedule);
                toastr.success(`${response.teams_scheduled} teams scheduled`);
            }
        });
    }
}
```

## 3. CONFLICT DETECTION AND RESOLUTION
### 3.1 Conflict Detection System
```sql
-- Conflict tracking table
CREATE TABLE scheduling_conflicts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conflict_type ENUM('double_booking', 'judge_overlap', 'venue_capacity', 
                       'team_availability', 'resource_shortage') NOT NULL,
    severity ENUM('warning', 'error', 'critical') NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    conflicting_entity_id INT NULL,
    conflict_date DATE NOT NULL,
    conflict_time TIME NULL,
    description TEXT NOT NULL,
    resolution_status ENUM('pending', 'resolved', 'ignored') DEFAULT 'pending',
    resolved_by INT NULL,
    resolution_notes TEXT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id),
    INDEX idx_status (resolution_status, severity)
);
```

## 3.2 Conflict Detection Engine
```php
// app/Services/ConflictDetector.php
class ConflictDetector {
    
    private $conflicts = [];
    
    public function detectAllConflicts($eventId) {
        $this->checkDoubleBookings($eventId);
        $this->checkJudgeAvailability($eventId);
        $this->checkVenueCapacity($eventId);
        $this->checkTeamConflicts($eventId);
        $this->checkResourceAvailability($eventId);
        
        return $this->conflicts;
    }
    
    private function checkDoubleBookings($eventId) {
        $sql = "SELECT t1.*, t2.id as conflicting_id
                FROM time_slots t1
                INNER JOIN time_slots t2 ON 
                    t1.venue_id = t2.venue_id AND
                    t1.slot_date = t2.slot_date AND
                    t1.table_number = t2.table_number AND
                    t1.id != t2.id
                WHERE 
                    t1.event_id = ? AND
                    t1.status IN ('reserved', 'confirmed') AND
                    t2.status IN ('reserved', 'confirmed') AND
                    ((t1.start_time < t2.end_time AND t1.end_time > t2.start_time))";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'type' => 'double_booking',
                'severity' => 'critical',
                'description' => "Table {$conflict->table_number} double-booked",
                'entities' => [$conflict->id, $conflict->conflicting_id]
            ]);
        }
    }
    
    private function checkJudgeAvailability($eventId) {
        // Check if judges are assigned to multiple places simultaneously
        $sql = "SELECT j1.judge_id, j1.slot_id, j2.slot_id as conflicting_slot
                FROM judge_assignments j1
                INNER JOIN judge_assignments j2 ON j1.judge_id = j2.judge_id
                INNER JOIN time_slots s1 ON j1.slot_id = s1.id
                INNER JOIN time_slots s2 ON j2.slot_id = s2.id
                WHERE 
                    s1.event_id = ? AND
                    j1.id != j2.id AND
                    s1.slot_date = s2.slot_date AND
                    ((s1.start_time < s2.end_time AND s1.end_time > s2.start_time))";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'type' => 'judge_overlap',
                'severity' => 'error',
                'description' => "Judge assigned to multiple sessions simultaneously",
                'judge_id' => $conflict->judge_id
            ]);
        }
    }
    
    public function resolveConflict($conflictId, $resolution) {
        $conflict = $this->getConflict($conflictId);
        
        switch ($conflict->conflict_type) {
            case 'double_booking':
                return $this->resolveDoubleBooking($conflict, $resolution);
            case 'judge_overlap':
                return $this->resolveJudgeOverlap($conflict, $resolution);
            case 'venue_capacity':
                return $this->resolveVenueCapacity($conflict, $resolution);
            default:
                return false;
        }
    }
}
```
## 3.2 Conflict Detection Engine
```php
// app/Services/ConflictDetector.php
class ConflictDetector {
    
    private $conflicts = [];
    
    public function detectAllConflicts($eventId) {
        $this->checkDoubleBookings($eventId);
        $this->checkJudgeAvailability($eventId);
        $this->checkVenueCapacity($eventId);
        $this->checkTeamConflicts($eventId);
        $this->checkResourceAvailability($eventId);
        
        return $this->conflicts;
    }
    
    private function checkDoubleBookings($eventId) {
        $sql = "SELECT t1.*, t2.id as conflicting_id
                FROM time_slots t1
                INNER JOIN time_slots t2 ON 
                    t1.venue_id = t2.venue_id AND
                    t1.slot_date = t2.slot_date AND
                    t1.table_number = t2.table_number AND
                    t1.id != t2.id
                WHERE 
                    t1.event_id = ? AND
                    t1.status IN ('reserved', 'confirmed') AND
                    t2.status IN ('reserved', 'confirmed') AND
                    ((t1.start_time < t2.end_time AND t1.end_time > t2.start_time))";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'type' => 'double_booking',
                'severity' => 'critical',
                'description' => "Table {$conflict->table_number} double-booked",
                'entities' => [$conflict->id, $conflict->conflicting_id]
            ]);
        }
    }
    
    private function checkJudgeAvailability($eventId) {
        // Check if judges are assigned to multiple places simultaneously
        $sql = "SELECT j1.judge_id, j1.slot_id, j2.slot_id as conflicting_slot
                FROM judge_assignments j1
                INNER JOIN judge_assignments j2 ON j1.judge_id = j2.judge_id
                INNER JOIN time_slots s1 ON j1.slot_id = s1.id
                INNER JOIN time_slots s2 ON j2.slot_id = s2.id
                WHERE 
                    s1.event_id = ? AND
                    j1.id != j2.id AND
                    s1.slot_date = s2.slot_date AND
                    ((s1.start_time < s2.end_time AND s1.end_time > s2.start_time))";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'type' => 'judge_overlap',
                'severity' => 'error',
                'description' => "Judge assigned to multiple sessions simultaneously",
                'judge_id' => $conflict->judge_id
            ]);
        }
    }
    
    public function resolveConflict($conflictId, $resolution) {
        $conflict = $this->getConflict($conflictId);
        
        switch ($conflict->conflict_type) {
            case 'double_booking':
                return $this->resolveDoubleBooking($conflict, $resolution);
            case 'judge_overlap':
                return $this->resolveJudgeOverlap($conflict, $resolution);
            case 'venue_capacity':
                return $this->resolveVenueCapacity($conflict, $resolution);
            default:
                return false;
        }
    }
}
```
## 3.3 Resolution Interface
```javascript
// public/js/conflict-resolver.js
class ConflictResolver {
    constructor() {
        this.conflicts = [];
        this.resolutionStrategies = {
            'double_booking': ['reschedule_team', 'change_venue', 'change_table'],
            'judge_overlap': ['reassign_judge', 'reschedule_session', 'add_judge'],
            'venue_capacity': ['split_groups', 'change_venue', 'extend_hours']
        };
    }
    
    loadConflicts() {
        $.ajax({
            url: '/api/schedule/conflicts',
            success: (data) => {
                this.conflicts = data.conflicts;
                this.renderConflicts();
            }
        });
    }
    
    renderConflicts() {
        const container = $('#conflict-list');
        container.empty();
        
        this.conflicts.forEach(conflict => {
            const card = $(`
                <div class="conflict-card ${conflict.severity}">
                    <div class="conflict-header">
                        <span class="conflict-type">${conflict.type}</span>
                        <span class="severity-badge">${conflict.severity}</span>
                    </div>
                    <p class="conflict-description">${conflict.description}</p>
                    <div class="resolution-options">
                        ${this.getResolutionOptions(conflict)}
                    </div>
                    <button class="btn-resolve" data-conflict-id="${conflict.id}">
                        Resolve
                    </button>
                </div>
            `);
            container.append(card);
        });
    }
    
    getResolutionOptions(conflict) {
        const strategies = this.resolutionStrategies[conflict.type] || [];
        return strategies.map(strategy => 
            `<label>
                <input type="radio" name="resolution-${conflict.id}" value="${strategy}">
                ${this.formatStrategy(strategy)}
            </label>`
        ).join('');
    }
    
    autoResolve() {
        // Attempt to automatically resolve conflicts
        $.ajax({
            url: '/api/schedule/auto-resolve',
            method: 'POST',
            success: (response) => {
                toastr.success(`Resolved ${response.resolved} of ${response.total} conflicts`);
                this.loadConflicts();
            }
        });
    }
}
```

## 4. AUTOMATED REMINDER NOTIFICATIONS
### 4.1 Notification System Database
```sql
-- Notification templates
CREATE TABLE notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('email', 'sms', 'whatsapp', 'push') NOT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    trigger_timing VARCHAR(50) NOT NULL, -- e.g., '24_hours_before', 'immediately'
    subject VARCHAR(200) NULL,
    body_template TEXT NOT NULL,
    variables JSON NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Scheduled notifications
CREATE TABLE scheduled_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    recipient_type ENUM('team', 'school', 'judge', 'volunteer', 'all') NOT NULL,
    recipient_id INT NULL,
    scheduled_for DATETIME NOT NULL,
    data JSON NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id),
    INDEX idx_scheduled (scheduled_for, status)
);

-- Notification log
CREATE TABLE notification_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NOT NULL,
    channel ENUM('email', 'sms', 'whatsapp', 'push') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    status ENUM('delivered', 'failed', 'bounced', 'opened') NOT NULL,
    delivered_at TIMESTAMP NULL,
    opened_at TIMESTAMP NULL,
    error_details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES scheduled_notifications(id)
);
```
## 4.2 Notification Scheduler Service
```php
// app/Services/NotificationScheduler.php
class NotificationScheduler {
    
    private $templates = [];
    private $queue = [];
    
    public function scheduleCompetitionReminders($eventId) {
        $event = $this->getEvent($eventId);
        $participants = $this->getEventParticipants($eventId);
        
        // Schedule different reminder types
        $this->scheduleReminder($event, $participants, '1_week_before');
        $this->scheduleReminder($event, $participants, '3_days_before');
        $this->scheduleReminder($event, $participants, '1_day_before');
        $this->scheduleReminder($event, $participants, 'morning_of');
        
        return count($this->queue);
    }
    
    private function scheduleReminder($event, $participants, $timing) {
        $sendTime = $this->calculateSendTime($event->start_datetime, $timing);
        $template = $this->getTemplate("competition_reminder_{$timing}");
        
        foreach ($participants as $participant) {
            $notification = [
                'template_id' => $template->id,
                'recipient_type' => $participant->type,
                'recipient_id' => $participant->id,
                'scheduled_for' => $sendTime,
                'data' => [
                    'event_name' => $event->name,
                    'event_date' => $event->start_datetime,
                    'venue' => $event->venue_name,
                    'team_name' => $participant->team_name,
                    'time_slot' => $participant->time_slot,
                    'table_number' => $participant->table_number
                ]
            ];
            
            $this->queue[] = $notification;
        }
    }
    
    private function calculateSendTime($eventTime, $timing) {
        $mappings = [
            '1_week_before' => '-7 days',
            '3_days_before' => '-3 days',
            '1_day_before' => '-1 day',
            'morning_of' => 'today 07:00'
        ];
        
        $eventTimestamp = strtotime($eventTime);
        return date('Y-m-d H:i:s', strtotime($mappings[$timing], $eventTimestamp));
    }
    
    public function processQueue() {
        $pending = $this->getPendingNotifications();
        
        foreach ($pending as $notification) {
            try {
                $this->sendNotification($notification);
                $this->markAsSent($notification->id);
            } catch (Exception $e) {
                $this->handleFailure($notification->id, $e->getMessage());
            }
        }
    }
}
```
## 4.3 Multi-Channel Delivery System
```php
// app/Services/NotificationDelivery.php
class NotificationDelivery {
    
    private $channels = [];
    
    public function __construct() {
        $this->channels = [
            'email' => new EmailChannel(),
            'sms' => new SMSChannel(),
            'whatsapp' => new WhatsAppChannel()
        ];
    }
    
    public function send($notification) {
        $recipient = $this->getRecipient($notification);
        $template = $this->getTemplate($notification->template_id);
        $message = $this->renderTemplate($template, $notification->data);
        
        // Send via preferred channel
        $preferredChannel = $recipient->notification_preference ?? 'email';
        
        if (isset($this->channels[$preferredChannel])) {
            return $this->channels[$preferredChannel]->send($recipient, $message);
        }
        
        // Fallback to email
        return $this->channels['email']->send($recipient, $message);
    }
    
    private function renderTemplate($template, $data) {
        $body = $template->body_template;
        
        // Replace variables
        foreach ($data as $key => $value) {
            $body = str_replace("{{$key}}", $value, $body);
        }
        
        return $body;
    }
}

// WhatsApp Integration
class WhatsAppChannel {
    
    private $apiKey;
    private $apiUrl = 'https://api.whatsapp.com/v1/messages';
    
    public function send($recipient, $message) {
        $payload = [
            'to' => $this->formatPhoneNumber($recipient->whatsapp_number),
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        $response = $this->httpPost($this->apiUrl, $payload, [
            'Authorization' => 'Bearer ' . $this->apiKey
        ]);
        
        return $response->success;
    }
    
    private function formatPhoneNumber($number) {
        // Format for South African numbers
        $number = preg_replace('/[^0-9]/', '', $number);
        if (substr($number, 0, 1) == '0') {
            $number = '27' . substr($number, 1);
        }
        return $number;
    }
}
```
## 4.4 Notification Management UI
```javascript
// public/js/notification-manager.js
class NotificationManager {
    constructor() {
        this.templates = [];
        this.scheduled = [];
    }
    
    initTemplateBuilder() {
        $('#template-form').on('submit', (e) => {
            e.preventDefault();
            
            const template = {
                name: $('#template-name').val(),
                type: $('#notification-type').val(),
                trigger_event: $('#trigger-event').val(),
                trigger_timing: $('#trigger-timing').val(),
                subject: $('#template-subject').val(),
                body: this.editor.getData()
            };
            
            this.saveTemplate(template);
        });
        
        // Initialize rich text editor for template body
        ClassicEditor
            .create(document.querySelector('#template-body'), {
                toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList'],
                placeholder: 'Use variables like {{team_name}}, {{event_date}}, {{venue}}'
            })
            .then(editor => {
                this.editor = editor;
            });
    }
    
    scheduleNotifications() {
        const data = {
            event_id: $('#event-select').val(),
            channels: $('.channel-checkbox:checked').map(function() {
                return $(this).val();
            }).get(),
            timings: $('.timing-checkbox:checked').map(function() {
                return $(this).val();
            }).get()
        };
        
        $.ajax({
            url: '/api/notifications/schedule-bulk',
            method: 'POST',
            data: data,
            success: (response) => {
                toastr.success(`${response.count} notifications scheduled`);
                this.loadScheduled();
            }
        });
    }
    
    viewScheduledNotifications() {
        const calendar = new FullCalendar.Calendar(document.getElementById('notification-calendar'), {
            initialView: 'listWeek',
            events: '/api/notifications/scheduled',
            eventClick: (info) => {
                this.showNotificationDetails(info.event.id);
            }
        });
        
        calendar.render();
    }
    
    testNotification() {
        const testData = {
            template_id: $('#test-template').val(),
            recipient: $('#test-recipient').val(),
            channel: $('#test-channel').val()
        };
        
        $.ajax({
            url: '/api/notifications/test',
            method: 'POST',
            data: testData,
            success: (response) => {
                toastr.success('Test notification sent successfully');
                $('#test-result').html(response.preview);
            }
        });
    }
}
```
# IMPLEMENTATION TIMELINE
## Database Setup & Core Models
- [ ] Create all scheduling-related tables
- [ ] Set up indexes and foreign keys
- [ ] Build Phase, Event, TimeSlot models
- [ ] Implement base scheduling service

## Calendar System

- [ ] Integrate FullCalendar library
- [ ] Build calendar API endpoints
- [ ] Create phase-based calendar views
- [ ] Implement event management interface

## Time Slot Allocation

- [ ] Build slot generation algorithm
- [ ] Create allocation optimization logic
- [ ] Implement drag-and-drop scheduling
- [ ] Add bulk scheduling capabilities

# Conflict Detection

- [ ] Implement conflict detection engine
- [ ] Build resolution strategies
- [ ] Create conflict management UI
- [ ] Add auto-resolution features

## Notification System

- [ ] Set up notification templates
- [ ] Build scheduling engine
- [ ] Integrate WhatsApp/SMS/Email
- [ ] Create notification management UI

# KEY DELIVERABLES
## 1. Multi-Phase Calendar System

- Visual calendar for all competition phases
- Separate views for different user roles
- Color-coded event types
- Print-ready schedule formats

## 2. Intelligent Scheduling Engine

- Automated time slot allocation
- Preference-based scheduling
- Load balancing across venues
- Travel time optimization

## 3. Proactive Conflict Management

- Real-time conflict detection
- Multiple resolution strategies
- Automated conflict resolution
- Conflict prevention rules

## 4. Multi-Channel Notifications

- Template-based messaging
- Scheduled reminders
- WhatsApp/SMS/Email delivery
- Delivery tracking and analytics

# TESTING CHECKLIST
## Functional Testing

- [ ] Calendar event creation/editing
- [ ] Time slot allocation accuracy
- [ ] Conflict detection reliability
- [ ] Notification delivery success

## Performance Testing

- [ ] Schedule generation speed (```target: <5 sec for 100 teams```)
- [ ] Calendar loading time
- [ ] Concurrent user handling
- [ ] Notification queue processing

## Integration Testing

- [ ] WhatsApp API integration
- [ ] Email delivery service
- [ ] SMS gateway connection
- [ ] Calendar export formats

## User Acceptance Testing

- [ ] Schedule clarity and readability
- [ ] Notification timing accuracy
- [ ] Conflict resolution effectiveness
- [ ] Mobile responsiveness

---
# SUCCESS METRICS
| Metric | Target | Measurement Method |
| ------ | ------- | ------------------|
| Schedule Generation Time | ```<5 seconds``` | System monitoring |
| Conflict Resolution Rate | ```>95% automated``` | Conflict logs |
| Notification Delivery Rate | ```>98%``` | Delivery reports |
| User Satisfaction | ```>4.5/5``` | User surveys |
| Double Booking Incidents | 0 | Error logs |
| On-Time Competition Start | 100% | Event tracking |

# RISK MITIGATION 
| Risk | Impact | Mitigation Strategy |
| --- | --- | --- |
| Venue availability changes | High | Real-time venue sync, backup venues |
| Mass notification failure | High | Multiple delivery channels, retry logic |
| Schedule conflicts | Medium | Automated detection, manual override |
| Time zone confusion | Medium | Clear timezone display, local time conversion |
