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
                        <i class="fas fa-user-edit me-2"></i>
                        Profile Settings
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="POST" action="/profile">
                                <?= $csrf_field ?? '' ?>
                                <input type="hidden" name="_method" value="PUT">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="first_name" class="form-label">First Name *</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="first_name" 
                                                   name="first_name" 
                                                   value="<?= htmlspecialchars($user->first_name ?? '') ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="last_name" class="form-label">Last Name *</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="last_name" 
                                                   name="last_name" 
                                                   value="<?= htmlspecialchars($user->last_name ?? '') ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($user->email ?? '') ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?= htmlspecialchars($user->phone ?? '') ?>">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           value="<?= htmlspecialchars($user->username ?? '') ?>" 
                                           readonly>
                                    <small class="form-text text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="role" 
                                           value="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user->role ?? ''))) ?>" 
                                           readonly>
                                    <small class="form-text text-muted">Role is assigned by administrators</small>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                    <a href="/dashboard" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user->created_at ?? 'now')) ?></li>
                                        <li><strong>Last Updated:</strong> <?= date('F j, Y', strtotime($user->updated_at ?? 'now')) ?></li>
                                        <li><strong>Status:</strong> 
                                            <span class="badge bg-<?= $user->status === 'active' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($user->status ?? 'unknown') ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card bg-light mt-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-key me-2"></i>Security</h5>
                                </div>
                                <div class="card-body">
                                    <p>To change your password or update security settings:</p>
                                    <a href="/auth/change-password" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-key me-2"></i>Change Password
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

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn {
    border-radius: 8px;
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>