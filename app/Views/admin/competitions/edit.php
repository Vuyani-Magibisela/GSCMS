<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competitions-edit">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? 'Edit Competition') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? $competition['name']) ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competitions/' . $competition['id']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Competition
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Competition Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/competitions/' . $competition['id']) ?>">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Competition Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($competition['name']) ?>" required>
                                    <small class="form-text text-muted">Enter a descriptive name for the competition</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Competition Type</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="standard" <?= $competition['type'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                                        <option value="pilot" <?= $competition['type'] === 'pilot' ? 'selected' : '' ?>>Pilot Programme</option>
                                        <option value="championship" <?= $competition['type'] === 'championship' ? 'selected' : '' ?>>Championship</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the competition objectives and format"><?= htmlspecialchars($competition['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $competition['start_date'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $competition['end_date'] ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_start">Registration Start</label>
                                    <input type="date" class="form-control" id="registration_start" name="registration_start" value="<?= $competition['registration_start'] ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_end">Registration End</label>
                                    <input type="date" class="form-control" id="registration_end" name="registration_end" value="<?= $competition['registration_end'] ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($competition['location'] ?? '') ?>" placeholder="Competition venue or location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_teams">Maximum Teams</label>
                                    <input type="number" class="form-control" id="max_teams" name="max_teams" value="<?= $competition['max_teams'] ?>" min="1" placeholder="Leave blank for unlimited">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_email">Contact Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($competition['contact_email'] ?? '') ?>" placeholder="contact@competition.org">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="draft" <?= $competition['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="active" <?= $competition['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="completed" <?= $competition['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $competition['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="rules">Competition Rules</label>
                            <textarea class="form-control" id="rules" name="rules" rows="4" placeholder="Enter detailed competition rules and regulations"><?= htmlspecialchars($competition['rules'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Competition
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Competition Info -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Competition Info
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>Created:</strong> <?= date('F d, Y', strtotime($competition['created_at'])) ?>
                        </div>
                        <div class="mb-2">
                            <strong>Last Updated:</strong> <?= date('F d, Y g:i A', strtotime($competition['updated_at'])) ?>
                        </div>
                        <div class="mb-2">
                            <strong>Competition ID:</strong> <?= $competition['id'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Guide -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-traffic-light"></i> Status Guide
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <span class="badge badge-warning">Draft</span> - Under development, not visible to public
                        </div>
                        <div class="mb-2">
                            <span class="badge badge-success">Active</span> - Open for registration and participation
                        </div>
                        <div class="mb-2">
                            <span class="badge badge-info">Completed</span> - Competition finished
                        </div>
                        <div class="mb-2">
                            <span class="badge badge-danger">Cancelled</span> - Competition cancelled
                        </div>
                    </div>
                </div>
            </div>

            <!-- Competition Types -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-tags"></i> Competition Types
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>Standard:</strong> Regular competition format
                        </div>
                        <div class="mb-2">
                            <strong>Pilot Programme:</strong> 2025 pilot with specific categories and phases
                        </div>
                        <div class="mb-2">
                            <strong>Championship:</strong> High-level competitive events
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= url('/admin/competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View Competition
                        </a>
                        <a href="<?= url('/admin/teams?competition_id=' . $competition['id']) ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-users"></i> Manage Teams
                        </a>
                        <a href="<?= url('/admin/scoring/' . $competition['id']) ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-clipboard-list"></i> Scoring Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update end date minimum when start date changes
    document.getElementById('start_date').addEventListener('change', function() {
        const endDate = document.getElementById('end_date');
        endDate.setAttribute('min', this.value);

        // If end date is before start date, clear it
        if (endDate.value && endDate.value < this.value) {
            endDate.value = '';
        }
    });

    // Update registration end minimum when registration start changes
    document.getElementById('registration_start').addEventListener('change', function() {
        const regEnd = document.getElementById('registration_end');
        regEnd.setAttribute('min', this.value);

        // If registration end is before registration start, clear it
        if (regEnd.value && regEnd.value < this.value) {
            regEnd.value = '';
        }
    });

    // Initialize minimum dates
    const startDate = document.getElementById('start_date').value;
    if (startDate) {
        document.getElementById('end_date').setAttribute('min', startDate);
    }

    const regStart = document.getElementById('registration_start').value;
    if (regStart) {
        document.getElementById('registration_end').setAttribute('min', regStart);
    }
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>