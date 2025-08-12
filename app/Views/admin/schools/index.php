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
    <div class="schools-header">
        <div class="schools-title-section">
            <h1>
                <i class="fas fa-school"></i>
                School Management
            </h1>
            <p>Manage school registrations, approvals, and information across all districts</p>
        </div>
        <div class="schools-actions">
            <a href="<?= $baseUrl ?>/admin/schools/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Register New School
            </a>
            <div class="dropdown">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-h"></i> More Actions
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" onclick="exportSchools()">
                        <i class="fas fa-download"></i> Export Schools
                    </a>
                    <a class="dropdown-item" href="#" onclick="bulkImportSchools()">
                        <i class="fas fa-upload"></i> Bulk Import
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= $baseUrl ?>/admin/districts">
                        <i class="fas fa-map"></i> Manage Districts
                    </a>
                    <a class="dropdown-item" href="<?= $baseUrl ?>/admin/schools/reports">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="schools-stats-grid">
        <div class="schools-stat-card primary">
            <div class="schools-stat-content">
                <div class="schools-stat-info">
                    <h3><?= $stats['total'] ?? 0 ?></h3>
                    <p>Total Schools</p>
                </div>
                <div class="schools-stat-icon">
                    <i class="fas fa-school"></i>
                </div>
            </div>
        </div>
        
        <div class="schools-stat-card success">
            <div class="schools-stat-content">
                <div class="schools-stat-info">
                    <h3><?= $stats['by_status']['active'] ?? 0 ?></h3>
                    <p>Active Schools</p>
                </div>
                <div class="schools-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="schools-stat-card warning">
            <div class="schools-stat-content">
                <div class="schools-stat-info">
                    <h3><?= $attention['pending_approvals'] ?? 0 ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="schools-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="schools-stat-card info">
            <div class="schools-stat-content">
                <div class="schools-stat-info">
                    <h3><?= $attention['no_teams'] ?? 0 ?></h3>
                    <p>Schools Without Teams</p>
                </div>
                <div class="schools-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts for Attention Items -->
    <?php if (!empty($attention) && array_sum($attention) > 0): ?>
    <div class="schools-attention-alert">
        <h5><i class="fas fa-info-circle"></i> Items Requiring Attention</h5>
        <ul>
            <?php if ($attention['pending_approvals'] > 0): ?>
                <li><strong><?= $attention['pending_approvals'] ?></strong> schools are awaiting approval</li>
            <?php endif; ?>
            <?php if ($attention['missing_coordinator'] > 0): ?>
                <li><strong><?= $attention['missing_coordinator'] ?></strong> active schools have no assigned coordinator</li>
            <?php endif; ?>
            <?php if ($attention['no_teams'] > 0): ?>
                <li><strong><?= $attention['no_teams'] ?></strong> active schools have not registered any teams</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Advanced Filters -->
    <div class="schools-filters">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-filter"></i> Search & Filter Schools
            </h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#advancedFilters">
                <i class="fas fa-cog"></i> Advanced Filters
            </button>
        </div>
        <div class="card-body">
            <form id="schoolFilterForm" method="GET" action="<?= $baseUrl ?>/admin/schools">
                <div class="schools-filter-row">
                    <div class="form-group">
                        <label for="search" class="form-label">Search Schools</label>
                        <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control" 
                                   placeholder="School name, email, or phone" 
                                   value="<?= htmlspecialchars($currentFilters['search'] ?? '') ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <?php if (isset($statuses) && is_array($statuses)): ?>
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= ($currentFilters['status'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="district_id" class="form-label">District</label>
                        <select id="district_id" name="district_id" class="form-control">
                            <option value="">All Districts</option>
                            <?php if (isset($districts) && is_array($districts)): ?>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= htmlspecialchars($district['id']) ?>" <?= ($currentFilters['district_id'] ?? '') == $district['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($district['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="school_type" class="form-label">School Type</label>
                        <select id="school_type" name="school_type" class="form-control">
                            <option value="">All Types</option>
                            <?php if (isset($schoolTypes) && is_array($schoolTypes)): ?>
                                <?php foreach ($schoolTypes as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= ($currentFilters['school_type'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters (Collapsible) -->
                <div class="collapse" id="advancedFilters">
                    <div class="schools-filter-advanced">
                        <div class="form-group">
                            <label for="province" class="form-label">Province</label>
                            <select id="province" name="province" class="form-control">
                                <option value="">All Provinces</option>
                                <?php if (isset($provinces) && is_array($provinces)): ?>
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?= htmlspecialchars($province) ?>" <?= ($currentFilters['province'] ?? '') === $province ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($province) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quintile" class="form-label">Quintile</label>
                            <select id="quintile" name="quintile" class="form-control">
                                <option value="">All Quintiles</option>
                                <?php if (isset($quintiles) && is_array($quintiles)): ?>
                                    <?php foreach ($quintiles as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key) ?>" <?= ($currentFilters['quintile'] ?? '') == $key ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="has_teams" class="form-label">Team Registration</label>
                            <select id="has_teams" name="has_teams" class="form-control">
                                <option value="">All Schools</option>
                                <option value="yes" <?= ($currentFilters['has_teams'] ?? '') === 'yes' ? 'selected' : '' ?>>Has Teams</option>
                                <option value="no" <?= ($currentFilters['has_teams'] ?? '') === 'no' ? 'selected' : '' ?>>No Teams</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Registration Date</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-2);">
                                <input type="date" name="registered_from" class="form-control" 
                                       placeholder="From" value="<?= htmlspecialchars($currentFilters['registered_from'] ?? '') ?>">
                                <input type="date" name="registered_to" class="form-control" 
                                       placeholder="To" value="<?= htmlspecialchars($currentFilters['registered_to'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary btn-block" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear All Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="schools-bulk-actions" id="bulkActionsCard" style="display: none;">
        <form id="bulkActionForm">
            <div class="schools-bulk-info">
                <span id="selectedCount">0</span> schools selected
            </div>
            <div class="schools-bulk-controls">
                <select id="bulkAction" name="action" class="form-control">
                    <option value="">Select Action</option>
                    <option value="approve">Approve Schools</option>
                    <option value="suspend">Suspend Schools</option>
                    <option value="archive">Archive Schools</option>
                    <option value="export">Export Selected</option>
                    <option value="send_email">Send Email</option>
                </select>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">
                    Execute
                </button>
                <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                    Clear Selection
                </button>
            </div>
        </form>
    </div>

    <!-- Schools Table -->
    <div class="schools-table-container">
        <div class="schools-table-header">
            <div class="schools-table-title">
                <h5>Schools List <span class="badge"><?= count($schools) ?> schools</span></h5>
            </div>
            <div class="schools-view-toggle">
                <button type="button" class="schools-view-btn active" onclick="toggleView('table')" id="tableViewBtn">
                    <i class="fas fa-table"></i> Table
                </button>
                <button type="button" class="schools-view-btn" onclick="toggleView('cards')" id="cardsViewBtn">
                    <i class="fas fa-th-large"></i> Cards
                </button>
            </div>
        </div>
        <div>
            <!-- Table View -->
            <div id="tableView" class="table-responsive">
                <table class="schools-table">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($currentFilters, ['sort_by' => 'name', 'sort_order' => ($currentFilters['sort_by'] ?? '') === 'name' && ($currentFilters['sort_order'] ?? '') === 'asc' ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-dark">
                                    School Name 
                                    <?php if (($currentFilters['sort_by'] ?? '') === 'name'): ?>
                                        <i class="fas fa-sort-<?= ($currentFilters['sort_order'] ?? '') === 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($currentFilters, ['sort_by' => 'district', 'sort_order' => ($currentFilters['sort_by'] ?? '') === 'district' && ($currentFilters['sort_order'] ?? '') === 'asc' ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-dark">
                                    District
                                    <?php if (($currentFilters['sort_by'] ?? '') === 'district'): ?>
                                        <i class="fas fa-sort-<?= ($currentFilters['sort_order'] ?? '') === 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Contact Person</th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($currentFilters, ['sort_by' => 'status', 'sort_order' => ($currentFilters['sort_by'] ?? '') === 'status' && ($currentFilters['sort_order'] ?? '') === 'asc' ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-dark">
                                    Status
                                    <?php if (($currentFilters['sort_by'] ?? '') === 'status'): ?>
                                        <i class="fas fa-sort-<?= ($currentFilters['sort_order'] ?? '') === 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Teams</th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($currentFilters, ['sort_by' => 'registration_date', 'sort_order' => ($currentFilters['sort_by'] ?? '') === 'registration_date' && ($currentFilters['sort_order'] ?? '') === 'asc' ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-dark">
                                    Registered
                                    <?php if (($currentFilters['sort_by'] ?? '') === 'registration_date'): ?>
                                        <i class="fas fa-sort-<?= ($currentFilters['sort_order'] ?? '') === 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schools)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="schools-empty-state">
                                    <i class="fas fa-search schools-empty-icon"></i>
                                    <h5>No schools found</h5>
                                    <p>No schools match your current filters or none have been registered yet.</p>
                                    <a href="<?= $baseUrl ?>/admin/schools/create" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Register First School
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($schools as $school): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="school-checkbox" value="<?= $school['id'] ?>" onchange="updateBulkActions()">
                                </td>
                                <td>
                                    <div class="school-info">
                                        <?php if ($school['logo_path']): ?>
                                            <img src="<?= htmlspecialchars($school['logo_path']) ?>" alt="School Logo" class="school-logo-thumb">
                                        <?php endif; ?>
                                        <div class="school-details">
                                            <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>" class="school-name">
                                                <?= htmlspecialchars($school['name']) ?>
                                            </a>
                                            <div class="school-type"><?= htmlspecialchars(ucfirst($school['school_type'])) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="district-info">
                                        <span class="district-badge" title="Click to filter by district" onclick="filterByDistrict(<?= $school['district_id'] ?>)">
                                            <?= htmlspecialchars($school['district_name'] ?? 'Unknown') ?>
                                        </span>
                                        <div class="province-info"><?= htmlspecialchars($school['province']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($school['coordinator_first_name'] || $school['coordinator_last_name']): ?>
                                        <div class="coordinator-info">
                                            <div class="coordinator-name"><?= htmlspecialchars(trim($school['coordinator_first_name'] . ' ' . $school['coordinator_last_name'])) ?></div>
                                            <a href="mailto:<?= htmlspecialchars($school['coordinator_email']) ?>" class="coordinator-email"><?= htmlspecialchars($school['coordinator_email']) ?></a>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-coordinator">No coordinator assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="schools-status-badge <?= $school['status'] ?? 'inactive' ?>">
                                        <?= htmlspecialchars(ucfirst($school['status'] ?? 'inactive')) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="schools-teams-badge" title="Total teams registered">
                                        <?= $school['team_count'] ?? 0 ?> teams
                                    </span>
                                    <?php if ($school['team_count'] > 0): ?>
                                        <div class="schools-participants-count"><?= $school['participant_count'] ?? 0 ?> participants</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('j M Y', strtotime($school['registration_date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="schools-actions-group">
                                        <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>" class="schools-action-btn view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>/edit" class="schools-action-btn edit" title="Edit School">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <div class="dropdown">
                                            <button type="button" class="schools-action-btn more dropdown-toggle" data-toggle="dropdown" title="More Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="mailto:<?= htmlspecialchars($school['email']) ?>">
                                                    <i class="fas fa-envelope"></i> Send Email
                                                </a>
                                                <a class="dropdown-item" href="tel:<?= htmlspecialchars($school['phone']) ?>">
                                                    <i class="fas fa-phone"></i> Call School
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <?php if ($school['status'] === 'pending'): ?>
                                                <a class="dropdown-item text-success" href="#" onclick="approveSchool(<?= $school['id'] ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                                <?php elseif ($school['status'] === 'active'): ?>
                                                <a class="dropdown-item text-warning" href="#" onclick="suspendSchool(<?= $school['id'] ?>)">
                                                    <i class="fas fa-pause"></i> Suspend
                                                </a>
                                                <?php endif; ?>
                                                <a class="dropdown-item" href="#" onclick="exportSchool(<?= $school['id'] ?>)">
                                                    <i class="fas fa-download"></i> Export Data
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cards View -->
            <div id="cardsView" class="schools-cards-container" style="display: none;">
                <?php if (empty($schools)): ?>
                    <div class="schools-empty-state">
                        <i class="fas fa-search schools-empty-icon"></i>
                        <h5>No schools found</h5>
                        <p>No schools match your current filters.</p>
                        <a href="<?= $baseUrl ?>/admin/schools/create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Register First School
                        </a>
                    </div>
                <?php else: ?>
                    <div class="schools-cards-grid">
                        <?php foreach ($schools as $school): ?>
                        <div class="school-card">
                            <div class="school-card-header">
                                <div>
                                    <input type="checkbox" class="school-checkbox" value="<?= $school['id'] ?>" onchange="updateBulkActions()">
                                </div>
                                <span class="schools-status-badge <?= $school['status'] ?? 'inactive' ?>">
                                    <?= htmlspecialchars(ucfirst($school['status'] ?? 'inactive')) ?>
                                </span>
                            </div>
                            <div class="school-card-body">
                                <h6 class="school-card-title">
                                    <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>">
                                        <?= htmlspecialchars($school['name']) ?>
                                    </a>
                                </h6>
                                
                                <div class="school-card-info">
                                    <div><?= htmlspecialchars(ucfirst($school['school_type'])) ?> • <?= htmlspecialchars($school['district_name'] ?? 'Unknown District') ?></div>
                                </div>
                                
                                <div class="school-card-contact">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?= htmlspecialchars($school['email']) ?>">
                                        <?= htmlspecialchars($school['email']) ?>
                                    </a>
                                </div>
                                
                                <div class="school-card-contact">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?= htmlspecialchars($school['phone']) ?>">
                                        <?= htmlspecialchars($school['phone']) ?>
                                    </a>
                                </div>
                                
                                <div class="school-card-stats">
                                    <div class="school-card-stat">
                                        <div class="school-card-stat-label">Teams</div>
                                        <div class="school-card-stat-value"><?= $school['team_count'] ?? 0 ?></div>
                                    </div>
                                    <div class="school-card-stat">
                                        <div class="school-card-stat-label">Participants</div>
                                        <div class="school-card-stat-value"><?= $school['participant_count'] ?? 0 ?></div>
                                    </div>
                                    <div class="school-card-stat">
                                        <div class="school-card-stat-label">Learners</div>
                                        <div class="school-card-stat-value"><?= number_format($school['total_learners'] ?? 0) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="school-card-footer">
                                <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?= $baseUrl ?>/admin/schools/<?= $school['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if (count($schools) > 0): ?>
    <div class="schools-pagination">
        <nav aria-label="Schools pagination">
            <ul class="pagination">
                <li class="page-item disabled">
                    <span class="page-link">Previous</span>
                </li>
                <li class="page-item active">
                    <span class="page-link">1</span>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Next</span>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Bulk Action Modals -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Bulk Action</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="bulkActionMessage"></p>
                <div class="form-group">
                    <label for="bulkActionReason">Reason (optional):</label>
                    <textarea id="bulkActionReason" class="form-control" rows="2" placeholder="Provide a reason for this action..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmBulkAction()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSchools = [];
let currentView = 'table';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on filter changes
    document.querySelectorAll('#schoolFilterForm select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('schoolFilterForm').submit();
        });
    });
    
    // Initialize view
    toggleView('table');
});

// View toggle functionality
function toggleView(view) {
    currentView = view;
    
    if (view === 'table') {
        document.getElementById('tableView').style.display = 'block';
        document.getElementById('cardsView').style.display = 'none';
        document.getElementById('tableViewBtn').classList.add('active');
        document.getElementById('cardsViewBtn').classList.remove('active');
    } else {
        document.getElementById('tableView').style.display = 'none';
        document.getElementById('cardsView').style.display = 'block';
        document.getElementById('tableViewBtn').classList.remove('active');
        document.getElementById('cardsViewBtn').classList.add('active');
    }
    
    // Store preference
    localStorage.setItem('schoolsViewPreference', view);
}

// Selection management
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.school-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.school-checkbox:checked');
    selectedSchools = Array.from(checkboxes).map(cb => cb.value);
    
    document.getElementById('selectedCount').textContent = selectedSchools.length;
    
    if (selectedSchools.length > 0) {
        document.getElementById('bulkActionsCard').style.display = 'block';
    } else {
        document.getElementById('bulkActionsCard').style.display = 'none';
    }
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.school-checkbox');
    const selectAll = document.getElementById('selectAll');
    selectAll.indeterminate = selectedSchools.length > 0 && selectedSchools.length < allCheckboxes.length;
    selectAll.checked = selectedSchools.length === allCheckboxes.length && allCheckboxes.length > 0;
}

function clearSelection() {
    document.querySelectorAll('.school-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    
    if (!action) {
        alert('Please select an action to perform.');
        return;
    }
    
    if (selectedSchools.length === 0) {
        alert('Please select at least one school.');
        return;
    }
    
    let message = '';
    switch (action) {
        case 'approve':
            message = `Are you sure you want to approve ${selectedSchools.length} schools?`;
            break;
        case 'suspend':
            message = `Are you sure you want to suspend ${selectedSchools.length} schools?`;
            break;
        case 'archive':
            message = `Are you sure you want to archive ${selectedSchools.length} schools?`;
            break;
        case 'export':
            exportSelectedSchools();
            return;
        case 'send_email':
            sendBulkEmail();
            return;
        default:
            message = `Are you sure you want to perform this action on ${selectedSchools.length} schools?`;
    }
    
    document.getElementById('bulkActionMessage').textContent = message;
    $('#bulkActionModal').modal('show');
}

function confirmBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const reason = document.getElementById('bulkActionReason').value;
    
    fetch('<?= $baseUrl ?>/admin/schools/bulk-action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            action: action,
            school_ids: selectedSchools,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#bulkActionModal').modal('hide');
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

// Individual actions
function approveSchool(schoolId) {
    if (confirm('Are you sure you want to approve this school?')) {
        updateSchoolStatus(schoolId, 'active');
    }
}

function suspendSchool(schoolId) {
    const reason = prompt('Please provide a reason for suspension:');
    if (reason) {
        updateSchoolStatus(schoolId, 'suspended', reason);
    }
}

function updateSchoolStatus(schoolId, status, reason = null) {
    fetch(`<?= $baseUrl ?>/admin/schools/${schoolId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            status: status,
            reason: reason
        })
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

// Filter helpers
function filterByDistrict(districtId) {
    const form = document.getElementById('schoolFilterForm');
    form.querySelector('#district_id').value = districtId;
    form.submit();
}

function clearFilters() {
    const form = document.getElementById('schoolFilterForm');
    form.querySelectorAll('input, select').forEach(input => {
        if (input.type === 'checkbox') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });
    form.submit();
}

// Export functions
function exportSchools() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'csv');
    window.open('<?= $baseUrl ?>/admin/schools/export?' + params.toString(), '_blank');
}

function exportSelectedSchools() {
    if (selectedSchools.length === 0) {
        alert('Please select schools to export.');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $baseUrl ?>/admin/schools/export';
    form.target = '_blank';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfInput);
    
    selectedSchools.forEach(schoolId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'school_ids[]';
        input.value = schoolId;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function exportSchool(schoolId) {
    window.open(`<?= $baseUrl ?>/admin/schools/${schoolId}/export`, '_blank');
}

// Communication functions
function sendBulkEmail() {
    if (selectedSchools.length === 0) {
        alert('Please select schools to email.');
        return;
    }
    
    // This would open a bulk email composition interface
    const emails = Array.from(document.querySelectorAll('.school-checkbox:checked'))
        .map(cb => {
            const row = cb.closest('tr');
            return row ? row.querySelector('a[href^="mailto:"]')?.getAttribute('href')?.replace('mailto:', '') : null;
        })
        .filter(email => email);
    
    window.location.href = 'mailto:' + emails.join(',');
}

function bulkImportSchools() {
    // This would open a bulk import interface
    alert('Bulk import functionality would be implemented here.');
}

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('schoolsViewPreference') || 'table';
    toggleView(savedView);
});
</script>


<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>