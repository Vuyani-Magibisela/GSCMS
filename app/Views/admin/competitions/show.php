<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competitions-show">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? $competition['name']) ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? 'Competition Details') ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competitions') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Competitions
            </a>
            <a href="<?= url('/admin/competitions/' . $competition['id'] . '/edit') ?>" class="btn btn-primary" onclick="console.log('Edit URL:', this.href); alert('Edit button clicked! URL: ' + this.href);">
                <i class="fas fa-edit"></i> Edit Competition
            </a>
        </div>
    </div>

    <!-- Competition Status Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <?php
            $statusColors = [
                'draft' => 'warning',
                'active' => 'success',
                'completed' => 'info',
                'cancelled' => 'danger'
            ];
            $statusColor = $statusColors[$competition['status']] ?? 'secondary';
            $statusIcons = [
                'draft' => 'fas fa-edit',
                'active' => 'fas fa-play-circle',
                'completed' => 'fas fa-check-circle',
                'cancelled' => 'fas fa-ban'
            ];
            $statusIcon = $statusIcons[$competition['status']] ?? 'fas fa-question-circle';
            ?>
            <div class="alert alert-<?= $statusColor ?> alert-dismissible">
                <i class="<?= $statusIcon ?>"></i>
                <strong>Status: <?= htmlspecialchars(ucfirst($competition['status'])) ?></strong>
                <?php if ($competition['status'] === 'active'): ?>
                    - This competition is currently active and accepting registrations.
                <?php elseif ($competition['status'] === 'draft'): ?>
                    - This competition is in draft mode and not visible to participants.
                <?php elseif ($competition['status'] === 'completed'): ?>
                    - This competition has been completed.
                <?php elseif ($competition['status'] === 'cancelled'): ?>
                    - This competition has been cancelled.
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Competition Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?= $stats['total_teams'] ?></h4>
                            <p class="card-text">Registered Teams</p>
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
                            <h4 class="card-title"><?= $stats['total_participants'] ?></h4>
                            <p class="card-text">Participants</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-friends fa-2x"></i>
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
                            <h4 class="card-title"><?= $stats['total_scores'] ?></h4>
                            <p class="card-text">Total Scores</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
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
                            <h4 class="card-title"><?= $stats['final_scores'] ?></h4>
                            <p class="card-text">Final Scores</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-medal fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Competition Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Competition Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?= htmlspecialchars($competition['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>
                                        <span class="badge badge-<?= ($competition['type'] ?? 'standard') === 'pilot' ? 'info' : 'secondary' ?>">
                                            <?= htmlspecialchars(ucfirst($competition['type'] ?? 'standard')) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Competition Date:</strong></td>
                                    <td><?= $competition['date'] ? date('F d, Y', strtotime($competition['date'])) : 'Not set' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Year:</strong></td>
                                    <td><?= htmlspecialchars($competition['year'] ?? 'Not set') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td><?= htmlspecialchars($competition['venue_name'] ?? 'Not specified') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Registration Deadline:</strong></td>
                                    <td><?= $competition['registration_deadline'] ? date('F d, Y g:i A', strtotime($competition['registration_deadline'])) : 'Not set' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Max Participants:</strong></td>
                                    <td><?= $competition['max_participants'] ? htmlspecialchars($competition['max_participants']) : 'Unlimited' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phase:</strong></td>
                                    <td><?= htmlspecialchars($competition['phase_name'] ?? 'Not specified') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact Email:</strong></td>
                                    <td><?= $competition['contact_email'] ? htmlspecialchars($competition['contact_email']) : 'Not specified' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td><?= date('F d, Y', strtotime($competition['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($competition['competition_rules'])): ?>
                    <div class="mt-3">
                        <h6>Competition Rules</h6>
                        <p><?= nl2br(htmlspecialchars($competition['competition_rules'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Registered Teams -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Registered Teams (<?= count($teams) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($teams)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h6>No Teams Registered</h6>
                            <p class="text-muted">Teams will appear here once they register for this competition.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Team Name</th>
                                        <th>School</th>
                                        <th>Category</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teams as $team): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($team['name']) ?></td>
                                        <td><?= htmlspecialchars($team['school_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($team['category_name'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($team['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= url('/admin/teams/' . $team['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= url('/admin/competitions/' . $competition['id'] . '/edit') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Competition
                        </a>
                        <a href="<?= url('/admin/scoring/' . $competition['id']) ?>" class="btn btn-outline-success">
                            <i class="fas fa-clipboard-list"></i> Scoring Dashboard
                        </a>
                        <a href="<?= url('/admin/teams?competition_id=' . $competition['id']) ?>" class="btn btn-outline-info">
                            <i class="fas fa-users"></i> Manage Teams
                        </a>
                        <?php if ($stats['total_teams'] == 0): ?>
                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i> Delete Competition
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list"></i> Available Categories
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-muted small">No categories configured</p>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small"><?= htmlspecialchars($category['name']) ?></span>
                            <span class="badge badge-secondary"><?= $category['team_count'] ?> teams</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($stats['total_teams'] == 0): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the competition "<?= htmlspecialchars($competition['name']) ?>"?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= url('/admin/competitions/' . $competition['id']) ?>" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Delete Competition</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    $('#deleteModal').modal('show');
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>