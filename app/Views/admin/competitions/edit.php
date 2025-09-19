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
                                    <label for="year">Competition Year</label>
                                    <input type="number" class="form-control" id="year" name="year" value="<?= htmlspecialchars($competition['year'] ?? date('Y')) ?>" min="2020" max="2030">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">Competition Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?= $competition['date'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_deadline">Registration Deadline</label>
                                    <input type="datetime-local" class="form-control" id="registration_deadline" name="registration_deadline" value="<?= $competition['registration_deadline'] ? date('Y-m-d\TH:i', strtotime($competition['registration_deadline'])) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="venue_name">Venue Name</label>
                                    <input type="text" class="form-control" id="venue_name" name="venue_name" value="<?= htmlspecialchars($competition['venue_name'] ?? '') ?>" placeholder="Competition venue or location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_participants">Maximum Participants</label>
                                    <input type="number" class="form-control" id="max_participants" name="max_participants" value="<?= $competition['max_participants'] ?>" min="1" placeholder="Leave blank for unlimited">
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
                                        <option value="planned" <?= $competition['status'] === 'planned' ? 'selected' : '' ?>>Planned</option>
                                        <option value="open_registration" <?= $competition['status'] === 'open_registration' ? 'selected' : '' ?>>Open Registration</option>
                                        <option value="registration_closed" <?= $competition['status'] === 'registration_closed' ? 'selected' : '' ?>>Registration Closed</option>
                                        <option value="in_progress" <?= $competition['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="completed" <?= $competition['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $competition['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="competition_rules">Competition Rules</label>
                            <textarea class="form-control" id="competition_rules" name="competition_rules" rows="4" placeholder="Enter detailed competition rules and regulations"><?= htmlspecialchars($competition['competition_rules'] ?? '') ?></textarea>
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