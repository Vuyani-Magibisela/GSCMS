<?php
// app/Models/JudgeProfile.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class JudgeProfile extends BaseModel
{
    protected $table = 'judge_profiles';
    
    public function judge()
    {
        $db = Database::getInstance();
        $judge = $db->query('SELECT * FROM users WHERE id = ? AND user_role = "judge"', [$this->judge_id]);
        return !empty($judge) ? $judge[0] : null;
    }
    
    public function getSpecialtyCategories()
    {
        if (empty($this->specialty_categories)) {
            return [];
        }
        
        $categoryIds = explode(',', $this->specialty_categories);
        $db = Database::getInstance();
        
        $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        $categories = $db->query(
            "SELECT * FROM categories WHERE id IN ({$placeholders})",
            $categoryIds
        );
        
        return $categories;
    }
    
    public function getPreferredCategories()
    {
        if (empty($this->preferred_categories)) {
            return [];
        }
        
        $categoryIds = explode(',', $this->preferred_categories);
        $db = Database::getInstance();
        
        $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        $categories = $db->query(
            "SELECT * FROM categories WHERE id IN ({$placeholders})",
            $categoryIds
        );
        
        return $categories;
    }
    
    public function getCertifications()
    {
        return $this->certifications ? json_decode($this->certifications, true) : [];
    }
    
    public function getContactPreferences()
    {
        return $this->contact_preferences ? json_decode($this->contact_preferences, true) : [];
    }
    
    public function hasSpecialtyInCategory($categoryId)
    {
        if (empty($this->specialty_categories)) {
            return false;
        }
        
        $specialties = explode(',', $this->specialty_categories);
        return in_array($categoryId, array_map('trim', $specialties));
    }
    
    public function getCurrentWorkload($date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $db = Database::getInstance();
        $workload = $db->query("
            SELECT COUNT(*) as current_assignments
            FROM judge_assignments ja
            INNER JOIN tournament_matches tm ON ja.match_id = tm.id
            WHERE ja.judge_id = ? 
            AND DATE(tm.scheduled_time) = ?
            AND ja.status = 'active'
        ", [$this->judge_id, $date]);
        
        return $workload[0]['current_assignments'] ?? 0;
    }
    
    public function isAvailableForAssignment($date = null)
    {
        $currentLoad = $this->getCurrentWorkload($date);
        return $currentLoad < $this->max_assignments_per_day;
    }
    
    public function getAverageCalibrationScore()
    {
        $db = Database::getInstance();
        $result = $db->query("
            SELECT AVG(calibration_score) as avg_score
            FROM judge_calibrations 
            WHERE judge_id = ? 
            AND valid_until > NOW()
        ", [$this->judge_id]);
        
        return $result[0]['avg_score'] ?? 0;
    }
    
    public function getRecentCalibrations($limit = 5)
    {
        $db = Database::getInstance();
        return $db->query("
            SELECT jc.*, c.category_name
            FROM judge_calibrations jc
            LEFT JOIN categories c ON jc.category_id = c.id
            WHERE jc.judge_id = ?
            ORDER BY jc.calibrated_at DESC
            LIMIT ?
        ", [$this->judge_id, $limit]);
    }
    
    public function needsCalibration($categoryId = null)
    {
        $db = Database::getInstance();
        
        if ($categoryId) {
            $calibration = $db->query("
                SELECT id FROM judge_calibrations 
                WHERE judge_id = ? AND category_id = ? AND valid_until > NOW()
            ", [$this->judge_id, $categoryId]);
        } else {
            $calibration = $db->query("
                SELECT id FROM judge_calibrations 
                WHERE judge_id = ? AND valid_until > NOW()
            ", [$this->judge_id]);
        }
        
        return empty($calibration);
    }
    
    public static function createProfile($data)
    {
        $profile = new self();
        $validation = $profile->validateProfileData($data);
        
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        // Check if profile already exists
        $existing = $db->query('SELECT id FROM judge_profiles WHERE judge_id = ?', [$data['judge_id']]);
        if (!empty($existing)) {
            throw new \Exception('Profile already exists for this judge');
        }
        
        $profileData = [
            'judge_id' => $data['judge_id'],
            'experience_level' => $data['experience_level'] ?? 'intermediate',
            'specialty_categories' => isset($data['specialty_categories']) && is_array($data['specialty_categories']) 
                ? implode(',', $data['specialty_categories']) 
                : $data['specialty_categories'],
            'max_assignments_per_day' => $data['max_assignments_per_day'] ?? 10,
            'preferred_categories' => isset($data['preferred_categories']) && is_array($data['preferred_categories']) 
                ? implode(',', $data['preferred_categories']) 
                : $data['preferred_categories'],
            'availability_notes' => $data['availability_notes'] ?? null,
            'bio' => $data['bio'] ?? null,
            'certifications' => isset($data['certifications']) && is_array($data['certifications']) 
                ? json_encode($data['certifications']) 
                : $data['certifications'],
            'languages_spoken' => $data['languages_spoken'] ?? 'English',
            'contact_preferences' => isset($data['contact_preferences']) && is_array($data['contact_preferences']) 
                ? json_encode($data['contact_preferences']) 
                : $data['contact_preferences'],
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
            'accessibility_needs' => $data['accessibility_needs'] ?? null
        ];
        
        $profileId = $db->insert('judge_profiles', $profileData);
        return $profile->find($profileId);
    }
    
    public function updateProfile($data)
    {
        $validation = $this->validateProfileData($data);
        
        if (!$validation['valid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db = Database::getInstance();
        
        $updateData = [];
        $allowedFields = [
            'experience_level', 'specialty_categories', 'max_assignments_per_day',
            'preferred_categories', 'availability_notes', 'bio', 'certifications',
            'languages_spoken', 'contact_preferences', 'emergency_contact',
            'dietary_restrictions', 'accessibility_needs'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, ['specialty_categories', 'preferred_categories']) && is_array($data[$field])) {
                    $updateData[$field] = implode(',', $data[$field]);
                } elseif (in_array($field, ['certifications', 'contact_preferences']) && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $fields = array_keys($updateData);
            $placeholders = array_fill(0, count($fields), '?');
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            
            $values = array_values($updateData);
            $values[] = $this->id;
            
            $db->query("UPDATE judge_profiles SET {$setClause} WHERE id = ?", $values);
        }
        
        return $this->find($this->id);
    }
    
    public function validateProfileData($data)
    {
        $errors = [];
        
        // Validate judge ID
        if (empty($data['judge_id'])) {
            $errors[] = 'Judge ID is required';
        } else {
            $db = Database::getInstance();
            $judge = $db->query('SELECT id FROM users WHERE id = ? AND user_role = "judge"', [$data['judge_id']]);
            if (empty($judge)) {
                $errors[] = 'Invalid judge ID or user is not a judge';
            }
        }
        
        // Validate experience level
        $validLevels = ['novice', 'intermediate', 'advanced', 'expert'];
        if (isset($data['experience_level']) && !in_array($data['experience_level'], $validLevels)) {
            $errors[] = 'Invalid experience level';
        }
        
        // Validate max assignments
        if (isset($data['max_assignments_per_day']) && (!is_numeric($data['max_assignments_per_day']) || $data['max_assignments_per_day'] < 1)) {
            $errors[] = 'Max assignments per day must be a positive number';
        }
        
        // Validate categories if provided
        if (isset($data['specialty_categories']) && !empty($data['specialty_categories'])) {
            $categoryIds = is_array($data['specialty_categories']) 
                ? $data['specialty_categories'] 
                : explode(',', $data['specialty_categories']);
            
            foreach ($categoryIds as $categoryId) {
                if (!is_numeric(trim($categoryId))) {
                    $errors[] = 'Invalid category ID in specialty categories';
                    break;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function getProfileByJudgeId($judgeId)
    {
        $db = Database::getInstance();
        $profile = $db->query('SELECT * FROM judge_profiles WHERE judge_id = ?', [$judgeId]);
        
        if (!empty($profile)) {
            $instance = new self();
            foreach ($profile[0] as $key => $value) {
                $instance->$key = $value;
            }
            return $instance;
        }
        
        return null;
    }
    
    public function getProfileSummary()
    {
        $judge = $this->judge();
        $specialties = $this->getSpecialtyCategories();
        $calibrationScore = $this->getAverageCalibrationScore();
        $currentLoad = $this->getCurrentWorkload();
        
        return [
            'id' => $this->id,
            'judge_name' => $judge ? "{$judge['first_name']} {$judge['last_name']}" : 'Unknown Judge',
            'judge_email' => $judge['email'] ?? null,
            'experience_level' => $this->experience_level,
            'specialty_count' => count($specialties),
            'specialty_categories' => array_column($specialties, 'category_name'),
            'max_assignments_per_day' => $this->max_assignments_per_day,
            'current_workload' => $currentLoad,
            'availability' => $this->isAvailableForAssignment(),
            'calibration_score' => round($calibrationScore, 1),
            'languages_spoken' => $this->languages_spoken,
            'has_accessibility_needs' => !empty($this->accessibility_needs)
        ];
    }
}