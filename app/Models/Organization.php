<?php
// app/Models/Organization.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class Organization extends BaseModel
{
    protected $table = 'organizations';
    
    protected $fillable = [
        'organization_name', 'organization_type', 'contact_person',
        'contact_email', 'contact_phone', 'address', 'website',
        'partnership_status', 'judges_provided'
    ];
    
    protected $rules = [
        'organization_name' => 'required|max:200',
        'organization_type' => 'required|in:educational,corporate,government,ngo,other',
        'contact_person' => 'required|max:100',
        'contact_email' => 'required|email|max:100',
        'contact_phone' => 'required|max:20',
        'partnership_status' => 'in:active,pending,inactive'
    ];
    
    // Organization type constants
    const TYPE_EDUCATIONAL = 'educational';
    const TYPE_CORPORATE = 'corporate';
    const TYPE_GOVERNMENT = 'government';
    const TYPE_NGO = 'ngo';
    const TYPE_OTHER = 'other';
    
    // Partnership status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_INACTIVE = 'inactive';
    
    /**
     * Get all judges associated with this organization
     */
    public function getJudges()
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email, u.status as user_status
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE jp.organization_id = ?
            ORDER BY u.last_name, u.first_name
        ", [$this->id]);
    }
    
    /**
     * Get active judges count
     */
    public function getActiveJudgesCount()
    {
        $db = Database::getInstance();
        $result = $db->query("
            SELECT COUNT(*) as count
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE jp.organization_id = ? 
            AND jp.status = 'active'
            AND u.status = 'active'
        ", [$this->id]);
        
        return $result[0]['count'] ?? 0;
    }
    
    /**
     * Get organization performance statistics
     */
    public function getPerformanceStats()
    {
        $db = Database::getInstance();
        
        $stats = $db->query("
            SELECT 
                COUNT(DISTINCT jp.id) as total_judges,
                COUNT(DISTINCT CASE WHEN jp.status = 'active' THEN jp.id END) as active_judges,
                COUNT(DISTINCT jca.id) as total_assignments,
                COUNT(DISTINCT CASE WHEN jca.assignment_status = 'completed' THEN jca.id END) as completed_assignments,
                AVG(jca.performance_rating) as avg_performance_rating,
                COUNT(DISTINCT jpm.id) as performance_records
            FROM judge_profiles jp
            LEFT JOIN judge_competition_assignments jca ON jp.id = jca.judge_id
            LEFT JOIN judge_performance_metrics jpm ON jp.id = jpm.judge_id
            WHERE jp.organization_id = ?
        ", [$this->id]);
        
        return $stats[0] ?? [];
    }
    
    /**
     * Create a new organization with validation
     */
    public static function createOrganization($data)
    {
        $organization = new self();
        
        // Validate data
        $validation = $organization->validate($data);
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        // Check for duplicate organization name
        $existing = $db->query("SELECT id FROM organizations WHERE organization_name = ?", [$data['organization_name']]);
        if (!empty($existing)) {
            throw new \Exception('Organization with this name already exists');
        }
        
        $organizationData = [
            'organization_name' => $data['organization_name'],
            'organization_type' => $data['organization_type'],
            'contact_person' => $data['contact_person'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'],
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'partnership_status' => $data['partnership_status'] ?? self::STATUS_PENDING
        ];
        
        $organizationId = $db->insert('organizations', $organizationData);
        return $organization->find($organizationId);
    }
    
    /**
     * Update organization judges count
     */
    public function updateJudgesCount()
    {
        $activeCount = $this->getActiveJudgesCount();
        
        $db = Database::getInstance();
        $db->query("UPDATE organizations SET judges_provided = ? WHERE id = ?", [$activeCount, $this->id]);
        
        $this->judges_provided = $activeCount;
        return $this;
    }
    
    /**
     * Get available organization types
     */
    public static function getOrganizationTypes()
    {
        return [
            self::TYPE_EDUCATIONAL => 'Educational Institution',
            self::TYPE_CORPORATE => 'Corporate/Industry',
            self::TYPE_GOVERNMENT => 'Government Agency',
            self::TYPE_NGO => 'Non-Governmental Organization',
            self::TYPE_OTHER => 'Other'
        ];
    }
    
    /**
     * Get available partnership statuses
     */
    public static function getPartnershipStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active Partner',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_INACTIVE => 'Inactive'
        ];
    }
    
    /**
     * Scope: Active organizations
     */
    public function scopeActive($query)
    {
        return $query->where('partnership_status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope: Organizations by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('organization_type', $type);
    }
    
    /**
     * Get organization contact information
     */
    public function getContactInfo()
    {
        return [
            'person' => $this->contact_person,
            'email' => $this->contact_email,
            'phone' => $this->contact_phone,
            'address' => $this->address
        ];
    }
    
    /**
     * Check if organization is active
     */
    public function isActive()
    {
        return $this->partnership_status === self::STATUS_ACTIVE;
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['is_active'] = $this->isActive();
        $attributes['active_judges_count'] = $this->getActiveJudgesCount();
        $attributes['type_label'] = self::getOrganizationTypes()[$this->organization_type] ?? $this->organization_type;
        $attributes['status_label'] = self::getPartnershipStatuses()[$this->partnership_status] ?? $this->partnership_status;
        $attributes['contact_info'] = $this->getContactInfo();
        
        return $attributes;
    }
}