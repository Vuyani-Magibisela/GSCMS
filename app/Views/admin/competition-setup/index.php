<?php
$layout = 'layouts/admin';  // ✅ CRITICAL - Must use this exact path
ob_start();
?>

<div class="admin-competition-setup">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Competition Setup</h1>
            <p class="text-muted">Configure and manage competition settings</p>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= $this->url('/admin/competition-setup/wizard') ?>" class="btn btn-primary">
                <i class="fas fa-magic"></i> Setup Wizard
            </a>
        </div>
    </div>

    <!-- Current Configuration -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Competition Type</h6>
                            <p class="text-muted">
                                <?php if (isset($current_type) && $current_type === 'pilot'): ?>
                                    <span class="badge bg-info">Pilot Programme 2025</span>
                                <?php elseif (isset($current_type) && $current_type === 'full'): ?>
                                    <span class="badge bg-success">Full System</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Configured</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Active Phases</h6>
                            <p class="text-muted">
                                <?php if (isset($active_phases) && !empty($active_phases)): ?>
                                    <?php foreach ($active_phases as $phase): ?>
                                        <span class="badge bg-primary me-1"><?= htmlspecialchars($phase['name'] ?? '') ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No active phases</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Options -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pilot Programme 2025</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Configure for the pilot programme with 9 categories and streamlined phases.</p>
                    <ul class="text-muted">
                        <li>9 Competition Categories</li>
                        <li>Phase 1 (School-based) and Phase 3 (Finals)</li>
                        <li>30 teams per category in Phase 1</li>
                        <li>6 teams per category in Phase 3</li>
                    </ul>
                    <a href="<?= $this->url('/admin/competition-setup/configure-pilot') ?>" class="btn btn-info">
                        <i class="fas fa-cog"></i> Configure Pilot
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Full Competition System</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Configure for the complete competition system with all phases.</p>
                    <ul class="text-muted">
                        <li>All Competition Categories</li>
                        <li>Phase 1 (School), Phase 2 (District), Phase 3 (Provincial)</li>
                        <li>Full progression through all phases</li>
                        <li>Complete tournament structure</li>
                    </ul>
                    <a href="<?= $this->url('/admin/competition-setup/configure-full') ?>" class="btn btn-success">
                        <i class="fas fa-trophy"></i> Configure Full System
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <?php if (isset($statistics) && !empty($statistics)): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Competition Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Pilot Programme Statistics</h6>
                            <?php if (isset($statistics['pilot'])): ?>
                                <div class="text-muted">
                                    <!-- Display pilot statistics here -->
                                    <p>Active Categories: <?= count($categories ?? []) ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No pilot statistics available</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Full System Statistics</h6>
                            <?php if (isset($statistics['full'])): ?>
                                <div class="text-muted">
                                    <!-- Display full system statistics here -->
                                    <p>Total Phases: <?= count($active_phases ?? []) ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No full system statistics available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="<?= $this->url('/admin/competitions') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> View Competitions
                        </a>
                        <a href="<?= $this->url('/admin/competition-setup/wizard') ?>" class="btn btn-outline-info">
                            <i class="fas fa-magic"></i> Setup Wizard
                        </a>
                        <a href="<?= $this->url('/admin/dashboard') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i> Back to Dashboard
                        </a>
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