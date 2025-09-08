<?php
// app/Services/CapacityMonitor.php

namespace App\Services;

use App\Models\Venue;
use App\Models\VenueSpace;
use App\Models\VenueBooking;
use App\Core\Database;

class CapacityMonitor
{
    private $db;
    private $alertThresholds = [
        'warning' => 75,  // 75% capacity
        'critical' => 90, // 90% capacity
        'full' => 100     // 100% capacity
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Track occupancy for a venue or specific space
     */
    public function trackOccupancy($venueId, $spaceId = null, $eventId = null)
    {
        $booking = $this->getCurrentBooking($venueId, $spaceId);
        
        if (!$booking && !$eventId) {
            return ['status' => 'no_event', 'occupancy' => 0];
        }
        
        $capacity = $this->getSpaceCapacity($spaceId ?: $venueId);
        $currentOccupancy = $this->calculateCurrentOccupancy($booking);
        
        $tracking = [
            'venue_id' => $venueId,
            'space_id' => $spaceId,
            'event_id' => $eventId ?: ($booking['event_id'] ?? null),
            'current_occupancy' => $currentOccupancy,
            'max_capacity' => $capacity,
            'occupancy_percentage' => $capacity > 0 ? round(($currentOccupancy / $capacity) * 100, 2) : 0,
            'status' => $this->determineCapacityStatus($currentOccupancy, $capacity),
            'alert_triggered' => false,
            'alert_level' => 'none',
            'tracking_method' => 'manual'
        ];
        
        $this->saveTracking($tracking);
        $this->checkAlerts($tracking);
        
        return $this->getOccupancyStatus($tracking);
    }
    
    /**
     * Get current booking for venue/space
     */
    private function getCurrentBooking($venueId, $spaceId = null)
    {
        $query = "
            SELECT vb.*, ce.title as event_title
            FROM venue_bookings vb
            LEFT JOIN calendar_events ce ON vb.event_id = ce.id
            WHERE vb.venue_id = ?
            AND vb.booking_date = CURDATE()
            AND vb.booking_status = 'confirmed'
            AND CURTIME() BETWEEN vb.start_time AND vb.end_time
        ";
        
        $params = [$venueId];
        
        if ($spaceId) {
            $query .= " AND vb.space_id = ?";
            $params[] = $spaceId;
        }
        
        $query .= " LIMIT 1";
        
        $result = $this->db->prepare($query);
        $result->execute($params);
        
        return $result->fetch() ?: null;
    }
    
    /**
     * Get capacity for space or venue
     */
    private function getSpaceCapacity($id)
    {
        // Try to get space capacity first
        $spaceQuery = "SELECT capacity_seated, capacity_standing FROM venue_spaces WHERE id = ?";
        $spaceResult = $this->db->prepare($spaceQuery);
        $spaceResult->execute([$id]);
        $space = $spaceResult->fetch();
        
        if ($space) {
            return $space['capacity_standing'] ?: $space['capacity_seated'];
        }
        
        // Fall back to venue capacity
        $venueQuery = "SELECT total_capacity FROM venues WHERE id = ?";
        $venueResult = $this->db->prepare($venueQuery);
        $venueResult->execute([$id]);
        $venue = $venueResult->fetch();
        
        return $venue ? $venue['total_capacity'] : 0;
    }
    
    /**
     * Calculate current occupancy from booking data
     */
    private function calculateCurrentOccupancy($booking)
    {
        if (!$booking) {
            return 0;
        }
        
        // Use actual attendance if available, otherwise expected
        return $booking['actual_attendance'] ?: $booking['expected_attendance'];
    }
    
    /**
     * Determine capacity status based on occupancy
     */
    private function determineCapacityStatus($occupancy, $capacity)
    {
        if ($occupancy > $capacity) {
            return 'over_capacity';
        } elseif ($occupancy >= $capacity) {
            return 'at_capacity';
        }
        
        $percentage = $capacity > 0 ? ($occupancy / $capacity) * 100 : 0;
        
        if ($percentage >= $this->alertThresholds['critical']) {
            return 'approaching_capacity';
        }
        
        return 'normal';
    }
    
    /**
     * Save tracking data to database
     */
    private function saveTracking($tracking)
    {
        $query = "
            INSERT INTO venue_capacity_tracking (
                venue_id, space_id, event_id, current_occupancy, max_capacity, 
                occupancy_percentage, status, alert_triggered, alert_level, tracking_method
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $tracking['venue_id'],
            $tracking['space_id'],
            $tracking['event_id'],
            $tracking['current_occupancy'],
            $tracking['max_capacity'],
            $tracking['occupancy_percentage'],
            $tracking['status'],
            $tracking['alert_triggered'] ? 1 : 0,
            $tracking['alert_level'],
            $tracking['tracking_method']
        ]);
    }
    
    /**
     * Check and trigger alerts based on capacity
     */
    private function checkAlerts($tracking)
    {
        $percentage = $tracking['occupancy_percentage'];
        
        if ($percentage >= $this->alertThresholds['critical']) {
            $this->sendCapacityAlert('critical', $tracking);
            $tracking['alert_triggered'] = true;
            $tracking['alert_level'] = 'critical';
        } elseif ($percentage >= $this->alertThresholds['warning']) {
            $this->sendCapacityAlert('warning', $tracking);
            $tracking['alert_triggered'] = true;
            $tracking['alert_level'] = 'warning';
        }
        
        // Check for safety violations
        if ($tracking['current_occupancy'] > $tracking['max_capacity']) {
            $this->sendSafetyAlert($tracking);
        }
    }
    
    /**
     * Send capacity alert notification
     */
    private function sendCapacityAlert($level, $tracking)
    {
        // TODO: Implement notification system
        // This could send emails, SMS, push notifications, etc.
        
        $message = sprintf(
            "Capacity Alert (%s): Venue/Space ID %d is at %d%% capacity (%d/%d people)",
            strtoupper($level),
            $tracking['space_id'] ?: $tracking['venue_id'],
            $tracking['occupancy_percentage'],
            $tracking['current_occupancy'],
            $tracking['max_capacity']
        );
        
        error_log("CAPACITY ALERT: " . $message);
    }
    
    /**
     * Send safety alert for overcapacity
     */
    private function sendSafetyAlert($tracking)
    {
        $message = sprintf(
            "SAFETY ALERT: Venue/Space ID %d is OVER CAPACITY! Current: %d, Max: %d",
            $tracking['space_id'] ?: $tracking['venue_id'],
            $tracking['current_occupancy'],
            $tracking['max_capacity']
        );
        
        error_log("SAFETY ALERT: " . $message);
        
        // TODO: Implement immediate notification to security/management
    }
    
    /**
     * Get occupancy status with formatted data
     */
    private function getOccupancyStatus($tracking)
    {
        return [
            'venue_id' => $tracking['venue_id'],
            'space_id' => $tracking['space_id'],
            'current_occupancy' => $tracking['current_occupancy'],
            'max_capacity' => $tracking['max_capacity'],
            'occupancy_percentage' => $tracking['occupancy_percentage'],
            'available_capacity' => max(0, $tracking['max_capacity'] - $tracking['current_occupancy']),
            'status' => $tracking['status'],
            'alert_level' => $tracking['alert_level'],
            'color_code' => $this->getStatusColor($tracking['status']),
            'timestamp' => date('Y-m-d H:i:s')
        ];\n    }\n    \n    /**\n     * Get venue heatmap for visual display\n     */\n    public function getVenueHeatmap($venueId)\n    {\n        $spaces = $this->getVenueSpaces($venueId);\n        $heatmap = [];\n        \n        foreach ($spaces as $space) {\n            $occupancy = $this->getSpaceOccupancy($space['id']);\n            $heatmap[] = [\n                'space_id' => $space['id'],\n                'space_name' => $space['space_name'],\n                'space_type' => $space['space_type'],\n                'occupancy_percentage' => $occupancy['percentage'],\n                'current_occupancy' => $occupancy['current'],\n                'max_capacity' => $occupancy['capacity'],\n                'color' => $this->getHeatmapColor($occupancy['percentage']),\n                'status' => $occupancy['status']\n            ];\n        }\n        \n        return $heatmap;\n    }\n    \n    /**\n     * Get spaces for a venue\n     */\n    private function getVenueSpaces($venueId)\n    {\n        $query = "SELECT * FROM venue_spaces WHERE venue_id = ? AND status != 'maintenance'";\n        $stmt = $this->db->prepare($query);\n        $stmt->execute([$venueId]);\n        \n        return $stmt->fetchAll();\n    }\n    \n    /**\n     * Get current occupancy for a specific space\n     */\n    private function getSpaceOccupancy($spaceId)\n    {\n        $capacity = $this->getSpaceCapacity($spaceId);\n        $booking = $this->getCurrentBooking(null, $spaceId);\n        $current = $this->calculateCurrentOccupancy($booking);\n        \n        return [\n            'current' => $current,\n            'capacity' => $capacity,\n            'percentage' => $capacity > 0 ? round(($current / $capacity) * 100, 2) : 0,\n            'status' => $this->determineCapacityStatus($current, $capacity)\n        ];\n    }\n    \n    /**\n     * Get status color for UI display\n     */\n    private function getStatusColor($status)\n    {\n        switch ($status) {\n            case 'normal':\n                return '#28a745'; // Green\n            case 'approaching_capacity':\n                return '#ffc107'; // Yellow\n            case 'at_capacity':\n                return '#fd7e14'; // Orange\n            case 'over_capacity':\n                return '#dc3545'; // Red\n            default:\n                return '#6c757d'; // Gray\n        }\n    }\n    \n    /**\n     * Get heatmap color based on percentage\n     */\n    private function getHeatmapColor($percentage)\n    {\n        if ($percentage >= 100) {\n            return '#dc3545'; // Red\n        } elseif ($percentage >= 90) {\n            return '#fd7e14'; // Orange\n        } elseif ($percentage >= 75) {\n            return '#ffc107'; // Yellow\n        } elseif ($percentage >= 50) {\n            return '#20c997'; // Teal\n        } else {\n            return '#28a745'; // Green\n        }\n    }\n    \n    /**\n     * Get capacity trends for analytics\n     */\n    public function getCapacityTrends($venueId, $days = 7)\n    {\n        $query = "\n            SELECT \n                DATE(timestamp) as date,\n                AVG(occupancy_percentage) as avg_occupancy,\n                MAX(occupancy_percentage) as peak_occupancy,\n                COUNT(*) as readings\n            FROM venue_capacity_tracking \n            WHERE venue_id = ? \n            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)\n            GROUP BY DATE(timestamp)\n            ORDER BY date ASC\n        ";\n        \n        $stmt = $this->db->prepare($query);\n        $stmt->execute([$venueId, $days]);\n        \n        return $stmt->fetchAll();\n    }\n    \n    /**\n     * Update occupancy manually\n     */\n    public function updateOccupancy($venueId, $spaceId, $occupancy, $method = 'manual')\n    {\n        $capacity = $this->getSpaceCapacity($spaceId ?: $venueId);\n        \n        $tracking = [\n            'venue_id' => $venueId,\n            'space_id' => $spaceId,\n            'event_id' => null,\n            'current_occupancy' => $occupancy,\n            'max_capacity' => $capacity,\n            'occupancy_percentage' => $capacity > 0 ? round(($occupancy / $capacity) * 100, 2) : 0,\n            'status' => $this->determineCapacityStatus($occupancy, $capacity),\n            'alert_triggered' => false,\n            'alert_level' => 'none',\n            'tracking_method' => $method\n        ];\n        \n        $this->saveTracking($tracking);\n        $this->checkAlerts($tracking);\n        \n        return $this->getOccupancyStatus($tracking);\n    }\n}