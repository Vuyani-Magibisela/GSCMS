<?php 
$layout = 'layouts/app';
$is_auth_page = true;
$content = ob_get_clean();
ob_start();
?>

<div class="auth-card">
    <div class="auth-header text-center p-4">
        <h3 class="mb-0">
            <i class="fas fa-microscope me-2"></i>
            GDE SciBOTICS Login
        </h3>
        <p class="mb-0 mt-2 opacity-75">Access your competition management system</p>
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
        
        <form method="POST" action="<?= htmlspecialchars($loginAction ?? '/auth/login') ?>" class="form-container">
            <?= $csrf_field ?? '' ?>
            
            <div class="form-group">
                <label for="login" class="form-label">Email or Username</label>
                <div class="input-icon-container">
                    <input 
                        type="text" 
                        class="form-control" 
                        id="login" 
                        name="login" 
                        value="<?= htmlspecialchars($old_input['login'] ?? '') ?>"
                        placeholder="Enter your email or username" 
                        required
                        autofocus
                    >
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-container">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                        style="padding-right: 3.5rem;"
                    >
                    <i class="fas fa-lock input-icon"></i>
                    <button 
                        class="password-toggle" 
                        type="button" 
                        onclick="togglePassword()"
                        id="togglePasswordBtn"
                        aria-label="Toggle password visibility"
                    >
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                <label class="form-check-label" for="remember">
                    Remember me for 30 days
                </label>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-auth btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-2">
                    <a href="<?= htmlspecialchars($forgotPasswordUrl ?? '/auth/forgot-password') ?>" class="text-decoration-none">
                        <i class="fas fa-key me-1"></i>
                        Forgot your password?
                    </a>
                </p>
                <p class="mb-0">
                    Don't have an account?
                    <a href="<?= htmlspecialchars($registerUrl ?? '/auth/register') ?>" class="text-decoration-none fw-bold">
                        Register here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordField.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>