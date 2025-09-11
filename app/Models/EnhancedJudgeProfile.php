<?php
// app/Models/EnhancedJudgeProfile.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class EnhancedJudgeProfile extends BaseModel
{
    protected $table = 'judge_profiles';
    
    protected $fillable = [
        'user_id', 'judge_code', 'organization_id', 'judge_type',
        'expertise_areas', 'categories_qualified', 'experience_level',
        'years_experience', 'professional_title', 'professional_bio',
        'linkedin_profile', 'specialty_categories', 'max_assignments_per_day',
        'preferred_categories', 'availability_notes', 'bio', 'certifications',
        'languages_spoken', 'contact_preferences', 'emergency_contact',
        'dietary_restrictions', 'accessibility_needs', 'availability',
        'preferred_venues', 'special_requirements', 'status',
        'onboarding_completed', 'background_check_status', 'background_check_date'
    ];
    
    protected $rules = [
        'user_id' => 'required|numeric',
        'judge_type' => 'required|in:coordinator,adjudicator,technical,volunteer,industry',
        'experience_level' => 'in:novice,intermediate,advanced,expert',
        'years_experience' => 'numeric|min:0',
        'max_assignments_per_day' => 'numeric|min:1|max:20'
    ];
    
    // Judge type constants
    const TYPE_COORDINATOR = 'coordinator';
    const TYPE_ADJUDICATOR = 'adjudicator';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_VOLUNTEER = 'volunteer';
    const TYPE_INDUSTRY = 'industry';
    
    // Experience level constants
    const EXPERIENCE_NOVICE = 'novice';
    const EXPERIENCE_INTERMEDIATE = 'intermediate';
    const EXPERIENCE_ADVANCED = 'advanced';
    const EXPERIENCE_EXPERT = 'expert';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLACKLISTED = 'blacklisted';
    
    // Background check status constants
    const BG_CHECK_NOT_REQUIRED = 'not_required';
    const BG_CHECK_PENDING = 'pending';
    const BG_CHECK_CLEARED = 'cleared';
    const BG_CHECK_FAILED = 'failed';
    
    /**
     * Get associated user
     */
    public function getUser()
    {
        $db = Database::getInstance();
        $user = $db->query('SELECT * FROM users WHERE id = ?', [$this->user_id]);
        return !empty($user) ? $user[0] : null;
    }
    
    /**
     * Get associated organization
     */
    public function getOrganization()
    {
        if (!$this->organization_id) {
            return null;
        }
        
        return Organization::find($this->organization_id);
    }
    
    /**
     * Get expertise areas as array
     */
    public function getExpertiseAreas()
    {
        return $this->expertise_areas ? json_decode($this->expertise_areas, true) : [];
    }
    
    /**
     * Get qualified categories as array
     */
    public function getQualifiedCategories()
    {
        return $this->categories_qualified ? json_decode($this->categories_qualified, true) : [];
    }
    
    /**
     * Get availability as array
     */
    public function getAvailability()
    {
        return $this->availability ? json_decode($this->availability, true) : [];
    }
    
    /**
     * Get preferred venues as array
     */
    public function getPreferredVenues()
    {
        return $this->preferred_venues ? json_decode($this->preferred_venues, true) : [];
    }
    
    /**
     * Get contact preferences as array
     */
    public function getContactPreferences()
    {
        return $this->contact_preferences ? json_decode($this->contact_preferences, true) : [];
    }
    
    /**
     * Get certifications as array
     */
    public function getCertifications()
    {
        return $this->certifications ? json_decode($this->certifications, true) : [];
    }
    
    /**
     * Check if judge has expertise in specific area
     */
    public function hasExpertise($area)
    {
        $expertise = $this->getExpertiseAreas();
        return in_array($area, $expertise);
    }
    
    /**
     * Check if judge is qualified for specific category
     */
    public function isQualifiedForCategory($categoryId)
    {
        $qualified = $this->getQualifiedCategories();
        return in_array($categoryId, $qualified);
    }
    
    /**
     * Get current workload for a specific date
     */
    public function getCurrentWorkload($date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $db = Database::getInstance();
        $workload = $db->query("
            SELECT COUNT(*) as current_assignments
            FROM judge_competition_assignments jca
            WHERE jca.judge_id = ? 
            AND DATE(jca.session_date) = ?
            AND jca.assignment_status IN ('assigned', 'confirmed')
        ", [$this->id, $date]);
        
        return $workload[0]['current_assignments'] ?? 0;
    }
    
    /**
     * Check if judge is available for assignment on date
     */
    public function isAvailableForAssignment($date = null)
    {
        $currentLoad = $this->getCurrentWorkload($date);
        return $currentLoad < $this->max_assignments_per_day && $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * Get judge qualifications
     */
    public function getQualifications()
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT * FROM judge_qualifications 
            WHERE judge_id = ?
            ORDER BY issue_date DESC
        ", [$this->id]);
    }
    
    /**
     * Get active qualifications (not expired)
     */
    public function getActiveQualifications()
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT * FROM judge_qualifications 
            WHERE judge_id = ?
            AND (expiry_date IS NULL OR expiry_date > CURDATE())
            AND verified = 1
            ORDER BY issue_date DESC
        ", [$this->id]);
    }
    
    /**
     * Get training records
     */
    public function getTrainingRecords()
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT * FROM judge_training_records 
            WHERE judge_id = ?
            ORDER BY completion_date DESC
        ", [$this->id]);
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($competitionId = null)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT * FROM judge_performance_metrics 
            WHERE judge_id = ?
        ";
        $params = [$this->id];
        
        if ($competitionId) {
            $sql .= " AND competition_id = ?";
            $params[] = $competitionId;
        }
        
        $sql .= " ORDER BY calculated_at DESC";
        
        return $db->query($sql, $params);
    }
    
    /**
     * Get average performance score
     */
    public function getAveragePerformanceScore()
    {
        $db = Database::getInstance();
        $result = $db->query("
            SELECT AVG(
                (consistency_score + 
                 COALESCE(on_time_rate, 100) + 
                 COALESCE(completion_rate, 100) + 
                 COALESCE(peer_rating * 20, 80) + 
                 COALESCE(admin_rating * 20, 80)) / 5
            ) as avg_score
            FROM judge_performance_metrics 
            WHERE judge_id = ?
        ", [$this->id]);
        
        return round($result[0]['avg_score'] ?? 0, 1);
    }
    
    /**
     * Get recent feedback
     */
    public function getRecentFeedback($limit = 5)
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT jf.*, u.first_name, u.last_name
            FROM judge_feedback jf
            LEFT JOIN users u ON jf.feedback_from_id = u.id
            WHERE jf.judge_id = ?
            ORDER BY jf.created_at DESC
            LIMIT ?
        ", [$this->id, $limit]);
    }
    
    /**
     * Check if onboarding is complete
     */
    public function isOnboardingComplete()
    {
        return $this->onboarding_completed && 
               $this->background_check_status !== self::BG_CHECK_FAILED &&
               $this->status !== self::STATUS_PENDING;
    }
    
    /**
     * Generate unique judge code
     */
    public static function generateJudgeCode($judgeType)
    {
        $prefixes = [
            self::TYPE_COORDINATOR => 'CRD',
            self::TYPE_ADJUDICATOR => 'ADJ',
            self::TYPE_TECHNICAL => 'TCH',
            self::TYPE_VOLUNTEER => 'VOL',
            self::TYPE_INDUSTRY => 'IND'
        ];
        
        $prefix = $prefixes[$judgeType] ?? 'JDG';
        $year = date('Y');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $year . $random;
    }
    
    /**
     * Create a new judge profile
     */
    public static function createJudgeProfile($data)
    {
        $profile = new self();
        
        // Validate data
        $validation = $profile->validate($data);
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            // Generate judge code
            $judgeCode = self::generateJudgeCode($data['judge_type']);
            
            // Ensure judge code is unique
            while ($db->query("SELECT id FROM judge_profiles WHERE judge_code = ?", [$judgeCode])) {
                $judgeCode = self::generateJudgeCode($data['judge_type']);
            }
            
            $profileData = [
                'user_id' => $data['user_id'],
                'judge_code' => $judgeCode,
                'organization_id' => $data['organization_id'] ?? null,
                'judge_type' => $data['judge_type'],
                'expertise_areas' => isset($data['expertise_areas']) ? json_encode($data['expertise_areas']) : null,
                'categories_qualified' => isset($data['categories_qualified']) ? json_encode($data['categories_qualified']) : null,
                'experience_level' => $data['experience_level'] ?? self::EXPERIENCE_NOVICE,
                'years_experience' => $data['years_experience'] ?? 0,
                'professional_title' => $data['professional_title'] ?? null,
                'professional_bio' => $data['professional_bio'] ?? null,
                'linkedin_profile' => $data['linkedin_profile'] ?? null,
                'specialty_categories' => $data['specialty_categories'] ?? null,
                'max_assignments_per_day' => $data['max_assignments_per_day'] ?? 10,
                'preferred_categories' => $data['preferred_categories'] ?? null,
                'availability_notes' => $data['availability_notes'] ?? null,
                'bio' => $data['bio'] ?? null,
                'certifications' => isset($data['certifications']) ? json_encode($data['certifications']) : null,
                'languages_spoken' => $data['languages_spoken'] ?? 'English',
                'contact_preferences' => isset($data['contact_preferences']) ? json_encode($data['contact_preferences']) : null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                'accessibility_needs' => $data['accessibility_needs'] ?? null,
                'availability' => isset($data['availability']) ? json_encode($data['availability']) : null,
                'preferred_venues' => isset($data['preferred_venues']) ? json_encode($data['preferred_venues']) : null,
                'special_requirements' => $data['special_requirements'] ?? null,
                'status' => self::STATUS_PENDING,
                'onboarding_completed' => false,
                'background_check_status' => self::BG_CHECK_NOT_REQUIRED
            ];
            
            $profileId = $db->insert('judge_profiles', $profileData);
            
            $db->commit();
            
            return $profile->find($profileId);
            
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get available judge types
     */
    public static function getJudgeTypes()
    {
        return [
            self::TYPE_COORDINATOR => 'GDE C&R Coordinator',
            self::TYPE_ADJUDICATOR => 'Professional Adjudicator',
            self::TYPE_TECHNICAL => 'Technical Judge',
            self::TYPE_VOLUNTEER => 'Volunteer Judge',
            self::TYPE_INDUSTRY => 'Industry Expert'
        ];
    }
    
    /**
     * Get available experience levels
     */
    public static function getExperienceLevels()
    {
        return [
            self::EXPERIENCE_NOVICE => 'Novice (0-1 years)',
            self::EXPERIENCE_INTERMEDIATE => 'Intermediate (1-3 years)',
            self::EXPERIENCE_ADVANCED => 'Advanced (3-5 years)',
            self::EXPERIENCE_EXPERT => 'Expert (5+ years)'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_BLACKLISTED => 'Blacklisted'
        ];
    }
    
    /**
     * Get background check statuses
     */
    public static function getBackgroundCheckStatuses()
    {
        return [
            self::BG_CHECK_NOT_REQUIRED => 'Not Required',
            self::BG_CHECK_PENDING => 'Pending',
            self::BG_CHECK_CLEARED => 'Cleared',
            self::BG_CHECK_FAILED => 'Failed'
        ];
    }
    
    /**
     * Get profile summary for dashboard
     */
    public function getProfileSummary()
    {
        $user = $this->getUser();
        $organization = $this->getOrganization();
        
        return [
            'id' => $this->id,
            'judge_code' => $this->judge_code,
            'name' => $user ? "{$user['first_name']} {$user['last_name']}" : 'Unknown',
            'email' => $user['email'] ?? null,
            'judge_type' => $this->judge_type,
            'judge_type_label' => self::getJudgeTypes()[$this->judge_type] ?? $this->judge_type,
            'experience_level' => $this->experience_level,
            'years_experience' => $this->years_experience,
            'organization' => $organization ? $organization->organization_name : 'Independent',
            'status' => $this->status,
            'status_label' => self::getStatuses()[$this->status] ?? $this->status,
            'onboarding_complete' => $this->isOnboardingComplete(),
            'current_workload' => $this->getCurrentWorkload(),
            'max_assignments' => $this->max_assignments_per_day,
            'availability' => $this->isAvailableForAssignment(),
            'avg_performance' => $this->getAveragePerformanceScore(),
            'qualified_categories' => count($this->getQualifiedCategories()),
            'active_qualifications' => count($this->getActiveQualifications())
        ];
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['user'] = $this->getUser();
        $attributes['organization'] = $this->getOrganization();
        $attributes['expertise_areas'] = $this->getExpertiseAreas();
        $attributes['qualified_categories'] = $this->getQualifiedCategories();
        $attributes['availability'] = $this->getAvailability();
        $attributes['preferred_venues'] = $this->getPreferredVenues();
        $attributes['contact_preferences'] = $this->getContactPreferences();
        $attributes['certifications'] = $this->getCertifications();
        $attributes['is_onboarding_complete'] = $this->isOnboardingComplete();
        $attributes['is_available'] = $this->isAvailableForAssignment();
        $attributes['current_workload'] = $this->getCurrentWorkload();
        $attributes['avg_performance'] = $this->getAveragePerformanceScore();
        $attributes['profile_summary'] = $this->getProfileSummary();
        
        return $attributes;
    }
}