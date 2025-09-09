<?php
// app/Models/RubricSection.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class RubricSection extends BaseModel
{
    protected $table = 'rubric_sections';
    
    public function rubricTemplate()
    {
        $db = Database::getInstance();
        $template = $db->query('SELECT * FROM rubric_templates WHERE id = ?', [$this->rubric_template_id]);
        return !empty($template) ? $template[0] : null;
    }
    
    public function criteria()
    {
        $db = Database::getInstance();
        return $db->query('
            SELECT * FROM rubric_criteria 
            WHERE section_id = ? 
            ORDER BY display_order ASC
        ', [$this->id]);
    }
    
    public function getCriteriaWithLevels()
    {
        $db = Database::getInstance();
        
        $criteria = $db->query('
            SELECT * FROM rubric_criteria 
            WHERE section_id = ? 
            ORDER BY display_order ASC
        ', [$this->id]);
        
        foreach ($criteria as &$criterion) {
            $levels = $db->query('
                SELECT * FROM rubric_levels 
                WHERE criteria_id = ? 
                ORDER BY level_number ASC
            ', [$criterion['id']]);
            
            $criterion['levels'] = $levels;
        }
        
        return $criteria;
    }
    
    public function calculateSectionScore($criteriaScores)
    {
        $totalPoints = 0;
        $maxPossible = 0;
        $scoredCriteria = 0;
        
        $criteria = $this->criteria();
        
        foreach ($criteria as $criterion) {
            $criterionId = $criterion['id'];
            $maxPossible += $criterion['max_points'];
            
            if (isset($criteriaScores[$criterionId])) {
                $totalPoints += $criteriaScores[$criterionId];
                $scoredCriteria++;
            }
        }
        
        // Apply section multiplier
        $finalScore = $totalPoints * $this->multiplier;
        
        return [
            'raw_points' => $totalPoints,
            'final_score' => $finalScore,
            'max_possible' => $maxPossible,
            'max_final' => $maxPossible * $this->multiplier,
            'percentage' => $maxPossible > 0 ? ($totalPoints / $maxPossible) * 100 : 0,
            'criteria_scored' => $scoredCriteria,
            'total_criteria' => count($criteria),
            'is_complete' => $scoredCriteria === count($criteria),
            'multiplier_applied' => $this->multiplier
        ];
    }
    
    public function getWeightedContribution($sectionScore)
    {
        // Calculate this section's contribution to the overall score
        $weightDecimal = $this->section_weight / 100;
        return $sectionScore * $weightDecimal;
    }
    
    public function isGameChallenge()
    {
        return $this->section_type === 'game_challenge';
    }
    
    public function isResearchChallenge()
    {
        return $this->section_type === 'research_challenge';
    }
    
    public function getScoringGuidance()
    {
        $guidance = [
            'section_name' => $this->section_name,
            'section_type' => $this->section_type,
            'weight' => $this->section_weight,
            'multiplier' => $this->multiplier,
            'instructions' => $this->scoring_instructions,
            'is_required' => $this->is_required
        ];
        
        if ($this->isGameChallenge()) {
            $guidance['emphasis'] = 'This section carries significant weight with a 3x multiplier. Focus on technical execution and mission completion.';
            $guidance['time_allocation'] = '60-70% of scoring time should be spent on this section.';
        } elseif ($this->isResearchChallenge()) {
            $guidance['emphasis'] = 'Evaluate core values, research quality, and presentation skills. No multiplier applied.';
            $guidance['time_allocation'] = '30-40% of scoring time should be spent on this section.';
        }
        
        return $guidance;
    }
    
    public function validateSectionData($data)
    {
        $errors = [];
        
        // Required fields
        if (empty($data['section_name'])) {
            $errors[] = 'Section name is required';
        }
        
        if (empty($data['section_type'])) {
            $errors[] = 'Section type is required';
        }
        
        if (!isset($data['section_weight']) || $data['section_weight'] <= 0) {
            $errors[] = 'Section weight must be greater than 0';
        }
        
        if (!isset($data['max_points']) || $data['max_points'] <= 0) {
            $errors[] = 'Maximum points must be greater than 0';
        }
        
        if (!isset($data['display_order']) || $data['display_order'] < 1) {
            $errors[] = 'Display order must be at least 1';
        }
        
        // Validate multiplier
        if (isset($data['multiplier']) && $data['multiplier'] < 0) {
            $errors[] = 'Multiplier cannot be negative';
        }
        
        // Validate section type
        $validTypes = ['game_challenge', 'research_challenge', 'presentation', 'teamwork', 'technical', 'innovation'];
        if (!empty($data['section_type']) && !in_array($data['section_type'], $validTypes)) {
            $errors[] = 'Invalid section type';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function createSection($data)
    {
        $section = new self();
        $validation = $section->validateSectionData($data);
        
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        // Set default multiplier based on section type
        if (!isset($data['multiplier'])) {
            $data['multiplier'] = ($data['section_type'] === 'game_challenge') ? 3.0 : 1.0;
        }
        
        $sectionId = $db->insert('rubric_sections', $data);
        
        return $section->find($sectionId);
    }
    
    public function updateSection($data)
    {
        $validation = $this->validateSectionData($data);
        
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        $db->query('
            UPDATE rubric_sections 
            SET section_name = ?, section_type = ?, section_description = ?, 
                section_weight = ?, max_points = ?, multiplier = ?, 
                display_order = ?, is_required = ?, scoring_instructions = ?,
                updated_at = NOW()
            WHERE id = ?
        ', [
            $data['section_name'],
            $data['section_type'],
            $data['section_description'] ?? null,
            $data['section_weight'],
            $data['max_points'],
            $data['multiplier'] ?? 1.0,
            $data['display_order'],
            $data['is_required'] ?? true,
            $data['scoring_instructions'] ?? null,
            $this->id
        ]);
        
        return $this->find($this->id);
    }
    
    public function reorderCriteria($criteriaIds)
    {
        $db = Database::getInstance();
        
        $db->beginTransaction();
        
        try {
            foreach ($criteriaIds as $order => $criteriaId) {
                $db->query('
                    UPDATE rubric_criteria 
                    SET display_order = ? 
                    WHERE id = ? AND section_id = ?
                ', [$order + 1, $criteriaId, $this->id]);
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    public function duplicateSection($newRubricTemplateId)
    {
        $db = Database::getInstance();
        
        $db->beginTransaction();
        
        try {
            // Create new section
            $sectionData = [
                'rubric_template_id' => $newRubricTemplateId,
                'section_name' => $this->section_name,
                'section_type' => $this->section_type,
                'section_description' => $this->section_description,
                'section_weight' => $this->section_weight,
                'max_points' => $this->max_points,
                'multiplier' => $this->multiplier,
                'display_order' => $this->display_order,
                'is_required' => $this->is_required,
                'scoring_instructions' => $this->scoring_instructions
            ];
            
            $newSectionId = $db->insert('rubric_sections', $sectionData);
            
            // Duplicate criteria
            $criteria = $this->criteria();
            foreach ($criteria as $criterion) {
                $criterionModel = new RubricCriterion();
                $criterionModel->id = $criterion['id'];
                $criterionModel->duplicateCriterion($newSectionId);
            }
            
            $db->commit();
            
            return self::find($newSectionId);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}