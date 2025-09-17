<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competition-wizard-review">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($page_title ?? 'Competition Setup Review') ?></h1>
            <p class="text-muted">Final review before deployment</p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competition-setup') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Success Alert -->
    <div class="alert alert-success">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3"></i>
            <div>
                <h5 class="alert-heading mb-1">Configuration Complete!</h5>
                <p class="mb-0">Your competition has been configured successfully. Review the settings below and deploy when ready.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Comprehensive Review -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-check"></i> Complete Configuration Review
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Basic Information -->
                    <?php if (!empty($review_data['basic_info'])): ?>
                    <div class="review-section mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-primary mb-0">
                                <i class="fas fa-info-circle"></i> Basic Information
                            </h6>
                            <a href="<?= url('/admin/competition-setup/wizard/step/1') ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Modify
                            </a>
                        </div>
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="font-weight-bold">Competition Name:</td>
                                                <td><?= htmlspecialchars($review_data['basic_info']['name'] ?? 'N/A') ?></td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold">Type:</td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= htmlspecialchars(ucfirst($review_data['basic_info']['type'] ?? 'standard')) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold">Year:</td>
                                                <td><?= htmlspecialchars($review_data['basic_info']['year'] ?? date('Y')) ?></td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold">Geographic Scope:</td>
                                                <td><?= htmlspecialchars($review_data['basic_info']['geographic_scope'] ?? 'Provincial') ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="font-weight-bold">Start Date:</td>
                                                <td><?= isset($review_data['basic_info']['start_date']) ? date('F d, Y', strtotime($review_data['basic_info']['start_date'])) : 'Not set' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold">End Date:</td>
                                                <td><?= isset($review_data['basic_info']['end_date']) ? date('F d, Y', strtotime($review_data['basic_info']['end_date'])) : 'Not set' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold">Contact Email:</td>
                                                <td><?= htmlspecialchars($review_data['basic_info']['contact_email'] ?? 'Not specified') ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <?php if (!empty($review_data['basic_info']['description'])): ?>
                                <hr class="my-2">
                                <div>
                                    <strong>Description:</strong>
                                    <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($review_data['basic_info']['description'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Phase Configuration -->
                    <?php if (!empty($review_data['phases'])): ?>
                    <div class="review-section mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-success mb-0">
                                <i class="fas fa-layer-group"></i> Phase Configuration
                            </h6>
                            <a href="<?= url('/admin/competition-setup/wizard/step/2') ?>" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-edit"></i> Modify
                            </a>
                        </div>
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <div class="row">
                                    <?php
                                    $phases = $review_data['phases']['phases'] ?? [];
                                    foreach ($phases as $phaseKey => $phase):
                                        if (!($phase['enabled'] ?? false)) continue;
                                    ?>
                                    <div class="col-md-4">
                                        <div class="card border-success mb-2">
                                            <div class="card-header py-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($phase['name'] ?? $phaseKey) ?></h6>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="small">
                                                    <?php if (isset($phase['capacity'])): ?>
                                                    <div><strong>Capacity:</strong> <?= htmlspecialchars($phase['capacity']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if (isset($phase['duration_weeks'])): ?>
                                                    <div><strong>Duration:</strong> <?= htmlspecialchars($phase['duration_weeks']) ?> weeks</div>
                                                    <?php elseif (isset($phase['duration_days'])): ?>
                                                    <div><strong>Duration:</strong> <?= htmlspecialchars($phase['duration_days']) ?> days</div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($phase['note'])): ?>
                                                    <div class="text-muted"><?= htmlspecialchars($phase['note']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Other sections can be added similarly... -->
                    <!-- For brevity, I'll include a summary view of remaining sections -->

                    <div class="row">
                        <div class="col-md-6">
                            <!-- Categories Summary -->
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark py-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-list"></i> Categories
                                        <a href="<?= url('/admin/competition-setup/wizard/step/3') ?>" class="btn btn-sm btn-outline-dark float-right">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <?php if (!empty($review_data['categories']['categories'])): ?>
                                        <div class="small">
                                            <strong><?= count($review_data['categories']['categories']) ?> categories selected</strong>
                                        </div>
                                        <ul class="list-unstyled small mt-2 mb-0">
                                            <?php foreach (array_slice($review_data['categories']['categories'], 0, 3) as $categoryId): ?>
                                                <li><i class="fas fa-check text-success"></i> Category ID: <?= htmlspecialchars($categoryId) ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($review_data['categories']['categories']) > 3): ?>
                                                <li class="text-muted">... and <?= count($review_data['categories']['categories']) - 3 ?> more</li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="text-muted small">No categories selected</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Registration Summary -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-plus"></i> Registration
                                        <a href="<?= url('/admin/competition-setup/wizard/step/4') ?>" class="btn btn-sm btn-outline-light float-right">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <?php if (!empty($review_data['registration'])): ?>
                                        <div class="small">
                                            <?php if (isset($review_data['registration']['registration_start'])): ?>
                                            <div><strong>Opens:</strong> <?= date('M d, Y', strtotime($review_data['registration']['registration_start'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($review_data['registration']['registration_end'])): ?>
                                            <div><strong>Closes:</strong> <?= date('M d, Y', strtotime($review_data['registration']['registration_end'])) ?></div>
                                            <?php endif; ?>
                                            <div><strong>Max per School:</strong> <?= htmlspecialchars($review_data['registration']['max_teams_per_school'] ?? '3') ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small">Not configured</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <!-- Competition Rules Summary -->
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white py-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-gavel"></i> Competition Rules
                                        <a href="<?= url('/admin/competition-setup/wizard/step/5') ?>" class="btn btn-sm btn-outline-light float-right">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <?php if (!empty($review_data['rules'])): ?>
                                        <div class="row small">
                                            <div class="col-md-6">
                                                <div><strong>Scoring Method:</strong> <?= htmlspecialchars(str_replace('_', ' ', ucfirst($review_data['rules']['scoring_method'] ?? 'points_based'))) ?></div>
                                                <div><strong>Max Score:</strong> <?= htmlspecialchars($review_data['rules']['max_score'] ?? '100') ?> points</div>
                                                <div><strong>Time Limit:</strong> <?= htmlspecialchars($review_data['rules']['time_limit'] ?? '15') ?> minutes</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div><strong>Judges per Team:</strong> <?= htmlspecialchars($review_data['rules']['judges_per_team'] ?? '3') ?></div>
                                                <div><strong>Max Attempts:</strong> <?= htmlspecialchars($review_data['rules']['max_attempts'] ?? '3') ?></div>
                                                <div><strong>Format:</strong> <?= htmlspecialchars(str_replace('_', ' ', ucfirst($review_data['rules']['competition_format'] ?? 'qualification'))) ?></div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small">Not configured</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deployment Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-rocket"></i> Deploy Competition
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p>Your competition configuration is complete and ready for deployment. Choose how you want to deploy:</p>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="<?= url('/admin/competition-setup/wizard/step/6') ?>" class="btn btn-success btn-lg">
                                <i class="fas fa-rocket"></i> Deploy Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Configuration Summary -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-pie"></i> Configuration Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <?php
                        $totalSteps = 5;
                        $completedSteps = 0;
                        $stepStatus = [
                            'Basic Information' => !empty($review_data['basic_info']),
                            'Phase Configuration' => !empty($review_data['phases']),
                            'Categories' => !empty($review_data['categories']),
                            'Registration Rules' => !empty($review_data['registration']),
                            'Competition Rules' => !empty($review_data['rules'])
                        ];

                        foreach ($stepStatus as $completed) {
                            if ($completed) $completedSteps++;
                        }

                        $completionPercentage = ($completedSteps / $totalSteps) * 100;
                        ?>

                        <div class="text-center mb-3">
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?= $completionPercentage ?>%">
                                    <?= round($completionPercentage) ?>%
                                </div>
                            </div>
                            <strong><?= $completedSteps ?> of <?= $totalSteps ?> steps completed</strong>
                        </div>

                        <?php foreach ($stepStatus as $stepName => $completed): ?>
                        <div class="mb-2 <?= $completed ? 'text-success' : 'text-warning' ?>">
                            <i class="fas fa-<?= $completed ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                            <?= htmlspecialchars($stepName) ?>
                        </div>
                        <?php endforeach; ?>
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
                        <a href="<?= url('/admin/competition-setup/wizard/step/6') ?>" class="btn btn-success">
                            <i class="fas fa-rocket"></i> Deploy Competition
                        </a>
                        <a href="<?= url('/admin/competition-setup/wizard/step/1') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Configuration
                        </a>
                        <a href="<?= url('/admin/competition-setup') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-save"></i> Save Draft
                        </a>
                        <a href="<?= url('/admin/competition-setup') ?>" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i> Cancel Setup
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle"></i> Need Help?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p>If you need to make changes to the configuration:</p>
                        <ul class="mb-2">
                            <li>Click "Edit Configuration" to return to step 1</li>
                            <li>Use the "Modify" buttons to edit specific sections</li>
                            <li>Save as draft to continue later</li>
                        </ul>
                        <p class="mb-0">Once deployed, you can still modify settings through the admin panel.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>