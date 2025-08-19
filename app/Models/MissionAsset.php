<?php

namespace App\Models;

class MissionAsset extends BaseModel
{
    protected $table = 'mission_assets';
    protected $softDeletes = true;
    
    protected $fillable = [
        'mission_template_id', 'asset_type', 'asset_name', 'file_path',
        'file_size', 'mime_type', 'description', 'usage_instructions',
        'download_count', 'is_public', 'version', 'upload_date', 'status'
    ];
    
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    
    // Validation rules
    protected $rules = [
        'mission_template_id' => 'required',
        'asset_type' => 'required',
        'asset_name' => 'required|max:255',
        'file_size' => 'numeric|min:0',
        'version' => 'max:20'
    ];
    
    protected $messages = [
        'mission_template_id.required' => 'Mission template is required.',
        'asset_type.required' => 'Asset type is required.',
        'asset_name.required' => 'Asset name is required.',
        'file_size.numeric' => 'File size must be a valid number.',
    ];
    
    // Asset type constants
    const TYPE_DOCUMENT = 'document';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_3D_MODEL = '3d_model';
    const TYPE_CODE_TEMPLATE = 'code_template';
    const TYPE_INSTRUCTION_MANUAL = 'instruction_manual';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_PENDING_REVIEW = 'pending_review';
    
    protected $belongsTo = [
        'missionTemplate' => ['model' => MissionTemplate::class, 'foreign_key' => 'mission_template_id']
    ];

    /**
     * Get mission template relation
     */
    public function missionTemplate()
    {
        return $this->belongsTo('App\Models\MissionTemplate', 'mission_template_id');
    }
    
    /**
     * Get formatted file size
     */
    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get file extension from file path
     */
    public function getFileExtension()
    {
        if (!$this->file_path) {
            return null;
        }
        
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }
    
    /**
     * Check if asset is an image
     */
    public function isImage()
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        return in_array($this->getFileExtension(), $imageExtensions) || 
               $this->asset_type === self::TYPE_IMAGE;
    }
    
    /**
     * Check if asset is a video
     */
    public function isVideo()
    {
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
        return in_array($this->getFileExtension(), $videoExtensions) || 
               $this->asset_type === self::TYPE_VIDEO;
    }
    
    /**
     * Check if asset is a document
     */
    public function isDocument()
    {
        $docExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
        return in_array($this->getFileExtension(), $docExtensions) || 
               $this->asset_type === self::TYPE_DOCUMENT;
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount()
    {
        return $this->update([
            'download_count' => $this->download_count + 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get assets by mission template
     */
    public function getAssetsByMissionTemplate($missionTemplateId)
    {
        return $this->db->table($this->table)
            ->where('mission_template_id', $missionTemplateId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('asset_type')
            ->orderBy('asset_name')
            ->get();
    }
    
    /**
     * Get public assets by type
     */
    public function getPublicAssetsByType($assetType)
    {
        return $this->db->table($this->table)
            ->where('asset_type', $assetType)
            ->where('is_public', true)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('download_count', 'DESC')
            ->orderBy('asset_name')
            ->get();
    }
    
    /**
     * Get popular assets
     */
    public function getPopularAssets($limit = 10)
    {
        return $this->db->query("
            SELECT 
                ma.*,
                mt.mission_name,
                c.name as category_name
            FROM mission_assets ma
            JOIN mission_templates mt ON ma.mission_template_id = mt.id
            JOIN categories c ON mt.category_id = c.id
            WHERE ma.status = 'active'
            AND ma.is_public = 1
            AND ma.deleted_at IS NULL
            ORDER BY ma.download_count DESC, ma.asset_name
            LIMIT ?
        ", [$limit]);
    }
    
    /**
     * Get assets by category
     */
    public function getAssetsByCategory($categoryId)
    {
        return $this->db->query("
            SELECT 
                ma.*,
                mt.mission_name
            FROM mission_assets ma
            JOIN mission_templates mt ON ma.mission_template_id = mt.id
            WHERE mt.category_id = ?
            AND ma.status = 'active'
            AND ma.deleted_at IS NULL
            ORDER BY ma.asset_type, ma.asset_name
        ", [$categoryId]);
    }
    
    /**
     * Search assets
     */
    public function searchAssets($searchTerm, $assetType = null, $isPublic = null)
    {
        $query = "
            SELECT 
                ma.*,
                mt.mission_name,
                c.name as category_name
            FROM mission_assets ma
            JOIN mission_templates mt ON ma.mission_template_id = mt.id
            JOIN categories c ON mt.category_id = c.id
            WHERE (ma.asset_name LIKE ? 
                OR ma.description LIKE ?
                OR mt.mission_name LIKE ?)
            AND ma.status = 'active'
            AND ma.deleted_at IS NULL
        ";
        
        $params = ["%{$searchTerm}%", "%{$searchTerm}%", "%{$searchTerm}%"];
        
        if ($assetType) {
            $query .= " AND ma.asset_type = ?";
            $params[] = $assetType;
        }
        
        if ($isPublic !== null) {
            $query .= " AND ma.is_public = ?";
            $params[] = $isPublic ? 1 : 0;
        }
        
        $query .= " ORDER BY ma.asset_name";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get asset statistics
     */
    public function getAssetStatistics($missionTemplateId = null)
    {
        $query = "
            SELECT 
                asset_type,
                COUNT(*) as count,
                SUM(file_size) as total_size,
                SUM(download_count) as total_downloads,
                AVG(download_count) as avg_downloads
            FROM mission_assets
            WHERE status = 'active'
            AND deleted_at IS NULL
        ";
        
        $params = [];
        
        if ($missionTemplateId) {
            $query .= " AND mission_template_id = ?";
            $params[] = $missionTemplateId;
        }
        
        $query .= " GROUP BY asset_type ORDER BY count DESC";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get available asset types
     */
    public static function getAvailableAssetTypes()
    {
        return [
            self::TYPE_DOCUMENT => 'Document',
            self::TYPE_IMAGE => 'Image',
            self::TYPE_VIDEO => 'Video',
            self::TYPE_AUDIO => 'Audio',
            self::TYPE_3D_MODEL => '3D Model',
            self::TYPE_CODE_TEMPLATE => 'Code Template',
            self::TYPE_INSTRUCTION_MANUAL => 'Instruction Manual'
        ];
    }
    
    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_PENDING_REVIEW => 'Pending Review'
        ];
    }
    
    /**
     * Scope: Public assets
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
    
    /**
     * Scope: By asset type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('asset_type', $type);
    }
    
    /**
     * Scope: Active assets
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Override toArray to include calculated fields
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        
        // Add calculated fields
        $attributes['formatted_file_size'] = $this->getFormattedFileSize();
        $attributes['file_extension'] = $this->getFileExtension();
        $attributes['is_image'] = $this->isImage();
        $attributes['is_video'] = $this->isVideo();
        $attributes['is_document'] = $this->isDocument();
        $attributes['asset_type_label'] = self::getAvailableAssetTypes()[$this->asset_type] ?? $this->asset_type;
        $attributes['status_label'] = self::getAvailableStatuses()[$this->status] ?? $this->status;
        $attributes['upload_age_days'] = $this->upload_date ? 
            floor((time() - strtotime($this->upload_date)) / (60 * 60 * 24)) : null;
        
        return $attributes;
    }
}