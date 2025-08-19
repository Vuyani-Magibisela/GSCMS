<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CompetitionSetup;
use App\Models\CompetitionCategory;
use App\Models\Category;
use App\Models\MissionTemplate;
use App\Models\EquipmentCategory;

class CategoryManagerController extends BaseController
{
    private $competitionSetup;
    private $competitionCategory;
    private $category;
    private $missionTemplate;
    private $equipmentCategory;
    
    public function __construct()
    {
        parent::__construct();
        $this->competitionSetup = new CompetitionSetup();
        $this->competitionCategory = new CompetitionCategory();
        $this->category = new Category();
        $this->missionTemplate = new MissionTemplate();
        $this->equipmentCategory = new EquipmentCategory();
    }
    
    /**
     * Display category manager dashboard
     */
    public function index()
    {
        try {
            // Check admin permissions
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            // Get active competitions with category counts
            $competitions = $this->competitionSetup->db->query("
                SELECT cs.*, 
                       COUNT(DISTINCT cc.id) as category_count,
                       SUM(cc.registration_count) as total_registrations
                FROM competition_setups cs
                LEFT JOIN competition_categories cc ON cs.id = cc.competition_id AND cc.deleted_at IS NULL
                WHERE cs.deleted_at IS NULL
                GROUP BY cs.id
                ORDER BY cs.year DESC, cs.start_date DESC
            ");
            
            // Get category statistics
            $categoryStats = $this->getCategoryStatistics();
            
            $data = [
                'competitions' => $competitions,
                'category_statistics' => $categoryStats,
                'page_title' => 'Category Manager'
            ];
            
            return $this->render('admin/category_manager/dashboard', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading category manager dashboard');
            return $this->redirect('/admin/dashboard');
        }
    }
    
    /**
     * Display category overview for competition
     */
    public function overview($competitionId)
    {
        try {
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                $this->setFlashMessage('error', 'Competition not found.');
                return $this->redirect('/admin/category-manager');
            }
            
            // Get competition categories with detailed statistics
            $categories = $this->competitionCategory->db->query("
                SELECT cc.*, c.name as category_name, c.code as original_code,
                       mt.mission_name, mt.difficulty_level,
                       COUNT(DISTINCT t.id) as team_count,
                       COUNT(DISTINCT t.school_id) as school_count,
                       SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) as active_teams
                FROM competition_categories cc
                JOIN categories c ON cc.category_id = c.id
                LEFT JOIN mission_templates mt ON cc.mission_template_id = mt.id
                LEFT JOIN teams t ON cc.category_id = t.category_id AND t.deleted_at IS NULL
                WHERE cc.competition_id = ?
                AND cc.deleted_at IS NULL
                GROUP BY cc.id
                ORDER BY c.name
            ", [$competitionId]);
            
            // Calculate category insights
            $insights = $this->calculateCategoryInsights($categories);
            
            $data = [
                'competition' => $competition,
                'categories' => $categories,
                'insights' => $insights,
                'page_title' => "Categories - {$competition->name}"
            ];
            
            return $this->render('admin/category_manager/overview', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading category overview');
            return $this->redirect('/admin/category-manager');
        }
    }
    
    /**
     * Configure specific category
     */
    public function configureCategory($categoryId)
    {
        try {
            $competitionCategory = $this->competitionCategory->find($categoryId);
            if (!$competitionCategory) {
                $this->setFlashMessage('error', 'Competition category not found.');
                return $this->redirect('/admin/category-manager');
            }
            
            // Get related data
            $competition = $this->competitionSetup->find($competitionCategory->competition_id);
            $baseCategory = $this->category->find($competitionCategory->category_id);
            $missionTemplates = $this->missionTemplate->db->query("
                SELECT * FROM mission_templates 
                WHERE category_id = ? AND deleted_at IS NULL
                ORDER BY difficulty_level, mission_name
            ", [$competitionCategory->category_id]);
            
            // Get equipment requirements
            $equipmentRequirements = $this->equipmentCategory->getEquipmentByCategory($competitionCategory->category_id);
            
            // Get rule templates
            $ruleTemplates = $this->getRuleTemplates($baseCategory->code ?? '');
            
            $data = [
                'competition' => $competition,
                'competition_category' => $competitionCategory,
                'base_category' => $baseCategory,
                'mission_templates' => $missionTemplates,
                'equipment_requirements' => $equipmentRequirements,
                'rule_templates' => $ruleTemplates,
                'grade_options' => $this->getGradeOptions(),
                'page_title' => "Configure {$competitionCategory->name}"
            ];
            
            return $this->render('admin/category_manager/configure', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading category configuration');
            return $this->redirect('/admin/category-manager');
        }
    }
    
    /**
     * Update category configuration
     */
    public function updateCategory()
    {
        try {
            $categoryId = $this->input('category_id');
            $updateData = $this->input('category_data', []);
            
            $competitionCategory = $this->competitionCategory->find($categoryId);
            if (!$competitionCategory) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition category not found'
                ]);
            }
            
            // Validate update data
            $validation = $this->validateCategoryConfiguration($updateData);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ]);
            }
            
            // Update category
            $competitionCategory->name = $updateData['name'] ?? $competitionCategory->name;
            $competitionCategory->grades = json_encode($updateData['grades'] ?? []);
            $competitionCategory->team_size = $updateData['team_size'] ?? $competitionCategory->team_size;
            $competitionCategory->max_teams_per_school = $updateData['max_teams_per_school'] ?? $competitionCategory->max_teams_per_school;
            $competitionCategory->time_limit_minutes = $updateData['time_limit_minutes'] ?? $competitionCategory->time_limit_minutes;
            $competitionCategory->max_attempts = $updateData['max_attempts'] ?? $competitionCategory->max_attempts;
            $competitionCategory->mission_template_id = $updateData['mission_template_id'] ?? $competitionCategory->mission_template_id;
            $competitionCategory->capacity_limit = $updateData['capacity_limit'] ?? $competitionCategory->capacity_limit;
            $competitionCategory->special_requirements = $updateData['special_requirements'] ?? $competitionCategory->special_requirements;
            $competitionCategory->safety_protocols = $updateData['safety_protocols'] ?? $competitionCategory->safety_protocols;
            
            // Update JSON fields
            if (isset($updateData['equipment_requirements'])) {
                $competitionCategory->equipment_requirements = json_encode($updateData['equipment_requirements']);
            }
            if (isset($updateData['scoring_rubric'])) {
                $competitionCategory->scoring_rubric = json_encode($updateData['scoring_rubric']);
            }
            if (isset($updateData['registration_rules'])) {
                $competitionCategory->registration_rules = json_encode($updateData['registration_rules']);
            }
            if (isset($updateData['custom_rules'])) {
                $competitionCategory->custom_rules = json_encode($updateData['custom_rules']);
            }
            
            $competitionCategory->updated_at = date('Y-m-d H:i:s');
            
            if ($competitionCategory->save()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Category configuration updated successfully'
                ]);
            }
            
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to update category configuration'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error updating category: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Customize scoring rubric
     */
    public function customizeRubric()
    {
        try {
            $categoryId = $this->input('category_id');
            $rubricData = $this->input('rubric_data', []);
            
            $competitionCategory = $this->competitionCategory->find($categoryId);
            if (!$competitionCategory) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition category not found'
                ]);
            }
            
            // Validate rubric data
            $validation = $this->validateScoringRubric($rubricData);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ]);
            }
            
            // Update scoring rubric
            $competitionCategory->scoring_rubric = json_encode($rubricData);
            $competitionCategory->updated_at = date('Y-m-d H:i:s');
            
            if ($competitionCategory->save()) {
                // Calculate total possible score
                $totalScore = 0;
                foreach ($rubricData as $criteria => $config) {
                    if (isset($config['max_points'])) {
                        $totalScore += $config['max_points'];
                    }
                }
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Scoring rubric updated successfully',
                    'total_possible_score' => $totalScore
                ]);
            }
            
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to update scoring rubric'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error updating rubric: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Set equipment requirements for category
     */
    public function setEquipmentRequirements()
    {
        try {
            $categoryId = $this->input('category_id');
            $equipmentData = $this->input('equipment_data', []);
            
            $competitionCategory = $this->competitionCategory->find($categoryId);
            if (!$competitionCategory) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition category not found'
                ]);
            }
            
            // Validate equipment requirements
            $validation = $this->validateEquipmentRequirements($equipmentData);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $validation['errors']
                ]);
            }
            
            // Update equipment requirements
            $competitionCategory->equipment_requirements = json_encode($equipmentData);
            $competitionCategory->updated_at = date('Y-m-d H:i:s');
            
            if ($competitionCategory->save()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Equipment requirements updated successfully'
                ]);
            }
            
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to update equipment requirements'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error updating equipment requirements: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validate category rules consistency
     */
    public function validateCategoryRules()
    {
        try {
            $competitionId = $this->input('competition_id');
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition not found'
                ]);
            }
            
            $categories = $this->competitionCategory->getCategoriesByCompetition($competitionId);
            
            // Perform validation checks
            $validationResults = $this->performCategoryRuleValidation($categories);
            
            return $this->jsonResponse([
                'success' => true,
                'validation_results' => $validationResults
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error validating category rules: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bulk update categories
     */
    public function bulkUpdate()
    {
        try {
            $competitionId = $this->input('competition_id');
            $updates = $this->input('updates', []);
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition not found'
                ]);
            }
            
            $updatedCategories = [];
            $errors = [];
            
            foreach ($updates as $categoryId => $updateData) {
                $competitionCategory = $this->competitionCategory->find($categoryId);
                if (!$competitionCategory || $competitionCategory->competition_id != $competitionId) {
                    $errors[] = "Category {$categoryId} not found or not part of this competition";
                    continue;
                }
                
                // Validate update data
                $validation = $this->validateCategoryConfiguration($updateData);
                if (!$validation['valid']) {
                    $errors[] = "Category {$categoryId}: " . implode(', ', $validation['errors']);
                    continue;
                }
                
                // Apply updates
                foreach ($updateData as $field => $value) {
                    if (in_array($field, $competitionCategory->fillable)) {
                        if (in_array($field, ['grades', 'equipment_requirements', 'scoring_rubric', 'registration_rules', 'custom_rules'])) {
                            $competitionCategory->$field = json_encode($value);
                        } else {
                            $competitionCategory->$field = $value;
                        }
                    }
                }
                
                $competitionCategory->updated_at = date('Y-m-d H:i:s');
                
                if ($competitionCategory->save()) {
                    $updatedCategories[] = $categoryId;
                } else {
                    $errors[] = "Failed to update category {$categoryId}";
                }
            }
            
            return $this->jsonResponse([
                'success' => empty($errors),
                'updated_categories' => $updatedCategories,
                'errors' => $errors,
                'message' => empty($errors) ? 'Bulk update completed successfully' : 'Bulk update completed with errors'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error performing bulk update: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Export category configuration
     */
    public function exportConfiguration()
    {
        try {
            $competitionId = $this->input('competition_id');
            $format = $this->input('format', 'json'); // json, csv, xlsx
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition not found'
                ]);
            }
            
            $categories = $this->competitionCategory->getCategoriesByCompetition($competitionId);
            
            // Export in requested format
            $exportData = $this->prepareExportData($categories);
            
            switch ($format) {
                case 'csv':
                    $filename = "category_config_{$competitionId}_" . date('Y-m-d') . ".csv";
                    return $this->exportAsCsv($exportData, $filename);
                case 'xlsx':
                    $filename = "category_config_{$competitionId}_" . date('Y-m-d') . ".xlsx";
                    return $this->exportAsExcel($exportData, $filename);
                default:
                    $filename = "category_config_{$competitionId}_" . date('Y-m-d') . ".json";
                    return $this->exportAsJson($exportData, $filename);
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error exporting configuration: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Import category configuration
     */
    public function importConfiguration()
    {
        try {
            $competitionId = $this->input('competition_id');
            
            if (!isset($_FILES['config_file'])) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'No configuration file uploaded'
                ]);
            }
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Competition not found'
                ]);
            }
            
            // Process uploaded file
            $file = $_FILES['config_file'];
            $importData = $this->processImportFile($file);
            
            if (!$importData) {
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Failed to process import file'
                ]);
            }
            
            // Validate and import data
            $importResults = $this->importCategoryData($competitionId, $importData);
            
            return $this->jsonResponse([
                'success' => $importResults['success'],
                'imported_count' => $importResults['imported_count'],
                'errors' => $importResults['errors'],
                'message' => $importResults['message']
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error importing configuration: ' . $e->getMessage()
            ]);
        }
    }
    
    // PRIVATE HELPER METHODS
    
    /**
     * Get category statistics
     */
    private function getCategoryStatistics()
    {
        return $this->competitionCategory->db->query("
            SELECT 
                COUNT(DISTINCT cc.id) as total_categories,
                COUNT(DISTINCT cc.competition_id) as competitions_with_categories,
                AVG(cc.registration_count) as avg_registrations_per_category,
                SUM(cc.registration_count) as total_registrations,
                COUNT(CASE WHEN cc.is_active = 1 THEN 1 END) as active_categories
            FROM competition_categories cc
            WHERE cc.deleted_at IS NULL
        ")[0] ?? [];
    }
    
    /**
     * Calculate category insights
     */
    private function calculateCategoryInsights($categories)
    {
        $insights = [
            'total_teams' => 0,
            'total_schools' => 0,
            'capacity_utilization' => [],
            'popular_categories' => [],
            'underutilized_categories' => []
        ];
        
        foreach ($categories as $category) {
            $insights['total_teams'] += $category['team_count'];
            $insights['total_schools'] += $category['school_count'];
            
            $utilization = $category['capacity_limit'] ? 
                ($category['team_count'] / $category['capacity_limit']) * 100 : 0;
            
            $insights['capacity_utilization'][] = [
                'category' => $category['name'],
                'utilization' => $utilization
            ];
            
            if ($category['team_count'] > 10) {
                $insights['popular_categories'][] = $category['name'];
            } elseif ($category['team_count'] < 3) {
                $insights['underutilized_categories'][] = $category['name'];
            }
        }
        
        return $insights;
    }
    
    /**
     * Get rule templates based on category type
     */
    private function getRuleTemplates($categoryCode)
    {
        if (strpos(strtoupper($categoryCode), 'INVENTOR') !== false) {
            return $this->getInventorRuleTemplates();
        } else {
            return $this->getRoboticsRuleTemplates();
        }
    }
    
    /**
     * Get robotics rule templates
     */
    private function getRoboticsRuleTemplates()
    {
        return [
            'time_limit' => ['default' => 15, 'min' => 5, 'max' => 30],
            'max_attempts' => ['default' => 3, 'min' => 1, 'max' => 5],
            'team_size' => ['default' => 4, 'min' => 2, 'max' => 6],
            'remote_control' => ['allowed' => false],
            'robot_touching' => ['penalty' => 'time_penalty'],
            'timing_method' => ['options' => ['laser_beam', 'manual', 'sensor']]
        ];
    }
    
    /**
     * Get inventor rule templates
     */
    private function getInventorRuleTemplates()
    {
        return [
            'presentation_time' => ['default' => 10, 'min' => 5, 'max' => 15],
            'team_size' => ['default' => 4, 'min' => 2, 'max' => 6],
            'materials_allowed' => ['any' => true, 'recycled_encouraged' => true],
            'prototype_required' => ['working' => true, 'demonstration' => true],
            'research_component' => ['required' => true, 'presentation' => true]
        ];
    }
    
    /**
     * Get grade options
     */
    private function getGradeOptions()
    {
        return ['R', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'];
    }
    
    /**
     * Validate category configuration
     */
    private function validateCategoryConfiguration($data)
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Category name is required';
        }
        
        if (isset($data['team_size']) && ($data['team_size'] < 1 || $data['team_size'] > 10)) {
            $errors['team_size'] = 'Team size must be between 1 and 10';
        }
        
        if (isset($data['time_limit_minutes']) && ($data['time_limit_minutes'] < 5 || $data['time_limit_minutes'] > 120)) {
            $errors['time_limit_minutes'] = 'Time limit must be between 5 and 120 minutes';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate scoring rubric
     */
    private function validateScoringRubric($rubricData)
    {
        $errors = [];
        
        foreach ($rubricData as $criteria => $config) {
            if (!isset($config['max_points']) || $config['max_points'] < 1) {
                $errors[$criteria] = 'Max points must be at least 1';
            }
            if (!isset($config['levels']) || $config['levels'] < 2) {
                $errors[$criteria] = 'Must have at least 2 scoring levels';
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate equipment requirements
     */
    private function validateEquipmentRequirements($equipmentData)
    {
        $errors = [];
        
        // Basic validation for equipment data structure
        if (!is_array($equipmentData)) {
            $errors['format'] = 'Equipment data must be an array';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Perform category rule validation
     */
    private function performCategoryRuleValidation($categories)
    {
        $results = [
            'consistency_issues' => [],
            'capacity_warnings' => [],
            'rule_conflicts' => [],
            'missing_requirements' => []
        ];
        
        foreach ($categories as $category) {
            // Check for missing mission templates
            if (!$category['mission_template_id']) {
                $results['missing_requirements'][] = [
                    'category' => $category['name'],
                    'issue' => 'No mission template assigned'
                ];
            }
            
            // Check capacity settings
            if ($category['capacity_limit'] && $category['team_count'] > $category['capacity_limit']) {
                $results['capacity_warnings'][] = [
                    'category' => $category['name'],
                    'issue' => 'Current registrations exceed capacity limit'
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Prepare export data
     */
    private function prepareExportData($categories)
    {
        $exportData = [];
        
        foreach ($categories as $category) {
            $exportData[] = [
                'category_code' => $category['category_code'],
                'name' => $category['name'],
                'grades' => $category['grades'],
                'team_size' => $category['team_size'],
                'max_teams_per_school' => $category['max_teams_per_school'],
                'time_limit_minutes' => $category['time_limit_minutes'],
                'max_attempts' => $category['max_attempts'],
                'capacity_limit' => $category['capacity_limit'],
                'mission_template' => $category['mission_name'],
                'equipment_requirements' => $category['equipment_requirements'],
                'scoring_rubric' => $category['scoring_rubric']
            ];
        }
        
        return $exportData;
    }
    
    /**
     * Export as JSON
     */
    private function exportAsJson($data, $filename)
    {
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Export as CSV
     */
    private function exportAsCsv($data, $filename)
    {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Process import file
     */
    private function processImportFile($file)
    {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'json':
                return json_decode(file_get_contents($file['tmp_name']), true);
            case 'csv':
                return $this->parseCsvFile($file['tmp_name']);
            default:
                return false;
        }
    }
    
    /**
     * Parse CSV file
     */
    private function parseCsvFile($filePath)
    {
        $data = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }
    
    /**
     * Import category data
     */
    private function importCategoryData($competitionId, $importData)
    {
        $results = [
            'success' => true,
            'imported_count' => 0,
            'errors' => [],
            'message' => ''
        ];
        
        foreach ($importData as $index => $categoryData) {
            try {
                // Find existing category or create new one
                $competitionCategory = $this->competitionCategory->db->query("
                    SELECT * FROM competition_categories 
                    WHERE competition_id = ? 
                    AND category_code = ? 
                    AND deleted_at IS NULL
                ", [$competitionId, $categoryData['category_code']])[0] ?? null;
                
                if (!$competitionCategory) {
                    // Create new category
                    $competitionCategory = new CompetitionCategory();
                    $competitionCategory->competition_id = $competitionId;
                    $competitionCategory->category_code = $categoryData['category_code'];
                }
                
                // Update fields
                foreach ($categoryData as $field => $value) {
                    if (in_array($field, $competitionCategory->fillable)) {
                        $competitionCategory->$field = $value;
                    }
                }
                
                if ($competitionCategory->save()) {
                    $results['imported_count']++;
                } else {
                    $results['errors'][] = "Row {$index}: Failed to save category";
                }
                
            } catch (Exception $e) {
                $results['errors'][] = "Row {$index}: " . $e->getMessage();
            }
        }
        
        if (!empty($results['errors'])) {
            $results['success'] = false;
            $results['message'] = 'Import completed with errors';
        } else {
            $results['message'] = "Successfully imported {$results['imported_count']} categories";
        }
        
        return $results;
    }
    
    /**
     * Check admin access
     */
    private function hasAdminAccess()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}