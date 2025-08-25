<?php

namespace App\Controllers\Registration;

use App\Controllers\BaseController;
use App\Models\BulkImport;
use App\Models\ImportValidationError;
use App\Models\Participant;
use App\Models\Category;
use App\Models\School;

/**
 * Comprehensive Bulk Import Controller
 * Handles CSV/Excel student imports with validation and error tracking
 */
class BulkImportController extends BaseController
{
    /**
     * Show bulk import dashboard
     */
    public function index()
    {
        $schoolId = $this->getUserSchoolId();
        
        if (!$schoolId) {
            return $this->redirect('/')->with('error', 'School association required');
        }
        
        // Get recent imports
        $recentImports = BulkImport::where('school_id', $schoolId)
                                  ->orderBy('started_at', 'desc')
                                  ->limit(10)
                                  ->get();
        
        // Get available categories
        $categories = Category::where('is_active', true)->orderBy('display_order')->get();
        
        return $this->renderView('registration/bulk_import/index', [
            'recent_imports' => $recentImports,
            'categories' => $categories,
            'school_id' => $schoolId
        ]);
    }
    
    /**
     * Show import wizard
     */
    public function wizard()
    {
        $schoolId = $this->getUserSchoolId();
        
        if (!$schoolId) {
            return $this->redirect('/')->with('error', 'School association required');
        }
        
        $categories = Category::where('is_active', true)->orderBy('display_order')->get();
        
        return $this->renderView('registration/bulk_import/wizard', [
            'categories' => $categories,
            'supported_formats' => ['csv', 'xlsx', 'xls'],
            'max_file_size' => ini_get('upload_max_filesize')
        ]);
    }
    
    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $format = $this->input('format', 'csv');
        $categoryId = $this->input('category_id');
        
        $category = null;
        if ($categoryId) {
            $category = Category::find($categoryId);
        }
        
        $template = $this->generateTemplate($category, $format);
        
        $filename = 'student_import_template_' . ($category ? $category->code : 'all') . '.' . $format;
        
        return $this->downloadFile($template, $filename, $format);
    }
    
    /**
     * Upload and validate file
     */
    public function upload()
    {
        $schoolId = $this->getUserSchoolId();
        
        if (!$schoolId) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'School association required'
            ], 403);
        }
        
        // Check file upload
        if (!isset($_FILES['import_file'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'No file uploaded'
            ], 400);
        }
        
        $uploadedFile = $_FILES['import_file'];
        
        // Validate file
        $fileValidation = $this->validateUploadedFile($uploadedFile);
        if (!$fileValidation['valid']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $fileValidation['error']
            ], 400);
        }
        
        try {
            // Create import record
            $importData = [
                'school_id' => $schoolId,
                'imported_by' => $_SESSION['user_id'],
                'file_name' => $uploadedFile['name'],
                'file_path' => '',
                'file_size' => $uploadedFile['size'],
                'file_type' => $this->getFileType($uploadedFile['name']),
                'file_hash' => hash_file('sha256', $uploadedFile['tmp_name']),
                'import_type' => $this->input('import_type', 'participants'),
                'import_status' => 'uploaded',
                'started_at' => date('Y-m-d H:i:s')
            ];
            
            $bulkImport = BulkImport::create($importData);
            
            // Move uploaded file to storage
            $storagePath = $this->storeUploadedFile($uploadedFile, $bulkImport->id);
            $bulkImport->file_path = $storagePath;
            $bulkImport->save();
            
            // Start validation process
            $this->startValidationProcess($bulkImport);
            
            return $this->jsonResponse([
                'success' => true,
                'import_id' => $bulkImport->id,
                'message' => 'File uploaded successfully. Starting validation...'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check validation progress
     */
    public function validationStatus()
    {
        $importId = $this->input('import_id');
        
        $bulkImport = BulkImport::find($importId);
        
        if (!$bulkImport) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Import not found'
            ], 404);
        }
        
        // Check user permission
        if (!$this->canAccessImport($bulkImport)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        
        return $this->jsonResponse([
            'success' => true,
            'status' => $bulkImport->import_status,
            'progress' => [
                'total_records' => $bulkImport->total_records,
                'processed_records' => $bulkImport->processed_records,
                'failed_records' => $bulkImport->failed_records,
                'validation_passed' => $bulkImport->validation_passed
            ]
        ]);
    }
    
    /**
     * Show validation results
     */
    public function validationResults()
    {
        $importId = $this->getRouteParam('id');
        
        $bulkImport = BulkImport::find($importId);
        
        if (!$bulkImport || !$this->canAccessImport($bulkImport)) {
            return $this->redirect('/bulk-import')->with('error', 'Import not found');
        }
        
        // Get validation errors
        $validationErrors = ImportValidationError::where('bulk_import_id', $importId)
                                                ->orderBy('import_row_number')
                                                ->get();
        
        return $this->renderView('registration/bulk_import/validation_results', [
            'import' => $bulkImport,
            'validation_errors' => $validationErrors,
            'error_summary' => $this->getErrorSummary($validationErrors)
        ]);
    }
    
    /**
     * Execute import after validation
     */
    public function executeImport()
    {
        $importId = $this->input('import_id');
        
        $bulkImport = BulkImport::find($importId);
        
        if (!$bulkImport || !$this->canAccessImport($bulkImport)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Import not found or access denied'
            ], 403);
        }
        
        if ($bulkImport->import_status !== 'validation_complete') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Import must complete validation before execution'
            ], 400);
        }
        
        if (!$bulkImport->validation_passed) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Cannot execute import with validation errors'
            ], 400);
        }
        
        try {
            // Update status
            $bulkImport->import_status = 'processing';
            $bulkImport->processing_started_at = date('Y-m-d H:i:s');
            $bulkImport->save();
            
            // Process the import
            $result = $this->processImportFile($bulkImport);
            
            // Update completion status
            $bulkImport->import_status = 'completed';
            $bulkImport->processing_completed_at = date('Y-m-d H:i:s');
            $bulkImport->import_summary = $result;
            $bulkImport->save();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Import completed successfully',
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $bulkImport->import_status = 'failed';
            $bulkImport->error_report = $e->getMessage();
            $bulkImport->save();
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show import results
     */
    public function results()
    {
        $importId = $this->getRouteParam('id');
        
        $bulkImport = BulkImport::find($importId);
        
        if (!$bulkImport || !$this->canAccessImport($bulkImport)) {
            return $this->redirect('/bulk-import')->with('error', 'Import not found');
        }
        
        return $this->renderView('registration/bulk_import/results', [
            'import' => $bulkImport,
            'summary' => $bulkImport->import_summary
        ]);
    }
    
    /**
     * Download error report
     */
    public function downloadErrorReport()
    {
        $importId = $this->getRouteParam('id');
        
        $bulkImport = BulkImport::find($importId);
        
        if (!$bulkImport || !$this->canAccessImport($bulkImport)) {
            return $this->redirect('/bulk-import')->with('error', 'Import not found');
        }
        
        $errors = ImportValidationError::where('bulk_import_id', $importId)
                                     ->orderBy('import_row_number')
                                     ->get();
        
        $csvContent = $this->generateErrorReportCsv($errors);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="import_errors_' . $importId . '.csv"');
        echo $csvContent;
        exit;
    }
    
    /**
     * Validate uploaded file
     */
    private function validateUploadedFile($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload error'];
        }
        
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File too large. Maximum size is 10MB'];
        }
        
        // Check file type
        $allowedTypes = ['csv', 'xlsx', 'xls'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type. Only CSV and Excel files are allowed'];
        }
        
        // Virus scan (if available)
        // This would integrate with antivirus scanning
        
        return ['valid' => true];
    }
    
    /**
     * Store uploaded file
     */
    private function storeUploadedFile($file, $importId)
    {
        $uploadDir = 'public/uploads/bulk_imports/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = $importId . '_' . time() . '_' . $file['name'];
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \Exception('Failed to store uploaded file');
        }
        
        return $filepath;
    }
    
    /**
     * Start validation process
     */
    private function startValidationProcess($bulkImport)
    {
        $bulkImport->import_status = 'validating';
        $bulkImport->save();
        
        try {
            $validationResult = $this->validateImportFile($bulkImport);
            
            $bulkImport->import_status = 'validation_complete';
            $bulkImport->total_records = $validationResult['total_records'];
            $bulkImport->failed_records = $validationResult['error_count'];
            $bulkImport->validation_passed = $validationResult['error_count'] === 0;
            $bulkImport->validation_summary = $validationResult['summary'];
            $bulkImport->save();
            
        } catch (\Exception $e) {
            $bulkImport->import_status = 'validation_failed';
            $bulkImport->error_report = $e->getMessage();
            $bulkImport->save();
        }
    }
    
    /**
     * Validate import file
     */
    private function validateImportFile($bulkImport)
    {
        $filePath = $bulkImport->file_path;
        $fileType = $bulkImport->file_type;
        
        $data = $this->readImportFile($filePath, $fileType);
        
        $totalRecords = count($data) - 1; // Subtract header row
        $errorCount = 0;
        $summary = [];
        
        // Validate headers
        $headers = $data[0];
        $headerValidation = $this->validateHeaders($headers);
        
        if (!$headerValidation['valid']) {
            ImportValidationError::create([
                'bulk_import_id' => $bulkImport->id,
                'import_row_number' => 1,
                'error_type' => 'invalid_format',
                'error_message' => $headerValidation['error'],
                'blocking_import' => true
            ]);
            $errorCount++;
        }
        
        // Validate data rows
        for ($i = 1; $i < count($data); $i++) {
            $rowData = array_combine($headers, $data[$i]);
            $rowErrors = $this->validateDataRow($rowData, $i + 1);
            
            foreach ($rowErrors as $error) {
                ImportValidationError::create([
                    'bulk_import_id' => $bulkImport->id,
                    'import_row_number' => $i + 1,
                    'field_name' => $error['field'],
                    'cell_value' => $error['value'],
                    'error_type' => $error['type'],
                    'error_message' => $error['message'],
                    'blocking_import' => $error['blocking'] ?? true
                ]);
                $errorCount++;
            }
        }
        
        return [
            'total_records' => $totalRecords,
            'error_count' => $errorCount,
            'summary' => $summary
        ];
    }
    
    /**
     * Read import file
     */
    private function readImportFile($filePath, $fileType)
    {
        switch ($fileType) {
            case 'csv':
                return $this->readCsvFile($filePath);
            case 'xlsx':
            case 'xls':
                return $this->readExcelFile($filePath);
            default:
                throw new \Exception('Unsupported file type');
        }
    }
    
    /**
     * Read CSV file
     */
    private function readCsvFile($filePath)
    {
        $data = [];
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Read Excel file (simplified - would use PhpSpreadsheet in real implementation)
     */
    private function readExcelFile($filePath)
    {
        // This is a simplified implementation
        // In a real application, you would use PhpSpreadsheet library
        throw new \Exception('Excel file support requires PhpSpreadsheet library');
    }
    
    /**
     * Validate headers
     */
    private function validateHeaders($headers)
    {
        $requiredHeaders = [
            'student_id_number',
            'first_name',
            'last_name',
            'date_of_birth',
            'grade_level',
            'gender',
            'parent_guardian_name',
            'parent_guardian_email',
            'parent_guardian_phone'
        ];
        
        $missingHeaders = array_diff($requiredHeaders, $headers);
        
        if (!empty($missingHeaders)) {
            return [
                'valid' => false,
                'error' => 'Missing required headers: ' . implode(', ', $missingHeaders)
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate data row
     */
    private function validateDataRow($rowData, $rowNumber)
    {
        $errors = [];
        
        // Validate student ID
        if (empty($rowData['student_id_number'])) {
            $errors[] = [
                'field' => 'student_id_number',
                'value' => $rowData['student_id_number'] ?? '',
                'type' => 'required_field_missing',
                'message' => 'Student ID is required'
            ];
        } elseif (!preg_match('/^[0-9A-Z]{8,15}$/', $rowData['student_id_number'])) {
            $errors[] = [
                'field' => 'student_id_number',
                'value' => $rowData['student_id_number'],
                'type' => 'invalid_format',
                'message' => 'Invalid student ID format'
            ];
        }
        
        // Validate email
        if (!empty($rowData['parent_guardian_email']) && 
            !filter_var($rowData['parent_guardian_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'field' => 'parent_guardian_email',
                'value' => $rowData['parent_guardian_email'],
                'type' => 'email_format_invalid',
                'message' => 'Invalid email format'
            ];
        }
        
        // Validate date of birth
        if (!empty($rowData['date_of_birth'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $rowData['date_of_birth']);
            if (!$date) {
                $errors[] = [
                    'field' => 'date_of_birth',
                    'value' => $rowData['date_of_birth'],
                    'type' => 'date_format_invalid',
                    'message' => 'Invalid date format. Expected YYYY-MM-DD'
                ];
            }
        }
        
        // Add more validations as needed
        
        return $errors;
    }
    
    /**
     * Process import file after validation
     */
    private function processImportFile($bulkImport)
    {
        $filePath = $bulkImport->file_path;
        $fileType = $bulkImport->file_type;
        
        $data = $this->readImportFile($filePath, $fileType);
        $headers = $data[0];
        
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        
        for ($i = 1; $i < count($data); $i++) {
            $rowData = array_combine($headers, $data[$i]);
            
            try {
                $result = $this->processParticipantData($rowData, $bulkImport->school_id);
                
                if ($result['action'] === 'created') {
                    $created++;
                } elseif ($result['action'] === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
                
                $processed++;
                
            } catch (\Exception $e) {
                $skipped++;
                error_log("Import row {$i} failed: " . $e->getMessage());
            }
        }
        
        return [
            'total_processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped
        ];
    }
    
    /**
     * Process individual participant data
     */
    private function processParticipantData($data, $schoolId)
    {
        // Check if participant exists
        $existing = Participant::where('student_id_number', $data['student_id_number'])->first();
        
        $participantData = [
            'student_id_number' => $data['student_id_number'],
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'],
            'grade' => $data['grade_level'],
            'gender' => $data['gender'],
            'parent_guardian_name' => $data['parent_guardian_name'],
            'parent_guardian_email' => $data['parent_guardian_email'],
            'parent_guardian_phone' => $data['parent_guardian_phone'],
            'school_id' => $schoolId,
            'status' => 'active'
        ];
        
        if ($existing) {
            $existing->update($participantData);
            return ['action' => 'updated', 'participant_id' => $existing->id];
        } else {
            $participant = Participant::create($participantData);
            return ['action' => 'created', 'participant_id' => $participant->id];
        }
    }
    
    /**
     * Generate import template
     */
    private function generateTemplate($category, $format)
    {
        $headers = [
            'student_id_number',
            'first_name',
            'last_name',
            'middle_name',
            'preferred_name',
            'date_of_birth',
            'grade_level',
            'gender',
            'home_address',
            'parent_guardian_name',
            'parent_guardian_email',
            'parent_guardian_phone',
            'emergency_contact_name',
            'emergency_contact_phone',
            'medical_conditions',
            'allergies',
            'special_needs',
            'previous_robotics_experience'
        ];
        
        $sampleData = [
            [
                '0123456789012',
                'John',
                'Smith',
                'William',
                'Johnny',
                '2010-05-15',
                'Grade 7',
                'M',
                '123 Main Street, City, 2000',
                'Mary Smith',
                'mary.smith@email.com',
                '011-555-0123',
                'Jane Smith',
                '011-555-0124',
                'None',
                'Nuts',
                'None',
                'Some LEGO experience'
            ]
        ];
        
        return $this->generateCsvContent($headers, $sampleData);
    }
    
    /**
     * Generate CSV content
     */
    private function generateCsvContent($headers, $data)
    {
        $output = fopen('php://temp', 'w');
        
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
    }
    
    /**
     * Download file
     */
    private function downloadFile($content, $filename, $format)
    {
        $mimeType = $format === 'csv' ? 'text/csv' : 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        
        echo $content;
        exit;
    }
    
    /**
     * Get user's school ID
     */
    private function getUserSchoolId()
    {
        return $_SESSION['school_id'] ?? null;
    }
    
    /**
     * Check if user can access import
     */
    private function canAccessImport($bulkImport)
    {
        $userRole = $_SESSION['user_role'] ?? 'participant';
        $userId = $_SESSION['user_id'] ?? null;
        
        if (in_array($userRole, ['admin', 'super_admin'])) {
            return true;
        }
        
        if ($userRole === 'school_coordinator') {
            $userSchoolId = $_SESSION['school_id'] ?? null;
            return $bulkImport->school_id == $userSchoolId;
        }
        
        return $bulkImport->imported_by == $userId;
    }
    
    /**
     * Get file type from filename
     */
    private function getFileType($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Get error summary
     */
    private function getErrorSummary($errors)
    {
        $summary = [
            'total_errors' => $errors->count(),
            'blocking_errors' => $errors->where('blocking_import', true)->count(),
            'by_type' => []
        ];
        
        $errorTypes = $errors->groupBy('error_type');
        
        foreach ($errorTypes as $type => $typeErrors) {
            $summary['by_type'][$type] = $typeErrors->count();
        }
        
        return $summary;
    }
    
    /**
     * Generate error report CSV
     */
    private function generateErrorReportCsv($errors)
    {
        $headers = ['Row', 'Field', 'Value', 'Error Type', 'Error Message', 'Blocking'];
        
        $data = [];
        foreach ($errors as $error) {
            $data[] = [
                $error->import_row_number,
                $error->field_name,
                $error->cell_value,
                $error->error_type,
                $error->error_message,
                $error->blocking_import ? 'Yes' : 'No'
            ];
        }
        
        return $this->generateCsvContent($headers, $data);
    }
}