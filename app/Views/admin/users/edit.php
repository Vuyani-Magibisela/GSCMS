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
        <h1 class="admin-title">Edit User</h1>
        <div class="admin-actions">
            <a href="/admin/users/<?= $user['id'] ?>" class="admin-btn admin-btn-secondary">
                <i class="icon-arrow-left"></i> Back to Details
            </a>
        </div>
    </div>

    <div class="admin-main">
        <div class="edit-user-container">
            <div class="edit-user-card">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php 
                        $errorMessages = [
                            'update_failed' => 'Failed to update user. Please try again.',
                            'validation_failed' => 'Please check your input and try again.'
                        ];
                        echo $errorMessages[$_GET['error']] ?? 'An error occurred';
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/users/<?= $user['id'] ?>" class="edit-user-form">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" 
                                   value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" 
                                   value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" id="username" name="username" class="form-input" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role" class="form-label">Role *</label>
                            <select id="role" name="role" class="form-select" required>
                                <?php foreach ($availableRoles as $roleValue => $roleLabel): ?>
                                    <option value="<?= $roleValue ?>" <?= $user['role'] === $roleValue ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($roleLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status *</label>
                            <select id="status" name="status" class="form-select" required>
                                <?php foreach ($availableStatuses as $statusValue => $statusLabel): ?>
                                    <option value="<?= $statusValue ?>" <?= $user['status'] === $statusValue ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="school_id" class="form-label">School</label>
                            <select id="school_id" name="school_id" class="form-select">
                                <option value="">No School</option>
                                <?php foreach ($schools as $school): ?>
                                    <option value="<?= $school['id'] ?>" <?= $user['school_id'] == $school['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($school['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Leave blank to keep current password">
                            <small class="form-help">Only fill this if you want to change the user's password</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="icon-save"></i> Update User
                        </button>
                        <a href="/admin/users/<?= $user['id'] ?>" class="admin-btn admin-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Add form validation
document.querySelector('.edit-user-form').addEventListener('submit', function(e) {
    const requiredFields = ['first_name', 'last_name', 'username', 'email', 'role', 'status'];
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields');
    }
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>