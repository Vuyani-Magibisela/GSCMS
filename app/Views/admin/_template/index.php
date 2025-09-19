<?php
/**
 * Admin View Template - Index Page
 *
 * Copy this file to your feature directory and customize.
 * This template follows the admin layout patterns.
 *
 * USAGE:
 * 1. Copy to app/Views/admin/yourfeature/index.php
 * 2. Replace "Your Feature" with your actual feature name
 * 3. Customize the table columns and data display
 * 4. Update the action buttons and links
 */

$layout = 'layouts/admin';  // ✅ CRITICAL - Must use this exact path
ob_start();
?>

<div class="admin-yourfeature">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? 'Your Feature') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? 'Manage your feature items') ?></p>
        </div>
        <div>
            <a href="<?= $this->url('/admin/yourfeature/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Item
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <?php if (isset($stats) && !empty($stats)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0"><?= htmlspecialchars($stats['total'] ?? 0) ?></h4>
                            <small>Total Items</small>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0"><?= htmlspecialchars($stats['active'] ?? 0) ?></h4>
                            <small>Active</small>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0"><?= htmlspecialchars($stats['inactive'] ?? 0) ?></h4>
                            <small>Inactive</small>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-pause-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="mb-0"><?= htmlspecialchars($stats['draft'] ?? 0) ?></h4>
                            <small>Draft</small>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-edit fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Items Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Items List</h5>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No items found</h5>
                    <p class="text-muted">Create your first item to get started.</p>
                    <a href="<?= $this->url('/admin/yourfeature/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Item
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Related Count</th>
                                <th>Created</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <?php if (!empty($item['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 100)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'secondary';
                                    switch ($item['status']) {
                                        case 'active':
                                            $statusClass = 'success';
                                            break;
                                        case 'inactive':
                                            $statusClass = 'danger';
                                            break;
                                        case 'draft':
                                            $statusClass = 'warning';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= ucfirst(htmlspecialchars($item['status'])) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($item['related_count'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($item['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= $this->url('/admin/yourfeature/' . $item['id']) ?>"
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= $this->url('/admin/yourfeature/' . $item['id'] . '/edit') ?>"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(itemId, itemName) {
    document.getElementById('deleteItemName').textContent = itemName;
    document.getElementById('deleteForm').action = '<?= $this->url('/admin/yourfeature/') ?>' + itemId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>