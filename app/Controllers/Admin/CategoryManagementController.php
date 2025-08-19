<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Category;
use App\Models\Team;
use App\Models\Participant;

class CategoryManagementController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }
    
    /**
     * Category management dashboard
     */
    public function index()
    {
        $categories = (new Category())->orderBy('name')->get();
        $categoryStats = [];
        
        foreach ($categories as $category) {
            $categoryStats[] = [
                'category' => $category,
                'statistics' => $category->getStatistics()
            ];
        }
        
        return $this->render('admin/category-management/index', [
            'categories' => $categories,
            'category_statistics' => $categoryStats,
            'pilot_categories_count' => 9,
            'available_equipment' => Category::getAvailableEquipmentTypes()
        ]);
    }
    
    /**
     * Create a new category
     */
    public function create()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $data = [
                    'name' => $this->input('name'),
                    'code' => $this->input('code'),
                    'description' => $this->input('description'),
                    'grade_range' => $this->input('grade_range'),
                    'hardware_requirements' => $this->input('hardware_requirements'),
                    'mission_description' => $this->input('mission_description'),
                    'max_team_size' => (int)$this->input('max_team_size', 4),
                    'max_coaches' => (int)$this->input('max_coaches', 2),
                    'scoring_criteria' => $this->input('scoring_criteria') ? json_encode($this->input('scoring_criteria')) : null,
                    'equipment_list' => $this->input('equipment_list'),
                    'rules' => $this->input('rules'),
                    'status' => $this->input('status', 'active')
                ];
                
                // Validate required fields
                if (empty($data['name']) || empty($data['code'])) {
                    throw new \Exception('Category name and code are required');
                }
                
                $category = new Category();
                $category->create($data);
                
                $this->flash('success', 'Category created successfully!');
                return $this->redirect('/admin/category-management');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to create category: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/category-management/create', [
            'available_statuses' => Category::getAvailableStatuses(),
            'available_equipment' => Category::getAvailableEquipmentTypes()
        ]);
    }
    
    /**
     * Edit existing category
     */
    public function edit($id)
    {
        $category = (new Category())->find($id);
        
        if (!$category) {
            $this->flash('error', 'Category not found');
            return $this->redirect('/admin/category-management');
        }
        
        if ($this->request->getMethod() === 'POST') {
            try {
                $data = [
                    'name' => $this->input('name'),
                    'code' => $this->input('code'),
                    'description' => $this->input('description'),
                    'grade_range' => $this->input('grade_range'),
                    'hardware_requirements' => $this->input('hardware_requirements'),
                    'mission_description' => $this->input('mission_description'),
                    'max_team_size' => (int)$this->input('max_team_size', 4),
                    'max_coaches' => (int)$this->input('max_coaches', 2),
                    'scoring_criteria' => $this->input('scoring_criteria') ? json_encode($this->input('scoring_criteria')) : null,
                    'equipment_list' => $this->input('equipment_list'),
                    'rules' => $this->input('rules'),
                    'status' => $this->input('status')
                ];
                
                $category->update($data);
                
                $this->flash('success', 'Category updated successfully!');
                return $this->redirect('/admin/category-management');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to update category: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/category-management/edit', [
            'category' => $category,
            'available_statuses' => Category::getAvailableStatuses(),
            'available_equipment' => Category::getAvailableEquipmentTypes()
        ]);
    }
    
    /**
     * View category details with teams and participants
     */
    public function show($id)
    {
        $category = (new Category())->find($id);
        
        if (!$category) {
            $this->flash('error', 'Category not found');
            return $this->redirect('/admin/category-management');
        }
        
        // Get teams in this category across all phases
        $teams = $this->db->query("
            SELECT 
                t.*,
                p.name as phase_name,
                s.name as school_name,
                COUNT(pt.id) as participant_count,
                COALESCE(AVG(sc.total_score), 0) as average_score
            FROM teams t
            JOIN phases p ON t.phase_id = p.id
            JOIN schools s ON t.school_id = s.id
            LEFT JOIN participants pt ON t.id = pt.team_id AND pt.deleted_at IS NULL
            LEFT JOIN scores sc ON t.id = sc.team_id
            WHERE t.category_id = ?
            AND t.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY p.order_sequence, t.name
        ", [$id]);
        
        // Get category statistics
        $statistics = $category->getStatistics();
        
        // Get phase-wise breakdown
        $phaseBreakdown = $this->db->query("
            SELECT 
                p.name as phase_name,
                p.order_sequence,
                COUNT(t.id) as team_count,
                COUNT(pt.id) as participant_count
            FROM phases p
            LEFT JOIN teams t ON p.id = t.phase_id AND t.category_id = ? AND t.deleted_at IS NULL
            LEFT JOIN participants pt ON t.id = pt.team_id AND pt.deleted_at IS NULL
            GROUP BY p.id
            ORDER BY p.order_sequence
        ", [$id]);
        
        return $this->render('admin/category-management/show', [
            'category' => $category,
            'teams' => $teams,
            'statistics' => $statistics,
            'phase_breakdown' => $phaseBreakdown
        ]);
    }
    
    /**
     * Setup pilot programme categories (9 categories)
     */
    public function setupPilotCategories()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $this->createPilotCategories();
                $this->flash('success', 'Pilot programme categories (9 categories) set up successfully!');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to set up pilot categories: ' . $e->getMessage());
            }
            
            return $this->redirect('/admin/category-management');
        }
        
        $pilotConfig = $this->getPilotCategoriesConfig();
        
        return $this->render('admin/category-management/setup-pilot', [
            'pilot_categories' => $pilotConfig,
            'existing_categories' => (new Category())->get()
        ]);
    }
    
    /**
     * Bulk update category statuses
     */
    public function bulkUpdateStatus()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $categoryIds = $this->input('category_ids', []);
                $action = $this->input('action'); // 'activate', 'deactivate', 'draft'
                
                if (empty($categoryIds)) {
                    throw new \Exception('No categories selected');
                }
                
                $statusMap = [
                    'activate' => 'active',
                    'deactivate' => 'inactive',
                    'draft' => 'draft'
                ];
                
                if (!isset($statusMap[$action])) {
                    throw new \Exception('Invalid action');
                }
                
                $newStatus = $statusMap[$action];
                
                foreach ($categoryIds as $categoryId) {
                    $category = (new Category())->find($categoryId);
                    if ($category) {
                        $category->update(['status' => $newStatus]);
                    }
                }
                
                $count = count($categoryIds);
                $this->flash('success', "Successfully updated {$count} categories to {$newStatus} status");
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to update categories: ' . $e->getMessage());
            }
        }
        
        return $this->redirect('/admin/category-management');
    }
    
    /**
     * Export category data
     */
    public function export($format = 'csv')
    {
        try {
            $includeTeams = $this->input('include_teams', false);
            $includeParticipants = $this->input('include_participants', false);
            
            $categories = (new Category())->orderBy('name')->get();
            $exportData = [];
            
            foreach ($categories as $category) {
                $statistics = $category->getStatistics();
                
                $row = [
                    'Category Name' => $category->name,
                    'Code' => $category->code,
                    'Description' => $category->description,
                    'Grade Range' => $category->grade_range,
                    'Hardware Requirements' => $category->hardware_requirements,
                    'Max Team Size' => $category->max_team_size,
                    'Status' => $category->status,
                    'Total Teams' => $statistics['team_count'],
                    'Total Participants' => $statistics['participant_count'],
                    'Participating Schools' => $statistics['school_count']
                ];
                
                if ($includeTeams || $includeParticipants) {
                    // Add detailed team/participant information
                    $teams = $this->db->query("
                        SELECT t.name, t.team_code, s.name as school_name, p.name as phase_name
                        FROM teams t
                        JOIN schools s ON t.school_id = s.id
                        JOIN phases p ON t.phase_id = p.id
                        WHERE t.category_id = ? AND t.deleted_at IS NULL
                        ORDER BY p.order_sequence, t.name
                    ", [$category->id]);
                    
                    $row['Team Details'] = implode('; ', array_map(function($team) {
                        return "{$team['name']} ({$team['school_name']}, {$team['phase_name']})";
                    }, $teams));
                }
                
                $exportData[] = $row;
            }
            
            if ($format === 'csv') {
                return $this->exportToCsv($exportData, 'categories_export.csv');
            } else if ($format === 'json') {
                return $this->exportToJson($exportData, 'categories_export.json');
            }
            
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to export data: ' . $e->getMessage());
            return $this->redirect('/admin/category-management');
        }
    }
    
    /**
     * Validate category configuration for pilot programme
     */
    public function validatePilotConfiguration()
    {
        $categories = (new Category())->where('status', 'active')->get();
        $validation = [
            'valid' => true,
            'issues' => [],
            'warnings' => []
        ];
        
        // Check for 9 active categories
        if (count($categories) !== 9) {
            $validation['issues'][] = "Expected 9 active categories for pilot programme, found " . count($categories);
            $validation['valid'] = false;
        }
        
        // Check required pilot categories
        $requiredCodes = [
            'JUNIOR', 'EXPLORER_COSMIC', 'EXPLORER_LOST', 
            'ARDUINO_THUNDER', 'ARDUINO_YELLOW',
            'INVENTOR_JUNIOR', 'INVENTOR_INTERMEDIATE', 'INVENTOR_SENIOR',
            'SPECIAL'
        ];
        
        $existingCodes = array_column($categories->toArray(), 'code');
        $missingCodes = array_diff($requiredCodes, $existingCodes);
        
        if (!empty($missingCodes)) {
            $validation['issues'][] = "Missing required pilot categories: " . implode(', ', $missingCodes);
            $validation['valid'] = false;
        }
        
        // Check team size consistency (should be 4 for pilot)
        foreach ($categories as $category) {
            if ($category->max_team_size != 4) {
                $validation['warnings'][] = "Category {$category->name} has team size {$category->max_team_size}, expected 4 for pilot programme";
            }
        }
        
        return $this->json($validation);
    }
    
    /**
     * Create pilot programme categories
     */
    private function createPilotCategories()
    {
        $pilotCategories = $this->getPilotCategoriesConfig();
        
        foreach ($pilotCategories as $categoryData) {
            $category = new Category();
            
            // Check if category already exists
            $existing = $category->where('code', $categoryData['code'])->first();
            
            if ($existing) {
                // Update existing category
                $existing->update($categoryData);
            } else {
                // Create new category
                $category->create($categoryData);
            }
        }
    }
    
    /**
     * Get pilot categories configuration
     */
    private function getPilotCategoriesConfig()
    {
        return [
            [
                'name' => 'Junior Robotics',
                'code' => 'JUNIOR',
                'description' => 'Life on the Red Planet - Grade R-3',
                'grade_range' => 'Grade R-3',
                'hardware_requirements' => 'Cubroid, BEE Bot, etc.',
                'mission_description' => 'Move between Base#1 and Base#2 on the Red Planet',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Explorer - Cosmic Cargo',
                'code' => 'EXPLORER_COSMIC',
                'description' => 'LEGO Spike Intermediate Mission - Grade 4-7',
                'grade_range' => 'Grade 4-7',
                'hardware_requirements' => 'LEGO Spike, EV3, etc.',
                'mission_description' => 'Cosmic Cargo Challenge',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Explorer - Lost in Space',
                'code' => 'EXPLORER_LOST',
                'description' => 'LEGO Spike Advanced Mission - Grade 8-9',
                'grade_range' => 'Grade 8-9',
                'hardware_requirements' => 'LEGO Spike, EV3, etc.',
                'mission_description' => 'Lost in Space Challenge',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Arduino - Thunderdrome',
                'code' => 'ARDUINO_THUNDER',
                'description' => 'Custom Arduino Robot - Grade 8-9',
                'grade_range' => 'Grade 8-9',
                'hardware_requirements' => 'SciBOT, Arduino robots, etc.',
                'mission_description' => 'Thunderdrome Challenge',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Arduino - Mission to Yellow Planet',
                'code' => 'ARDUINO_YELLOW',
                'description' => 'Machine Learning Mission - Grade 10-11',
                'grade_range' => 'Grade 10-11',
                'hardware_requirements' => 'SciBOT, Arduino robots, etc.',
                'mission_description' => 'Mission to the Yellow Planet',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Inventor Junior',
                'code' => 'INVENTOR_JUNIOR',
                'description' => 'Young Inventors Challenge - Grade R-3',
                'grade_range' => 'Grade R-3',
                'hardware_requirements' => 'Arduino Inventor Kit, Any Robotics Kits',
                'mission_description' => 'Blue Planet Mission - Life on Earth Solutions',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Inventor Intermediate',
                'code' => 'INVENTOR_INTERMEDIATE',
                'description' => 'Intermediate Innovation Challenge - Grade 4-7',
                'grade_range' => 'Grade 4-7',
                'hardware_requirements' => 'Arduino Inventor Kit, Any self-designed robots',
                'mission_description' => 'Real World Problem Solutions',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Inventor Senior',
                'code' => 'INVENTOR_SENIOR',
                'description' => 'Advanced Innovation Challenge - Grade 8-11',
                'grade_range' => 'Grade 8-11',
                'hardware_requirements' => 'Any Robotics Kits, Self-designed robots',
                'mission_description' => 'Complex Problem Solutions with Technology',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Special Category',
                'code' => 'SPECIAL',
                'description' => 'Special competition category for unique innovations',
                'grade_range' => 'All Grades',
                'hardware_requirements' => 'Any Technology',
                'mission_description' => 'Open Innovation Challenge',
                'max_team_size' => 4,
                'max_coaches' => 2,
                'status' => 'active'
            ]
        ];
    }
    
    /**
     * Export data to CSV
     */
    private function exportToCsv($data, $filename)
    {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        if (!empty($data)) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
        }
        
        exit;
    }
    
    /**
     * Export data to JSON
     */
    private function exportToJson($data, $filename)
    {
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}