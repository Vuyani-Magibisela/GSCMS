<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\BracketGenerator;
use App\Services\RoundRobinGenerator;
use App\Services\SeedingService;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\Team;
use App\Models\Category;
use App\Models\CompetitionPhase;
use App\Models\Venue;
use Exception;

/**
 * TournamentController - Admin tournament management
 * 
 * Handles CRUD operations for tournaments, bracket generation,
 * match management, and tournament administration.
 */
class TournamentController extends BaseController
{
    private $bracketGenerator;
    private $roundRobinGenerator;
    private $seedingService;
    
    public function __construct()
    {
        parent::__construct();
        $this->bracketGenerator = new BracketGenerator();
        $this->roundRobinGenerator = new RoundRobinGenerator();
        $this->seedingService = new SeedingService();
        
        // Ensure user is admin
        if (!$this->isAdmin()) {
            $this->redirect('/auth/login');
        }
    }
    
    /**
     * Display all tournaments
     */
    public function index()
    {
        try {
            $tournaments = $this->db->query("
                SELECT t.*, 
                       cp.name as phase_name,
                       c.name as category_name, c.code as category_code,
                       v.name as venue_name,
                       COUNT(ts.id) as team_count,
                       COUNT(CASE WHEN tm.match_status = 'completed' THEN 1 END) as completed_matches,
                       COUNT(tm.id) as total_matches
                FROM tournaments t
                LEFT JOIN competition_phases cp ON t.competition_phase_id = cp.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN venues v ON t.venue_id = v.id
                LEFT JOIN tournament_seedings ts ON t.id = ts.tournament_id
                LEFT JOIN tournament_matches tm ON t.id = tm.tournament_id
                GROUP BY t.id
                ORDER BY t.created_at DESC
            ");
            
            $this->render('admin/tournaments/index', [
                'tournaments' => $tournaments,
                'title' => 'Tournament Management'
            ]);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error loading tournaments: ' . $e->getMessage());
            $this->redirect('/admin/dashboard');
        }
    }
    
    /**
     * Show tournament creation form
     */
    public function create()
    {
        $phases = $this->db->table('competition_phases')->get();
        $categories = $this->db->table('categories')->where('status', 'active')->get();
        $venues = $this->db->table('venues')->get();
        
        $this->render('admin/tournaments/create', [
            'phases' => $phases,
            'categories' => $categories,
            'venues' => $venues,
            'title' => 'Create Tournament'
        ]);
    }
    
    /**
     * Store new tournament
     */
    public function store()
    {
        try {
            // Validate input
            $validation = $this->validate($_POST, [
                'tournament_name' => 'required|max:200',
                'competition_phase_id' => 'required|numeric',
                'tournament_type' => 'required',
                'category_id' => 'required|numeric',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'max_teams' => 'required|numeric|min:2',
                'advancement_count' => 'required|numeric|min:1'
            ]);
            
            if (!$validation['valid']) {
                $this->setFlash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                $this->redirect('/admin/tournaments/create');
            }
            
            // Create tournament
            $tournamentId = $this->db->table('tournaments')->insertGetId([
                'tournament_name' => $this->input('tournament_name'),
                'competition_phase_id' => $this->input('competition_phase_id'),
                'tournament_type' => $this->input('tournament_type'),
                'category_id' => $this->input('category_id'),
                'venue_id' => $this->input('venue_id') ?: null,
                'start_date' => $this->input('start_date'),
                'end_date' => $this->input('end_date'),
                'max_teams' => $this->input('max_teams'),
                'advancement_count' => $this->input('advancement_count'),
                'seeding_method' => $this->input('seeding_method', 'performance'),
                'status' => 'setup',
                'created_by' => $this->getCurrentUserId(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->setFlash('success', 'Tournament created successfully. Now set up seeding and generate bracket.');
            $this->redirect('/admin/tournaments/' . $tournamentId);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error creating tournament: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/create');
        }
    }
    
    /**
     * Show tournament details and management
     */
    public function show($id)
    {
        try {
            $tournament = $this->getTournamentWithDetails($id);
            
            if (!$tournament) {
                $this->setFlash('error', 'Tournament not found');
                $this->redirect('/admin/tournaments');
            }
            
            // Get tournament statistics
            $stats = $this->getTournamentStats($id);
            
            // Get recent matches
            $recentMatches = $this->db->query("
                SELECT tm.*, 
                       t1.name as team1_name, t1.team_code as team1_code,
                       t2.name as team2_name, t2.team_code as team2_code,
                       w.name as winner_name,
                       tb.round_name
                FROM tournament_matches tm
                LEFT JOIN teams t1 ON tm.team1_id = t1.id
                LEFT JOIN teams t2 ON tm.team2_id = t2.id
                LEFT JOIN teams w ON tm.winner_team_id = w.id
                LEFT JOIN tournament_brackets tb ON tm.bracket_id = tb.id
                WHERE tm.tournament_id = ?
                ORDER BY tm.updated_at DESC
                LIMIT 10
            ", [$id]);
            
            $this->render('admin/tournaments/show', [
                'tournament' => $tournament,
                'stats' => $stats,
                'recentMatches' => $recentMatches,
                'title' => 'Tournament: ' . $tournament['tournament_name']
            ]);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error loading tournament: ' . $e->getMessage());
            $this->redirect('/admin/tournaments');
        }
    }
    
    /**
     * Generate tournament seeding
     */
    public function generateSeeding($id)
    {
        try {
            $tournament = $this->getTournament($id);
            
            if (!$tournament || $tournament['status'] !== 'setup') {
                $this->setFlash('error', 'Tournament not found or not in setup status');
                $this->redirect('/admin/tournaments/' . $id);
            }
            
            $seeding = $this->seedingService->calculateSeeding($id);
            
            // Update tournament status
            $this->db->table('tournaments')
                ->where('id', $id)
                ->update([
                    'status' => 'seeding',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            $this->setFlash('success', 'Seeding generated for ' . count($seeding) . ' teams');
            $this->redirect('/admin/tournaments/' . $id . '/seeding');
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error generating seeding: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/' . $id);
        }
    }
    
    /**
     * Show and manage seeding
     */
    public function seeding($id)
    {
        try {
            $tournament = $this->getTournamentWithDetails($id);
            
            $seeding = $this->db->query("
                SELECT ts.*, 
                       t.name as team_name, t.team_code,
                       s.name as school_name
                FROM tournament_seedings ts
                JOIN teams t ON ts.team_id = t.id
                JOIN schools s ON t.school_id = s.id
                WHERE ts.tournament_id = ?
                ORDER BY ts.seed_number ASC
            ", [$id]);
            
            $this->render('admin/tournaments/seeding', [
                'tournament' => $tournament,
                'seeding' => $seeding,
                'title' => 'Tournament Seeding: ' . $tournament['tournament_name']
            ]);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error loading seeding: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/' . $id);
        }
    }
    
    /**
     * Update seeding (manual adjustments)
     */
    public function updateSeeding($id)
    {
        try {
            $adjustments = $this->input('adjustments', []);
            
            if (empty($adjustments)) {
                $this->setFlash('error', 'No seeding adjustments provided');
                $this->redirect('/admin/tournaments/' . $id . '/seeding');
            }
            
            $this->seedingService->applyManualSeeding($id, $adjustments);
            
            $this->setFlash('success', 'Seeding updated successfully');
            $this->redirect('/admin/tournaments/' . $id . '/seeding');
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error updating seeding: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/' . $id . '/seeding');
        }
    }
    
    /**
     * Generate tournament bracket
     */
    public function generateBracket($id)
    {
        try {
            $tournament = $this->getTournament($id);
            
            if (!$tournament || $tournament['status'] !== 'seeding') {
                $this->setFlash('error', 'Tournament must be in seeding status to generate bracket');
                $this->redirect('/admin/tournaments/' . $id);
            }
            
            if ($tournament['tournament_type'] === 'round_robin') {
                $result = $this->roundRobinGenerator->generateRoundRobin($id);
                $this->setFlash('success', 'Round-robin schedule generated: ' . $result['total_matches'] . ' matches');
            } else {
                $result = $this->bracketGenerator->generateEliminationBracket($id);
                $this->setFlash('success', 'Elimination bracket generated');
            }
            
            $this->redirect('/admin/tournaments/' . $id . '/bracket');
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error generating bracket: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/' . $id);
        }
    }
    
    /**
     * Show tournament bracket
     */
    public function bracket($id)
    {
        try {
            $tournament = $this->getTournamentWithDetails($id);
            $bracketData = $this->getBracketData($id);
            
            $this->render('admin/tournaments/bracket', [
                'tournament' => $tournament,
                'bracketData' => $bracketData,
                'title' => 'Tournament Bracket: ' . $tournament['tournament_name']
            ]);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error loading bracket: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/' . $id);
        }
    }
    
    /**
     * Update match score
     */
    public function updateMatch($matchId)
    {
        header('Content-Type: application/json');
        
        try {
            $match = $this->db->table('tournament_matches')->find($matchId);
            
            if (!$match) {
                echo json_encode(['error' => 'Match not found']);
                exit;
            }
            
            $team1Score = floatval($this->input('team1_score'));
            $team2Score = floatval($this->input('team2_score'));
            $forfeit = $this->input('forfeit') === 'true';
            $forfeitReason = $this->input('forfeit_reason');
            
            // Determine winner
            $winnerId = null;
            $loserId = null;
            
            if ($forfeit) {
                $winnerId = $this->input('forfeit_winner_id');
                $loserId = ($winnerId == $match['team1_id']) ? $match['team2_id'] : $match['team1_id'];
            } elseif ($team1Score > $team2Score) {
                $winnerId = $match['team1_id'];
                $loserId = $match['team2_id'];
            } elseif ($team2Score > $team1Score) {
                $winnerId = $match['team2_id'];
                $loserId = $match['team1_id'];
            }
            
            // Update match
            $this->db->table('tournament_matches')
                ->where('id', $matchId)
                ->update([
                    'team1_score' => $team1Score,
                    'team2_score' => $team2Score,
                    'winner_team_id' => $winnerId,
                    'loser_team_id' => $loserId,
                    'match_status' => $forfeit ? 'forfeit' : 'completed',
                    'forfeit_reason' => $forfeitReason,
                    'actual_end_time' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            // Advance winner if elimination tournament
            if ($match['next_match_id'] && $winnerId) {
                $this->advanceWinner($match['next_match_id'], $winnerId);
            }
            
            // Update round-robin standings if applicable
            if ($this->isRoundRobinMatch($match['tournament_id'])) {
                $this->roundRobinGenerator->updateStandings($matchId);
            }
            
            echo json_encode([
                'success' => true,
                'match' => $this->getMatchDetails($matchId)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Get tournament results
     */
    public function results($id)
    {
        try {
            $tournament = $this->getTournamentWithDetails($id);
            
            $results = $this->db->query("
                SELECT tr.*, 
                       t.name as team_name, t.team_code,
                       s.name as school_name
                FROM tournament_results tr
                JOIN teams t ON tr.team_id = t.id
                JOIN schools s ON t.school_id = s.id
                WHERE tr.tournament_id = ?
                ORDER BY tr.placement ASC
            ", [$id]);
            
            $this->render('admin/tournaments/results', [
                'tournament' => $tournament,
                'results' => $results,
                'title' => 'Tournament Results: ' . $tournament['tournament_name']
            ]);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Error loading results: ' . $e->getMessage());
            $this->redirect('/admin/tournaments/' . $id);
        }
    }
    
    // Helper methods
    
    private function getTournament($id)
    {
        return $this->db->table('tournaments')->find($id);
    }
    
    private function getTournamentWithDetails($id)
    {
        return $this->db->query("
            SELECT t.*, 
                   cp.name as phase_name,
                   c.name as category_name, c.code as category_code,
                   v.name as venue_name, v.address as venue_address,
                   u.first_name as created_by_name, u.last_name as created_by_surname
            FROM tournaments t
            LEFT JOIN competition_phases cp ON t.competition_phase_id = cp.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN venues v ON t.venue_id = v.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = ?
        ", [$id])[0] ?? null;
    }
    
    private function getTournamentStats($id)
    {
        return $this->db->query("
            SELECT 
                COUNT(DISTINCT ts.team_id) as total_teams,
                COUNT(tm.id) as total_matches,
                COUNT(CASE WHEN tm.match_status = 'completed' THEN 1 END) as completed_matches,
                COUNT(CASE WHEN tm.match_status = 'ready' THEN 1 END) as ready_matches,
                COUNT(CASE WHEN tm.match_status = 'pending' THEN 1 END) as pending_matches,
                MAX(tb.round_number) as total_rounds
            FROM tournaments t
            LEFT JOIN tournament_seedings ts ON t.id = ts.tournament_id
            LEFT JOIN tournament_matches tm ON t.id = tm.tournament_id
            LEFT JOIN tournament_brackets tb ON t.id = tb.tournament_id
            WHERE t.id = ?
        ", [$id])[0];
    }
    
    private function getBracketData($id)
    {
        $brackets = $this->db->query("
            SELECT * FROM tournament_brackets 
            WHERE tournament_id = ? 
            ORDER BY round_number
        ", [$id]);
        
        $matches = $this->db->query("
            SELECT tm.*, 
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   w.name as winner_name,
                   tb.round_name, tb.round_number
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            LEFT JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            WHERE tm.tournament_id = ?
            ORDER BY tb.round_number, tm.match_number
        ", [$id]);
        
        return [
            'brackets' => $brackets,
            'matches' => $matches
        ];
    }
    
    private function advanceWinner($nextMatchId, $winnerId)
    {
        $nextMatch = $this->db->table('tournament_matches')->find($nextMatchId);
        
        if (!$nextMatch['team1_id']) {
            $this->db->table('tournament_matches')
                ->where('id', $nextMatchId)
                ->update([
                    'team1_id' => $winnerId,
                    'match_status' => $nextMatch['team2_id'] ? 'ready' : 'pending'
                ]);
        } elseif (!$nextMatch['team2_id']) {
            $this->db->table('tournament_matches')
                ->where('id', $nextMatchId)
                ->update([
                    'team2_id' => $winnerId,
                    'match_status' => 'ready'
                ]);
        }
    }
    
    private function isRoundRobinMatch($tournamentId)
    {
        $tournament = $this->getTournament($tournamentId);
        return $tournament && $tournament['tournament_type'] === 'round_robin';
    }
    
    private function getMatchDetails($matchId)
    {
        return $this->db->query("
            SELECT tm.*, 
                   t1.name as team1_name, t1.team_code as team1_code,
                   t2.name as team2_name, t2.team_code as team2_code,
                   w.name as winner_name,
                   tb.round_name
            FROM tournament_matches tm
            LEFT JOIN teams t1 ON tm.team1_id = t1.id
            LEFT JOIN teams t2 ON tm.team2_id = t2.id
            LEFT JOIN teams w ON tm.winner_team_id = w.id
            LEFT JOIN tournament_brackets tb ON tm.bracket_id = tb.id
            WHERE tm.id = ?
        ", [$matchId])[0] ?? null;
    }
}