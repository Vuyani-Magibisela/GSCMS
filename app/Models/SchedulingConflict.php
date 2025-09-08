<?php

namespace App\Models;

class SchedulingConflict extends BaseModel
{
    protected $table = 'scheduling_conflicts';
    protected $softDeletes = true;
    
    protected $fillable = [
        'conflict_type', 'severity', 'entity_type', 'entity_id', 'conflicting_entity_id',
        'conflict_date', 'conflict_time', 'description', 'resolution_status',
        'resolved_by', 'resolution_notes', 'resolved_at', 'auto_resolvable', 'impact_score'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at', 'detected_at'];
    
    // Conflict type constants
    const TYPE_DOUBLE_BOOKING = 'double_booking';
    const TYPE_JUDGE_OVERLAP = 'judge_overlap';
    const TYPE_VENUE_CAPACITY = 'venue_capacity';
    const TYPE_TEAM_AVAILABILITY = 'team_availability';
    const TYPE_RESOURCE_SHORTAGE = 'resource_shortage';
    
    // Severity constants
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
    
    // Resolution status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
    
    // Validation rules
    protected $rules = [
        'conflict_type' => 'required|in:double_booking,judge_overlap,venue_capacity,team_availability,resource_shortage',
        'severity' => 'required|in:warning,error,critical',
        'entity_type' => 'required|max:50',
        'entity_id' => 'required|numeric',
        'conflict_date' => 'required|date',
        'description' => 'required',
        'resolution_status' => 'in:pending,resolved,ignored',
        'impact_score' => 'numeric|min:0|max:100'
    ];
    
    protected $messages = [
        'conflict_type.required' => 'Conflict type is required.',
        'severity.required' => 'Severity level is required.',
        'entity_type.required' => 'Entity type is required.',
        'entity_id.required' => 'Entity ID is required.',
        'conflict_date.required' => 'Conflict date is required.',
        'description.required' => 'Description is required.'
    ];
    
    /**
     * Get resolver relation
     */
    public function resolver()
    {
        return $this->belongsTo('App\\Models\\User', 'resolved_by');
    }
    
    /**
     * Get primary entity (generic relation)
     */
    public function getEntity()
    {
        switch ($this->entity_type) {
            case 'time_slot':
                return (new TimeSlot())->find($this->entity_id);
            case 'calendar_event':
                return (new CalendarEvent())->find($this->entity_id);
            case 'team':
                return (new Team())->find($this->entity_id);
            case 'venue':
                return (new Venue())->find($this->entity_id);
            default:
                return null;
        }
    }
    
    /**
     * Get conflicting entity (generic relation)
     */
    public function getConflictingEntity()
    {
        if (!$this->conflicting_entity_id) {
            return null;
        }
        
        switch ($this->entity_type) {
            case 'time_slot':
                return (new TimeSlot())->find($this->conflicting_entity_id);
            case 'calendar_event':
                return (new CalendarEvent())->find($this->conflicting_entity_id);
            case 'team':
                return (new Team())->find($this->conflicting_entity_id);
            case 'venue':
                return (new Venue())->find($this->conflicting_entity_id);
            default:
                return null;
        }
    }
    
    /**
     * Check if conflict is still active
     */
    public function isActive()
    {
        return $this->resolution_status === self::STATUS_PENDING;
    }
    
    /**
     * Check if conflict is resolvable automatically
     */
    public function canAutoResolve()
    {
        return $this->auto_resolvable && $this->isActive();
    }
    
    /**
     * Get priority score based on severity and impact
     */
    public function getPriorityScore()
    {
        $severityScores = [
            self::SEVERITY_WARNING => 1,
            self::SEVERITY_ERROR => 3,
            self::SEVERITY_CRITICAL => 5
        ];
        
        $severityScore = $severityScores[$this->severity] ?? 1;
        $impactScore = $this->impact_score ?? 0;
        
        return ($severityScore * 20) + $impactScore;
    }
    
    /**
     * Mark conflict as resolved
     */
    public function markResolved($resolvedBy, $notes = null)
    {
        $this->resolution_status = self::STATUS_RESOLVED;
        $this->resolved_by = $resolvedBy;
        $this->resolution_notes = $notes;
        $this->resolved_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }
    
    /**
     * Mark conflict as ignored
     */
    public function markIgnored($resolvedBy, $notes = null)
    {
        $this->resolution_status = self::STATUS_IGNORED;
        $this->resolved_by = $resolvedBy;
        $this->resolution_notes = $notes;
        $this->resolved_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }
    
    /**
     * Generate resolution suggestions
     */
    public function getResolutionSuggestions()
    {
        $suggestions = [];
        
        switch ($this->conflict_type) {
            case self::TYPE_DOUBLE_BOOKING:
                $suggestions = [
                    'reschedule_entity' => 'Reschedule one of the conflicting items',
                    'change_venue' => 'Move to a different venue',
                    'change_table' => 'Assign to a different table',
                    'extend_time' => 'Modify time allocation'
                ];
                break;
                
            case self::TYPE_JUDGE_OVERLAP:
                $suggestions = [
                    'reassign_judge' => 'Assign a different judge',
                    'reschedule_session' => 'Reschedule one of the sessions',
                    'add_judge' => 'Add an additional judge',
                    'modify_panels' => 'Reorganize judge panels'
                ];
                break;
                
            case self::TYPE_VENUE_CAPACITY:
                $suggestions = [
                    'split_groups' => 'Split into multiple smaller groups',
                    'change_venue' => 'Move to a larger venue',
                    'extend_hours' => 'Extend operating hours',
                    'reduce_capacity' => 'Reduce participant numbers'
                ];
                break;
                
            case self::TYPE_TEAM_AVAILABILITY:
                $suggestions = [
                    'reschedule_team' => 'Reschedule team to available time',
                    'contact_team' => 'Contact team to confirm availability',
                    'provide_alternatives' => 'Offer alternative time slots'
                ];
                break;
                
            case self::TYPE_RESOURCE_SHORTAGE:
                $suggestions = [
                    'acquire_resources' => 'Obtain additional resources',
                    'reschedule_activities' => 'Reschedule resource-intensive activities',
                    'modify_requirements' => 'Adjust resource requirements'
                ];
                break;
        }
        
        return $suggestions;
    }
    
    /**
     * Get conflicts by severity
     */
    public static function getBySeverity($severity)
    {
        return self::where('severity', $severity)
                  ->where('resolution_status', self::STATUS_PENDING)
                  ->orderBy('detected_at', 'DESC')
                  ->get();
    }
    
    /**
     * Get active conflicts
     */
    public static function getActive()
    {
        return self::where('resolution_status', self::STATUS_PENDING)
                  ->orderBy('severity', 'DESC')
                  ->orderBy('impact_score', 'DESC')
                  ->orderBy('detected_at', 'DESC')
                  ->get();
    }
    
    /**
     * Get auto-resolvable conflicts
     */
    public static function getAutoResolvable()
    {
        return self::where('resolution_status', self::STATUS_PENDING)
                  ->where('auto_resolvable', true)
                  ->orderBy('impact_score', 'DESC')
                  ->get();
    }
    
    /**
     * Get conflicts by date range
     */
    public static function getInDateRange($startDate, $endDate)
    {
        return self::where('conflict_date', '>=', $startDate)
                  ->where('conflict_date', '<=', $endDate)
                  ->orderBy('conflict_date')
                  ->orderBy('severity', 'DESC')
                  ->get();
    }
    
    /**
     * Get conflict statistics
     */
    public static function getStatistics()
    {
        $stats = [];
        
        // Total conflicts
        $stats['total'] = self::count();
        
        // By status
        $statusCounts = self::selectRaw('resolution_status, COUNT(*) as count')
                           ->groupBy('resolution_status')
                           ->get();
        
        foreach ($statusCounts as $status) {
            $stats['by_status'][$status->resolution_status] = $status->count;
        }
        
        // By severity
        $severityCounts = self::selectRaw('severity, COUNT(*) as count')
                             ->where('resolution_status', self::STATUS_PENDING)
                             ->groupBy('severity')
                             ->get();
        
        foreach ($severityCounts as $severity) {
            $stats['by_severity'][$severity->severity] = $severity->count;
        }
        
        // By type
        $typeCounts = self::selectRaw('conflict_type, COUNT(*) as count')
                         ->where('resolution_status', self::STATUS_PENDING)
                         ->groupBy('conflict_type')
                         ->get();
        
        foreach ($typeCounts as $type) {
            $stats['by_type'][$type->conflict_type] = $type->count;
        }
        
        return $stats;
    }
    
    /**
     * Create new conflict
     */
    public static function createConflict($data)
    {
        // Calculate impact score if not provided
        if (!isset($data['impact_score'])) {
            $data['impact_score'] = self::calculateImpactScore($data);
        }
        
        // Set auto_resolvable if not provided
        if (!isset($data['auto_resolvable'])) {
            $data['auto_resolvable'] = self::isAutoResolvable($data);
        }
        
        $conflict = new self();
        foreach ($data as $key => $value) {
            if (in_array($key, $conflict->fillable)) {
                $conflict->$key = $value;
            }
        }
        
        return $conflict->save() ? $conflict : null;
    }
    
    /**
     * Calculate impact score for conflict
     */
    private static function calculateImpactScore($data)
    {
        $score = 0;
        
        // Base score by severity
        switch ($data['severity']) {
            case self::SEVERITY_CRITICAL:
                $score += 50;
                break;
            case self::SEVERITY_ERROR:
                $score += 30;
                break;
            case self::SEVERITY_WARNING:
                $score += 10;
                break;
        }
        
        // Additional score by conflict type
        switch ($data['conflict_type']) {
            case self::TYPE_DOUBLE_BOOKING:
            case self::TYPE_JUDGE_OVERLAP:
                $score += 30;
                break;
            case self::TYPE_VENUE_CAPACITY:
                $score += 20;
                break;
            case self::TYPE_TEAM_AVAILABILITY:
                $score += 15;
                break;
            case self::TYPE_RESOURCE_SHORTAGE:
                $score += 10;
                break;
        }
        
        return min(100, $score);
    }
    
    /**
     * Determine if conflict is auto-resolvable
     */
    private static function isAutoResolvable($data)
    {
        // Only certain types and severities are auto-resolvable
        $autoResolvableTypes = [
            self::TYPE_RESOURCE_SHORTAGE,
            self::TYPE_TEAM_AVAILABILITY
        ];
        
        return in_array($data['conflict_type'], $autoResolvableTypes) && 
               $data['severity'] === self::SEVERITY_WARNING;
    }
    
    /**
     * Get available conflict types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_DOUBLE_BOOKING => 'Double Booking',
            self::TYPE_JUDGE_OVERLAP => 'Judge Overlap',
            self::TYPE_VENUE_CAPACITY => 'Venue Capacity',
            self::TYPE_TEAM_AVAILABILITY => 'Team Availability',
            self::TYPE_RESOURCE_SHORTAGE => 'Resource Shortage'
        ];
    }
    
    /**
     * Get available severities
     */
    public static function getAvailableSeverities()
    {
        return [
            self::SEVERITY_WARNING => 'Warning',
            self::SEVERITY_ERROR => 'Error',
            self::SEVERITY_CRITICAL => 'Critical'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_IGNORED => 'Ignored'
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['is_active'] = $this->isActive();
        $attributes['can_auto_resolve'] = $this->canAutoResolve();
        $attributes['priority_score'] = $this->getPriorityScore();
        $attributes['resolution_suggestions'] = $this->getResolutionSuggestions();
        $attributes['type_label'] = self::getAvailableTypes()[$this->conflict_type] ?? $this->conflict_type;
        $attributes['severity_label'] = self::getAvailableSeverities()[$this->severity] ?? $this->severity;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->resolution_status] ?? $this->resolution_status;
        
        return $attributes;
    }
}