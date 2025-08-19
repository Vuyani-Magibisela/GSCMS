<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Wizard Progress Header -->
<div class="wizard-header">
    <div class="wizard-progress">
        <div class="progress-steps">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-label">Basic Information</div>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-label">Phase Configuration</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">Category Setup</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-label">Registration Rules</div>
            </div>
            <div class="step">
                <div class="step-number">5</div>
                <div class="step-label">Competition Rules</div>
            </div>
            <div class="step">
                <div class="step-number">6</div>
                <div class="step-label">Review & Deploy</div>
            </div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: 33.33%"></div>
        </div>
    </div>
</div>

<!-- Step Content -->
<div class="wizard-content">
    <div class="step-header">
        <h2 class="step-title"><?= htmlspecialchars($step_title) ?></h2>
        <p class="step-description"><?= htmlspecialchars($step_description) ?></p>
    </div>

    <form id="wizardStep2Form" class="wizard-form">
        <input type="hidden" name="step" value="2">
        
        <!-- Competition Type Info -->
        <div class="info-banner">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-content">
                <h4>Configuration for <?= ucfirst($competition_type) ?> Competition</h4>
                <p>The phase templates below are optimized for <?= $competition_type ?> competitions and can be customized as needed.</p>
            </div>
        </div>
        
        <!-- Phase Configuration Grid -->
        <div class="phases-grid">
            <?php foreach ($phase_templates as $phaseKey => $phase): ?>
                <div class="phase-card <?= $phase['enabled'] ? 'enabled' : 'disabled' ?>" data-phase="<?= $phaseKey ?>">
                    <div class="phase-header">
                        <div class="phase-toggle">
                            <label class="switch">
                                <input type="checkbox" name="phases[<?= $phaseKey ?>][enabled]" 
                                       value="1" <?= $phase['enabled'] ? 'checked' : '' ?>
                                       <?= isset($phase['note']) ? 'disabled' : '' ?>
                                       onchange="togglePhase('<?= $phaseKey ?>')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <h3 class="phase-name"><?= htmlspecialchars($phase['name']) ?></h3>
                        <?php if (isset($phase['note'])): ?>
                            <div class="phase-note">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><?= htmlspecialchars($phase['note']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="phase-content" style="<?= $phase['enabled'] ? '' : 'display: none;' ?>">
                        <!-- Phase Name (Editable) -->
                        <div class="form-group">
                            <label class="form-label">Phase Name</label>
                            <input type="text" name="phases[<?= $phaseKey ?>][name]" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($wizard_data['step_2']['phases'][$phaseKey]['name'] ?? $phase['name']) ?>"
                                   placeholder="Enter custom phase name">
                        </div>
                        
                        <!-- Phase Description -->
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="phases[<?= $phaseKey ?>][description]" 
                                      class="form-control" rows="2"
                                      placeholder="Describe this phase..."><?= htmlspecialchars($wizard_data['step_2']['phases'][$phaseKey]['description'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Phase Dates -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Start Date</label>
                                <input type="date" name="phases[<?= $phaseKey ?>][start_date]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($wizard_data['step_2']['phases'][$phaseKey]['start_date'] ?? '') ?>" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">End Date</label>
                                <input type="date" name="phases[<?= $phaseKey ?>][end_date]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($wizard_data['step_2']['phases'][$phaseKey]['end_date'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <!-- Capacity Settings -->
                        <div class="form-group">
                            <label class="form-label">Capacity per Category</label>
                            <div class="input-group">
                                <input type="number" name="phases[<?= $phaseKey ?>][capacity]" 
                                       class="form-control" min="1" 
                                       value="<?= htmlspecialchars($wizard_data['step_2']['phases'][$phaseKey]['capacity'] ?? $phase['capacity'] ?? 30) ?>"
                                       placeholder="30">
                                <span class="input-addon">teams</span>
                            </div>
                            <small class="form-help">Maximum teams per category that can advance to this phase</small>
                        </div>
                        
                        <!-- Venue Requirements -->
                        <div class="form-group">
                            <label class="form-label">Venue Requirements</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="phases[<?= $phaseKey ?>][venue_requirements][]" value="tables">
                                    <span>Competition Tables</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="phases[<?= $phaseKey ?>][venue_requirements][]" value="power">
                                    <span>Power Supply</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="phases[<?= $phaseKey ?>][venue_requirements][]" value="judging_area">
                                    <span>Judging Area</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="phases[<?= $phaseKey ?>][venue_requirements][]" value="audience_seating">
                                    <span>Audience Seating</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Advancement Criteria -->
                        <div class="form-group">
                            <label class="form-label">Advancement Criteria</label>
                            <select name="phases[<?= $phaseKey ?>][advancement_type]" class="form-control">
                                <option value="top_scores">Top Scores</option>
                                <option value="percentage">Top Percentage</option>
                                <option value="qualified_only">Pre-qualified Only</option>
                                <option value="all_participants">All Participants</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Advancement Value</label>
                            <input type="number" name="phases[<?= $phaseKey ?>][advancement_value]" 
                                   class="form-control" min="1" 
                                   value="<?= htmlspecialchars($wizard_data['step_2']['phases'][$phaseKey]['advancement_value'] ?? '') ?>"
                                   placeholder="e.g., 6 (teams) or 25 (percentage)">
                            <small class="form-help">Number of teams or percentage that advance from this phase</small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Phase Timeline Visualization -->
        <div class="timeline-section">
            <h3 class="section-title">Phase Timeline</h3>
            <div class="timeline-container">
                <div class="timeline-visualization" id="phaseTimeline">
                    <!-- Timeline will be generated by JavaScript -->
                </div>
            </div>
        </div>
        
        <!-- Auto-save Indicator -->
        <div class="auto-save-indicator" id="autoSaveIndicator" style="display: none;">
            <i class="fas fa-save"></i>
            <span>Auto-saving...</span>
        </div>
    </form>
</div>

<!-- Wizard Navigation -->
<div class="wizard-navigation">
    <div class="nav-left">
        <a href="<?= url('/admin/competition-setup/wizard/step/1') ?>" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Previous Step
        </a>
    </div>
    
    <div class="nav-right">
        <button type="button" class="btn btn-outline" onclick="saveDraft()">
            <i class="fas fa-save"></i> Save Draft
        </button>
        <button type="button" class="btn btn-primary" onclick="nextStep()">
            Next Step <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<script>
// Form management and validation
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save functionality
    const form = document.getElementById('wizardStep2Form');
    const inputs = form.querySelectorAll('input, select, textarea');
    let autoSaveTimeout;
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSave, 2000);
            updateTimeline();
        });
        
        input.addEventListener('change', function() {
            updateTimeline();
        });
    });
    
    // Initialize timeline
    updateTimeline();
    
    // Date validation for phases
    validatePhaseDates();
});

function togglePhase(phaseKey) {
    const card = document.querySelector(`[data-phase="${phaseKey}"]`);
    const checkbox = card.querySelector('input[type="checkbox"]');
    const content = card.querySelector('.phase-content');
    
    if (checkbox.checked) {
        card.classList.add('enabled');
        card.classList.remove('disabled');
        content.style.display = 'block';
    } else {
        card.classList.add('disabled');
        card.classList.remove('enabled');
        content.style.display = 'none';
    }
    
    updateTimeline();
}

function updateTimeline() {
    const timeline = document.getElementById('phaseTimeline');
    const enabledPhases = [];
    
    // Collect enabled phases with dates
    document.querySelectorAll('.phase-card.enabled').forEach(card => {
        const phaseKey = card.dataset.phase;
        const name = card.querySelector('input[name*="[name]"]').value;
        const startDate = card.querySelector('input[name*="[start_date]"]').value;
        const endDate = card.querySelector('input[name*="[end_date]"]').value;
        
        if (startDate && endDate) {
            enabledPhases.push({
                key: phaseKey,
                name: name || phaseKey.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
                startDate: startDate,
                endDate: endDate
            });
        }
    });
    
    // Sort phases by start date
    enabledPhases.sort((a, b) => new Date(a.startDate) - new Date(b.startDate));
    
    // Generate timeline HTML
    if (enabledPhases.length > 0) {
        let timelineHTML = '<div class="timeline-track">';
        
        enabledPhases.forEach((phase, index) => {
            const startDate = new Date(phase.startDate);
            const endDate = new Date(phase.endDate);
            const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            
            timelineHTML += `
                <div class="timeline-phase" data-phase="${phase.key}">
                    <div class="timeline-phase-header">
                        <span class="phase-number">${index + 1}</span>
                        <span class="phase-name">${phase.name}</span>
                    </div>
                    <div class="timeline-phase-dates">
                        <span class="start-date">${startDate.toLocaleDateString()}</span>
                        <span class="duration">${duration} days</span>
                        <span class="end-date">${endDate.toLocaleDateString()}</span>
                    </div>
                    <div class="timeline-phase-bar">
                        <div class="phase-bar" style="width: 100%"></div>
                    </div>
                </div>
            `;
        });
        
        timelineHTML += '</div>';
        timeline.innerHTML = timelineHTML;
    } else {
        timeline.innerHTML = '<div class="timeline-empty">No phases configured yet. Enable phases to see timeline.</div>';
    }
}

function validatePhaseDates() {
    // Add date validation logic
    const phaseCards = document.querySelectorAll('.phase-card');
    
    phaseCards.forEach(card => {
        const startInput = card.querySelector('input[name*="[start_date]"]');
        const endInput = card.querySelector('input[name*="[end_date]"]');
        
        if (startInput && endInput) {
            [startInput, endInput].forEach(input => {
                input.addEventListener('change', function() {
                    if (startInput.value && endInput.value) {
                        if (new Date(endInput.value) <= new Date(startInput.value)) {
                            endInput.setCustomValidity('End date must be after start date');
                        } else {
                            endInput.setCustomValidity('');
                        }
                    }
                });
            });
        }
    });
}

function autoSave() {
    const form = document.getElementById('wizardStep2Form');
    const formData = new FormData(form);
    const indicator = document.getElementById('autoSaveIndicator');
    
    indicator.style.display = 'flex';
    
    // Convert FormData to nested object structure
    const data = { phases: {} };
    
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('phases[')) {
            // Parse phase data structure
            const matches = key.match(/phases\[([^\]]+)\]\[([^\]]+)\](?:\[\])?/);
            if (matches) {
                const phaseKey = matches[1];
                const fieldName = matches[2];
                
                if (!data.phases[phaseKey]) {
                    data.phases[phaseKey] = {};
                }
                
                if (key.includes('[]')) {
                    // Handle array fields (like venue_requirements)
                    if (!data.phases[phaseKey][fieldName]) {
                        data.phases[phaseKey][fieldName] = [];
                    }
                    data.phases[phaseKey][fieldName].push(value);
                } else {
                    data.phases[phaseKey][fieldName] = value;
                }
            }
        }
    }
    
    fetch('<?= url('/admin/competition-setup/wizard/save-step') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            step: 2,
            step_data: data,
            auto_save: true
        })
    })
    .then(response => response.json())
    .then(result => {
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 1000);
        
        if (!result.success) {
            console.warn('Auto-save failed:', result.message);
        }
    })
    .catch(error => {
        console.error('Auto-save error:', error);
        indicator.style.display = 'none';
    });
}

function saveDraft() {
    const form = document.getElementById('wizardStep2Form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    showLoading();
    autoSave();
    
    setTimeout(() => {
        hideLoading();
        alert('Draft saved successfully!');
    }, 1000);
}

function nextStep() {
    const form = document.getElementById('wizardStep2Form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Check if at least one phase is enabled
    const enabledPhases = document.querySelectorAll('.phase-card.enabled').length;
    if (enabledPhases === 0) {
        alert('Please enable at least one phase before continuing.');
        return;
    }
    
    showLoading();
    autoSave();
    
    setTimeout(() => {
        hideLoading();
        window.location.href = '<?= url('/admin/competition-setup/wizard/step/3') ?>';
    }, 1000);
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>