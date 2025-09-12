<?php 
$layout = 'admin';
ob_start(); 
?>

<div class="admin-participant-show">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?>
            </h1>
            <p class="text-muted">Participant Details</p>
        </div>
        <div class="btn-group">
            <a href="/admin/participants" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
            <a href="/admin/participants/<?= $participant['id'] ?>/edit" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Information -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Full Name</h6>
                            <p><?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Age</h6>
                            <p><?= $participant['age'] ? $participant['age'] . ' years old' : 'Not specified' ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Grade</h6>
                            <p><?= htmlspecialchars($participant['grade']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <span class="badge bg-<?= $participant['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($participant['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">School & Team Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">School</h6>
                            <p><?= htmlspecialchars($participant['school_name'] ?? 'Not assigned') ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Team</h6>
                            <p><?= htmlspecialchars($participant['team_name'] ?? 'Not assigned') ?></p>
                        </div>
                        <?php if (isset($participant['coach_first_name'])): ?>
                        <div class="col-md-6">
                            <h6 class="text-muted">Coach</h6>
                            <p><?= htmlspecialchars($participant['coach_first_name'] . ' ' . $participant['coach_last_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Coach Email</h6>
                            <p><?= htmlspecialchars($participant['coach_email']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($scores)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Competition Scores</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Competition</th>
                                    <th>Category</th>
                                    <th>Match</th>
                                    <th>Score</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scores as $score): ?>
                                <tr>
                                    <td><?= htmlspecialchars($score['competition_name']) ?></td>
                                    <td><?= htmlspecialchars($score['category_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($score['match_name'] ?? '-') ?></td>
                                    <td><strong><?= $score['total_score'] ?></strong></td>
                                    <td><?= date('M j, Y', strtotime($score['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h3 class="text-primary"><?= count($scores) ?></h3>
                        <p class="text-muted mb-0">Competitions Participated</p>
                    </div>
                    <?php if (!empty($scores)): ?>
                    <div class="mb-3">
                        <h3 class="text-success"><?= number_format(array_sum(array_column($scores, 'total_score')) / count($scores), 1) ?></h3>
                        <p class="text-muted mb-0">Average Score</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Activity Log</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($activity_log)): ?>
                        <?php foreach ($activity_log as $activity): ?>
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <small class="text-muted">
                                    <?= ucwords(str_replace('_', ' ', $activity['action'])) ?>
                                    <br>
                                    <?= date('M j, Y H:i', strtotime($activity['timestamp'])) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No activity recorded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/admin/participants/<?= $participant['id'] ?>/edit" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Participant
                        </a>
                        <?php if ($participant['status'] === 'active'): ?>
                        <button type="button" class="btn btn-warning" onclick="changeStatus('inactive')">
                            <i class="fas fa-pause me-1"></i> Deactivate
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-success" onclick="changeStatus('active')">
                            <i class="fas fa-play me-1"></i> Activate
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash me-1"></i> Delete Participant
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeStatus(newStatus) {
    if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this participant?`)) {
        // Implement status change functionality
        window.location.href = `/admin/participants/<?= $participant['id'] ?>/edit`;
    }
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete this participant?\n\nThis action cannot be undone.')) {
        fetch(`/admin/participants/<?= $participant['id'] ?>`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/admin/participants';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('An error occurred while deleting the participant.');
        });
    }
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>