<?php 
$layout = 'admin';
$team = $composition_data['team'];
$composition = $composition_data['composition'];
$summary = $composition_data['summary'];
$participants = $composition_data['participants'];
$coaches = $composition_data['coaches'];
$validation = $composition_data['validation'];
$title = 'Team Composition - ' . htmlspecialchars($team->name);
ob_start(); 
?>

<div class="team-composition-detail">
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb">
        <a href="/team/composition">Team Compositions</a>
        <span class="separator">/</span>
        <span class="current"><?= htmlspecialchars($team->name) ?></span>
    </nav>

    <!-- Team Header -->
    <div class="team-header">
        <div class="team-info">
            <h1 class="team-name"><?= htmlspecialchars($team->name) ?></h1>
            <div class="team-meta">
                <span class="category">
                    <i class="fas fa-tag"></i>
                    <?= htmlspecialchars($team->category_name ?? 'Unknown Category') ?>
                </span>
                <span class="school">
                    <i class="fas fa-school"></i>
                    <?= htmlspecialchars($team->school_name ?? 'Unknown School') ?>
                </span>
                <span class="competition">
                    <i class="fas fa-trophy"></i>
                    <?= htmlspecialchars($team->competition_name ?? 'Unknown Competition') ?>
                </span>
            </div>
        </div>
        
        <div class="team-status">
            <div class="status-badge status-<?= $composition->composition_status ?>">
                <?= ucfirst($composition->composition_status) ?>
            </div>
            <?php if ($can_edit): ?>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddParticipantModal()">
                        <i class="fas fa-user-plus"></i>
                        Add Participant
                    </button>
                    <button class="btn btn-secondary" onclick="validateTeamComposition()">
                        <i class="fas fa-check"></i>
                        Validate
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline dropdown-toggle" onclick="toggleDropdown('team-actions')">
                            <i class="fas fa-cog"></i>
                            More Actions
                        </button>
                        <div class="dropdown-menu" id="team-actions">
                            <a href="#" onclick="exportTeamData()">
                                <i class="fas fa-download"></i>
                                Export Team Data
                            </a>
                            <a href="#" onclick="openTeamSettings()">
                                <i class="fas fa-cog"></i>
                                Team Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="requestRosterModification()" class="text-warning">
                                <i class="fas fa-edit"></i>
                                Request Modification
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Validation Status -->
    <?php if (!$validation['is_valid']): ?>
        <div class="validation-alert alert-danger">
            <div class="alert-header">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Team Composition Issues Found</strong>
            </div>
            <div class="alert-content">
                <p><?= $validation['error_count'] ?> error(s) need to be resolved before this team can participate.</p>
                <button class="btn btn-sm btn-danger" onclick="showValidationDetails()">
                    View Details
                </button>
            </div>
        </div>
    <?php elseif (!empty($validation['warnings'])): ?>
        <div class="validation-alert alert-warning">
            <div class="alert-header">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Team Composition Warnings</strong>
            </div>
            <div class="alert-content">
                <p><?= $validation['warning_count'] ?> warning(s) should be reviewed.</p>
                <button class="btn btn-sm btn-warning" onclick="showValidationDetails()">
                    View Details
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="validation-alert alert-success">
            <div class="alert-header">
                <i class="fas fa-check-circle"></i>
                <strong>Team Composition Valid</strong>
            </div>
            <div class="alert-content">
                <p>All validation checks passed. This team is ready for competition.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Team Composition Summary -->
    <div class="composition-overview">
        <div class="overview-cards">
            <!-- Participants Card -->
            <div class="overview-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-users"></i>
                        Participants
                    </h3>
                    <span class="count-badge"><?= $summary['participant_count'] ?> / <?= $summary['max_allowed'] ?></span>
                </div>
                <div class="card-content">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $summary['max_allowed'] > 0 ? ($summary['participant_count'] / $summary['max_allowed'] * 100) : 0 ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <?php if ($summary['slots_available'] > 0): ?>
                            <span class="available"><?= $summary['slots_available'] ?> slots available</span>
                        <?php elseif ($summary['participant_count'] > $summary['max_allowed']): ?>
                            <span class="oversize"><?= $summary['participant_count'] - $summary['max_allowed'] ?> over limit</span>
                        <?php else: ?>
                            <span class="full">Team full</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Coaches Card -->
            <div class="overview-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-chalkboard-teacher"></i>
                        Coaches
                    </h3>
                    <span class="count-badge"><?= $coaches->count() ?> / 2</span>
                </div>
                <div class="card-content">
                    <div class="coach-status">
                        <?php 
                        $primaryCoach = $coaches->where('coach_role', 'primary')->first();
                        $secondaryCoach = $coaches->where('coach_role', 'secondary')->first();
                        ?>
                        <div class="coach-item">
                            <span class="coach-label">Primary:</span>
                            <span class="coach-value <?= $primaryCoach ? 'assigned' : 'missing' ?>">
                                <?= $primaryCoach ? 'Assigned' : 'Not Assigned' ?>
                            </span>
                        </div>
                        <div class="coach-item">
                            <span class="coach-label">Secondary:</span>
                            <span class="coach-value <?= $secondaryCoach ? 'assigned' : 'optional' ?>">
                                <?= $secondaryCoach ? 'Assigned' : 'Optional' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="overview-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-file-check"></i>
                        Documents
                    </h3>
                    <span class="count-badge">
                        <?= $participants->where('documents_complete', true)->count() ?> / <?= $participants->count() ?>
                    </span>
                </div>
                <div class="card-content">
                    <?php 
                    $completedDocs = $participants->where('documents_complete', true)->count();
                    $totalDocs = $participants->count();
                    $docPercentage = $totalDocs > 0 ? ($completedDocs / $totalDocs * 100) : 0;
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $docPercentage ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span class="<?= $docPercentage === 100 ? 'complete' : ($docPercentage > 50 ? 'partial' : 'incomplete') ?>">
                            <?= round($docPercentage) ?>% complete
                        </span>
                    </div>
                </div>
            </div>

            <!-- Last Validation Card -->
            <div class="overview-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-clock"></i>
                        Last Validation
                    </h3>
                </div>
                <div class="card-content">
                    <div class="validation-time">
                        <?php if ($summary['last_validated']): ?>
                            <span class="time"><?= date('M j, Y', strtotime($summary['last_validated'])) ?></span>
                            <span class="time-detail"><?= date('g:i A', strtotime($summary['last_validated'])) ?></span>
                        <?php else: ?>
                            <span class="time">Never</span>
                            <span class="time-detail">Validation required</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Participants Section -->
        <div class="content-section participants-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-users"></i>
                    Team Participants
                </h2>
                <?php if ($can_edit): ?>
                    <button class="btn btn-sm btn-primary" onclick="openAddParticipantModal()">
                        <i class="fas fa-plus"></i>
                        Add Participant
                    </button>
                <?php endif; ?>
            </div>

            <div class="participants-list">
                <?php if ($participants->isEmpty()): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-plus"></i>
                        <h4>No Participants</h4>
                        <p>This team has no participants assigned yet.</p>
                        <?php if ($can_edit): ?>
                            <button class="btn btn-primary" onclick="openAddParticipantModal()">
                                Add First Participant
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($participants as $teamParticipant): ?>
                        <?php $participant = $teamParticipant->participant(); ?>
                        <div class="participant-card" data-participant-id="<?= $teamParticipant->participant_id ?>">
                            <div class="participant-avatar">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($participant->name ?? 'U', 0, 1)) ?>
                                </div>
                            </div>
                            
                            <div class="participant-info">
                                <h4 class="participant-name"><?= htmlspecialchars($participant->name ?? 'Unknown') ?></h4>
                                <div class="participant-details">
                                    <span class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        Grade <?= htmlspecialchars($participant->grade ?? 'N/A') ?>
                                    </span>
                                    <span class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        Joined <?= date('M j, Y', strtotime($teamParticipant->joined_date)) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="participant-role">
                                <div class="role-badge role-<?= $teamParticipant->role ?>">
                                    <?= ucfirst(str_replace('_', ' ', $teamParticipant->role)) ?>
                                    <?php if ($teamParticipant->role === 'team_leader'): ?>
                                        <i class="fas fa-crown"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if ($teamParticipant->specialization): ?>
                                    <div class="specialization">
                                        <?= htmlspecialchars($teamParticipant->specialization) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="participant-status">
                                <div class="status-items">
                                    <div class="status-item">
                                        <span class="status-label">Eligibility:</span>
                                        <span class="status-badge status-<?= $teamParticipant->eligibility_status ?>">
                                            <?= ucfirst($teamParticipant->eligibility_status) ?>
                                        </span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-label">Documents:</span>
                                        <span class="status-badge status-<?= $teamParticipant->documents_complete ? 'complete' : 'incomplete' ?>">
                                            <?= $teamParticipant->documents_complete ? 'Complete' : 'Incomplete' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($can_edit): ?>
                                <div class="participant-actions">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline dropdown-toggle" onclick="toggleDropdown('participant-<?= $teamParticipant->participant_id ?>')">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="participant-<?= $teamParticipant->participant_id ?>">
                                            <a href="#" onclick="viewParticipantDetails(<?= $teamParticipant->participant_id ?>)">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </a>
                                            <a href="#" onclick="changeParticipantRole(<?= $teamParticipant->participant_id ?>, '<?= $teamParticipant->role ?>')">
                                                <i class="fas fa-user-tag"></i>
                                                Change Role
                                            </a>
                                            <a href="#" onclick="updateDocumentStatus(<?= $teamParticipant->participant_id ?>, <?= $teamParticipant->documents_complete ? 'false' : 'true' ?>)">
                                                <i class="fas fa-file-check"></i>
                                                <?= $teamParticipant->documents_complete ? 'Mark Incomplete' : 'Mark Complete' ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="#" onclick="removeParticipant(<?= $teamParticipant->participant_id ?>)" class="text-danger">
                                                <i class="fas fa-user-minus"></i>
                                                Remove from Team
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coaches Section -->
        <div class="content-section coaches-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-chalkboard-teacher"></i>
                    Team Coaches
                </h2>
                <?php if ($can_edit && $coaches->count() < 2): ?>
                    <button class="btn btn-sm btn-primary" onclick="openAssignCoachModal()">
                        <i class="fas fa-plus"></i>
                        Assign Coach
                    </button>
                <?php endif; ?>
            </div>

            <div class="coaches-list">
                <?php if ($coaches->isEmpty()): ?>
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h4>No Coaches Assigned</h4>
                        <p>This team needs at least one primary coach.</p>
                        <?php if ($can_edit): ?>
                            <button class="btn btn-primary" onclick="openAssignCoachModal()">
                                Assign Primary Coach
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($coaches as $teamCoach): ?>
                        <?php $coach = $teamCoach->user(); ?>
                        <div class="coach-card" data-coach-id="<?= $teamCoach->id ?>">
                            <div class="coach-avatar">
                                <div class="avatar-circle coach-avatar-<?= $teamCoach->coach_role ?>">
                                    <?= strtoupper(substr($coach->name ?? 'C', 0, 1)) ?>
                                </div>
                            </div>
                            
                            <div class="coach-info">
                                <h4 class="coach-name"><?= htmlspecialchars($coach->name ?? 'Unknown') ?></h4>
                                <div class="coach-details">
                                    <span class="detail-item">
                                        <i class="fas fa-envelope"></i>
                                        <?= htmlspecialchars($coach->email ?? 'N/A') ?>
                                    </span>
                                    <span class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        Since <?= date('M j, Y', strtotime($teamCoach->assigned_date)) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="coach-role">
                                <div class="role-badge coach-role-<?= $teamCoach->coach_role ?>">
                                    <?= ucfirst($teamCoach->coach_role) ?> Coach
                                    <?php if ($teamCoach->coach_role === 'primary'): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if ($teamCoach->specialization): ?>
                                    <div class="specialization">
                                        <?= htmlspecialchars($teamCoach->specialization) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="coach-status">
                                <div class="status-items">
                                    <div class="status-item">
                                        <span class="status-label">Qualification:</span>
                                        <span class="status-badge status-<?= $teamCoach->qualification_status ?>">
                                            <?= ucfirst($teamCoach->qualification_status) ?>
                                        </span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-label">Training:</span>
                                        <span class="status-badge status-<?= $teamCoach->training_completed ? 'complete' : 'incomplete' ?>">
                                            <?= $teamCoach->training_completed ? 'Complete' : 'Incomplete' ?>
                                        </span>
                                    </div>
                                    <div class="status-item">
                                        <span class="status-label">Background:</span>
                                        <span class="status-badge status-<?= $teamCoach->background_check_status ?>">
                                            <?= ucfirst($teamCoach->background_check_status) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($can_edit): ?>
                                <div class="coach-actions">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline dropdown-toggle" onclick="toggleDropdown('coach-<?= $teamCoach->id ?>')">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="coach-<?= $teamCoach->id ?>">
                                            <a href="#" onclick="viewCoachDetails(<?= $teamCoach->id ?>)">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </a>
                                            <?php if ($teamCoach->status === 'pending_approval'): ?>
                                                <a href="#" onclick="approveCoach(<?= $teamCoach->id ?>)">
                                                    <i class="fas fa-check"></i>
                                                    Approve Coach
                                                </a>
                                            <?php endif; ?>
                                            <div class="dropdown-divider"></div>
                                            <a href="#" onclick="removeCoach(<?= $teamCoach->id ?>)" class="text-danger">
                                                <i class="fas fa-user-times"></i>
                                                Remove Coach
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Participant Modal -->
<div class="modal" id="add-participant-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Participant</h3>
            <button class="modal-close" onclick="closeModal('add-participant-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-participant-form">
                <input type="hidden" name="team_id" value="<?= $team->id ?>">
                
                <div class="form-group">
                    <label for="participant_select">Select Participant:</label>
                    <select name="participant_id" id="participant_select" required>
                        <option value="">Choose a participant...</option>
                        <?php foreach ($available_participants as $participant): ?>
                            <option value="<?= $participant->id ?>">
                                <?= htmlspecialchars($participant->name) ?> - Grade <?= htmlspecialchars($participant->grade) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="participant_role">Role:</label>
                    <select name="role" id="participant_role" required>
                        <option value="regular">Regular Member</option>
                        <option value="team_leader">Team Leader</option>
                        <option value="programmer">Programmer</option>
                        <option value="builder">Builder</option>
                        <option value="designer">Designer</option>
                        <option value="researcher">Researcher</option>
                    </select>
                </div>

                <div id="participant-validation-results" class="validation-results"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('add-participant-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="addParticipant()">Add Participant</button>
        </div>
    </div>
</div>

<!-- Assign Coach Modal -->
<div class="modal" id="assign-coach-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Coach</h3>
            <button class="modal-close" onclick="closeModal('assign-coach-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="assign-coach-form">
                <input type="hidden" name="team_id" value="<?= $team->id ?>">
                
                <div class="form-group">
                    <label for="coach_user_select">Select Coach:</label>
                    <select name="user_id" id="coach_user_select" required>
                        <option value="">Choose a coach...</option>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="coach_role_select">Role:</label>
                    <select name="role" id="coach_role_select" required>
                        <option value="primary">Primary Coach</option>
                        <option value="secondary">Secondary Coach</option>
                        <option value="assistant">Assistant Coach</option>
                        <option value="mentor">Mentor</option>
                    </select>
                </div>

                <div id="coach-validation-results" class="validation-results"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('assign-coach-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="assignCoach()">Assign Coach</button>
        </div>
    </div>
</div>

<!-- Validation Details Modal -->
<div class="modal" id="validation-details-modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Team Validation Details</h3>
            <button class="modal-close" onclick="closeModal('validation-details-modal')">&times;</button>
        </div>
        <div class="modal-body" id="validation-details-content">
            <!-- Validation details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('validation-details-modal')">Close</button>
        </div>
    </div>
</div>

<style>
/* Include CSS for the detailed team composition view */
.team-composition-detail {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    font-size: 14px;
}

.breadcrumb a {
    color: #3498db;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb .separator {
    margin: 0 10px;
    color: #95a5a6;
}

.breadcrumb .current {
    color: #2c3e50;
    font-weight: 500;
}

/* Team Header */
.team-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.team-name {
    font-size: 28px;
    color: #2c3e50;
    margin: 0 0 10px 0;
}

.team-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    font-size: 14px;
    color: #7f8c8d;
}

.team-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.team-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 15px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

/* Status Badge */
.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-complete { background: #d5f4e6; color: #27ae60; }
.status-incomplete { background: #fef9e7; color: #f39c12; }
.status-oversize { background: #fadbd8; color: #e74c3c; }
.status-invalid { background: #f8f9fa; color: #6c757d; }

/* Validation Alert */
.validation-alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border-left: 4px solid;
}

.alert-danger {
    background: #fadbd8;
    border-color: #e74c3c;
    color: #c0392b;
}

.alert-warning {
    background: #fef9e7;
    border-color: #f39c12;
    color: #d68910;
}

.alert-success {
    background: #d5f4e6;
    border-color: #27ae60;
    color: #1e8449;
}

.alert-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 600;
}

/* Overview Cards */
.composition-overview {
    margin-bottom: 30px;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.overview-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.overview-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.overview-card h3 {
    font-size: 16px;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.count-badge {
    background: #ecf0f1;
    color: #2c3e50;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #27ae60, #2ecc71);
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 12px;
    color: #7f8c8d;
}

.coach-status .coach-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 13px;
}

.coach-value.assigned { color: #27ae60; }
.coach-value.missing { color: #e74c3c; }
.coach-value.optional { color: #95a5a6; }

.validation-time {
    text-align: center;
}

.validation-time .time {
    display: block;
    font-size: 14px;
    color: #2c3e50;
    font-weight: 500;
}

.validation-time .time-detail {
    font-size: 11px;
    color: #95a5a6;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* Content Sections */
.content-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.section-header {
    padding: 20px;
    border-bottom: 1px solid #e0e6ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h2 {
    font-size: 20px;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Participant Cards */
.participants-list,
.coaches-list {
    padding: 20px;
}

.participant-card,
.coach-card {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: box-shadow 0.2s ease;
}

.participant-card:hover,
.coach-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.participant-avatar,
.coach-avatar {
    margin-right: 15px;
}

.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #3498db;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 18px;
}

.coach-avatar-primary { background: #e74c3c; }
.coach-avatar-secondary { background: #f39c12; }

.participant-info,
.coach-info {
    flex: 1;
    margin-right: 15px;
}

.participant-name,
.coach-name {
    font-size: 16px;
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.participant-details,
.coach-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 12px;
    color: #7f8c8d;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.participant-role,
.coach-role {
    margin-right: 15px;
    text-align: center;
}

.role-badge {
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.role-team_leader { background: #e8f4fd; color: #2980b9; }
.role-programmer { background: #f0f8e8; color: #27ae60; }
.role-builder { background: #fff3e0; color: #f39c12; }
.role-designer { background: #f3e5f5; color: #9b59b6; }
.role-regular { background: #f8f9fa; color: #6c757d; }

.coach-role-primary { background: #fadbd8; color: #e74c3c; }
.coach-role-secondary { background: #fff3e0; color: #f39c12; }

.specialization {
    font-size: 10px;
    color: #95a5a6;
    margin-top: 3px;
}

.participant-status,
.coach-status {
    margin-right: 15px;
}

.status-items {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
}

.status-label {
    color: #7f8c8d;
    min-width: 70px;
}

.status-badge {
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 500;
}

.status-eligible,
.status-complete,
.status-qualified,
.status-verified { background: #d5f4e6; color: #27ae60; }

.status-pending { background: #fef9e7; color: #f39c12; }

.status-ineligible,
.status-incomplete,
.status-unqualified,
.status-failed { background: #fadbd8; color: #e74c3c; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
}

.empty-state i {
    font-size: 48px;
    color: #bdc3c7;
    margin-bottom: 15px;
}

.empty-state h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

/* Dropdown */
.dropdown {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e0e6ed;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 160px;
    z-index: 100;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 13px;
    border-bottom: 1px solid #f8f9fa;
}

.dropdown-menu a:hover {
    background: #f8f9fa;
}

.dropdown-menu a.text-danger {
    color: #e74c3c;
}

.dropdown-menu a.text-warning {
    color: #f39c12;
}

.dropdown-divider {
    border-top: 1px solid #e0e6ed;
    margin: 5px 0;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow: hidden;
}

.modal-content.large {
    max-width: 800px;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e0e6ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e0e6ed;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #2c3e50;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.validation-results {
    margin-top: 15px;
    padding: 10px;
    border-radius: 6px;
    font-size: 12px;
}

.validation-results.error {
    background: #fadbd8;
    border: 1px solid #e74c3c;
    color: #c0392b;
}

.validation-results.success {
    background: #d5f4e6;
    border: 1px solid #27ae60;
    color: #1e8449;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .team-header {
        flex-direction: column;
        align-items: stretch;
        gap: 20px;
    }
    
    .team-status {
        align-items: flex-start;
    }
    
    .header-actions {
        flex-wrap: wrap;
    }
    
    .overview-cards {
        grid-template-columns: 1fr;
    }
    
    .participant-card,
    .coach-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .participant-info,
    .coach-info {
        margin-right: 0;
    }
    
    .status-items {
        flex-direction: row;
        flex-wrap: wrap;
    }
}
</style>

<script>
// JavaScript for team composition detail page
const teamId = <?= $team->id ?>;
const canEdit = <?= $can_edit ? 'true' : 'false' ?>;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadAvailableCoaches();
});

// Modal functions
function openAddParticipantModal() {
    document.getElementById('add-participant-modal').classList.add('show');
}

function openAssignCoachModal() {
    document.getElementById('assign-coach-modal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function showValidationDetails() {
    // Load and show detailed validation results
    const modal = document.getElementById('validation-details-modal');
    const content = document.getElementById('validation-details-content');
    
    const validation = <?= json_encode($validation) ?>;
    
    let html = '<div class="validation-detail-content">';
    
    if (!validation.is_valid && validation.errors) {
        html += '<div class="validation-section errors">';
        html += '<h4><i class="fas fa-exclamation-triangle"></i> Validation Errors</h4>';
        for (const [field, errors] of Object.entries(validation.errors)) {
            html += `<div class="validation-group">`;
            html += `<h5>${field.replace(/_/g, ' ').toUpperCase()}</h5>`;
            html += '<ul>';
            errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        html += '</div>';
    }
    
    if (validation.warnings && Object.keys(validation.warnings).length > 0) {
        html += '<div class="validation-section warnings">';
        html += '<h4><i class="fas fa-exclamation-circle"></i> Warnings</h4>';
        for (const [field, warnings] of Object.entries(validation.warnings)) {
            html += `<div class="validation-group">`;
            html += `<h5>${field.replace(/_/g, ' ').toUpperCase()}</h5>`;
            html += '<ul>';
            warnings.forEach(warning => {
                html += `<li>${warning}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        html += '</div>';
    }
    
    html += '</div>';
    content.innerHTML = html;
    modal.classList.add('show');
}

// Participant functions
function addParticipant() {
    const form = document.getElementById('add-participant-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch('/team/composition/add-participant', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Participant added successfully');
            closeModal('add-participant-modal');
            location.reload(); // Refresh the page to show new participant
        } else {
            showValidationError('participant-validation-results', result.errors || {}, result.message);
        }
    })
    .catch(error => {
        console.error('Error adding participant:', error);
        showError('Failed to add participant');
    });
}

function removeParticipant(participantId) {
    if (!confirm('Are you sure you want to remove this participant from the team?')) {
        return;
    }
    
    const reason = prompt('Please provide a reason for removal:', 'voluntary') || 'voluntary';
    
    fetch('/team/composition/remove-participant', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_id: teamId,
            participant_id: participantId,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Participant removed successfully');
            location.reload();
        } else {
            showError('Failed to remove participant: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error removing participant:', error);
        showError('Failed to remove participant');
    });
}

function changeParticipantRole(participantId, currentRole) {
    const roles = [
        { value: 'regular', label: 'Regular Member' },
        { value: 'team_leader', label: 'Team Leader' },
        { value: 'programmer', label: 'Programmer' },
        { value: 'builder', label: 'Builder' },
        { value: 'designer', label: 'Designer' },
        { value: 'researcher', label: 'Researcher' }
    ];
    
    let options = roles.map(role => 
        `<option value="${role.value}" ${role.value === currentRole ? 'selected' : ''}>${role.label}</option>`
    ).join('');
    
    const newRole = prompt(`Select new role:\n${roles.map(r => r.label).join('\n')}`, currentRole);
    
    if (!newRole || newRole === currentRole) {
        return;
    }
    
    fetch('/team/composition/update-participant-role', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_id: teamId,
            participant_id: participantId,
            new_role: newRole
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Participant role updated successfully');
            location.reload();
        } else {
            showError('Failed to update role: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error updating role:', error);
        showError('Failed to update participant role');
    });
}

// Coach functions
function loadAvailableCoaches() {
    // This would load available coaches from the server
    // For now, we'll use placeholder data
    const coachSelect = document.getElementById('coach_user_select');
    // Add coach options dynamically
}

function assignCoach() {
    const form = document.getElementById('assign-coach-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch('/team/coach/assign', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Coach assigned successfully');
            closeModal('assign-coach-modal');
            location.reload();
        } else {
            showValidationError('coach-validation-results', result.errors || {}, result.message);
        }
    })
    .catch(error => {
        console.error('Error assigning coach:', error);
        showError('Failed to assign coach');
    });
}

function removeCoach(coachId) {
    if (!confirm('Are you sure you want to remove this coach from the team?')) {
        return;
    }
    
    fetch('/team/coach/remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            coach_id: coachId,
            reason: 'Removed by team manager'
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Coach removed successfully');
            location.reload();
        } else {
            showError('Failed to remove coach: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error removing coach:', error);
        showError('Failed to remove coach');
    });
}

// Validation functions
function validateTeamComposition() {
    fetch('/team/composition/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_id: teamId,
            context: 'real_time'
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Team validation completed');
            location.reload(); // Refresh to show updated validation status
        } else {
            showError('Validation failed: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error validating team:', error);
        showError('Failed to validate team');
    });
}

// Utility functions
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    const isVisible = dropdown.classList.contains('show');
    
    // Close all dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
    
    // Toggle current dropdown
    if (!isVisible) {
        dropdown.classList.add('show');
    }
}

function showValidationError(containerId, errors, message) {
    const container = document.getElementById(containerId);
    let html = '<div class="validation-results error">';
    
    if (message) {
        html += `<div class="error-message">${message}</div>`;
    }
    
    if (errors && Object.keys(errors).length > 0) {
        html += '<ul>';
        for (const [field, fieldErrors] of Object.entries(errors)) {
            fieldErrors.forEach(error => {
                html += `<li>${error}</li>`;
            });
        }
        html += '</ul>';
    }
    
    html += '</div>';
    container.innerHTML = html;
}

function showSuccess(message) {
    // Implementation for success notification
    console.log('Success:', message);
    // You would integrate with your notification system here
}

function showError(message) {
    // Implementation for error notification
    console.log('Error:', message);
    // You would integrate with your notification system here
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Close modal on outside click
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>