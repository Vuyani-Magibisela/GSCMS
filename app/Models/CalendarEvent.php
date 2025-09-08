<?php

namespace App\Models;

class CalendarEvent extends BaseModel
{
    protected $table = 'calendar_events';
    protected $softDeletes = true;
    
    protected $fillable = [
        'phase_id', 'event_type', 'event_name', 'event_description', 'start_datetime',
        'end_datetime', 'venue_id', 'category_id', 'district_id', 'recurrence_rule',
        'color_code', 'is_mandatory', 'max_participants', 'current_participants',
        'status', 'created_by'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Event type constants
    const TYPE_TRAINING = 'training';
    const TYPE_COMPETITION = 'competition';
    const TYPE_MEETING = 'meeting';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_ANNOUNCEMENT = 'announcement';
    
    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    // Validation rules
    protected $rules = [
        'phase_id' => 'required|numeric',
        'event_type' => 'required|in:training,competition,meeting,deadline,announcement',
        'event_name' => 'required|max:200',
        'start_datetime' => 'required|datetime',
        'end_datetime' => 'required|datetime|after:start_datetime',
        'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        'created_by' => 'required|numeric'
    ];
    
    protected $messages = [
        'phase_id.required' => 'Competition phase is required.',
        'event_type.required' => 'Event type is required.',
        'event_name.required' => 'Event name is required.',
        'start_datetime.required' => 'Start date and time is required.',
        'end_datetime.required' => 'End date and time is required.',
        'end_datetime.after' => 'End time must be after start time.'
    ];
    
    /**
     * Get competition phase relation
     */
    public function phase()
    {
        return $this->belongsTo('App\\Models\\CompetitionPhase', 'phase_id');
    }
    
    /**
     * Get venue relation
     */
    public function venue()
    {
        return $this->belongsTo('App\\Models\\Venue', 'venue_id');
    }
    
    /**
     * Get category relation
     */
    public function category()
    {
        return $this->belongsTo('App\\Models\\Category', 'category_id');
    }
    
    /**
     * Get creator relation
     */
    public function creator()
    {
        return $this->belongsTo('App\\Models\\User', 'created_by');
    }
    
    /**
     * Get time slots for this event
     */
    public function timeSlots()
    {
        return $this->hasMany('App\\Models\\TimeSlot', 'event_id', 'id');
    }
    
    /**
     * Get duration in minutes
     */
    public function getDurationMinutes()
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return 0;
        }
        
        $start = strtotime($this->start_datetime);
        $end = strtotime($this->end_datetime);
        
        return floor(($end - $start) / 60);
    }
    
    /**
     * Check if event is active now
     */
    public function isActiveNow()
    {
        $now = time();
        $start = strtotime($this->start_datetime);
        $end = strtotime($this->end_datetime);
        
        return $this->status === self::STATUS_IN_PROGRESS && 
               $now >= $start && $now <= $end;
    }
    
    /**
     * Check if event is upcoming
     */
    public function isUpcoming()
    {
        $now = time();
        $start = strtotime($this->start_datetime);
        
        return $this->status === self::STATUS_SCHEDULED && $now < $start;
    }
    
    /**
     * Check if event is past
     */
    public function isPast()
    {
        $now = time();
        $end = strtotime($this->end_datetime);
        
        return $end < $now || $this->status === self::STATUS_COMPLETED;
    }
    
    /**
     * Check if event is full
     */
    public function isFull()
    {
        if (!$this->max_participants) {
            return false;
        }
        
        return $this->current_participants >= $this->max_participants;
    }
    
    /**
     * Get available spots
     */
    public function getAvailableSpots()
    {
        if (!$this->max_participants) {
            return null;
        }
        
        return $this->max_participants - $this->current_participants;
    }
    
    /**
     * Register participant for event
     */
    public function registerParticipant($participantId, $participantType = 'team')
    {
        if ($this->isFull()) {
            return ['success' => false, 'message' => 'Event is full'];
        }
        
        // Check if already registered
        $existing = $this->db->query("
            SELECT id FROM event_participants 
            WHERE event_id = ? AND participant_id = ? AND participant_type = ?
        ", [$this->id, $participantId, $participantType]);
        
        if (!empty($existing)) {
            return ['success' => false, 'message' => 'Already registered for this event'];
        }
        
        // Register participant
        $this->db->query("
            INSERT INTO event_participants (event_id, participant_id, participant_type, registered_at)
            VALUES (?, ?, ?, NOW())
        ", [$this->id, $participantId, $participantType]);
        
        // Update participant count
        $this->update(['current_participants' => $this->current_participants + 1]);
        
        return ['success' => true, 'message' => 'Successfully registered for event'];
    }
    
    /**
     * Get events by type
     */
    public static function getByType($eventType, $limit = null)
    {
        $query = self::where('event_type', $eventType)
                    ->where('status', '!=', self::STATUS_CANCELLED)
                    ->orderBy('start_datetime');
                    
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    /**
     * Get upcoming events
     */
    public static function getUpcoming($limit = 10)
    {
        return self::where('start_datetime', '>', date('Y-m-d H:i:s'))
                  ->where('status', self::STATUS_SCHEDULED)
                  ->orderBy('start_datetime')
                  ->limit($limit)
                  ->get();
    }
    
    /**
     * Get events in date range
     */
    public static function getInDateRange($startDate, $endDate)
    {
        return self::where('start_datetime', '>=', $startDate)
                  ->where('end_datetime', '<=', $endDate)
                  ->where('status', '!=', self::STATUS_CANCELLED)
                  ->orderBy('start_datetime')
                  ->get();
    }
    
    /**
     * Get events by phase
     */
    public static function getByPhase($phaseId)
    {
        return self::where('phase_id', $phaseId)
                  ->where('status', '!=', self::STATUS_CANCELLED)
                  ->orderBy('start_datetime')
                  ->get();
    }
    
    /**
     * Get available event types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_TRAINING => 'Training Session',
            self::TYPE_COMPETITION => 'Competition Event',
            self::TYPE_MEETING => 'Meeting',
            self::TYPE_DEADLINE => 'Deadline',
            self::TYPE_ANNOUNCEMENT => 'Announcement'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }
    
    /**
     * Get color code based on event type
     */
    public function getTypeColor()
    {
        if ($this->color_code) {
            return $this->color_code;
        }
        
        $colors = [
            self::TYPE_TRAINING => '#28a745',      // Green
            self::TYPE_COMPETITION => '#dc3545',   // Red
            self::TYPE_MEETING => '#ffc107',       // Yellow
            self::TYPE_DEADLINE => '#6f42c1',      // Purple
            self::TYPE_ANNOUNCEMENT => '#17a2b8'   // Cyan
        ];
        
        return $colors[$this->event_type] ?? '#007bff';
    }
    
    /**
     * Generate calendar event data
     */
    public function toCalendarEvent()
    {
        return [
            'id' => $this->id,
            'title' => $this->event_name,
            'start' => $this->start_datetime,
            'end' => $this->end_datetime,
            'color' => $this->getTypeColor(),
            'description' => $this->event_description,
            'type' => $this->event_type,
            'venue' => $this->venue ? $this->venue->name : null,
            'category' => $this->category ? $this->category->name : null,
            'status' => $this->status,
            'participants' => "{$this->current_participants}/{$this->max_participants}",
            'is_mandatory' => $this->is_mandatory,
            'extendedProps' => [
                'event_type' => $this->event_type,
                'status' => $this->status,
                'venue_id' => $this->venue_id,
                'category_id' => $this->category_id,
                'current_participants' => $this->current_participants,
                'max_participants' => $this->max_participants,
                'is_full' => $this->isFull()
            ]
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['duration_minutes'] = $this->getDurationMinutes();
        $attributes['is_active_now'] = $this->isActiveNow();
        $attributes['is_upcoming'] = $this->isUpcoming();
        $attributes['is_past'] = $this->isPast();
        $attributes['is_full'] = $this->isFull();
        $attributes['available_spots'] = $this->getAvailableSpots();
        $attributes['type_color'] = $this->getTypeColor();
        $attributes['type_label'] = self::getAvailableTypes()[$this->event_type] ?? $this->event_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}