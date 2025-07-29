<?php
$title = 'GDE SciBOTICS Competition 2025 | Future Innovators';
$description = 'Join the GDE SciBOTICS Competition 2025 - Empowering Future Innovators Through Robotics Excellence. Register your school team today!';
$pageClass = 'home-page';
$pageCSS = ['/css/home_style.css'];
$pageJS = ['/js/home_script.js'];
$layout = 'public';

ob_start();
include VIEW_PATH . '/_header.php';
?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>GDE SciBOTICS 2025</h1>
            <p class="subtitle">Empowering Future Innovators Through Robotics Excellence</p>
            
            <div class="countdown">
                <h3>Competition Countdown</h3>
                <div class="countdown-timer">
                    <div class="time-unit">
                        <span class="time-number" id="days">120</span>
                        <span class="time-label">Days</span>
                    </div>
                    <div class="time-unit">
                        <span class="time-number" id="hours">15</span>
                        <span class="time-label">Hours</span>
                    </div>
                    <div class="time-unit">
                        <span class="time-number" id="minutes">30</span>
                        <span class="time-label">Minutes</span>
                    </div>
                    <div class="time-unit">
                        <span class="time-number" id="seconds">45</span>
                        <span class="time-label">Seconds</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-cta-buttons">
                <a href="<?= htmlspecialchars($loginUrl ?? '/auth/login') ?>" class="cta-button">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to System
                </a>
                <a href="<?= htmlspecialchars($registerUrl ?? '/auth/register') ?>" class="cta-button cta-secondary">
                    <i class="fas fa-user-plus me-2"></i>Register School
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <h2 class="section-title" style="color: #333;">Competition by Numbers</h2>
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <span class="stat-number">4</span>
                    <span class="stat-label">Competition Categories</span>
                </div>
                <div class="stat-card fade-in">
                    <span class="stat-number">3</span>
                    <span class="stat-label">Competition Phases</span>
                </div>
                <div class="stat-card fade-in">
                    <span class="stat-number">15</span>
                    <span class="stat-label">Teams per Category (Max)</span>
                </div>
                <div class="stat-card fade-in">
                    <span class="stat-number">6</span>
                    <span class="stat-label">Participants per Team (Max)</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="categories-section">
        <div class="container">
            <h2 class="section-title">Competition Categories</h2>
            <p class="section-subtitle">Choose your path to robotics excellence</p>
            
            <div class="categories-grid">
                <div class="category-card fade-in">
                    <h3>🤖 JUNIOR</h3>
                    <div class="grade">Grade R-3</div>
                    <p>Perfect for our youngest innovators! Using Cubroid and BEE Bot equipment, students solve age-appropriate missions that introduce them to the exciting world of robotics.</p>
                    <div class="category-features">
                        <span class="feature-tag">Cubroid & BEE Bot</span>
                        <span class="feature-tag">Life on Red Planet</span>
                    </div>
                </div>
                
                <div class="category-card fade-in">
                    <h3>⚡ SPIKE</h3>
                    <div class="grade">Grade 4-9</div>
                    <p>Intermediate robotics using LEGO Spike equipment. Teams tackle tabletop missions that challenge their problem-solving and programming skills.</p>
                    <div class="category-features">
                        <span class="feature-tag">LEGO Spike</span>
                        <span class="feature-tag">Lost in Space</span>
                    </div>
                </div>
                
                <div class="category-card fade-in">
                    <h3>🔧 ARDUINO</h3>
                    <div class="grade">Grade 8-12</div>
                    <p>Advanced robotics with SciBOT and Arduino platforms. Teams build custom robots and tackle machine learning missions that push technological boundaries.</p>
                    <div class="category-features">
                        <span class="feature-tag">SciBOT & Arduino</span>
                        <span class="feature-tag">Thunderdrome</span>
                    </div>
                </div>
                
                <div class="category-card fade-in">
                    <h3>💡 INVENTOR</h3>
                    <div class="grade">All Grades</div>
                    <p>Innovation-focused category using Arduino Inventor Kits. Teams develop creative solutions to real-world problems in their communities.</p>
                    <div class="category-features">
                        <span class="feature-tag">Arduino Inventor Kit</span>
                        <span class="feature-tag">Real-world Solutions</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Phases Section -->
    <section id="phases" class="phases-section">
        <div class="container">
            <h2 class="section-title" style="color: #333;">Competition Phases</h2>
            <p class="section-subtitle" style="color: #666;">Your journey to robotics excellence</p>
            
            <div class="phases-timeline">
                <div class="timeline-line"></div>
                
                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3>Phase 1: School-Based</h3>
                        <p><strong>What:</strong> Internal school competitions to select the best teams</p>
                        <p><strong>When:</strong> Ongoing preparation phase</p>
                        <p><strong>Goal:</strong> Prepare and identify top teams for district level</p>
                        <div class="phase-features">
                            <span class="phase-tag">Internal Selection</span>
                            <span class="phase-tag">Team Preparation</span>
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3>Phase 2: District Semifinals</h3>
                        <p><strong>What:</strong> District-wide competitions with standardized challenges</p>
                        <p><strong>When:</strong> August 31, 2025</p>
                        <p><strong>Goal:</strong> Select top teams for the provincial finals</p>
                        <div class="phase-features">
                            <span class="phase-tag">Max 15 Teams/Category</span>
                            <span class="phase-tag">Standardized Challenges</span>
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3>Phase 3: Provincial Finals</h3>
                        <p><strong>What:</strong> Live, high-profile competition with media coverage</p>
                        <p><strong>When:</strong> September 27, 2025</p>
                        <p><strong>Goal:</strong> Crown the champions and celebrate excellence</p>
                        <div class="phase-features">
                            <span class="phase-tag">Live Competition</span>
                            <span class="phase-tag">Media Coverage</span>
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section (Enhanced from original) -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title" style="color: #333;">Why Join SciBOTICS?</h2>
            <p class="section-subtitle" style="color: #666;">Benefits that extend beyond the competition</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">Hands-on Experience</h3>
                    <p class="text-muted">Build and program world-class robots using industry-standard equipment and platforms.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Team Collaboration</h3>
                    <p class="text-muted">Develop essential teamwork skills while solving complex engineering challenges together.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="feature-title">Recognition & Awards</h3>
                    <p class="text-muted">Gain recognition for excellence and open doors to exciting STEM career opportunities.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3 class="feature-title">Innovation Skills</h3>
                    <p class="text-muted">Develop creative problem-solving abilities that prepare you for tomorrow's challenges.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="feature-title">Educational Excellence</h3>
                    <p class="text-muted">Benefits that enhance your tertiary education and future career prospects.</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="feature-title">Future Ready</h3>
                    <p class="text-muted">Prepare for careers in space exploration, AI, and cutting-edge technology fields.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include VIEW_PATH . '/_footer.php'; ?>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/layouts/' . $layout . '.php';
?>