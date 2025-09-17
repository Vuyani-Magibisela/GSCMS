<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-scoring-interface" data-team-id="<?= $team['id'] ?>" data-competition-id="<?= $competition['id'] ?>">
    <!-- Header with Team Info -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/admin/scoring">Scoring System</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($team['name']) ?></li>
                </ol>
            </nav>

            <div class="team-header">
                <h1 class="h2 mb-2"><?= htmlspecialchars($team['name']) ?></h1>
                <div class="team-meta mb-3">
                    <span class="badge badge-primary"><?= htmlspecialchars($team['category_name'] ?? 'N/A') ?></span>
                    <span class="text-muted mx-2">•</span>
                    <span class="text-muted"><?= htmlspecialchars($team['school_name'] ?? 'N/A') ?></span>
                    <span class="text-muted mx-2">•</span>
                    <span class="text-muted"><?= htmlspecialchars($competition['name'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-right">
            <div class="admin-controls">
                <div class="mb-2">
                    <small class="text-muted">Admin Scoring Interface</small>
                </div>
                <button class="btn btn-outline-secondary" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-primary" id="new-score-btn">
                    <i class="fas fa-plus"></i> New Score
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Existing Scores Column -->
        <div class="col-lg-8">
            <!-- All Scores for this Team -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i>
                        All Scores for this Team
                        <span class="badge badge-info ml-2"><?= count($all_scores) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($all_scores)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard text-muted mb-3" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">No scores yet</h5>
                            <p class="text-muted">This team hasn't been scored by any judges yet.</p>
                            <button class="btn btn-primary" id="start-scoring-btn">
                                <i class="fas fa-clipboard-check"></i> Start Scoring
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Judge</th>
                                        <th>Role</th>
                                        <th>Game Challenge</th>
                                        <th>Research Challenge</th>
                                        <th>Bonus Points</th>
                                        <th>Penalty Points</th>
                                        <th>Total Score</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_scores as $score): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($score['judge_name']) ?></strong>
                                            <?php if (isset($score['admin_modified']) && $score['admin_modified']): ?>
                                                <br><small class="text-warning">
                                                    <i class="fas fa-user-shield"></i> Admin Modified
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?= ucfirst(str_replace('_', ' ', $score['judge_role'] ?? 'judge')) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light"><?= number_format($score['game_challenge_score'] ?? 0, 1) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light"><?= number_format($score['research_challenge_score'] ?? 0, 1) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light"><?= number_format($score['bonus_points'] ?? 0, 1) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light"><?= number_format($score['penalty_points'] ?? 0, 1) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <strong class="text-primary"><?= number_format($score['total_score'] ?? 0, 1) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'draft' => 'warning',
                                                'in_progress' => 'info',
                                                'final' => 'success',
                                                'validated' => 'success'
                                            ][$score['scoring_status']] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?= $statusClass ?>">
                                                <?= ucfirst($score['scoring_status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('M j, Y H:i', strtotime($score['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary edit-score"
                                                        data-score-id="<?= $score['id'] ?>"
                                                        title="Edit Score">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-info view-details"
                                                        data-score-id="<?= $score['id'] ?>"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($score['scoring_status'] !== 'validated'): ?>
                                                <button class="btn btn-outline-danger delete-score"
                                                        data-score-id="<?= $score['id'] ?>"
                                                        title="Delete Score">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Judge Comparison Analysis -->
            <?php if (!empty($judge_comparison) && count($judge_comparison) > 1): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i>
                        Judge Score Comparison Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="scoreComparisonChart" width="400" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="comparison-stats">
                                <?php
                                $scores = array_column($judge_comparison, 'total_score');
                                $avgScore = array_sum($scores) / count($scores);
                                $maxScore = max($scores);
                                $minScore = min($scores);
                                $stdDev = sqrt(array_sum(array_map(function($x) use ($avgScore) { return pow($x - $avgScore, 2); }, $scores)) / count($scores));
                                ?>
                                <div class="stat-item mb-3">
                                    <h6>Average Score</h6>
                                    <span class="h4 text-primary"><?= number_format($avgScore, 1) ?></span>
                                </div>
                                <div class="stat-item mb-3">
                                    <h6>Score Range</h6>
                                    <span class="text-muted"><?= number_format($minScore, 1) ?> - <?= number_format($maxScore, 1) ?></span>
                                    <small class="text-muted d-block">Spread: <?= number_format($maxScore - $minScore, 1) ?></small>
                                </div>
                                <div class="stat-item mb-3">
                                    <h6>Standard Deviation</h6>
                                    <span class="text-muted"><?= number_format($stdDev, 1) ?></span>
                                    <?php if ($stdDev > 10): ?>
                                        <small class="text-warning d-block">
                                            <i class="fas fa-exclamation-triangle"></i> High variance detected
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="stat-item">
                                    <h6>Total Judges</h6>
                                    <span class="badge badge-info"><?= count($judge_comparison) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar with Quick Actions and Info -->
        <div class="col-lg-4">
            <!-- Quick Score Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($all_scores)): ?>
                        <?php
                        $totalScores = array_column($all_scores, 'total_score');
                        $avgTeamScore = array_sum($totalScores) / count($totalScores);
                        $highestScore = max($totalScores);
                        $lowestScore = min($totalScores);
                        ?>
                        <div class="stat-grid">
                            <div class="stat-item text-center">
                                <div class="stat-value text-primary h4"><?= number_format($avgTeamScore, 1) ?></div>
                                <div class="stat-label text-muted">Average</div>
                            </div>
                            <div class="stat-item text-center">
                                <div class="stat-value text-success h4"><?= number_format($highestScore, 1) ?></div>
                                <div class="stat-label text-muted">Highest</div>
                            </div>
                            <div class="stat-item text-center">
                                <div class="stat-value text-warning h4"><?= number_format($lowestScore, 1) ?></div>
                                <div class="stat-label text-muted">Lowest</div>
                            </div>
                            <div class="stat-item text-center">
                                <div class="stat-value text-info h4"><?= count($all_scores) ?></div>
                                <div class="stat-label text-muted">Total Scores</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line mb-2" style="font-size: 2rem;"></i>
                            <p>No statistics available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Team Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Team Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Team ID:</dt>
                        <dd class="col-sm-7">#<?= $team['id'] ?></dd>

                        <dt class="col-sm-5">School:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($team['school_name'] ?? 'N/A') ?></dd>

                        <dt class="col-sm-5">Category:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-primary"><?= htmlspecialchars($team['category_name'] ?? 'N/A') ?></span>
                        </dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-success"><?= ucfirst($team['status'] ?? 'active') ?></span>
                        </dd>

                        <?php if (isset($team['coach_name'])): ?>
                        <dt class="col-sm-5">Coach:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($team['coach_name']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="/admin/teams/<?= $team['id'] ?>" class="btn btn-outline-primary btn-sm btn-block">
                        <i class="fas fa-info-circle"></i> View Full Team Details
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" id="admin-score-btn">
                            <i class="fas fa-clipboard-check"></i> Score as Admin
                        </button>
                        <button class="btn btn-outline-info" id="export-scores-btn">
                            <i class="fas fa-download"></i> Export Scores
                        </button>
                        <button class="btn btn-outline-warning" id="flag-for-review-btn">
                            <i class="fas fa-flag"></i> Flag for Review
                        </button>
                        <hr>
                        <button class="btn btn-outline-secondary" id="view-rubric-btn">
                            <i class="fas fa-list-check"></i> View Rubric
                        </button>
                        <button class="btn btn-outline-secondary" id="scoring-history-btn">
                            <i class="fas fa-history"></i> Scoring History
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scoring Modal -->
<div class="modal fade" id="scoringModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Score Team: <?= htmlspecialchars($team['name']) ?></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Scoring interface will be loaded here -->
                <div id="scoring-interface-container">
                    Loading scoring interface...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="save-score-btn">
                    <i class="fas fa-save"></i> Save Score
                </button>
                <button type="button" class="btn btn-primary" id="submit-score-btn">
                    <i class="fas fa-check"></i> Submit Final Score
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    const teamId = <?= $team['id'] ?>;
    const competitionId = <?= $competition['id'] ?>;

    // Initialize score comparison chart if data exists
    <?php if (!empty($judge_comparison) && count($judge_comparison) > 1): ?>
    const ctx = document.getElementById('scoreComparisonChart').getContext('2d');
    const scoreComparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($judge_comparison, 'judge_name')) ?>,
            datasets: [{
                label: 'Total Score',
                data: <?= json_encode(array_column($judge_comparison, 'total_score')) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif; ?>

    // Start scoring button
    $('#start-scoring-btn, #new-score-btn, #admin-score-btn').click(function() {
        $('#scoringModal').modal('show');
        loadScoringInterface();
    });

    // Edit score button
    $('.edit-score').click(function() {
        const scoreId = $(this).data('score-id');
        $('#scoringModal').modal('show');
        loadScoringInterface(scoreId);
    });

    // View details button
    $('.view-details').click(function() {
        const scoreId = $(this).data('score-id');
        // Could open a detailed view modal
        alert('Score details view - to be implemented');
    });

    // Delete score button
    $('.delete-score').click(function() {
        const scoreId = $(this).data('score-id');

        if (confirm('Are you sure you want to delete this score? This action cannot be undone.')) {
            $.ajax({
                url: `/admin/scoring/${scoreId}`,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting score: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error deleting score. Please try again.');
                }
            });
        }
    });

    // Export scores
    $('#export-scores-btn').click(function() {
        window.location.href = `/admin/scoring/export?team=${teamId}&competition=${competitionId}`;
    });

    // Flag for review
    $('#flag-for-review-btn').click(function() {
        const reason = prompt('Please enter the reason for flagging this team for review:');
        if (reason) {
            // AJAX call to flag team
            alert('Team flagged for review: ' + reason);
        }
    });

    // View rubric
    $('#view-rubric-btn').click(function() {
        window.open(`/admin/rubrics/view?category=${<?= $team['category_id'] ?? 'null' ?>}`, '_blank');
    });

    // Scoring history
    $('#scoring-history-btn').click(function() {
        window.location.href = `/admin/scoring/history?team=${teamId}`;
    });

    function loadScoringInterface(scoreId = null) {
        const url = scoreId ?
            `/admin/scoring/interface/${competitionId}/${teamId}?edit=${scoreId}` :
            `/admin/scoring/interface/${competitionId}/${teamId}`;

        $('#scoring-interface-container').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');

        $.get(url)
            .done(function(data) {
                $('#scoring-interface-container').html(data);
            })
            .fail(function() {
                $('#scoring-interface-container').html('<div class="alert alert-danger">Error loading scoring interface</div>');
            });
    }

    // Save score
    $('#save-score-btn').click(function() {
        saveScore('draft');
    });

    // Submit final score
    $('#submit-score-btn').click(function() {
        if (confirm('Are you sure you want to submit this as a final score?')) {
            saveScore('final');
        }
    });

    function saveScore(status) {
        // Collect scoring data from the interface
        const scoreData = {
            team_id: teamId,
            competition_id: competitionId,
            category_id: <?= $team['category_id'] ?? 'null' ?>,
            scoring_status: status,
            // Add other score fields based on the interface
        };

        $.ajax({
            url: '/admin/scoring',
            method: 'POST',
            data: JSON.stringify(scoreData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#scoringModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error saving score: ' + response.message);
                }
            },
            error: function() {
                alert('Error saving score. Please try again.');
            }
        });
    }
});
</script>

<style>
.team-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 15px;
}

.team-meta .badge {
    font-size: 0.8rem;
}

.stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-weight: 600;
}

.stat-label {
    font-size: 0.8rem;
}

.comparison-stats .stat-item {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
}

.comparison-stats .stat-item:last-child {
    border-bottom: none;
}

.admin-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

@media (max-width: 768px) {
    .admin-controls {
        align-items: stretch;
        margin-top: 15px;
    }

    .stat-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>