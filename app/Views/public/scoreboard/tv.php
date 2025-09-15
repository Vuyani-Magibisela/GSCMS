<?php
// TV Scoreboard View - Optimized for large displays and projection
$layout = 'layouts/public';
ob_start();
?>

<div class="tv-scoreboard" data-session-id="<?= $session['id'] ?>">
    <!-- TV Header -->
    <div class="tv-header">
        <div class="header-left">
            <div class="competition-logo">
                <i class="fas fa-robot"></i>
            </div>
            <div class="competition-info">
                <h1 class="competition-title">SciBOTICS 2025</h1>
                <div class="session-details">
                    <span class="session-name"><?= htmlspecialchars($scoreboard['session']['name']) ?></span>
                    <span class="separator">•</span>
                    <span class="category-name"><?= htmlspecialchars($scoreboard['category']) ?></span>
                </div>
            </div>
        </div>
        
        <div class="header-center">
            <div class="live-indicator-tv">
                <span class="live-dot-tv"></span>
                <span class="live-text-tv">LIVE RESULTS</span>
            </div>
        </div>
        
        <div class="header-right">
            <div class="session-stats">
                <div class="stat-item-tv">
                    <span class="stat-value-tv" id="teams-count"><?= count($scoreboard['standings']) ?></span>
                    <span class="stat-label-tv">TEAMS</span>
                </div>
                <div class="stat-item-tv">
                    <span class="stat-value-tv" id="judges-count"><?= $scoreboard['judges_active'] ?? 0 ?></span>
                    <span class="stat-label-tv">JUDGES</span>
                </div>
                <div class="stat-item-tv">
                    <span class="stat-value-tv" id="viewers-count"><?= $scoreboard['viewers'] ?? 1 ?></span>
                    <span class="stat-label-tv">VIEWERS</span>
                </div>
            </div>
            <div class="current-time" id="current-time"></div>
        </div>
    </div>

    <!-- Main Scoreboard -->
    <div class="scoreboard-main">
        <?php if (!empty($scoreboard['standings'])): ?>
            <!-- Top 3 Podium -->
            <div class="podium-section">
                <h2 class="section-title">
                    <i class="fas fa-trophy"></i>
                    TOP 3 LEADERS
                </h2>
                
                <div class="podium">
                    <?php 
                    $topThree = array_slice($scoreboard['standings'], 0, 3);
                    $positions = [1 => 'first', 2 => 'second', 3 => 'third'];
                    ?>
                    
                    <?php if (isset($topThree[1])): // 2nd place ?>
                        <div class="podium-position position-second">
                            <div class="position-number">2</div>
                            <div class="team-info-podium">
                                <div class="team-name-podium"><?= htmlspecialchars($topThree[1]['team_name']) ?></div>
                                <div class="school-name-podium"><?= htmlspecialchars($topThree[1]['school_name']) ?></div>
                                <div class="score-podium"><?= number_format($topThree[1]['total_score'], 1) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($topThree[0])): // 1st place ?>
                        <div class="podium-position position-first">
                            <div class="crown"><i class="fas fa-crown"></i></div>
                            <div class="position-number">1</div>
                            <div class="team-info-podium">
                                <div class="team-name-podium"><?= htmlspecialchars($topThree[0]['team_name']) ?></div>
                                <div class="school-name-podium"><?= htmlspecialchars($topThree[0]['school_name']) ?></div>
                                <div class="score-podium"><?= number_format($topThree[0]['total_score'], 1) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($topThree[2])): // 3rd place ?>
                        <div class="podium-position position-third">
                            <div class="position-number">3</div>
                            <div class="team-info-podium">
                                <div class="team-name-podium"><?= htmlspecialchars($topThree[2]['team_name']) ?></div>
                                <div class="school-name-podium"><?= htmlspecialchars($topThree[2]['school_name']) ?></div>
                                <div class="score-podium"><?= number_format($topThree[2]['total_score'], 1) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Full Rankings -->
            <div class="rankings-section">
                <h2 class="section-title">
                    <i class="fas fa-list-ol"></i>
                    FULL RANKINGS
                </h2>
                
                <div class="rankings-table">
                    <div class="table-header">
                        <div class="col-rank">RANK</div>
                        <div class="col-team">TEAM</div>
                        <div class="col-school">SCHOOL</div>
                        <div class="col-score">SCORE</div>
                        <div class="col-trend">TREND</div>
                    </div>
                    
                    <div class="table-body" id="rankings-tbody">
                        <?php foreach ($scoreboard['standings'] as $index => $team): ?>
                            <div class="table-row rank-<?= $team['rank'] ?>" data-team-id="<?= $team['team_id'] ?>">
                                <div class="col-rank">
                                    <div class="rank-display rank-<?= $team['rank'] ?>">
                                        <?= $team['rank'] ?>
                                        <?php if ($team['rank'] <= 3): ?>
                                            <i class="fas fa-medal rank-medal"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-team">
                                    <div class="team-name-table"><?= htmlspecialchars($team['team_name']) ?></div>
                                    <?php if (!empty($team['last_updated'])): ?>
                                        <div class="last-update">Last scored: <?= timeAgo($team['last_updated']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-school">
                                    <?= htmlspecialchars($team['school_name']) ?>
                                </div>
                                
                                <div class="col-score">
                                    <div class="score-main"><?= number_format($team['total_score'], 1) ?></div>
                                    <?php if (!empty($team['scores'])): ?>
                                        <div class="score-breakdown-tv">
                                            <?php foreach (array_slice($team['scores'], 0, 4) as $criterion => $score): ?>
                                                <span class="criterion-score-tv" title="<?= htmlspecialchars($criterion) ?>">
                                                    <?= number_format($score, 1) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-trend">
                                    <?php if ($team['rank_change'] > 0): ?>
                                        <div class="trend-up">
                                            <i class="fas fa-arrow-up"></i>
                                            <span>+<?= $team['rank_change'] ?></span>
                                        </div>
                                    <?php elseif ($team['rank_change'] < 0): ?>
                                        <div class="trend-down">
                                            <i class="fas fa-arrow-down"></i>
                                            <span><?= $team['rank_change'] ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="trend-stable">
                                            <i class="fas fa-minus"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-scores-tv">
                <div class="no-scores-content-tv">
                    <i class="fas fa-hourglass-start"></i>
                    <h2>Competition Starting Soon</h2>
                    <p>Judges are preparing to evaluate teams</p>
                    <div class="countdown-tv" id="countdown">
                        <div class="countdown-item">
                            <span class="countdown-number" id="countdown-minutes">--</span>
                            <span class="countdown-label">MINUTES</span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-number" id="countdown-seconds">--</span>
                            <span class="countdown-label">SECONDS</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer Info -->
    <div class="tv-footer">
        <div class="footer-left">
            <div class="qr-section">
                <div class="qr-code">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="qr-info">
                    <div class="qr-title">SCAN FOR MOBILE VIEW</div>
                    <div class="qr-url"><?= $_SERVER['HTTP_HOST'] ?>/scoreboard/<?= $session['id'] ?>?mode=mobile</div>
                </div>
            </div>
        </div>
        
        <div class="footer-center">
            <div class="update-status">
                <i class="fas fa-sync-alt" id="update-indicator"></i>
                <span id="last-update-tv">Live updates active</span>
            </div>
        </div>
        
        <div class="footer-right">
            <div class="branding">
                <div class="powered-by">Powered by</div>
                <div class="gscms-logo">GSCMS</div>
            </div>
        </div>
    </div>
</div>

<style>
/* TV Scoreboard Styles */
.tv-scoreboard {
    width: 100vw;
    height: 100vh;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: white;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}

.tv-scoreboard::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at center, rgba(255,255,255,0.02) 0%, transparent 70%);
    pointer-events: none;
}

/* TV Header */
.tv-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem 3rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.competition-logo {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.competition-title {
    font-size: 3rem;
    font-weight: 900;
    margin: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.session-details {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 0.5rem;
    font-weight: 500;
}

.separator {
    margin: 0 1rem;
    opacity: 0.6;
}

.header-center {
    flex: 1;
    text-align: center;
}

.live-indicator-tv {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    background: rgba(239, 68, 68, 0.2);
    padding: 1rem 2rem;
    border-radius: 50px;
    border: 2px solid #ef4444;
    backdrop-filter: blur(10px);
}

.live-dot-tv {
    width: 16px;
    height: 16px;
    background: #ef4444;
    border-radius: 50%;
    animation: pulse 2s infinite;
    box-shadow: 0 0 20px #ef4444;
}

.live-text-tv {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ef4444;
    text-shadow: 0 0 10px #ef4444;
    letter-spacing: 2px;
}

.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1rem;
}

.session-stats {
    display: flex;
    gap: 2rem;
}

.stat-item-tv {
    text-align: center;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.stat-value-tv {
    display: block;
    font-size: 2rem;
    font-weight: 800;
    color: #10b981;
    text-shadow: 0 0 10px #10b981;
}

.stat-label-tv {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.current-time {
    font-size: 1.5rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    font-family: 'Courier New', monospace;
}

/* Main Scoreboard */
.scoreboard-main {
    flex: 1;
    padding: 2rem 3rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
    overflow-y: auto;
}

.section-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.section-title i {
    color: #f59e0b;
    text-shadow: 0 0 10px #f59e0b;
}

/* Podium Section */
.podium-section {
    margin-bottom: 3rem;
}

.podium {
    display: flex;
    justify-content: center;
    align-items: end;
    gap: 2rem;
    height: 300px;
    margin-top: 2rem;
}

.podium-position {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    min-width: 250px;
}

.position-first {
    order: 2;
    transform: translateY(-40px);
}

.position-second {
    order: 1;
}

.position-third {
    order: 3;
}

.crown {
    position: absolute;
    top: -60px;
    font-size: 3rem;
    color: #fbbf24;
    text-shadow: 0 0 20px #fbbf24;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.position-number {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.position-first .position-number {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #92400e;
    border: 3px solid #fbbf24;
}

.position-second .position-number {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
    border: 3px solid #9ca3af;
}

.position-third .position-number {
    background: linear-gradient(135deg, #cd7f32 0%, #a67c00 100%);
    color: white;
    border: 3px solid #cd7f32;
}

.team-info-podium {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 2rem 1.5rem;
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.team-name-podium {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: white;
}

.school-name-podium {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 1rem;
}

.score-podium {
    font-size: 2.5rem;
    font-weight: 900;
    color: #10b981;
    text-shadow: 0 0 20px #10b981;
}

/* Rankings Section */
.rankings-section {
    flex: 1;
}

.rankings-table {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 100px 1fr 300px 200px 120px;
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    font-weight: 800;
    font-size: 1.1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.9);
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.table-body {
    max-height: 600px;
    overflow-y: auto;
}

.table-row {
    display: grid;
    grid-template-columns: 100px 1fr 300px 200px 120px;
    gap: 1rem;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
    position: relative;
}

.table-row:hover {
    background: rgba(255, 255, 255, 0.05);
}

.table-row.updated {
    background: rgba(16, 185, 129, 0.1);
    border-left: 4px solid #10b981;
    animation: flash 2s ease;
}

@keyframes flash {
    0%, 100% { background: rgba(16, 185, 129, 0.1); }
    50% { background: rgba(16, 185, 129, 0.2); }
}

.rank-display {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    position: relative;
}

.rank-display.rank-1 {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #92400e;
    box-shadow: 0 5px 20px rgba(251, 191, 36, 0.4);
}

.rank-display.rank-2 {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
    box-shadow: 0 5px 20px rgba(156, 163, 175, 0.4);
}

.rank-display.rank-3 {
    background: linear-gradient(135deg, #cd7f32 0%, #a67c00 100%);
    color: white;
    box-shadow: 0 5px 20px rgba(205, 127, 50, 0.4);
}

.rank-display:not(.rank-1):not(.rank-2):not(.rank-3) {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.rank-medal {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 1rem;
    color: inherit;
}

.team-name-table {
    font-size: 1.3rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.25rem;
}

.last-update {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
}

.col-school {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.score-main {
    font-size: 2rem;
    font-weight: 900;
    color: #10b981;
    text-shadow: 0 0 10px #10b981;
    margin-bottom: 0.5rem;
}

.score-breakdown-tv {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-start;
}

.criterion-score-tv {
    background: rgba(255, 255, 255, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
}

.trend-up {
    color: #10b981;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
}

.trend-down {
    color: #ef4444;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
}

.trend-stable {
    color: rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* No Scores TV */
.no-scores-tv {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-scores-content-tv {
    text-align: center;
    max-width: 800px;
}

.no-scores-content-tv i {
    font-size: 8rem;
    color: rgba(255, 255, 255, 0.3);
    margin-bottom: 2rem;
}

.no-scores-content-tv h2 {
    font-size: 4rem;
    font-weight: 800;
    margin-bottom: 1rem;
    color: white;
}

.no-scores-content-tv p {
    font-size: 1.8rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 3rem;
}

.countdown-tv {
    display: flex;
    justify-content: center;
    gap: 4rem;
}

.countdown-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 2rem 3rem;
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.countdown-number {
    display: block;
    font-size: 4rem;
    font-weight: 900;
    color: #f59e0b;
    text-shadow: 0 0 20px #f59e0b;
    font-family: 'Courier New', monospace;
}

.countdown-label {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.8);
    text-transform: uppercase;
    letter-spacing: 2px;
    font-weight: 700;
}

/* TV Footer */
.tv-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 3rem;
    background: rgba(0, 0, 0, 0.3);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
}

.qr-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.qr-code {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #1f2937;
}

.qr-info {
    color: rgba(255, 255, 255, 0.8);
}

.qr-title {
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.25rem;
}

.qr-url {
    font-size: 1.2rem;
    font-family: 'Courier New', monospace;
    color: #10b981;
}

.update-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
}

#update-indicator {
    color: #10b981;
    animation: spin 2s linear infinite;
}

#update-indicator.paused {
    animation-play-state: paused;
}

.branding {
    text-align: right;
}

.powered-by {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.gscms-logo {
    font-size: 2rem;
    font-weight: 900;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Animations */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.1);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive Design */
@media (max-width: 1920px) {
    .competition-title {
        font-size: 2.5rem;
    }
    
    .podium {
        height: 250px;
    }
    
    .score-podium {
        font-size: 2rem;
    }
}

@media (max-width: 1366px) {
    .tv-header {
        padding: 1.5rem 2rem;
    }
    
    .scoreboard-main {
        padding: 1.5rem 2rem;
    }
    
    .competition-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .podium {
        height: 200px;
    }
    
    .table-header, .table-row {
        grid-template-columns: 80px 1fr 250px 150px 100px;
        padding: 1rem 1.5rem;
    }
    
    .tv-footer {
        padding: 1rem 2rem;
    }
}

/* Scrollbar styling for webkit browsers */
.table-body::-webkit-scrollbar {
    width: 8px;
}

.table-body::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.table-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.table-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>

<script>
// TV Scoreboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const tvScoreboard = new TVScoreboardManager();
    tvScoreboard.init();
});

class TVScoreboardManager {
    constructor() {
        this.sessionId = document.querySelector('.tv-scoreboard').dataset.sessionId;
        this.ws = null;
        this.lastUpdate = Date.now();
        this.currentTime = null;
        this.updateInterval = null;
        this.autoScrollInterval = null;
        this.isScrolling = false;
    }
    
    init() {
        this.startClock();
        this.initWebSocket();
        this.startAutoScroll();
        this.startUpdateTimer();
        this.setupFullscreenMode();
    }
    
    startClock() {
        this.updateClock();
        setInterval(() => {
            this.updateClock();
        }, 1000);
    }
    
    updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        const clockElement = document.getElementById('current-time');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
    }
    
    initWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname;
        const port = 8080;
        
        try {
            this.ws = new WebSocket(`${protocol}//${host}:${port}?session=${this.sessionId}&viewer=tv`);
            
            this.ws.onopen = () => {
                console.log('TV WebSocket connected');
                this.updateIndicator('connected');
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };
            
            this.ws.onclose = () => {
                console.log('TV WebSocket disconnected');
                this.updateIndicator('disconnected');
                setTimeout(() => this.initWebSocket(), 5000);
            };
            
            this.ws.onerror = (error) => {
                console.error('TV WebSocket error:', error);
                this.updateIndicator('error');
            };
            
        } catch (error) {
            console.error('Failed to initialize WebSocket:', error);
            this.updateIndicator('error');
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
                this.handleSessionEnd();
                break;
            case 'stats_update':
                this.updateStats(data.data);
                break;
        }
        
        this.lastUpdate = Date.now();
        this.updateLastUpdateTime();
    }
    
    updateScoreboard(data) {
        if (data.standings) {
            this.updateStandings(data.standings);
        }
        
        if (data.podium) {
            this.updatePodium(data.podium);
        }
    }
    
    updateStandings(standings) {
        const tbody = document.getElementById('rankings-tbody');
        
        standings.forEach(team => {
            const teamRow = tbody.querySelector(`[data-team-id="${team.team_id}"]`);
            if (teamRow) {
                this.updateTeamRow(teamRow, team);
            }
        });
        
        // Re-sort rows by rank
        this.sortTableRows(tbody);
    }
    
    updateTeamRow(row, teamData) {
        // Update rank
        const rankDisplay = row.querySelector('.rank-display');
        if (rankDisplay) {
            const currentRank = parseInt(rankDisplay.textContent);
            if (currentRank !== teamData.rank) {
                rankDisplay.textContent = teamData.rank;
                rankDisplay.className = `rank-display rank-${teamData.rank}`;
                this.flashElement(rankDisplay);
            }
        }
        
        // Update score
        const scoreMain = row.querySelector('.score-main');
        if (scoreMain) {
            const currentScore = parseFloat(scoreMain.textContent);
            const newScore = parseFloat(teamData.total_score);
            
            if (Math.abs(currentScore - newScore) > 0.1) {
                this.animateScoreChange(scoreMain, newScore);
            }
        }
        
        // Update trend
        this.updateTrend(row, teamData.rank_change);
        
        // Update last update time
        const lastUpdate = row.querySelector('.last-update');
        if (lastUpdate && teamData.last_updated) {
            lastUpdate.textContent = `Last scored: ${this.timeAgo(teamData.last_updated)}`;
        }
        
        // Update breakdown scores
        this.updateScoreBreakdown(row, teamData.scores);
    }
    
    animateScoreChange(element, newScore) {
        element.style.transform = 'scale(1.2)';
        element.style.transition = 'all 0.3s ease';
        element.style.textShadow = '0 0 20px #10b981';
        
        setTimeout(() => {
            element.textContent = newScore.toFixed(1);
            element.style.transform = 'scale(1)';
        }, 150);
        
        setTimeout(() => {
            element.style.textShadow = '0 0 10px #10b981';
        }, 500);
    }
    
    updateTrend(row, rankChange) {
        const trendCol = row.querySelector('.col-trend');
        
        if (rankChange > 0) {
            trendCol.innerHTML = `
                <div class="trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>+${rankChange}</span>
                </div>
            `;
        } else if (rankChange < 0) {
            trendCol.innerHTML = `
                <div class="trend-down">
                    <i class="fas fa-arrow-down"></i>
                    <span>${rankChange}</span>
                </div>
            `;
        } else {
            trendCol.innerHTML = `
                <div class="trend-stable">
                    <i class="fas fa-minus"></i>
                </div>
            `;
        }
    }
    
    updateScoreBreakdown(row, scores) {
        const breakdown = row.querySelector('.score-breakdown-tv');
        if (breakdown && scores) {
            breakdown.innerHTML = '';
            Object.entries(scores).slice(0, 4).forEach(([criterion, score]) => {
                const span = document.createElement('span');
                span.className = 'criterion-score-tv';
                span.title = criterion;
                span.textContent = parseFloat(score).toFixed(1);
                breakdown.appendChild(span);
            });
        }
    }
    
    sortTableRows(tbody) {
        const rows = Array.from(tbody.children);
        rows.sort((a, b) => {
            const rankA = parseInt(a.querySelector('.rank-display').textContent);
            const rankB = parseInt(b.querySelector('.rank-display').textContent);
            return rankA - rankB;
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
    
    highlightTeamUpdate(teamId) {
        const teamRow = document.querySelector(`[data-team-id="${teamId}"]`);
        if (teamRow) {
            teamRow.classList.add('updated');
            setTimeout(() => {
                teamRow.classList.remove('updated');
            }, 3000);
        }
    }
    
    updateStats(stats) {
        document.getElementById('teams-count').textContent = stats.teams_count || 0;
        document.getElementById('judges-count').textContent = stats.judges_active || 0;
        document.getElementById('viewers-count').textContent = stats.viewers || 1;
    }
    
    updatePodium(podiumData) {
        // Update podium displays for top 3
        podiumData.slice(0, 3).forEach((team, index) => {
            const position = index + 1;
            const podiumElement = document.querySelector(`.position-${this.getPositionName(position)}`);
            
            if (podiumElement) {
                const teamName = podiumElement.querySelector('.team-name-podium');
                const schoolName = podiumElement.querySelector('.school-name-podium');
                const score = podiumElement.querySelector('.score-podium');
                
                if (teamName) teamName.textContent = team.team_name;
                if (schoolName) schoolName.textContent = team.school_name;
                if (score) {
                    const currentScore = parseFloat(score.textContent);
                    const newScore = parseFloat(team.total_score);
                    if (Math.abs(currentScore - newScore) > 0.1) {
                        this.animateScoreChange(score, newScore);
                    }
                }
            }
        });
    }
    
    getPositionName(position) {
        const positions = { 1: 'first', 2: 'second', 3: 'third' };
        return positions[position] || '';
    }
    
    animateRankChange(data) {
        const teamRow = document.querySelector(`[data-team-id="${data.team_id}"]`);
        if (teamRow) {
            // Flash animation for rank changes
            this.flashElement(teamRow);
            
            // Move to new position with smooth animation
            setTimeout(() => {
                this.sortTableRows(document.getElementById('rankings-tbody'));
            }, 500);
        }
    }
    
    flashElement(element) {
        element.style.background = 'rgba(16, 185, 129, 0.2)';
        element.style.transform = 'scale(1.02)';
        element.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            element.style.background = '';
            element.style.transform = 'scale(1)';
        }, 1000);
    }
    
    handleSessionEnd() {
        // Update live indicator
        const liveDot = document.querySelector('.live-dot-tv');
        const liveText = document.querySelector('.live-text-tv');
        
        if (liveDot) {
            liveDot.style.backgroundColor = '#ef4444';
            liveDot.style.animation = 'none';
        }
        
        if (liveText) {
            liveText.textContent = 'SESSION ENDED';
            liveText.style.color = '#ef4444';
        }
        
        // Stop auto-scroll
        if (this.autoScrollInterval) {
            clearInterval(this.autoScrollInterval);
        }
    }
    
    startAutoScroll() {
        const tbody = document.getElementById('rankings-tbody');
        if (!tbody) return;
        
        let scrollPosition = 0;
        const scrollStep = 2;
        
        this.autoScrollInterval = setInterval(() => {
            if (this.isScrolling) return;
            
            scrollPosition += scrollStep;
            
            if (scrollPosition >= tbody.scrollHeight - tbody.clientHeight) {
                scrollPosition = 0;
            }
            
            tbody.scrollTop = scrollPosition;
        }, 100);
        
        // Pause auto-scroll on manual interaction
        tbody.addEventListener('wheel', () => {
            this.isScrolling = true;
            setTimeout(() => {
                this.isScrolling = false;
            }, 5000);
        });
    }
    
    startUpdateTimer() {
        this.updateInterval = setInterval(() => {
            this.updateLastUpdateTime();
        }, 10000);
    }
    
    updateLastUpdateTime() {
        const updateElement = document.getElementById('last-update-tv');
        const timeSinceUpdate = Date.now() - this.lastUpdate;
        
        if (timeSinceUpdate < 10000) {
            updateElement.textContent = 'Live updates active';
        } else if (timeSinceUpdate < 60000) {
            updateElement.textContent = `Updated ${Math.floor(timeSinceUpdate / 1000)}s ago`;
        } else {
            updateElement.textContent = `Updated ${Math.floor(timeSinceUpdate / 60000)}m ago`;
        }
    }
    
    updateIndicator(status) {
        const indicator = document.getElementById('update-indicator');
        
        switch (status) {
            case 'connected':
                indicator.style.color = '#10b981';
                indicator.classList.remove('paused');
                break;
            case 'disconnected':
            case 'error':
                indicator.style.color = '#ef4444';
                indicator.classList.add('paused');
                break;
        }
    }
    
    setupFullscreenMode() {
        // Automatically enter fullscreen on load (user interaction required)
        document.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(console.log);
            }
        }, { once: true });
        
        // Handle fullscreen changes
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                console.log('Exited fullscreen mode');
            }
        });
        
        // Keyboard shortcut for fullscreen
        document.addEventListener('keydown', (e) => {
            if (e.key === 'f' || e.key === 'F') {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    document.documentElement.requestFullscreen();
                }
            }
        });
    }
    
    timeAgo(dateString) {
        const now = new Date();
        const past = new Date(dateString);
        const diff = now - past;
        
        if (diff < 60000) return 'just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        return Math.floor(diff / 86400000) + 'd ago';
    }
}

// Helper function for timeAgo in PHP context
function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString);
    const diff = now - past;
    
    if (diff < 60000) return 'just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return Math.floor(diff / 86400000) + 'd ago';
}

// Prevent context menu and selection for kiosk mode
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('selectstart', e => e.preventDefault());
document.addEventListener('dragstart', e => e.preventDefault());
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>