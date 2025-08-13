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
                    <i class="fas fa-users" aria-hidden="true"></i>
                    User Management
                </h1>
                <p class="page-description">Manage all system users, their roles, and access permissions</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-outline-primary" onclick="exportUsers()">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    Export Users
                </button>
                <button class="btn btn-primary" onclick="showCreateUserModal()">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Add New User
                </button>
            </div>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="users-stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total-users">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($userStats['total'] ?? 0) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon active-users">
                    <i class="fas fa-user-check" aria-hidden="true"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($userStats['active'] ?? 0) ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon new-users">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($userStats['monthly_new'] ?? 0) ?></div>
                    <div class="stat-label">New This Month</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon admin-users">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format(($userStats['by_role'][App\Models\User::SUPER_ADMIN] ?? 0) + ($userStats['by_role'][App\Models\User::COMPETITION_ADMIN] ?? 0)) ?></div>
                    <div class="stat-label">Administrators</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="users-filters-section">
        <div class="filters-header">
            <h3 class="filters-title">Filter Users</h3>
            <button class="btn btn-ghost btn-sm" onclick="clearAllFilters()">
                <i class="fas fa-times" aria-hidden="true"></i>
                Clear Filters
            </button>
        </div>

        <form class="filters-form" method="GET" action="<?= $baseUrl ?>/admin/users">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search" class="filter-label">Search</label>
                    <div class="filter-input-group">
                        <input type="text" 
                               id="search" 
                               name="search" 
                               class="form-input" 
                               placeholder="Search users by name, email, or username..." 
                               value="<?= htmlspecialchars($filters['search']) ?>">
                        <i class="fas fa-search filter-input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <label for="role" class="filter-label">Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach ($availableRoles as $roleKey => $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleKey) ?>" <?= $filters['role'] === $roleKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($roleLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status" class="filter-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach ($availableStatuses as $statusKey => $statusLabel): ?>
                            <option value="<?= htmlspecialchars($statusKey) ?>" <?= $filters['status'] === $statusKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($statusLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="school" class="filter-label">School</label>
                    <select id="school" name="school" class="form-select">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>" <?= $filters['school'] == $school['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    Apply Filters
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                    <i class="fas fa-undo" aria-hidden="true"></i>
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="users-table-section">
        <div class="table-header">
            <h3 class="table-title">
                Users 
                <span class="table-count">(<?= number_format($pagination['total_count']) ?> total)</span>
            </h3>
            
            <div class="table-actions">
                <div class="bulk-actions" style="display: none;" id="bulkActions">
                    <span class="bulk-selected-count" id="bulkSelectedCount">0 selected</span>
                    <button class="btn btn-sm btn-outline-primary" onclick="bulkUpdateStatus()">
                        <i class="fas fa-edit" aria-hidden="true"></i>
                        Update Status
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="bulkDeleteUsers()">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <h4 class="empty-state-title">No Users Found</h4>
                <p class="empty-state-description">
                    <?php if (!empty(array_filter($filters))): ?>
                        No users match your current filter criteria. Try adjusting your filters.
                    <?php else: ?>
                        There are no users in the system yet. Create your first user to get started.
                    <?php endif; ?>
                </p>
                <button class="btn btn-primary" onclick="showCreateUserModal()">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Add First User
                </button>
            </div>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table" id="usersTable">
                    <thead class="admin-table-header">
                        <tr>
                            <th class="admin-table-th checkbox-col">
                                <input type="checkbox" id="selectAllUsers" class="form-checkbox" onchange="toggleAllUsers(this)">
                            </th>
                            <th class="admin-table-th">User</th>
                            <th class="admin-table-th">Role</th>
                            <th class="admin-table-th">School</th>
                            <th class="admin-table-th">Status</th>
                            <th class="admin-table-th">Last Login</th>
                            <th class="admin-table-th">Created</th>
                            <th class="admin-table-th">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="admin-table-body">
                        <?php foreach ($users as $user): ?>
                            <tr class="admin-table-row" data-user-id="<?= $user['id'] ?>">
                                <td class="admin-table-td checkbox-col">
                                    <input type="checkbox" class="form-checkbox user-checkbox" value="<?= $user['id'] ?>" onchange="updateBulkActions()">
                                </td>
                                <td class="admin-table-td">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user" aria-hidden="true"></i>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name">
                                                <a href="<?= $baseUrl ?>/admin/users/<?= $user['id'] ?>" class="user-name-link">
                                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                                </a>
                                            </div>
                                            <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                                            <div class="user-email">
                                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="email-link">
                                                    <?= htmlspecialchars($user['email']) ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="admin-table-td">
                                    <span class="role-badge role-<?= strtolower($user['role']) ?>">
                                        <?= htmlspecialchars($availableRoles[$user['role']] ?? $user['role']) ?>
                                    </span>
                                </td>
                                <td class="admin-table-td">
                                    <?= $user['school_name'] ? htmlspecialchars($user['school_name']) : '<span class="text-muted">No school</span>' ?>
                                </td>
                                <td class="admin-table-td">
                                    <span class="status-badge status-<?= strtolower($user['status']) ?>">
                                        <?= htmlspecialchars($availableStatuses[$user['status']] ?? ucfirst($user['status'])) ?>
                                    </span>
                                </td>
                                <td class="admin-table-td">
                                    <span class="last-login-date">
                                        <?= isset($user['last_login']) && $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?>
                                    </span>
                                </td>
                                <td class="admin-table-td">
                                    <span class="created-date">
                                        <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                    </span>
                                </td>
                                <td class="admin-table-td">
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-ghost" 
                                                onclick="viewUser(<?= $user['id'] ?>)" 
                                                title="View Details">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn btn-sm btn-ghost" 
                                                onclick="editUser(<?= $user['id'] ?>)" 
                                                title="Edit User">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn btn-sm btn-ghost" 
                                                onclick="changeUserStatus(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>', '<?= $user['status'] ?>')" 
                                                title="Change Status">
                                            <i class="fas fa-toggle-on" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn btn-sm btn-ghost text-danger" 
                                                onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')" 
                                                title="Delete User">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Showing <?= ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 ?>-<?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count']) ?> 
                        of <?= number_format($pagination['total_count']) ?> users
                    </div>
                    
                    <nav class="pagination-nav" aria-label="User pagination">
                        <ul class="pagination">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1])) ?>">
                                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $pagination['current_page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1])) ?>">
                                        Next
                                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="modal" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New User</h3>
                <button type="button" class="modal-close" onclick="closeCreateUserModal()">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <form id="createUserForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createFirstName" class="form-label">First Name *</label>
                            <input type="text" id="createFirstName" name="first_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="createLastName" class="form-label">Last Name *</label>
                            <input type="text" id="createLastName" name="last_name" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createUsername" class="form-label">Username *</label>
                            <input type="text" id="createUsername" name="username" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="createEmail" class="form-label">Email Address *</label>
                            <input type="email" id="createEmail" name="email" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createRole" class="form-label">Role *</label>
                            <select id="createRole" name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php foreach ($availableRoles as $roleKey => $roleLabel): ?>
                                    <option value="<?= htmlspecialchars($roleKey) ?>">
                                        <?= htmlspecialchars($roleLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="createStatus" class="form-label">Status</label>
                            <select id="createStatus" name="status" class="form-select">
                                <?php foreach ($availableStatuses as $statusKey => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusKey) ?>" <?= $statusKey === 'active' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="createSchool" class="form-label">School (Optional)</label>
                        <select id="createSchool" name="school_id" class="form-select">
                            <option value="">No School</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['id'] ?>">
                                    <?= htmlspecialchars($school['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="createPassword" class="form-label">Password *</label>
                        <input type="password" id="createPassword" name="password" class="form-input" required minlength="8">
                        <div class="form-help">Minimum 8 characters</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div id="changeStatusModal" class="modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Change User Status</h3>
                <button type="button" class="modal-close" onclick="closeChangeStatusModal()">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Change the status for: <strong id="changeStatusUserName"></strong></p>
                <div class="form-group">
                    <label for="newUserStatus" class="form-label">New Status:</label>
                    <select id="newUserStatus" class="form-select">
                        <?php foreach ($availableStatuses as $statusKey => $statusLabel): ?>
                            <option value="<?= htmlspecialchars($statusKey) ?>">
                                <?= htmlspecialchars($statusLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeChangeStatusModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmStatusChange()">Update Status</button>
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

<script>
// Global variables
let currentStatusChangeUserId = null;
let currentStatusChangeUserName = '';
const baseUrl = '<?= $baseUrl ?>';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeUserManagement();
    setupFormHandlers();
});

function initializeUserManagement() {
    console.log('User management initialized');
}

function setupFormHandlers() {
    // Create user form handler
    const createUserForm = document.getElementById('createUserForm');
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCreateUser();
        });
    }
}

function showCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'flex';
    document.getElementById('createFirstName').focus();
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
    document.getElementById('createUserForm').reset();
}

function submitCreateUser() {
    const formData = new FormData(document.getElementById('createUserForm'));
    
    showLoading();
    
    fetch(`${baseUrl}/admin/users`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('User created successfully!');
            closeCreateUserModal();
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to create user'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while creating the user.');
    });
}

function viewUser(userId) {
    window.location.href = `${baseUrl}/admin/users/${userId}`;
}

function editUser(userId) {
    // Navigate to edit page or open edit modal
    window.location.href = `${baseUrl}/admin/users/${userId}/edit`;
}

function changeUserStatus(userId, userName, currentStatus) {
    currentStatusChangeUserId = userId;
    currentStatusChangeUserName = userName;
    
    document.getElementById('changeStatusUserName').textContent = userName;
    document.getElementById('newUserStatus').value = currentStatus;
    
    document.getElementById('changeStatusModal').style.display = 'flex';
}

function closeChangeStatusModal() {
    document.getElementById('changeStatusModal').style.display = 'none';
    currentStatusChangeUserId = null;
    currentStatusChangeUserName = '';
}

function confirmStatusChange() {
    console.log('confirmStatusChange called');
    if (!currentStatusChangeUserId) {
        console.log('No user ID set');
        return;
    }
    
    const newStatus = document.getElementById('newUserStatus').value;
    console.log('Updating user', currentStatusChangeUserId, 'to status', newStatus);
    
    showLoading();
    
    fetch(`${baseUrl}/admin/users/update-status`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: currentStatusChangeUserId,
            status: newStatus
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('User status updated successfully!');
            closeChangeStatusModal();
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to update user status'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while updating the user status.');
    });
}

function deleteUser(userId, userName) {
    if (!confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        return;
    }
    
    showLoading();
    
    fetch(`${baseUrl}/admin/users/${userId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('User deleted successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete user'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while deleting the user.');
    });
}

function exportUsers() {
    window.open(`${baseUrl}/admin/users/export`, '_blank');
}

function clearAllFilters() {
    window.location.href = `${baseUrl}/admin/users`;
}

function resetFilters() {
    document.getElementById('search').value = '';
    document.getElementById('role').value = '';
    document.getElementById('status').value = '';
    document.getElementById('school').value = '';
}

function toggleAllUsers(checkbox) {
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('bulkSelectedCount');
    
    if (selectedCheckboxes.length > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = `${selectedCheckboxes.length} selected`;
    } else {
        bulkActions.style.display = 'none';
    }
    
    // Update select all checkbox
    const selectAllCheckbox = document.getElementById('selectAllUsers');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    selectAllCheckbox.checked = selectedCheckboxes.length === userCheckboxes.length;
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