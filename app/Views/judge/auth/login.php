<?php 
$layout = 'public';
ob_start(); 
?>

<div class="judge-auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="judge-login-card">
                    <div class="card-header text-center">
                        <img src="/images/gde-logo.png" alt="GDE SciBOTICS" class="logo mb-3" style="max-height: 60px;">
                        <h3 class="mb-1">Judge Portal</h3>
                        <p class="text-muted">GDE SciBOTICS Competition</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($message = $_SESSION['flash_success'] ?? null): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error = $_SESSION['flash_error'] ?? null): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="auth-methods mb-4">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="auth_method" id="method_password" value="password" checked>
                                <label class="btn btn-outline-primary" for="method_password">
                                    <i class="fas fa-key me-1"></i> Password
                                </label>
                                
                                <input type="radio" class="btn-check" name="auth_method" id="method_pin" value="pin">
                                <label class="btn btn-outline-primary" for="method_pin">
                                    <i class="fas fa-hashtag me-1"></i> PIN
                                </label>
                                
                                <input type="radio" class="btn-check" name="auth_method" id="method_biometric" value="biometric" style="display: none;">
                                <label class="btn btn-outline-primary" for="method_biometric" id="biometric_label" style="display: none;">
                                    <i class="fas fa-fingerprint me-1"></i> Biometric
                                </label>
                            </div>
                        </div>
                        
                        <form id="judge-login-form">
                            <div class="mb-3">
                                <label for="identifier" class="form-label">Judge Code or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="identifier" name="identifier" required>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="password_group">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggle_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="pin_group" style="display: none;">
                                <label class="form-label">PIN Code</label>
                                <div class="pin-input-container">
                                    <input type="text" class="form-control pin-digit" maxlength="1" data-index="0">
                                    <input type="text" class="form-control pin-digit" maxlength="1" data-index="1">
                                    <input type="text" class="form-control pin-digit" maxlength="1" data-index="2">
                                    <input type="text" class="form-control pin-digit" maxlength="1" data-index="3">
                                    <input type="text" class="form-control pin-digit" maxlength="1" data-index="4">
                                    <input type="text" class="form-control pin-digit" maxlength="1" data-index="5">
                                </div>
                            </div>
                            
                            <div class="mb-3" id="two_fa_group" style="display: none;">
                                <label for="two_fa_code" class="form-label">Two-Factor Authentication Code</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                    <input type="text" class="form-control" id="two_fa_code" name="two_fa_code" maxlength="6" placeholder="123456">
                                </div>
                                <div class="form-text">Enter the 6-digit code from your authenticator app</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="remember_device" name="remember_device">
                                <label class="form-check-label" for="remember_device">
                                    <i class="fas fa-shield-alt me-1"></i> Trust this device
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="login_button">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    <span class="btn-text">Sign In</span>
                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                                </button>
                            </div>
                            
                            <input type="hidden" id="device_id" name="device_id">
                        </form>
                        
                        <div class="auth-footer mt-4">
                            <div class="row text-center">
                                <div class="col">
                                    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                        <i class="fas fa-key me-1"></i> Forgot Password?
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#helpModal">
                                        <i class="fas fa-question-circle me-1"></i> Need Help?
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reset-password-form">
                    <div class="mb-3">
                        <label for="reset_identifier" class="form-label">Judge Code or Email</label>
                        <input type="text" class="form-control" id="reset_identifier" name="identifier" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="reset-password-form" class="btn btn-primary">Send Reset Link</button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Authentication Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Having trouble signing in?</h6>
                <ul>
                    <li><strong>Password Login:</strong> Use your registered email and password</li>
                    <li><strong>PIN Login:</strong> Use your 6-digit PIN if configured</li>
                    <li><strong>Judge Code:</strong> Your unique judge identifier (e.g., ADJ2024001)</li>
                    <li><strong>2FA:</strong> Use your authenticator app for the 6-digit code</li>
                </ul>
                <hr>
                <h6>Contact Support</h6>
                <p>Email: <a href="mailto:support@gdescibotics.co.za">support@gdescibotics.co.za</a><br>
                   Phone: +27 (0) 12 345 6789</p>
            </div>
        </div>
    </div>
</div>

<style>
.judge-auth-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    padding: 2rem 0;
}

.judge-login-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.judge-login-card .card-header {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    padding: 2rem;
    border: none;
}

.judge-login-card .card-body {
    padding: 2rem;
}

.auth-methods .btn-check:checked + .btn {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
}

.pin-input-container {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.pin-digit {
    width: 3rem;
    text-align: center;
    font-size: 1.2rem;
    font-weight: bold;
}

.btn-primary {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

.form-control {
    border-left: none;
}

.auth-footer a {
    color: #667eea;
    font-size: 0.9rem;
}

.auth-footer a:hover {
    color: #764ba2;
}

@media (max-width: 768px) {
    .auth-methods .btn-group {
        flex-direction: column;
    }
    
    .pin-input-container {
        gap: 0.25rem;
    }
    
    .pin-digit {
        width: 2.5rem;
        font-size: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const judgeAuth = new JudgeAuth();
    judgeAuth.init();
});

class JudgeAuth {
    constructor() {
        this.currentMethod = 'password';
        this.deviceId = this.getOrCreateDeviceId();
        this.biometricAvailable = false;
    }
    
    init() {
        this.setupDeviceId();
        this.bindEvents();
        this.checkBiometricSupport();
    }
    
    setupDeviceId() {
        document.getElementById('device_id').value = this.deviceId;
    }
    
    bindEvents() {
        // Auth method switching
        document.querySelectorAll('input[name="auth_method"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.switchAuthMethod(e.target.value);
            });
        });
        
        // PIN input handling
        document.querySelectorAll('.pin-digit').forEach((input, index) => {
            input.addEventListener('input', (e) => this.handlePinInput(e, index));
            input.addEventListener('keydown', (e) => this.handlePinKeydown(e, index));
        });
        
        // Password visibility toggle
        document.getElementById('toggle_password').addEventListener('click', this.togglePasswordVisibility);
        
        // Form submission
        document.getElementById('judge-login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.attemptLogin();
        });
        
        // Reset password form
        document.getElementById('reset-password-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.resetPassword();
        });
    }
    
    switchAuthMethod(method) {
        this.currentMethod = method;
        
        // Hide all auth groups
        document.getElementById('password_group').style.display = 'none';
        document.getElementById('pin_group').style.display = 'none';
        
        // Show selected auth group
        if (method === 'password') {
            document.getElementById('password_group').style.display = 'block';
            document.getElementById('password').focus();
        } else if (method === 'pin') {
            document.getElementById('pin_group').style.display = 'block';
            document.querySelector('.pin-digit').focus();
        } else if (method === 'biometric') {
            this.performBiometricAuth();
        }
    }
    
    handlePinInput(e, index) {
        const value = e.target.value;
        if (value.length === 1 && /^\d$/.test(value)) {
            const nextInput = document.querySelector(`.pin-digit[data-index="${index + 1}"]`);
            if (nextInput) {
                nextInput.focus();
            }
        }
    }
    
    handlePinKeydown(e, index) {
        if (e.key === 'Backspace' && e.target.value === '') {
            const prevInput = document.querySelector(`.pin-digit[data-index="${index - 1}"]`);
            if (prevInput) {
                prevInput.focus();
            }
        }
    }
    
    togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('#toggle_password i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }
    
    async attemptLogin() {
        const button = document.getElementById('login_button');
        const spinner = button.querySelector('.spinner-border');
        const btnText = button.querySelector('.btn-text');
        
        button.disabled = true;
        spinner.classList.remove('d-none');
        btnText.textContent = 'Signing In...';
        
        const credentials = this.gatherCredentials();
        
        try {
            const response = await fetch('/judge/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(credentials),
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.requires_2fa) {
                this.show2FAInput();
            } else if (data.success) {
                this.handleSuccessfulLogin(data);
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            this.showError(error.message);
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
            btnText.textContent = 'Sign In';
        }
    }
    
    gatherCredentials() {
        const credentials = {
            identifier: document.getElementById('identifier').value,
            device_id: this.deviceId
        };
        
        if (this.currentMethod === 'password') {
            credentials.password = document.getElementById('password').value;
        } else if (this.currentMethod === 'pin') {
            credentials.pin = Array.from(document.querySelectorAll('.pin-digit'))
                .map(input => input.value)
                .join('');
        }
        
        const twoFaCode = document.getElementById('two_fa_code').value;
        if (twoFaCode) {
            credentials.two_fa_code = twoFaCode;
        }
        
        return credentials;
    }
    
    show2FAInput() {
        document.getElementById('two_fa_group').style.display = 'block';
        document.getElementById('two_fa_code').focus();
        this.showInfo('Please enter your two-factor authentication code');
    }
    
    handleSuccessfulLogin(data) {
        this.showSuccess('Login successful! Redirecting...');
        setTimeout(() => {
            window.location.href = data.redirect;
        }, 1000);
    }
    
    async resetPassword() {
        const identifier = document.getElementById('reset_identifier').value;
        
        try {
            const response = await fetch('/judge/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ identifier }),
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(data.message);
                bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            this.showError(error.message);
        }
    }
    
    async checkBiometricSupport() {
        if (window.PublicKeyCredential) {
            try {
                const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (available) {
                    this.biometricAvailable = true;
                    document.getElementById('method_biometric').style.display = 'block';
                    document.getElementById('biometric_label').style.display = 'block';
                }
            } catch (error) {
                console.log('Biometric support check failed:', error);
            }
        }
    }
    
    async performBiometricAuth() {
        if (!this.biometricAvailable) {
            this.showError('Biometric authentication is not available on this device');
            return;
        }
        
        try {
            // This would implement WebAuthn biometric authentication
            this.showInfo('Biometric authentication is not fully implemented yet');
        } catch (error) {
            this.showError('Biometric authentication failed: ' + error.message);
        }
    }
    
    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('judge_device_id');
        if (!deviceId) {
            deviceId = 'dev_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            localStorage.setItem('judge_device_id', deviceId);
        }
        return deviceId;
    }
    
    showError(message) {
        this.showAlert(message, 'danger');
    }
    
    showSuccess(message) {
        this.showAlert(message, 'success');
    }
    
    showInfo(message) {
        this.showAlert(message, 'info');
    }
    
    showAlert(message, type) {
        const alertContainer = document.querySelector('.judge-login-card .card-body');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertContainer.insertBefore(alert, alertContainer.firstChild);
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>