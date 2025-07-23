<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'GDE SciBOTICS CMS' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-button {
            display: inline-block;
            padding: 15px 30px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            border: 2px solid white;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            background-color: white;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .features-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .footer-section {
            background: #333;
            color: white;
            padding: 40px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">🔬 GDE SciBOTICS</h1>
            <p class="hero-subtitle">Competition Management System</p>
            <p class="lead mb-4"><?= htmlspecialchars($message) ?></p>
            
            <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info">
                    <small>Debug Info:<br>
                    Base URL: <?= htmlspecialchars($baseUrl ?? 'not set') ?><br>
                    Login URL: <?= htmlspecialchars($loginUrl ?? 'not set') ?><br>
                    Register URL: <?= htmlspecialchars($registerUrl ?? 'not set') ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>" class="cta-button">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <a href="<?= htmlspecialchars($registerUrl ?? '/auth/register') ?>" class="cta-button">
                    <i class="fas fa-user-plus me-2"></i>Register
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold mb-3">Welcome to SciBOTICS CMS</h2>
                    <p class="lead text-muted">Your gateway to robotics competition management</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <h3 class="feature-title">School Management</h3>
                        <p class="text-muted">Register and manage your school's participation in robotics competitions with ease.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Team Organization</h3>
                        <p class="text-muted">Create and manage teams, track participants, and organize competition entries.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="feature-title">Competition Tracking</h3>
                        <p class="text-muted">Monitor progress, view schedules, and access real-time competition updates.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <h3 class="feature-title">Judging System</h3>
                        <p class="text-muted">Streamlined scoring and evaluation system for fair and transparent judging.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Reports & Analytics</h3>
                        <p class="text-muted">Generate comprehensive reports and analytics for competition insights.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Secure Platform</h3>
                        <p class="text-muted">Enterprise-grade security with role-based access control and data protection.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0">&copy; <?= date('Y') ?> GDE SciBOTICS Competition Management System. All rights reserved.</p>
                    <p class="mb-0 mt-2">
                        <small>Empowering future innovators through robotics excellence</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>