<?php
// app/Services/JudgeAssignmentService.php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentBracket;

class JudgeAssignmentService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Automatically assign judges to tournament matches based on availability and expertise
     */
    public function autoAssignJudges($tournamentId, $options = [])
    {
        $defaults = [
            'min_judges_per_match' => 3,
            'max_judges_per_match' => 5,
            'prefer_experienced' => true,
            'avoid_conflicts' => true,
            'balanced_workload' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        $this->db->beginTransaction();
        
        try {
            // Get tournament details
            $tournament = Tournament::find($tournamentId);
            if (!$tournament) {
                throw new \Exception("Tournament not found: {$tournamentId}");
            }
            
            // Get available judges for this tournament
            $availableJudges = $this->getAvailableJudges($tournamentId);
            
            // Get all matches needing judges
            $matches = $this->getTournamentMatches($tournamentId);
            
            // Generate optimal assignments
            $assignments = $this->generateOptimalAssignments($matches, $availableJudges, $options);
            
            // Save assignments to database
            foreach ($assignments as $assignment) {
                $this->createJudgeAssignment($assignment);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'assignments_created' => count($assignments),
                'matches_assigned' => count($matches),
                'judges_utilized' => count(array_unique(array_column($assignments, 'judge_id')))
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get judges available for tournament assignment
     */
    public function getAvailableJudges($tournamentId, $categoryId = null)
    {
        $sql = "
            SELECT u.*, jq.*, jp.experience_level, jp.specialty_categories, jp.max_assignments_per_day,
                   COALESCE(assignments.current_load, 0) as current_assignments,
                   COALESCE(calibration.calibration_score, 0) as calibration_score,
                   COALESCE(calibration.last_calibrated, '1970-01-01') as last_calibrated
            FROM users u
            INNER JOIN judge_qualifications jq ON u.id = jq.judge_id
            LEFT JOIN judge_profiles jp ON u.id = jp.judge_id
            LEFT JOIN (
                SELECT judge_id, COUNT(*) as current_load
                FROM judge_assignments ja
                INNER JOIN tournament_matches tm ON ja.match_id = tm.id
                WHERE tm.tournament_id = ? AND ja.status = 'active'
                GROUP BY judge_id
            ) assignments ON u.id = assignments.judge_id
            LEFT JOIN (
                SELECT judge_id, AVG(calibration_score) as calibration_score, 
                       MAX(calibrated_at) as last_calibrated
                FROM judge_calibrations
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY judge_id
            ) calibration ON u.id = calibration.judge_id
            WHERE u.user_role = 'judge' 
            AND u.user_status = 'active'
            AND jq.certification_status = 'certified'
        ";
        
        $params = [$tournamentId];
        
        if ($categoryId) {
            $sql .= " AND (jp.specialty_categories IS NULL OR FIND_IN_SET(?, jp.specialty_categories))";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY calibration.calibration_score DESC, jp.experience_level DESC";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Get tournament matches that need judge assignments
     */
    private function getTournamentMatches($tournamentId)
    {
        return $this->db->query("
            SELECT tm.*, tb.category_id, c.category_name,
                   COALESCE(assigned_judges.judge_count, 0) as current_judges
            FROM tournament_matches tm
            INNER JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            INNER JOIN categories c ON tb.category_id = c.id
            LEFT JOIN (
                SELECT match_id, COUNT(*) as judge_count
                FROM judge_assignments
                WHERE status = 'active'
                GROUP BY match_id
            ) assigned_judges ON tm.id = assigned_judges.match_id
            WHERE tm.tournament_id = ?
            AND tm.match_status IN ('scheduled', 'pending')
            ORDER BY tm.scheduled_time ASC
        ", [$tournamentId]);
    }
    
    /**
     * Generate optimal judge assignments using advanced algorithms
     */
    private function generateOptimalAssignments($matches, $judges, $options)
    {
        $assignments = [];
        $judgeWorkloads = array_fill_keys(array_column($judges, 'id'), 0);
        $judgesByCategory = $this->groupJudgesBySpecialty($judges);
        
        foreach ($matches as $match) {
            $neededJudges = max(0, $options['min_judges_per_match'] - $match['current_judges']);
            
            if ($neededJudges === 0) continue;
            
            // Get suitable judges for this match
            $suitableJudges = $this->getSuitableJudges(
                $judges, 
                $match['category_id'], 
                $judgeWorkloads, 
                $options
            );
            
            // Sort judges by suitability score
            usort($suitableJudges, function($a, $b) {
                return $b['suitability_score'] <=> $a['suitability_score'];
            });
            
            // Assign top judges
            $assignedCount = 0;
            foreach ($suitableJudges as $judge) {
                if ($assignedCount >= $neededJudges) break;
                if ($assignedCount >= $options['max_judges_per_match']) break;
                
                // Check if judge is available for this time slot
                if ($this->isJudgeAvailable($judge['id'], $match['scheduled_time'], $match['estimated_duration'])) {
                    $assignments[] = [
                        'match_id' => $match['id'],
                        'judge_id' => $judge['id'],
                        'assignment_type' => $assignedCount === 0 ? 'lead_judge' : 'panel_judge',
                        'assigned_at' => date('Y-m-d H:i:s'),
                        'status' => 'active',
                        'auto_assigned' => true
                    ];
                    
                    $judgeWorkloads[$judge['id']]++;
                    $assignedCount++;
                }
            }
        }
        
        return $assignments;
    }
    
    /**
     * Calculate suitability score for judge-match pairing
     */
    private function calculateSuitabilityScore($judge, $categoryId, $currentWorkload, $options)
    {
        $score = 100; // Base score
        
        // Experience level bonus
        $experienceMultipliers = [
            'novice' => 0.8,
            'intermediate' => 1.0,
            'advanced' => 1.2,
            'expert' => 1.5
        ];
        $score *= $experienceMultipliers[$judge['experience_level']] ?? 1.0;
        
        // Category specialty bonus
        if ($judge['specialty_categories']) {
            $specialties = explode(',', $judge['specialty_categories']);
            if (in_array($categoryId, $specialties)) {
                $score *= 1.3; // 30% bonus for category expertise
            }
        }
        
        // Calibration score bonus
        if ($judge['calibration_score'] > 0) {
            $score *= (1 + ($judge['calibration_score'] / 100) * 0.2);
        }
        
        // Workload balance penalty
        if ($options['balanced_workload']) {
            $maxLoad = $judge['max_assignments_per_day'] ?? 10;
            $loadRatio = $currentWorkload / $maxLoad;
            $score *= (1 - $loadRatio * 0.5); // Penalty for high workload
        }
        
        // Recency penalty for last calibration
        $daysSinceCalibration = (time() - strtotime($judge['last_calibrated'])) / (60 * 60 * 24);
        if ($daysSinceCalibration > 180) { // 6 months
            $score *= 0.9;
        }
        
        return round($score, 2);
    }
    
    /**
     * Get judges suitable for a specific match
     */
    private function getSuitableJudges($judges, $categoryId, $judgeWorkloads, $options)
    {
        $suitable = [];
        
        foreach ($judges as $judge) {
            $workload = $judgeWorkloads[$judge['id']];
            $maxLoad = $judge['max_assignments_per_day'] ?? 10;
            
            // Skip if judge is at capacity
            if ($workload >= $maxLoad) continue;
            
            // Calculate suitability score
            $suitabilityScore = $this->calculateSuitabilityScore($judge, $categoryId, $workload, $options);
            
            $judge['suitability_score'] = $suitabilityScore;
            $suitable[] = $judge;
        }
        
        return $suitable;
    }
    
    /**
     * Check if judge is available at specific time
     */
    private function isJudgeAvailable($judgeId, $scheduledTime, $estimatedDuration)
    {
        $endTime = date('Y-m-d H:i:s', strtotime($scheduledTime) + ($estimatedDuration * 60));
        
        $conflicts = $this->db->query("
            SELECT COUNT(*) as conflict_count
            FROM judge_assignments ja
            INNER JOIN tournament_matches tm ON ja.match_id = tm.id
            WHERE ja.judge_id = ?
            AND ja.status = 'active'
            AND (
                (tm.scheduled_time BETWEEN ? AND ?) OR
                (DATE_ADD(tm.scheduled_time, INTERVAL tm.estimated_duration MINUTE) BETWEEN ? AND ?) OR
                (tm.scheduled_time <= ? AND DATE_ADD(tm.scheduled_time, INTERVAL tm.estimated_duration MINUTE) >= ?)
            )
        ", [$judgeId, $scheduledTime, $endTime, $scheduledTime, $endTime, $scheduledTime, $endTime]);
        
        return $conflicts[0]['conflict_count'] == 0;
    }
    
    /**
     * Group judges by their specialty categories
     */
    private function groupJudgesBySpecialty($judges)
    {
        $grouped = [];
        
        foreach ($judges as $judge) {
            if ($judge['specialty_categories']) {
                $specialties = explode(',', $judge['specialty_categories']);
                foreach ($specialties as $specialty) {
                    $grouped[trim($specialty)][] = $judge;
                }
            } else {
                $grouped['general'][] = $judge;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Create a judge assignment record
     */
    private function createJudgeAssignment($assignmentData)
    {
        return $this->db->insert('judge_assignments', $assignmentData);
    }
    
    /**
     * Manually assign a judge to a specific match
     */
    public function assignJudgeToMatch($judgeId, $matchId, $assignmentType = 'panel_judge')
    {
        // Validate judge availability
        $match = $this->db->query("SELECT * FROM tournament_matches WHERE id = ?", [$matchId]);
        if (empty($match)) {
            throw new \Exception("Match not found: {$matchId}");
        }
        
        $match = $match[0];
        
        if (!$this->isJudgeAvailable($judgeId, $match['scheduled_time'], $match['estimated_duration'])) {
            throw new \Exception("Judge is not available at the scheduled time");
        }
        
        // Check if already assigned
        $existing = $this->db->query("
            SELECT id FROM judge_assignments 
            WHERE judge_id = ? AND match_id = ? AND status = 'active'
        ", [$judgeId, $matchId]);
        
        if (!empty($existing)) {
            throw new \Exception("Judge is already assigned to this match");
        }
        
        return $this->createJudgeAssignment([
            'match_id' => $matchId,
            'judge_id' => $judgeId,
            'assignment_type' => $assignmentType,
            'assigned_at' => date('Y-m-d H:i:s'),
            'status' => 'active',
            'auto_assigned' => false
        ]);
    }
    
    /**
     * Remove judge assignment
     */
    public function removeJudgeAssignment($assignmentId, $reason = null)
    {
        $this->db->query("
            UPDATE judge_assignments 
            SET status = 'cancelled', removed_at = NOW(), removal_reason = ?
            WHERE id = ?
        ", [$reason, $assignmentId]);
        
        return true;
    }
    
    /**
     * Get assignment statistics for reporting
     */
    public function getAssignmentStatistics($tournamentId)
    {
        $stats = [];
        
        // Overall assignment coverage
        $coverage = $this->db->query("
            SELECT 
                COUNT(DISTINCT tm.id) as total_matches,
                COUNT(DISTINCT ja.match_id) as assigned_matches,
                AVG(judge_counts.judge_count) as avg_judges_per_match
            FROM tournament_matches tm
            LEFT JOIN judge_assignments ja ON tm.id = ja.match_id AND ja.status = 'active'
            LEFT JOIN (
                SELECT match_id, COUNT(*) as judge_count
                FROM judge_assignments
                WHERE status = 'active'
                GROUP BY match_id
            ) judge_counts ON tm.id = judge_counts.match_id
            WHERE tm.tournament_id = ?
        ", [$tournamentId]);
        
        $stats['coverage'] = $coverage[0];
        
        // Judge workload distribution
        $workload = $this->db->query("
            SELECT 
                u.first_name, u.last_name, u.email,
                COUNT(ja.id) as assignments,
                jp.max_assignments_per_day,
                AVG(jc.calibration_score) as avg_calibration
            FROM users u
            INNER JOIN judge_assignments ja ON u.id = ja.judge_id
            INNER JOIN tournament_matches tm ON ja.match_id = tm.id
            LEFT JOIN judge_profiles jp ON u.id = jp.judge_id
            LEFT JOIN judge_calibrations jc ON u.id = jc.judge_id
            WHERE tm.tournament_id = ? AND ja.status = 'active'
            GROUP BY u.id
            ORDER BY assignments DESC
        ", [$tournamentId]);
        
        $stats['judge_workload'] = $workload;
        
        // Category coverage
        $categoryStats = $this->db->query("
            SELECT 
                c.category_name,
                COUNT(DISTINCT tm.id) as total_matches,
                COUNT(DISTINCT ja.match_id) as assigned_matches,
                AVG(judge_counts.judge_count) as avg_judges_per_match
            FROM categories c
            INNER JOIN tournament_brackets tb ON c.id = tb.category_id
            INNER JOIN tournament_matches tm ON tb.id = tm.bracket_id
            LEFT JOIN judge_assignments ja ON tm.id = ja.match_id AND ja.status = 'active'
            LEFT JOIN (
                SELECT match_id, COUNT(*) as judge_count
                FROM judge_assignments
                WHERE status = 'active'
                GROUP BY match_id
            ) judge_counts ON tm.id = judge_counts.match_id
            WHERE tm.tournament_id = ?
            GROUP BY c.id, c.category_name
            ORDER BY c.category_name
        ", [$tournamentId]);
        
        $stats['category_coverage'] = $categoryStats;
        
        return $stats;
    }
}