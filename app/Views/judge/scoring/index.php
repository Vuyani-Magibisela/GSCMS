<?php
$layout = 'layouts/judge';
ob_start();
?>

<div class="judge-scoring-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-clipboard-check"></i>
                Scoring Dashboard
            </h1>
            <div class="judge-info">
                <span class="judge-name">Welcome, <?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?></span>
                <span class="judge-code"><?= htmlspecialchars($judge['judge_code'] ?? 'J-' . $judge['id']) ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users text-primary"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $total_teams_today ?></span>
                <span class="stat-label">Teams Assigned Today</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle text-success"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $completed_today ?></span>
                <span class="stat-label">Completed Today</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock text-warning"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= $average_scoring_time ?>min</span>
                <span class="stat-label">Avg Scoring Time</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-broadcast-tower text-info"></i>
            </div>
            <div class="stat-details">
                <span class="stat-number"><?= count($active_sessions) ?></span>
                <span class="stat-label">Active Sessions</span>
            </div>
        </div>
    </div>

    <!-- Active Sessions -->
    <?php if (!empty($active_sessions)): ?>
    <div class="section-card">
        <div class="section-header">
            <h2>
                <i class="fas fa-broadcast-tower"></i>
                Live Scoring Sessions
            </h2>
            <span class="live-indicator">
                <span class="live-dot"></span>
                LIVE
            </span>
        </div>
        
        <div class="sessions-grid">
            <?php foreach ($active_sessions as $session): ?>
            <div class="session-card" data-session-id="<?= $session['id'] ?>">
                <div class="session-header">
                    <h3><?= htmlspecialchars($session['session_name']) ?></h3>
                    <span class="session-status status-<?= $session['status'] ?>">
                        <?= ucfirst($session['status']) ?>
                    </span>
                </div>
                
                <div class="session-details">
                    <div class="session-info">
                        <span class="competition"><?= htmlspecialchars($session['competition_name']) ?></span>
                        <span class="category"><?= htmlspecialchars($session['category_name']) ?></span>
                    </div>
                    
                    <div class="session-timing">
                        <i class="fas fa-clock"></i>
                        <?= date('H:i', strtotime($session['start_time'])) ?>
                        <?php if ($session['venue_name']): ?>
                            <span class="venue">@ <?= htmlspecialchars($session['venue_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="session-actions">
                    <?php if ($session['status'] === 'active'): ?>
                        <button class="btn btn-primary btn-sm join-session" data-session-id="<?= $session['id'] ?>">
                            <i class="fas fa-play"></i>
                            Join Session
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-clock"></i>
                            Scheduled
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Assigned Teams -->
    <div class="section-card">
        <div class="section-header">
            <h2>
                <i class="fas fa-clipboard-list"></i>
                Teams to Score
            </h2>
            <div class="section-actions">
                <button class="btn btn-outline-primary btn-sm" id="refresh-assignments">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <div class="teams-table-container">
            <table class="table table-hover teams-table">
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>School</th>
                        <th>Category</th>
                        <th>Competition</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assigned_teams)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-clipboard text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">No teams assigned for scoring today</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($assigned_teams as $team): ?>
                        <tr class="team-row" data-team-id="<?= $team['id'] ?>">
                            <td>
                                <div class="team-info">
                                    <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="school-name"><?= htmlspecialchars($team['school_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-category"><?= htmlspecialchars($team['category_name']) ?></span>
                            </td>
                            <td>
                                <span class="competition-name"><?= htmlspecialchars($team['competition_name']) ?></span>
                            </td>
                            <td>
                                <div class="schedule-info">
                                    <?php if ($team['session_date']): ?>
                                        <div class="date"><?= date('M j', strtotime($team['session_date'])) ?></div>
                                        <?php if ($team['session_time']): ?>
                                            <div class="time"><?= date('H:i', strtotime($team['session_time'])) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">TBD</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $team['scoring_status'] ?>">
                                    <?php if ($team['scoring_status'] === 'scored'): ?>
                                        <i class="fas fa-check"></i> Scored
                                    <?php else: ?>
                                        <i class="fas fa-hourglass-half"></i> Pending
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="team-actions">
                                    <?php if ($team['scoring_status'] === 'scored'): ?>
                                        <button class="btn btn-outline-primary btn-sm edit-score" 
                                                data-team-id="<?= $team['id'] ?>"
                                                data-competition-id="<?= $team['competition_id'] ?? '' ?>">
                                            <i class="fas fa-edit"></i>
                                            Edit Score
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-primary btn-sm start-scoring" 
                                                data-team-id="<?= $team['id'] ?>"
                                                data-competition-id="<?= $team['competition_id'] ?? '' ?>">
                                            <i class="fas fa-play"></i>
                                            Start Scoring
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pending Scores -->
    <?php if (!empty($pending_scores)): ?>
    <div class="section-card">
        <div class="section-header">
            <h2>
                <i class="fas fa-edit"></i>
                Draft Scores
            </h2>
            <span class="badge badge-warning"><?= count($pending_scores) ?> pending</span>
        </div>
        
        <div class="pending-scores-grid">
            <?php foreach ($pending_scores as $score): ?>
            <div class="pending-score-card">
                <div class="score-header">
                    <h4><?= htmlspecialchars($score['team_name']) ?></h4>
                    <span class="score-status"><?= ucfirst($score['scoring_status']) ?></span>
                </div>
                
                <div class="score-details">
                    <div class="school"><?= htmlspecialchars($score['school_name']) ?></div>
                    <div class="current-score">
                        Current Score: <strong><?= number_format($score['total_score'], 1) ?></strong>
                    </div>
                    <div class="last-updated">
                        Updated: <?= date('M j, H:i', strtotime($score['updated_at'])) ?>
                    </div>
                </div>
                
                <div class="score-actions">
                    <button class="btn btn-primary btn-sm continue-scoring"
                            data-score-id="<?= $score['id'] ?>"
                            data-team-id="<?= $score['team_id'] ?>">
                        <i class="fas fa-play"></i>
                        Continue
                    </button>
                    
                    <button class="btn btn-success btn-sm submit-score"
                            data-score-id="<?= $score['id'] ?>"
                            <?= $score['total_score'] < 10 ? 'disabled title="Score too low to submit"' : '' ?>>
                        <i class="fas fa-check"></i>
                        Submit
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <?php if (!empty($recent_activity)): ?>
    <div class="section-card">
        <div class="section-header">
            <h2>
                <i class="fas fa-history"></i>
                Recent Activity
            </h2>
        </div>
        
        <div class="activity-timeline">
            <?php foreach ($recent_activity as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <?php if ($activity['scoring_status'] === 'submitted'): ?>
                        <i class="fas fa-check text-success"></i>
                    <?php elseif ($activity['scoring_status'] === 'in_progress'): ?>
                        <i class="fas fa-edit text-warning"></i>
                    <?php else: ?>
                        <i class="fas fa-save text-info"></i>
                    <?php endif; ?>
                </div>
                
                <div class="activity-content">
                    <div class="activity-title">
                        <?= htmlspecialchars($activity['team_name']) ?>
                        <span class="activity-action">
                            <?php
                            switch ($activity['scoring_status']) {
                                case 'submitted': echo 'scored and submitted'; break;
                                case 'in_progress': echo 'scoring in progress'; break;
                                default: echo 'draft saved'; break;
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="activity-details">
                        <?= htmlspecialchars($activity['competition_name']) ?>
                        <span class="score">Score: <?= number_format($activity['total_score'], 1) ?></span>
                    </div>
                    
                    <div class="activity-time">
                        <?php if ($activity['submitted_at']): ?>
                            <?= date('M j, H:i', strtotime($activity['submitted_at'])) ?>
                        <?php else: ?>
                            <?= date('M j, H:i', strtotime($activity['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modals and overlays would be included here -->

<script>
$(document).ready(function() {
    // Start scoring button click
    $('.start-scoring').click(function() {
        const teamId = $(this).data('team-id');
        const competitionId = $(this).data('competition-id');
        
        if (teamId && competitionId) {
            window.location.href = `/judge/scoring/${competitionId}/${teamId}`;
        }
    });
    
    // Edit score button click
    $('.edit-score').click(function() {
        const teamId = $(this).data('team-id');
        const competitionId = $(this).data('competition-id');
        
        if (teamId && competitionId) {
            window.location.href = `/judge/scoring/${competitionId}/${teamId}`;
        }
    });
    
    // Continue scoring button click
    $('.continue-scoring').click(function() {
        const scoreId = $(this).data('score-id');
        const teamId = $(this).data('team-id');
        
        // This would redirect to the scoring interface with the existing score
        window.location.href = `/judge/scoring/continue/${scoreId}`;
    });
    
    // Submit score button click
    $('.submit-score').click(function() {
        const scoreId = $(this).data('score-id');
        
        if (confirm('Are you sure you want to submit this score? You may not be able to edit it afterwards.')) {
            // AJAX call to submit score
            $.ajax({
                url: '/judge/scoring/submit',
                method: 'POST',
                data: { score_id: scoreId },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error submitting score: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error submitting score. Please try again.');
                }
            });
        }
    });
    
    // Join session button click
    $('.join-session').click(function() {
        const sessionId = $(this).data('session-id');
        // This would connect to the live scoring session
        window.location.href = `/judge/scoring/session/${sessionId}`;
    });
    
    // Refresh assignments
    $('#refresh-assignments').click(function() {
        location.reload();
    });
    
    // Auto-refresh every 2 minutes for live updates
    setInterval(function() {
        $('.live-indicator .live-dot').fadeOut().fadeIn();
        // Could also update session statuses via AJAX
    }, 120000);
});
</script>

<style>
.judge-scoring-dashboard {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 300;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.judge-info {
    text-align: right;
}

.judge-name {
    display: block;
    font-size: 1.2rem;
    font-weight: 500;
}

.judge-code {
    display: block;
    opacity: 0.8;
    font-size: 0.9rem;
}

.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 2.5rem;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 600;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.section-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.section-header {
    padding: 20px 30px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #dc3545;
    font-size: 0.9rem;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #dc3545;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.sessions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 30px;
}

.session-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.session-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.session-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.session-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-scheduled {
    background: #fff3cd;
    color: #856404;
}

.teams-table-container {
    overflow-x: auto;
}

.teams-table {
    margin: 0;
}

.teams-table th {
    background: #f8f9fa;
    border-top: none;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 15px;
}

.teams-table td {
    padding: 15px;
    vertical-align: middle;
}

.team-name {
    font-weight: 600;
    color: #333;
}

.badge-category {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.schedule-info {
    font-size: 0.9rem;
}

.schedule-info .date {
    font-weight: 600;
}

.schedule-info .time {
    color: #666;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-scored {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.pending-scores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    padding: 30px;
}

.pending-score-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fefefe;
}

.score-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.score-header h4 {
    margin: 0;
    font-size: 1.1rem;
}

.current-score {
    font-weight: 600;
    margin: 10px 0;
}

.last-updated {
    font-size: 0.85rem;
    color: #666;
}

.score-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.activity-timeline {
    padding: 30px;
}

.activity-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.2rem;
    padding-top: 2px;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.activity-action {
    font-weight: 400;
    color: #666;
}

.activity-details {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
}

.activity-time {
    font-size: 0.8rem;
    color: #999;
}

.venue {
    font-style: italic;
    color: #666;
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>