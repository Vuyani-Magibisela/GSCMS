<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentSeeding;
use Exception;

/**
 * SeedingService Class
 * 
 * Handles intelligent seeding based on performance for GDE SciBOTICS 2025 tournaments.
 * Uses multiple factors including previous performance, consistency, and ELO ratings.
 */
class SeedingService
{
    private $db;
    
    // Weighting factors for seeding calculation
    private $weights = [
        'previous_score' => 0.4,      // 40% - Previous phase/competition performance
        'consistency' => 0.2,         // 20% - Performance consistency
        'strength_of_schedule' => 0.2, // 20% - Quality of opponents faced
        'recent_form' => 0.2          // 20% - Recent match performance
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Calculate seeding for tournament
     * 
     * @param int $tournamentId
     * @return array
     * @throws Exception
     */
    public function calculateSeeding($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        $teams = $this->getEligibleTeams($tournament);
        
        if (empty($teams)) {
            throw new Exception("No eligible teams found for tournament");
        }
        
        $seedingData = [];
        
        foreach ($teams as $team) {
            $seedingData[] = [
                'team_id' => $team['id'],
                'team_name' => $team['name'],
                'school_name' => $team['school_name'],
                'seeding_score' => $this->calculateSeedingScore($team, $tournament),
                'previous_rank' => $this->getPreviousPhaseRank($team, $tournament),
                'elo_rating' => $this->calculateEloRating($team),
                'district_rank' => $this->getDistrictRank($team),
                'performance_metrics' => $this->getPerformanceMetrics($team)
            ];
        }
        
        // Sort by seeding score (highest first)
        usort($seedingData, function($a, $b) {
            return $b['seeding_score'] <=> $a['seeding_score'];
        });
        
        // Assign seed numbers
        foreach ($seedingData as $index => &$seed) {
            $seed['seed_number'] = $index + 1;
        }
        
        // Save seeding data to database
        $this->saveSeedingData($tournamentId, $seedingData);
        
        return $seedingData;
    }
    
    /**
     * Calculate comprehensive seeding score for a team
     */
    private function calculateSeedingScore($team, $tournament)
    {
        $score = 0;
        $components = [];
        
        // Component 1: Previous phase performance (40%)
        if ($tournament['competition_phase_id'] > 1) {
            $previousScore = $this->getPreviousPhaseScore($team, $tournament);
            $weightedPrevious = $previousScore * $this->weights['previous_score'];
            $score += $weightedPrevious;
            $components['previous_score'] = [
                'raw' => $previousScore,
                'weighted' => $weightedPrevious
            ];
        }
        
        // Component 2: Consistency score (20%)
        $consistency = $this->calculateConsistency($team);
        $weightedConsistency = $consistency * $this->weights['consistency'];
        $score += $weightedConsistency;
        $components['consistency'] = [
            'raw' => $consistency,
            'weighted' => $weightedConsistency
        ];
        
        // Component 3: Strength of schedule (20%)
        $sos = $this->calculateStrengthOfSchedule($team);
        $weightedSos = $sos * $this->weights['strength_of_schedule'];
        $score += $weightedSos;
        $components['strength_of_schedule'] = [
            'raw' => $sos,
            'weighted' => $weightedSos
        ];
        
        // Component 4: Recent form (20%)
        $recentForm = $this->calculateRecentForm($team);
        $weightedForm = $recentForm * $this->weights['recent_form'];
        $score += $weightedForm;
        $components['recent_form'] = [
            'raw' => $recentForm,
            'weighted' => $weightedForm
        ];
        
        // Store breakdown for transparency
        $team['seeding_breakdown'] = $components;
        
        return round($score, 2);
    }
    
    /**
     * Get previous phase performance score (0-100)
     */
    private function getPreviousPhaseScore($team, $tournament)
    {
        // Look for scores from previous phase
        $previousPhaseId = $tournament['competition_phase_id'] - 1;
        
        $previousScores = $this->db->query("
            SELECT AVG(total_score) as avg_score, COUNT(*) as score_count
            FROM scores s
            WHERE s.team_id = ?
            AND s.phase_id = ?
        ", [$team['id'], $previousPhaseId]);
        
        if (empty($previousScores) || !$previousScores[0]['avg_score']) {
            // No previous phase data, use qualification score or default
            return $team['qualification_score'] ?? 50; // Default middle rating
        }
        
        // Normalize to 0-100 scale (assuming max score is around 100)
        $avgScore = $previousScores[0]['avg_score'];
        return min(100, max(0, $avgScore));
    }
    
    /**
     * Calculate performance consistency (0-100)
     */
    private function calculateConsistency($team)
    {
        $scores = $this->db->query("
            SELECT total_score
            FROM scores
            WHERE team_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ", [$team['id']]);
        
        if (count($scores) < 2) {
            return 50; // Default if insufficient data
        }
        
        $scoreValues = array_column($scores, 'total_score');
        $mean = array_sum($scoreValues) / count($scoreValues);
        
        // Calculate standard deviation
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $scoreValues)) / count($scoreValues);
        
        $stdDev = sqrt($variance);
        
        // Convert to consistency score (lower std dev = higher consistency)
        // Normalize so that std dev of 0 = 100, std dev of 20+ = 0
        $consistency = max(0, 100 - ($stdDev * 5));
        
        return round($consistency, 2);
    }
    
    /**
     * Calculate strength of schedule (0-100)
     */
    private function calculateStrengthOfSchedule($team)
    {
        // Get opponents this team has faced
        $opponents = $this->db->query("
            SELECT DISTINCT 
                CASE 
                    WHEN tm.team1_id = ? THEN tm.team2_id
                    ELSE tm.team1_id
                END as opponent_id
            FROM tournament_matches tm
            WHERE (tm.team1_id = ? OR tm.team2_id = ?)
            AND tm.match_status = 'completed'
        ", [$team['id'], $team['id'], $team['id']]);
        
        if (empty($opponents)) {
            return 50; // Default if no match history
        }
        
        $opponentIds = array_column($opponents, 'opponent_id');
        $placeholders = str_repeat('?,', count($opponentIds) - 1) . '?';
        
        // Get average performance of opponents
        $opponentStrength = $this->db->query("
            SELECT AVG(s.total_score) as avg_opponent_score
            FROM scores s
            WHERE s.team_id IN ({$placeholders})
        ", $opponentIds);
        
        $avgOpponentScore = $opponentStrength[0]['avg_opponent_score'] ?? 50;
        
        // Normalize to 0-100 scale
        return min(100, max(0, $avgOpponentScore));
    }
    
    /**
     * Calculate recent form (0-100)
     */
    private function calculateRecentForm($team)
    {
        // Get last 3 matches
        $recentMatches = $this->db->query("
            SELECT tm.*, 
                   CASE 
                       WHEN tm.winner_team_id = ? THEN 'W'
                       WHEN tm.team1_score = tm.team2_score THEN 'D'
                       ELSE 'L'
                   END as result,
                   CASE
                       WHEN tm.team1_id = ? THEN tm.team1_score
                       ELSE tm.team2_score
                   END as team_score,
                   CASE
                       WHEN tm.team1_id = ? THEN tm.team2_score
                       ELSE tm.team1_score
                   END as opponent_score
            FROM tournament_matches tm
            WHERE (tm.team1_id = ? OR tm.team2_id = ?)
            AND tm.match_status = 'completed'
            ORDER BY tm.updated_at DESC
            LIMIT 3
        ", [$team['id'], $team['id'], $team['id'], $team['id'], $team['id']]);
        
        if (empty($recentMatches)) {
            return 50; // Default if no recent matches
        }
        
        $formScore = 0;
        $totalWeight = 0;
        
        // Weight recent matches more heavily
        $weights = [1.0, 0.7, 0.5]; // Most recent gets highest weight
        
        foreach ($recentMatches as $index => $match) {
            $weight = $weights[$index] ?? 0.3;
            $totalWeight += $weight;
            
            // Score based on result and performance
            $matchScore = 0;
            
            switch ($match['result']) {
                case 'W':
                    $matchScore = 100;
                    break;
                case 'D':
                    $matchScore = 50;
                    break;
                case 'L':
                    $matchScore = 20; // Still get some points for playing
                    break;
            }
            
            // Adjust based on score differential
            if ($match['team_score'] && $match['opponent_score']) {
                $differential = $match['team_score'] - $match['opponent_score'];
                $matchScore += min(20, max(-20, $differential * 0.5));
            }
            
            $formScore += $matchScore * $weight;
        }
        
        return round($formScore / $totalWeight, 2);
    }
    
    /**
     * Calculate ELO rating for team
     */
    public function calculateEloRating($team)
    {
        $elo = 1200; // Starting ELO
        $matches = $this->getTeamMatches($team['id']);
        
        foreach ($matches as $match) {
            $opponent = $this->getOpponent($match, $team['id']);
            $opponentElo = $this->getTeamElo($opponent['id']) ?? 1200;
            
            // Calculate expected score
            $expected = 1 / (1 + pow(10, ($opponentElo - $elo) / 400));
            
            // Actual score (1 for win, 0.5 for draw, 0 for loss)
            $actual = $this->getMatchResult($match, $team['id']);
            
            // Update ELO
            $k = $this->getKFactor($elo, $matches); // Adaptive K-factor
            $elo += $k * ($actual - $expected);
        }
        
        return round($elo);
    }
    
    /**
     * Get adaptive K-factor based on rating and experience
     */
    private function getKFactor($elo, $matches)
    {
        $matchCount = count($matches);
        
        // Higher K for new teams, lower for experienced teams
        if ($matchCount < 5) return 40;
        if ($matchCount < 15) return 32;
        if ($elo > 2000) return 16;
        return 24;
    }
    
    /**
     * Get match result for specific team (1 = win, 0.5 = draw, 0 = loss)
     */
    private function getMatchResult($match, $teamId)
    {
        if ($match['winner_team_id'] == $teamId) {
            return 1.0;
        } elseif ($match['winner_team_id'] === null) {
            return 0.5; // Draw
        }
        return 0.0;
    }
    
    /**
     * Apply manual seeding adjustments
     */
    public function applyManualSeeding($tournamentId, $adjustments)
    {
        foreach ($adjustments as $adjustment) {
            $this->db->table('tournament_seedings')
                ->where('tournament_id', $tournamentId)
                ->where('team_id', $adjustment['team_id'])
                ->update([
                    'seed_number' => $adjustment['new_seed'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }
        
        // Recalculate dependent systems if needed
        return true;
    }
    
    /**
     * Simulate tournament outcomes based on seeding
     */
    public function simulateBracket($tournamentId, $simulations = 1000)
    {
        $seeding = $this->getSeeding($tournamentId);
        $results = [];
        
        for ($i = 0; $i < $simulations; $i++) {
            $bracket = $this->simulateTournament($seeding);
            
            foreach ($bracket['results'] as $teamId => $placement) {
                if (!isset($results[$teamId])) {
                    $results[$teamId] = [
                        'wins' => 0,
                        'finals' => 0,
                        'top3' => 0,
                        'simulations' => 0
                    ];
                }
                
                $results[$teamId]['simulations']++;
                
                if ($placement == 1) $results[$teamId]['wins']++;
                if ($placement <= 2) $results[$teamId]['finals']++;
                if ($placement <= 3) $results[$teamId]['top3']++;
            }
        }
        
        // Calculate probabilities
        foreach ($results as $teamId => &$teamResult) {
            $sims = $teamResult['simulations'];
            $teamResult['win_probability'] = ($teamResult['wins'] / $sims) * 100;
            $teamResult['finals_probability'] = ($teamResult['finals'] / $sims) * 100;
            $teamResult['top3_probability'] = ($teamResult['top3'] / $sims) * 100;
        }
        
        return $results;
    }
    
    /**
     * Simulate a single tournament
     */
    private function simulateTournament($seeding)
    {
        // Simplified simulation based on ELO differences
        $teams = [];
        foreach ($seeding as $seed) {
            $teams[] = [
                'id' => $seed['team_id'],
                'elo' => $seed['elo_rating'],
                'seed' => $seed['seed_number']
            ];
        }
        
        // Simulate elimination rounds
        while (count($teams) > 1) {
            $nextRound = [];
            
            for ($i = 0; $i < count($teams); $i += 2) {
                if (!isset($teams[$i + 1])) {
                    $nextRound[] = $teams[$i]; // Bye
                    continue;
                }
                
                $team1 = $teams[$i];
                $team2 = $teams[$i + 1];
                
                // Calculate win probability
                $expected1 = 1 / (1 + pow(10, ($team2['elo'] - $team1['elo']) / 400));
                $winner = (mt_rand() / mt_getrandmax()) < $expected1 ? $team1 : $team2;
                
                $nextRound[] = $winner;
            }
            
            $teams = $nextRound;
        }
        
        return [
            'results' => [
                $teams[0]['id'] => 1 // Winner
            ]
        ];
    }
    
    /**
     * Get district ranking for team
     */
    private function getDistrictRank($team)
    {
        $rank = $this->db->query("
            SELECT COUNT(*) + 1 as rank
            FROM teams t1
            LEFT JOIN scores s1 ON t1.id = s1.team_id
            JOIN schools sc1 ON t1.school_id = sc1.id
            JOIN schools sc2 ON ? = sc2.id
            WHERE sc1.district = sc2.district
            AND t1.category_id = ?
            AND (SELECT AVG(s2.total_score) FROM scores s2 WHERE s2.team_id = t1.id) > 
                (SELECT AVG(s3.total_score) FROM scores s3 WHERE s3.team_id = ?)
        ", [$team['school_id'], $team['category_id'], $team['id']]);
        
        return $rank[0]['rank'] ?? null;
    }
    
    // Helper methods
    private function getTournament($tournamentId)
    {
        $tournament = $this->db->table('tournaments')->find($tournamentId);
        if (!$tournament) {
            throw new Exception("Tournament not found");
        }
        return $tournament;
    }
    
    private function getEligibleTeams($tournament)
    {
        return $this->db->query("
            SELECT t.*, s.name as school_name, s.district, c.name as category_name
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE t.category_id = ? 
            AND t.status IN ('approved', 'competing')
            AND t.deleted_at IS NULL
            ORDER BY t.name
        ", [$tournament['category_id']]);
    }
    
    private function getTeamMatches($teamId)
    {
        return $this->db->query("
            SELECT tm.*
            FROM tournament_matches tm
            WHERE (tm.team1_id = ? OR tm.team2_id = ?)
            AND tm.match_status = 'completed'
            ORDER BY tm.updated_at DESC
        ", [$teamId, $teamId]);
    }
    
    private function getOpponent($match, $teamId)
    {
        $opponentId = ($match['team1_id'] == $teamId) ? $match['team2_id'] : $match['team1_id'];
        return $this->db->table('teams')->find($opponentId);
    }
    
    private function getTeamElo($teamId)
    {
        $seeding = $this->db->table('tournament_seedings')
            ->where('team_id', $teamId)
            ->orderBy('updated_at', 'DESC')
            ->first();
            
        return $seeding['elo_rating'] ?? null;
    }
    
    private function getSeeding($tournamentId)
    {
        return $this->db->query("
            SELECT ts.*, t.name as team_name
            FROM tournament_seedings ts
            JOIN teams t ON ts.team_id = t.id
            WHERE ts.tournament_id = ?
            ORDER BY ts.seed_number ASC
        ", [$tournamentId]);
    }
    
    private function getPreviousPhaseRank($team, $tournament)
    {
        return $this->db->query("
            SELECT placement
            FROM tournament_results tr
            JOIN tournaments tour ON tr.tournament_id = tour.id
            WHERE tr.team_id = ?
            AND tour.competition_phase_id = ?
            AND tour.category_id = ?
            ORDER BY tr.created_at DESC
            LIMIT 1
        ", [$team['id'], $tournament['competition_phase_id'] - 1, $tournament['category_id']])[0]['placement'] ?? null;
    }
    
    private function getPerformanceMetrics($team)
    {
        $metrics = $this->db->query("
            SELECT 
                COUNT(*) as matches_played,
                COUNT(CASE WHEN winner_team_id = ? THEN 1 END) as matches_won,
                AVG(CASE WHEN team1_id = ? THEN team1_score ELSE team2_score END) as avg_score
            FROM tournament_matches
            WHERE (team1_id = ? OR team2_id = ?)
            AND match_status = 'completed'
        ", [$team['id'], $team['id'], $team['id'], $team['id']]);
        
        return $metrics[0] ?? [
            'matches_played' => 0,
            'matches_won' => 0,
            'avg_score' => 0
        ];
    }
    
    private function saveSeedingData($tournamentId, $seedingData)
    {
        // Clear existing seedings
        $this->db->table('tournament_seedings')
            ->where('tournament_id', $tournamentId)
            ->delete();
        
        // Insert new seeding data
        foreach ($seedingData as $data) {
            $this->db->table('tournament_seedings')->insert([
                'tournament_id' => $tournamentId,
                'team_id' => $data['team_id'],
                'seed_number' => $data['seed_number'],
                'seeding_score' => $data['seeding_score'],
                'previous_phase_rank' => $data['previous_rank'],
                'district_rank' => $data['district_rank'],
                'elo_rating' => $data['elo_rating'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}