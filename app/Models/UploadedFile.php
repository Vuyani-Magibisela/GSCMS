<?php

namespace App\Models;

class UploadedFile extends BaseModel
{
    protected $table = 'uploaded_files';
    protected $softDeletes = true;
    
    protected $fillable = [
        'original_name', 'stored_name', 'file_path', 'relative_path',
        'file_size', 'mime_type', 'file_extension', 'upload_type',
        'uploaded_by', 'related_type', 'related_id', 'metadata',
        'hash_md5', 'hash_sha256', 'status', 'access_level', 'download_count'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'original_name' => 'required|max:255',
        'file_path' => 'required',
        'file_size' => 'required',
        'upload_type' => 'required',
        'uploaded_by' => 'required'
    ];
    
    protected $messages = [
        'original_name.required' => 'Original filename is required.',
        'file_path.required' => 'File path is required.',
        'file_size.required' => 'File size is required.',
        'upload_type.required' => 'Upload type is required.',
        'uploaded_by.required' => 'Uploader information is required.'
    ];
    
    // Upload type constants
    const TYPE_CONSENT_FORM = 'consent_forms';
    const TYPE_TEAM_SUBMISSION = 'team_submissions';
    const TYPE_PROFILE_PHOTO = 'profile_photos';
    const TYPE_CERTIFICATE = 'certificates';
    const TYPE_SYSTEM_FILE = 'system';
    const TYPE_BACKUP = 'backups';
    const TYPE_TEMP = 'temp';
    
    // Status constants
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_ERROR = 'error';
    const STATUS_QUARANTINE = 'quarantine';
    const STATUS_ARCHIVED = 'archived';
    
    // Access level constants
    const ACCESS_PRIVATE = 'private';
    const ACCESS_SCHOOL = 'school';
    const ACCESS_TEAM = 'team';
    const ACCESS_PUBLIC = 'public';
    const ACCESS_ADMIN = 'admin';
    
    protected $belongsTo = [
        'uploader' => ['model' => User::class, 'foreign_key' => 'uploaded_by']
    ];
    
    /**
     * Get uploader relationship
     */
    public function uploader()
    {
        return $this->belongsTo('App\Models\User', 'uploaded_by');
    }
    
    /**
     * Get related model (polymorphic relationship)
     */
    public function related()
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }
        
        $model = new $this->related_type();
        return $model->find($this->related_id);
    }
    
    /**
     * Create file record
     */
    public static function createFileRecord($fileData, $uploadType, $uploadedBy, $relatedType = null, $relatedId = null)
    {
        $model = new static();
        
        $data = [
            'original_name' => $fileData['original_name'],
            'stored_name' => $fileData['filename'],
            'file_path' => $fileData['path'],
            'relative_path' => $fileData['relative_path'],
            'file_size' => $fileData['size'],
            'mime_type' => $fileData['type'],
            'file_extension' => pathinfo($fileData['original_name'], PATHINFO_EXTENSION),
            'upload_type' => $uploadType,
            'uploaded_by' => $uploadedBy,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'metadata' => json_encode($fileData['metadata'] ?? []),
            'hash_md5' => $fileData['metadata']['md5_hash'] ?? null,
            'status' => self::STATUS_UPLOADED,
            'access_level' => self::ACCESS_PRIVATE,
            'download_count' => 0
        ];
        
        return $model->create($data);
    }
    
    /**
     * Get files by upload type
     */
    public static function getByUploadType($uploadType, $limit = null)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('upload_type', $uploadType)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'DESC');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    /**
     * Get files by user
     */
    public static function getByUser($userId, $uploadType = null)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('uploaded_by', $userId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'DESC');
            
        if ($uploadType) {
            $query->where('upload_type', $uploadType);
        }
        
        return $query->get();
    }
    
    /**
     * Get files for related model
     */
    public static function getForRelated($relatedType, $relatedId, $uploadType = null)
    {
        $model = new static();
        $query = $model->db->table($model->table)
            ->where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'DESC');
            
        if ($uploadType) {
            $query->where('upload_type', $uploadType);
        }
        
        return $query->get();
    }
    
    /**
     * Get file usage statistics
     */
    public static function getUsageStats($period = '30 days')
    {
        $model = new static();
        $sinceDate = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        return $model->db->query("
            SELECT 
                upload_type,
                COUNT(*) as file_count,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size,
                MAX(file_size) as max_size,
                MIN(file_size) as min_size
            FROM uploaded_files 
            WHERE created_at >= ? 
            AND deleted_at IS NULL
            GROUP BY upload_type
            ORDER BY total_size DESC
        ", [$sinceDate]);
    }
    
    /**
     * Get storage usage by type
     */
    public static function getStorageUsage()
    {
        $model = new static();
        
        return $model->db->query("
            SELECT 
                upload_type,
                COUNT(*) as file_count,
                SUM(file_size) as total_size
            FROM uploaded_files 
            WHERE deleted_at IS NULL
            GROUP BY upload_type
            ORDER BY total_size DESC
        ");
    }
    
    /**
     * Find duplicate files by hash
     */
    public static function findDuplicates($hash = null, $hashType = 'md5')
    {
        $model = new static();
        
        if ($hash) {
            $column = $hashType === 'sha256' ? 'hash_sha256' : 'hash_md5';
            return $model->db->table($model->table)
                ->where($column, $hash)
                ->whereNull('deleted_at')
                ->get();
        }
        
        // Find all duplicates
        $column = $hashType === 'sha256' ? 'hash_sha256' : 'hash_md5';
        return $model->db->query("
            SELECT {$column} as hash, COUNT(*) as count, GROUP_CONCAT(id) as file_ids
            FROM uploaded_files 
            WHERE {$column} IS NOT NULL 
            AND deleted_at IS NULL
            GROUP BY {$column}
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ");
    }
    
    /**
     * Clean up orphaned files
     */
    public static function cleanupOrphanedFiles()
    {
        $model = new static();
        $orphanedFiles = [];
        
        // Find files in database that don't exist on filesystem
        $files = $model->db->table($model->table)
            ->whereNull('deleted_at')
            ->get();
            
        foreach ($files as $file) {
            if (!file_exists($file['file_path'])) {
                $orphanedFiles[] = $file;
                // Mark as deleted in database
                $model->db->table($model->table)
                    ->where('id', $file['id'])
                    ->update([
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'status' => self::STATUS_ERROR
                    ]);
            }
        }
        
        return $orphanedFiles;
    }
    
    /**
     * Archive old files
     */
    public static function archiveOldFiles($olderThan = '1 year', $uploadTypes = [])
    {
        $model = new static();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$olderThan}"));
        
        $query = $model->db->table($model->table)
            ->where('created_at', '<', $cutoffDate)
            ->where('status', '!=', self::STATUS_ARCHIVED)
            ->whereNull('deleted_at');
            
        if (!empty($uploadTypes)) {
            $query->whereIn('upload_type', $uploadTypes);
        }
        
        $filesToArchive = $query->get();
        $archivedCount = 0;
        
        foreach ($filesToArchive as $file) {
            // Update status to archived
            $updated = $model->db->table($model->table)
                ->where('id', $file['id'])
                ->update([
                    'status' => self::STATUS_ARCHIVED,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
            if ($updated) {
                $archivedCount++;
            }
        }
        
        return $archivedCount;
    }
    
    /**
     * Update download count
     */
    public function incrementDownloadCount()
    {
        $this->download_count = ($this->download_count ?? 0) + 1;
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'download_count' => $this->download_count,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Set file status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'status' => $this->status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Set access level
     */
    public function setAccessLevel($accessLevel)
    {
        $this->access_level = $accessLevel;
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'access_level' => $this->access_level,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Check if file exists on filesystem
     */
    public function fileExists()
    {
        return file_exists($this->file_path);
    }
    
    /**
     * Get file URL
     */
    public function getUrl($secure = true)
    {
        if ($secure) {
            return "/files/download/{$this->id}";
        }
        
        return str_replace(PUBLIC_PATH, '', $this->file_path);
    }
    
    /**
     * Get thumbnail URL (for images)
     */
    public function getThumbnailUrl()
    {
        if (!$this->isImage()) {
            return null;
        }
        
        $thumbnailPath = dirname($this->file_path) . '/thumbnails/' . $this->stored_name;
        
        if (file_exists($thumbnailPath)) {
            return str_replace(PUBLIC_PATH, '', $thumbnailPath);
        }
        
        return null;
    }
    
    /**
     * Check if file is an image
     */
    public function isImage()
    {
        return strpos($this->mime_type, 'image/') === 0;
    }
    
    /**
     * Check if file is a document
     */
    public function isDocument()
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];
        
        return in_array($this->mime_type, $documentMimes);
    }
    
    /**
     * Check if file is an archive
     */
    public function isArchive()
    {
        return in_array($this->mime_type, [
            'application/zip',
            'application/x-zip-compressed'
        ]);
    }
    
    /**
     * Get formatted file size
     */
    public function getFormattedSize()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($this->file_size, 1024));
        $power = min($power, count($units) - 1);
        
        return round($this->file_size / pow(1024, $power), 2) . ' ' . $units[$power];
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
        
        return $this->db->table($this->table)
            ->where('id', $this->id)
            ->update([
                'metadata' => $this->metadata,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Add to metadata
     */
    public function addMetadata($key, $value)
    {
        $metadata = $this->getMetadata();
        $metadata[$key] = $value;
        return $this->setMetadata($metadata);
    }
    
    /**
     * Get available upload types
     */
    public static function getAvailableUploadTypes()
    {
        return [
            self::TYPE_CONSENT_FORM => 'Consent Form',
            self::TYPE_TEAM_SUBMISSION => 'Team Submission',
            self::TYPE_PROFILE_PHOTO => 'Profile Photo',
            self::TYPE_CERTIFICATE => 'Certificate',
            self::TYPE_SYSTEM_FILE => 'System File',
            self::TYPE_BACKUP => 'Backup',
            self::TYPE_TEMP => 'Temporary'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_UPLOADED => 'Uploaded',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_READY => 'Ready',
            self::STATUS_ERROR => 'Error',
            self::STATUS_QUARANTINE => 'Quarantine',
            self::STATUS_ARCHIVED => 'Archived'
        ];
    }
    
    /**
     * Get available access levels
     */
    public static function getAvailableAccessLevels()
    {
        return [
            self::ACCESS_PRIVATE => 'Private',
            self::ACCESS_SCHOOL => 'School Access',
            self::ACCESS_TEAM => 'Team Access',
            self::ACCESS_PUBLIC => 'Public',
            self::ACCESS_ADMIN => 'Admin Only'
        ];
    }
    
    /**
     * Get upload type label
     */
    public function getUploadTypeLabel()
    {
        $types = self::getAvailableUploadTypes();
        return $types[$this->upload_type] ?? $this->upload_type;
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
     * Get access level label
     */
    public function getAccessLevelLabel()
    {
        $levels = self::getAvailableAccessLevels();
        return $levels[$this->access_level] ?? $this->access_level;
    }
    
    /**
     * Scope: Files by upload type
     */
    public function scopeByUploadType($query, $type)
    {
        return $query->where('upload_type', $type);
    }
    
    /**
     * Scope: Files by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope: Files by access level
     */
    public function scopeByAccessLevel($query, $level)
    {
        return $query->where('access_level', $level);
    }
    
    /**
     * Scope: Image files
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'LIKE', 'image/%');
    }
    
    /**
     * Scope: Document files
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }
    
    /**
     * Scope: Recent files
     */
    public function scopeRecent($query, $days = 7)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $query->where('created_at', '>=', $date);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['upload_type_label'] = $this->getUploadTypeLabel();
        $attributes['status_label'] = $this->getStatusLabel();
        $attributes['access_level_label'] = $this->getAccessLevelLabel();
        $attributes['formatted_size'] = $this->getFormattedSize();
        $attributes['url'] = $this->getUrl();
        $attributes['thumbnail_url'] = $this->getThumbnailUrl();
        $attributes['file_exists'] = $this->fileExists();
        $attributes['is_image'] = $this->isImage();
        $attributes['is_document'] = $this->isDocument();
        $attributes['is_archive'] = $this->isArchive();
        $attributes['parsed_metadata'] = $this->getMetadata();
        
        return $attributes;
    }
}