<?php
$layout = 'public';
ob_start();
?>

<div class="judge-registration-container">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">
                            <i class="fas fa-user-tie"></i>
                            GDE SciBOTICS Judge Registration
                        </h2>
                        <p class="mb-0 mt-2">Join our panel of expert judges for the GDE SciBOTICS Competition</p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Progress Indicator -->
                        <div class="progress mb-4">
                            <div class="progress-bar" role="progressbar" style="width: 33%">
                                Step 1: Basic Information
                            </div>
                        </div>
                        
                        <form id="judge-registration-form" enctype="multipart/form-data">
                            <!-- Personal Information Section -->
                            <div class="registration-section">
                                <h4 class="section-title">
                                    <i class="fas fa-user"></i>
                                    Personal Information
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="first_name" class="required">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="last_name" class="required">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="required">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                            <small class="form-text text-muted">This will be your login email</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="required">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password" class="required">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <small class="form-text text-muted">Minimum 8 characters</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_confirmation" class="required">Confirm Password</label>
                                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Judge Information Section -->
                            <div class="registration-section">
                                <h4 class="section-title">
                                    <i class="fas fa-gavel"></i>
                                    Judge Information
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="judge_type" class="required">Judge Type</label>
                                            <select class="form-control" id="judge_type" name="judge_type" required>
                                                <option value="">Select Judge Type</option>
                                                <?php foreach ($judge_types as $key => $label): ?>
                                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                <strong>Coordinator:</strong> GDE C&R school-level selection<br>
                                                <strong>Adjudicator:</strong> Professional finals judging<br>
                                                <strong>Technical:</strong> Technical evaluation expert<br>
                                                <strong>Volunteer:</strong> Community volunteer judge<br>
                                                <strong>Industry:</strong> Industry professional
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="experience_level" class="required">Experience Level</label>
                                            <select class="form-control" id="experience_level" name="experience_level" required>
                                                <option value="">Select Experience Level</option>
                                                <?php foreach ($experience_levels as $key => $label): ?>
                                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="years_experience">Years of Experience</label>
                                            <input type="number" class="form-control" id="years_experience" name="years_experience" min="0" max="50">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="professional_title">Professional Title</label>
                                            <input type="text" class="form-control" id="professional_title" name="professional_title" placeholder="e.g., Software Engineer, Teacher, etc.">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="organization_id">Organization (Optional)</label>
                                    <select class="form-control" id="organization_id" name="organization_id">
                                        <option value="">Independent Judge</option>
                                        <?php foreach ($organizations as $org): ?>
                                            <option value="<?= htmlspecialchars($org['id']) ?>">
                                                <?= htmlspecialchars($org['organization_name']) ?> (<?= htmlspecialchars($org['organization_type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Expertise Section -->
                            <div class="registration-section">
                                <h4 class="section-title">
                                    <i class="fas fa-star"></i>
                                    Expertise & Qualifications
                                </h4>
                                
                                <div class="form-group">
                                    <label>Competition Categories (Select all that apply)</label>
                                    <div class="row">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" 
                                                           id="category_<?= $category['id'] ?>" 
                                                           name="categories_qualified[]" 
                                                           value="<?= $category['id'] ?>">
                                                    <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                                        <?= htmlspecialchars($category['category_name']) ?>
                                                    </label>
                                                    <?php if (!empty($category['description'])): ?>
                                                        <small class="form-text text-muted"><?= htmlspecialchars($category['description']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Expertise Areas (Select all that apply)</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_robotics" name="expertise_areas[]" value="robotics">
                                                <label class="form-check-label" for="exp_robotics">Robotics</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_programming" name="expertise_areas[]" value="programming">
                                                <label class="form-check-label" for="exp_programming">Programming</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_electronics" name="expertise_areas[]" value="electronics">
                                                <label class="form-check-label" for="exp_electronics">Electronics</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_education" name="expertise_areas[]" value="education">
                                                <label class="form-check-label" for="exp_education">Education</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_engineering" name="expertise_areas[]" value="engineering">
                                                <label class="form-check-label" for="exp_engineering">Engineering</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_research" name="expertise_areas[]" value="research">
                                                <label class="form-check-label" for="exp_research">Research</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_innovation" name="expertise_areas[]" value="innovation">
                                                <label class="form-check-label" for="exp_innovation">Innovation</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_business" name="expertise_areas[]" value="business">
                                                <label class="form-check-label" for="exp_business">Business</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="exp_other" name="expertise_areas[]" value="other">
                                                <label class="form-check-label" for="exp_other">Other</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="professional_bio">Professional Bio</label>
                                    <textarea class="form-control" id="professional_bio" name="professional_bio" rows="4" 
                                              placeholder="Brief description of your professional background and expertise..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Availability Section -->
                            <div class="registration-section">
                                <h4 class="section-title">
                                    <i class="fas fa-calendar"></i>
                                    Availability & Preferences
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="max_assignments_per_day">Maximum Assignments Per Day</label>
                                            <select class="form-control" id="max_assignments_per_day" name="max_assignments_per_day">
                                                <option value="5">5 assignments</option>
                                                <option value="10" selected>10 assignments</option>
                                                <option value="15">15 assignments</option>
                                                <option value="20">20 assignments</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="languages_spoken">Languages Spoken</label>
                                            <input type="text" class="form-control" id="languages_spoken" name="languages_spoken" 
                                                   value="English" placeholder="e.g., English, Afrikaans, Zulu">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="availability_notes">Availability Notes</label>
                                    <textarea class="form-control" id="availability_notes" name="availability_notes" rows="3"
                                              placeholder="Any specific availability constraints, preferred times, or notes..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_contact">Emergency Contact</label>
                                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                                           placeholder="Name and phone number">
                                </div>
                            </div>
                            
                            <!-- Document Upload Section -->
                            <div class="registration-section">
                                <h4 class="section-title">
                                    <i class="fas fa-file-upload"></i>
                                    Documents (Optional - can be uploaded later)
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cv">CV/Resume (PDF, DOC, DOCX)</label>
                                            <input type="file" class="form-control-file" id="cv" name="cv" 
                                                   accept=".pdf,.doc,.docx">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_document">ID Document (PDF, JPG, PNG)</label>
                                            <input type="file" class="form-control-file" id="id_document" name="id_document" 
                                                   accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="qualifications">Qualifications/Certificates (PDF)</label>
                                            <input type="file" class="form-control-file" id="qualifications" name="qualifications" 
                                                   accept=".pdf">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="police_clearance">Police Clearance (PDF)</label>
                                            <input type="file" class="form-control-file" id="police_clearance" name="police_clearance" 
                                                   accept=".pdf">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="registration-section">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="terms_accepted" name="terms_accepted" required>
                                    <label class="form-check-label" for="terms_accepted">
                                        I agree to the <a href="#" target="_blank">Terms and Conditions</a> and 
                                        <a href="#" target="_blank">Judge Code of Conduct</a>
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="data_consent" name="data_consent" required>
                                    <label class="form-check-label" for="data_consent">
                                        I consent to the processing of my personal data for the purpose of judge registration and assignment
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                    <i class="fas fa-user-plus"></i>
                                    Register as Judge
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Registration Success Modal -->
<div class="modal fade" id="success-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h4 class="modal-title">
                    <i class="fas fa-check-circle"></i>
                    Registration Successful!
                </h4>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-user-check fa-4x text-success mb-3"></i>
                    <h5>Welcome to the GDE SciBOTICS Judge Panel!</h5>
                    <p class="lead">Your judge registration has been submitted successfully.</p>
                    
                    <div class="alert alert-info">
                        <strong>Judge Code:</strong> <span id="judge-code-display"></span>
                    </div>
                    
                    <h6>Next Steps:</h6>
                    <ul class="list-unstyled text-left">
                        <li><i class="fas fa-envelope text-primary"></i> Check your email for verification instructions</li>
                        <li><i class="fas fa-tasks text-primary"></i> Complete your onboarding checklist</li>
                        <li><i class="fas fa-upload text-primary"></i> Upload any remaining documents</li>
                        <li><i class="fas fa-graduation-cap text-primary"></i> Complete required training modules</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="onboarding-link" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    Go to Onboarding Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.registration-section {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    background-color: #f8f9fa;
}

.section-title {
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.section-title i {
    margin-right: 10px;
    color: #007bff;
}

.required::after {
    content: " *";
    color: #dc3545;
}

.form-check {
    margin-bottom: 10px;
}

.progress {
    height: 25px;
}

.progress-bar {
    font-size: 14px;
    line-height: 25px;
}

#submit-btn {
    min-width: 200px;
}

.judge-registration-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('judge-registration-form');
    const submitBtn = document.getElementById('submit-btn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
        
        // Create FormData object to handle file uploads
        const formData = new FormData(form);
        
        fetch('/judge/register', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                document.getElementById('judge-code-display').textContent = data.judge_code;
                document.getElementById('onboarding-link').href = data.redirect;
                $('#success-modal').modal('show');
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        })
        .catch(error => {
            alert('Registration failed: ' + error.message);
            console.error('Error:', error);
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Register as Judge';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>