<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- School Details View -->
<div class="school-details-container">
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
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <i class="fas fa-school"></i>
                    <?= htmlspecialchars($school['name']) ?>
                    <span class="badge badge-<?= ($school['status'] == 'active') ? 'success' : (($school['status'] == 'pending') ? 'warning' : 'secondary') ?> ml-2">
                        <?= htmlspecialchars(ucfirst($school['status'])) ?>
                    </span>
                </h1>
                <p class="page-subtitle">
                    <?= htmlspecialchars(ucfirst($school['school_type'])) ?> • 
                    <?= htmlspecialchars($school['district']) ?> • 
                    <?= htmlspecialchars($school['province']) ?>
                </p>
            </div>
            <div class="col-auto">
                <div class="btn-group">
                    <a href="/admin/schools/<?= $school['id'] ?>/edit" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit School
                    </a>
                    <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="/admin/schools/<?= $school['id'] ?>/teams">
                            <i class="fas fa-users"></i> Manage Teams
                        </a>
                        <a class="dropdown-item" href="/admin/schools/<?= $school['id'] ?>/contacts">
                            <i class="fas fa-address-book"></i> Manage Contacts
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="exportSchoolData(<?= $school['id'] ?>)">
                            <i class="fas fa-download"></i> Export Data
                        </a>
                        <a class="dropdown-item" href="#" onclick="printSchoolProfile(<?= $school['id'] ?>)">
                            <i class="fas fa-print"></i> Print Profile
                        </a>
                        <?php if ($school['status'] === 'pending'): ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-success" href="#" onclick="approveSchool(<?= $school['id'] ?>)">
                            <i class="fas fa-check"></i> Approve School
                        </a>
                        <a class="dropdown-item text-danger" href="#" onclick="rejectSchool(<?= $school['id'] ?>)">
                            <i class="fas fa-times"></i> Reject School
                        </a>
                        <?php elseif ($school['status'] === 'active'): ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-warning" href="#" onclick="suspendSchool(<?= $school['id'] ?>)">
                            <i class="fas fa-pause"></i> Suspend School
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- School Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title mb-0"><?= $stats['total_teams'] ?></h3>
                            <p class="card-text">Teams Registered</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title mb-0"><?= $stats['total_participants'] ?></h3>
                            <p class="card-text">Total Participants</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title mb-0"><?= $stats['active_teams'] ?></h3>
                            <p class="card-text">Active Teams</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-trophy fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title mb-0"><?= count($stats['document_requirements']) ?></h3>
                            <p class="card-text">Missing Documents</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Content -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="contacts-tab" data-toggle="tab" href="#contacts" role="tab">
                        <i class="fas fa-address-book"></i> Contacts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="teams-tab" data-toggle="tab" href="#teams" role="tab">
                        <i class="fas fa-users"></i> Teams (<?= count($teams) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="facilities-tab" data-toggle="tab" href="#facilities" role="tab">
                        <i class="fas fa-building"></i> Facilities
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab">
                        <i class="fas fa-history"></i> History
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Basic Information Tab -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-school text-primary"></i> School Details</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">School Name:</th>
                                    <td><?= htmlspecialchars($school['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>EMIS Number:</th>
                                    <td><?= htmlspecialchars($school['emis_number'] ?: 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <th>Registration Number:</th>
                                    <td><?= htmlspecialchars($school['registration_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>School Type:</th>
                                    <td><?= htmlspecialchars(ucfirst($school['school_type'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Quintile:</th>
                                    <td><?= $school['quintile'] ? 'Quintile ' . $school['quintile'] : 'Not specified' ?></td>
                                </tr>
                                <tr>
                                    <th>Total Learners:</th>
                                    <td><?= number_format($school['total_learners']) ?></td>
                                </tr>
                                <tr>
                                    <th>Established:</th>
                                    <td><?= $school['establishment_date'] ? date('Y', strtotime($school['establishment_date'])) : 'Not specified' ?></td>
                                </tr>
                                <tr>
                                    <th>Registration Date:</th>
                                    <td><?= date('j F Y', strtotime($school['registration_date'])) ?></td>
                                </tr>
                                <?php if ($school['approval_date']): ?>
                                <tr>
                                    <th>Approval Date:</th>
                                    <td><?= date('j F Y', strtotime($school['approval_date'])) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-map-marker-alt text-success"></i> Location & Contact</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Address:</th>
                                    <td><?= htmlspecialchars(trim($school['address_line1'] . ' ' . $school['address_line2'] . ', ' . $school['city'] . ' ' . $school['postal_code'])) ?></td>
                                </tr>
                                <tr>
                                    <th>District:</th>
                                    <td><?= htmlspecialchars($school['district']) ?></td>
                                </tr>
                                <tr>
                                    <th>Province:</th>
                                    <td><?= htmlspecialchars($school['province']) ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?= htmlspecialchars($school['phone']) ?></td>
                                </tr>
                                <?php if ($school['fax']): ?>
                                <tr>
                                    <th>Fax:</th>
                                    <td><?= htmlspecialchars($school['fax']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Email:</th>
                                    <td><a href="mailto:<?= htmlspecialchars($school['email']) ?>"><?= htmlspecialchars($school['email']) ?></a></td>
                                </tr>
                                <?php if ($school['website']): ?>
                                <tr>
                                    <th>Website:</th>
                                    <td><a href="<?= htmlspecialchars($school['website']) ?>" target="_blank"><?= htmlspecialchars($school['website']) ?></a></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Communication Preference:</th>
                                    <td><?= htmlspecialchars($school['communication_preference_label'] ?? 'Email') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($school['notes']): ?>
                    <div class="mt-4">
                        <h5><i class="fas fa-sticky-note text-warning"></i> Administrative Notes</h5>
                        <div class="alert alert-info">
                            <?= nl2br(htmlspecialchars($school['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contacts Tab -->
                <div class="tab-pane fade" id="contacts" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-users text-primary"></i> School Contacts</h5>
                        <a href="/admin/contacts/create?school_id=<?= $school['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Contact
                        </a>
                    </div>

                    <?php if (empty($contacts)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No contacts have been added for this school yet.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($contacts as $contact): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1">
                                                    <?= htmlspecialchars($contact->getFullName()) ?>
                                                    <?php if ($contact->isPrimary()): ?>
                                                        <span class="badge badge-primary badge-sm ml-1">Primary</span>
                                                    <?php endif; ?>
                                                    <?php if ($contact->isEmergency()): ?>
                                                        <span class="badge badge-danger badge-sm ml-1">Emergency</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="card-text text-muted mb-2"><?= htmlspecialchars($contact['position']) ?></p>
                                                <p class="card-text small mb-1">
                                                    <i class="fas fa-envelope text-muted mr-1"></i>
                                                    <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a>
                                                </p>
                                                <?php if ($contact['phone']): ?>
                                                <p class="card-text small mb-1">
                                                    <i class="fas fa-phone text-muted mr-1"></i>
                                                    <a href="tel:<?= htmlspecialchars($contact['phone']) ?>"><?= htmlspecialchars($contact['phone']) ?></a>
                                                </p>
                                                <?php endif; ?>
                                                <?php if ($contact['mobile']): ?>
                                                <p class="card-text small mb-0">
                                                    <i class="fas fa-mobile-alt text-muted mr-1"></i>
                                                    <a href="tel:<?= htmlspecialchars($contact['mobile']) ?>"><?= htmlspecialchars($contact['mobile']) ?></a>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="/admin/contacts/<?= $contact['id'] ?>/edit">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="sendEmail('<?= htmlspecialchars($contact['email']) ?>')">
                                                        <i class="fas fa-envelope"></i> Send Email
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#" onclick="deleteContact(<?= $contact['id'] ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Teams Tab -->
                <div class="tab-pane fade" id="teams" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-users text-success"></i> School Teams</h5>
                        <?php if ($school['status'] === 'approved' || $school['status'] === 'active'): ?>
                        <a href="/teams/create?school_id=<?= $school['id'] ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Register New Team
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($teams)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No teams have been registered for this school yet.
                            <?php if ($school['status'] === 'approved' || $school['status'] === 'active'): ?>
                                <a href="/teams/create?school_id=<?= $school['id'] ?>" class="alert-link">Register the first team</a>.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Team Name</th>
                                        <th>Category</th>
                                        <th>Phase</th>
                                        <th>Participants</th>
                                        <th>Coach</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teams as $team): ?>
                                    <tr>
                                        <td>
                                            <a href="/admin/teams/<?= $team['id'] ?>" class="font-weight-bold text-decoration-none">
                                                <?= htmlspecialchars($team['name']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($team['category_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($team['phase_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge badge-info"><?= $team['participant_count'] ?? 0 ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($team['coach_name'] ?? 'Not assigned') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $team['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($team['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/admin/teams/<?= $team['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/admin/teams/<?= $team['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Facilities Tab -->
                <div class="tab-pane fade" id="facilities" role="tabpanel">
                    <h5><i class="fas fa-building text-info"></i> School Facilities</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-building"></i> General Facilities</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($school['facilities']): ?>
                                        <p><?= nl2br(htmlspecialchars($school['facilities'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No facility information provided.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-laptop"></i> Computer Laboratory</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($school['computer_lab']): ?>
                                        <p><?= nl2br(htmlspecialchars($school['computer_lab'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No computer lab information provided.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-wifi"></i> Internet Connectivity</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($school['internet_status']): ?>
                                        <span class="badge badge-<?= $school['internet_status'] === 'high_speed_fiber' ? 'success' : ($school['internet_status'] === 'none' ? 'danger' : 'warning') ?> badge-lg">
                                            <?= ucwords(str_replace('_', ' ', $school['internet_status'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <p class="text-muted">Internet status not specified.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-wheelchair"></i> Accessibility Features</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($school['accessibility_features']): ?>
                                        <p><?= nl2br(htmlspecialchars($school['accessibility_features'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No accessibility information provided.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-trophy"></i> Previous Participation</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($school['previous_participation']): ?>
                                        <p><?= nl2br(htmlspecialchars($school['previous_participation'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No previous participation history provided.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="history" role="tabpanel">
                    <h5><i class="fas fa-history text-secondary"></i> School History</h5>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">School Registered</h6>
                                <p class="timeline-text">School was registered in the SciBOTICS system.</p>
                                <small class="timeline-date"><?= date('j F Y', strtotime($school['registration_date'])) ?></small>
                            </div>
                        </div>

                        <?php if ($school['approval_date']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">School Approved</h6>
                                <p class="timeline-text">School registration was approved by administrators.</p>
                                <small class="timeline-date"><?= date('j F Y', strtotime($school['approval_date'])) ?></small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($teams)): ?>
                            <?php foreach (array_slice($teams, 0, 5) as $team): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Team Registered</h6>
                                    <p class="timeline-text">
                                        Team "<?= htmlspecialchars($team['name']) ?>" was registered for <?= htmlspecialchars($team['category_name'] ?? 'Unknown Category') ?>.
                                    </p>
                                    <small class="timeline-date"><?= date('j F Y', strtotime($team['created_at'])) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (count($stats['document_requirements']) > 0): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Document Requirements</h6>
                                <p class="timeline-text">Missing required documents:</p>
                                <ul class="mb-2">
                                    <?php foreach ($stats['document_requirements'] as $requirement): ?>
                                        <li><?= htmlspecialchars($requirement) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <small class="timeline-date">Current</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// School management functions
function approveSchool(schoolId) {
    if (confirm('Are you sure you want to approve this school?')) {
        fetch(`/admin/schools/${schoolId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
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

function rejectSchool(schoolId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        fetch(`/admin/schools/${schoolId}/reject`, {
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
                location.reload();
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

function suspendSchool(schoolId) {
    const reason = prompt('Please provide a reason for suspension:');
    if (reason) {
        fetch(`/admin/schools/${schoolId}/suspend`, {
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
                location.reload();
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

function exportSchoolData(schoolId) {
    window.open(`/admin/schools/${schoolId}/export`, '_blank');
}

function printSchoolProfile(schoolId) {
    window.print();
}

function sendEmail(email) {
    window.location.href = `mailto:${email}`;
}

function deleteContact(contactId) {
    if (confirm('Are you sure you want to delete this contact?')) {
        fetch(`/admin/contacts/${contactId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
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
</script>

<style>
.school-details-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.badge-lg {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: 600;
    color: #495057;
}

.timeline-text {
    margin-bottom: 5px;
    color: #6c757d;
}

.timeline-date {
    color: #adb5bd;
    font-weight: 500;
}

.card-title {
    color: #495057;
}

.opacity-75 {
    opacity: 0.75;
}

@media print {
    .btn, .dropdown, .nav-tabs {
        display: none !important;
    }
    
    .tab-content > .tab-pane {
        display: block !important;
        opacity: 1 !important;
    }
}
</style>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>