<?php

namespace App\Models;

class BulkImportValidationError extends BaseModel
{
    protected $table = 'bulk_import_validation_errors';
    protected $fillable = [
        'bulk_import_id',
        'row_number',
        'field_name',
        'field_value',
        'error_type',
        'error_message',
        'suggested_fix'
    ];

    const ERROR_TYPE_REQUIRED = 'required';
    const ERROR_TYPE_INVALID_FORMAT = 'invalid_format';
    const ERROR_TYPE_DUPLICATE = 'duplicate';
    const ERROR_TYPE_AGE_REQUIREMENT = 'age_requirement';
    const ERROR_TYPE_GRADE_REQUIREMENT = 'grade_requirement';
    const ERROR_TYPE_INVALID_CATEGORY = 'invalid_category';
    const ERROR_TYPE_TEAM_LIMIT = 'team_limit';
    const ERROR_TYPE_SCHOOL_MISMATCH = 'school_mismatch';

    /**
     * Get the bulk import associated with this error
     */
    public function bulkImport()
    {
        return $this->belongsTo(BulkImport::class, 'bulk_import_id');
    }

    /**
     * Get error type display text
     */
    public function getErrorTypeText()
    {
        $errorTypes = [
            self::ERROR_TYPE_REQUIRED => 'Required Field',
            self::ERROR_TYPE_INVALID_FORMAT => 'Invalid Format',
            self::ERROR_TYPE_DUPLICATE => 'Duplicate Entry',
            self::ERROR_TYPE_AGE_REQUIREMENT => 'Age Requirement',
            self::ERROR_TYPE_GRADE_REQUIREMENT => 'Grade Requirement',
            self::ERROR_TYPE_INVALID_CATEGORY => 'Invalid Category',
            self::ERROR_TYPE_TEAM_LIMIT => 'Team Limit Exceeded',
            self::ERROR_TYPE_SCHOOL_MISMATCH => 'School Mismatch'
        ];

        return $errorTypes[$this->error_type] ?? 'Unknown Error';
    }

    /**
     * Get errors grouped by type for an import
     */
    public static function getErrorsByType($bulkImportId)
    {
        $errors = self::where('bulk_import_id', $bulkImportId)
                     ->orderBy('error_type')
                     ->orderBy('row_number')
                     ->get();

        $grouped = [];
        foreach ($errors as $error) {
            $grouped[$error->error_type][] = $error;
        }

        return $grouped;
    }

    /**
     * Get error summary for an import
     */
    public static function getErrorSummary($bulkImportId)
    {
        $db = \App\Core\Database::getInstance();
        
        $sql = "
            SELECT 
                error_type,
                COUNT(*) as error_count,
                COUNT(DISTINCT row_number) as affected_rows
            FROM bulk_import_validation_errors 
            WHERE bulk_import_id = :bulk_import_id
            GROUP BY error_type
            ORDER BY error_count DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':bulk_import_id', $bulkImportId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}