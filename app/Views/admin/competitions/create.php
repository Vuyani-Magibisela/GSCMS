<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competitions-create">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? 'Create Competition') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competitions') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Competitions
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Competition Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/competitions') ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Competition Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <small class="form-text text-muted">Enter a descriptive name for the competition</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Competition Type</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="standard">Standard</option>
                                        <option value="pilot">Pilot Programme</option>
                                        <option value="championship">Championship</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the competition objectives and format"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_start">Registration Start</label>
                                    <input type="date" class="form-control" id="registration_start" name="registration_start">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_end">Registration End</label>
                                    <input type="date" class="form-control" id="registration_end" name="registration_end">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="Competition venue or location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_teams">Maximum Teams</label>
                                    <input type="number" class="form-control" id="max_teams" name="max_teams" min="1" placeholder="Leave blank for unlimited">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_email">Contact Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="contact@competition.org">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="rules">Competition Rules</label>
                            <textarea class="form-control" id="rules" name="rules" rows="4" placeholder="Enter detailed competition rules and regulations"></textarea>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Competition
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Help Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle"></i> Need Help?
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small">For more advanced competition setup with categories, phases, and detailed configuration, consider using the <strong>Competition Setup Wizard</strong>.</p>
                    <a href="<?= url('/admin/competition-setup/wizard') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-magic"></i> Launch Setup Wizard
                    </a>
                </div>
            </div>

            <!-- Competition Types Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Competition Types
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

            <!-- Status Info -->
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
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum dates to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').setAttribute('min', today);
    document.getElementById('end_date').setAttribute('min', today);
    document.getElementById('registration_start').setAttribute('min', today);
    document.getElementById('registration_end').setAttribute('min', today);

    // Update end date minimum when start date changes
    document.getElementById('start_date').addEventListener('change', function() {
        document.getElementById('end_date').setAttribute('min', this.value);
    });

    // Update registration end minimum when registration start changes
    document.getElementById('registration_start').addEventListener('change', function() {
        document.getElementById('registration_end').setAttribute('min', this.value);
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>