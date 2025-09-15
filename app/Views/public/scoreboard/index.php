<?php
// Scoreboard Discovery Page - Public scoreboard index
$layout = 'layouts/public';
ob_start();
?>

<div class="scoreboard-discovery">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <i class="fas fa-chart-line hero-icon"></i>
                    Live Scoreboards
                </h1>
                <p class="hero-subtitle">
                    Follow live scoring from SciBOTICS competitions and track real-time results
                </p>
            </div>
        </div>
    </div>

    <!-- Active Sessions -->
    <div class="active-sessions-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-broadcast-tower"></i>
                    Active Live Sessions
                    <span class="live-badge" id="live-count">
                        <?= count($active_sessions) ?>
                    </span>
                </h2>
                <div class="section-actions">
                    <button class="btn btn-outline refresh-btn" id="refresh-sessions" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                        <span class="d-none d-md-inline">Refresh</span>
                    </button>
                    <button class="btn btn-outline" id="qr-scanner-btn" title="Scan QR Code">
                        <i class="fas fa-qrcode"></i>
                        <span class="d-none d-md-inline">Scan QR</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($active_sessions)): ?>
                <div class="sessions-grid" id="sessions-grid">
                    <?php foreach ($active_sessions as $session): ?>
                        <div class="session-card" data-session-id="<?= $session['id'] ?>">
                            <div class="session-header">
                                <div class="session-status">
                                    <span class="status-indicator status-<?= $session['status'] ?>"></span>
                                    <span class="status-text"><?= ucfirst($session['status']) ?></span>
                                </div>
                                <div class="session-type">
                                    <span class="type-badge type-<?= $session['session_type'] ?>">
                                        <?= ucfirst($session['session_type']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="session-content">
                                <h3 class="session-title">
                                    <a href="<?= url("/scoreboard/{$session['id']}") ?>">
                                        <?= htmlspecialchars($session['session_name']) ?>
                                    </a>
                                </h3>
                                
                                <div class="session-details">
                                    <div class="detail-item">
                                        <i class="fas fa-trophy"></i>
                                        <span><?= htmlspecialchars($session['competition_name']) ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-list"></i>
                                        <span><?= htmlspecialchars($session['category_name']) ?></span>
                                    </div>
                                    
                                    <?php if (!empty($session['venue_name'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($session['venue_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <span><?= $session['viewer_count'] ?> viewers</span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-users-cog"></i>
                                        <span><?= $session['teams_scored'] ?> teams scored</span>
                                    </div>
                                </div>
                            </div>

                            <div class="session-actions">
                                <div class="view-modes">
                                    <a href="<?= url("/scoreboard/{$session['id']}") ?>" 
                                       class="mode-btn mode-standard" title="Standard View">
                                        <i class="fas fa-desktop"></i>
                                    </a>
                                    <a href="<?= url("/scoreboard/{$session['id']}?mode=mobile") ?>" 
                                       class="mode-btn mode-mobile" title="Mobile View">
                                        <i class="fas fa-mobile-alt"></i>
                                    </a>
                                    <a href="<?= url("/scoreboard/{$session['id']}?mode=tv") ?>" 
                                       class="mode-btn mode-tv" title="TV Display">
                                        <i class="fas fa-tv"></i>
                                    </a>
                                </div>
                                
                                <div class="share-actions">
                                    <button class="btn btn-sm btn-outline share-btn" 
                                            data-session-id="<?= $session['id'] ?>"
                                            data-session-name="<?= htmlspecialchars($session['session_name']) ?>"
                                            title="Share">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    <a href="<?= url("/scoreboard/{$session['id']}/qr") ?>" 
                                       class="btn btn-sm btn-outline" target="_blank" title="QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-sessions">
                    <div class="no-sessions-content">
                        <i class="fas fa-calendar-times no-sessions-icon"></i>
                        <h3>No Live Sessions Active</h3>
                        <p>Check back later when competitions are in progress</p>
                        <button class="btn btn-primary" id="refresh-sessions-empty">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Access Methods -->
    <div class="access-methods-section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                How to Access Live Scores
            </h2>
            
            <div class="methods-grid">
                <div class="method-card">
                    <div class="method-icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h3>Desktop/Laptop</h3>
                    <p>Visit this page and click on any active session to view the full scoreboard</p>
                </div>
                
                <div class="method-card">
                    <div class="method-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Device</h3>
                    <p>Use your mobile browser or scan QR codes for mobile-optimized views</p>
                </div>
                
                <div class="method-card">
                    <div class="method-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3>QR Code Access</h3>
                    <p>Scan QR codes displayed at venues or generated from this page</p>
                </div>
                
                <div class="method-card">
                    <div class="method-icon">
                        <i class="fas fa-tv"></i>
                    </div>
                    <h3>TV/Display</h3>
                    <p>Use TV mode for large displays and projection screens</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-qrcode"></i>
                    Scan Scoreboard QR Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qr-scanner" class="qr-scanner">
                    <div class="scanner-placeholder">
                        <i class="fas fa-camera"></i>
                        <p>Allow camera access to scan QR codes</p>
                        <button class="btn btn-primary" id="start-scanner">Start Scanner</button>
                    </div>
                </div>
                <div class="scanner-instructions">
                    <h6>Instructions:</h6>
                    <ul>
                        <li>Allow camera access when prompted</li>
                        <li>Point your camera at the QR code</li>
                        <li>The scoreboard will open automatically</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-share-alt"></i>
                    Share Live Scoreboard
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="share-options">
                    <div class="share-option">
                        <label>Direct Link:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="share-url" readonly>
                            <button class="btn btn-outline-secondary" id="copy-url">Copy</button>
                        </div>
                    </div>
                    
                    <div class="share-social">
                        <button class="btn btn-primary btn-social facebook" id="share-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button class="btn btn-info btn-social twitter" id="share-twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <button class="btn btn-success btn-social whatsapp" id="share-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoreboard Discovery Styles */
.scoreboard-discovery {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0 3rem;
    text-align: center;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.hero-icon {
    margin-right: 1rem;
    opacity: 0.9;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.active-sessions-section {
    padding: 3rem 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.live-badge {
    background: #f56565;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: normal;
    animation: pulse 2s infinite;
}

.section-actions {
    display: flex;
    gap: 0.5rem;
}

.sessions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.session-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.session-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.session-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-active {
    background: #48bb78;
    animation: pulse 2s infinite;
}

.status-scheduled {
    background: #ed8936;
}

.status-paused {
    background: #a0aec0;
}

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
}

.type-final {
    background: #fed7d7;
    color: #c53030;
}

.type-semifinal {
    background: #feebc8;
    color: #c05621;
}

.type-qualifying {
    background: #e6fffa;
    color: #285e61;
}

.type-practice {
    background: #e2e8f0;
    color: #4a5568;
}

.session-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.session-title a {
    color: #2d3748;
    text-decoration: none;
}

.session-title a:hover {
    color: #667eea;
}

.session-details {
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: #4a5568;
}

.detail-item i {
    width: 16px;
    text-align: center;
    color: #718096;
}

.session-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.view-modes {
    display: flex;
    gap: 0.5rem;
}

.mode-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #4a5568;
    text-decoration: none;
    transition: all 0.2s ease;
}

.mode-btn:hover {
    background: #f7fafc;
    color: #667eea;
    border-color: #667eea;
}

.share-actions {
    display: flex;
    gap: 0.5rem;
}

.no-sessions {
    text-align: center;
    padding: 4rem 2rem;
}

.no-sessions-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-sessions-icon {
    font-size: 4rem;
    color: #a0aec0;
    margin-bottom: 1.5rem;
}

.access-methods-section {
    background: white;
    padding: 3rem 0;
    border-top: 1px solid #e2e8f0;
}

.methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.method-card {
    text-align: center;
    padding: 2rem 1rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f9f9f9;
}

.method-icon {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.method-card h3 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #2d3748;
}

.method-card p {
    color: #4a5568;
    line-height: 1.5;
}

/* QR Scanner Styles */
.qr-scanner {
    width: 100%;
    height: 300px;
    background: #f7fafc;
    border: 2px dashed #cbd5e0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.scanner-placeholder {
    text-align: center;
    color: #718096;
}

.scanner-placeholder i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.share-options {
    margin-bottom: 1rem;
}

.share-option {
    margin-bottom: 1.5rem;
}

.share-option label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.share-social {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-social {
    flex: 1;
    min-width: 120px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .sessions-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .session-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .view-modes, .share-actions {
        justify-content: center;
    }
    
    .methods-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 2rem 0 1.5rem;
    }
    
    .active-sessions-section {
        padding: 2rem 0;
    }
    
    .session-card {
        padding: 1rem;
    }
    
    .share-social {
        flex-direction: column;
    }
    
    .btn-social {
        min-width: auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize scoreboard discovery functionality
    const scoreboardDiscovery = new ScoreboardDiscoveryManager();
    scoreboardDiscovery.init();
});

class ScoreboardDiscoveryManager {
    constructor() {
        this.refreshInterval = null;
        this.currentShareSession = null;
    }
    
    init() {
        this.setupEventListeners();
        this.checkLiveSessions();
        this.startAutoRefresh();
    }
    
    setupEventListeners() {
        // Refresh buttons
        document.getElementById('refresh-sessions')?.addEventListener('click', () => {
            this.refreshSessions();
        });
        
        document.getElementById('refresh-sessions-empty')?.addEventListener('click', () => {
            this.refreshSessions();
        });
        
        // QR Scanner
        document.getElementById('qr-scanner-btn')?.addEventListener('click', () => {
            this.openQRScanner();
        });
        
        // Share buttons
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const sessionId = e.target.closest('[data-session-id]').dataset.sessionId;
                const sessionName = e.target.closest('[data-session-id]').dataset.sessionName;
                this.openShareModal(sessionId, sessionName);
            });
        });
        
        // Share modal actions
        document.getElementById('copy-url')?.addEventListener('click', () => {
            this.copyToClipboard();
        });
        
        document.getElementById('share-facebook')?.addEventListener('click', () => {
            this.shareToFacebook();
        });
        
        document.getElementById('share-twitter')?.addEventListener('click', () => {
            this.shareToTwitter();
        });
        
        document.getElementById('share-whatsapp')?.addEventListener('click', () => {
            this.shareToWhatsApp();
        });
    }
    
    async refreshSessions() {
        const refreshBtn = document.getElementById('refresh-sessions');
        const refreshIcon = refreshBtn?.querySelector('.fas');
        
        if (refreshIcon) {
            refreshIcon.classList.add('fa-spin');
        }
        
        try {
            const response = await fetch('/scoreboard/api/active-sessions');
            const data = await response.json();
            
            if (data.success) {
                this.updateSessionsDisplay(data.sessions);
                this.updateLiveIndicator(data.sessions.length);
            }
        } catch (error) {
            console.error('Failed to refresh sessions:', error);
        } finally {
            if (refreshIcon) {
                refreshIcon.classList.remove('fa-spin');
            }
        }
    }
    
    updateSessionsDisplay(sessions) {
        const grid = document.getElementById('sessions-grid');
        if (!grid) return;
        
        if (sessions.length === 0) {
            // Show no sessions message
            grid.innerHTML = `
                <div class="no-sessions col-span-full">
                    <div class="no-sessions-content">
                        <i class="fas fa-calendar-times no-sessions-icon"></i>
                        <h3>No Live Sessions Active</h3>
                        <p>Check back later when competitions are in progress</p>
                        <button class="btn btn-primary" id="refresh-sessions-empty">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            `;
            return;
        }
        
        // Update existing sessions or add new ones
        // This would require more complex DOM manipulation
        // For simplicity, we'll reload the page
        window.location.reload();
    }
    
    updateLiveIndicator(count) {
        const liveIndicator = document.getElementById('live-indicator');
        const liveCount = document.getElementById('live-count');
        
        if (liveCount) {
            liveCount.textContent = count;
        }
        
        // Update public navigation indicator
        const publicIndicator = document.getElementById('live-indicator');
        if (publicIndicator) {
            if (count > 0) {
                publicIndicator.classList.add('active');
            } else {
                publicIndicator.classList.remove('active');
            }
        }
    }
    
    checkLiveSessions() {
        // Check if there are active sessions for the navigation indicator
        const sessionCount = document.querySelectorAll('.session-card').length;
        this.updateLiveIndicator(sessionCount);
    }
    
    startAutoRefresh() {
        // Refresh every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.refreshSessions();
        }, 30000);
    }
    
    openQRScanner() {
        const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
        modal.show();
        
        // Initialize QR scanner when modal opens
        document.getElementById('start-scanner')?.addEventListener('click', () => {
            this.initQRScanner();
        });
    }
    
    initQRScanner() {
        // This would integrate with a QR code scanning library
        // For now, show a placeholder
        document.getElementById('qr-scanner').innerHTML = `
            <div class="scanner-active">
                <p>QR Scanner would be initialized here</p>
                <p>In production, this would use a library like QuaggaJS or ZXing</p>
            </div>
        `;
    }
    
    openShareModal(sessionId, sessionName) {
        this.currentShareSession = { sessionId, sessionName };
        const shareUrl = `${window.location.origin}/scoreboard/${sessionId}`;
        
        document.getElementById('share-url').value = shareUrl;
        
        const modal = new bootstrap.Modal(document.getElementById('shareModal'));
        modal.show();
    }
    
    copyToClipboard() {
        const urlField = document.getElementById('share-url');
        urlField.select();
        document.execCommand('copy');
        
        // Show feedback
        const copyBtn = document.getElementById('copy-url');
        const originalText = copyBtn.textContent;
        copyBtn.textContent = 'Copied!';
        copyBtn.classList.add('btn-success');
        
        setTimeout(() => {
            copyBtn.textContent = originalText;
            copyBtn.classList.remove('btn-success');
        }, 2000);
    }
    
    shareToFacebook() {
        const url = document.getElementById('share-url').value;
        const text = `Live scores for ${this.currentShareSession.sessionName}`;
        
        window.open(
            `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
            'facebook-share',
            'width=600,height=400'
        );
    }
    
    shareToTwitter() {
        const url = document.getElementById('share-url').value;
        const text = `Live scores for ${this.currentShareSession.sessionName} 🏆 #SciBOTICS`;
        
        window.open(
            `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`,
            'twitter-share',
            'width=600,height=400'
        );
    }
    
    shareToWhatsApp() {
        const url = document.getElementById('share-url').value;
        const text = `Check out the live scores for ${this.currentShareSession.sessionName}: ${url}`;
        
        window.open(
            `https://wa.me/?text=${encodeURIComponent(text)}`,
            'whatsapp-share'
        );
    }
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>