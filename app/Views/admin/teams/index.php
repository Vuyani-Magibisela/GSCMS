<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<div class="teams-management-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <i class="fas fa-users"></i> Team Management
                </h1>
                <p class="page-subtitle">
                    Manage competition teams, participants, and registrations
                </p>
            </div>
            <div class="col-auto">
                <a href="<?= $baseUrl ?>/admin/teams/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Team
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row stats-cards mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $stats['total_teams'] ?></h3>
                        <p>Total Teams</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate text-success"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $stats['total_participants'] ?></h3>
                        <p>Total Participants</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle text-info"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $stats['teams_by_status']['approved'] ?? 0 ?></h3>
                        <p>Approved Teams</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon">
                        <i class="fas fa-trophy text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $stats['teams_by_status']['competing'] ?? 0 ?></h3>
                        <p>Competing Teams</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="filterSchool" class="form-label">Filter by School</label>
                    <select id="filterSchool" class="form-control">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>"><?= htmlspecialchars($school['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterCategory" class="form-label">Filter by Category</label>
                    <select id="filterCategory" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Filter by Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="registered">Registered</option>
                        <option value="approved">Approved</option>
                        <option value="competing">Competing</option>
                        <option value="eliminated">Eliminated</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="searchTeam" class="form-label">Search Teams</label>
                    <input type="text" id="searchTeam" class="form-control" placeholder="Search by team name...">
                </div>
            </div>
        </div>
    </div>

    <!-- Teams Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Teams Overview
            </h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="teamsTable">
                    <thead>
                        <tr>
                            <th>Team Code</th>
                            <th>Team Name</th>
                            <th>School</th>
                            <th>Category</th>
                            <th>Participants</th>
                            <th>Coach(es)</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teams)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No teams registered yet</h5>
                                    <p class="text-muted">
                                        <a href="<?= $baseUrl ?>/admin/teams/create" class="btn btn-primary">
                                            Create your first team
                                        </a>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teams as $team): ?>
                                <tr data-school-id="<?= $team['school_id'] ?>" 
                                    data-category-id="<?= $team['category_id'] ?>"
                                    data-status="<?= $team['status'] ?>">
                                    <td>
                                        <code><?= htmlspecialchars($team['team_code']) ?></code>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($team['name']) ?></strong>
                                        <?php if (!empty($team['robot_name'])): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-robot"></i> <?= htmlspecialchars($team['robot_name']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($team['school_name']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($team['district'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?= htmlspecialchars($team['category_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="participant-count">
                                            <i class="fas fa-users"></i> 
                                            <?= $team['participant_count'] ?? 0 ?>
                                        </span>
                                        <?php 
                                        $maxParticipants = $team['max_team_size'] ?? 4;
                                        $currentCount = $team['participant_count'] ?? 0;
                                        if ($currentCount >= $maxParticipants): ?>
                                            <span class="badge badge-success">Full</span>
                                        <?php elseif ($currentCount == 0): ?>
                                            <span class="badge badge-warning">Empty</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><?= ($maxParticipants - $currentCount) ?> slots</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($team['coach1_name'])): ?>
                                            <div><i class="fas fa-user"></i> <?= htmlspecialchars($team['coach1_name']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($team['coach2_name'])): ?>
                                            <div><i class="fas fa-user"></i> <?= htmlspecialchars($team['coach2_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'registered' => 'badge-info',
                                            'approved' => 'badge-success',
                                            'competing' => 'badge-warning',
                                            'eliminated' => 'badge-secondary',
                                            'completed' => 'badge-primary'
                                        ];
                                        $class = $statusClass[$team['status']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?= $class ?>">
                                            <?= ucfirst(htmlspecialchars($team['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('M d, Y', strtotime($team['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>/edit" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit Team">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="addParticipant(<?= $team['id'] ?>)" title="Add Participant">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Statistics -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Category Statistics
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Teams</th>
                                    <th>Participants</th>
                                    <th>Avg Team Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['teams_by_category'] as $categoryStat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($categoryStat['category_name']) ?></td>
                                        <td><?= $categoryStat['team_count'] ?></td>
                                        <td><?= $categoryStat['participant_count'] ?></td>
                                        <td>
                                            <?= $categoryStat['team_count'] > 0 
                                                ? number_format($categoryStat['participant_count'] / $categoryStat['team_count'], 1) 
                                                : '0' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.teams-management-container {
    padding: 1rem 0;
}

.stats-cards .stat-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stats-cards .stat-card:hover {
    transform: translateY(-2px);
}

.stat-card .card-body {
    display: flex;
    align-items: center;
    padding: 1.5rem;
}

.stat-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.875rem;
}

.participant-count {
    font-weight: bold;
}

.table tbody tr:hover {
    background-color: rgba(0,123,255,0.1);
}

.btn-group .btn {
    margin-right: 2px;
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    margin: 0;
    color: #333;
}

.page-subtitle {
    margin: 0;
    color: #6c757d;
}

.badge {
    font-size: 0.75rem;
}

code {
    background: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
});

function initializeFilters() {
    const schoolFilter = document.getElementById('filterSchool');
    const categoryFilter = document.getElementById('filterCategory');
    const statusFilter = document.getElementById('filterStatus');
    const searchInput = document.getElementById('searchTeam');
    
    // Add event listeners
    schoolFilter.addEventListener('change', filterTeams);
    categoryFilter.addEventListener('change', filterTeams);
    statusFilter.addEventListener('change', filterTeams);
    searchInput.addEventListener('input', filterTeams);
}

function filterTeams() {
    const schoolFilter = document.getElementById('filterSchool').value;
    const categoryFilter = document.getElementById('filterCategory').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const searchFilter = document.getElementById('searchTeam').value.toLowerCase();
    
    const rows = document.querySelectorAll('#teamsTable tbody tr[data-school-id]');
    
    rows.forEach(row => {
        let show = true;
        
        // School filter
        if (schoolFilter && row.dataset.schoolId !== schoolFilter) {
            show = false;
        }
        
        // Category filter
        if (categoryFilter && row.dataset.categoryId !== categoryFilter) {
            show = false;
        }
        
        // Status filter
        if (statusFilter && row.dataset.status !== statusFilter) {
            show = false;
        }
        
        // Search filter
        if (searchFilter) {
            const teamName = row.cells[1].textContent.toLowerCase();
            const schoolName = row.cells[2].textContent.toLowerCase();
            if (!teamName.includes(searchFilter) && !schoolName.includes(searchFilter)) {
                show = false;
            }
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function addParticipant(teamId) {
    if (confirm('Are you sure you want to add a new participant to this team?')) {
        window.location.href = `<?= $baseUrl ?>/admin/teams/${teamId}/participants/create`;
    }
}

function updateTeamStatus(teamId, status) {
    if (confirm(`Are you sure you want to change this team's status to ${status}?`)) {
        fetch(`<?= $baseUrl ?>/admin/teams/${teamId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating team status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating team status.');
        });
    }
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php';
?>