<?php
// app/Models/ResourceCategory.php

namespace App\Models;

class ResourceCategory extends BaseModel
{
    protected $table = 'resource_categories';
    
    protected $fillable = [
        'category_name', 'category_type', 'description'
    ];
    
    protected $guarded = ['id', 'created_at'];
    
    // Validation rules
    protected $rules = [
        'category_name' => 'required|max:100',
        'category_type' => 'required'
    ];
    
    // Category type constants
    const TYPE_EQUIPMENT = 'equipment';
    const TYPE_FURNITURE = 'furniture';
    const TYPE_TECHNOLOGY = 'technology';
    const TYPE_SUPPLIES = 'supplies';
    const TYPE_SAFETY = 'safety';

    protected $hasMany = [
        'resources' => ['model' => Resource::class, 'foreign_key' => 'category_id']
    ];

    /**
     * Get category resources
     */
    public function resources()
    {
        return $this->hasMany('App\Models\Resource', 'category_id', 'id');
    }
    
    /**
     * Get resource count for this category
     */
    public function getResourceCount()
    {
        return $this->db->query("
            SELECT COUNT(*) as count 
            FROM resources 
            WHERE category_id = ? AND status = 'active'
        ", [$this->id])[0]['count'] ?? 0;
    }
    
    /**
     * Get total quantity of all resources in category
     */
    public function getTotalQuantity()
    {
        return $this->db->query("
            SELECT SUM(total_quantity) as total 
            FROM resources 
            WHERE category_id = ? AND status = 'active'
        ", [$this->id])[0]['total'] ?? 0;
    }
    
    /**
     * Get available quantity of all resources in category
     */
    public function getAvailableQuantity()
    {
        return $this->db->query("
            SELECT SUM(available_quantity) as available 
            FROM resources 
            WHERE category_id = ? AND status = 'active'
        ", [$this->id])[0]['available'] ?? 0;
    }
    
    /**
     * Get total value of resources in category
     */
    public function getTotalValue()
    {
        return $this->db->query("
            SELECT SUM(total_quantity * unit_cost) as value 
            FROM resources 
            WHERE category_id = ? AND status = 'active'
        ", [$this->id])[0]['value'] ?? 0;
    }
    
    /**
     * Get available category types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_EQUIPMENT => 'Equipment',
            self::TYPE_FURNITURE => 'Furniture',
            self::TYPE_TECHNOLOGY => 'Technology',
            self::TYPE_SUPPLIES => 'Supplies',
            self::TYPE_SAFETY => 'Safety'
        ];
    }
    
    /**
     * Get category statistics
     */
    public function getStatistics()
    {
        return [
            'resource_count' => $this->getResourceCount(),
            'total_quantity' => $this->getTotalQuantity(),
            'available_quantity' => $this->getAvailableQuantity(),
            'total_value' => $this->getTotalValue(),
            'utilization_rate' => $this->getUtilizationRate()
        ];
    }
    
    /**
     * Calculate utilization rate
     */
    private function getUtilizationRate()
    {
        $total = $this->getTotalQuantity();
        $available = $this->getAvailableQuantity();
        
        if ($total == 0) {
            return 0;
        }
        
        return round((($total - $available) / $total) * 100, 2);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['statistics'] = $this->getStatistics();
        $attributes['type_label'] = self::getAvailableTypes()[$this->category_type] ?? $this->category_type;
        
        return $attributes;
    }
}