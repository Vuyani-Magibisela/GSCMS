<?php
// Mobile Scoreboard View - Optimized for mobile devices
$layout = 'layouts/public';
ob_start();
?>

<div class="mobile-scoreboard" data-session-id="<?= $session['id'] ?>">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="header-content">
            <div class="session-info">
                <h1 class="session-title"><?= htmlspecialchars($scoreboard['session']['name']) ?></h1>
                <div class="session-meta">
                    <span class="competition"><?= htmlspecialchars($scoreboard['competition']) ?></span>
                    <span class="separator">•</span>
                    <span class="category"><?= htmlspecialchars($scoreboard['category']) ?></span>
                </div>
            </div>
            
            <div class="header-actions">
                <button class="action-btn refresh-btn" id="refresh-scores" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="action-btn share-btn" id="share-scoreboard" title="Share">
                    <i class="fas fa-share-alt"></i>
                </button>
                <button class="action-btn fullscreen-btn" id="toggle-fullscreen" title="Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="status-bar">
            <div class="live-indicator">
                <span class="live-dot"></span>
                <span class="live-text">LIVE</span>
            </div>
            
            <div class="update-info">
                <span id="last-update">Just updated</span>
            </div>
            
            <div class="connection-status" id="connection-status">
                <i class="fas fa-circle connected-icon"></i>
            </div>
        </div>
    </div>

    <!-- Scores Display -->
    <div class="scores-container">
        <?php if (!empty($scoreboard['standings'])): ?>
            <div class="standings-list" id="standings-list">
                <?php foreach ($scoreboard['standings'] as $index => $team): ?>
                    <div class="team-item" data-team-id="<?= $team['team_id'] ?>">
                        <div class="rank-section">
                            <div class="rank-number rank-<?= $team['rank'] ?>">
                                <?= $team['rank'] ?>
                            </div>
                            <?php if ($team['rank_change'] != 0): ?>
                                <div class="rank-change <?= $team['rank_change'] > 0 ? 'up' : 'down' ?>">
                                    <i class="fas fa-arrow-<?= $team['rank_change'] > 0 ? 'up' : 'down' ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="team-info">
                            <div class="team-name"><?= htmlspecialchars($team['team_name']) ?></div>
                            <div class="team-meta">
                                <span class="school-name"><?= htmlspecialchars($team['school_name']) ?></span>
                                <?php if (!empty($team['last_updated'])): ?>
                                    <span class="separator">•</span>
                                    <span class="last-scored"><?= timeAgo($team['last_updated']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="score-section">
                            <div class="total-score"><?= number_format($team['total_score'], 1) ?></div>
                            <div class="score-breakdown">
                                <?php if (!empty($team['scores'])): ?>
                                    <?php foreach (array_slice($team['scores'], 0, 3) as $criterion => $score): ?>
                                        <span class="criterion-score" title="<?= htmlspecialchars($criterion) ?>">
                                            <?= number_format($score, 1) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-scores">
                <div class="no-scores-content">
                    <i class="fas fa-hourglass-start"></i>
                    <h3>Waiting for Scores</h3>
                    <p>Judges are currently evaluating teams</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats" id="quick-stats">
        <div class="stat-item">
            <div class="stat-value"><?= count($scoreboard['standings']) ?></div>
            <div class="stat-label">Teams</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-value" id="judges-active"><?= $scoreboard['judges_active'] ?? 0 ?></div>
            <div class="stat-label">Judges</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-value" id="scores-submitted"><?= $scoreboard['scores_submitted'] ?? 0 ?></div>
            <div class="stat-label">Scores</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-value" id="viewers-count"><?= $scoreboard['viewers'] ?? 1 ?></div>
            <div class="stat-label">Viewers</div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="footer-actions">
        <div class="view-toggles">
            <button class="toggle-btn" id="toggle-detailed" title="Toggle Details">
                <i class="fas fa-list-ul"></i>
                <span>Details</span>
            </button>
            <button class="toggle-btn" id="toggle-stats" title="Toggle Stats">
                <i class="fas fa-chart-bar"></i>
                <span>Stats</span>
            </button>
        </div>
        
        <div class="mode-switcher">
            <a href="<?= url("/scoreboard/{$session['id']}") ?>" class="mode-link">
                <i class="fas fa-desktop"></i>
                <span>Desktop</span>
            </a>
            <a href="<?= url("/scoreboard/{$session['id']}?mode=tv") ?>" class="mode-link">
                <i class="fas fa-tv"></i>
                <span>TV</span>
            </a>
        </div>
    </div>
</div>

<!-- Detailed View Modal -->
<div class="modal fade" id="detailedModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detailed Scores</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailed-scores" class="detailed-scores">
                    <!-- Detailed scores will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Mobile Scoreboard Styles */
.mobile-scoreboard {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 0;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

/* Mobile Header */
.mobile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.session-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    line-height: 1.2;
}

.session-meta {
    font-size: 0.85rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.separator {
    opacity: 0.6;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.action-btn.active {
    background: rgba(255, 255, 255, 0.4);
}

/* Status Bar */
.status-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    opacity: 0.9;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.live-dot {
    width: 6px;
    height: 6px;
    background: #48bb78;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.connected-icon {
    color: #48bb78;
    font-size: 0.7rem;
}

.connected-icon.disconnected {
    color: #f56565;
}

/* Scores Container */
.scores-container {
    padding: 1rem;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* Team Items */
.team-item {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.6);
    transition: all 0.3s ease;
    position: relative;
}

.team-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.team-item.updated {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    border-color: #fc8181;
    animation: highlight 2s ease;
}

@keyframes highlight {
    0% { background: #fed7d7; }
    100% { background: white; }
}

/* Rank Section */
.rank-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 50px;
}

.rank-number {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: white;
}

.rank-1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #744210; }
.rank-2 { background: linear-gradient(135deg, #c0c0c0 0%, #e2e8f0 100%); color: #2d3748; }
.rank-3 { background: linear-gradient(135deg, #cd7f32 0%, #dd6b20 100%); }

.rank-number:not(.rank-1):not(.rank-2):not(.rank-3) {
    background: linear-gradient(135deg, #4a5568 0%, #718096 100%);
}

.rank-change {
    margin-top: 0.25rem;
    font-size: 0.7rem;
}

.rank-change.up { color: #48bb78; }
.rank-change.down { color: #f56565; }

/* Team Info */
.team-info {
    flex: 1;
    min-width: 0;
}

.team-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: #2d3748;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.team-meta {
    font-size: 0.75rem;
    color: #718096;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.school-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

/* Score Section */
.score-section {
    text-align: right;
    min-width: 60px;
}

.total-score {
    font-weight: 700;
    font-size: 1.1rem;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.score-breakdown {
    display: flex;
    justify-content: flex-end;
    gap: 0.25rem;
}

.criterion-score {
    background: #e2e8f0;
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-size: 0.7rem;
    color: #4a5568;
}

/* No Scores */
.no-scores {
    text-align: center;
    padding: 3rem 1rem;
}

.no-scores-content {
    color: #718096;
}

.no-scores-content i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.6;
}

.no-scores-content h3 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: #4a5568;
}

/* Quick Stats */
.quick-stats {
    background: white;
    padding: 1rem;
    margin: 0 1rem 1rem;
    border-radius: 12px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-weight: 700;
    font-size: 1.25rem;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Footer Actions */
.footer-actions {
    background: white;
    padding: 1rem;
    margin: 0 1rem 1rem;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.view-toggles, .mode-switcher {
    display: flex;
    gap: 0.5rem;
}

.toggle-btn, .mode-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.8rem;
    transition: all 0.2s ease;
    text-decoration: none;
    color: #4a5568;
    border: 1px solid #e2e8f0;
    background: white;
}

.toggle-btn:hover, .mode-link:hover {
    background: #f7fafc;
    color: #667eea;
    border-color: #667eea;
}

.toggle-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Detailed Scores Modal */
.detailed-scores {
    padding: 1rem;
}

.detailed-team {
    background: #f7fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.detailed-team h4 {
    margin-bottom: 0.75rem;
    color: #2d3748;
}

.criteria-scores {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.5rem;
}

.criteria-item {
    background: white;
    padding: 0.5rem;
    border-radius: 6px;
    text-align: center;
    border: 1px solid #e2e8f0;
}

.criteria-name {
    font-size: 0.75rem;
    color: #718096;
    margin-bottom: 0.25rem;
}

.criteria-value {
    font-weight: 600;
    color: #2d3748;
}

/* Responsive Design */
@media (max-width: 375px) {
    .session-title {
        font-size: 1rem;
    }
    
    .header-actions {
        gap: 0.25rem;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    .team-item {
        padding: 0.75rem;
        gap: 0.5rem;
    }
    
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .footer-actions {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (min-width: 768px) {
    .mobile-scoreboard {
        max-width: 480px;
        margin: 0 auto;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
}

/* Animation helpers */
.slide-up-enter {
    opacity: 0;
    transform: translateY(20px);
}

.slide-up-enter-active {
    opacity: 1;
    transform: translateY(0);
    transition: all 0.3s ease;
}

.bounce-enter {
    animation: bounceIn 0.6s ease;
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    50% {
        opacity: 1;
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

/* Pull to refresh indicator */
.pull-to-refresh {
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #4a5568;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pull-to-refresh.active {
    top: 10px;
}

.pull-to-refresh i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Mobile Scoreboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const mobileScoreboard = new MobileScoreboardManager();
    mobileScoreboard.init();
});

class MobileScoreboardManager {
    constructor() {
        this.sessionId = document.querySelector('.mobile-scoreboard').dataset.sessionId;
        this.ws = null;
        this.lastUpdate = Date.now();
        this.refreshing = false;
        this.touchStartY = 0;
        this.pullThreshold = 80;
        this.isDetailedView = false;
    }
    
    init() {
        this.setupEventListeners();
        this.initWebSocket();
        this.setupPullToRefresh();
        this.startUpdateTimer();
    }
    
    setupEventListeners() {
        // Action buttons
        document.getElementById('refresh-scores').addEventListener('click', () => {
            this.refreshScores();
        });
        
        document.getElementById('share-scoreboard').addEventListener('click', () => {
            this.shareScoreboard();
        });
        
        document.getElementById('toggle-fullscreen').addEventListener('click', () => {
            this.toggleFullscreen();
        });
        
        // View toggles
        document.getElementById('toggle-detailed').addEventListener('click', () => {
            this.toggleDetailedView();
        });
        
        document.getElementById('toggle-stats').addEventListener('click', () => {
            this.toggleStats();
        });
        
        // Team item interactions
        document.querySelectorAll('.team-item').forEach(item => {
            item.addEventListener('click', () => {
                this.showTeamDetails(item.dataset.teamId);
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'r' || e.key === 'R') {
                this.refreshScores();
            }
            if (e.key === 'f' || e.key === 'F') {
                this.toggleFullscreen();
            }
        });
    }
    
    initWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname;
        const port = 8080;
        
        try {
            this.ws = new WebSocket(`${protocol}//${host}:${port}?session=${this.sessionId}&viewer=mobile`);
            
            this.ws.onopen = () => {
                this.updateConnectionStatus('connected');
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };
            
            this.ws.onclose = () => {
                this.updateConnectionStatus('disconnected');
                setTimeout(() => this.initWebSocket(), 5000); // Reconnect
            };
            
            this.ws.onerror = () => {
                this.updateConnectionStatus('error');
            };
            
        } catch (error) {
            console.error('WebSocket connection failed:', error);
            this.updateConnectionStatus('error');
        }
    }
    
    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'scoreboard_update':
                this.updateScoreboard(data.data);
                break;
            case 'rank_change':
                this.animateRankChange(data.data);
                break;
            case 'new_score':
                this.highlightTeamUpdate(data.team_id);
                break;
            case 'session_ended':
                this.showSessionEndedMessage();
                break;
        }
        
        this.lastUpdate = Date.now();
        this.updateLastUpdateTime();
    }
    
    updateScoreboard(data) {
        if (data.standings) {
            this.updateStandings(data.standings);
        }
        
        if (data.stats) {
            this.updateQuickStats(data.stats);
        }
    }
    
    updateStandings(standings) {
        const standingsList = document.getElementById('standings-list');
        
        standings.forEach(team => {
            const teamElement = standingsList.querySelector(`[data-team-id="${team.team_id}"]`);
            if (teamElement) {
                this.updateTeamElement(teamElement, team);
            }
        });
    }
    
    updateTeamElement(element, teamData) {
        // Update rank
        const rankElement = element.querySelector('.rank-number');
        if (rankElement && rankElement.textContent !== teamData.rank.toString()) {
            rankElement.textContent = teamData.rank;
            rankElement.className = `rank-number rank-${teamData.rank}`;
            this.animateElement(rankElement, 'bounce-enter');
        }
        
        // Update score
        const scoreElement = element.querySelector('.total-score');
        if (scoreElement) {
            const currentScore = parseFloat(scoreElement.textContent);
            const newScore = parseFloat(teamData.total_score);
            
            if (currentScore !== newScore) {
                this.animateScoreChange(scoreElement, newScore);
            }
        }
        
        // Update rank change indicator
        this.updateRankChange(element, teamData.rank_change);
        
        // Update last scored time
        const lastScoredElement = element.querySelector('.last-scored');
        if (lastScoredElement && teamData.last_updated) {
            lastScoredElement.textContent = this.timeAgo(teamData.last_updated);
        }
    }
    
    animateScoreChange(element, newScore) {
        element.style.transform = 'scale(1.2)';
        element.style.color = '#48bb78';
        
        setTimeout(() => {
            element.textContent = newScore.toFixed(1);
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 200);
    }
    
    updateRankChange(teamElement, rankChange) {
        let changeElement = teamElement.querySelector('.rank-change');
        
        if (rankChange === 0) {
            if (changeElement) {
                changeElement.remove();
            }
            return;
        }
        
        if (!changeElement) {
            changeElement = document.createElement('div');
            changeElement.className = 'rank-change';
            teamElement.querySelector('.rank-section').appendChild(changeElement);
        }
        
        changeElement.className = `rank-change ${rankChange > 0 ? 'up' : 'down'}`;
        changeElement.innerHTML = `<i class="fas fa-arrow-${rankChange > 0 ? 'up' : 'down'}"></i>`;
        
        this.animateElement(changeElement, 'bounce-enter');
    }
    
    updateQuickStats(stats) {
        document.getElementById('judges-active').textContent = stats.judges_active || 0;
        document.getElementById('scores-submitted').textContent = stats.scores_submitted || 0;
        document.getElementById('viewers-count').textContent = stats.viewers || 1;
    }
    
    highlightTeamUpdate(teamId) {
        const teamElement = document.querySelector(`[data-team-id="${teamId}"]`);
        if (teamElement) {
            teamElement.classList.add('updated');
            setTimeout(() => {
                teamElement.classList.remove('updated');
            }, 2000);
        }
    }
    
    animateRankChange(data) {
        const teamElement = document.querySelector(`[data-team-id="${data.team_id}"]`);
        if (teamElement) {
            // Move element to new position with animation
            this.animateElementToNewPosition(teamElement, data.new_position);
        }
    }
    
    animateElementToNewPosition(element, newPosition) {
        const currentRect = element.getBoundingClientRect();
        const parent = element.parentElement;
        const children = Array.from(parent.children);
        const currentIndex = children.indexOf(element);
        
        // Move element to new position
        if (newPosition < children.length) {
            parent.insertBefore(element, children[newPosition]);
        } else {
            parent.appendChild(element);
        }
        
        // Animate the movement
        const newRect = element.getBoundingClientRect();
        const deltaY = currentRect.top - newRect.top;
        
        element.style.transform = `translateY(${deltaY}px)`;
        element.style.transition = 'none';
        
        requestAnimationFrame(() => {
            element.style.transform = '';
            element.style.transition = 'transform 0.3s ease';
        });
    }
    
    setupPullToRefresh() {
        const scoresContainer = document.querySelector('.scores-container');
        
        scoresContainer.addEventListener('touchstart', (e) => {
            this.touchStartY = e.touches[0].clientY;
        });
        
        scoresContainer.addEventListener('touchmove', (e) => {
            if (scoresContainer.scrollTop === 0) {
                const touchY = e.touches[0].clientY;
                const pullDistance = touchY - this.touchStartY;
                
                if (pullDistance > 0 && pullDistance < this.pullThreshold * 2) {
                    this.showPullToRefreshIndicator(pullDistance);
                }
            }
        });
        
        scoresContainer.addEventListener('touchend', (e) => {
            if (scoresContainer.scrollTop === 0) {
                const pullDistance = e.changedTouches[0].clientY - this.touchStartY;
                
                if (pullDistance > this.pullThreshold) {
                    this.refreshScores();
                }
                
                this.hidePullToRefreshIndicator();
            }
        });
    }
    
    showPullToRefreshIndicator(distance) {
        let indicator = document.querySelector('.pull-to-refresh');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'pull-to-refresh';
            indicator.innerHTML = '<i class="fas fa-sync-alt"></i><span>Pull to refresh</span>';
            document.querySelector('.mobile-scoreboard').appendChild(indicator);
        }
        
        const opacity = Math.min(distance / this.pullThreshold, 1);
        indicator.style.opacity = opacity;
        
        if (distance > this.pullThreshold) {
            indicator.innerHTML = '<i class="fas fa-sync-alt"></i><span>Release to refresh</span>';
            indicator.classList.add('active');
        }
    }
    
    hidePullToRefreshIndicator() {
        const indicator = document.querySelector('.pull-to-refresh');
        if (indicator) {
            indicator.style.opacity = '0';
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 300);
        }
    }
    
    async refreshScores() {
        if (this.refreshing) return;
        
        this.refreshing = true;
        const refreshBtn = document.getElementById('refresh-scores');
        const refreshIcon = refreshBtn.querySelector('.fas');
        
        refreshIcon.classList.add('fa-spin');
        
        try {
            const response = await fetch(`/scoreboard/${this.sessionId}/api?mode=mobile`);
            const data = await response.json();
            
            if (data.standings) {
                this.updateStandings(data.standings);
                this.lastUpdate = Date.now();
                this.updateLastUpdateTime();
            }
        } catch (error) {
            console.error('Failed to refresh scores:', error);
        } finally {
            this.refreshing = false;
            refreshIcon.classList.remove('fa-spin');
        }
    }
    
    shareScoreboard() {
        const url = window.location.href;
        const text = `Live scores for ${document.querySelector('.session-title').textContent}`;
        
        if (navigator.share) {
            navigator.share({
                title: text,
                url: url
            });
        } else {
            // Fallback to copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                this.showToast('Link copied to clipboard!');
            });
        }
    }
    
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            document.getElementById('toggle-fullscreen').innerHTML = '<i class="fas fa-compress"></i>';
        } else {
            document.exitFullscreen();
            document.getElementById('toggle-fullscreen').innerHTML = '<i class="fas fa-expand"></i>';
        }
    }
    
    toggleDetailedView() {
        this.isDetailedView = !this.isDetailedView;
        const modal = new bootstrap.Modal(document.getElementById('detailedModal'));
        
        if (this.isDetailedView) {
            this.loadDetailedScores();
            modal.show();
        } else {
            modal.hide();
        }
    }
    
    async loadDetailedScores() {
        const detailedContainer = document.getElementById('detailed-scores');
        detailedContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        try {
            const response = await fetch(`/scoreboard/${this.sessionId}/api?detailed=1`);
            const data = await response.json();
            
            if (data.detailed_standings) {
                this.renderDetailedScores(data.detailed_standings);
            }
        } catch (error) {
            console.error('Failed to load detailed scores:', error);
            detailedContainer.innerHTML = '<div class="text-center text-danger">Failed to load detailed scores</div>';
        }
    }
    
    renderDetailedScores(standings) {
        const container = document.getElementById('detailed-scores');
        container.innerHTML = '';
        
        standings.forEach(team => {
            const teamElement = document.createElement('div');
            teamElement.className = 'detailed-team';
            teamElement.innerHTML = `
                <h4>${team.rank}. ${team.team_name}</h4>
                <div class="criteria-scores">
                    ${Object.entries(team.scores || {}).map(([criterion, score]) => `
                        <div class="criteria-item">
                            <div class="criteria-name">${criterion}</div>
                            <div class="criteria-value">${score}</div>
                        </div>
                    `).join('')}
                </div>
            `;
            container.appendChild(teamElement);
        });
    }
    
    toggleStats() {
        const statsElement = document.getElementById('quick-stats');
        statsElement.style.display = statsElement.style.display === 'none' ? 'grid' : 'none';
        
        const toggleBtn = document.getElementById('toggle-stats');
        toggleBtn.classList.toggle('active');
    }
    
    showTeamDetails(teamId) {
        // Haptic feedback if available
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        // Show team details modal or navigate to team page
        console.log('Show details for team:', teamId);
    }
    
    updateConnectionStatus(status) {
        const statusIcon = document.getElementById('connection-status').querySelector('i');
        
        statusIcon.className = 'fas fa-circle';
        switch (status) {
            case 'connected':
                statusIcon.classList.add('connected-icon');
                break;
            case 'disconnected':
                statusIcon.classList.add('disconnected');
                break;
            case 'error':
                statusIcon.classList.add('disconnected');
                break;
        }
    }
    
    startUpdateTimer() {
        setInterval(() => {
            this.updateLastUpdateTime();
        }, 30000); // Update every 30 seconds
    }
    
    updateLastUpdateTime() {
        const updateElement = document.getElementById('last-update');
        updateElement.textContent = this.timeAgo(this.lastUpdate);
    }
    
    timeAgo(timestamp) {
        const now = Date.now();
        const diff = now - timestamp;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        return Math.floor(diff / 86400000) + 'd ago';
    }
    
    animateElement(element, animationClass) {
        element.classList.add(animationClass);
        setTimeout(() => {
            element.classList.remove(animationClass);
        }, 600);
    }
    
    showToast(message, type = 'info') {
        // Simple toast implementation
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            z-index: 1000;
            animation: slideUp 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease forwards';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
    
    showSessionEndedMessage() {
        this.showToast('Session has ended', 'warning');
        
        // Update header to show session ended
        const statusText = document.querySelector('.live-text');
        if (statusText) {
            statusText.textContent = 'ENDED';
            statusText.style.color = '#f56565';
        }
        
        const liveDot = document.querySelector('.live-dot');
        if (liveDot) {
            liveDot.style.backgroundColor = '#f56565';
            liveDot.style.animation = 'none';
        }
    }
}

// Time ago helper function
function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString);
    const diff = now - past;
    
    if (diff < 60000) return 'just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return Math.floor(diff / 86400000) + 'd ago';
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>