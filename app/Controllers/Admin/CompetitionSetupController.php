<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Competition;
use App\Models\Phase;
use App\Models\Category;
use App\Core\PhaseManager;
use App\Core\PilotPhaseProgression;
use App\Core\FullSystemPhaseProgression;

class CompetitionSetupController extends BaseController
{
    private $phaseManager;
    private $pilotProgression;
    private $fullProgression;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        
        $this->phaseManager = new PhaseManager();
        $this->pilotProgression = new PilotPhaseProgression();
        $this->fullProgression = new FullSystemPhaseProgression();
    }
    
    /**
     * Competition setup dashboard
     */
    public function index()
    {
        $currentCompetitionType = $this->getCompetitionType();
        $activePhases = $this->phaseManager->getActivePhases();
        $categories = (new Category())->where('status', 'active')->get();
        
        $statistics = [
            'pilot' => $this->pilotProgression->getPilotStatistics(),
            'full' => $this->fullProgression->getFullSystemStatistics()
        ];
        
        return $this->render('admin/competition-setup/index', [
            'current_type' => $currentCompetitionType,
            'active_phases' => $activePhases,
            'categories' => $categories,
            'statistics' => $statistics,
            'pilot_timeline' => $this->phaseManager->getPilotTimeline()
        ]);
    }
    
    /**
     * Configure pilot competition (2025 programme)
     */
    public function configurePilotCompetition()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                // Set competition type to pilot
                $this->setCompetitionType('pilot');
                
                // Configure pilot categories (9 categories)
                $this->setupPilotCategories();
                
                // Configure pilot phases (Phase 1 and Phase 3 only)
                $this->setupPilotPhases();
                
                // Set pilot timeline
                $this->updatePilotTimeline();
                
                $this->flash('success', 'Pilot Programme 2025 configured successfully!');
                return $this->redirect('/admin/competition-setup');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to configure pilot programme: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/competition-setup/configure-pilot', [
            'pilot_categories' => $this->getPilotCategoriesConfig(),
            'pilot_phases' => $this->getPilotPhasesConfig(),
            'pilot_timeline' => $this->phaseManager->getPilotTimeline()
        ]);
    }
    
    /**
     * Configure full competition system
     */
    public function configureFullCompetition()
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                // Set competition type to full
                $this->setCompetitionType('full');
                
                // Configure all phases (1, 2, 3)
                $this->setupFullSystemPhases();
                
                // Configure standard categories
                $this->setupStandardCategories();
                
                $this->flash('success', 'Full Competition System configured successfully!');
                return $this->redirect('/admin/competition-setup');
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to configure full system: ' . $e->getMessage());
            }
        }
        
        return $this->render('admin/competition-setup/configure-full', [
            'full_phases' => $this->getFullSystemPhasesConfig(),
            'standard_categories' => $this->getStandardCategoriesConfig()
        ]);
    }
    
    /**
     * Switch competition mode between pilot and full
     */
    public function switchCompetitionMode()
    {
        if ($this->request->getMethod() === 'POST') {
            $mode = $this->input('mode'); // 'pilot' or 'full'
            
            if (!in_array($mode, ['pilot', 'full'])) {
                $this->flash('error', 'Invalid competition mode');
                return $this->redirect('/admin/competition-setup');
            }
            
            try {
                $this->setCompetitionType($mode);
                
                // Update phase manager
                $this->phaseManager->setCompetitionType($mode);
                
                $modeLabel = $mode === 'pilot' ? 'Pilot Programme' : 'Full Competition System';
                $this->flash('success', "Competition mode switched to {$modeLabel}");
                
            } catch (\Exception $e) {
                $this->flash('error', 'Failed to switch competition mode: ' . $e->getMessage());
            }
        }
        
        return $this->redirect('/admin/competition-setup');
    }
    
    /**
     * Get current competition type from settings
     */
    private function getCompetitionType()
    {
        $setting = $this->db->table('settings')
            ->where('key', 'competition_type')
            ->first();
            
        return $setting['value'] ?? 'pilot';
    }
    
    /**
     * Set competition type in settings
     */
    private function setCompetitionType($type)
    {
        $this->db->table('settings')->updateOrInsert(
            ['key' => 'competition_type'],
            ['value' => $type, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Setup pilot programme categories (9 categories)
     */
    private function setupPilotCategories()
    {
        $pilotCategories = [
            [
                'name' => 'Junior Robotics',
                'code' => 'JUNIOR',
                'description' => 'Life on the Red Planet - Grade R-3',
                'grade_range' => 'Grade R-3',
                'hardware_requirements' => 'Cubroid, BEE Bot, etc.',
                'mission_description' => 'Move between Base#1 and Base#2 on the Red Planet',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Explorer - Cosmic Cargo',
                'code' => 'EXPLORER_COSMIC',
                'description' => 'LEGO Spike Intermediate Mission - Grade 4-7',
                'grade_range' => 'Grade 4-7',
                'hardware_requirements' => 'LEGO Spike, EV3, etc.',
                'mission_description' => 'Cosmic Cargo Challenge',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Explorer - Lost in Space',
                'code' => 'EXPLORER_LOST',
                'description' => 'LEGO Spike Advanced Mission - Grade 8-9',
                'grade_range' => 'Grade 8-9',
                'hardware_requirements' => 'LEGO Spike, EV3, etc.',
                'mission_description' => 'Lost in Space Challenge',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Arduino - Thunderdrome',
                'code' => 'ARDUINO_THUNDER',
                'description' => 'Custom Arduino Robot - Grade 8-9',
                'grade_range' => 'Grade 8-9',
                'hardware_requirements' => 'SciBOT, Arduino robots, etc.',
                'mission_description' => 'Thunderdrome Challenge',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Arduino - Mission to Yellow Planet',
                'code' => 'ARDUINO_YELLOW',
                'description' => 'Machine Learning Mission - Grade 10-11',
                'grade_range' => 'Grade 10-11',
                'hardware_requirements' => 'SciBOT, Arduino robots, etc.',
                'mission_description' => 'Mission to the Yellow Planet',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Inventor Junior',
                'code' => 'INVENTOR_JUNIOR',
                'description' => 'Young Inventors Challenge - Grade R-3',
                'grade_range' => 'Grade R-3',
                'hardware_requirements' => 'Arduino Inventor Kit, Any Robotics Kits',
                'mission_description' => 'Blue Planet Mission - Life on Earth Solutions',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Inventor Intermediate',
                'code' => 'INVENTOR_INTERMEDIATE',
                'description' => 'Intermediate Innovation Challenge - Grade 4-7',
                'grade_range' => 'Grade 4-7',
                'hardware_requirements' => 'Arduino Inventor Kit, Any self-designed robots',
                'mission_description' => 'Real World Problem Solutions',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Inventor Senior',
                'code' => 'INVENTOR_SENIOR',
                'description' => 'Advanced Innovation Challenge - Grade 8-11',
                'grade_range' => 'Grade 8-11',
                'hardware_requirements' => 'Any Robotics Kits, Self-designed robots',
                'mission_description' => 'Complex Problem Solutions with Technology',
                'max_team_size' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Special Category',
                'code' => 'SPECIAL',
                'description' => 'Special competition category for unique innovations',
                'grade_range' => 'All Grades',
                'hardware_requirements' => 'Any Technology',
                'mission_description' => 'Open Innovation Challenge',
                'max_team_size' => 4,
                'status' => 'active'
            ]
        ];
        
        foreach ($pilotCategories as $category) {
            $this->db->table('categories')->updateOrInsert(
                ['code' => $category['code']],
                array_merge($category, ['updated_at' => date('Y-m-d H:i:s')])
            );
        }
    }
    
    /**
     * Setup pilot programme phases (Phase 1 and Phase 3 only)
     */
    private function setupPilotPhases()
    {
        $pilotPhases = [
            [
                'name' => 'Phase 1 - Elimination @ Schools',
                'code' => 'PHASE_1',
                'description' => 'School-based competitions and team selection',
                'order_sequence' => 1,
                'registration_start' => '2025-08-01 00:00:00',
                'registration_end' => '2025-09-05 23:59:59',
                'competition_start' => '2025-09-12 08:00:00',
                'competition_end' => '2025-09-12 17:00:00',
                'max_teams' => 30, // 30 teams per category
                'venue_requirements' => json_encode(['type' => 'school_based']),
                'status' => 'active'
            ],
            [
                'name' => 'SciBOTICS Final @ Sci-Bono',
                'code' => 'PHASE_3',
                'description' => 'Provincial finals at Sci-Bono Discovery Centre',
                'order_sequence' => 3,
                'registration_start' => '2025-09-18 00:00:00',
                'registration_end' => '2025-09-25 23:59:59',
                'competition_start' => '2025-09-27 08:00:00',
                'competition_end' => '2025-09-27 18:00:00',
                'max_teams' => 6, // 6 teams per category
                'venue_requirements' => json_encode([
                    'type' => 'centralized',
                    'venue' => 'Sci-Bono Discovery Centre'
                ]),
                'status' => 'draft'
            ]
        ];
        
        foreach ($pilotPhases as $phase) {
            $this->db->table('phases')->updateOrInsert(
                ['code' => $phase['code']],
                array_merge($phase, ['updated_at' => date('Y-m-d H:i:s')])
            );
        }
    }
    
    /**
     * Setup full system phases (all 3 phases)
     */
    private function setupFullSystemPhases()
    {
        $fullPhases = [
            [
                'name' => 'Phase 1 - School-Based Competition',
                'code' => 'PHASE_1',
                'description' => 'Internal school competitions and team formation',
                'order_sequence' => 1,
                'max_teams' => null, // Unlimited
                'venue_requirements' => json_encode(['type' => 'school_facilities']),
                'status' => 'active'
            ],
            [
                'name' => 'Phase 2 - District/Semifinals',
                'code' => 'PHASE_2',
                'description' => 'District-level competitions with selected teams',
                'order_sequence' => 2,
                'max_teams' => 15, // 15 teams per category
                'venue_requirements' => json_encode(['type' => 'district_venues']),
                'status' => 'draft'
            ],
            [
                'name' => 'Phase 3 - Provincial Finals',
                'code' => 'PHASE_3',
                'description' => 'Provincial championship with top teams',
                'order_sequence' => 3,
                'max_teams' => 6, // 6 teams per category
                'venue_requirements' => json_encode(['type' => 'provincial_venue']),
                'status' => 'draft'
            ]
        ];
        
        foreach ($fullPhases as $phase) {
            $this->db->table('phases')->updateOrInsert(
                ['code' => $phase['code']],
                array_merge($phase, ['updated_at' => date('Y-m-d H:i:s')])
            );
        }
    }
    
    /**
     * Update pilot timeline settings
     */
    private function updatePilotTimeline()
    {
        $timeline = $this->phaseManager->getPilotTimeline();
        
        foreach ($timeline as $key => $date) {
            $this->db->table('settings')->updateOrInsert(
                ['key' => "pilot_{$key}"],
                ['value' => $date, 'updated_at' => date('Y-m-d H:i:s')]
            );
        }
    }
    
    /**
     * Get pilot categories configuration
     */
    private function getPilotCategoriesConfig()
    {
        return [
            'total_categories' => 9,
            'team_size' => 4,
            'total_capacity_phase_1' => 270, // 30 teams × 9 categories
            'total_capacity_phase_3' => 54,  // 6 teams × 9 categories
            'expected_participants_finals' => 216, // 54 teams × 4 members
            'medal_count' => 108, // 3 teams × 9 categories × 4 members
            'trophy_count' => 9   // 1 per category
        ];
    }
    
    /**
     * Get pilot phases configuration
     */
    private function getPilotPhasesConfig()
    {
        return [
            'active_phases' => [1, 3],
            'skipped_phases' => [2],
            'progression' => '1 ’ 3 (skip 2)',
            'venue_phase_1' => 'School-based',
            'venue_phase_3' => 'Sci-Bono Discovery Centre'
        ];
    }
    
    /**
     * Get standard categories configuration
     */
    private function getStandardCategoriesConfig()
    {
        return Category::getDefaultCategories();
    }
    
    /**
     * Get full system phases configuration
     */
    private function getFullSystemPhasesConfig()
    {
        return [
            'active_phases' => [1, 2, 3],
            'progression' => '1 ’ 2 ’ 3',
            'capacity' => [
                'phase_1' => 'Unlimited',
                'phase_2' => '15 teams per category',
                'phase_3' => '6 teams per category'
            ]
        ];
    }
}