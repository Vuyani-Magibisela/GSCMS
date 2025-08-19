<?php

namespace App\Validators;

use App\Core\CompetitionSecurity;

/**
 * Competition Setup Validator
 * Comprehensive validation for competition setup data
 */
class CompetitionSetupValidator
{
    private $security;
    private $errors = [];
    
    public function __construct()
    {
        $this->security = CompetitionSecurity::getInstance();
    }
    
    /**
     * Validate complete competition setup
     */
    public function validateCompetitionSetup($wizardData)
    {
        $this->errors = [];
        
        // Validate each step
        for ($step = 1; $step <= 6; $step++) {
            $stepData = $wizardData["step_{$step}"] ?? [];
            $stepValidation = $this->validateStep($step, $stepData);
            
            if (!$stepValidation['valid']) {
                $this->errors["step_{$step}"] = $stepValidation['errors'];
            }
        }
        
        // Cross-step validation
        $this->validateCrossStepConsistency($wizardData);
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Validate individual step
     */
    public function validateStep($step, $data)
    {
        $this->errors = [];
        
        switch ($step) {
            case 1:
                $this->validateBasicInformation($data);
                break;
            case 2:
                $this->validatePhaseConfiguration($data);
                break;
            case 3:
                $this->validateCategorySetup($data);
                break;
            case 4:
                $this->validateRegistrationRules($data);
                break;
            case 5:
                $this->validateCompetitionRules($data);
                break;
            case 6:
                $this->validateReviewAndDeploy($data);
                break;
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Validate basic information (Step 1)
     */
    private function validateBasicInformation($data)
    {
        // Competition name
        if (empty($data['name'])) {
            $this->addError('name', 'Competition name is required');
        } elseif (strlen($data['name']) < 3) {
            $this->addError('name', 'Competition name must be at least 3 characters');
        } elseif (strlen($data['name']) > 200) {
            $this->addError('name', 'Competition name cannot exceed 200 characters');
        } elseif (!$this->validateCompetitionNameUniqueness($data['name'], $data['year'] ?? null)) {
            $this->addError('name', 'Competition name already exists for this year');
        }
        
        // Year validation
        if (empty($data['year'])) {
            $this->addError('year', 'Competition year is required');
        } else {
            $year = (int) $data['year'];
            $currentYear = (int) date('Y');
            
            if ($year < $currentYear) {
                $this->addError('year', 'Competition year cannot be in the past');
            } elseif ($year > ($currentYear + 5)) {
                $this->addError('year', 'Competition year cannot be more than 5 years in the future');
            }
        }
        
        // Competition type
        if (empty($data['type'])) {
            $this->addError('type', 'Competition type is required');
        } elseif (!in_array($data['type'], ['pilot', 'full', 'special'])) {
            $this->addError('type', 'Invalid competition type');
        }
        
        // Geographic scope
        if (empty($data['geographic_scope'])) {
            $this->addError('geographic_scope', 'Geographic scope is required');
        } elseif (!in_array($data['geographic_scope'], ['district', 'provincial', 'national', 'international'])) {
            $this->addError('geographic_scope', 'Invalid geographic scope');
        }
        
        // Date validation
        if (empty($data['start_date'])) {
            $this->addError('start_date', 'Competition start date is required');
        }
        
        if (empty($data['end_date'])) {
            $this->addError('end_date', 'Competition end date is required');
        }
        
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);
            
            if ($startDate === false || $endDate === false) {
                $this->addError('dates', 'Invalid date format');
            } elseif ($endDate <= $startDate) {
                $this->addError('end_date', 'End date must be after start date');
            } elseif (($endDate - $startDate) > (365 * 24 * 60 * 60)) {
                $this->addError('dates', 'Competition duration cannot exceed 1 year');
            }
        }
        
        // Registration dates
        if (!empty($data['registration_opening']) && !empty($data['registration_closing'])) {
            $regOpen = strtotime($data['registration_opening']);
            $regClose = strtotime($data['registration_closing']);
            
            if ($regClose <= $regOpen) {
                $this->addError('registration_closing', 'Registration closing must be after opening date');
            }
            
            if (!empty($data['start_date']) && $regClose > strtotime($data['start_date'])) {
                $this->addError('registration_closing', 'Registration must close before competition starts');
            }
        }
        
        // Contact email
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $this->addError('contact_email', 'Invalid email address format');
        }
        
        // Description length
        if (!empty($data['description']) && strlen($data['description']) > 2000) {
            $this->addError('description', 'Description cannot exceed 2000 characters');
        }
    }
    
    /**
     * Validate phase configuration (Step 2)
     */
    private function validatePhaseConfiguration($data)
    {
        if (empty($data['phases']) || !is_array($data['phases'])) {
            $this->addError('phases', 'Phase configuration is required');
            return;
        }
        
        $enabledPhases = [];
        $phaseNames = [];
        
        foreach ($data['phases'] as $phaseKey => $phase) {
            if (!empty($phase['enabled'])) {
                $enabledPhases[] = $phaseKey;
                
                // Phase name validation
                if (empty($phase['name'])) {
                    $this->addError("phases.{$phaseKey}.name", 'Phase name is required');
                } else {
                    if (in_array($phase['name'], $phaseNames)) {
                        $this->addError("phases.{$phaseKey}.name", 'Phase names must be unique');
                    }
                    $phaseNames[] = $phase['name'];
                }
                
                // Date validation
                if (empty($phase['start_date'])) {
                    $this->addError("phases.{$phaseKey}.start_date", 'Phase start date is required');
                }
                
                if (empty($phase['end_date'])) {
                    $this->addError("phases.{$phaseKey}.end_date", 'Phase end date is required');
                }
                
                if (!empty($phase['start_date']) && !empty($phase['end_date'])) {
                    $phaseStart = strtotime($phase['start_date']);
                    $phaseEnd = strtotime($phase['end_date']);
                    
                    if ($phaseEnd <= $phaseStart) {
                        $this->addError("phases.{$phaseKey}.end_date", 'Phase end date must be after start date');
                    }
                }
                
                // Capacity validation
                if (isset($phase['capacity'])) {
                    $capacity = (int) $phase['capacity'];
                    if ($capacity < 1 || $capacity > 1000) {
                        $this->addError("phases.{$phaseKey}.capacity", 'Phase capacity must be between 1 and 1000');
                    }
                }
                
                // Advancement criteria validation
                if (!empty($phase['advancement_type'])) {
                    $validTypes = ['top_scores', 'percentage', 'qualified_only', 'all_participants'];
                    if (!in_array($phase['advancement_type'], $validTypes)) {
                        $this->addError("phases.{$phaseKey}.advancement_type", 'Invalid advancement type');
                    }
                    
                    if (in_array($phase['advancement_type'], ['top_scores', 'percentage'])) {
                        if (empty($phase['advancement_value']) || $phase['advancement_value'] <= 0) {
                            $this->addError("phases.{$phaseKey}.advancement_value", 'Advancement value is required');
                        }
                    }
                }
            }
        }
        
        // At least one phase must be enabled
        if (empty($enabledPhases)) {
            $this->addError('phases', 'At least one phase must be enabled');
        }
        
        // Maximum phase limit
        if (count($enabledPhases) > 10) {
            $this->addError('phases', 'Maximum 10 phases allowed');
        }
        
        // Validate phase chronological order
        $this->validatePhaseChronology($data['phases'], $enabledPhases);
    }
    
    /**
     * Validate category setup (Step 3)
     */
    private function validateCategorySetup($data)
    {
        if (empty($data['categories']) || !is_array($data['categories'])) {
            $this->addError('categories', 'At least one category must be configured');
            return;
        }
        
        if (count($data['categories']) > 20) {
            $this->addError('categories', 'Maximum 20 categories allowed');
        }
        
        $categoryNames = [];
        $categoryCodes = [];
        
        foreach ($data['categories'] as $index => $category) {
            // Name validation
            if (empty($category['name'])) {
                $this->addError("categories.{$index}.name", 'Category name is required');
            } else {
                if (in_array($category['name'], $categoryNames)) {
                    $this->addError("categories.{$index}.name", 'Category names must be unique');
                }
                $categoryNames[] = $category['name'];
            }
            
            // Category code validation
            if (empty($category['category_code'])) {
                $this->addError("categories.{$index}.category_code", 'Category code is required');
            } else {
                if (in_array($category['category_code'], $categoryCodes)) {
                    $this->addError("categories.{$index}.category_code", 'Category codes must be unique');
                }
                $categoryCodes[] = $category['category_code'];
            }
            
            // Team size validation
            if (isset($category['team_size'])) {
                $teamSize = (int) $category['team_size'];
                if ($teamSize < 1 || $teamSize > 10) {
                    $this->addError("categories.{$index}.team_size", 'Team size must be between 1 and 10');
                }
            }
            
            // Max teams per school validation
            if (isset($category['max_teams_per_school'])) {
                $maxTeams = (int) $category['max_teams_per_school'];
                if ($maxTeams < 1 || $maxTeams > 20) {
                    $this->addError("categories.{$index}.max_teams_per_school", 'Max teams per school must be between 1 and 20');
                }
            }
            
            // Time limit validation
            if (isset($category['time_limit_minutes'])) {
                $timeLimit = (int) $category['time_limit_minutes'];
                if ($timeLimit < 5 || $timeLimit > 120) {
                    $this->addError("categories.{$index}.time_limit_minutes", 'Time limit must be between 5 and 120 minutes');
                }
            }
            
            // Max attempts validation
            if (isset($category['max_attempts'])) {
                $maxAttempts = (int) $category['max_attempts'];
                if ($maxAttempts < 1 || $maxAttempts > 10) {
                    $this->addError("categories.{$index}.max_attempts", 'Max attempts must be between 1 and 10');
                }
            }
            
            // Grades validation
            if (!empty($category['grades'])) {
                $validGrades = ['R', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'];
                foreach ($category['grades'] as $grade) {
                    if (!in_array($grade, $validGrades)) {
                        $this->addError("categories.{$index}.grades", "Invalid grade: {$grade}");
                    }
                }
            }
        }
    }
    
    /**
     * Validate registration rules (Step 4)
     */
    private function validateRegistrationRules($data)
    {
        // Auto approval validation
        if (isset($data['auto_approval']) && !is_bool($data['auto_approval'])) {
            $this->addError('auto_approval', 'Auto approval must be true or false');
        }
        
        // Late registration validation
        if (isset($data['allow_late_registration']) && !is_bool($data['allow_late_registration'])) {
            $this->addError('allow_late_registration', 'Allow late registration must be true or false');
        }
        
        // Registration fees
        if (isset($data['registration_fee'])) {
            $fee = (float) $data['registration_fee'];
            if ($fee < 0 || $fee > 10000) {
                $this->addError('registration_fee', 'Registration fee must be between 0 and 10000');
            }
        }
        
        // Document requirements
        if (!empty($data['required_documents']) && is_array($data['required_documents'])) {
            $validDocuments = ['consent_form', 'school_letter', 'participant_list', 'emergency_contacts'];
            foreach ($data['required_documents'] as $doc) {
                if (!in_array($doc, $validDocuments)) {
                    $this->addError('required_documents', "Invalid document requirement: {$doc}");
                }
            }
        }
    }
    
    /**
     * Validate competition rules (Step 5)
     */
    private function validateCompetitionRules($data)
    {
        // Scoring method validation
        if (!empty($data['scoring_method'])) {
            $validMethods = ['best_attempt', 'average_attempts', 'last_attempt', 'cumulative'];
            if (!in_array($data['scoring_method'], $validMethods)) {
                $this->addError('scoring_method', 'Invalid scoring method');
            }
        }
        
        // Judge training requirements
        if (isset($data['judge_training_required']) && !is_bool($data['judge_training_required'])) {
            $this->addError('judge_training_required', 'Judge training requirement must be true or false');
        }
        
        // Safety protocols
        if (isset($data['safety_briefing_required']) && !is_bool($data['safety_briefing_required'])) {
            $this->addError('safety_briefing_required', 'Safety briefing requirement must be true or false');
        }
        
        // Appeal process
        if (!empty($data['appeal_deadline_hours'])) {
            $hours = (int) $data['appeal_deadline_hours'];
            if ($hours < 1 || $hours > 168) { // Max 1 week
                $this->addError('appeal_deadline_hours', 'Appeal deadline must be between 1 and 168 hours');
            }
        }
    }
    
    /**
     * Validate review and deploy (Step 6)
     */
    private function validateReviewAndDeploy($data)
    {
        // Deploy mode validation
        if (!empty($data['deploy_mode'])) {
            if (!in_array($data['deploy_mode'], ['test', 'production'])) {
                $this->addError('deploy_mode', 'Invalid deployment mode');
            }
        }
        
        // Confirmation checkboxes
        $requiredConfirmations = ['terms_accepted', 'data_reviewed', 'ready_to_deploy'];
        foreach ($requiredConfirmations as $confirmation) {
            if (empty($data[$confirmation]) || !$data[$confirmation]) {
                $this->addError($confirmation, 'This confirmation is required before deployment');
            }
        }
    }
    
    /**
     * Validate cross-step consistency
     */
    private function validateCrossStepConsistency($wizardData)
    {
        $step1 = $wizardData['step_1'] ?? [];
        $step2 = $wizardData['step_2'] ?? [];
        $step3 = $wizardData['step_3'] ?? [];
        
        // Competition dates vs phase dates
        if (!empty($step1['start_date']) && !empty($step1['end_date']) && !empty($step2['phases'])) {
            $competitionStart = strtotime($step1['start_date']);
            $competitionEnd = strtotime($step1['end_date']);
            
            foreach ($step2['phases'] as $phaseKey => $phase) {
                if (!empty($phase['enabled']) && !empty($phase['start_date']) && !empty($phase['end_date'])) {
                    $phaseStart = strtotime($phase['start_date']);
                    $phaseEnd = strtotime($phase['end_date']);
                    
                    if ($phaseStart < $competitionStart) {
                        $this->addError('cross_validation', "Phase '{$phase['name']}' starts before competition start date");
                    }
                    
                    if ($phaseEnd > $competitionEnd) {
                        $this->addError('cross_validation', "Phase '{$phase['name']}' ends after competition end date");
                    }
                }
            }
        }
        
        // Competition type vs categories
        if (!empty($step1['type']) && $step1['type'] === 'pilot' && !empty($step3['categories'])) {
            if (count($step3['categories']) > 5) {
                $this->addError('cross_validation', 'Pilot competitions should have a maximum of 5 categories');
            }
        }
    }
    
    /**
     * Validate phase chronological order
     */
    private function validatePhaseChronology($phases, $enabledPhases)
    {
        $phaseDates = [];
        
        foreach ($enabledPhases as $phaseKey) {
            $phase = $phases[$phaseKey];
            if (!empty($phase['start_date']) && !empty($phase['end_date'])) {
                $phaseDates[] = [
                    'key' => $phaseKey,
                    'name' => $phase['name'],
                    'start' => strtotime($phase['start_date']),
                    'end' => strtotime($phase['end_date'])
                ];
            }
        }
        
        // Sort by start date
        usort($phaseDates, function($a, $b) {
            return $a['start'] - $b['start'];
        });
        
        // Check for overlaps
        for ($i = 0; $i < count($phaseDates) - 1; $i++) {
            $currentPhase = $phaseDates[$i];
            $nextPhase = $phaseDates[$i + 1];
            
            if ($currentPhase['end'] > $nextPhase['start']) {
                $this->addError('phases_chronology', 
                    "Phase '{$currentPhase['name']}' overlaps with phase '{$nextPhase['name']}'");
            }
        }
    }
    
    /**
     * Validate competition name uniqueness
     */
    private function validateCompetitionNameUniqueness($name, $year)
    {
        // This would check the database for existing competitions
        // For now, we'll implement a basic check
        
        // In a real implementation, you would query the database
        // SELECT COUNT(*) FROM competition_setups WHERE name = ? AND year = ? AND deleted_at IS NULL
        
        return true; // Placeholder
    }
    
    /**
     * Add validation error
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     */
    public function isValid()
    {
        return empty($this->errors);
    }
}