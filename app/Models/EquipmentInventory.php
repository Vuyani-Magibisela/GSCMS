<?php

namespace App\Models;

class EquipmentInventory extends BaseModel
{
    protected $table = 'equipment_inventory';
    protected $softDeletes = true;
    
    protected $fillable = [
        'equipment_category_id', 'school_id', 'available_quantity',
        'condition_status', 'last_maintenance', 'next_maintenance_due',
        'acquisition_date', 'replacement_needed', 'location',
        'serial_numbers', 'usage_history', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'equipment_category_id' => 'required',
        'available_quantity' => 'required|min:0',
        'condition_status' => 'required',
        'acquisition_date' => 'date'
    ];
    
    protected $messages = [
        'equipment_category_id.required' => 'Equipment category is required.',
        'available_quantity.required' => 'Available quantity is required.',
        'condition_status.required' => 'Condition status is required.',
        'acquisition_date.date' => 'Acquisition date must be a valid date.'
    ];
    
    // Condition status constants
    const CONDITION_EXCELLENT = 'excellent';
    const CONDITION_GOOD = 'good';
    const CONDITION_FAIR = 'fair';
    const CONDITION_POOR = 'poor';
    const CONDITION_BROKEN = 'broken';
    
    protected $belongsTo = [
        'equipmentCategory' => ['model' => EquipmentCategory::class, 'foreign_key' => 'equipment_category_id'],
        'school' => ['model' => School::class, 'foreign_key' => 'school_id']
    ];

    /**
     * Get equipment category relation
     */
    public function equipmentCategory()
    {
        return $this->belongsTo('App\Models\EquipmentCategory', 'equipment_category_id');
    }
    
    /**
     * Get school relation
     */
    public function school()
    {
        return $this->belongsTo('App\Models\School', 'school_id');
    }
    
    /**
     * Get serial numbers as array
     */
    public function getSerialNumbers()
    {
        if (!$this->serial_numbers) {
            return [];
        }
        
        return json_decode($this->serial_numbers, true) ?? [];
    }
    
    /**
     * Get usage history as array
     */
    public function getUsageHistory()
    {
        if (!$this->usage_history) {
            return [];
        }
        
        return json_decode($this->usage_history, true) ?? [];
    }
    
    /**
     * Check if maintenance is due
     */
    public function isMaintenanceDue()
    {
        if (!$this->next_maintenance_due) {
            return false;
        }
        
        return strtotime($this->next_maintenance_due) <= time();
    }
    
    /**
     * Check if replacement is needed
     */
    public function needsReplacement()
    {
        return $this->replacement_needed || 
               $this->condition_status === self::CONDITION_BROKEN ||
               $this->condition_status === self::CONDITION_POOR;
    }
    
    /**
     * Get inventory by school
     */
    public function getInventoryBySchool($schoolId)
    {
        return $this->db->query("
            SELECT 
                ei.*,
                ec.equipment_name,
                ec.equipment_code,
                ec.equipment_type,
                ec.required_quantity,
                c.name as category_name
            FROM equipment_inventory ei
            JOIN equipment_categories ec ON ei.equipment_category_id = ec.id
            JOIN categories c ON ec.category_id = c.id
            WHERE ei.school_id = ?
            AND ei.deleted_at IS NULL
            ORDER BY c.name, ec.equipment_name
        ", [$schoolId]);
    }
    
    /**
     * Get inventory by equipment category
     */
    public function getInventoryByEquipmentCategory($equipmentCategoryId)
    {
        return $this->db->query("
            SELECT 
                ei.*,
                s.name as school_name,
                s.address as school_address
            FROM equipment_inventory ei
            LEFT JOIN schools s ON ei.school_id = s.id
            WHERE ei.equipment_category_id = ?
            AND ei.deleted_at IS NULL
            ORDER BY s.name, ei.condition_status
        ", [$equipmentCategoryId]);
    }
    
    /**
     * Get maintenance schedule
     */
    public function getMaintenanceSchedule($schoolId = null, $daysAhead = 30)
    {
        $query = "
            SELECT 
                ei.*,
                ec.equipment_name,
                ec.equipment_code,
                s.name as school_name
            FROM equipment_inventory ei
            JOIN equipment_categories ec ON ei.equipment_category_id = ec.id
            LEFT JOIN schools s ON ei.school_id = s.id
            WHERE ei.next_maintenance_due IS NOT NULL
            AND ei.next_maintenance_due <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND ei.deleted_at IS NULL
        ";
        
        $params = [$daysAhead];
        
        if ($schoolId) {
            $query .= " AND ei.school_id = ?";
            $params[] = $schoolId;
        }
        
        $query .= " ORDER BY ei.next_maintenance_due, s.name, ec.equipment_name";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get replacement schedule
     */
    public function getReplacementSchedule($schoolId = null)
    {
        $query = "
            SELECT 
                ei.*,
                ec.equipment_name,
                ec.equipment_code,
                ec.cost_estimate,
                s.name as school_name
            FROM equipment_inventory ei
            JOIN equipment_categories ec ON ei.equipment_category_id = ec.id
            LEFT JOIN schools s ON ei.school_id = s.id
            WHERE (ei.replacement_needed = 1 
                OR ei.condition_status IN ('poor', 'broken'))
            AND ei.deleted_at IS NULL
        ";
        
        $params = [];
        
        if ($schoolId) {
            $query .= " AND ei.school_id = ?";
            $params[] = $schoolId;
        }
        
        $query .= " ORDER BY ei.condition_status DESC, s.name, ec.equipment_name";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Record maintenance activity
     */
    public function recordMaintenance($maintenanceDate, $nextDueDate = null, $notes = null)
    {
        $updateData = [
            'last_maintenance' => $maintenanceDate,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($nextDueDate) {
            $updateData['next_maintenance_due'] = $nextDueDate;
        }
        
        if ($notes) {
            // Add to usage history
            $history = $this->getUsageHistory();
            $history[] = [
                'type' => 'maintenance',
                'date' => $maintenanceDate,
                'notes' => $notes,
                'recorded_at' => date('Y-m-d H:i:s')
            ];
            $updateData['usage_history'] = json_encode($history);
        }
        
        return $this->update($updateData);
    }
    
    /**
     * Record usage activity
     */
    public function recordUsage($activityType, $description, $date = null)
    {
        $history = $this->getUsageHistory();
        $history[] = [
            'type' => $activityType,
            'description' => $description,
            'date' => $date ?: date('Y-m-d'),
            'recorded_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update([
            'usage_history' => json_encode($history),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update condition status
     */
    public function updateCondition($newCondition, $notes = null)
    {
        $updateData = [
            'condition_status' => $newCondition,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Automatically flag for replacement if condition is poor or broken
        if (in_array($newCondition, [self::CONDITION_POOR, self::CONDITION_BROKEN])) {
            $updateData['replacement_needed'] = true;
        }
        
        if ($notes) {
            $history = $this->getUsageHistory();
            $history[] = [
                'type' => 'condition_update',
                'old_condition' => $this->condition_status,
                'new_condition' => $newCondition,
                'notes' => $notes,
                'date' => date('Y-m-d'),
                'recorded_at' => date('Y-m-d H:i:s')
            ];
            $updateData['usage_history'] = json_encode($history);
        }
        
        return $this->update($updateData);
    }
    
    /**
     * Get inventory statistics
     */
    public function getInventoryStatistics($schoolId = null)
    {
        $query = "
            SELECT 
                ei.condition_status,
                COUNT(*) as count,
                SUM(ei.available_quantity) as total_quantity,
                COUNT(CASE WHEN ei.replacement_needed = 1 THEN 1 END) as replacement_needed_count,
                COUNT(CASE WHEN ei.next_maintenance_due <= CURDATE() THEN 1 END) as maintenance_due_count
            FROM equipment_inventory ei
            WHERE ei.deleted_at IS NULL
        ";
        
        $params = [];
        
        if ($schoolId) {
            $query .= " AND ei.school_id = ?";
            $params[] = $schoolId;
        }
        
        $query .= " GROUP BY ei.condition_status";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get available condition statuses
     */
    public static function getAvailableConditionStatuses()
    {
        return [
            self::CONDITION_EXCELLENT => 'Excellent',
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_FAIR => 'Fair',
            self::CONDITION_POOR => 'Poor',
            self::CONDITION_BROKEN => 'Broken'
        ];
    }
    
    /**
     * Scope: By condition
     */
    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition_status', $condition);
    }
    
    /**
     * Scope: Maintenance due
     */
    public function scopeMaintenanceDue($query)
    {
        return $query->where('next_maintenance_due', '<=', date('Y-m-d'));
    }
    
    /**
     * Scope: Replacement needed
     */
    public function scopeReplacementNeeded($query)
    {
        return $query->where('replacement_needed', true)
                    ->orWhereIn('condition_status', [self::CONDITION_POOR, self::CONDITION_BROKEN]);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['serial_numbers_parsed'] = $this->getSerialNumbers();
        $attributes['usage_history_parsed'] = $this->getUsageHistory();
        $attributes['is_maintenance_due'] = $this->isMaintenanceDue();
        $attributes['needs_replacement'] = $this->needsReplacement();
        $attributes['condition_status_label'] = self::getAvailableConditionStatuses()[$this->condition_status] ?? $this->condition_status;
        $attributes['days_until_maintenance'] = $this->next_maintenance_due ? 
            max(0, floor((strtotime($this->next_maintenance_due) - time()) / (60 * 60 * 24))) : null;
        
        return $attributes;
    }
}