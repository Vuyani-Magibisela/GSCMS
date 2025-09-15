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
}