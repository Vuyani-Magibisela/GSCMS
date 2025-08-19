<?php

namespace App\Models;

class EquipmentCategory extends BaseModel
{
    protected $table = 'equipment_categories';
    protected $softDeletes = true;
    
    protected $fillable = [
        'category_id', 'equipment_name', 'equipment_code', 'equipment_type',
        'required_quantity', 'is_required', 'alternative_options',
        'specifications', 'safety_requirements', 'cost_estimate',
        'supplier_info', 'maintenance_requirements', 'compatibility_notes',
        'status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'category_id' => 'required',
        'equipment_name' => 'required|max:255',
        'equipment_code' => 'required|max:50',
        'equipment_type' => 'required',
        'required_quantity' => 'required|min:1',
        'cost_estimate' => 'numeric|min:0'
    ];
    
    protected $messages = [
        'category_id.required' => 'Category is required.',
        'equipment_name.required' => 'Equipment name is required.',
        'equipment_code.required' => 'Equipment code is required.',
        'equipment_type.required' => 'Equipment type is required.',
        'required_quantity.required' => 'Required quantity is required.',
        'cost_estimate.numeric' => 'Cost estimate must be a valid number.'
    ];
    
    // Equipment type constants
    const TYPE_ROBOT_KIT = 'robot_kit';
    const TYPE_SENSOR = 'sensor';
    const TYPE_ACTUATOR = 'actuator';
    const TYPE_PROGRAMMING_TOOL = 'programming_tool';
    const TYPE_MATERIAL = 'material';
    const TYPE_ACCESSORY = 'accessory';
    
    // Status constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_DISCONTINUED = 'discontinued';
    const STATUS_RECOMMENDED = 'recommended';
    
    protected $belongsTo = [
        'category' => ['model' => Category::class, 'foreign_key' => 'category_id']
    ];
    
    protected $hasMany = [
        'inventory' => ['model' => EquipmentInventory::class, 'foreign_key' => 'equipment_category_id']
    ];

    /**
     * Get category relation
     */
    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }
    
    /**
     * Get equipment inventory
     */
    public function inventory()
    {
        return $this->hasMany('App\Models\EquipmentInventory', 'equipment_category_id', 'id');
    }
    
    /**
     * Get alternative options as array
     */
    public function getAlternativeOptions()
    {
        if (!$this->alternative_options) {
            return [];
        }
        
        return json_decode($this->alternative_options, true) ?? [];
    }
    
    /**
     * Get specifications as array
     */
    public function getSpecifications()
    {
        if (!$this->specifications) {
            return [];
        }
        
        return json_decode($this->specifications, true) ?? [];
    }
    
    /**
     * Get safety requirements as array
     */
    public function getSafetyRequirements()
    {
        if (!$this->safety_requirements) {
            return [];
        }
        
        return json_decode($this->safety_requirements, true) ?? [];
    }
    
    /**
     * Get supplier info as array
     */
    public function getSupplierInfo()
    {
        if (!$this->supplier_info) {
            return [];
        }
        
        return json_decode($this->supplier_info, true) ?? [];
    }
    
    /**
     * Get equipment by category
     */
    public function getEquipmentByCategory($categoryId)
    {
        return $this->db->table($this->table)
            ->where('category_id', $categoryId)
            ->whereNull('deleted_at')
            ->orderBy('is_required', 'DESC')
            ->orderBy('equipment_name')
            ->get();
    }
    
    /**
     * Get required equipment for category
     */
    public function getRequiredEquipmentByCategory($categoryId)
    {
        return $this->db->table($this->table)
            ->where('category_id', $categoryId)
            ->where('is_required', true)
            ->where('status', 'available')
            ->whereNull('deleted_at')
            ->orderBy('equipment_name')
            ->get();
    }
    
    /**
     * Get equipment by type
     */
    public function getEquipmentByType($equipmentType)
    {
        return $this->db->table($this->table)
            ->where('equipment_type', $equipmentType)
            ->where('status', 'available')
            ->whereNull('deleted_at')
            ->orderBy('equipment_name')
            ->get();
    }
    
    /**
     * Calculate total cost for category
     */
    public function getTotalCostForCategory($categoryId)
    {
        $equipment = $this->getRequiredEquipmentByCategory($categoryId);
        $totalCost = 0;
        
        foreach ($equipment as $item) {
            $cost = $item['cost_estimate'] ?? 0;
            $quantity = $item['required_quantity'] ?? 1;
            $totalCost += ($cost * $quantity);
        }
        
        return $totalCost;
    }
    
    /**
     * Check equipment availability
     */
    public function checkAvailability($schoolId = null)
    {
        $inventoryQuery = $this->db->table('equipment_inventory')
            ->where('equipment_category_id', $this->id);
            
        if ($schoolId) {
            $inventoryQuery->where('school_id', $schoolId);
        }
        
        $inventory = $inventoryQuery->get();
        
        $totalAvailable = 0;
        $totalInGoodCondition = 0;
        
        foreach ($inventory as $item) {
            $totalAvailable += $item['available_quantity'] ?? 0;
            if (in_array($item['condition_status'], ['excellent', 'good'])) {
                $totalInGoodCondition += $item['available_quantity'] ?? 0;
            }
        }
        
        return [
            'total_available' => $totalAvailable,
            'in_good_condition' => $totalInGoodCondition,
            'meets_requirements' => $totalInGoodCondition >= $this->required_quantity,
            'shortage' => max(0, $this->required_quantity - $totalInGoodCondition)
        ];
    }
    
    /**
     * Get equipment statistics
     */
    public function getEquipmentStatistics()
    {
        // Get usage across all schools
        $usageStats = $this->db->query("
            SELECT 
                COUNT(DISTINCT ei.school_id) as schools_with_equipment,
                SUM(ei.available_quantity) as total_quantity,
                AVG(ei.available_quantity) as avg_per_school,
                COUNT(CASE WHEN ei.condition_status IN ('excellent', 'good') THEN 1 END) as good_condition_count,
                COUNT(CASE WHEN ei.replacement_needed = 1 THEN 1 END) as replacement_needed_count
            FROM equipment_inventory ei
            WHERE ei.equipment_category_id = ?
        ", [$this->id])[0] ?? [];
        
        return [
            'required_quantity' => $this->required_quantity,
            'is_required' => $this->is_required,
            'cost_estimate' => $this->cost_estimate,
            'equipment_type' => $this->equipment_type,
            'status' => $this->status,
            'usage_statistics' => $usageStats,
            'has_alternatives' => !empty($this->getAlternativeOptions()),
            'safety_critical' => !empty($this->getSafetyRequirements())
        ];
    }
    
    /**
     * Generate equipment checklist for category
     */
    public function generateEquipmentChecklist($categoryId)
    {
        $equipment = $this->getEquipmentByCategory($categoryId);
        $checklist = [];
        
        foreach ($equipment as $item) {
            $checklist[] = [
                'equipment_name' => $item['equipment_name'],
                'equipment_code' => $item['equipment_code'],
                'required_quantity' => $item['required_quantity'],
                'is_required' => $item['is_required'],
                'cost_estimate' => $item['cost_estimate'],
                'alternatives' => json_decode($item['alternative_options'] ?? '[]', true),
                'safety_notes' => json_decode($item['safety_requirements'] ?? '[]', true),
                'specifications' => json_decode($item['specifications'] ?? '[]', true)
            ];
        }
        
        return $checklist;
    }
    
    /**
     * Get available equipment types
     */
    public static function getAvailableEquipmentTypes()
    {
        return [
            self::TYPE_ROBOT_KIT => 'Robot Kit',
            self::TYPE_SENSOR => 'Sensor',
            self::TYPE_ACTUATOR => 'Actuator',
            self::TYPE_PROGRAMMING_TOOL => 'Programming Tool',
            self::TYPE_MATERIAL => 'Material',
            self::TYPE_ACCESSORY => 'Accessory'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_DISCONTINUED => 'Discontinued',
            self::STATUS_RECOMMENDED => 'Recommended'
        ];
    }
    
    /**
     * Scope: Required equipment
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
    
    /**
     * Scope: By equipment type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('equipment_type', $type);
    }
    
    /**
     * Scope: Available equipment
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['alternative_options_parsed'] = $this->getAlternativeOptions();
        $attributes['specifications_parsed'] = $this->getSpecifications();
        $attributes['safety_requirements_parsed'] = $this->getSafetyRequirements();
        $attributes['supplier_info_parsed'] = $this->getSupplierInfo();
        $attributes['equipment_type_label'] = self::getAvailableEquipmentTypes()[$this->equipment_type] ?? $this->equipment_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['statistics'] = $this->getEquipmentStatistics();
        $attributes['availability'] = $this->checkAvailability();
        
        return $attributes;
    }
}