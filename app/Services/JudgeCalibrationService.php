<?php
// app/Services/JudgeCalibrationService.php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\RubricTemplate;
use App\Models\Score;

class JudgeCalibrationService
{
    private $db;
    
    const CALIBRATION_THRESHOLDS = [
        'excellent' => 90,
        'good' => 75,
        'acceptable' => 60,
        'needs_improvement' => 45
    ];
    
    const AGREEMENT_THRESHOLDS = [
        'high_agreement' => 85,
        'moderate_agreement' => 70,
        'low_agreement' => 55
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create calibration exercises for judges
     */
    public function createCalibrationExercise($categoryId, $difficultyLevel = 'intermediate')
    {
        $this->db->beginTransaction();
        
        try {
            // Generate reference scores for calibration
            $referenceScenarios = $this->generateReferenceScenarios($categoryId, $difficultyLevel);
            
            $exerciseId = $this->db->insert('judge_calibration_exercises', [
                'category_id' => $categoryId,
                'exercise_name' => "Calibration Exercise - Category {$categoryId}",
                'difficulty_level' => $difficultyLevel,
                'reference_scenarios' => json_encode($referenceScenarios),
                'expert_scores' => json_encode($this->generateExpertScores($referenceScenarios, $categoryId)),
                'created_at' => date('Y-m-d H:i:s'),
                'is_active' => true
            ]);
            
            $this->db->commit();
            
            return $exerciseId;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Generate reference scoring scenarios for calibration
     */
    private function generateReferenceScenarios($categoryId, $difficultyLevel)
    {
        $scenarios = [];
        
        // Get category details
        $category = $this->db->query("SELECT * FROM categories WHERE id = ?", [$categoryId])[0];
        
        switch ($category['category_name']) {
            case 'JUNIOR':
                $scenarios = [
                    [
                        'scenario_id' => 1,
                        'title' => 'Robot Design Presentation',
                        'description' => 'Team presents a well-decorated robot with clear explanation of design choices. Robot moves forward and backward reliably.',
                        'video_url' => null,
                        'key_points' => [
                            'Creative robot decoration',
                            'Clear verbal explanation',
                            'Demonstrates basic movement',
                            'Shows enthusiasm'
                        ],
                        'difficulty' => 'basic'
                    ],
                    [
                        'scenario_id' => 2,
                        'title' => 'Mission Challenge Performance',
                        'description' => 'Robot completes 2 out of 3 missions successfully. Team shows good problem-solving when robot gets stuck.',
                        'video_url' => null,
                        'key_points' => [
                            'Completed most missions',
                            'Good team communication',
                            'Problem-solving skills',
                            'Recovery from failures'
                        ],
                        'difficulty' => 'intermediate'
                    ]
                ];
                break;
                
            case 'SPIKE/EXPLORER':
                $scenarios = [
                    [
                        'scenario_id' => 1,
                        'title' => 'Autonomous Navigation',
                        'description' => 'Robot navigates course autonomously using sensors. Makes some navigation errors but completes most objectives.',
                        'video_url' => null,
                        'key_points' => [
                            'Effective sensor usage',
                            'Autonomous decision making',
                            'Course completion rate',
                            'Programming complexity'
                        ],
                        'difficulty' => 'intermediate'
                    ]
                ];
                break;
                
            case 'ARDUINO':
                $scenarios = [
                    [
                        'scenario_id' => 1,
                        'title' => 'IoT Environmental Monitoring',
                        'description' => 'System collects and displays environmental data with clean code structure and proper documentation.',
                        'video_url' => null,
                        'key_points' => [
                            'Code quality and structure',
                            'Sensor integration',
                            'Data visualization',
                            'Technical documentation'
                        ],
                        'difficulty' => 'advanced'
                    ]
                ];
                break;
                
            case 'INVENTOR':
                $scenarios = [
                    [
                        'scenario_id' => 1,
                        'title' => 'Innovation Project Presentation',
                        'description' => 'Comprehensive solution addressing real-world problem with prototype, business model, and impact analysis.',
                        'video_url' => null,
                        'key_points' => [
                            'Problem identification',
                            'Solution innovation',
                            'Prototype effectiveness',
                            'Business viability',
                            'Social impact'
                        ],
                        'difficulty' => 'expert'
                    ]
                ];
                break;
        }
        
        return $scenarios;
    }
    
    /**
     * Generate expert reference scores for scenarios
     */
    private function generateExpertScores($scenarios, $categoryId)
    {
        $expertScores = [];
        
        foreach ($scenarios as $scenario) {
            $rubric = RubricTemplate::getByCategory($categoryId);
            $scenarioScore = [];
            
            // Generate scores based on scenario difficulty and key points
            foreach ($rubric['sections'] as $section) {
                foreach ($section['criteria'] as $criterion) {
                    $level = $this->determineExpertLevel($scenario, $criterion);
                    $points = ($criterion['max_points'] * $level) / 4; // 4 levels
                    
                    $scenarioScore[$criterion['id']] = [
                        'points' => round($points, 1),
                        'level' => $level,
                        'rationale' => $this->generateScoreRationale($scenario, $criterion, $level)
                    ];
                }
            }
            
            $expertScores[$scenario['scenario_id']] = $scenarioScore;
        }
        
        return $expertScores;
    }
    
    /**
     * Determine expert scoring level for a criterion
     */
    private function determineExpertLevel($scenario, $criterion)
    {
        // Basic algorithm - can be enhanced with ML/AI
        $baseLevel = 2; // Start with developing level
        
        switch ($scenario['difficulty']) {
            case 'basic':
                $baseLevel = 2; // Developing
                break;
            case 'intermediate':
                $baseLevel = 3; // Accomplished
                break;
            case 'advanced':
                $baseLevel = 3; // Accomplished
                break;
            case 'expert':
                $baseLevel = 4; // Exceeded
                break;
        }
        
        // Adjust based on criterion relevance to scenario key points
        foreach ($scenario['key_points'] as $keyPoint) {
            if (stripos($criterion['criteria_name'], explode(' ', $keyPoint)[0]) !== false) {
                $baseLevel = min(4, $baseLevel + 1);
                break;
            }
        }
        
        return $baseLevel;
    }
    
    /**
     * Generate score rationale for expert scoring
     */
    private function generateScoreRationale($scenario, $criterion, $level)
    {
        $levelNames = [1 => 'Basic', 2 => 'Developing', 3 => 'Accomplished', 4 => 'Exceeded'];
        
        return "Expert assessment: {$levelNames[$level]} level achieved. " . 
               "Evaluated against scenario: {$scenario['title']}. " .
               "Key evidence supports this level based on demonstrated competency.";
    }
    
    /**
     * Conduct judge calibration session
     */
    public function conductCalibrationSession($judgeId, $exerciseId)
    {
        // Get exercise details
        $exercise = $this->db->query("
            SELECT * FROM judge_calibration_exercises WHERE id = ?
        ", [$exerciseId]);
        
        if (empty($exercise)) {
            throw new \Exception("Calibration exercise not found: {$exerciseId}");
        }
        
        $exercise = $exercise[0];
        $scenarios = json_decode($exercise['reference_scenarios'], true);
        $expertScores = json_decode($exercise['expert_scores'], true);
        
        // Create calibration session record
        $sessionId = $this->db->insert('judge_calibration_sessions', [
            'judge_id' => $judgeId,
            'exercise_id' => $exerciseId,
            'started_at' => date('Y-m-d H:i:s'),
            'status' => 'in_progress'
        ]);
        
        return [
            'session_id' => $sessionId,
            'exercise' => $exercise,
            'scenarios' => $scenarios,
            'instructions' => $this->getCalibrationInstructions($exercise['category_id'])
        ];
    }
    
    /**
     * Submit judge scores for calibration scenario
     */
    public function submitCalibrationScores($sessionId, $scenarioId, $judgeScores)
    {
        $this->db->beginTransaction();
        
        try {
            // Get session details
            $session = $this->db->query("
                SELECT jcs.*, jce.expert_scores, jce.category_id
                FROM judge_calibration_sessions jcs
                INNER JOIN judge_calibration_exercises jce ON jcs.exercise_id = jce.id
                WHERE jcs.id = ?
            ", [$sessionId]);
            
            if (empty($session)) {
                throw new \Exception("Calibration session not found: {$sessionId}");
            }
            
            $session = $session[0];
            $expertScores = json_decode($session['expert_scores'], true);
            
            // Calculate agreement with expert scores
            $agreement = $this->calculateAgreementScore($judgeScores, $expertScores[$scenarioId]);
            
            // Store calibration scores
            $this->db->insert('judge_calibration_scores', [
                'session_id' => $sessionId,
                'scenario_id' => $scenarioId,
                'judge_scores' => json_encode($judgeScores),
                'agreement_score' => $agreement['overall_agreement'],
                'detailed_analysis' => json_encode($agreement['detailed_analysis']),
                'submitted_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return $agreement;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Calculate agreement between judge and expert scores
     */
    private function calculateAgreementScore($judgeScores, $expertScores)
    {
        $totalCriteria = count($expertScores);
        $agreements = [];
        $overallAgreement = 0;
        
        foreach ($expertScores as $criterionId => $expertScore) {
            if (!isset($judgeScores[$criterionId])) {
                $agreements[$criterionId] = [
                    'agreement' => 0,
                    'reason' => 'Missing score'
                ];
                continue;
            }
            
            $judgeScore = $judgeScores[$criterionId];
            
            // Calculate point difference
            $pointDifference = abs($expertScore['points'] - $judgeScore['points']);
            $maxPoints = max($expertScore['points'], $judgeScore['points']);
            $percentageDifference = $maxPoints > 0 ? ($pointDifference / $maxPoints) * 100 : 0;
            
            // Calculate level agreement
            $levelDifference = abs($expertScore['level'] - $judgeScore['level']);
            
            // Agreement score (0-100)
            $criterionAgreement = 100;
            
            // Penalize for level differences
            $criterionAgreement -= ($levelDifference * 25); // 25% penalty per level difference
            
            // Penalize for point differences
            $criterionAgreement -= min(50, $percentageDifference * 2); // Up to 50% penalty for points
            
            $criterionAgreement = max(0, $criterionAgreement);
            
            $agreements[$criterionId] = [
                'agreement' => round($criterionAgreement, 1),
                'expert_points' => $expertScore['points'],
                'judge_points' => $judgeScore['points'],
                'expert_level' => $expertScore['level'],
                'judge_level' => $judgeScore['level'],
                'point_difference' => round($pointDifference, 1),
                'level_difference' => $levelDifference
            ];
            
            $overallAgreement += $criterionAgreement;
        }
        
        $overallAgreement = $totalCriteria > 0 ? round($overallAgreement / $totalCriteria, 1) : 0;
        
        return [
            'overall_agreement' => $overallAgreement,
            'detailed_analysis' => $agreements,
            'quality_level' => $this->getAgreementQuality($overallAgreement)
        ];
    }
    
    /**
     * Get quality level based on agreement score
     */
    private function getAgreementQuality($agreementScore)
    {
        if ($agreementScore >= self::AGREEMENT_THRESHOLDS['high_agreement']) {
            return 'high_agreement';
        } elseif ($agreementScore >= self::AGREEMENT_THRESHOLDS['moderate_agreement']) {
            return 'moderate_agreement';
        } elseif ($agreementScore >= self::AGREEMENT_THRESHOLDS['low_agreement']) {
            return 'low_agreement';
        } else {
            return 'poor_agreement';
        }
    }
    
    /**
     * Complete calibration session and generate report
     */
    public function completeCalibrationSession($sessionId)
    {
        $this->db->beginTransaction();
        
        try {
            // Get all scores for session
            $scores = $this->db->query("
                SELECT * FROM judge_calibration_scores
                WHERE session_id = ?
                ORDER BY submitted_at ASC
            ", [$sessionId]);
            
            if (empty($scores)) {
                throw new \Exception("No calibration scores found for session: {$sessionId}");
            }
            
            // Calculate overall calibration score
            $totalAgreement = array_sum(array_column($scores, 'agreement_score'));
            $avgAgreement = round($totalAgreement / count($scores), 1);
            
            // Determine calibration level
            $calibrationLevel = $this->getCalibrationLevel($avgAgreement);
            
            // Update session status
            $this->db->query("
                UPDATE judge_calibration_sessions
                SET status = 'completed', 
                    completed_at = NOW(),
                    final_score = ?,
                    calibration_level = ?
                WHERE id = ?
            ", [$avgAgreement, $calibrationLevel, $sessionId]);
            
            // Create calibration record
            $session = $this->db->query("SELECT * FROM judge_calibration_sessions WHERE id = ?", [$sessionId])[0];
            
            $this->db->insert('judge_calibrations', [
                'judge_id' => $session['judge_id'],
                'category_id' => $session['exercise_id'], // Assuming exercise maps to category
                'calibration_score' => $avgAgreement,
                'calibration_level' => $calibrationLevel,
                'session_data' => json_encode($scores),
                'calibrated_at' => date('Y-m-d H:i:s'),
                'valid_until' => date('Y-m-d H:i:s', strtotime('+6 months'))
            ]);
            
            $this->db->commit();
            
            return [
                'calibration_score' => $avgAgreement,
                'calibration_level' => $calibrationLevel,
                'scenarios_completed' => count($scores),
                'recommendations' => $this->generateCalibrationRecommendations($avgAgreement, $scores)
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get calibration level based on score
     */
    private function getCalibrationLevel($score)
    {
        if ($score >= self::CALIBRATION_THRESHOLDS['excellent']) {
            return 'excellent';
        } elseif ($score >= self::CALIBRATION_THRESHOLDS['good']) {
            return 'good';
        } elseif ($score >= self::CALIBRATION_THRESHOLDS['acceptable']) {
            return 'acceptable';
        } else {
            return 'needs_improvement';
        }
    }
    
    /**
     * Generate calibration recommendations
     */
    private function generateCalibrationRecommendations($averageScore, $scores)
    {
        $recommendations = [];
        
        if ($averageScore >= 90) {
            $recommendations[] = "Excellent calibration! You demonstrate strong consistency with expert scoring standards.";
        } elseif ($averageScore >= 75) {
            $recommendations[] = "Good calibration overall. Consider reviewing scenarios where agreement was lower.";
        } elseif ($averageScore >= 60) {
            $recommendations[] = "Acceptable calibration. Additional practice recommended before judging important matches.";
        } else {
            $recommendations[] = "Calibration improvement needed. Please complete additional training before judging assignments.";
        }
        
        // Identify specific areas for improvement
        $lowAgreementCount = 0;
        foreach ($scores as $score) {
            if ($score['agreement_score'] < 70) {
                $lowAgreementCount++;
            }
        }
        
        if ($lowAgreementCount > 0) {
            $recommendations[] = "Focus on improving consistency in {$lowAgreementCount} scenario(s) with lower agreement.";
        }
        
        return $recommendations;
    }
    
    /**
     * Get calibration instructions for category
     */
    private function getCalibrationInstructions($categoryId)
    {
        return [
            'overview' => 'Score each scenario using the official rubric. Your scores will be compared to expert benchmarks.',
            'guidelines' => [
                'Review each scenario carefully before scoring',
                'Use the full range of scoring levels (1-4)',
                'Provide detailed rationale for your scoring decisions',
                'Take your time - accuracy is more important than speed'
            ],
            'scoring_tips' => [
                'Level 1 (Basic): Minimal achievement, significant improvement needed',
                'Level 2 (Developing): Some progress shown, room for improvement',
                'Level 3 (Accomplished): Good achievement meeting expected standards',
                'Level 4 (Exceeded): Exceptional performance exceeding expectations'
            ]
        ];
    }
    
    /**
     * Get judge calibration history and status
     */
    public function getJudgeCalibrationStatus($judgeId)
    {
        $calibrations = $this->db->query("
            SELECT jc.*, c.category_name, 
                   CASE 
                       WHEN jc.valid_until > NOW() THEN 'current'
                       ELSE 'expired'
                   END as status
            FROM judge_calibrations jc
            LEFT JOIN categories c ON jc.category_id = c.id
            WHERE jc.judge_id = ?
            ORDER BY jc.calibrated_at DESC
        ", [$judgeId]);
        
        $sessions = $this->db->query("
            SELECT jcs.*, jce.exercise_name, jce.category_id
            FROM judge_calibration_sessions jcs
            INNER JOIN judge_calibration_exercises jce ON jcs.exercise_id = jce.id
            WHERE jcs.judge_id = ?
            ORDER BY jcs.started_at DESC
        ", [$judgeId]);
        
        return [
            'calibrations' => $calibrations,
            'sessions' => $sessions,
            'needs_recalibration' => $this->needsRecalibration($calibrations)
        ];
    }
    
    /**
     * Check if judge needs recalibration
     */
    private function needsRecalibration($calibrations)
    {
        if (empty($calibrations)) {
            return true; // No calibration yet
        }
        
        foreach ($calibrations as $calibration) {
            if (strtotime($calibration['valid_until']) > time()) {
                return false; // Has valid calibration
            }
        }
        
        return true; // All calibrations expired
    }
}