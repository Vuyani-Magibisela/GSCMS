<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Wizard Progress Header -->
<div class="wizard-header">
    <div class="wizard-progress">
        <div class="progress-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-label">Basic Information</div>
            </div>
            <div class="step">
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
            <div class="progress-fill" style="width: 16.67%"></div>
        </div>
    </div>
</div>

<!-- Step Content -->
<div class="wizard-content">
    <div class="step-header">
        <h2 class="step-title"><?= htmlspecialchars($step_title) ?></h2>
        <p class="step-description"><?= htmlspecialchars($step_description) ?></p>
    </div>

    <form id="wizardStep1Form" class="wizard-form">
        <input type="hidden" name="step" value="1">
        
        <div class="form-grid">
            <!-- Left Column -->
            <div class="form-column">
                <div class="form-section">
                    <h3 class="section-title">Competition Details</h3>
                    
                    <!-- Competition Name -->
                    <div class="form-group">
                        <label for="name" class="form-label required">Competition Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?= htmlspecialchars($wizard_data['step_1']['name'] ?? '') ?>" 
                               placeholder="e.g., GDE SciBOTICS Championship 2025" required>
                        <small class="form-help">Enter a descriptive name for this competition</small>
                    </div>
                    
                    <!-- Competition Year -->
                    <div class="form-group">
                        <label for="year" class="form-label required">Competition Year</label>
                        <select id="year" name="year" class="form-control" required>
                            <?php for ($y = $current_year; $y <= $current_year + 2; $y++): ?>
                                <option value="<?= $y ?>" <?= ($wizard_data['step_1']['year'] ?? $current_year) == $y ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <!-- Competition Type -->
                    <div class="form-group">
                        <label for="type" class="form-label required">Competition Type</label>
                        <select id="type" name="type" class="form-control" required onchange="updateTypeDescription()">
                            <option value="">Select competition type...</option>
                            <?php foreach ($competition_types as $type => $label): ?>
                                <option value="<?= htmlspecialchars($type) ?>" 
                                        <?= ($wizard_data['step_1']['type'] ?? '') === $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="typeDescription" class="form-help mt-2"></div>
                    </div>
                    
                    <!-- Geographic Scope -->
                    <div class="form-group">
                        <label for="geographic_scope" class="form-label required">Geographic Scope</label>
                        <select id="geographic_scope" name="geographic_scope" class="form-control" required>
                            <option value="">Select scope...</option>
                            <?php foreach ($geographic_scopes as $scope => $label): ?>
                                <option value="<?= htmlspecialchars($scope) ?>" 
                                        <?= ($wizard_data['step_1']['geographic_scope'] ?? '') === $scope ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="form-column">
                <div class="form-section">
                    <h3 class="section-title">Timeline & Dates</h3>
                    
                    <!-- Competition Start Date -->
                    <div class="form-group">
                        <label for="start_date" class="form-label required">Competition Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" 
                               value="<?= htmlspecialchars($wizard_data['step_1']['start_date'] ?? '') ?>" required>
                        <small class="form-help">First day of competition activities</small>
                    </div>
                    
                    <!-- Competition End Date -->
                    <div class="form-group">
                        <label for="end_date" class="form-label required">Competition End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" 
                               value="<?= htmlspecialchars($wizard_data['step_1']['end_date'] ?? '') ?>" required>
                        <small class="form-help">Final day of competition activities</small>
                    </div>
                    
                    <!-- Registration Opening -->
                    <div class="form-group">
                        <label for="registration_opening" class="form-label">Registration Opening</label>
                        <input type="date" id="registration_opening" name="registration_opening" class="form-control" 
                               value="<?= htmlspecialchars($wizard_data['step_1']['registration_opening'] ?? '') ?>">
                        <small class="form-help">When team registration becomes available</small>
                    </div>
                    
                    <!-- Registration Closing -->
                    <div class="form-group">
                        <label for="registration_closing" class="form-label">Registration Deadline</label>
                        <input type="date" id="registration_closing" name="registration_closing" class="form-control" 
                               value="<?= htmlspecialchars($wizard_data['step_1']['registration_closing'] ?? '') ?>">
                        <small class="form-help">Final deadline for team registrations</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">Contact Information</h3>
                    
                    <!-- Contact Email -->
                    <div class="form-group">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control" 
                               value="<?= htmlspecialchars($wizard_data['step_1']['contact_email'] ?? '') ?>" 
                               placeholder="competition@gde.gov.za">
                        <small class="form-help">Primary contact for competition inquiries</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Description Section -->
        <div class="form-section full-width">
            <h3 class="section-title">Competition Description</h3>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" 
                          placeholder="Provide a detailed description of this competition..."><?= htmlspecialchars($wizard_data['step_1']['description'] ?? '') ?></textarea>
                <small class="form-help">This description will be visible to schools and participants</small>
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
        <a href="<?= url('/admin/competition-setup/wizard') ?>" class="btn btn-outline">
            <i class="fas fa-times"></i> Cancel
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
// Form validation and navigation
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save functionality
    const form = document.getElementById('wizardStep1Form');
    const inputs = form.querySelectorAll('input, select, textarea');
    let autoSaveTimeout;
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSave, 2000); // Auto-save after 2 seconds of inactivity
        });
    });
    
    // Date validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const regOpening = document.getElementById('registration_opening');
    const regClosing = document.getElementById('registration_closing');
    
    function validateDates() {
        if (startDate.value && endDate.value) {
            if (new Date(endDate.value) <= new Date(startDate.value)) {
                endDate.setCustomValidity('End date must be after start date');
                return false;
            } else {
                endDate.setCustomValidity('');
            }
        }
        
        if (regOpening.value && regClosing.value) {
            if (new Date(regClosing.value) <= new Date(regOpening.value)) {
                regClosing.setCustomValidity('Registration deadline must be after opening date');
                return false;
            } else {
                regClosing.setCustomValidity('');
            }
        }
        
        return true;
    }
    
    [startDate, endDate, regOpening, regClosing].forEach(input => {
        input.addEventListener('change', validateDates);
    });
    
    // Initialize type description
    updateTypeDescription();
});

function updateTypeDescription() {
    const typeSelect = document.getElementById('type');
    const descriptionDiv = document.getElementById('typeDescription');
    
    const descriptions = {
        'pilot': 'A smaller-scale competition for testing new formats and procedures. Typically involves fewer schools and simplified phases.',
        'full': 'A comprehensive competition with all phases including school-based eliminations, district semifinals, and provincial finals.',
        'special': 'A special purpose competition with custom rules and requirements. Used for unique events or specific educational objectives.'
    };
    
    const selectedType = typeSelect.value;
    if (selectedType && descriptions[selectedType]) {
        descriptionDiv.innerHTML = '<i class="fas fa-info-circle text-info"></i> ' + descriptions[selectedType];
        descriptionDiv.style.display = 'block';
    } else {
        descriptionDiv.style.display = 'none';
    }
}

function autoSave() {
    const form = document.getElementById('wizardStep1Form');
    const formData = new FormData(form);
    const indicator = document.getElementById('autoSaveIndicator');
    
    indicator.style.display = 'flex';
    
    // Convert FormData to object
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    fetch('<?= url('/admin/competition-setup/wizard/save-step') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            step: 1,
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
    const form = document.getElementById('wizardStep1Form');
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
    const form = document.getElementById('wizardStep1Form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    showLoading();
    
    fetch('<?= url('/admin/competition-setup/wizard/save-step') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            step: 1,
            step_data: data
        })
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        
        if (result.success) {
            window.location.href = '<?= url('/admin/competition-setup/wizard/step/2') ?>';
        } else {
            alert('Error saving step: ' + (result.message || 'Unknown error'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Network error occurred. Please try again.');
    });
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>