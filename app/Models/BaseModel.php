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
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $timestamps = true;
    protected $softDeletes = false;
    
    // Validation rules
    protected $rules = [];
    protected $messages = [];
    
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
     * Find record by ID (excluding soft deleted)
     */
    public function find($id)
    {
        $query = $this->db->table($this->table)->where($this->primaryKey, $id);
        if ($this->softDeletes && $this->hasDeletedAtColumn()) {
            $query->whereNull('deleted_at');
        }
        $result = $query->first();
        return $result ? $this->newInstance($result) : null;
    }
    
    /**
     * Find record by ID including soft deleted
     */
    public function findWithTrashed($id)
    {
        $result = $this->db->table($this->table)->where($this->primaryKey, $id)->first();
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
     * Get all records (excluding soft deleted)
     */
    public function all()
    {
        $query = $this->db->table($this->table);
        if ($this->softDeletes && $this->hasDeletedAtColumn()) {
            $query->whereNull('deleted_at');
        }
        $results = $query->get();
        return $this->collection($results);
    }
    
    /**
     * Get all records including soft deleted
     */
    public function withTrashed()
    {
        $results = $this->db->table($this->table)->get();
        return $this->collection($results);
    }
    
    /**
     * Get only soft deleted records
     */
    public function onlyTrashed()
    {
        if (!$this->softDeletes || !$this->hasDeletedAtColumn()) {
            return [];
        }
        $results = $this->db->table($this->table)
            ->whereNotNull('deleted_at')
            ->get();
        return $this->collection($results);
    }
    
    /**
     * Create new record
     */
    public function create($data)
    {
        // Validate data
        if (!$this->validate($data)) {
            return false;
        }
        
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
        // Validate data for update
        if (!$this->validate($data, $id)) {
            return false;
        }
        
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $query = $this->db->table($this->table)->where($this->primaryKey, $id);
        if ($this->softDeletes && $this->hasDeletedAtColumn()) {
            $query->whereNull('deleted_at');
        }
        $query->update($data);
        return $this->find($id);
    }
    
    /**
     * Delete record (soft delete if enabled)
     */
    public function delete($id)
    {
        if ($this->softDeletes && $this->hasDeletedAtColumn()) {
            return $this->db->table($this->table)
                ->where($this->primaryKey, $id)
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
        return $this->db->table($this->table)->where($this->primaryKey, $id)->delete();
    }
    
    /**
     * Force delete (permanently delete)
     */
    public function forceDelete($id)
    {
        return $this->db->table($this->table)->where($this->primaryKey, $id)->delete();
    }
    
    /**
     * Restore soft deleted record
     */
    public function restore($id)
    {
        if ($this->softDeletes && $this->hasDeletedAtColumn()) {
            return $this->db->table($this->table)
                ->where($this->primaryKey, $id)
                ->update(['deleted_at' => null]);
        }
        return false;
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
     * Check if table has deleted_at column
     */
    protected function hasDeletedAtColumn()
    {
        static $checkedTables = [];
        
        if (!isset($checkedTables[$this->table])) {
            try {
                $this->db->query("SELECT deleted_at FROM {$this->table} LIMIT 1");
                $checkedTables[$this->table] = true;
            } catch (\Exception $e) {
                $checkedTables[$this->table] = false;
            }
        }
        
        return $checkedTables[$this->table];
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
     * Validate data against rules
     */
    public function validate($data, $id = null)
    {
        if (empty($this->rules)) {
            return true;
        }
        
        $errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $rules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($rules as $rule) {
                $error = $this->validateRule($field, $data[$field] ?? null, $rule, $data, $id);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->validationErrors = $errors;
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate individual rule
     */
    protected function validateRule($field, $value, $rule, $data, $id = null)
    {
        if (strpos($rule, ':') !== false) {
            list($rule, $parameter) = explode(':', $rule, 2);
        } else {
            $parameter = null;
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    return $this->getValidationMessage($field, $rule, "The {$field} field is required.");
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->getValidationMessage($field, $rule, "The {$field} must be a valid email address.");
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $parameter) {
                    return $this->getValidationMessage($field, $rule, "The {$field} may not be greater than {$parameter} characters.");
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $parameter) {
                    return $this->getValidationMessage($field, $rule, "The {$field} must be at least {$parameter} characters.");
                }
                break;
                
            case 'unique':
                if (!empty($value)) {
                    $query = $this->db->table($this->table)->where($field, $value);
                    if ($id) {
                        $query->where($this->primaryKey, '!=', $id);
                    }
                    if ($this->softDeletes && $this->hasDeletedAtColumn()) {
                        $query->whereNull('deleted_at');
                    }
                    if ($query->first()) {
                        return $this->getValidationMessage($field, $rule, "The {$field} has already been taken.");
                    }
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Get validation message
     */
    protected function getValidationMessage($field, $rule, $default)
    {
        $key = "{$field}.{$rule}";
        return $this->messages[$key] ?? $this->messages[$rule] ?? $default;
    }
    
    /**
     * Get validation errors
     */
    public function getValidationErrors()
    {
        return $this->validationErrors ?? [];
    }
    
    /**
     * Query scope: active records
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Query scope: by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Query scope: recent records
     */
    public function scopeRecent($query, $days = 30)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $query->where('created_at', '>=', $date);
    }
    
    /**
     * Apply scope method
     */
    public function scope($name, ...$parameters)
    {
        $method = 'scope' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method($this->db->table($this->table), ...$parameters);
        }
        return $this->db->table($this->table);
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
            if ($created) {
                $this->{$this->primaryKey} = $created->{$this->primaryKey};
            }
            return $created;
        }
    }
    
    // ========================================================================
    // STATIC HELPER METHODS
    // ========================================================================
    
    /**
     * Static helper to get all records
     */
    public static function getAll()
    {
        $instance = new static();
        return $instance->all();
    }
    
    /**
     * Static helper to find record by ID
     */
    public static function findById($id)
    {
        $instance = new static();
        return $instance->find($id);
    }
    
    /**
     * Static helper to create record
     */
    public static function createRecord($data)
    {
        $instance = new static();
        return $instance->create($data);
    }
    
    /**
     * Alias for update method
     */
    public function updateById($id, $data)
    {
        return $this->update($id, $data);
    }
}