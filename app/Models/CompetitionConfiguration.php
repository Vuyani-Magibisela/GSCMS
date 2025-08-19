<?php

namespace App\Models;

class CompetitionConfiguration extends BaseModel
{
    protected $table = 'competition_configurations';
    protected $softDeletes = true;
    
    protected $fillable = [
        'competition_id', 'config_key', 'config_value', 'config_type',
        'description', 'category', 'is_required', 'validation_rules',
        'default_value', 'last_modified_by'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at', 'last_modified_at'];
    
    // Validation rules
    protected $rules = [
        'competition_id' => 'required',
        'config_key' => 'required|max:100',
        'config_type' => 'required',
        'last_modified_by' => 'required'
    ];
    
    protected $messages = [
        'competition_id.required' => 'Competition is required.',
        'config_key.required' => 'Configuration key is required.',
        'config_type.required' => 'Configuration type is required.',
        'last_modified_by.required' => 'Last modified by is required.'
    ];
    
    // Configuration type constants
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_TEXT = 'text';
    
    protected $belongsTo = [
        'competition' => ['model' => CompetitionSetup::class, 'foreign_key' => 'competition_id'],
        'lastModifiedBy' => ['model' => User::class, 'foreign_key' => 'last_modified_by']
    ];

    /**
     * Get competition setup relation
     */
    public function competition()
    {
        return $this->belongsTo('App\\Models\\CompetitionSetup', 'competition_id');
    }
    
    /**
     * Get last modified by user relation
     */
    public function lastModifiedBy()
    {
        return $this->belongsTo('App\\Models\\User', 'last_modified_by');
    }
    
    /**
     * Get validation rules as array
     */
    public function getValidationRules()
    {
        if (!$this->validation_rules) {
            return [];
        }
        
        return json_decode($this->validation_rules, true) ?? [];
    }
    
    /**
     * Get typed configuration value
     */
    public function getTypedValue()
    {
        if ($this->config_value === null) {
            return $this->getTypedDefaultValue();
        }
        
        switch ($this->config_type) {
            case self::TYPE_INTEGER:
                return (int) $this->config_value;
            case self::TYPE_BOOLEAN:
                return filter_var($this->config_value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_JSON:
                return json_decode($this->config_value, true);
            case self::TYPE_TEXT:
            case self::TYPE_STRING:
            default:
                return $this->config_value;
        }
    }
    
    /**
     * Get typed default value
     */
    public function getTypedDefaultValue()
    {
        if ($this->default_value === null) {
            return null;
        }
        
        switch ($this->config_type) {
            case self::TYPE_INTEGER:
                return (int) $this->default_value;
            case self::TYPE_BOOLEAN:
                return filter_var($this->default_value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_JSON:
                return json_decode($this->default_value, true);
            case self::TYPE_TEXT:
            case self::TYPE_STRING:
            default:
                return $this->default_value;
        }
    }
    
    /**
     * Set typed configuration value
     */
    public function setTypedValue($value, $userId)
    {
        switch ($this->config_type) {
            case self::TYPE_INTEGER:
                $this->config_value = (string) (int) $value;
                break;
            case self::TYPE_BOOLEAN:
                $this->config_value = $value ? '1' : '0';
                break;
            case self::TYPE_JSON:
                $this->config_value = json_encode($value);
                break;
            case self::TYPE_TEXT:
            case self::TYPE_STRING:
            default:
                $this->config_value = (string) $value;
                break;
        }
        
        $this->last_modified_by = $userId;
        $this->last_modified_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }
    
    /**
     * Validate configuration value
     */
    public function validateValue($value)
    {
        $rules = $this->getValidationRules();
        $errors = [];
        
        // Type validation
        switch ($this->config_type) {
            case self::TYPE_INTEGER:
                if (!is_numeric($value)) {
                    $errors[] = 'Value must be a number.';
                }
                break;
            case self::TYPE_BOOLEAN:
                if (!in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'])) {
                    $errors[] = 'Value must be a boolean (true/false).';
                }
                break;
            case self::TYPE_JSON:
                if (json_decode($value) === null && json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'Value must be valid JSON.';
                }
                break;
        }
        
        // Custom validation rules
        foreach ($rules as $rule => $ruleValue) {
            switch ($rule) {
                case 'min':
                    if (is_numeric($value) && $value < $ruleValue) {
                        $errors[] = "Value must be at least {$ruleValue}.";
                    }
                    break;
                case 'max':
                    if (is_numeric($value) && $value > $ruleValue) {
                        $errors[] = "Value cannot exceed {$ruleValue}.";
                    }
                    break;
                case 'required':
                    if ($ruleValue && empty($value)) {
                        $errors[] = 'Value is required.';
                    }
                    break;
                case 'options':
                    if (!in_array($value, $ruleValue)) {
                        $errors[] = 'Value must be one of: ' . implode(', ', $ruleValue);
                    }
                    break;
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Get configurations by competition
     */
    public function getConfigurationsByCompetition($competitionId)
    {
        return $this->db->table($this->table)
            ->where('competition_id', $competitionId)
            ->whereNull('deleted_at')
            ->orderBy('category')
            ->orderBy('config_key')
            ->get();
    }
    
    /**
     * Get configurations by category
     */
    public function getConfigurationsByCategory($competitionId, $category)
    {
        return $this->db->table($this->table)
            ->where('competition_id', $competitionId)
            ->where('category', $category)
            ->whereNull('deleted_at')
            ->orderBy('config_key')
            ->get();
    }
    
    /**
     * Get configuration value by key
     */
    public function getConfigurationValue($competitionId, $configKey)
    {
        $config = $this->db->table($this->table)
            ->where('competition_id', $competitionId)
            ->where('config_key', $configKey)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$config) {
            return null;
        }
        
        // Create temporary instance to use getTypedValue method
        $tempConfig = new self();
        $tempConfig->config_value = $config['config_value'];
        $tempConfig->config_type = $config['config_type'];
        $tempConfig->default_value = $config['default_value'];
        
        return $tempConfig->getTypedValue();
    }
    
    /**
     * Set configuration value by key
     */
    public function setConfigurationValue($competitionId, $configKey, $value, $userId)
    {
        $config = $this->where('competition_id', $competitionId)
                      ->where('config_key', $configKey)
                      ->first();
        
        if (!$config) {
            return false;
        }
        
        return $config->setTypedValue($value, $userId);
    }
    
    /**
     * Create default configurations for competition
     */
    public function createDefaultConfigurations($competitionId, $userId)
    {
        $defaultConfigs = $this->getDefaultConfigurationSet();
        
        foreach ($defaultConfigs as $config) {
            $newConfig = new self();
            $newConfig->competition_id = $competitionId;
            $newConfig->config_key = $config['key'];
            $newConfig->config_value = $config['default_value'];
            $newConfig->config_type = $config['type'];
            $newConfig->description = $config['description'];
            $newConfig->category = $config['category'];
            $newConfig->is_required = $config['is_required'];
            $newConfig->validation_rules = json_encode($config['validation_rules']);
            $newConfig->default_value = $config['default_value'];
            $newConfig->last_modified_by = $userId;
            $newConfig->save();
        }
        
        return true;
    }
    
    /**
     * Get default configuration set
     */
    private function getDefaultConfigurationSet()
    {
        return [
            [
                'key' => 'registration_auto_approval',
                'default_value' => '0',
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Automatically approve team registrations',
                'category' => 'registration',
                'is_required' => true,
                'validation_rules' => ['required' => true]
            ],
            [
                'key' => 'max_participants_per_team',
                'default_value' => '4',
                'type' => self::TYPE_INTEGER,
                'description' => 'Maximum number of participants per team',
                'category' => 'registration',
                'is_required' => true,
                'validation_rules' => ['required' => true, 'min' => 1, 'max' => 10]
            ],
            [
                'key' => 'allow_late_registrations',
                'default_value' => '0',
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Allow registrations after deadline',
                'category' => 'registration',
                'is_required' => false,
                'validation_rules' => []
            ],
            [
                'key' => 'competition_time_limit',
                'default_value' => '15',
                'type' => self::TYPE_INTEGER,
                'description' => 'Default competition time limit in minutes',
                'category' => 'competition',
                'is_required' => true,
                'validation_rules' => ['required' => true, 'min' => 5, 'max' => 120]
            ],
            [
                'key' => 'max_attempts_per_team',
                'default_value' => '3',
                'type' => self::TYPE_INTEGER,
                'description' => 'Maximum attempts per team',
                'category' => 'competition',
                'is_required' => true,
                'validation_rules' => ['required' => true, 'min' => 1, 'max' => 10]
            ],
            [
                'key' => 'scoring_method',
                'default_value' => 'best_attempt',
                'type' => self::TYPE_STRING,
                'description' => 'Scoring method for multiple attempts',
                'category' => 'scoring',
                'is_required' => true,
                'validation_rules' => ['required' => true, 'options' => ['best_attempt', 'average_attempts', 'last_attempt']]
            ],
            [
                'key' => 'judge_training_required',
                'default_value' => '1',
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Require judge training completion',
                'category' => 'judging',
                'is_required' => false,
                'validation_rules' => []
            ],
            [
                'key' => 'safety_briefing_required',
                'default_value' => '1',
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Require safety briefing for all participants',
                'category' => 'safety',
                'is_required' => true,
                'validation_rules' => ['required' => true]
            ],
            [
                'key' => 'venue_capacity_limit',
                'default_value' => '200',
                'type' => self::TYPE_INTEGER,
                'description' => 'Maximum venue capacity',
                'category' => 'venue',
                'is_required' => false,
                'validation_rules' => ['min' => 1]
            ],
            [
                'key' => 'communication_preferences',
                'default_value' => '{"email": true, "sms": false, "system_notifications": true}',
                'type' => self::TYPE_JSON,
                'description' => 'Communication preferences for participants',
                'category' => 'communication',
                'is_required' => false,
                'validation_rules' => []
            ]
        ];
    }
    
    /**
     * Get available configuration types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_STRING => 'String',
            self::TYPE_INTEGER => 'Integer',
            self::TYPE_BOOLEAN => 'Boolean',
            self::TYPE_JSON => 'JSON',
            self::TYPE_TEXT => 'Text'
        ];
    }
    
    /**
     * Get configuration categories
     */
    public static function getConfigurationCategories()
    {
        return [
            'registration' => 'Registration Settings',
            'competition' => 'Competition Rules',
            'scoring' => 'Scoring Configuration',
            'judging' => 'Judging Requirements',
            'safety' => 'Safety Protocols',
            'venue' => 'Venue Management',
            'communication' => 'Communication Settings',
            'equipment' => 'Equipment Management',
            'reporting' => 'Reporting Settings'
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['validation_rules_parsed'] = $this->getValidationRules();
        $attributes['typed_value'] = $this->getTypedValue();
        $attributes['typed_default_value'] = $this->getTypedDefaultValue();
        $attributes['type_label'] = self::getAvailableTypes()[$this->config_type] ?? $this->config_type;
        $attributes['category_label'] = self::getConfigurationCategories()[$this->category] ?? $this->category;
        
        return $attributes;
    }
}