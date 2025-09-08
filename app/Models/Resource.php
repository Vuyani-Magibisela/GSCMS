<?php
// app/Models/Resource.php

namespace App\Models;

class Resource extends BaseModel
{
    protected $table = 'resources';
    protected $softDeletes = true;
    
    protected $fillable = [
        'category_id', 'resource_name', 'resource_code', 'description', 'unit_type',
        'total_quantity', 'available_quantity', 'reserved_quantity', 'damaged_quantity',
        'unit_cost', 'replacement_cost', 'supplier_name', 'supplier_contact',
        'purchase_date', 'warranty_expiry', 'storage_location', 'min_quantity',
        'reorder_point', 'image_path', 'specifications', 'status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    // Validation rules
    protected $rules = [
        'category_id' => 'required|integer',
        'resource_name' => 'required|max:200',
        'resource_code' => 'required|max:50|unique:resources,resource_code',
        'unit_type' => 'required',
        'total_quantity' => 'required|integer|min:0',
        'available_quantity' => 'integer|min:0',
        'min_quantity' => 'integer|min:0',
        'reorder_point' => 'integer|min:0'
    ];
    
    // Unit type constants
    const UNIT_PIECE = 'piece';
    const UNIT_SET = 'set';
    const UNIT_KIT = 'kit';
    const UNIT_BOX = 'box';
    const UNIT_ROLL = 'roll';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_DISCONTINUED = 'discontinued';
    const STATUS_MAINTENANCE = 'maintenance';

    protected $belongsTo = [
        'category' => ['model' => ResourceCategory::class, 'foreign_key' => 'category_id']
    ];

    protected $hasMany = [
        'allocations' => ['model' => ResourceAllocation::class, 'foreign_key' => 'resource_id']
    ];

    /**
     * Get resource category
     */
    public function category()
    {
        return $this->belongsTo('App\Models\ResourceCategory', 'category_id');
    }
    
    /**
     * Get resource allocations
     */
    public function allocations()
    {
        return $this->hasMany('App\Models\ResourceAllocation', 'resource_id', 'id');
    }
    
    /**
     * Check if resource needs reordering
     */
    public function needsReorder()
    {
        return $this->available_quantity <= $this->reorder_point;
    }
    
    /**
     * Check if resource is low stock
     */
    public function isLowStock()
    {
        return $this->available_quantity <= $this->min_quantity;
    }
    
    /**
     * Get allocated quantity
     */
    public function getAllocatedQuantity()
    {
        $result = $this->db->query("
            SELECT SUM(quantity) as allocated 
            FROM resource_allocations 
            WHERE resource_id = ? AND status = 'allocated'
        ", [$this->id]);
        
        return $result[0]['allocated'] ?? 0;
    }
    
    /**
     * Get availability percentage
     */
    public function getAvailabilityPercentage()
    {
        if ($this->total_quantity == 0) {
            return 0;
        }
        
        return round(($this->available_quantity / $this->total_quantity) * 100, 2);
    }
    
    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentage()
    {
        if ($this->total_quantity == 0) {
            return 0;
        }
        
        $allocated = $this->getAllocatedQuantity();
        return round(($allocated / $this->total_quantity) * 100, 2);
    }
    
    /**
     * Check availability for allocation
     */
    public function checkAvailability($quantity)
    {
        return $this->available_quantity >= $quantity;
    }
    
    /**
     * Reserve quantity for allocation
     */
    public function reserve($quantity)
    {
        if (!$this->checkAvailability($quantity)) {
            throw new \Exception("Insufficient quantity available. Requested: {$quantity}, Available: {$this->available_quantity}");
        }
        
        $this->available_quantity -= $quantity;
        $this->reserved_quantity += $quantity;
        $this->save();
        
        return true;
    }
    
    /**
     * Release reserved quantity back to available
     */
    public function release($quantity)
    {
        $releaseAmount = min($quantity, $this->reserved_quantity);
        
        $this->reserved_quantity -= $releaseAmount;
        $this->available_quantity += $releaseAmount;
        $this->save();
        
        return $releaseAmount;
    }
    
    /**
     * Mark quantity as damaged
     */
    public function markDamaged($quantity, $notes = '')
    {
        $this->damaged_quantity += $quantity;
        $this->total_quantity -= $quantity;
        $this->save();
        
        // Log the damage
        $this->logResourceActivity('damaged', $quantity, $notes);
    }
    
    /**
     * Restock resource
     */
    public function restock($quantity, $unitCost = null)
    {
        $this->total_quantity += $quantity;
        $this->available_quantity += $quantity;
        
        if ($unitCost !== null) {
            $this->unit_cost = $unitCost;
        }
        
        $this->save();
        
        $this->logResourceActivity('restocked', $quantity, "Restocked {$quantity} units");
        
        return true;
    }
    
    /**
     * Get current stock status
     */
    public function getStockStatus()
    {
        if ($this->available_quantity == 0) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        } elseif ($this->needsReorder()) {
            return 'reorder_needed';
        } else {
            return 'in_stock';
        }
    }
    
    /**
     * Get total value of resource
     */
    public function getTotalValue()
    {
        return $this->total_quantity * $this->unit_cost;
    }
    
    /**
     * Get available value
     */
    public function getAvailableValue()
    {
        return $this->available_quantity * $this->unit_cost;
    }
    
    /**
     * Log resource activity
     */
    private function logResourceActivity($action, $quantity, $notes)
    {
        // TODO: Implement resource activity logging
        error_log("Resource {$this->resource_code}: {$action} {$quantity} - {$notes}");
    }
    
    /**
     * Get recent allocations
     */
    public function getRecentAllocations($limit = 10)
    {
        return $this->db->query("
            SELECT ra.*, u.name as allocated_by_name
            FROM resource_allocations ra
            LEFT JOIN users u ON ra.allocated_by = u.id
            WHERE ra.resource_id = ?
            ORDER BY ra.created_at DESC
            LIMIT ?
        ", [$this->id, $limit]);
    }
    
    /**
     * Get available unit types
     */
    public static function getAvailableUnitTypes()
    {
        return [
            self::UNIT_PIECE => 'Piece',
            self::UNIT_SET => 'Set',
            self::UNIT_KIT => 'Kit',
            self::UNIT_BOX => 'Box',
            self::UNIT_ROLL => 'Roll'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DISCONTINUED => 'Discontinued',
            self::STATUS_MAINTENANCE => 'Maintenance'
        ];
    }
    
    /**
     * Get stock status labels
     */
    public static function getStockStatusLabels()
    {
        return [
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'reorder_needed' => 'Reorder Needed',
            'out_of_stock' => 'Out of Stock'
        ];
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
     * Get resource statistics
     */
    public function getStatistics()
    {
        return [
            'total_quantity' => $this->total_quantity,
            'available_quantity' => $this->available_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'damaged_quantity' => $this->damaged_quantity,
            'allocated_quantity' => $this->getAllocatedQuantity(),
            'availability_percentage' => $this->getAvailabilityPercentage(),
            'utilization_percentage' => $this->getUtilizationPercentage(),
            'total_value' => $this->getTotalValue(),
            'available_value' => $this->getAvailableValue(),
            'stock_status' => $this->getStockStatus(),
            'needs_reorder' => $this->needsReorder(),
            'is_low_stock' => $this->isLowStock()
        ];
    }
    
    /**
     * Scope: Active resources
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Low stock resources
     */
    public function scopeLowStock($query)
    {
        return $query->where('available_quantity', '<=', 'min_quantity');
    }
    
    /**
     * Scope: Resources needing reorder
     */
    public function scopeNeedsReorder($query)
    {
        return $query->where('available_quantity', '<=', 'reorder_point');
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['statistics'] = $this->getStatistics();
        $attributes['specifications_parsed'] = $this->getSpecifications();
        $attributes['unit_type_label'] = self::getAvailableUnitTypes()[$this->unit_type] ?? $this->unit_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['stock_status_label'] = self::getStockStatusLabels()[$this->getStockStatus()] ?? $this->getStockStatus();
        
        return $attributes;
    }
}