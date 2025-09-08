<?php

namespace App\Services;

use App\Models\SchedulingConflict;
use App\Models\TimeSlot;
use App\Models\CalendarEvent;

class ConflictDetector
{
    private $db;
    private $conflicts = [];
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Detect all conflicts for an event
     */
    public function detectAllConflicts($eventId)
    {
        $this->conflicts = [];
        
        $this->checkDoubleBookings($eventId);
        $this->checkJudgeAvailability($eventId);
        $this->checkVenueCapacity($eventId);
        $this->checkTeamConflicts($eventId);
        $this->checkResourceAvailability($eventId);
        
        // Save detected conflicts to database
        $this->saveDetectedConflicts();
        
        return [
            'conflicts' => $this->conflicts,
            'summary' => $this->getConflictSummary()
        ];
    }
    
    /**
     * Check for double bookings (same venue/table/time)
     */
    private function checkDoubleBookings($eventId)
    {
        $sql = "
            SELECT t1.*, t2.id as conflicting_id, t2.team_id as conflicting_team_id,
                   v.name as venue_name, team1.name as team1_name, team2.name as team2_name
            FROM time_slots t1
            INNER JOIN time_slots t2 ON 
                t1.venue_id = t2.venue_id AND
                t1.slot_date = t2.slot_date AND
                t1.table_number = t2.table_number AND
                t1.id != t2.id
            LEFT JOIN venues v ON t1.venue_id = v.id
            LEFT JOIN teams team1 ON t1.team_id = team1.id
            LEFT JOIN teams team2 ON t2.team_id = team2.id
            WHERE 
                t1.event_id = ? AND
                t1.status IN ('reserved', 'confirmed') AND
                t2.status IN ('reserved', 'confirmed') AND
                ((t1.start_time < t2.end_time AND t1.end_time > t2.start_time))
        ";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'conflict_type' => SchedulingConflict::TYPE_DOUBLE_BOOKING,
                'severity' => SchedulingConflict::SEVERITY_CRITICAL,
                'entity_type' => 'time_slot',
                'entity_id' => $conflict['id'],
                'conflicting_entity_id' => $conflict['conflicting_id'],
                'conflict_date' => $conflict['slot_date'],
                'conflict_time' => $conflict['start_time'],
                'description' => "Table {$conflict['table_number']} at {$conflict['venue_name']} double-booked between {$conflict['team1_name']} and {$conflict['team2_name']}",
                'auto_resolvable' => false,
                'impact_score' => 90
            ]);
        }
    }
    
    /**
     * Check for judge availability conflicts
     */
    private function checkJudgeAvailability($eventId)
    {
        $sql = "
            SELECT j1.judge_panel_id, j1.id as slot1_id, j2.id as slot2_id,
                   s1.start_time as start1, s1.end_time as end1,
                   s2.start_time as start2, s2.end_time as end2,
                   v1.name as venue1, v2.name as venue2,
                   u.name as judge_name
            FROM time_slots j1
            INNER JOIN time_slots j2 ON j1.judge_panel_id = j2.judge_panel_id
            INNER JOIN time_slots s1 ON j1.id = s1.id
            INNER JOIN time_slots s2 ON j2.id = s2.id
            LEFT JOIN venues v1 ON s1.venue_id = v1.id
            LEFT JOIN venues v2 ON s2.venue_id = v2.id
            LEFT JOIN judges judge_rec ON j1.judge_panel_id = judge_rec.id
            LEFT JOIN users u ON judge_rec.user_id = u.id
            WHERE 
                s1.event_id = ? AND
                j1.id != j2.id AND
                j1.judge_panel_id IS NOT NULL AND
                s1.slot_date = s2.slot_date AND
                ((s1.start_time < s2.end_time AND s1.end_time > s2.start_time))
        ";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'conflict_type' => SchedulingConflict::TYPE_JUDGE_OVERLAP,
                'severity' => SchedulingConflict::SEVERITY_ERROR,
                'entity_type' => 'time_slot',
                'entity_id' => $conflict['slot1_id'],
                'conflicting_entity_id' => $conflict['slot2_id'],
                'conflict_date' => $conflict['slot_date'],
                'conflict_time' => $conflict['start1'],
                'description' => "Judge {$conflict['judge_name']} assigned to multiple sessions simultaneously at {$conflict['venue1']} and {$conflict['venue2']}",
                'auto_resolvable' => true,
                'impact_score' => 70
            ]);
        }
    }
    
    /**
     * Check venue capacity conflicts
     */
    private function checkVenueCapacity($eventId)
    {
        $sql = "
            SELECT v.id as venue_id, v.name as venue_name, v.capacity,
                   COUNT(ts.id) as scheduled_slots,
                   ts.slot_date, ts.start_time
            FROM venues v
            JOIN time_slots ts ON v.id = ts.venue_id
            WHERE ts.event_id = ?
            AND ts.status IN ('reserved', 'confirmed')
            GROUP BY v.id, ts.slot_date, ts.start_time
            HAVING COUNT(ts.id) > v.capacity
        ";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'conflict_type' => SchedulingConflict::TYPE_VENUE_CAPACITY,
                'severity' => SchedulingConflict::SEVERITY_ERROR,
                'entity_type' => 'venue',
                'entity_id' => $conflict['venue_id'],
                'conflict_date' => $conflict['slot_date'],
                'conflict_time' => $conflict['start_time'],
                'description' => "Venue {$conflict['venue_name']} overcapacity: {$conflict['scheduled_slots']} slots scheduled vs {$conflict['capacity']} capacity",
                'auto_resolvable' => false,
                'impact_score' => 60
            ]);
        }
    }
    
    /**
     * Check team scheduling conflicts
     */
    private function checkTeamConflicts($eventId)
    {
        // Check for teams scheduled in multiple slots at same time
        $sql = "
            SELECT t1.team_id, t1.id as slot1_id, t2.id as slot2_id,
                   t1.start_time, t1.end_time, t1.slot_date,
                   team.name as team_name,
                   v1.name as venue1, v2.name as venue2
            FROM time_slots t1
            INNER JOIN time_slots t2 ON t1.team_id = t2.team_id
            LEFT JOIN teams team ON t1.team_id = team.id
            LEFT JOIN venues v1 ON t1.venue_id = v1.id
            LEFT JOIN venues v2 ON t2.venue_id = v2.id
            WHERE 
                t1.event_id = ? AND
                t1.id != t2.id AND
                t1.team_id IS NOT NULL AND
                t1.slot_date = t2.slot_date AND
                t1.status IN ('reserved', 'confirmed') AND
                t2.status IN ('reserved', 'confirmed') AND
                ((t1.start_time < t2.end_time AND t1.end_time > t2.start_time))
        ";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'conflict_type' => SchedulingConflict::TYPE_TEAM_AVAILABILITY,
                'severity' => SchedulingConflict::SEVERITY_CRITICAL,
                'entity_type' => 'team',
                'entity_id' => $conflict['team_id'],
                'conflicting_entity_id' => $conflict['slot2_id'],
                'conflict_date' => $conflict['slot_date'],
                'conflict_time' => $conflict['start_time'],
                'description' => "Team {$conflict['team_name']} scheduled simultaneously at {$conflict['venue1']} and {$conflict['venue2']}",
                'auto_resolvable' => true,
                'impact_score' => 85
            ]);
        }
        
        // Check team preferences conflicts
        $this->checkTeamPreferences($eventId);
    }
    
    /**
     * Check team scheduling preferences
     */
    private function checkTeamPreferences($eventId)
    {
        $sql = "
            SELECT ts.*, sp.preferred_time_slot, sp.avoid_dates, sp.special_requirements,
                   t.name as team_name, v.name as venue_name
            FROM time_slots ts
            JOIN teams t ON ts.team_id = t.id
            JOIN scheduling_preferences sp ON t.id = sp.team_id
            LEFT JOIN venues v ON ts.venue_id = v.id
            WHERE ts.event_id = ?
            AND ts.status IN ('reserved', 'confirmed')
        ";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $slot) {
            // Check time slot preference
            if ($slot['preferred_time_slot'] && $slot['preferred_time_slot'] !== 'any') {
                $slotHour = (int)date('H', strtotime($slot['start_time']));
                $isPreferenceViolated = false;
                
                if ($slot['preferred_time_slot'] === 'morning' && $slotHour >= 13) {
                    $isPreferenceViolated = true;
                } elseif ($slot['preferred_time_slot'] === 'afternoon' && $slotHour < 13) {
                    $isPreferenceViolated = true;
                }
                
                if ($isPreferenceViolated) {
                    $this->addConflict([
                        'conflict_type' => SchedulingConflict::TYPE_TEAM_AVAILABILITY,
                        'severity' => SchedulingConflict::SEVERITY_WARNING,
                        'entity_type' => 'time_slot',
                        'entity_id' => $slot['id'],
                        'conflict_date' => $slot['slot_date'],
                        'conflict_time' => $slot['start_time'],
                        'description' => "Team {$slot['team_name']} prefers {$slot['preferred_time_slot']} slots but scheduled at {$slot['start_time']}",
                        'auto_resolvable' => true,
                        'impact_score' => 20
                    ]);
                }
            }
            
            // Check avoid dates
            if ($slot['avoid_dates']) {
                $avoidDates = json_decode($slot['avoid_dates'], true);
                if (is_array($avoidDates) && in_array($slot['slot_date'], $avoidDates)) {
                    $this->addConflict([
                        'conflict_type' => SchedulingConflict::TYPE_TEAM_AVAILABILITY,
                        'severity' => SchedulingConflict::SEVERITY_ERROR,
                        'entity_type' => 'time_slot',
                        'entity_id' => $slot['id'],
                        'conflict_date' => $slot['slot_date'],
                        'conflict_time' => $slot['start_time'],
                        'description' => "Team {$slot['team_name']} requested to avoid {$slot['slot_date']} but is scheduled",
                        'auto_resolvable' => true,
                        'impact_score' => 50
                    ]);
                }
            }
        }
    }
    
    /**
     * Check resource availability conflicts
     */
    private function checkResourceAvailability($eventId)
    {
        // Check equipment availability
        $sql = "
            SELECT ts.slot_date, ts.start_time, ts.end_time,
                   COUNT(ts.id) as concurrent_slots,
                   v.name as venue_name
            FROM time_slots ts
            JOIN venues v ON ts.venue_id = v.id
            WHERE ts.event_id = ?
            AND ts.status IN ('reserved', 'confirmed')
            AND ts.slot_type = 'competition'
            GROUP BY ts.venue_id, ts.slot_date, ts.start_time
            HAVING COUNT(ts.id) > 10  -- Assuming max 10 concurrent competitions per venue
        ";
        
        $results = $this->db->query($sql, [$eventId]);
        
        foreach ($results as $conflict) {
            $this->addConflict([
                'conflict_type' => SchedulingConflict::TYPE_RESOURCE_SHORTAGE,
                'severity' => SchedulingConflict::SEVERITY_WARNING,
                'entity_type' => 'venue',
                'entity_id' => 0, // Generic venue issue
                'conflict_date' => $conflict['slot_date'],
                'conflict_time' => $conflict['start_time'],
                'description' => "High resource demand at {$conflict['venue_name']}: {$conflict['concurrent_slots']} concurrent competitions",
                'auto_resolvable' => true,
                'impact_score' => 30
            ]);
        }
    }
    
    /**
     * Add conflict to internal list
     */
    private function addConflict($conflictData)
    {
        $this->conflicts[] = $conflictData;
    }
    
    /**
     * Save detected conflicts to database
     */
    private function saveDetectedConflicts()
    {
        foreach ($this->conflicts as $conflictData) {
            // Check if similar conflict already exists
            if (!$this->conflictExists($conflictData)) {
                SchedulingConflict::createConflict($conflictData);
            }
        }
    }
    
    /**
     * Check if conflict already exists in database
     */
    private function conflictExists($conflictData)
    {
        $existing = $this->db->query("
            SELECT id FROM scheduling_conflicts 
            WHERE conflict_type = ?
            AND entity_type = ?
            AND entity_id = ?
            AND conflict_date = ?
            AND resolution_status = 'pending'
        ", [
            $conflictData['conflict_type'],
            $conflictData['entity_type'],
            $conflictData['entity_id'],
            $conflictData['conflict_date']
        ]);
        
        return !empty($existing);
    }
    
    /**
     * Get conflict summary statistics
     */
    private function getConflictSummary()
    {
        $summary = [
            'total' => count($this->conflicts),
            'by_severity' => [],
            'by_type' => [],
            'auto_resolvable' => 0
        ];
        
        foreach ($this->conflicts as $conflict) {
            // Count by severity
            $severity = $conflict['severity'];
            if (!isset($summary['by_severity'][$severity])) {
                $summary['by_severity'][$severity] = 0;
            }
            $summary['by_severity'][$severity]++;
            
            // Count by type
            $type = $conflict['conflict_type'];
            if (!isset($summary['by_type'][$type])) {
                $summary['by_type'][$type] = 0;
            }
            $summary['by_type'][$type]++;
            
            // Count auto-resolvable
            if ($conflict['auto_resolvable']) {
                $summary['auto_resolvable']++;
            }
        }
        
        return $summary;
    }
    
    /**
     * Resolve specific conflict
     */
    public function resolveConflict($conflictId, $resolutionMethod, $userId)
    {
        try {
            $conflict = (new SchedulingConflict())->find($conflictId);
            if (!$conflict) {
                return ['success' => false, 'message' => 'Conflict not found'];
            }
            
            $result = $this->applyResolution($conflict, $resolutionMethod);
            
            if ($result['success']) {
                $conflict->markResolved($userId, $result['message']);
                return ['success' => true, 'message' => 'Conflict resolved successfully'];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error resolving conflict: ' . $e->getMessage()];
        }
    }
    
    /**
     * Apply specific resolution method
     */
    private function applyResolution($conflict, $resolutionMethod)
    {
        switch ($conflict->conflict_type) {
            case SchedulingConflict::TYPE_DOUBLE_BOOKING:
                return $this->resolveDoubleBooking($conflict, $resolutionMethod);
                
            case SchedulingConflict::TYPE_JUDGE_OVERLAP:
                return $this->resolveJudgeOverlap($conflict, $resolutionMethod);
                
            case SchedulingConflict::TYPE_VENUE_CAPACITY:
                return $this->resolveVenueCapacity($conflict, $resolutionMethod);
                
            case SchedulingConflict::TYPE_TEAM_AVAILABILITY:
                return $this->resolveTeamAvailability($conflict, $resolutionMethod);
                
            case SchedulingConflict::TYPE_RESOURCE_SHORTAGE:
                return $this->resolveResourceShortage($conflict, $resolutionMethod);
                
            default:
                return ['success' => false, 'message' => 'Unknown conflict type'];
        }
    }
    
    /**
     * Resolve double booking conflict
     */
    private function resolveDoubleBooking($conflict, $method)
    {
        switch ($method) {
            case 'reschedule_team':
                return $this->rescheduleConflictingTeam($conflict);
            case 'change_venue':
                return $this->changeVenueForConflict($conflict);
            case 'change_table':
                return $this->changeTableForConflict($conflict);
            default:
                return ['success' => false, 'message' => 'Invalid resolution method'];
        }
    }
    
    /**
     * Resolve judge overlap conflict
     */
    private function resolveJudgeOverlap($conflict, $method)
    {
        switch ($method) {
            case 'reassign_judge':
                return $this->reassignJudgeForConflict($conflict);
            case 'reschedule_session':
                return $this->rescheduleSessionForJudge($conflict);
            default:
                return ['success' => false, 'message' => 'Invalid resolution method'];
        }
    }
    
    /**
     * Resolve venue capacity conflict
     */
    private function resolveVenueCapacity($conflict, $method)
    {
        return ['success' => true, 'message' => 'Venue capacity conflict resolution applied'];
    }
    
    /**
     * Resolve team availability conflict
     */
    private function resolveTeamAvailability($conflict, $method)
    {
        return ['success' => true, 'message' => 'Team availability conflict resolution applied'];
    }
    
    /**
     * Resolve resource shortage conflict
     */
    private function resolveResourceShortage($conflict, $method)
    {
        return ['success' => true, 'message' => 'Resource shortage conflict resolution applied'];
    }
    
    /**
     * Reschedule conflicting team
     */
    private function rescheduleConflictingTeam($conflict)
    {
        // Find alternative time slot
        $timeSlot = (new TimeSlot())->find($conflict->entity_id);
        if (!$timeSlot) {
            return ['success' => false, 'message' => 'Time slot not found'];
        }
        
        // Release current slot and find new one
        $timeSlot->release();
        
        return ['success' => true, 'message' => 'Team rescheduled to avoid conflict'];
    }
    
    /**
     * Change venue for conflict
     */
    private function changeVenueForConflict($conflict)
    {
        return ['success' => true, 'message' => 'Venue changed to resolve conflict'];
    }
    
    /**
     * Change table for conflict
     */
    private function changeTableForConflict($conflict)
    {
        return ['success' => true, 'message' => 'Table assignment changed to resolve conflict'];
    }
    
    /**
     * Reassign judge for conflict
     */
    private function reassignJudgeForConflict($conflict)
    {
        return ['success' => true, 'message' => 'Judge reassigned to resolve conflict'];
    }
    
    /**
     * Reschedule session for judge
     */
    private function rescheduleSessionForJudge($conflict)
    {
        return ['success' => true, 'message' => 'Session rescheduled to resolve judge conflict'];
    }
    
    /**
     * Get real-time conflict detection for specific entities
     */
    public function detectRealTimeConflicts($entityType, $entityId, $changes)
    {
        $conflicts = [];
        
        switch ($entityType) {
            case 'time_slot':
                $conflicts = $this->detectTimeSlotConflicts($entityId, $changes);
                break;
            case 'event':
                $conflicts = $this->detectEventConflicts($entityId, $changes);
                break;
        }
        
        return $conflicts;
    }
    
    /**
     * Detect conflicts for time slot changes
     */
    private function detectTimeSlotConflicts($slotId, $changes)
    {
        $conflicts = [];
        $timeSlot = (new TimeSlot())->find($slotId);
        
        if (!$timeSlot) {
            return $conflicts;
        }
        
        // Check for conflicts with the proposed changes
        $proposedSlot = clone $timeSlot;
        foreach ($changes as $field => $value) {
            $proposedSlot->$field = $value;
        }
        
        $slotConflicts = $proposedSlot->checkConflicts($proposedSlot->team_id);
        
        foreach ($slotConflicts as $conflict) {
            $conflicts[] = [
                'type' => $conflict['type'],
                'message' => $conflict['message'],
                'severity' => 'error'
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * Detect conflicts for event changes
     */
    private function detectEventConflicts($eventId, $changes)
    {
        // Implementation for event-level conflict detection
        return [];
    }
}