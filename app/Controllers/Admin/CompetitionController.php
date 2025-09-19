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
            $this->flash('error', 'Error loading competitions: ' . $e->getMessage());
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
            $this->flash('error', 'Error loading create form: ' . $e->getMessage());
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
                $this->flash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                return $this->redirect('/admin/competitions/create');
            }

            // Get default phase and category IDs
            $defaultPhase = $this->getDefaultPhase();
            $defaultCategory = $this->getDefaultCategory();

            // Create competition - match actual database schema
            $competitionData = [
                'name' => $this->input('name'),
                'year' => date('Y', strtotime($this->input('start_date'))),
                'phase_id' => $defaultPhase['id'],
                'category_id' => $defaultCategory['id'],
                'venue_name' => $this->input('location'),
                'date' => $this->input('start_date'),
                'registration_deadline' => $this->input('registration_end') ? $this->input('registration_end') . ' 23:59:59' : null,
                'max_participants' => $this->input('max_teams') ? (int)$this->input('max_teams') * 6 : null,
                'status' => $this->mapStatus($this->input('status', 'draft')),
                'contact_email' => $this->input('contact_email'),
                'competition_rules' => $this->input('rules')
            ];

            // Use direct database insertion to bypass model validation issues
            $insertResult = $this->db->statement("
                INSERT INTO competitions (name, year, phase_id, category_id, venue_name, date, registration_deadline, max_participants, status, contact_email, competition_rules, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [
                $competitionData['name'],
                $competitionData['year'],
                $competitionData['phase_id'],
                $competitionData['category_id'],
                $competitionData['venue_name'],
                $competitionData['date'],
                $competitionData['registration_deadline'],
                $competitionData['max_participants'],
                $competitionData['status'],
                $competitionData['contact_email'],
                $competitionData['competition_rules']
            ]);

            // Get the inserted competition ID
            $competitionIdResult = $this->db->query("SELECT LAST_INSERT_ID() as id");
            $competitionId = $competitionIdResult[0]['id'] ?? null;

            if ($competitionId) {
                $this->flash('success', 'Competition created successfully!');
                return $this->redirect('/admin/competitions/' . $competitionId);
            } else {
                $this->flash('error', 'Failed to create competition');
                return $this->redirect('/admin/competitions/create');
            }

        } catch (\Exception $e) {
            error_log("Competition store error: " . $e->getMessage());
            $this->flash('error', 'Error creating competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions/create');
        }
    }

    /**
     * Show specific competition
     */
    public function show($id)
    {
        try {
            // Handle if $id is a Request object (router issue)
            if (is_object($id) && method_exists($id, 'getUri')) {
                // Extract ID from URL path
                $pathParts = explode('/', trim($id->getUri(), '/'));
                $id = end($pathParts);
            }

            // Ensure ID is numeric
            $id = (int)$id;
            if ($id <= 0) {
                $this->flash('error', 'Invalid competition ID');
                return $this->redirect('/admin/competitions');
            }

            // Get competition with joined data
            $competitionResult = $this->db->query("
                SELECT c.*, p.name as phase_name, cat.name as category_name
                FROM competitions c
                LEFT JOIN phases p ON c.phase_id = p.id
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.id = ?
            ", [$id]);

            if (empty($competitionResult)) {
                $this->flash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            $competition = $competitionResult[0];

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
            $this->flash('error', 'Error loading competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions');
        }
    }

    /**
     * Show edit competition form
     */
    public function edit($id)
    {
        try {
            // Handle if $id is a Request object (router issue)
            if (is_object($id) && method_exists($id, 'getUri')) {
                $pathParts = explode('/', trim($id->getUri(), '/'));
                $id = end($pathParts);
            }
            $id = (int)$id;

            $competition = Competition::findById($id);
            if (!$competition) {
                $this->flash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            // Convert object to array for view compatibility
            $competitionArray = get_object_vars($competition);

            $categories = $this->getAvailableCategories();
            $phases = $this->getAvailablePhases();

            return $this->view('admin/competitions/edit', [
                'competition' => $competitionArray,
                'categories' => $categories,
                'phases' => $phases,
                'title' => 'Edit Competition',
                'pageTitle' => 'Edit Competition',
                'pageSubtitle' => $competitionArray['name']
            ]);

        } catch (\Exception $e) {
            error_log("Competition edit form error: " . $e->getMessage());
            $this->flash('error', 'Error loading edit form: ' . $e->getMessage());
            return $this->redirect('/admin/competitions');
        }
    }

    /**
     * Update competition
     */
    public function update($id)
    {
        try {
            // Handle if $id is a Request object (router issue)
            if (is_object($id) && method_exists($id, 'getUri')) {
                $pathParts = explode('/', trim($id->getUri(), '/'));
                $id = end($pathParts);
            }
            $id = (int)$id;

            $competition = Competition::findById($id);
            if (!$competition) {
                $this->flash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            // Validate input
            $validation = $this->validateCompetitionData();
            if (!$validation['valid']) {
                $this->flash('error', 'Validation failed: ' . implode(', ', $validation['errors']));
                return $this->redirect('/admin/competitions/' . $id . '/edit');
            }

            // Update competition - match database schema
            $competitionData = [
                'name' => $this->input('name'),
                'year' => $this->input('year'),
                'venue_name' => $this->input('venue_name'),
                'date' => $this->input('date'),
                'registration_deadline' => $this->input('registration_deadline'),
                'max_participants' => $this->input('max_participants'),
                'status' => $this->input('status'),
                'contact_email' => $this->input('contact_email'),
                'competition_rules' => $this->input('competition_rules'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Use direct database update to bypass model issues
            $updated = $this->db->statement("
                UPDATE competitions
                SET name = ?, year = ?, venue_name = ?, date = ?, registration_deadline = ?,
                    max_participants = ?, status = ?, contact_email = ?, competition_rules = ?, updated_at = NOW()
                WHERE id = ?
            ", [
                $competitionData['name'],
                $competitionData['year'],
                $competitionData['venue_name'],
                $competitionData['date'],
                $competitionData['registration_deadline'],
                $competitionData['max_participants'],
                $competitionData['status'],
                $competitionData['contact_email'],
                $competitionData['competition_rules'],
                $id
            ]);

            if ($updated) {
                $this->flash('success', 'Competition updated successfully!');
                return $this->redirect('/admin/competitions/' . $id);
            } else {
                $this->flash('error', 'Failed to update competition');
                return $this->redirect('/admin/competitions/' . $id . '/edit');
            }

        } catch (\Exception $e) {
            error_log("Competition update error: " . $e->getMessage());
            $this->flash('error', 'Error updating competition: ' . $e->getMessage());
            return $this->redirect('/admin/competitions/' . $id . '/edit');
        }
    }

    /**
     * Delete competition
     */
    public function destroy($id)
    {
        try {
            // Handle if $id is a Request object (router issue)
            if (is_object($id) && method_exists($id, 'getUri')) {
                $pathParts = explode('/', trim($id->getUri(), '/'));
                $id = end($pathParts);
            }
            $id = (int)$id;

            $competition = Competition::findById($id);
            if (!$competition) {
                $this->flash('error', 'Competition not found');
                return $this->redirect('/admin/competitions');
            }

            // Check if competition has teams registered
            $teamCount = $this->getCompetitionTeamCount($id);
            if ($teamCount > 0) {
                $this->flash('error', 'Cannot delete competition with registered teams');
                return $this->redirect('/admin/competitions/' . $id);
            }

            $deleted = Competition::delete($id);

            if ($deleted) {
                $this->flash('success', 'Competition deleted successfully!');
            } else {
                $this->flash('error', 'Failed to delete competition');
            }

            return $this->redirect('/admin/competitions');

        } catch (\Exception $e) {
            error_log("Competition delete error: " . $e->getMessage());
            $this->flash('error', 'Error deleting competition: ' . $e->getMessage());
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
        return $this->db->query("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name");
    }

    private function getAvailablePhases()
    {
        return $this->db->query("SELECT * FROM phases WHERE status = 'active' ORDER BY phase_number");
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
            WHERE c.deleted_at IS NULL
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
            ORDER BY p.phase_number
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

        if (empty($this->input('date'))) {
            $errors[] = 'Competition date is required';
        }

        // Date validation
        $competitionDate = $this->input('date');
        $regDeadline = $this->input('registration_deadline');

        if ($competitionDate && strtotime($competitionDate) < strtotime('today')) {
            $errors[] = 'Competition date cannot be in the past';
        }

        if ($regDeadline && $competitionDate && strtotime($regDeadline) > strtotime($competitionDate)) {
            $errors[] = 'Registration deadline must be before competition date';
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

    private function mapStatus($formStatus)
    {
        // Map form status values to database enum values
        $statusMap = [
            'draft' => 'planned',
            'active' => 'open_registration',
            'completed' => 'completed',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$formStatus] ?? 'planned';
    }

    private function getDefaultPhase()
    {
        $phases = $this->db->query("SELECT * FROM phases WHERE status = 'active' ORDER BY phase_number LIMIT 1");
        return !empty($phases) ? $phases[0] : ['id' => 1]; // Fallback to ID 1
    }

    private function getDefaultCategory()
    {
        $categories = $this->db->query("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name LIMIT 1");
        return !empty($categories) ? $categories[0] : ['id' => 1]; // Fallback to ID 1
    }
}