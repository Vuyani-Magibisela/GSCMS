<?php

namespace App\Models;

class TeamSubmission extends BaseModel
{
    protected $table = 'team_submissions';
    protected $softDeletes = true;
    
    protected $fillable = [
        'team_id', 'phase_id', 'submission_type', 'title', 'description',
        'file_path', 'file_name', 'file_size', 'file_type', 'status',
        'submitted_date', 'reviewed_date', 'reviewed_by', 'score',
        'feedback', 'notes', 'metadata', 'uploaded_file_id'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'team_id' => 'required',
        'phase_id' => 'required',
        'submission_type' => 'required',
        'title' => 'required|max:255',
        'status' => 'required'
    ];
    
    protected $messages = [
        'team_id.required' => 'Team is required.',
        'phase_id.required' => 'Phase is required.',
        'submission_type.required' => 'Submission type is required.',
        'title.required' => 'Submission title is required.',
        'status.required' => 'Submission status is required.'
    ];
    
    // Submission type constants
    const TYPE_DESIGN_PORTFOLIO = 'design_portfolio';
    const TYPE_TECHNICAL_REPORT = 'technical_report';
    const TYPE_PRESENTATION = 'presentation';
    const TYPE_VIDEO_DEMO = 'video_demo';
    const TYPE_SOURCE_CODE = 'source_code';
    const TYPE_PROTOTYPE_IMAGES = 'prototype_images';
    const TYPE_TESTING_RESULTS = 'testing_results';
    const TYPE_DOCUMENTATION = 'documentation';
    const TYPE_OTHER = 'other';
    
    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REQUIRES_REVISION = 'requires_revision';
    const STATUS_REVISED = 'revised';
    
    protected $belongsTo = [
        'team' => ['model' => Team::class, 'foreign_key' => 'team_id'],
        'phase' => ['model' => Phase::class, 'foreign_key' => 'phase_id'],
        'reviewer' => ['model' => User::class, 'foreign_key' => 'reviewed_by'],
        'uploadedFile' => ['model' => UploadedFile::class, 'foreign_key' => 'uploaded_file_id']
    ];
    
    /**
     * Get team relationship
     */
    public function team()
    {
        return $this->belongsTo('App\Models\Team', 'team_id');
    }
    
    /**
     * Get phase relationship
     */
    public function phase()
    {
        return $this->belongsTo('App\Models\Phase', 'phase_id');
    }
    
    /**
     * Get reviewer relationship
     */
    public function reviewer()
    {
        return $this->belongsTo('App\Models\User', 'reviewed_by');
    }
    
    /**
     * Get uploaded file relationship
     */
    public function uploadedFile()
    {
        return $this->belongsTo('App\Models\UploadedFile', 'uploaded_file_id');
    }
    
    /**
     * Submit the submission for review
     */
    public function submit()
    {
        $this->status = self::STATUS_SUBMITTED;
        $this->submitted_date = date('Y-m-d H:i:s');
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'submitted_date' => $this->submitted_date,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Approve submission
     */
    public function approve($reviewerId, $score = null, $feedback = null)
    {
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_date = date('Y-m-d H:i:s');
        if ($score !== null) {
            $this->score = $score;
        }
        if ($feedback) {
            $this->feedback = $feedback;
        }
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'reviewed_by' => $this->reviewed_by,
                'reviewed_date' => $this->reviewed_date,
                'score' => $this->score,
                'feedback' => $this->feedback,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Reject submission
     */
    public function reject($reviewerId, $feedback = null)
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_date = date('Y-m-d H:i:s');
        if ($feedback) {
            $this->feedback = $feedback;
        }
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'reviewed_by' => $this->reviewed_by,
                'reviewed_date' => $this->reviewed_date,
                'feedback' => $this->feedback,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Request revision
     */
    public function requestRevision($reviewerId, $feedback)
    {
        $this->status = self::STATUS_REQUIRES_REVISION;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_date = date('Y-m-d H:i:s');
        $this->feedback = $feedback;
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'reviewed_by' => $this->reviewed_by,
                'reviewed_date' => $this->reviewed_date,
                'feedback' => $this->feedback,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Mark as revised
     */
    public function markRevised()
    {
        $this->status = self::STATUS_REVISED;
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Attach uploaded file to team submission
     */
    public function attachFile($uploadedFileId, $filePath = null, $fileName = null, $fileSize = null, $fileType = null)
    {
        $updateData = [
            'uploaded_file_id' => $uploadedFileId,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($filePath) $updateData['file_path'] = $filePath;
        if ($fileName) $updateData['file_name'] = $fileName;
        if ($fileSize) $updateData['file_size'] = $fileSize;
        if ($fileType) $updateData['file_type'] = $fileType;
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update($updateData);
    }
    
    /**
     * Get submissions for a team
     */
    public static function getForTeam($teamId, $phaseId = null)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('team_id', $teamId)
            ->whereNull('deleted_at')
            ->orderBy('submission_type')
            ->orderBy('created_at', 'DESC');
            
        if ($phaseId) {
            $query->where('phase_id', $phaseId);
        }
        
        return $query->get();
    }
    
    /**
     * Get submissions by phase
     */
    public static function getByPhase($phaseId, $submissionType = null)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('phase_id', $phaseId)
            ->whereNull('deleted_at')
            ->orderBy('submitted_date', 'DESC');
            
        if ($submissionType) {
            $query->where('submission_type', $submissionType);
        }
        
        return $query->get();
    }
    
    /**
     * Get submissions requiring review
     */
    public static function getPendingReview($phaseId = null)
    {
        $model = new static();
        $query = "
            SELECT 
                ts.*,
                t.name as team_name,
                t.team_code,
                s.name as school_name,
                p.name as phase_name
            FROM team_submissions ts
            JOIN teams t ON ts.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN phases p ON ts.phase_id = p.id
            WHERE ts.status IN (?, ?)
            AND ts.deleted_at IS NULL
        ";
        
        $params = [self::STATUS_SUBMITTED, self::STATUS_REVISED];
        
        if ($phaseId) {
            $query .= " AND ts.phase_id = ?";
            $params[] = $phaseId;
        }
        
        $query .= " ORDER BY ts.submitted_date ASC";
        
        return $model->db->query($query, $params);
    }
    
    /**
     * Get submission statistics
     */
    public static function getSubmissionStats($phaseId = null, $teamId = null)
    {
        $model = new static();
        $query = "
            SELECT 
                ts.submission_type,
                ts.status,
                COUNT(*) as count,
                AVG(ts.score) as average_score
            FROM team_submissions ts
            WHERE ts.deleted_at IS NULL
        ";
        
        $params = [];
        
        if ($phaseId) {
            $query .= " AND ts.phase_id = ?";
            $params[] = $phaseId;
        }
        
        if ($teamId) {
            $query .= " AND ts.team_id = ?";
            $params[] = $teamId;
        }
        
        $query .= " GROUP BY ts.submission_type, ts.status ORDER BY ts.submission_type, ts.status";
        
        return $model->db->query($query, $params);
    }
    
    /**
     * Get submissions with team details
     */
    public static function getSubmissionsWithTeamDetails($filters = [])
    {
        $model = new static();
        $query = "
            SELECT 
                ts.*,
                t.name as team_name,
                t.team_code,
                s.name as school_name,
                p.name as phase_name,
                CONCAT(u.first_name, ' ', u.last_name) as reviewer_name
            FROM team_submissions ts
            JOIN teams t ON ts.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            JOIN phases p ON ts.phase_id = p.id
            LEFT JOIN users u ON ts.reviewed_by = u.id
            WHERE ts.deleted_at IS NULL
        ";
        
        $params = [];
        
        if (!empty($filters['team_id'])) {
            $query .= " AND ts.team_id = ?";
            $params[] = $filters['team_id'];
        }
        
        if (!empty($filters['phase_id'])) {
            $query .= " AND ts.phase_id = ?";
            $params[] = $filters['phase_id'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND ts.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['submission_type'])) {
            $query .= " AND ts.submission_type = ?";
            $params[] = $filters['submission_type'];
        }
        
        if (!empty($filters['school_id'])) {
            $query .= " AND t.school_id = ?";
            $params[] = $filters['school_id'];
        }
        
        $query .= " ORDER BY ts.submitted_date DESC";
        
        return $model->db->query($query, $params);
    }
    
    /**
     * Check if team has required submissions for phase
     */
    public static function checkRequiredSubmissions($teamId, $phaseId)
    {
        $model = new static();
        
        // Get phase requirements (this would come from phase configuration)
        $phase = $model->db->table('phases')->find($phaseId);
        
        if (!$phase) {
            return ['complete' => false, 'message' => 'Phase not found'];
        }
        
        // Get required submission types for this phase
        $requiredTypes = $model->getRequiredSubmissionTypes($phaseId);
        
        $missingSubmissions = [];
        
        foreach ($requiredTypes as $type) {
            $submission = $model->db->table($model->table)
                ->where('team_id', $teamId)
                ->where('phase_id', $phaseId)
                ->where('submission_type', $type)
                ->where('status', '!=', self::STATUS_DRAFT)
                ->whereNull('deleted_at')
                ->first();
                
            if (!$submission) {
                $missingSubmissions[] = $model->getSubmissionTypeLabel($type);
            }
        }
        
        return [
            'complete' => empty($missingSubmissions),
            'missing_submissions' => $missingSubmissions,
            'total_required' => count($requiredTypes),
            'submitted_count' => count($requiredTypes) - count($missingSubmissions)
        ];
    }
    
    /**
     * Get required submission types for phase
     */
    protected function getRequiredSubmissionTypes($phaseId)
    {
        // This would typically come from phase configuration
        // For now, return default requirements based on phase
        $phase = $this->db->table('phases')->find($phaseId);
        
        if (!$phase) {
            return [];
        }
        
        // Default requirements based on phase name/type
        $defaultRequirements = [
            'Phase 1' => [self::TYPE_DESIGN_PORTFOLIO, self::TYPE_TECHNICAL_REPORT],
            'Phase 2' => [self::TYPE_PROTOTYPE_IMAGES, self::TYPE_TESTING_RESULTS, self::TYPE_PRESENTATION],
            'Phase 3' => [self::TYPE_VIDEO_DEMO, self::TYPE_DOCUMENTATION, self::TYPE_SOURCE_CODE]
        ];
        
        return $defaultRequirements[$phase['name']] ?? [];
    }
    
    /**
     * Get file download URL
     */
    public function getDownloadUrl()
    {
        if (!$this->file_path) {
            return null;
        }
        
        // Generate secure download URL
        return '/uploads/team_submissions/download/' . $this->id;
    }
    
    /**
     * Check if file exists
     */
    public function fileExists()
    {
        return $this->file_path && file_exists(PUBLIC_PATH . $this->file_path);
    }
    
    /**
     * Get file size formatted
     */
    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($this->file_size, 1024));
        $power = min($power, count($units) - 1);
        
        return round($this->file_size / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    
    /**
     * Get available submission types
     */
    public static function getAvailableSubmissionTypes()
    {
        return [
            self::TYPE_DESIGN_PORTFOLIO => 'Design Portfolio',
            self::TYPE_TECHNICAL_REPORT => 'Technical Report',
            self::TYPE_PRESENTATION => 'Presentation',
            self::TYPE_VIDEO_DEMO => 'Video Demonstration',
            self::TYPE_SOURCE_CODE => 'Source Code',
            self::TYPE_PROTOTYPE_IMAGES => 'Prototype Images',
            self::TYPE_TESTING_RESULTS => 'Testing Results',
            self::TYPE_DOCUMENTATION => 'Documentation',
            self::TYPE_OTHER => 'Other'
        ];
    }
    
    /**
     * Get submission type label
     */
    public function getSubmissionTypeLabel($type = null)
    {
        $type = $type ?? $this->submission_type;
        $types = self::getAvailableSubmissionTypes();
        return $types[$type] ?? $type;
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REQUIRES_REVISION => 'Requires Revision',
            self::STATUS_REVISED => 'Revised'
        ];
    }
    
    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        $statuses = self::getAvailableStatuses();
        return $statuses[$this->status] ?? $this->status;
    }
    
    /**
     * Check if submission can be edited
     */
    public function canEdit()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REQUIRES_REVISION
        ]);
    }
    
    /**
     * Check if submission can be deleted
     */
    public function canDelete()
    {
        return $this->status === self::STATUS_DRAFT;
    }
    
    /**
     * Check if submission is finalized
     */
    public function isFinalized()
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED
        ]);
    }
    
    /**
     * Get metadata as array
     */
    public function getMetadata()
    {
        return $this->metadata ? json_decode($this->metadata, true) : [];
    }
    
    /**
     * Set metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = json_encode($metadata);
    }
    
    /**
     * Add to metadata
     */
    public function addMetadata($key, $value)
    {
        $metadata = $this->getMetadata();
        $metadata[$key] = $value;
        $this->setMetadata($metadata);
    }
    
    /**
     * Scope: Submissions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope: Submissions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('submission_type', $type);
    }
    
    /**
     * Scope: Submissions by phase
     */
    public function scopeByPhase($query, $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }
    
    /**
     * Scope: Submissions by team
     */
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
    
    /**
     * Scope: Pending submissions
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_REVISED]);
    }
    
    /**
     * Scope: Approved submissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    
    /**
     * Scope: Draft submissions
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['submission_type_label'] = $this->getSubmissionTypeLabel();
        $attributes['status_label'] = $this->getStatusLabel();
        $attributes['formatted_file_size'] = $this->getFormattedFileSize();
        $attributes['download_url'] = $this->getDownloadUrl();
        $attributes['can_edit'] = $this->canEdit();
        $attributes['can_delete'] = $this->canDelete();
        $attributes['is_finalized'] = $this->isFinalized();
        $attributes['file_exists'] = $this->fileExists();
        $attributes['parsed_metadata'] = $this->getMetadata();
        
        return $attributes;
    }
}