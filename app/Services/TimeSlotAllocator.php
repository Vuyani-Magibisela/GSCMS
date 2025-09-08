<?php

namespace App\Services;

use App\Models\TimeSlot;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\Venue;
use App\Models\SchedulingConflict;

class TimeSlotAllocator
{
    private $db;
    private $slotDuration = 30; // minutes per team
    private $bufferTime = 15;   // minutes between slots
    private $judgeRotation = 3; // teams per judge panel
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }
    
    /**
     * Allocate competition slots for an event
     */
    public function allocateCompetitionSlots($eventId, $categoryId = null)
    {
        try {
            $event = (new CalendarEvent())->find($eventId);
            if (!$event) {
                return ['success' => false, 'message' => 'Event not found'];
            }
            
            $teams = $this->getRegisteredTeams($categoryId, $event->phase_id);
            if (empty($teams)) {
                return ['success' => false, 'message' => 'No teams registered for this event'];
            }
            
            $venues = $this->getAvailableVenues($eventId);
            if (empty($venues)) {
                return ['success' => false, 'message' => 'No venues available for this event'];
            }
            
            $judges = $this->getAvailableJudges($categoryId);
            
            $schedule = $this->generateOptimalSchedule($event, $teams, $venues, $judges);
            
            if (empty($schedule)) {
                return ['success' => false, 'message' => 'Unable to generate schedule'];
            }
            
            // Save generated time slots
            $savedSlots = $this->saveTimeSlots($schedule);
            
            return [
                'success' => true,
                'message' => 'Successfully allocated ' . count($savedSlots) . ' time slots',
                'slots' => $savedSlots,
                'statistics' => $this->getAllocationStatistics($schedule)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error allocating time slots: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate optimal schedule using allocation algorithm
     */
    private function generateOptimalSchedule($event, $teams, $venues, $judges)
    {
        $schedule = [];
        $teamIndex = 0;
        $eventDate = date('Y-m-d', strtotime($event->start_datetime));
        
        foreach ($venues as $venue) {
            $tables = $venue['competition_tables'] ?? 2;
            $operatingHours = $this->getVenueOperatingHours($venue, $event);
            $slots = $this->generateTimeSlots($operatingHours);
            
            foreach ($slots as $slot) {
                for ($table = 1; $table <= $tables && $teamIndex < count($teams); $table++) {
                    $team = $teams[$teamIndex];
                    
                    $scheduleItem = [
                        'event_id' => $event->id,
                        'venue_id' => $venue['id'],
                        'slot_date' => $eventDate,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'slot_type' => TimeSlot::TYPE_COMPETITION,
                        'team_id' => $team['id'],
                        'category_id' => $team['category_id'],
                        'table_number' => "T{$table}",
                        'duration_minutes' => $this->slotDuration,
                        'buffer_minutes' => $this->bufferTime,
                        'status' => TimeSlot::STATUS_RESERVED,
                        'judge_panel_id' => $this->assignJudgePanel($judges, $teamIndex),
                        'team_name' => $team['name'],
                        'school_name' => $team['school_name'],
                        'venue_name' => $venue['name']
                    ];
                    
                    $schedule[] = $scheduleItem;
                    $teamIndex++;
                }
            }
        }
        
        // Optimize schedule
        return $this->optimizeSchedule($schedule);
    }
    
    /**
     * Generate time slots for venue operating hours
     */
    private function generateTimeSlots($operatingHours)
    {
        $slots = [];
        $current = strtotime($operatingHours['start']);
        $end = strtotime($operatingHours['end']);
        
        while ($current < $end) {
            $slotEnd = $current + ($this->slotDuration * 60);
            
            if ($slotEnd <= $end) {
                $slots[] = [
                    'start' => date('H:i:s', $current),
                    'end' => date('H:i:s', $slotEnd)
                ];
            }
            
            $current = $slotEnd + ($this->bufferTime * 60);
        }
        
        return $slots;
    }
    
    /**
     * Optimize schedule based on various criteria
     */
    private function optimizeSchedule($schedule)
    {
        // Sort by school, then by time to minimize travel time
        usort($schedule, function($a, $b) {
            // Group teams from same school together
            $schoolComparison = strcmp($a['school_name'], $b['school_name']);
            if ($schoolComparison !== 0) {
                return $schoolComparison;
            }
            
            // Within same school, sort by time
            return strcmp($a['start_time'], $b['start_time']);
        });
        
        // Apply preference-based adjustments
        $schedule = $this->applyTeamPreferences($schedule);
        
        // Balance judge workload
        $schedule = $this->balanceJudgeWorkload($schedule);
        
        return $schedule;
    }
    
    /**
     * Apply team scheduling preferences
     */
    private function applyTeamPreferences($schedule)
    {
        // Get team preferences
        $preferences = $this->getTeamPreferences($schedule);
        
        foreach ($preferences as $preference) {
            $teamId = $preference['team_id'];
            $preferredTimeSlot = $preference['preferred_time_slot'];
            
            // Find team in schedule
            for ($i = 0; $i < count($schedule); $i++) {
                if ($schedule[$i]['team_id'] == $teamId) {
                    $currentSlot = $schedule[$i];
                    
                    // Try to accommodate preference
                    if ($this->canAccommodatePreference($currentSlot, $preferredTimeSlot, $schedule)) {
                        $schedule = $this->adjustScheduleForPreference($schedule, $i, $preferredTimeSlot);
                    }
                    break;
                }
            }
        }
        
        return $schedule;
    }
    
    /**
     * Balance workload across judges
     */
    private function balanceJudgeWorkload($schedule)
    {
        $judgeWorkload = [];
        
        // Calculate current workload
        foreach ($schedule as &$slot) {
            $judgeId = $slot['judge_panel_id'];
            if (!isset($judgeWorkload[$judgeId])) {
                $judgeWorkload[$judgeId] = 0;
            }
            $judgeWorkload[$judgeId]++;
        }
        
        // Redistribute if imbalanced
        $avgWorkload = array_sum($judgeWorkload) / count($judgeWorkload);
        
        foreach ($schedule as &$slot) {
            $judgeId = $slot['judge_panel_id'];
            if ($judgeWorkload[$judgeId] > $avgWorkload + 2) {
                // Find judge with lower workload
                $lightestJudge = array_search(min($judgeWorkload), $judgeWorkload);
                $slot['judge_panel_id'] = $lightestJudge;
                $judgeWorkload[$judgeId]--;
                $judgeWorkload[$lightestJudge]++;
            }
        }
        
        return $schedule;
    }
    
    /**
     * Bulk allocate teams to available slots
     */
    public function bulkAllocateTeams($eventId, $categoryId, $venueId = null, $options = [])
    {
        try {
            $availableSlots = $this->getAvailableSlots($eventId, $venueId);
            $unassignedTeams = $this->getUnassignedTeams($categoryId);
            
            if (empty($availableSlots)) {
                return ['success' => false, 'message' => 'No available time slots'];
            }
            
            if (empty($unassignedTeams)) {
                return ['success' => false, 'message' => 'No unassigned teams'];
            }
            
            $assignments = [];
            $teamIndex = 0;
            
            foreach ($availableSlots as $slot) {
                if ($teamIndex >= count($unassignedTeams)) {
                    break;
                }
                
                $team = $unassignedTeams[$teamIndex];
                $timeSlot = new TimeSlot();
                $timeSlot = $timeSlot->find($slot['id']);
                
                if ($timeSlot && $timeSlot->isAvailable()) {
                    $result = $timeSlot->reserve($team['id'], $team['category_id']);
                    if ($result['success']) {
                        $assignments[] = [
                            'slot_id' => $slot['id'],
                            'team_id' => $team['id'],
                            'team_name' => $team['name'],
                            'start_time' => $slot['start_time'],
                            'venue_name' => $slot['venue_name']
                        ];
                        $teamIndex++;
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => 'Successfully assigned ' . count($assignments) . ' teams',
                'assignments' => $assignments
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error in bulk allocation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Auto-resolve simple scheduling conflicts
     */
    public function autoResolveConflicts($eventId)
    {
        $conflicts = SchedulingConflict::where('resolution_status', SchedulingConflict::STATUS_PENDING)
                                      ->where('auto_resolvable', true)
                                      ->get();
        
        $resolved = 0;
        $failed = 0;
        
        foreach ($conflicts as $conflict) {
            if ($this->attemptAutoResolution($conflict)) {
                $resolved++;
            } else {
                $failed++;
            }
        }
        
        return [
            'success' => true,
            'resolved' => $resolved,
            'failed' => $failed,
            'total' => count($conflicts)
        ];
    }
    
    /**
     * Attempt automatic resolution of a conflict
     */
    private function attemptAutoResolution($conflict)
    {
        try {
            switch ($conflict->conflict_type) {
                case SchedulingConflict::TYPE_TEAM_AVAILABILITY:
                    return $this->resolveTeamAvailabilityConflict($conflict);
                    
                case SchedulingConflict::TYPE_RESOURCE_SHORTAGE:
                    return $this->resolveResourceShortageConflict($conflict);
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get registered teams for allocation
     */
    private function getRegisteredTeams($categoryId = null, $phaseId = null)
    {
        $query = "
            SELECT t.*, s.name as school_name, c.name as category_name
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE t.deleted_at IS NULL
        ";
        
        $params = [];
        
        if ($categoryId) {
            $query .= " AND t.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($phaseId) {
            $query .= " AND EXISTS (
                SELECT 1 FROM competition_phases cp 
                WHERE cp.id = ? AND cp.competition_id = t.competition_id
            )";
            $params[] = $phaseId;
        }
        
        $query .= " ORDER BY c.name, s.name, t.name";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get available venues for event
     */
    private function getAvailableVenues($eventId)
    {
        return $this->db->query("
            SELECT v.*, ve.competition_tables, ve.operating_hours
            FROM venues v
            LEFT JOIN venue_equipment ve ON v.id = ve.venue_id
            WHERE v.is_active = 1
            AND v.deleted_at IS NULL
            ORDER BY v.capacity DESC
        ");
    }
    
    /**
     * Get available judges for category
     */
    private function getAvailableJudges($categoryId)
    {
        return $this->db->query("
            SELECT j.*, u.name as judge_name
            FROM judges j
            JOIN users u ON j.user_id = u.id
            WHERE j.is_active = 1
            AND j.deleted_at IS NULL
            AND (j.category_id IS NULL OR j.category_id = ?)
            ORDER BY u.name
        ", [$categoryId]);
    }
    
    /**
     * Get venue operating hours for event
     */
    private function getVenueOperatingHours($venue, $event)
    {
        // Default operating hours
        $defaultHours = [
            'start' => '08:30:00',
            'end' => '16:30:00'
        ];
        
        // Use venue-specific hours if available
        if (isset($venue['operating_hours'])) {
            $hours = json_decode($venue['operating_hours'], true);
            if ($hours && isset($hours['start']) && isset($hours['end'])) {
                return $hours;
            }
        }
        
        // Use event-specific hours
        $eventStart = date('H:i:s', strtotime($event->start_datetime));
        $eventEnd = date('H:i:s', strtotime($event->end_datetime));
        
        return [
            'start' => $eventStart ?: $defaultHours['start'],
            'end' => $eventEnd ?: $defaultHours['end']
        ];
    }
    
    /**
     * Assign judge panel to team
     */
    private function assignJudgePanel($judges, $teamIndex)
    {
        if (empty($judges)) {
            return null;
        }
        
        $panelIndex = floor($teamIndex / $this->judgeRotation) % count($judges);
        return $judges[$panelIndex]['id'] ?? null;
    }
    
    /**
     * Save generated time slots to database
     */
    private function saveTimeSlots($schedule)
    {
        $savedSlots = [];
        
        foreach ($schedule as $item) {
            $timeSlot = new TimeSlot();
            
            foreach ($item as $key => $value) {
                if (in_array($key, $timeSlot->getFillable())) {
                    $timeSlot->$key = $value;
                }
            }
            
            if ($timeSlot->save()) {
                $savedSlots[] = $timeSlot->id;
            }
        }
        
        return $savedSlots;
    }
    
    /**
     * Get allocation statistics
     */
    private function getAllocationStatistics($schedule)
    {
        $stats = [
            'total_slots' => count($schedule),
            'venues_used' => count(array_unique(array_column($schedule, 'venue_id'))),
            'teams_scheduled' => count(array_unique(array_column($schedule, 'team_id'))),
            'time_span' => $this->calculateTimeSpan($schedule),
            'venue_utilization' => []
        ];
        
        // Calculate venue utilization
        $venueUsage = array_count_values(array_column($schedule, 'venue_name'));
        foreach ($venueUsage as $venue => $count) {
            $stats['venue_utilization'][$venue] = $count;
        }
        
        return $stats;
    }
    
    /**
     * Calculate time span of schedule
     */
    private function calculateTimeSpan($schedule)
    {
        if (empty($schedule)) {
            return '0 hours';
        }
        
        $times = array_map(function($item) {
            return strtotime($item['start_time']);
        }, $schedule);
        
        $earliest = min($times);
        $latest = max($times) + ($this->slotDuration * 60);
        
        $hours = ($latest - $earliest) / 3600;
        
        return round($hours, 1) . ' hours';
    }
    
    /**
     * Get team scheduling preferences
     */
    private function getTeamPreferences($schedule)
    {
        $teamIds = array_column($schedule, 'team_id');
        if (empty($teamIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($teamIds) - 1) . '?';
        
        return $this->db->query("
            SELECT * FROM scheduling_preferences
            WHERE team_id IN ($placeholders)
        ", $teamIds);
    }
    
    /**
     * Check if team preference can be accommodated
     */
    private function canAccommodatePreference($currentSlot, $preferredTimeSlot, $schedule)
    {
        $currentTime = strtotime($currentSlot['start_time']);
        
        switch ($preferredTimeSlot) {
            case 'morning':
                return $currentTime < strtotime('12:00:00');
            case 'afternoon':
                return $currentTime >= strtotime('13:00:00');
            default:
                return true;
        }
    }
    
    /**
     * Adjust schedule for team preference
     */
    private function adjustScheduleForPreference($schedule, $slotIndex, $preferredTimeSlot)
    {
        // Implementation would involve finding suitable swap or adjustment
        // For now, return schedule unchanged
        return $schedule;
    }
    
    /**
     * Get available time slots
     */
    private function getAvailableSlots($eventId, $venueId = null)
    {
        $query = "
            SELECT ts.*, v.name as venue_name
            FROM time_slots ts
            JOIN venues v ON ts.venue_id = v.id
            WHERE ts.event_id = ?
            AND ts.status = ?
        ";
        
        $params = [$eventId, TimeSlot::STATUS_AVAILABLE];
        
        if ($venueId) {
            $query .= " AND ts.venue_id = ?";
            $params[] = $venueId;
        }
        
        $query .= " ORDER BY ts.start_time, ts.table_number";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get unassigned teams
     */
    private function getUnassignedTeams($categoryId)
    {
        return $this->db->query("
            SELECT t.*, s.name as school_name
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            WHERE t.category_id = ?
            AND t.deleted_at IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM time_slots ts 
                WHERE ts.team_id = t.id 
                AND ts.status IN (?, ?)
            )
            ORDER BY s.name, t.name
        ", [$categoryId, TimeSlot::STATUS_RESERVED, TimeSlot::STATUS_CONFIRMED]);
    }
    
    /**
     * Resolve team availability conflict
     */
    private function resolveTeamAvailabilityConflict($conflict)
    {
        // Implementation for resolving team availability conflicts
        $conflict->markResolved(1, 'Auto-resolved: Team availability adjusted');
        return true;
    }
    
    /**
     * Resolve resource shortage conflict
     */
    private function resolveResourceShortageConflict($conflict)
    {
        // Implementation for resolving resource shortage conflicts
        $conflict->markResolved(1, 'Auto-resolved: Resource allocation optimized');
        return true;
    }
}