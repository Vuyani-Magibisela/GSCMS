<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CalendarEvent;
use App\Models\TimeSlot;
use App\Models\TrainingSession;
use App\Models\SchedulingConflict;
use App\Models\CompetitionPhase;
use App\Services\TimeSlotAllocator;
use App\Services\ConflictDetector;
use App\Services\NotificationScheduler;

class SchedulingController extends BaseController
{
    private $calendarEvent;
    private $timeSlot;
    private $trainingSession;
    private $schedulingConflict;
    private $timeSlotAllocator;
    private $conflictDetector;
    private $notificationScheduler;
    
    public function __construct()
    {
        parent::__construct();
        $this->calendarEvent = new CalendarEvent();
        $this->timeSlot = new TimeSlot();
        $this->trainingSession = new TrainingSession();
        $this->schedulingConflict = new SchedulingConflict();
        $this->timeSlotAllocator = new TimeSlotAllocator();
        $this->conflictDetector = new ConflictDetector();
        $this->notificationScheduler = new NotificationScheduler();
    }
    
    /**
     * Display scheduling dashboard
     */
    public function index()
    {
        try {
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            // Get dashboard statistics
            $stats = [
                'upcoming_events' => $this->calendarEvent->getUpcoming(5),
                'active_conflicts' => $this->schedulingConflict->getActive(),
                'recent_training_sessions' => $this->trainingSession->getUpcoming(5),
                'slot_utilization' => $this->getSlotUtilizationStats(),
                'conflict_summary' => $this->schedulingConflict->getStatistics(),
                'notification_stats' => $this->notificationScheduler->getStatistics()
            ];
            
            $data = [
                'stats' => $stats,
                'page_title' => 'Scheduling Dashboard'
            ];
            
            return $this->render('admin/scheduling/dashboard', $data);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'Error loading scheduling dashboard');
            return $this->redirect('/admin/dashboard');
        }
    }
    
    /**
     * Display calendar view
     */
    public function calendar()
    {
        try {
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            $data = [
                'page_title' => 'Competition Calendar',
                'calendar_config' => $this->getCalendarConfig()
            ];
            
            return $this->render('admin/scheduling/calendar', $data);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'Error loading calendar');
            return $this->redirect('/admin/scheduling');
        }
    }
    
    /**
     * Get calendar events (AJAX endpoint)
     */
    public function getCalendarEvents()
    {
        try {
            header('Content-Type: application/json');
            
            $start = $this->input('start');
            $end = $this->input('end');
            $eventType = $this->input('event_type');
            $categoryId = $this->input('category_id');
            
            $events = $this->getEventsForCalendar($start, $end, $eventType, $categoryId);
            
            echo json_encode($events);
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Create calendar event
     */
    public function createEvent()
    {
        try {
            header('Content-Type: application/json');
            
            $eventData = $this->getJsonInput();
            
            // Validate required fields
            $validation = $this->validateEventData($eventData);
            if (!$validation['valid']) {
                echo json_encode(['success' => false, 'errors' => $validation['errors']]);
                exit;
            }
            
            // Create event
            $event = new CalendarEvent();
            foreach ($eventData as $key => $value) {
                if (in_array($key, $event->getFillable())) {
                    $event->$key = $value;
                }
            }
            $event->created_by = $_SESSION['user_id'];
            
            if ($event->save()) {
                // Generate time slots if it's a competition event
                if ($event->event_type === CalendarEvent::TYPE_COMPETITION && isset($eventData['generate_slots'])) {
                    $this->generateTimeSlotsForEvent($event->id, $eventData);
                }
                
                // Schedule notifications
                $this->notificationScheduler->scheduleCompetitionReminders($event->id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'event' => $event->toCalendarEvent()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create event']);
            }
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Update calendar event
     */
    public function updateEvent()
    {
        try {
            header('Content-Type: application/json');
            
            $eventId = $this->input('event_id');
            $updateData = $this->getJsonInput();
            
            $event = $this->calendarEvent->find($eventId);
            if (!$event) {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                exit;
            }
            
            // Check for conflicts with update
            $conflicts = $this->conflictDetector->detectRealTimeConflicts('event', $eventId, $updateData);
            if (!empty($conflicts)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Update would create conflicts',
                    'conflicts' => $conflicts
                ]);
                exit;
            }
            
            // Update event
            foreach ($updateData as $key => $value) {
                if (in_array($key, $event->getFillable())) {
                    $event->$key = $value;
                }
            }
            
            if ($event->save()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event updated successfully',
                    'event' => $event->toCalendarEvent()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update event']);
            }
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Display time slot allocation interface
     */
    public function timeSlots()
    {
        try {
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            $eventId = $this->input('event_id');
            $event = null;
            $timeSlots = [];
            
            if ($eventId) {
                $event = $this->calendarEvent->find($eventId);
                $timeSlots = $this->timeSlot->getForEvent($eventId);
            }
            
            $data = [
                'event' => $event,
                'time_slots' => $timeSlots,
                'available_events' => $this->calendarEvent->getByType(CalendarEvent::TYPE_COMPETITION),
                'page_title' => 'Time Slot Management'
            ];
            
            return $this->render('admin/scheduling/time_slots', $data);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'Error loading time slots');
            return $this->redirect('/admin/scheduling');
        }
    }
    
    /**
     * Auto-allocate time slots
     */
    public function autoAllocateSlots()
    {
        try {
            header('Content-Type: application/json');
            
            $eventId = $this->input('event_id');
            $categoryId = $this->input('category_id');
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                exit;
            }
            
            $result = $this->timeSlotAllocator->allocateCompetitionSlots($eventId, $categoryId);
            
            if ($result['success']) {
                // Detect conflicts after allocation
                $this->conflictDetector->detectAllConflicts($eventId);
            }
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Bulk assign teams to slots
     */
    public function bulkAssignTeams()
    {
        try {
            header('Content-Type: application/json');
            
            $eventId = $this->input('event_id');
            $categoryId = $this->input('category_id');
            $venueId = $this->input('venue_id');
            
            $result = $this->timeSlotAllocator->bulkAllocateTeams($eventId, $categoryId, $venueId);
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Assign team to specific slot (drag-and-drop)
     */
    public function assignTeamToSlot()
    {
        try {
            header('Content-Type: application/json');
            
            $slotId = $this->input('slot_id');
            $teamId = $this->input('team_id');
            $categoryId = $this->input('category_id');
            
            $timeSlot = $this->timeSlot->find($slotId);
            if (!$timeSlot) {
                echo json_encode(['success' => false, 'message' => 'Time slot not found']);
                exit;
            }
            
            $result = $timeSlot->reserve($teamId, $categoryId);
            
            if ($result['success']) {
                // Check for conflicts after assignment
                $conflicts = $this->conflictDetector->detectRealTimeConflicts('time_slot', $slotId, [
                    'team_id' => $teamId,
                    'category_id' => $categoryId
                ]);
                
                if (!empty($conflicts)) {
                    $result['warnings'] = $conflicts;
                }
            }
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Display conflict management interface
     */
    public function conflicts()
    {
        try {
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            $conflicts = $this->schedulingConflict->getActive();
            $statistics = $this->schedulingConflict->getStatistics();
            
            $data = [
                'conflicts' => $conflicts,
                'statistics' => $statistics,
                'page_title' => 'Scheduling Conflicts'
            ];
            
            return $this->render('admin/scheduling/conflicts', $data);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'Error loading conflicts');
            return $this->redirect('/admin/scheduling');
        }
    }
    
    /**
     * Detect conflicts for event
     */
    public function detectConflicts()
    {
        try {
            header('Content-Type: application/json');
            
            $eventId = $this->input('event_id');
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                exit;
            }
            
            $result = $this->conflictDetector->detectAllConflicts($eventId);
            
            echo json_encode([
                'success' => true,
                'conflicts' => $result['conflicts'],
                'summary' => $result['summary']
            ]);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Resolve conflict
     */
    public function resolveConflict()
    {
        try {
            header('Content-Type: application/json');
            
            $conflictId = $this->input('conflict_id');
            $resolutionMethod = $this->input('resolution_method');
            
            $result = $this->conflictDetector->resolveConflict(
                $conflictId, 
                $resolutionMethod, 
                $_SESSION['user_id']
            );
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Auto-resolve conflicts
     */
    public function autoResolveConflicts()
    {
        try {
            header('Content-Type: application/json');
            
            $eventId = $this->input('event_id');
            
            $result = $this->timeSlotAllocator->autoResolveConflicts($eventId);
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Display training session management
     */
    public function trainingSessions()
    {
        try {
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            $startDate = $this->input('start_date', date('Y-m-d'));
            $endDate = $this->input('end_date', date('Y-m-d', strtotime('+30 days')));
            
            $sessions = $this->trainingSession->getInDateRange($startDate, $endDate);
            
            $data = [
                'sessions' => $sessions,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'page_title' => 'Training Sessions'
            ];
            
            return $this->render('admin/scheduling/training_sessions', $data);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'Error loading training sessions');
            return $this->redirect('/admin/scheduling');
        }
    }
    
    /**
     * Generate training schedule
     */
    public function generateTrainingSchedule()
    {
        try {
            header('Content-Type: application/json');
            
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            $venueId = $this->input('venue_id');
            
            if (!$startDate || !$endDate) {
                echo json_encode(['success' => false, 'message' => 'Start and end dates are required']);
                exit;
            }
            
            $result = TrainingSession::generateSchedule($startDate, $endDate, $venueId);
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Schedule notifications
     */
    public function scheduleNotifications()
    {
        try {
            header('Content-Type: application/json');
            
            $eventId = $this->input('event_id');
            $notificationType = $this->input('type', 'competition');
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                exit;
            }
            
            if ($notificationType === 'training') {
                $result = $this->notificationScheduler->scheduleTrainingReminders($eventId);
            } else {
                $result = $this->notificationScheduler->scheduleCompetitionReminders($eventId);
            }
            
            echo json_encode($result);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Process notification queue
     */
    public function processNotifications()
    {
        try {
            header('Content-Type: application/json');
            
            $result = $this->notificationScheduler->processQueue();
            
            echo json_encode([
                'success' => true,
                'message' => "Processed {$result['processed']} notifications, {$result['failed']} failed",
                'statistics' => $result
            ]);
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    // PRIVATE HELPER METHODS
    
    /**
     * Get events for calendar display
     */
    private function getEventsForCalendar($start, $end, $eventType = null, $categoryId = null)
    {
        $events = $this->calendarEvent->getInDateRange($start, $end);
        $calendarEvents = [];
        
        foreach ($events as $event) {
            // Filter by event type if specified
            if ($eventType && $event->event_type !== $eventType) {
                continue;
            }
            
            // Filter by category if specified
            if ($categoryId && $event->category_id != $categoryId) {
                continue;
            }
            
            $calendarEvents[] = $event->toCalendarEvent();
        }
        
        return $calendarEvents;
    }
    
    /**
     * Get calendar configuration
     */
    private function getCalendarConfig()
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek'
            ],
            'businessHours' => [
                'daysOfWeek' => [1, 2, 3, 4, 5, 6], // Mon-Sat
                'startTime' => '08:00',
                'endTime' => '17:00'
            ],
            'validRange' => [
                'start' => '2025-07-01',
                'end' => '2025-10-31'
            ],
            'eventColors' => [
                'training' => '#28a745',
                'competition' => '#dc3545',
                'meeting' => '#ffc107',
                'deadline' => '#6f42c1',
                'announcement' => '#17a2b8'
            ]
        ];
    }
    
    /**
     * Validate event data
     */
    private function validateEventData($data)
    {
        $errors = [];
        
        if (empty($data['phase_id'])) {
            $errors['phase_id'] = 'Competition phase is required';
        }
        if (empty($data['event_type'])) {
            $errors['event_type'] = 'Event type is required';
        }
        if (empty($data['event_name'])) {
            $errors['event_name'] = 'Event name is required';
        }
        if (empty($data['start_datetime'])) {
            $errors['start_datetime'] = 'Start date and time is required';
        }
        if (empty($data['end_datetime'])) {
            $errors['end_datetime'] = 'End date and time is required';
        }
        
        if (!empty($data['start_datetime']) && !empty($data['end_datetime'])) {
            if (strtotime($data['end_datetime']) <= strtotime($data['start_datetime'])) {
                $errors['end_datetime'] = 'End time must be after start time';
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Generate time slots for event
     */
    private function generateTimeSlotsForEvent($eventId, $config)
    {
        $venueId = $config['venue_id'] ?? null;
        if (!$venueId) {
            return;
        }
        
        $slotConfig = [
            'slot_duration' => $config['slot_duration'] ?? 30,
            'buffer_time' => $config['buffer_time'] ?? 15,
            'tables_count' => $config['tables_count'] ?? 2,
            'start_time' => $config['start_time'] ?? '09:00:00',
            'end_time' => $config['end_time'] ?? '16:00:00'
        ];
        
        TimeSlot::generateSlotsForEvent($eventId, $venueId, $slotConfig);
    }
    
    /**
     * Get slot utilization statistics
     */
    private function getSlotUtilizationStats()
    {
        $stats = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 1) as percentage
            FROM time_slots
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY status
        ");
        
        return $stats;
    }
    
    /**
     * Get JSON input
     */
    private function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Check admin access
     */
    private function hasAdminAccess()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}