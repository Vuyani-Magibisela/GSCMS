<?php
// app/Models/Score.php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;
use Exception;

class Score extends BaseModel
{
    protected $table = 'scores';
    
    // Validation rules based on GDE SciBOTICS requirements
    private $validationRules = [
        'score_range' => [
            'min' => 0,
            'max' => 250 // 75 (game) * 3 multiplier + 25 (research) = 250
        ],
        'required_sections' => [
            'game_challenge',
            'research_challenge'
        ],
        'judge_deviation' => [
            'max_percentage' => 15 // Max 15% deviation between judges
        ],
        'completion' => [
            'min_criteria_percentage' => 100 // All criteria must be scored
        ],
        'timing' => [
            'max_duration_minutes' => 45 // Maximum 45 minutes per team
        ]
    ];
    
    public function team()
    {
        $db = Database::getInstance();
        $team = $db->query('SELECT * FROM teams WHERE id = ?', [$this->team_id]);
        return !empty($team) ? $team[0] : null;
    }
    
    public function judge()
    {
        $db = Database::getInstance();
        $judge = $db->query('SELECT * FROM users WHERE id = ? AND role = "judge"', [$this->judge_id]);
        return !empty($judge) ? $judge[0] : null;
    }
    
    public function rubricTemplate()
    {
        $db = Database::getInstance();
        $template = $db->query('SELECT * FROM rubric_templates WHERE id = ?', [$this->rubric_template_id]);
        return !empty($template) ? $template[0] : null;
    }
    
    public function scoreDetails()
    {
        $db = Database::getInstance();
        return $db->query('
            SELECT sd.*, rc.criteria_name, rc.max_points as criteria_max_points,
                   rs.section_name, rs.section_type, rs.multiplier
            FROM score_details sd
            JOIN rubric_criteria rc ON sd.criteria_id = rc.id
            JOIN rubric_sections rs ON rc.section_id = rs.id
            WHERE sd.score_id = ?
            ORDER BY rs.display_order, rc.display_order
        ', [$this->id]);
    }
    
    public function recordScore($data)
    {
        $db = Database::getInstance();
        
        // Validate input data
        $validation = $this->validateScoreData($data);
        if (!$validation['valid']) {
            throw new Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }
        
        $db->beginTransaction();
        
        try {
            // Update or create score record
            $scoreData = [
                'team_id' => $data['team_id'],
                'competition_id' => $data['competition_id'] ?? null,
                'tournament_id' => $data['tournament_id'] ?? null,
                'rubric_template_id' => $data['rubric_template_id'],
                'judge_id' => $data['judge_id'],
                'total_score' => 0,
                'normalized_score' => 0,
                'game_challenge_score' => 0,
                'research_challenge_score' => 0,
                'bonus_points' => $data['bonus_points'] ?? 0,
                'penalty_points' => $data['penalty_points'] ?? 0,
                'scoring_status' => $data['status'] ?? 'in_progress',
                'judge_notes' => $data['judge_notes'] ?? null,
                'scoring_duration_minutes' => $data['duration_minutes'] ?? null,
                'device_info' => json_encode($data['device_info'] ?? [])
            ];
            
            // Check if score already exists
            $existingScore = $db->query('
                SELECT id FROM scores 
                WHERE team_id = ? AND judge_id = ? AND 
                      COALESCE(competition_id, 0) = ? AND COALESCE(tournament_id, 0) = ?
            ', [
                $data['team_id'],
                $data['judge_id'],
                $data['competition_id'] ?? 0,
                $data['tournament_id'] ?? 0
            ]);
            
            if (!empty($existingScore)) {
                $scoreId = $existingScore[0]['id'];
                $this->id = $scoreId;
                
                // Update existing score
                $updateQuery = '
                    UPDATE scores SET 
                        rubric_template_id = ?, total_score = ?, normalized_score = ?,
                        game_challenge_score = ?, research_challenge_score = ?,
                        bonus_points = ?, penalty_points = ?, scoring_status = ?,
                        judge_notes = ?, scoring_duration_minutes = ?, device_info = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ';
                
                $db->query($updateQuery, [
                    $scoreData['rubric_template_id'],
                    $scoreData['total_score'],
                    $scoreData['normalized_score'],
                    $scoreData['game_challenge_score'],
                    $scoreData['research_challenge_score'],
                    $scoreData['bonus_points'],
                    $scoreData['penalty_points'],
                    $scoreData['scoring_status'],
                    $scoreData['judge_notes'],
                    $scoreData['scoring_duration_minutes'],
                    $scoreData['device_info'],
                    $scoreId
                ]);
            } else {
                // Create new score
                $scoreId = $db->insert('scores', $scoreData);
                $this->id = $scoreId;
            }
            
            // Record detailed scores and calculate totals
            $this->recordCriteriaScores($data['criteria_scores']);
            
            // Update calculated fields
            $this->recalculateScores();
            
            // Log the action
            $this->logScoreAction('updated', $data);
            
            $db->commit();
            
            return $this->find($scoreId);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    private function recordCriteriaScores($criteriaScores)
    {
        $db = Database::getInstance();
        
        // Delete existing score details
        $db->query('DELETE FROM score_details WHERE score_id = ?', [$this->id]);
        
        foreach ($criteriaScores as $criteriaScore) {
            $detailData = [
                'score_id' => $this->id,
                'criteria_id' => $criteriaScore['criteria_id'],
                'level_selected' => $criteriaScore['level_selected'] ?? null,
                'points_awarded' => $criteriaScore['points_awarded'],
                'max_possible' => $criteriaScore['max_possible'],
                'judge_comment' => $criteriaScore['comment'] ?? null,
                'scoring_timestamp' => date('Y-m-d H:i:s'),
                'time_spent_seconds' => $criteriaScore['time_spent'] ?? null,
                'revision_number' => 1
            ];
            
            $db->insert('score_details', $detailData);
        }
    }
    
    private function recalculateScores()
    {
        $db = Database::getInstance();
        
        // Get score details with section information
        $details = $db->query('
            SELECT sd.points_awarded, rs.section_type, rs.multiplier
            FROM score_details sd
            JOIN rubric_criteria rc ON sd.criteria_id = rc.id
            JOIN rubric_sections rs ON rc.section_id = rs.id
            WHERE sd.score_id = ?
        ', [$this->id]);
        
        $gameScore = 0;
        $researchScore = 0;
        
        foreach ($details as $detail) {
            $points = $detail['points_awarded'];
            
            if ($detail['section_type'] === 'game_challenge') {
                $gameScore += $points * $detail['multiplier'];
            } else {
                $researchScore += $points;
            }
        }
        
        $totalScore = $gameScore + $researchScore;
        $normalizedScore = ($totalScore / 250) * 100;
        
        // Update scores
        $db->query('
            UPDATE scores 
            SET game_challenge_score = ?, research_challenge_score = ?, 
                total_score = ?, normalized_score = ?, updated_at = NOW()
            WHERE id = ?
        ', [$gameScore, $researchScore, $totalScore, $normalizedScore, $this->id]);
        
        // Refresh model data
        $updated = $this->find($this->id);
        foreach ($updated as $key => $value) {
            $this->$key = $value;
        }
    }
    
    public function validateScoreData($data)
    {
        $errors = [];
        
        // Required fields
        $required = ['team_id', 'judge_id', 'rubric_template_id', 'criteria_scores'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        if (isset($data['criteria_scores'])) {
            // Validate each criterion score
            foreach ($data['criteria_scores'] as $index => $criteriaScore) {
                if (!isset($criteriaScore['criteria_id'])) {
                    $errors[] = "Criteria ID missing for score at index {$index}";
                    continue;
                }
                
                if (!isset($criteriaScore['points_awarded'])) {
                    $errors[] = "Points awarded missing for criteria {$criteriaScore['criteria_id']}";
                    continue;
                }
                
                // Validate points range
                $points = $criteriaScore['points_awarded'];
                $maxPossible = $criteriaScore['max_possible'] ?? 0;
                
                if ($points < 0 || $points > $maxPossible) {
                    $errors[] = "Points for criteria {$criteriaScore['criteria_id']} must be between 0 and {$maxPossible}";
                }
                
                // Validate level selection if provided
                if (isset($criteriaScore['level_selected'])) {
                    $level = $criteriaScore['level_selected'];
                    if ($level < 1 || $level > 4) {
                        $errors[] = "Level for criteria {$criteriaScore['criteria_id']} must be between 1 and 4";
                    }
                }
            }
        }
        
        // Validate score completeness
        if (isset($data['criteria_scores']) && isset($data['rubric_template_id'])) {
            $db = Database::getInstance();
            $totalCriteria = $db->query('
                SELECT COUNT(*) as count 
                FROM rubric_criteria rc
                JOIN rubric_sections rs ON rc.section_id = rs.id
                WHERE rs.rubric_template_id = ?
            ', [$data['rubric_template_id']])[0]['count'] ?? 0;
            
            $scoredCriteria = count($data['criteria_scores']);
            
            if ($scoredCriteria < $totalCriteria) {
                $errors[] = "Incomplete scoring: {$scoredCriteria}/{$totalCriteria} criteria scored";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public function submitScore()
    {
        $db = Database::getInstance();
        
        // Validate completeness
        $validation = $this->validateForSubmission();
        if (!$validation['valid']) {
            throw new Exception('Cannot submit incomplete score: ' . implode(', ', $validation['errors']));
        }
        
        $db->beginTransaction();
        
        try {
            // Update status
            $db->query('
                UPDATE scores 
                SET scoring_status = "submitted", submitted_at = NOW()
                WHERE id = ?
            ', [$this->id]);
            
            // Log submission
            $this->logScoreAction('submitted', [
                'total_score' => $this->total_score,
                'final_score' => $this->final_score
            ]);
            
            // Check for auto-validation
            if ($this->canAutoValidate()) {
                $this->autoValidate();
            }
            
            $db->commit();
            
            return $this->find($this->id);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    private function validateForSubmission()
    {
        $errors = [];
        
        // Check all criteria are scored
        $db = Database::getInstance();
        
        $totalCriteria = $db->query('
            SELECT COUNT(*) as count 
            FROM rubric_criteria rc
            JOIN rubric_sections rs ON rc.section_id = rs.id
            WHERE rs.rubric_template_id = ?
        ', [$this->rubric_template_id])[0]['count'] ?? 0;
        
        $scoredCriteria = $db->query('
            SELECT COUNT(*) as count 
            FROM score_details 
            WHERE score_id = ?
        ', [$this->id])[0]['count'] ?? 0;
        
        if ($scoredCriteria < $totalCriteria) {
            $errors[] = "Incomplete scoring: {$scoredCriteria}/{$totalCriteria} criteria scored";
        }
        
        // Check score reasonableness
        if ($this->total_score < 10) {
            $errors[] = "Total score unusually low - please review";
        }
        
        if ($this->total_score > 250) {
            $errors[] = "Total score exceeds maximum possible (250 points)";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public function checkJudgeConsistency($teamId, $competitionId = null, $tournamentId = null)
    {
        $db = Database::getInstance();
        
        $query = 'SELECT total_score, judge_id FROM scores WHERE team_id = ? AND scoring_status IN ("submitted", "validated", "final")';
        $params = [$teamId];
        
        if ($competitionId) {
            $query .= ' AND competition_id = ?';
            $params[] = $competitionId;
        }
        
        if ($tournamentId) {
            $query .= ' AND tournament_id = ?';
            $params[] = $tournamentId;
        }
        
        $scores = $db->query($query, $params);
        
        if (count($scores) < 2) {
            return ['consistent' => true, 'message' => 'Insufficient scores for comparison'];
        }
        
        $scoreValues = array_column($scores, 'total_score');
        $avgScore = array_sum($scoreValues) / count($scoreValues);
        $maxDeviation = 0;
        
        foreach ($scoreValues as $score) {
            $deviation = abs($score - $avgScore) / $avgScore * 100;
            $maxDeviation = max($maxDeviation, $deviation);
        }
        
        $maxAllowed = $this->validationRules['judge_deviation']['max_percentage'];
        
        return [
            'consistent' => $maxDeviation <= $maxAllowed,
            'max_deviation' => round($maxDeviation, 2),
            'threshold' => $maxAllowed,
            'average_score' => round($avgScore, 2),
            'score_range' => [min($scoreValues), max($scoreValues)],
            'judge_scores' => array_combine(array_column($scores, 'judge_id'), $scoreValues)
        ];
    }
    
    private function canAutoValidate()
    {
        // Check if score is within reasonable bounds and complete
        return ($this->total_score >= 10 && 
                $this->total_score <= 250 &&
                $this->scoring_status === 'submitted');
    }
    
    private function autoValidate()
    {
        $db = Database::getInstance();
        
        $db->query('
            UPDATE scores 
            SET scoring_status = "validated", validated_at = NOW(), validated_by = 1
            WHERE id = ?
        ', [$this->id]);
        
        $this->logScoreAction('validated', ['method' => 'automatic']);
    }
    
    private function logScoreAction($action, $data = [])
    {
        $db = Database::getInstance();
        
        $logData = [
            'score_id' => $this->id,
            'action' => $action,
            'performed_by' => $data['performed_by'] ?? $this->judge_id ?? 1,
            'previous_value' => json_encode($data['previous_value'] ?? []),
            'new_value' => json_encode($data['new_value'] ?? $data),
            'reason' => $data['reason'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'session_id' => session_id() ?: null,
            'additional_data' => json_encode($data['additional'] ?? [])
        ];
        
        $db->insert('score_audit_log', $logData);
    }
    
    public function getScoringSummary()
    {
        $details = $this->scoreDetails();
        $sections = [];
        
        foreach ($details as $detail) {
            $sectionType = $detail['section_type'];
            
            if (!isset($sections[$sectionType])) {
                $sections[$sectionType] = [
                    'name' => $detail['section_name'],
                    'type' => $sectionType,
                    'multiplier' => $detail['multiplier'],
                    'raw_points' => 0,
                    'final_points' => 0,
                    'criteria_count' => 0,
                    'max_possible' => 0
                ];
            }
            
            $sections[$sectionType]['raw_points'] += $detail['points_awarded'];
            $sections[$sectionType]['max_possible'] += $detail['criteria_max_points'];
            $sections[$sectionType]['criteria_count']++;
        }
        
        // Calculate final points with multiplier
        foreach ($sections as &$section) {
            $section['final_points'] = $section['raw_points'] * $section['multiplier'];
            $section['percentage'] = $section['max_possible'] > 0 ? 
                ($section['raw_points'] / $section['max_possible']) * 100 : 0;
        }
        
        return [
            'score_id' => $this->id,
            'team_id' => $this->team_id,
            'judge_id' => $this->judge_id,
            'status' => $this->scoring_status,
            'sections' => $sections,
            'totals' => [
                'game_challenge' => $this->game_challenge_score,
                'research_challenge' => $this->research_challenge_score,
                'bonus_points' => $this->bonus_points,
                'penalty_points' => $this->penalty_points,
                'total_score' => $this->total_score,
                'final_score' => $this->final_score,
                'normalized_score' => $this->normalized_score
            ],
            'metadata' => [
                'duration_minutes' => $this->scoring_duration_minutes,
                'submitted_at' => $this->submitted_at,
                'validated_at' => $this->validated_at
            ]
        ];
    }
}