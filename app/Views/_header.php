<!-- Navigation -->
<nav id="navbar">
    <div class="nav-container">
        <a href="#home" class="logo">SciBOTICS 2025</a>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#categories">Categories</a></li>
            <li><a href="#phases">Phases</a></li>
        </ul>
        <div class="nav-buttons">
            <a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>" class="nav-btn login-btn">
                <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
            <a href="<?= htmlspecialchars($registerUrl ?? '/auth/register') ?>" class="nav-btn register-btn">
                <i class="fas fa-user-plus me-1"></i>Register
            </a>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#categories">Categories</a></li>
            <li><a href="#phases">Phases</a></li>
        </ul>
        <div class="mobile-nav-buttons">
            <a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>" class="nav-btn login-btn">
                <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
            <a href="<?= htmlspecialchars($registerUrl ?? '/auth/register') ?>" class="nav-btn register-btn">
                <i class="fas fa-user-plus me-1"></i>Register
            </a>
        </div>
    </div>
</nav>