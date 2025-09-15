<?php
$layout = 'layouts/judge';
ob_start();
?>

<div class="real-time-scoring-interface" data-team-id="<?= $team['id'] ?>" data-competition-id="<?= $competition['id'] ?>">
    <!-- Header with Team Info -->
    <div class="scoring-header">
        <div class="team-section">
            <div class="team-photo-placeholder">
                <i class="fas fa-users team-icon"></i>
            </div>
            <div class="team-details">
                <h1 class="team-name"><?= htmlspecialchars($team['team_name']) ?></h1>
                <div class="team-info">
                    <span class="school-name"><?= htmlspecialchars($team['school_name']) ?></span>
                    <span class="category-badge"><?= htmlspecialchars($team['category_name']) ?></span>
                </div>
                <div class="competition-info">
                    <?= htmlspecialchars($competition['name']) ?>
                </div>
            </div>
        </div>
        
        <div class="scoring-controls">
            <div class="connection-status" id="connection-status">
                <span class="status-indicator disconnected"></span>
                <span class="status-text">Connecting...</span>
            </div>
            
            <div class="judge-info">
                <span class="judge-name"><?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?></span>
                <span class="judge-code"><?= htmlspecialchars($judge['judge_code'] ?? 'J-' . $judge['id']) ?></span>
            </div>
            
            <div class="scoring-timer">
                <i class="fas fa-clock"></i>
                <span id="scoring-timer">00:00</span>
            </div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="scoring-progress">
        <div class="progress-bar-container">
            <div class="progress-bar" id="scoring-progress-bar"></div>
        </div>
        <div class="progress-text">
            <span id="progress-completed">0</span> of <span id="progress-total">0</span> criteria completed
        </div>
    </div>

    <!-- Category-Specific Scoring Interface -->
    <div class="scoring-content">
        <div id="scoring-interface-container">
            <?= $scoring_interface['html'] ?>
        </div>

        <!-- Score Summary Sidebar -->
        <div class="score-summary-sidebar">
            <div class="current-score-display">
                <h3>Current Score</h3>
                <div class="total-score">
                    <span class="score-number" id="total-score-display">0</span>
                    <span class="max-score">/ <?= $rubric['max_score'] ?? 100 ?></span>
                </div>
                <div class="score-percentage" id="score-percentage">0%</div>
            </div>

            <!-- Other Judges' Scores (if available) -->
            <?php if ($judge_comparison && $judge_comparison['judge_count'] > 0): ?>
            <div class="judge-comparison">
                <h4>Other Judges</h4>
                <div class="comparison-stats">
                    <div class="stat">
                        <span class="label">Average:</span>
                        <span class="value"><?= number_format($judge_comparison['avg_score'], 1) ?></span>
                    </div>
                    <div class="stat">
                        <span class="label">Range:</span>
                        <span class="value"><?= number_format($judge_comparison['min_score'], 1) ?> - <?= number_format($judge_comparison['max_score'], 1) ?></span>
                    </div>
                    <div class="stat">
                        <span class="label">Judges:</span>
                        <span class="value"><?= $judge_comparison['judge_count'] ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Auto-save Status -->
            <div class="auto-save-status">
                <div class="auto-save-indicator" id="auto-save-indicator">
                    <i class="fas fa-save"></i>
                    <span id="auto-save-text">Auto-saved</span>
                </div>
                <div class="last-save-time">
                    Last saved: <span id="last-save-time">--:--</span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="btn btn-outline-secondary" id="save-draft-btn" type="button">
                    <i class="fas fa-save"></i>
                    Save Draft
                </button>
                
                <button class="btn btn-warning" id="reset-scores-btn" type="button">
                    <i class="fas fa-undo"></i>
                    Reset
                </button>
                
                <button class="btn btn-info" id="preview-scores-btn" type="button">
                    <i class="fas fa-eye"></i>
                    Preview
                </button>
            </div>
        </div>
    </div>

    <!-- Judge Notes Section -->
    <div class="judge-notes-section">
        <h3>
            <i class="fas fa-sticky-note"></i>
            Judge Notes
        </h3>
        <textarea 
            id="judge-notes" 
            class="form-control" 
            rows="4" 
            placeholder="Add any additional observations, feedback, or notes about this team's performance..."
            maxlength="1000"><?= htmlspecialchars($existing_score['judge_notes'] ?? '') ?></textarea>
        <div class="character-count">
            <span id="notes-char-count">0</span>/1000 characters
        </div>
    </div>

    <!-- Submission Controls -->
    <div class="submission-controls">
        <div class="submission-status">
            <?php if ($existing_score): ?>
                <div class="current-status">
                    Status: <span class="status-badge status-<?= $existing_score['scoring_status'] ?>">
                        <?= ucfirst($existing_score['scoring_status']) ?>
                    </span>
                </div>
                <?php if ($existing_score['submitted_at']): ?>
                    <div class="submission-time">
                        Submitted: <?= date('M j, Y H:i', strtotime($existing_score['submitted_at'])) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="submission-buttons">
            <button class="btn btn-secondary" id="back-to-dashboard" type="button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </button>
            
            <button class="btn btn-success" id="submit-final-score" type="button" 
                    <?= ($existing_score && in_array($existing_score['scoring_status'], ['final', 'validated'])) ? 'disabled' : '' ?>>
                <i class="fas fa-check"></i>
                <?= ($existing_score && $existing_score['scoring_status'] === 'submitted') ? 'Update Score' : 'Submit Final Score' ?>
            </button>
        </div>
    </div>
</div>

<!-- Conflict Resolution Modal -->
<div class="modal fade" id="conflict-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Score Conflict Detected
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="conflict-details">
                <!-- Conflict details will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Review My Score
                </button>
                <button type="button" class="btn btn-warning" id="request-head-judge">
                    Request Head Judge
                </button>
                <button type="button" class="btn btn-primary" id="proceed-anyway">
                    Submit Anyway
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Offline Mode Modal -->
<div class="modal fade" id="offline-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-wifi"></i>
                    Connection Lost
                </h5>
            </div>
            <div class="modal-body">
                <p>Your connection to the live scoring system has been lost.</p>
                <p>You can continue scoring offline. Your scores will be synchronized when the connection is restored.</p>
                <div class="offline-status">
                    <div class="reconnect-attempts">
                        Reconnection attempts: <span id="reconnect-count">0</span>/5
                    </div>
                    <button class="btn btn-primary" id="retry-connection">
                        <i class="fas fa-sync-alt"></i>
                        Retry Connection
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="continue-offline">
                    Continue Offline
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include category-specific JavaScript and CSS -->
<?php if (isset($scoring_interface['javascript'])): ?>
<script>
<?= $scoring_interface['javascript'] ?>
</script>
<?php endif; ?>

<?php if (isset($scoring_interface['css'])): ?>
<style>
<?= $scoring_interface['css'] ?>
</style>
<?php endif; ?>

<script>
class RealTimeScoringManager {
    constructor() {
        this.ws = null;
        this.teamId = <?= $team['id'] ?>;
        this.competitionId = <?= $competition['id'] ?>;
        this.judgeId = <?= $judge['id'] ?>;
        this.sessionId = <?= $scoring_session['id'] ?>;
        this.websocketToken = '<?= $websocket_token ?>';
        this.autoSaveInterval = <?= $auto_save_interval ?>;
        this.maxScoringTime = <?= $max_scoring_time ?> * 60; // Convert to seconds
        
        this.scores = {};
        this.startTime = Date.now();
        this.lastSaveTime = null;
        this.autoSaveTimer = null;
        this.scoringTimer = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.isOfflineMode = false;
        
        this.init();
    }
    
    init() {
        this.connectWebSocket();
        this.bindEvents();
        this.startAutoSave();
        this.startScoringTimer();
        this.loadExistingScore();
        this.initializeProgress();
    }
    
    connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}/ws?token=${this.websocketToken}&session=${this.sessionId}&type=judge`;
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.updateConnectionStatus('connected');
                this.reconnectAttempts = 0;
                this.sendJudgeReady();
            };
            
            this.ws.onmessage = (event) => {
                this.handleWebSocketMessage(JSON.parse(event.data));
            };
            
            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                this.updateConnectionStatus('disconnected');
                this.attemptReconnect();
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.updateConnectionStatus('error');
            };
        } catch (error) {
            console.error('Failed to create WebSocket:', error);
            this.enterOfflineMode();
        }
    }
    
    handleWebSocketMessage(message) {
        switch (message.type) {
            case 'initial_state':
                this.handleInitialState(message.data);
                break;
                
            case 'score_confirmed':
                this.handleScoreConfirmed(message.data);
                break;
                
            case 'conflict_detected':
                this.handleConflictDetected(message.data);
                break;
                
            case 'score_update':
                this.handleOtherJudgeUpdate(message.data);
                break;
                
            case 'session_status':
                this.handleSessionStatus(message.data);
                break;
                
            case 'error':
                console.error('WebSocket error:', message.message);
                break;
        }
    }
    
    sendScoreUpdate(criteriaId, score) {
        const scoreUpdate = {
            type: 'score_update',
            session_id: this.sessionId,
            team_id: this.teamId,
            judge_id: this.judgeId,
            criteria_id: criteriaId,
            score: score,
            timestamp: Date.now()
        };
        
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(scoreUpdate));
        } else {
            // Queue for later if offline
            this.queueScoreUpdate(scoreUpdate);
        }
        
        // Update local scores
        this.scores[criteriaId] = score;
        this.updateTotalScore();
    }
    
    bindEvents() {
        // Save draft button
        $('#save-draft-btn').click(() => this.saveDraft());
        
        // Submit final score button
        $('#submit-final-score').click(() => this.submitFinalScore());
        
        // Reset scores button
        $('#reset-scores-btn').click(() => this.resetScores());
        
        // Preview scores button
        $('#preview-scores-btn').click(() => this.previewScores());
        
        // Back to dashboard
        $('#back-to-dashboard').click(() => {
            if (this.hasUnsavedChanges()) {
                if (confirm('You have unsaved changes. Save before leaving?')) {
                    this.saveDraft();
                }
            }
            window.location.href = '/judge/scoring';
        });
        
        // Judge notes character count
        $('#judge-notes').on('input', (e) => {
            const count = e.target.value.length;
            $('#notes-char-count').text(count);
            
            if (count > 900) {
                $('#notes-char-count').addClass('text-warning');
            }
            if (count > 950) {
                $('#notes-char-count').addClass('text-danger');
            }
        });
        
        // Conflict resolution buttons
        $('#request-head-judge').click(() => this.requestHeadJudge());
        $('#proceed-anyway').click(() => this.proceedWithConflict());
        
        // Offline mode buttons
        $('#retry-connection').click(() => this.connectWebSocket());
        $('#continue-offline').click(() => this.continueOfflineMode());
        
        // Prevent accidental page unload
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    }
    
    startAutoSave() {
        this.autoSaveTimer = setInterval(() => {
            if (this.hasUnsavedChanges()) {
                this.autoSave();
            }
        }, this.autoSaveInterval);
    }
    
    autoSave() {
        const scoreData = this.collectScoreData();
        scoreData.status = 'in_progress';
        
        this.saveScore(scoreData, false).then(() => {
            this.updateAutoSaveStatus('Auto-saved', true);
            this.lastSaveTime = Date.now();
            $('#last-save-time').text(new Date().toLocaleTimeString());
        }).catch(() => {
            this.updateAutoSaveStatus('Save failed', false);
        });
    }
    
    saveDraft() {
        const scoreData = this.collectScoreData();
        scoreData.status = 'draft';
        
        this.updateAutoSaveStatus('Saving...', null);
        
        this.saveScore(scoreData, true).then(() => {
            this.updateAutoSaveStatus('Draft saved', true);
            this.showNotification('Draft saved successfully', 'success');
        }).catch(() => {
            this.updateAutoSaveStatus('Save failed', false);
            this.showNotification('Failed to save draft', 'error');
        });
    }
    
    submitFinalScore() {
        if (!this.validateCompleteScore()) {
            this.showNotification('Please complete all required criteria before submitting', 'warning');
            return;
        }
        
        if (!confirm('Are you sure you want to submit this final score? You may not be able to edit it afterwards.')) {
            return;
        }
        
        const scoreData = this.collectScoreData();
        scoreData.status = 'submitted';
        scoreData.duration_minutes = Math.ceil((Date.now() - this.startTime) / 60000);
        
        $('#submit-final-score').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        
        this.saveScore(scoreData, true).then((response) => {
            if (response.conflicts && !response.conflicts.consistent) {
                this.showConflictDialog(response.conflicts);
            } else {
                this.showNotification('Score submitted successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/judge/scoring';
                }, 2000);
            }
        }).catch((error) => {
            this.showNotification('Failed to submit score: ' + error.message, 'error');
        }).finally(() => {
            $('#submit-final-score').prop('disabled', false).html('<i class="fas fa-check"></i> Submit Final Score');
        });
    }
    
    saveScore(scoreData, showProgress) {
        const url = this.scores.id ? `/judge/scoring/${this.scores.id}` : '/judge/scoring';
        const method = this.scores.id ? 'PUT' : 'POST';
        
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(scoreData),
                success: (response) => {
                    if (response.success) {
                        this.scores.id = response.score_id || this.scores.id;
                        resolve(response);
                    } else {
                        reject(new Error(response.message || 'Save failed'));
                    }
                },
                error: (xhr) => {
                    reject(new Error('Network error occurred'));
                }
            });
        });
    }
    
    collectScoreData() {
        const interfaceData = this.getScoringInterfaceData();
        
        return {
            team_id: this.teamId,
            competition_id: this.competitionId,
            criteria_scores: interfaceData.criteria_scores || {},
            judge_notes: $('#judge-notes').val(),
            duration_minutes: Math.ceil((Date.now() - this.startTime) / 60000)
        };
    }
    
    getScoringInterfaceData() {
        // This method will be implemented by the specific scoring interface
        if (window.visualScoring) {
            return window.visualScoring.getScores();
        } else if (window.technicalScoring) {
            return window.technicalScoring.getScores();
        } else if (window.standardScoring) {
            return window.standardScoring.getScores();
        }
        
        return { criteria_scores: this.scores };
    }
    
    updateConnectionStatus(status) {
        const indicator = $('#connection-status .status-indicator');
        const text = $('#connection-status .status-text');
        
        indicator.removeClass('connected disconnected error');
        indicator.addClass(status);
        
        switch (status) {
            case 'connected':
                text.text('Connected');
                break;
            case 'disconnected':
                text.text('Disconnected');
                break;
            case 'error':
                text.text('Connection Error');
                break;
        }
    }
    
    updateAutoSaveStatus(message, success) {
        const indicator = $('#auto-save-indicator');
        const text = $('#auto-save-text');
        
        indicator.removeClass('success error');
        
        if (success === true) {
            indicator.addClass('success');
        } else if (success === false) {
            indicator.addClass('error');
        }
        
        text.text(message);
    }
    
    startScoringTimer() {
        this.scoringTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            
            $('#scoring-timer').text(
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
            );
            
            // Warn when approaching time limit
            if (elapsed > this.maxScoringTime * 0.9) {
                $('#scoring-timer').addClass('text-warning');
            }
            
            if (elapsed > this.maxScoringTime) {
                $('#scoring-timer').addClass('text-danger');
                this.showNotification('Scoring time limit exceeded', 'warning');
            }
        }, 1000);
    }
    
    showNotification(message, type) {
        // Simple notification implementation
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 'alert-danger';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show notification" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.alert('close');
        }, 5000);
    }
    
    hasUnsavedChanges() {
        return this.lastSaveTime === null || Date.now() - this.lastSaveTime > this.autoSaveInterval;
    }
    
    // Additional methods would be implemented here...
    validateCompleteScore() { return true; }
    loadExistingScore() { /* Load existing score data */ }
    initializeProgress() { /* Initialize progress tracking */ }
    updateTotalScore() { /* Update total score display */ }
    showConflictDialog(conflicts) { /* Show conflict resolution dialog */ }
    enterOfflineMode() { $('#offline-modal').modal('show'); }
    attemptReconnect() { /* Implement reconnection logic */ }
}

// Initialize the real-time scoring manager when document is ready
$(document).ready(() => {
    window.realTimeScoring = new RealTimeScoringManager();
});
</script>

<style>
.real-time-scoring-interface {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.scoring-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.team-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.team-photo-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

.team-name {
    font-size: 2rem;
    font-weight: 600;
    margin: 0 0 10px 0;
    color: #333;
}

.team-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 5px;
}

.school-name {
    color: #666;
    font-weight: 500;
}

.category-badge {
    background: #007bff;
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
}

.competition-info {
    color: #888;
    font-size: 0.9rem;
}

.scoring-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.connection-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #dc3545;
}

.status-indicator.connected {
    background: #28a745;
}

.status-indicator.disconnected {
    background: #ffc107;
}

.status-indicator.error {
    background: #dc3545;
}

.scoring-progress {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.progress-bar-container {
    height: 12px;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    border-radius: 6px;
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    text-align: center;
    color: #666;
    font-size: 0.9rem;
}

.scoring-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    margin-bottom: 30px;
}

.score-summary-sidebar {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 25px;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.current-score-display {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid #eee;
}

.current-score-display h3 {
    margin-bottom: 15px;
    color: #333;
}

.total-score {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 10px;
}

.score-number {
    color: #28a745;
}

.max-score {
    color: #999;
    font-size: 1.5rem;
}

.score-percentage {
    font-size: 1.2rem;
    color: #666;
}

.judge-comparison {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.judge-comparison h4 {
    margin-bottom: 15px;
    font-size: 1rem;
    color: #333;
}

.comparison-stats .stat {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.auto-save-status {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.auto-save-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.auto-save-indicator.success {
    color: #28a745;
}

.auto-save-indicator.error {
    color: #dc3545;
}

.last-save-time {
    font-size: 0.8rem;
    color: #666;
    margin-top: 5px;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.judge-notes-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.judge-notes-section h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: #333;
}

.character-count {
    text-align: right;
    font-size: 0.8rem;
    color: #666;
    margin-top: 5px;
}

.submission-controls {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.submission-status {
    color: #666;
}

.current-status {
    font-weight: 500;
    margin-bottom: 5px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-in_progress {
    background: #d1ecf1;
    color: #0c5460;
}

.status-submitted {
    background: #d4edda;
    color: #155724;
}

.submission-buttons {
    display: flex;
    gap: 15px;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    min-width: 300px;
}

.scoring-timer {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    color: #333;
}

/* Responsive design */
@media (max-width: 1200px) {
    .scoring-content {
        grid-template-columns: 1fr;
    }
    
    .score-summary-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .scoring-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .team-section {
        flex-direction: column;
        text-align: center;
    }
    
    .submission-controls {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>