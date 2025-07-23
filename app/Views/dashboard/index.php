<?php 
$layout = 'layouts/app';
$content = ob_get_clean();
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h2><?= htmlspecialchars($welcome_message) ?></h2>
                            <p class="lead">Welcome to the GDE SciBOTICS Competition Management System</p>
                            
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Your Account Information</h5>
                                <ul class="mb-0">
                                    <li><strong>Name:</strong> <?= htmlspecialchars($user->getDisplayName()) ?></li>
                                    <li><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></li>
                                    <li><strong>Username:</strong> <?= htmlspecialchars($user->username) ?></li>
                                    <li><strong>Role:</strong> <?= htmlspecialchars($user->getRoleDisplayName()) ?></li>
                                    <li><strong>Status:</strong> 
                                        <span class="badge bg-<?= $user->status === 'active' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($user->status) ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            
                            <?php if ($user->role === 'school_coordinator'): ?>
                                <div class="alert alert-primary">
                                    <h5><i class="fas fa-school me-2"></i>School Coordinator Dashboard</h5>
                                    <p>As a School Coordinator, you can manage your school's participation in competitions.</p>
                                    <a href="/schools/manage" class="btn btn-outline-primary">
                                        <i class="fas fa-cog me-2"></i>Manage School
                                    </a>
                                </div>
                            <?php elseif ($user->role === 'team_coach'): ?>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-users me-2"></i>Team Coach Dashboard</h5>
                                    <p>As a Team Coach, you can manage your teams and participants.</p>
                                    <a href="/teams/manage" class="btn btn-outline-success">
                                        <i class="fas fa-users me-2"></i>Manage Teams
                                    </a>
                                </div>
                            <?php elseif ($user->role === 'judge'): ?>
                                <div class="alert alert-warning">
                                    <h5><i class="fas fa-gavel me-2"></i>Judge Dashboard</h5>
                                    <p>As a Judge, you can score competitions and view judging assignments.</p>
                                    <a href="/judging/dashboard" class="btn btn-outline-warning">
                                        <i class="fas fa-clipboard-list me-2"></i>Judging Panel
                                    </a>
                                </div>
                            <?php elseif ($user->isAdmin()): ?>
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-user-shield me-2"></i>Administrator Dashboard</h5>
                                    <p>You have administrative access to the system.</p>
                                    <a href="/admin/dashboard" class="btn btn-outline-danger">
                                        <i class="fas fa-cogs me-2"></i>Admin Panel
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5><i class="fas fa-user-cog me-2"></i>Account Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="<?= $baseUrl ?? '' ?>/auth/change-password" class="btn btn-outline-primary">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </a>
                                        
                                        <form method="POST" action="<?= $baseUrl ?? '' ?>/auth/logout" class="d-grid">
                                            <?= $csrf_field ?? '' ?>
                                            <button type="submit" class="btn btn-outline-secondary">
                                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card bg-light mt-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-bell me-2"></i>Quick Links</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="/competitions" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-trophy me-2"></i>Competitions
                                        </a>
                                        <a href="/schedule" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-calendar me-2"></i>Schedule
                                        </a>
                                        <a href="/resources" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-file-alt me-2"></i>Resources
                                        </a>
                                        <a href="/support" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-question-circle me-2"></i>Support
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
</div>

<style>
.card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-radius: 15px 15px 0 0;
}

.btn {
    border-radius: 8px;
}

.alert {
    border-radius: 10px;
    border: none;
}
</style>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>