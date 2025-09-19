<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competitions">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? 'Competitions') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competitions/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Competition
            </a>
            <a href="<?= url('/admin/competition-setup/wizard') ?>" class="btn btn-outline-primary">
                <i class="fas fa-magic"></i> Setup Wizard
            </a>
        </div>
    </div>

    <!-- Competition Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?= $stats['total'] ?></h4>
                            <p class="card-text">Total Competitions</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-trophy fa-2x"></i>
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
                            <h4 class="card-title"><?= $stats['active'] ?></h4>
                            <p class="card-text">Active</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play-circle fa-2x"></i>
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
                            <h4 class="card-title"><?= $stats['draft'] ?></h4>
                            <p class="card-text">Draft</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-edit fa-2x"></i>
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
                            <h4 class="card-title"><?= $stats['completed'] ?></h4>
                            <p class="card-text">Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Competitions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">All Competitions</h5>
        </div>
        <div class="card-body">
            <?php if (empty($competitions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                    <h5>No Competitions Found</h5>
                    <p class="text-muted">Get started by creating your first competition or using the setup wizard.</p>
                    <div class="mt-3">
                        <a href="<?= url('/admin/competitions/create') ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Competition
                        </a>
                        <a href="<?= url('/admin/competition-setup/wizard') ?>" class="btn btn-outline-primary ml-2">
                            <i class="fas fa-magic"></i> Setup Wizard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>Teams</th>
                                <th>Participants</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competitions as $competition): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($competition['name']) ?></strong>
                                    <?php if (!empty($competition['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($competition['description'], 0, 60)) ?><?= strlen($competition['description']) > 60 ? '...' : '' ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= ($competition['type'] ?? 'standard') === 'pilot' ? 'info' : 'secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($competition['type'] ?? 'standard')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'draft' => 'warning',
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$competition['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $statusColor ?>">
                                        <?= htmlspecialchars(ucfirst($competition['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $competition['date'] ? date('M d, Y', strtotime($competition['date'])) : 'Not set' ?>
                                    <?php if ($competition['year']): ?>
                                        <br><small class="text-muted">Year: <?= htmlspecialchars($competition['year']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= $competition['team_count'] ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= $competition['participant_count'] ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= url('/admin/competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= url('/admin/competitions/' . $competition['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Edit" onclick="console.log('Edit URL:', this.href); alert('Edit button clicked! URL: ' + this.href);">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($competition['team_count'] == 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                                data-url="<?= url('/admin/competitions/' . $competition['id']) ?>"
                                                data-message="Are you sure you want to delete '<?= htmlspecialchars($competition['name']) ?>'? This action cannot be undone."
                                                title="Delete">
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
</div>

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
                <p id="deleteMessage">Are you sure you want to delete this competition?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete buttons
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            const message = this.dataset.message;

            document.getElementById('deleteMessage').textContent = message;
            document.getElementById('deleteForm').action = url;

            $('#deleteModal').modal('show');
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>