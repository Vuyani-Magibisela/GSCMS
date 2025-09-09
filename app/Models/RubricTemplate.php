<?php
// app/Models/RubricTemplate.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;
use Exception;

class RubricTemplate extends BaseModel
{
    protected $table = 'rubric_templates';
    
    // Define GDE SciBOTICS rubric structures based on competition documentation
    const RUBRIC_STRUCTURES = [
        'JUNIOR' => [
            'game_challenge' => [
                'robot_presentation' => ['max' => 25, 'levels' => 4, 'description' => 'Quality of robot presentation and demonstration'],
                'mission_completion' => ['max' => 30, 'levels' => 4, 'description' => 'Successful completion of age-appropriate missions'],
                'creativity_innovation' => ['max' => 20, 'levels' => 4, 'description' => 'Creative solutions and innovative approaches'],
                'technical_implementation' => ['max' => 25, 'levels' => 4, 'description' => 'Technical execution using Cubroid/BEE Bot']
            ],
            'research_challenge' => [
                'content_knowledge' => ['max' => 5, 'levels' => 4, 'description' => 'Understanding of "Life on Red Planet" theme'],
                'solution_development' => ['max' => 5, 'levels' => 4, 'description' => 'Development of age-appropriate solutions'],
                'model_presentation' => ['max' => 5, 'levels' => 4, 'description' => 'Quality of model/prototype presentation'],
                'communication_skills' => ['max' => 5, 'levels' => 4, 'description' => 'Clear communication of ideas'],
                'teamwork' => ['max' => 5, 'levels' => 4, 'description' => 'Collaboration and teamwork demonstration']
            ]
        ],
        'SPIKE' => [
            'game_challenge' => [
                'robot_design' => ['max' => 25, 'levels' => 4, 'description' => 'LEGO Spike robot design and construction'],
                'programming_logic' => ['max' => 25, 'levels' => 4, 'description' => 'Programming logic and code structure'],
                'mission_accuracy' => ['max' => 30, 'levels' => 4, 'description' => 'Precision in mission completion'],
                'problem_solving' => ['max' => 20, 'levels' => 4, 'description' => 'Creative problem-solving approaches']
            ],
            'research_challenge' => [
                'problem_identification' => ['max' => 5, 'levels' => 4, 'description' => 'Clear identification of "Lost in Space" challenges'],
                'solution_quality' => ['max' => 10, 'levels' => 4, 'description' => 'Quality and feasibility of proposed solutions'],
                'presentation_skills' => ['max' => 5, 'levels' => 4, 'description' => 'Presentation and communication effectiveness'],
                'collaboration' => ['max' => 5, 'levels' => 4, 'description' => 'Team collaboration and coordination']
            ]
        ],
        'ARDUINO' => [
            'game_challenge' => [
                'technical_complexity' => ['max' => 30, 'levels' => 4, 'description' => 'Technical complexity and sophistication'],
                'code_quality' => ['max' => 25, 'levels' => 4, 'description' => 'Code structure, efficiency, and documentation'],
                'mission_performance' => ['max' => 25, 'levels' => 4, 'description' => 'Performance in "Thunderdrome" missions'],
                'hardware_integration' => ['max' => 20, 'levels' => 4, 'description' => 'SciBOT and Arduino hardware integration']
            ],
            'research_challenge' => [
                'research_depth' => ['max' => 10, 'levels' => 4, 'description' => 'Depth of research and understanding'],
                'innovation_impact' => ['max' => 10, 'levels' => 4, 'description' => 'Innovation and potential real-world impact'],
                'technical_documentation' => ['max' => 5, 'levels' => 4, 'description' => 'Quality of technical documentation']
            ]
        ],
        'INVENTOR' => [
            'game_challenge' => [
                'prototype_quality' => ['max' => 30, 'levels' => 4, 'description' => 'Quality and functionality of prototype'],
                'problem_solving' => ['max' => 25, 'levels' => 4, 'description' => 'Creative problem-solving approach'],
                'innovation_creativity' => ['max' => 25, 'levels' => 4, 'description' => 'Innovation and creative thinking'],
                'feasibility_design' => ['max' => 20, 'levels' => 4, 'description' => 'Feasibility and design considerations']
            ],
            'research_challenge' => [
                'real_world_application' => ['max' => 10, 'levels' => 4, 'description' => 'Practical real-world applications'],
                'community_impact' => ['max' => 10, 'levels' => 4, 'description' => 'Potential community and social impact'],
                'sustainability' => ['max' => 5, 'levels' => 4, 'description' => 'Environmental and sustainability considerations']
            ]
        ]
    ];
    
    // Standard 4-level scoring system for GDE SciBOTICS
    const SCORING_LEVELS = [
        1 => ['name' => 'Basic', 'percentage' => 25, 'color' => '#dc3545', 'description' => 'Minimal achievement, needs significant improvement'],
        2 => ['name' => 'Developing', 'percentage' => 50, 'color' => '#ffc107', 'description' => 'Some progress shown, room for improvement'],
        3 => ['name' => 'Accomplished', 'percentage' => 75, 'color' => '#28a745', 'description' => 'Good achievement, meets expectations'],
        4 => ['name' => 'Exceeded', 'percentage' => 100, 'color' => '#007bff', 'description' => 'Exceptional achievement, exceeds expectations']
    ];
    
    public function createCategoryRubric($categoryCode)
    {
        // Map category codes to structure keys
        $categoryMapping = [
            'JUNIOR' => 'JUNIOR',
            'SPIKE_47' => 'SPIKE',
            'SPIKE_89' => 'SPIKE', 
            'EXPLORER_COSMIC' => 'SPIKE',
            'EXPLORER_LOST' => 'SPIKE',
            'ARDUINO_89' => 'ARDUINO',
            'ARDUINO_1011' => 'ARDUINO',
            'ARDUINO_THUNDER' => 'ARDUINO',
            'ARDUINO_YELLOW' => 'ARDUINO',
            'INVENTOR_R3' => 'INVENTOR',
            'INVENTOR_47' => 'INVENTOR',
            'INVENTOR_811' => 'INVENTOR',
            'INVENTOR_JUNIOR' => 'INVENTOR',
            'INVENTOR_MID' => 'INVENTOR',
            'INVENTOR_SENIOR' => 'INVENTOR'
        ];
        
        $structureKey = $categoryMapping[$categoryCode] ?? null;
        
        if (!$structureKey || !isset(self::RUBRIC_STRUCTURES[$structureKey])) {
            throw new Exception("Invalid category code: {$categoryCode}");
        }
        
        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            // Get category information
            $category = $db->query('SELECT * FROM categories WHERE code = ?', [$categoryCode])[0] ?? null;
            if (!$category) {
                throw new Exception("Category not found: {$categoryCode}");
            }
            
            // Create main rubric template
            $templateData = [
                'template_name' => "{$category['name']} Competition Rubric",
                'category_id' => $category['id'],
                'competition_phase_id' => null, // Can be set later for phase-specific rubrics
                'rubric_type' => 'combined',
                'total_points' => 250, // 75 points (game) x 3 multiplier + 25 points (research) = 250
                'version' => '1.0',
                'is_active' => true,
                'created_by' => 1, // System user
                'template_description' => "Official scoring rubric for {$category['name']} category in GDE SciBOTICS 2025"
            ];
            
            $templateId = $db->insert('rubric_templates', $templateData);
            
            // Create sections and criteria
            $structure = self::RUBRIC_STRUCTURES[$structureKey];
            $this->createSections($templateId, $structure);
            
            $db->commit();
            
            return $this->find($templateId);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    private function createSections($templateId, $structure)
    {
        $db = Database::getInstance();
        $displayOrder = 1;
        
        foreach ($structure as $sectionType => $criteriaList) {
            // Calculate section totals
            $maxPoints = array_sum(array_column($criteriaList, 'max'));
            $multiplier = ($sectionType === 'game_challenge') ? 3.00 : 1.00;
            $weight = ($sectionType === 'game_challenge') ? 75.0 : 25.0;
            
            $sectionData = [
                'rubric_template_id' => $templateId,
                'section_name' => $this->formatSectionName($sectionType),
                'section_type' => $sectionType,
                'section_description' => $this->getSectionDescription($sectionType),
                'section_weight' => $weight,
                'max_points' => $maxPoints,
                'multiplier' => $multiplier,
                'display_order' => $displayOrder++,
                'is_required' => true,
                'scoring_instructions' => $this->getScoringInstructions($sectionType)
            ];
            
            $sectionId = $db->insert('rubric_sections', $sectionData);
            
            $this->createCriteria($sectionId, $criteriaList);
        }
    }
    
    private function createCriteria($sectionId, $criteriaList)
    {
        $db = Database::getInstance();
        $displayOrder = 1;
        
        foreach ($criteriaList as $criteriaName => $config) {
            $criteriaData = [
                'section_id' => $sectionId,
                'criteria_name' => $this->formatCriteriaName($criteriaName),
                'criteria_description' => $config['description'],
                'max_points' => $config['max'],
                'weight_percentage' => null, // Equal weighting within section
                'display_order' => $displayOrder++,
                'scoring_type' => 'levels',
                'is_bonus' => false,
                'scoring_notes' => null,
                'validation_rules' => null
            ];
            
            $criteriaId = $db->insert('rubric_criteria', $criteriaData);
            
            $this->createLevels($criteriaId, $config);
        }
    }
    
    private function createLevels($criteriaId, $config)
    {
        $db = Database::getInstance();
        
        foreach (self::SCORING_LEVELS as $levelNum => $levelConfig) {
            $pointsAwarded = ($config['max'] * $levelConfig['percentage']) / 100;
            
            $levelData = [
                'criteria_id' => $criteriaId,
                'level_number' => $levelNum,
                'level_name' => $levelConfig['name'],
                'level_description' => $this->getLevelDescription($levelConfig['name'], $levelConfig['description']),
                'points_awarded' => round($pointsAwarded, 2),
                'percentage_value' => $levelConfig['percentage'],
                'display_color' => $levelConfig['color'],
                'icon_class' => $this->getLevelIcon($levelNum)
            ];
            
            $db->insert('rubric_levels', $levelData);
        }
    }
    
    private function formatSectionName($sectionType)
    {
        return match($sectionType) {
            'game_challenge' => 'Game Challenge',
            'research_challenge' => 'Research Challenge',
            'presentation' => 'Presentation',
            'teamwork' => 'Teamwork',
            'technical' => 'Technical Implementation',
            'innovation' => 'Innovation & Creativity',
            default => ucwords(str_replace('_', ' ', $sectionType))
        };
    }
    
    private function getSectionDescription($sectionType)
    {
        return match($sectionType) {
            'game_challenge' => 'Technical robot performance and mission completion (75% of total score with 3x multiplier)',
            'research_challenge' => 'Research presentation and core values demonstration (25% of total score)',
            'presentation' => 'Communication and presentation skills',
            'teamwork' => 'Collaboration and team dynamics',
            'technical' => 'Technical implementation and complexity',
            'innovation' => 'Creative thinking and innovative solutions',
            default => "Evaluation of {$sectionType} performance"
        };
    }
    
    private function getScoringInstructions($sectionType)
    {
        return match($sectionType) {
            'game_challenge' => 'Evaluate robot performance, mission completion accuracy, and technical implementation. This section has a 3x multiplier.',
            'research_challenge' => 'Assess research quality, presentation skills, and adherence to core values. No multiplier applied.',
            'presentation' => 'Judge communication clarity, organization, and engagement with audience.',
            'teamwork' => 'Observe team collaboration, role distribution, and conflict resolution.',
            default => "Please evaluate all criteria within this section using the 4-level scoring system."
        };
    }
    
    private function formatCriteriaName($criteriaName)
    {
        return ucwords(str_replace('_', ' ', $criteriaName));
    }
    
    private function getLevelDescription($levelName, $genericDescription)
    {
        return "{$levelName}: {$genericDescription}";
    }
    
    private function getLevelIcon($levelNumber)
    {
        return match($levelNumber) {
            1 => 'fas fa-times-circle',      // Basic - red X
            2 => 'fas fa-exclamation-circle', // Developing - yellow warning
            3 => 'fas fa-check-circle',      // Accomplished - green check
            4 => 'fas fa-star',              // Exceeded - blue star
            default => 'fas fa-circle'
        };
    }
    
    public function getRubricWithStructure($id)
    {
        $db = Database::getInstance();
        
        // Get template
        $template = $this->find($id);
        if (!$template) {
            return null;
        }
        
        // Get sections with criteria and levels
        $sections = $db->query('
            SELECT s.*, 
                   COUNT(c.id) as criteria_count,
                   SUM(c.max_points) as section_total_points
            FROM rubric_sections s
            LEFT JOIN rubric_criteria c ON s.id = c.section_id
            WHERE s.rubric_template_id = ?
            GROUP BY s.id
            ORDER BY s.display_order
        ', [$id]);
        
        foreach ($sections as &$section) {
            // Get criteria for this section
            $criteria = $db->query('
                SELECT c.*
                FROM rubric_criteria c
                WHERE c.section_id = ?
                ORDER BY c.display_order
            ', [$section['id']]);
            
            foreach ($criteria as &$criterion) {
                // Get levels for this criterion
                $levels = $db->query('
                    SELECT *
                    FROM rubric_levels
                    WHERE criteria_id = ?
                    ORDER BY level_number
                ', [$criterion['id']]);
                
                $criterion['levels'] = $levels;
            }
            
            $section['criteria'] = $criteria;
        }
        
        $template['sections'] = $sections;
        
        return $template;
    }
    
    public function getActiveTemplateForCategory($categoryId, $phaseId = null)
    {
        $db = Database::getInstance();
        
        $query = 'SELECT * FROM rubric_templates WHERE category_id = ? AND is_active = 1';
        $params = [$categoryId];
        
        if ($phaseId) {
            $query .= ' AND (competition_phase_id = ? OR competition_phase_id IS NULL)';
            $params[] = $phaseId;
        }
        
        $query .= ' ORDER BY competition_phase_id DESC, created_at DESC LIMIT 1';
        
        $templates = $db->query($query, $params);
        
        return !empty($templates) ? $templates[0] : null;
    }
    
    public function validateRubricStructure($templateId)
    {
        $rubric = $this->getRubricWithStructure($templateId);
        
        if (!$rubric) {
            return ['valid' => false, 'errors' => ['Template not found']];
        }
        
        $errors = [];
        
        // Check sections exist
        if (empty($rubric['sections'])) {
            $errors[] = 'No sections defined';
        }
        
        $totalWeight = 0;
        $gameChallengeSections = 0;
        $researchChallengeSections = 0;
        
        foreach ($rubric['sections'] as $section) {
            $totalWeight += $section['section_weight'];
            
            if ($section['section_type'] === 'game_challenge') {
                $gameChallengeSections++;
            } elseif ($section['section_type'] === 'research_challenge') {
                $researchChallengeSections++;
            }
            
            // Check criteria exist
            if (empty($section['criteria'])) {
                $errors[] = "No criteria defined for section: {$section['section_name']}";
            }
            
            foreach ($section['criteria'] as $criterion) {
                // Check levels exist
                if (empty($criterion['levels'])) {
                    $errors[] = "No levels defined for criterion: {$criterion['criteria_name']}";
                } elseif (count($criterion['levels']) !== 4) {
                    $errors[] = "Criterion {$criterion['criteria_name']} must have exactly 4 levels";
                }
            }
        }
        
        // Validate GDE SciBOTICS structure
        if ($gameChallengeSections === 0) {
            $errors[] = 'Missing required Game Challenge section';
        }
        
        if ($researchChallengeSections === 0) {
            $errors[] = 'Missing required Research Challenge section';
        }
        
        if (abs($totalWeight - 100.0) > 0.01) {
            $errors[] = "Section weights must total 100% (currently {$totalWeight}%)";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'summary' => [
                'sections' => count($rubric['sections']),
                'total_criteria' => array_sum(array_column($rubric['sections'], 'criteria_count')),
                'total_weight' => $totalWeight,
                'game_sections' => $gameChallengeSections,
                'research_sections' => $researchChallengeSections
            ]
        ];
    }
}