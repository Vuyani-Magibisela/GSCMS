<?php
ob_start();
$layout = 'layouts/admin';
?>
<div class="admin-content">
    <!-- Breadcrumbs -->
    <nav class="admin-breadcrumbs" aria-label="breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <li class="breadcrumb-item <?= $index === count($breadcrumbs) - 1 ? 'active' : '' ?>">
                    <?php if (!empty($breadcrumb['url']) && $index < count($breadcrumbs) - 1): ?>
                        <a href="<?= $breadcrumb['url'] ?>"><?= htmlspecialchars($breadcrumb['title']) ?></a>
                    <?php else: ?>
                        <?= htmlspecialchars($breadcrumb['title']) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="admin-header">
        <h1 class="admin-title">User Details</h1>
        <div class="admin-actions">
            <a href="/admin/users" class="admin-btn admin-btn-secondary">
                <i class="icon-arrow-left"></i> Back to Users
            </a>
            <a href="/admin/users/<?= $user['id'] ?>/edit" class="admin-btn admin-btn-primary">
                <i class="icon-edit"></i> Edit User
            </a>
        </div>
    </div>

    <div class="admin-main">
        <div class="user-details-container">
            <div class="user-details-card">
                <div class="user-details-header">
                    <div class="user-avatar">
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="user-info">
                        <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                        <p class="user-role"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))) ?></p>
                        <span class="status-badge status-<?= $user['status'] ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="user-details-body">
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Username</label>
                            <span><?= htmlspecialchars($user['username']) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Email</label>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Role</label>
                            <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Status</label>
                            <span class="status-badge status-<?= $user['status'] ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </div>
                        
                        <div class="detail-item">
                            <label>School</label>
                            <span><?= $user['school_name'] ? htmlspecialchars($user['school_name']) : 'N/A' ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Member Since</label>
                            <span><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Last Updated</label>
                            <span><?= isset($user['updated_at']) && $user['updated_at'] ? date('F j, Y g:i A', strtotime($user['updated_at'])) : 'Never' ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Last Login</label>
                            <span><?= isset($user['last_login']) && $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-actions-card">
                <h3>User Actions</h3>
                <div class="action-buttons">
                    <a href="/admin/users/<?= $user['id'] ?>/edit" class="admin-btn admin-btn-primary">
                        <i class="icon-edit"></i> Edit User
                    </a>
                    
                    <div class="status-change-buttons">
                        <?php if ($user['status'] !== 'active'): ?>
                            <button class="admin-btn admin-btn-success" onclick="changeUserStatus(<?= $user['id'] ?>, 'active')">
                                <i class="icon-check"></i> Activate
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($user['status'] !== 'inactive'): ?>
                            <button class="admin-btn admin-btn-warning" onclick="changeUserStatus(<?= $user['id'] ?>, 'inactive')">
                                <i class="icon-pause"></i> Deactivate
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($user['status'] !== 'suspended'): ?>
                            <button class="admin-btn admin-btn-danger" onclick="changeUserStatus(<?= $user['id'] ?>, 'suspended')">
                                <i class="icon-ban"></i> Suspend
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($user['id'] != $auth->id()): // Prevent self-deletion ?>
                        <button class="admin-btn admin-btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')">
                            <i class="icon-trash"></i> Delete User
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeUserStatus(userId, newStatus) {
    if (confirm(`Are you sure you want to ${newStatus} this user?`)) {
        fetch('/admin/users/update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to update user status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating user status');
        });
    }
}

function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
        // Create a form and submit it as DELETE request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/users/${userId}`;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>