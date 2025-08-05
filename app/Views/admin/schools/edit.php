<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- School Edit Form -->
<div class="school-edit-container">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($breadcrumb['name']) ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?= htmlspecialchars($breadcrumb['url']) ?>"><?= htmlspecialchars($breadcrumb['name']) ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <i class="fas fa-edit"></i>
                    Edit School - <?= htmlspecialchars($school['name']) ?>
                </h1>
                <p class="page-subtitle">Update school information and manage settings.</p>
            </div>
            <div class="col-auto">
                <a href="/admin/schools/<?= $school['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to School
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form id="schoolEditForm" class="school-form" method="POST" action="/admin/schools/<?= $school['id'] ?>" enctype="multipart/form-data">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= $this->csrf->generateToken() ?>">
        <input type="hidden" name="_method" value="PUT">

        <!-- Tabbed Interface -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="location-tab" data-toggle="tab" href="#location" role="tab">
                            <i class="fas fa-map-marker-alt"></i> Location & Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contacts-tab" data-toggle="tab" href="#contacts" role="tab">
                            <i class="fas fa-users"></i> Administrative Contacts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="facilities-tab" data-toggle="tab" href="#facilities" role="tab">
                            <i class="fas fa-building"></i> Facilities & Resources
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="admin-tab" data-toggle="tab" href="#admin" role="tab">
                            <i class="fas fa-cog"></i> Administrative Settings
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Basic Information Tab -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <h5><i class="fas fa-school text-primary"></i> Basic School Information</h5>
                        <div class="form-grid">
                            <div class="form-group col-span-2">
                                <label for="name" class="form-label required">School Name</label>
                                <input type="text" id="name" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($school['name']) ?>"
                                       minlength="5" maxlength="100">
                                <div class="form-help">Enter the complete official name as registered with the Department of Education</div>
                            </div>

                            <div class="form-group">
                                <label for="emis_number" class="form-label">EMIS Number</label>
                                <input type="text" id="emis_number" name="emis_number" class="form-control"
                                       value="<?= htmlspecialchars($school['emis_number'] ?? '') ?>"
                                       pattern="[0-9]{8,12}">
                                <div class="form-help">8-12 digit Education Management Information System number</div>
                            </div>

                            <div class="form-group">
                                <label for="registration_number" class="form-label required">Registration Number</label>
                                <input type="text" id="registration_number" name="registration_number" class="form-control" required
                                       value="<?= htmlspecialchars($school['registration_number']) ?>"
                                       pattern="[0-9]{8,12}">
                                <div class="form-help">Official Department of Education registration number</div>
                            </div>

                            <div class="form-group">
                                <label for="school_type" class="form-label required">School Type</label>
                                <select id="school_type" name="school_type" class="form-control" required>
                                    <option value="">Select school type</option>
                                    <?php if (isset($schoolTypes) && is_array($schoolTypes)): ?>
                                        <?php foreach ($schoolTypes as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $school['school_type'] === $key ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="quintile" class="form-label">School Quintile</label>
                                <select id="quintile" name="quintile" class="form-control">
                                    <option value="">Select quintile</option>
                                    <?php if (isset($quintiles) && is_array($quintiles)): ?>
                                        <?php foreach ($quintiles as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $school['quintile'] == $key ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="establishment_date" class="form-label">Establishment Date</label>
                                <input type="date" id="establishment_date" name="establishment_date" class="form-control"
                                       value="<?= htmlspecialchars($school['establishment_date'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="total_learners" class="form-label required">Total Number of Learners</label>
                                <input type="number" id="total_learners" name="total_learners" class="form-control" required
                                       value="<?= htmlspecialchars($school['total_learners']) ?>"
                                       min="50" max="5000">
                                <div class="form-help">Current total enrollment (minimum 50, maximum 5000)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Location & Contact Tab -->
                    <div class="tab-pane fade" id="location" role="tabpanel">
                        <h5><i class="fas fa-map-marker-alt text-success"></i> Location & Contact Information</h5>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="province" class="form-label required">Province</label>
                                <select id="province" name="province" class="form-control" required>
                                    <option value="">Select province</option>
                                    <?php if (isset($provinces) && is_array($provinces)): ?>
                                        <?php foreach ($provinces as $province): ?>
                                            <option value="<?= htmlspecialchars($province) ?>" <?= $school['province'] === $province ? 'selected' : '' ?>>
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
                                            <option value="<?= htmlspecialchars($district['id']) ?>" <?= $school['district_id'] == $district['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($district['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group col-span-2">
                                <label for="address_line1" class="form-label required">Street Address</label>
                                <input type="text" id="address_line1" name="address_line1" class="form-control" required
                                       value="<?= htmlspecialchars($school['address_line1']) ?>"
                                       minlength="20" maxlength="200">
                            </div>

                            <div class="form-group col-span-2">
                                <label for="address_line2" class="form-label">Address Line 2</label>
                                <input type="text" id="address_line2" name="address_line2" class="form-control"
                                       value="<?= htmlspecialchars($school['address_line2'] ?? '') ?>"
                                       maxlength="200">
                            </div>

                            <div class="form-group">
                                <label for="city" class="form-label required">City/Town</label>
                                <input type="text" id="city" name="city" class="form-control" required
                                       value="<?= htmlspecialchars($school['city']) ?>"
                                       maxlength="100">
                            </div>

                            <div class="form-group">
                                <label for="postal_code" class="form-label required">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" class="form-control" required
                                       value="<?= htmlspecialchars($school['postal_code']) ?>"
                                       pattern="[0-9]{4}">
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label required">Primary Phone</label>
                                <input type="tel" id="phone" name="phone" class="form-control" required
                                       value="<?= htmlspecialchars($school['phone']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="fax" class="form-label">Fax Number</label>
                                <input type="tel" id="fax" name="fax" class="form-control"
                                       value="<?= htmlspecialchars($school['fax'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label required">School Email</label>
                                <input type="email" id="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($school['email']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" id="website" name="website" class="form-control"
                                       value="<?= htmlspecialchars($school['website'] ?? '') ?>">
                            </div>

                            <div class="form-group col-span-2">
                                <label for="gps_coordinates" class="form-label">GPS Coordinates</label>
                                <input type="text" id="gps_coordinates" name="gps_coordinates" class="form-control"
                                       value="<?= htmlspecialchars($school['gps_coordinates'] ?? '') ?>"
                                       placeholder="e.g. -26.2041, 28.0473 (Latitude, Longitude)">
                                <div class="form-help">GPS coordinates for mapping and logistics</div>
                            </div>
                        </div>
                    </div>

                    <!-- Administrative Contacts Tab -->
                    <div class="tab-pane fade" id="contacts" role="tabpanel">
                        <h5><i class="fas fa-users text-info"></i> Administrative Contacts</h5>
                        
                        <div class="contact-section">
                            <h6><i class="fas fa-user-tie"></i> Principal Information</h6>
                            <div class="form-grid">
                                <div class="form-group col-span-2">
                                    <label for="principal_name" class="form-label required">Principal Name</label>
                                    <input type="text" id="principal_name" name="principal_name" class="form-control" required
                                           value="<?= htmlspecialchars($school['principal_name']) ?>"
                                           maxlength="100">
                                </div>

                                <div class="form-group">
                                    <label for="principal_email" class="form-label required">Principal Email</label>
                                    <input type="email" id="principal_email" name="principal_email" class="form-control" required
                                           value="<?= htmlspecialchars($school['principal_email']) ?>">
                                </div>

                                <div class="form-group">
                                    <label for="principal_phone" class="form-label">Principal Phone</label>
                                    <input type="tel" id="principal_phone" name="principal_phone" class="form-control"
                                           value="<?= htmlspecialchars($school['principal_phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="communication_preference" class="form-label">Preferred Communication Method</label>
                            <select id="communication_preference" name="communication_preference" class="form-control">
                                <?php if (isset($communicationPrefs) && is_array($communicationPrefs)): ?>
                                    <?php foreach ($communicationPrefs as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key) ?>" <?= $school['communication_preference'] === $key ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Contacts Management -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6><i class="fas fa-address-book"></i> Additional Contacts</h6>
                                <a href="/admin/contacts/create?school_id=<?= $school['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Add Contact
                                </a>
                            </div>

                            <?php if (!empty($contacts)): ?>
                                <div class="contacts-list">
                                    <?php foreach ($contacts as $contact): ?>
                                        <div class="contact-item card mb-2">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col">
                                                        <strong><?= htmlspecialchars($contact->getFullName()) ?></strong>
                                                        <span class="text-muted">(<?= htmlspecialchars($contact['position']) ?>)</span>
                                                        <?php if ($contact->isPrimary()): ?>
                                                            <span class="badge badge-primary badge-sm ml-1">Primary</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-auto">
                                                        <small class="text-muted"><?= htmlspecialchars($contact['email']) ?></small>
                                                    </div>
                                                    <div class="col-auto">
                                                        <a href="/admin/contacts/<?= $contact['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No additional contacts have been added yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Facilities & Resources Tab -->
                    <div class="tab-pane fade" id="facilities" role="tabpanel">
                        <h5><i class="fas fa-building text-warning"></i> Facilities & Resources</h5>
                        
                        <div class="form-grid">
                            <div class="form-group col-span-2">
                                <label for="facilities" class="form-label">General Facilities</label>
                                <textarea id="facilities" name="facilities" class="form-control" rows="3"><?= htmlspecialchars($school['facilities'] ?? '') ?></textarea>
                                <div class="form-help">List major facilities available at the school</div>
                            </div>

                            <div class="form-group col-span-2">
                                <label for="computer_lab" class="form-label">Computer Laboratory Details</label>
                                <textarea id="computer_lab" name="computer_lab" class="form-control" rows="2"><?= htmlspecialchars($school['computer_lab'] ?? '') ?></textarea>
                                <div class="form-help">Details about computing facilities and equipment</div>
                            </div>

                            <div class="form-group">
                                <label for="internet_status" class="form-label">Internet Connectivity</label>
                                <select id="internet_status" name="internet_status" class="form-control">
                                    <option value="">Select connectivity status</option>
                                    <option value="high_speed_fiber" <?= $school['internet_status'] === 'high_speed_fiber' ? 'selected' : '' ?>>High-speed Fiber</option>
                                    <option value="broadband" <?= $school['internet_status'] === 'broadband' ? 'selected' : '' ?>>Broadband (ADSL/Wireless)</option>
                                    <option value="limited" <?= $school['internet_status'] === 'limited' ? 'selected' : '' ?>>Limited/Slow Connection</option>
                                    <option value="none" <?= $school['internet_status'] === 'none' ? 'selected' : '' ?>>No Internet Access</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="accessibility_features" class="form-label">Accessibility Features</label>
                                <textarea id="accessibility_features" name="accessibility_features" class="form-control" rows="2"><?= htmlspecialchars($school['accessibility_features'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group col-span-2">
                                <label for="previous_participation" class="form-label">Previous Competition Participation</label>
                                <textarea id="previous_participation" name="previous_participation" class="form-control" rows="2"><?= htmlspecialchars($school['previous_participation'] ?? '') ?></textarea>
                                <div class="form-help">Information about past involvement in science competitions</div>
                            </div>
                        </div>
                    </div>

                    <!-- Administrative Settings Tab -->
                    <div class="tab-pane fade" id="admin" role="tabpanel">
                        <h5><i class="fas fa-cog text-danger"></i> Administrative Settings</h5>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="status" class="form-label required">School Status</label>
                                <select id="status" name="status" class="form-control" required>
                                    <?php if (isset($statuses) && is_array($statuses)): ?>
                                        <?php foreach ($statuses as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $school['status'] === $key ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-help">
                                    <strong>Status Meanings:</strong><br>
                                    • <strong>Pending:</strong> Awaiting approval<br>
                                    • <strong>Active:</strong> Can register teams and participate<br>
                                    • <strong>Inactive:</strong> Temporarily suspended<br>
                                    • <strong>Suspended:</strong> Disciplinary action<br>
                                    • <strong>Archived:</strong> Historical records only
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="coordinator_id" class="form-label">Assigned Coordinator</label>
                                <select id="coordinator_id" name="coordinator_id" class="form-control">
                                    <option value="">No coordinator assigned</option>
                                    <!-- This would be populated with available coordinators from the User model -->
                                </select>
                                <div class="form-help">Assign a SciBOTICS coordinator to this school</div>
                            </div>

                            <div class="form-group col-span-2">
                                <label for="notes" class="form-label">Administrative Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="4"><?= htmlspecialchars($school['notes'] ?? '') ?></textarea>
                                <div class="form-help">Internal notes for administrators (not visible to school users)</div>
                            </div>

                            <!-- Registration Information (Read-only) -->
                            <div class="form-group">
                                <label class="form-label">Registration Date</label>
                                <input type="text" class="form-control" readonly 
                                       value="<?= date('j F Y', strtotime($school['registration_date'])) ?>">
                            </div>

                            <?php if ($school['approval_date']): ?>
                            <div class="form-group">
                                <label class="form-label">Approval Date</label>
                                <input type="text" class="form-control" readonly 
                                       value="<?= date('j F Y', strtotime($school['approval_date'])) ?>">
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Danger Zone -->
                        <div class="danger-zone mt-5">
                            <h6 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h6>
                            <div class="alert alert-danger">
                                <p><strong>Warning:</strong> These actions cannot be undone.</p>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="archiveSchool(<?= $school['id'] ?>)">
                                        <i class="fas fa-archive"></i> Archive School
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteSchool(<?= $school['id'] ?>)">
                                        <i class="fas fa-trash"></i> Delete School
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <div class="row">
                <div class="col">
                    <a href="/admin/schools/<?= $school['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Change Tracking Modal -->
<div class="modal fade" id="changeTrackingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Changes</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>The following changes will be made:</p>
                <div id="changesList"></div>
                <div class="form-group">
                    <label for="changeReason">Reason for changes (optional):</label>
                    <textarea id="changeReason" class="form-control" rows="2" placeholder="Explain why these changes are being made..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmSave()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
// Track original form values for change detection
let originalValues = {};

document.addEventListener('DOMContentLoaded', function() {
    // Store original form values
    const form = document.getElementById('schoolEditForm');
    const formData = new FormData(form);
    for (let [key, value] of formData.entries()) {
        originalValues[key] = value;
    }
});

// Form submission with change tracking
document.getElementById('schoolEditForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Detect changes
    const changes = detectChanges();
    
    if (changes.length === 0) {
        alert('No changes were made.');
        return;
    }
    
    // Show change confirmation modal
    showChangeConfirmation(changes);
});

function detectChanges() {
    const form = document.getElementById('schoolEditForm');
    const formData = new FormData(form);
    const changes = [];
    
    for (let [key, value] of formData.entries()) {
        if (originalValues[key] !== value && key !== 'csrf_token' && key !== '_method') {
            const fieldLabel = document.querySelector(`label[for="${key}"]`)?.textContent?.replace('*', '').trim() || key;
            changes.push({
                field: fieldLabel,
                from: originalValues[key] || '(empty)',
                to: value || '(empty)'
            });
        }
    }
    
    return changes;
}

function showChangeConfirmation(changes) {
    const changesList = document.getElementById('changesList');
    changesList.innerHTML = '<ul class="list-group">' + 
        changes.map(change => 
            `<li class="list-group-item">
                <strong>${change.field}:</strong><br>
                <small class="text-muted">From:</small> ${change.from}<br>
                <small class="text-muted">To:</small> ${change.to}
            </li>`
        ).join('') + '</ul>';
    
    $('#changeTrackingModal').modal('show');
}

function confirmSave() {
    const reason = document.getElementById('changeReason').value;
    const form = document.getElementById('schoolEditForm');
    
    // Add reason to form data
    if (reason) {
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'change_reason';
        reasonInput.value = reason;
        form.appendChild(reasonInput);
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    // Submit form
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#changeTrackingModal').modal('hide');
            alert('School updated successfully!');
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            alert('Error: ' + (data.message || 'Update failed. Please try again.'));
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Danger zone functions
function archiveSchool(schoolId) {
    const reason = prompt('Please provide a reason for archiving this school:');
    if (reason) {
        if (confirm('Are you sure you want to archive this school? This action cannot be undone.')) {
            fetch(`/admin/schools/${schoolId}/archive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('School archived successfully.');
                    window.location.href = '/admin/schools';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    }
}

function deleteSchool(schoolId) {
    const confirmation = prompt('Type "DELETE" to confirm permanent deletion of this school:');
    if (confirmation === 'DELETE') {
        if (confirm('This will permanently delete the school and all associated data. Are you absolutely sure?')) {
            fetch(`/admin/schools/${schoolId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('School deleted successfully.');
                    window.location.href = '/admin/schools';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    }
}

// Auto-save draft functionality
let autosaveTimer;
function autosave() {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
        const formData = new FormData(document.getElementById('schoolEditForm'));
        localStorage.setItem('schoolEditDraft_<?= $school["id"] ?>', JSON.stringify(Object.fromEntries(formData)));
    }, 3000);
}

// Attach auto-save to form inputs
document.querySelectorAll('#schoolEditForm input, #schoolEditForm select, #schoolEditForm textarea').forEach(input => {
    input.addEventListener('input', autosave);
    input.addEventListener('change', autosave);
});
</script>

<style>
.school-edit-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.form-group.col-span-2 {
    grid-column: span 2;
}

.contact-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.contact-section h6 {
    color: #495057;
    margin-bottom: 15px;
}

.contacts-list {
    max-height: 300px;
    overflow-y: auto;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.danger-zone {
    border-top: 2px solid #dc3545;
    padding-top: 20px;
}

.form-help {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 5px;
}

.required::after {
    content: " *";
    color: #dc3545;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.col-span-2 {
        grid-column: span 1;
    }
}
</style>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>