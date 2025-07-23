<?php 
$layout = 'layouts/app';
$is_auth_page = true;
$content = ob_get_clean();
ob_start();
?>

<div class="auth-card">
    <div class="auth-header text-center p-4">
        <h3 class="mb-0">
            <i class="fas fa-key me-2"></i>
            Forgot Password
        </h3>
        <p class="mb-0 mt-2 opacity-75">Reset your password to regain access</p>
    </div>
    
    <div class="card-body p-4">
        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-4">
            <p class="text-muted text-center">
                Enter your email address and we'll send you instructions to reset your password.
            </p>
        </div>
        
        <form method="POST" action="<?= htmlspecialchars($forgotPasswordAction ?? '/auth/forgot-password') ?>">
            <?= $csrf_field ?? '' ?>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($old_input['email'] ?? '') ?>"
                        placeholder="Enter your registered email address" 
                        required
                        autofocus
                    >
                </div>
            </div>
            
            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-auth btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Reset Instructions
                </button>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-2">
                    <a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Login
                    </a>
                </p>
                <p class="mb-0">
                    Don't have an account?
                    <a href="/auth/register" class="text-decoration-none fw-bold">
                        Register here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>