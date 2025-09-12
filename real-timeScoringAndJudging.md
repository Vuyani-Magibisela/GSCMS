#  REAL-TIME SCORING & JUDGING - Detailed Execution Plan
## Overview

Based on your competition structure, the real-time scoring system needs to handle the SciBOTICS Finals at Sci-Bono with 216 learners (6 teams per category), multiple judges, and a live audience of 500+ people. The system must provide instant updates, handle conflicts, and deliver an engaging experience for both judges and spectators.

## Competition Context
- Finals Date: September 27, 2025
- Venue: Sci-Bono Discovery Centre
- Participants: 216 learners (54 teams)
- Audience: 500+ guests including VIPs, officials, partners
- Categories: 9 competition categories (JUNIOR, EXPLORERS, ARDUINO, INVENTOR variants)
---
## 1. LIVE SCORING INTERFACE
### 1.1 Real-Time Infrastructure Setup
```sql
-- Real-time scoring tables
CREATE TABLE live_scoring_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competition_id INT NOT NULL,
    session_name VARCHAR(200) NOT NULL,
    session_type ENUM('practice', 'qualifying', 'semifinal', 'final') NOT NULL,
    category_id INT NOT NULL,
    venue_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    status ENUM('scheduled', 'active', 'paused', 'completed') DEFAULT 'scheduled',
    live_stream_url VARCHAR(500) NULL,
    spectator_access_code VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    INDEX idx_status (status),
    INDEX idx_competition (competition_id, status)
);

-- Real-time score updates
CREATE TABLE live_score_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    team_id INT NOT NULL,
    judge_id INT NOT NULL,
    criteria_id INT NOT NULL,
    score_value DECIMAL(10,2) NOT NULL,
    previous_value DECIMAL(10,2) NULL,
    update_type ENUM('initial', 'correction', 'final', 'disputed') DEFAULT 'initial',
    client_timestamp TIMESTAMP NOT NULL,
    server_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sync_status ENUM('pending', 'synced', 'conflict', 'resolved') DEFAULT 'pending',
    conflict_resolution TEXT NULL,
    FOREIGN KEY (session_id) REFERENCES live_scoring_sessions(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (judge_id) REFERENCES judge_profiles(id),
    FOREIGN KEY (criteria_id) REFERENCES rubric_criteria(id),
    INDEX idx_session_team (session_id, team_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_timestamp (server_timestamp)
);

-- WebSocket connections tracking
CREATE TABLE websocket_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    connection_id VARCHAR(100) UNIQUE NOT NULL,
    user_id INT NULL,
    user_type ENUM('judge', 'admin', 'spectator', 'team') NOT NULL,
    session_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_ping TIMESTAMP NULL,
    disconnected_at TIMESTAMP NULL,
    total_messages_sent INT DEFAULT 0,
    total_messages_received INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (session_id) REFERENCES live_scoring_sessions(id),
    INDEX idx_session (session_id),
    INDEX idx_connection (connection_id)
);
```
### 1.2 WebSocket Server Implementation
```php
// app/Services/WebSocketServer.php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ScoringWebSocketServer implements MessageComponentInterface {
    
    protected $clients;
    protected $sessions;
    protected $judges;
    protected $subscribers;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->sessions = [];
        $this->judges = [];
        $this->subscribers = [];
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // Parse connection parameters
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);
        
        // Authenticate connection
        $authToken = $params['token'] ?? null;
        $sessionId = $params['session'] ?? null;
        
        if ($this->authenticateConnection($authToken, $sessionId)) {
            $this->registerConnection($conn, $params);
            $this->sendInitialState($conn, $sessionId);
            
            echo "New connection: {$conn->resourceId}\n";
        } else {
            $conn->close();
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        switch ($data['type']) {
            case 'score_update':
                $this->handleScoreUpdate($from, $data);
                break;
                
            case 'judge_ready':
                $this->handleJudgeReady($from, $data);
                break;
                
            case 'request_sync':
                $this->handleSyncRequest($from, $data);
                break;
                
            case 'conflict_detected':
                $this->handleConflict($from, $data);
                break;
                
            case 'subscribe':
                $this->handleSubscription($from, $data);
                break;
                
            case 'ping':
                $this->handlePing($from);
                break;
        }
    }
    
    private function handleScoreUpdate($conn, $data) {
        // Validate score update
        if (!$this->validateScoreUpdate($data)) {
            $this->sendError($conn, 'Invalid score data');
            return;
        }
        
        // Check for conflicts with other judges
        $conflicts = $this->detectConflicts($data);
        
        if (!empty($conflicts)) {
            // Notify about conflict
            $this->broadcastConflict($data['session_id'], $conflicts);
            return;
        }
        
        // Store update
        $updateId = $this->storeScoreUpdate($data);
        
        // Broadcast to all subscribers
        $this->broadcastScoreUpdate($data['session_id'], [
            'update_id' => $updateId,
            'team_id' => $data['team_id'],
            'judge_id' => $data['judge_id'],
            'criteria_id' => $data['criteria_id'],
            'score' => $data['score'],
            'timestamp' => microtime(true)
        ]);
        
        // Update aggregated scores
        $this->updateAggregatedScores($data['team_id'], $data['session_id']);
    }
    
    private function broadcastScoreUpdate($sessionId, $update) {
        $message = json_encode([
            'type' => 'score_update',
            'data' => $update
        ]);
        
        // Send to all subscribers of this session
        foreach ($this->subscribers[$sessionId] ?? [] as $client) {
            $client->send($message);
        }
        
        // Log broadcast
        $this->logBroadcast($sessionId, 'score_update', count($this->subscribers[$sessionId] ?? []));
    }
    
    private function detectConflicts($data) {
        $conflicts = [];
        
        // Get other judges' scores for same criteria
        $otherScores = LiveScoreUpdate::where('session_id', $data['session_id'])
            ->where('team_id', $data['team_id'])
            ->where('criteria_id', $data['criteria_id'])
            ->where('judge_id', '!=', $data['judge_id'])
            ->where('update_type', '!=', 'disputed')
            ->get();
        
        foreach ($otherScores as $otherScore) {
            $deviation = abs($data['score'] - $otherScore->score_value);
            $maxDeviation = $this->getMaxAllowedDeviation($data['criteria_id']);
            
            if ($deviation > $maxDeviation) {
                $conflicts[] = [
                    'judge_id' => $otherScore->judge_id,
                    'their_score' => $otherScore->score_value,
                    'your_score' => $data['score'],
                    'deviation' => $deviation,
                    'max_allowed' => $maxDeviation
                ];
            }
        }
        
        return $conflicts;
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->unregisterConnection($conn);
        echo "Connection {$conn->resourceId} disconnected\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}
```
### 1.3 Real-Time Scoring Interface (Frontend)
```javascript
// public/js/real-time-scoring.js
class RealTimeScoring {
    constructor() {
        this.ws = null;
        this.sessionId = null;
        this.judgeId = null;
        this.currentTeam = null;
        this.scores = {};
        this.conflicts = [];
        this.syncStatus = 'disconnected';
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }
    
    init(sessionId, judgeId) {
        this.sessionId = sessionId;
        this.judgeId = judgeId;
        
        this.connect();
        this.setupUI();
        this.bindEvents();
    }
    
    connect() {
        const wsUrl = `wss://${window.location.host}/ws?token=${this.getAuthToken()}&session=${this.sessionId}`;
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.syncStatus = 'connected';
                this.reconnectAttempts = 0;
                this.updateConnectionStatus();
                this.sendJudgeReady();
            };
            
            this.ws.onmessage = (event) => {
                this.handleMessage(JSON.parse(event.data));
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.handleConnectionError();
            };
            
            this.ws.onclose = () => {
                this.syncStatus = 'disconnected';
                this.updateConnectionStatus();
                this.attemptReconnect();
            };
            
        } catch (error) {
            console.error('Failed to create WebSocket:', error);
        }
    }
    
    handleMessage(message) {
        switch (message.type) {
            case 'initial_state':
                this.loadInitialState(message.data);
                break;
                
            case 'score_update':
                this.handleScoreUpdate(message.data);
                break;
                
            case 'conflict_detected':
                this.handleConflict(message.data);
                break;
                
            case 'team_change':
                this.handleTeamChange(message.data);
                break;
                
            case 'session_status':
                this.handleSessionStatus(message.data);
                break;
                
            case 'sync_complete':
                this.handleSyncComplete(message.data);
                break;
        }
    }
    
    setupUI() {
        const template = `
            <div class="real-time-scoring">
                <div class="scoring-header">
                    <div class="connection-status">
                        <span class="status-indicator ${this.syncStatus}"></span>
                        <span class="status-text">${this.syncStatus}</span>
                    </div>
                    <div class="session-info">
                        <h3>Live Scoring Session</h3>
                        <span class="session-id">Session: ${this.sessionId}</span>
                    </div>
                    <div class="sync-controls">
                        <button id="force-sync" class="btn btn-sm btn-secondary">
                            <i class="fas fa-sync"></i> Force Sync
                        </button>
                    </div>
                </div>
                
                <div class="team-selector">
                    <div class="team-queue">
                        <h4>Team Queue</h4>
                        <div id="team-list"></div>
                    </div>
                    <div class="current-team">
                        <h4>Currently Scoring</h4>
                        <div id="current-team-info"></div>
                    </div>
                </div>
                
                <div class="scoring-panel">
                    <div class="rubric-container" id="live-rubric">
                        <!-- Dynamic rubric loaded here -->
                    </div>
                    
                    <div class="score-summary">
                        <div class="live-total">
                            <span class="label">Total Score:</span>
                            <span class="value" id="total-score">0</span>
                        </div>
                        <div class="other-judges">
                            <h5>Other Judges</h5>
                            <div id="other-judge-scores"></div>
                        </div>
                    </div>
                </div>
                
                <div class="conflict-panel" id="conflict-panel" style="display:none;">
                    <div class="alert alert-warning">
                        <h5>Score Conflict Detected</h5>
                        <div id="conflict-details"></div>
                        <div class="conflict-actions">
                            <button class="btn btn-primary" id="review-score">Review My Score</button>
                            <button class="btn btn-secondary" id="request-head-judge">Request Head Judge</button>
                            <button class="btn btn-success" id="resolve-conflict">Mark as Resolved</button>
                        </div>
                    </div>
                </div>
                
                <div class="scoring-actions">
                    <button class="btn btn-secondary" id="save-draft">
                        <i class="fas fa-save"></i> Save Draft
                    </button>
                    <button class="btn btn-warning" id="pause-scoring">
                        <i class="fas fa-pause"></i> Pause
                    </button>
                    <button class="btn btn-primary" id="submit-scores">
                        <i class="fas fa-check"></i> Submit Scores
                    </button>
                    <button class="btn btn-success" id="next-team">
                        Next Team <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        `;
        
        $('#scoring-interface').html(template);
    }
    
    sendScore(criteriaId, score) {
        if (this.ws.readyState !== WebSocket.OPEN) {
            this.queueForSync({ criteriaId, score });
            return;
        }
        
        const message = {
            type: 'score_update',
            session_id: this.sessionId,
            team_id: this.currentTeam.id,
            judge_id: this.judgeId,
            criteria_id: criteriaId,
            score: score,
            timestamp: Date.now()
        };
        
        this.ws.send(JSON.stringify(message));
        
        // Update local state
        this.scores[criteriaId] = score;
        this.updateTotalScore();
    }
    
    handleConflict(conflictData) {
        this.conflicts.push(conflictData);
        
        // Show conflict panel
        $('#conflict-panel').show();
        
        // Display conflict details
        const detailsHtml = `
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Judge</th>
                        <th>Their Score</th>
                        <th>Your Score</th>
                        <th>Difference</th>
                    </tr>
                </thead>
                <tbody>
                    ${conflictData.conflicts.map(c => `
                        <tr>
                            <td>${c.judge_name}</td>
                            <td>${c.their_score}</td>
                            <td>${c.your_score}</td>
                            <td class="text-danger">${c.deviation}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        $('#conflict-details').html(detailsHtml);
        
        // Play alert sound
        this.playAlert();
        
        // Highlight conflicting criteria
        $(`.criteria-${conflictData.criteria_id}`).addClass('conflict');
    }
    
    attemptReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.showOfflineMode();
            return;
        }
        
        this.reconnectAttempts++;
        
        setTimeout(() => {
            console.log(`Reconnection attempt ${this.reconnectAttempts}`);
            this.connect();
        }, 2000 * this.reconnectAttempts);
    }
    
    showOfflineMode() {
        const modal = `
            <div class="modal" id="offline-modal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">Offline Scoring Mode</h5>
                        </div>
                        <div class="modal-body">
                            <p>Connection lost. You can continue scoring offline.</p>
                            <p>Your scores will be synchronized when connection is restored.</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" onclick="realTimeScoring.continueOffline()">
                                Continue Offline
                            </button>
                            <button class="btn btn-secondary" onclick="realTimeScoring.retryConnection()">
                                Retry Connection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modal);
        $('#offline-modal').modal('show');
    }
}
```

## 2. CATEGORY-SPECIFIC RUBRICS IMPLEMENTATION
### 2.1 Dynamic Rubric Loader
```php
// app/Services/CategoryRubricService.php
class CategoryRubricService {
    
    const CATEGORY_RUBRICS = [
        'JUNIOR' => [
            'interface_type' => 'visual',
            'scoring_method' => 'emoji',
            'sections' => [
                'robot_performance' => [
                    'weight' => 40,
                    'display' => 'emoji_scale',
                    'levels' => [
                        1 => ['emoji' => '😟', 'text' => 'Needs Practice'],
                        2 => ['emoji' => '😐', 'text' => 'Getting There'],
                        3 => ['emoji' => '😊', 'text' => 'Good Job'],
                        4 => ['emoji' => '🌟', 'text' => 'Amazing']
                    ]
                ],
                'teamwork' => [
                    'weight' => 30,
                    'display' => 'visual_cards'
                ],
                'creativity' => [
                    'weight' => 30,
                    'display' => 'star_rating'
                ]
            ]
        ],
        'SPIKE_INTERMEDIATE' => [
            'interface_type' => 'standard',
            'scoring_method' => 'points',
            'sections' => [
                'programming' => [
                    'weight' => 35,
                    'sub_criteria' => [
                        'code_structure' => 10,
                        'algorithm_efficiency' => 15,
                        'error_handling' => 10
                    ]
                ],
                'mission_completion' => [
                    'weight' => 35,
                    'checkpoints' => [
                        'start_position' => 5,
                        'navigation' => 10,
                        'task_execution' => 15,
                        'return_home' => 5
                    ]
                ],
                'innovation' => ['weight' => 30]
            ]
        ],
        'ARDUINO' => [
            'interface_type' => 'technical',
            'scoring_method' => 'detailed',
            'sections' => [
                'technical_implementation' => [
                    'weight' => 40,
                    'metrics' => [
                        'complexity_score' => 'calculated',
                        'optimization_level' => 'assessed',
                        'documentation_quality' => 'reviewed'
                    ]
                ],
                'hardware_integration' => ['weight' => 30],
                'performance_metrics' => ['weight' => 30]
            ]
        ]
    ];
    
    public function getRubricForCategory($categoryId, $sessionType = 'final') {
        $category = Category::find($categoryId);
        $rubricConfig = self::CATEGORY_RUBRICS[$category->name] ?? null;
        
        if (!$rubricConfig) {
            throw new Exception("No rubric configured for category: {$category->name}");
        }
        
        // Load rubric template
        $template = RubricTemplate::where('category_id', $categoryId)
                                 ->where('is_active', true)
                                 ->first();
        
        // Merge with configuration
        return $this->mergeRubricWithConfig($template, $rubricConfig, $sessionType);
    }
    
    public function generateScoringInterface($categoryId) {
        $rubric = $this->getRubricForCategory($categoryId);
        
        switch ($rubric['interface_type']) {
            case 'visual':
                return $this->generateVisualInterface($rubric);
            case 'standard':
                return $this->generateStandardInterface($rubric);
            case 'technical':
                return $this->generateTechnicalInterface($rubric);
            default:
                return $this->generateStandardInterface($rubric);
        }
    }
}
```
## 2. CATEGORY-SPECIFIC RUBRICS IMPLEMENTATION
### 2.1 Dynamic Rubric Loader
```php
// app/Services/CategoryRubricService.php
class CategoryRubricService {
    
    const CATEGORY_RUBRICS = [
        'JUNIOR' => [
            'interface_type' => 'visual',
            'scoring_method' => 'emoji',
            'sections' => [
                'robot_performance' => [
                    'weight' => 40,
                    'display' => 'emoji_scale',
                    'levels' => [
                        1 => ['emoji' => '😟', 'text' => 'Needs Practice'],
                        2 => ['emoji' => '😐', 'text' => 'Getting There'],
                        3 => ['emoji' => '😊', 'text' => 'Good Job'],
                        4 => ['emoji' => '🌟', 'text' => 'Amazing']
                    ]
                ],
                'teamwork' => [
                    'weight' => 30,
                    'display' => 'visual_cards'
                ],
                'creativity' => [
                    'weight' => 30,
                    'display' => 'star_rating'
                ]
            ]
        ],
        'SPIKE_INTERMEDIATE' => [
            'interface_type' => 'standard',
            'scoring_method' => 'points',
            'sections' => [
                'programming' => [
                    'weight' => 35,
                    'sub_criteria' => [
                        'code_structure' => 10,
                        'algorithm_efficiency' => 15,
                        'error_handling' => 10
                    ]
                ],
                'mission_completion' => [
                    'weight' => 35,
                    'checkpoints' => [
                        'start_position' => 5,
                        'navigation' => 10,
                        'task_execution' => 15,
                        'return_home' => 5
                    ]
                ],
                'innovation' => ['weight' => 30]
            ]
        ],
        'ARDUINO' => [
            'interface_type' => 'technical',
            'scoring_method' => 'detailed',
            'sections' => [
                'technical_implementation' => [
                    'weight' => 40,
                    'metrics' => [
                        'complexity_score' => 'calculated',
                        'optimization_level' => 'assessed',
                        'documentation_quality' => 'reviewed'
                    ]
                ],
                'hardware_integration' => ['weight' => 30],
                'performance_metrics' => ['weight' => 30]
            ]
        ]
    ];
    
    public function getRubricForCategory($categoryId, $sessionType = 'final') {
        $category = Category::find($categoryId);
        $rubricConfig = self::CATEGORY_RUBRICS[$category->name] ?? null;
        
        if (!$rubricConfig) {
            throw new Exception("No rubric configured for category: {$category->name}");
        }
        
        // Load rubric template
        $template = RubricTemplate::where('category_id', $categoryId)
                                 ->where('is_active', true)
                                 ->first();
        
        // Merge with configuration
        return $this->mergeRubricWithConfig($template, $rubricConfig, $sessionType);
    }
    
    public function generateScoringInterface($categoryId) {
        $rubric = $this->getRubricForCategory($categoryId);
        
        switch ($rubric['interface_type']) {
            case 'visual':
                return $this->generateVisualInterface($rubric);
            case 'standard':
                return $this->generateStandardInterface($rubric);
            case 'technical':
                return $this->generateTechnicalInterface($rubric);
            default:
                return $this->generateStandardInterface($rubric);
        }
    }
}
```
### 2.2 Category-Specific Scoring Components
```javascript
// public/js/category-scoring-components.js
class CategoryScoringComponents {
    
    static renderJuniorInterface(rubric) {
        return `
            <div class="junior-scoring-interface">
                <div class="visual-header">
                    <h3>How did ${rubric.team_name} do? 🤖</h3>
                    <div class="team-photo">
                        <img src="${rubric.team_photo}" alt="Team Photo" />
                    </div>
                </div>
                
                ${rubric.sections.map(section => `
                    <div class="scoring-section visual-section">
                        <h4>${section.name} ${section.icon}</h4>
                        
                        ${section.display === 'emoji_scale' ? `
                            <div class="emoji-scale" data-criteria-id="${section.id}">
                                ${section.levels.map(level => `
                                    <button class="emoji-button" 
                                            data-level="${level.value}"
                                            data-points="${level.points}">
                                        <span class="emoji">${level.emoji}</span>
                                        <span class="label">${level.text}</span>
                                    </button>
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        ${section.display === 'visual_cards' ? `
                            <div class="visual-cards" data-criteria-id="${section.id}">
                                ${section.levels.map(level => `
                                    <div class="visual-card" 
                                         data-level="${level.value}"
                                         data-points="${level.points}">
                                        <img src="${level.image}" alt="${level.text}" />
                                        <p>${level.text}</p>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        <div class="section-feedback">
                            <p class="feedback-text"></p>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                `).join('')}
                
                <div class="total-score-visual">
                    <div class="score-stars">
                        ${[1,2,3,4,5].map(i => `
                            <i class="fas fa-star star-${i}"></i>
                        `).join('')}
                    </div>
                    <h2 class="total-points">0 / 100</h2>
                </div>
            </div>
        `;
    }
    
    static renderArduinoInterface(rubric) {
        return `
            <div class="arduino-scoring-interface">
                <div class="technical-header">
                    <h3>Technical Assessment - ${rubric.team_name}</h3>
                    <div class="timer-display">
                        <i class="fas fa-stopwatch"></i>
                        <span id="execution-time">00:00</span>
                    </div>
                </div>
                
                <div class="code-analysis-section">
                    <h4>Code Quality Analysis</h4>
                    <div class="metrics-grid">
                        ${rubric.technical_metrics.map(metric => `
                            <div class="metric-card">
                                <label>${metric.name}</label>
                                <input type="range" 
                                       class="form-range technical-slider"
                                       data-criteria-id="${metric.id}"
                                       min="${metric.min}"
                                       max="${metric.max}"
                                       value="${metric.default}"
                                       step="${metric.step}">
                                <div class="metric-value">
                                    <span class="current-value">${metric.default}</span>
                                    <span class="max-value">/ ${metric.max}</span>
                                </div>
                                <small class="metric-description">${metric.description}</small>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="mission-checkpoints">
                    <h4>Mission Checkpoints</h4>
                    <div class="checkpoint-list">
                        ${rubric.checkpoints.map((checkpoint, index) => `
                            <div class="checkpoint-item" data-checkpoint-id="${checkpoint.id}">
                                <div class="checkpoint-number">${index + 1}</div>
                                <div class="checkpoint-details">
                                    <h5>${checkpoint.name}</h5>
                                    <div class="checkpoint-options">
                                        <label class="radio-option">
                                            <input type="radio" name="checkpoint-${checkpoint.id}" value="0">
                                            <span>Failed (0 pts)</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="checkpoint-${checkpoint.id}" value="${checkpoint.partial}">
                                            <span>Partial (${checkpoint.partial} pts)</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="checkpoint-${checkpoint.id}" value="${checkpoint.full}">
                                            <span>Complete (${checkpoint.full} pts)</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="checkpoint-score">
                                    <span class="score-value">0</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="optimization-bonus">
                    <h4>Performance Optimization</h4>
                    <div class="bonus-criteria">
                        <div class="time-bonus">
                            <label>Completion Time (seconds)</label>
                            <input type="number" id="completion-time" class="form-control" />
                            <span class="bonus-calculation">Bonus: +<span id="time-bonus">0</span> pts</span>
                        </div>
                        <div class="efficiency-bonus">
                            <label>Code Efficiency Score</label>
                            <select id="efficiency-score" class="form-control">
                                <option value="0">Standard</option>
                                <option value="5">Optimized</option>
                                <option value="10">Highly Optimized</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="technical-notes-section">
                    <h4>Technical Observations</h4>
                    <div class="quick-tags">
                        <button class="tag-btn" data-tag="innovative-approach">Innovative Approach</button>
                        <button class="tag-btn" data-tag="clean-code">Clean Code</button>
                        <button class="tag-btn" data-tag="error-handling">Good Error Handling</button>
                        <button class="tag-btn" data-tag="needs-optimization">Needs Optimization</button>
                    </div>
                    <textarea id="technical-notes" class="form-control" rows="4"
                              placeholder="Additional technical observations..."></textarea>
                </div>
            </div>
        `;
    }
}
```
## 3. LIVE SCOREBOARD SYSTEM
### 3.1 Public Display Infrastructure
```php
// app/Services/LiveScoreboardService.php
class LiveScoreboardService {
    
    private $redis;
    private $updateInterval = 2; // seconds
    
    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    public function updateScoreboard($sessionId) {
        // Get current standings
        $standings = $this->calculateStandings($sessionId);
        
        // Store in Redis for fast access
        $this->redis->setex(
            "scoreboard:{$sessionId}",
            60,
            json_encode($standings)
        );
        
        // Broadcast update via WebSocket
        $this->broadcastUpdate($sessionId, $standings);
        
        // Update social media if configured
        if ($this->shouldUpdateSocialMedia($standings)) {
            $this->updateSocialMedia($standings);
        }
        
        return $standings;
    }
    
    private function calculateStandings($sessionId) {
        $teams = DB::select("
            SELECT 
                t.id,
                t.name,
                s.name as school_name,
                c.name as category,
                COALESCE(agg.total_score, 0) as total_score,
                COALESCE(agg.game_challenge_score, 0) as game_score,
                COALESCE(agg.research_challenge_score, 0) as research_score,
                agg.num_judges,
                agg.finalized,
                RANK() OVER (PARTITION BY t.category_id ORDER BY agg.total_score DESC) as rank,
                LAG(agg.total_score, 1) OVER (PARTITION BY t.category_id ORDER BY agg.total_score DESC) - agg.total_score as points_behind
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN aggregated_scores agg ON t.id = agg.team_id
            WHERE t.competition_id = (
                SELECT competition_id FROM live_scoring_sessions WHERE id = ?
            )
            ORDER BY c.display_order, rank
        ", [$sessionId]);
        
        return $this->formatStandings($teams);
    }
    
    public function getPublicScoreboard($sessionId, $displayMode = 'standard') {
        // Check cache first
        $cached = $this->redis->get("scoreboard:{$sessionId}");
        
        if ($cached) {
            $standings = json_decode($cached, true);
        } else {
            $standings = $this->updateScoreboard($sessionId);
        }
        
        // Format for display mode
        switch ($displayMode) {
            case 'tv':
                return $this->formatForTV($standings);
            case 'mobile':
                return $this->formatForMobile($standings);
            case 'social':
                return $this->formatForSocial($standings);
            default:
                return $standings;
        }
    }
}
```
## 3. LIVE SCOREBOARD SYSTEM
### 3.1 Public Display Infrastructure
```php
// app/Services/LiveScoreboardService.php
class LiveScoreboardService {
    
    private $redis;
    private $updateInterval = 2; // seconds
    
    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    public function updateScoreboard($sessionId) {
        // Get current standings
        $standings = $this->calculateStandings($sessionId);
        
        // Store in Redis for fast access
        $this->redis->setex(
            "scoreboard:{$sessionId}",
            60,
            json_encode($standings)
        );
        
        // Broadcast update via WebSocket
        $this->broadcastUpdate($sessionId, $standings);
        
        // Update social media if configured
        if ($this->shouldUpdateSocialMedia($standings)) {
            $this->updateSocialMedia($standings);
        }
        
        return $standings;
    }
    
    private function calculateStandings($sessionId) {
        $teams = DB::select("
            SELECT 
                t.id,
                t.name,
                s.name as school_name,
                c.name as category,
                COALESCE(agg.total_score, 0) as total_score,
                COALESCE(agg.game_challenge_score, 0) as game_score,
                COALESCE(agg.research_challenge_score, 0) as research_score,
                agg.num_judges,
                agg.finalized,
                RANK() OVER (PARTITION BY t.category_id ORDER BY agg.total_score DESC) as rank,
                LAG(agg.total_score, 1) OVER (PARTITION BY t.category_id ORDER BY agg.total_score DESC) - agg.total_score as points_behind
            FROM teams t
            JOIN schools s ON t.school_id = s.id
            JOIN categories c ON t.category_id = c.id
            LEFT JOIN aggregated_scores agg ON t.id = agg.team_id
            WHERE t.competition_id = (
                SELECT competition_id FROM live_scoring_sessions WHERE id = ?
            )
            ORDER BY c.display_order, rank
        ", [$sessionId]);
        
        return $this->formatStandings($teams);
    }
    
    public function getPublicScoreboard($sessionId, $displayMode = 'standard') {
        // Check cache first
        $cached = $this->redis->get("scoreboard:{$sessionId}");
        
        if ($cached) {
            $standings = json_decode($cached, true);
        } else {
            $standings = $this->updateScoreboard($sessionId);
        }
        
        // Format for display mode
        switch ($displayMode) {
            case 'tv':
                return $this->formatForTV($standings);
            case 'mobile':
                return $this->formatForMobile($standings);
            case 'social':
                return $this->formatForSocial($standings);
            default:
                return $standings;
        }
    }
}
```
### 3.2 Live Scoreboard UI
```javascript
// public/js/live-scoreboard.js
class LiveScoreboard {
    constructor() {
        this.ws = null;
        this.sessionId = null;
        this.displayMode = 'standard';
        this.autoRotate = false;
        this.currentCategory = 'all';
        this.updateQueue = [];
        this.animationDuration = 1000;
    }
    
    init(sessionId, mode = 'standard') {
        this.sessionId = sessionId;
        this.displayMode = mode;
        
        this.setupDisplay();
        this.connectWebSocket();
        this.loadInitialData();
        
        if (mode === 'tv') {
            this.startAutoRotate();
        }
    }
    
    setupDisplay() {
        const template = this.getTemplate();
        $('#scoreboard-container').html(template);
        
        // Initialize animations
        this.initAnimations();
    }
    
    getTemplate() {
        if (this.displayMode === 'tv') {
            return this.getTVTemplate();
        } else if (this.displayMode === 'mobile') {
            return this.getMobileTemplate();
        }
        
        return `
            <div class="live-scoreboard">
                <div class="scoreboard-header">
                    <div class="competition-logo">
                        <img src="/images/scibotics-logo.png" alt="SciBOTICS" />
                    </div>
                    <div class="competition-title">
                        <h1>GDE SciBOTICS Competition 2025</h1>
                        <h2>Live Finals Scoreboard</h2>
                    </div>
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        <span class="live-text">LIVE</span>
                    </div>
                </div>
                
                <div class="category-selector">
                    <button class="category-btn active" data-category="all">All Categories</button>
                    <button class="category-btn" data-category="JUNIOR">JUNIOR</button>
                    <button class="category-btn" data-category="SPIKE">SPIKE</button>
                    <button class="category-btn" data-category="ARDUINO">ARDUINO</button>
                    <button class="category-btn" data-category="INVENTOR">INVENTOR</button>
                </div>
                
                <div class="scoreboard-content">
                    <div class="standings-grid" id="standings-grid">
                        <!-- Dynamic content -->
                    </div>
                    
                    <div class="scoreboard-sidebar">
                        <div class="recent-updates">
                            <h3>Recent Updates</h3>
                            <div id="update-feed"></div>
                        </div>
                        
                        <div class="top-performers">
                            <h3>Top Performers</h3>
                            <div id="top-teams"></div>
                        </div>
                        
                        <div class="competition-stats">
                            <h3>Competition Stats</h3>
                            <div class="stat-item">
                                <span class="stat-label">Teams Competing:</span>
                                <span class="stat-value" id="total-teams">54</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Judges Active:</span>
                                <span class="stat-value" id="active-judges">12</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Scores Submitted:</span>
                                <span class="stat-value" id="scores-submitted">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="scoreboard-footer">
                    <div class="social-share">
                        <button class="share-btn" data-platform="twitter">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button class="share-btn" data-platform="facebook">
                            <i class="fab fa-facebook"></i>
                        </button>
                        <button class="share-btn" data-platform="instagram">
                            <i class="fab fa-instagram"></i>
                        </button>
                    </div>
                    <div class="qr-code">
                        <img src="/api/qr/scoreboard/${this.sessionId}" alt="QR Code" />
                        <span>Scan to view on mobile</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    connectWebSocket() {
        const wsUrl = `wss://${window.location.host}/scoreboard?session=${this.sessionId}`;
        
        this.ws = new WebSocket(wsUrl);
        
        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleUpdate(data);
        };
    }
    
    handleUpdate(data) {
        switch (data.type) {
            case 'score_update':
                this.animateScoreUpdate(data.team_id, data.new_score);
                break;
                
            case 'rank_change':
                this.animateRankChange(data.team_id, data.old_rank, data.new_rank);
                break;
                
            case 'milestone':
                this.showMilestone(data);
                break;
        }
        
        // Add to update feed
        this.addToUpdateFeed(data);
    }
    
    animateScoreUpdate(teamId, newScore) {
        const teamCard = $(`.team-card[data-team-id="${teamId}"]`);
        const scoreElement = teamCard.find('.team-score');
        const oldScore = parseInt(scoreElement.text());
        
        // Flash effect
        teamCard.addClass('score-updating');
        
        // Animate number
        $({ score: oldScore }).animate({ score: newScore }, {
            duration: this.animationDuration,
            easing: 'swing',
            step: function() {
                scoreElement.text(Math.ceil(this.score));
            },
            complete: function() {
                scoreElement.text(newScore);
                teamCard.removeClass('score-updating');
                
                // Check if rank changed
                this.checkRankChange(teamId);
            }.bind(this)
        });
    }
    
    animateRankChange(teamId, oldRank, newRank) {
        const teamCard = $(`.team-card[data-team-id="${teamId}"]`);
        const direction = newRank < oldRank ? 'up' : 'down';
        const positions = Math.abs(newRank - oldRank);
        
        // Add movement class
        teamCard.addClass(`moving-${direction}`);
        
        // Calculate new position
        const newPosition = this.calculateCardPosition(newRank);
        
        // Animate movement
        teamCard.animate({
            top: newPosition.top,
            left: newPosition.left
        }, {
            duration: this.animationDuration,
            complete: function() {
                teamCard.removeClass(`moving-${direction}`);
                
                // Update rank display
                teamCard.find('.rank-number').text(newRank);
                
                // Show rank change indicator
                const indicator = `
                    <span class="rank-change ${direction}">
                        <i class="fas fa-arrow-${direction}"></i> ${positions}
                    </span>
                `;
                teamCard.find('.rank-display').append(indicator);
                
                setTimeout(() => {
                    teamCard.find('.rank-change').fadeOut();
                }, 3000);
            }
        });
    }
    
    getTVTemplate() {
        return `
            <div class="tv-scoreboard">
                <div class="tv-header">
                    <div class="logo-section">
                        <img src="/images/scibotics-logo-large.png" alt="SciBOTICS" />
                    </div>
                    <div class="event-info">
                        <h1>GDE SciBOTICS FINALS 2025</h1>
                        <div class="venue">Sci-Bono Discovery Centre</div>
                    </div>
                    <div class="sponsor-logos">
                        <img src="/images/sponsors.png" alt="Sponsors" />
                    </div>
                </div>
                
                <div class="tv-content">
                    <div class="main-leaderboard">
                        <div class="leaderboard-category" id="current-category">
                            <h2 class="category-title">JUNIOR CATEGORY</h2>
                            <div class="top-three">
                                <div class="position position-2">
                                    <div class="podium silver"></div>
                                    <div class="team-info">
                                        <span class="position-number">2</span>
                                        <span class="team-name">Team Beta</span>
                                        <span class="school-name">School B</span>
                                        <span class="score">85</span>
                                    </div>
                                </div>
                                <div class="position position-1">
                                    <div class="podium gold"></div>
                                    <div class="team-info">
                                        <span class="position-number">1</span>
                                        <span class="team-name">Team Alpha</span>
                                        <span class="school-name">School A</span>
                                        <span class="score">92</span>
                                    </div>
                                </div>
                                <div class="position position-3">
                                    <div class="podium bronze"></div>
                                    <div class="team-info">
                                        <span class="position-number">3</span>
                                        <span class="team-name">Team Gamma</span>
                                        <span class="school-name">School C</span>
                                        <span class="score">78</span>
                                    </div>
                                </div>
                            </div>
                            <div class="remaining-teams">
                                <!-- Teams 4-6 -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="ticker-section">
                        <div class="ticker-content">
                            <span class="ticker-item">🏆 Team Alpha takes the lead in JUNIOR category!</span>
                            <span class="ticker-item">📈 Team Delta moves up 3 positions!</span>
                            <span class="ticker-item">⏱️ ARDUINO finals starting in 10 minutes</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}
```
## 4. CONFLICT RESOLUTION MECHANISMS
### 4.1 Conflict Detection & Resolution System
```php
// app/Services/ConflictResolutionService.php
class ConflictResolutionService {
    
    const DEVIATION_THRESHOLDS = [
        'JUNIOR' => 20,         // 20% allowed deviation
        'SPIKE' => 15,         // 15% allowed deviation
        'ARDUINO' => 10,       // 10% allowed deviation
        'INVENTOR' => 15       // 15% allowed deviation
    ];
    
    public function detectConflicts($sessionId, $teamId) {
        $conflicts = [];
        
        // Get all scores for this team
        $scores = LiveScoreUpdate::where('session_id', $sessionId)
                                ->where('team_id', $teamId)
                                ->where('sync_status', '!=', 'resolved')
                                ->get()
                                ->groupBy('criteria_id');
        
        foreach ($scores as $criteriaId => $judgeScores) {
            if ($judgeScores->count() < 2) continue;
            
            $analysis = $this->analyzeScores($judgeScores);
            
            if ($analysis['has_conflict']) {
                $conflicts[] = [
                    'criteria_id' => $criteriaId,
                    'analysis' => $analysis,
                    'severity' => $this->calculateSeverity($analysis),
                    'suggested_resolution' => $this->suggestResolution($analysis)
                ];
            }
        }
        
        return $conflicts;
    }
    
    private function analyzeScores($scores) {
        $values = $scores->pluck('score_value')->toArray();
        
        $analysis = [
            'mean' => array_sum($values) / count($values),
            'median' => $this->median($values),
            'std_dev' => $this->standardDeviation($values),
            'range' => max($values) - min($values),
            'outliers' => $this->detectOutliers($values),
            'has_conflict' => false
        ];
        
        // Check for conflicts
        $category = $this->getCategoryFromScore($scores->first());
        $threshold = self::DEVIATION_THRESHOLDS[$category] ?? 15;
        
        foreach ($values as $value) {
            $deviation = abs($value - $analysis['mean']) / $analysis['mean'] * 100;
            if ($deviation > $threshold) {
                $analysis['has_conflict'] = true;
                break;
            }
        }
        
        return $analysis;
    }
    
    public function resolveConflict($conflictId, $resolution) {
        DB::beginTransaction();
        
        try {
            $conflict = ScoreConflict::find($conflictId);
            
            switch ($resolution['method']) {
                case 'use_median':
                    $finalScore = $this->useMedianResolution($conflict);
                    break;
                    
                case 'use_head_judge':
                    $finalScore = $this->useHeadJudgeScore($conflict);
                    break;
                    
                case 'exclude_outliers':
                    $finalScore = $this->excludeOutliers($conflict);
                    break;
                    
                case 'manual_override':
                    $finalScore = $resolution['override_value'];
                    break;
                    
                default:
                    throw new Exception("Invalid resolution method");
            }
            
            // Update scores
            $this->applyResolution($conflict, $finalScore, $resolution);
            
            // Notify judges
            $this->notifyJudgesOfResolution($conflict, $resolution);
            
            DB::commit();
            
            return $finalScore;
            
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    public function escalateToHeadJudge($conflictId) {
        $conflict = ScoreConflict::find($conflictId);
        
        // Find head judge for this session
        $headJudge = JudgeCompetitionAssignment::where('session_id', $conflict->session_id)
                                               ->where('assignment_role', 'head_judge')
                                               ->first();
        
        if (!$headJudge) {
            throw new Exception("No head judge assigned to this session");
        }
        
        // Create escalation ticket
        $escalation = ConflictEscalation::create([
            'conflict_id' => $conflictId,
            'head_judge_id' => $headJudge->judge_id,
            'status' => 'pending',
            'priority' => $this->calculatePriority($conflict),
            'deadline' => now()->addMinutes(10)
        ]);
        
        // Send notification to head judge
        $this->notifyHeadJudge($headJudge, $escalation);
        
        // Set timeout for auto-resolution
        $this->scheduleAutoResolution($escalation);
        
        return $escalation;
    }
}
```
### 4.2 Conflict Resolution UI
```javascript
// public/js/conflict-resolution.js
class ConflictResolution {
    constructor() {
        this.activeConflicts = [];
        this.resolutionHistory = [];
    }
    
    detectConflict(scoreData) {
        $.ajax({
            url: '/api/conflicts/detect',
            method: 'POST',
            data: scoreData,
            success: (response) => {
                if (response.has_conflict) {
                    this.showConflictDialog(response.conflict);
                }
            }
        });
    }
    
    showConflictDialog(conflict) {
        const modal = `
            <div class="modal fade" id="conflict-modal">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle"></i>
                                Score Conflict Detected
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="conflict-details">
                                <h6>Criteria: ${conflict.criteria_name}</h6>
                                <p>Team: ${conflict.team_name}</p>
                                
                                <div class="score-comparison">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Judge</th>
                                                <th>Score</th>
                                                <th>Deviation</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${conflict.scores.map(s => `
                                                <tr class="${s.is_outlier ? 'table-danger' : ''}">
                                                    <td>${s.judge_name}</td>
                                                    <td>${s.score}</td>
                                                    <td>${s.deviation}%</td>
                                                    <td>
                                                        ${s.is_outlier ? 
                                                            '<span class="badge bg-danger">Outlier</span>' : 
                                                            '<span class="badge bg-success">Normal</span>'
                                                        }
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="statistical-analysis">
                                    <h6>Statistical Analysis</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>Mean:</label>
                                            <span>${conflict.analysis.mean}</span>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Median:</label>
                                            <span>${conflict.analysis.median}</span>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Std Dev:</label>
                                            <span>${conflict.analysis.std_dev}</span>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Range:</label>
                                            <span>${conflict.analysis.range}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="resolution-options">
                                    <h6>Resolution Options</h6>
                                    <div class="resolution-methods">
                                        <label class="resolution-option">
                                            <input type="radio" name="resolution" value="use_median">
                                            <span>Use Median Score (${conflict.analysis.median})</span>
                                        </label>
                                        <label class="resolution-option">
                                            <input type="radio" name="resolution" value="exclude_outliers">
                                            <span>Exclude Outliers and Recalculate</span>
                                        </label>
                                        <label class="resolution-option">
                                            <input type="radio" name="resolution" value="head_judge">
                                            <span>Request Head Judge Decision</span>
                                        </label>
                                        <label class="resolution-option">
                                            <input type="radio" name="resolution" value="discussion">
                                            <span>Initiate Judge Discussion</span>
                                        </label>
                                        <label class="resolution-option">
                                            <input type="radio" name="resolution" value="manual">
                                            <span>Manual Override</span>
                                            <input type="number" id="manual-score" class="form-control" 
                                                   placeholder="Enter score" style="display:none;">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button class="btn btn-warning" onclick="conflictResolution.initiateDiscussion()">
                                Start Discussion
                            </button>
                            <button class="btn btn-primary" onclick="conflictResolution.applyResolution()">
                                Apply Resolution
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modal);
        $('#conflict-modal').modal('show');
    }
    
    initiateDiscussion() {
        // Open discussion panel
        const discussionPanel = `
            <div class="discussion-panel">
                <h5>Judge Discussion</h5>
                <div class="discussion-participants">
                    <!-- List of judges -->
                </div>
                <div class="discussion-chat">
                    <div id="chat-messages"></div>
                    <div class="chat-input">
                        <input type="text" id="chat-message" placeholder="Type message...">
                        <button onclick="conflictResolution.sendMessage()">Send</button>
                    </div>
                </div>
                <div class="consensus-voting">
                    <h6>Consensus Score</h6>
                    <input type="number" id="consensus-score" class="form-control">
                    <button class="btn btn-success" onclick="conflictResolution.submitConsensus()">
                        Submit Consensus
                    </button>
                </div>
            </div>
        `;
        
        $('#conflict-modal .modal-body').append(discussionPanel);
    }
}
```
----
# IMPLEMENTATION TIMELINE
## Real-Time Infrastructure

- [ ] Set up WebSocket server
- [ ] Create real-time database tables
- [ ] Implement connection management
- [ ] Build message queue system

## Category-Specific Interfaces

- [ ] Build JUNIOR visual interface
- [ ] Create SPIKE standard interface
- [ ] Develop ARDUINO technical interface
- [ ] Design INVENTOR comprehensive interface

## Live Scoring Logic

- [ ] Implement score synchronization
- [ ] Build offline mode support
- [ ] Create validation system
- [ ] Add auto-save functionality

## Live Scoreboard

- [ ] Design public display layouts
- [ ] Implement real-time updates
- [ ] Add animation effects
- [ ] Create mobile/TV modes

## Conflict Resolution

- [ ] Build conflict detection algorithm
- [ ] Create resolution workflows
- [ ] Implement head judge escalation
- [ ] Add consensus mechanisms

---
# KEY DELIVERABLES
1. Real-Time Scoring Platform

- WebSocket-based live updates
- Offline mode with sync
- Multi-judge coordination
- Category-specific interfaces

2. Engaging Live Scoreboard

- Public display system
- Real-time animations
- Social media integration
- Mobile/TV optimized views

3. Robust Conflict Resolution

- Automatic conflict detection
- Multiple resolution methods
- Head judge escalation
Consensus building tools

4. Performance & Reliability

- Sub-second updates
- 99.9% uptime during competition
- Automatic failover
- Data integrity protection

---
# SUCCESS METRICS
| Metric | Target | Measurement |
| --- | --- | --- |
| Update Latency | `<500ms` | WebSocket ping time |
| Sync Success Rate | `>99%` | Successful syncs/total |
| Conflict Resolution Time |`<2 min` | Average resolution time |
| Scoreboard Accuracy | `100%` | Score verification |
| User Satisfaction | `>4.5/5` | Post-event survey |

This comprehensive Real-time Scoring & Judging system will provide an engaging, accurate, and efficient scoring experience for the SciBOTICS Finals with 500+ attendees, ensuring fair competition and exciting live updates. 
