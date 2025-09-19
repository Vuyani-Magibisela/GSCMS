<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CompetitionSetup;
use App\Models\CompetitionPhase;
use App\Models\CompetitionCategory;
use App\Models\CompetitionConfiguration;
use App\Models\Category;
use App\Models\MissionTemplate;
use App\Models\User;

class CompetitionWizardController extends BaseController
{
    private $competitionSetup;
    private $competitionPhase;
    private $competitionCategory;
    private $competitionConfiguration;
    
    public function __construct()
    {
        parent::__construct();
        $this->competitionSetup = new CompetitionSetup();
        $this->competitionPhase = new CompetitionPhase();
        $this->competitionCategory = new CompetitionCategory();
        $this->competitionConfiguration = new CompetitionConfiguration();
    }
    
    /**
     * Display competition setup dashboard
     */
    public function index()
    {
        try {
            // Check admin permissions
            if (!$this->hasAdminAccess()) {
                return $this->redirect('/admin/dashboard');
            }
            
            // Get recent competitions
            $recentCompetitions = $this->competitionSetup->db->query("
                SELECT cs.*, u.name as creator_name
                FROM competition_setups cs
                LEFT JOIN users u ON cs.created_by = u.id
                WHERE cs.deleted_at IS NULL
                ORDER BY cs.created_at DESC
                LIMIT 10
            ");
            
            // Get competition statistics
            $stats = $this->getCompetitionStatistics();
            
            $data = [
                'recent_competitions' => $recentCompetitions,
                'statistics' => $stats,
                'page_title' => 'Competition Setup Dashboard'
            ];
            
            return $this->render('admin/competition_setup/dashboard', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading competition setup dashboard');
            return $this->redirect('/admin/dashboard');
        }
    }
    
    /**
     * Start competition creation wizard
     */
    public function startWizard()
    {
        try {
            // Check admin permissions
            if (!$this->hasAdminAccess()) {
                $this->flashMessage('error', 'Access denied. Admin privileges required.');
                return $this->redirect('/admin/dashboard');
            }
            
            // Create new draft competition
            $wizardId = $this->generateWizardId();
            $this->setSessionData('competition_wizard_id', $wizardId);
            $this->setSessionData('competition_wizard_step', 1);
            $this->setSessionData('competition_wizard_data', []);
            
            return $this->redirect('/admin/competition-setup/wizard/step/1');
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error starting competition wizard');
            return $this->redirect('/admin/competition-setup');
        }
    }
    
    /**
     * Display wizard step
     */
    public function showStep($step = 1)
    {
        try {
            // Validate step number
            $step = (int) $step;
            if ($step < 1 || $step > 6) {
                $this->flashMessage('error', 'Invalid wizard step.');
                return $this->redirect('/admin/competition-setup');
            }
            
            // Check if wizard is active
            if (!$this->getSessionData('competition_wizard_id')) {
                return $this->redirect('/admin/competition-setup/wizard/start');
            }
            
            // Get wizard data
            $wizardData = $this->getSessionData('competition_wizard_data', []);
            
            // Prepare step-specific data
            $data = [
                'step' => $step,
                'wizard_data' => $wizardData,
                'page_title' => "Competition Setup - Step {$step}"
            ];
            
            // Add step-specific data
            switch ($step) {
                case 1:
                    $data = array_merge($data, $this->getStep1Data());
                    break;
                case 2:
                    $data = array_merge($data, $this->getStep2Data($wizardData));
                    break;
                case 3:
                    $data = array_merge($data, $this->getStep3Data($wizardData));
                    break;
                case 4:
                    $data = array_merge($data, $this->getStep4Data($wizardData));
                    break;
                case 5:
                    $data = array_merge($data, $this->getStep5Data($wizardData));
                    break;
                case 6:
                    $data = array_merge($data, $this->getStep6Data($wizardData));
                    break;
            }
            
            return $this->render("admin/competition_setup/wizard/step_{$step}", $data);
            
        } catch (Exception $e) {
            $this->handleError($e, "Error loading wizard step {$step}");
            return $this->redirect('/admin/competition-setup');
        }
    }
    
    /**
     * Save wizard step data
     */
    public function saveStep()
    {
        try {
            // Check if wizard is active
            if (!$this->getSessionData('competition_wizard_id')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Wizard session expired']);
            }
            
            $step = (int) $this->input('step');
            $stepData = $this->input('step_data', []);
            
            // Validate step data
            $validation = $this->validateStepData($step, $stepData);
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'success' => false, 
                    'errors' => $validation['errors']
                ]);
            }
            
            // Save step data to session
            $wizardData = $this->getSessionData('competition_wizard_data', []);
            $wizardData["step_{$step}"] = $stepData;
            $this->setSessionData('competition_wizard_data', $wizardData);
            $this->setSessionData('competition_wizard_step', $step);
            
            // Auto-save draft if enabled
            if ($this->input('auto_save', false)) {
                $this->saveDraftCompetition($wizardData);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'next_step' => $step < 6 ? $step + 1 : null,
                'message' => 'Step data saved successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error saving step data: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Review complete configuration
     */
    public function reviewConfiguration()
    {
        try {
            // Check if wizard is complete
            if (!$this->getSessionData('competition_wizard_id')) {
                $this->flashMessage('error', 'Wizard session expired.');
                return $this->redirect('/admin/competition-setup');
            }
            
            $wizardData = $this->getSessionData('competition_wizard_data', []);
            
            // Validate all steps are complete
            for ($i = 1; $i <= 5; $i++) {
                if (!isset($wizardData["step_{$i}"])) {
                    $this->flashMessage('error', "Step {$i} is incomplete. Please complete all steps.");
                    return $this->redirect("/admin/competition-setup/wizard/step/{$i}");
                }
            }
            
            // Prepare review data
            $reviewData = $this->prepareReviewData($wizardData);
            
            $data = [
                'wizard_data' => $wizardData,
                'review_data' => $reviewData,
                'page_title' => 'Competition Setup - Review Configuration'
            ];
            
            return $this->render('admin/competition_setup/wizard/review', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading configuration review');
            return $this->redirect('/admin/competition-setup');
        }
    }
    
    /**
     * Deploy competition configuration
     */
    public function deployCompetition()
    {
        try {
            // Check if wizard is complete
            if (!$this->getSessionData('competition_wizard_id')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Wizard session expired']);
            }
            
            $wizardData = $this->getSessionData('competition_wizard_data', []);
            $deployMode = $this->input('deploy_mode', 'production'); // test or production
            
            // Create competition setup
            $competition = $this->createCompetitionFromWizard($wizardData, $deployMode);
            
            if (!$competition) {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to create competition']);
            }
            
            // Create phases
            $this->createPhasesFromWizard($competition, $wizardData);
            
            // Create categories
            $this->createCategoriesFromWizard($competition, $wizardData);
            
            // Create configurations
            $this->createConfigurationsFromWizard($competition, $wizardData);
            
            // Clear wizard session
            $this->clearWizardSession();
            
            // Log creation
            $this->logCompetitionCreation($competition);
            
            return $this->jsonResponse([
                'success' => true,
                'competition_id' => $competition->id,
                'redirect_url' => "/admin/competition-setup/view/{$competition->id}",
                'message' => 'Competition created successfully!'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error deploying competition: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * View existing competition
     */
    public function viewCompetition($id)
    {
        try {
            $competition = $this->competitionSetup->find($id);
            if (!$competition) {
                $this->flashMessage('error', 'Competition not found.');
                return $this->redirect('/admin/competition-setup');
            }
            
            // Get related data
            $phases = $this->competitionPhase->getPhasesByCompetition($id);
            $categories = $this->competitionCategory->getCategoriesByCompetition($id);
            $configurations = $this->competitionConfiguration->getConfigurationsByCompetition($id);
            
            $data = [
                'competition' => $competition,
                'phases' => $phases,
                'categories' => $categories,
                'configurations' => $configurations,
                'statistics' => $competition->getOverviewStatistics(),
                'page_title' => $competition->name
            ];
            
            return $this->render('admin/competition_setup/view', $data);
            
        } catch (Exception $e) {
            $this->handleError($e, 'Error loading competition');
            return $this->redirect('/admin/competition-setup');
        }
    }
    
    /**
     * Clone competition for new year
     */
    public function cloneCompetition()
    {
        try {
            $competitionId = $this->input('competition_id');
            $newYear = $this->input('new_year');
            $newName = $this->input('new_name');
            
            $competition = $this->competitionSetup->find($competitionId);
            if (!$competition) {
                return $this->jsonResponse(['success' => false, 'message' => 'Competition not found']);
            }
            
            $newCompetition = $competition->cloneForNewYear($newYear, $newName);
            
            if (!$newCompetition) {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to clone competition']);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'competition_id' => $newCompetition->id,
                'message' => 'Competition cloned successfully!'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Error cloning competition: ' . $e->getMessage()
            ]);
        }
    }
    
    // PRIVATE HELPER METHODS
    
    /**
     * Get Step 1 data (Basic Information)
     */
    private function getStep1Data()
    {
        return [
            'competition_types' => CompetitionSetup::getAvailableTypes(),
            'geographic_scopes' => CompetitionSetup::getAvailableScopes(),
            'current_year' => date('Y'),
            'step_title' => 'Basic Information',
            'step_description' => 'Set up the fundamental details for your competition'
        ];
    }
    
    /**
     * Get Step 2 data (Phase Configuration)
     */
    private function getStep2Data($wizardData)
    {
        $competitionType = $wizardData['step_1']['type'] ?? 'pilot';
        
        return [
            'competition_type' => $competitionType,
            'phase_templates' => $this->getPhaseTemplates($competitionType),
            'step_title' => 'Phase Configuration',
            'step_description' => 'Configure competition phases and timeline'
        ];
    }
    
    /**
     * Get Step 3 data (Category Setup)
     */
    private function getStep3Data($wizardData)
    {
        $categoryModel = new Category();
        $availableCategories = $categoryModel->db->table('categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
        
        $missionTemplateModel = new MissionTemplate();
        $missionTemplates = $missionTemplateModel->db->table('mission_templates')
            ->whereNull('deleted_at')
            ->get();
        
        return [
            'available_categories' => $availableCategories,
            'mission_templates' => $missionTemplates,
            'step_title' => 'Category Setup',
            'step_description' => 'Select and configure competition categories'
        ];
    }
    
    /**
     * Get Step 4 data (Registration Rules)
     */
    private function getStep4Data($wizardData)
    {
        return [
            'step_title' => 'Registration Rules',
            'step_description' => 'Configure registration timeline and requirements'
        ];
    }
    
    /**
     * Get Step 5 data (Competition Rules)
     */
    private function getStep5Data($wizardData)
    {
        return [
            'step_title' => 'Competition Rules',
            'step_description' => 'Set up scoring systems and competition rules'
        ];
    }
    
    /**
     * Get Step 6 data (Review & Deploy)
     */
    private function getStep6Data($wizardData)
    {
        return [
            'review_data' => $this->prepareReviewData($wizardData),
            'step_title' => 'Review & Deploy',
            'step_description' => 'Review configuration and deploy competition'
        ];
    }
    
    /**
     * Validate step data
     */
    private function validateStepData($step, $data)
    {
        $errors = [];
        
        switch ($step) {
            case 1:
                if (empty($data['name'])) $errors['name'] = 'Competition name is required';
                if (empty($data['year'])) $errors['year'] = 'Year is required';
                if (empty($data['type'])) $errors['type'] = 'Competition type is required';
                if (empty($data['start_date'])) $errors['start_date'] = 'Start date is required';
                if (empty($data['end_date'])) $errors['end_date'] = 'End date is required';
                break;
            case 2:
                if (empty($data['phases'])) $errors['phases'] = 'At least one phase must be configured';
                break;
            case 3:
                if (empty($data['categories'])) $errors['categories'] = 'At least one category must be selected';
                break;
            // Add validation for other steps as needed
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Create competition from wizard data
     */
    private function createCompetitionFromWizard($wizardData, $deployMode)
    {
        $step1 = $wizardData['step_1'];
        
        $competition = new CompetitionSetup();
        $competition->name = $step1['name'];
        $competition->year = $step1['year'];
        $competition->type = $step1['type'];
        $competition->status = $deployMode === 'test' ? 'draft' : 'active';
        $competition->start_date = $step1['start_date'];
        $competition->end_date = $step1['end_date'];
        $competition->geographic_scope = $step1['geographic_scope'];
        $competition->description = $step1['description'] ?? '';
        $competition->contact_email = $step1['contact_email'] ?? '';
        $competition->registration_opening = $step1['registration_opening'] ?? null;
        $competition->registration_closing = $step1['registration_closing'] ?? null;
        $competition->created_by = $this->getCurrentUserId();
        
        if ($step2 = $wizardData['step_2'] ?? null) {
            $competition->phase_configuration = json_encode($step2['phases']);
        }
        
        return $competition->save() ? $competition : false;
    }
    
    /**
     * Generate unique wizard ID
     */
    private function generateWizardId()
    {
        return 'wizard_' . uniqid() . '_' . time();
    }
    
    /**
     * Get competition statistics
     */
    private function getCompetitionStatistics()
    {
        return $this->competitionSetup->db->query("
            SELECT 
                COUNT(*) as total_competitions,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_competitions,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_competitions,
                COUNT(CASE WHEN type = 'pilot' THEN 1 END) as pilot_competitions
            FROM competition_setups
            WHERE deleted_at IS NULL
        ")[0] ?? [];
    }
    
    /**
     * Check admin access
     */
    private function hasAdminAccess()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Get current user ID
     */
    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? 1;
    }
    
    /**
     * Clear wizard session
     */
    private function clearWizardSession()
    {
        unset($_SESSION['competition_wizard_id']);
        unset($_SESSION['competition_wizard_step']);
        unset($_SESSION['competition_wizard_data']);
    }
    
    /**
     * Get phase templates
     */
    private function getPhaseTemplates($competitionType)
    {
        if ($competitionType === 'pilot') {
            return [
                'phase_1' => [
                    'name' => 'School-Based Elimination',
                    'enabled' => true,
                    'duration_weeks' => 2,
                    'capacity' => 30
                ],
                'phase_2' => [
                    'name' => 'District Semifinals',
                    'enabled' => false,
                    'note' => 'Disabled for pilot programme'
                ],
                'phase_3' => [
                    'name' => 'Provincial Finals',
                    'enabled' => true,
                    'duration_days' => 1,
                    'capacity' => 6
                ]
            ];
        }
        
        return [
            'phase_1' => [
                'name' => 'School-Based Competition',
                'enabled' => true,
                'capacity' => 'unlimited'
            ],
            'phase_2' => [
                'name' => 'District Semifinals',
                'enabled' => true,
                'capacity' => 15
            ],
            'phase_3' => [
                'name' => 'Provincial Finals',
                'enabled' => true,
                'capacity' => 6
            ]
        ];
    }
    
    /**
     * Prepare review data
     */
    private function prepareReviewData($wizardData)
    {
        // Process wizard data for review display
        return [
            'basic_info' => $wizardData['step_1'] ?? [],
            'phases' => $wizardData['step_2'] ?? [],
            'categories' => $wizardData['step_3'] ?? [],
            'registration' => $wizardData['step_4'] ?? [],
            'rules' => $wizardData['step_5'] ?? []
        ];
    }
    
    /**
     * Create phases from wizard data
     */
    private function createPhasesFromWizard($competition, $wizardData)
    {
        $phaseData = $wizardData['step_2']['phases'] ?? [];
        
        foreach ($phaseData as $phaseNum => $phase) {
            if (!$phase['enabled']) continue;
            
            $competitionPhase = new CompetitionPhase();
            $competitionPhase->competition_id = $competition->id;
            $competitionPhase->phase_number = (int) str_replace('phase_', '', $phaseNum);
            $competitionPhase->name = $phase['name'];
            $competitionPhase->description = $phase['description'] ?? '';
            $competitionPhase->start_date = $phase['start_date'];
            $competitionPhase->end_date = $phase['end_date'];
            $competitionPhase->capacity_per_category = $phase['capacity'] ?? 30;
            $competitionPhase->is_active = true;
            $competitionPhase->phase_order = $competitionPhase->phase_number;
            $competitionPhase->save();
        }
    }
    
    /**
     * Create categories from wizard data
     */
    private function createCategoriesFromWizard($competition, $wizardData)
    {
        $categoryData = $wizardData['step_3']['categories'] ?? [];
        
        foreach ($categoryData as $categoryInfo) {
            $competitionCategory = new CompetitionCategory();
            $competitionCategory->competition_id = $competition->id;
            $competitionCategory->category_id = $categoryInfo['category_id'];
            $competitionCategory->category_code = $categoryInfo['category_code'];
            $competitionCategory->name = $categoryInfo['name'];
            $competitionCategory->grades = json_encode($categoryInfo['grades']);
            $competitionCategory->team_size = $categoryInfo['team_size'] ?? 4;
            $competitionCategory->max_teams_per_school = $categoryInfo['max_teams_per_school'] ?? 3;
            $competitionCategory->mission_template_id = $categoryInfo['mission_template_id'] ?? null;
            $competitionCategory->time_limit_minutes = $categoryInfo['time_limit_minutes'] ?? 15;
            $competitionCategory->max_attempts = $categoryInfo['max_attempts'] ?? 3;
            $competitionCategory->is_active = true;
            $competitionCategory->save();
        }
    }
    
    /**
     * Create configurations from wizard data
     */
    private function createConfigurationsFromWizard($competition, $wizardData)
    {
        // Create default configurations
        $this->competitionConfiguration->createDefaultConfigurations(
            $competition->id, 
            $this->getCurrentUserId()
        );
        
        // Override with wizard-specific configurations
        $rulesData = $wizardData['step_5'] ?? [];
        foreach ($rulesData as $key => $value) {
            $this->competitionConfiguration->setConfigurationValue(
                $competition->id, 
                $key, 
                $value, 
                $this->getCurrentUserId()
            );
        }
    }
    
    /**
     * Save draft competition
     */
    private function saveDraftCompetition($wizardData)
    {
        // Implementation for auto-saving draft
        // This would create a temporary competition record
    }
    
    /**
     * Log competition creation
     */
    private function logCompetitionCreation($competition)
    {
        // Log the creation for audit purposes
        error_log("Competition created: {$competition->name} (ID: {$competition->id}) by user {$this->getCurrentUserId()}");
    }
}