<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-plus mr-2"></i>
                        Add New School Contact
                    </h3>
                    <?php if ($school): ?>
                    <div class="card-tools">
                        <span class="badge badge-info">
                            <i class="fas fa-school mr-1"></i>
                            <?php echo htmlspecialchars($school->name); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <form action="/GSCMS/admin/contacts" method="POST" id="contactForm" class="needs-validation" novalidate>
                    <div class="card-body">
                        
                        <?php if (!empty($schoolUsers)): ?>
                        <!-- Contact Creation Method Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h5 class="alert-heading">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Create Contact From Existing User or Manual Entry
                                    </h5>
                                    <p class="mb-3">You can either select an existing user from this school or create a new contact manually.</p>
                                    
                                    <div class="btn-group" id="contactMethodToggle">
                                        <button type="button" class="btn btn-primary active" id="btn_method_manual" data-method="manual" onclick="toggleContactMethod('manual')">
                                            <i class="fas fa-user-plus mr-2"></i>Manual Entry
                                        </button>
                                        <button type="button" class="btn btn-outline-success" id="btn_method_select" data-method="select" onclick="toggleContactMethod('select')">
                                            <i class="fas fa-user-check mr-2"></i>Select Existing User
                                        </button>
                                    </div>
                                    
                                    <!-- Hidden input to track method -->
                                    <input type="hidden" name="contact_method" id="contact_method" value="manual">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Existing User Selection -->
                        <div class="row mb-4" id="userSelectionSection" style="display: none;">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-users mr-2"></i>
                                            Select User from <?= htmlspecialchars($school->name ?? 'School') ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="existing_user_id">Available Users</label>
                                            <select name="existing_user_id" id="existing_user_id" class="form-control select2">
                                                <option value="">Select a user to create contact from...</option>
                                                <?php foreach ($schoolUsers as $user): ?>
                                                    <option value="<?= $user->id ?>" 
                                                            data-first-name="<?= htmlspecialchars($user->first_name) ?>"
                                                            data-last-name="<?= htmlspecialchars($user->last_name) ?>"
                                                            data-email="<?= htmlspecialchars($user->email) ?>"
                                                            data-phone="<?= htmlspecialchars($user->phone ?? '') ?>"
                                                            data-role="<?= htmlspecialchars($user->role) ?>">
                                                        <?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?> 
                                                        (<?= htmlspecialchars($user->email) ?>) 
                                                        - <?= htmlspecialchars(ucwords(str_replace('_', ' ', $user->role))) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                Selecting a user will automatically populate the form fields below with their information.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Manual Entry Form -->
                        <div id="manualEntrySection">
                        <div class="row">
                            <!-- School Selection -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="school_id" class="required">School</label>
                                    <select name="school_id" id="school_id" class="form-control select2" required>
                                        <option value="">Select a school...</option>
                                        <?php foreach ($schools as $schoolOption): ?>
                                            <option value="<?php echo $schoolOption->id; ?>" 
                                                    <?php echo ($selectedSchoolId == $schoolOption->id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($schoolOption->name . ' - ' . $schoolOption->district); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a school.</div>
                                </div>
                            </div>

                            <!-- Contact Type -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_type" class="required">Contact Type</label>
                                    <select name="contact_type" id="contact_type" class="form-control" required>
                                        <option value="">Select contact type...</option>
                                        <?php foreach ($contactTypes as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a contact type.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Title -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <select name="title" id="title" class="form-control">
                                        <option value="">None</option>
                                        <option value="Mr">Mr</option>
                                        <option value="Mrs">Mrs</option>
                                        <option value="Ms">Ms</option>
                                        <option value="Dr">Dr</option>
                                        <option value="Prof">Prof</option>
                                    </select>
                                </div>
                            </div>

                            <!-- First Name -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="first_name" class="required">First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" 
                                           maxlength="100" required>
                                    <div class="invalid-feedback">First name is required.</div>
                                </div>
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="last_name" class="required">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control" 
                                           maxlength="100" required>
                                    <div class="invalid-feedback">Last name is required.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Position -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="position" class="required">Position</label>
                                    <input type="text" name="position" id="position" class="form-control" 
                                           maxlength="100" placeholder="e.g., Principal, Science Head, etc." required>
                                    <div class="invalid-feedback">Position is required.</div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="required">Status</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <?php foreach ($statuses as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" 
                                                    <?php echo ($key === 'active') ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a status.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="required">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           maxlength="255" required>
                                    <div class="invalid-feedback">A valid email address is required.</div>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control" 
                                           maxlength="20" placeholder="011 123 4567">
                                </div>
                            </div>

                            <!-- Mobile -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="mobile">Mobile</label>
                                    <input type="text" name="mobile" id="mobile" class="form-control" 
                                           maxlength="20" placeholder="082 123 4567">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Language Preference -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="language_preference">Language Preference</label>
                                    <select name="language_preference" id="language_preference" class="form-control">
                                        <?php foreach ($languagePreferences as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" 
                                                    <?php echo ($key === 'english') ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Communication Preference -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="communication_preference">Communication Preference</label>
                                    <select name="communication_preference" id="communication_preference" class="form-control">
                                        <?php foreach ($communicationPreferences as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" 
                                                    <?php echo ($key === 'email') ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Address -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea name="address" id="address" class="form-control" rows="3" 
                                              placeholder="Physical or postal address for this contact"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Fax -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fax">Fax</label>
                                    <input type="text" name="fax" id="fax" class="form-control" 
                                           maxlength="20" placeholder="011 123 4568">
                                </div>
                            </div>

                            <!-- Checkboxes -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Contact Options</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_primary" id="is_primary" class="form-check-input" value="1">
                                        <label class="form-check-label" for="is_primary">
                                            Primary Contact for School
                                            <small class="text-muted">(Only one primary contact per school)</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_emergency" id="is_emergency" class="form-check-input" value="1">
                                        <label class="form-check-label" for="is_emergency">
                                            Emergency Contact
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Notes -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                                              placeholder="Additional notes or comments about this contact"></textarea>
                                </div>
                            </div>
                        </div>
                        </div> <!-- End Manual Entry Section -->
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Save Contact
                                </button>
                                <button type="button" class="btn btn-secondary ml-2" onclick="resetForm()">
                                    <i class="fas fa-redo mr-2"></i>Reset
                                </button>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="/GSCMS/admin/contacts" class="btn btn-outline-secondary">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                                <?php if ($school): ?>
                                <a href="/GSCMS/admin/schools/<?php echo $school->id; ?>" class="btn btn-outline-info ml-2">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to School
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.required::after {
    content: " *";
    color: red;
}
.form-group label.required {
    font-weight: 600;
}
.card-tools .badge {
    font-size: 0.9em;
}
</style>

<script>
// Inline function that works immediately without jQuery
function toggleContactMethod(method) {
    console.log('toggleContactMethod called with:', method);
    
    // Update button visual states
    const manualBtn = document.getElementById('btn_method_manual');
    const selectBtn = document.getElementById('btn_method_select');
    const hiddenInput = document.getElementById('contact_method');
    const userSection = document.getElementById('userSelectionSection');
    const manualSection = document.getElementById('manualEntrySection');
    
    // Reset button classes
    manualBtn.className = 'btn btn-outline-primary';
    selectBtn.className = 'btn btn-outline-success';
    
    // Update hidden input
    if (hiddenInput) hiddenInput.value = method;
    
    if (method === 'select') {
        selectBtn.className = 'btn btn-success active';
        if (userSection) {
            userSection.style.display = 'block';
            console.log('Showing user selection section');
        }
        if (manualSection) {
            manualSection.style.display = 'none';
            console.log('Hiding manual entry section');
        }
    } else {
        manualBtn.className = 'btn btn-primary active';
        if (userSection) {
            userSection.style.display = 'none';
            console.log('Hiding user selection section');
        }
        if (manualSection) {
            manualSection.style.display = 'block';
            console.log('Showing manual entry section');
        }
    }
}

// Test function for debugging
function testToggle() {
    console.log('Testing toggle...');
    const userSection = document.getElementById('userSelectionSection');
    if (userSection) {
        userSection.style.display = userSection.style.display === 'none' ? 'block' : 'none';
        console.log('User section visibility:', userSection.style.display);
    } else {
        console.log('User section not found!');
    }
}

$(document).ready(function() {
    // Initialize Select2 for dropdowns
    $('#school_id').select2({
        placeholder: 'Select a school...',
        allowClear: true,
        width: '100%'
    });
    
    $('#existing_user_id').select2({
        placeholder: 'Select a user to create contact from...',
        allowClear: true,
        width: '100%'
    });
    
    // Handle contact method toggle
    $('#contactMethodToggle button').click(function() {
        const method = $(this).data('method');
        const $buttons = $('#contactMethodToggle button');
        
        // Update button states
        $buttons.removeClass('btn-primary btn-success active').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary btn-outline-success').addClass(method === 'select' ? 'btn-success' : 'btn-primary').addClass('active');
        
        // Update hidden input
        $('#contact_method').val(method);
        
        console.log('Contact method changed to:', method); // Debug log
        
        if (method === 'select') {
            console.log('Showing user selection section'); // Debug log
            $('#userSelectionSection').slideDown(300);
            $('#manualEntrySection').slideUp(300);
            
            // Make user selection required
            $('#existing_user_id').prop('required', true);
            
            // Make manual fields optional
            $('#school_id, #contact_type, #first_name, #last_name, #position, #email, #status').prop('required', false);
            
        } else {
            console.log('Showing manual entry section'); // Debug log
            $('#userSelectionSection').slideUp(300);
            $('#manualEntrySection').slideDown(300);
            
            // Make user selection optional
            $('#existing_user_id').prop('required', false);
            
            // Make manual fields required
            $('#school_id, #contact_type, #first_name, #last_name, #position, #email, #status').prop('required', true);
            
            // Clear user selection
            $('#existing_user_id').val('').trigger('change');
            clearFormFields();
        }
    });
    
    // Debug: Check if sections exist and jQuery is working
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('User selection section exists:', $('#userSelectionSection').length > 0);
    console.log('Manual entry section exists:', $('#manualEntrySection').length > 0);
    console.log('Toggle buttons exist:', $('#contactMethodToggle button').length);
    
    // Add debug buttons (remove these in production)
    if ($('#userSelectionSection').length > 0) {
        const debugBtn = $('<button type="button" class="btn btn-info btn-sm ml-2" onclick="testToggle()">Debug: Test Toggle</button>');
        const directBtn = $('<button type="button" class="btn btn-warning btn-sm ml-1" onclick="toggleContactMethod(\'select\')">Direct: Show Users</button>');
        $('#contactMethodToggle').after(directBtn).after(debugBtn);
    }
    
    // Immediate DOM check
    console.log('DOM elements check:');
    console.log('- Manual button exists:', document.getElementById('btn_method_manual') !== null);
    console.log('- Select button exists:', document.getElementById('btn_method_select') !== null);
    console.log('- User section exists:', document.getElementById('userSelectionSection') !== null);
    console.log('- Manual section exists:', document.getElementById('manualEntrySection') !== null);
    
    // Handle user selection
    $('#existing_user_id').change(function() {
        const selectedOption = $(this).find(':selected');
        
        if (selectedOption.val()) {
            // Auto-populate form fields from selected user
            const userData = {
                first_name: selectedOption.data('first-name'),
                last_name: selectedOption.data('last-name'),
                email: selectedOption.data('email'),
                phone: selectedOption.data('phone'),
                role: selectedOption.data('role')
            };
            
            populateFormFromUser(userData);
            
            // Show populated fields for review
            $('#manualEntrySection').slideDown();
            
        } else {
            clearFormFields();
        }
    });
    
    function populateFormFromUser(userData) {
        // Populate basic fields
        $('#first_name').val(userData.first_name);
        $('#last_name').val(userData.last_name);
        $('#email').val(userData.email);
        $('#phone').val(userData.phone || '');
        
        // Set position based on role
        const rolePositionMap = {
            'super_admin': 'System Administrator',
            'competition_admin': 'Competition Administrator', 
            'school_coordinator': 'School Coordinator',
            'team_coach': 'Team Coach',
            'judge': 'Judge',
            'participant': 'Participant'
        };
        
        $('#position').val(rolePositionMap[userData.role] || userData.role.replace('_', ' '));
        
        // Set contact type based on role
        const roleContactTypeMap = {
            'super_admin': 'administrative',
            'competition_admin': 'administrative',
            'school_coordinator': 'coordinator',
            'team_coach': 'coordinator',
            'judge': 'other',
            'participant': 'other'
        };
        
        $('#contact_type').val(roleContactTypeMap[userData.role] || 'other');
        
        // Set default status
        $('#status').val('active');
    }
    
    function clearFormFields() {
        $('#first_name, #last_name, #email, #phone, #position, #notes').val('');
        $('#contact_type').val('');
        $('#title').val('');
        $('#mobile, #fax, #address').val('');
        $('#is_primary, #is_emergency').prop('checked', false);
        $('#language_preference').val('english');
        $('#communication_preference').val('email');
        $('#status').val('active');
    }
    
    // School selection change handler for loading users
    $('#school_id').change(function() {
        const schoolId = $(this).val();
        const currentMethod = $('#contact_method').val();
        
        if (schoolId && currentMethod === 'select') {
            // Reload page with new school to get users
            window.location.href = '/GSCMS/admin/contacts/create?school_id=' + schoolId;
        }
    });

    // Form validation
    $('#contactForm').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Auto-populate contact type based on position
    $('#position').on('blur', function() {
        const position = $(this).val().toLowerCase();
        if (!$('#contact_type').val()) {
            if (position.includes('principal')) {
                $('#contact_type').val('principal');
            } else if (position.includes('coordinator') || position.includes('scibotics')) {
                $('#contact_type').val('coordinator');
            } else if (position.includes('deputy')) {
                $('#contact_type').val('deputy');
            } else if (position.includes('admin')) {
                $('#contact_type').val('administrative');
            } else if (position.includes('it')) {
                $('#contact_type').val('it_coordinator');
            }
        }
    });

    // Primary contact warning
    $('#is_primary').on('change', function() {
        if ($(this).is(':checked')) {
            if (confirm('Setting this as the primary contact will remove the primary flag from any existing primary contact for this school. Continue?')) {
                // Keep checked
            } else {
                $(this).prop('checked', false);
            }
        }
    });

    // Phone number formatting
    $('#phone, #mobile, #fax').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 3) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
        }
        $(this).val(value);
    });
});

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('contactForm').reset();
        $('#contactForm').removeClass('was-validated');
        $('#school_id').val(null).trigger('change');
    }
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>