<?php
// app/Models/BaseModel.php

namespace App\Models;

use App\Core\Database;
use Exception;

abstract class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $dates = ['created_at', 'updated_at'];
    protected $timestamps = true;
    
    // Relationships
    protected $hasOne = [];
    protected $hasMany = [];
    protected $belongsTo = [];
    protected $belongsToMany = [];
    
    // Query scopes
    protected $globalScopes = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Auto-set table name if not specified
        if (!$this->table) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className)) . 's';
        }
    }
    
    /**
     * Find record by ID
     */
    public function find($id)
    {
        $result = $this->db->table($this->table)->find($id);
        return $result ? $this->newInstance($result) : null;
    }
    
    /**
     * Find multiple records by IDs
     */
    public function findMany($ids)
    {
        $results = $this->db->table($this->table)->whereIn($this->primaryKey, $ids)->get();
        return $this->collection($results);
    }
    
    /**
     * Find record or throw exception
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            throw new Exception("Model not found with ID: {$id}", 404);
        }
        return $result;
    }
    
    /**
     * Get all records
     */
    public function all()
    {
        $results = $this->db->table($this->table)->get();
        return $this->collection($results);
    }
    
    /**
     * Create new record
     */
    public function create($data)
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $id = $this->db->table($this->table)->insert($data);
        return $this->find($id);
    }
    
    /**
     * Update record
     */
    public function update($id, $data)
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->table($this->table)->where($this->primaryKey, $id)->update($data);
        return $this->find($id);
    }
    
    /**
     * Delete record
     */
    public function delete($id)
    {
        return $this->db->table($this->table)->where($this->primaryKey, $id)->delete();
    }
    
    /**
     * Start query builder
     */
    public function where($column, $operator = '=', $value = null)
    {
        return $this->db->table($this->table)->where($column, $operator, $value);
    }
    
    /**
     * OR WHERE
     */
    public function orWhere($column, $operator = '=', $value = null)
    {
        return $this->db->table($this->table)->orWhere($column, $operator, $value);
    }
    
    /**
     * WHERE IN
     */
    public function whereIn($column, $values)
    {
        return $this->db->table($this->table)->whereIn($column, $values);
    }
    
    /**
     * ORDER BY
     */
    public function orderBy($column, $direction = 'ASC')
    {
        return $this->db->table($this->table)->orderBy($column, $direction);
    }
    
    /**
     * LIMIT
     */
    public function limit($limit, $offset = null)
    {
        return $this->db->table($this->table)->limit($limit, $offset);
    }
    
    /**
     * Get query results
     */
    public function get()
    {
        $results = $this->db->table($this->table)->get();
        return $this->collection($results);
    }
    
    /**
     * Get first result
     */
    public function first()
    {
        $result = $this->db->table($this->table)->first();
        return $result ? $this->newInstance($result) : null;
    }
    
    /**
     * Get count
     */
    public function count()
    {
        return $this->db->table($this->table)->count();
    }
    
    /**
     * Paginate results
     */
    public function paginate($perPage = 15, $page = 1)
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count();
        
        $results = $this->db->table($this->table)->limit($perPage, $offset)->get();
        
        return [
            'data' => $this->collection($results),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Define hasOne relationship
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        $relatedModel = new $related();
        return $relatedModel->where($foreignKey, $this->{$localKey})->first();
    }
    
    /**
     * Define hasMany relationship
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        $relatedModel = new $related();
        return $relatedModel->where($foreignKey, $this->{$localKey})->get();
    }
    
    /**
     * Define belongsTo relationship
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $relatedModel = new $related();
        $foreignKey = $foreignKey ?: $relatedModel->getForeignKey();
        $ownerKey = $ownerKey ?: $relatedModel->primaryKey;
        
        return $relatedModel->where($ownerKey, $this->{$foreignKey})->first();
    }
    
    /**
     * Get foreign key name
     */
    protected function getForeignKey()
    {
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower($className) . '_id';
    }
    
    /**
     * Filter fillable attributes
     */
    protected function filterFillable($data)
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }
        
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }
        
        return $data;
    }
    
    /**
     * Create new model instance
     */
    protected function newInstance($data)
    {
        $instance = new static();
        foreach ($data as $key => $value) {
            $instance->{$key} = $value;
        }
        return $instance;
    }
    
    /**
     * Create collection of model instances
     */
    protected function collection($results)
    {
        return array_map(function($result) {
            return $this->newInstance($result);
        }, $results);
    }
    
    /**
     * Convert dates to proper format
     */
    protected function convertDates($data)
    {
        foreach ($this->dates as $dateField) {
            if (isset($data[$dateField]) && $data[$dateField]) {
                $data[$dateField] = date('Y-m-d H:i:s', strtotime($data[$dateField]));
            }
        }
        
        return $data;
    }
    
    /**
     * Apply query scopes
     */
    protected function applyGlobalScopes($query)
    {
        foreach ($this->globalScopes as $scope) {
            $query = $this->$scope($query);
        }
        
        return $query;
    }
    
    /**
     * Convert model to array
     */
    public function toArray()
    {
        $attributes = [];
        
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $name = $property->getName();
            $attributes[$name] = $this->{$name};
        }
        
        return $attributes;
    }
    
    /**
     * Convert model to JSON
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Magic getter for relationships
     */
    public function __get($name)
    {
        // Check for hasOne relationship
        if (isset($this->hasOne[$name])) {
            return $this->hasOne($this->hasOne[$name]['model'], 
                                $this->hasOne[$name]['foreign_key'] ?? null,
                                $this->hasOne[$name]['local_key'] ?? null);
        }
        
        // Check for hasMany relationship
        if (isset($this->hasMany[$name])) {
            return $this->hasMany($this->hasMany[$name]['model'], 
                                 $this->hasMany[$name]['foreign_key'] ?? null,
                                 $this->hasMany[$name]['local_key'] ?? null);
        }
        
        // Check for belongsTo relationship
        if (isset($this->belongsTo[$name])) {
            return $this->belongsTo($this->belongsTo[$name]['model'], 
                                   $this->belongsTo[$name]['foreign_key'] ?? null,
                                   $this->belongsTo[$name]['owner_key'] ?? null);
        }
        
        return null;
    }
    
    /**
     * Magic setter
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
    
    /**
     * Save model
     */
    public function save()
    {
        $data = $this->toArray();
        
        if (isset($this->{$this->primaryKey})) {
            // Update existing record
            return $this->update($this->{$this->primaryKey}, $data);
        } else {
            // Create new record
            $created = $this->create($data);
            $this->{$this->primaryKey} = $created->{$this->primaryKey};
            return $created;
        }
    }
}