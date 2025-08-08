<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<div class="team-creation-container">
    <!-- Progress Indicator -->
    <div class="progress-steps mb-4">
        <div class="step active" data-step="1">
            <div class="step-circle">1</div>
            <span>Team Information</span>
        </div>
        <div class="step" data-step="2">
            <div class="step-circle">2</div>
            <span>Category Selection</span>
        </div>
        <div class="step" data-step="3">
            <div class="step-circle">3</div>
            <span>Coach Assignment</span>
        </div>
        <div class="step" data-step="4">
            <div class="step-circle">4</div>
            <span>Additional Details</span>
        </div>
        <div class="step" data-step="5">
            <div class="step-circle">5</div>
            <span>Review & Submit</span>
        </div>
    </div>

    <form id="teamCreationForm" method="POST" action="<?= $baseUrl ?>/admin/teams" enctype="multipart/form-data">
        <!-- Step 1: Basic Team Information -->
        <div class="form-step active" data-step="1">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Basic Team Information</h3>
                    <p>Enter the fundamental details about the team</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="school_id" class="form-label required">School</label>
                                <select id="school_id" name="school_id" class="form-control" required>
                                    <option value="">Select school</option>
                                    <?php foreach ($schools as $school): ?>
                                        <option value="<?= $school['id'] ?>" 
                                                data-district="<?= htmlspecialchars($school['district']) ?>">
                                            <?= htmlspecialchars($school['name']) ?> - <?= htmlspecialchars($school['district']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-help">Select the school this team will represent</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label required">Team Name</label>
                                <input type="text" id="name" name="name" class="form-control" required
                                       placeholder="e.g. Robo Eagles">
                                <div class="form-help">Choose a unique team name for your school</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Category Selection -->
        <div class="form-step" data-step="2">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Competition Category</h3>
                    <p>Select the competition category based on participant grades</p>
                </div>
                <div class="card-body">
                    <div class="category-selection">
                        <?php foreach ($categories as $category): ?>
                            <div class="category-option" data-category-id="<?= $category['id'] ?>">
                                <input type="radio" id="category_<?= $category['id'] ?>" 
                                       name="category_id" value="<?= $category['id'] ?>" required>
                                <label for="category_<?= $category['id'] ?>" class="category-card">
                                    <div class="category-header">
                                        <h4><?= htmlspecialchars($category['name']) ?></h4>
                                        <span class="badge"><?= htmlspecialchars($category['grade_range']) ?></span>
                                    </div>
                                    <div class="category-details">
                                        <p><strong>Max Participants:</strong> <?= $category['max_team_size'] ?></p>
                                        
                                        <?php if (!empty($category['subdivisions'])): ?>
                                            <div class="subdivisions">
                                                <h5>Category Options:</h5>
                                                <?php foreach ($category['subdivisions'] as $subdivision): ?>
                                                    <div class="subdivision">
                                                        <strong><?= htmlspecialchars($subdivision['name']) ?></strong>
                                                        - Grades <?= $subdivision['min_grade'] == 0 ? 'R' : $subdivision['min_grade'] ?>
                                                        to <?= $subdivision['max_grade'] ?>
                                                        (Max <?= $subdivision['max_participants'] ?> participants)
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Category-specific information display -->
                    <div id="categoryInfo" class="category-info mt-3" style="display: none;">
                        <div class="alert alert-info">
                            <h5 id="selectedCategoryName"></h5>
                            <p id="selectedCategoryDescription"></p>
                            <div id="selectedSubdivisions"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Coach Assignment -->
        <div class="form-step" data-step="3">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Coach Assignment</h3>
                    <p>Assign primary and secondary coaches to the team</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="coach1_id" class="form-label required">Primary Coach</label>
                                <select id="coach1_id" name="coach1_id" class="form-control" required>
                                    <option value="">Select primary coach</option>
                                    <?php foreach ($coaches as $coach): ?>
                                        <option value="<?= $coach['id'] ?>">
                                            <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?>
                                            (<?= htmlspecialchars($coach['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-help">Primary coach is responsible for team coordination</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="coach2_id" class="form-label">Secondary Coach (Optional)</label>
                                <select id="coach2_id" name="coach2_id" class="form-control">
                                    <option value="">Select secondary coach</option>
                                    <?php foreach ($coaches as $coach): ?>
                                        <option value="<?= $coach['id'] ?>">
                                            <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?>
                                            (<?= htmlspecialchars($coach['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-help">Optional secondary coach for additional support</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i>
                        <strong>Coach Requirements:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Maximum 2 coaches per team</li>
                            <li>Each coach can manage maximum 2 teams</li>
                            <li>Primary coach is required for team registration</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Additional Details -->
        <div class="form-step" data-step="4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-robot"></i> Robot & Additional Details</h3>
                    <p>Provide information about the robot and special requirements</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="robot_name" class="form-label">Robot Name</label>
                                <input type="text" id="robot_name" name="robot_name" class="form-control"
                                       placeholder="e.g. RoboHero">
                                <div class="form-help">Name your team's robot (optional)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="programming_language" class="form-label">Programming Language</label>
                                <select id="programming_language" name="programming_language" class="form-control">
                                    <option value="">Select language</option>
                                    <option value="Scratch">Scratch</option>
                                    <option value="Python">Python</option>
                                    <option value="C++">C++</option>
                                    <option value="Arduino IDE">Arduino IDE</option>
                                    <option value="LEGO Mindstorms">LEGO Mindstorms</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="robot_description" class="form-label">Robot Description</label>
                        <textarea id="robot_description" name="robot_description" class="form-control" rows="3"
                                  placeholder="Describe your robot's design, features, and capabilities..."></textarea>
                        <div class="form-help">Describe your robot's design and intended capabilities</div>
                    </div>

                    <div class="form-group">
                        <label for="special_requirements" class="form-label">Special Requirements</label>
                        <textarea id="special_requirements" name="special_requirements" class="form-control" rows="3"
                                  placeholder="Any special accommodations, dietary requirements, accessibility needs..."></textarea>
                        <div class="form-help">List any special accommodations or requirements</div>
                    </div>

                    <h5 class="mt-4">Emergency Contact Information</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emergency_contact_name" class="form-label required">Emergency Contact Name</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                                       class="form-control" required placeholder="Full name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emergency_contact_phone" class="form-label required">Emergency Contact Phone</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                                       class="form-control" required placeholder="Contact number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                <select id="emergency_contact_relationship" name="emergency_contact_relationship" class="form-control">
                                    <option value="">Select relationship</option>
                                    <option value="parent">Parent</option>
                                    <option value="guardian">Guardian</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="principal">Principal</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Review & Submit -->
        <div class="form-step" data-step="5">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle"></i> Review & Submit</h3>
                    <p>Review all information before submitting the team registration</p>
                </div>
                <div class="card-body">
                    <div id="reviewContent">
                        <!-- Review content will be populated by JavaScript -->
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Next Steps:</strong>
                        <ul class="mb-0 mt-2">
                            <li>After submission, the team will be created with "Registered" status</li>
                            <li>You can add participants to the team after creation</li>
                            <li>All participants must meet grade requirements for the selected category</li>
                            <li>Consent forms and documentation will be required for each participant</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="form-navigation mt-4">
            <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            
            <div class="ml-auto">
                <button type="button" id="nextBtn" class="btn btn-primary">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
                
                <button type="submit" id="submitBtn" class="btn btn-success" style="display: none;">
                    <i class="fas fa-save"></i> Create Team
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.team-creation-container {
    max-width: 1000px;
    margin: 0 auto;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 60%;
    width: 80%;
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.step.active .step-circle {
    background: #007bff;
    color: white;
}

.step.completed .step-circle {
    background: #28a745;
    color: white;
}

.step.completed::after {
    background: #28a745;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    z-index: 2;
    position: relative;
}

.step span {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    text-align: center;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

.category-option {
    margin-bottom: 1rem;
}

.category-card {
    display: block;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-card:hover {
    border-color: #007bff;
    background: #f8f9fa;
}

.category-option input[type="radio"]:checked + .category-card {
    border-color: #007bff;
    background: #e3f2fd;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.category-header h4 {
    margin: 0;
    color: #333;
}

.category-header .badge {
    background: #6c757d;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

.subdivisions {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #dee2e6;
}

.subdivision {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.form-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.form-help {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.required::after {
    content: " *";
    color: #dc3545;
}
</style>

<script>
let currentStep = 1;
const totalSteps = 5;
const categories = <?= json_encode($categories) ?>;

document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupEventListeners();
});

function initializeForm() {
    showStep(1);
    updateNavigationButtons();
}

function setupEventListeners() {
    // Navigation buttons
    document.getElementById('nextBtn').addEventListener('click', nextStep);
    document.getElementById('prevBtn').addEventListener('click', prevStep);
    
    // Category selection
    document.querySelectorAll('input[name="category_id"]').forEach(radio => {
        radio.addEventListener('change', updateCategoryInfo);
    });
    
    // Form submission
    document.getElementById('teamCreationForm').addEventListener('submit', handleSubmit);
    
    // Coach validation
    document.getElementById('coach1_id').addEventListener('change', validateCoachSelection);
    document.getElementById('coach2_id').addEventListener('change', validateCoachSelection);
}

function nextStep() {
    if (validateStep(currentStep)) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateNavigationButtons();
            
            if (currentStep === totalSteps) {
                updateReviewContent();
            }
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateNavigationButtons();
    }
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(stepDiv => {
        stepDiv.classList.remove('active');
    });
    
    // Show current step
    document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
    
    // Update progress indicators
    document.querySelectorAll('.step').forEach((stepIndicator, index) => {
        stepIndicator.classList.remove('active', 'completed');
        if (index + 1 === step) {
            stepIndicator.classList.add('active');
        } else if (index + 1 < step) {
            stepIndicator.classList.add('completed');
        }
    });
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Previous button
    prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
    
    // Next/Submit buttons
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'block';
    } else {
        nextBtn.style.display = 'block';
        submitBtn.style.display = 'none';
    }
}

function validateStep(step) {
    let isValid = true;
    const currentStepDiv = document.querySelector(`.form-step[data-step="${step}"]`);
    const requiredFields = currentStepDiv.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Additional step-specific validation
    switch (step) {
        case 2: // Category selection
            if (!document.querySelector('input[name="category_id"]:checked')) {
                alert('Please select a competition category.');
                isValid = false;
            }
            break;
        case 3: // Coach assignment
            if (!validateCoaches()) {
                isValid = false;
            }
            break;
    }
    
    if (!isValid) {
        alert('Please complete all required fields before proceeding.');
    }
    
    return isValid;
}

function updateCategoryInfo() {
    const selectedCategoryId = document.querySelector('input[name="category_id"]:checked')?.value;
    const categoryInfo = document.getElementById('categoryInfo');
    
    if (selectedCategoryId) {
        const category = categories.find(c => c.id == selectedCategoryId);
        
        if (category) {
            document.getElementById('selectedCategoryName').textContent = category.name;
            document.getElementById('selectedCategoryDescription').textContent = 
                `Grade Range: ${category.grade_range} | Max Team Size: ${category.max_team_size}`;
            
            // Show subdivisions if they exist
            const subdivisionsDiv = document.getElementById('selectedSubdivisions');
            if (category.subdivisions && category.subdivisions.length > 0) {
                let subdivisionsHtml = '<h6>Available Options:</h6><ul>';
                category.subdivisions.forEach(sub => {
                    const gradeRange = `Grade ${sub.min_grade === 0 ? 'R' : sub.min_grade} - ${sub.max_grade}`;
                    subdivisionsHtml += `<li><strong>${sub.name}</strong> (${gradeRange}, Max ${sub.max_participants} participants)</li>`;
                });
                subdivisionsHtml += '</ul>';
                subdivisionsDiv.innerHTML = subdivisionsHtml;
            } else {
                subdivisionsDiv.innerHTML = '';
            }
            
            categoryInfo.style.display = 'block';
        }
    } else {
        categoryInfo.style.display = 'none';
    }
}

function validateCoaches() {
    const coach1 = document.getElementById('coach1_id').value;
    const coach2 = document.getElementById('coach2_id').value;
    
    if (coach1 && coach2 && coach1 === coach2) {
        alert('Primary and secondary coaches must be different people.');
        return false;
    }
    
    return true;
}

function validateCoachSelection() {
    validateCoaches();
}

function updateReviewContent() {
    const reviewContent = document.getElementById('reviewContent');
    
    // Gather form data
    const formData = {
        school: document.querySelector('#school_id option:checked')?.textContent || 'Not selected',
        teamName: document.getElementById('name').value,
        category: document.querySelector('input[name="category_id"]:checked') ? 
                  document.querySelector(`label[for="category_${document.querySelector('input[name="category_id"]:checked').value}"] h4`).textContent : 'Not selected',
        primaryCoach: document.querySelector('#coach1_id option:checked')?.textContent || 'Not selected',
        secondaryCoach: document.querySelector('#coach2_id option:checked')?.textContent || 'None selected',
        robotName: document.getElementById('robot_name').value || 'Not specified',
        programmingLanguage: document.getElementById('programming_language').value || 'Not specified',
        emergencyContact: document.getElementById('emergency_contact_name').value || 'Not provided'
    };
    
    reviewContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Team Information</h5>
                <table class="table table-borderless">
                    <tr><td><strong>School:</strong></td><td>${formData.school}</td></tr>
                    <tr><td><strong>Team Name:</strong></td><td>${formData.teamName}</td></tr>
                    <tr><td><strong>Category:</strong></td><td>${formData.category}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Coaching Staff</h5>
                <table class="table table-borderless">
                    <tr><td><strong>Primary Coach:</strong></td><td>${formData.primaryCoach}</td></tr>
                    <tr><td><strong>Secondary Coach:</strong></td><td>${formData.secondaryCoach}</td></tr>
                    <tr><td><strong>Emergency Contact:</strong></td><td>${formData.emergencyContact}</td></tr>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h5>Robot Details</h5>
                <table class="table table-borderless">
                    <tr><td><strong>Robot Name:</strong></td><td>${formData.robotName}</td></tr>
                    <tr><td><strong>Programming Language:</strong></td><td>${formData.programmingLanguage}</td></tr>
                </table>
            </div>
        </div>
    `;
}

function handleSubmit(e) {
    e.preventDefault();
    
    if (!validateStep(currentStep)) {
        return;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Team...';
    submitBtn.disabled = true;
    
    // Submit form data
    const formData = new FormData(e.target);
    
    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = '<?= $baseUrl ?>/admin/teams';
            }
        } else {
            alert('Error: ' + (data.message || 'Team creation failed. Please try again.'));
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the team. Please try again.');
        
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php';
?>