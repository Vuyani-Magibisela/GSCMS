<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- District Management Dashboard -->
<div class="district-management-container">
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
                    <i class="fas fa-map"></i>
                    District Management
                </h1>
                <p class="page-subtitle">Manage geographic districts and their coordinators across all provinces.</p>
            </div>
            <div class="col-auto">
                <div class="btn-group">
                    <a href="/admin/districts/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New District
                    </a>
                    <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" onclick="exportDistricts()">
                            <i class="fas fa-download"></i> Export Districts
                        </a>
                        <a class="dropdown-item" href="#" onclick="showDistrictMap()">
                            <i class="fas fa-globe"></i> View Map
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="/admin/schools">
                            <i class="fas fa-school"></i> Manage Schools
                        </a>
                        <a class="dropdown-item" href="/admin/districts/reports">
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
                            <p class="card-text">Total Districts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-map fa-2x opacity-75"></i>
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
                            <h3 class="card-title mb-0"><?= $stats['total_schools'] ?? 0 ?></h3>
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
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title mb-0"><?= $stats['total_teams'] ?? 0 ?></h3>
                            <p class="card-text">Total Teams</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-75"></i>
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
                            <h3 class="card-title mb-0"><?= $stats['total_participants'] ?? 0 ?></h3>
                            <p class="card-text">Total Participants</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter"></i> Search & Filter Districts
            </h5>
        </div>
        <div class="card-body">
            <form id="districtFilterForm" method="GET" action="/admin/districts">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search" class="form-label">Search Districts</label>
                            <div class="input-group">
                                <input type="text" id="search" name="search" class="form-control" 
                                       placeholder="District name or code" 
                                       value="<?= htmlspecialchars($currentFilters['search'] ?? '') ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
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
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Districts by Province -->
    <?php if (!empty($districts)): ?>
        <?php 
        $districtsByProvince = [];
        foreach ($districts as $district) {
            $districtsByProvince[$district['province']][] = $district;
        }
        ?>

        <?php foreach ($districtsByProvince as $province => $provinceDistricts): ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($province) ?>
                            <span class="badge badge-secondary ml-2"><?= count($provinceDistricts) ?> districts</span>
                        </h5>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleProvinceDistricts('<?= htmlspecialchars($province) ?>')">
                            <i class="fas fa-chevron-down" id="chevron-<?= htmlspecialchars($province) ?>"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0" id="districts-<?= htmlspecialchars($province) ?>">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>District Name</th>
                                <th>Code</th>
                                <th>Coordinator</th>
                                <th>Schools</th>
                                <th>Teams</th>
                                <th>Participants</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($provinceDistricts as $district): ?>
                            <tr>
                                <td>
                                    <div>
                                        <a href="/admin/districts/<?= $district['id'] ?>" class="font-weight-bold text-decoration-none">
                                            <?= htmlspecialchars($district['name']) ?>
                                        </a>
                                        <?php if ($district['region']): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($district['region']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($district['code']) ?></code>
                                </td>
                                <td>
                                    <?php if ($district['coordinator_first_name'] || $district['coordinator_last_name']): ?>
                                        <div>
                                            <strong><?= htmlspecialchars(trim($district['coordinator_first_name'] . ' ' . $district['coordinator_last_name'])) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <a href="mailto:<?= htmlspecialchars($district['coordinator_email']) ?>"><?= htmlspecialchars($district['coordinator_email']) ?></a>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No coordinator assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= $district['school_count'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?= $district['team_count'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= $district['participant_count'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $district['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($district['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/districts/<?= $district['id'] ?>" class="btn btn-outline-primary btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin/districts/<?= $district['id'] ?>/edit" class="btn btn-outline-secondary btn-sm" title="Edit District">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" title="More Actions">
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="/admin/schools?district_id=<?= $district['id'] ?>">
                                                <i class="fas fa-school"></i> View Schools
                                            </a>
                                            <?php if ($district['coordinator_email']): ?>
                                            <a class="dropdown-item" href="mailto:<?= htmlspecialchars($district['coordinator_email']) ?>">
                                                <i class="fas fa-envelope"></i> Email Coordinator
                                            </a>
                                            <?php endif; ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#" onclick="exportDistrict(<?= $district['id'] ?>)">
                                                <i class="fas fa-download"></i> Export Data
                                            </a>
                                            <?php if (($district['school_count'] ?? 0) === 0): ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteDistrict(<?= $district['id'] ?>)">
                                                <i class="fas fa-trash"></i> Delete District
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-map fa-3x mb-3"></i><br>
                    <h5>No districts found</h5>
                    <p>No districts match your current filters or none have been created yet.</p>
                    <a href="/admin/districts/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First District
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Province Statistics -->
    <?php if (!empty($stats['by_province'])): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-pie"></i> Statistics by Province
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($stats['by_province'] as $province => $count): ?>
                <div class="col-md-4 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold"><?= htmlspecialchars($province) ?></span>
                        <span class="badge badge-primary"><?= $count ?> districts</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= ($count / max($stats['by_province'])) * 100 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Toggle province districts visibility
function toggleProvinceDistricts(province) {
    const districtsDiv = document.getElementById('districts-' + province);
    const chevron = document.getElementById('chevron-' + province);
    
    if (districtsDiv.style.display === 'none') {
        districtsDiv.style.display = 'block';
        chevron.classList.remove('fa-chevron-right');
        chevron.classList.add('fa-chevron-down');
    } else {
        districtsDiv.style.display = 'none';
        chevron.classList.remove('fa-chevron-down');
        chevron.classList.add('fa-chevron-right');
    }
}

// Auto-submit form on filter changes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#districtFilterForm select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('districtFilterForm').submit();
        });
    });
});

// Export functions
function exportDistricts() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'csv');
    window.open('/admin/districts/export?' + params.toString(), '_blank');
}

function exportDistrict(districtId) {
    window.open(`/admin/districts/${districtId}/export?format=csv`, '_blank');
}

// Delete district
function deleteDistrict(districtId) {
    if (confirm('Are you sure you want to delete this district? This action cannot be undone.')) {
        fetch(`/admin/districts/${districtId}`, {
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

// Show district map (placeholder)
function showDistrictMap() {
    alert('District mapping functionality would be implemented here with GIS integration.');
}
</script>

<style>
.district-management-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.opacity-75 {
    opacity: 0.75;
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

@media (max-width: 768px) {
    .district-management-container {
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
</style>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>