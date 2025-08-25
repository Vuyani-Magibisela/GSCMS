<?php

namespace App\Models;

use App\Core\Database;

class BulkImport extends BaseModel
{
    protected $table = 'bulk_imports';
    protected $fillable = [
        'school_id',
        'file_name',
        'file_path',
        'file_size',
        'import_type',
        'import_status',
        'total_records',
        'processed_records',
        'failed_records',
        'started_at',
        'completed_at',
        'error_details'
    ];

    const STATUS_UPLOADED = 'uploaded';
    const STATUS_VALIDATING = 'validating';
    const STATUS_VALIDATION_COMPLETE = 'validation_complete';
    const STATUS_VALIDATION_FAILED = 'validation_failed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const TYPE_PARTICIPANTS = 'participants';
    const TYPE_COACHES = 'coaches';
    const TYPE_SCHOOLS = 'schools';

    /**
     * Get the school associated with this import
     */
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Get validation errors for this import
     */
    public function validationErrors()
    {
        return $this->hasMany(BulkImportValidationError::class, 'bulk_import_id');
    }

    /**
     * Check if import is in progress
     */
    public function isInProgress()
    {
        return in_array($this->import_status, [
            self::STATUS_UPLOADED,
            self::STATUS_VALIDATING,
            self::STATUS_PROCESSING
        ]);
    }

    /**
     * Check if import is completed successfully
     */
    public function isCompleted()
    {
        return $this->import_status === self::STATUS_COMPLETED;
    }

    /**
     * Check if import has failed
     */
    public function isFailed()
    {
        return in_array($this->import_status, [
            self::STATUS_VALIDATION_FAILED,
            self::STATUS_FAILED
        ]);
    }

    /**
     * Calculate import progress percentage
     */
    public function getProgressPercentage()
    {
        if ($this->total_records <= 0) {
            return 0;
        }

        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    /**
     * Get import status display text
     */
    public function getStatusText()
    {
        $statusTexts = [
            self::STATUS_UPLOADED => 'File Uploaded',
            self::STATUS_VALIDATING => 'Validating Data',
            self::STATUS_VALIDATION_COMPLETE => 'Validation Complete',
            self::STATUS_VALIDATION_FAILED => 'Validation Failed',
            self::STATUS_PROCESSING => 'Processing Import',
            self::STATUS_COMPLETED => 'Import Completed',
            self::STATUS_FAILED => 'Import Failed'
        ];

        return $statusTexts[$this->import_status] ?? 'Unknown Status';
    }

    /**
     * Update import progress
     */
    public function updateProgress($processed, $failed = null, $status = null)
    {
        $data = ['processed_records' => $processed];
        
        if ($failed !== null) {
            $data['failed_records'] = $failed;
        }
        
        if ($status !== null) {
            $data['import_status'] = $status;
            
            if ($status === self::STATUS_COMPLETED || $status === self::STATUS_FAILED) {
                $data['completed_at'] = date('Y-m-d H:i:s');
            }
        }

        return $this->update($data);
    }

    /**
     * Get recent imports for a school
     */
    public static function getRecentImports($schoolId, $limit = 10)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT * FROM bulk_imports 
            WHERE school_id = :school_id 
            ORDER BY started_at DESC 
            LIMIT :limit
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':school_id', $schoolId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $imports = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $import = new self();
            $import->fill($row);
            $imports[] = $import;
        }
        
        return $imports;
    }

    /**
     * Get import statistics for a school
     */
    public static function getImportStats($schoolId)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                COUNT(*) as total_imports,
                SUM(CASE WHEN import_status = :completed THEN 1 ELSE 0 END) as completed_imports,
                SUM(CASE WHEN import_status IN (:failed, :validation_failed) THEN 1 ELSE 0 END) as failed_imports,
                SUM(total_records) as total_records_processed,
                SUM(failed_records) as total_records_failed
            FROM bulk_imports 
            WHERE school_id = :school_id
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':school_id', $schoolId, \PDO::PARAM_INT);
        $stmt->bindValue(':completed', self::STATUS_COMPLETED);
        $stmt->bindValue(':failed', self::STATUS_FAILED);
        $stmt->bindValue(':validation_failed', self::STATUS_VALIDATION_FAILED);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Clean up old import files
     */
    public static function cleanupOldImports($daysOld = 30)
    {
        $db = Database::getInstance();
        
        // Get old imports to delete their files
        $sql = "
            SELECT file_path FROM bulk_imports 
            WHERE started_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            AND file_path IS NOT NULL
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':days', $daysOld, \PDO::PARAM_INT);
        $stmt->execute();
        
        $deletedFiles = 0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (file_exists($row['file_path'])) {
                unlink($row['file_path']);
                $deletedFiles++;
            }
        }
        
        // Delete old import records
        $sql = "
            DELETE FROM bulk_imports 
            WHERE started_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':days', $daysOld, \PDO::PARAM_INT);
        $deleted = $stmt->execute();
        
        return [
            'deleted_records' => $stmt->rowCount(),
            'deleted_files' => $deletedFiles
        ];
    }
}