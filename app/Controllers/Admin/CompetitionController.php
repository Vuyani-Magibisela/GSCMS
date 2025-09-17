<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Competition;
use App\Models\Category;
use App\Models\Phase;

class CompetitionController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Display competitions overview
     */
    public function index()
    {
        try {
            // Get all competitions with related data
            $competitions = $this->getCompetitionsWithStats();

            // Get summary statistics
            $stats = $this->getCompetitionStats();

            return $this->view('admin/competitions/index', [
                'competitions' => $competitions,
                'stats' => $stats,
                'title' => 'Competition Management',
                'pageTitle' => 'Competitions',
                'pageSubtitle' => 'Manage and monitor all competitions'
            ]);

        } catch (\Exception $e) {
            error_log("Competition index error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error loading competitions: ' . $e->getMessage());
            return $this->redirect('/admin/dashboard');
        }
    }

    /**
     * Show create competition form
     */
    public function create()
    {
        try {
            $categories = $this->getAvailableCategories();
            $phases = $this->getAvailablePhases();

            return $this->view('admin/competitions/create', [
                'categories' => $categories,
                'phases' => $phases,
                'title' => 'Create Competition',
                'pageTitle' => 'Create New Competition',
                'pageSubtitle' => 'Set up a new competition'
            ]);

        } catch (\Exception $e) {
            error_log("Competition create form error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error loading create form: ' . $e->getMessage());
            return $this->redirect('/admin/competitions');
        }
    }

    /**
     * Store new competition
     */
    public function store()
    {
        try {
            // Validate input
            $validation = $this->validateCompetitionData();
            if (!$validation['valid']) {
                $this->session->setFlash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                return $this->redirect('/admin/competitions/create');
            }

            // Create competition
            $competitionData = [
                'name' => $this->input('name'),
                'description' => $this->input('description'),
                'start_date' => $this->input('start_date'),
                'end_date' => $this->input('end_date'),
                'registration_start' => $this->input('registration_start'),
                'registration_end' => $this->input('registration_end'),
                'status' => $this->input('status', 'draft'),
                'type' => $this->input('type', 'standard'),
                'location' => $this->input('location'),
                'max_teams' => $this->input('max_teams'),
                'contact_email' => $this->input('contact_email'),
                'rules' => $this->input('rules'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $competitionId = Competition::create($competitionData);

            if ($competitionId) {
                $this->session->setFlash('success', 'Competition created successfully!');
                return $this->redirect('/admin/competitions/' . $competitionId);
            } else {
                $this->session->setFlash('error', 'Failed to create competition');
                return $this->redirect('/admin/competitions/create');
            }

        } catch (\Exception $e) {
            error_log("Competition store error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error creating competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions/create');
        }
    }

    /**
     * Show specific competition
     */
    public function show($id)
    {
        try {
            $competition = Competition::find($id);
            if (!$competition) {
                $this->session->setFlash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            // Get related data
            $competitionStats = $this->getCompetitionDetails($id);
            $teams = $this->getCompetitionTeams($id);
            $categories = $this->getCompetitionCategories($id);
            $phases = $this->getCompetitionPhases($id);

            return $this->view('admin/competitions/show', [
                'competition' => $competition,
                'stats' => $competitionStats,
                'teams' => $teams,
                'categories' => $categories,
                'phases' => $phases,
                'title' => $competition['name'],
                'pageTitle' => $competition['name'],
                'pageSubtitle' => 'Competition Details'
            ]);

        } catch (\Exception $e) {
            error_log("Competition show error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error loading competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions');
        }
    }

    /**
     * Show edit competition form
     */
    public function edit($id)
    {
        try {
            $competition = Competition::find($id);
            if (!$competition) {
                $this->session->setFlash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            $categories = $this->getAvailableCategories();
            $phases = $this->getAvailablePhases();

            return $this->view('admin/competitions/edit', [
                'competition' => $competition,
                'categories' => $categories,
                'phases' => $phases,
                'title' => 'Edit Competition',
                'pageTitle' => 'Edit Competition',
                'pageSubtitle' => $competition['name']
            ]);

        } catch (\Exception $e) {
            error_log("Competition edit form error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error loading edit form: ' . $e->getMessage());
            return $this->redirect('/admin/competitions');
        }
    }

    /**
     * Update competition
     */
    public function update($id)
    {
        try {
            $competition = Competition::find($id);
            if (!$competition) {
                $this->session->setFlash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            // Validate input
            $validation = $this->validateCompetitionData();
            if (!$validation['valid']) {
                $this->session->setFlash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                return $this->redirect('/admin/competitions/' . $id . '/edit');
            }

            // Update competition
            $competitionData = [
                'name' => $this->input('name'),
                'description' => $this->input('description'),
                'start_date' => $this->input('start_date'),
                'end_date' => $this->input('end_date'),
                'registration_start' => $this->input('registration_start'),
                'registration_end' => $this->input('registration_end'),
                'status' => $this->input('status'),
                'type' => $this->input('type'),
                'location' => $this->input('location'),
                'max_teams' => $this->input('max_teams'),
                'contact_email' => $this->input('contact_email'),
                'rules' => $this->input('rules'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = Competition::update($id, $competitionData);

            if ($updated) {
                $this->session->setFlash('success', 'Competition updated successfully!');
                return $this->redirect('/admin/competitions/' . $id);
            } else {
                $this->session->setFlash('error', 'Failed to update competition');
                return $this->redirect('/admin/competitions/' . $id . '/edit');
            }

        } catch (\Exception $e) {
            error_log("Competition update error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error updating competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions/' . $id . '/edit');
        }
    }

    /**
     * Delete competition
     */
    public function destroy($id)
    {
        try {
            $competition = Competition::find($id);
            if (!$competition) {
                $this->session->setFlash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            // Check if competition has teams registered
            $teamCount = $this->getCompetitionTeamCount($id);
            if ($teamCount > 0) {
                $this->session->setFlash('error', 'Cannot delete competition with registered teams');
                return $this->redirect('/admin/competitions/' . $id);
            }

            $deleted = Competition::delete($id);

            if ($deleted) {
                $this->session->setFlash('success', 'Competition deleted successfully!');
            } else {
                $this->session->setFlash('error', 'Failed to delete competition');
            }

            return $this->redirect('/admin/competitions');

        } catch (\Exception $e) {
            error_log("Competition delete error: " . $e->getMessage());
            $this->session->setFlash('error', 'Error deleting competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions');
        }
    }

    // Private helper methods

    private function getCompetitionsWithStats()
    {
        $query = "SELECT c.*,
                         COUNT(DISTINCT t.id) as team_count,
                         COUNT(DISTINCT s.id) as score_count,
                         COUNT(DISTINCT u.id) as participant_count
                  FROM competitions c
                  LEFT JOIN teams t ON c.id = t.competition_id
                  LEFT JOIN scores s ON c.id = s.competition_id
                  LEFT JOIN participants p ON t.id = p.team_id
                  LEFT JOIN users u ON p.user_id = u.id
                  GROUP BY c.id
                  ORDER BY c.created_at DESC";

        return $this->db->query($query);
    }

    private function getCompetitionStats()
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'draft' => 0,
            'completed' => 0
        ];

        try {
            $result = $this->db->query("
                SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                FROM competitions
            ");

            if (!empty($result)) {
                $stats = $result[0];
            }
        } catch (\Exception $e) {
            error_log("Error getting competition stats: " . $e->getMessage());
        }

        return $stats;
    }

    private function getAvailableCategories()
    {
        return $this->db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    }

    private function getAvailablePhases()
    {
        return $this->db->query("SELECT * FROM phases WHERE status = 'active' ORDER BY order_sequence");
    }

    private function getCompetitionDetails($competitionId)
    {
        try {
            $result = $this->db->query("
                SELECT
                    COUNT(DISTINCT t.id) as total_teams,
                    COUNT(DISTINCT p.id) as total_participants,
                    COUNT(DISTINCT s.id) as total_scores,
                    COUNT(DISTINCT CASE WHEN s.scoring_status = 'final' THEN s.id END) as final_scores
                FROM competitions c
                LEFT JOIN teams t ON c.id = t.competition_id
                LEFT JOIN participants p ON t.id = p.team_id
                LEFT JOIN scores s ON c.id = s.competition_id
                WHERE c.id = ?
            ", [$competitionId]);

            return $result[0] ?? [
                'total_teams' => 0,
                'total_participants' => 0,
                'total_scores' => 0,
                'final_scores' => 0
            ];
        } catch (\Exception $e) {
            error_log("Error getting competition details: " . $e->getMessage());
            return ['total_teams' => 0, 'total_participants' => 0, 'total_scores' => 0, 'final_scores' => 0];
        }
    }

    private function getCompetitionTeams($competitionId)
    {
        return $this->db->query("
            SELECT t.*, s.name as school_name, c.name as category_name
            FROM teams t
            LEFT JOIN schools s ON t.school_id = s.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.competition_id = ?
            ORDER BY t.created_at DESC
        ", [$competitionId]);
    }

    private function getCompetitionCategories($competitionId)
    {
        return $this->db->query("
            SELECT c.*, COUNT(t.id) as team_count
            FROM categories c
            LEFT JOIN teams t ON c.id = t.category_id AND t.competition_id = ?
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY c.name
        ", [$competitionId]);
    }

    private function getCompetitionPhases($competitionId)
    {
        return $this->db->query("
            SELECT p.*
            FROM phases p
            WHERE p.status = 'active'
            ORDER BY p.order_sequence
        ");
    }

    private function getCompetitionTeamCount($competitionId)
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM teams WHERE competition_id = ?", [$competitionId]);
        return $result[0]['count'] ?? 0;
    }

    private function validateCompetitionData()
    {
        $errors = [];

        // Required fields
        if (empty($this->input('name'))) {
            $errors[] = 'Competition name is required';
        }

        if (empty($this->input('start_date'))) {
            $errors[] = 'Start date is required';
        }

        if (empty($this->input('end_date'))) {
            $errors[] = 'End date is required';
        }

        // Date validation
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        if ($startDate && $endDate && strtotime($startDate) >= strtotime($endDate)) {
            $errors[] = 'End date must be after start date';
        }

        // Registration dates
        $regStart = $this->input('registration_start');
        $regEnd = $this->input('registration_end');

        if ($regStart && $regEnd && strtotime($regStart) >= strtotime($regEnd)) {
            $errors[] = 'Registration end date must be after registration start date';
        }

        // Email validation
        $email = $this->input('contact_email');
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid contact email format';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}