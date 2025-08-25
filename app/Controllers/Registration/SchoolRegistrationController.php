<?php

namespace App\Controllers\Registration;

use App\Controllers\BaseController;
use App\Models\SchoolRegistration;
use App\Models\School;
use App\Models\District;
use App\Models\User;
use App\Models\Category;
use App\Core\DeadlineEnforcer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class SchoolRegistrationController extends BaseController
{
    protected $session;
    private $deadlineEnforcer;
    
    public function __construct()
    {
        parent::__construct();
        $this->session = Session::getInstance();
        $this->deadlineEnforcer = new DeadlineEnforcer();
    }
    
    /**
     * Show registration landing page
     */
    public function index()
    {
        // Check if registration is still open
        $deadlineStatus = $this->deadlineEnforcer->getDeadlineStatus();
        
        if ($deadlineStatus['active_competition'] && 
            $deadlineStatus['deadlines']['school_registration']['is_overdue']) {
            return $this->renderView('registration/school/closed', [
                'deadline_info' => $deadlineStatus['deadlines']['school_registration']
            ]);
        }
        
        return $this->renderView('registration/school/index', [
            'deadline_info' => $deadlineStatus['deadlines']['school_registration'] ?? null,
            'competition_info' => $deadlineStatus['active_competition'] ? [
                'name' => $deadlineStatus['competition_name']
            ] : null
        ]);
    }
    
    /**
     * Start new registration
     */
    public function create()
    {
        // Check registration deadline
        $deadlineStatus = $this->deadlineEnforcer->getDeadlineStatus();
        
        if ($deadlineStatus['active_competition'] && 
            $deadlineStatus['deadlines']['school_registration']['is_overdue']) {
            return $this->redirect('/register/school')->with('error', 'School registration deadline has passed');
        }
        
        // Start with step 1
        return $this->showStep(1);
    }
    
    /**
     * Show specific registration step
     */
    public function showStep($step = 1)
    {
        $step = (int)$step;
        
        // Get or create registration from session
        $registrationData = $this->getSessionRegistrationData();
        $registration = null;
        
        if (!empty($registrationData['id'])) {
            $registration = SchoolRegistration::find($registrationData['id']);
        }
        
        // Validate step
        if ($step < 1 || $step > 6) {
            return $this->redirect('/register/school/step/1');
        }
        
        // Get step data
        $stepData = $this->getStepData($step, $registration);
        
        return $this->renderView("registration/school/step_{$step}", [
            'step' => $step,
            'registration' => $registration,
            'step_data' => $stepData,
            'progress' => $this->calculateProgress($step, $registration),
            'validation_errors' => $this->getValidationErrors()
        ]);
    }
    
    /**
     * Process registration step
     */
    public function processStep(Request $request, $step)
    {
        $step = (int) $step;
        $data = $request->all();
        $registrationData = $this->session->get('school_registration', []);
        
        try {
            switch ($step) {
                case 1:
                    $this->validateStep1($data);
                    $registrationData = array_merge($registrationData, $data);
                    $registrationData['step1_completed'] = true;
                    break;
                    
                case 2:
                    $this->validateStep2($data);
                    $registrationData = array_merge($registrationData, $data);
                    $registrationData['step2_completed'] = true;
                    break;
                    
                case 3:
                    $this->validateStep3($data);
                    $registrationData = array_merge($registrationData, $data);
                    $registrationData['step3_completed'] = true;
                    break;
                    
                case 4:
                    $this->validateStep4($data);
                    $registrationData = array_merge($registrationData, $data);
                    $registrationData['step4_completed'] = true;
                    break;
                    
                case 5:
                    $this->validateStep5($data);
                    $registrationData = array_merge($registrationData, $data);
                    $registrationData['step5_completed'] = true;
                    
                    // Final step - create school record
                    return $this->submitRegistration($registrationData);
            }
            
            $this->session->put('school_registration', $registrationData);
            
            // Auto-save progress
            $this->autoSaveProgress($registrationData);
            
            // Redirect to next step
            $nextStep = $step + 1;
            return $this->redirect("/register/school/step/{$nextStep}");
            
        } catch (\Exception $e) {
            $this->session->flash('errors', [$e->getMessage()]);
            return $this->redirect("/register/school/step/{$step}");
        }
    }
    
    /**
     * Validate Step 1: School Information
     */
    private function validateStep1($data)
    {
        $required = ['school_name', 'emis_number', 'registration_number', 'school_type'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        // Validate EMIS number format
        if (!preg_match('/^[0-9]{8,12}$/', $data['emis_number'])) {
            throw new \Exception('EMIS number must be 8-12 digits');
        }
        
        // Check for duplicate school name
        $existingSchool = School::where('name', $data['school_name'])->first();
        if ($existingSchool) {
            throw new \Exception('A school with this name already exists');
        }
        
        // Check for duplicate EMIS number
        $existingEmis = School::where('emis_number', $data['emis_number'])->first();
        if ($existingEmis) {
            throw new \Exception('A school with this EMIS number already exists');
        }
        
        // Check for duplicate registration number
        $existingReg = School::where('registration_number', $data['registration_number'])->first();
        if ($existingReg) {
            throw new \Exception('A school with this registration number already exists');
        }
    }
    
    /**
     * Validate Step 2: Contact Information
     */
    private function validateStep2($data)
    {
        $required = ['principal_name', 'principal_email', 'principal_phone', 
                    'coordinator_name', 'coordinator_email', 'coordinator_phone', 'school_email'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        // Validate email formats
        if (!filter_var($data['principal_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid principal email format');
        }
        
        if (!filter_var($data['coordinator_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid coordinator email format');
        }
        
        if (!filter_var($data['school_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid school email format');
        }
        
        // Validate phone number formats
        if (!preg_match('/^[0-9\-\+\(\)\s]{10,15}$/', $data['principal_phone'])) {
            throw new \Exception('Invalid principal phone number format');
        }
        
        if (!preg_match('/^[0-9\-\+\(\)\s]{10,15}$/', $data['coordinator_phone'])) {
            throw new \Exception('Invalid coordinator phone number format');
        }
    }
    
    /**
     * Validate Step 3: Physical Address
     */
    private function validateStep3($data)
    {
        $required = ['address_line1', 'city', 'postal_code', 'district_id'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        // Validate postal code
        if (!preg_match('/^[0-9]{4}$/', $data['postal_code'])) {
            throw new \Exception('Postal code must be 4 digits');
        }
        
        // Validate district exists
        $district = District::find($data['district_id']);
        if (!$district) {
            throw new \Exception('Selected district does not exist');
        }
        
        // Validate address length
        if (strlen($data['address_line1']) < 20) {
            throw new \Exception('Address must be at least 20 characters long');
        }
    }
    
    /**
     * Validate Step 4: School Details
     */
    private function validateStep4($data)
    {
        $required = ['province', 'total_learners', 'quintile'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        // Validate province
        if (!in_array($data['province'], School::PROVINCES)) {
            throw new \Exception('Invalid province selected');
        }
        
        // Validate total learners
        if (!is_numeric($data['total_learners']) || $data['total_learners'] < 50 || $data['total_learners'] > 5000) {
            throw new \Exception('Total learners must be between 50 and 5000');
        }
        
        // Validate quintile
        if (!in_array($data['quintile'], [1, 2, 3, 4, 5])) {
            throw new \Exception('Invalid quintile selected');
        }
    }
    
    /**
     * Validate Step 5: Competition Information
     */
    private function validateStep5($data)
    {
        $required = ['intended_categories', 'communication_preference'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        // Validate communication preference
        $validComm = [School::COMM_EMAIL, School::COMM_PHONE, School::COMM_SMS, School::COMM_POSTAL];
        if (!in_array($data['communication_preference'], $validComm)) {
            throw new \Exception('Invalid communication preference selected');
        }
    }
    
    /**
     * Submit final registration
     */
    private function submitRegistration($data)
    {
        try {
            // Create school record
            $schoolData = [
                'name' => $data['school_name'],
                'emis_number' => $data['emis_number'],
                'registration_number' => $data['registration_number'],
                'school_type' => $data['school_type'],
                'quintile' => $data['quintile'],
                'district_id' => $data['district_id'],
                'province' => $data['province'],
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'phone' => $data['principal_phone'],
                'email' => $data['school_email'],
                'website' => $data['website'] ?? null,
                'gps_coordinates' => $data['gps_coordinates'] ?? null,
                'principal_name' => $data['principal_name'],
                'principal_email' => $data['principal_email'],
                'principal_phone' => $data['principal_phone'],
                'total_learners' => $data['total_learners'],
                'facilities' => json_encode($data['facilities'] ?? []),
                'computer_lab' => $data['computer_lab'] ?? false,
                'internet_status' => $data['internet_status'] ?? 'unknown',
                'accessibility_features' => $data['accessibility_features'] ?? null,
                'previous_participation' => $data['previous_participation'] ?? false,
                'communication_preference' => $data['communication_preference'],
                'status' => School::STATUS_PENDING,
                'registration_date' => date('Y-m-d H:i:s'),
                'notes' => $data['notes'] ?? null
            ];
            
            $school = new School();
            $schoolId = $school->create($schoolData);
            
            // Clear session data
            $this->session->forget('school_registration');
            
            // Set success message
            $this->session->flash('success', 'School registration submitted successfully! You will receive an email confirmation shortly. Your application will be reviewed within 3-5 business days.');
            
            return $this->render('registration/school/success', [
                'title' => 'Registration Submitted - GDE SciBOTICS Competition 2025',
                'school_name' => $data['school_name'],
                'registration_number' => $data['registration_number'],
                'contact_email' => $data['coordinator_email']
            ]);
            
        } catch (\Exception $e) {
            $this->session->flash('errors', ['Registration failed: ' . $e->getMessage()]);
            return $this->redirect('/register/school/step/5');
        }
    }
    
    /**
     * Auto-save registration progress
     */
    private function autoSaveProgress($data)
    {
        // Store progress in session with timestamp
        $data['last_saved'] = time();
        $this->session->put('school_registration', $data);
    }
    
    /**
     * Resume registration from saved progress
     */
    public function resume()
    {
        $registrationData = $this->session->get('school_registration');
        
        if (!$registrationData) {
            $this->session->flash('info', 'No saved registration found. Starting new registration.');
            return $this->redirect('/register/school');
        }
        
        // Find the furthest completed step
        $lastStep = 1;
        for ($i = 1; $i <= 5; $i++) {
            if (isset($registrationData["step{$i}_completed"])) {
                $lastStep = $i + 1;
            } else {
                break;
            }
        }
        
        if ($lastStep > 5) {
            $lastStep = 5;
        }
        
        $this->session->flash('info', 'Resuming registration from Step ' . $lastStep);
        return $this->redirect("/register/school/step/{$lastStep}");
    }
    
    /**
     * Check registration status
     */
    public function checkStatus(Request $request)
    {
        $data = $request->all();
        
        if (empty($data['registration_number']) || empty($data['email'])) {
            $this->session->flash('errors', ['Registration number and email are required']);
            return $this->redirect('/register/school/status');
        }
        
        $school = School::where('registration_number', $data['registration_number'])
                       ->where('email', $data['email'])
                       ->first();
        
        if (!$school) {
            $this->session->flash('errors', ['School not found with provided details']);
            return $this->redirect('/register/school/status');
        }
        
        return $this->render('registration/school/status', [
            'title' => 'Registration Status - GDE SciBOTICS Competition 2025',
            'school' => $school,
            'status_info' => $school->getStatusInfo()
        ]);
    }
    
    /**
     * Show status check form
     */
    public function showStatusForm()
    {
        return $this->render('registration/school/status_form', [
            'title' => 'Check Registration Status - GDE SciBOTICS Competition 2025'
        ]);
    }
}