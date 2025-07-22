<?php 
require_once '../app/views/_header.php'
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
            
            <a href="#register" class="cta-button">Register Your School</a>
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
                </div>
                
                <div class="category-card fade-in">
                    <h3>⚡ SPIKE</h3>
                    <div class="grade">Grade 4-9</div>
                    <p>Intermediate robotics using LEGO Spike equipment. Teams tackle tabletop missions that challenge their problem-solving and programming skills.</p>
                </div>
                
                <div class="category-card fade-in">
                    <h3>🔧 ARDUINO</h3>
                    <div class="grade">Grade 8-12</div>
                    <p>Advanced robotics with SciBOT and Arduino platforms. Teams build custom robots and tackle machine learning missions that push technological boundaries.</p>
                </div>
                
                <div class="category-card fade-in">
                    <h3>💡 INVENTOR</h3>
                    <div class="grade">All Grades</div>
                    <p>Innovation-focused category using Arduino Inventor Kits. Teams develop creative solutions to real-world problems in their communities.</p>
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
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3>Phase 2: District Semifinals</h3>
                        <p><strong>What:</strong> District-wide competitions with standardized challenges</p>
                        <p><strong>When:</strong> August 31, 2025</p>
                        <p><strong>Goal:</strong> Select top teams for the provincial finals</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item fade-in">
                    <div class="timeline-content">
                        <h3>Phase 3: Provincial Finals</h3>
                        <p><strong>What:</strong> Live, high-profile competition with media coverage</p>
                        <p><strong>When:</strong> September 31, 2025</p>
                        <p><strong>Goal:</strong> Crown the champions and celebrate excellence</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section id="register" class="registration-section">
        <div class="container">
            <h2 class="section-title">Ready to Compete?</h2>
            <p class="section-subtitle">Register your school and join the robotics revolution</p>
            
            <form class="registration-form" id="registrationForm">
                <div class="form-group">
                    <label for="schoolName">School Name</label>
                    <input type="text" id="schoolName" name="schoolName" required>
                </div>
                
                <div class="form-group">
                    <label for="district">District</label>
                    <select id="district" name="district" required>
                        <option value="">Select District</option>
                        <option value="johannesburg-east">Johannesburg East</option>
                        <option value="johannesburg-west">Johannesburg West</option>
                        <option value="johannesburg-central">Johannesburg Central</option>
                        <option value="johannesburg-south">Johannesburg South</option>
                        <option value="tshwane-north">Tshwane North</option>
                        <option value="tshwane-south">Tshwane South</option>
                        <option value="ekurhuleni-north">Ekurhuleni North</option>
                        <option value="ekurhuleni-south">Ekurhuleni South</option>
                        <option value="sedibeng-east">Sedibeng East</option>
                        <option value="sedibeng-west">Sedibeng West</option>
                        <option value="west-rand">West Rand</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="contactPerson">Contact Person</label>
                    <input type="text" id="contactPerson" name="contactPerson" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Interested Categories (You can select multiple later)</label>
                    <select id="category" name="category" required>
                        <option value="">Select Primary Category</option>
                        <option value="junior">Junior (Grade R-3)</option>
                        <option value="spike">Spike (Grade 4-9)</option>
                        <option value="arduino">Arduino (Grade 8-12)</option>
                        <option value="inventor">Inventor (All Grades)</option>
                    </select>
                </div>
                
                <button type="submit" class="submit-btn">Register School</button>
            </form>
        </div>
    </section>

<?php require_once '../app/views/_footer.php'; ?>
