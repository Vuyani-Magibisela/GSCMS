<?php
// app/Services/ConflictResolutionService.php

namespace App\Services;

use App\Core\Database;
use App\Models\LiveScoreUpdate;
use App\Models\JudgeProfile;

class ConflictResolutionService
{
    private $db;
    
    // Deviation thresholds by category (percentage)
    const DEVIATION_THRESHOLDS = [
        'JUNIOR' => 20,         // 20% allowed deviation for visual scoring
        'SPIKE_INTERMEDIATE' => 15,  // 15% allowed deviation
        'ARDUINO' => 10,        // 10% allowed deviation for technical scoring
        'INVENTOR' => 15        // 15% allowed deviation
    ];
    
    // Severity levels for conflicts
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function detectConflicts($sessionId, $teamId, $criteriaId = null)
    {
        $conflicts = [];
        
        // Get session info for threshold
        $session = $this->getSessionInfo($sessionId);
        if (!$session) {
            throw new \Exception("Session not found: {$sessionId}");
        }
        
        // Get all scores for this team in this session
        $query = "
            SELECT lsu.*, jp.judge_code, u.first_name, u.last_name, rc.criteria_name, rc.max_points,
                   cat.name as category_name
            FROM live_score_updates lsu
            JOIN judge_profiles jp ON lsu.judge_id = jp.id
            JOIN users u ON jp.user_id = u.id
            JOIN rubric_criteria rc ON lsu.criteria_id = rc.id
            JOIN rubric_sections rs ON rc.section_id = rs.id
            JOIN rubric_templates rt ON rs.rubric_template_id = rt.id
            JOIN categories cat ON rt.category_id = cat.id
            WHERE lsu.session_id = ? AND lsu.team_id = ?
        ";
        
        $params = [$sessionId, $teamId];
        
        if ($criteriaId) {
            $query .= " AND lsu.criteria_id = ?";
            $params[] = $criteriaId;
        }
        
        $query .= " AND lsu.sync_status NOT IN ('resolved', 'ignored') ORDER BY lsu.criteria_id, lsu.server_timestamp DESC";
        
        $scores = $this->db->query($query, $params);
        
        // Group by criteria
        $scoresByCriteria = [];
        foreach ($scores as $score) {
            $scoresByCriteria[$score['criteria_id']][] = $score;
        }
        
        // Analyze each criteria for conflicts
        foreach ($scoresByCriteria as $criteriaId => $judgeScores) {
            if (count($judgeScores) < 2) continue;
            
            $analysis = $this->analyzeScores($judgeScores);
            
            if ($analysis['has_conflict']) {
                $conflicts[] = [
                    'criteria_id' => $criteriaId,
                    'criteria_name' => $judgeScores[0]['criteria_name'],
                    'category' => $judgeScores[0]['category_name'],
                    'analysis' => $analysis,
                    'scores' => $judgeScores,
                    'severity' => $this->calculateSeverity($analysis),
                    'suggested_resolution' => $this->suggestResolution($analysis, $judgeScores),
                    'detected_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        // Store conflicts in database for tracking
        foreach ($conflicts as $conflict) {
            $this->storeConflict($sessionId, $teamId, $conflict);
        }
        
        return $conflicts;
    }
    
    private function analyzeScores($scores)
    {
        $values = array_map(function($score) {
            return floatval($score['score_value']);
        }, $scores);
        
        $judgeIds = array_map(function($score) {
            return $score['judge_id'];
        }, $scores);
        
        // Calculate statistical measures
        $mean = array_sum($values) / count($values);
        $median = $this->median($values);
        $stdDev = $this->standardDeviation($values);
        $range = max($values) - min($values);
        $outliers = $this->detectOutliers($values, $judgeIds);
        
        // Determine conflict threshold
        $category = $scores[0]['category_name'];
        $threshold = self::DEVIATION_THRESHOLDS[$category] ?? 15;
        
        // Check for conflicts
        $hasConflict = false;
        $maxDeviation = 0;
        
        foreach ($values as $value) {
            if ($mean > 0) {
                $deviation = abs($value - $mean) / $mean * 100;
                $maxDeviation = max($maxDeviation, $deviation);
                
                if ($deviation > $threshold) {
                    $hasConflict = true;
                }
            } elseif ($range > 0) {
                // For zero-mean scores, use range-based detection
                $hasConflict = $range > ($threshold / 100) * max($values);
            }
        }
        
        return [
            'has_conflict' => $hasConflict,
            'mean' => round($mean, 2),
            'median' => round($median, 2),
            'std_dev' => round($stdDev, 2),
            'range' => round($range, 2),
            'max_deviation' => round($maxDeviation, 2),
            'threshold' => $threshold,
            'outliers' => $outliers,
            'coefficient_of_variation' => $mean > 0 ? round(($stdDev / $mean) * 100, 2) : 0,
            'score_spread' => [
                'min' => min($values),
                'max' => max($values),
                'q1' => $this->percentile($values, 25),
                'q3' => $this->percentile($values, 75)
            ]
        ];
    }
    
    private function calculateSeverity($analysis)
    {
        $maxDeviation = $analysis['max_deviation'];
        $threshold = $analysis['threshold'];
        
        if ($maxDeviation < $threshold * 1.2) {
            return self::SEVERITY_LOW;
        } elseif ($maxDeviation < $threshold * 1.5) {
            return self::SEVERITY_MEDIUM;
        } elseif ($maxDeviation < $threshold * 2.0) {
            return self::SEVERITY_HIGH;
        } else {
            return self::SEVERITY_CRITICAL;
        }
    }
    
    private function suggestResolution($analysis, $scores)
    {
        $suggestions = [];
        
        // Median-based resolution
        if (count($scores) >= 3) {
            $suggestions[] = [
                'method' => 'use_median',
                'value' => $analysis['median'],
                'description' => 'Use the median score to minimize outlier impact',
                'confidence' => 0.8
            ];
        }
        
        // Remove outliers and recalculate
        if (!empty($analysis['outliers'])) {
            $nonOutlierScores = array_filter($scores, function($score) use ($analysis) {
                return !in_array($score['judge_id'], array_column($analysis['outliers'], 'judge_id'));
            });
            
            if (count($nonOutlierScores) >= 2) {
                $nonOutlierValues = array_map(function($score) {
                    return floatval($score['score_value']);
                }, $nonOutlierScores);
                
                $suggestions[] = [
                    'method' => 'exclude_outliers',
                    'value' => array_sum($nonOutlierValues) / count($nonOutlierValues),
                    'description' => 'Exclude outlier scores and average remaining',
                    'confidence' => 0.7,
                    'excluded_judges' => array_column($analysis['outliers'], 'judge_id')
                ];
            }
        }
        
        // Head judge resolution
        $suggestions[] = [
            'method' => 'head_judge_decision',
            'description' => 'Escalate to head judge for final decision',
            'confidence' => 0.9
        ];
        
        // Judge discussion
        if ($analysis['max_deviation'] < $analysis['threshold'] * 1.5) {
            $suggestions[] = [
                'method' => 'judge_discussion',
                'description' => 'Initiate discussion between conflicting judges',
                'confidence' => 0.6
            ];
        }
        
        // Weighted average based on judge experience
        $suggestions[] = [
            'method' => 'weighted_average',
            'description' => 'Weight scores by judge experience and reliability',
            'confidence' => 0.7
        ];
        
        return $suggestions;
    }
    
    public function resolveConflict($conflictId, $resolution)
    {
        $this->db->beginTransaction();
        
        try {
            $conflict = $this->getConflict($conflictId);
            if (!$conflict) {
                throw new \Exception("Conflict not found: {$conflictId}");
            }
            
            $finalScore = null;
            $resolutionNotes = '';
            
            switch ($resolution['method']) {
                case 'use_median':
                    $finalScore = $this->useMedianResolution($conflict);
                    $resolutionNotes = 'Resolved using median score';
                    break;
                    
                case 'exclude_outliers':
                    $finalScore = $this->excludeOutliers($conflict);
                    $resolutionNotes = 'Resolved by excluding outlier scores';
                    break;
                    
                case 'head_judge_decision':
                    $finalScore = $resolution['head_judge_score'];
                    $resolutionNotes = 'Resolved by head judge decision';
                    break;
                    
                case 'judge_discussion':
                    $finalScore = $resolution['consensus_score'];
                    $resolutionNotes = 'Resolved through judge discussion';
                    break;
                    
                case 'weighted_average':
                    $finalScore = $this->calculateWeightedAverage($conflict);
                    $resolutionNotes = 'Resolved using weighted average';
                    break;
                    
                case 'manual_override':
                    $finalScore = $resolution['override_value'];
                    $resolutionNotes = 'Manual override by administrator';
                    break;
                    
                default:
                    throw new \Exception("Invalid resolution method: {$resolution['method']}");
            }
            
            // Update all conflicting score updates to resolved
            $this->markScoresAsResolved($conflict, $finalScore, $resolutionNotes);
            
            // Update conflict record
            $this->updateConflictRecord($conflictId, $resolution, $finalScore);
            
            // Notify relevant judges
            $this->notifyJudgesOfResolution($conflict, $resolution, $finalScore);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'final_score' => $finalScore,
                'method' => $resolution['method'],
                'notes' => $resolutionNotes
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function escalateToHeadJudge($conflictId, $priority = 'normal')
    {
        $conflict = $this->getConflict($conflictId);
        if (!$conflict) {
            throw new \Exception("Conflict not found: {$conflictId}");
        }
        
        // Find head judge for this session
        $headJudge = $this->db->query("
            SELECT jp.id, jp.user_id, u.first_name, u.last_name, u.email
            FROM live_scoring_sessions lss
            JOIN judge_profiles jp ON lss.head_judge_id = jp.id
            JOIN users u ON jp.user_id = u.id
            WHERE lss.id = ?
        ", [$conflict['session_id']]);
        
        if (empty($headJudge)) {
            throw new \Exception("No head judge assigned to this session");
        }
        
        $headJudgeData = $headJudge[0];
        
        // Create escalation record
        $escalationData = [
            'conflict_id' => $conflictId,
            'head_judge_id' => $headJudgeData['id'],
            'priority' => $priority,
            'status' => 'pending',
            'escalated_at' => date('Y-m-d H:i:s'),
            'deadline' => date('Y-m-d H:i:s', strtotime('+10 minutes')), // 10 minute deadline
            'escalated_by' => $_SESSION['user_id'] ?? null
        ];
        
        $escalationId = $this->db->insert('conflict_escalations', $escalationData);
        
        // Send notification to head judge
        $this->notifyHeadJudge($headJudgeData, $conflict, $escalationId);
        
        // Schedule auto-resolution if no response
        $this->scheduleAutoResolution($escalationId);
        
        return [
            'escalation_id' => $escalationId,
            'head_judge' => $headJudgeData,
            'deadline' => $escalationData['deadline']
        ];
    }
    
    public function initiateJudgeDiscussion($conflictId, $participantJudgeIds)
    {
        $conflict = $this->getConflict($conflictId);
        if (!$conflict) {
            throw new \Exception("Conflict not found: {$conflictId}");
        }
        
        // Create discussion session
        $discussionData = [
            'conflict_id' => $conflictId,
            'session_id' => $conflict['session_id'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'deadline' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
        ];
        
        $discussionId = $this->db->insert('judge_discussions', $discussionData);
        
        // Add participants
        foreach ($participantJudgeIds as $judgeId) {
            $this->db->insert('judge_discussion_participants', [
                'discussion_id' => $discussionId,
                'judge_id' => $judgeId,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Notify judges
        $this->notifyJudgesForDiscussion($discussionId, $participantJudgeIds, $conflict);
        
        return [
            'discussion_id' => $discussionId,
            'participants' => count($participantJudgeIds),
            'deadline' => $discussionData['deadline']
        ];
    }
    
    private function useMedianResolution($conflict)
    {
        $scores = $this->getConflictScores($conflict['id']);
        $values = array_map(function($score) {
            return floatval($score['score_value']);
        }, $scores);
        
        return $this->median($values);
    }
    
    private function excludeOutliers($conflict)
    {
        $scores = $this->getConflictScores($conflict['id']);
        $values = array_map(function($score) {
            return floatval($score['score_value']);
        }, $scores);
        
        $judgeIds = array_map(function($score) {
            return $score['judge_id'];
        }, $scores);
        
        $outliers = $this->detectOutliers($values, $judgeIds);
        $outlierJudgeIds = array_column($outliers, 'judge_id');
        
        $validScores = array_filter($scores, function($score) use ($outlierJudgeIds) {
            return !in_array($score['judge_id'], $outlierJudgeIds);
        });
        
        if (empty($validScores)) {
            return $this->median($values); // Fallback to median if all are outliers
        }
        
        $validValues = array_map(function($score) {
            return floatval($score['score_value']);
        }, $validScores);
        
        return array_sum($validValues) / count($validValues);
    }
    
    private function calculateWeightedAverage($conflict)
    {
        $scores = $this->getConflictScores($conflict['id']);
        
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($scores as $score) {
            $weight = $this->getJudgeWeight($score['judge_id']);
            $weightedSum += floatval($score['score_value']) * $weight;
            $totalWeight += $weight;
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }
    
    private function getJudgeWeight($judgeId)
    {
        // Calculate judge weight based on experience and reliability
        $judgeMetrics = $this->db->query("
            SELECT jp.experience_level, jp.years_experience,
                   AVG(jpm.consistency_score) as avg_consistency,
                   COUNT(s.id) as total_scores
            FROM judge_profiles jp
            LEFT JOIN judge_performance_metrics jpm ON jp.id = jpm.judge_id
            LEFT JOIN scores s ON jp.id = s.judge_id
            WHERE jp.id = ?
            GROUP BY jp.id
        ", [$judgeId]);
        
        if (empty($judgeMetrics)) {
            return 1.0; // Default weight
        }
        
        $metrics = $judgeMetrics[0];
        
        // Base weight from experience level
        $experienceWeights = [
            'novice' => 0.7,
            'intermediate' => 1.0,
            'advanced' => 1.3,
            'expert' => 1.5
        ];
        
        $weight = $experienceWeights[$metrics['experience_level']] ?? 1.0;
        
        // Adjust for consistency
        if ($metrics['avg_consistency']) {
            $weight *= (floatval($metrics['avg_consistency']) / 100);
        }
        
        // Adjust for experience (years)
        $yearsBonus = min(floatval($metrics['years_experience']) * 0.1, 0.5);
        $weight += $yearsBonus;
        
        return max($weight, 0.3); // Minimum weight of 0.3
    }
    
    // Utility statistical functions
    private function median($values)
    {
        sort($values);
        $count = count($values);
        
        if ($count === 0) return 0;
        if ($count === 1) return $values[0];
        
        $middle = intval($count / 2);
        
        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }
    
    private function standardDeviation($values)
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return sqrt(array_sum($squaredDiffs) / count($values));
    }
    
    private function percentile($values, $percentile)
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[intval($index)];
        } else {
            $lower = $values[intval(floor($index))];
            $upper = $values[intval(ceil($index))];
            $fraction = $index - floor($index);
            
            return $lower + ($fraction * ($upper - $lower));
        }
    }
    
    private function detectOutliers($values, $judgeIds)
    {
        if (count($values) < 3) return [];
        
        $q1 = $this->percentile($values, 25);
        $q3 = $this->percentile($values, 75);
        $iqr = $q3 - $q1;
        
        $lowerBound = $q1 - 1.5 * $iqr;
        $upperBound = $q3 + 1.5 * $iqr;
        
        $outliers = [];
        
        for ($i = 0; $i < count($values); $i++) {
            if ($values[$i] < $lowerBound || $values[$i] > $upperBound) {
                $outliers[] = [
                    'judge_id' => $judgeIds[$i],
                    'score' => $values[$i],
                    'type' => $values[$i] < $lowerBound ? 'low' : 'high'
                ];
            }
        }
        
        return $outliers;
    }
    
    // Helper methods for database operations
    private function storeConflict($sessionId, $teamId, $conflict)
    {
        // Implementation would store conflict in database
        error_log("Storing conflict for session {$sessionId}, team {$teamId}, criteria {$conflict['criteria_id']}");
    }
    
    private function getSessionInfo($sessionId)
    {
        $session = $this->db->query("SELECT * FROM live_scoring_sessions WHERE id = ?", [$sessionId]);
        return !empty($session) ? $session[0] : null;
    }
    
    private function getConflict($conflictId)
    {
        // Implementation would retrieve conflict from database
        return [
            'id' => $conflictId,
            'session_id' => 1,
            'team_id' => 1,
            'criteria_id' => 1
        ];
    }
    
    private function getConflictScores($conflictId)
    {
        // Implementation would retrieve conflict scores
        return [];
    }
    
    private function markScoresAsResolved($conflict, $finalScore, $notes)
    {
        // Implementation would update scores
        error_log("Marking conflict scores as resolved with final score: {$finalScore}");
    }
    
    private function updateConflictRecord($conflictId, $resolution, $finalScore)
    {
        // Implementation would update conflict record
        error_log("Updating conflict record {$conflictId}");
    }
    
    private function notifyJudgesOfResolution($conflict, $resolution, $finalScore)
    {
        // Implementation would send notifications
        error_log("Notifying judges of conflict resolution");
    }
    
    private function notifyHeadJudge($headJudge, $conflict, $escalationId)
    {
        // Implementation would send notification to head judge
        error_log("Notifying head judge {$headJudge['id']} of escalation {$escalationId}");
    }
    
    private function notifyJudgesForDiscussion($discussionId, $judgeIds, $conflict)
    {
        // Implementation would notify judges
        error_log("Starting judge discussion {$discussionId} for conflict");
    }
    
    private function scheduleAutoResolution($escalationId)
    {
        // Implementation would schedule auto-resolution
        error_log("Scheduling auto-resolution for escalation {$escalationId}");
    }
}