<?php
// app/Models/Venue.php

namespace App\Models;

class Venue extends BaseModel
{
    protected $table = 'venues';
    protected $softDeletes = true;
    
    protected $fillable = [
        'venue_name', 'venue_type', 'district_id', 'address', 'gps_coordinates',
        'contact_person', 'contact_phone', 'contact_email', 'emergency_contact',
        'total_capacity', 'parking_spaces', 'accessibility_features', 'facilities',
        'operating_hours', 'cost_per_day', 'status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    // Validation rules
    protected $rules = [
        'venue_name' => 'required|max:200',
        'venue_type' => 'required',
        'address' => 'required',
        'contact_person' => 'required|max:100',
        'contact_phone' => 'required|max:20',
        'contact_email' => 'required|email|max:100',
        'total_capacity' => 'required|integer|min:1'
    ];
    
    protected $messages = [
        'venue_name.required' => 'Venue name is required.',
        'venue_type.required' => 'Venue type is required.',
        'address.required' => 'Address is required.',
        'contact_person.required' => 'Contact person is required.',
        'contact_phone.required' => 'Contact phone is required.',
        'contact_email.required' => 'Contact email is required.',
        'contact_email.email' => 'Contact email must be valid.',
        'total_capacity.required' => 'Total capacity is required.',
        'total_capacity.integer' => 'Total capacity must be a number.',
        'total_capacity.min' => 'Total capacity must be at least 1.'
    ];
    
    // Venue type constants
    const TYPE_MAIN = 'main';
    const TYPE_DISTRICT = 'district';
    const TYPE_SCHOOL = 'school';
    const TYPE_TRAINING = 'training';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';

    protected $hasMany = [
        'spaces' => ['model' => VenueSpace::class, 'foreign_key' => 'venue_id'],
        'bookings' => ['model' => VenueBooking::class, 'foreign_key' => 'venue_id'],
        'competitions' => ['model' => Competition::class, 'foreign_key' => 'venue_id']
    ];

    /**
     * Get venue spaces
     */
    public function spaces()
    {
        return $this->hasMany('App\Models\VenueSpace', 'venue_id', 'id');
    }
    
    /**
     * Get venue bookings
     */
    public function bookings()
    {
        return $this->hasMany('App\Models\VenueBooking', 'venue_id', 'id');
    }
    
    /**
     * Get competitions at this venue
     */
    public function competitions()
    {
        return $this->hasMany('App\Models\Competition', 'venue_id', 'id');
    }
    
    /**
     * Get current occupancy for venue
     */
    public function getCurrentOccupancy()
    {
        $currentBookings = $this->db->query("
            SELECT SUM(vb.expected_attendance) as total_attendance
            FROM venue_bookings vb
            WHERE vb.venue_id = ?
            AND vb.booking_date = CURDATE()
            AND vb.booking_status = 'confirmed'
            AND CURTIME() BETWEEN vb.start_time AND vb.end_time
        ", [$this->id]);
        
        return $currentBookings[0]['total_attendance'] ?? 0;
    }
    
    /**
     * Get occupancy percentage
     */
    public function getOccupancyPercentage()
    {
        $currentOccupancy = $this->getCurrentOccupancy();
        return $this->total_capacity > 0 ? round(($currentOccupancy / $this->total_capacity) * 100, 2) : 0;
    }
    
    /**
     * Check if venue is at capacity
     */
    public function isAtCapacity()
    {
        return $this->getCurrentOccupancy() >= $this->total_capacity;
    }
    
    /**
     * Check if venue is over capacity
     */
    public function isOverCapacity()
    {
        return $this->getCurrentOccupancy() > $this->total_capacity;
    }
    
    /**
     * Get available capacity
     */
    public function getAvailableCapacity()
    {
        return max(0, $this->total_capacity - $this->getCurrentOccupancy());
    }
    
    /**
     * Get capacity status
     */
    public function getCapacityStatus()
    {
        $percentage = $this->getOccupancyPercentage();
        
        if ($percentage >= 100) {
            return 'at_capacity';
        } elseif ($percentage >= 90) {
            return 'approaching_capacity';
        } elseif ($percentage >= 75) {
            return 'warning';
        } else {
            return 'normal';
        }
    }
    
    /**
     * Get venue statistics
     */
    public function getStatistics()
    {
        $currentOccupancy = $this->getCurrentOccupancy();
        $spacesCount = $this->db->query("
            SELECT COUNT(*) as count FROM venue_spaces WHERE venue_id = ?
        ", [$this->id])[0]['count'] ?? 0;
        
        $todayBookings = $this->db->query("
            SELECT COUNT(*) as count FROM venue_bookings 
            WHERE venue_id = ? AND booking_date = CURDATE()
        ", [$this->id])[0]['count'] ?? 0;
        
        return [
            'current_occupancy' => $currentOccupancy,
            'total_capacity' => $this->total_capacity,
            'available_capacity' => $this->getAvailableCapacity(),
            'occupancy_percentage' => $this->getOccupancyPercentage(),
            'capacity_status' => $this->getCapacityStatus(),
            'total_spaces' => $spacesCount,
            'today_bookings' => $todayBookings
        ];
    }
    
    /**
     * Get available venue types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_MAIN => 'Main Venue',
            self::TYPE_DISTRICT => 'District Venue',
            self::TYPE_SCHOOL => 'School Venue',
            self::TYPE_TRAINING => 'Training Facility'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_MAINTENANCE => 'Maintenance'
        ];
    }
    
    /**
     * Scope: Active venues
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: By type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('venue_type', $type);
    }
    
    /**
     * Get accessibility features as array
     */
    public function getAccessibilityFeatures()
    {
        if (!$this->accessibility_features) {
            return [];
        }
        
        return json_decode($this->accessibility_features, true) ?? [];
    }
    
    /**
     * Get facilities as array
     */
    public function getFacilities()
    {
        if (!$this->facilities) {
            return [];
        }
        
        return json_decode($this->facilities, true) ?? [];
    }
    
    /**
     * Get operating hours as array
     */
    public function getOperatingHours()
    {
        if (!$this->operating_hours) {
            return [];
        }
        
        return json_decode($this->operating_hours, true) ?? [];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['current_occupancy'] = $this->getCurrentOccupancy();
        $attributes['occupancy_percentage'] = $this->getOccupancyPercentage();
        $attributes['available_capacity'] = $this->getAvailableCapacity();
        $attributes['capacity_status'] = $this->getCapacityStatus();
        $attributes['is_at_capacity'] = $this->isAtCapacity();
        $attributes['is_over_capacity'] = $this->isOverCapacity();
        $attributes['statistics'] = $this->getStatistics();
        $attributes['accessibility_features_parsed'] = $this->getAccessibilityFeatures();
        $attributes['facilities_parsed'] = $this->getFacilities();
        $attributes['operating_hours_parsed'] = $this->getOperatingHours();
        $attributes['type_label'] = self::getAvailableTypes()[$this->venue_type] ?? $this->venue_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}