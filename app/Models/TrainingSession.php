<?php

namespace App\Models;

class TrainingSession extends BaseModel
{
    protected $table = 'training_sessions';
    protected $softDeletes = true;
    
    protected $fillable = [
        'session_date', 'day_of_week', 'morning_slot_start', 'morning_slot_end',
        'afternoon_slot_start', 'afternoon_slot_end', 'morning_activity',
        'afternoon_activity', 'venue_id', 'max_capacity', 'registered_teams',
        'status', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Status constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_FULL = 'full';
    const STATUS_CANCELLED = 'cancelled';
    
    // Day of week constants
    const DAYS = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ];
    
    // Time slot constants
    const SLOT_MORNING = 'morning';
    const SLOT_AFTERNOON = 'afternoon';
    const SLOT_BOTH = 'both';
    
    // Validation rules
    protected $rules = [
        'session_date' => 'required|date',
        'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        'max_capacity' => 'numeric|min:1',
        'registered_teams' => 'numeric|min:0',
        'status' => 'required|in:available,full,cancelled'
    ];
    
    protected $messages = [
        'session_date.required' => 'Session date is required.',
        'day_of_week.required' => 'Day of week is required.',
        'max_capacity.min' => 'Maximum capacity must be at least 1.',
        'registered_teams.min' => 'Registered teams count cannot be negative.'
    ];
    
    /**
     * Get venue relation
     */
    public function venue()
    {
        return $this->belongsTo('App\\Models\\Venue', 'venue_id');
    }
    
    /**
     * Get teams registered for this session
     */
    public function registeredTeams()
    {
        return $this->db->query("
            SELECT t.*, s.name as school_name, c.name as category_name,
                   tsr.slot_preference, tsr.registered_at
            FROM training_session_registrations tsr
            JOIN teams t ON tsr.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE tsr.training_session_id = ?
            AND t.deleted_at IS NULL
            ORDER BY tsr.registered_at
        ", [$this->id]);
    }
    
    /**
     * Check if session has available capacity
     */
    public function hasCapacity($slot = null)
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }
        
        if ($slot) {
            // Check specific slot capacity
            $slotCapacity = floor($this->max_capacity / 2);
            $slotRegistered = $this->getSlotRegistrations($slot);
            return $slotRegistered < $slotCapacity;
        }
        
        return $this->registered_teams < $this->max_capacity;
    }
    
    /**
     * Get remaining capacity
     */
    public function getRemainingCapacity($slot = null)
    {
        if ($slot) {
            $slotCapacity = floor($this->max_capacity / 2);
            $slotRegistered = $this->getSlotRegistrations($slot);
            return max(0, $slotCapacity - $slotRegistered);
        }
        
        return max(0, $this->max_capacity - $this->registered_teams);
    }
    
    /**
     * Get slot registrations count
     */
    private function getSlotRegistrations($slot)
    {
        $count = $this->db->query("
            SELECT COUNT(*) as count
            FROM training_session_registrations
            WHERE training_session_id = ? 
            AND (slot_preference = ? OR slot_preference = 'both')
        ", [$this->id, $slot]);
        
        return $count[0]['count'] ?? 0;
    }
    
    /**
     * Register team for training session
     */
    public function registerTeam($teamId, $slotPreference = self::SLOT_BOTH)
    {
        // Check if team is already registered
        $existing = $this->db->query("
            SELECT id FROM training_session_registrations
            WHERE training_session_id = ? AND team_id = ?
        ", [$this->id, $teamId]);
        
        if (!empty($existing)) {
            return ['success' => false, 'message' => 'Team is already registered for this session'];
        }
        
        // Check capacity
        if (!$this->hasCapacity($slotPreference)) {
            return ['success' => false, 'message' => 'Training session is at capacity'];
        }
        
        // Register team
        $this->db->query("
            INSERT INTO training_session_registrations 
            (training_session_id, team_id, slot_preference, registered_at)
            VALUES (?, ?, ?, NOW())
        ", [$this->id, $teamId, $slotPreference]);
        
        // Update registered count
        $this->registered_teams = $this->registered_teams + 1;
        
        // Update status if full
        if ($this->registered_teams >= $this->max_capacity) {
            $this->status = self::STATUS_FULL;
        }
        
        $this->updated_at = date('Y-m-d H:i:s');
        $this->save();
        
        return ['success' => true, 'message' => 'Team registered successfully'];
    }
    
    /**
     * Unregister team from training session
     */
    public function unregisterTeam($teamId)
    {
        // Check if team is registered
        $registration = $this->db->query("
            SELECT id FROM training_session_registrations
            WHERE training_session_id = ? AND team_id = ?
        ", [$this->id, $teamId]);
        
        if (empty($registration)) {
            return ['success' => false, 'message' => 'Team is not registered for this session'];
        }
        
        // Remove registration
        $this->db->query("
            DELETE FROM training_session_registrations
            WHERE training_session_id = ? AND team_id = ?
        ", [$this->id, $teamId]);
        
        // Update registered count
        $this->registered_teams = max(0, $this->registered_teams - 1);
        
        // Update status if no longer full
        if ($this->status === self::STATUS_FULL) {
            $this->status = self::STATUS_AVAILABLE;
        }
        
        $this->updated_at = date('Y-m-d H:i:s');
        $this->save();
        
        return ['success' => true, 'message' => 'Team unregistered successfully'];
    }
    
    /**
     * Get session timeline (morning and afternoon slots)
     */
    public function getTimeline()
    {
        $timeline = [];
        
        if ($this->morning_activity) {
            $timeline[] = [
                'slot' => 'morning',
                'activity' => $this->morning_activity,
                'start_time' => $this->morning_slot_start,
                'end_time' => $this->morning_slot_end,
                'duration' => $this->getSlotDuration('morning'),
                'registrations' => $this->getSlotRegistrations('morning')
            ];
        }
        
        if ($this->afternoon_activity) {
            $timeline[] = [
                'slot' => 'afternoon',
                'activity' => $this->afternoon_activity,
                'start_time' => $this->afternoon_slot_start,
                'end_time' => $this->afternoon_slot_end,
                'duration' => $this->getSlotDuration('afternoon'),
                'registrations' => $this->getSlotRegistrations('afternoon')
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Get slot duration in hours
     */
    public function getSlotDuration($slot)
    {
        if ($slot === 'morning') {
            $start = strtotime($this->morning_slot_start);
            $end = strtotime($this->morning_slot_end);
        } else {
            $start = strtotime($this->afternoon_slot_start);
            $end = strtotime($this->afternoon_slot_end);
        }
        
        return round(($end - $start) / 3600, 1);
    }
    
    /**
     * Check if session is today
     */
    public function isToday()
    {
        return $this->session_date === date('Y-m-d');
    }
    
    /**
     * Check if session is upcoming
     */
    public function isUpcoming()
    {
        return $this->session_date > date('Y-m-d') && $this->status !== self::STATUS_CANCELLED;
    }
    
    /**
     * Check if session is past
     */
    public function isPast()
    {
        return $this->session_date < date('Y-m-d');
    }
    
    /**
     * Get sessions by date range
     */
    public static function getInDateRange($startDate, $endDate)
    {
        return self::where('session_date', '>=', $startDate)
                  ->where('session_date', '<=', $endDate)
                  ->where('status', '!=', self::STATUS_CANCELLED)
                  ->orderBy('session_date')
                  ->get();
    }
    
    /**
     * Get upcoming sessions
     */
    public static function getUpcoming($limit = 10)
    {
        return self::where('session_date', '>=', date('Y-m-d'))
                  ->where('status', '!=', self::STATUS_CANCELLED)
                  ->orderBy('session_date')
                  ->limit($limit)
                  ->get();
    }
    
    /**
     * Generate training schedule based on PDF schedule
     */
    public static function generateSchedule($startDate, $endDate, $venueId = null)
    {
        // Training schedule from PDF
        $scheduleTemplate = [
            'Saturday' => [
                'morning' => 'Training Session - Beginner',
                'afternoon' => 'Training Session - Advanced'
            ],
            'Wednesday' => [
                'morning' => 'Special Training Workshop',
                'afternoon' => 'Mentorship Session'
            ],
            'Thursday' => [
                'morning' => 'Technical Skills Training',
                'afternoon' => 'Team Building & Strategy'
            ]
        ];
        
        $sessions = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $dayOfWeek = date('l', $current);
            $sessionDate = date('Y-m-d', $current);
            
            if (isset($scheduleTemplate[$dayOfWeek])) {
                $activities = $scheduleTemplate[$dayOfWeek];
                
                $session = new self();
                $session->session_date = $sessionDate;
                $session->day_of_week = $dayOfWeek;
                $session->morning_activity = $activities['morning'] ?? null;
                $session->afternoon_activity = $activities['afternoon'] ?? null;
                $session->venue_id = $venueId;
                $session->max_capacity = 50;
                $session->registered_teams = 0;
                $session->status = self::STATUS_AVAILABLE;
                
                if ($session->save()) {
                    $sessions[] = $session->id;
                }
            }
            
            $current = strtotime('+1 day', $current);
        }
        
        return [
            'success' => true,
            'message' => 'Generated ' . count($sessions) . ' training sessions',
            'session_ids' => $sessions
        ];
    }
    
    /**
     * Get available slot options
     */
    public static function getSlotOptions()
    {
        return [
            self::SLOT_MORNING => 'Morning Only',
            self::SLOT_AFTERNOON => 'Afternoon Only',
            self::SLOT_BOTH => 'Both Sessions'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_FULL => 'Full',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['has_capacity'] = $this->hasCapacity();
        $attributes['remaining_capacity'] = $this->getRemainingCapacity();
        $attributes['is_today'] = $this->isToday();
        $attributes['is_upcoming'] = $this->isUpcoming();
        $attributes['is_past'] = $this->isPast();
        $attributes['timeline'] = $this->getTimeline();
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}