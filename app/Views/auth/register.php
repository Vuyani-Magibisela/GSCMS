<?php 
$layout = 'layouts/app';
$is_auth_page = true;
$content = ob_get_clean();
ob_start();
?>

<div class="auth-card">
    <div class="auth-header text-center p-4">
        <h3 class="mb-0">
            <i class="fas fa-user-plus me-2"></i>
            Create Account
        </h3>
        <p class="mb-0 mt-2 opacity-75">Join the GDE SciBOTICS Competition</p>
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
        
        <form method="POST" action="<?= htmlspecialchars($registerAction ?? '/auth/register') ?>" id="registerForm">
            <?= $csrf_field ?? '' ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name *</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="first_name" 
                        name="first_name" 
                        value="<?= htmlspecialchars($old_input['first_name'] ?? '') ?>"
                        placeholder="Enter first name" 
                        required
                    >
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name *</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="last_name" 
                        name="last_name" 
                        value="<?= htmlspecialchars($old_input['last_name'] ?? '') ?>"
                        placeholder="Enter last name" 
                        required
                    >
                </div>
            </div>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username *</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        value="<?= htmlspecialchars($old_input['username'] ?? '') ?>"
                        placeholder="Choose a username" 
                        required
                        pattern="[a-zA-Z0-9_]+"
                        title="Username can only contain letters, numbers, and underscores"
                    >
                </div>
                <div class="form-text">Only letters, numbers, and underscores allowed</div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address *</label>
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
                        placeholder="Enter your email address" 
                        required
                    >
                </div>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-phone"></i>
                    </span>
                    <input 
                        type="tel" 
                        class="form-control" 
                        id="phone" 
                        name="phone" 
                        value="<?= htmlspecialchars($old_input['phone'] ?? '') ?>"
                        placeholder="Enter phone number (optional)" 
                    >
                </div>
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Role *</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user-tag"></i>
                    </span>
                    <select class="form-control" id="role" name="role" required>
                        <option value="">Select your role</option>
                        <option value="school_coordinator" <?= ($old_input['role'] ?? '') === 'school_coordinator' ? 'selected' : '' ?>>
                            School Coordinator
                        </option>
                        <option value="team_coach" <?= ($old_input['role'] ?? '') === 'team_coach' ? 'selected' : '' ?>>
                            Team Coach
                        </option>
                    </select>
                </div>
                <div class="form-text">Choose the role that best describes your participation</div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password *</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Create a strong password" 
                        required
                        minlength="8"
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
                <label for="password_confirmation" class="form-label">Confirm Password *</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        placeholder="Confirm your password" 
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
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="/terms" target="_blank">Terms of Service</a> and 
                    <a href="/privacy" target="_blank">Privacy Policy</a> *
                </label>
            </div>
            
            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-auth btn-primary">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                </button>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">
                    Already have an account?
                    <a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>" class="text-decoration-none fw-bold">
                        Sign in here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

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

// Password strength validation
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const confirmPassword = document.getElementById('password_confirmation');
    
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