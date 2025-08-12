<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<div class="admin-content">
    <!-- Breadcrumbs -->
    <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
    <nav class="admin-breadcrumbs" aria-label="breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <?php if ($index === count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($breadcrumb['title']) ?>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <a href="<?= $baseUrl . htmlspecialchars($breadcrumb['url']) ?>" class="breadcrumb-link">
                            <?= htmlspecialchars($breadcrumb['title']) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="school-details-header">
        <div class="school-title-section">
            <h1>
                <i class="fas fa-school"></i>
                <?= htmlspecialchars($school['name']) ?>
                <span class="school-status-badge <?= $school['status'] ?? 'inactive' ?>">
                    <?= htmlspecialchars(ucfirst($school['status'] ?? 'inactive')) ?>
                </span>
            </h1>
            <p class="school-subtitle">
                <?= htmlspecialchars(ucfirst($school['school_type'])) ?> • 
                <?= htmlspecialchars($school['district'] ?? 'Unknown District') ?> • 
                <?= htmlspecialchars($school['province'] ?? '') ?>
            </p>
        </div>
        <div class="school-actions">
            <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>/edit" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit School
            </a>
            <div class="dropdown">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-h"></i> More Actions
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>/teams">
                        <i class="fas fa-users"></i> Manage Teams
                    </a>
                    <a class="dropdown-item" href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>/contacts">
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

    <!-- School Statistics Cards -->
    <div class="school-stats-grid">
        <div class="school-stat-card primary">
            <div class="school-stat-content">
                <div class="school-stat-info">
                    <h3><?= $stats['total_teams'] ?? 0 ?></h3>
                    <p>Teams Registered</p>
                </div>
                <div class="school-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="school-stat-card success">
            <div class="school-stat-content">
                <div class="school-stat-info">
                    <h3><?= $stats['total_participants'] ?? 0 ?></h3>
                    <p>Total Participants</p>
                </div>
                <div class="school-stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>
        
        <div class="school-stat-card info">
            <div class="school-stat-content">
                <div class="school-stat-info">
                    <h3><?= $stats['active_teams'] ?? 0 ?></h3>
                    <p>Active Teams</p>
                </div>
                <div class="school-stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
        </div>
        
        <div class="school-stat-card warning">
            <div class="school-stat-content">
                <div class="school-stat-info">
                    <h3><?= count($stats['document_requirements'] ?? []) ?></h3>
                    <p>Missing Documents</p>
                </div>
                <div class="school-stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Content -->
    <div class="school-tabs-container">
        <div class="school-tabs-header">
            <ul class="school-nav-tabs" role="tablist">
                <li class="school-nav-item">
                    <a class="school-nav-link active" id="basic-tab" data-tab="basic" href="#basic" role="tab">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </a>
                </li>
                <li class="school-nav-item">
                    <a class="school-nav-link" id="contacts-tab" data-tab="contacts" href="#contacts" role="tab">
                        <i class="fas fa-address-book"></i> Contacts
                    </a>
                </li>
                <li class="school-nav-item">
                    <a class="school-nav-link" id="teams-tab" data-tab="teams" href="#teams" role="tab">
                        <i class="fas fa-users"></i> Teams (<?= count($teams ?? []) ?>)
                    </a>
                </li>
                <li class="school-nav-item">
                    <a class="school-nav-link" id="facilities-tab" data-tab="facilities" href="#facilities" role="tab">
                        <i class="fas fa-building"></i> Facilities
                    </a>
                </li>
                <li class="school-nav-item">
                    <a class="school-nav-link" id="history-tab" data-tab="history" href="#history" role="tab">
                        <i class="fas fa-history"></i> History
                    </a>
                </li>
            </ul>
        </div>
        <div class="school-tabs-content">
            <!-- Basic Information Tab -->
            <div class="school-tab-content active" id="basic" role="tabpanel">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: var(--space-6);">
                        <div>
                            <div class="school-section-heading">
                                <i class="fas fa-school"></i>
                                <h5>School Details</h5>
                            </div>
                            <table class="school-info-table">
                                <tr>
                                    <th>School Name:</th>
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
                                    <td><?= number_format($school['total_learners'] ?? 0) ?></td>
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
                        <div>
                            <div class="school-section-heading">
                                <i class="fas fa-map-marker-alt"></i>
                                <h5>Location & Contact</h5>
                            </div>
                            <table class="school-info-table">
                                <tr>
                                    <th>Address:</th>
                                    <td><?= htmlspecialchars(trim(($school['address_line1'] ?? '') . ' ' . ($school['address_line2'] ?? '') . ', ' . ($school['city'] ?? '') . ' ' . ($school['postal_code'] ?? ''))) ?></td>
                                </tr>
                                <tr>
                                    <th>District:</th>
                                    <td><?= htmlspecialchars($school['district'] ?? 'Unknown') ?></td>
                                </tr>
                                <tr>
                                    <th>Province:</th>
                                    <td><?= htmlspecialchars($school['province'] ?? 'Unknown') ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?= htmlspecialchars($school['phone'] ?? 'Not provided') ?></td>
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
                                    <td><?= htmlspecialchars($school['communication_preference'] ?? 'Email') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($school['notes']): ?>
                    <div class="school-admin-notes">
                        <h5><i class="fas fa-sticky-note"></i> Administrative Notes</h5>
                        <div class="school-admin-notes-content">
                            <?= nl2br(htmlspecialchars($school['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contacts Tab -->
                <div class="school-tab-content" id="contacts" role="tabpanel">
                    <div class="contacts-header">
                        <h3 class="contacts-title">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            School Contacts
                        </h3>
                        <a href="<?= $baseUrl ?>/admin/contacts/create?school_id=<?= $school['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            Add Contact
                        </a>
                    </div>

                    <?php if (empty($contacts)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-users" aria-hidden="true"></i>
                            </div>
                            <h4 class="empty-state-title">No Contacts Added</h4>
                            <p class="empty-state-description">No contacts have been added for this school yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="contacts-grid">
                            <?php foreach ($contacts as $contact): ?>
                            <div class="contact-card">
                                <div class="contact-card-header">
                                    <div class="contact-name-badges">
                                        <h4 class="contact-name"><?= htmlspecialchars($contact->getFullName()) ?></h4>
                                        <div class="contact-badges">
                                            <?php if ($contact->isPrimary()): ?>
                                                <span class="badge badge-primary">Primary</span>
                                            <?php endif; ?>
                                            <?php if ($contact->isEmergency()): ?>
                                                <span class="badge badge-danger">Emergency</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="contact-actions">
                                        <div class="dropdown">
                                            <button class="btn btn-ghost btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="<?= $baseUrl ?>/admin/contacts/<?= $contact['id'] ?>/edit">
                                                    <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="sendEmail('<?= htmlspecialchars($contact['email']) ?>')">
                                                    <i class="fas fa-envelope" aria-hidden="true"></i> Send Email
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#" onclick="deleteContact(<?= $contact['id'] ?>)">
                                                    <i class="fas fa-trash" aria-hidden="true"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="contact-details">
                                    <p class="contact-position"><?= htmlspecialchars($contact['position']) ?></p>
                                    <div class="contact-info">
                                        <div class="contact-info-item">
                                            <i class="fas fa-envelope" aria-hidden="true"></i>
                                            <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a>
                                        </div>
                                        <?php if ($contact['phone']): ?>
                                        <div class="contact-info-item">
                                            <i class="fas fa-phone" aria-hidden="true"></i>
                                            <a href="tel:<?= htmlspecialchars($contact['phone']) ?>"><?= htmlspecialchars($contact['phone']) ?></a>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($contact['mobile']): ?>
                                        <div class="contact-info-item">
                                            <i class="fas fa-mobile-alt" aria-hidden="true"></i>
                                            <a href="tel:<?= htmlspecialchars($contact['mobile']) ?>"><?= htmlspecialchars($contact['mobile']) ?></a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Teams Tab -->
                <div class="school-tab-content" id="teams" role="tabpanel">
                    <div class="teams-header">
                        <h3 class="teams-title">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            School Teams
                        </h3>
                        <?php if ($school['status'] === 'approved' || $school['status'] === 'active'): ?>
                        <a href="<?= $baseUrl ?>/teams/create?school_id=<?= $school['id'] ?>" class="btn btn-success">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            Register New Team
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($teams)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-users" aria-hidden="true"></i>
                            </div>
                            <h4 class="empty-state-title">No Teams Registered</h4>
                            <p class="empty-state-description">No teams have been registered for this school yet.</p>
                            <?php if ($school['status'] === 'approved' || $school['status'] === 'active'): ?>
                                <a href="<?= $baseUrl ?>/teams/create?school_id=<?= $school['id'] ?>" class="btn btn-primary">Register First Team</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="teams-table-container">
                            <div class="admin-table-wrapper">
                                <table class="admin-table">
                                    <thead class="admin-table-header">
                                        <tr>
                                            <th class="admin-table-th">Team Name</th>
                                            <th class="admin-table-th">Category</th>
                                            <th class="admin-table-th">Phase</th>
                                            <th class="admin-table-th">Participants</th>
                                            <th class="admin-table-th">Coach</th>
                                            <th class="admin-table-th">Status</th>
                                            <th class="admin-table-th">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="admin-table-body">
                                        <?php foreach ($teams as $team): ?>
                                        <tr class="admin-table-row">
                                            <td class="admin-table-td">
                                                <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>" class="table-link-primary">
                                                    <?= htmlspecialchars($team['name']) ?>
                                                </a>
                                            </td>
                                            <td class="admin-table-td"><?= htmlspecialchars($team['category_name'] ?? 'N/A') ?></td>
                                            <td class="admin-table-td"><?= htmlspecialchars($team['phase_name'] ?? 'N/A') ?></td>
                                            <td class="admin-table-td">
                                                <span class="badge badge-info"><?= $team['participant_count'] ?? 0 ?></span>
                                            </td>
                                            <td class="admin-table-td"><?= htmlspecialchars($team['coach_name'] ?? 'Not assigned') ?></td>
                                            <td class="admin-table-td">
                                                <span class="badge badge-<?= $team['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($team['status']) ?>
                                                </span>
                                            </td>
                                            <td class="admin-table-td">
                                                <div class="table-actions">
                                                    <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>" class="btn btn-sm btn-ghost" title="View Team">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>/edit" class="btn btn-sm btn-ghost" title="Edit Team">
                                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Facilities Tab -->
                <div class="school-tab-content" id="facilities" role="tabpanel">
                    <h3 class="facilities-title">
                        <i class="fas fa-building" aria-hidden="true"></i>
                        School Facilities
                    </h3>
                    
                    <div class="facilities-grid">
                        <div class="facility-section">
                            <div class="facility-card">
                                <div class="facility-card-header">
                                    <h4 class="facility-card-title">
                                        <i class="fas fa-building" aria-hidden="true"></i>
                                        General Facilities
                                    </h4>
                                </div>
                                <div class="facility-card-body">
                                    <?php if ($school['facilities']): ?>
                                        <div class="facility-content"><?= nl2br(htmlspecialchars($school['facilities'])) ?></div>
                                    <?php else: ?>
                                        <div class="facility-empty">No facility information provided.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="facility-card">
                                <div class="facility-card-header">
                                    <h4 class="facility-card-title">
                                        <i class="fas fa-laptop" aria-hidden="true"></i>
                                        Computer Laboratory
                                    </h4>
                                </div>
                                <div class="facility-card-body">
                                    <?php if ($school['computer_lab']): ?>
                                        <div class="facility-content"><?= nl2br(htmlspecialchars($school['computer_lab'])) ?></div>
                                    <?php else: ?>
                                        <div class="facility-empty">No computer lab information provided.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="facility-section">
                            <div class="facility-card">
                                <div class="facility-card-header">
                                    <h4 class="facility-card-title">
                                        <i class="fas fa-wifi" aria-hidden="true"></i>
                                        Internet Connectivity
                                    </h4>
                                </div>
                                <div class="facility-card-body">
                                    <?php if ($school['internet_status']): ?>
                                        <span class="connectivity-badge connectivity-<?= $school['internet_status'] === 'high_speed_fiber' ? 'excellent' : ($school['internet_status'] === 'none' ? 'none' : 'limited') ?>">
                                            <?= ucwords(str_replace('_', ' ', $school['internet_status'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <div class="facility-empty">Internet status not specified.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="facility-card">
                                <div class="facility-card-header">
                                    <h4 class="facility-card-title">
                                        <i class="fas fa-wheelchair" aria-hidden="true"></i>
                                        Accessibility Features
                                    </h4>
                                </div>
                                <div class="facility-card-body">
                                    <?php if ($school['accessibility_features']): ?>
                                        <div class="facility-content"><?= nl2br(htmlspecialchars($school['accessibility_features'])) ?></div>
                                    <?php else: ?>
                                        <div class="facility-empty">No accessibility information provided.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="facility-card">
                                <div class="facility-card-header">
                                    <h4 class="facility-card-title">
                                        <i class="fas fa-trophy" aria-hidden="true"></i>
                                        Previous Participation
                                    </h4>
                                </div>
                                <div class="facility-card-body">
                                    <?php if ($school['previous_participation']): ?>
                                        <div class="facility-content"><?= nl2br(htmlspecialchars($school['previous_participation'])) ?></div>
                                    <?php else: ?>
                                        <div class="facility-empty">No previous participation history provided.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="school-tab-content" id="history" role="tabpanel">
                    <h3 class="history-title">
                        <i class="fas fa-history" aria-hidden="true"></i>
                        School History
                    </h3>
                    
                    <div class="school-timeline">
                        <div class="school-timeline-item">
                            <div class="school-timeline-marker school-timeline-marker-success"></div>
                            <div class="school-timeline-content">
                                <h4 class="school-timeline-title">School Registered</h4>
                                <p class="school-timeline-text">School was registered in the SciBOTICS system.</p>
                                <time class="school-timeline-date"><?= date('j F Y', strtotime($school['registration_date'])) ?></time>
                            </div>
                        </div>

                        <?php if ($school['approval_date']): ?>
                        <div class="school-timeline-item">
                            <div class="school-timeline-marker school-timeline-marker-primary"></div>
                            <div class="school-timeline-content">
                                <h4 class="school-timeline-title">School Approved</h4>
                                <p class="school-timeline-text">School registration was approved by administrators.</p>
                                <time class="school-timeline-date"><?= date('j F Y', strtotime($school['approval_date'])) ?></time>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($teams)): ?>
                            <?php foreach (array_slice($teams, 0, 5) as $team): ?>
                            <div class="school-timeline-item">
                                <div class="school-timeline-marker school-timeline-marker-info"></div>
                                <div class="school-timeline-content">
                                    <h4 class="school-timeline-title">Team Registered</h4>
                                    <p class="school-timeline-text">
                                        Team "<?= htmlspecialchars($team['name']) ?>" was registered for <?= htmlspecialchars($team['category_name'] ?? 'Unknown Category') ?>.
                                    </p>
                                    <time class="school-timeline-date"><?= date('j F Y', strtotime($team['created_at'])) ?></time>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (count($stats['document_requirements']) > 0): ?>
                        <div class="school-timeline-item">
                            <div class="school-timeline-marker school-timeline-marker-warning"></div>
                            <div class="school-timeline-content">
                                <h4 class="school-timeline-title">Document Requirements</h4>
                                <div class="school-timeline-text">
                                    <p>Missing required documents:</p>
                                    <ul class="timeline-requirements-list">
                                        <?php foreach ($stats['document_requirements'] as $requirement): ?>
                                            <li><?= htmlspecialchars($requirement) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <time class="school-timeline-date">Current</time>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
        </div>
    </div>
</div>

<script>
// School management functions with proper base URL
const baseUrl = '<?= $baseUrl ?>';

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.school-nav-link');
    const tabContents = document.querySelectorAll('.school-tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});

function approveSchool(schoolId) {
    if (confirm('Are you sure you want to approve this school?')) {
        fetch(`${baseUrl}/admin/schools/${schoolId}/approve`, {
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
        fetch(`${baseUrl}/admin/schools/${schoolId}/reject`, {
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
        fetch(`${baseUrl}/admin/schools/${schoolId}/suspend`, {
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
    window.open(`${baseUrl}/admin/schools/${schoolId}/export`, '_blank');
}

function printSchoolProfile(schoolId) {
    window.print();
}

function sendEmail(email) {
    window.location.href = `mailto:${email}`;
}

function deleteContact(contactId) {
    if (confirm('Are you sure you want to delete this contact?')) {
        fetch(`${baseUrl}/admin/contacts/${contactId}`, {
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