<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competition-wizard-step6">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($page_title ?? 'Competition Setup Wizard') ?></h1>
            <p class="text-muted">Step <?= $step ?> of 6: <?= htmlspecialchars($step_title ?? 'Review & Deploy') ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competition-setup') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <small class="text-muted">Step <?= $step ?> of 6</small>
            <small class="text-success">100% Complete - Ready to Deploy!</small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Configuration Review -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-check"></i> Configuration Review
                    </h5>
                    <p class="card-text small mb-0 mt-2">Review all settings before deploying your competition</p>
                </div>
                <div class="card-body">
                    <!-- Basic Information Review -->
                    <?php if (!empty($review_data['basic_info'])): ?>
                    <div class="review-section mb-4">
                        <h6 class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-info-circle text-primary"></i> Basic Information</span>
                            <a href="<?= url('/admin/competition-setup/wizard/step/1') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?= htmlspecialchars($review_data['basic_info']['name'] ?? 'N/A') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars(ucfirst($review_data['basic_info']['type'] ?? 'standard')) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Year:</strong></td>
                                        <td><?= htmlspecialchars($review_data['basic_info']['year'] ?? date('Y')) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Start Date:</strong></td>
                                        <td><?= isset($review_data['basic_info']['start_date']) ? date('F d, Y', strtotime($review_data['basic_info']['start_date'])) : 'Not set' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>End Date:</strong></td>
                                        <td><?= isset($review_data['basic_info']['end_date']) ? date('F d, Y', strtotime($review_data['basic_info']['end_date'])) : 'Not set' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Scope:</strong></td>
                                        <td><?= htmlspecialchars($review_data['basic_info']['geographic_scope'] ?? 'Provincial') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <?php if (!empty($review_data['basic_info']['description'])): ?>
                        <div class="mt-2">
                            <small><strong>Description:</strong> <?= htmlspecialchars($review_data['basic_info']['description']) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Phase Configuration Review -->
                    <?php if (!empty($review_data['phases'])): ?>
                    <div class="review-section mb-4">
                        <h6 class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-layer-group text-success"></i> Phase Configuration</span>
                            <a href="<?= url('/admin/competition-setup/wizard/step/2') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </h6>
                        <div class="row">
                            <?php
                            $phases = $review_data['phases']['phases'] ?? [];
                            foreach ($phases as $phaseKey => $phase):
                                if (!($phase['enabled'] ?? false)) continue;
                            ?>
                            <div class="col-md-4">
                                <div class="card border-success mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small mb-1"><?= htmlspecialchars($phase['name'] ?? $phaseKey) ?></h6>
                                        <div class="small text-muted">
                                            <?php if (isset($phase['capacity'])): ?>
                                            <div>Capacity: <?= htmlspecialchars($phase['capacity']) ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($phase['duration_weeks'])): ?>
                                            <div>Duration: <?= htmlspecialchars($phase['duration_weeks']) ?> weeks</div>
                                            <?php elseif (isset($phase['duration_days'])): ?>
                                            <div>Duration: <?= htmlspecialchars($phase['duration_days']) ?> days</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Categories Review -->
                    <?php if (!empty($review_data['categories'])): ?>
                    <div class="review-section mb-4">
                        <h6 class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list text-warning"></i> Selected Categories</span>
                            <a href="<?= url('/admin/competition-setup/wizard/step/3') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </h6>
                        <div class="row">
                            <?php
                            $categories = $review_data['categories']['categories'] ?? [];
                            foreach ($categories as $categoryId):
                            ?>
                            <div class="col-md-6 mb-2">
                                <div class="card border-warning">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small mb-1">Category ID: <?= htmlspecialchars($categoryId) ?></h6>
                                        <div class="small text-muted">
                                            <?php if (isset($review_data['categories']['category_config'][$categoryId])): ?>
                                                <?php $config = $review_data['categories']['category_config'][$categoryId]; ?>
                                                <div>Team Size: <?= htmlspecialchars($config['team_size'] ?? '4') ?></div>
                                                <div>Time Limit: <?= htmlspecialchars($config['time_limit_minutes'] ?? '15') ?> min</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Registration Rules Review -->
                    <?php if (!empty($review_data['registration'])): ?>
                    <div class="review-section mb-4">
                        <h6 class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user-plus text-info"></i> Registration Rules</span>
                            <a href="<?= url('/admin/competition-setup/wizard/step/4') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Registration Opens:</strong></td>
                                        <td><?= isset($review_data['registration']['registration_start']) ? date('M d, Y H:i', strtotime($review_data['registration']['registration_start'])) : 'Not set' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Registration Closes:</strong></td>
                                        <td><?= isset($review_data['registration']['registration_end']) ? date('M d, Y H:i', strtotime($review_data['registration']['registration_end'])) : 'Not set' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Max Teams per School:</strong></td>
                                        <td><?= htmlspecialchars($review_data['registration']['max_teams_per_school'] ?? '3') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="small">
                                    <strong>Requirements:</strong>
                                    <?php if (!empty($review_data['registration']['registration_requirements'])): ?>
                                        <ul class="mb-0">
                                            <?php foreach ($review_data['registration']['registration_requirements'] as $requirement): ?>
                                                <li><?= htmlspecialchars(str_replace('_', ' ', ucfirst($requirement))) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">None specified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Competition Rules Review -->
                    <?php if (!empty($review_data['rules'])): ?>
                    <div class="review-section mb-4">
                        <h6 class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-gavel text-danger"></i> Competition Rules</span>
                            <a href="<?= url('/admin/competition-setup/wizard/step/5') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Scoring Method:</strong></td>
                                        <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($review_data['rules']['scoring_method'] ?? 'points_based'))) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Max Score:</strong></td>
                                        <td><?= htmlspecialchars($review_data['rules']['max_score'] ?? '100') ?> points</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Time Limit:</strong></td>
                                        <td><?= htmlspecialchars($review_data['rules']['time_limit'] ?? '15') ?> minutes</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Judges per Team:</strong></td>
                                        <td><?= htmlspecialchars($review_data['rules']['judges_per_team'] ?? '3') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Max Attempts:</strong></td>
                                        <td><?= htmlspecialchars($review_data['rules']['max_attempts'] ?? '3') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Format:</strong></td>
                                        <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($review_data['rules']['competition_format'] ?? 'qualification'))) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Deployment Options -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-rocket"></i> Deployment Options
                    </h5>
                </div>
                <div class="card-body">
                    <form id="deploymentForm" method="POST" action="<?= url('/admin/competition-setup/wizard/deploy') ?>">
                        <div class="form-group">
                            <label class="form-label">Deployment Mode</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deploy_mode" id="deploy_test" value="test">
                                <label class="form-check-label" for="deploy_test">
                                    <strong>Test Mode</strong>
                                    <small class="d-block text-muted">Deploy as draft for testing - not visible to participants</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deploy_mode" id="deploy_production" value="production" checked>
                                <label class="form-check-label" for="deploy_production">
                                    <strong>Production Mode</strong>
                                    <small class="d-block text-muted">Deploy live competition - visible to participants</small>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="create_demo_data" name="create_demo_data" value="1">
                                <label class="form-check-label" for="create_demo_data">
                                    Create sample data for testing
                                </label>
                                <small class="form-text text-muted">Generate sample teams and participants for testing purposes</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_notifications" name="send_notifications" value="1" checked>
                                <label class="form-check-label" for="send_notifications">
                                    Send notifications to administrators
                                </label>
                                <small class="form-text text-muted">Notify relevant administrators about the new competition</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Once deployed, you can still make changes to the competition configuration through the admin panel.
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('/admin/competition-setup/wizard/step/5') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Previous
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-rocket"></i> Deploy Competition
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Deployment Status -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-check-circle"></i> Readiness Check
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> Basic information configured
                        </div>
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> Phases configured
                        </div>
                        <div class="mb-2 <?= !empty($review_data['categories']) ? 'text-success' : 'text-warning' ?>">
                            <i class="fas fa-<?= !empty($review_data['categories']) ? 'check' : 'exclamation-triangle' ?>"></i>
                            Categories selected
                        </div>
                        <div class="mb-2 <?= !empty($review_data['registration']) ? 'text-success' : 'text-warning' ?>">
                            <i class="fas fa-<?= !empty($review_data['registration']) ? 'check' : 'exclamation-triangle' ?>"></i>
                            Registration rules set
                        </div>
                        <div class="mb-2 <?= !empty($review_data['rules']) ? 'text-success' : 'text-warning' ?>">
                            <i class="fas fa-<?= !empty($review_data['rules']) ? 'check' : 'exclamation-triangle' ?>"></i>
                            Competition rules defined
                        </div>

                        <hr class="my-3">

                        <?php
                        $allStepsComplete = !empty($review_data['basic_info']) &&
                                          !empty($review_data['phases']) &&
                                          !empty($review_data['categories']) &&
                                          !empty($review_data['registration']) &&
                                          !empty($review_data['rules']);
                        ?>

                        <div class="text-center">
                            <?php if ($allStepsComplete): ?>
                                <div class="text-success">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                    <div class="mt-2"><strong>Ready to Deploy!</strong></div>
                                </div>
                            <?php else: ?>
                                <div class="text-warning">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    <div class="mt-2"><strong>Some steps incomplete</strong></div>
                                    <small>You can still deploy, but consider completing all steps.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- What Happens Next -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-forward"></i> What Happens Next?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <i class="fas fa-database"></i> Competition record created in database
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-layer-group"></i> Phases and categories configured
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-cog"></i> System configuration applied
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-users"></i> Registration opens according to schedule
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-bell"></i> Notifications sent (if enabled)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions After Deploy -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt"></i> Next Steps
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p>After deployment, you may want to:</p>
                        <ul class="mb-0">
                            <li>Set up judging panels</li>
                            <li>Configure scoring rubrics</li>
                            <li>Test registration process</li>
                            <li>Announce to schools</li>
                            <li>Prepare venue logistics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deployment Progress Modal -->
<div class="modal fade" id="deploymentModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deploying Competition</h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <h6 id="deploymentStatus">Initializing deployment...</h6>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         style="width: 0%"
                         id="deploymentProgress">
                    </div>
                </div>
                <div class="mt-3">
                    <ul class="list-unstyled text-left small" id="deploymentSteps">
                        <li><i class="fas fa-clock text-muted"></i> Creating competition record...</li>
                        <li><i class="fas fa-clock text-muted"></i> Configuring phases...</li>
                        <li><i class="fas fa-clock text-muted"></i> Setting up categories...</li>
                        <li><i class="fas fa-clock text-muted"></i> Applying configurations...</li>
                        <li><i class="fas fa-clock text-muted"></i> Finalizing deployment...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deploymentForm = document.getElementById('deploymentForm');

    deploymentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show deployment modal
        const modal = $('#deploymentModal');
        modal.modal('show');

        // Simulate deployment steps
        simulateDeployment();

        // Submit form
        const formData = new FormData(deploymentForm);

        fetch(deploymentForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update final step
                updateDeploymentStep(5, 'success', 'Deployment completed successfully!');
                document.getElementById('deploymentProgress').style.width = '100%';

                setTimeout(() => {
                    modal.modal('hide');
                    window.location.href = data.redirect_url || '/admin/competitions';
                }, 2000);
            } else {
                updateDeploymentStep(0, 'error', 'Deployment failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            updateDeploymentStep(0, 'error', 'Deployment failed due to network error');
        });
    });

    function simulateDeployment() {
        const steps = [
            'Creating competition record...',
            'Configuring phases...',
            'Setting up categories...',
            'Applying configurations...',
            'Finalizing deployment...'
        ];

        steps.forEach((step, index) => {
            setTimeout(() => {
                updateDeploymentStep(index + 1, 'progress', step);
                document.getElementById('deploymentProgress').style.width = `${((index + 1) / steps.length) * 90}%`;
            }, (index + 1) * 1000);
        });
    }

    function updateDeploymentStep(stepIndex, status, message) {
        document.getElementById('deploymentStatus').textContent = message;

        const stepElements = document.querySelectorAll('#deploymentSteps li');
        stepElements.forEach((element, index) => {
            const icon = element.querySelector('i');
            if (index < stepIndex) {
                icon.className = 'fas fa-check text-success';
            } else if (index === stepIndex - 1) {
                icon.className = status === 'error' ? 'fas fa-times text-danger' : 'fas fa-spinner fa-spin text-primary';
            } else {
                icon.className = 'fas fa-clock text-muted';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>