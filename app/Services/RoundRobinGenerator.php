<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\RoundRobinStanding;
use App\Models\RoundRobinSchedule;
use App\Models\TournamentMatch;
use Exception;

/**
 * RoundRobinGenerator Service
 * 
 * Handles generation of round-robin tournaments for GDE SciBOTICS 2025 finals:
 * - Finals (Sci-Bono): 6 teams/category → Top 3 winners
 * - All teams play each other once for maximum fairness
 */
class RoundRobinGenerator
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate round-robin tournament schedule
     * 
     * @param int $tournamentId
     * @return array
     * @throws Exception
     */
    public function generateRoundRobin($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        $teams = $this->getTeams($tournamentId);
        $teamCount = count($teams);
        
        if ($teamCount < 2) {
            throw new Exception("Round-robin tournament requires at least 2 teams");
        }
        
        // Special handling for finals: 6 teams in round-robin
        if ($tournament['competition_phase_id'] == 3) { // Finals phase
            return $this->generateFinalsRoundRobin($tournamentId, $teams);
        }
        
        // Standard round-robin algorithm using Berger tables
        $schedule = $this->bergerTables($teams);
        
        // Save schedule to database
        $this->saveSchedule($tournamentId, $schedule);
        
        // Initialize standings
        $this->initializeStandings($tournamentId, $teams);
        
        // Update tournament status
        $this->updateTournamentStatus($tournamentId, 'active');
        
        return [
            'tournament_id' => $tournamentId,
            'schedule' => $schedule,
            'total_rounds' => count($schedule),
            'matches_per_round' => $this->getMatchesPerRound($teamCount),
            'total_matches' => $this->getTotalMatches($teamCount)
        ];
    }
    
    /**
     * Generate finals round-robin for 6 teams
     */
    private function generateFinalsRoundRobin($tournamentId, $teams)
    {
        if (count($teams) != 6) {
            throw new Exception("Finals round-robin requires exactly 6 teams");
        }
        
        // 6 teams play each other once: 6 * 5 / 2 = 15 matches total
        // Organized into 5 rounds with 3 matches each
        
        $schedule = [];
        $teamIds = array_column($teams, 'id');
        
        // Use circular algorithm for balanced scheduling
        $rounds = $this->generateCircularSchedule($teamIds);
        
        // Save to database
        foreach ($rounds as $roundNumber => $roundMatches) {
            foreach ($roundMatches as $matchIndex => $match) {
                // Create match record
                $matchId = $this->createMatch(
                    $tournamentId,
                    null, // No bracket for round-robin
                    $match['home'],
                    $match['away'],
                    $matchIndex,
                    $roundNumber
                );
                
                // Create schedule record
                $this->createScheduleRecord(
                    $tournamentId,
                    $roundNumber,
                    $match['home'],
                    $match['away'],
                    $matchId
                );
            }
        }
        
        return [
            'tournament_id' => $tournamentId,
            'format' => 'finals_round_robin',
            'teams' => count($teams),
            'total_rounds' => count($rounds),
            'matches_per_round' => 3,
            'total_matches' => 15,
            'schedule' => $rounds
        ];
    }
    
    /**
     * Generate circular schedule for round-robin (optimal for even number of teams)
     */
    private function generateCircularSchedule($teamIds)
    {
        $n = count($teamIds);
        
        if ($n % 2 != 0) {
            // Add bye for odd number of teams
            $teamIds[] = null;
            $n++;
        }
        
        $rounds = [];
        $rounds_total = $n - 1;
        
        for ($round = 0; $round < $rounds_total; $round++) {
            $roundMatches = [];
            
            for ($match = 0; $match < $n / 2; $match++) {
                $home = ($round + $match) % ($n - 1);
                $away = ($n - 1 - $match + $round) % ($n - 1);
                
                // Fixed position for team at index (n-1)
                if ($match == 0) {
                    $away = $n - 1;
                }
                
                // Skip bye matches
                if ($teamIds[$home] !== null && $teamIds[$away] !== null) {
                    $roundMatches[] = [
                        'home' => $teamIds[$home],
                        'away' => $teamIds[$away]
                    ];
                }
            }
            
            if (!empty($roundMatches)) {
                $rounds[$round + 1] = $roundMatches;
            }
        }
        
        return $rounds;
    }
    
    /**
     * Standard Berger tables algorithm for round-robin scheduling
     */
    private function bergerTables($teams)
    {
        $n = count($teams);
        $rounds = $n - 1;
        
        if ($n % 2 != 0) {
            $teams[] = ['id' => null, 'name' => 'BYE'];
            $n++;
            $rounds = $n;
        }
        
        $schedule = [];
        
        for ($round = 0; $round < $rounds; $round++) {
            $roundMatches = [];
            
            for ($match = 0; $match < $n / 2; $match++) {
                $home = ($round + $match) % ($n - 1);
                $away = ($n - 1 - $match + $round) % ($n - 1);
                
                if ($match == 0) {
                    $away = $n - 1;
                }
                
                // Skip bye matches
                if ($teams[$home]['id'] !== null && $teams[$away]['id'] !== null) {
                    $roundMatches[] = [
                        'round' => $round + 1,
                        'match' => $match + 1,
                        'home' => $teams[$home],
                        'away' => $teams[$away]
                    ];
                }
            }
            
            if (!empty($roundMatches)) {
                $schedule[] = $roundMatches;
            }
        }
        
        return $schedule;
    }
    
    /**
     * Save schedule to database
     */
    private function saveSchedule($tournamentId, $schedule)
    {
        foreach ($schedule as $roundIndex => $roundMatches) {
            $roundNumber = $roundIndex + 1;
            
            foreach ($roundMatches as $match) {
                // Create match record
                $matchId = $this->createMatch(
                    $tournamentId,
                    null, // No bracket for round-robin
                    $match['home']['id'],
                    $match['away']['id'],
                    $match['match'],
                    $roundNumber
                );
                
                // Create schedule record
                $this->createScheduleRecord(
                    $tournamentId,
                    $roundNumber,
                    $match['home']['id'],
                    $match['away']['id'],
                    $matchId
                );
            }
        }
        
        // Update tournament rounds total
        $this->updateTournamentRounds($tournamentId, count($schedule));
    }
    
    /**
     * Initialize standings table for all teams
     */
    private function initializeStandings($tournamentId, $teams)
    {
        foreach ($teams as $team) {
            $this->db->table('round_robin_standings')->insert([
                'tournament_id' => $tournamentId,
                'team_id' => $team['id'],
                'matches_played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points_for' => 0.00,
                'points_against' => 0.00,
                'league_points' => 0,
                'ranking' => null,
                'qualified' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Update standings after a match is completed
     */
    public function updateStandings($matchId)
    {
        $match = $this->getMatch($matchId);
        
        if (!$match || $match['match_status'] !== 'completed') {
            throw new Exception("Match not found or not completed");
        }
        
        // Determine match result
        $winner = null;
        $loser = null;
        $isDraw = false;
        
        if ($match['team1_score'] > $match['team2_score']) {
            $winner = $match['team1_id'];
            $loser = $match['team2_id'];
        } elseif ($match['team2_score'] > $match['team1_score']) {
            $winner = $match['team2_id'];
            $loser = $match['team1_id'];
        } else {
            $isDraw = true;
        }
        
        // Update team 1 standing
        $this->updateTeamStanding($match['tournament_id'], $match['team1_id'], [
            'played' => 1,
            'won' => $winner == $match['team1_id'] ? 1 : 0,
            'drawn' => $isDraw ? 1 : 0,
            'lost' => $loser == $match['team1_id'] ? 1 : 0,
            'for' => $match['team1_score'],
            'against' => $match['team2_score'],
            'points' => $winner == $match['team1_id'] ? 3 : ($isDraw ? 1 : 0)
        ]);
        
        // Update team 2 standing
        $this->updateTeamStanding($match['tournament_id'], $match['team2_id'], [
            'played' => 1,
            'won' => $winner == $match['team2_id'] ? 1 : 0,
            'drawn' => $isDraw ? 1 : 0,
            'lost' => $loser == $match['team2_id'] ? 1 : 0,
            'for' => $match['team2_score'],
            'against' => $match['team1_score'],
            'points' => $winner == $match['team2_id'] ? 3 : ($isDraw ? 1 : 0)
        ]);
        
        // Update head-to-head record
        $this->updateHeadToHead($match['tournament_id'], $match['team1_id'], $match['team2_id'], $match);
        
        // Recalculate rankings
        $this->recalculateRankings($match['tournament_id']);
        
        // Check if tournament is completed
        $this->checkTournamentCompletion($match['tournament_id']);
        
        return true;
    }
    
    /**
     * Update individual team standing
     */
    private function updateTeamStanding($tournamentId, $teamId, $stats)
    {
        $current = $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $teamId)
            ->first();
            
        if (!$current) {
            throw new Exception("Standing not found for team {$teamId}");
        }
        
        return $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $teamId)
            ->update([
                'matches_played' => $current['matches_played'] + $stats['played'],
                'wins' => $current['wins'] + $stats['won'],
                'draws' => $current['draws'] + $stats['drawn'],
                'losses' => $current['losses'] + $stats['lost'],
                'points_for' => $current['points_for'] + $stats['for'],
                'points_against' => $current['points_against'] + $stats['against'],
                'league_points' => $current['league_points'] + $stats['points'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Update head-to-head records for tiebreaking
     */
    private function updateHeadToHead($tournamentId, $team1Id, $team2Id, $match)
    {
        // Get current head-to-head data
        $team1Standing = $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $team1Id)
            ->first();
            
        $team2Standing = $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $team2Id)
            ->first();
        
        // Parse existing head-to-head data
        $team1H2H = json_decode($team1Standing['head_to_head'] ?? '{}', true);
        $team2H2H = json_decode($team2Standing['head_to_head'] ?? '{}', true);
        
        // Add this match result
        $team1H2H[$team2Id] = [
            'scored' => $match['team1_score'],
            'conceded' => $match['team2_score'],
            'result' => $match['team1_score'] > $match['team2_score'] ? 'W' : 
                       ($match['team1_score'] < $match['team2_score'] ? 'L' : 'D')
        ];
        
        $team2H2H[$team1Id] = [
            'scored' => $match['team2_score'],
            'conceded' => $match['team1_score'],
            'result' => $match['team2_score'] > $match['team1_score'] ? 'W' : 
                       ($match['team2_score'] < $match['team1_score'] ? 'L' : 'D')
        ];
        
        // Update database
        $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $team1Id)
            ->update(['head_to_head' => json_encode($team1H2H)]);
            
        $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $team2Id)
            ->update(['head_to_head' => json_encode($team2H2H)]);
    }
    
    /**
     * Recalculate rankings using proper tiebreaking rules
     */
    private function recalculateRankings($tournamentId)
    {
        $standings = $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->get();
        
        // Sort by: Points, Goal Difference, Goals For, Head-to-Head
        usort($standings, function($a, $b) {
            // First by league points
            if ($a['league_points'] != $b['league_points']) {
                return $b['league_points'] - $a['league_points'];
            }
            
            // Then by point differential
            $aDiff = $a['points_for'] - $a['points_against'];
            $bDiff = $b['points_for'] - $b['points_against'];
            
            if ($aDiff != $bDiff) {
                return $bDiff - $aDiff;
            }
            
            // Then by points for
            if ($a['points_for'] != $b['points_for']) {
                return $b['points_for'] - $a['points_for'];
            }
            
            // Finally by head-to-head
            return $this->compareHeadToHead($a, $b);
        });
        
        // Update rankings and qualification status
        foreach ($standings as $rank => $team) {
            $isQualified = $rank < 3; // Top 3 qualify
            
            $this->db->table('round_robin_standings')
                ->where('id', $team['id'])
                ->update([
                    'ranking' => $rank + 1,
                    'qualified' => $isQualified,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }
    }
    
    /**
     * Compare head-to-head record for tiebreaking
     */
    private function compareHeadToHead($a, $b)
    {
        $aH2H = json_decode($a['head_to_head'] ?? '{}', true);
        $bH2H = json_decode($b['head_to_head'] ?? '{}', true);
        
        // Check if teams have played each other
        if (isset($aH2H[$b['team_id']]) && isset($bH2H[$a['team_id']])) {
            $aResult = $aH2H[$b['team_id']]['result'];
            $bResult = $bH2H[$a['team_id']]['result'];
            
            if ($aResult == 'W' && $bResult == 'L') return -1;
            if ($aResult == 'L' && $bResult == 'W') return 1;
            
            // If draw or haven't played, compare goal difference in H2H
            $aH2HDiff = ($aH2H[$b['team_id']]['scored'] ?? 0) - ($aH2H[$b['team_id']]['conceded'] ?? 0);
            $bH2HDiff = ($bH2H[$a['team_id']]['scored'] ?? 0) - ($bH2H[$a['team_id']]['conceded'] ?? 0);
            
            return $bH2HDiff - $aH2HDiff;
        }
        
        return 0; // Equal if no head-to-head available
    }
    
    /**
     * Check if tournament is completed
     */
    private function checkTournamentCompletion($tournamentId)
    {
        $incompleteMatches = $this->db->table('tournament_matches')
            ->where('tournament_id', $tournamentId)
            ->whereNotIn('match_status', ['completed', 'bye'])
            ->count();
        
        if ($incompleteMatches == 0) {
            $this->updateTournamentStatus($tournamentId, 'completed');
            
            // Generate final results
            $this->generateFinalResults($tournamentId);
        }
    }
    
    /**
     * Generate final results from standings
     */
    private function generateFinalResults($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        $standings = $this->db->table('round_robin_standings')
            ->where('tournament_id', $tournamentId)
            ->orderBy('ranking')
            ->get();
        
        foreach ($standings as $standing) {
            $medalType = match($standing['ranking']) {
                1 => 'gold',
                2 => 'silver',
                3 => 'bronze',
                default => 'none'
            };
            
            $this->db->table('tournament_results')->insert([
                'tournament_id' => $tournamentId,
                'category_id' => $tournament['category_id'],
                'placement' => $standing['ranking'],
                'team_id' => $standing['team_id'],
                'team_score' => $standing['league_points'],
                'medal_type' => $medalType,
                'published' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Get current standings
     */
    public function getStandings($tournamentId)
    {
        return $this->db->query("
            SELECT rrs.*, 
                   t.name as team_name, t.team_code,
                   s.name as school_name
            FROM round_robin_standings rrs
            JOIN teams t ON rrs.team_id = t.id
            JOIN schools s ON t.school_id = s.id
            WHERE rrs.tournament_id = ?
            ORDER BY rrs.ranking ASC, rrs.league_points DESC, 
                     (rrs.points_for - rrs.points_against) DESC
        ", [$tournamentId]);
    }
    
    /**
     * Get schedule for tournament
     */
    public function getSchedule($tournamentId)
    {
        return $this->db->query("
            SELECT rrs.*,
                   tm.team1_score, tm.team2_score, tm.match_status,
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code
            FROM round_robin_schedule rrs
            JOIN teams t1 ON rrs.team1_id = t1.id
            JOIN teams t2 ON rrs.team2_id = t2.id
            LEFT JOIN tournament_matches tm ON rrs.match_id = tm.id
            WHERE rrs.tournament_id = ?
            ORDER BY rrs.round_number, rrs.id
        ", [$tournamentId]);
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
    
    private function getTeams($tournamentId)
    {
        return $this->db->query("
            SELECT t.*, ts.seed_number
            FROM teams t
            JOIN tournament_seedings ts ON t.id = ts.team_id
            WHERE ts.tournament_id = ?
            ORDER BY ts.seed_number ASC
        ", [$tournamentId]);
    }
    
    private function getMatch($matchId)
    {
        return $this->db->table('tournament_matches')->find($matchId);
    }
    
    private function createMatch($tournamentId, $bracketId, $team1Id, $team2Id, $matchNumber, $roundNumber = null)
    {
        return $this->db->table('tournament_matches')->insertGetId([
            'tournament_id' => $tournamentId,
            'bracket_id' => $bracketId,
            'match_number' => $matchNumber,
            'match_position' => $matchNumber,
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'match_status' => 'ready',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function createScheduleRecord($tournamentId, $roundNumber, $team1Id, $team2Id, $matchId)
    {
        return $this->db->table('round_robin_schedule')->insert([
            'tournament_id' => $tournamentId,
            'round_number' => $roundNumber,
            'match_day' => date('Y-m-d'), // To be scheduled later
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'match_id' => $matchId,
            'is_played' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function updateTournamentStatus($tournamentId, $status)
    {
        return $this->db->table('tournaments')
            ->where('id', $tournamentId)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    private function updateTournamentRounds($tournamentId, $totalRounds)
    {
        return $this->db->table('tournaments')
            ->where('id', $tournamentId)
            ->update([
                'rounds_total' => $totalRounds,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    private function getMatchesPerRound($teamCount)
    {
        return $teamCount % 2 == 0 ? $teamCount / 2 : ($teamCount - 1) / 2;
    }
    
    private function getTotalMatches($teamCount)
    {
        return $teamCount * ($teamCount - 1) / 2;
    }
}