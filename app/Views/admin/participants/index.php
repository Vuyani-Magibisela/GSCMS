<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<div class="admin-participants">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Participant Management</h1>
            <p class="text-muted">Manage competition participants across all schools and teams</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fas fa-download me-1"></i> Export
            </button>
            <a href="/admin/participants/create" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Participant
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1"><?= number_format($statistics['total']) ?></h5>
                            <p class="text-muted mb-0">Total Participants</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-user-check text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1"><?= number_format($statistics['active']) ?></h5>
                            <p class="text-muted mb-0">Active Participants</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-user-plus text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1"><?= number_format($statistics['recent']) ?></h5>
                            <p class="text-muted mb-0">New (30 days)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-graduation-cap text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1"><?= count($statistics['by_grade']) ?></h5>
                            <p class="text-muted mb-0">Grade Levels</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/admin/participants" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($filters['search']) ?>" 
                           placeholder="Name, ID number...">
                </div>
                <div class="col-md-3">
                    <label for="school_id" class="form-label">School</label>
                    <select class="form-select" id="school_id" name="school_id">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $filters['school_id'] == $school['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['school_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="team_id" class="form-label">Team</label>
                    <select class="form-select" id="team_id" name="team_id">
                        <option value="">All Teams</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['id'] ?>" <?= $filters['team_id'] == $team['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($team['school_name'] . ' - ' . $team['team_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="/admin/participants" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Participants Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                Participants 
                <small class="text-muted">(<?= number_format($pagination['total_records']) ?> total)</small>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($participants)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">No participants found</h5>
                    <p class="text-muted">Try adjusting your search filters or add a new participant.</p>
                    <a href="/admin/participants/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add First Participant
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>School / Team</th>
                                <th>Grade</th>
                                <th>Age</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $participant): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?>
                                                </div>
                                                <?php if ($participant['id_number']): ?>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($participant['id_number']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($participant['school_name'] ?? 'No School') ?></div>
                                        <?php if ($participant['team_name']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($participant['team_name']) ?></small>
                                        <?php else: ?>
                                            <small class="text-warning">No Team</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            Grade <?= htmlspecialchars($participant['grade']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($participant['age']): ?>
                                            <?= $participant['age'] ?> years
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'suspended' => 'danger',
                                            'deleted' => 'dark'
                                        ];
                                        $statusColor = $statusColors[$participant['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= ucfirst($participant['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('M j, Y', strtotime($participant['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/admin/participants/<?= $participant['id'] ?>" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/admin/participants/<?= $participant['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Delete" 
                                                    onclick="confirmDelete(<?= $participant['id'] ?>, '<?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) ?>')">
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
        
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer">
                <nav aria-label="Participants pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($filters), '', '&') ? '&' . http_build_query(array_filter($filters)) : '' ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= http_build_query(array_filter($filters), '', '&') ? '&' . http_build_query(array_filter($filters)) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($filters), '', '&') ? '&' . http_build_query(array_filter($filters)) : '' ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Participants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" value="csv" id="csv" checked>
                            <label class="form-check-label" for="csv">CSV (Excel)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" value="pdf" id="pdf">
                            <label class="form-check-label" for="pdf">PDF Report</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Include</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" value="contact" id="contact" checked>
                            <label class="form-check-label" for="contact">Contact Information</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" value="scores" id="scores">
                            <label class="form-check-label" for="scores">Competition Scores</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" value="demographics" id="demographics">
                            <label class="form-check-label" for="demographics">Demographics (if consented)</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="exportParticipants()">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(participantId, participantName) {
    if (confirm(`Are you sure you want to delete participant "${participantName}"?\n\nThis action will mark the participant as deleted and cannot be undone.`)) {
        fetch(`/admin/participants/${participantId}`, {
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
                location.reload();
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

function exportParticipants() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    // Add current filters to export
    const currentFilters = new URLSearchParams(window.location.search);
    for (let [key, value] of currentFilters) {
        formData.append(key, value);
    }
    
    // Create download link
    const params = new URLSearchParams(formData);
    const downloadUrl = `/admin/participants/export?${params.toString()}`;
    
    // Open download
    window.open(downloadUrl, '_blank');
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
}

// Auto-submit form on school change to update teams
document.getElementById('school_id').addEventListener('change', function() {
    // You might want to implement AJAX team loading here
    // For now, clear team selection when school changes
    document.getElementById('team_id').value = '';
});
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>