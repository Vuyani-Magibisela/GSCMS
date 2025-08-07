<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\School;
use App\Models\District;
use App\Models\Contact;
use App\Models\User;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\AuditLog;
use Exception;

class SchoolManagementController extends BaseController
{
    protected $schoolModel;
    protected $districtModel;
    protected $contactModel;
    protected $userModel;
    protected $auditLog;

    public function __construct()
    {
        parent::__construct();
        $this->schoolModel = new School();
        $this->districtModel = new District();
        $this->contactModel = new Contact();
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
        
        // Ensure user is authenticated and has appropriate permissions
        $this->auth->requireAuth();
        $this->auth->requireAnyRole(['super_admin', 'competition_admin']);
    }

    /**
     * Display the school listing page with advanced filtering and search
     */
    public function index()
    {
        try {
            $request = new Request();
            $criteria = [];

            // Extract search and filter criteria from request
            if ($request->get('search')) {
                $criteria['name'] = $request->get('search');
            }

            if ($request->get('district_id')) {
                $criteria['district_id'] = $request->get('district_id');
            }

            if ($request->get('province')) {
                $criteria['province'] = $request->get('province');
            }

            if ($request->get('school_type')) {
                $criteria['school_type'] = $request->get('school_type');
            }

            if ($request->get('status')) {
                $criteria['status'] = $request->get('status');
            }

            if ($request->get('quintile')) {
                $criteria['quintile'] = $request->get('quintile');
            }

            if ($request->get('has_teams')) {
                $criteria['has_teams'] = $request->get('has_teams');
            }

            if ($request->get('registered_from')) {
                $criteria['registered_from'] = $request->get('registered_from');
            }

            if ($request->get('registered_to')) {
                $criteria['registered_to'] = $request->get('registered_to');
            }

            // Sorting
            $criteria['sort_by'] = $request->get('sort_by', 'name');
            $criteria['sort_order'] = $request->get('sort_order', 'asc');

            // Get schools with search/filter criteria with proper counts
            $db = \App\Core\Database::getInstance();
            $schools = $db->query("
                SELECT s.*, 
                       d.name as district_name, 
                       d.province as district_province,
                       u.first_name as coordinator_first_name,
                       u.last_name as coordinator_last_name,
                       u.email as coordinator_email,
                       COALESCE(team_counts.team_count, 0) as team_count,
                       COALESCE(participant_counts.participant_count, 0) as participant_count
                FROM schools s
                LEFT JOIN districts d ON s.district_id = d.id
                LEFT JOIN users u ON s.coordinator_id = u.id
                LEFT JOIN (
                    SELECT school_id, COUNT(*) as team_count
                    FROM teams 
                    WHERE deleted_at IS NULL
                    GROUP BY school_id
                ) team_counts ON s.id = team_counts.school_id
                LEFT JOIN (
                    SELECT t.school_id, COUNT(p.id) as participant_count
                    FROM teams t
                    LEFT JOIN participants p ON t.id = p.team_id
                    WHERE t.deleted_at IS NULL AND p.deleted_at IS NULL
                    GROUP BY t.school_id
                ) participant_counts ON s.id = participant_counts.school_id
                WHERE s.deleted_at IS NULL
                ORDER BY s.name ASC
            ");

            // Get filter options
            $districts = $db->table('districts')
                ->whereNull('deleted_at')
                ->orderBy('name', 'ASC')
                ->get();
            $provinces = ['Gauteng', 'Western Cape', 'KwaZulu-Natal']; // Hardcoded for now
            $schoolTypes = ['primary' => 'Primary', 'secondary' => 'Secondary', 'combined' => 'Combined'];
            $statuses = ['active' => 'Active', 'pending' => 'Pending', 'inactive' => 'Inactive'];
            $quintiles = [1 => 'Quintile 1', 2 => 'Quintile 2', 3 => 'Quintile 3', 4 => 'Quintile 4', 5 => 'Quintile 5'];

            // Get summary statistics with proper array handling
            $stats = [
                'total' => count($schools),
                'by_status' => [],
                'by_type' => [],
                'by_quintile' => []
            ];

            // Process statistics from school arrays
            foreach ($schools as $school) {
                // Count by status
                $status = $school['status'] ?? 'unknown';
                $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
                
                // Count by school type
                $type = $school['school_type'] ?? 'unknown';
                $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
                
                // Count by quintile
                $quintile = $school['quintile'] ?? 'unknown';
                $stats['by_quintile'][$quintile] = ($stats['by_quintile'][$quintile] ?? 0) + 1;
            }

            // Get schools requiring attention with proper array handling
            $attention = [
                'pending_approvals' => 0,
                'missing_coordinator' => 0,
                'no_teams' => 0
            ];

            foreach ($schools as $school) {
                // Count pending approvals
                if (isset($school['status']) && $school['status'] === 'pending') {
                    $attention['pending_approvals']++;
                }
                
                // Count missing coordinators
                if (empty($school['coordinator_id'])) {
                    $attention['missing_coordinator']++;
                }
                
                // Count schools with no teams
                if (isset($school['team_count']) && $school['team_count'] == 0) {
                    $attention['no_teams']++;
                }
            }

            $data = [
                'schools' => $schools,
                'districts' => $districts,
                'provinces' => $provinces,
                'schoolTypes' => $schoolTypes,
                'statuses' => $statuses,
                'quintiles' => $quintiles,
                'stats' => $stats,
                'attention' => $attention,
                'currentFilters' => $criteria,
                'title' => 'School Management - GSCMS Admin',
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'School Management', 'url' => '']
                ]
            ];

            return $this->view('admin/schools/index', $data);

        } catch (Exception $e) {
            // Log error for debugging
            error_log("School index error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return $this->errorResponse('Error loading schools: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show the school registration form
     */
    public function create()
    {
        try {
            // Load districts from database
            $db = \App\Core\Database::getInstance();
            $districts = $db->query("
                SELECT id, name, province 
                FROM districts 
                WHERE deleted_at IS NULL 
                ORDER BY province, name
            ");
            
            $data = [
                'districts' => $districts,
                'provinces' => ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West'],
                'schoolTypes' => ['primary' => 'Primary School', 'secondary' => 'Secondary School', 'combined' => 'Combined School'],
                'quintiles' => [1 => 'Quintile 1', 2 => 'Quintile 2', 3 => 'Quintile 3', 4 => 'Quintile 4', 5 => 'Quintile 5'],
                'communicationPrefs' => ['email' => 'Email', 'phone' => 'Phone', 'sms' => 'SMS'],
                'title' => 'Register New School - GSCMS Admin',
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => $this->baseUrl('/admin/dashboard')],
                    ['title' => 'School Management', 'url' => $this->baseUrl('/admin/schools')],
                    ['title' => 'Register School', 'url' => '']
                ]
            ];

            return $this->view('admin/schools/create', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@create: ' . $e->getMessage());
            return $this->errorResponse('Error loading registration form: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Debug create method
     */
    public function createDebug()
    {
        try {
            $data = [
                'title' => 'Debug School Create - GSCMS Admin',
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'Debug', 'url' => '']
                ]
            ];

            return $this->view('admin/schools/create_debug', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in debug create: ' . $e->getMessage());
            return $this->errorResponse('Debug error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a new school registration
     */
    public function store()
    {
        try {
            $request = new Request();
            
            // Log form submission attempt with detailed info
            error_log("School form submission started - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            error_log("User authenticated: " . ($this->auth->check() ? 'YES' : 'NO'));
            error_log("User ID: " . ($this->auth->check() ? $this->auth->id() : 'NONE'));
            error_log("User role: " . ($this->auth->check() ? $this->auth->user()->role : 'NONE'));
            error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
            error_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
            error_log("Form data keys: " . implode(', ', array_keys($request->all())));
            
            // Check authentication first
            if (!$this->auth->check()) {
                error_log("School form submission failed - user not authenticated");
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Authentication required. Please log in again.',
                    'redirect' => '/auth/login'
                ], 401);
            }
            
            // Check user role
            if (!$this->auth->user()->hasAnyRole(['super_admin', 'competition_admin'])) {
                error_log("School form submission failed - insufficient permissions");
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Insufficient permissions for this action.'
                ], 403);
            }
            
            // Simple validation for required fields
            $requiredFields = ['name', 'registration_number', 'school_type', 'district_id', 'province', 'address_line1', 'city', 'postal_code', 'email'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($request->post($field))) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                error_log("School form validation failed - missing fields: " . implode(', ', $missingFields));
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Required fields are missing: ' . implode(', ', $missingFields),
                    'errors' => array_fill_keys($missingFields, ['Field is required'])
                ], 422);
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Prepare school data
                $schoolData = [
                    'name' => $request->post('name'),
                    'emis_number' => $request->post('emis_number'),
                    'registration_number' => $request->post('registration_number'),
                    'school_type' => $request->post('school_type'),
                    'quintile' => $request->post('quintile'),
                    'district' => $request->post('district'),
                    'district_id' => $request->post('district_id'),
                    'province' => $request->post('province'),
                    'address_line1' => $request->post('address_line1'),
                    'address_line2' => $request->post('address_line2'),
                    'city' => $request->post('city'),
                    'postal_code' => $request->post('postal_code'),
                    'phone' => $request->post('phone'),
                    'fax' => $request->post('fax'),
                    'email' => $request->post('email'),
                    'website' => $request->post('website'),
                    'gps_coordinates' => $request->post('gps_coordinates'),
                    'principal_name' => $request->post('principal_name'),
                    'principal_email' => $request->post('principal_email'),
                    'principal_phone' => $request->post('principal_phone'),
                    'establishment_date' => $request->post('establishment_date'),
                    'total_learners' => $request->post('total_learners'),
                    'facilities' => $request->post('facilities'),
                    'computer_lab' => $request->post('computer_lab'),
                    'internet_status' => $request->post('internet_status'),
                    'accessibility_features' => $request->post('accessibility_features'),
                    'previous_participation' => $request->post('previous_participation'),
                    'communication_preference' => $request->post('communication_preference'),
                    'status' => 'pending',
                    'registration_date' => date('Y-m-d')
                ];

                // Insert school record using raw SQL
                $schoolData['created_at'] = date('Y-m-d H:i:s');
                
                $fields = implode(', ', array_keys($schoolData));
                $placeholders = ':' . implode(', :', array_keys($schoolData));
                
                $sql = "INSERT INTO schools ({$fields}) VALUES ({$placeholders})";
                $this->db->statement($sql, $schoolData);
                $schoolId = $this->db->getConnection()->lastInsertId();

                // Create principal contact using direct SQL to avoid model issues
                if (!empty($request->post('principal_name')) && !empty($request->post('principal_email'))) {
                    $contactData = [
                        'school_id' => $schoolId,
                        'contact_type' => 'principal',
                        'first_name' => $this->extractFirstName($request->post('principal_name')),
                        'last_name' => $this->extractLastName($request->post('principal_name')),
                        'email' => $request->post('principal_email'),
                        'phone' => $request->post('principal_phone'),
                        'position' => 'Principal',
                        'is_primary' => 1,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $contactFields = implode(', ', array_keys($contactData));
                    $contactPlaceholders = ':' . implode(', :', array_keys($contactData));
                    
                    $contactSql = "INSERT INTO contacts ({$contactFields}) VALUES ({$contactPlaceholders})";
                    $this->db->statement($contactSql, $contactData);
                }

                // Create coordinator contact if provided
                if (!empty($request->post('coordinator_name')) && !empty($request->post('coordinator_email'))) {
                    $coordinatorData = [
                        'school_id' => $schoolId,
                        'contact_type' => 'coordinator',
                        'first_name' => $this->extractFirstName($request->post('coordinator_name')),
                        'last_name' => $this->extractLastName($request->post('coordinator_name')),
                        'email' => $request->post('coordinator_email'),
                        'phone' => $request->post('coordinator_phone'),
                        'position' => 'SciBOTICS Coordinator',
                        'is_primary' => 0,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $coordFields = implode(', ', array_keys($coordinatorData));
                    $coordPlaceholders = ':' . implode(', :', array_keys($coordinatorData));
                    
                    $coordSql = "INSERT INTO contacts ({$coordFields}) VALUES ({$coordPlaceholders})";
                    $this->db->statement($coordSql, $coordinatorData);
                }

                $this->db->commit();

                // Log the activity
                $this->logger->info("New school registered: {$schoolData['name']} (ID: {$schoolId}) by user: " . $this->auth->user()->id);

                // Audit log the school creation
                $this->auditLog->logSchoolCreate($schoolId, $schoolData);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'School registered successfully and is pending approval.',
                    'school_id' => $schoolId,
                    'redirect' => $this->baseUrl('/admin/schools')
                ]);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("School form submission error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->logger->error('Error in SchoolManagementController@store: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error registering school: ' . $e->getMessage(),
                'debug_info' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Show detailed school information
     */
    public function show($id)
    {
        // Handle case where $id might be a Request object (router issue)
        if (is_object($id) && method_exists($id, 'getParameter')) {
            $actualId = $id->getParameter('id');
            
            // If parameter is empty, try to extract from URL
            if (empty($actualId) && method_exists($id, 'getUri')) {
                $uri = $id->getUri();
                // Extract ID from URI like /GSCMS/public/admin/schools/32
                if (preg_match('/\/admin\/schools\/(\d+)/', $uri, $matches)) {
                    $actualId = $matches[1];
                }
            }
        } elseif (is_object($id) && isset($id->id)) {
            $actualId = $id->id;
        } else {
            $actualId = $id;
        }
            
            // Bypass model and use direct SQL to avoid parameter issues
            $sql = "SELECT * FROM schools WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$actualId]);
            $school = $stmt->fetch();
            
            if (!$school) {
                // Try without deleted_at condition to see if it exists but is soft deleted
                $sql2 = "SELECT * FROM schools WHERE id = ?";
                $stmt2 = $this->db->getConnection()->prepare($sql2);
                $stmt2->execute([$actualId]);
                $school2 = $stmt2->fetch();
                
                if ($school2) {
                    throw new Exception("School found but appears to be deleted (actualId: $actualId)");
                } else {
                    throw new Exception("School not found at all (actualId: $actualId, type: " . gettype($actualId) . ")");
                }
            }
            
            // Temporarily simplified - skip complex relationships to get basic page working
            $contacts = []; // Contact::getBySchool($actualId);
            $teams = [];    // $school->teams();
            $participants = []; // $school->participants();
            
            // Get school statistics
            $stats = [
                'total_teams' => 0,
                'total_participants' => 0,
                'active_teams' => 0,
                'document_requirements' => []
            ];

            // Get communication history (placeholder for future implementation)
            $communicationHistory = [];

            // Get change log (placeholder for future implementation)
            $changeLog = [];

            $schoolName = $school['name'] ?? 'Unknown School';
            
            $data = [
                'school' => $school,
                'contacts' => $contacts,
                'teams' => $teams,
                'participants' => $participants,
                'stats' => $stats,
                'communicationHistory' => $communicationHistory,
                'changeLog' => $changeLog,
                'title' => 'School Details - ' . $schoolName,
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'School Management', 'url' => '/admin/schools'],
                    ['title' => $schoolName, 'url' => '']
                ]
            ];

            try {
                return $this->view('admin/schools/show', $data);
            } catch (Exception $e) {
                error_log("View rendering error: " . $e->getMessage());
                error_log("Error file: " . $e->getFile() . ":" . $e->getLine());
                error_log("Stack trace: " . $e->getTraceAsString());
                return $this->errorResponse('View rendering failed: ' . $e->getMessage(), 500);
            }
    }

    /**
     * Show school edit form
     */
    public function edit($id)
    {
        try {
            // Get school data using raw query to avoid model issues
            $db = \App\Core\Database::getInstance();
            $school = $db->query("
                SELECT s.*, 
                       d.name as district_name, 
                       d.province as district_province,
                       u.first_name as coordinator_first_name,
                       u.last_name as coordinator_last_name,
                       u.email as coordinator_email
                FROM schools s
                LEFT JOIN districts d ON s.district_id = d.id
                LEFT JOIN users u ON s.coordinator_id = u.id
                WHERE s.id = ? AND s.deleted_at IS NULL
                LIMIT 1
            ", [$id]);

            if (empty($school)) {
                return $this->errorResponse('School not found', 404);
            }

            $school = $school[0]; // Get first result

            // Load districts from database
            $districts = $db->query("
                SELECT id, name, province 
                FROM districts 
                WHERE deleted_at IS NULL 
                ORDER BY province, name
            ");

            $data = [
                'school' => $school,
                'districts' => $districts,
                'provinces' => ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West'],
                'schoolTypes' => ['primary' => 'Primary School', 'secondary' => 'Secondary School', 'combined' => 'Combined School'],
                'quintiles' => [1 => 'Quintile 1', 2 => 'Quintile 2', 3 => 'Quintile 3', 4 => 'Quintile 4', 5 => 'Quintile 5'],
                'statuses' => ['active' => 'Active', 'pending' => 'Pending', 'inactive' => 'Inactive', 'suspended' => 'Suspended'],
                'communicationPrefs' => ['email' => 'Email', 'phone' => 'Phone', 'sms' => 'SMS'],
                'title' => 'Edit School - ' . $school['name'],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'School Management', 'url' => '/admin/schools'],
                    ['title' => $school['name'], 'url' => '/admin/schools/' . $id],
                    ['title' => 'Edit', 'url' => '']
                ]
            ];

            return $this->view('admin/schools/edit', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@edit: ' . $e->getMessage());
            return $this->errorResponse('School not found', 404);
        }
    }

    /**
     * Update school information
     */
    public function update($id)
    {
        try {
            $school = $this->schoolModel->findOrFail($id);
            $request = new Request();
            $validator = new Validator();

            // Modify validation rules for update (allow current values)
            $rules = $this->schoolModel->rules;
            $rules['name'] = str_replace('unique', 'unique:' . $id, $rules['name']);
            $rules['email'] = str_replace('unique', 'unique:' . $id, $rules['email']);
            $rules['registration_number'] = str_replace('unique', 'unique:' . $id, $rules['registration_number']);
            if (isset($rules['emis_number'])) {
                $rules['emis_number'] = str_replace('unique', 'unique:' . $id, $rules['emis_number']);
            }

            $validation = $validator->validate($request->all(), $rules, $this->schoolModel->messages);

            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 422);
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Prepare update data
                $updateData = [
                    'name' => $request->post('name'),
                    'emis_number' => $request->post('emis_number'),
                    'registration_number' => $request->post('registration_number'),
                    'school_type' => $request->post('school_type'),
                    'quintile' => $request->post('quintile'),
                    'district_id' => $request->post('district_id'),
                    'province' => $request->post('province'),
                    'address_line1' => $request->post('address_line1'),
                    'address_line2' => $request->post('address_line2'),
                    'city' => $request->post('city'),
                    'postal_code' => $request->post('postal_code'),
                    'phone' => $request->post('phone'),
                    'fax' => $request->post('fax'),
                    'email' => $request->post('email'),
                    'website' => $request->post('website'),
                    'gps_coordinates' => $request->post('gps_coordinates'),
                    'principal_name' => $request->post('principal_name'),
                    'principal_email' => $request->post('principal_email'),
                    'principal_phone' => $request->post('principal_phone'),
                    'establishment_date' => $request->post('establishment_date'),
                    'total_learners' => $request->post('total_learners'),
                    'facilities' => $request->post('facilities'),
                    'computer_lab' => $request->post('computer_lab'),
                    'internet_status' => $request->post('internet_status'),
                    'accessibility_features' => $request->post('accessibility_features'),
                    'previous_participation' => $request->post('previous_participation'),
                    'communication_preference' => $request->post('communication_preference'),
                    'status' => $request->post('status'),
                    'notes' => $request->post('notes')
                ];

                // Set approval date if status changed to active
                if ($request->post('status') === School::STATUS_ACTIVE && $school['status'] !== School::STATUS_ACTIVE) {
                    $updateData['approval_date'] = date('Y-m-d H:i:s');
                }

                // Update the school
                $this->schoolModel->updateById($id, $updateData);

                // Update primary contact (principal)
                $principalContact = Contact::getPrimaryContact($id);
                $principalData = [
                    'first_name' => $this->extractFirstName($request->post('principal_name')),
                    'last_name' => $this->extractLastName($request->post('principal_name')),
                    'email' => $request->post('principal_email'),
                    'phone' => $request->post('principal_phone')
                ];

                if ($principalContact) {
                    $this->contactModel->updateById($principalContact['id'], $principalData);
                } else {
                    $principalData['school_id'] = $id;
                    $principalData['contact_type'] = Contact::TYPE_PRINCIPAL;
                    $principalData['position'] = 'Principal';
                    $principalData['is_primary'] = 1;
                    $principalData['status'] = Contact::STATUS_ACTIVE;
                    $this->contactModel->create($principalData);
                }

                $this->db->commit();

                // Log the activity
                $this->logger->info("School updated: {$updateData['name']} (ID: {$id}) by user: " . $this->auth->user()->id);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'School updated successfully.',
                    'redirect' => '/admin/schools/' . $id
                ]);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@update: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error updating school: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on schools
     */
    public function bulkAction()
    {
        try {
            $request = new Request();
            $action = $request->post('action');
            $schoolIds = $request->post('school_ids');
            $reason = $request->post('reason');

            if (!$schoolIds || !is_array($schoolIds)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No schools selected.'
                ], 400);
            }

            $result = false;
            $message = '';

            switch ($action) {
                case 'approve':
                    $result = School::bulkUpdateStatus($schoolIds, School::STATUS_ACTIVE, $reason);
                    $message = 'Schools approved successfully.';
                    break;

                case 'suspend':
                    $result = School::bulkUpdateStatus($schoolIds, School::STATUS_SUSPENDED, $reason);
                    $message = 'Schools suspended successfully.';
                    break;

                case 'archive':
                    $result = School::bulkUpdateStatus($schoolIds, School::STATUS_ARCHIVED, $reason);
                    $message = 'Schools archived successfully.';
                    break;

                default:
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Invalid action.'
                    ], 400);
            }

            if ($result) {
                $this->logger->info("Bulk action '{$action}' performed on schools: " . implode(', ', $schoolIds) . " by user: " . $this->auth->user()->id);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error performing bulk action.'
                ], 500);
            }

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@bulkAction: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export schools data
     */
    public function export()
    {
        try {
            $request = new Request();
            $format = $request->get('format', 'csv');
            $criteria = $request->all();

            // Remove non-filter parameters
            unset($criteria['format']);

            $data = School::exportData($criteria);

            switch ($format) {
                case 'csv':
                    return $this->exportCSV($data, 'schools_export_' . date('Y-m-d') . '.csv');
                
                case 'excel':
                    return $this->exportExcel($data, 'schools_export_' . date('Y-m-d') . '.xlsx');
                
                default:
                    return $this->jsonResponse($data);
            }

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@export: ' . $e->getMessage());
            return $this->errorResponse('Error exporting data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get school statistics for dashboard
     */
    private function getSchoolStatistics($schools = [])
    {
        try {
            $total = count($schools);
            
            $statusCounts = [];
            $typeCounts = [];
            $quintileCounts = [];
            
            foreach ($schools as $schoolData) {
                // Status counts
                if (isset($schoolData['status'])) {
                    $status = $schoolData['status'];
                    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                }
                
                // Type counts
                if (isset($schoolData['school_type'])) {
                    $type = $schoolData['school_type'];
                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                }
                
                // Quintile counts
                if (isset($schoolData['quintile']) && $schoolData['quintile']) {
                    $quintile = $schoolData['quintile'];
                    $quintileCounts[$quintile] = ($quintileCounts[$quintile] ?? 0) + 1;
                }
            }

            return [
                'total' => $total,
                'by_status' => $statusCounts,
                'by_type' => $typeCounts,
                'by_quintile' => $quintileCounts
            ];

        } catch (Exception $e) {
            $this->logger->error('Error getting school statistics: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [],
                'by_type' => [],
                'by_quintile' => []
            ];
        }
    }

    /**
     * Helper method to extract first name from full name
     */
    private function extractFirstName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    /**
     * Helper method to extract last name from full name
     */
    private function extractLastName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    }

    /**
     * Export data as CSV
     */
    private function exportCSV($data, $filename)
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'text/csv');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Write header row
            fputcsv($output, array_keys($data[0]));
            
            // Write data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        return $response;
    }

    /**
     * Export data as Excel (basic implementation)
     */
    private function exportExcel($data, $filename)
    {
        // For now, return CSV with Excel MIME type
        // In a full implementation, you would use a library like PhpSpreadsheet
        $response = new Response();
        $response->setHeader('Content-Type', 'application/vnd.ms-excel');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Write header row
            fputcsv($output, array_keys($data[0]));
            
            // Write data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        return $response;
    }

    /**
     * Delete a school (soft delete)
     */
    public function destroy($id)
    {
        try {
            $db = \App\Core\Database::getInstance();
            
            // Check if school exists
            $school = $db->query("SELECT * FROM schools WHERE id = ? AND deleted_at IS NULL", [$id]);
            
            if (empty($school)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'School not found.'
                ], 404);
            }
            
            $school = $school[0];
            
            // Check if school has teams - prevent deletion if teams exist
            $teams = $db->query("SELECT COUNT(*) as count FROM teams WHERE school_id = ? AND deleted_at IS NULL", [$id]);
            $teamCount = $teams[0]['count'] ?? 0;
            
            if ($teamCount > 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cannot delete school that has active teams. Please remove or reassign teams first.',
                    'details' => "This school has {$teamCount} active team(s)."
                ], 409);
            }
            
            // Perform soft delete
            $result = $db->query("
                UPDATE schools 
                SET deleted_at = NOW(), 
                    deleted_by = ? 
                WHERE id = ?
            ", [$this->auth->id(), $id]);
            
            if ($result) {
                // Log the deletion
                $this->logger->info("School deleted", [
                    'school_id' => $id,
                    'school_name' => $school['name'],
                    'deleted_by' => $this->auth->id(),
                    'action' => 'school_deleted'
                ]);
                
                // Audit log the deletion
                $this->auditLog->logSchoolDelete($id, $school);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'School deleted successfully.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete school.'
                ], 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@destroy: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error deleting school: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show audit history for a school
     */
    public function auditHistory($id)
    {
        try {
            // Get school data
            $db = \App\Core\Database::getInstance();
            $school = $db->query("SELECT * FROM schools WHERE id = ? AND deleted_at IS NULL", [$id]);
            
            if (empty($school)) {
                return $this->errorResponse('School not found', 404);
            }
            
            $school = $school[0];
            
            // Get audit history
            $auditHistory = $this->auditLog->getSchoolHistory($id);
            
            $data = [
                'school' => $school,
                'auditHistory' => $auditHistory,
                'title' => 'Audit History - ' . $school['name'],
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'School Management', 'url' => '/admin/schools'],
                    ['title' => $school['name'], 'url' => '/admin/schools/' . $id],
                    ['title' => 'Audit History', 'url' => '']
                ]
            ];

            return $this->view('admin/schools/audit_history', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@auditHistory: ' . $e->getMessage());
            return $this->errorResponse('Error loading audit history: ' . $e->getMessage(), 500);
        }
    }
}