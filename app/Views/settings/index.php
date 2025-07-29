<?php
$layout = 'layouts/app';
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Account Settings
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Available Settings</h5>
                            <p class="text-muted">Manage your account preferences and security settings.</p>
                            
                            <div class="list-group">
                                <a href="/profile" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="fas fa-user-edit me-2"></i>Profile Information
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-chevron-right"></i>
                                        </small>
                                    </div>
                                    <p class="mb-1">Update your personal information and contact details.</p>
                                </a>
                                
                                <a href="/auth/change-password" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-chevron-right"></i>
                                        </small>
                                    </div>
                                    <p class="mb-1">Update your account password for security.</p>
                                </a>
                                
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="fas fa-bell me-2"></i>Notifications
                                        </h6>
                                        <small class="text-muted">Coming Soon</small>
                                    </div>
                                    <p class="mb-1 text-muted">Manage email and system notifications (feature under development).</p>
                                </div>
                                
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="fas fa-shield-alt me-2"></i>Privacy & Security
                                        </h6>
                                        <small class="text-muted">Coming Soon</small>
                                    </div>
                                    <p class="mb-1 text-muted">Advanced security settings and privacy controls (feature under development).</p>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="/dashboard" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5><i class="fas fa-user me-2"></i>Your Account</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><strong>Name:</strong> <?= htmlspecialchars($user->getDisplayName()) ?></li>
                                        <li><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></li>
                                        <li><strong>Username:</strong> <?= htmlspecialchars($user->username) ?></li>
                                        <li><strong>Role:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user->role))) ?></li>
                                        <li><strong>Status:</strong> 
                                            <span class="badge bg-<?= $user->status === 'active' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($user->status) ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card bg-light mt-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-question-circle me-2"></i>Need Help?</h5>
                                </div>
                                <div class="card-body">
                                    <p>If you need assistance with your account settings, please contact support.</p>
                                    <a href="mailto:support@gscms.local" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-envelope me-2"></i>Contact Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-radius: 15px 15px 0 0;
}

.list-group-item {
    border-radius: 8px !important;
    margin-bottom: 8px;
    border: 1px solid rgba(0,0,0,.125);
}

.list-group-item-action:hover {
    background-color: rgba(102, 126, 234, 0.1);
    border-color: var(--primary-color);
}

.btn {
    border-radius: 8px;
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>