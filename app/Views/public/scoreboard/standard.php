<?php
$layout = 'layouts/public';
ob_start();
?>

<div class="live-scoreboard-container" data-session-id="<?= $session['id'] ?>">
    <!-- Header Section -->
    <div class="scoreboard-header">
        <div class="competition-branding">
            <div class="logo-section">
                <img src="/images/scibotics-logo.png" alt="SciBOTICS" class="competition-logo" />
            </div>
            <div class="competition-info">
                <h1 class="competition-title">GDE SciBOTICS Finals 2025</h1>
                <h2 class="session-title"><?= htmlspecialchars($scoreboard['session']['name']) ?></h2>
                <div class="competition-details">
                    <span class="competition-name"><?= htmlspecialchars($scoreboard['session']['competition']) ?></span>
                    <span class="category-name"><?= htmlspecialchars($scoreboard['session']['category']) ?></span>
                </div>
            </div>
        </div>
        
        <div class="scoreboard-controls">
            <div class="live-indicator">
                <span class="live-dot"></span>
                <span class="live-text">LIVE</span>
                <span class="last-update">Updated <?= date('H:i:s', strtotime($scoreboard['metadata']['last_updated'])) ?></span>
            </div>
            
            <div class="view-controls">
                <div class="display-mode-selector">
                    <button class="mode-btn active" data-mode="standard">
                        <i class="fas fa-desktop"></i>
                        Standard
                    </button>
                    <button class="mode-btn" data-mode="mobile">
                        <i class="fas fa-mobile-alt"></i>
                        Mobile
                    </button>
                    <button class="mode-btn" data-mode="tv">
                        <i class="fas fa-tv"></i>
                        TV Display
                    </button>
                </div>
                
                <button class="share-btn" id="share-scoreboard">
                    <i class="fas fa-share-alt"></i>
                    Share
                </button>
            </div>
        </div>
    </div>

    <!-- Session Statistics -->
    <div class="session-stats">
        <div class="stat-item">
            <span class="stat-value"><?= $scoreboard['metadata']['total_teams'] ?></span>
            <span class="stat-label">Teams Competing</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?= $scoreboard['statistics']['active_judges'] ?? 0 ?></span>
            <span class="stat-label">Judges Active</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?= $scoreboard['statistics']['total_score_updates'] ?? 0 ?></span>
            <span class="stat-label">Live Updates</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?= $scoreboard['statistics']['conflicts'] ?? 0 ?></span>
            <span class="stat-label">Conflicts Resolved</span>
        </div>
    </div>

    <!-- Main Scoreboard -->
    <div class="scoreboard-content">
        <div class="standings-section">
            <h3 class="section-title">
                <i class="fas fa-trophy"></i>
                Live Standings
            </h3>
            
            <div class="standings-table-container">
                <table class="standings-table" id="standings-table">
                    <thead>
                        <tr>
                            <th class="rank-col">Rank</th>
                            <th class="team-col">Team</th>
                            <th class="school-col">School</th>
                            <th class="score-col">Total Score</th>
                            <th class="game-score-col">Game Challenge</th>
                            <th class="research-score-col">Research</th>
                            <th class="judges-col">Judges</th>
                            <th class="trend-col">Trend</th>
                            <th class="status-col">Status</th>
                        </tr>
                    </thead>
                    <tbody id="standings-tbody">
                        <?php if (empty($scoreboard['standings'])): ?>
                        <tr class="no-data">
                            <td colspan="9" class="text-center">
                                <div class="no-data-message">
                                    <i class="fas fa-hourglass-half"></i>
                                    <p>Competition starting soon...</p>
                                    <p class="small">Scores will appear here once judging begins</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($scoreboard['standings'] as $team): ?>
                            <tr class="team-row" data-team-id="<?= $team['team_id'] ?>" data-rank="<?= $team['rank'] ?>">
                                <td class="rank-col">
                                    <div class="rank-display">
                                        <span class="rank-number"><?= $team['rank'] ?></span>
                                        <?php if ($team['rank'] <= 3): ?>
                                            <i class="fas fa-medal rank-medal rank-<?= $team['rank'] ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="team-col">
                                    <div class="team-info">
                                        <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                        <?php if ($team['points_behind'] > 0 && $team['rank'] > 1): ?>
                                            <span class="points-behind">-<?= number_format($team['points_behind'], 1) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="school-col">
                                    <span class="school-name"><?= htmlspecialchars($team['school_name']) ?></span>
                                </td>
                                
                                <td class="score-col">
                                    <div class="score-display">
                                        <span class="score-value total-score"><?= number_format($team['total_score'], 1) ?></span>
                                        <?php if ($team['total_score'] > 0): ?>
                                            <div class="score-progress">
                                                <div class="progress-bar" style="width: <?= ($team['total_score'] / 250 * 100) ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="game-score-col">
                                    <span class="score-value"><?= number_format($team['game_score'], 1) ?></span>
                                </td>
                                
                                <td class="research-score-col">
                                    <span class="score-value"><?= number_format($team['research_score'], 1) ?></span>
                                </td>
                                
                                <td class="judges-col">
                                    <div class="judges-progress">
                                        <span class="judges-count"><?= $team['judges_completed'] ?>/3</span>
                                        <div class="judges-indicators">
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <span class="judge-indicator <?= $i <= $team['judges_completed'] ? 'completed' : '' ?>"></span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="trend-col">
                                    <div class="trend-indicator trend-<?= $team['trend'] ?>">
                                        <?php if ($team['trend'] === 'up'): ?>
                                            <i class="fas fa-arrow-up"></i>
                                        <?php elseif ($team['trend'] === 'down'): ?>
                                            <i class="fas fa-arrow-down"></i>
                                        <?php else: ?>
                                            <i class="fas fa-minus"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="status-col">
                                    <span class="status-badge <?= $team['is_finalized'] ? 'final' : 'live' ?>">
                                        <?= $team['is_finalized'] ? 'Final' : 'Live' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar with Additional Info -->
        <div class="scoreboard-sidebar">
            <!-- Recent Updates Feed -->
            <div class="sidebar-section recent-updates">
                <h4>
                    <i class="fas fa-rss"></i>
                    Live Updates
                </h4>
                <div id="updates-feed" class="updates-feed">
                    <div class="update-item">
                        <span class="update-time">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="sidebar-section top-performers">
                <h4>
                    <i class="fas fa-star"></i>
                    Top 3
                </h4>
                <div class="podium-mini">
                    <?php if (!empty($scoreboard['standings'])): ?>
                        <?php foreach (array_slice($scoreboard['standings'], 0, 3) as $i => $team): ?>
                        <div class="podium-item position-<?= $i + 1 ?>">
                            <div class="position"><?= $i + 1 ?></div>
                            <div class="team-name"><?= htmlspecialchars($team['team_name']) ?></div>
                            <div class="score"><?= number_format($team['total_score'], 1) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Competition Info -->
            <div class="sidebar-section competition-details">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    Competition Details
                </h4>
                <div class="detail-items">
                    <div class="detail-item">
                        <span class="label">Category:</span>
                        <span class="value"><?= htmlspecialchars($scoreboard['session']['category']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Status:</span>
                        <span class="value status-<?= $scoreboard['session']['status'] ?>">
                            <?= ucfirst($scoreboard['session']['status']) ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Started:</span>
                        <span class="value"><?= date('H:i', strtotime($scoreboard['session']['start_time'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Share & Access -->
            <div class="sidebar-section share-section">
                <h4>
                    <i class="fas fa-share-alt"></i>
                    Share This Scoreboard
                </h4>
                <div class="share-options">
                    <button class="share-option" data-share="link">
                        <i class="fas fa-link"></i>
                        Copy Link
                    </button>
                    <button class="share-option" data-share="qr">
                        <i class="fas fa-qrcode"></i>
                        QR Code
                    </button>
                    <button class="share-option" data-share="twitter">
                        <i class="fab fa-twitter"></i>
                        Twitter
                    </button>
                    <button class="share-option" data-share="facebook">
                        <i class="fab fa-facebook"></i>
                        Facebook
                    </button>
                </div>
                
                <div class="qr-code-container" style="display: none;">
                    <img src="<?= $qr_code ?>" alt="QR Code" class="qr-code" />
                    <p class="qr-help">Scan to view on mobile</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="share-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Scoreboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="share-url-section">
                    <label>Share URL:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="share-url" readonly value="<?= "https://{$_SERVER['HTTP_HOST']}/scoreboard/{$session['id']}" ?>" />
                        <button class="btn btn-outline-secondary" id="copy-url">
                            <i class="fas fa-copy"></i>
                            Copy
                        </button>
                    </div>
                </div>
                
                <div class="embed-section">
                    <label>Embed Code:</label>
                    <textarea class="form-control" id="embed-code" readonly rows="3"><?= "<iframe src=\"https://{$_SERVER['HTTP_HOST']}/scoreboard/{$session['id']}/embed\" width=\"800\" height=\"600\" frameborder=\"0\"></iframe>" ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
class LiveScoreboardViewer {
    constructor() {
        this.ws = null;
        this.sessionId = <?= $session['id'] ?>;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.updateInterval = <?= $scoreboard['metadata']['update_interval'] ?? 5 ?> * 1000;
        this.lastUpdateTime = null;
        
        this.init();
    }
    
    init() {
        this.connectWebSocket();
        this.bindEvents();
        this.startUpdateTimer();
        this.initializeAnimations();
    }
    
    connectWebSocket() {
        const wsUrl = '<?= $websocket_url ?>';
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('Scoreboard WebSocket connected');
                this.reconnectAttempts = 0;
                this.updateLiveIndicator(true);
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleUpdate(data);
            };
            
            this.ws.onclose = () => {
                console.log('Scoreboard WebSocket disconnected');
                this.updateLiveIndicator(false);
                this.attemptReconnect();
            };
            
            this.ws.onerror = (error) => {
                console.error('Scoreboard WebSocket error:', error);
            };
        } catch (error) {
            console.error('Failed to create WebSocket:', error);
            this.fallbackToPolling();
        }
    }
    
    handleUpdate(data) {
        switch (data.type) {
            case 'scoreboard_update':
                this.updateScoreboard(data.data);
                break;
                
            case 'score_update':
                this.handleLiveScoreUpdate(data.data);
                break;
                
            case 'rank_change':
                this.animateRankChange(data.data);
                break;
        }
        
        this.lastUpdateTime = Date.now();
        this.updateLastUpdateTime();
    }
    
    updateScoreboard(newData) {
        const tbody = document.getElementById('standings-tbody');
        
        // Update standings
        newData.standings.forEach(team => {
            const row = tbody.querySelector(`tr[data-team-id="${team.team_id}"]`);
            if (row) {
                this.updateTeamRow(row, team);
            }
        });
        
        // Re-sort if needed
        this.sortStandings();
        
        // Update statistics
        this.updateStatistics(newData.statistics);
        
        // Add to updates feed
        this.addUpdateToFeed('Scoreboard refreshed', 'system');
    }
    
    updateTeamRow(row, teamData) {
        // Update rank
        const rankCell = row.querySelector('.rank-number');
        if (rankCell && parseInt(rankCell.textContent) !== teamData.rank) {
            const oldRank = parseInt(rankCell.textContent);
            rankCell.textContent = teamData.rank;
            this.animateRankChange(row, oldRank, teamData.rank);
        }
        
        // Update scores with animation
        const totalScoreEl = row.querySelector('.total-score');
        if (totalScoreEl) {
            const oldScore = parseFloat(totalScoreEl.textContent);
            const newScore = teamData.total_score;
            
            if (oldScore !== newScore) {
                this.animateScoreChange(totalScoreEl, oldScore, newScore);
                this.addUpdateToFeed(`${teamData.team_name} scored ${newScore.toFixed(1)} points`, 'score');
            }
        }
        
        // Update game and research scores
        const gameScoreEl = row.querySelector('.game-score-col .score-value');
        if (gameScoreEl) {
            gameScoreEl.textContent = teamData.game_score.toFixed(1);
        }
        
        const researchScoreEl = row.querySelector('.research-score-col .score-value');
        if (researchScoreEl) {
            researchScoreEl.textContent = teamData.research_score.toFixed(1);
        }
        
        // Update progress bar
        const progressBar = row.querySelector('.progress-bar');
        if (progressBar) {
            const percentage = (teamData.total_score / 250) * 100;
            progressBar.style.width = percentage + '%';
        }
        
        // Update trend indicator
        const trendEl = row.querySelector('.trend-indicator');
        if (trendEl) {
            trendEl.className = `trend-indicator trend-${teamData.trend}`;
            const icon = trendEl.querySelector('i');
            if (icon) {
                icon.className = teamData.trend === 'up' ? 'fas fa-arrow-up' : 
                                 teamData.trend === 'down' ? 'fas fa-arrow-down' : 'fas fa-minus';
            }
        }
        
        // Update judges progress
        const judgesCount = row.querySelector('.judges-count');
        if (judgesCount) {
            judgesCount.textContent = `${teamData.judges_completed}/3`;
        }
        
        const judgeIndicators = row.querySelectorAll('.judge-indicator');
        judgeIndicators.forEach((indicator, index) => {
            if (index < teamData.judges_completed) {
                indicator.classList.add('completed');
            } else {
                indicator.classList.remove('completed');
            }
        });
    }
    
    animateScoreChange(element, oldScore, newScore) {
        element.style.transform = 'scale(1.1)';
        element.style.color = '#28a745';
        
        // Animate number change
        const duration = 1000;
        const startTime = Date.now();
        const difference = newScore - oldScore;
        
        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentScore = oldScore + (difference * progress);
            element.textContent = currentScore.toFixed(1);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.transform = 'scale(1)';
                element.style.color = '';
            }
        };
        
        animate();
    }
    
    animateRankChange(row, oldRank, newRank) {
        if (oldRank === newRank) return;
        
        const direction = newRank < oldRank ? 'up' : 'down';
        const positions = Math.abs(newRank - oldRank);
        
        // Add visual indicator
        const rankChangeEl = document.createElement('div');
        rankChangeEl.className = `rank-change-indicator ${direction}`;
        rankChangeEl.innerHTML = `<i class="fas fa-arrow-${direction}"></i> ${positions}`;
        
        row.querySelector('.rank-display').appendChild(rankChangeEl);
        
        // Remove after animation
        setTimeout(() => {
            rankChangeEl.remove();
        }, 3000);
        
        // Add to updates feed
        const teamName = row.querySelector('.team-name').textContent;
        this.addUpdateToFeed(`${teamName} moved ${direction} ${positions} position${positions > 1 ? 's' : ''}`, 'rank');
    }
    
    addUpdateToFeed(message, type) {
        const feed = document.getElementById('updates-feed');
        const updateItem = document.createElement('div');
        updateItem.className = `update-item ${type}`;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });
        
        updateItem.innerHTML = `
            <span class="update-time">${timeString}</span>
            <span class="update-message">${message}</span>
        `;
        
        // Add to top of feed
        feed.insertBefore(updateItem, feed.firstChild);
        
        // Keep only last 10 updates
        while (feed.children.length > 10) {
            feed.removeChild(feed.lastChild);
        }
    }
    
    updateLiveIndicator(connected) {
        const indicator = document.querySelector('.live-indicator');
        if (connected) {
            indicator.classList.add('connected');
            indicator.classList.remove('disconnected');
        } else {
            indicator.classList.add('disconnected');
            indicator.classList.remove('connected');
        }
    }
    
    updateLastUpdateTime() {
        const lastUpdateEl = document.querySelector('.last-update');
        if (lastUpdateEl) {
            const now = new Date();
            lastUpdateEl.textContent = `Updated ${now.toLocaleTimeString()}`;
        }
    }
    
    bindEvents() {
        // Mode selector buttons
        document.querySelectorAll('.mode-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const mode = e.target.dataset.mode;
                window.location.href = `?mode=${mode}`;
            });
        });
        
        // Share functionality
        document.getElementById('share-scoreboard').addEventListener('click', () => {
            document.getElementById('share-modal').classList.add('show');
        });
        
        document.getElementById('copy-url').addEventListener('click', () => {
            const urlInput = document.getElementById('share-url');
            urlInput.select();
            document.execCommand('copy');
            
            const btn = document.getElementById('copy-url');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        });
        
        // Share options
        document.querySelectorAll('.share-option').forEach(option => {
            option.addEventListener('click', (e) => {
                const shareType = e.currentTarget.dataset.share;
                this.handleShare(shareType);
            });
        });
    }
    
    handleShare(type) {
        const url = window.location.href;
        const title = document.title;
        
        switch (type) {
            case 'link':
                navigator.clipboard.writeText(url);
                this.showNotification('Link copied to clipboard!');
                break;
                
            case 'qr':
                document.querySelector('.qr-code-container').style.display = 
                    document.querySelector('.qr-code-container').style.display === 'none' ? 'block' : 'none';
                break;
                
            case 'twitter':
                const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}&hashtags=SciBOTICS2025,STEMEducation`;
                window.open(twitterUrl, '_blank');
                break;
                
            case 'facebook':
                const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                window.open(facebookUrl, '_blank');
                break;
        }
    }
    
    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    fallbackToPolling() {
        // Fallback to HTTP polling if WebSocket fails
        setInterval(() => {
            fetch(`/scoreboard/${this.sessionId}/api`)
                .then(response => response.json())
                .then(data => {
                    this.updateScoreboard(data);
                })
                .catch(error => {
                    console.error('Polling error:', error);
                });
        }, this.updateInterval);
    }
    
    startUpdateTimer() {
        setInterval(() => {
            if (this.lastUpdateTime && Date.now() - this.lastUpdateTime > this.updateInterval * 2) {
                // Haven't received updates recently, try to reconnect
                if (this.ws && this.ws.readyState !== WebSocket.OPEN) {
                    this.connectWebSocket();
                }
            }
        }, 10000); // Check every 10 seconds
    }
    
    attemptReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.fallbackToPolling();
            return;
        }
        
        this.reconnectAttempts++;
        setTimeout(() => {
            this.connectWebSocket();
        }, 2000 * this.reconnectAttempts);
    }
    
    sortStandings() {
        const tbody = document.getElementById('standings-tbody');
        const rows = Array.from(tbody.querySelectorAll('.team-row'));
        
        rows.sort((a, b) => {
            const rankA = parseInt(a.dataset.rank);
            const rankB = parseInt(b.dataset.rank);
            return rankA - rankB;
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
    
    initializeAnimations() {
        // Add entrance animations
        document.querySelectorAll('.team-row').forEach((row, index) => {
            row.style.animationDelay = `${index * 0.1}s`;
            row.classList.add('fade-in');
        });
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.scoreboardViewer = new LiveScoreboardViewer();
});
</script>

<style>
.live-scoreboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Roboto', sans-serif;
}

.scoreboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.competition-branding {
    display: flex;
    align-items: center;
    gap: 30px;
}

.competition-logo {
    height: 80px;
    filter: brightness(0) invert(1);
}

.competition-title {
    font-size: 2.5rem;
    font-weight: 300;
    margin: 0 0 10px 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.session-title {
    font-size: 1.8rem;
    font-weight: 400;
    margin: 0 0 10px 0;
}

.competition-details {
    display: flex;
    gap: 20px;
    font-size: 1.1rem;
    opacity: 0.9;
}

.scoreboard-controls {
    text-align: right;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    margin-bottom: 20px;
}

.live-dot {
    width: 12px;
    height: 12px;
    background: #ff4757;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.live-indicator.connected .live-dot {
    background: #2ed573;
}

.live-indicator.disconnected .live-dot {
    background: #ffa502;
}

.session-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-item {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.scoreboard-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

.standings-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-title {
    background: #f8f9fa;
    padding: 20px 30px;
    margin: 0;
    font-size: 1.3rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #dee2e6;
}

.standings-table-container {
    overflow-x: auto;
}

.standings-table {
    width: 100%;
    border-collapse: collapse;
}

.standings-table th {
    background: #2c3e50;
    color: white;
    padding: 15px 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.standings-table td {
    padding: 15px 10px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.team-row {
    transition: all 0.3s ease;
    animation: fadeIn 0.5s ease-in;
}

.team-row:hover {
    background: #f8f9fa;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.rank-display {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 1.1rem;
}

.rank-medal {
    font-size: 1.2rem;
}

.rank-1 { color: #ffd700; }
.rank-2 { color: #c0c0c0; }
.rank-3 { color: #cd7f32; }

.team-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.team-name {
    font-weight: 600;
    font-size: 1rem;
    color: #2c3e50;
}

.points-behind {
    font-size: 0.8rem;
    color: #e74c3c;
    font-weight: 500;
}

.school-name {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.score-display {
    text-align: center;
}

.score-value {
    font-weight: 700;
    font-size: 1.2rem;
    color: #27ae60;
}

.total-score {
    font-size: 1.5rem !important;
    color: #2c3e50 !important;
}

.score-progress {
    width: 60px;
    height: 4px;
    background: #ecf0f1;
    border-radius: 2px;
    margin: 5px auto 0;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #27ae60, #2ecc71);
    border-radius: 2px;
    transition: width 0.5s ease;
}

.judges-progress {
    text-align: center;
}

.judges-count {
    font-weight: 600;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 5px;
}

.judges-indicators {
    display: flex;
    justify-content: center;
    gap: 3px;
}

.judge-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ecf0f1;
    transition: background 0.3s ease;
}

.judge-indicator.completed {
    background: #27ae60;
}

.trend-indicator {
    text-align: center;
    font-size: 1.2rem;
}

.trend-up { color: #27ae60; }
.trend-down { color: #e74c3c; }
.trend-stable { color: #95a5a6; }

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.live {
    background: #3498db;
    color: white;
}

.status-badge.final {
    background: #27ae60;
    color: white;
}

.scoreboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
}

.sidebar-section h4 {
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #2c3e50;
}

.updates-feed {
    max-height: 300px;
    overflow-y: auto;
}

.update-item {
    padding: 8px 0;
    border-bottom: 1px solid #ecf0f1;
    font-size: 0.9rem;
}

.update-item:last-child {
    border-bottom: none;
}

.update-time {
    color: #7f8c8d;
    font-weight: 600;
    margin-right: 10px;
}

.update-message {
    color: #2c3e50;
}

.podium-mini {
    display: flex;
    justify-content: space-around;
    align-items: end;
    padding: 20px 0;
}

.podium-item {
    text-align: center;
    flex: 1;
}

.podium-item .position {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: white;
    margin: 0 auto 8px;
}

.position-1 .position { background: #ffd700; }
.position-2 .position { background: #c0c0c0; }
.position-3 .position { background: #cd7f32; }

.podium-item .team-name {
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.podium-item .score {
    font-size: 0.9rem;
    color: #27ae60;
    font-weight: 700;
}

.share-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.share-option {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.share-option:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.qr-code-container {
    text-align: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.qr-code {
    width: 120px;
    height: 120px;
}

.qr-help {
    margin-top: 8px;
    font-size: 0.8rem;
    color: #6c757d;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #27ae60;
    color: white;
    padding: 15px 20px;
    border-radius: 6px;
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .scoreboard-content {
        grid-template-columns: 1fr;
    }
    
    .scoreboard-sidebar {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        display: grid;
    }
}

@media (max-width: 768px) {
    .scoreboard-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .competition-branding {
        flex-direction: column;
        gap: 20px;
    }
    
    .competition-title {
        font-size: 1.8rem;
    }
    
    .session-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .standings-table th,
    .standings-table td {
        padding: 8px 4px;
        font-size: 0.8rem;
    }
    
    .team-name {
        font-size: 0.9rem;
    }
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>