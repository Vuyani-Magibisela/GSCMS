<?php

namespace App\Models;

class TimeSlot extends BaseModel
{
    protected $table = 'time_slots';
    protected $softDeletes = true;
    
    protected $fillable = [
        'event_id', 'venue_id', 'slot_date', 'start_time', 'end_time', 'slot_type',
        'category_id', 'team_id', 'judge_panel_id', 'table_number', 'duration_minutes',
        'buffer_minutes', 'status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Slot type constants
    const TYPE_COMPETITION = 'competition';
    const TYPE_PRACTICE = 'practice';
    const TYPE_JUDGING = 'judging';
    const TYPE_BREAK = 'break';
    const TYPE_SETUP = 'setup';
    
    // Status constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_RESERVED = 'reserved';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    
    // Validation rules
    protected $rules = [
        'event_id' => 'required|numeric',
        'venue_id' => 'required|numeric',
        'slot_date' => 'required|date',
        'start_time' => 'required',
        'end_time' => 'required',
        'slot_type' => 'required|in:competition,practice,judging,break,setup',
        'duration_minutes' => 'required|numeric|min:1',
        'status' => 'required|in:available,reserved,confirmed,completed'
    ];
    
    protected $messages = [
        'event_id.required' => 'Event is required.',
        'venue_id.required' => 'Venue is required.',
        'slot_date.required' => 'Slot date is required.',
        'start_time.required' => 'Start time is required.',
        'end_time.required' => 'End time is required.',
        'slot_type.required' => 'Slot type is required.',
        'duration_minutes.required' => 'Duration is required.',
        'duration_minutes.min' => 'Duration must be at least 1 minute.'
    ];
    
    /**
     * Get event relation
     */
    public function event()
    {
        return $this->belongsTo('App\\Models\\CalendarEvent', 'event_id');
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
     * Get team relation
     */
    public function team()
    {
        return $this->belongsTo('App\\Models\\Team', 'team_id');
    }
    
    /**
     * Get full datetime for start
     */
    public function getStartDateTime()
    {
        return $this->slot_date . ' ' . $this->start_time;
    }
    
    /**
     * Get full datetime for end
     */
    public function getEndDateTime()
    {
        return $this->slot_date . ' ' . $this->end_time;
    }
    
    /**
     * Check if slot is currently active
     */
    public function isActiveNow()
    {
        $now = time();
        $start = strtotime($this->getStartDateTime());
        $end = strtotime($this->getEndDateTime());
        
        return $now >= $start && $now <= $end && 
               in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_RESERVED]);
    }
    
    /**
     * Check if slot is available for booking
     */
    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
    
    /**
     * Check if slot can be modified
     */
    public function isModifiable()
    {
        return in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_RESERVED]);
    }
    
    /**
     * Reserve slot for team
     */
    public function reserve($teamId, $categoryId = null)
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'message' => 'Time slot is not available'];
        }
        
        // Check for conflicts
        $conflicts = $this->checkConflicts($teamId);
        if (!empty($conflicts)) {
            return ['success' => false, 'message' => 'Scheduling conflict detected', 'conflicts' => $conflicts];
        }
        
        $this->team_id = $teamId;
        $this->category_id = $categoryId;
        $this->status = self::STATUS_RESERVED;
        $this->updated_at = date('Y-m-d H:i:s');
        
        if ($this->save()) {
            return ['success' => true, 'message' => 'Time slot reserved successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to reserve time slot'];
    }
    
    /**
     * Confirm reserved slot
     */
    public function confirm()
    {
        if ($this->status !== self::STATUS_RESERVED) {
            return ['success' => false, 'message' => 'Only reserved slots can be confirmed'];
        }
        
        $this->status = self::STATUS_CONFIRMED;
        $this->updated_at = date('Y-m-d H:i:s');
        
        if ($this->save()) {
            return ['success' => true, 'message' => 'Time slot confirmed successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to confirm time slot'];
    }
    
    /**
     * Release slot
     */
    public function release()
    {
        if (!in_array($this->status, [self::STATUS_RESERVED, self::STATUS_CONFIRMED])) {
            return ['success' => false, 'message' => 'Slot cannot be released'];
        }
        
        $this->team_id = null;
        $this->category_id = null;
        $this->judge_panel_id = null;
        $this->status = self::STATUS_AVAILABLE;
        $this->updated_at = date('Y-m-d H:i:s');
        
        if ($this->save()) {
            return ['success' => true, 'message' => 'Time slot released successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to release time slot'];
    }
    
    /**
     * Check for scheduling conflicts
     */
    public function checkConflicts($teamId)
    {
        $conflicts = [];
        
        // Check for team double booking
        $teamConflicts = $this->db->query("
            SELECT ts.*, ce.event_name, v.name as venue_name
            FROM time_slots ts
            JOIN calendar_events ce ON ts.event_id = ce.id
            JOIN venues v ON ts.venue_id = v.id
            WHERE ts.team_id = ? 
            AND ts.slot_date = ? 
            AND ts.id != ?
            AND ((ts.start_time <= ? AND ts.end_time > ?) 
                 OR (ts.start_time < ? AND ts.end_time >= ?))
            AND ts.status IN ('reserved', 'confirmed')
        ", [
            $teamId, $this->slot_date, $this->id,
            $this->start_time, $this->start_time,
            $this->end_time, $this->end_time
        ]);
        
        if (!empty($teamConflicts)) {
            foreach ($teamConflicts as $conflict) {
                $conflicts[] = [
                    'type' => 'team_double_booking',
                    'message' => "Team is already scheduled at {$conflict['venue_name']} from {$conflict['start_time']} to {$conflict['end_time']}"
                ];
            }
        }
        
        // Check for venue table conflicts
        if ($this->table_number) {
            $tableConflicts = $this->db->query("
                SELECT ts.*, t.name as team_name
                FROM time_slots ts
                LEFT JOIN teams t ON ts.team_id = t.id
                WHERE ts.venue_id = ? 
                AND ts.slot_date = ? 
                AND ts.table_number = ?
                AND ts.id != ?
                AND ((ts.start_time <= ? AND ts.end_time > ?) 
                     OR (ts.start_time < ? AND ts.end_time >= ?))
                AND ts.status IN ('reserved', 'confirmed')
            ", [
                $this->venue_id, $this->slot_date, $this->table_number, $this->id,
                $this->start_time, $this->start_time,
                $this->end_time, $this->end_time
            ]);
            
            if (!empty($tableConflicts)) {
                foreach ($tableConflicts as $conflict) {
                    $conflicts[] = [
                        'type' => 'table_conflict',
                        'message' => "Table {$this->table_number} is already booked by {$conflict['team_name']}"
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Get available slots for date/venue
     */
    public static function getAvailable($venueId, $date, $categoryId = null)
    {
        $query = self::where('venue_id', $venueId)
                    ->where('slot_date', $date)
                    ->where('status', self::STATUS_AVAILABLE);
                    
        if ($categoryId) {
            $query->where(function($q) use ($categoryId) {
                $q->whereNull('category_id')
                  ->orWhere('category_id', $categoryId);
            });
        }
        
        return $query->orderBy('start_time')->get();
    }
    
    /**
     * Get slots for team
     */
    public static function getForTeam($teamId)
    {
        return self::where('team_id', $teamId)
                  ->where('status', '!=', self::STATUS_AVAILABLE)
                  ->orderBy('slot_date')
                  ->orderBy('start_time')
                  ->get();
    }
    
    /**
     * Get slots for event
     */
    public static function getForEvent($eventId)
    {
        return self::where('event_id', $eventId)
                  ->orderBy('slot_date')
                  ->orderBy('start_time')
                  ->orderBy('table_number')
                  ->get();
    }
    
    /**
     * Generate time slots for event
     */
    public static function generateSlotsForEvent($eventId, $venueId, $config = [])
    {
        $event = (new CalendarEvent())->find($eventId);
        if (!$event) {
            return ['success' => false, 'message' => 'Event not found'];
        }
        
        $defaults = [
            'slot_duration' => 30,  // minutes
            'buffer_time' => 15,    // minutes
            'tables_count' => 1,
            'start_time' => '09:00:00',
            'end_time' => '16:00:00'
        ];
        
        $config = array_merge($defaults, $config);
        
        $slots = [];
        $eventDate = date('Y-m-d', strtotime($event->start_datetime));
        
        // Generate time slots
        $currentTime = strtotime($eventDate . ' ' . $config['start_time']);
        $endTime = strtotime($eventDate . ' ' . $config['end_time']);
        
        while ($currentTime < $endTime) {
            $slotEndTime = $currentTime + ($config['slot_duration'] * 60);
            
            if ($slotEndTime <= $endTime) {
                for ($table = 1; $table <= $config['tables_count']; $table++) {
                    $timeSlot = new self();
                    $timeSlot->event_id = $eventId;
                    $timeSlot->venue_id = $venueId;
                    $timeSlot->slot_date = $eventDate;
                    $timeSlot->start_time = date('H:i:s', $currentTime);
                    $timeSlot->end_time = date('H:i:s', $slotEndTime);
                    $timeSlot->slot_type = self::TYPE_COMPETITION;
                    $timeSlot->table_number = "T{$table}";
                    $timeSlot->duration_minutes = $config['slot_duration'];
                    $timeSlot->buffer_minutes = $config['buffer_time'];
                    $timeSlot->status = self::STATUS_AVAILABLE;
                    
                    if ($timeSlot->save()) {
                        $slots[] = $timeSlot->id;
                    }
                }
            }
            
            $currentTime = $slotEndTime + ($config['buffer_time'] * 60);
        }
        
        return [
            'success' => true, 
            'message' => 'Generated ' . count($slots) . ' time slots',
            'slot_ids' => $slots
        ];
    }
    
    /**
     * Get available slot types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_COMPETITION => 'Competition',
            self::TYPE_PRACTICE => 'Practice',
            self::TYPE_JUDGING => 'Judging',
            self::TYPE_BREAK => 'Break',
            self::TYPE_SETUP => 'Setup'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['start_datetime'] = $this->getStartDateTime();
        $attributes['end_datetime'] = $this->getEndDateTime();
        $attributes['is_active_now'] = $this->isActiveNow();
        $attributes['is_available'] = $this->isAvailable();
        $attributes['is_modifiable'] = $this->isModifiable();
        $attributes['type_label'] = self::getAvailableTypes()[$this->slot_type] ?? $this->slot_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}