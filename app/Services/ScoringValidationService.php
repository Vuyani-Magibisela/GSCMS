<?php
// app/Services/ScoringValidationService.php

namespace App\Services;

use App\Core\Database;
use App\Models\Score;
use App\Models\RubricCriterion;
use App\Models\JudgeProfile;

class ScoringValidationService
{
    private $db;
    
    const VALIDATION_RULES = [
        'score_range' => [
            'min_percentage' => 0,
            'max_percentage' => 100,
            'absolute_min' => 0,
            'absolute_max' => 250
        ],
        'judge_consistency' => [
            'max_deviation_percentage' => 15,
            'outlier_threshold' => 2.0, // Standard deviations
            'min_judges_for_analysis' => 2
        ],
        'temporal_consistency' => [
            'max_score_change_percentage' => 25,
            'review_window_hours' => 4
        ],
        'logical_consistency' => [
            'level_point_tolerance' => 0.1,
            'section_weight_tolerance' => 5.0
        ]
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Comprehensive score validation
     */
    public function validateScore($scoreData, $options = [])
    {
        $validationResults = [
            'is_valid' => true,
            'warnings' => [],
            'errors' => [],
            'flags' => [],
            'confidence_score' => 100,
            'validation_details' => []
        ];
        
        try {
            // 1. Basic score validation
            $basicValidation = $this->validateBasicScoreRequirements($scoreData);
            $validationResults = $this->mergeValidationResults($validationResults, $basicValidation);
            
            // 2. Range validation
            $rangeValidation = $this->validateScoreRanges($scoreData);
            $validationResults = $this->mergeValidationResults($validationResults, $rangeValidation);
            
            // 3. Logical consistency validation
            $logicalValidation = $this->validateLogicalConsistency($scoreData);
            $validationResults = $this->mergeValidationResults($validationResults, $logicalValidation);
            
            // 4. Judge consistency validation (if multiple judges)
            if (isset($scoreData['team_id']) && isset($scoreData['category_id'])) {
                $consistencyValidation = $this->validateJudgeConsistency($scoreData);
                $validationResults = $this->mergeValidationResults($validationResults, $consistencyValidation);
            }
            
            // 5. Temporal consistency validation
            $temporalValidation = $this->validateTemporalConsistency($scoreData);
            $validationResults = $this->mergeValidationResults($validationResults, $temporalValidation);
            
            // 6. Statistical outlier detection
            $outlierValidation = $this->validateStatisticalOutliers($scoreData);
            $validationResults = $this->mergeValidationResults($validationResults, $outlierValidation);
            
            // 7. Judge qualification validation
            if (isset($scoreData['judge_id'])) {
                $qualificationValidation = $this->validateJudgeQualifications($scoreData);
                $validationResults = $this->mergeValidationResults($validationResults, $qualificationValidation);
            }
            
            // Calculate final confidence score
            $validationResults['confidence_score'] = $this->calculateConfidenceScore($validationResults);
            
            // Determine if score requires review
            $validationResults['requires_review'] = $this->determineReviewRequirement($validationResults);
            
            // Log validation if there are issues
            if (!empty($validationResults['errors']) || !empty($validationResults['warnings'])) {
                $this->logValidationResult($scoreData, $validationResults);
            }
            
        } catch (\Exception $e) {
            $validationResults['is_valid'] = false;
            $validationResults['errors'][] = 'Validation system error: ' . $e->getMessage();
            $validationResults['confidence_score'] = 0;
        }
        
        return $validationResults;
    }
    
    /**
     * Validate basic score requirements
     */
    private function validateBasicScoreRequirements($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        // Required fields
        $requiredFields = ['team_id', 'judge_id', 'category_id', 'score_details'];
        foreach ($requiredFields as $field) {
            if (!isset($scoreData[$field]) || empty($scoreData[$field])) {
                $results['errors'][] = "Required field '{$field}' is missing";
                $results['is_valid'] = false;
            }
        }
        
        // Validate score details structure
        if (isset($scoreData['score_details'])) {
            $scoreDetails = is_string($scoreData['score_details']) 
                ? json_decode($scoreData['score_details'], true) 
                : $scoreData['score_details'];
            
            if (!is_array($scoreDetails) || empty($scoreDetails)) {
                $results['errors'][] = 'Score details must be a valid non-empty array';
                $results['is_valid'] = false;
            } else {
                // Validate each criterion score
                foreach ($scoreDetails as $criterionId => $scoreDetail) {
                    if (!is_numeric($criterionId)) {
                        $results['errors'][] = "Invalid criterion ID: {$criterionId}";
                        $results['is_valid'] = false;
                    }
                    
                    if (!isset($scoreDetail['points']) || !is_numeric($scoreDetail['points'])) {
                        $results['errors'][] = "Invalid points for criterion {$criterionId}";
                        $results['is_valid'] = false;
                    }
                    
                    if (!isset($scoreDetail['level']) || !in_array($scoreDetail['level'], [1, 2, 3, 4])) {
                        $results['errors'][] = "Invalid level for criterion {$criterionId}";
                        $results['is_valid'] = false;
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Validate score ranges
     */
    private function validateScoreRanges($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        if (!isset($scoreData['score_details'])) return $results;
        
        $scoreDetails = is_string($scoreData['score_details']) 
            ? json_decode($scoreData['score_details'], true) 
            : $scoreData['score_details'];
        
        $totalScore = 0;
        
        foreach ($scoreDetails as $criterionId => $scoreDetail) {
            try {
                $criterion = RubricCriterion::find($criterionId);
                if (!$criterion) {
                    $results['errors'][] = "Criterion not found: {$criterionId}";
                    $results['is_valid'] = false;
                    continue;
                }
                
                $points = $scoreDetail['points'];
                $maxPoints = $criterion['max_points'];
                
                // Check absolute range
                if ($points < 0) {
                    $results['errors'][] = "Negative points not allowed for criterion {$criterionId}";
                    $results['is_valid'] = false;
                } elseif ($points > $maxPoints) {
                    $results['errors'][] = "Points ({$points}) exceed maximum ({$maxPoints}) for criterion {$criterionId}";
                    $results['is_valid'] = false;
                }
                
                // Check level consistency
                $expectedPoints = $criterion['calculatePoints']($scoreDetail['level']);
                $pointsDifference = abs($points - $expectedPoints);
                
                if ($pointsDifference > self::VALIDATION_RULES['logical_consistency']['level_point_tolerance']) {
                    $results['warnings'][] = "Points ({$points}) don't match level {$scoreDetail['level']} expected points ({$expectedPoints}) for criterion {$criterionId}";
                }
                
                $totalScore += $points;
                
            } catch (\Exception $e) {
                $results['errors'][] = "Error validating criterion {$criterionId}: " . $e->getMessage();
                $results['is_valid'] = false;
            }
        }
        
        // Validate total score range
        if ($totalScore > self::VALIDATION_RULES['score_range']['absolute_max']) {
            $results['errors'][] = "Total score ({$totalScore}) exceeds maximum allowed (" . self::VALIDATION_RULES['score_range']['absolute_max'] . ")";
            $results['is_valid'] = false;
        }
        
        return $results;
    }
    
    /**
     * Validate logical consistency
     */
    private function validateLogicalConsistency($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        if (!isset($scoreData['score_details'])) return $results;
        
        $scoreDetails = is_string($scoreData['score_details']) 
            ? json_decode($scoreData['score_details'], true) 
            : $scoreData['score_details'];
        
        // Check for logical patterns in scoring
        $levelCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        $totalCriteria = count($scoreDetails);
        
        foreach ($scoreDetails as $criterionId => $scoreDetail) {
            $level = $scoreDetail['level'];
            $levelCounts[$level]++;
        }
        
        // Flag unusual patterns
        if ($levelCounts[4] > $totalCriteria * 0.8) {
            $results['flags'][] = 'High concentration of "Exceeded" levels (80%+) - verify exceptional performance';
        }
        
        if ($levelCounts[1] > $totalCriteria * 0.6) {
            $results['flags'][] = 'High concentration of "Basic" levels (60%+) - verify struggling performance';
        }
        
        if ($levelCounts[2] === 0 && $levelCounts[3] === 0 && ($levelCounts[1] > 0 || $levelCounts[4] > 0)) {
            $results['warnings'][] = 'Extreme scoring pattern detected - no middle levels used';
        }
        
        return $results;
    }
    
    /**
     * Validate judge consistency across multiple judges
     */
    private function validateJudgeConsistency($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        // Get other scores for the same team/category
        $otherScores = $this->db->query("
            SELECT s.*, sd.total_score
            FROM scores s
            INNER JOIN score_details sd ON s.id = sd.score_id
            WHERE s.team_id = ? 
            AND s.category_id = ? 
            AND s.judge_id != ?
            AND s.status = 'final'
        ", [$scoreData['team_id'], $scoreData['category_id'], $scoreData['judge_id']]);
        
        if (count($otherScores) < self::VALIDATION_RULES['judge_consistency']['min_judges_for_analysis']) {
            return $results; // Not enough data for consistency analysis
        }
        
        $currentTotalScore = $this->calculateTotalScore($scoreData['score_details']);
        $otherTotalScores = array_column($otherScores, 'total_score');
        
        // Calculate statistics
        $avgOtherScore = array_sum($otherTotalScores) / count($otherTotalScores);
        $deviation = abs($currentTotalScore - $avgOtherScore);
        $deviationPercentage = ($avgOtherScore > 0) ? ($deviation / $avgOtherScore) * 100 : 0;
        
        if ($deviationPercentage > self::VALIDATION_RULES['judge_consistency']['max_deviation_percentage']) {
            $results['warnings'][] = "Score significantly differs from other judges (deviation: {$deviationPercentage:.1f}%)";
            $results['flags'][] = 'judge_consistency_issue';
        }
        
        // Statistical outlier detection
        if (count($otherTotalScores) >= 3) {
            $allScores = array_merge($otherTotalScores, [$currentTotalScore]);
            $mean = array_sum($allScores) / count($allScores);
            $stdDev = $this->calculateStandardDeviation($allScores, $mean);
            
            $zScore = $stdDev > 0 ? abs($currentTotalScore - $mean) / $stdDev : 0;
            
            if ($zScore > self::VALIDATION_RULES['judge_consistency']['outlier_threshold']) {
                $results['flags'][] = 'statistical_outlier';
                $results['warnings'][] = "Score is statistical outlier (z-score: {$zScore:.2f})";
            }
        }
        
        return $results;
    }
    
    /**
     * Validate temporal consistency
     */
    private function validateTemporalConsistency($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        if (!isset($scoreData['judge_id'])) return $results;
        
        // Check for recent scores by the same judge
        $recentScores = $this->db->query("
            SELECT s.*, sd.total_score
            FROM scores s
            INNER JOIN score_details sd ON s.id = sd.score_id
            WHERE s.judge_id = ?
            AND s.created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY s.created_at DESC
            LIMIT 5
        ", [$scoreData['judge_id'], self::VALIDATION_RULES['temporal_consistency']['review_window_hours']]);
        
        if (empty($recentScores)) return $results;
        
        $currentTotalScore = $this->calculateTotalScore($scoreData['score_details']);
        $recentTotalScores = array_column($recentScores, 'total_score');
        
        foreach ($recentTotalScores as $recentScore) {
            $changePercentage = ($recentScore > 0) ? abs($currentTotalScore - $recentScore) / $recentScore * 100 : 0;
            
            if ($changePercentage > self::VALIDATION_RULES['temporal_consistency']['max_score_change_percentage']) {
                $results['warnings'][] = "Significant scoring pattern change from recent scores (change: {$changePercentage:.1f}%)";
                $results['flags'][] = 'temporal_inconsistency';
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Validate statistical outliers
     */
    private function validateStatisticalOutliers($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        if (!isset($scoreData['category_id'])) return $results;
        
        // Get category score distribution
        $categoryScores = $this->db->query("
            SELECT sd.total_score
            FROM scores s
            INNER JOIN score_details sd ON s.id = sd.score_id
            WHERE s.category_id = ?
            AND s.status = 'final'
            AND s.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$scoreData['category_id']]);
        
        if (count($categoryScores) < 10) return $results; // Not enough data
        
        $totalScores = array_column($categoryScores, 'total_score');
        $currentScore = $this->calculateTotalScore($scoreData['score_details']);
        
        // Calculate quartiles
        sort($totalScores);
        $count = count($totalScores);
        $q1 = $totalScores[floor($count * 0.25)];
        $q3 = $totalScores[floor($count * 0.75)];
        $iqr = $q3 - $q1;
        
        // Detect outliers using IQR method
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        
        if ($currentScore < $lowerBound || $currentScore > $upperBound) {
            $results['flags'][] = 'category_outlier';
            $results['warnings'][] = "Score is outlier for category (score: {$currentScore}, range: {$lowerBound}-{$upperBound})";
        }
        
        return $results;
    }
    
    /**
     * Validate judge qualifications
     */
    private function validateJudgeQualifications($scoreData)
    {
        $results = ['is_valid' => true, 'warnings' => [], 'errors' => [], 'flags' => []];
        
        try {
            $judgeProfile = JudgeProfile::getProfileByJudgeId($scoreData['judge_id']);
            
            if (!$judgeProfile) {
                $results['warnings'][] = 'Judge profile not found - using default qualifications';
                return $results;
            }
            
            // Check category specialization
            if (!empty($judgeProfile->specialty_categories)) {
                $hasSpecialty = $judgeProfile->hasSpecialtyInCategory($scoreData['category_id']);
                if (!$hasSpecialty) {
                    $results['flags'][] = 'judge_no_specialty';
                    $results['warnings'][] = 'Judge does not specialize in this category';
                }
            }
            
            // Check calibration status
            if ($judgeProfile->needsCalibration($scoreData['category_id'])) {
                $results['flags'][] = 'judge_needs_calibration';
                $results['warnings'][] = 'Judge calibration expired or missing for this category';
            }
            
            // Check experience level for complex categories
            $complexCategories = ['ARDUINO', 'INVENTOR'];
            $categoryName = $this->getCategoryName($scoreData['category_id']);
            
            if (in_array($categoryName, $complexCategories) && in_array($judgeProfile->experience_level, ['novice', 'intermediate'])) {
                $results['flags'][] = 'judge_experience_concern';
                $results['warnings'][] = 'Judge experience level may be insufficient for complex category';
            }
            
        } catch (\Exception $e) {
            $results['warnings'][] = 'Could not validate judge qualifications: ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Calculate total score from score details
     */
    private function calculateTotalScore($scoreDetails)
    {
        if (is_string($scoreDetails)) {
            $scoreDetails = json_decode($scoreDetails, true);
        }
        
        if (!is_array($scoreDetails)) return 0;
        
        $total = 0;
        foreach ($scoreDetails as $criterionScore) {
            if (isset($criterionScore['points'])) {
                $total += $criterionScore['points'];
            }
        }
        
        return $total;
    }
    
    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation($values, $mean)
    {
        $squaredDifferences = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        $variance = array_sum($squaredDifferences) / count($values);
        return sqrt($variance);
    }
    
    /**
     * Merge validation results
     */
    private function mergeValidationResults($existing, $new)
    {
        $existing['is_valid'] = $existing['is_valid'] && $new['is_valid'];
        $existing['warnings'] = array_merge($existing['warnings'], $new['warnings']);
        $existing['errors'] = array_merge($existing['errors'], $new['errors']);
        $existing['flags'] = array_merge($existing['flags'], $new['flags']);
        
        return $existing;
    }
    
    /**
     * Calculate confidence score based on validation results
     */
    private function calculateConfidenceScore($validationResults)
    {
        $baseScore = 100;
        
        // Deduct for errors (major issues)
        $baseScore -= count($validationResults['errors']) * 20;
        
        // Deduct for warnings (moderate issues)
        $baseScore -= count($validationResults['warnings']) * 10;
        
        // Deduct for flags (minor concerns)
        $baseScore -= count($validationResults['flags']) * 5;
        
        return max(0, min(100, $baseScore));
    }
    
    /**
     * Determine if score requires review
     */
    private function determineReviewRequirement($validationResults)
    {
        // Always review if there are errors
        if (!empty($validationResults['errors'])) {
            return true;
        }
        
        // Review if confidence is low
        if ($validationResults['confidence_score'] < 70) {
            return true;
        }
        
        // Review for specific flags
        $reviewFlags = ['statistical_outlier', 'judge_consistency_issue', 'category_outlier'];
        foreach ($reviewFlags as $flag) {
            if (in_array($flag, $validationResults['flags'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get category name by ID
     */
    private function getCategoryName($categoryId)
    {
        $category = $this->db->query("SELECT category_name FROM categories WHERE id = ?", [$categoryId]);
        return !empty($category) ? $category[0]['category_name'] : '';
    }
    
    /**
     * Log validation result
     */
    private function logValidationResult($scoreData, $validationResults)
    {
        $logData = [
            'team_id' => $scoreData['team_id'] ?? null,
            'judge_id' => $scoreData['judge_id'] ?? null,
            'category_id' => $scoreData['category_id'] ?? null,
            'validation_result' => json_encode($validationResults),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->db->insert('score_validation_log', $logData);
        } catch (\Exception $e) {
            error_log("Failed to log validation result: " . $e->getMessage());
        }
    }
    
    /**
     * Batch validate multiple scores
     */
    public function batchValidateScores($scoresData)
    {
        $results = [];
        
        foreach ($scoresData as $index => $scoreData) {
            try {
                $results[$index] = $this->validateScore($scoreData);
            } catch (\Exception $e) {
                $results[$index] = [
                    'is_valid' => false,
                    'errors' => ['Batch validation error: ' . $e->getMessage()],
                    'warnings' => [],
                    'flags' => [],
                    'confidence_score' => 0
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get validation statistics for reporting
     */
    public function getValidationStatistics($categoryId = null, $dateRange = 30)
    {
        $whereClause = "WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [$dateRange];
        
        if ($categoryId) {
            $whereClause .= " AND category_id = ?";
            $params[] = $categoryId;
        }
        
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total_validations,
                SUM(JSON_LENGTH(JSON_EXTRACT(validation_result, '$.errors'))) as total_errors,
                SUM(JSON_LENGTH(JSON_EXTRACT(validation_result, '$.warnings'))) as total_warnings,
                SUM(JSON_LENGTH(JSON_EXTRACT(validation_result, '$.flags'))) as total_flags,
                AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(validation_result, '$.confidence_score')) AS DECIMAL(5,2))) as avg_confidence
            FROM score_validation_log
            {$whereClause}
        ", $params);
        
        return $stats[0] ?? [];
    }
}