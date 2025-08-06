<?php 
$layout = 'layouts/admin';
$pageCSS = ['/css/school-form.css'];
ob_start(); 
?>


<!-- School Registration Form - Multi-Step Wizard -->
<div class="school-registration-container">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($breadcrumb['title']) ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?= htmlspecialchars($breadcrumb['url']) ?>"><?= htmlspecialchars($breadcrumb['title']) ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-school"></i>
            Register New School
        </h1>
        <p class="page-subtitle">Complete all sections to register a new school in the SciBOTICS competition system.</p>
    </div>

    <!-- Progress Indicator -->
    <div class="registration-progress">
        <div class="progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Basic Information</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Location & Contact</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Administrative Contacts</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Facilities & Resources</div>
            </div>
            <div class="step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-label">Review & Submit</div>
            </div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: 20%"></div>
        </div>
    </div>

    <!-- Registration Form -->
    <form id="schoolRegistrationForm" class="school-form" method="POST" action="/admin/schools" enctype="multipart/form-data">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">

        <!-- Step 1: Basic Information -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h3><i class="fas fa-info-circle"></i> Basic School Information</h3>
                <p>Provide the fundamental information about the school.</p>
            </div>

            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label for="name" class="form-label required">School Name</label>
                    <input type="text" id="name" name="name" class="form-control" required
                           placeholder="Enter the full official name of the school"
                           minlength="5" maxlength="100">
                    <div class="form-help">Enter the complete official name as registered with the Department of Education</div>
                </div>

                <div class="form-group">
                    <label for="emis_number" class="form-label">EMIS Number</label>
                    <input type="text" id="emis_number" name="emis_number" class="form-control"
                           placeholder="e.g. 900123456" pattern="[0-9]{8,12}">
                    <div class="form-help">8-12 digit Education Management Information System number (if available)</div>
                </div>

                <div class="form-group">
                    <label for="registration_number" class="form-label required">Registration Number</label>
                    <input type="text" id="registration_number" name="registration_number" class="form-control" required
                           placeholder="e.g. 900123456789" pattern="[0-9]{8,12}">
                    <div class="form-help">Official Department of Education registration number</div>
                </div>

                <div class="form-group">
                    <label for="school_type" class="form-label required">School Type</label>
                    <select id="school_type" name="school_type" class="form-control" required>
                        <option value="">Select school type</option>
                        <?php if (isset($schoolTypes) && is_array($schoolTypes)): ?>
                            <?php foreach ($schoolTypes as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-help">Select the primary education level offered</div>
                </div>

                <div class="form-group">
                    <label for="quintile" class="form-label">School Quintile</label>
                    <select id="quintile" name="quintile" class="form-control">
                        <option value="">Select quintile (if known)</option>
                        <?php if (isset($quintiles) && is_array($quintiles)): ?>
                            <?php foreach ($quintiles as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-help">Economic classification based on school fees policy</div>
                </div>

                <div class="form-group">
                    <label for="establishment_date" class="form-label">Establishment Date</label>
                    <input type="date" id="establishment_date" name="establishment_date" class="form-control">
                    <div class="form-help">When was the school established?</div>
                </div>

                <div class="form-group">
                    <label for="total_learners" class="form-label required">Total Number of Learners</label>
                    <input type="number" id="total_learners" name="total_learners" class="form-control" required
                           min="50" max="5000" placeholder="e.g. 450">
                    <div class="form-help">Current total enrollment (minimum 50, maximum 5000)</div>
                </div>
            </div>
        </div>

        <!-- Step 2: Location & Contact -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h3><i class="fas fa-map-marker-alt"></i> Location & Contact Information</h3>
                <p>Provide the school's physical location and contact details.</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="province" class="form-label required">Province</label>
                    <select id="province" name="province" class="form-control" required>
                        <option value="">Select province</option>
                        <?php if (isset($provinces) && is_array($provinces)): ?>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?= htmlspecialchars($province) ?>" <?= $province === 'Gauteng' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($province) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="district_id" class="form-label required">District</label>
                    <select id="district_id" name="district_id" class="form-control" required>
                        <option value="">Select district</option>
                        <?php if (isset($districts) && is_array($districts)): ?>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= htmlspecialchars($district['id']) ?>">
                                    <?= htmlspecialchars($district['name']) ?> (<?= htmlspecialchars($district['province']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No districts available</option>
                            <!-- Debug: <?= isset($districts) ? 'Districts is set but not array: ' . gettype($districts) : 'Districts not set' ?> -->
                        <?php endif; ?>
                    </select>
                    <div class="form-help">Select the education district</div>
                </div>

                <div class="form-group col-span-2">
                    <label for="address_line1" class="form-label required">Street Address</label>
                    <input type="text" id="address_line1" name="address_line1" class="form-control" required
                           placeholder="Enter street address" minlength="20" maxlength="200">
                    <div class="form-help">Complete street address including street number and name</div>
                </div>

                <div class="form-group col-span-2">
                    <label for="address_line2" class="form-label">Address Line 2</label>
                    <input type="text" id="address_line2" name="address_line2" class="form-control"
                           placeholder="Suburb, area, or additional address information" maxlength="200">
                </div>

                <div class="form-group">
                    <label for="city" class="form-label required">City/Town</label>
                    <input type="text" id="city" name="city" class="form-control" required
                           placeholder="e.g. Johannesburg" maxlength="100">
                </div>

                <div class="form-group">
                    <label for="postal_code" class="form-label required">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control" required
                           placeholder="e.g. 2001" pattern="[0-9]{4}">
                    <div class="form-help">4-digit postal code</div>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label required">Primary Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required
                           placeholder="e.g. 011 123 4567">
                    <div class="form-help">Main school telephone number</div>
                </div>

                <div class="form-group">
                    <label for="fax" class="form-label">Fax Number</label>
                    <input type="tel" id="fax" name="fax" class="form-control"
                           placeholder="e.g. 011 123 4568">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label required">School Email</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           placeholder="e.g. info@schoolname.edu.za">
                    <div class="form-help">Official school email address</div>
                </div>

                <div class="form-group">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" id="website" name="website" class="form-control"
                           placeholder="e.g. https://www.schoolname.edu.za">
                </div>

                <div class="form-group col-span-2">
                    <label for="gps_coordinates" class="form-label">GPS Coordinates</label>
                    <input type="text" id="gps_coordinates" name="gps_coordinates" class="form-control"
                           placeholder="e.g. -26.2041, 28.0473 (Latitude, Longitude)">
                    <div class="form-help">GPS coordinates for mapping and logistics (optional)</div>
                </div>
            </div>
        </div>

        <!-- Step 3: Administrative Contacts -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h3><i class="fas fa-users"></i> Administrative Contacts</h3>
                <p>Provide contact information for key school personnel.</p>
            </div>

            <div class="contact-section">
                <h4><i class="fas fa-user-tie"></i> Principal Information</h4>
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label for="principal_name" class="form-label required">Principal Name</label>
                        <input type="text" id="principal_name" name="principal_name" class="form-control" required
                               placeholder="e.g. Dr. Jane Smith" maxlength="100">
                        <div class="form-help">Full name including title</div>
                    </div>

                    <div class="form-group">
                        <label for="principal_email" class="form-label required">Principal Email</label>
                        <input type="email" id="principal_email" name="principal_email" class="form-control" required
                               placeholder="principal@schoolname.edu.za">
                        <div class="form-help">Must be different from school email</div>
                    </div>

                    <div class="form-group">
                        <label for="principal_phone" class="form-label">Principal Phone</label>
                        <input type="tel" id="principal_phone" name="principal_phone" class="form-control"
                               placeholder="e.g. 082 123 4567">
                    </div>
                </div>
            </div>

            <div class="contact-section">
                <h4><i class="fas fa-user-graduate"></i> SciBOTICS Coordinator (Optional)</h4>
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label for="coordinator_name" class="form-label">Coordinator Name</label>
                        <input type="text" id="coordinator_name" name="coordinator_name" class="form-control"
                               placeholder="e.g. Mr. John Doe" maxlength="100">
                        <div class="form-help">Designated SciBOTICS coordinator (can be assigned later)</div>
                    </div>

                    <div class="form-group">
                        <label for="coordinator_email" class="form-label">Coordinator Email</label>
                        <input type="email" id="coordinator_email" name="coordinator_email" class="form-control"
                               placeholder="coordinator@schoolname.edu.za">
                    </div>

                    <div class="form-group">
                        <label for="coordinator_phone" class="form-label">Coordinator Phone</label>
                        <input type="tel" id="coordinator_phone" name="coordinator_phone" class="form-control"
                               placeholder="e.g. 082 987 6543">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="communication_preference" class="form-label">Preferred Communication Method</label>
                <select id="communication_preference" name="communication_preference" class="form-control">
                    <?php if (isset($communicationPrefs) && is_array($communicationPrefs)): ?>
                        <?php foreach ($communicationPrefs as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === 'email' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Step 4: Facilities & Resources -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h3><i class="fas fa-building"></i> Facilities & Resources</h3>
                <p>Information about school facilities and available resources.</p>
            </div>

            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label for="facilities" class="form-label">General Facilities</label>
                    <textarea id="facilities" name="facilities" class="form-control" rows="3"
                              placeholder="Describe general school facilities (classrooms, hall, sports facilities, etc.)"></textarea>
                    <div class="form-help">List major facilities available at the school</div>
                </div>

                <div class="form-group col-span-2">
                    <label for="computer_lab" class="form-label">Computer Laboratory Details</label>
                    <textarea id="computer_lab" name="computer_lab" class="form-control" rows="2"
                              placeholder="Describe computer lab facilities (number of computers, specifications, etc.)"></textarea>
                    <div class="form-help">Details about computing facilities and equipment</div>
                </div>

                <div class="form-group">
                    <label for="internet_status" class="form-label">Internet Connectivity</label>
                    <select id="internet_status" name="internet_status" class="form-control">
                        <option value="">Select connectivity status</option>
                        <option value="high_speed_fiber">High-speed Fiber</option>
                        <option value="broadband">Broadband (ADSL/Wireless)</option>
                        <option value="limited">Limited/Slow Connection</option>
                        <option value="none">No Internet Access</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="accessibility_features" class="form-label">Accessibility Features</label>
                    <textarea id="accessibility_features" name="accessibility_features" class="form-control" rows="2"
                              placeholder="Describe accessibility features for learners with disabilities"></textarea>
                </div>

                <div class="form-group col-span-2">
                    <label for="previous_participation" class="form-label">Previous Competition Participation</label>
                    <textarea id="previous_participation" name="previous_participation" class="form-control" rows="2"
                              placeholder="Has the school participated in science competitions before? Which ones?"></textarea>
                    <div class="form-help">Information about past involvement in science competitions</div>
                </div>
            </div>
        </div>

        <!-- Step 5: Review & Submit -->
        <div class="form-step" data-step="5">
            <div class="step-header">
                <h3><i class="fas fa-check-circle"></i> Review & Submit</h3>
                <p>Please review all information before submitting the registration.</p>
            </div>

            <div class="review-section">
                <div class="review-card">
                    <h4>Basic Information</h4>
                    <div class="review-content" id="review-basic"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="goToStep(1)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>

                <div class="review-card">
                    <h4>Location & Contact</h4>
                    <div class="review-content" id="review-location"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="goToStep(2)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>

                <div class="review-card">
                    <h4>Administrative Contacts</h4>
                    <div class="review-content" id="review-contacts"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="goToStep(3)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>

                <div class="review-card">
                    <h4>Facilities & Resources</h4>
                    <div class="review-content" id="review-facilities"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="goToStep(4)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" id="terms_agreement" name="terms_agreement" required>
                    <span class="checkmark"></span>
                    I confirm that all information provided is accurate and complete. I understand that this school registration is subject to approval by the SciBOTICS competition administrators.
                </label>
            </div>
        </div>

        <!-- Form Navigation -->
        <div class="form-navigation">
            <button type="button" id="prevBtn" class="btn btn-secondary" onclick="changeStep(-1)" disabled>
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            
            <div class="step-info">
                Step <span id="currentStep">1</span> of 5
            </div>
            
            <button type="button" id="nextBtn" class="btn btn-primary" onclick="changeStep(1)">
                Next <i class="fas fa-arrow-right"></i>
            </button>
            
            <button type="submit" id="submitBtn" class="btn btn-success" style="display: none;">
                <i class="fas fa-paper-plane"></i> Submit Registration
            </button>
        </div>
    </form>
</div>

<!-- Auto-save notification -->
<div id="autosaveNotification" class="autosave-notification">
    <i class="fas fa-save"></i> Draft saved automatically
</div>

<script>
// Multi-step form functionality
let currentStepIndex = 1;
const totalSteps = 5;

function showStep(stepIndex) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Show current step
    const targetStep = document.querySelector(`.form-step[data-step="${stepIndex}"]`);
    if (targetStep) {
        targetStep.classList.add('active');
    } else {
        console.error('Target step not found:', stepIndex);
    }
    
    // Update progress indicator
    document.querySelectorAll('.progress-steps .step').forEach((step, index) => {
        if (index + 1 <= stepIndex) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Update progress bar
    const progressWidth = (stepIndex / totalSteps) * 100;
    document.querySelector('.progress-fill').style.width = progressWidth + '%';
    
    // Update step info
    document.getElementById('currentStep').textContent = stepIndex;
    
    // Handle navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.disabled = stepIndex === 1;
    
    if (stepIndex === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
        updateReviewSection();
    } else {
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }
}

function changeStep(direction) {
    const newStep = currentStepIndex + direction;
    
    if (newStep >= 1 && newStep <= totalSteps) {
        if (direction > 0 && !validateCurrentStep()) {
            return;
        }
        
        currentStepIndex = newStep;
        showStep(currentStepIndex);
    }
}

function goToStep(stepIndex) {
    if (stepIndex >= 1 && stepIndex <= totalSteps) {
        currentStepIndex = stepIndex;
        showStep(currentStepIndex);
    }
}

function validateCurrentStep() {
    const currentStep = document.querySelector(`.form-step[data-step="${currentStepIndex}"]`);
    if (!currentStep) {
        console.error('Current step not found:', currentStepIndex);
        return false;
    }
    
    const requiredFields = currentStep.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields before proceeding.');
    }
    
    return isValid;
}

function updateReviewSection() {
    // Update basic information review
    const basicReview = document.getElementById('review-basic');
    basicReview.innerHTML = `
        <p><strong>School Name:</strong> ${document.getElementById('name').value}</p>
        <p><strong>Registration Number:</strong> ${document.getElementById('registration_number').value}</p>
        <p><strong>School Type:</strong> ${document.getElementById('school_type').selectedOptions[0]?.text || 'Not selected'}</p>
        <p><strong>Total Learners:</strong> ${document.getElementById('total_learners').value}</p>
    `;
    
    // Update location review
    const locationReview = document.getElementById('review-location');
    locationReview.innerHTML = `
        <p><strong>Address:</strong> ${document.getElementById('address_line1').value}, ${document.getElementById('city').value}</p>
        <p><strong>Province:</strong> ${document.getElementById('province').value}</p>
        <p><strong>Email:</strong> ${document.getElementById('email').value}</p>
        <p><strong>Phone:</strong> ${document.getElementById('phone').value}</p>
    `;
    
    // Update contacts review
    const contactsReview = document.getElementById('review-contacts');
    contactsReview.innerHTML = `
        <p><strong>Principal:</strong> ${document.getElementById('principal_name').value}</p>
        <p><strong>Principal Email:</strong> ${document.getElementById('principal_email').value}</p>
        <p><strong>Coordinator:</strong> ${document.getElementById('coordinator_name').value || 'Not specified'}</p>
    `;
    
    // Update facilities review
    const facilitiesReview = document.getElementById('review-facilities');
    facilitiesReview.innerHTML = `
        <p><strong>Computer Lab:</strong> ${document.getElementById('computer_lab').value || 'Not specified'}</p>
        <p><strong>Internet Status:</strong> ${document.getElementById('internet_status').selectedOptions[0]?.text || 'Not specified'}</p>
    `;
}

// Auto-save functionality
let autosaveTimer;
function autosave() {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
        const formData = new FormData(document.getElementById('schoolRegistrationForm'));
        // In a real implementation, you would save to local storage or send to server
        localStorage.setItem('schoolRegistrationDraft', JSON.stringify(Object.fromEntries(formData)));
        
        // Show notification
        const notification = document.getElementById('autosaveNotification');
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
        }, 2000);
    }, 5000);
}

// Attach auto-save to form inputs
document.addEventListener('DOMContentLoaded', function() {
    const formInputs = document.querySelectorAll('#schoolRegistrationForm input, #schoolRegistrationForm select, #schoolRegistrationForm textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', autosave);
        input.addEventListener('change', autosave);
    });
    
    // Load draft if available
    const draft = localStorage.getItem('schoolRegistrationDraft');
    if (draft) {
        const draftData = JSON.parse(draft);
        Object.keys(draftData).forEach(key => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = draftData[key];
            }
        });
    }
});

// Form submission
document.getElementById('schoolRegistrationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!document.getElementById('terms_agreement').checked) {
        alert('Please agree to the terms and conditions.');
        return;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Submit form
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            localStorage.removeItem('schoolRegistrationDraft');
            alert('School registration submitted successfully! You will be redirected to the school details page.');
            window.location.href = data.redirect;
        } else {
            console.error('Server error:', data);
            alert('Error: ' + (data.message || 'Registration failed. Please try again.'));
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again. Check the browser console for details.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Initialize form when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    showStep(1);
});
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>