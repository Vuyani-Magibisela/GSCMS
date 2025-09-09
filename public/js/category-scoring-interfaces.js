// public/js/category-scoring-interfaces.js

class CategoryScoringInterface {
    constructor(category, teamId, rubricId) {
        this.category = category;
        this.teamId = teamId;
        this.rubricId = rubricId;
        this.interface = null;
        this.rubricData = null;
        this.scores = {};
        this.autoSaveInterval = null;
    }
    
    async init() {
        try {
            // Load rubric structure
            await this.loadRubricStructure();
            
            // Initialize category-specific interface
            switch(this.category.toUpperCase()) {
                case 'JUNIOR':
                    this.interface = new JuniorScoringInterface(this);
                    break;
                case 'SPIKE':
                case 'EXPLORER':
                    this.interface = new SpikeScoringInterface(this);
                    break;
                case 'ARDUINO':
                    this.interface = new ArduinoScoringInterface(this);
                    break;
                case 'INVENTOR':
                    this.interface = new InventorScoringInterface(this);
                    break;
                default:
                    this.interface = new StandardScoringInterface(this);
            }
            
            // Render interface
            await this.interface.render();
            
            // Setup auto-save
            this.initAutoSave();
            
        } catch (error) {
            console.error('Failed to initialize scoring interface:', error);
            this.showError('Failed to load scoring interface. Please refresh and try again.');
        }
    }
    
    async loadRubricStructure() {
        const response = await fetch(`/api/rubric/${this.rubricId}/structure`);
        if (!response.ok) {
            throw new Error('Failed to load rubric structure');
        }
        this.rubricData = await response.json();
    }
    
    initAutoSave() {
        // Auto-save every 30 seconds
        this.autoSaveInterval = setInterval(() => {
            this.saveDraft();
        }, 30000);
        
        // Save on page unload
        window.addEventListener('beforeunload', () => {
            this.saveDraft();
        });
    }
    
    async saveDraft() {
        try {
            const scoreData = this.collectScoreData();
            scoreData.status = 'draft';
            
            await fetch('/api/judge/save-score', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(scoreData)
            });
            
            this.showToast('Draft saved automatically', 'success');
        } catch (error) {
            console.warn('Auto-save failed:', error);
        }
    }
    
    collectScoreData() {
        const criteriaScores = [];
        
        document.querySelectorAll('.scoring-criterion').forEach(criterion => {
            const criteriaId = criterion.dataset.criteriaId;
            const selectedInput = criterion.querySelector('input[type="radio"]:checked');
            const comment = criterion.querySelector('.criteria-comment input')?.value || '';
            
            if (selectedInput) {
                criteriaScores.push({
                    criteria_id: parseInt(criteriaId),
                    level_selected: parseInt(selectedInput.value),
                    points_awarded: parseFloat(selectedInput.dataset.points),
                    max_possible: parseFloat(selectedInput.dataset.maxPoints),
                    comment: comment
                });
            }
        });
        
        return {
            team_id: this.teamId,
            rubric_template_id: this.rubricId,
            criteria_scores: criteriaScores,
            judge_notes: document.querySelector('#judge-notes')?.value || '',
            device_info: {
                user_agent: navigator.userAgent,
                screen_resolution: `${screen.width}x${screen.height}`,
                timestamp: new Date().toISOString()
            }
        };
    }
    
    async submitScore() {
        try {
            const scoreData = this.collectScoreData();
            scoreData.status = 'submitted';
            
            // Validate completeness
            const validation = this.validateCompleteness(scoreData);
            if (!validation.valid) {
                this.showValidationErrors(validation.errors);
                return false;
            }
            
            const response = await fetch('/api/judge/submit-score', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(scoreData)
            });
            
            if (!response.ok) {
                throw new Error('Failed to submit score');
            }
            
            const result = await response.json();
            
            this.showToast('Score submitted successfully!', 'success');
            
            // Clear auto-save
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
            }
            
            // Redirect or disable interface
            setTimeout(() => {
                window.location.href = '/judge/dashboard';
            }, 2000);
            
            return true;
            
        } catch (error) {
            console.error('Score submission failed:', error);
            this.showError('Failed to submit score. Please try again.');
            return false;
        }
    }
    
    validateCompleteness(scoreData) {
        const errors = [];
        const totalCriteria = this.rubricData.sections.reduce((total, section) => {
            return total + section.criteria.length;
        }, 0);
        
        if (scoreData.criteria_scores.length < totalCriteria) {
            errors.push(`Please score all criteria (${scoreData.criteria_scores.length}/${totalCriteria} completed)`);
        }
        
        // Check for reasonable score ranges
        const totalScore = scoreData.criteria_scores.reduce((sum, score) => sum + score.points_awarded, 0);
        if (totalScore < 10) {
            errors.push('Total score seems unusually low. Please review your scoring.');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    showValidationErrors(errors) {
        const errorHtml = errors.map(error => `<li>${error}</li>`).join('');
        this.showError(`Please fix the following issues:<ul>${errorHtml}</ul>`);
    }
    
    showError(message) {
        // Create or update error display
        let errorDiv = document.querySelector('#scoring-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'scoring-error';
            errorDiv.className = 'alert alert-danger';
            document.querySelector('#scoring-container').prepend(errorDiv);
        }
        
        errorDiv.innerHTML = message;
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    showToast(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} toast-notification`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transition: opacity 0.3s;
        `;
        
        document.body.appendChild(toast);
        
        // Fade in
        setTimeout(() => toast.style.opacity = '1', 100);
        
        // Auto-remove
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Junior Category Interface (Visual & Simple)
class JuniorScoringInterface {
    constructor(parent) {
        this.parent = parent;
    }
    
    async render() {
        const container = document.getElementById('scoring-interface');
        
        const template = `
            <div class="junior-scoring">
                <div class="scoring-header">
                    <h2>🤖 Junior Robotics Scoring</h2>
                    <div class="team-info">
                        <span class="team-badge">Team: ${this.parent.teamId}</span>
                        <span class="category-badge">Junior (Grade R-3)</span>
                    </div>
                </div>
                
                <div class="visual-rubric">
                    ${this.renderSections()}
                </div>
                
                <div class="scoring-summary">
                    <div class="progress-indicator">
                        <h3>Scoring Progress</h3>
                        <div class="progress">
                            <div class="progress-bar bg-primary" id="scoring-progress" style="width: 0%">
                                <span class="progress-text">0% Complete</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="score-display">
                        <div class="score-section">
                            <h4>Game Challenge 🏆</h4>
                            <div class="score-value" id="game-score">0</div>
                            <small>× 3 = <span id="game-total">0</span> points</small>
                        </div>
                        <div class="score-section">
                            <h4>Research Challenge 📚</h4>
                            <div class="score-value" id="research-score">0</div>
                        </div>
                        <div class="score-section total">
                            <h4>Total Score</h4>
                            <div class="score-value" id="total-score">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="judge-notes">
                    <h3>Judge Comments 📝</h3>
                    <textarea id="judge-notes" class="form-control" rows="3" 
                              placeholder="Special observations about this team's performance..."></textarea>
                </div>
                
                <div class="scoring-actions">
                    <button class="btn btn-secondary" id="save-draft">💾 Save Draft</button>
                    <button class="btn btn-primary" id="submit-score">✨ Submit Score</button>
                </div>
            </div>
        `;
        
        container.innerHTML = template;
        this.bindEvents();
    }
    
    renderSections() {
        let html = '';
        
        this.parent.rubricData.sections.forEach(section => {
            html += `
                <div class="visual-section" data-section-type="${section.section_type}">
                    <h3>${this.getSectionIcon(section.section_type)} ${section.section_name}</h3>
                    <div class="criteria-grid">
                        ${this.renderVisualCriteria(section.criteria)}
                    </div>
                </div>
            `;
        });
        
        return html;
    }
    
    renderVisualCriteria(criteria) {
        let html = '';
        
        criteria.forEach(criterion => {
            html += `
                <div class="criteria-card scoring-criterion" data-criteria-id="${criterion.id}">
                    <h4>${criterion.criteria_name}</h4>
                    <p class="criteria-description">${criterion.criteria_description || ''}</p>
                    
                    <div class="visual-levels">
                        ${this.renderVisualLevels(criterion.levels, criterion.id)}
                    </div>
                    
                    <div class="criteria-comment">
                        <input type="text" class="form-control" placeholder="Optional comment...">
                    </div>
                </div>
            `;
        });
        
        return html;
    }
    
    renderVisualLevels(levels, criteriaId) {
        return levels.map(level => `
            <label class="visual-level level-${level.level_number}" 
                   style="border-color: ${level.display_color};">
                <input type="radio" name="criteria-${criteriaId}" 
                       value="${level.level_number}"
                       data-points="${level.points_awarded}"
                       data-max-points="${levels[levels.length - 1].points_awarded}">
                <div class="level-content">
                    <div class="level-icon" style="color: ${level.display_color};">
                        ${this.getLevelEmoji(level.level_number)}
                    </div>
                    <div class="level-info">
                        <strong>${level.level_name}</strong>
                        <span class="points">${level.points_awarded} pts</span>
                        <p class="level-desc">${this.getSimplifiedDescription(level.level_name)}</p>
                    </div>
                </div>
            </label>
        `).join('');
    }
    
    getSectionIcon(sectionType) {
        const icons = {
            'game_challenge': '🤖',
            'research_challenge': '📚',
            'presentation': '🗣️',
            'teamwork': '👥'
        };
        return icons[sectionType] || '⭐';
    }
    
    getLevelEmoji(levelNumber) {
        const emojis = {
            1: '😟',
            2: '😐', 
            3: '😊',
            4: '🌟'
        };
        return emojis[levelNumber] || '⭐';
    }
    
    getSimplifiedDescription(levelName) {
        const descriptions = {
            'Basic': 'Needs practice 💪',
            'Developing': 'Getting better! 📈',
            'Accomplished': 'Great job! 👏',
            'Exceeded': 'Amazing! 🎉'
        };
        return descriptions[levelName] || levelName;
    }
    
    bindEvents() {
        // Level selection
        document.querySelectorAll('.visual-level input[type="radio"]').forEach(input => {
            input.addEventListener('change', () => {
                this.updateScores();
                this.updateProgress();
            });
        });
        
        // Action buttons
        document.getElementById('save-draft').addEventListener('click', () => {
            this.parent.saveDraft();
        });
        
        document.getElementById('submit-score').addEventListener('click', () => {
            this.parent.submitScore();
        });
    }
    
    updateScores() {
        let gameScore = 0;
        let researchScore = 0;
        
        document.querySelectorAll('.scoring-criterion').forEach(criterion => {
            const sectionType = criterion.closest('.visual-section').dataset.sectionType;
            const selectedInput = criterion.querySelector('input[type="radio"]:checked');
            
            if (selectedInput) {
                const points = parseFloat(selectedInput.dataset.points);
                
                if (sectionType === 'game_challenge') {
                    gameScore += points;
                } else {
                    researchScore += points;
                }
            }
        });
        
        // Apply multiplier for game challenge
        const gameTotalScore = gameScore * 3;
        const totalScore = gameTotalScore + researchScore;
        
        // Update display
        document.getElementById('game-score').textContent = gameScore.toFixed(1);
        document.getElementById('game-total').textContent = gameTotalScore.toFixed(1);
        document.getElementById('research-score').textContent = researchScore.toFixed(1);
        document.getElementById('total-score').textContent = totalScore.toFixed(1);
    }
    
    updateProgress() {
        const totalCriteria = document.querySelectorAll('.scoring-criterion').length;
        const scoredCriteria = document.querySelectorAll('.scoring-criterion input[type="radio"]:checked').length;
        const progress = (scoredCriteria / totalCriteria) * 100;
        
        const progressBar = document.getElementById('scoring-progress');
        progressBar.style.width = progress + '%';
        progressBar.querySelector('.progress-text').textContent = Math.round(progress) + '% Complete';
        
        // Change color based on progress
        progressBar.className = 'progress-bar';
        if (progress === 100) {
            progressBar.classList.add('bg-success');
        } else if (progress >= 50) {
            progressBar.classList.add('bg-warning');
        } else {
            progressBar.classList.add('bg-primary');
        }
    }
}

// Standard Scoring Interface (for SPIKE/Explorer categories)
class SpikeScoringInterface {
    constructor(parent) {
        this.parent = parent;
    }
    
    async render() {
        const container = document.getElementById('scoring-interface');
        
        const template = `
            <div class="spike-scoring">
                <div class="scoring-header">
                    <h2>⚡ SPIKE/Explorer Scoring</h2>
                    <div class="team-info">
                        <span class="team-badge">Team: ${this.parent.teamId}</span>
                        <span class="category-badge">SPIKE (Grade 4-9)</span>
                    </div>
                </div>
                
                <div class="standard-rubric">
                    ${this.renderStandardSections()}
                </div>
                
                <div class="scoring-controls">
                    <div class="timer-section">
                        <h4>⏱️ Mission Timer</h4>
                        <div class="timer-display">00:00</div>
                        <div class="timer-buttons">
                            <button class="btn btn-sm btn-success" id="start-timer">Start</button>
                            <button class="btn btn-sm btn-warning" id="pause-timer">Pause</button>
                            <button class="btn btn-sm btn-secondary" id="reset-timer">Reset</button>
                        </div>
                    </div>
                    
                    <div class="score-summary">
                        <div class="score-breakdown">
                            <div class="score-item">
                                <label>Game Challenge:</label>
                                <span id="game-score">0</span> × 3 = <strong id="game-total">0</strong>
                            </div>
                            <div class="score-item">
                                <label>Research Challenge:</label>
                                <span id="research-score">0</span>
                            </div>
                            <div class="score-item total">
                                <label>Total Score:</label>
                                <strong id="total-score">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="judge-notes">
                    <h3>Judge Observations</h3>
                    <textarea id="judge-notes" class="form-control" rows="4" 
                              placeholder="Technical observations, programming insights, teamwork notes..."></textarea>
                </div>
                
                <div class="scoring-actions">
                    <button class="btn btn-secondary" id="save-draft">💾 Save Draft</button>
                    <button class="btn btn-primary" id="submit-score">📤 Submit Score</button>
                </div>
            </div>
        `;
        
        container.innerHTML = template;
        this.bindEvents();
        this.initTimer();
    }
    
    renderStandardSections() {
        let html = '';
        
        this.parent.rubricData.sections.forEach(section => {
            html += `
                <div class="rubric-section" data-section-type="${section.section_type}">
                    <div class="section-header">
                        <h3>${section.section_name}</h3>
                        <div class="section-meta">
                            <span class="weight-badge">${section.section_weight}%</span>
                            ${section.multiplier > 1 ? `<span class="multiplier-badge">×${section.multiplier}</span>` : ''}
                        </div>
                    </div>
                    
                    <div class="criteria-list">
                        ${this.renderStandardCriteria(section.criteria)}
                    </div>
                </div>
            `;
        });
        
        return html;
    }
    
    renderStandardCriteria(criteria) {
        return criteria.map(criterion => `
            <div class="scoring-criterion" data-criteria-id="${criterion.id}">
                <div class="criterion-header">
                    <h4>${criterion.criteria_name}</h4>
                    <span class="max-points">${criterion.max_points} pts</span>
                </div>
                
                ${criterion.criteria_description ? `<p class="criterion-description">${criterion.criteria_description}</p>` : ''}
                
                <div class="level-options">
                    ${criterion.levels.map(level => `
                        <label class="level-option level-${level.level_number}">
                            <input type="radio" name="criteria-${criterion.id}" 
                                   value="${level.level_number}"
                                   data-points="${level.points_awarded}"
                                   data-max-points="${criterion.max_points}">
                            <div class="level-content">
                                <div class="level-header">
                                    <span class="level-name">${level.level_name}</span>
                                    <span class="level-points">${level.points_awarded}pts</span>
                                </div>
                                <p class="level-description">${level.level_description}</p>
                            </div>
                        </label>
                    `).join('')}
                </div>
                
                <div class="criteria-comment">
                    <input type="text" class="form-control" placeholder="Optional comment...">
                </div>
            </div>
        `).join('');
    }
    
    initTimer() {
        let startTime = null;
        let elapsed = 0;
        let timerInterval = null;
        
        const display = document.querySelector('.timer-display');
        const startBtn = document.getElementById('start-timer');
        const pauseBtn = document.getElementById('pause-timer');
        const resetBtn = document.getElementById('reset-timer');
        
        function updateDisplay() {
            const totalSeconds = Math.floor(elapsed / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        startBtn.addEventListener('click', () => {
            startTime = Date.now() - elapsed;
            timerInterval = setInterval(() => {
                elapsed = Date.now() - startTime;
                updateDisplay();
            }, 100);
            
            startBtn.disabled = true;
            pauseBtn.disabled = false;
        });
        
        pauseBtn.addEventListener('click', () => {
            clearInterval(timerInterval);
            startBtn.disabled = false;
            pauseBtn.disabled = true;
        });
        
        resetBtn.addEventListener('click', () => {
            clearInterval(timerInterval);
            elapsed = 0;
            updateDisplay();
            startBtn.disabled = false;
            pauseBtn.disabled = true;
        });
    }
    
    bindEvents() {
        // Level selection
        document.querySelectorAll('.level-option input[type="radio"]').forEach(input => {
            input.addEventListener('change', () => {
                // Highlight selected level
                const criterion = input.closest('.scoring-criterion');
                criterion.querySelectorAll('.level-option').forEach(opt => opt.classList.remove('selected'));
                input.closest('.level-option').classList.add('selected');
                
                this.updateScores();
            });
        });
        
        // Action buttons
        document.getElementById('save-draft').addEventListener('click', () => {
            this.parent.saveDraft();
        });
        
        document.getElementById('submit-score').addEventListener('click', () => {
            this.parent.submitScore();
        });
    }
    
    updateScores() {
        let gameScore = 0;
        let researchScore = 0;
        
        document.querySelectorAll('.scoring-criterion').forEach(criterion => {
            const sectionType = criterion.closest('.rubric-section').dataset.sectionType;
            const selectedInput = criterion.querySelector('input[type="radio"]:checked');
            
            if (selectedInput) {
                const points = parseFloat(selectedInput.dataset.points);
                
                if (sectionType === 'game_challenge') {
                    gameScore += points;
                } else {
                    researchScore += points;
                }
            }
        });
        
        const gameTotalScore = gameScore * 3;
        const totalScore = gameTotalScore + researchScore;
        
        document.getElementById('game-score').textContent = gameScore.toFixed(1);
        document.getElementById('game-total').textContent = gameTotalScore.toFixed(1);
        document.getElementById('research-score').textContent = researchScore.toFixed(1);
        document.getElementById('total-score').textContent = totalScore.toFixed(1);
    }
}

// Arduino Category Interface (Technical)
class ArduinoScoringInterface {
    constructor(parent) {
        this.parent = parent;
    }
    
    async render() {
        const container = document.getElementById('scoring-interface');
        
        const template = `
            <div class="arduino-scoring">
                <div class="scoring-header">
                    <h2>🔧 Arduino/SciBOT Scoring</h2>
                    <div class="team-info">
                        <span class="team-badge">Team: ${this.parent.teamId}</span>
                        <span class="category-badge">Arduino (Grade 8-12)</span>
                    </div>
                </div>
                
                <div class="technical-assessment">
                    ${this.renderTechnicalSections()}
                </div>
                
                <div class="technical-metrics">
                    <div class="code-analysis">
                        <h4>📊 Code Quality Metrics</h4>
                        <div class="metrics-grid">
                            <div class="metric-item">
                                <label>Complexity Level:</label>
                                <select class="form-control" id="complexity-level">
                                    <option value="">Select...</option>
                                    <option value="1">Basic</option>
                                    <option value="2">Intermediate</option>
                                    <option value="3">Advanced</option>
                                    <option value="4">Expert</option>
                                </select>
                            </div>
                            
                            <div class="metric-item">
                                <label>Documentation Quality:</label>
                                <input type="range" min="0" max="10" value="5" class="form-range" id="documentation-quality">
                                <span class="range-value">5/10</span>
                            </div>
                            
                            <div class="metric-item">
                                <label>Performance Efficiency:</label>
                                <input type="range" min="0" max="10" value="5" class="form-range" id="performance-efficiency">
                                <span class="range-value">5/10</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mission-checkpoints">
                        <h4>✅ Mission Checkpoints</h4>
                        <div class="checkpoint-list">
                            <label><input type="checkbox" id="checkpoint-1"> Robot Initialization & Setup</label>
                            <label><input type="checkbox" id="checkpoint-2"> Navigation & Movement</label>
                            <label><input type="checkbox" id="checkpoint-3"> Sensor Integration</label>
                            <label><input type="checkbox" id="checkpoint-4"> Task Execution</label>
                            <label><input type="checkbox" id="checkpoint-5"> Error Handling</label>
                            <label><input type="checkbox" id="checkpoint-6"> Mission Completion</label>
                        </div>
                    </div>
                </div>
                
                <div class="standard-rubric">
                    ${this.renderTechnicalCriteria()}
                </div>
                
                <div class="technical-observations">
                    <h3>Technical Observations & Code Review</h3>
                    <textarea id="judge-notes" class="form-control" rows="6" 
                              placeholder="Technical implementation details, code quality observations, innovation notes, areas for improvement..."></textarea>
                </div>
                
                <div class="scoring-summary">
                    <div class="score-breakdown">
                        <div class="score-section">
                            <h4>Game Challenge (Technical)</h4>
                            <div class="score-display" id="game-score">0</div>
                            <small>× 3 multiplier = <span id="game-total">0</span></small>
                        </div>
                        <div class="score-section">
                            <h4>Research Challenge</h4>
                            <div class="score-display" id="research-score">0</div>
                        </div>
                        <div class="score-section total">
                            <h4>Final Score</h4>
                            <div class="score-display" id="total-score">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="scoring-actions">
                    <button class="btn btn-secondary" id="save-draft">💾 Save Draft</button>
                    <button class="btn btn-warning" id="request-review">⚠️ Request Review</button>
                    <button class="btn btn-primary" id="submit-score">📤 Submit Score</button>
                </div>
            </div>
        `;
        
        container.innerHTML = template;
        this.bindEvents();
    }
    
    renderTechnicalSections() {
        // Similar to standard sections but with technical focus
        return this.parent.rubricData.sections.map(section => `
            <div class="rubric-section technical-section" data-section-type="${section.section_type}">
                <div class="section-header">
                    <h3>${section.section_name}</h3>
                    <div class="section-meta">
                        <span class="weight-badge">${section.section_weight}%</span>
                        ${section.multiplier > 1 ? `<span class="multiplier-badge">×${section.multiplier}</span>` : ''}
                    </div>
                </div>
                
                <div class="technical-guidance">
                    <p><strong>Focus Areas:</strong> ${this.getTechnicalGuidance(section.section_type)}</p>
                </div>
                
                <div class="criteria-list">
                    ${this.renderTechnicalCriteriaForSection(section.criteria)}
                </div>
            </div>
        `).join('');
    }
    
    getTechnicalGuidance(sectionType) {
        const guidance = {
            'game_challenge': 'Code complexity, algorithm efficiency, hardware integration, error handling',
            'research_challenge': 'Innovation depth, technical documentation, real-world applications',
            'technical': 'Implementation quality, best practices, optimization techniques',
            'innovation': 'Novel approaches, creative solutions, advanced features'
        };
        return guidance[sectionType] || 'Technical implementation and quality';
    }
    
    renderTechnicalCriteriaForSection(criteria) {
        return criteria.map(criterion => `
            <div class="scoring-criterion technical-criterion" data-criteria-id="${criterion.id}">
                <div class="criterion-header">
                    <h4>${criterion.criteria_name}</h4>
                    <span class="max-points">${criterion.max_points} pts</span>
                </div>
                
                <div class="technical-levels">
                    ${criterion.levels.map(level => `
                        <label class="technical-level level-${level.level_number}">
                            <input type="radio" name="criteria-${criterion.id}" 
                                   value="${level.level_number}"
                                   data-points="${level.points_awarded}"
                                   data-max-points="${criterion.max_points}">
                            <div class="level-content">
                                <div class="level-indicator" style="background-color: ${level.display_color};">
                                    ${level.level_number}
                                </div>
                                <div class="level-details">
                                    <strong>${level.level_name}</strong> - ${level.points_awarded}pts
                                    <p>${level.level_description}</p>
                                </div>
                            </div>
                        </label>
                    `).join('')}
                </div>
                
                <div class="criteria-comment">
                    <input type="text" class="form-control" placeholder="Technical observations...">
                </div>
            </div>
        `).join('');
    }
    
    renderTechnicalCriteria() {
        // Render main rubric criteria with technical styling
        let html = '';
        
        this.parent.rubricData.sections.forEach(section => {
            html += `<div class="rubric-section" data-section-type="${section.section_type}">`;
            html += section.criteria.map(criterion => 
                this.renderTechnicalCriteriaForSection([criterion])
            ).join('');
            html += '</div>';
        });
        
        return html;
    }
    
    bindEvents() {
        // Range inputs
        document.querySelectorAll('input[type="range"]').forEach(range => {
            range.addEventListener('input', (e) => {
                const valueSpan = e.target.nextElementSibling;
                valueSpan.textContent = `${e.target.value}/10`;
            });
        });
        
        // Checkbox tracking
        document.querySelectorAll('.checkpoint-list input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', this.updateCheckpoints.bind(this));
        });
        
        // Standard scoring events
        document.querySelectorAll('.technical-level input[type="radio"]').forEach(input => {
            input.addEventListener('change', () => {
                const criterion = input.closest('.scoring-criterion');
                criterion.querySelectorAll('.technical-level').forEach(opt => opt.classList.remove('selected'));
                input.closest('.technical-level').classList.add('selected');
                
                this.updateScores();
            });
        });
        
        // Action buttons
        document.getElementById('save-draft').addEventListener('click', () => {
            this.parent.saveDraft();
        });
        
        document.getElementById('request-review').addEventListener('click', () => {
            this.requestTechnicalReview();
        });
        
        document.getElementById('submit-score').addEventListener('click', () => {
            this.parent.submitScore();
        });
    }
    
    updateCheckpoints() {
        const checkboxes = document.querySelectorAll('.checkpoint-list input[type="checkbox"]');
        const checked = document.querySelectorAll('.checkpoint-list input[type="checkbox"]:checked');
        const progress = (checked.length / checkboxes.length) * 100;
        
        // Visual feedback could be added here
        console.log(`Mission progress: ${progress}%`);
    }
    
    async requestTechnicalReview() {
        // Request additional review for complex technical implementations
        try {
            const scoreData = this.parent.collectScoreData();
            scoreData.review_requested = true;
            scoreData.review_reason = 'Complex technical implementation requires additional review';
            
            await fetch('/api/judge/request-review', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(scoreData)
            });
            
            this.parent.showToast('Technical review requested successfully', 'warning');
        } catch (error) {
            this.parent.showError('Failed to request review');
        }
    }
    
    updateScores() {
        let gameScore = 0;
        let researchScore = 0;
        
        document.querySelectorAll('.scoring-criterion').forEach(criterion => {
            const sectionType = criterion.closest('.rubric-section').dataset.sectionType;
            const selectedInput = criterion.querySelector('input[type="radio"]:checked');
            
            if (selectedInput) {
                const points = parseFloat(selectedInput.dataset.points);
                
                if (sectionType === 'game_challenge') {
                    gameScore += points;
                } else {
                    researchScore += points;
                }
            }
        });
        
        const gameTotalScore = gameScore * 3;
        const totalScore = gameTotalScore + researchScore;
        
        document.getElementById('game-score').textContent = gameScore.toFixed(1);
        document.getElementById('game-total').textContent = gameTotalScore.toFixed(1);
        document.getElementById('research-score').textContent = researchScore.toFixed(1);
        document.getElementById('total-score').textContent = totalScore.toFixed(1);
    }
}

// Inventor Category Interface (Comprehensive)
class InventorScoringInterface {
    constructor(parent) {
        this.parent = parent;
    }
    
    async render() {
        const container = document.getElementById('scoring-interface');
        
        const template = `
            <div class="inventor-scoring">
                <div class="scoring-header">
                    <h2>💡 Inventor Category Scoring</h2>
                    <div class="team-info">
                        <span class="team-badge">Team: ${this.parent.teamId}</span>
                        <span class="category-badge">Inventor (All Grades)</span>
                    </div>
                </div>
                
                <div class="innovation-assessment">
                    <div class="assessment-tabs">
                        <button class="tab-button active" data-tab="prototype">🔧 Prototype</button>
                        <button class="tab-button" data-tab="innovation">💡 Innovation</button>
                        <button class="tab-button" data-tab="impact">🌍 Impact</button>
                        <button class="tab-button" data-tab="presentation">🎤 Presentation</button>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-panel active" id="prototype-panel">
                            ${this.renderPrototypeAssessment()}
                        </div>
                        <div class="tab-panel" id="innovation-panel">
                            ${this.renderInnovationAssessment()}
                        </div>
                        <div class="tab-panel" id="impact-panel">
                            ${this.renderImpactAssessment()}
                        </div>
                        <div class="tab-panel" id="presentation-panel">
                            ${this.renderPresentationAssessment()}
                        </div>
                    </div>
                </div>
                
                <div class="comprehensive-rubric">
                    ${this.renderComprehensiveCriteria()}
                </div>
                
                <div class="innovation-notes">
                    <h3>Innovation Assessment & Feedback</h3>
                    <textarea id="judge-notes" class="form-control" rows="8" 
                              placeholder="Detailed assessment of innovation, prototype quality, market potential, community impact, presentation effectiveness, team collaboration..."></textarea>
                </div>
                
                <div class="scoring-summary">
                    <div class="comprehensive-score-display">
                        <div class="score-category">
                            <h4>Game Challenge (Prototype & Innovation)</h4>
                            <div class="score-value" id="game-score">0</div>
                            <small>× 3 = <span id="game-total">0</span> points</small>
                        </div>
                        <div class="score-category">
                            <h4>Research Challenge (Impact & Application)</h4>
                            <div class="score-value" id="research-score">0</div>
                        </div>
                        <div class="score-category total">
                            <h4>Total Innovation Score</h4>
                            <div class="score-value" id="total-score">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="scoring-actions">
                    <button class="btn btn-secondary" id="save-draft">💾 Save Progress</button>
                    <button class="btn btn-info" id="export-feedback">📄 Export Feedback</button>
                    <button class="btn btn-primary" id="submit-score">🏆 Submit Final Score</button>
                </div>
            </div>
        `;
        
        container.innerHTML = template;
        this.bindEvents();
    }
    
    renderPrototypeAssessment() {
        return `
            <div class="prototype-evaluation">
                <h4>Prototype Quality Assessment</h4>
                <div class="evaluation-criteria">
                    <div class="criterion">
                        <label>Functionality Level:</label>
                        <div class="functionality-scale">
                            <label><input type="radio" name="functionality" value="1"> Concept Only</label>
                            <label><input type="radio" name="functionality" value="2"> Basic Prototype</label>
                            <label><input type="radio" name="functionality" value="3"> Working Model</label>
                            <label><input type="radio" name="functionality" value="4"> Fully Functional</label>
                        </div>
                    </div>
                    
                    <div class="criterion">
                        <label>Build Quality:</label>
                        <input type="range" min="0" max="10" value="5" class="form-range" id="build-quality">
                        <span class="range-value">5/10</span>
                    </div>
                    
                    <div class="criterion">
                        <label>Design Elegance:</label>
                        <input type="range" min="0" max="10" value="5" class="form-range" id="design-elegance">
                        <span class="range-value">5/10</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderInnovationAssessment() {
        return `
            <div class="innovation-evaluation">
                <h4>Innovation & Creativity Assessment</h4>
                <div class="innovation-metrics">
                    <div class="metric">
                        <label>Novelty of Approach:</label>
                        <div class="star-rating" data-rating="novelty">
                            ${[1,2,3,4,5].map(n => `<span class="star" data-value="${n}">⭐</span>`).join('')}
                        </div>
                    </div>
                    
                    <div class="metric">
                        <label>Creative Problem Solving:</label>
                        <div class="star-rating" data-rating="creativity">
                            ${[1,2,3,4,5].map(n => `<span class="star" data-value="${n}">⭐</span>`).join('')}
                        </div>
                    </div>
                    
                    <div class="metric">
                        <label>Technical Innovation:</label>
                        <div class="star-rating" data-rating="technical">
                            ${[1,2,3,4,5].map(n => `<span class="star" data-value="${n}">⭐</span>`).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderImpactAssessment() {
        return `
            <div class="impact-evaluation">
                <h4>Community & Environmental Impact</h4>
                <div class="impact-areas">
                    <div class="impact-category">
                        <h5>Community Benefit</h5>
                        <textarea class="form-control" rows="3" placeholder="How does this innovation benefit the community?"></textarea>
                        <div class="impact-scale">
                            <label>Impact Level:</label>
                            <select class="form-control">
                                <option value="">Select...</option>
                                <option value="1">Local Impact</option>
                                <option value="2">Regional Impact</option>
                                <option value="3">National Impact</option>
                                <option value="4">Global Potential</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="impact-category">
                        <h5>Sustainability Considerations</h5>
                        <div class="sustainability-checklist">
                            <label><input type="checkbox"> Environmental Awareness</label>
                            <label><input type="checkbox"> Resource Efficiency</label>
                            <label><input type="checkbox"> Waste Reduction</label>
                            <label><input type="checkbox"> Renewable/Sustainable Materials</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderPresentationAssessment() {
        return `
            <div class="presentation-evaluation">
                <h4>Presentation & Communication Assessment</h4>
                <div class="presentation-criteria">
                    <div class="criterion">
                        <label>Clarity of Explanation:</label>
                        <input type="range" min="0" max="10" value="5" class="form-range">
                        <span class="range-value">5/10</span>
                    </div>
                    
                    <div class="criterion">
                        <label>Audience Engagement:</label>
                        <input type="range" min="0" max="10" value="5" class="form-range">
                        <span class="range-value">5/10</span>
                    </div>
                    
                    <div class="criterion">
                        <label>Question Handling:</label>
                        <input type="range" min="0" max="10" value="5" class="form-range">
                        <span class="range-value">5/10</span>
                    </div>
                    
                    <div class="criterion">
                        <label>Visual Aids Quality:</label>
                        <input type="range" min="0" max="10" value="5" class="form-range">
                        <span class="range-value">5/10</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderComprehensiveCriteria() {
        let html = '';
        
        this.parent.rubricData.sections.forEach(section => {
            html += `
                <div class="rubric-section comprehensive-section" data-section-type="${section.section_type}">
                    <div class="section-header">
                        <h3>${section.section_name}</h3>
                        <div class="section-meta">
                            <span class="weight-badge">${section.section_weight}%</span>
                            ${section.multiplier > 1 ? `<span class="multiplier-badge">×${section.multiplier}</span>` : ''}
                        </div>
                    </div>
                    
                    <div class="criteria-list">
                        ${section.criteria.map(criterion => `
                            <div class="scoring-criterion comprehensive-criterion" data-criteria-id="${criterion.id}">
                                <div class="criterion-header">
                                    <h4>${criterion.criteria_name}</h4>
                                    <span class="max-points">${criterion.max_points} pts</span>
                                </div>
                                
                                <div class="comprehensive-levels">
                                    ${criterion.levels.map(level => `
                                        <label class="comprehensive-level level-${level.level_number}">
                                            <input type="radio" name="criteria-${criterion.id}" 
                                                   value="${level.level_number}"
                                                   data-points="${level.points_awarded}"
                                                   data-max-points="${criterion.max_points}">
                                            <div class="level-content">
                                                <div class="level-badge" style="background-color: ${level.display_color};">
                                                    ${level.level_name}
                                                </div>
                                                <div class="level-details">
                                                    <span class="points">${level.points_awarded} points</span>
                                                    <p>${level.level_description}</p>
                                                </div>
                                            </div>
                                        </label>
                                    `).join('')}
                                </div>
                                
                                <div class="criteria-comment">
                                    <textarea class="form-control" rows="2" placeholder="Detailed feedback for this criterion..."></textarea>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        });
        
        return html;
    }
    
    bindEvents() {
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const tabName = e.target.dataset.tab;
                this.switchTab(tabName);
            });
        });
        
        // Star ratings
        document.querySelectorAll('.star-rating').forEach(rating => {
            const stars = rating.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    stars.forEach((s, i) => {
                        s.classList.toggle('active', i <= index);
                    });
                });
            });
        });
        
        // Range inputs
        document.querySelectorAll('input[type="range"]').forEach(range => {
            range.addEventListener('input', (e) => {
                const valueSpan = e.target.nextElementSibling;
                valueSpan.textContent = `${e.target.value}/10`;
            });
        });
        
        // Comprehensive scoring
        document.querySelectorAll('.comprehensive-level input[type="radio"]').forEach(input => {
            input.addEventListener('change', () => {
                const criterion = input.closest('.scoring-criterion');
                criterion.querySelectorAll('.comprehensive-level').forEach(opt => opt.classList.remove('selected'));
                input.closest('.comprehensive-level').classList.add('selected');
                
                this.updateScores();
            });
        });
        
        // Action buttons
        document.getElementById('save-draft').addEventListener('click', () => {
            this.parent.saveDraft();
        });
        
        document.getElementById('export-feedback').addEventListener('click', () => {
            this.exportDetailedFeedback();
        });
        
        document.getElementById('submit-score').addEventListener('click', () => {
            this.parent.submitScore();
        });
    }
    
    switchTab(tabName) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
        
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`${tabName}-panel`).classList.add('active');
    }
    
    async exportDetailedFeedback() {
        try {
            const feedbackData = this.collectDetailedFeedback();
            
            const response = await fetch('/api/judge/export-feedback', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(feedbackData)
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `inventor-feedback-team-${this.parent.teamId}.pdf`;
                a.click();
            }
            
            this.parent.showToast('Feedback exported successfully', 'success');
        } catch (error) {
            this.parent.showError('Failed to export feedback');
        }
    }
    
    collectDetailedFeedback() {
        return {
            team_id: this.parent.teamId,
            prototype_assessment: this.collectPrototypeData(),
            innovation_assessment: this.collectInnovationData(),
            impact_assessment: this.collectImpactData(),
            presentation_assessment: this.collectPresentationData(),
            detailed_scores: this.parent.collectScoreData()
        };
    }
    
    collectPrototypeData() {
        return {
            functionality: document.querySelector('input[name="functionality"]:checked')?.value,
            build_quality: document.getElementById('build-quality')?.value,
            design_elegance: document.getElementById('design-elegance')?.value
        };
    }
    
    collectInnovationData() {
        return {
            novelty: this.getStarRating('novelty'),
            creativity: this.getStarRating('creativity'),
            technical: this.getStarRating('technical')
        };
    }
    
    collectImpactData() {
        return {
            community_benefit: document.querySelector('.impact-evaluation textarea')?.value,
            sustainability_items: Array.from(document.querySelectorAll('.sustainability-checklist input:checked')).map(cb => cb.nextSibling.textContent)
        };
    }
    
    collectPresentationData() {
        const ranges = document.querySelectorAll('.presentation-evaluation input[type="range"]');
        return {
            clarity: ranges[0]?.value,
            engagement: ranges[1]?.value,
            question_handling: ranges[2]?.value,
            visual_aids: ranges[3]?.value
        };
    }
    
    getStarRating(ratingName) {
        const activeStars = document.querySelectorAll(`[data-rating="${ratingName}"] .star.active`);
        return activeStars.length;
    }
    
    updateScores() {
        let gameScore = 0;
        let researchScore = 0;
        
        document.querySelectorAll('.scoring-criterion').forEach(criterion => {
            const sectionType = criterion.closest('.rubric-section').dataset.sectionType;
            const selectedInput = criterion.querySelector('input[type="radio"]:checked');
            
            if (selectedInput) {
                const points = parseFloat(selectedInput.dataset.points);
                
                if (sectionType === 'game_challenge') {
                    gameScore += points;
                } else {
                    researchScore += points;
                }
            }
        });
        
        const gameTotalScore = gameScore * 3;
        const totalScore = gameTotalScore + researchScore;
        
        document.getElementById('game-score').textContent = gameScore.toFixed(1);
        document.getElementById('game-total').textContent = gameTotalScore.toFixed(1);
        document.getElementById('research-score').textContent = researchScore.toFixed(1);
        document.getElementById('total-score').textContent = totalScore.toFixed(1);
    }
}

// Standard fallback interface
class StandardScoringInterface extends SpikeScoringInterface {
    constructor(parent) {
        super(parent);
    }
    
    async render() {
        // Use SPIKE interface as fallback with modified header
        await super.render();
        const header = document.querySelector('.scoring-header h2');
        if (header) {
            header.textContent = '📊 Standard Scoring Interface';
        }
    }
}