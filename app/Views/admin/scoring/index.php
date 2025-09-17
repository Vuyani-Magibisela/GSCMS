<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-scoring">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? 'Competition Scoring System') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? 'Admin access to competition scoring and management') ?></p>
        </div>
        <div class="admin-actions">
            <button class="btn btn-outline-primary" id="refresh-data">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?= $total_teams ?></h4>
                            <p class="card-text">Total Teams</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?= $scoring_stats['completed_scores'] ?></h4>
                            <p class="card-text">Completed Scores</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?= count($pending_scores) ?></h4>
                            <p class="card-text">Pending Scores</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?= count($active_sessions) ?></h4>
                            <p class="card-text">Active Sessions</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-broadcast-tower fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Scoring Sessions -->
    <?php if (!empty($active_sessions)): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-broadcast-tower text-danger"></i>
                Live Scoring Sessions
            </h5>
            <span class="badge badge-danger">
                <span class="live-indicator-dot"></span>
                LIVE
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($active_sessions as $session): ?>
                <div class="col-md-6 mb-3">
                    <div class="session-card border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><?= htmlspecialchars($session['session_name']) ?></h6>
                            <span class="badge badge-<?= $session['status'] === 'active' ? 'success' : 'warning' ?>">
                                <?= ucfirst($session['status']) ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars($session['competition_name']) ?> -
                            <?= htmlspecialchars($session['category_name'] ?? 'All Categories') ?>
                        </p>
                        <?php if ($session['venue_name']): ?>
                            <p class="text-muted small">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($session['venue_name']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary monitor-session"
                                    data-session-id="<?= $session['id'] ?>">
                                <i class="fas fa-eye"></i> Monitor
                            </button>
                            <?php if ($session['status'] === 'active'): ?>
                                <button class="btn btn-sm btn-outline-danger control-session"
                                        data-session-id="<?= $session['id'] ?>"
                                        data-action="stop">
                                    <i class="fas fa-stop"></i> Stop
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Teams Scoring Overview -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list"></i>
                All Teams - Scoring Status
            </h5>
            <div class="header-actions">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" data-filter="all">All Teams</a>
                        <a class="dropdown-item" href="#" data-filter="scored">Scored</a>
                        <a class="dropdown-item" href="#" data-filter="pending">Pending</a>
                        <a class="dropdown-item" href="#" data-filter="in-progress">In Progress</a>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm" id="create-scoring-session">
                    <i class="fas fa-plus"></i> New Session
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="teams-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Team</th>
                            <th>School</th>
                            <th>Category</th>
                            <th>Competition</th>
                            <th>Score Count</th>
                            <th>Average Score</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teams)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-clipboard text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0">No teams found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($teams as $team): ?>
                            <tr class="team-row" data-team-id="<?= $team['id'] ?>" data-status="<?= $team['score_count'] > 0 ? 'scored' : 'pending' ?>">
                                <td>
                                    <div class="team-info">
                                        <strong><?= htmlspecialchars($team['name']) ?></strong>
                                        <br><small class="text-muted">Team #<?= $team['id'] ?></small>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($team['school_name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($team['category_name'] ?? 'N/A') ?></span>
                                </td>
                                <td><?= htmlspecialchars($team['competition_name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?= $team['score_count'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <?php if (isset($team['average_score']) && $team['average_score'] > 0): ?>
                                        <span class="font-weight-bold text-success">
                                            <?= number_format($team['average_score'], 1) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No scores</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($team['score_count'] > 0): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Scored
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-hourglass-half"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary score-team"
                                                data-team-id="<?= $team['id'] ?>"
                                                data-competition-id="<?= $team['competition_id'] ?? '' ?>"
                                                title="Score Team">
                                            <i class="fas fa-clipboard-check"></i>
                                        </button>

                                        <?php if ($team['score_count'] > 0): ?>
                                        <button class="btn btn-outline-info view-scores"
                                                data-team-id="<?= $team['id'] ?>"
                                                title="View All Scores">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php endif; ?>

                                        <button class="btn btn-outline-secondary team-details"
                                                data-team-id="<?= $team['id'] ?>"
                                                title="Team Details">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Judge Assignment Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-user-tie text-primary"></i>
                Judge Assignments
            </h5>
            <div class="header-actions">
                <button class="btn btn-primary btn-sm" id="assign-judge-btn">
                    <i class="fas fa-plus"></i> Assign Judge
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Available Judges -->
                <div class="col-md-4">
                    <h6 class="mb-3">Available Judges (<?= count($available_judges) ?>)</h6>
                    <div class="judges-list">
                        <?php if (empty($available_judges)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-user-slash mb-2"></i>
                                <p class="mb-0">No judges available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($available_judges as $judge): ?>
                            <div class="judge-item border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($judge['email']) ?></small>
                                        <?php if (isset($judge['judge_code']) && $judge['judge_code']): ?>
                                        <br><small class="text-info">Code: <?= htmlspecialchars($judge['judge_code']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-<?= $judge['current_assignments'] == 0 ? 'success' : ($judge['current_assignments'] < 3 ? 'warning' : 'danger') ?>">
                                            <?= $judge['current_assignments'] ?> assignments
                                        </span>
                                        <div class="mt-1">
                                            <button class="btn btn-outline-primary btn-xs quick-assign"
                                                    data-judge-id="<?= $judge['id'] ?>"
                                                    data-judge-name="<?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?>"
                                                    title="Quick Assign">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Current Assignments -->
                <div class="col-md-4">
                    <h6 class="mb-3">Current Assignments (<?= count($judge_assignments) ?>)</h6>
                    <div class="assignments-list">
                        <?php if (empty($judge_assignments)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-clipboard-list mb-2"></i>
                                <p class="mb-0">No active assignments</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($judge_assignments as $assignment): ?>
                            <div class="assignment-item border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($assignment['judge_name']) ?></strong>
                                        <br><small class="text-primary"><?= htmlspecialchars($assignment['competition_name'] ?? 'N/A') ?></small>
                                        <?php if ($assignment['category_name']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($assignment['category_name']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($assignment['judge_type'] !== 'primary'): ?>
                                        <br><span class="badge badge-secondary badge-sm"><?= ucfirst($assignment['judge_type']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($assignment['table_number']): ?>
                                        <br><small>Table: <?= htmlspecialchars($assignment['table_number']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-<?= $assignment['status'] === 'active' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($assignment['status']) ?>
                                        </span>
                                        <div class="mt-1">
                                            <button class="btn btn-outline-danger btn-xs remove-assignment"
                                                    data-assignment-id="<?= $assignment['id'] ?>"
                                                    title="Remove Assignment">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Unassigned Sessions -->
                <div class="col-md-4">
                    <h6 class="mb-3">Sessions Need Judges (<?= count($unassigned_sessions) ?>)</h6>
                    <div class="unassigned-sessions">
                        <?php if (empty($unassigned_sessions)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle mb-2"></i>
                                <p class="mb-0">All sessions have judges</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($unassigned_sessions as $session): ?>
                            <div class="session-item border border-warning rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($session['session_name']) ?></strong>
                                        <br><small class="text-primary"><?= htmlspecialchars($session['competition_name'] ?? 'N/A') ?></small>
                                        <?php if ($session['category_name']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($session['category_name']) ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-warning">
                                            <?= $session['assigned_judges_count'] ?>/<?= $session['max_concurrent_judges'] ?> judges
                                        </small>
                                        <?php if ($session['start_time']): ?>
                                        <br><small>
                                            <i class="fas fa-clock"></i>
                                            <?= date('M j, H:i', strtotime($session['start_time'])) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-<?= $session['status'] === 'active' ? 'danger' : 'warning' ?>">
                                            <?= ucfirst($session['status']) ?>
                                        </span>
                                        <div class="mt-1">
                                            <button class="btn btn-outline-primary btn-xs assign-to-session"
                                                    data-competition-id="<?= $session['competition_id'] ?>"
                                                    data-category-id="<?= $session['category_id'] ?>"
                                                    data-session-name="<?= htmlspecialchars($session['session_name']) ?>"
                                                    title="Assign Judge">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Scores Alert -->
    <?php if (!empty($pending_scores)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle"></i>
                Pending Scores Requiring Attention
                <span class="badge badge-light text-warning ml-2"><?= count($pending_scores) ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team</th>
                            <th>Judge</th>
                            <th>Competition</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_scores as $score): ?>
                        <tr>
                            <td><?= htmlspecialchars($score['team_name']) ?></td>
                            <td><?= htmlspecialchars($score['judge_name']) ?></td>
                            <td><?= htmlspecialchars($score['competition_name']) ?></td>
                            <td>
                                <span class="badge badge-warning">
                                    <?= ucfirst($score['scoring_status']) ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?= date('M j, H:i', strtotime($score['updated_at'])) ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary review-score"
                                        data-score-id="<?= $score['id'] ?>"
                                        data-team-id="<?= $score['team_id'] ?>">
                                    <i class="fas fa-edit"></i> Review
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <?php if (!empty($recent_activity)): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history"></i>
                Recent Scoring Activity (Last 7 Days)
            </h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach (array_slice($recent_activity, 0, 10) as $activity): ?>
                <div class="timeline-item">
                    <div class="timeline-marker">
                        <?php if ($activity['scoring_status'] === 'final'): ?>
                            <i class="fas fa-check text-success"></i>
                        <?php elseif ($activity['scoring_status'] === 'draft'): ?>
                            <i class="fas fa-edit text-warning"></i>
                        <?php else: ?>
                            <i class="fas fa-clock text-info"></i>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">
                            <strong><?= htmlspecialchars($activity['team_name']) ?></strong>
                            scored by <?= htmlspecialchars($activity['judge_name']) ?>
                        </div>
                        <div class="timeline-details text-muted small">
                            <?= htmlspecialchars($activity['competition_name']) ?> -
                            <?= htmlspecialchars($activity['category_name'] ?? 'N/A') ?>
                            <span class="ml-2">Score: <strong><?= number_format($activity['total_score'], 1) ?></strong></span>
                        </div>
                        <div class="timeline-time text-muted small">
                            <?= date('M j, Y H:i', strtotime($activity['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Judge Assignment Modal -->
<div class="modal fade" id="judgeAssignmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Judge to Session</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="judgeAssignmentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assignJudgeSelect">Select Judge</label>
                                <select class="form-control" id="assignJudgeSelect" name="judge_id" required>
                                    <option value="">Choose a judge...</option>
                                    <?php if (empty($available_judges)): ?>
                                        <option value="" disabled>No judges available - check database setup</option>
                                    <?php else: ?>
                                        <?php foreach ($available_judges as $judge): ?>
                                        <option value="<?= $judge['id'] ?>"
                                                data-assignments="<?= $judge['current_assignments'] ?>"
                                                data-email="<?= htmlspecialchars($judge['email']) ?>">
                                            <?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?>
                                            (<?= $judge['current_assignments'] ?> assignments)
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Select a judge to assign to the scoring session</small>
                            </div>

                            <div class="form-group">
                                <label for="assignCompetitionSelect">Competition</label>
                                <select class="form-control" id="assignCompetitionSelect" name="competition_id" required>
                                    <option value="">Select competition...</option>
                                    <?php if (empty($competitions)): ?>
                                        <option value="" disabled>No competitions available - check database setup</option>
                                    <?php else: ?>
                                        <?php foreach ($competitions as $competition): ?>
                                        <option value="<?= $competition['id'] ?>">
                                            <?= htmlspecialchars($competition['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assignCategorySelect">Category</label>
                                <select class="form-control" id="assignCategorySelect" name="category_id" required>
                                    <option value="">Select category...</option>
                                </select>
                                <small class="form-text text-muted">Categories will load based on selected competition</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="assignJudgeType">Judge Type</label>
                                <select class="form-control" id="assignJudgeType" name="judge_type">
                                    <option value="primary">Primary Judge</option>
                                    <option value="secondary">Secondary Judge</option>
                                    <option value="backup">Backup Judge</option>
                                    <option value="head">Head Judge</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assignTableNumber">Table Number (Optional)</label>
                                <input type="text" class="form-control" id="assignTableNumber" name="table_number"
                                       placeholder="e.g., Table 1, A-1, etc.">
                            </div>

                            <div class="form-group">
                                <label for="assignPhase">Competition Phase</label>
                                <select class="form-control" id="assignPhase" name="phase">
                                    <option value="preliminary">Preliminary</option>
                                    <option value="semifinal">Semifinal</option>
                                    <option value="final">Final</option>
                                    <option value="all">All Phases</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assignSpecialInstructions">Special Instructions (Optional)</label>
                                <textarea class="form-control" id="assignSpecialInstructions" name="special_instructions"
                                          rows="3" placeholder="Any special instructions for this judge assignment..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="judgePreview" class="alert alert-info" style="display: none;">
                        <h6>Assignment Preview:</h6>
                        <div id="previewContent"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAssignment">
                    <i class="fas fa-user-plus"></i> Assign Judge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Assignment Modal -->
<div class="modal fade" id="quickAssignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Assign: <span id="quickAssignJudgeName"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="quickAssignForm">
                    <input type="hidden" id="quickAssignJudgeId" name="judge_id">

                    <div class="form-group">
                        <label>Select Session to Assign To:</label>
                        <div id="quickAssignSessionsList">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmQuickAssign">
                    <i class="fas fa-plus"></i> Assign
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// Wait for DOM and libraries to load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Judge Assignment System - Initializing...');

    // Function to initialize when jQuery is ready
    function initJudgeAssignments() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.modal === 'undefined') {
            console.log('Libraries not ready, retrying in 100ms...');
            setTimeout(initJudgeAssignments, 100);
            return;
        }

        console.log('✅ jQuery', jQuery.fn.jquery, 'and Bootstrap modal available');

        // Use jQuery for consistency with Bootstrap
        jQuery(document).ready(function($) {
            console.log('✅ Judge assignment system initialized successfully');

            // Debug information
            console.log('Button exists:', $('#assign-judge-btn').length > 0);
            console.log('Modal exists:', $('#judgeAssignmentModal').length > 0);

            // Main assign judge button
            $('#assign-judge-btn').click(function() {
                console.log('🎯 Assign Judge button clicked');

                // Debug modal element
                var modal = document.getElementById('judgeAssignmentModal');
                console.log('Modal element exists:', modal !== null);
                console.log('Modal HTML:', modal ? modal.outerHTML.substring(0, 200) + '...' : 'null');

                // Check jQuery and Bootstrap
                console.log('jQuery version:', $.fn.jquery);
                console.log('Bootstrap modal available:', typeof $.fn.modal);

                // Try different approaches
                console.log('Attempting to show modal...');
                try {
                    $('#judgeAssignmentModal').modal('show');
                    console.log('Modal show command executed');

                    setTimeout(function() {
                        console.log('After 500ms:');
                        console.log('Modal classes:', modal.className);
                        console.log('Modal display:', getComputedStyle(modal).display);
                        console.log('Modal visibility:', getComputedStyle(modal).visibility);
                        console.log('Modal opacity:', getComputedStyle(modal).opacity);
                    }, 500);

                } catch (e) {
                    console.error('Error showing modal:', e);
                }
            });

            // Score team button
    $('.score-team').click(function() {
        const teamId = $(this).data('team-id');
        const competitionId = $(this).data('competition-id');

        if (teamId && competitionId) {
            window.location.href = `/admin/scoring/${competitionId}/${teamId}`;
        } else {
            alert('Missing team or competition information');
        }
    });

    // View scores button
    $('.view-scores').click(function() {
        const teamId = $(this).data('team-id');
        // Could open a modal or redirect to scores view
        alert('View scores functionality - to be implemented');
    });

    // Team details button
    $('.team-details').click(function() {
        const teamId = $(this).data('team-id');
        window.location.href = `/admin/teams/${teamId}`;
    });

    // Review score button
    $('.review-score').click(function() {
        const scoreId = $(this).data('score-id');
        const teamId = $(this).data('team-id');
        // Open scoring interface for this specific score
        alert('Review score functionality - to be implemented');
    });

    // Monitor session button
    $('.monitor-session').click(function() {
        const sessionId = $(this).data('session-id');
        window.location.href = `/admin/live-scoring/sessions/${sessionId}`;
    });

    // Control session button
    $('.control-session').click(function() {
        const sessionId = $(this).data('session-id');
        const action = $(this).data('action');

        if (confirm(`Are you sure you want to ${action} this session?`)) {
            $.ajax({
                url: `/admin/live-scoring/sessions/${sessionId}/${action}`,
                method: 'POST',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error controlling session. Please try again.');
                }
            });
        }
    });

    // Filter functionality
    $('.dropdown-item[data-filter]').click(function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');

        $('.team-row').each(function() {
            const status = $(this).data('status');

            if (filter === 'all' || status === filter) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Update dropdown button text
        $('.dropdown-toggle').html('<i class="fas fa-filter"></i> ' + $(this).text());
    });

    // Refresh data
    $('#refresh-data').click(function() {
        location.reload();
    });

    // Create scoring session
    $('#create-scoring-session').click(function() {
        window.location.href = '/admin/live-scoring/create';
    });

    // Judge Assignment functionality
    $('#assign-judge-btn').click(function() {
        console.log('Assign Judge button clicked');

        // Check if jQuery and Bootstrap are loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded');
            alert('Error: jQuery is required but not loaded. Please refresh the page.');
            return;
        }

        if (typeof jQuery.fn.modal === 'undefined') {
            console.error('Bootstrap modal is not loaded');
            alert('Error: Bootstrap is required but not loaded. Please refresh the page.');
            return;
        }

        // Check if modal exists
        if ($('#judgeAssignmentModal').length === 0) {
            console.error('Judge assignment modal element not found');
            alert('Error: Modal element not found. Please refresh the page.');
            return;
        }

        console.log('Opening judge assignment modal');
        $('#judgeAssignmentModal').modal('show');
    });

    // Quick assign button
    $('.quick-assign').click(function() {
        const judgeId = $(this).data('judge-id');
        const judgeName = $(this).data('judge-name');

        $('#quickAssignJudgeId').val(judgeId);
        $('#quickAssignJudgeName').text(judgeName);

        // Load available sessions for this judge
        loadAvailableSessionsForQuickAssign();
        $('#quickAssignModal').modal('show');
    });

    // Assign to session button
    $('.assign-to-session').click(function() {
        const competitionId = $(this).data('competition-id');
        const categoryId = $(this).data('category-id');
        const sessionName = $(this).data('session-name');

        // Pre-fill the assignment modal with session data
        $('#assignCompetitionSelect').val(competitionId);
        loadCategoriesForCompetition(competitionId, categoryId);
        $('#judgeAssignmentModal').modal('show');
    });

    // Remove assignment button
    $('.remove-assignment').click(function() {
        const assignmentId = $(this).data('assignment-id');

        if (confirm('Are you sure you want to remove this judge assignment?')) {
            $.ajax({
                url: '/admin/scoring/assignments/' + assignmentId,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error removing assignment. Please try again.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // Use default error message
                    }
                    alert(errorMessage);
                }
            });
        }
    });

    // Competition change handler - load categories
    $('#assignCompetitionSelect').change(function() {
        const competitionId = $(this).val();
        if (competitionId) {
            loadCategoriesForCompetition(competitionId);
        } else {
            $('#assignCategorySelect').html('<option value="">Select category...</option>');
        }
    });

    // Form preview update
    $('#judgeAssignmentForm select, #judgeAssignmentForm input, #judgeAssignmentForm textarea').on('change input', function() {
        updateAssignmentPreview();
    });

    // Confirm assignment
    $('#confirmAssignment').click(function() {
        const form = $('#judgeAssignmentForm');
        const formData = {
            judge_id: $('#assignJudgeSelect').val(),
            competition_id: $('#assignCompetitionSelect').val(),
            category_id: $('#assignCategorySelect').val(),
            judge_type: $('#assignJudgeType').val(),
            table_number: $('#assignTableNumber').val(),
            phase: $('#assignPhase').val(),
            special_instructions: $('#assignSpecialInstructions').val()
        };

        // Validate required fields
        if (!formData.judge_id || !formData.competition_id || !formData.category_id) {
            alert('Please fill in all required fields (Judge, Competition, Category).');
            return;
        }

        // Show loading state
        const $button = $(this);
        const originalText = $button.html();
        $button.html('<i class="fas fa-spinner fa-spin"></i> Assigning...').prop('disabled', true);

        $.ajax({
            url: '/admin/scoring/assign-judge',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    $('#judgeAssignmentModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error assigning judge. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    // Use default error message
                }
                alert(errorMessage);
            },
            complete: function() {
                // Restore button state
                $button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Confirm quick assignment
    $('#confirmQuickAssign').click(function() {
        const selectedSession = $('input[name="quick_assign_session"]:checked');

        if (selectedSession.length === 0) {
            alert('Please select a session to assign the judge to.');
            return;
        }

        const formData = {
            judge_id: $('#quickAssignJudgeId').val(),
            competition_id: selectedSession.data('competition-id'),
            category_id: selectedSession.data('category-id'),
            judge_type: 'primary',
            phase: 'all'
        };

        // Show loading state
        const $button = $(this);
        const originalText = $button.html();
        $button.html('<i class="fas fa-spinner fa-spin"></i> Assigning...').prop('disabled', true);

        $.ajax({
            url: '/admin/scoring/assign-judge',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    $('#quickAssignModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error assigning judge. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    // Use default error message
                }
                alert(errorMessage);
            },
            complete: function() {
                // Restore button state
                $button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Helper functions
    function loadCategoriesForCompetition(competitionId, selectedCategoryId = null) {
        if (!competitionId) return;

        $.ajax({
            url: '/admin/scoring/competitions/' + competitionId + '/categories',
            method: 'GET',
            success: function(response) {
                let options = '<option value="">Select category...</option>';
                if (response.categories && response.categories.length > 0) {
                    response.categories.forEach(function(category) {
                        const selected = selectedCategoryId == category.id ? ' selected' : '';
                        options += `<option value="${category.id}"${selected}>${category.name}</option>`;
                    });
                }
                $('#assignCategorySelect').html(options);
            },
            error: function() {
                $('#assignCategorySelect').html('<option value="">Error loading categories</option>');
            }
        });
    }

    function loadAvailableSessionsForQuickAssign() {
        const judgeId = $('#quickAssignJudgeId').val();
        if (!judgeId) return;

        $.ajax({
            url: '/admin/scoring/judges/' + judgeId + '/availability',
            method: 'GET',
            success: function(response) {
                let content = '<div class="text-muted">No available sessions found.</div>';

                if (response.available_sessions && response.available_sessions.length > 0) {
                    content = '<div class="list-group">';
                    response.available_sessions.forEach(function(session) {
                        content += `
                            <label class="list-group-item">
                                <input type="radio" name="quick_assign_session" value="${session.id}"
                                       data-competition-id="${session.competition_id}"
                                       data-category-id="${session.category_id || ''}" class="mr-2">
                                <div>
                                    <strong>${session.session_name || 'Unnamed Session'}</strong>
                                    <br><small class="text-muted">${session.competition_name}</small>
                                    ${session.category_name ? `<br><small class="text-muted">${session.category_name}</small>` : ''}
                                    ${session.start_time ? `<br><small><i class="fas fa-clock"></i> ${formatDate(session.start_time)}</small>` : ''}
                                </div>
                            </label>`;
                    });
                    content += '</div>';
                }

                $('#quickAssignSessionsList').html(content);
            },
            error: function() {
                $('#quickAssignSessionsList').html('<div class="text-danger">Error loading available sessions.</div>');
            }
        });
    }

    function updateAssignmentPreview() {
        const judgeId = $('#assignJudgeSelect').val();
        const competitionId = $('#assignCompetitionSelect').val();
        const categoryId = $('#assignCategorySelect').val();
        const judgeType = $('#assignJudgeType').val();
        const tableNumber = $('#assignTableNumber').val();
        const phase = $('#assignPhase').val();

        if (!judgeId || !competitionId) {
            $('#judgePreview').hide();
            return;
        }

        const judgeName = $('#assignJudgeSelect option:selected').text();
        const competitionName = $('#assignCompetitionSelect option:selected').text();
        const categoryName = $('#assignCategorySelect option:selected').text();
        const judgeAssignments = $('#assignJudgeSelect option:selected').data('assignments');

        let previewContent = `
            <strong>Judge:</strong> ${judgeName}<br>
            <strong>Competition:</strong> ${competitionName}<br>
            <strong>Category:</strong> ${categoryName || 'All Categories'}<br>
            <strong>Role:</strong> ${judgeType.charAt(0).toUpperCase() + judgeType.slice(1)} Judge<br>
            <strong>Phase:</strong> ${phase.charAt(0).toUpperCase() + phase.slice(1)}<br>
        `;

        if (tableNumber) {
            previewContent += `<strong>Table:</strong> ${tableNumber}<br>`;
        }

        previewContent += `<strong>Current Assignments:</strong> ${judgeAssignments} assignments`;

        if (judgeAssignments >= 3) {
            previewContent += ' <span class="text-warning">(Heavy workload)</span>';
        }

        $('#previewContent').html(previewContent);
        $('#judgePreview').show();
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    // Reset modals when closed
    $('#judgeAssignmentModal').on('hidden.bs.modal', function() {
        $('#judgeAssignmentForm')[0].reset();
        $('#judgePreview').hide();
    });

    $('#quickAssignModal').on('hidden.bs.modal', function() {
        $('#quickAssignForm')[0].reset();
        $('#quickAssignSessionsList').empty();
    });
            // Add other essential functionality here
            console.log('All event handlers attached successfully');
        });
    }

    // Start the initialization process
    initJudgeAssignments();
});

// TEMPORARILY COMMENTED OUT FALLBACK HANDLERS
/*
// Fallback event handler using GSCMS ready event
document.addEventListener('gscms:ready', function() {
    console.log('🚀 GSCMS READY EVENT FIRED - Setting up fallback handlers');

    const assignBtn = document.getElementById('assign-judge-btn');
    const modal = document.getElementById('judgeAssignmentModal');

    if (assignBtn) {
        console.log('Found assign-judge-btn element, adding GSCMS fallback click listener');
        assignBtn.addEventListener('click', function() {
            console.log('🎯 GSCMS FALLBACK: Assign Judge button clicked!');

            if (modal && typeof jQuery !== 'undefined' && typeof jQuery.fn.modal !== 'undefined') {
                console.log('Opening modal with GSCMS fallback handler');
                jQuery(modal).modal('show');
            } else {
                alert('Assign Judge button was clicked! (GSCMS fallback - modal not available)');
            }
        });
    } else {
        console.log('assign-judge-btn element not found in GSCMS ready handler');
    }
});

// Additional window load fallback
window.addEventListener('load', function() {
    console.log('WINDOW LOAD EVENT FIRED');

    // Additional retry after window load
    setTimeout(function() {
        if (typeof jQuery !== 'undefined') {
            console.log('Window load: jQuery is now available');
            initJudgeAssignments();
        } else {
            console.log('Window load: jQuery still not available');
        }
    }, 500);

    const assignBtn = document.getElementById('assign-judge-btn');
    if (assignBtn) {
        console.log('Found assign-judge-btn element, adding window load fallback listener');
        assignBtn.addEventListener('click', function() {
            console.log('🎯 WINDOW LOAD FALLBACK: Assign Judge button clicked!');

            if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                jQuery('#judgeAssignmentModal').modal('show');
            } else {
                alert('Assign Judge button was clicked! (Window load fallback - jQuery not available)');
            }
        });
    } else {
        console.log('assign-judge-btn element not found in window load handler');
    }
});
*/
</script>

<style>
/* Bootstrap Modal Fix - Override conflicting admin modal styles */
/* The admin CSS has a .modal class that conflicts with Bootstrap - override it */
#judgeAssignmentModal.modal.fade,
#quickAssignModal.modal.fade {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: auto !important;
    bottom: auto !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 1050 !important;
    display: none !important;
    align-items: flex-start !important;
    justify-content: center !important;
    opacity: 1 !important;
    visibility: visible !important;
    animation: none !important;
    transition: none !important;
}

#judgeAssignmentModal.modal.fade.show,
#quickAssignModal.modal.fade.show {
    display: block !important;
}

#judgeAssignmentModal .modal-dialog,
#quickAssignModal .modal-dialog {
    position: relative !important;
    margin: 1.75rem auto !important;
    max-width: 800px !important;
    width: calc(100% - 1rem) !important;
    pointer-events: auto !important;
    transform: none !important;
    animation: none !important;
}

#judgeAssignmentModal .modal-content,
#quickAssignModal .modal-content {
    background-color: #fff !important;
    border: 1px solid rgba(0,0,0,.2) !important;
    border-radius: 0.3rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    pointer-events: auto !important;
    max-height: none !important;
    overflow: visible !important;
    animation: none !important;
}

#judgeAssignmentModal .modal-header,
#quickAssignModal .modal-header {
    display: flex !important;
    align-items: flex-start !important;
    justify-content: space-between !important;
    padding: 1rem 1rem !important;
    border-bottom: 1px solid #dee2e6 !important;
    border-top-left-radius: calc(0.3rem - 1px) !important;
    border-top-right-radius: calc(0.3rem - 1px) !important;
    background: #fff !important;
}

#judgeAssignmentModal .modal-body,
#quickAssignModal .modal-body {
    position: relative !important;
    flex: 1 1 auto !important;
    padding: 1rem !important;
}

#judgeAssignmentModal .modal-footer,
#quickAssignModal .modal-footer {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    padding: 0.75rem !important;
    border-top: 1px solid #dee2e6 !important;
    border-bottom-right-radius: calc(0.3rem - 1px) !important;
    border-bottom-left-radius: calc(0.3rem - 1px) !important;
    background: #fff !important;
}

.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 1040 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: #000 !important;
}

.modal-backdrop.show {
    opacity: 0.5 !important;
}

/* Additional fixes for admin modal conflicts */
#judgeAssignmentModal .modal-backdrop,
#quickAssignModal .modal-backdrop {
    background: rgba(0,0,0,0.5) !important;
    backdrop-filter: none !important;
}

.live-indicator-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: white;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
    margin-right: 5px;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.session-card {
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.session-card:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 0;
    width: 20px;
    height: 20px;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.timeline-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.timeline-details {
    margin-bottom: 5px;
}

.timeline-time {
    font-size: 0.8rem;
}

.team-info strong {
    display: block;
}

.team-info small {
    font-size: 0.8rem;
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>