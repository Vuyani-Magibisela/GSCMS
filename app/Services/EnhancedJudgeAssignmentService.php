<?php
// app/Services/EnhancedJudgeAssignmentService.php

namespace App\Services;

use App\Core\Database;
use App\Models\EnhancedJudgeProfile;
use App\Models\Organization;

class EnhancedJudgeAssignmentService
{
    private $db;
    
    const MIN_JUDGES_PER_CATEGORY = 3;
    const MAX_TEAMS_PER_JUDGE = 10;
    const JUDGE_BREAK_MINUTES = 30;
    
    // Scoring weights for assignment algorithm
    const WEIGHT_EXPERIENCE = 0.25;
    const WEIGHT_EXPERTISE = 0.30;
    const WEIGHT_CALIBRATION = 0.20;
    const WEIGHT_WORKLOAD = 0.15;
    const WEIGHT_AVAILABILITY = 0.10;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Assign judges to a competition with advanced algorithms
     */
    public function assignJudges($competitionId, $phaseId, $options = [])
    {
        $defaults = [
            'min_judges_per_category' => self::MIN_JUDGES_PER_CATEGORY,
            'max_teams_per_judge' => self::MAX_TEAMS_PER_JUDGE,
            'prefer_experienced' => true,
            'balance_workload' => true,
            'avoid_conflicts' => true,
            'require_calibration' => true,
            'organization_diversity' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        $this->db->beginTransaction();
        
        try {
            $competition = $this->getCompetition($competitionId);
            $categories = $this->getCompetitionCategories($competitionId);
            $assignments = [];
            
            foreach ($categories as $category) {
                $teams = $this->getTeamsForCategory($competitionId, $category['id']);
                $availableJudges = $this->getAvailableJudges($category['id'], $competition['date']);
                
                // Validate sufficient judges
                if (count($availableJudges) < $options['min_judges_per_category']) {
                    throw new InsufficientJudgesException(
                        "Not enough qualified judges for category: {$category['category_name']}. " .
                        "Required: {$options['min_judges_per_category']}, Available: " . count($availableJudges)
                    );
                }
                
                // Generate optimal assignments for this category
                $categoryAssignments = $this->generateOptimalAssignments(
                    $teams,
                    $availableJudges,
                    $category,
                    $competition,
                    $phaseId,
                    $options
                );
                
                $assignments = array_merge($assignments, $categoryAssignments);
            }
            
            // Save all assignments
            $this->saveAssignments($assignments);
            
            // Create judge panels
            $this->createJudgePanels($competitionId, $assignments);
            
            // Send notifications to assigned judges
            $this->notifyAssignedJudges($assignments);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'total_assignments' => count($assignments),
                'categories_covered' => count($categories),
                'judges_assigned' => count(array_unique(array_column($assignments, 'judge_id'))),
                'assignments' => $assignments
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get available judges with comprehensive filtering and scoring
     */
    public function getAvailableJudges($categoryId, $competitionDate)
    {
        $sql = "
            SELECT 
                jp.*,
                u.first_name, u.last_name, u.email, u.phone,
                o.organization_name, o.organization_type,
                COALESCE(current_assignments.assignment_count, 0) as current_workload,
                COALESCE(performance.avg_performance, 0) as avg_performance_score,
                COALESCE(calibration.calibration_score, 0) as calibration_score,
                COALESCE(calibration.last_calibrated, '1970-01-01') as last_calibrated,
                COALESCE(feedback.avg_rating, 0) as avg_feedback_rating,
                COUNT(qualifications.id) as qualification_count
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN organizations o ON jp.organization_id = o.id
            LEFT JOIN (
                SELECT 
                    judge_id, 
                    COUNT(*) as assignment_count
                FROM judge_competition_assignments
                WHERE session_date = ? 
                AND assignment_status IN ('assigned', 'confirmed')
                GROUP BY judge_id
            ) current_assignments ON jp.id = current_assignments.judge_id
            LEFT JOIN (
                SELECT 
                    judge_id,
                    AVG((consistency_score + on_time_rate + completion_rate + 
                         COALESCE(peer_rating * 20, 80) + 
                         COALESCE(admin_rating * 20, 80)) / 5) as avg_performance
                FROM judge_performance_metrics
                WHERE calculated_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY judge_id
            ) performance ON jp.id = performance.judge_id
            LEFT JOIN (
                SELECT 
                    judge_id, 
                    AVG(calibration_score) as calibration_score,
                    MAX(calibrated_at) as last_calibrated
                FROM judge_calibrations
                WHERE valid_until > NOW()
                GROUP BY judge_id
            ) calibration ON jp.id = calibration.judge_id
            LEFT JOIN (
                SELECT 
                    judge_id,
                    AVG(rating) as avg_rating
                FROM judge_feedback
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
                AND rating IS NOT NULL
                GROUP BY judge_id
            ) feedback ON jp.id = feedback.judge_id
            LEFT JOIN judge_qualifications qualifications ON jp.id = qualifications.judge_id
                AND qualifications.verified = 1
                AND (qualifications.expiry_date IS NULL OR qualifications.expiry_date > CURDATE())
            WHERE jp.status = 'active'
            AND u.status = 'active'
            AND jp.onboarding_completed = 1
            AND (jp.categories_qualified IS NULL OR JSON_CONTAINS(jp.categories_qualified, ?))
            AND (jp.background_check_status != 'failed')
            GROUP BY jp.id
            HAVING current_workload < jp.max_assignments_per_day
            ORDER BY 
                avg_performance_score DESC,
                calibration_score DESC,
                jp.experience_level DESC
        ";
        
        $judges = $this->db->query($sql, [$competitionDate, json_encode($categoryId)]);
        
        // Calculate suitability scores for each judge
        foreach ($judges as &$judge) {
            $judge['suitability_score'] = $this->calculateSuitabilityScore($judge, $categoryId);
            $judge['availability_score'] = $this->calculateAvailabilityScore($judge, $competitionDate);
            $judge['overall_score'] = $this->calculateOverallScore($judge);
        }
        
        // Sort by overall score
        usort($judges, function($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        return $judges;
    }
    
    /**
     * Calculate judge suitability score for a specific category
     */
    private function calculateSuitabilityScore($judge, $categoryId)
    {
        $score = 0;
        
        // Experience level scoring
        $experienceScores = [
            'novice' => 60,
            'intermediate' => 75,
            'advanced' => 90,
            'expert' => 100
        ];
        $score += $experienceScores[$judge['experience_level']] * self::WEIGHT_EXPERIENCE;
        
        // Category expertise scoring
        $categoryScore = 70; // Base score
        if ($judge['categories_qualified']) {
            $qualifiedCategories = json_decode($judge['categories_qualified'], true);
            if (in_array($categoryId, $qualifiedCategories)) {
                $categoryScore = 100; // Perfect match
            }
        }
        $score += $categoryScore * self::WEIGHT_EXPERTISE;
        
        // Calibration scoring
        $calibrationScore = min(100, max(0, $judge['calibration_score']));
        $score += $calibrationScore * self::WEIGHT_CALIBRATION;
        
        // Workload balance scoring (lower workload = higher score)
        $workloadScore = 100 - (($judge['current_workload'] / $judge['max_assignments_per_day']) * 100);
        $score += $workloadScore * self::WEIGHT_WORKLOAD;
        
        // Recent performance scoring
        $performanceScore = min(100, max(0, $judge['avg_performance_score']));
        $score += $performanceScore * 0.10; // Additional weight for performance
        
        return round($score, 2);
    }
    
    /**
     * Calculate availability score based on judge preferences and constraints
     */
    private function calculateAvailabilityScore($judge, $competitionDate)
    {
        $score = 100; // Start with perfect score
        
        // Check if judge has indicated availability for this date
        if ($judge['availability']) {
            $availability = json_decode($judge['availability'], true);
            if (isset($availability['dates']) && is_array($availability['dates'])) {
                if (!in_array($competitionDate, $availability['dates'])) {
                    $score -= 30; // Penalty for not specifically available
                }
            }
        }
        
        // Check workload capacity
        $workloadRatio = $judge['current_workload'] / $judge['max_assignments_per_day'];
        if ($workloadRatio > 0.8) {
            $score -= 20; // Penalty for high workload
        } elseif ($workloadRatio > 0.6) {
            $score -= 10; // Moderate penalty
        }
        
        // Check recency of last calibration
        $daysSinceCalibration = (time() - strtotime($judge['last_calibrated'])) / (60 * 60 * 24);
        if ($daysSinceCalibration > 180) { // 6 months
            $score -= 15;
        } elseif ($daysSinceCalibration > 90) { // 3 months
            $score -= 5;
        }
        
        return max(0, $score);
    }
    
    /**
     * Calculate overall judge score combining all factors
     */
    private function calculateOverallScore($judge)
    {
        return ($judge['suitability_score'] * 0.8) + ($judge['availability_score'] * 0.2);
    }
    
    /**
     * Generate optimal assignments using advanced algorithms
     */
    private function generateOptimalAssignments($teams, $judges, $category, $competition, $phaseId, $options)
    {
        $assignments = [];
        $judgeWorkloads = array_fill_keys(array_column($judges, 'id'), 0);
        $organizationCount = []; // Track organization diversity
        
        // Group teams into manageable chunks for panel assignment
        $teamsPerPanel = min($options['max_teams_per_judge'], count($teams));
        $teamChunks = array_chunk($teams, $teamsPerPanel);
        
        foreach ($teamChunks as $chunkIndex => $teamChunk) {
            // Select optimal judge panel for this chunk
            $panel = $this->selectOptimalPanel(
                $judges, 
                $category, 
                $judgeWorkloads, 
                $organizationCount,
                $options
            );
            
            if (count($panel) < $options['min_judges_per_category']) {
                throw new \Exception("Cannot form adequate panel for category {$category['category_name']}");
            }
            
            // Assign head judge (highest scoring available judge)
            $headJudge = $panel[0];
            
            // Create assignments for each team in this chunk
            foreach ($teamChunk as $team) {
                foreach ($panel as $panelIndex => $judge) {
                    $assignmentRole = ($panelIndex === 0) ? 'head_judge' : 'primary';
                    if ($panelIndex >= 3) $assignmentRole = 'secondary';
                    if ($panelIndex >= 5) $assignmentRole = 'backup';
                    
                    $assignments[] = [
                        'judge_id' => $judge['id'],
                        'competition_id' => $competition['id'],
                        'phase_id' => $phaseId,
                        'category_id' => $category['id'],
                        'assignment_role' => $assignmentRole,
                        'table_numbers' => json_encode([$chunkIndex + 1]),
                        'session_date' => $competition['date'],
                        'session_time' => $this->calculateSessionTime($chunkIndex),
                        'teams_assigned' => count($teamChunk),
                        'assignment_status' => 'assigned',
                        'auto_assigned' => true,
                        'suitability_score' => $judge['overall_score']
                    ];
                }
                
                // Update workloads
                foreach ($panel as $judge) {
                    $judgeWorkloads[$judge['id']]++;
                    
                    // Track organization diversity
                    $orgId = $judge['organization_id'] ?? 'independent';
                    $organizationCount[$orgId] = ($organizationCount[$orgId] ?? 0) + 1;
                }
            }
        }
        
        return $assignments;
    }
    
    /**
     * Select optimal judge panel using advanced selection criteria
     */
    private function selectOptimalPanel($judges, $category, $currentWorkloads, $organizationCount, $options)
    {
        $panel = [];
        $usedJudges = [];
        $usedOrganizations = [];
        
        // Sort judges by overall score
        $availableJudges = array_filter($judges, function($judge) use ($currentWorkloads) {
            return $currentWorkloads[$judge['id']] < $judge['max_assignments_per_day'];
        });
        
        usort($availableJudges, function($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        // Select panel members with diversity constraints
        foreach ($availableJudges as $judge) {
            if (count($panel) >= $options['min_judges_per_category'] + 2) break; // Max panel size
            
            // Skip if already selected
            if (in_array($judge['id'], $usedJudges)) continue;
            
            // Organization diversity check
            $judgeOrg = $judge['organization_id'] ?? 'independent';
            if ($options['organization_diversity'] && 
                in_array($judgeOrg, $usedOrganizations) && 
                count($usedOrganizations) > 1) {
                continue; // Skip to ensure organization diversity
            }
            
            // Experience level balance check
            if (count($panel) > 0 && $options['prefer_experienced']) {
                $experienceLevels = array_column($panel, 'experience_level');
                if (count(array_unique($experienceLevels)) < 2 && 
                    !in_array($judge['experience_level'], ['advanced', 'expert'])) {
                    continue; // Prefer experience diversity
                }
            }
            
            // Add to panel
            $panel[] = $judge;
            $usedJudges[] = $judge['id'];
            if ($judgeOrg !== 'independent') {
                $usedOrganizations[] = $judgeOrg;
            }
        }
        
        return $panel;
    }
    
    /**
     * Calculate session time based on panel index
     */
    private function calculateSessionTime($panelIndex)
    {
        $baseTime = '09:00:00';
        $sessionDuration = 90; // minutes per session
        $breakTime = self::JUDGE_BREAK_MINUTES;
        
        $totalMinutes = $panelIndex * ($sessionDuration + $breakTime);
        return date('H:i:s', strtotime($baseTime) + ($totalMinutes * 60));
    }
    
    /**
     * Save assignments to database
     */
    private function saveAssignments($assignments)
    {
        foreach ($assignments as $assignment) {
            $this->db->insert('judge_competition_assignments', array_merge($assignment, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]));
        }
    }
    
    /**
     * Create judge panels based on assignments
     */
    private function createJudgePanels($competitionId, $assignments)
    {
        $panels = [];
        
        // Group assignments by category and table
        foreach ($assignments as $assignment) {
            $key = $assignment['category_id'] . '_' . json_decode($assignment['table_numbers'])[0];
            $panels[$key][] = $assignment;
        }
        
        // Create panel records
        foreach ($panels as $panelKey => $panelAssignments) {
            list($categoryId, $tableNumber) = explode('_', $panelKey);
            
            $headJudge = null;
            $panelMembers = [];
            
            foreach ($panelAssignments as $assignment) {
                $panelMembers[] = $assignment['judge_id'];
                if ($assignment['assignment_role'] === 'head_judge') {
                    $headJudge = $assignment['judge_id'];
                }
            }
            
            if (!$headJudge) {
                $headJudge = $panelMembers[0]; // Fallback to first member
            }
            
            $this->db->insert('judge_panels', [
                'panel_name' => "Category {$categoryId} - Table {$tableNumber}",
                'competition_id' => $competitionId,
                'category_id' => $categoryId,
                'head_judge_id' => $headJudge,
                'panel_members' => json_encode($panelMembers),
                'panel_type' => 'standard',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Send notifications to assigned judges
     */
    private function notifyAssignedJudges($assignments)
    {
        $judgeAssignments = [];
        
        // Group assignments by judge
        foreach ($assignments as $assignment) {
            $judgeAssignments[$assignment['judge_id']][] = $assignment;
        }
        
        // Send notification to each judge
        foreach ($judgeAssignments as $judgeId => $judgeAssignmentList) {
            $this->sendJudgeAssignmentNotification($judgeId, $judgeAssignmentList);
        }
    }
    
    /**
     * Send assignment notification to individual judge
     */
    private function sendJudgeAssignmentNotification($judgeId, $assignments)
    {
        $judge = $this->db->query("
            SELECT jp.*, u.first_name, u.last_name, u.email
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            WHERE jp.id = ?
        ", [$judgeId]);
        
        if (empty($judge)) return;
        
        $judge = $judge[0];
        
        // Get competition details
        $competition = $this->getCompetition($assignments[0]['competition_id']);
        
        // Create assignment summary
        $assignmentSummary = [
            'judge_name' => "{$judge['first_name']} {$judge['last_name']}",
            'competition_name' => $competition['name'],
            'competition_date' => $competition['date'],
            'total_assignments' => count($assignments),
            'categories' => array_unique(array_column($assignments, 'category_id')),
            'head_judge_count' => count(array_filter($assignments, function($a) {
                return $a['assignment_role'] === 'head_judge';
            }))
        ];
        
        // Generate confirmation token
        $confirmationToken = bin2hex(random_bytes(16));
        
        // Update assignments with confirmation token
        foreach ($assignments as $assignment) {
            $this->db->query("
                UPDATE judge_competition_assignments 
                SET confirmation_token = ?
                WHERE judge_id = ? AND competition_id = ?
            ", [$confirmationToken, $judgeId, $assignment['competition_id']]);
        }
        
        // Send email notification (implementation would depend on mail service)
        $this->sendAssignmentEmail($judge['email'], $assignmentSummary, $confirmationToken);
    }
    
    /**
     * Send assignment confirmation email
     */
    private function sendAssignmentEmail($email, $summary, $confirmationToken)
    {
        // Implementation would use the Mail service
        // For now, just log the assignment
        error_log("Judge assignment notification sent to: {$email} for competition: {$summary['competition_name']}");
    }
    
    /**
     * Suggest alternative judges for failed assignments
     */
    public function suggestAlternativeJudges($competitionId, $categoryId, $excludeIds = [])
    {
        $competition = $this->getCompetition($competitionId);
        $category = $this->getCategory($categoryId);
        
        // Find judges with relevant expertise
        $alternativeJudges = $this->db->query("
            SELECT 
                jp.*,
                u.first_name, u.last_name, u.email,
                jp.experience_level,
                jp.categories_qualified,
                COALESCE(performance.avg_performance, 0) as avg_performance_score,
                COALESCE(calibration.calibration_score, 0) as calibration_score,
                COALESCE(assignments.current_load, 0) as current_workload
            FROM judge_profiles jp
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN (
                SELECT 
                    judge_id,
                    AVG((consistency_score + on_time_rate + completion_rate) / 3) as avg_performance
                FROM judge_performance_metrics
                WHERE calculated_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY judge_id
            ) performance ON jp.id = performance.judge_id
            LEFT JOIN (
                SELECT judge_id, AVG(calibration_score) as calibration_score
                FROM judge_calibrations
                WHERE valid_until > NOW()
                GROUP BY judge_id
            ) calibration ON jp.id = calibration.judge_id
            LEFT JOIN (
                SELECT judge_id, COUNT(*) as current_load
                FROM judge_competition_assignments
                WHERE session_date = ?
                AND assignment_status IN ('assigned', 'confirmed')
                GROUP BY judge_id
            ) assignments ON jp.id = assignments.judge_id
            WHERE jp.status = 'active'
            AND u.status = 'active'
            AND jp.id NOT IN (" . str_repeat('?,', count($excludeIds) - 1) . "?)
            AND (jp.categories_qualified IS NULL OR JSON_CONTAINS(jp.categories_qualified, ?))
            AND COALESCE(assignments.current_load, 0) < jp.max_assignments_per_day
            ORDER BY 
                calibration_score DESC,
                avg_performance_score DESC,
                jp.experience_level DESC
            LIMIT 10
        ", array_merge([$competition['date']], $excludeIds, [json_encode($categoryId)]));
        
        // Calculate compatibility scores
        foreach ($alternativeJudges as &$judge) {
            $judge['compatibility_score'] = $this->calculateCompatibilityScore($judge, $category, $competition);
        }
        
        // Sort by compatibility
        usort($alternativeJudges, function($a, $b) {
            return $b['compatibility_score'] <=> $a['compatibility_score'];
        });
        
        return $alternativeJudges;
    }
    
    /**
     * Calculate compatibility score for judge-category-competition combination
     */
    private function calculateCompatibilityScore($judge, $category, $competition)
    {
        $score = 0;
        
        // Base experience score
        $experienceScores = ['novice' => 60, 'intermediate' => 75, 'advanced' => 90, 'expert' => 100];
        $score += $experienceScores[$judge['experience_level']] * 0.3;
        
        // Category qualification score
        $categoryScore = 70; // Default
        if ($judge['categories_qualified']) {
            $qualified = json_decode($judge['categories_qualified'], true);
            if (in_array($category['id'], $qualified)) {
                $categoryScore = 100;
            }
        }
        $score += $categoryScore * 0.4;
        
        // Performance and calibration
        $score += $judge['avg_performance_score'] * 0.2;
        $score += $judge['calibration_score'] * 0.1;
        
        return round($score, 2);
    }
    
    /**
     * Get assignment statistics and reporting
     */
    public function getAssignmentStatistics($competitionId)
    {
        $stats = $this->db->query("
            SELECT 
                COUNT(DISTINCT jca.judge_id) as total_judges_assigned,
                COUNT(jca.id) as total_assignments,
                COUNT(DISTINCT jca.category_id) as categories_covered,
                AVG(jca.teams_assigned) as avg_teams_per_judge,
                COUNT(CASE WHEN jca.assignment_status = 'confirmed' THEN 1 END) as confirmed_assignments,
                COUNT(CASE WHEN jca.assignment_status = 'declined' THEN 1 END) as declined_assignments,
                COUNT(CASE WHEN jca.assignment_role = 'head_judge' THEN 1 END) as head_judge_assignments
            FROM judge_competition_assignments jca
            WHERE jca.competition_id = ?
        ", [$competitionId]);
        
        $judgeWorkload = $this->db->query("
            SELECT 
                u.first_name, u.last_name,
                jp.experience_level,
                o.organization_name,
                COUNT(jca.id) as assignment_count,
                jca.assignment_status,
                AVG(jp.max_assignments_per_day) as capacity
            FROM judge_competition_assignments jca
            INNER JOIN judge_profiles jp ON jca.judge_id = jp.id
            INNER JOIN users u ON jp.user_id = u.id
            LEFT JOIN organizations o ON jp.organization_id = o.id
            WHERE jca.competition_id = ?
            GROUP BY jca.judge_id
            ORDER BY assignment_count DESC
        ", [$competitionId]);
        
        return [
            'overall_stats' => $stats[0] ?? [],
            'judge_workload' => $judgeWorkload,
            'assignment_coverage' => $this->calculateAssignmentCoverage($competitionId)
        ];
    }
    
    /**
     * Calculate assignment coverage metrics
     */
    private function calculateAssignmentCoverage($competitionId)
    {
        return $this->db->query("
            SELECT 
                c.category_name,
                COUNT(DISTINCT t.id) as total_teams,
                COUNT(DISTINCT jca.judge_id) as judges_assigned,
                ROUND(COUNT(DISTINCT jca.judge_id) / COUNT(DISTINCT t.id) * 100, 2) as coverage_percentage
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id
            LEFT JOIN judge_competition_assignments jca ON c.id = jca.category_id 
                AND jca.competition_id = ?
            GROUP BY c.id, c.category_name
            ORDER BY coverage_percentage DESC
        ", [$competitionId]);
    }
    
    /**
     * Helper methods for getting data
     */
    private function getCompetition($competitionId)
    {
        $competition = $this->db->query("SELECT * FROM competitions WHERE id = ?", [$competitionId]);
        return $competition[0] ?? null;
    }
    
    private function getCategory($categoryId)
    {
        $category = $this->db->query("SELECT * FROM categories WHERE id = ?", [$categoryId]);
        return $category[0] ?? null;
    }
    
    private function getCompetitionCategories($competitionId)
    {
        return $this->db->query("
            SELECT DISTINCT c.*
            FROM categories c
            INNER JOIN teams t ON c.id = t.category_id
            INNER JOIN team_registrations tr ON t.id = tr.team_id
            WHERE tr.competition_id = ?
        ", [$competitionId]);
    }
    
    private function getTeamsForCategory($competitionId, $categoryId)
    {
        return $this->db->query("
            SELECT t.*
            FROM teams t
            INNER JOIN team_registrations tr ON t.id = tr.team_id
            WHERE tr.competition_id = ? AND t.category_id = ?
        ", [$competitionId, $categoryId]);
    }
}

class InsufficientJudgesException extends \Exception {}