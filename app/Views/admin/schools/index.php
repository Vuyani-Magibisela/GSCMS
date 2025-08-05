<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- School Management Dashboard -->
<div class="school-management-container">
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
                    <i class="fas fa-school"></i>
                    School Management
                </h1>
                <p class="page-subtitle">Manage school registrations, approvals, and information across all districts.</p>
            </div>
            <div class="col-auto">
                <div class="btn-group">
                    <a href="/admin/schools/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Register New School
                    </a>
                    <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" onclick="exportSchools()">
                            <i class="fas fa-download"></i> Export Schools
                        </a>
                        <a class="dropdown-item" href="#" onclick="bulkImportSchools()">
                            <i class="fas fa-upload"></i> Bulk Import
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="/admin/districts">
                            <i class="fas fa-map"></i> Manage Districts
                        </a>
                        <a class="dropdown-item" href="/admin/schools/reports">
                            <i class="fas fa-chart-bar"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title mb-0"><?= $stats['total'] ?? 0 ?></h3>
                            <p class="card-text">Total Schools</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-school fa-2x opacity-75"></i>
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
                            <h3 class="card-title mb-0"><?= $stats['by_status']['active'] ?? 0 ?></h3>
                            <p class="card-text">Active Schools</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                            <h3 class="card-title mb-0"><?= $attention['pending_approvals'] ?? 0 ?></h3>
                            <p class="card-text">Pending Approvals</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
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
                            <h3 class="card-title mb-0"><?= $attention['no_teams'] ?? 0 ?></h3>
                            <p class="card-text">Schools Without Teams</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts for Attention Items -->
    <?php if (!empty($attention) && array_sum($attention) > 0): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <h5><i class="fas fa-info-circle"></i> Items Requiring Attention</h5>
        <ul class="mb-0">
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
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Advanced Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter"></i> Search & Filter Schools
                <button class="btn btn-sm btn-outline-secondary float-right" type="button" data-toggle="collapse" data-target="#advancedFilters">
                    <i class="fas fa-cog"></i> Advanced Filters
                </button>
            </h5>
        </div>
        <div class="card-body">
            <form id="schoolFilterForm" method="GET" action="/admin/schools">
                <div class="row">
                    <div class="col-md-4">
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
                    </div>
                    <div class="col-md-2">
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
                    </div>
                    <div class="col-md-2">
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
                    </div>
                    <div class="col-md-2">
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
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters (Collapsible) -->
                <div class="collapse" id="advancedFilters">
                    <hr>
                    <div class="row">
                        <div class="col-md-3">
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
                        </div>
                        <div class="col-md-3">
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
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="has_teams" class="form-label">Team Registration</label>
                                <select id="has_teams" name="has_teams" class="form-control">
                                    <option value="">All Schools</option>
                                    <option value="yes" <?= ($currentFilters['has_teams'] ?? '') === 'yes' ? 'selected' : '' ?>>Has Teams</option>
                                    <option value="no" <?= ($currentFilters['has_teams'] ?? '') === 'no' ? 'selected' : '' ?>>No Teams</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Registration Date</label>
                                <div class="row">
                                    <div class="col">
                                        <input type="date" name="registered_from" class="form-control form-control-sm" 
                                               placeholder="From" value="<?= htmlspecialchars($currentFilters['registered_from'] ?? '') ?>">
                                    </div>
                                    <div class="col">
                                        <input type="date" name="registered_to" class="form-control form-control-sm" 
                                               placeholder="To" value="<?= htmlspecialchars($currentFilters['registered_to'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear All Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-4" id="bulkActionsCard" style="display: none;">
        <div class="card-body">
            <form id="bulkActionForm">
                <div class="row align-items-center">
                    <div class="col">
                        <span class="font-weight-bold">
                            <span id="selectedCount">0</span> schools selected
                        </span>
                    </div>
                    <div class="col-auto">
                        <div class="form-group mb-0 mr-3">
                            <select id="bulkAction" name="action" class="form-control">
                                <option value="">Select Action</option>
                                <option value="approve">Approve Schools</option>
                                <option value="suspend">Suspend Schools</option>
                                <option value="archive">Archive Schools</option>
                                <option value="export">Export Selected</option>
                                <option value="send_email">Send Email</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary" onclick="executeBulkAction()">
                            Execute
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" onclick="clearSelection()">
                            Clear Selection
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Schools Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">
                        Schools List
                        <span class="badge badge-secondary ml-2"><?= count($schools) ?> schools</span>
                    </h5>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleView('table')" id="tableViewBtn">
                            <i class="fas fa-table"></i> Table
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleView('cards')" id="cardsViewBtn">
                            <i class="fas fa-th-large"></i> Cards
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Table View -->
            <div id="tableView" class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
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
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-search fa-2x mb-2"></i><br>
                                    No schools found matching your criteria.
                                    <br><br>
                                    <a href="/admin/schools/create" class="btn btn-primary">
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
                                    <div class="d-flex align-items-center">
                                        <?php if ($school['logo_path']): ?>
                                            <img src="<?= htmlspecialchars($school['logo_path']) ?>" alt="School Logo" class="school-logo-thumb mr-2">
                                        <?php endif; ?>
                                        <div>
                                            <a href="/admin/schools/<?= $school['id'] ?>" class="font-weight-bold text-decoration-none">
                                                <?= htmlspecialchars($school['name']) ?>
                                            </a>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($school->getSchoolTypeLabel()) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info" title="Click to filter by district" style="cursor: pointer;" onclick="filterByDistrict(<?= $school['district_id'] ?>)">
                                        <?= htmlspecialchars($school['district_name'] ?? 'Unknown') ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($school['province']) ?></small>
                                </td>
                                <td>
                                    <?php if ($school['coordinator_first_name'] || $school['coordinator_last_name']): ?>
                                        <div>
                                            <strong><?= htmlspecialchars(trim($school['coordinator_first_name'] . ' ' . $school['coordinator_last_name'])) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <a href="mailto:<?= htmlspecialchars($school['coordinator_email']) ?>"><?= htmlspecialchars($school['coordinator_email']) ?></a>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No coordinator assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $school->getStatusInfo()['color'] ?>">
                                        <?= htmlspecialchars($school->getStatusInfo()['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" title="Total teams registered">
                                        <?= $school['team_count'] ?? 0 ?> teams
                                    </span>
                                    <?php if ($school['team_count'] > 0): ?>
                                        <br>
                                        <small class="text-muted"><?= $school['participant_count'] ?? 0 ?> participants</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('j M Y', strtotime($school['registration_date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/schools/<?= $school['id'] ?>" class="btn btn-outline-primary btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin/schools/<?= $school['id'] ?>/edit" class="btn btn-outline-secondary btn-sm" title="Edit School">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" title="More Actions">
                                            <span class="sr-only">Toggle Dropdown</span>
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
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cards View -->
            <div id="cardsView" class="p-3" style="display: none;">
                <?php if (empty($schools)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-search fa-3x mb-3"></i><br>
                            <h5>No schools found</h5>
                            <p>No schools match your current filters.</p>
                            <a href="/admin/schools/create" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Register First School
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($schools as $school): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card school-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input school-checkbox" value="<?= $school['id'] ?>" onchange="updateBulkActions()">
                                        </div>
                                        <span class="badge badge-<?= $school->getStatusInfo()['color'] ?>">
                                            <?= htmlspecialchars($school->getStatusInfo()['label']) ?>
                                        </span>
                                    </div>
                                    
                                    <h6 class="card-title">
                                        <a href="/admin/schools/<?= $school['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($school['name']) ?>
                                        </a>
                                    </h6>
                                    
                                    <p class="card-text small text-muted mb-2">
                                        <?= htmlspecialchars($school->getSchoolTypeLabel()) ?> "
                                        <?= htmlspecialchars($school['district_name'] ?? 'Unknown District') ?>
                                    </p>
                                    
                                    <p class="card-text small">
                                        <i class="fas fa-envelope text-muted mr-1"></i>
                                        <a href="mailto:<?= htmlspecialchars($school['email']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($school['email']) ?>
                                        </a>
                                    </p>
                                    
                                    <p class="card-text small">
                                        <i class="fas fa-phone text-muted mr-1"></i>
                                        <a href="tel:<?= htmlspecialchars($school['phone']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($school['phone']) ?>
                                        </a>
                                    </p>
                                    
                                    <div class="row text-center mt-3">
                                        <div class="col">
                                            <small class="text-muted">Teams</small><br>
                                            <strong><?= $school['team_count'] ?? 0 ?></strong>
                                        </div>
                                        <div class="col">
                                            <small class="text-muted">Participants</small><br>
                                            <strong><?= $school['participant_count'] ?? 0 ?></strong>
                                        </div>
                                        <div class="col">
                                            <small class="text-muted">Learners</small><br>
                                            <strong><?= number_format($school['total_learners']) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="/admin/schools/<?= $school['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/admin/schools/<?= $school['id'] ?>/edit" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </div>
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
    <nav aria-label="Schools pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- This would be populated by pagination logic -->
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
    
    fetch('/admin/schools/bulk-action', {
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
    fetch(`/admin/schools/${schoolId}/status`, {
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
    window.open('/admin/schools/export?' + params.toString(), '_blank');
}

function exportSelectedSchools() {
    if (selectedSchools.length === 0) {
        alert('Please select schools to export.');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/schools/export';
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
    window.open(`/admin/schools/${schoolId}/export`, '_blank');
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

<style>
.school-management-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.opacity-75 {
    opacity: 0.75;
}

.school-logo-thumb {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 4px;
}

.school-card {
    transition: box-shadow 0.15s ease-in-out;
}

.school-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-label {
    font-weight: 600;
    margin-bottom: 5px;
}

.badge {
    font-size: 0.75rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
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

@media (max-width: 768px) {
    .school-management-container {
        padding: 10px;
    }
    
    .card-body.p-0 {
        padding: 0 !important;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.125rem 0.25rem;
        font-size: 0.625rem;
    }
}

/* Custom styles for better mobile experience */
@media (max-width: 576px) {
    .row.align-items-center .col-auto {
        margin-top: 10px;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-bottom: 5px;
        border-radius: 0.25rem !important;
    }
}
</style>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>