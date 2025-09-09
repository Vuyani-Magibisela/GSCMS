<?php
// app/Models/RubricCriterion.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class RubricCriterion extends BaseModel
{
    protected $table = 'rubric_criteria';
    
    public function section()
    {
        $db = Database::getInstance();
        $section = $db->query('SELECT * FROM rubric_sections WHERE id = ?', [$this->section_id]);
        return !empty($section) ? $section[0] : null;
    }
    
    public function levels()
    {
        $db = Database::getInstance();
        return $db->query('
            SELECT * FROM rubric_levels 
            WHERE criteria_id = ? 
            ORDER BY level_number ASC
        ', [$this->id]);
    }
    
    public function getLevel($levelNumber)
    {
        $db = Database::getInstance();
        $level = $db->query('
            SELECT * FROM rubric_levels 
            WHERE criteria_id = ? AND level_number = ?
        ', [$this->id, $levelNumber]);
        
        return !empty($level) ? $level[0] : null;
    }
    
    public function getLevelByPoints($points)
    {
        $levels = $this->levels();
        
        // Find the level that matches the points awarded
        foreach ($levels as $level) {
            if (abs($level['points_awarded'] - $points) < 0.01) {
                return $level;
            }
        }
        
        // If no exact match, find the closest level
        $closestLevel = null;
        $smallestDifference = PHP_FLOAT_MAX;
        
        foreach ($levels as $level) {
            $difference = abs($level['points_awarded'] - $points);
            if ($difference < $smallestDifference) {
                $smallestDifference = $difference;
                $closestLevel = $level;
            }
        }
        
        return $closestLevel;
    }
    
    public function calculatePoints($levelNumber)
    {
        $level = $this->getLevel($levelNumber);
        return $level ? $level['points_awarded'] : 0;
    }
    
    public function getPerformanceLevel($points)
    {
        $percentage = ($this->max_points > 0) ? ($points / $this->max_points) * 100 : 0;
        
        if ($percentage >= 87.5) return 4; // Exceeded (87.5-100%)
        if ($percentage >= 62.5) return 3; // Accomplished (62.5-87.4%)
        if ($percentage >= 37.5) return 2; // Developing (37.5-62.4%)
        return 1; // Basic (0-37.4%)
    }
    
    public function validateCriterionData($data)
    {
        $errors = [];
        
        // Required fields
        if (empty($data['criteria_name'])) {
            $errors[] = 'Criteria name is required';
        }
        
        if (!isset($data['max_points']) || $data['max_points'] <= 0) {
            $errors[] = 'Maximum points must be greater than 0';
        }
        
        if (!isset($data['display_order']) || $data['display_order'] < 1) {
            $errors[] = 'Display order must be at least 1';
        }
        
        // Validate scoring type
        $validTypes = ['points', 'levels', 'percentage', 'binary', 'custom'];
        if (!empty($data['scoring_type']) && !in_array($data['scoring_type'], $validTypes)) {
            $errors[] = 'Invalid scoring type';
        }
        
        // Validate weight percentage
        if (isset($data['weight_percentage']) && ($data['weight_percentage'] < 0 || $data['weight_percentage'] > 100)) {
            $errors[] = 'Weight percentage must be between 0 and 100';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function createCriterion($data)
    {
        $criterion = new self();
        $validation = $criterion->validateCriterionData($data);
        
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        $db->beginTransaction();
        
        try {
            // Create criterion
            $criterionId = $db->insert('rubric_criteria', [
                'section_id' => $data['section_id'],
                'criteria_name' => $data['criteria_name'],
                'criteria_description' => $data['criteria_description'] ?? null,
                'max_points' => $data['max_points'],
                'weight_percentage' => $data['weight_percentage'] ?? null,
                'display_order' => $data['display_order'],
                'scoring_type' => $data['scoring_type'] ?? 'levels',
                'is_bonus' => $data['is_bonus'] ?? false,
                'scoring_notes' => $data['scoring_notes'] ?? null,
                'validation_rules' => $data['validation_rules'] ?? null
            ]);
            
            // Create default 4-level structure if using levels
            if (($data['scoring_type'] ?? 'levels') === 'levels') {
                $criterion->id = $criterionId;
                $criterion->createDefaultLevels($data['max_points']);
            }
            
            $db->commit();
            
            return $criterion->find($criterionId);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    public function createDefaultLevels($maxPoints)
    {
        $db = Database::getInstance();
        
        $levels = [
            1 => ['name' => 'Basic', 'percentage' => 25, 'color' => '#dc3545', 'icon' => 'fas fa-times-circle'],
            2 => ['name' => 'Developing', 'percentage' => 50, 'color' => '#ffc107', 'icon' => 'fas fa-exclamation-circle'],
            3 => ['name' => 'Accomplished', 'percentage' => 75, 'color' => '#28a745', 'icon' => 'fas fa-check-circle'],
            4 => ['name' => 'Exceeded', 'percentage' => 100, 'color' => '#007bff', 'icon' => 'fas fa-star']
        ];
        
        foreach ($levels as $levelNum => $levelConfig) {
            $pointsAwarded = ($maxPoints * $levelConfig['percentage']) / 100;
            
            $db->insert('rubric_levels', [
                'criteria_id' => $this->id,
                'level_number' => $levelNum,
                'level_name' => $levelConfig['name'],
                'level_description' => $this->getDefaultLevelDescription($levelConfig['name']),
                'points_awarded' => round($pointsAwarded, 2),
                'percentage_value' => $levelConfig['percentage'],
                'display_color' => $levelConfig['color'],
                'icon_class' => $levelConfig['icon']
            ]);
        }
    }
    
    private function getDefaultLevelDescription($levelName)
    {
        return match($levelName) {
            'Basic' => 'Minimal achievement of this criterion. Significant improvement needed.',
            'Developing' => 'Some progress shown in this area. Room for improvement remains.',
            'Accomplished' => 'Good achievement that meets expected standards.',
            'Exceeded' => 'Exceptional performance that exceeds expectations.',
            default => 'Performance level: ' . $levelName
        };
    }
    
    public function updateCriterion($data)
    {
        $validation = $this->validateCriterionData($data);
        
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        $db->beginTransaction();
        
        try {
            // Update criterion
            $db->query('
                UPDATE rubric_criteria 
                SET criteria_name = ?, criteria_description = ?, max_points = ?, 
                    weight_percentage = ?, display_order = ?, scoring_type = ?, 
                    is_bonus = ?, scoring_notes = ?, validation_rules = ?,
                    updated_at = NOW()
                WHERE id = ?
            ', [
                $data['criteria_name'],
                $data['criteria_description'] ?? null,
                $data['max_points'],
                $data['weight_percentage'] ?? null,
                $data['display_order'],
                $data['scoring_type'] ?? 'levels',
                $data['is_bonus'] ?? false,
                $data['scoring_notes'] ?? null,
                $data['validation_rules'] ?? null,
                $this->id
            ]);
            
            // Update levels if max points changed
            $currentData = $this->find($this->id);
            if ($currentData['max_points'] != $data['max_points']) {
                $this->updateLevelPoints($data['max_points']);
            }
            
            $db->commit();
            
            return $this->find($this->id);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    public function updateLevelPoints($newMaxPoints)
    {
        $db = Database::getInstance();
        
        $levels = $this->levels();
        
        foreach ($levels as $level) {
            $newPoints = ($newMaxPoints * $level['percentage_value']) / 100;
            
            $db->query('
                UPDATE rubric_levels 
                SET points_awarded = ? 
                WHERE id = ?
            ', [round($newPoints, 2), $level['id']]);
        }
    }
    
    public function duplicateCriterion($newSectionId)
    {
        $db = Database::getInstance();
        
        $db->beginTransaction();
        
        try {
            // Create new criterion
            $criterionData = [
                'section_id' => $newSectionId,
                'criteria_name' => $this->criteria_name,
                'criteria_description' => $this->criteria_description,
                'max_points' => $this->max_points,
                'weight_percentage' => $this->weight_percentage,
                'display_order' => $this->display_order,
                'scoring_type' => $this->scoring_type,
                'is_bonus' => $this->is_bonus,
                'scoring_notes' => $this->scoring_notes,
                'validation_rules' => $this->validation_rules
            ];
            
            $newCriterionId = $db->insert('rubric_criteria', $criterionData);
            
            // Duplicate levels
            $levels = $this->levels();
            foreach ($levels as $level) {
                $db->insert('rubric_levels', [
                    'criteria_id' => $newCriterionId,
                    'level_number' => $level['level_number'],
                    'level_name' => $level['level_name'],
                    'level_description' => $level['level_description'],
                    'points_awarded' => $level['points_awarded'],
                    'percentage_value' => $level['percentage_value'],
                    'display_color' => $level['display_color'],
                    'icon_class' => $level['icon_class']
                ]);
            }
            
            $db->commit();
            
            return self::find($newCriterionId);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    public function getScoringGuidance()
    {
        $levels = $this->levels();
        
        return [
            'criterion_name' => $this->criteria_name,
            'description' => $this->criteria_description,
            'max_points' => $this->max_points,
            'scoring_type' => $this->scoring_type,
            'is_bonus' => $this->is_bonus,
            'notes' => $this->scoring_notes,
            'levels' => array_map(function($level) {
                return [
                    'number' => $level['level_number'],
                    'name' => $level['level_name'],
                    'description' => $level['level_description'],
                    'points' => $level['points_awarded'],
                    'percentage' => $level['percentage_value'],
                    'color' => $level['display_color'],
                    'icon' => $level['icon_class']
                ];
            }, $levels)
        ];
    }
    
    public function validateScore($points, $levelNumber = null)
    {
        $errors = [];
        
        // Check points range
        if ($points < 0 || $points > $this->max_points) {
            $errors[] = "Points must be between 0 and {$this->max_points}";
        }
        
        // Check level validity if provided
        if ($levelNumber !== null) {
            if ($levelNumber < 1 || $levelNumber > 4) {
                $errors[] = "Level must be between 1 and 4";
            }
            
            // Check if points match level
            $expectedPoints = $this->calculatePoints($levelNumber);
            if (abs($expectedPoints - $points) > 0.01) {
                $errors[] = "Points ({$points}) do not match selected level ({$levelNumber}) expected points ({$expectedPoints})";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}