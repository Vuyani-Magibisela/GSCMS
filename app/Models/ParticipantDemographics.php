<?php

namespace App\Models;

use App\Models\BaseModel;
use PDO;

/**
 * Participant Demographics Model
 * POPIA-compliant demographic data tracking
 */
class ParticipantDemographics extends BaseModel
{
    protected $table = 'participant_demographics';
    protected $fillable = [
        'participant_id',
        'data_category',
        'data_key',
        'data_value_encrypted',
        'data_type',
        'consent_given',
        'consent_date',
        'consent_by_user_id',
        'collection_date',
        'expires_at',
        'data_source',
        'verification_status',
        'access_level',
        'anonymized'
    ];
    
    protected $casts = [
        'consent_given' => 'boolean',
        'consent_date' => 'datetime',
        'collection_date' => 'datetime',
        'expires_at' => 'datetime',
        'anonymized' => 'boolean'
    ];
    
    /**
     * Valid data categories
     */
    const DATA_CATEGORIES = [
        'personal' => 'Personal Information',
        'educational' => 'Educational Information',
        'socioeconomic' => 'Socioeconomic Information',
        'accessibility' => 'Accessibility Needs',
        'support_needs' => 'Support Requirements'
    ];
    
    /**
     * Valid data types
     */
    const DATA_TYPES = [
        'string' => 'String',
        'number' => 'Number',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'json' => 'JSON Object'
    ];
    
    /**
     * Valid data sources
     */
    const DATA_SOURCES = [
        'registration' => 'Registration Form',
        'import' => 'Data Import',
        'manual' => 'Manual Entry',
        'survey' => 'Survey Response'
    ];
    
    /**
     * Valid verification statuses
     */
    const VERIFICATION_STATUSES = [
        'unverified' => 'Unverified',
        'verified' => 'Verified',
        'disputed' => 'Disputed',
        'corrected' => 'Corrected'
    ];
    
    /**
     * Valid access levels
     */
    const ACCESS_LEVELS = [
        'public' => 'Public',
        'internal' => 'Internal Use',
        'restricted' => 'Restricted Access',
        'confidential' => 'Confidential'
    ];
    
    /**
     * Encryption key for data (should be in environment variables)
     */
    private static $encryptionKey = null;
    
    /**
     * Get participant associated with this demographic data
     */
    public function participant()
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }
    
    /**
     * Get user who gave consent
     */
    public function consentUser()
    {
        return $this->belongsTo(User::class, 'consent_by_user_id');
    }
    
    /**
     * Get encryption key
     */
    private static function getEncryptionKey()
    {
        if (self::$encryptionKey === null) {
            // In production, this should come from environment variables
            self::$encryptionKey = $_ENV['DEMOGRAPHICS_ENCRYPTION_KEY'] ?? 'default-key-change-in-production';
        }
        
        return self::$encryptionKey;
    }
    
    /**
     * Encrypt sensitive data
     */
    private function encryptData($data)
    {
        $key = self::getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    private function decryptData($encryptedData)
    {
        if (!$encryptedData) {
            return null;
        }
        
        $key = self::getEncryptionKey();
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Set demographic data with encryption
     */
    public function setValue($value)
    {
        $this->data_value_encrypted = $this->encryptData((string)$value);
        return $this;
    }
    
    /**
     * Get decrypted demographic data
     */
    public function getValue()
    {
        $decrypted = $this->decryptData($this->data_value_encrypted);
        
        // Convert to appropriate type
        switch ($this->data_type) {
            case 'number':
                return is_numeric($decrypted) ? (float)$decrypted : $decrypted;
            case 'boolean':
                return filter_var($decrypted, FILTER_VALIDATE_BOOLEAN);
            case 'date':
                return $decrypted ? date('Y-m-d', strtotime($decrypted)) : null;
            case 'json':
                return json_decode($decrypted, true);
            default:
                return $decrypted;
        }
    }
    
    /**
     * Store demographic data with consent validation
     */
    public static function storeDemographicData($participantId, $category, $key, $value, $dataType = 'string', $consentUserId = null, $dataSource = 'registration')
    {
        // Validate category and data type
        if (!array_key_exists($category, self::DATA_CATEGORIES)) {
            throw new \Exception('Invalid data category');
        }
        
        if (!array_key_exists($dataType, self::DATA_TYPES)) {
            throw new \Exception('Invalid data type');
        }
        
        // Check for existing record
        $existing = static::where('participant_id', $participantId)
                         ->where('data_category', $category)
                         ->where('data_key', $key)
                         ->first();
        
        if ($existing) {
            // Update existing record
            $demographic = $existing;
        } else {
            // Create new record
            $demographic = new static([
                'participant_id' => $participantId,
                'data_category' => $category,
                'data_key' => $key,
                'data_type' => $dataType,
                'data_source' => $dataSource,
                'collection_date' => date('Y-m-d H:i:s'),
                'verification_status' => 'unverified',
                'access_level' => 'internal',
                'anonymized' => false
            ]);
        }
        
        // Set the encrypted value
        $demographic->setValue($value);
        
        // Handle consent
        if ($consentUserId) {
            $demographic->consent_given = true;
            $demographic->consent_date = date('Y-m-d H:i:s');
            $demographic->consent_by_user_id = $consentUserId;
        }
        
        // Set expiry based on data category (POPIA compliance)
        $demographic->setDataExpiry();
        
        $demographic->save();
        
        return $demographic;
    }
    
    /**
     * Set data expiry based on POPIA requirements
     */
    private function setDataExpiry()
    {
        // Set different retention periods based on data category
        switch ($this->data_category) {
            case 'personal':
                // Personal data: 5 years after last activity
                $this->expires_at = date('Y-m-d H:i:s', strtotime('+5 years'));
                break;
            case 'educational':
                // Educational data: 7 years (standard academic record retention)
                $this->expires_at = date('Y-m-d H:i:s', strtotime('+7 years'));
                break;
            case 'socioeconomic':
                // Socioeconomic data: 3 years (for program improvement analysis)
                $this->expires_at = date('Y-m-d H:i:s', strtotime('+3 years'));
                break;
            case 'accessibility':
                // Accessibility needs: 10 years (ongoing support needs)
                $this->expires_at = date('Y-m-d H:i:s', strtotime('+10 years'));
                break;
            case 'support_needs':
                // Support needs: 5 years
                $this->expires_at = date('Y-m-d H:i:s', strtotime('+5 years'));
                break;
        }
    }
    
    /**
     * Get demographic data for participant
     */
    public static function getForParticipant($participantId, $category = null, $includeExpired = false)
    {
        $query = static::where('participant_id', $participantId);
        
        if ($category) {
            $query->where('data_category', $category);
        }
        
        if (!$includeExpired) {
            $query->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            });
        }
        
        return $query->get();
    }
    
    /**
     * Get anonymized demographic data for analytics
     */
    public static function getAnonymizedData($filters = [])
    {
        $query = static::where('anonymized', false)
                      ->where('access_level', '!=', 'confidential');
        
        // Apply filters
        if (isset($filters['category'])) {
            $query->where('data_category', $filters['category']);
        }
        
        if (isset($filters['data_key'])) {
            $query->where('data_key', $filters['data_key']);
        }
        
        $demographics = $query->get();
        $anonymizedData = [];
        
        foreach ($demographics as $demographic) {
            $value = $demographic->getValue();
            
            // Create anonymized record
            $anonymizedData[] = [
                'category' => $demographic->data_category,
                'key' => $demographic->data_key,
                'value' => $value,
                'data_type' => $demographic->data_type,
                'collection_date' => $demographic->collection_date->format('Y-m'),  // Month only
                'data_source' => $demographic->data_source
            ];
        }
        
        return $anonymizedData;
    }
    
    /**
     * Update consent status
     */
    public function updateConsent($consentGiven, $consentUserId = null)
    {
        $this->consent_given = $consentGiven;
        
        if ($consentGiven && $consentUserId) {
            $this->consent_date = date('Y-m-d H:i:s');
            $this->consent_by_user_id = $consentUserId;
        } elseif (!$consentGiven) {
            $this->consent_date = null;
            $this->consent_by_user_id = null;
        }
        
        $this->save();
        
        return $this;
    }
    
    /**
     * Verify demographic data
     */
    public function verify($verifiedBy = null)
    {
        $this->verification_status = 'verified';
        $this->save();
        
        return $this;
    }
    
    /**
     * Anonymize demographic data
     */
    public function anonymize()
    {
        // Mark as anonymized but keep encrypted data for compliance
        $this->anonymized = true;
        $this->participant_id = null;  // Remove personal identifier
        $this->consent_by_user_id = null;
        $this->save();
        
        return $this;
    }
    
    /**
     * Delete expired data (POPIA compliance)
     */
    public static function deleteExpiredData()
    {
        $expiredRecords = static::where('expires_at', '<', date('Y-m-d H:i:s'))
                               ->where('expires_at', '!=', null)
                               ->get();
        
        $deletedCount = 0;
        
        foreach ($expiredRecords as $record) {
            // Log the deletion for audit purposes
            error_log("Deleting expired demographic data: ID {$record->id}, Category: {$record->data_category}");
            
            $record->delete();
            $deletedCount++;
        }
        
        return $deletedCount;
    }
    
    /**
     * Get consent status summary for participant
     */
    public static function getConsentSummary($participantId)
    {
        $demographics = static::where('participant_id', $participantId)->get();
        
        $summary = [
            'total_records' => $demographics->count(),
            'with_consent' => $demographics->where('consent_given', true)->count(),
            'without_consent' => $demographics->where('consent_given', false)->count(),
            'by_category' => []
        ];
        
        foreach (self::DATA_CATEGORIES as $key => $name) {
            $categoryData = $demographics->where('data_category', $key);
            $summary['by_category'][$key] = [
                'name' => $name,
                'total' => $categoryData->count(),
                'with_consent' => $categoryData->where('consent_given', true)->count(),
                'consent_rate' => $categoryData->count() > 0 ? 
                    round(($categoryData->where('consent_given', true)->count() / $categoryData->count()) * 100, 2) : 0
            ];
        }
        
        return $summary;
    }
    
    /**
     * Bulk collect standard demographic data
     */
    public static function collectStandardDemographics($participantId, $demographicData, $consentUserId = null, $dataSource = 'registration')
    {
        $collected = [];
        
        // Personal demographics
        $personalFields = [
            'gender' => 'string',
            'home_language' => 'string',
            'nationality' => 'string',
            'disability_status' => 'string'
        ];
        
        foreach ($personalFields as $key => $type) {
            if (isset($demographicData[$key])) {
                $collected[] = static::storeDemographicData(
                    $participantId, 
                    'personal', 
                    $key, 
                    $demographicData[$key], 
                    $type, 
                    $consentUserId, 
                    $dataSource
                );
            }
        }
        
        // Educational demographics
        $educationalFields = [
            'mathematics_level' => 'string',
            'science_level' => 'string',
            'coding_experience' => 'string',
            'robotics_experience' => 'string',
            'school_quintile' => 'number',
            'school_type' => 'string'
        ];
        
        foreach ($educationalFields as $key => $type) {
            if (isset($demographicData[$key])) {
                $collected[] = static::storeDemographicData(
                    $participantId, 
                    'educational', 
                    $key, 
                    $demographicData[$key], 
                    $type, 
                    $consentUserId, 
                    $dataSource
                );
            }
        }
        
        // Socioeconomic demographics
        $socioeconomicFields = [
            'guardian_education' => 'string',
            'technology_access' => 'string',
            'transport_method' => 'string',
            'language_support' => 'string'
        ];
        
        foreach ($socioeconomicFields as $key => $type) {
            if (isset($demographicData[$key])) {
                $collected[] = static::storeDemographicData(
                    $participantId, 
                    'socioeconomic', 
                    $key, 
                    $demographicData[$key], 
                    $type, 
                    $consentUserId, 
                    $dataSource
                );
            }
        }
        
        return $collected;
    }
    
    /**
     * Generate demographic report with privacy protection
     */
    public static function generateReport($filters = [])
    {
        $anonymizedData = static::getAnonymizedData($filters);
        
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_records' => count($anonymizedData),
            'categories' => [],
            'summary_statistics' => []
        ];
        
        // Group by category
        $grouped = [];
        foreach ($anonymizedData as $record) {
            $grouped[$record['category']][] = $record;
        }
        
        foreach ($grouped as $category => $records) {
            $report['categories'][$category] = [
                'name' => self::DATA_CATEGORIES[$category],
                'record_count' => count($records),
                'data_points' => array_unique(array_column($records, 'key'))
            ];
        }
        
        return $report;
    }
}