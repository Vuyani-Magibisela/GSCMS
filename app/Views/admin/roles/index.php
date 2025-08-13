<?php 
ob_start();
$layout = 'layouts/admin'; 
?>

<div class="admin-content">
    <!-- Breadcrumbs -->
    <nav class="admin-breadcrumbs" aria-label="breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <li class="breadcrumb-item">
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <span class="breadcrumb-current" aria-current="page"><?= htmlspecialchars($breadcrumb['title']) ?></span>
                    <?php else: ?>
                        <a href="<?= $baseUrl . htmlspecialchars($breadcrumb['url']) ?>" class="breadcrumb-link">
                            <?= htmlspecialchars($breadcrumb['title']) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <i class="fas fa-chevron-right breadcrumb-separator" aria-hidden="true"></i>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="admin-page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1 class="page-title">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                    Role Management
                </h1>
                <p class="page-description">Manage user roles and permissions across the system</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-outline-primary" onclick="refreshRoleData()">
                    <i class="fas fa-sync-alt" aria-hidden="true"></i>
                    Refresh
                </button>
                <button class="btn btn-primary" onclick="exportRoles()">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    Export
                </button>
            </div>
        </div>
    </div>

    <!-- Role Statistics -->
    <div class="roles-stats-section">
        <h2 class="section-title">Role Distribution</h2>
        <div class="roles-stats-grid">
            <?php foreach ($roleStats as $role => $stats): ?>
                <div class="role-stat-card" data-role="<?= htmlspecialchars($role) ?>">
                    <div class="role-stat-header">
                        <div class="role-icon">
                            <i class="<?= getRoleIcon($role) ?>" aria-hidden="true"></i>
                        </div>
                        <div class="role-info">
                            <h3 class="role-name"><?= htmlspecialchars($stats['label']) ?></h3>
                            <div class="role-count">
                                <span class="count-number"><?= number_format($stats['count']) ?></span>
                                <span class="count-label">users</span>
                            </div>
                        </div>
                    </div>
                    <div class="role-percentage">
                        <div class="percentage-bar">
                            <div class="percentage-fill" style="width: <?= $stats['percentage'] ?>%"></div>
                        </div>
                        <span class="percentage-text"><?= $stats['percentage'] ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Roles Management -->
    <div class="roles-management-section">
        <div class="section-header">
            <h2 class="section-title">Users by Role</h2>
            <div class="section-actions">
                <div class="search-box">
                    <input type="text" 
                           id="roleSearch" 
                           class="form-input" 
                           placeholder="Search users..." 
                           onkeyup="filterUsers(this.value)">
                    <i class="fas fa-search search-icon" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="roles-tabs">
            <div class="roles-tabs-header">
                <ul class="roles-nav-tabs" role="tablist">
                    <?php $first = true; foreach ($usersByRole as $role => $data): ?>
                        <li class="roles-nav-item">
                            <a class="roles-nav-link <?= $first ? 'active' : '' ?>" 
                               data-role="<?= htmlspecialchars($role) ?>" 
                               href="#role-<?= htmlspecialchars($role) ?>" 
                               role="tab"
                               onclick="showRoleTab('<?= htmlspecialchars($role) ?>')">
                                <i class="<?= getRoleIcon($role) ?>" aria-hidden="true"></i>
                                <?= htmlspecialchars($data['label']) ?>
                                <span class="tab-count"><?= $data['total_count'] ?></span>
                            </a>
                        </li>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="roles-tabs-content">
                <?php $first = true; foreach ($usersByRole as $role => $data): ?>
                    <div class="role-tab-content <?= $first ? 'active' : '' ?>" 
                         id="role-<?= htmlspecialchars($role) ?>" 
                         data-role="<?= htmlspecialchars($role) ?>">
                        
                        <!-- Role Info Header -->
                        <div class="role-info-header">
                            <div class="role-description">
                                <h3><?= htmlspecialchars($data['label']) ?></h3>
                                <p><?= htmlspecialchars($roleDescriptions[$role] ?? 'No description available') ?></p>
                            </div>
                            <div class="role-permissions">
                                <strong>Key Permissions:</strong>
                                <div class="permission-tags">
                                    <?php foreach (($rolePermissions[$role] ?? []) as $permission): ?>
                                        <span class="permission-tag"><?= htmlspecialchars($permission) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="users-table-container" id="users-container-<?= htmlspecialchars($role) ?>">
                            <?php if (empty($data['users'])): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="<?= getRoleIcon($role) ?>" aria-hidden="true"></i>
                                    </div>
                                    <h4 class="empty-state-title">No <?= htmlspecialchars($data['label']) ?>s</h4>
                                    <p class="empty-state-description">There are currently no users with this role.</p>
                                </div>
                            <?php else: ?>
                                <div class="admin-table-wrapper">
                                    <table class="admin-table" id="users-table-<?= htmlspecialchars($role) ?>">
                                        <thead class="admin-table-header">
                                            <tr>
                                                <th class="admin-table-th">User</th>
                                                <th class="admin-table-th">Email</th>
                                                <th class="admin-table-th">School</th>
                                                <th class="admin-table-th">Status</th>
                                                <th class="admin-table-th">Last Login</th>
                                                <th class="admin-table-th">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="admin-table-body">
                                            <?php foreach ($data['users'] as $user): ?>
                                                <tr class="admin-table-row" data-user-id="<?= $user['id'] ?>">
                                                    <td class="admin-table-td">
                                                        <div class="user-info">
                                                            <div class="user-avatar">
                                                                <i class="fas fa-user" aria-hidden="true"></i>
                                                            </div>
                                                            <div class="user-details">
                                                                <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                                                <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="admin-table-td">
                                                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="email-link">
                                                            <?= htmlspecialchars($user['email']) ?>
                                                        </a>
                                                    </td>
                                                    <td class="admin-table-td">
                                                        <?= $user['school_name'] ? htmlspecialchars($user['school_name']) : '<span class="text-muted">No school</span>' ?>
                                                    </td>
                                                    <td class="admin-table-td">
                                                        <span class="status-badge status-<?= strtolower($user['status']) ?>">
                                                            <?= ucfirst($user['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="admin-table-td">
                                                        <span class="last-login-date">
                                                            <?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?>
                                                        </span>
                                                    </td>
                                                    <td class="admin-table-td">
                                                        <div class="table-actions">
                                                            <button class="btn btn-sm btn-ghost" 
                                                                    onclick="showUserDetails(<?= $user['id'] ?>)" 
                                                                    title="View Details">
                                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-ghost" 
                                                                    onclick="changeUserRole(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>', '<?= htmlspecialchars($role) ?>')" 
                                                                    title="Change Role">
                                                                <i class="fas fa-user-cog" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ($data['total_count'] > count($data['users'])): ?>
                                    <div class="load-more-section">
                                        <button class="btn btn-outline-primary" 
                                                onclick="loadMoreUsers('<?= htmlspecialchars($role) ?>')">
                                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                            Load More (<?= $data['total_count'] - count($data['users']) ?> remaining)
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $first = false; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Role Change Modal -->
<div id="roleChangeModal" class="modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Change User Role</h3>
                <button type="button" class="modal-close" onclick="closeRoleChangeModal()">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="user-change-info">
                    <p>You are about to change the role for:</p>
                    <div class="user-highlight">
                        <strong id="changeUserName"></strong>
                        <br>
                        <span class="text-muted">Current Role: <span id="currentRoleLabel"></span></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="newUserRole" class="form-label">New Role:</label>
                    <select id="newUserRole" class="form-select">
                        <?php foreach ($availableRoles as $roleKey => $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleKey) ?>"><?= htmlspecialchars($roleLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="role-change-warning">
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        <strong>Warning:</strong> Changing a user's role will immediately affect their access permissions throughout the system.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRoleChangeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmRoleChange()">Change Role</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        <span>Processing...</span>
    </div>
</div>

<?php
function getRoleIcon($role) {
    $icons = [
        'super_admin' => 'fas fa-crown',
        'competition_admin' => 'fas fa-trophy',
        'school_coordinator' => 'fas fa-school',
        'team_coach' => 'fas fa-users',
        'judge' => 'fas fa-gavel',
        'participant' => 'fas fa-user-graduate'
    ];
    return $icons[$role] ?? 'fas fa-user';
}
?>

<script>
// Global variables
let currentChangeUserId = null;
let currentChangeUserName = '';
let currentUserRole = '';
const baseUrl = '<?= $baseUrl ?>';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if needed
    initializeRoleManagement();
});

function initializeRoleManagement() {
    // Add any initialization code here
    console.log('Role management initialized');
}

function showRoleTab(role) {
    // Hide all tab contents
    document.querySelectorAll('.role-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all nav links
    document.querySelectorAll('.roles-nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(`role-${role}`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Add active class to clicked nav link
    const selectedLink = document.querySelector(`[data-role="${role}"]`);
    if (selectedLink) {
        selectedLink.classList.add('active');
    }
}

function changeUserRole(userId, userName, currentRole) {
    currentChangeUserId = userId;
    currentChangeUserName = userName;
    currentUserRole = currentRole;
    
    document.getElementById('changeUserName').textContent = userName;
    document.getElementById('currentRoleLabel').textContent = getRoleLabel(currentRole);
    document.getElementById('newUserRole').value = currentRole;
    
    document.getElementById('roleChangeModal').style.display = 'flex';
}

function closeRoleChangeModal() {
    document.getElementById('roleChangeModal').style.display = 'none';
    currentChangeUserId = null;
    currentChangeUserName = '';
    currentUserRole = '';
}

function confirmRoleChange() {
    if (!currentChangeUserId) return;
    
    const newRole = document.getElementById('newUserRole').value;
    
    if (newRole === currentUserRole) {
        alert('Please select a different role.');
        return;
    }
    
    showLoading();
    
    fetch(`${baseUrl}/admin/roles/update-user-role`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: currentChangeUserId,
            role: newRole
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('User role updated successfully!');
            closeRoleChangeModal();
            // Refresh the page to show updated data
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to update user role'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while updating the user role.');
    });
}

function getRoleLabel(role) {
    const labels = {
        'super_admin': 'Super Administrator',
        'competition_admin': 'Competition Administrator',
        'school_coordinator': 'School Coordinator',
        'team_coach': 'Team Coach',
        'judge': 'Judge',
        'participant': 'Participant'
    };
    return labels[role] || role;
}

function showUserDetails(userId) {
    // Implement user details modal or redirect
    window.open(`${baseUrl}/admin/users/${userId}`, '_blank');
}

function refreshRoleData() {
    window.location.reload();
}

function exportRoles() {
    const format = 'csv'; // Could be made configurable
    window.open(`${baseUrl}/admin/roles/export?format=${format}`, '_blank');
}

function filterUsers(searchTerm) {
    // Simple client-side filtering
    const rows = document.querySelectorAll('.admin-table-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

function loadMoreUsers(role) {
    // Implement pagination loading
    console.log('Loading more users for role:', role);
    // This would make an AJAX request to get more users
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>