<?php

namespace App\Models;

class Contact extends BaseModel
{
    protected $table = 'contacts';
    protected $fillable = [
        'school_id', 'contact_type', 'title', 'first_name', 'last_name', 
        'position', 'email', 'phone', 'mobile', 'fax', 'address', 
        'is_primary', 'is_emergency', 'language_preference', 
        'communication_preference', 'status', 'notes'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $softDeletes = true;
    
    // Validation rules
    protected $rules = [
        'school_id' => 'required|exists:schools,id',
        'contact_type' => 'required',
        'first_name' => 'required|max:100',
        'last_name' => 'required|max:100',
        'position' => 'required|max:100',
        'email' => 'required|email|max:255',
        'phone' => 'max:20',
        'mobile' => 'max:20',
        'status' => 'required'
    ];
    
    protected $messages = [
        'school_id.required' => 'School is required.',
        'school_id.exists' => 'Selected school does not exist.',
        'contact_type.required' => 'Contact type is required.',
        'first_name.required' => 'First name is required.',
        'last_name.required' => 'Last name is required.',
        'position.required' => 'Position is required.',
        'email.required' => 'Email address is required.',
        'email.email' => 'Please provide a valid email address.',
        'status.required' => 'Contact status is required.'
    ];
    
    // Contact type constants
    const TYPE_PRINCIPAL = 'principal';
    const TYPE_COORDINATOR = 'coordinator';
    const TYPE_DEPUTY = 'deputy';
    const TYPE_ADMIN = 'administrative';
    const TYPE_IT = 'it_coordinator';
    const TYPE_SECURITY = 'security';
    const TYPE_FACILITIES = 'facilities';
    const TYPE_MEDICAL = 'medical';
    const TYPE_OTHER = 'other';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    
    // Communication preference constants
    const COMM_EMAIL = 'email';
    const COMM_PHONE = 'phone';
    const COMM_SMS = 'sms';
    const COMM_WHATSAPP = 'whatsapp';
    
    // Language preference constants
    const LANG_ENGLISH = 'english';
    const LANG_AFRIKAANS = 'afrikaans';
    const LANG_ZULU = 'zulu';
    const LANG_XHOSA = 'xhosa';
    const LANG_SOTHO = 'sotho';
    const LANG_TSWANA = 'tswana';
    const LANG_PEDI = 'pedi';
    const LANG_VENDA = 'venda';
    const LANG_TSONGA = 'tsonga';
    const LANG_NDEBELE = 'ndebele';
    const LANG_SWATI = 'swati';
    
    /**
     * Relationship: Contact belongs to a school
     */
    public function school()
    {
        return $this->belongsTo('App\Models\School', 'school_id', 'id');
    }
    
    /**
     * Get full name
     */
    public function getFullName()
    {
        $name = trim($this->first_name . ' ' . $this->last_name);
        return $this->title ? $this->title . ' ' . $name : $name;
    }
    
    /**
     * Get formatted name with position
     */
    public function getNameWithPosition()
    {
        return $this->getFullName() . ' (' . $this->position . ')';
    }
    
    /**
     * Check if this is the primary contact for the school
     */
    public function isPrimary()
    {
        return $this->is_primary == 1;
    }
    
    /**
     * Check if this is an emergency contact
     */
    public function isEmergency()
    {
        return $this->is_emergency == 1;
    }
    
    /**
     * Get contacts by school
     */
    public static function getBySchool($schoolId, $activeOnly = true)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('school_id', $schoolId);
        
        if ($activeOnly) {
            $query->where('status', self::STATUS_ACTIVE);
        }
        
        $query->whereNull('deleted_at')
              ->orderBy('is_primary', 'DESC')
              ->orderBy('contact_type')
              ->orderBy('last_name');
        
        $results = $query->get();
        return $model->collection($results);
    }
    
    /**
     * Get primary contact for school
     */
    public static function getPrimaryContact($schoolId)
    {
        $model = new static();
        $result = $model->db->table($model->table)
            ->where('school_id', $schoolId)
            ->where('is_primary', 1)
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->first();
        
        return $result ? $model->newInstance($result) : null;
    }
    
    /**
     * Get emergency contacts for school
     */
    public static function getEmergencyContacts($schoolId)
    {
        $model = new static();
        $results = $model->db->table($model->table)
            ->where('school_id', $schoolId)
            ->where('is_emergency', 1)
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->orderBy('contact_type')
            ->get();
        
        return $model->collection($results);
    }
    
    /**
     * Get contacts by type
     */
    public static function getByType($schoolId, $contactType)
    {
        $model = new static();
        $results = $model->db->table($model->table)
            ->where('school_id', $schoolId)
            ->where('contact_type', $contactType)
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->orderBy('is_primary', 'DESC')
            ->get();
        
        return $model->collection($results);
    }
    
    /**
     * Search contacts
     */
    public static function search($criteria = [])
    {
        $model = new static();
        $query = $model->db->table($model->table);
        
        // Apply soft delete filter
        $query->whereNull('deleted_at');
        
        if (!empty($criteria['school_id'])) {
            $query->where('school_id', $criteria['school_id']);
        }
        
        if (!empty($criteria['contact_type'])) {
            $query->where('contact_type', $criteria['contact_type']);
        }
        
        if (!empty($criteria['name'])) {
            $query->where(function($q) use ($criteria) {
                $q->where('first_name', 'LIKE', '%' . $criteria['name'] . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $criteria['name'] . '%');
            });
        }
        
        if (!empty($criteria['email'])) {
            $query->where('email', 'LIKE', '%' . $criteria['email'] . '%');
        }
        
        if (!empty($criteria['phone'])) {
            $query->where(function($q) use ($criteria) {
                $q->where('phone', 'LIKE', '%' . $criteria['phone'] . '%')
                  ->orWhere('mobile', 'LIKE', '%' . $criteria['phone'] . '%');
            });
        }
        
        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }
        
        if (isset($criteria['is_primary'])) {
            $query->where('is_primary', $criteria['is_primary']);
        }
        
        if (isset($criteria['is_emergency'])) {
            $query->where('is_emergency', $criteria['is_emergency']);
        }
        
        $results = $query->orderBy('school_id')
                        ->orderBy('is_primary', 'DESC')
                        ->orderBy('contact_type')
                        ->orderBy('last_name')
                        ->get();
        
        return $model->collection($results);
    }
    
    /**
     * Get available contact types
     */
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_PRINCIPAL => 'Principal',
            self::TYPE_COORDINATOR => 'SciBOTICS Coordinator',
            self::TYPE_DEPUTY => 'Deputy Principal',
            self::TYPE_ADMIN => 'Administrative Assistant',
            self::TYPE_IT => 'IT Coordinator',
            self::TYPE_SECURITY => 'Security Personnel',
            self::TYPE_FACILITIES => 'Facilities Manager',
            self::TYPE_MEDICAL => 'Medical Personnel',
            self::TYPE_OTHER => 'Other'
        ];
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
     * Get communication preferences
     */
    public static function getCommunicationPreferences()
    {
        return [
            self::COMM_EMAIL => 'Email',
            self::COMM_PHONE => 'Phone',
            self::COMM_SMS => 'SMS',
            self::COMM_WHATSAPP => 'WhatsApp'
        ];
    }
    
    /**
     * Get language preferences
     */
    public static function getLanguagePreferences()
    {
        return [
            self::LANG_ENGLISH => 'English',
            self::LANG_AFRIKAANS => 'Afrikaans',
            self::LANG_ZULU => 'isiZulu',
            self::LANG_XHOSA => 'isiXhosa',
            self::LANG_SOTHO => 'Sesotho',
            self::LANG_TSWANA => 'Setswana',
            self::LANG_PEDI => 'Sepedi',
            self::LANG_VENDA => 'Tshivenda',
            self::LANG_TSONGA => 'Xitsonga',
            self::LANG_NDEBELE => 'isiNdebele',
            self::LANG_SWATI => 'siSwati'
        ];
    }
    
    /**
     * Validate email deliverability (basic check)
     */
    public function validateEmail()
    {
        if (!$this->email) {
            return ['valid' => false, 'message' => 'No email address provided'];
        }
        
        // Basic email format validation
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        
        // Check for disposable email domains (basic list)
        $disposableDomains = ['10minutemail.com', 'guerrillamail.com', 'tempmail.org'];
        $domain = substr(strrchr($this->email, "@"), 1);
        
        if (in_array(strtolower($domain), $disposableDomains)) {
            return ['valid' => false, 'message' => 'Disposable email addresses are not allowed'];
        }
        
        return ['valid' => true, 'message' => 'Email appears valid'];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['full_name'] = $this->getFullName();
        $attributes['name_with_position'] = $this->getNameWithPosition();
        $attributes['type_label'] = self::getAvailableTypes()[$this->contact_type] ?? $this->contact_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['communication_preference_label'] = self::getCommunicationPreferences()[$this->communication_preference] ?? $this->communication_preference;
        $attributes['language_preference_label'] = self::getLanguagePreferences()[$this->language_preference] ?? $this->language_preference;
        
        return $attributes;
    }
}