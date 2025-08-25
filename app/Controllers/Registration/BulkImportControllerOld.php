<?php

namespace App\Controllers\Registration;

use App\Controllers\BaseController;
use App\Models\Participant;
use App\Models\Category;
use App\Models\School;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class BulkImportController extends BaseController
{
    private $session;
    
    public function __construct()
    {
        parent::__construct();
        $this->session = Session::getInstance();
    }
    
    /**
     * Show bulk import interface
     */
    public function index()
    {
        $categories = Category::where('status', Category::STATUS_ACTIVE)->get();
        
        return $this->render('registration/bulk-import/index', [
            'title' => 'Bulk Student Import - GDE SciBOTICS Competition 2025',
            'categories' => $categories
        ]);
    }
    
    /**
     * Download CSV template for category
     */
    public function downloadTemplate($categoryId = null)
    {
        $category = null;
        if ($categoryId) {
            $category = Category::find($categoryId);
            if (!$category) {
                $this->session->flash('errors', ['Invalid category selected']);
                return $this->redirect('/bulk-import');
            }
        }
        
        // Generate CSV template
        $headers = $this->getCsvHeaders($category);
        $sampleData = $this->getSampleData($category);
        
        // Set download headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="student_import_template_' . ($category ? $category->code : 'all') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write sample data
        foreach ($sampleData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Process bulk import
     */
    public function processImport(Request $request)
    {
        $data = $request->all();
        $schoolId = auth()->user()->school_id; // Assuming coordinator is logged in
        
        if (!$schoolId) {
            $this->session->flash('errors', ['No school associated with your account']);
            return $this->redirect('/bulk-import');
        }
        
        $categoryId = $data['category_id'] ?? null;
        if (!$categoryId) {
            $this->session->flash('errors', ['Please select a category']);
            return $this->redirect('/bulk-import');
        }
        
        // Validate category
        $category = Category::find($categoryId);
        if (!$category || $category->status !== Category::STATUS_ACTIVE) {
            $this->session->flash('errors', ['Invalid or inactive category selected']);
            return $this->redirect('/bulk-import');
        }
        
        // Check file upload
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->flash('errors', ['Please select a valid CSV file to upload']);
            return $this->redirect('/bulk-import');
        }
        
        $file = $_FILES['import_file'];
        
        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes) && !str_ends_with($file['name'], '.csv')) {
            $this->session->flash('errors', ['Only CSV files are allowed']);
            return $this->redirect('/bulk-import');
        }
        
        try {
            $result = $this->processImportFile($file['tmp_name'], $schoolId, $categoryId);
            
            // Store import result in session for display
            $this->session->put('import_result', $result);
            
            return $this->redirect('/bulk-import/results');
            
        } catch (\Exception $e) {
            $this->session->flash('errors', ['Import failed: ' . $e->getMessage()]);
            return $this->redirect('/bulk-import');
        }
    }
    
    /**
     * Show import results
     */
    public function showResults()
    {
        $result = $this->session->get('import_result');
        
        if (!$result) {
            return $this->redirect('/bulk-import');
        }
        
        return $this->render('registration/bulk-import/results', [
            'title' => 'Bulk Import Results - GDE SciBOTICS Competition 2025',
            'result' => $result
        ]);
    }
    
    /**
     * Process import file
     */
    private function processImportFile($filePath, $schoolId, $categoryId)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Unable to open import file');
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \Exception('Invalid CSV file - no headers found');
        }
        
        // Validate headers
        $this->validateCsvHeaders($headers);
        
        $category = Category::find($categoryId);
        $results = [
            'total_rows' => 0,
            'processed' => 0,
            'errors' => [],
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'category' => $category->name
        ];
        
        $rowNum = 1;
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            $results['total_rows']++;
            
            try {
                // Combine headers with row data
                $data = array_combine($headers, $row);
                
                // Validate row data
                $this->validateRowData($data, $category);
                
                // Check if student already exists
                $existingParticipant = Participant::where('student_id_number', $data['student_id_number'])
                                                ->first();
                
                if ($existingParticipant) {
                    // Update existing participant
                    $participantData = $this->prepareParticipantData($data, $schoolId, $categoryId);
                    
                    $participant = new Participant();
                    $participant->update($existingParticipant['id'], $participantData);
                    
                    $results['updated'][] = [
                        'row' => $rowNum,
                        'name' => $data['first_name'] . ' ' . $data['last_name'],
                        'student_id' => $data['student_id_number']
                    ];
                } else {
                    // Create new participant
                    $participantData = $this->prepareParticipantData($data, $schoolId, $categoryId);
                    
                    $participant = new Participant();
                    $participantId = $participant->create($participantData);
                    
                    $results['created'][] = [
                        'row' => $rowNum,
                        'name' => $data['first_name'] . ' ' . $data['last_name'],
                        'student_id' => $data['student_id_number']
                    ];
                }
                
                $results['processed']++;
                
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ];
            }
        }
        
        fclose($handle);
        
        return $results;
    }
    
    /**
     * Validate CSV headers
     */
    private function validateCsvHeaders($headers)
    {
        $requiredHeaders = [
            'student_id_number', 'first_name', 'last_name', 'date_of_birth',
            'grade_level', 'gender', 'parent_guardian_name', 'parent_guardian_email',
            'parent_guardian_phone'
        ];
        
        $missingHeaders = [];
        foreach ($requiredHeaders as $required) {
            if (!in_array($required, $headers)) {
                $missingHeaders[] = $required;
            }
        }
        
        if (!empty($missingHeaders)) {
            throw new \Exception('Missing required headers: ' . implode(', ', $missingHeaders));
        }
    }
    
    /**
     * Validate row data
     */
    private function validateRowData($data, $category)
    {
        // Check required fields
        $required = [
            'student_id_number', 'first_name', 'last_name', 'date_of_birth',
            'grade_level', 'gender', 'parent_guardian_name', 'parent_guardian_email',
            'parent_guardian_phone'
        ];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate student ID format (13 digits for SA ID or custom format)
        if (!preg_match('/^[0-9]{13}$/', $data['student_id_number']) && 
            !preg_match('/^[A-Z0-9]{8,15}$/', $data['student_id_number'])) {
            throw new \Exception("Invalid student ID format: {$data['student_id_number']}");
        }
        
        // Validate date format
        $date = \DateTime::createFromFormat('Y-m-d', $data['date_of_birth']);
        if (!$date) {
            throw new \Exception("Invalid date format: {$data['date_of_birth']}. Expected YYYY-MM-DD");
        }
        
        // Validate age based on date of birth
        $age = date_diff(date_create($data['date_of_birth']), date_create('today'))->y;
        $ageValidation = $category->validateParticipantAge($age);
        if (!$ageValidation['valid']) {
            throw new \Exception($ageValidation['message']);
        }
        
        // Validate grade
        $gradeValidation = $category->validateParticipantGrade($data['grade_level']);
        if (!$gradeValidation['valid']) {
            throw new \Exception($gradeValidation['message']);
        }
        
        // Validate gender
        if (!in_array(strtoupper($data['gender']), ['M', 'F', 'MALE', 'FEMALE', 'OTHER'])) {
            throw new \Exception("Invalid gender: {$data['gender']}. Expected M, F, Male, Female, or Other");
        }
        
        // Validate email
        if (!filter_var($data['parent_guardian_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid parent email format: {$data['parent_guardian_email']}");
        }
        
        // Validate phone number
        if (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $data['parent_guardian_phone'])) {
            throw new \Exception("Invalid phone number format: {$data['parent_guardian_phone']}");
        }
    }
    
    /**
     * Prepare participant data for database
     */
    private function prepareParticipantData($data, $schoolId, $categoryId)
    {
        // Normalize gender
        $gender = strtoupper($data['gender']);
        if (in_array($gender, ['MALE', 'M'])) {
            $gender = 'M';
        } elseif (in_array($gender, ['FEMALE', 'F'])) {
            $gender = 'F';
        } else {
            $gender = 'Other';
        }
        
        return [
            'student_id_number' => $data['student_id_number'],
            'first_name' => ucfirst(trim($data['first_name'])),
            'last_name' => ucfirst(trim($data['last_name'])),
            'middle_name' => !empty($data['middle_name']) ? ucfirst(trim($data['middle_name'])) : null,
            'preferred_name' => !empty($data['preferred_name']) ? ucfirst(trim($data['preferred_name'])) : null,
            'date_of_birth' => $data['date_of_birth'],
            'grade_level' => $data['grade_level'],
            'gender' => $gender,
            'home_address' => $data['home_address'] ?? null,
            'parent_guardian_name' => ucwords(trim($data['parent_guardian_name'])),
            'parent_guardian_email' => strtolower(trim($data['parent_guardian_email'])),
            'parent_guardian_phone' => $data['parent_guardian_phone'],
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'medical_conditions' => $data['medical_conditions'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'special_needs' => $data['special_needs'] ?? null,
            'previous_robotics_experience' => $data['previous_robotics_experience'] ?? null,
            'programming_skills_level' => $data['programming_skills_level'] ?? 'beginner',
            'preferred_team_role' => $data['preferred_team_role'] ?? null,
            'school_id' => $schoolId,
            'intended_category_id' => $categoryId,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get CSV headers for template
     */
    private function getCsvHeaders($category = null)
    {
        $headers = [
            'student_id_number', 'first_name', 'last_name', 'middle_name', 'preferred_name',
            'date_of_birth', 'grade_level', 'gender', 'home_address',
            'parent_guardian_name', 'parent_guardian_email', 'parent_guardian_phone',
            'emergency_contact_name', 'emergency_contact_phone',
            'medical_conditions', 'allergies', 'special_needs',
            'previous_robotics_experience', 'programming_skills_level', 'preferred_team_role'
        ];
        
        if ($category) {
            $headers[] = 'equipment_experience_' . strtolower($category->code);
            $headers[] = 'skill_assessment_score';
            $headers[] = 'teacher_recommendation';
        }
        
        return $headers;
    }
    
    /**
     * Get sample data for template
     */
    private function getSampleData($category = null)
    {
        $baseData = [
            [
                '0123456789012', 'John', 'Smith', 'William', 'Johnny',
                '2010-05-15', 'Grade 7', 'M', '123 Main Street, Johannesburg, 2000',
                'Mary Smith', 'mary.smith@email.com', '011-555-0123',
                'Jane Smith', '011-555-0124',
                'None', 'Nuts', 'None',
                'Some experience with LEGO', 'intermediate', 'programmer'
            ],
            [
                '9876543210987', 'Sarah', 'Johnson', '', 'Sara',
                '2011-08-22', 'Grade 6', 'F', '456 Oak Avenue, Pretoria, 0001',
                'Robert Johnson', 'r.johnson@email.com', '012-555-0234',
                'Lisa Johnson', '012-555-0235',
                'Asthma', 'None', 'None',
                'First time with robotics', 'beginner', 'builder'
            ]
        ];
        
        if ($category) {
            // Add category-specific sample data
            foreach ($baseData as &$row) {
                $row[] = 'Some experience'; // equipment_experience
                $row[] = '75'; // skill_assessment_score
                $row[] = 'Highly recommended by teacher'; // teacher_recommendation
            }
        }
        
        return $baseData;
    }
}