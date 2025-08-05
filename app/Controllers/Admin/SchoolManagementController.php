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
use Exception;

class SchoolManagementController extends BaseController
{
    protected $schoolModel;
    protected $districtModel;
    protected $contactModel;
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->schoolModel = new School();
        $this->districtModel = new District();
        $this->contactModel = new Contact();
        $this->userModel = new User();
        
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

            // Get schools with search/filter criteria - use simplified query first 
            $db = \App\Core\Database::getInstance();
            $schools = $db->query("
                SELECT s.*, 
                       d.name as district_name, 
                       d.province as district_province,
                       u.first_name as coordinator_first_name,
                       u.last_name as coordinator_last_name,
                       u.email as coordinator_email,
                       0 as team_count,
                       0 as participant_count
                FROM schools s
                LEFT JOIN districts d ON s.district_id = d.id
                LEFT JOIN users u ON s.coordinator_id = u.id
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

            // Get summary statistics (temporarily disabled)
            $stats = [
                'total' => count($schools),
                'by_status' => [],
                'by_type' => [],
                'by_quintile' => []
            ];

            // Get schools requiring attention
            $attention = [
                'pending_approvals' => 0,
                'missing_coordinator' => 0,
                'no_teams' => 0
            ]; // Temporarily disabled due to errors

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
            $districts = District::getAll();
            $provinces = School::PROVINCES;
            $schoolTypes = School::getAvailableSchoolTypes();
            $quintiles = School::getAvailableQuintiles();
            $communicationPrefs = School::getCommunicationPreferences();

            $data = [
                'districts' => $districts,
                'provinces' => $provinces,
                'schoolTypes' => $schoolTypes,
                'quintiles' => $quintiles,
                'communicationPrefs' => $communicationPrefs,
                'title' => 'Register New School',
                'breadcrumbs' => [
                    ['name' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['name' => 'School Management', 'url' => '/admin/schools'],
                    ['name' => 'Register School', 'url' => '']
                ]
            ];

            return $this->view('admin/schools/create', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@create: ' . $e->getMessage());
            return $this->errorResponse('Error loading registration form: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a new school registration
     */
    public function store()
    {
        try {
            $request = new Request();
            $validator = new Validator();

            // Validate the school data
            $validation = $validator->validate($request->all(), $this->schoolModel->rules, $this->schoolModel->messages);

            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
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
                    'status' => School::STATUS_PENDING,
                    'registration_date' => date('Y-m-d')
                ];

                // Create the school
                $schoolId = $this->schoolModel->create($schoolData);
                $school = $this->schoolModel->find($schoolId);

                // Create primary contact (principal)
                $principalContactData = [
                    'school_id' => $schoolId,
                    'contact_type' => Contact::TYPE_PRINCIPAL,
                    'first_name' => $this->extractFirstName($request->post('principal_name')),
                    'last_name' => $this->extractLastName($request->post('principal_name')),
                    'position' => 'Principal',
                    'email' => $request->post('principal_email'),
                    'phone' => $request->post('principal_phone'),
                    'is_primary' => 1,
                    'status' => Contact::STATUS_ACTIVE
                ];

                $this->contactModel->create($principalContactData);

                // Create coordinator contact if provided
                if ($request->post('coordinator_name') && $request->post('coordinator_email')) {
                    $coordinatorContactData = [
                        'school_id' => $schoolId,
                        'contact_type' => Contact::TYPE_COORDINATOR,
                        'first_name' => $this->extractFirstName($request->post('coordinator_name')),
                        'last_name' => $this->extractLastName($request->post('coordinator_name')),
                        'position' => 'SciBOTICS Coordinator',
                        'email' => $request->post('coordinator_email'),
                        'phone' => $request->post('coordinator_phone'),
                        'is_primary' => 0,
                        'status' => Contact::STATUS_ACTIVE
                    ];

                    $this->contactModel->create($coordinatorContactData);
                }

                $this->db->commit();

                // Log the activity
                $this->logger->info("New school registered: {$school['name']} (ID: {$schoolId}) by user: " . $this->auth->user()->id);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'School registered successfully and is pending approval.',
                    'school_id' => $schoolId,
                    'redirect' => '/admin/schools/' . $schoolId
                ]);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@store: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error registering school: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show detailed school information
     */
    public function show($id)
    {
        try {
            $school = $this->schoolModel->findOrFail($id);
            $contacts = Contact::getBySchool($id);
            $teams = $school->teams();
            $participants = $school->participants();
            
            // Get school statistics
            $stats = [
                'total_teams' => count($teams),
                'total_participants' => count($participants),
                'active_teams' => count(array_filter($teams, function($team) {
                    return $team['status'] === 'active';
                })),
                'document_requirements' => $school->checkDocumentRequirements()
            ];

            // Get communication history (placeholder for future implementation)
            $communicationHistory = [];

            // Get change log (placeholder for future implementation)
            $changeLog = [];

            $data = [
                'school' => $school,
                'contacts' => $contacts,
                'teams' => $teams,
                'participants' => $participants,
                'stats' => $stats,
                'communicationHistory' => $communicationHistory,
                'changeLog' => $changeLog,
                'title' => 'School Details - ' . $school['name'],
                'breadcrumbs' => [
                    ['name' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['name' => 'School Management', 'url' => '/admin/schools'],
                    ['name' => $school['name'], 'url' => '']
                ]
            ];

            return $this->view('admin/schools/show', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in SchoolManagementController@show: ' . $e->getMessage());
            return $this->errorResponse('School not found', 404);
        }
    }

    /**
     * Show school edit form
     */
    public function edit($id)
    {
        try {
            $school = $this->schoolModel->findOrFail($id);
            $contacts = Contact::getBySchool($id);
            $districts = District::getAll();
            $provinces = School::PROVINCES;
            $schoolTypes = School::getAvailableSchoolTypes();
            $quintiles = School::getAvailableQuintiles();
            $statuses = School::getAvailableStatuses();
            $communicationPrefs = School::getCommunicationPreferences();

            $data = [
                'school' => $school,
                'contacts' => $contacts,
                'districts' => $districts,
                'provinces' => $provinces,
                'schoolTypes' => $schoolTypes,
                'quintiles' => $quintiles,
                'statuses' => $statuses,
                'communicationPrefs' => $communicationPrefs,
                'title' => 'Edit School - ' . $school['name'],
                'breadcrumbs' => [
                    ['name' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['name' => 'School Management', 'url' => '/admin/schools'],
                    ['name' => $school['name'], 'url' => '/admin/schools/' . $id],
                    ['name' => 'Edit', 'url' => '']
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
}