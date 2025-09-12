<?php 
$layout = 'admin';
ob_start(); 
?>

<div class="admin-participant-edit">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Participant</h1>
            <p class="text-muted">Update participant information</p>
        </div>
        <div class="btn-group">
            <a href="/admin/participants/<?= $participant['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Details
            </a>
            <a href="/admin/participants" class="btn btn-outline-secondary">
                <i class="fas fa-list me-1"></i> All Participants
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Participant Information</h5>
                </div>
                <div class="card-body">
                    <form action="/admin/participants/<?= $participant['id'] ?>" method="POST" id="participantForm">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($participant['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($participant['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?= htmlspecialchars($participant['date_of_birth']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="grade" class="form-label">Grade <span class="text-danger">*</span></label>
                                <select class="form-select" id="grade" name="grade" required>
                                    <option value="">Select Grade</option>
                                    <?php for ($i = 8; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= $participant['grade'] == $i ? 'selected' : '' ?>>
                                            Grade <?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?= $participant['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $participant['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= $participant['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                    <option value="prefer_not_to_say" <?= $participant['gender'] === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" 
                                       value="<?= htmlspecialchars($participant['id_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                       value="<?= htmlspecialchars($participant['contact_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($participant['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="team_id" class="form-label">Team Assignment</label>
                            <select class="form-select" id="team_id" name="team_id">
                                <option value="">No Team Assignment</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>" 
                                            <?= $participant['team_id'] == $team['id'] ? 'selected' : '' ?>
                                            data-current-participants="<?= $team['current_participants'] ?>"
                                            data-max-participants="<?= $team['max_participants'] ?>">
                                        <?= htmlspecialchars($team['school_name'] . ' - ' . $team['team_name']) ?>
                                        (<?= $team['current_participants'] ?>/<?= $team['max_participants'] ?> members)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select a team to assign this participant to.</div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $participant['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $participant['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= $participant['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="special_requirements" class="form-label">Special Requirements</label>
                            <textarea class="form-control" id="special_requirements" name="special_requirements" rows="3"
                                      placeholder="Any special accommodations or requirements..."><?= htmlspecialchars($participant['special_requirements'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Internal notes about this participant..."><?= htmlspecialchars($participant['notes'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/admin/participants/<?= $participant['id'] ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Current Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">School:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($participant['school_name'] ?? 'Not assigned') ?></dd>
                        
                        <dt class="col-sm-5">Team:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($participant['team_name'] ?? 'Not assigned') ?></dd>
                        
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-<?= $participant['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($participant['status']) ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-5">Created:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($participant['created_at'])) ?></dd>
                        
                        <dt class="col-sm-5">Last Updated:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($participant['updated_at'])) ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Help</h5>
                </div>
                <div class="card-body">
                    <h6>Team Assignment</h6>
                    <p class="small text-muted">
                        When changing team assignment, ensure the new team has available slots. 
                        Teams showing "Full" have reached their maximum participant limit.
                    </p>
                    
                    <h6>Status Options</h6>
                    <ul class="small text-muted">
                        <li><strong>Active:</strong> Can participate in competitions</li>
                        <li><strong>Inactive:</strong> Temporarily unable to participate</li>
                        <li><strong>Suspended:</strong> Suspended from participation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('participantForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = document.querySelector('button[type="submit"]');
    
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text().then(text => {
                throw new Error('Server error: ' + response.status);
            });
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        alert('An error occurred while saving the participant.');
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save me-1"></i> Save Changes';
    });
});

// Team capacity warning
document.getElementById('team_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const current = parseInt(selectedOption.dataset.currentParticipants);
        const max = parseInt(selectedOption.dataset.maxParticipants);
        
        if (current >= max) {
            alert('Warning: This team is at full capacity. Adding this participant will exceed the team limit.');
        }
    }
});
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>