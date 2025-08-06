<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\District;
use App\Models\School;
use App\Models\User;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use Exception;

class DistrictController extends BaseController
{
    protected $districtModel;
    protected $schoolModel;
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->districtModel = new District();
        $this->schoolModel = new School();
        $this->userModel = new User();
        
        // Ensure user is authenticated and has appropriate permissions
        $this->auth->requireAuth();
        $this->auth->requireAnyRole(['super_admin', 'competition_admin']);
    }

    /**
     * Display the district listing page
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

            if ($request->get('province')) {
                $criteria['province'] = $request->get('province');
            }

            if ($request->get('status')) {
                $criteria['status'] = $request->get('status');
            }

            // Sorting
            $criteria['sort_by'] = $request->get('sort_by', 'name');
            $criteria['sort_order'] = $request->get('sort_order', 'asc');

            // Get districts with proper counts using raw query
            $db = \App\Core\Database::getInstance();
            $districts = $db->query("
                SELECT d.*, 
                       u.first_name as coordinator_first_name,
                       u.last_name as coordinator_last_name, 
                       u.email as coordinator_email,
                       COALESCE(school_counts.school_count, 0) as school_count,
                       COALESCE(team_counts.team_count, 0) as team_count,
                       COALESCE(participant_counts.participant_count, 0) as participant_count
                FROM districts d
                LEFT JOIN users u ON d.coordinator_id = u.id  
                LEFT JOIN (
                    SELECT district_id, COUNT(*) as school_count
                    FROM schools 
                    WHERE deleted_at IS NULL
                    GROUP BY district_id
                ) school_counts ON d.id = school_counts.district_id
                LEFT JOIN (
                    SELECT s.district_id, COUNT(t.id) as team_count
                    FROM schools s
                    LEFT JOIN teams t ON s.id = t.school_id
                    WHERE s.deleted_at IS NULL AND t.deleted_at IS NULL
                    GROUP BY s.district_id
                ) team_counts ON d.id = team_counts.district_id
                LEFT JOIN (
                    SELECT s.district_id, COUNT(p.id) as participant_count
                    FROM schools s
                    LEFT JOIN teams t ON s.id = t.school_id
                    LEFT JOIN participants p ON t.id = p.team_id
                    WHERE s.deleted_at IS NULL AND t.deleted_at IS NULL AND p.deleted_at IS NULL
                    GROUP BY s.district_id
                ) participant_counts ON d.id = participant_counts.district_id
                WHERE d.deleted_at IS NULL
                ORDER BY d.province, d.name
            ");

            // Filter districts based on criteria
            if (!empty($criteria['name'])) {
                $districts = array_filter($districts, function($district) use ($criteria) {
                    return stripos($district['name'], $criteria['name']) !== false;
                });
            }

            if (!empty($criteria['province'])) {
                $districts = array_filter($districts, function($district) use ($criteria) {
                    return $district['province'] === $criteria['province'];
                });
            }

            if (!empty($criteria['status'])) {
                $districts = array_filter($districts, function($district) use ($criteria) {
                    return $district['status'] === $criteria['status'];
                });
            }

            // Get filter options (simplified)
            $provinces = ['Gauteng', 'Western Cape', 'KwaZulu-Natal'];
            $statuses = ['active' => 'Active', 'inactive' => 'Inactive'];

            // Get summary statistics (temporarily disabled)
            $stats = [
                'total' => count($districts),
                'by_status' => [],
                'by_province' => [],
                'total_schools' => 0,
                'total_teams' => 0,
                'total_participants' => 0
            ];

            // Get available coordinators (simplified)
            $availableCoordinators = [];

            $data = [
                'districts' => array_values($districts), // Re-index array after filtering
                'provinces' => $provinces,
                'statuses' => $statuses,
                'availableCoordinators' => $availableCoordinators,
                'stats' => $stats,
                'currentFilters' => $criteria,
                'title' => 'District Management - GSCMS Admin',
                'breadcrumbs' => [
                    ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['title' => 'District Management', 'url' => '']
                ]
            ];

            return $this->view('admin/districts/index', $data);

        } catch (Exception $e) {
            // Log error for debugging
            error_log("District index error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return $this->errorResponse('Error loading districts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show the district creation form
     */
    public function create()
    {
        try {
            $provinces = District::PROVINCES;
            $availableCoordinators = User::getByRole('district_coordinator');
            $statuses = District::getAvailableStatuses();

            $data = [
                'provinces' => $provinces,
                'availableCoordinators' => $availableCoordinators,
                'statuses' => $statuses,
                'title' => 'Create New District',
                'breadcrumbs' => [
                    ['name' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['name' => 'District Management', 'url' => '/admin/districts'],
                    ['name' => 'Create District', 'url' => '']
                ]
            ];

            return $this->view('admin/districts/create', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@create: ' . $e->getMessage());
            return $this->errorResponse('Error loading district form: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a new district
     */
    public function store()
    {
        try {
            $request = new Request();
            $validator = new Validator();

            // Validate the district data
            $validation = $validator->validate($request->all(), $this->districtModel->rules, $this->districtModel->messages);

            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 422);
            }

            // Prepare district data
            $districtData = [
                'name' => $request->post('name'),
                'province' => $request->post('province'),
                'code' => $request->post('code'),
                'region' => $request->post('region'),
                'coordinator_id' => $request->post('coordinator_id') ?: null,
                'description' => $request->post('description'),
                'boundary_coordinates' => $request->post('boundary_coordinates'),
                'status' => $request->post('status') ?: District::STATUS_ACTIVE
            ];

            // Create the district
            $districtId = $this->districtModel->create($districtData);
            $district = $this->districtModel->find($districtId);

            // Log the activity
            $this->logger->info("New district created: {$district['name']} (ID: {$districtId}) by user: " . $this->auth->user()->id);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'District created successfully.',
                'district_id' => $districtId,
                'redirect' => '/admin/districts/' . $districtId
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@store: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error creating district: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show detailed district information
     */
    public function show($id)
    {
        try {
            $district = $this->districtModel->findOrFail($id);
            $schools = School::search(['district_id' => $id]);
            
            // Get district statistics
            $stats = $district->getStats();

            // Get coordinator information
            $coordinator = null;
            if ($district['coordinator_id']) {
                $coordinator = User::find($district['coordinator_id']);
            }

            // Get geographic data for mapping
            $mapData = $this->getDistrictMapData($district);

            $data = [
                'district' => $district,
                'schools' => $schools,
                'coordinator' => $coordinator,
                'stats' => $stats,
                'mapData' => $mapData,
                'title' => 'District Details - ' . $district['name'],
                'breadcrumbs' => [
                    ['name' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['name' => 'District Management', 'url' => '/admin/districts'],
                    ['name' => $district['name'], 'url' => '']
                ]
            ];

            return $this->view('admin/districts/show', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@show: ' . $e->getMessage());
            return $this->errorResponse('District not found', 404);
        }
    }

    /**
     * Show district edit form
     */
    public function edit($id)
    {
        try {
            $district = $this->districtModel->findOrFail($id);
            $provinces = District::PROVINCES;
            $availableCoordinators = User::getByRole('district_coordinator');
            $statuses = District::getAvailableStatuses();

            $data = [
                'district' => $district,
                'provinces' => $provinces,
                'availableCoordinators' => $availableCoordinators,
                'statuses' => $statuses,
                'title' => 'Edit District - ' . $district['name'],
                'breadcrumbs' => [
                    ['name' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ['name' => 'District Management', 'url' => '/admin/districts'],
                    ['name' => $district['name'], 'url' => '/admin/districts/' . $id],
                    ['name' => 'Edit', 'url' => '']
                ]
            ];

            return $this->view('admin/districts/edit', $data);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@edit: ' . $e->getMessage());
            return $this->errorResponse('District not found', 404);
        }
    }

    /**
     * Update district information
     */
    public function update($id)
    {
        try {
            $district = $this->districtModel->findOrFail($id);
            $request = new Request();
            $validator = new Validator();

            // Modify validation rules for update (allow current values)
            $rules = $this->districtModel->rules;
            $rules['name'] = str_replace('unique', 'unique:' . $id, $rules['name']);
            $rules['code'] = str_replace('unique', 'unique:' . $id, $rules['code']);

            $validation = $validator->validate($request->all(), $rules, $this->districtModel->messages);

            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 422);
            }

            // Prepare update data
            $updateData = [
                'name' => $request->post('name'),
                'province' => $request->post('province'),
                'code' => $request->post('code'),
                'region' => $request->post('region'),
                'coordinator_id' => $request->post('coordinator_id') ?: null,
                'description' => $request->post('description'),
                'boundary_coordinates' => $request->post('boundary_coordinates'),
                'status' => $request->post('status')
            ];

            // Update the district
            $this->districtModel->updateById($id, $updateData);

            // Log the activity
            $this->logger->info("District updated: {$updateData['name']} (ID: {$id}) by user: " . $this->auth->user()->id);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'District updated successfully.',
                'redirect' => '/admin/districts/' . $id
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@update: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error updating district: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete district (soft delete)
     */
    public function destroy($id)
    {
        try {
            $district = $this->districtModel->findOrFail($id);

            // Check if district has schools
            $schoolCount = School::search(['district_id' => $id]);
            if (count($schoolCount) > 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cannot delete district that has schools assigned to it. Please reassign or remove schools first.'
                ], 400);
            }

            // Soft delete the district
            $this->districtModel->deleteById($id);

            // Log the activity
            $this->logger->info("District deleted: {$district['name']} (ID: {$id}) by user: " . $this->auth->user()->id);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'District deleted successfully.',
                'redirect' => '/admin/districts'
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@destroy: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error deleting district: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get district schools for AJAX requests
     */
    public function getSchools($id)
    {
        try {
            $schools = School::search(['district_id' => $id]);
            
            $schoolData = array_map(function($school) {
                return [
                    'id' => $school['id'],
                    'name' => $school['name'],
                    'status' => $school['status'],
                    'team_count' => $school['team_count'] ?? 0,
                    'participant_count' => $school['participant_count'] ?? 0,
                    'email' => $school['email'],
                    'phone' => $school['phone']
                ];
            }, $schools);

            return $this->jsonResponse([
                'success' => true,
                'schools' => $schoolData
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@getSchools: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error loading district schools: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export district data
     */
    public function export($id = null)
    {
        try {
            $request = new Request();
            $format = $request->get('format', 'csv');

            if ($id) {
                // Export single district
                $district = $this->districtModel->findOrFail($id);
                $districts = [$district];
                $filename = 'district_' . $district['code'] . '_export_' . date('Y-m-d');
            } else {
                // Export all districts based on current filters
                $criteria = $request->all();
                unset($criteria['format']);
                $districts = District::search($criteria);
                $filename = 'districts_export_' . date('Y-m-d');
            }

            $data = $this->prepareExportData($districts);

            switch ($format) {
                case 'csv':
                    return $this->exportCSV($data, $filename . '.csv');
                case 'excel':
                    return $this->exportExcel($data, $filename . '.xlsx');
                default:
                    return $this->jsonResponse($data);
            }

        } catch (Exception $e) {
            $this->logger->error('Error in DistrictController@export: ' . $e->getMessage());
            return $this->errorResponse('Error exporting data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get district statistics for dashboard
     */
    private function getDistrictStatistics($districts)
    {
        $stats = [
            'total' => count($districts),
            'by_status' => [],
            'by_province' => [],
            'total_schools' => 0,
            'total_teams' => 0,
            'total_participants' => 0
        ];

        foreach ($districts as $district) {
            // Convert to array if it's an object
            $districtData = is_object($district) ? $district->toArray() : $district;
            
            // Status counts
            $status = $districtData['status'];
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

            // Province counts
            $province = $districtData['province'];
            $stats['by_province'][$province] = ($stats['by_province'][$province] ?? 0) + 1;

            // Aggregate totals
            $stats['total_schools'] += $districtData['school_count'] ?? 0;
            $stats['total_teams'] += $districtData['team_count'] ?? 0;
            $stats['total_participants'] += $districtData['participant_count'] ?? 0;
        }

        return $stats;
    }

    /**
     * Get district map data for visualization
     */
    private function getDistrictMapData($district)
    {
        // This would normally integrate with GIS data
        // For now, return basic structure
        return [
            'center' => null, // Would be calculated from boundary coordinates
            'boundaries' => $district['boundary_coordinates'] ? json_decode($district['boundary_coordinates'], true) : null,
            'schools' => [] // Would include school locations
        ];
    }

    /**
     * Prepare data for export
     */
    private function prepareExportData($districts)
    {
        $data = [];
        foreach ($districts as $district) {
            $row = [
                'ID' => $district['id'],
                'Name' => $district['name'],
                'Code' => $district['code'],
                'Province' => $district['province'],
                'Region' => $district['region'] ?? '',
                'Coordinator' => $district['coordinator_first_name'] ? 
                    trim($district['coordinator_first_name'] . ' ' . $district['coordinator_last_name']) : 'None',
                'Coordinator Email' => $district['coordinator_email'] ?? '',
                'Schools' => $district['school_count'] ?? 0,
                'Teams' => $district['team_count'] ?? 0,
                'Participants' => $district['participant_count'] ?? 0,
                'Status' => ucfirst($district['status']),
                'Description' => $district['description'] ?? '',
                'Created' => date('j M Y', strtotime($district['created_at']))
            ];
            $data[] = $row;
        }
        return $data;
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