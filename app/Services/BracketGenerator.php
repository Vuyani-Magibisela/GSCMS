<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentBracket;
use App\Models\TournamentMatch;
use App\Models\TournamentSeeding;
use Exception;

/**
 * BracketGenerator Service
 * 
 * Handles generation of elimination tournament brackets according to GDE SciBOTICS 2025 competition structure:
 * - Phase 1 (School Elimination): 30 teams/category → 6 finalists
 * - Finals (Sci-Bono): 6 teams/category → Top 3 winners
 */
class BracketGenerator
{
    private $db;
    
    // Competition structure constants based on GDE SciBOTICS 2025
    const PHASE1_CONFIG = [
        'teams_per_category' => 30,
        'advancement_count' => 6,
        'format' => 'elimination_with_repechage' // Allows second chance
    ];
    
    const FINALS_CONFIG = [
        'teams_per_category' => 6,
        'advancement_count' => 3,
        'format' => 'round_robin' // All play all for fairness
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate elimination bracket for tournament
     * 
     * @param int $tournamentId
     * @return array
     * @throws Exception
     */
    public function generateEliminationBracket($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        $teams = $this->getSeededTeams($tournamentId);
        $teamCount = count($teams);
        
        if ($teamCount < 2) {
            throw new Exception("Tournament must have at least 2 teams");
        }
        
        // Calculate number of rounds needed
        $roundsConfig = $this->calculateRounds($teamCount, $tournament['advancement_count']);
        
        // Handle byes for non-power-of-2 team counts
        $bracketSize = $this->getNextPowerOfTwo($teamCount);
        $byes = $bracketSize - $teamCount;
        
        // Create bracket structure
        $bracket = $this->createBracketStructure($tournament, $roundsConfig);
        
        // Place teams with proper seeding
        $this->placeTeamsInBracket($bracket, $teams, $byes);
        
        // Update tournament status
        $this->updateTournamentStatus($tournamentId, 'active');
        
        return $bracket;
    }
    
    /**
     * Calculate rounds configuration based on team count and advancement requirements
     */
    private function calculateRounds($teamCount, $advancementCount)
    {
        // Special handling for GDE SciBOTICS structure: 30 teams to 6 teams
        if ($teamCount == 30 && $advancementCount == 6) {
            return [
                'rounds' => 4,
                'structure' => [
                    1 => ['matches' => 14, 'description' => 'First Round (28 teams play, 2 get byes)'],
                    2 => ['matches' => 8, 'description' => 'Second Round (16 teams)'],
                    3 => ['matches' => 4, 'description' => 'Quarter-Finals (8 teams)'],
                    4 => ['matches' => 2, 'description' => 'Semi-Finals (4 teams + 2 lucky losers = 6 finalists)']
                ],
                'special_handling' => 'repechage_for_finals'
            ];
        }
        
        // Calculate standard elimination rounds
        $rounds = [];
        $currentTeams = $teamCount;
        $roundNum = 1;
        
        while ($currentTeams > $advancementCount) {
            $matches = floor($currentTeams / 2);
            $rounds[$roundNum] = [
                'matches' => $matches,
                'description' => $this->getRoundName($roundNum, $currentTeams)
            ];
            $currentTeams = $matches + ($currentTeams % 2); // Handle odd numbers
            $roundNum++;
        }
        
        return [
            'rounds' => count($rounds),
            'structure' => $rounds,
            'special_handling' => 'standard_elimination'
        ];
    }
    
    /**
     * Get round name based on round number and team count
     */
    private function getRoundName($roundNum, $teamCount)
    {
        if ($teamCount <= 4) return 'Finals';
        if ($teamCount <= 8) return 'Semi-Finals';
        if ($teamCount <= 16) return 'Quarter-Finals';
        if ($teamCount <= 32) return 'Round of 16';
        if ($teamCount <= 64) return 'Round of 32';
        return "Round $roundNum";
    }
    
    /**
     * Create bracket structure in database
     */
    private function createBracketStructure($tournament, $roundsConfig)
    {
        $bracket = [
            'tournament_id' => $tournament['id'],
            'rounds' => [],
            'config' => $roundsConfig
        ];
        
        foreach ($roundsConfig['structure'] as $roundNum => $roundData) {
            // Create tournament bracket record
            $bracketId = $this->db->table('tournament_brackets')->insertGetId([
                'tournament_id' => $tournament['id'],
                'bracket_type' => 'winners',
                'round_number' => $roundNum,
                'round_name' => $roundData['description'],
                'matches_in_round' => $roundData['matches'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $bracket['rounds'][$roundNum] = [
                'bracket_id' => $bracketId,
                'matches' => [],
                'config' => $roundData
            ];
        }
        
        return $bracket;
    }
    
    /**
     * Place teams in bracket with proper seeding
     */
    private function placeTeamsInBracket($bracket, $teams, $byes)
    {
        $seeds = $this->generateSeeding(count($teams));
        $tournamentId = $bracket['tournament_id'];
        
        // Create first round matches
        $firstRound = $bracket['rounds'][1];
        $matchNumber = 0;
        
        for ($i = 0; $i < count($seeds); $i += 2) {
            if ($i + 1 >= count($seeds)) break;
            
            $seed1 = $seeds[$i];
            $seed2 = $seeds[$i + 1];
            $team1 = $teams[$seed1 - 1] ?? null;
            $team2 = $teams[$seed2 - 1] ?? null;
            
            // Handle byes
            if (!$team2 || $byes > 0) {
                $this->createByeMatch($firstRound['bracket_id'], $team1, $matchNumber++);
                $byes--;
            } else {
                $this->createMatch(
                    $firstRound['bracket_id'],
                    $tournamentId,
                    $team1,
                    $team2,
                    $matchNumber++,
                    $seed1,
                    $seed2
                );
            }
        }
        
        // Create subsequent round placeholders
        for ($round = 2; $round <= count($bracket['rounds']); $round++) {
            $roundData = $bracket['rounds'][$round];
            for ($match = 0; $match < $roundData['config']['matches']; $match++) {
                $this->createPlaceholderMatch($roundData['bracket_id'], $tournamentId, $match);
            }
        }
    }
    
    /**
     * Generate seeding order for balanced bracket
     */
    private function generateSeeding($teamCount)
    {
        $bracketSize = $this->getNextPowerOfTwo($teamCount);
        $seeds = [1];
        
        while (count($seeds) < $bracketSize) {
            $newSeeds = [];
            foreach ($seeds as $seed) {
                $newSeeds[] = $seed;
                $newSeeds[] = count($seeds) * 2 + 1 - $seed;
            }
            $seeds = $newSeeds;
        }
        
        // Trim to actual team count
        return array_slice($seeds, 0, $teamCount);
    }
    
    /**
     * Create tournament match record
     */
    private function createMatch($bracketId, $tournamentId, $team1, $team2, $matchNumber, $seed1 = null, $seed2 = null)
    {
        return $this->db->table('tournament_matches')->insertGetId([
            'tournament_id' => $tournamentId,
            'bracket_id' => $bracketId,
            'match_number' => $matchNumber,
            'match_position' => $matchNumber,
            'team1_id' => $team1['id'] ?? null,
            'team2_id' => $team2['id'] ?? null,
            'team1_seed' => $seed1,
            'team2_seed' => $seed2,
            'match_status' => ($team1 && $team2) ? 'ready' : 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Create bye match (team advances automatically)
     */
    private function createByeMatch($bracketId, $team, $matchNumber)
    {
        return $this->db->table('tournament_matches')->insertGetId([
            'tournament_id' => $team['tournament_id'] ?? null,
            'bracket_id' => $bracketId,
            'match_number' => $matchNumber,
            'match_position' => $matchNumber,
            'team1_id' => $team['id'],
            'team2_id' => null,
            'team1_seed' => $team['seed_number'] ?? null,
            'winner_team_id' => $team['id'],
            'match_status' => 'bye',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Create placeholder match for future rounds
     */
    private function createPlaceholderMatch($bracketId, $tournamentId, $matchNumber)
    {
        return $this->db->table('tournament_matches')->insertGetId([
            'tournament_id' => $tournamentId,
            'bracket_id' => $bracketId,
            'match_number' => $matchNumber,
            'match_position' => $matchNumber,
            'match_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Generate school elimination bracket (30 teams → 6 teams)
     */
    public function generateSchoolEliminationBracket($schoolId, $categoryId)
    {
        $teams = $this->getSchoolTeams($schoolId, $categoryId);
        
        if (count($teams) <= 6) {
            // Direct advancement if 6 or fewer teams
            return $this->directAdvancement($teams);
        }
        
        // Create tournament record
        $tournamentId = $this->createTournament([
            'tournament_name' => "School Elimination - Category {$categoryId}",
            'competition_phase_id' => 1, // Phase 1 elimination
            'tournament_type' => 'elimination',
            'category_id' => $categoryId,
            'max_teams' => count($teams),
            'current_teams' => count($teams),
            'advancement_count' => 6,
            'status' => 'setup',
            'created_by' => 1 // System generated
        ]);
        
        // Generate seeding
        $seedingService = new SeedingService();
        $seedingService->calculateSeeding($tournamentId);
        
        // Generate bracket
        return $this->generateEliminationBracket($tournamentId);
    }
    
    /**
     * Advance match winner and update bracket progression
     */
    public function advanceWinner($matchId, $winnerId, $team1Score = null, $team2Score = null)
    {
        $match = $this->getMatch($matchId);
        
        if (!$match) {
            throw new Exception("Match not found");
        }
        
        // Update match result
        $this->db->table('tournament_matches')
            ->where('id', $matchId)
            ->update([
                'winner_team_id' => $winnerId,
                'loser_team_id' => ($winnerId == $match['team1_id']) ? $match['team2_id'] : $match['team1_id'],
                'team1_score' => $team1Score,
                'team2_score' => $team2Score,
                'match_status' => 'completed',
                'actual_end_time' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        
        // Advance winner to next round
        if ($match['next_match_id']) {
            $this->placeTeamInNextMatch($match['next_match_id'], $winnerId);
        }
        
        // Update tournament progress
        $this->checkTournamentCompletion($match['tournament_id']);
        
        return true;
    }
    
    /**
     * Place team in next match
     */
    private function placeTeamInNextMatch($nextMatchId, $teamId)
    {
        $nextMatch = $this->getMatch($nextMatchId);
        
        if (!$nextMatch['team1_id']) {
            $this->db->table('tournament_matches')
                ->where('id', $nextMatchId)
                ->update(['team1_id' => $teamId, 'match_status' => 'ready']);
        } elseif (!$nextMatch['team2_id']) {
            $this->db->table('tournament_matches')
                ->where('id', $nextMatchId)
                ->update(['team2_id' => $teamId, 'match_status' => 'ready']);
        }
    }
    
    /**
     * Check if tournament is complete
     */
    private function checkTournamentCompletion($tournamentId)
    {
        $incompleteMatches = $this->db->table('tournament_matches')
            ->where('tournament_id', $tournamentId)
            ->whereIn('match_status', ['pending', 'ready', 'in_progress'])
            ->count();
        
        if ($incompleteMatches == 0) {
            $this->updateTournamentStatus($tournamentId, 'completed');
            
            // Generate final results
            $resultsService = new ResultsPublisher();
            $resultsService->generateTournamentResults($tournamentId);
        }
    }
    
    /**
     * Get next power of 2 for bracket size
     */
    private function getNextPowerOfTwo($n)
    {
        return pow(2, ceil(log($n, 2)));
    }
    
    /**
     * Get tournament details
     */
    private function getTournament($tournamentId)
    {
        $tournament = $this->db->table('tournaments')->find($tournamentId);
        
        if (!$tournament) {
            throw new Exception("Tournament not found");
        }
        
        return $tournament;
    }
    
    /**
     * Get seeded teams for tournament
     */
    private function getSeededTeams($tournamentId)
    {
        return $this->db->query("
            SELECT t.*, ts.seed_number, ts.seeding_score
            FROM teams t
            JOIN tournament_seedings ts ON t.id = ts.team_id
            WHERE ts.tournament_id = ?
            ORDER BY ts.seed_number ASC
        ", [$tournamentId]);
    }
    
    /**
     * Get teams by school and category
     */
    private function getSchoolTeams($schoolId, $categoryId)
    {
        return $this->db->query("
            SELECT t.*, s.name as school_name, c.name as category_name
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            WHERE t.school_id = ? AND t.category_id = ?
            AND t.deleted_at IS NULL
            ORDER BY t.name
        ", [$schoolId, $categoryId]);
    }
    
    /**
     * Get match details
     */
    private function getMatch($matchId)
    {
        return $this->db->table('tournament_matches')->find($matchId);
    }
    
    /**
     * Create tournament record
     */
    private function createTournament($data)
    {
        return $this->db->table('tournaments')->insertGetId(array_merge($data, [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]));
    }
    
    /**
     * Update tournament status
     */
    private function updateTournamentStatus($tournamentId, $status)
    {
        return $this->db->table('tournaments')
            ->where('id', $tournamentId)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Handle direct advancement for small team counts
     */
    private function directAdvancement($teams)
    {
        return [
            'type' => 'direct_advancement',
            'teams' => $teams,
            'advancement_count' => count($teams),
            'message' => 'All teams advance directly due to small field size'
        ];
    }
    
    /**
     * Get bracket visualization data
     */
    public function getBracketData($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        
        $brackets = $this->db->query("
            SELECT * FROM tournament_brackets 
            WHERE tournament_id = ? 
            ORDER BY round_number
        ", [$tournamentId]);
        
        $matches = $this->db->query("
            SELECT tm.*, 
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   w.name as winner_name, w.team_code as winner_code,
                   tb.round_name
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            LEFT JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            WHERE tm.tournament_id = ?
            ORDER BY tb.round_number, tm.match_number
        ", [$tournamentId]);
        
        return [
            'tournament' => $tournament,
            'brackets' => $brackets,
            'matches' => $matches,
            'status' => $tournament['status']
        ];
    }
}