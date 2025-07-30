<?php

namespace App\Models;

class ConsentForm extends BaseModel
{
    protected $table = 'consent_forms';
    protected $softDeletes = true;
    
    protected $fillable = [
        'participant_id', 'form_type', 'status', 'submitted_date', 'reviewed_date',
        'reviewed_by', 'parent_guardian_name', 'parent_guardian_signature',
        'parent_guardian_date', 'file_path', 'notes', 'rejection_reason'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'participant_id' => 'required',
        'form_type' => 'required',
        'status' => 'required',
        'parent_guardian_name' => 'required|max:255',
        'submitted_date' => 'required'
    ];
    
    protected $messages = [
        'participant_id.required' => 'Participant is required.',
        'form_type.required' => 'Form type is required.',
        'status.required' => 'Consent form status is required.',
        'parent_guardian_name.required' => 'Parent/Guardian name is required.',
        'submitted_date.required' => 'Submission date is required.'
    ];
    
    // Form type constants
    const FORM_TYPE_PARTICIPATION = 'participation_consent';
    const FORM_TYPE_MEDICAL = 'medical_consent';
    const FORM_TYPE_PHOTO_VIDEO = 'photo_video_consent';
    const FORM_TYPE_TRANSPORT = 'transport_consent';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    
    protected $belongsTo = [
        'participant' => ['model' => Participant::class, 'foreign_key' => 'participant_id'],
        'reviewer' => ['model' => User::class, 'foreign_key' => 'reviewed_by']
    ];
    
    /**
     * Get participant relationship
     */
    public function participant()
    {
        return $this->belongsTo('App\Models\Participant', 'participant_id');
    }
    
    /**
     * Get reviewer relationship
     */
    public function reviewer()
    {
        return $this->belongsTo('App\Models\User', 'reviewed_by');
    }
    
    /**
     * Check if consent form is valid (approved and not expired)
     */
    public function isValid()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }
        
        // Check if consent has expired (typically valid for 1 year)
        if ($this->parent_guardian_date) {
            $expiryDate = date('Y-m-d', strtotime($this->parent_guardian_date . ' +1 year'));
            if (date('Y-m-d') > $expiryDate) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get consent form expiry date
     */
    public function getExpiryDate()
    {
        if (!$this->parent_guardian_date) {
            return null;
        }
        
        return date('Y-m-d', strtotime($this->parent_guardian_date . ' +1 year'));
    }
    
    /**
     * Check if consent form is expired
     */
    public function isExpired()
    {
        $expiryDate = $this->getExpiryDate();
        if (!$expiryDate) {
            return false;
        }
        
        return date('Y-m-d') > $expiryDate;
    }
    
    /**
     * Approve consent form
     */
    public function approve($reviewerId, $notes = null)
    {
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_date = date('Y-m-d H:i:s');
        if ($notes) {
            $this->notes = $notes;
        }
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'reviewed_by' => $this->reviewed_by,
                'reviewed_date' => $this->reviewed_date,
                'notes' => $this->notes,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Reject consent form
     */
    public function reject($reviewerId, $reason, $notes = null)
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_date = date('Y-m-d H:i:s');
        $this->rejection_reason = $reason;
        if ($notes) {
            $this->notes = $notes;
        }
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'reviewed_by' => $this->reviewed_by,
                'reviewed_date' => $this->reviewed_date,
                'rejection_reason' => $this->rejection_reason,
                'notes' => $this->notes,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Get all consent forms for a participant
     */
    public static function getForParticipant($participantId)
    {
        $model = new static();
        return $model->db->table($model->table)
            ->where('participant_id', $participantId)
            ->whereNull('deleted_at')
            ->orderBy('form_type')
            ->orderBy('created_at', 'DESC')
            ->get();
    }
    
    /**
     * Check if participant has all required consent forms
     */
    public static function checkParticipantConsents($participantId)
    {
        $model = new static();
        $requiredForms = [
            self::FORM_TYPE_PARTICIPATION,
            self::FORM_TYPE_MEDICAL,
            self::FORM_TYPE_PHOTO_VIDEO
        ];
        
        $missingForms = [];
        $expiredForms = [];
        
        foreach ($requiredForms as $formType) {
            $consentForm = $model->db->table($model->table)
                ->where('participant_id', $participantId)
                ->where('form_type', $formType)
                ->where('status', self::STATUS_APPROVED)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'DESC')
                ->first();
                
            if (!$consentForm) {
                $missingForms[] = $formType;
            } else {
                // Check if expired
                $consentModel = $model->newInstance($consentForm);
                if ($consentModel->isExpired()) {
                    $expiredForms[] = $formType;
                }
            }
        }
        
        return [
            'has_all_consents' => empty($missingForms) && empty($expiredForms),
            'missing_forms' => $missingForms,
            'expired_forms' => $expiredForms
        ];
    }
    
    /**
     * Get consent forms requiring review
     */
    public static function getPendingReview()
    {
        $model = new static();
        return $model->db->query("
            SELECT 
                cf.*,
                p.first_name,
                p.last_name,
                t.name as team_name,
                s.name as school_name
            FROM consent_forms cf
            JOIN participants p ON cf.participant_id = p.id
            JOIN teams t ON p.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE cf.status = ?
            AND cf.deleted_at IS NULL
            ORDER BY cf.submitted_date ASC
        ", [self::STATUS_PENDING]);
    }
    
    /**
     * Get expired consent forms
     */
    public static function getExpiredForms()
    {
        $model = new static();
        $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
        
        return $model->db->query("
            SELECT 
                cf.*,
                p.first_name,
                p.last_name,
                t.name as team_name,
                s.name as school_name
            FROM consent_forms cf
            JOIN participants p ON cf.participant_id = p.id
            JOIN teams t ON p.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE cf.status = ?
            AND cf.parent_guardian_date < ?
            AND cf.deleted_at IS NULL
            ORDER BY cf.parent_guardian_date ASC
        ", [self::STATUS_APPROVED, $oneYearAgo]);
    }
    
    /**
     * Generate consent form summary report
     */
    public static function getConsentSummary($filters = [])
    {
        $model = new static();
        $query = "
            SELECT 
                cf.form_type,
                cf.status,
                COUNT(*) as count,
                s.name as school_name
            FROM consent_forms cf
            JOIN participants p ON cf.participant_id = p.id
            JOIN teams t ON p.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE cf.deleted_at IS NULL
        ";
        
        $params = [];
        
        if (!empty($filters['school_id'])) {
            $query .= " AND t.school_id = ?";
            $params[] = $filters['school_id'];
        }
        
        if (!empty($filters['form_type'])) {
            $query .= " AND cf.form_type = ?";
            $params[] = $filters['form_type'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND cf.status = ?";
            $params[] = $filters['status'];
        }
        
        $query .= " GROUP BY cf.form_type, cf.status";
        if (empty($filters['school_id'])) {
            $query .= ", s.id";
        }
        $query .= " ORDER BY s.name, cf.form_type, cf.status";
        
        return $model->db->query($query, $params);
    }
    
    /**
     * Get available form types
     */
    public static function getAvailableFormTypes()
    {
        return [
            self::FORM_TYPE_PARTICIPATION => 'Participation Consent',
            self::FORM_TYPE_MEDICAL => 'Medical Consent',
            self::FORM_TYPE_PHOTO_VIDEO => 'Photo/Video Consent',
            self::FORM_TYPE_TRANSPORT => 'Transport Consent'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired'
        ];
    }
    
    /**
     * Scope: Consent forms by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope: Consent forms by form type
     */
    public function scopeByFormType($query, $formType)
    {
        return $query->where('form_type', $formType);
    }
    
    /**
     * Scope: Pending consent forms
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    /**
     * Scope: Approved consent forms
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    
    /**
     * Scope: Expired consent forms
     */
    public function scopeExpired($query)
    {
        $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
        return $query->where('status', self::STATUS_APPROVED)
                    ->where('parent_guardian_date', '<', $oneYearAgo);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['is_valid'] = $this->isValid();
        $attributes['is_expired'] = $this->isExpired();
        $attributes['expiry_date'] = $this->getExpiryDate();
        $attributes['form_type_label'] = self::getAvailableFormTypes()[$this->form_type] ?? $this->form_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['days_until_expiry'] = $this->getExpiryDate() ? 
            ceil((strtotime($this->getExpiryDate()) - time()) / (60 * 60 * 24)) : null;
        
        return $attributes;
    }
}