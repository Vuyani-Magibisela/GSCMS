<?php
// app/Services/TournamentScoringIntegrationService.php

namespace App\Services;

use App\Core\Database;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\Score;
use App\Models\RubricTemplate;
use App\Services\JudgeAssignmentService;
use App\Services\MultiJudgeScoringService;
use App\Services\ScoringValidationService;

class TournamentScoringIntegrationService
{
    private $db;
    private $judgeAssignmentService;
    private $multiJudgeScoringService;
    private $validationService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->judgeAssignmentService = new JudgeAssignmentService();
        $this->multiJudgeScoringService = new MultiJudgeScoringService();
        $this->validationService = new ScoringValidationService();
    }
    
    /**
     * Initialize scoring for a tournament
     */
    public function initializeTournamentScoring($tournamentId)
    {
        $this->db->beginTransaction();
        
        try {
            $tournament = Tournament::find($tournamentId);
            if (!$tournament) {
                throw new \Exception("Tournament not found: {$tournamentId}");
            }
            
            // 1. Create or validate rubric template for tournament category
            $rubricTemplate = $this->ensureRubricTemplate($tournament['category_id']);
            
            // 2. Auto-assign judges to tournament matches
            $assignmentResult = $this->judgeAssignmentService->autoAssignJudges($tournamentId, [
                'min_judges_per_match' => 3,
                'max_judges_per_match' => 5,
                'prefer_experienced' => true,
                'balanced_workload' => true
            ]);
            
            // 3. Create scoring sessions for all matches
            $scoringSessionsCreated = $this->createScoringSessionsForTournament($tournamentId);
            
            // 4. Initialize real-time scoring status tracking
            $this->initializeRealTimeScoringStatus($tournamentId);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'rubric_template_id' => $rubricTemplate['id'],
                'judge_assignments' => $assignmentResult,
                'scoring_sessions_created' => $scoringSessionsCreated,
                'tournament_ready_for_scoring' => true
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Process match scoring completion
     */
    public function processMatchScoringCompletion($matchId)
    {
        $this->db->beginTransaction();
        
        try {
            $match = TournamentMatch::find($matchId);
            if (!$match) {
                throw new \Exception("Tournament match not found: {$matchId}");
            }
            
            // Get all scores for this match
            $matchScores = $this->getMatchScores($matchId);
            
            if (empty($matchScores)) {
                throw new \Exception("No scores found for match: {$matchId}");
            }
            
            // Check if all required judges have submitted scores
            $requiredJudges = $this->getRequiredJudgesForMatch($matchId);
            $scoringJudges = array_unique(array_column($matchScores, 'judge_id'));
            
            if (count($scoringJudges) < count($requiredJudges)) {
                // Not all judges have scored yet
                return [
                    'scoring_complete' => false,
                    'judges_remaining' => array_diff($requiredJudges, $scoringJudges),
                    'scores_submitted' => count($scoringJudges),
                    'scores_required' => count($requiredJudges)
                ];
            }
            
            // Validate all scores
            $validationResults = [];
            foreach ($matchScores as $score) {
                $validation = $this->validationService->validateScore($score);
                $validationResults[] = $validation;
                
                if (!$validation['is_valid']) {
                    throw new \Exception("Score validation failed for judge {$score['judge_id']}: " . implode(', ', $validation['errors']));
                }
            }
            
            // Aggregate multi-judge scores
            $aggregationResult = $this->multiJudgeScoringService->aggregateScores(
                $matchId,
                'average',
                ['detect_outliers' => true, 'confidence_threshold' => 70]
            );
            
            // Determine match winner based on aggregated scores
            $matchResult = $this->determineMatchWinner($match, $aggregationResult);
            
            // Update match status
            $this->updateMatchWithScoringResults($matchId, $matchResult, $aggregationResult);
            
            // Update tournament progression if all matches in round are complete
            $this->checkAndUpdateTournamentProgression($match['tournament_id']);
            
            // Create tournament results record
            $this->createTournamentResult($match, $matchResult, $aggregationResult);
            
            $this->db->commit();
            
            return [
                'scoring_complete' => true,
                'match_winner' => $matchResult['winner'],
                'aggregation_result' => $aggregationResult,
                'validation_summary' => $this->summarizeValidationResults($validationResults),
                'tournament_advancement_updated' => true
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get real-time tournament scoring status
     */
    public function getTournamentScoringStatus($tournamentId)
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            throw new \Exception("Tournament not found: {$tournamentId}");
        }
        
        // Get all matches and their scoring status
        $matchesStatus = $this->db->query("
            SELECT 
                tm.id as match_id,
                tm.match_number,
                tm.round_number,
                tm.match_status,
                tm.team1_id,
                tm.team2_id,
                tm.winner_team_id,
                t1.name as team1_name,
                t2.name as team2_name,
                COUNT(DISTINCT ja.judge_id) as judges_assigned,
                COUNT(DISTINCT s.judge_id) as judges_scored,
                AVG(CASE WHEN s.status = 'final' THEN 1 ELSE 0 END) as scoring_complete_ratio,
                MAX(s.updated_at) as last_score_update
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN judge_assignments ja ON tm.id = ja.match_id AND ja.status = 'active'
            LEFT JOIN scores s ON tm.id = s.match_id
            WHERE tm.tournament_id = ?
            GROUP BY tm.id
            ORDER BY tm.round_number, tm.match_number
        ", [$tournamentId]);
        
        // Calculate overall progress
        $totalMatches = count($matchesStatus);
        $completedMatches = 0;
        $inProgressMatches = 0;
        $pendingMatches = 0;
        
        foreach ($matchesStatus as $match) {
            if ($match['scoring_complete_ratio'] == 1.0 && $match['judges_scored'] > 0) {
                $completedMatches++;
            } elseif ($match['judges_scored'] > 0) {
                $inProgressMatches++;
            } else {
                $pendingMatches++;
            }
        }
        
        // Get judge workload status
        $judgeWorkload = $this->db->query("
            SELECT 
                u.first_name,
                u.last_name,
                u.email,
                COUNT(ja.id) as assignments,
                COUNT(DISTINCT s.id) as completed_scores,
                AVG(CASE WHEN s.status = 'final' THEN 1 ELSE 0 END) as completion_rate
            FROM users u
            INNER JOIN judge_assignments ja ON u.id = ja.judge_id
            INNER JOIN tournament_matches tm ON ja.match_id = tm.id
            LEFT JOIN scores s ON ja.judge_id = s.judge_id AND ja.match_id = s.match_id
            WHERE tm.tournament_id = ? AND ja.status = 'active'
            GROUP BY u.id
            ORDER BY completion_rate ASC, assignments DESC
        ", [$tournamentId]);
        
        return [
            'tournament_id' => $tournamentId,
            'tournament_name' => $tournament['tournament_name'],
            'status' => $tournament['status'],
            'progress' => [
                'total_matches' => $totalMatches,
                'completed_matches' => $completedMatches,
                'in_progress_matches' => $inProgressMatches,
                'pending_matches' => $pendingMatches,
                'completion_percentage' => $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 1) : 0
            ],
            'matches_detail' => $matchesStatus,
            'judge_workload' => $judgeWorkload,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Handle scoring disputes and reviews
     */
    public function handleScoringDispute($matchId, $disputeData)
    {
        $this->db->beginTransaction();
        
        try {
            // Log the dispute
            $disputeId = $this->db->insert('scoring_disputes', [
                'match_id' => $matchId,
                'disputed_by' => $disputeData['disputed_by'],
                'dispute_type' => $disputeData['dispute_type'],
                'description' => $disputeData['description'],
                'evidence_files' => json_encode($disputeData['evidence_files'] ?? []),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Freeze current scores
            $this->db->query("
                UPDATE scores 
                SET status = 'disputed', dispute_id = ?
                WHERE match_id = ?
            ", [$disputeId, $matchId]);
            
            // Request additional judge review if needed
            if ($disputeData['request_additional_review']) {
                $this->requestAdditionalJudgeReview($matchId, $disputeId);
            }
            
            // Update match status
            $this->db->query("
                UPDATE tournament_matches 
                SET match_status = 'under_review', 
                    updated_at = NOW()
                WHERE id = ?
            ", [$matchId]);
            
            $this->db->commit();
            
            return [
                'dispute_id' => $disputeId,
                'status' => 'logged',
                'match_frozen' => true,
                'additional_review_requested' => $disputeData['request_additional_review'] ?? false
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Export tournament scoring results
     */
    public function exportTournamentResults($tournamentId, $format = 'json')
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            throw new \Exception("Tournament not found: {$tournamentId}");
        }
        
        // Get comprehensive tournament results
        $results = [
            'tournament_info' => [
                'id' => $tournament['id'],
                'name' => $tournament['tournament_name'],
                'category' => $tournament['category']['category_name'] ?? 'Unknown',
                'type' => $tournament['tournament_type'],
                'status' => $tournament['status'],
                'start_date' => $tournament['start_date'],
                'end_date' => $tournament['end_date']
            ],
            'final_standings' => $this->getFinalStandings($tournamentId),
            'match_results' => $this->getDetailedMatchResults($tournamentId),
            'scoring_statistics' => $this->getScoringStatistics($tournamentId),
            'judge_performance' => $this->getJudgePerformanceMetrics($tournamentId),
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        switch ($format) {
            case 'json':
                return json_encode($results, JSON_PRETTY_PRINT);
            
            case 'csv':
                return $this->convertResultsToCSV($results);
            
            case 'pdf':
                return $this->generateResultsPDF($results);
            
            default:
                return $results;
        }
    }
    
    /**
     * Private helper methods
     */
    
    private function ensureRubricTemplate($categoryId)
    {
        $existingTemplate = RubricTemplate::getByCategory($categoryId);
        
        if (!$existingTemplate) {
            return RubricTemplate::createFromCategory($categoryId);
        }
        
        return $existingTemplate;
    }
    
    private function createScoringSessionsForTournament($tournamentId)
    {
        $matches = $this->db->query("
            SELECT tm.id as match_id, ja.judge_id
            FROM tournament_matches tm
            INNER JOIN judge_assignments ja ON tm.id = ja.match_id
            WHERE tm.tournament_id = ? AND ja.status = 'active'
        ", [$tournamentId]);
        
        $sessionsCreated = 0;
        
        foreach ($matches as $assignment) {
            $this->db->insert('scoring_sessions', [
                'match_id' => $assignment['match_id'],
                'judge_id' => $assignment['judge_id'],
                'tournament_id' => $tournamentId,
                'session_status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $sessionsCreated++;
        }
        
        return $sessionsCreated;
    }
    
    private function initializeRealTimeScoringStatus($tournamentId)
    {
        return $this->db->insert('tournament_scoring_status', [
            'tournament_id' => $tournamentId,
            'total_matches' => $this->db->query("SELECT COUNT(*) as count FROM tournament_matches WHERE tournament_id = ?", [$tournamentId])[0]['count'],
            'completed_matches' => 0,
            'last_updated' => date('Y-m-d H:i:s'),
            'status' => 'initialized'
        ]);
    }
    
    private function getMatchScores($matchId)
    {
        return $this->db->query("
            SELECT s.*, sd.total_score, sd.criteria_breakdown
            FROM scores s
            INNER JOIN score_details sd ON s.id = sd.score_id
            WHERE s.match_id = ? AND s.status IN ('final', 'submitted')
        ", [$matchId]);
    }
    
    private function getRequiredJudgesForMatch($matchId)
    {
        $judges = $this->db->query("
            SELECT judge_id
            FROM judge_assignments
            WHERE match_id = ? AND status = 'active'
        ", [$matchId]);
        
        return array_column($judges, 'judge_id');
    }
    
    private function determineMatchWinner($match, $aggregationResult)
    {
        // For team vs team matches, higher aggregated score wins
        $team1Score = $aggregationResult['team_scores'][$match['team1_id']] ?? 0;
        $team2Score = $aggregationResult['team_scores'][$match['team2_id']] ?? 0;
        
        if ($team1Score > $team2Score) {
            return [
                'winner' => $match['team1_id'],
                'loser' => $match['team2_id'],
                'winning_score' => $team1Score,
                'losing_score' => $team2Score,
                'margin' => $team1Score - $team2Score
            ];
        } elseif ($team2Score > $team1Score) {
            return [
                'winner' => $match['team2_id'],
                'loser' => $match['team1_id'],
                'winning_score' => $team2Score,
                'losing_score' => $team1Score,
                'margin' => $team2Score - $team1Score
            ];
        } else {
            // Tie - use tiebreaker rules
            return $this->resolveTie($match, $aggregationResult);
        }
    }
    
    private function resolveTie($match, $aggregationResult)
    {
        // Implement tiebreaker logic (e.g., highest individual criterion score)
        // For now, default to team1 as winner in ties
        return [
            'winner' => $match['team1_id'],
            'loser' => $match['team2_id'],
            'winning_score' => $aggregationResult['team_scores'][$match['team1_id']],
            'losing_score' => $aggregationResult['team_scores'][$match['team2_id']],
            'margin' => 0,
            'tie_resolved_by' => 'default_rule'
        ];
    }
    
    private function updateMatchWithScoringResults($matchId, $matchResult, $aggregationResult)
    {
        $this->db->query("
            UPDATE tournament_matches 
            SET winner_team_id = ?,
                match_status = 'completed',
                final_score_data = ?,
                completed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ", [
            $matchResult['winner'],
            json_encode($aggregationResult),
            $matchId
        ]);
    }
    
    private function checkAndUpdateTournamentProgression($tournamentId)
    {
        // Check if current round is complete
        $currentRound = $this->db->query("
            SELECT current_round FROM tournaments WHERE id = ?
        ", [$tournamentId])[0]['current_round'];
        
        $incompleteMatches = $this->db->query("
            SELECT COUNT(*) as count
            FROM tournament_matches
            WHERE tournament_id = ? 
            AND round_number = ?
            AND match_status NOT IN ('completed', 'bye')
        ", [$tournamentId, $currentRound])[0]['count'];
        
        if ($incompleteMatches == 0) {
            // Round is complete, advance to next round or complete tournament
            $this->advanceTournamentRound($tournamentId);
        }
    }
    
    private function advanceTournamentRound($tournamentId)
    {
        $tournament = Tournament::find($tournamentId);
        $nextRound = $tournament['current_round'] + 1;
        
        if ($nextRound > $tournament['rounds_total']) {
            // Tournament is complete
            $this->db->query("
                UPDATE tournaments 
                SET status = 'completed', current_round = ?, updated_at = NOW()
                WHERE id = ?
            ", [$tournament['rounds_total'], $tournamentId]);
        } else {
            // Advance to next round
            $this->db->query("
                UPDATE tournaments 
                SET current_round = ?, updated_at = NOW()
                WHERE id = ?
            ", [$nextRound, $tournamentId]);
        }
    }
    
    private function createTournamentResult($match, $matchResult, $aggregationResult)
    {
        return $this->db->insert('tournament_results', [
            'tournament_id' => $match['tournament_id'],
            'match_id' => $match['id'],
            'team_id' => $matchResult['winner'],
            'opponent_id' => $matchResult['loser'],
            'result_type' => 'match_win',
            'score_achieved' => $matchResult['winning_score'],
            'opponent_score' => $matchResult['losing_score'],
            'score_data' => json_encode($aggregationResult),
            'round_number' => $match['round_number'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function summarizeValidationResults($validationResults)
    {
        $summary = [
            'total_scores' => count($validationResults),
            'valid_scores' => 0,
            'scores_with_warnings' => 0,
            'scores_with_errors' => 0,
            'average_confidence' => 0
        ];
        
        $totalConfidence = 0;
        
        foreach ($validationResults as $result) {
            if ($result['is_valid']) {
                $summary['valid_scores']++;
            }
            if (!empty($result['warnings'])) {
                $summary['scores_with_warnings']++;
            }
            if (!empty($result['errors'])) {
                $summary['scores_with_errors']++;
            }
            $totalConfidence += $result['confidence_score'];
        }
        
        $summary['average_confidence'] = count($validationResults) > 0 
            ? round($totalConfidence / count($validationResults), 1) 
            : 0;
        
        return $summary;
    }
    
    private function requestAdditionalJudgeReview($matchId, $disputeId)
    {
        // Logic to assign additional judges for review
        // This could involve finding available expert judges
        return true;
    }
    
    private function getFinalStandings($tournamentId)
    {
        return $this->db->query("
            SELECT 
                t.id,
                t.name as team_name,
                t.team_code,
                s.name as school_name,
                COUNT(tr.id) as matches_played,
                SUM(CASE WHEN tr.result_type = 'match_win' THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN tr.result_type = 'match_loss' THEN 1 ELSE 0 END) as losses,
                AVG(tr.score_achieved) as avg_score,
                MAX(tr.score_achieved) as best_score,
                SUM(tr.score_achieved) as total_score
            FROM teams t
            INNER JOIN schools s ON t.school_id = s.id
            INNER JOIN tournament_results tr ON t.id = tr.team_id
            WHERE tr.tournament_id = ?
            GROUP BY t.id
            ORDER BY wins DESC, avg_score DESC, best_score DESC
        ", [$tournamentId]);
    }
    
    private function getDetailedMatchResults($tournamentId)
    {
        return $this->db->query("
            SELECT 
                tm.id as match_id,
                tm.match_number,
                tm.round_number,
                tm.match_status,
                t1.name as team1_name,
                t2.name as team2_name,
                tw.name as winner_name,
                tm.final_score_data,
                tm.completed_at
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN teams tw ON tm.winner_team_id = tw.id
            WHERE tm.tournament_id = ?
            ORDER BY tm.round_number, tm.match_number
        ", [$tournamentId]);
    }
    
    private function getScoringStatistics($tournamentId)
    {
        return $this->db->query("
            SELECT 
                COUNT(DISTINCT s.id) as total_scores_submitted,
                COUNT(DISTINCT s.judge_id) as judges_participated,
                AVG(sd.total_score) as average_score,
                MIN(sd.total_score) as lowest_score,
                MAX(sd.total_score) as highest_score,
                STDDEV(sd.total_score) as score_stddev
            FROM scores s
            INNER JOIN score_details sd ON s.id = sd.score_id
            INNER JOIN tournament_matches tm ON s.match_id = tm.id
            WHERE tm.tournament_id = ?
        ", [$tournamentId]);
    }
    
    private function getJudgePerformanceMetrics($tournamentId)
    {
        return $this->db->query("
            SELECT 
                u.first_name,
                u.last_name,
                u.email,
                COUNT(s.id) as scores_submitted,
                AVG(sd.total_score) as avg_score_given,
                STDDEV(sd.total_score) as score_consistency,
                AVG(jc.calibration_score) as calibration_score
            FROM users u
            INNER JOIN scores s ON u.id = s.judge_id
            INNER JOIN score_details sd ON s.id = sd.score_id
            INNER JOIN tournament_matches tm ON s.match_id = tm.id
            LEFT JOIN judge_calibrations jc ON u.id = jc.judge_id
            WHERE tm.tournament_id = ?
            GROUP BY u.id
            ORDER BY scores_submitted DESC, calibration_score DESC
        ", [$tournamentId]);
    }
}