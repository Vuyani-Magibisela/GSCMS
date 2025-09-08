<?php
// app/Models/VenueSpace.php

namespace App\Models;

class VenueSpace extends BaseModel
{
    protected $table = 'venue_spaces';
    
    protected $fillable = [
        'venue_id', 'space_name', 'space_type', 'floor_level', 
        'capacity_seated', 'capacity_standing', 'area_sqm', 'competition_tables',
        'has_av_equipment', 'has_aircon', 'has_wifi', 'power_outlets',
        'amenities', 'hourly_rate', 'daily_rate', 'setup_time_minutes',
        'breakdown_time_minutes', 'status', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at'];
    
    // Validation rules
    protected $rules = [
        'venue_id' => 'required|integer',
        'space_name' => 'required|max:100',
        'space_type' => 'required',
        'capacity_seated' => 'required|integer|min:1',
        'setup_time_minutes' => 'integer|min:0',
        'breakdown_time_minutes' => 'integer|min:0'
    ];
    
    // Space type constants
    const TYPE_COMPETITION_HALL = 'competition_hall';
    const TYPE_JUDGING_ROOM = 'judging_room';
    const TYPE_CATERING_AREA = 'catering_area';
    const TYPE_BOARDROOM = 'boardroom';
    const TYPE_CLASSROOM = 'classroom';
    const TYPE_FOYER = 'foyer';
    const TYPE_OUTDOOR = 'outdoor';
    const TYPE_STORAGE = 'storage';
    
    // Status constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_BOOKED = 'booked';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_SETUP = 'setup';

    protected $belongsTo = [
        'venue' => ['model' => Venue::class, 'foreign_key' => 'venue_id']
    ];

    protected $hasMany = [
        'bookings' => ['model' => VenueBooking::class, 'foreign_key' => 'space_id']
    ];

    /**
     * Get parent venue
     */
    public function venue()
    {
        return $this->belongsTo('App\Models\Venue', 'venue_id');
    }
    
    /**
     * Get space bookings
     */
    public function bookings()
    {
        return $this->hasMany('App\Models\VenueBooking', 'space_id', 'id');
    }
    
    /**
     * Check if space is available at given time
     */
    public function isAvailableAt($date, $startTime, $endTime, $excludeBookingId = null)
    {
        $query = "
            SELECT COUNT(*) as count 
            FROM venue_bookings 
            WHERE space_id = ? 
            AND booking_date = ?
            AND booking_status != 'cancelled'
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND start_time < ?)
            )
        ";
        
        $params = [$this->id, $date, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime];
        
        if ($excludeBookingId) {
            $query .= " AND id != ?";
            $params[] = $excludeBookingId;
        }
        
        $result = $this->db->query($query, $params);
        return $result[0]['count'] == 0;
    }
    
    /**
     * Get current booking
     */
    public function getCurrentBooking()
    {
        $result = $this->db->query("
            SELECT * FROM venue_bookings 
            WHERE space_id = ? 
            AND booking_date = CURDATE()
            AND booking_status = 'confirmed'
            AND CURTIME() BETWEEN start_time AND end_time
            LIMIT 1
        ", [$this->id]);
        
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Get next booking
     */
    public function getNextBooking()
    {
        $result = $this->db->query("
            SELECT * FROM venue_bookings 
            WHERE space_id = ? 
            AND (
                (booking_date = CURDATE() AND start_time > CURTIME()) OR
                booking_date > CURDATE()
            )
            AND booking_status = 'confirmed'
            ORDER BY booking_date ASC, start_time ASC
            LIMIT 1
        ", [$this->id]);
        
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Calculate effective capacity
     */
    public function getEffectiveCapacity()
    {
        return $this->capacity_standing ?: $this->capacity_seated;
    }
    
    /**
     * Get space availability status
     */
    public function getAvailabilityStatus()
    {
        if ($this->status !== self::STATUS_AVAILABLE) {
            return $this->status;
        }
        
        $currentBooking = $this->getCurrentBooking();
        if ($currentBooking) {
            return 'occupied';
        }
        
        return 'available';
    }
    
    /**
     * Get today's bookings
     */
    public function getTodayBookings()
    {
        return $this->db->query("
            SELECT * FROM venue_bookings 
            WHERE space_id = ? 
            AND booking_date = CURDATE()
            AND booking_status != 'cancelled'
            ORDER BY start_time ASC
        ", [$this->id]);
    }
    
    /**
     * Get available space types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_COMPETITION_HALL => 'Competition Hall',
            self::TYPE_JUDGING_ROOM => 'Judging Room',
            self::TYPE_CATERING_AREA => 'Catering Area',
            self::TYPE_BOARDROOM => 'Boardroom',
            self::TYPE_CLASSROOM => 'Classroom',
            self::TYPE_FOYER => 'Foyer',
            self::TYPE_OUTDOOR => 'Outdoor Area',
            self::TYPE_STORAGE => 'Storage'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_BOOKED => 'Booked',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_SETUP => 'Setup'
        ];
    }
    
    /**
     * Get amenities as array
     */
    public function getAmenities()
    {
        if (!$this->amenities) {
            return [];
        }
        
        return json_decode($this->amenities, true) ?? [];
    }
    
    /**
     * Get space statistics
     */
    public function getStatistics()
    {
        $currentBooking = $this->getCurrentBooking();
        $nextBooking = $this->getNextBooking();
        $todayBookings = count($this->getTodayBookings());
        
        return [
            'effective_capacity' => $this->getEffectiveCapacity(),
            'availability_status' => $this->getAvailabilityStatus(),
            'current_booking' => $currentBooking,
            'next_booking' => $nextBooking,
            'today_bookings_count' => $todayBookings,
            'utilization_today' => $this->calculateUtilizationToday()
        ];
    }
    
    /**
     * Calculate today's utilization percentage
     */
    private function calculateUtilizationToday()
    {
        $bookings = $this->getTodayBookings();
        $totalBookedMinutes = 0;
        
        foreach ($bookings as $booking) {
            $start = new \DateTime($booking['start_time']);
            $end = new \DateTime($booking['end_time']);
            $totalBookedMinutes += $start->diff($end)->i + ($start->diff($end)->h * 60);
        }
        
        $totalMinutesInDay = 24 * 60; // Assuming 24-hour availability
        return $totalMinutesInDay > 0 ? round(($totalBookedMinutes / $totalMinutesInDay) * 100, 2) : 0;
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['effective_capacity'] = $this->getEffectiveCapacity();
        $attributes['availability_status'] = $this->getAvailabilityStatus();
        $attributes['amenities_parsed'] = $this->getAmenities();
        $attributes['statistics'] = $this->getStatistics();
        $attributes['type_label'] = self::getAvailableTypes()[$this->space_type] ?? $this->space_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}