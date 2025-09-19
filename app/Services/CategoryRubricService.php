<?php
// app/Services/CategoryRubricService.php

namespace App\Services;

use App\Models\Category;
use App\Models\RubricTemplate;
use App\Core\Database;

class CategoryRubricService
{
    private $db;
    
    // Category-specific rubric configurations
    const CATEGORY_RUBRICS = [
        'JUNIOR' => [
            'interface_type' => 'visual',
            'scoring_method' => 'emoji',
            'max_score' => 100,
            'sections' => [
                'robot_performance' => [
                    'weight' => 40,
                    'display' => 'emoji_scale',
                    'levels' => [
                        1 => ['emoji' => '😟', 'text' => 'Needs Practice', 'points' => 10],
                        2 => ['emoji' => '😐', 'text' => 'Getting There', 'points' => 25],
                        3 => ['emoji' => '😊', 'text' => 'Good Job', 'points' => 35],
                        4 => ['emoji' => '🌟', 'text' => 'Amazing!', 'points' => 40]
                    ]
                ],
                'teamwork' => [
                    'weight' => 30,
                    'display' => 'star_rating',
                    'levels' => [
                        1 => ['stars' => 1, 'text' => 'Individual Work', 'points' => 5],
                        2 => ['stars' => 2, 'text' => 'Some Cooperation', 'points' => 15],
                        3 => ['stars' => 3, 'text' => 'Good Teamwork', 'points' => 25],
                        4 => ['stars' => 4, 'text' => 'Excellent Teamwork', 'points' => 30]
                    ]
                ],
                'creativity' => [
                    'weight' => 30,
                    'display' => 'visual_cards',
                    'levels' => [
                        1 => ['image' => 'basic.png', 'text' => 'Basic Solution', 'points' => 5],
                        2 => ['image' => 'creative.png', 'text' => 'Creative Ideas', 'points' => 15],
                        3 => ['image' => 'innovative.png', 'text' => 'Very Innovative', 'points' => 25],
                        4 => ['image' => 'exceptional.png', 'text' => 'Exceptional Creativity', 'points' => 30]
                    ]
                ]
            ]
        ],
        'SPIKE_INTERMEDIATE' => [
            'interface_type' => 'standard',
            'scoring_method' => 'points',
            'max_score' => 100,
            'sections' => [
                'programming' => [
                    'weight' => 35,
                    'sub_criteria' => [
                        'code_structure' => ['max_points' => 10, 'description' => 'Code organization and readability'],
                        'algorithm_efficiency' => ['max_points' => 15, 'description' => 'Logic and efficiency of solution'],
                        'error_handling' => ['max_points' => 10, 'description' => 'Error handling and robustness']
                    ]
                ],
                'mission_completion' => [
                    'weight' => 35,
                    'checkpoints' => [
                        'start_position' => ['points' => 5, 'description' => 'Robot starts in correct position'],
                        'navigation' => ['points' => 10, 'description' => 'Robot navigates course successfully'],
                        'task_execution' => ['points' => 15, 'description' => 'Completes required tasks'],
                        'return_home' => ['points' => 5, 'description' => 'Returns to starting position']
                    ]
                ],
                'innovation' => [
                    'weight' => 30,
                    'max_points' => 30,
                    'description' => 'Creative problem-solving and unique approaches'
                ]
            ]
        ],
        'ARDUINO' => [
            'interface_type' => 'technical',
            'scoring_method' => 'detailed',
            'max_score' => 100,
            'sections' => [
                'technical_implementation' => [
                    'weight' => 40,
                    'metrics' => [
                        'complexity_score' => [
                            'type' => 'slider',
                            'min' => 0,
                            'max' => 15,
                            'step' => 1,
                            'description' => 'Technical complexity and sophistication'
                        ],
                        'optimization_level' => [
                            'type' => 'slider',
                            'min' => 0,
                            'max' => 15,
                            'step' => 1,
                            'description' => 'Code optimization and efficiency'
                        ],
                        'documentation_quality' => [
                            'type' => 'slider',
                            'min' => 0,
                            'max' => 10,
                            'step' => 1,
                            'description' => 'Code documentation and comments'
                        ]
                    ]
                ],
                'hardware_integration' => [
                    'weight' => 30,
                    'max_points' => 30,
                    'checkpoints' => [
                        'sensor_usage' => ['points' => 10, 'description' => 'Effective use of sensors'],
                        'actuator_control' => ['points' => 10, 'description' => 'Precise actuator control'],
                        'system_integration' => ['points' => 10, 'description' => 'Overall system integration']
                    ]
                ],
                'performance_metrics' => [
                    'weight' => 30,
                    'time_bonus' => [
                        'enabled' => true,
                        'max_bonus' => 10,
                        'target_time' => 300, // 5 minutes
                        'formula' => 'linear_decay'
                    ],
                    'accuracy_score' => [
                        'max_points' => 20,
                        'measurement_type' => 'percentage'
                    ]
                ]
            ]
        ],
        'INVENTOR' => [
            'interface_type' => 'comprehensive',
            'scoring_method' => 'hybrid',
            'max_score' => 100,
            'sections' => [
                'innovation' => [
                    'weight' => 30,
                    'display' => 'detailed_rubric',
                    'criteria' => [
                        'novelty' => ['max_points' => 10],
                        'feasibility' => ['max_points' => 10],
                        'impact' => ['max_points' => 10]
                    ]
                ],
                'technical_execution' => [
                    'weight' => 35,
                    'display' => 'technical_assessment'
                ],
                'presentation' => [
                    'weight' => 35,
                    'display' => 'presentation_rubric'
                ]
            ]
        ]
    ];

    // Presentation judging rubric configurations
    const PRESENTATION_RUBRICS = [
        'ALL_CATEGORIES' => [
            'interface_type' => 'presentation',
            'scoring_method' => 'presentation_detailed',
            'max_score' => 75, // Total presentation score
            'time_limit_minutes' => 10,
            'sections' => [
                'problem_research' => [
                    'name' => 'Problem & Solution Research',
                    'weight' => 25,
                    'max_points' => 25,
                    'display' => 'detailed_rubric',
                    'levels' => [
                        1 => [
                            'level_name' => 'Basic Research',
                            'points' => 6,
                            'description' => 'Limited understanding of problem. Basic research with minimal depth.',
                            'indicators' => [
                                'Shows basic awareness of problem',
                                'Limited research sources',
                                'Surface-level understanding'
                            ]
                        ],
                        2 => [
                            'level_name' => 'Developing Research',
                            'points' => 12,
                            'description' => 'Good understanding of problem. Research shows some depth and multiple sources.',
                            'indicators' => [
                                'Clear problem identification',
                                'Multiple research sources used',
                                'Good understanding demonstrated'
                            ]
                        ],
                        3 => [
                            'level_name' => 'Proficient Research',
                            'points' => 19,
                            'description' => 'Thorough understanding of problem. Comprehensive research with analysis.',
                            'indicators' => [
                                'Detailed problem analysis',
                                'Diverse, credible sources',
                                'Evidence of critical thinking'
                            ]
                        ],
                        4 => [
                            'level_name' => 'Advanced Research',
                            'points' => 25,
                            'description' => 'Exceptional understanding with innovative insights. Expert-level research depth.',
                            'indicators' => [
                                'Complex problem analysis',
                                'Expert sources and interviews',
                                'Original insights and connections'
                            ]
                        ]
                    ]
                ],
                'robot_presentation' => [
                    'name' => 'Robot Presentation',
                    'weight' => 25,
                    'max_points' => 25,
                    'display' => 'detailed_rubric',
                    'levels' => [
                        1 => [
                            'level_name' => 'Basic Demonstration',
                            'points' => 6,
                            'description' => 'Robot functions partially. Basic explanation of features.',
                            'indicators' => [
                                'Robot performs some functions',
                                'Basic feature explanation',
                                'Limited technical detail'
                            ]
                        ],
                        2 => [
                            'level_name' => 'Good Demonstration',
                            'points' => 12,
                            'description' => 'Robot works well. Clear explanation of design and functionality.',
                            'indicators' => [
                                'Robot performs most functions',
                                'Clear design explanation',
                                'Good technical understanding'
                            ]
                        ],
                        3 => [
                            'level_name' => 'Excellent Demonstration',
                            'points' => 19,
                            'description' => 'Robot performs excellently. Detailed technical explanation.',
                            'indicators' => [
                                'Robot performs all functions smoothly',
                                'Detailed technical explanation',
                                'Shows engineering process'
                            ]
                        ],
                        4 => [
                            'level_name' => 'Outstanding Demonstration',
                            'points' => 25,
                            'description' => 'Robot exceeds expectations. Expert-level technical presentation.',
                            'indicators' => [
                                'Robot performance exceeds requirements',
                                'Advanced technical concepts explained',
                                'Innovative design features demonstrated'
                            ]
                        ]
                    ]
                ],
                'model_presentation' => [
                    'name' => 'Model Presentation',
                    'weight' => 15,
                    'max_points' => 15,
                    'display' => 'detailed_rubric',
                    'levels' => [
                        1 => [
                            'level_name' => 'Basic Model',
                            'points' => 4,
                            'description' => 'Simple model with basic explanation.',
                            'indicators' => [
                                'Basic model constructed',
                                'Simple explanation provided',
                                'Limited detail'
                            ]
                        ],
                        2 => [
                            'level_name' => 'Good Model',
                            'points' => 7,
                            'description' => 'Well-constructed model with clear explanation.',
                            'indicators' => [
                                'Well-built model',
                                'Clear explanation of function',
                                'Good visual presentation'
                            ]
                        ],
                        3 => [
                            'level_name' => 'Excellent Model',
                            'points' => 11,
                            'description' => 'Detailed model with comprehensive explanation.',
                            'indicators' => [
                                'Detailed, accurate model',
                                'Comprehensive explanation',
                                'Professional presentation'
                            ]
                        ],
                        4 => [
                            'level_name' => 'Outstanding Model',
                            'points' => 15,
                            'description' => 'Exceptional model with expert-level detail.',
                            'indicators' => [
                                'Highly detailed, professional model',
                                'Expert-level explanation',
                                'Innovative presentation methods'
                            ]
                        ]
                    ]
                ],
                'communication_skills' => [
                    'name' => 'Communication Skills',
                    'weight' => 5,
                    'max_points' => 5,
                    'display' => 'detailed_rubric',
                    'levels' => [
                        1 => [
                            'level_name' => 'Basic Communication',
                            'points' => 1,
                            'description' => 'Unclear presentation, difficult to follow.',
                            'indicators' => [
                                'Unclear speech',
                                'Poor organization',
                                'Limited audience engagement'
                            ]
                        ],
                        2 => [
                            'level_name' => 'Developing Communication',
                            'points' => 2,
                            'description' => 'Generally clear with some organization.',
                            'indicators' => [
                                'Mostly clear speech',
                                'Basic organization',
                                'Some audience engagement'
                            ]
                        ],
                        3 => [
                            'level_name' => 'Good Communication',
                            'points' => 4,
                            'description' => 'Clear, well-organized presentation.',
                            'indicators' => [
                                'Clear, confident speech',
                                'Well-organized content',
                                'Good audience engagement'
                            ]
                        ],
                        4 => [
                            'level_name' => 'Excellent Communication',
                            'points' => 5,
                            'description' => 'Exceptional presentation skills and engagement.',
                            'indicators' => [
                                'Confident, engaging delivery',
                                'Excellent organization',
                                'Outstanding audience connection'
                            ]
                        ]
                    ]
                ],
                'teamwork_collaboration' => [
                    'name' => 'Teamwork & Collaboration',
                    'weight' => 5,
                    'max_points' => 5,
                    'display' => 'detailed_rubric',
                    'levels' => [
                        1 => [
                            'level_name' => 'Individual Work',
                            'points' => 1,
                            'description' => 'Little evidence of teamwork during presentation.',
                            'indicators' => [
                                'One person dominates',
                                'No clear role distribution',
                                'Limited collaboration evident'
                            ]
                        ],
                        2 => [
                            'level_name' => 'Basic Teamwork',
                            'points' => 2,
                            'description' => 'Some evidence of teamwork and role sharing.',
                            'indicators' => [
                                'Some role sharing',
                                'Basic collaboration shown',
                                'Team members support each other'
                            ]
                        ],
                        3 => [
                            'level_name' => 'Good Teamwork',
                            'points' => 4,
                            'description' => 'Clear evidence of collaboration and shared responsibility.',
                            'indicators' => [
                                'Clear role distribution',
                                'Good collaboration',
                                'Effective team communication'
                            ]
                        ],
                        4 => [
                            'level_name' => 'Excellent Teamwork',
                            'points' => 5,
                            'description' => 'Outstanding collaboration with seamless teamwork.',
                            'indicators' => [
                                'Seamless collaboration',
                                'Each member contributes meaningfully',
                                'Exceptional team dynamics'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    // Gameplay judging configurations for timing-based competitions
    const GAMEPLAY_RUBRICS = [
        'ALL_CATEGORIES' => [
            'interface_type' => 'gameplay',
            'scoring_method' => 'time_and_mission',
            'max_runs' => 3,
            'max_mission_score' => 100,
            'time_bonus_enabled' => true,
            'mission_objectives' => [
                'startup_sequence' => [
                    'name' => 'Robot Startup & Initialization',
                    'points' => 10,
                    'description' => 'Robot powers up correctly and initializes all systems'
                ],
                'navigation_start' => [
                    'name' => 'Navigation from Start Position',
                    'points' => 15,
                    'description' => 'Robot successfully leaves starting position and begins navigation'
                ],
                'obstacle_navigation' => [
                    'name' => 'Obstacle Navigation',
                    'points' => 20,
                    'description' => 'Robot successfully navigates around or through obstacles'
                ],
                'mission_zone_entry' => [
                    'name' => 'Mission Zone Entry',
                    'points' => 15,
                    'description' => 'Robot enters designated mission zone(s)'
                ],
                'task_execution' => [
                    'name' => 'Primary Task Execution',
                    'points' => 25,
                    'description' => 'Robot performs primary mission tasks successfully'
                ],
                'return_navigation' => [
                    'name' => 'Return Navigation',
                    'points' => 10,
                    'description' => 'Robot navigates back towards start area'
                ],
                'final_position' => [
                    'name' => 'Final Position/Parking',
                    'points' => 5,
                    'description' => 'Robot ends in designated final position'
                ]
            ],
            'time_scoring' => [
                'target_time_seconds' => 300, // 5 minutes
                'maximum_time_seconds' => 600, // 10 minutes
                'time_bonus_formula' => 'linear_decay', // Bonus points for faster completion
                'penalty_per_minute_over' => 5 // Point penalty for going over target time
            ]
        ]
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function getRubricForCategory($categoryId, $sessionType = 'final')
    {
        $category = $this->getCategory($categoryId);
        if (!$category) {
            throw new \Exception("Category not found: {$categoryId}");
        }
        
        $rubricConfig = self::CATEGORY_RUBRICS[$category['name']] ?? null;
        
        if (!$rubricConfig) {
            // Fallback to standard rubric for unknown categories
            $rubricConfig = self::CATEGORY_RUBRICS['SPIKE_INTERMEDIATE'];
        }
        
        // Load rubric template from database
        $template = $this->getRubricTemplate($categoryId);
        
        // Merge with configuration
        return $this->mergeRubricWithConfig($template, $rubricConfig, $sessionType, $category);
    }
    
    public function generateScoringInterface($categoryId)
    {
        $rubric = $this->getRubricForCategory($categoryId);
        
        switch ($rubric['interface_type']) {
            case 'visual':
                return $this->generateVisualInterface($rubric);
            case 'standard':
                return $this->generateStandardInterface($rubric);
            case 'technical':
                return $this->generateTechnicalInterface($rubric);
            case 'comprehensive':
                return $this->generateComprehensiveInterface($rubric);
            default:
                return $this->generateStandardInterface($rubric);
        }
    }
    
    private function generateVisualInterface($rubric)
    {
        $html = '<div class="visual-scoring-interface" data-interface-type="visual">';
        
        // Header section
        $html .= '<div class="visual-header">';
        $html .= '<h3 class="scoring-title">How did this team do? 🤖</h3>';
        $html .= '<div class="team-encouragement">';
        $html .= '<p class="encouragement-text">Let\'s celebrate their hard work!</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Scoring sections
        foreach ($rubric['sections'] as $sectionKey => $section) {
            $html .= '<div class="scoring-section visual-section" data-section="' . $sectionKey . '">';
            $html .= '<h4 class="section-title">' . ucwords(str_replace('_', ' ', $sectionKey));
            
            // Add section emoji
            $emoji = $this->getSectionEmoji($sectionKey);
            if ($emoji) {
                $html .= ' ' . $emoji;
            }
            
            $html .= '</h4>';
            
            // Generate section content based on display type
            switch ($section['display']) {
                case 'emoji_scale':
                    $html .= $this->generateEmojiScale($sectionKey, $section);
                    break;
                    
                case 'star_rating':
                    $html .= $this->generateStarRating($sectionKey, $section);
                    break;
                    
                case 'visual_cards':
                    $html .= $this->generateVisualCards($sectionKey, $section);
                    break;
            }
            
            $html .= '<div class="section-feedback">';
            $html .= '<textarea class="feedback-input" placeholder="Any encouraging words for the team..."></textarea>';
            $html .= '<div class="progress-indicator">';
            $html .= '<div class="progress-bar"></div>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        // Total score display
        $html .= '<div class="total-score-visual">';
        $html .= '<div class="score-celebration">';
        $html .= '<div class="score-stars" id="celebration-stars">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= '<i class="fas fa-star score-star" data-star="' . $i . '"></i>';
        }
        $html .= '</div>';
        $html .= '<h2 class="total-points"><span id="total-score">0</span> / ' . $rubric['max_score'] . '</h2>';
        $html .= '<p class="score-encouragement">Great job, team! 🎉</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return [
            'html' => $html,
            'javascript' => $this->generateVisualInterfaceJS(),
            'css' => $this->generateVisualInterfaceCSS()
        ];
    }
    
    private function generateEmojiScale($sectionKey, $section)
    {
        $html = '<div class="emoji-scale" data-criteria-section="' . $sectionKey . '">';
        
        foreach ($section['levels'] as $level => $data) {
            $html .= '<button class="emoji-button" ';
            $html .= 'data-level="' . $level . '" ';
            $html .= 'data-points="' . $data['points'] . '" ';
            $html .= 'data-section="' . $sectionKey . '">';
            $html .= '<div class="emoji-display">' . $data['emoji'] . '</div>';
            $html .= '<div class="emoji-label">' . $data['text'] . '</div>';
            $html .= '<div class="emoji-points">' . $data['points'] . ' pts</div>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function generateStarRating($sectionKey, $section)
    {
        $html = '<div class="star-rating" data-criteria-section="' . $sectionKey . '">';
        
        foreach ($section['levels'] as $level => $data) {
            $html .= '<div class="star-option" data-level="' . $level . '" data-points="' . $data['points'] . '">';
            $html .= '<div class="star-display">';
            
            for ($i = 1; $i <= 4; $i++) {
                $class = $i <= $data['stars'] ? 'fas fa-star filled' : 'far fa-star';
                $html .= '<i class="' . $class . '"></i>';
            }
            
            $html .= '</div>';
            $html .= '<div class="star-label">' . $data['text'] . '</div>';
            $html .= '<div class="star-points">' . $data['points'] . ' pts</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function generateVisualCards($sectionKey, $section)
    {
        $html = '<div class="visual-cards" data-criteria-section="' . $sectionKey . '">';
        
        foreach ($section['levels'] as $level => $data) {
            $html .= '<div class="visual-card" data-level="' . $level . '" data-points="' . $data['points'] . '">';
            $html .= '<div class="card-image">';
            $html .= '<img src="/images/scoring/' . $data['image'] . '" alt="' . $data['text'] . '" />';
            $html .= '</div>';
            $html .= '<div class="card-content">';
            $html .= '<h5>' . $data['text'] . '</h5>';
            $html .= '<span class="card-points">' . $data['points'] . ' points</span>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function generateTechnicalInterface($rubric)
    {
        $html = '<div class="technical-scoring-interface" data-interface-type="technical">';
        
        // Technical header
        $html .= '<div class="technical-header">';
        $html .= '<h3 class="scoring-title">Technical Assessment</h3>';
        $html .= '<div class="assessment-timer">';
        $html .= '<i class="fas fa-stopwatch"></i>';
        $html .= '<span id="execution-time">00:00</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Code analysis section
        if (isset($rubric['sections']['technical_implementation'])) {
            $section = $rubric['sections']['technical_implementation'];
            $html .= '<div class="code-analysis-section">';
            $html .= '<h4>Code Quality Analysis</h4>';
            $html .= '<div class="metrics-grid">';
            
            foreach ($section['metrics'] as $metricKey => $metric) {
                $html .= '<div class="metric-card" data-metric="' . $metricKey . '">';
                $html .= '<label class="metric-label">' . ucwords(str_replace('_', ' ', $metricKey)) . '</label>';
                $html .= '<input type="range" ';
                $html .= 'class="form-range technical-slider" ';
                $html .= 'data-criteria-id="' . $metricKey . '" ';
                $html .= 'min="' . $metric['min'] . '" ';
                $html .= 'max="' . $metric['max'] . '" ';
                $html .= 'value="0" ';
                $html .= 'step="' . $metric['step'] . '">';
                $html .= '<div class="metric-value">';
                $html .= '<span class="current-value">0</span>';
                $html .= '<span class="max-value">/ ' . $metric['max'] . '</span>';
                $html .= '</div>';
                $html .= '<small class="metric-description">' . $metric['description'] . '</small>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Mission checkpoints
        if (isset($rubric['sections']['hardware_integration'])) {
            $section = $rubric['sections']['hardware_integration'];
            $html .= '<div class="mission-checkpoints">';
            $html .= '<h4>Hardware Integration</h4>';
            $html .= '<div class="checkpoint-list">';
            
            $index = 1;
            foreach ($section['checkpoints'] as $checkpointKey => $checkpoint) {
                $html .= '<div class="checkpoint-item" data-checkpoint-id="' . $checkpointKey . '">';
                $html .= '<div class="checkpoint-number">' . $index . '</div>';
                $html .= '<div class="checkpoint-details">';
                $html .= '<h5>' . ucwords(str_replace('_', ' ', $checkpointKey)) . '</h5>';
                $html .= '<p class="checkpoint-description">' . $checkpoint['description'] . '</p>';
                $html .= '<div class="checkpoint-options">';
                $html .= '<label class="radio-option">';
                $html .= '<input type="radio" name="checkpoint-' . $checkpointKey . '" value="0">';
                $html .= '<span>Not Achieved (0 pts)</span>';
                $html .= '</label>';
                $html .= '<label class="radio-option">';
                $html .= '<input type="radio" name="checkpoint-' . $checkpointKey . '" value="' . ($checkpoint['points'] / 2) . '">';
                $html .= '<span>Partial (' . ($checkpoint['points'] / 2) . ' pts)</span>';
                $html .= '</label>';
                $html .= '<label class="radio-option">';
                $html .= '<input type="radio" name="checkpoint-' . $checkpointKey . '" value="' . $checkpoint['points'] . '">';
                $html .= '<span>Complete (' . $checkpoint['points'] . ' pts)</span>';
                $html .= '</label>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="checkpoint-score">';
                $html .= '<span class="score-value">0</span>';
                $html .= '</div>';
                $html .= '</div>';
                $index++;
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Performance metrics
        if (isset($rubric['sections']['performance_metrics'])) {
            $section = $rubric['sections']['performance_metrics'];
            $html .= '<div class="performance-metrics">';
            $html .= '<h4>Performance Assessment</h4>';
            
            if (isset($section['time_bonus'])) {
                $html .= '<div class="time-bonus">';
                $html .= '<label>Completion Time (seconds)</label>';
                $html .= '<input type="number" id="completion-time" class="form-control" min="0" max="' . ($section['time_bonus']['target_time'] * 2) . '">';
                $html .= '<span class="bonus-calculation">Time Bonus: +<span id="time-bonus">0</span> pts</span>';
                $html .= '</div>';
            }
            
            if (isset($section['accuracy_score'])) {
                $html .= '<div class="accuracy-assessment">';
                $html .= '<label>Accuracy Score (%)</label>';
                $html .= '<input type="range" id="accuracy-score" class="form-range" min="0" max="100" value="0">';
                $html .= '<span class="accuracy-value"><span id="accuracy-percent">0</span>% = <span id="accuracy-points">0</span> pts</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Technical notes
        $html .= '<div class="technical-notes-section">';
        $html .= '<h4>Technical Observations</h4>';
        $html .= '<div class="quick-tags">';
        $html .= '<button class="tag-btn" data-tag="innovative-approach">Innovative Approach</button>';
        $html .= '<button class="tag-btn" data-tag="clean-code">Clean Code</button>';
        $html .= '<button class="tag-btn" data-tag="error-handling">Good Error Handling</button>';
        $html .= '<button class="tag-btn" data-tag="needs-optimization">Needs Optimization</button>';
        $html .= '<button class="tag-btn" data-tag="excellent-documentation">Excellent Documentation</button>';
        $html .= '</div>';
        $html .= '<textarea id="technical-notes" class="form-control" rows="4" placeholder="Detailed technical observations and feedback..."></textarea>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return [
            'html' => $html,
            'javascript' => $this->generateTechnicalInterfaceJS(),
            'css' => $this->generateTechnicalInterfaceCSS()
        ];
    }
    
    private function generateStandardInterface($rubric)
    {
        $html = '<div class="standard-scoring-interface" data-interface-type="standard">';
        
        foreach ($rubric['sections'] as $sectionKey => $section) {
            $html .= '<div class="scoring-section standard-section" data-section="' . $sectionKey . '">';
            $html .= '<h4 class="section-title">' . ucwords(str_replace('_', ' ', $sectionKey)) . '</h4>';
            
            if (isset($section['sub_criteria'])) {
                foreach ($section['sub_criteria'] as $criteriaKey => $criteria) {
                    $html .= '<div class="criteria-item">';
                    $html .= '<label>' . ucwords(str_replace('_', ' ', $criteriaKey)) . '</label>';
                    $html .= '<input type="number" class="form-control" ';
                    $html .= 'data-criteria="' . $criteriaKey . '" ';
                    $html .= 'min="0" max="' . $criteria['max_points'] . '" value="0">';
                    $html .= '<small>' . $criteria['description'] . '</small>';
                    $html .= '</div>';
                }
            }
            
            if (isset($section['checkpoints'])) {
                foreach ($section['checkpoints'] as $checkpointKey => $checkpoint) {
                    $html .= '<div class="checkpoint-item">';
                    $html .= '<label>';
                    $html .= '<input type="checkbox" data-checkpoint="' . $checkpointKey . '" value="' . $checkpoint['points'] . '">';
                    $html .= ucwords(str_replace('_', ' ', $checkpointKey)) . ' (' . $checkpoint['points'] . ' pts)';
                    $html .= '</label>';
                    $html .= '<small>' . $checkpoint['description'] . '</small>';
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return [
            'html' => $html,
            'javascript' => $this->generateStandardInterfaceJS(),
            'css' => $this->generateStandardInterfaceCSS()
        ];
    }
    
    private function getCategory($categoryId)
    {
        $category = $this->db->query('SELECT * FROM categories WHERE id = ?', [$categoryId]);
        return !empty($category) ? $category[0] : null;
    }
    
    private function getRubricTemplate($categoryId)
    {
        $template = $this->db->query('
            SELECT rt.*, rs.id as section_id, rs.section_name, rs.section_type, rs.multiplier,
                   rc.id as criteria_id, rc.criteria_name, rc.max_points
            FROM rubric_templates rt
            LEFT JOIN rubric_sections rs ON rt.id = rs.rubric_template_id
            LEFT JOIN rubric_criteria rc ON rs.id = rc.section_id
            WHERE rt.category_id = ? AND rt.is_active = true
            ORDER BY rs.display_order, rc.display_order
        ', [$categoryId]);
        
        if (empty($template)) {
            return null;
        }
        
        // Group the results
        $rubric = $template[0];
        $rubric['sections'] = [];
        $rubric['criteria'] = [];
        
        foreach ($template as $row) {
            if ($row['section_id'] && !isset($rubric['sections'][$row['section_id']])) {
                $rubric['sections'][$row['section_id']] = [
                    'id' => $row['section_id'],
                    'name' => $row['section_name'],
                    'type' => $row['section_type'],
                    'multiplier' => $row['multiplier']
                ];
            }
            
            if ($row['criteria_id']) {
                $rubric['criteria'][] = [
                    'id' => $row['criteria_id'],
                    'section_id' => $row['section_id'],
                    'name' => $row['criteria_name'],
                    'max_points' => $row['max_points']
                ];
            }
        }
        
        return $rubric;
    }
    
    private function mergeRubricWithConfig($template, $config, $sessionType, $category)
    {
        // Start with the configuration
        $merged = $config;
        
        // Add database information
        $merged['category'] = $category;
        $merged['template_id'] = $template['id'] ?? null;
        $merged['session_type'] = $sessionType;
        
        // If we have a database template, merge its criteria
        if ($template && isset($template['criteria'])) {
            $merged['database_criteria'] = $template['criteria'];
        }
        
        return $merged;
    }
    
    private function getSectionEmoji($sectionKey)
    {
        $emojis = [
            'robot_performance' => '🤖',
            'teamwork' => '👥',
            'creativity' => '💡',
            'programming' => '💻',
            'mission_completion' => '🎯',
            'innovation' => '🚀',
            'technical_implementation' => '⚙️',
            'hardware_integration' => '🔧',
            'performance_metrics' => '📊'
        ];
        
        return $emojis[$sectionKey] ?? '';
    }
    
    // JavaScript generation methods
    private function generateVisualInterfaceJS()
    {
        return '
        class VisualScoringInterface {
            constructor() {
                this.scores = {};
                this.totalScore = 0;
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.updateTotalScore();
            }
            
            bindEvents() {
                // Emoji button clicks
                $(".emoji-button").click((e) => {
                    const btn = $(e.currentTarget);
                    const section = btn.data("section");
                    const level = btn.data("level");
                    const points = btn.data("points");
                    
                    // Clear other selections in this section
                    $(`.emoji-button[data-section="${section}"]`).removeClass("selected");
                    btn.addClass("selected");
                    
                    // Store score
                    this.scores[section] = points;
                    this.updateTotalScore();
                    
                    // Animate selection
                    this.animateSelection(btn);
                });
                
                // Star rating clicks
                $(".star-option").click((e) => {
                    const option = $(e.currentTarget);
                    const section = option.closest(".star-rating").data("criteria-section");
                    const points = option.data("points");
                    
                    // Update selection
                    option.siblings().removeClass("selected");
                    option.addClass("selected");
                    
                    this.scores[section] = points;
                    this.updateTotalScore();
                });
                
                // Visual card clicks
                $(".visual-card").click((e) => {
                    const card = $(e.currentTarget);
                    const section = card.closest(".visual-cards").data("criteria-section");
                    const points = card.data("points");
                    
                    // Update selection
                    card.siblings().removeClass("selected");
                    card.addClass("selected");
                    
                    this.scores[section] = points;
                    this.updateTotalScore();
                });
            }
            
            updateTotalScore() {
                this.totalScore = Object.values(this.scores).reduce((sum, score) => sum + score, 0);
                $("#total-score").text(this.totalScore);
                
                // Update celebration stars
                const starCount = Math.ceil((this.totalScore / 100) * 5);
                $(".score-star").removeClass("active");
                for (let i = 1; i <= starCount; i++) {
                    $(`.score-star[data-star="${i}"]`).addClass("active");
                }
                
                // Update progress bars
                $(".scoring-section").each((i, section) => {
                    const sectionKey = $(section).data("section");
                    const sectionScore = this.scores[sectionKey] || 0;
                    const maxScore = this.getSectionMaxScore(sectionKey);
                    const percentage = (sectionScore / maxScore) * 100;
                    
                    $(section).find(".progress-bar").css("width", percentage + "%");
                });
            }
            
            getSectionMaxScore(sectionKey) {
                // This would be dynamically set based on rubric configuration
                return 40; // Default
            }
            
            animateSelection(element) {
                element.addClass("pulse");
                setTimeout(() => element.removeClass("pulse"), 600);
            }
            
            getScores() {
                return {
                    criteria_scores: this.scores,
                    total_score: this.totalScore,
                    interface_type: "visual"
                };
            }
        }
        
        // Initialize when document is ready
        $(document).ready(() => {
            window.visualScoring = new VisualScoringInterface();
        });
        ';
    }
    
    private function generateTechnicalInterfaceJS()
    {
        return '
        class TechnicalScoringInterface {
            constructor() {
                this.scores = {};
                this.timeBonus = 0;
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.startTimer();
            }
            
            bindEvents() {
                // Slider changes
                $(".technical-slider").on("input", (e) => {
                    const slider = $(e.target);
                    const criteriaId = slider.data("criteria-id");
                    const value = parseInt(slider.val());
                    
                    slider.siblings(".metric-value").find(".current-value").text(value);
                    this.scores[criteriaId] = value;
                    this.updateTotalScore();
                });
                
                // Checkpoint radio buttons
                $("input[type=radio][name^=checkpoint-]").change((e) => {
                    const radio = $(e.target);
                    const checkpointId = radio.attr("name").replace("checkpoint-", "");
                    const value = parseInt(radio.val());
                    
                    this.scores[checkpointId] = value;
                    radio.closest(".checkpoint-item").find(".score-value").text(value);
                    this.updateTotalScore();
                });
                
                // Time bonus calculation
                $("#completion-time").on("input", (e) => {
                    const completionTime = parseInt($(e.target).val()) || 0;
                    this.calculateTimeBonus(completionTime);
                });
                
                // Accuracy score
                $("#accuracy-score").on("input", (e) => {
                    const accuracy = parseInt($(e.target).val());
                    const points = Math.round((accuracy / 100) * 20); // Max 20 points
                    
                    $("#accuracy-percent").text(accuracy);
                    $("#accuracy-points").text(points);
                    this.scores["accuracy"] = points;
                    this.updateTotalScore();
                });
                
                // Quick tags
                $(".tag-btn").click((e) => {
                    const btn = $(e.target);
                    btn.toggleClass("selected");
                    
                    const selectedTags = $(".tag-btn.selected").map((i, el) => $(el).data("tag")).get();
                    this.scores["tags"] = selectedTags;
                });
            }
            
            calculateTimeBonus(completionTime) {
                const targetTime = 300; // 5 minutes
                let bonus = 0;
                
                if (completionTime <= targetTime) {
                    bonus = Math.round(10 * (targetTime - completionTime) / targetTime);
                }
                
                this.timeBonus = Math.max(0, bonus);
                $("#time-bonus").text(this.timeBonus);
                this.scores["time_bonus"] = this.timeBonus;
                this.updateTotalScore();
            }
            
            updateTotalScore() {
                const total = Object.values(this.scores)
                    .filter(score => typeof score === "number")
                    .reduce((sum, score) => sum + score, 0);
                    
                $(".total-score-display").text(total);
            }
            
            startTimer() {
                const startTime = Date.now();
                setInterval(() => {
                    const elapsed = Math.floor((Date.now() - startTime) / 1000);
                    const minutes = Math.floor(elapsed / 60);
                    const seconds = elapsed % 60;
                    $("#execution-time").text(`${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`);
                }, 1000);
            }
            
            getScores() {
                return {
                    criteria_scores: this.scores,
                    technical_notes: $("#technical-notes").val(),
                    interface_type: "technical"
                };
            }
        }
        
        $(document).ready(() => {
            window.technicalScoring = new TechnicalScoringInterface();
        });
        ';
    }
    
    private function generateStandardInterfaceJS()
    {
        return '
        class StandardScoringInterface {
            constructor() {
                this.scores = {};
                this.init();
            }
            
            init() {
                this.bindEvents();
            }
            
            bindEvents() {
                // Number inputs
                $("input[type=number][data-criteria]").on("input", (e) => {
                    const input = $(e.target);
                    const criteria = input.data("criteria");
                    const value = parseInt(input.val()) || 0;
                    
                    this.scores[criteria] = value;
                    this.updateTotalScore();
                });
                
                // Checkboxes
                $("input[type=checkbox][data-checkpoint]").change((e) => {
                    const checkbox = $(e.target);
                    const checkpoint = checkbox.data("checkpoint");
                    const value = checkbox.is(":checked") ? parseInt(checkbox.val()) : 0;
                    
                    this.scores[checkpoint] = value;
                    this.updateTotalScore();
                });
            }
            
            updateTotalScore() {
                const total = Object.values(this.scores).reduce((sum, score) => sum + score, 0);
                $(".total-score-display").text(total);
            }
            
            getScores() {
                return {
                    criteria_scores: this.scores,
                    interface_type: "standard"
                };
            }
        }
        
        $(document).ready(() => {
            window.standardScoring = new StandardScoringInterface();
        });
        ';
    }
    
    // CSS generation methods
    private function generateVisualInterfaceCSS()
    {
        return '
        .visual-scoring-interface {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: "Comic Sans MS", cursive, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
        }
        
        .visual-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .scoring-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .emoji-scale {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .emoji-button {
            background: white;
            color: #333;
            border: 3px solid transparent;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            transform: scale(1);
        }
        
        .emoji-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .emoji-button.selected {
            border-color: #FFD700;
            background: #FFF8DC;
            transform: scale(1.1);
        }
        
        .emoji-display {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .emoji-label {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .emoji-points {
            color: #666;
            font-size: 1rem;
        }
        
        .total-score-visual {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
        }
        
        .score-stars {
            margin-bottom: 20px;
        }
        
        .score-star {
            font-size: 2rem;
            margin: 0 5px;
            color: #ccc;
            transition: color 0.3s ease;
        }
        
        .score-star.active {
            color: #FFD700;
            animation: starPulse 0.5s ease;
        }
        
        @keyframes starPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .progress-bar {
            height: 8px;
            background: #4CAF50;
            border-radius: 4px;
            transition: width 0.5s ease;
            width: 0%;
        }
        ';
    }
    
    private function generateTechnicalInterfaceCSS()
    {
        return '
        .technical-scoring-interface {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            font-family: "Roboto", sans-serif;
        }
        
        .technical-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 30px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .technical-slider {
            width: 100%;
            margin: 10px 0;
            -webkit-appearance: none;
            height: 8px;
            border-radius: 4px;
            background: #ddd;
            outline: none;
        }
        
        .technical-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
        }
        
        .checkpoint-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .checkpoint-number {
            background: #28a745;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .tag-btn {
            background: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 20px;
            padding: 5px 15px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tag-btn.selected {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        ';
    }
    
    private function generateStandardInterfaceCSS()
    {
        return '
        .standard-scoring-interface {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .scoring-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .criteria-item, .checkpoint-item {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }
        ';
    }
    
    private function generateComprehensiveInterface($rubric)
    {
        // This would be a combination of multiple interface types
        // For now, return the standard interface
        return $this->generateStandardInterface($rubric);
    }

    /**
     * Get presentation rubric configuration
     */
    public function getPresentationRubric($categoryId = null)
    {
        // For now, all categories use the same presentation rubric
        // Can be extended later for category-specific presentation rubrics
        return self::PRESENTATION_RUBRICS['ALL_CATEGORIES'];
    }

    /**
     * Get gameplay rubric configuration
     */
    public function getGameplayRubric($categoryId = null)
    {
        // For now, all categories use the same gameplay rubric
        // Can be extended later for category-specific gameplay rubrics
        return self::GAMEPLAY_RUBRICS['ALL_CATEGORIES'];
    }

    /**
     * Generate presentation scoring interface
     */
    public function generatePresentationInterface($categoryId = null)
    {
        $rubric = $this->getPresentationRubric($categoryId);

        $html = '<div class="presentation-scoring-interface" data-interface-type="presentation">';

        // Header section
        $html .= '<div class="presentation-header">';
        $html .= '<h3 class="scoring-title">🎤 Team Presentation Judging</h3>';
        $html .= '<div class="presentation-timer">';
        $html .= '<div class="timer-display" id="presentation-timer">00:00</div>';
        $html .= '<div class="timer-controls">';
        $html .= '<button class="btn btn-success btn-sm" id="start-presentation">Start Presentation</button>';
        $html .= '<button class="btn btn-warning btn-sm" id="pause-presentation">Pause</button>';
        $html .= '<button class="btn btn-secondary btn-sm" id="reset-presentation">Reset</button>';
        $html .= '</div>';
        $html .= '<div class="time-limit">Time Limit: ' . $rubric['time_limit_minutes'] . ' minutes</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Scoring sections
        foreach ($rubric['sections'] as $sectionKey => $section) {
            $html .= '<div class="presentation-section" data-section="' . $sectionKey . '">';
            $html .= '<h4 class="section-title">' . $section['name'] . ' (' . $section['max_points'] . ' points)</h4>';

            $html .= '<div class="presentation-levels">';
            foreach ($section['levels'] as $level => $levelData) {
                $html .= '<label class="presentation-level level-' . $level . '">';
                $html .= '<input type="radio" name="presentation-' . $sectionKey . '" ';
                $html .= 'value="' . $level . '" ';
                $html .= 'data-points="' . $levelData['points'] . '" ';
                $html .= 'data-section="' . $sectionKey . '">';
                $html .= '<div class="level-content">';
                $html .= '<div class="level-header">';
                $html .= '<span class="level-name">' . $levelData['level_name'] . '</span>';
                $html .= '<span class="level-points">' . $levelData['points'] . ' pts</span>';
                $html .= '</div>';
                $html .= '<p class="level-description">' . $levelData['description'] . '</p>';
                if (isset($levelData['indicators'])) {
                    $html .= '<ul class="level-indicators">';
                    foreach ($levelData['indicators'] as $indicator) {
                        $html .= '<li>' . $indicator . '</li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</div>';
                $html .= '</label>';
            }
            $html .= '</div>';

            $html .= '<div class="section-notes">';
            $html .= '<textarea class="section-feedback" placeholder="Notes for ' . $section['name'] . '..."></textarea>';
            $html .= '</div>';

            $html .= '</div>';
        }

        // Total score display
        $html .= '<div class="presentation-total">';
        $html .= '<h3>Total Presentation Score</h3>';
        $html .= '<div class="score-breakdown">';
        foreach ($rubric['sections'] as $sectionKey => $section) {
            $html .= '<div class="score-item">';
            $html .= '<span class="score-label">' . $section['name'] . ':</span>';
            $html .= '<span class="score-value" id="score-' . $sectionKey . '">0</span>';
            $html .= '<span class="score-max">/ ' . $section['max_points'] . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<div class="total-score-display">';
        $html .= '<span class="total-label">Total:</span>';
        $html .= '<span class="total-value" id="total-presentation-score">0</span>';
        $html .= '<span class="total-max">/ ' . $rubric['max_score'] . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return [
            'html' => $html,
            'javascript' => $this->generatePresentationInterfaceJS(),
            'css' => $this->generatePresentationInterfaceCSS()
        ];
    }

    /**
     * Generate gameplay scoring interface
     */
    public function generateGameplayInterface($categoryId = null)
    {
        $rubric = $this->getGameplayRubric($categoryId);

        $html = '<div class="gameplay-scoring-interface" data-interface-type="gameplay">';

        // Header section
        $html .= '<div class="gameplay-header">';
        $html .= '<h3 class="scoring-title">⏱️ Gameplay Judging</h3>';
        $html .= '<div class="run-selector">';
        $html .= '<button class="btn btn-primary run-btn active" data-run="1">Run 1</button>';
        $html .= '<button class="btn btn-secondary run-btn" data-run="2">Run 2</button>';
        $html .= '<button class="btn btn-secondary run-btn" data-run="3">Run 3</button>';
        $html .= '</div>';
        $html .= '</div>';

        // Current run display
        $html .= '<div class="current-run-display">';
        $html .= '<h4>Current Run: <span id="current-run-number">1</span></h4>';
        $html .= '<div class="run-timer">';
        $html .= '<div class="timer-display" id="run-timer">00:00</div>';
        $html .= '<div class="timer-controls">';
        $html .= '<button class="btn btn-success" id="start-run">Start Run</button>';
        $html .= '<button class="btn btn-warning" id="pause-run">Pause</button>';
        $html .= '<button class="btn btn-danger" id="end-run">End Run</button>';
        $html .= '<button class="btn btn-secondary" id="reset-run">Reset</button>';
        $html .= '</div>';
        $html .= '<div class="run-status" id="run-status">Ready to start</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Mission objectives checklist
        $html .= '<div class="mission-objectives">';
        $html .= '<h4>Mission Objectives</h4>';
        $html .= '<div class="objectives-list">';
        foreach ($rubric['mission_objectives'] as $objKey => $objective) {
            $html .= '<div class="objective-item">';
            $html .= '<label class="objective-label">';
            $html .= '<input type="checkbox" class="objective-checkbox" ';
            $html .= 'data-objective="' . $objKey . '" ';
            $html .= 'data-points="' . $objective['points'] . '">';
            $html .= '<span class="objective-name">' . $objective['name'] . '</span>';
            $html .= '<span class="objective-points">(' . $objective['points'] . ' pts)</span>';
            $html .= '</label>';
            $html .= '<div class="objective-description">' . $objective['description'] . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Run results summary
        $html .= '<div class="run-results">';
        $html .= '<h4>Run Results</h4>';
        $html .= '<div class="runs-summary">';
        for ($i = 1; $i <= $rubric['max_runs']; $i++) {
            $html .= '<div class="run-summary" id="run-' . $i . '-summary">';
            $html .= '<h5>Run ' . $i . '</h5>';
            $html .= '<div class="run-time">Time: <span class="time-value">--:--</span></div>';
            $html .= '<div class="run-mission-score">Mission Score: <span class="mission-score">0</span> pts</div>';
            $html .= '<div class="run-total-score">Total: <span class="total-score">0</span> pts</div>';
            $html .= '<div class="run-completion">Status: <span class="completion-status">Not started</span></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Best run display
        $html .= '<div class="best-run-display">';
        $html .= '<h4>Best Run Selection</h4>';
        $html .= '<div class="best-run-info">';
        $html .= '<div class="best-run-number">Best Run: <span id="best-run">TBD</span></div>';
        $html .= '<div class="best-run-time">Fastest Time: <span id="best-time">--:--</span></div>';
        $html .= '<div class="best-run-score">Final Score: <span id="final-gameplay-score">0</span> pts</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return [
            'html' => $html,
            'javascript' => $this->generateGameplayInterfaceJS(),
            'css' => $this->generateGameplayInterfaceCSS()
        ];
    }

    private function generatePresentationInterfaceJS()
    {
        return '
        class PresentationScoringInterface {
            constructor() {
                this.scores = {};
                this.totalScore = 0;
                this.timer = null;
                this.startTime = null;
                this.elapsed = 0;
                this.isRunning = false;
                this.init();
            }

            init() {
                this.bindEvents();
            }

            bindEvents() {
                // Timer controls
                $("#start-presentation").click(() => this.startTimer());
                $("#pause-presentation").click(() => this.pauseTimer());
                $("#reset-presentation").click(() => this.resetTimer());

                // Level selection
                $(".presentation-level input[type=radio]").change((e) => {
                    const input = $(e.target);
                    const section = input.data("section");
                    const points = input.data("points");

                    // Update section styling
                    input.closest(".presentation-section").find(".presentation-level").removeClass("selected");
                    input.closest(".presentation-level").addClass("selected");

                    this.scores[section] = points;
                    this.updateScoreDisplay();
                });
            }

            startTimer() {
                if (!this.isRunning) {
                    this.startTime = Date.now() - this.elapsed;
                    this.isRunning = true;
                    this.timer = setInterval(() => this.updateTimer(), 1000);
                    $("#start-presentation").prop("disabled", true);
                    $("#pause-presentation").prop("disabled", false);
                }
            }

            pauseTimer() {
                if (this.isRunning) {
                    clearInterval(this.timer);
                    this.isRunning = false;
                    $("#start-presentation").prop("disabled", false);
                    $("#pause-presentation").prop("disabled", true);
                }
            }

            resetTimer() {
                clearInterval(this.timer);
                this.elapsed = 0;
                this.isRunning = false;
                this.updateTimerDisplay();
                $("#start-presentation").prop("disabled", false);
                $("#pause-presentation").prop("disabled", true);
            }

            updateTimer() {
                this.elapsed = Date.now() - this.startTime;
                this.updateTimerDisplay();
            }

            updateTimerDisplay() {
                const seconds = Math.floor(this.elapsed / 1000);
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;

                const display = `${minutes.toString().padStart(2, "0")}:${remainingSeconds.toString().padStart(2, "0")}`;
                $("#presentation-timer").text(display);

                // Warning at 8 minutes (if 10 minute limit)
                if (minutes >= 8) {
                    $("#presentation-timer").addClass("warning");
                }
                if (minutes >= 10) {
                    $("#presentation-timer").addClass("overtime");
                }
            }

            updateScoreDisplay() {
                this.totalScore = Object.values(this.scores).reduce((sum, score) => sum + score, 0);

                // Update individual section scores
                Object.keys(this.scores).forEach(section => {
                    $(`#score-${section}`).text(this.scores[section]);
                });

                // Update total
                $("#total-presentation-score").text(this.totalScore);
            }

            getScores() {
                return {
                    judging_mode: "presentation",
                    presentation_scores: this.scores,
                    total_score: this.totalScore,
                    presentation_duration_minutes: Math.floor(this.elapsed / 60000),
                    section_notes: this.getSectionNotes(),
                    interface_type: "presentation"
                };
            }

            getSectionNotes() {
                const notes = {};
                $(".section-feedback").each(function() {
                    const section = $(this).closest(".presentation-section").data("section");
                    notes[section] = $(this).val();
                });
                return notes;
            }
        }

        $(document).ready(() => {
            window.presentationScoring = new PresentationScoringInterface();
        });
        ';
    }

    private function generateGameplayInterfaceJS()
    {
        return '
        class GameplayScoringInterface {
            constructor() {
                this.currentRun = 1;
                this.runs = {
                    1: { time: 0, missions: {}, status: "not_started", score: 0 },
                    2: { time: 0, missions: {}, status: "not_started", score: 0 },
                    3: { time: 0, missions: {}, status: "not_started", score: 0 }
                };
                this.timer = null;
                this.startTime = null;
                this.isRunning = false;
                this.bestRun = null;
                this.init();
            }

            init() {
                this.bindEvents();
                this.updateCurrentRun();
            }

            bindEvents() {
                // Run selection
                $(".run-btn").click((e) => {
                    const runNumber = parseInt($(e.target).data("run"));
                    this.switchToRun(runNumber);
                });

                // Timer controls
                $("#start-run").click(() => this.startTimer());
                $("#pause-run").click(() => this.pauseTimer());
                $("#end-run").click(() => this.endRun());
                $("#reset-run").click(() => this.resetRun());

                // Mission objectives
                $(".objective-checkbox").change((e) => {
                    const checkbox = $(e.target);
                    const objective = checkbox.data("objective");
                    const points = checkbox.data("points");

                    this.runs[this.currentRun].missions[objective] = checkbox.is(":checked") ? points : 0;
                    this.updateRunScore();
                });
            }

            switchToRun(runNumber) {
                if (this.isRunning) {
                    this.pauseTimer();
                }

                // Save current run state
                this.saveCurrentRunState();

                // Switch to new run
                this.currentRun = runNumber;
                $(".run-btn").removeClass("active").addClass("btn-secondary");
                $(`.run-btn[data-run="${runNumber}"]`).addClass("active").removeClass("btn-secondary").addClass("btn-primary");

                // Load new run state
                this.loadRunState(runNumber);
                this.updateCurrentRun();
            }

            saveCurrentRunState() {
                // Save mission checkboxes state
                $(".objective-checkbox").each((i, checkbox) => {
                    const $checkbox = $(checkbox);
                    const objective = $checkbox.data("objective");
                    const points = $checkbox.data("points");
                    this.runs[this.currentRun].missions[objective] = $checkbox.is(":checked") ? points : 0;
                });
            }

            loadRunState(runNumber) {
                const run = this.runs[runNumber];

                // Load mission checkboxes
                $(".objective-checkbox").each((i, checkbox) => {
                    const $checkbox = $(checkbox);
                    const objective = $checkbox.data("objective");
                    $checkbox.prop("checked", run.missions[objective] > 0);
                });

                this.updateRunScore();
            }

            startTimer() {
                if (!this.isRunning) {
                    this.startTime = Date.now() - this.runs[this.currentRun].time;
                    this.isRunning = true;
                    this.runs[this.currentRun].status = "in_progress";
                    this.timer = setInterval(() => this.updateTimer(), 100);

                    $("#start-run").prop("disabled", true);
                    $("#pause-run").prop("disabled", false);
                    $("#run-status").text("Run in progress...");
                }
            }

            pauseTimer() {
                if (this.isRunning) {
                    clearInterval(this.timer);
                    this.isRunning = false;
                    this.runs[this.currentRun].status = "paused";

                    $("#start-run").prop("disabled", false);
                    $("#pause-run").prop("disabled", true);
                    $("#run-status").text("Run paused");
                }
            }

            endRun() {
                if (this.isRunning) {
                    this.pauseTimer();
                }

                this.runs[this.currentRun].status = "completed";
                $("#run-status").text("Run completed");

                this.updateRunSummary();
                this.determineBestRun();
            }

            resetRun() {
                clearInterval(this.timer);
                this.runs[this.currentRun] = { time: 0, missions: {}, status: "not_started", score: 0 };
                this.isRunning = false;

                // Reset UI
                $("#run-timer").text("00:00");
                $("#run-status").text("Ready to start");
                $(".objective-checkbox").prop("checked", false);

                this.updateRunScore();
                this.updateRunSummary();
            }

            updateTimer() {
                this.runs[this.currentRun].time = Date.now() - this.startTime;
                this.updateTimerDisplay();
            }

            updateTimerDisplay() {
                const time = this.runs[this.currentRun].time;
                const seconds = Math.floor(time / 1000);
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;

                const display = `${minutes.toString().padStart(2, "0")}:${remainingSeconds.toString().padStart(2, "0")}`;
                $("#run-timer").text(display);
            }

            updateCurrentRun() {
                $("#current-run-number").text(this.currentRun);
                this.updateTimerDisplay();
                $("#run-status").text(this.runs[this.currentRun].status.replace("_", " "));
            }

            updateRunScore() {
                const run = this.runs[this.currentRun];
                const missionScore = Object.values(run.missions).reduce((sum, points) => sum + points, 0);
                run.score = missionScore;

                // Update display
                this.updateRunSummary();
            }

            updateRunSummary() {
                for (let i = 1; i <= 3; i++) {
                    const run = this.runs[i];
                    const summary = $(`#run-${i}-summary`);

                    const timeDisplay = run.time > 0 ? this.formatTime(run.time) : "--:--";
                    summary.find(".time-value").text(timeDisplay);
                    summary.find(".mission-score").text(Object.values(run.missions).reduce((sum, points) => sum + points, 0));
                    summary.find(".total-score").text(run.score);
                    summary.find(".completion-status").text(run.status.replace("_", " "));
                }
            }

            determineBestRun() {
                let bestTime = Infinity;
                let bestRunNumber = null;

                for (let i = 1; i <= 3; i++) {
                    const run = this.runs[i];
                    if (run.status === "completed" && run.time > 0 && run.time < bestTime) {
                        bestTime = run.time;
                        bestRunNumber = i;
                    }
                }

                if (bestRunNumber) {
                    this.bestRun = bestRunNumber;
                    $("#best-run").text(`Run ${bestRunNumber}`);
                    $("#best-time").text(this.formatTime(bestTime));
                    $("#final-gameplay-score").text(this.runs[bestRunNumber].score);
                } else {
                    $("#best-run").text("TBD");
                    $("#best-time").text("--:--");
                    $("#final-gameplay-score").text("0");
                }
            }

            formatTime(milliseconds) {
                const seconds = Math.floor(milliseconds / 1000);
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                return `${minutes.toString().padStart(2, "0")}:${remainingSeconds.toString().padStart(2, "0")}`;
            }

            getScores() {
                return {
                    judging_mode: "gameplay",
                    runs_data: this.runs,
                    best_run_number: this.bestRun,
                    best_run_time_seconds: this.bestRun ? Math.floor(this.runs[this.bestRun].time / 1000) : null,
                    final_score: this.bestRun ? this.runs[this.bestRun].score : 0,
                    interface_type: "gameplay"
                };
            }
        }

        $(document).ready(() => {
            window.gameplayScoring = new GameplayScoringInterface();
        });
        ';
    }

    private function generatePresentationInterfaceCSS()
    {
        return '
        .presentation-scoring-interface {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 12px;
            color: #2d3748;
        }

        .presentation-header {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .presentation-timer {
            text-align: center;
        }

        .timer-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d3748;
            font-family: monospace;
            margin-bottom: 10px;
        }

        .timer-display.warning {
            color: #d69e2e;
        }

        .timer-display.overtime {
            color: #e53e3e;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .timer-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 10px;
        }

        .time-limit {
            font-size: 0.9rem;
            color: #718096;
        }

        .presentation-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #2d3748;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .presentation-levels {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .presentation-level {
            display: block;
            cursor: pointer;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            background: #f8faff;
            transition: all 0.3s ease;
        }

        .presentation-level:hover {
            border-color: #4facfe;
            background: #ebf8ff;
        }

        .presentation-level.selected {
            border-color: #4facfe;
            background: #ebf8ff;
            box-shadow: 0 4px 12px rgba(79, 172, 254, 0.2);
        }

        .presentation-level input[type="radio"] {
            display: none;
        }

        .level-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .level-name {
            font-weight: 600;
            color: #2d3748;
        }

        .level-points {
            background: #4facfe;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .level-description {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .level-indicators {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .level-indicators li {
            color: #4a5568;
            font-size: 0.8rem;
            padding: 2px 0;
            position: relative;
            padding-left: 15px;
        }

        .level-indicators li:before {
            content: "•";
            color: #4facfe;
            position: absolute;
            left: 0;
        }

        .section-notes textarea {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 60px;
        }

        .presentation-total {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .score-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .score-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: #f8faff;
            border-radius: 8px;
        }

        .total-score-display {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        ';
    }

    private function generateGameplayInterfaceCSS()
    {
        return '
        .gameplay-scoring-interface {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }

        .gameplay-header {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .run-selector {
            display: flex;
            gap: 10px;
        }

        .run-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .run-btn.active {
            background: #4facfe;
            color: white;
        }

        .current-run-display {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }

        .run-timer .timer-display {
            font-size: 3rem;
            font-weight: bold;
            font-family: monospace;
            margin: 20px 0;
            color: #4facfe;
        }

        .timer-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }

        .mission-objectives {
            background: rgba(255,255,255,0.95);
            color: #2d3748;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .objectives-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .objective-item {
            background: #f8faff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }

        .objective-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .objective-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #4facfe;
        }

        .objective-points {
            color: #4facfe;
            font-weight: 600;
            margin-left: auto;
        }

        .objective-description {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #718096;
        }

        .run-results {
            background: rgba(255,255,255,0.95);
            color: #2d3748;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .runs-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .run-summary {
            background: #f8faff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .run-summary h5 {
            margin-bottom: 10px;
            color: #2d3748;
        }

        .run-time, .run-mission-score, .run-total-score, .run-completion {
            margin: 8px 0;
            font-size: 0.9rem;
        }

        .best-run-display {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .best-run-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .best-run-number, .best-run-time, .best-run-score {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
        }
        ';
    }
}