<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competition-wizard-step4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($page_title ?? 'Competition Setup Wizard') ?></h1>
            <p class="text-muted">Step <?= $step ?> of 6: <?= htmlspecialchars($step_title ?? 'Registration Rules') ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competition-setup') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($step / 6) * 100 ?>%"></div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <small class="text-muted">Step <?= $step ?> of 6</small>
            <small class="text-muted"><?= round(($step / 6) * 100) ?>% Complete</small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus"></i> <?= htmlspecialchars($step_title ?? 'Registration Rules') ?>
                    </h5>
                    <p class="card-text small mb-0 mt-2"><?= htmlspecialchars($step_description ?? 'Configure registration timeline and requirements') ?></p>
                </div>
                <div class="card-body">
                    <form id="wizardStepForm" method="POST" action="<?= url('/admin/competition-setup/wizard/save-step') ?>">
                        <input type="hidden" name="step" value="<?= $step ?>">

                        <!-- Registration Timeline -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-calendar-alt"></i> Registration Timeline
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="registration_start">Registration Start Date <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="registration_start" name="registration_start" required>
                                        <small class="form-text text-muted">When registration opens to the public</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="registration_end">Registration End Date <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="registration_end" name="registration_end" required>
                                        <small class="form-text text-muted">Final deadline for registration</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="early_registration_end">Early Registration End (Optional)</label>
                                        <input type="datetime-local" class="form-control" id="early_registration_end" name="early_registration_end">
                                        <small class="form-text text-muted">Deadline for early bird registration</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="late_registration_end">Late Registration End (Optional)</label>
                                        <input type="datetime-local" class="form-control" id="late_registration_end" name="late_registration_end">
                                        <small class="form-text text-muted">Extended deadline with penalty</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Limits -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-users"></i> Registration Limits
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_teams_total">Maximum Total Teams</label>
                                        <input type="number" class="form-control" id="max_teams_total" name="max_teams_total" min="1" placeholder="Leave blank for unlimited">
                                        <small class="form-text text-muted">Total teams across all categories</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_teams_per_school">Maximum Teams per School</label>
                                        <input type="number" class="form-control" id="max_teams_per_school" name="max_teams_per_school" value="3" min="1" max="10">
                                        <small class="form-text text-muted">Teams one school can register</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_participants_per_team">Maximum Participants per Team</label>
                                        <input type="number" class="form-control" id="max_participants_per_team" name="max_participants_per_team" value="4" min="2" max="8">
                                        <small class="form-text text-muted">Maximum team size</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="min_participants_per_team">Minimum Participants per Team</label>
                                        <input type="number" class="form-control" id="min_participants_per_team" name="min_participants_per_team" value="2" min="1" max="4">
                                        <small class="form-text text-muted">Minimum team size</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Requirements -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-clipboard-check"></i> Registration Requirements
                            </h6>
                            <div class="form-group">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="require_consent_forms" name="registration_requirements[]" value="consent_forms">
                                    <label class="form-check-label" for="require_consent_forms">
                                        <strong>Consent Forms Required</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">Teams must upload signed consent forms for all participants</small>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="require_coach_details" name="registration_requirements[]" value="coach_details">
                                    <label class="form-check-label" for="require_coach_details">
                                        <strong>Team Coach Details Required</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">Teams must provide qualified coach information</small>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="require_school_approval" name="registration_requirements[]" value="school_approval">
                                    <label class="form-check-label" for="require_school_approval">
                                        <strong>School Coordinator Approval</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">School coordinator must approve team registrations</small>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="require_payment" name="registration_requirements[]" value="payment">
                                    <label class="form-check-label" for="require_payment">
                                        <strong>Registration Fee Payment</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">Teams must pay registration fee before confirmation</small>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Fees -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-money-bill-wave"></i> Registration Fees (Optional)
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="early_registration_fee">Early Registration Fee (ZAR)</label>
                                        <input type="number" class="form-control" id="early_registration_fee" name="early_registration_fee" min="0" step="0.01" placeholder="0.00">
                                        <small class="form-text text-muted">Fee for early bird registration</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="regular_registration_fee">Regular Registration Fee (ZAR)</label>
                                        <input type="number" class="form-control" id="regular_registration_fee" name="regular_registration_fee" min="0" step="0.01" placeholder="0.00">
                                        <small class="form-text text-muted">Standard registration fee</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="late_registration_fee">Late Registration Fee (ZAR)</label>
                                        <input type="number" class="form-control" id="late_registration_fee" name="late_registration_fee" min="0" step="0.01" placeholder="0.00">
                                        <small class="form-text text-muted">Fee for late registration</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_methods">Accepted Payment Methods</label>
                                        <select class="form-control" id="payment_methods" name="payment_methods[]" multiple>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="credit_card">Credit Card</option>
                                            <option value="payfast">PayFast</option>
                                            <option value="school_account">School Account</option>
                                        </select>
                                        <small class="form-text text-muted">Hold Ctrl to select multiple options</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Communication Settings -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-envelope"></i> Communication Settings
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirmation_email_template">Registration Confirmation Email</label>
                                        <select class="form-control" id="confirmation_email_template" name="confirmation_email_template">
                                            <option value="default">Default Template</option>
                                            <option value="pilot_2025">Pilot Programme 2025</option>
                                            <option value="championship">Championship Template</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="send_reminder_emails" name="send_reminder_emails" value="1" checked>
                                            <label class="form-check-label" for="send_reminder_emails">
                                                Send registration deadline reminders
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('/admin/competition-setup/wizard/step/3') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Previous
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Help Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle"></i> Registration Rules Help
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Timeline:</strong> Set when registration opens and closes. Consider early bird and late registration periods.</p>
                        <p><strong>Limits:</strong> Control the number of teams and participants to manage venue capacity.</p>
                        <p><strong>Requirements:</strong> Select what documentation and approvals are needed.</p>
                        <p><strong>Fees:</strong> Set up tiered pricing for different registration periods.</p>
                    </div>
                </div>
            </div>

            <!-- Wizard Progress -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list-ol"></i> Wizard Steps
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 1. Basic Information
                        </div>
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 2. Phase Configuration
                        </div>
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 3. Category Setup
                        </div>
                        <div class="mb-2 text-primary">
                            <i class="fas fa-arrow-right"></i> <strong>4. Registration Rules</strong>
                        </div>
                        <div class="mb-2 text-muted">
                            <i class="fas fa-circle"></i> 5. Competition Rules
                        </div>
                        <div class="mb-2 text-muted">
                            <i class="fas fa-circle"></i> 6. Review & Deploy
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Timeline Preview -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-clock"></i> Timeline Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="timelinePreview" class="small text-muted">
                        Select dates to see timeline preview
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizardStepForm');
    const regStart = document.getElementById('registration_start');
    const regEnd = document.getElementById('registration_end');
    const earlyEnd = document.getElementById('early_registration_end');
    const lateEnd = document.getElementById('late_registration_end');
    const minParticipants = document.getElementById('min_participants_per_team');
    const maxParticipants = document.getElementById('max_participants_per_team');

    // Set minimum date to today
    const today = new Date().toISOString().slice(0, 16);
    regStart.min = today;

    // Update date dependencies
    regStart.addEventListener('change', function() {
        regEnd.min = this.value;
        earlyEnd.min = this.value;
        updateTimelinePreview();
    });

    regEnd.addEventListener('change', function() {
        lateEnd.min = this.value;
        updateTimelinePreview();
    });

    earlyEnd.addEventListener('change', updateTimelinePreview);
    lateEnd.addEventListener('change', updateTimelinePreview);

    // Validate participant limits
    minParticipants.addEventListener('change', function() {
        maxParticipants.min = this.value;
    });

    maxParticipants.addEventListener('change', function() {
        minParticipants.max = this.value;
    });

    function updateTimelinePreview() {
        const preview = document.getElementById('timelinePreview');
        let timeline = '';

        if (regStart.value) {
            timeline += `<div class="mb-1"><i class="fas fa-play-circle text-success"></i> Registration Opens: ${formatDateTime(regStart.value)}</div>`;
        }

        if (earlyEnd.value) {
            timeline += `<div class="mb-1"><i class="fas fa-clock text-warning"></i> Early Bird Ends: ${formatDateTime(earlyEnd.value)}</div>`;
        }

        if (regEnd.value) {
            timeline += `<div class="mb-1"><i class="fas fa-stop-circle text-danger"></i> Registration Closes: ${formatDateTime(regEnd.value)}</div>`;
        }

        if (lateEnd.value) {
            timeline += `<div class="mb-1"><i class="fas fa-exclamation-triangle text-danger"></i> Late Registration Ends: ${formatDateTime(lateEnd.value)}</div>`;
        }

        preview.innerHTML = timeline || 'Select dates to see timeline preview';
    }

    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validation
        if (!regStart.value || !regEnd.value) {
            alert('Please set both registration start and end dates.');
            return;
        }

        if (new Date(regStart.value) >= new Date(regEnd.value)) {
            alert('Registration end date must be after start date.');
            return;
        }

        const minVal = parseInt(minParticipants.value);
        const maxVal = parseInt(maxParticipants.value);
        if (minVal > maxVal) {
            alert('Minimum participants cannot be greater than maximum participants.');
            return;
        }

        // Submit via AJAX
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.next_step) {
                    window.location.href = `/admin/competition-setup/wizard/step/${data.next_step}`;
                } else {
                    window.location.href = '/admin/competition-setup/wizard/review';
                }
            } else {
                alert('Error: ' + (data.message || 'Failed to save step'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the step.');
        });
    });

    // Load saved data if available
    <?php if (!empty($wizard_data['step_4'])): ?>
    const savedData = <?= json_encode($wizard_data['step_4']) ?>;
    Object.keys(savedData).forEach(key => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = savedData[key];
            } else {
                field.value = savedData[key];
            }
        }
    });
    updateTimelinePreview();
    <?php endif; ?>
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>