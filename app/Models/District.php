<?php

namespace App\Models;

class District extends BaseModel
{
    protected $table = 'districts';
    protected $fillable = [
        'name', 'province', 'code', 'region', 'coordinator_id', 
        'description', 'boundary_coordinates', 'status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $softDeletes = true;
    
    // Validation rules
    protected $rules = [
        'name' => 'required|max:100|unique',
        'province' => 'required|max:50',
        'code' => 'required|max:10|unique',
        'status' => 'required'
    ];
    
    protected $messages = [
        'name.required' => 'District name is required.',
        'name.unique' => 'A district with this name already exists.',
        'province.required' => 'Province is required.',
        'code.required' => 'District code is required.',
        'code.unique' => 'A district with this code already exists.',
        'status.required' => 'District status is required.'
    ];
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    
    // South African provinces
    const PROVINCES = [
        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
        'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'
    ];
    
    /**
     * Relationship: District has many schools
     */
    public function schools()
    {
        return $this->hasMany('App\Models\School', 'district_id', 'id');
    }
    
    /**
     * Relationship: District belongs to a coordinator (user)
     */
    public function coordinator()
    {
        return $this->belongsTo('App\Models\User', 'coordinator_id', 'id');
    }
    
    /**
     * Get active schools in this district
     */
    public function activeSchools()
    {
        return $this->hasMany('App\Models\School', 'district_id', 'id')
                    ->where('status', 'active');
    }
    
    /**
     * Get district statistics
     */
    public function getStats()
    {
        $stats = $this->db->query("
            SELECT 
                COUNT(DISTINCT s.id) as school_count,
                COUNT(DISTINCT t.id) as team_count,
                COUNT(DISTINCT p.id) as participant_count,
                SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) as active_schools
            FROM districts d
            LEFT JOIN schools s ON d.id = s.district_id AND s.deleted_at IS NULL
            LEFT JOIN teams t ON s.id = t.school_id AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            WHERE d.id = ?
            GROUP BY d.id
        ", [$this->id]);
        
        return $stats[0] ?? [
            'school_count' => 0,
            'team_count' => 0,
            'participant_count' => 0,
            'active_schools' => 0
        ];
    }
    
    /**
     * Search districts by various criteria
     */
    public static function search($criteria = [])
    {
        $model = new static();
        $query = $model->db->table($model->table);
        
        // Apply soft delete filter
        $query->whereNull('deleted_at');
        
        if (!empty($criteria['name'])) {
            $query->where('name', 'LIKE', '%' . $criteria['name'] . '%');
        }
        
        if (!empty($criteria['province'])) {
            $query->where('province', $criteria['province']);
        }
        
        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }
        
        if (!empty($criteria['code'])) {
            $query->where('code', 'LIKE', '%' . $criteria['code'] . '%');
        }
        
        $results = $query->orderBy('province')->orderBy('name')->get();
        return $model->collection($results);
    }
    
    /**
     * Get districts by province with statistics
     */
    public static function getByProvinceWithStats()
    {
        $model = new static();
        $results = $model->db->query("
            SELECT 
                d.*,
                COUNT(DISTINCT s.id) as school_count,
                COUNT(DISTINCT t.id) as team_count,
                COUNT(DISTINCT p.id) as participant_count,
                u.first_name as coordinator_first_name,
                u.last_name as coordinator_last_name,
                u.email as coordinator_email
            FROM districts d
            LEFT JOIN schools s ON d.id = s.district_id AND s.deleted_at IS NULL
            LEFT JOIN teams t ON s.id = t.school_id AND t.deleted_at IS NULL
            LEFT JOIN participants p ON t.id = p.team_id AND p.deleted_at IS NULL
            LEFT JOIN users u ON d.coordinator_id = u.id
            WHERE d.deleted_at IS NULL
            GROUP BY d.id
            ORDER BY d.province, d.name
        ");
        
        return $model->collection($results);
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive'
        ];
    }
    
    /**
     * Scope: Active districts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Districts by province
     */
    public function scopeByProvince($query, $province)
    {
        return $query->where('province', $province);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $stats = $this->getStats();
        $attributes['school_count'] = $stats['school_count'];
        $attributes['team_count'] = $stats['team_count'];
        $attributes['participant_count'] = $stats['participant_count'];
        $attributes['active_schools'] = $stats['active_schools'];
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        
        return $attributes;
    }
}