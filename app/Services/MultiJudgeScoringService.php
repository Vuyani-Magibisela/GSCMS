<?php
// app/Services/MultiJudgeScoringService.php

namespace App\Services;

use App\Core\Database;
use App\Models\Score;
use App\Models\AggregatedScore;
use Exception;

class MultiJudgeScoringService
{
    const MIN_JUDGES_REQUIRED = 2;
    const MAX_SCORE_DEVIATION = 15; // percentage
    const OUTLIER_Z_SCORE_THRESHOLD = 2.5;
    
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Aggregate scores from multiple judges for a team
     */
    public function aggregateScores($teamId, $competitionId = null, $tournamentId = null)
    {
        // Get all submitted scores for this team
        $scores = $this->getSubmittedScores($teamId, $competitionId, $tournamentId);
        
        if (count($scores) < self::MIN_JUDGES_REQUIRED) {
            throw new Exception(
                "Need at least " . self::MIN_JUDGES_REQUIRED . " judges to aggregate scores. Currently have: " . count($scores)
            );
        }
        
        // Detect outliers
        $outlierAnalysis = $this->detectOutliers($scores);
        
        // Determine aggregation method
        $aggregationMethod = $this->determineAggregationMethod($scores, $outlierAnalysis);
        
        // Calculate aggregated score
        $aggregatedData = $this->calculateAggregatedScore($scores, $aggregationMethod, $outlierAnalysis);
        
        // Store aggregated result
        return $this->storeAggregatedScore($teamId, $competitionId, $tournamentId, $aggregatedData, $scores);
    }
    
    private function getSubmittedScores($teamId, $competitionId = null, $tournamentId = null)
    {
        $query = '
            SELECT s.*, u.name as judge_name, u.email as judge_email,
                   rt.template_name as rubric_name
            FROM scores s
            JOIN users u ON s.judge_id = u.id
            JOIN rubric_templates rt ON s.rubric_template_id = rt.id
            WHERE s.team_id = ? 
            AND s.scoring_status IN ("submitted", "validated", "final")
        ';
        
        $params = [$teamId];
        
        if ($competitionId) {
            $query .= ' AND s.competition_id = ?';
            $params[] = $competitionId;
        }
        
        if ($tournamentId) {
            $query .= ' AND s.tournament_id = ?';
            $params[] = $tournamentId;
        }
        
        $query .= ' ORDER BY s.submitted_at ASC';
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Detect statistical outliers using Interquartile Range (IQR) method
     */
    private function detectOutliers($scores)
    {
        $scoreValues = array_column($scores, 'total_score');
        $judgeIds = array_column($scores, 'judge_id');
        
        if (count($scoreValues) < 3) {
            return [
                'outliers' => [],
                'method' => 'insufficient_data',
                'statistics' => $this->calculateBasicStatistics($scoreValues)
            ];
        }
        
        sort($scoreValues);
        
        // Calculate quartiles
        $q1 = $this->percentile($scoreValues, 25);
        $q3 = $this->percentile($scoreValues, 75);
        $iqr = $q3 - $q1;
        
        // Calculate outlier bounds
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        
        // Identify outliers
        $outliers = [];
        foreach ($scores as $score) {
            if ($score['total_score'] < $lowerBound || $score['total_score'] > $upperBound) {
                $outliers[] = [
                    'judge_id' => $score['judge_id'],
                    'judge_name' => $score['judge_name'],
                    'score' => $score['total_score'],
                    'deviation_type' => $score['total_score'] < $lowerBound ? 'low' : 'high',
                    'severity' => $this->calculateOutlierSeverity($score['total_score'], $lowerBound, $upperBound)
                ];
            }
        }
        
        return [
            'outliers' => $outliers,
            'method' => 'iqr',
            'bounds' => ['lower' => $lowerBound, 'upper' => $upperBound],
            'quartiles' => ['q1' => $q1, 'q3' => $q3, 'iqr' => $iqr],
            'statistics' => $this->calculateBasicStatistics($scoreValues)
        ];
    }
    
    private function calculateOutlierSeverity($score, $lowerBound, $upperBound)
    {
        if ($score < $lowerBound) {
            $deviation = ($lowerBound - $score) / $lowerBound;
        } else {
            $deviation = ($score - $upperBound) / $upperBound;
        }
        
        if ($deviation > 0.3) return 'extreme';
        if ($deviation > 0.15) return 'moderate';
        return 'mild';
    }
    
    /**
     * Determine the best aggregation method based on score distribution
     */
    private function determineAggregationMethod($scores, $outlierAnalysis)
    {
        $hasOutliers = !empty($outlierAnalysis['outliers']);
        $scoreCount = count($scores);
        $stats = $outlierAnalysis['statistics'];
        
        // If we have extreme outliers, use trimmed mean
        $extremeOutliers = array_filter($outlierAnalysis['outliers'], function($outlier) {
            return $outlier['severity'] === 'extreme';
        });
        
        if (!empty($extremeOutliers)) {
            return 'trimmed_mean';
        }
        
        // If coefficient of variation is high, use median
        $coefficientOfVariation = $stats['std_dev'] / $stats['mean'];
        if ($coefficientOfVariation > 0.2) {
            return 'median';
        }
        
        // If we have consensus (low deviation), use average
        if ($this->hasConsensus($scores)) {
            return 'consensus';
        }
        
        // Default to average for small datasets
        if ($scoreCount <= 3) {
            return 'average';
        }
        
        // For larger datasets with some outliers, use trimmed mean
        if ($hasOutliers && $scoreCount >= 5) {
            return 'trimmed_mean';
        }
        
        return 'average';
    }
    
    /**
     * Calculate aggregated score using specified method
     */
    private function calculateAggregatedScore($scores, $method, $outlierAnalysis)
    {
        $scoreValues = array_column($scores, 'total_score');
        $gameScores = array_column($scores, 'game_challenge_score');
        $researchScores = array_column($scores, 'research_challenge_score');
        
        $result = [
            'method' => $method,
            'num_judges' => count($scores),
            'raw_scores' => array_combine(array_column($scores, 'judge_id'), $scoreValues),
            'outliers_detected' => $outlierAnalysis['outliers']
        ];
        
        switch ($method) {
            case 'average':
                $result['total_score'] = array_sum($scoreValues) / count($scoreValues);
                $result['game_challenge_score'] = array_sum($gameScores) / count($gameScores);
                $result['research_challenge_score'] = array_sum($researchScores) / count($researchScores);
                break;
                
            case 'median':
                $result['total_score'] = $this->median($scoreValues);
                $result['game_challenge_score'] = $this->median($gameScores);
                $result['research_challenge_score'] = $this->median($researchScores);
                break;
                
            case 'trimmed_mean':
                $result['total_score'] = $this->trimmedMean($scoreValues);
                $result['game_challenge_score'] = $this->trimmedMean($gameScores);
                $result['research_challenge_score'] = $this->trimmedMean($researchScores);
                break;
                
            case 'consensus':
                // Use average when there's consensus
                $result['total_score'] = array_sum($scoreValues) / count($scoreValues);
                $result['game_challenge_score'] = array_sum($gameScores) / count($gameScores);
                $result['research_challenge_score'] = array_sum($researchScores) / count($researchScores);
                $result['consensus_achieved'] = true;
                break;
                
            case 'highest':
                $result['total_score'] = max($scoreValues);
                $result['game_challenge_score'] = max($gameScores);
                $result['research_challenge_score'] = max($researchScores);
                break;
                
            default:
                $result['total_score'] = array_sum($scoreValues) / count($scoreValues);
                $result['game_challenge_score'] = array_sum($gameScores) / count($gameScores);
                $result['research_challenge_score'] = array_sum($researchScores) / count($researchScores);
        }
        
        // Calculate confidence metrics
        $result['score_variance'] = $this->variance($scoreValues);
        $result['confidence_level'] = $this->calculateConfidenceLevel($scoreValues, $outlierAnalysis);
        $result['normalized_score'] = ($result['total_score'] / 250) * 100;
        
        // Determine if review is required
        $result['requires_review'] = $this->requiresReview($result, $outlierAnalysis);
        $result['review_reason'] = $this->getReviewReason($result, $outlierAnalysis);
        
        return $result;
    }
    
    /**
     * Store aggregated score in database
     */
    private function storeAggregatedScore($teamId, $competitionId, $tournamentId, $aggregatedData, $scores)
    {
        $this->db->beginTransaction();
        
        try {
            // Get rubric template ID from first score
            $rubricTemplateId = $scores[0]['rubric_template_id'];
            
            // Check if aggregated score already exists
            $existingQuery = '
                SELECT id FROM aggregated_scores 
                WHERE team_id = ? AND COALESCE(competition_id, 0) = ? AND COALESCE(tournament_id, 0) = ?
            ';
            $existing = $this->db->query($existingQuery, [
                $teamId,
                $competitionId ?? 0,
                $tournamentId ?? 0
            ]);
            
            $aggregatedScoreData = [
                'team_id' => $teamId,
                'competition_id' => $competitionId,
                'tournament_id' => $tournamentId,
                'rubric_template_id' => $rubricTemplateId,
                'num_judges' => $aggregatedData['num_judges'],
                'aggregation_method' => $aggregatedData['method'],
                'raw_scores' => json_encode($aggregatedData['raw_scores']),
                'total_score' => round($aggregatedData['total_score'], 2),
                'normalized_score' => round($aggregatedData['normalized_score'], 2),
                'game_challenge_score' => round($aggregatedData['game_challenge_score'], 2),
                'research_challenge_score' => round($aggregatedData['research_challenge_score'], 2),
                'score_variance' => round($aggregatedData['score_variance'], 4),
                'confidence_level' => round($aggregatedData['confidence_level'], 2),
                'requires_review' => $aggregatedData['requires_review'],
                'review_reason' => $aggregatedData['review_reason'],
                'outliers_detected' => json_encode($aggregatedData['outliers_detected']),
                'finalized' => false
            ];
            
            if (!empty($existing)) {
                // Update existing record
                $aggregatedScoreId = $existing[0]['id'];
                $aggregatedScoreData['updated_at'] = date('Y-m-d H:i:s');
                
                $updateFields = [];
                $updateValues = [];
                foreach ($aggregatedScoreData as $field => $value) {
                    if ($field !== 'team_id') {
                        $updateFields[] = "{$field} = ?";
                        $updateValues[] = $value;
                    }
                }
                $updateValues[] = $aggregatedScoreId;
                
                $updateQuery = 'UPDATE aggregated_scores SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
                $this->db->query($updateQuery, $updateValues);
            } else {
                // Create new record
                $aggregatedScoreId = $this->db->insert('aggregated_scores', $aggregatedScoreData);
            }
            
            // Log the aggregation
            $this->logAggregation($aggregatedScoreId, $aggregatedData);
            
            $this->db->commit();
            
            // Return the aggregated score
            return $this->db->query('SELECT * FROM aggregated_scores WHERE id = ?', [$aggregatedScoreId])[0];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Check if judges have consensus (low deviation)
     */
    private function hasConsensus($scores)
    {
        $scoreValues = array_column($scores, 'total_score');
        $mean = array_sum($scoreValues) / count($scoreValues);
        
        foreach ($scoreValues as $score) {
            $deviation = abs($score - $mean) / $mean * 100;
            if ($deviation > self::MAX_SCORE_DEVIATION) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Resolve scoring discrepancies between judges
     */
    public function resolveDiscrepancy($teamId, $competitionId = null, $tournamentId = null, $resolution = [])
    {
        $scores = $this->getSubmittedScores($teamId, $competitionId, $tournamentId);
        
        if (count($scores) < 2) {
            throw new Exception('Need at least 2 scores to resolve discrepancies');
        }
        
        // Calculate detailed statistics
        $scoreValues = array_column($scores, 'total_score');
        $stats = $this->calculateDetailedStatistics($scoreValues, $scores);
        
        // Identify problematic scores
        $problematicScores = $this->identifyProblematicScores($scores, $stats);
        
        // Create resolution record
        $resolutionData = [
            'team_id' => $teamId,
            'competition_id' => $competitionId,
            'tournament_id' => $tournamentId,
            'statistics' => $stats,
            'problematic_scores' => $problematicScores,
            'resolution_method' => $resolution['method'] ?? 'head_judge_review',
            'resolved_by' => $resolution['resolved_by'] ?? null,
            'resolution_notes' => $resolution['notes'] ?? null,
            'final_score_override' => $resolution['final_score'] ?? null
        ];
        
        // Request head judge review if needed
        if (!empty($problematicScores) && empty($resolution)) {
            $this->requestHeadJudgeReview($teamId, $competitionId, $tournamentId, $problematicScores);
        }
        
        return $resolutionData;
    }
    
    /**
     * Get judge performance statistics for calibration
     */
    public function getJudgePerformanceStats($judgeId, $categoryId = null)
    {
        $query = '
            SELECT s.total_score, s.normalized_score, s.scoring_duration_minutes,
                   t.category_id, c.name as category_name,
                   COUNT(*) OVER (PARTITION BY s.judge_id) as total_scores,
                   AVG(s.total_score) OVER (PARTITION BY t.category_id) as category_avg
            FROM scores s
            JOIN teams t ON s.team_id = t.id
            JOIN categories c ON t.category_id = c.id
            WHERE s.judge_id = ? AND s.scoring_status IN ("submitted", "validated", "final")
        ';
        
        $params = [$judgeId];
        
        if ($categoryId) {
            $query .= ' AND t.category_id = ?';
            $params[] = $categoryId;
        }
        
        $query .= ' ORDER BY s.submitted_at DESC';
        
        $judgeScores = $this->db->query($query, $params);
        
        if (empty($judgeScores)) {
            return null;
        }
        
        // Calculate statistics
        $scoreValues = array_column($judgeScores, 'total_score');
        $durations = array_filter(array_column($judgeScores, 'scoring_duration_minutes'));
        
        return [
            'judge_id' => $judgeId,
            'total_scores' => count($judgeScores),
            'score_statistics' => $this->calculateBasicStatistics($scoreValues),
            'timing_statistics' => empty($durations) ? null : $this->calculateBasicStatistics($durations),
            'consistency_metrics' => $this->calculateJudgeConsistency($judgeScores),
            'category_performance' => $this->calculateCategoryPerformance($judgeScores)
        ];
    }
    
    // Helper methods
    
    private function percentile($values, $percentile)
    {
        $count = count($values);
        $index = ($percentile / 100) * ($count - 1);
        
        if ($index === intval($index)) {
            return $values[$index];
        } else {
            $lower = $values[floor($index)];
            $upper = $values[ceil($index)];
            $weight = $index - floor($index);
            return $lower + ($weight * ($upper - $lower));
        }
    }
    
    private function median($values)
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }
    
    private function trimmedMean($values, $trimPercent = 20)
    {
        $count = count($values);
        $trimCount = max(1, floor($count * ($trimPercent / 100)));
        
        sort($values);
        
        // Remove highest and lowest values
        for ($i = 0; $i < $trimCount; $i++) {
            array_shift($values);
            array_pop($values);
        }
        
        return empty($values) ? 0 : array_sum($values) / count($values);
    }
    
    private function variance($values)
    {
        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return array_sum($squaredDifferences) / count($squaredDifferences);
    }
    
    private function standardDeviation($values)
    {
        return sqrt($this->variance($values));
    }
    
    private function calculateBasicStatistics($values)
    {
        if (empty($values)) {
            return null;
        }
        
        $count = count($values);
        $mean = array_sum($values) / $count;
        $min = min($values);
        $max = max($values);
        $range = $max - $min;
        $variance = $this->variance($values);
        $stdDev = sqrt($variance);
        
        return [
            'count' => $count,
            'mean' => round($mean, 2),
            'median' => round($this->median($values), 2),
            'min' => $min,
            'max' => $max,
            'range' => $range,
            'variance' => round($variance, 4),
            'std_dev' => round($stdDev, 2),
            'coefficient_of_variation' => $mean > 0 ? round(($stdDev / $mean) * 100, 2) : 0
        ];
    }
    
    private function calculateDetailedStatistics($scoreValues, $scores)
    {
        $basicStats = $this->calculateBasicStatistics($scoreValues);
        
        // Calculate judge-specific stats
        $judgeStats = [];
        foreach ($scores as $score) {
            $judgeId = $score['judge_id'];
            if (!isset($judgeStats[$judgeId])) {
                $judgeStats[$judgeId] = [
                    'judge_name' => $score['judge_name'],
                    'score' => $score['total_score'],
                    'deviation_from_mean' => $score['total_score'] - $basicStats['mean'],
                    'z_score' => $basicStats['std_dev'] > 0 ? 
                        ($score['total_score'] - $basicStats['mean']) / $basicStats['std_dev'] : 0
                ];
            }
        }
        
        return array_merge($basicStats, [
            'judge_statistics' => $judgeStats
        ]);
    }
    
    private function identifyProblematicScores($scores, $stats)
    {
        $problematic = [];
        
        foreach ($stats['judge_statistics'] as $judgeId => $judgeStats) {
            $issues = [];
            
            // Check for statistical outliers (Z-score > 2.5)
            if (abs($judgeStats['z_score']) > self::OUTLIER_Z_SCORE_THRESHOLD) {
                $issues[] = 'statistical_outlier';
            }
            
            // Check for extreme deviation
            $deviationPercent = abs($judgeStats['deviation_from_mean']) / $stats['mean'] * 100;
            if ($deviationPercent > self::MAX_SCORE_DEVIATION) {
                $issues[] = 'extreme_deviation';
            }
            
            if (!empty($issues)) {
                $problematic[] = array_merge($judgeStats, [
                    'judge_id' => $judgeId,
                    'issues' => $issues,
                    'deviation_percentage' => round($deviationPercent, 2)
                ]);
            }
        }
        
        return $problematic;
    }
    
    private function calculateConfidenceLevel($scoreValues, $outlierAnalysis)
    {
        $stats = $outlierAnalysis['statistics'];
        $hasOutliers = !empty($outlierAnalysis['outliers']);
        
        // Base confidence on coefficient of variation
        $cv = $stats['std_dev'] / $stats['mean'];
        
        if ($cv < 0.05) return 95; // Very low variation
        if ($cv < 0.10) return 90; // Low variation
        if ($cv < 0.15) return 80; // Moderate variation
        if ($cv < 0.20) return 70; // High variation
        
        // Reduce confidence if outliers present
        if ($hasOutliers) {
            $extremeOutliers = array_filter($outlierAnalysis['outliers'], function($outlier) {
                return $outlier['severity'] === 'extreme';
            });
            
            if (!empty($extremeOutliers)) return 50;
            return 60;
        }
        
        return 65; // Very high variation
    }
    
    private function requiresReview($aggregatedData, $outlierAnalysis)
    {
        // Require review if confidence is low
        if ($aggregatedData['confidence_level'] < 70) {
            return true;
        }
        
        // Require review if there are extreme outliers
        $extremeOutliers = array_filter($outlierAnalysis['outliers'], function($outlier) {
            return $outlier['severity'] === 'extreme';
        });
        
        if (!empty($extremeOutliers)) {
            return true;
        }
        
        // Require review if score is very high or very low
        if ($aggregatedData['total_score'] < 20 || $aggregatedData['total_score'] > 230) {
            return true;
        }
        
        return false;
    }
    
    private function getReviewReason($aggregatedData, $outlierAnalysis)
    {
        $reasons = [];
        
        if ($aggregatedData['confidence_level'] < 70) {
            $reasons[] = "Low confidence level ({$aggregatedData['confidence_level']}%)";
        }
        
        $extremeOutliers = array_filter($outlierAnalysis['outliers'], function($outlier) {
            return $outlier['severity'] === 'extreme';
        });
        
        if (!empty($extremeOutliers)) {
            $judgeNames = array_column($extremeOutliers, 'judge_name');
            $reasons[] = "Extreme outlier scores from: " . implode(', ', $judgeNames);
        }
        
        if ($aggregatedData['total_score'] < 20) {
            $reasons[] = "Unusually low total score ({$aggregatedData['total_score']})";
        }
        
        if ($aggregatedData['total_score'] > 230) {
            $reasons[] = "Unusually high total score ({$aggregatedData['total_score']})";
        }
        
        return empty($reasons) ? null : implode('; ', $reasons);
    }
    
    private function requestHeadJudgeReview($teamId, $competitionId, $tournamentId, $problematicScores)
    {
        // Create review request record
        $reviewData = [
            'team_id' => $teamId,
            'competition_id' => $competitionId,
            'tournament_id' => $tournamentId,
            'request_type' => 'score_discrepancy',
            'problematic_scores' => json_encode($problematicScores),
            'status' => 'pending',
            'requested_at' => date('Y-m-d H:i:s'),
            'urgency' => 'normal'
        ];
        
        // This would typically insert into a review_requests table
        // and send notifications to head judges
        
        return $reviewData;
    }
    
    private function calculateJudgeConsistency($judgeScores)
    {
        if (count($judgeScores) < 2) {
            return null;
        }
        
        $scoreValues = array_column($judgeScores, 'total_score');
        $categoryAvgs = array_column($judgeScores, 'category_avg');
        
        // Calculate consistency within categories
        $consistencyMetrics = [
            'internal_consistency' => $this->calculateBasicStatistics($scoreValues)['coefficient_of_variation'],
            'category_alignment' => []
        ];
        
        // Group by category
        $categoryGroups = [];
        foreach ($judgeScores as $score) {
            $categoryId = $score['category_id'];
            if (!isset($categoryGroups[$categoryId])) {
                $categoryGroups[$categoryId] = [];
            }
            $categoryGroups[$categoryId][] = $score['total_score'];
        }
        
        foreach ($categoryGroups as $categoryId => $categoryScores) {
            if (count($categoryScores) > 1) {
                $consistencyMetrics['category_alignment'][$categoryId] = 
                    $this->calculateBasicStatistics($categoryScores)['coefficient_of_variation'];
            }
        }
        
        return $consistencyMetrics;
    }
    
    private function calculateCategoryPerformance($judgeScores)
    {
        $performance = [];
        
        foreach ($judgeScores as $score) {
            $categoryName = $score['category_name'];
            if (!isset($performance[$categoryName])) {
                $performance[$categoryName] = [
                    'scores' => [],
                    'count' => 0
                ];
            }
            
            $performance[$categoryName]['scores'][] = $score['total_score'];
            $performance[$categoryName]['count']++;
        }
        
        // Calculate statistics for each category
        foreach ($performance as $categoryName => &$data) {
            $data['statistics'] = $this->calculateBasicStatistics($data['scores']);
            unset($data['scores']); // Remove raw scores to save space
        }
        
        return $performance;
    }
    
    private function logAggregation($aggregatedScoreId, $aggregatedData)
    {
        // Log the aggregation process for audit purposes
        $logData = [
            'aggregated_score_id' => $aggregatedScoreId,
            'action' => 'score_aggregated',
            'method_used' => $aggregatedData['method'],
            'num_judges' => $aggregatedData['num_judges'],
            'outliers_detected' => !empty($aggregatedData['outliers_detected']),
            'requires_review' => $aggregatedData['requires_review'],
            'aggregation_data' => json_encode($aggregatedData)
        ];
        
        // This would typically insert into an aggregation_audit_log table
        
        return $logData;
    }
}