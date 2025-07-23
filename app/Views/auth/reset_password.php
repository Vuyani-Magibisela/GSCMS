<?php 
$layout = 'layouts/app';
$is_auth_page = true;
$content = ob_get_clean();
ob_start();
?>

<div class="auth-card">
    <div class="auth-header text-center p-4">
        <h3 class="mb-0">
            <i class="fas fa-lock-open me-2"></i>
            Reset Password
        </h3>
        <p class="mb-0 mt-2 opacity-75">Create a new password for your account</p>
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
        
        <form method="POST" action="/auth/reset-password" id="resetPasswordForm">
            <?= $csrf_field ?? '' ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter new password" 
                        required
                        minlength="8"
                        autofocus
                    >
                    <button 
                        class="btn btn-outline-secondary" 
                        type="button" 
                        onclick="togglePassword('password')"
                    >
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
                <div class="form-text">
                    Must be at least 8 characters with uppercase, lowercase, number, and special character
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        placeholder="Confirm new password" 
                        required
                        minlength="8"
                    >
                    <button 
                        class="btn btn-outline-secondary" 
                        type="button" 
                        onclick="togglePassword('password_confirmation')"
                    >
                        <i class="fas fa-eye" id="passwordConfirmationIcon"></i>
                    </button>
                </div>
            </div>
            
            <!-- Password strength indicator -->
            <div class="mb-3">
                <div class="password-strength">
                    <div class="strength-meter">
                        <div class="strength-meter-fill" id="strengthMeter"></div>
                    </div>
                    <small class="form-text" id="strengthText">Enter a password to see strength</small>
                </div>
            </div>
            
            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-auth btn-primary">
                    <i class="fas fa-check me-2"></i>
                    Reset Password
                </button>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">
                    Remember your password?
                    <a href="/auth/login" class="text-decoration-none fw-bold">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Login
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<style>
.password-strength {
    margin-top: 5px;
}

.strength-meter {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-meter-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak {
    background-color: #dc3545;
    width: 25%;
}

.strength-fair {
    background-color: #fd7e14;
    width: 50%;
}

.strength-good {
    background-color: #ffc107;
    width: 75%;
}

.strength-strong {
    background-color: #28a745;
    width: 100%;
}
</style>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId + 'Icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordField.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}

function checkPasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[@$!%*?&]/.test(password)) score++;
    
    return score;
}

function updatePasswordStrength(password) {
    const strengthMeter = document.getElementById('strengthMeter');
    const strengthText = document.getElementById('strengthText');
    const score = checkPasswordStrength(password);
    
    // Remove all strength classes
    strengthMeter.className = 'strength-meter-fill';
    
    if (password.length === 0) {
        strengthText.textContent = 'Enter a password to see strength';
        strengthText.className = 'form-text text-muted';
        return;
    }
    
    switch (score) {
        case 0:
        case 1:
        case 2:
            strengthMeter.classList.add('strength-weak');
            strengthText.textContent = 'Weak password';
            strengthText.className = 'form-text text-danger';
            break;
        case 3:
            strengthMeter.classList.add('strength-fair');
            strengthText.textContent = 'Fair password';
            strengthText.className = 'form-text text-warning';
            break;
        case 4:
            strengthMeter.classList.add('strength-good');
            strengthText.textContent = 'Good password';
            strengthText.className = 'form-text text-info';
            break;
        case 5:
            strengthMeter.classList.add('strength-strong');
            strengthText.textContent = 'Strong password';
            strengthText.className = 'form-text text-success';
            break;
    }
}

// Password strength validation
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const confirmPassword = document.getElementById('password_confirmation');
    
    updatePasswordStrength(password);
    
    // Check password strength
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[@$!%*?&]/.test(password);
    const isLongEnough = password.length >= 8;
    
    if (password && (!hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecialChar || !isLongEnough)) {
        this.setCustomValidity('Password must contain uppercase, lowercase, number, and special character');
    } else {
        this.setCustomValidity('');
    }
    
    // Check password confirmation match
    if (confirmPassword.value && confirmPassword.value !== password) {
        confirmPassword.setCustomValidity('Passwords do not match');
    } else {
        confirmPassword.setCustomValidity('');
    }
});

document.getElementById('password_confirmation').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && confirmPassword !== password) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>