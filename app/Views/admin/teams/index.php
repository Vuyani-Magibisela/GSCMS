<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<div class="admin-content">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <div class="stat-card-value"><?= $stats['total_teams'] ?></div>
                    <div class="stat-card-label">Total Teams</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <div class="stat-card-value"><?= $stats['total_participants'] ?></div>
                    <div class="stat-card-label">Total Participants</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-user-graduate text-success"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <div class="stat-card-value"><?= $stats['teams_by_status']['approved'] ?? 0 ?></div>
                    <div class="stat-card-label">Approved Teams</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <div class="stat-card-value"><?= $stats['teams_by_status']['competing'] ?? 0 ?></div>
                    <div class="stat-card-label">Competing Teams</div>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-trophy text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="admin-filters">
        <div class="admin-form-row">
            <div class="form-group">
                <label for="filterSchool" class="form-label">Filter by School</label>
                <select id="filterSchool" class="form-control">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?= $school->id ?>"><?= htmlspecialchars($school->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="filterCategory" class="form-label">Filter by Category</label>
                <select id="filterCategory" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category->id ?>"><?= htmlspecialchars($category->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
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
            <div class="form-group">
                <label for="searchTeam" class="form-label">Search Teams</label>
                <input type="text" id="searchTeam" class="form-control" placeholder="Search by team name...">
            </div>
        </div>
    </div>

    <!-- Teams Table -->
    <div class="data-table-container">
        <div class="data-table-header">
            <h3 class="data-table-title">
                <i class="fas fa-list"></i> Teams Overview
            </h3>
            <div class="data-table-actions">
                <a href="<?= $baseUrl ?>/admin/teams/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Team
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="teamsTable">
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
                                <td colspan="9" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="fas fa-users empty-state-icon"></i>
                                        <h3 class="empty-state-title">No teams registered yet</h3>
                                        <p class="empty-state-text">Get started by creating your first team</p>
                                        <a href="<?= $baseUrl ?>/admin/teams/create" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create First Team
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teams as $team): ?>
                                <tr data-school-id="<?= $team['school_id'] ?>" 
                                    data-category-id="<?= $team['category_id'] ?>"
                                    data-status="<?= $team['status'] ?>">
                                    <td>
                                        <code class="team-code"><?= htmlspecialchars($team['team_code']) ?></code>
                                    </td>
                                    <td>
                                        <div class="team-info">
                                            <div class="team-name"><?= htmlspecialchars($team['name']) ?></div>
                                            <?php if (!empty($team['robot_name'])): ?>
                                                <div class="robot-name">
                                                    <i class="fas fa-robot"></i> 
                                                    <?= htmlspecialchars($team['robot_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="school-info">
                                            <div class="school-name"><?= htmlspecialchars($team['school_name']) ?></div>
                                            <?php if (!empty($team['district'])): ?>
                                                <div class="school-district"><?= htmlspecialchars($team['district']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $categoryCode = strtolower($team['category_code'] ?? 'default');
                                        ?>
                                        <span class="category-badge <?= $categoryCode ?>">
                                            <?= htmlspecialchars($team['category_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="participant-info">
                                            <div class="participant-count">
                                                <i class="fas fa-users"></i> 
                                                <?= $team['participant_count'] ?? 0 ?>
                                            </div>
                                            <?php 
                                            $maxParticipants = $team['max_team_size'] ?? 4;
                                            $currentCount = $team['participant_count'] ?? 0;
                                            if ($currentCount >= $maxParticipants): ?>
                                                <span class="status-badge active">Full</span>
                                            <?php elseif ($currentCount == 0): ?>
                                                <span class="status-badge pending">Empty</span>
                                            <?php else: ?>
                                                <span class="status-badge"><?= ($maxParticipants - $currentCount) ?> slots</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="coach-list">
                                            <?php if (!empty($team['coach1_name'])): ?>
                                                <div class="coach-item">
                                                    <i class="fas fa-user"></i> 
                                                    <?= htmlspecialchars($team['coach1_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($team['coach2_name'])): ?>
                                                <div class="coach-item">
                                                    <i class="fas fa-user"></i> 
                                                    <?= htmlspecialchars($team['coach2_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'registered' => 'pending',
                                            'approved' => 'active',
                                            'competing' => 'active',
                                            'eliminated' => 'inactive',
                                            'completed' => 'active'
                                        ];
                                        $class = $statusClass[$team['status']] ?? 'inactive';
                                        ?>
                                        <span class="status-badge <?= $class ?>">
                                            <?= ucfirst(htmlspecialchars($team['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('M d, Y', strtotime($team['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>" 
                                               class="action-btn view-btn" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= $baseUrl ?>/admin/teams/<?= $team['id'] ?>/edit" 
                                               class="action-btn edit-btn" title="Edit Team">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="action-btn edit-btn" 
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

    <!-- Category Statistics -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fas fa-chart-bar"></i> Category Statistics
            </h3>
        </div>
        <div class="admin-card-body">
            <div class="table-responsive">
                <table class="data-table">
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