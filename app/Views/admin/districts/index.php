<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<div class="admin-content">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <h3><?= $stats['total'] ?? 0 ?></h3>
                    <p>Total Districts</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-map"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <h3><?= $stats['total_schools'] ?? 0 ?></h3>
                    <p>Total Schools</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-school"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <h3><?= $stats['total_teams'] ?? 0 ?></h3>
                    <p>Total Teams</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <h3><?= $stats['total_participants'] ?? 0 ?></h3>
                    <p>Total Participants</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Header -->
    <div class="data-table-header">
        <div class="data-table-title">
            <h2>District Management</h2>
            <p>Manage geographic districts and their coordinators across all provinces</p>
        </div>
        <div class="data-table-actions">
            <a href="<?= $baseUrl ?>/admin/districts/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create District
            </a>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="actionsDropdown" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-h"></i> More
                </button>
                <div class="dropdown-menu" aria-labelledby="actionsDropdown">
                    <a class="dropdown-item" href="#" onclick="exportDistricts()">
                        <i class="fas fa-download"></i> Export Districts
                    </a>
                    <a class="dropdown-item" href="#" onclick="showDistrictMap()">
                        <i class="fas fa-globe"></i> View Map
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= $baseUrl ?>/admin/schools">
                        <i class="fas fa-school"></i> Manage Schools
                    </a>
                    <a class="dropdown-item" href="<?= $baseUrl ?>/admin/districts/reports">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="admin-filters">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-filter"></i> Search & Filter Districts
            </h5>
        </div>
        <div class="card-body">
            <form id="districtFilterForm" method="GET" action="<?= $baseUrl ?>/admin/districts">
                <div class="admin-form-row">
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
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Filter
                        </button>
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
        <div class="province-section">
            <div class="province-header">
                <div>
                    <h5 class="province-title">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($province) ?>
                    </h5>
                    <span class="province-badge"><?= count($provinceDistricts) ?> districts</span>
                </div>
                <button type="button" class="toggle-btn" onclick="toggleProvinceDistricts('<?= htmlspecialchars($province) ?>')">
                    <i class="fas fa-chevron-down" id="chevron-<?= htmlspecialchars($province) ?>"></i>
                </button>
            </div>
            <div class="data-table-container" id="districts-<?= htmlspecialchars($province) ?>">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>District</th>
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
                                <div class="district-info">
                                    <a href="<?= $baseUrl ?>/admin/districts/<?= $district['id'] ?>" class="district-name">
                                        <?= htmlspecialchars($district['name']) ?>
                                    </a>
                                    <?php if (!empty($district['region'])): ?>
                                        <div class="district-region"><?= htmlspecialchars($district['region']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="district-code"><?= htmlspecialchars($district['code']) ?></span>
                            </td>
                            <td>
                                <?php if ($district['coordinator_first_name'] || $district['coordinator_last_name']): ?>
                                    <div class="coordinator-info">
                                        <div class="coordinator-name">
                                            <?= htmlspecialchars(trim($district['coordinator_first_name'] . ' ' . $district['coordinator_last_name'])) ?>
                                        </div>
                                        <a href="mailto:<?= htmlspecialchars($district['coordinator_email']) ?>" class="coordinator-email">
                                            <?= htmlspecialchars($district['coordinator_email']) ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="no-coordinator">No coordinator assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="count-badge schools"><?= $district['school_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <span class="count-badge teams"><?= $district['team_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <span class="count-badge participants"><?= $district['participant_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <span class="status-badge <?= $district['status'] ?? 'inactive' ?>">
                                    <?= ucfirst($district['status'] ?? 'inactive') ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= $baseUrl ?>/admin/districts/<?= $district['id'] ?>" 
                                       class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= $baseUrl ?>/admin/districts/<?= $district['id'] ?>/edit" 
                                       class="action-btn edit" title="Edit District">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button type="button" class="action-btn more dropdown-toggle" 
                                                data-toggle="dropdown" title="More Actions">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="<?= $baseUrl ?>/admin/schools?district_id=<?= $district['id'] ?>">
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
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-map empty-state-icon"></i>
            <h5>No districts found</h5>
            <p>No districts match your current filters or none have been created yet.</p>
            <a href="<?= $baseUrl ?>/admin/districts/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create First District
            </a>
        </div>
    <?php endif; ?>

    <!-- Province Statistics -->
    <?php if (!empty($stats['by_province'])): ?>
    <div class="province-stats">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-chart-pie"></i> Statistics by Province
            </h5>
        </div>
        <div class="card-body">
            <div class="admin-form-row">
                <?php foreach ($stats['by_province'] as $province => $count): ?>
                <div class="form-group">
                    <div class="province-stat-item">
                        <span class="province-stat-name"><?= htmlspecialchars($province) ?></span>
                        <span class="province-stat-badge"><?= $count ?> districts</span>
                    </div>
                    <div class="province-progress">
                        <div class="province-progress-bar" 
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
    window.open('<?= $baseUrl ?>/admin/districts/export?' + params.toString(), '_blank');
}

function exportDistrict(districtId) {
    window.open(`<?= $baseUrl ?>/admin/districts/${districtId}/export?format=csv`, '_blank');
}

// Delete district
function deleteDistrict(districtId) {
    if (confirm('Are you sure you want to delete this district? This action cannot be undone.')) {
        fetch(`<?= $baseUrl ?>/admin/districts/${districtId}`, {
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

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>