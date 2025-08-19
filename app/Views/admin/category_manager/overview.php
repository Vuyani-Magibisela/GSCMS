<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Category Overview Header -->
<div class="category-header">
    <div class="header-content">
        <div class="competition-info">
            <h2 class="competition-name"><?= htmlspecialchars($competition->name) ?></h2>
            <div class="competition-meta">
                <span class="badge badge-<?= $competition->status === 'active' ? 'success' : 'secondary' ?>">
                    <?= ucfirst($competition->status) ?>
                </span>
                <span class="badge badge-info"><?= $competition->year ?></span>
                <span class="competition-type"><?= ucfirst($competition->type) ?> Competition</span>
            </div>
        </div>
        
        <div class="category-actions">
            <button onclick="addNewCategory()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Category
            </button>
            <button onclick="bulkEditCategories()" class="btn btn-outline">
                <i class="fas fa-edit"></i> Bulk Edit
            </button>
            <button onclick="validateAllCategories()" class="btn btn-outline">
                <i class="fas fa-check-circle"></i> Validate All
            </button>
            <div class="dropdown">
                <button class="btn btn-outline dropdown-toggle" onclick="toggleExportMenu()">
                    <i class="fas fa-download"></i> Export
                </button>
                <div class="dropdown-menu" id="exportMenu" style="display: none;">
                    <a href="#" onclick="exportCategories('json')" class="dropdown-item">JSON Format</a>
                    <a href="#" onclick="exportCategories('csv')" class="dropdown-item">CSV Format</a>
                    <a href="#" onclick="exportCategories('xlsx')" class="dropdown-item">Excel Format</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Insights Dashboard -->
<div class="insights-dashboard">
    <div class="insight-cards">
        <div class="insight-card primary">
            <div class="card-icon">
                <i class="fas fa-tags"></i>
            </div>
            <div class="card-content">
                <h3><?= count($categories) ?></h3>
                <p>Total Categories</p>
            </div>
        </div>
        
        <div class="insight-card success">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-content">
                <h3><?= $insights['total_teams'] ?></h3>
                <p>Registered Teams</p>
            </div>
        </div>
        
        <div class="insight-card info">
            <div class="card-icon">
                <i class="fas fa-school"></i>
            </div>
            <div class="card-content">
                <h3><?= $insights['total_schools'] ?></h3>
                <p>Participating Schools</p>
            </div>
        </div>
        
        <div class="insight-card warning">
            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="card-content">
                <h3><?= round(array_sum(array_column($insights['capacity_utilization'], 'utilization')), 1) ?>%</h3>
                <p>Avg Capacity Utilization</p>
            </div>
        </div>
    </div>
    
    <!-- Category Performance Indicators -->
    <div class="performance-indicators">
        <?php if (!empty($insights['popular_categories'])): ?>
            <div class="indicator popular">
                <i class="fas fa-fire"></i>
                <span>Popular Categories:</span>
                <div class="category-tags">
                    <?php foreach ($insights['popular_categories'] as $category): ?>
                        <span class="category-tag"><?= htmlspecialchars($category) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($insights['underutilized_categories'])): ?>
            <div class="indicator warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Low Registration:</span>
                <div class="category-tags">
                    <?php foreach ($insights['underutilized_categories'] as $category): ?>
                        <span class="category-tag warning"><?= htmlspecialchars($category) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Category Grid -->
<div class="categories-grid">
    <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $category): ?>
            <div class="category-card" data-category-id="<?= $category['id'] ?>">
                <div class="card-header">
                    <div class="category-info">
                        <h3 class="category-name"><?= htmlspecialchars($category['name']) ?></h3>
                        <div class="category-meta">
                            <span class="category-code"><?= htmlspecialchars($category['original_code']) ?></span>
                            <?php if ($category['mission_name']): ?>
                                <span class="mission-info">
                                    <i class="fas fa-bullseye"></i>
                                    <?= htmlspecialchars($category['mission_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="category-status">
                        <span class="status-indicator <?= $category['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                        <?php if ($category['difficulty_level']): ?>
                            <span class="difficulty-badge level-<?= strtolower($category['difficulty_level']) ?>">
                                <?= ucfirst($category['difficulty_level']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-content">
                    <!-- Category Statistics -->
                    <div class="category-stats">
                        <div class="stat-row">
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span class="stat-label">Teams</span>
                                <span class="stat-value"><?= $category['team_count'] ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-school"></i>
                                <span class="stat-label">Schools</span>
                                <span class="stat-value"><?= $category['school_count'] ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-play-circle"></i>
                                <span class="stat-label">Active</span>
                                <span class="stat-value"><?= $category['active_teams'] ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Configuration Summary -->
                    <div class="config-summary">
                        <div class="config-item">
                            <span class="config-label">Team Size:</span>
                            <span class="config-value"><?= $category['team_size'] ?> members</span>
                        </div>
                        
                        <div class="config-item">
                            <span class="config-label">Time Limit:</span>
                            <span class="config-value"><?= $category['time_limit_minutes'] ?> minutes</span>
                        </div>
                        
                        <div class="config-item">
                            <span class="config-label">Max Attempts:</span>
                            <span class="config-value"><?= $category['max_attempts'] ?></span>
                        </div>
                        
                        <?php if ($category['capacity_limit']): ?>
                            <div class="config-item">
                                <span class="config-label">Capacity:</span>
                                <span class="config-value"><?= $category['capacity_limit'] ?> teams</span>
                                <?php 
                                $utilization = $category['capacity_limit'] ? 
                                    ($category['team_count'] / $category['capacity_limit']) * 100 : 0;
                                ?>
                                <div class="capacity-bar">
                                    <div class="capacity-fill" style="width: <?= min(100, $utilization) ?>%"></div>
                                </div>
                                <small class="capacity-text"><?= round($utilization) ?>% utilized</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Grade Levels -->
                    <?php if ($category['grades']): ?>
                        <div class="grade-levels">
                            <span class="grade-label">Grades:</span>
                            <div class="grade-badges">
                                <?php 
                                $grades = json_decode($category['grades'], true) ?? [];
                                foreach ($grades as $grade): 
                                ?>
                                    <span class="grade-badge"><?= htmlspecialchars($grade) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Configuration Status -->
                    <div class="config-status">
                        <div class="status-indicators">
                            <div class="status-indicator <?= $category['scoring_rubric'] ? 'complete' : 'incomplete' ?>" 
                                 title="Scoring Rubric">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="status-indicator <?= $category['equipment_requirements'] ? 'complete' : 'incomplete' ?>" 
                                 title="Equipment Requirements">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="status-indicator <?= $category['registration_rules'] ? 'complete' : 'incomplete' ?>" 
                                 title="Registration Rules">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="status-indicator <?= $category['custom_rules'] ? 'complete' : 'incomplete' ?>" 
                                 title="Custom Rules">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <small class="config-completeness">
                            Configuration: 
                            <?php 
                            $completeness = 0;
                            if ($category['scoring_rubric']) $completeness += 25;
                            if ($category['equipment_requirements']) $completeness += 25;
                            if ($category['registration_rules']) $completeness += 25;
                            if ($category['custom_rules']) $completeness += 25;
                            echo $completeness;
                            ?>% Complete
                        </small>
                    </div>
                </div>
                
                <div class="card-actions">
                    <a href="<?= url('/admin/category-manager/configure/' . $category['id']) ?>" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-cogs"></i> Configure
                    </a>
                    <button onclick="duplicateCategory(<?= $category['id'] ?>)" 
                            class="btn btn-outline btn-sm">
                        <i class="fas fa-copy"></i> Duplicate
                    </button>
                    <button onclick="viewTeams(<?= $category['id'] ?>)" 
                            class="btn btn-outline btn-sm">
                        <i class="fas fa-users"></i> Teams (<?= $category['team_count'] ?>)
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline btn-sm dropdown-toggle" 
                                onclick="toggleCategoryActions(<?= $category['id'] ?>)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu category-actions-menu" id="categoryActions<?= $category['id'] ?>" style="display: none;">
                            <a href="#" onclick="editCategory(<?= $category['id'] ?>)" class="dropdown-item">
                                <i class="fas fa-edit"></i> Edit Details
                            </a>
                            <a href="#" onclick="customizeRubric(<?= $category['id'] ?>)" class="dropdown-item">
                                <i class="fas fa-chart-bar"></i> Customize Rubric
                            </a>
                            <a href="#" onclick="setEquipmentRequirements(<?= $category['id'] ?>)" class="dropdown-item">
                                <i class="fas fa-wrench"></i> Equipment Requirements
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="toggleCategoryStatus(<?= $category['id'] ?>)" class="dropdown-item">
                                <i class="fas fa-<?= $category['is_active'] ? 'pause' : 'play' ?>"></i> 
                                <?= $category['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </a>
                            <a href="#" onclick="deleteCategory(<?= $category['id'] ?>)" class="dropdown-item text-danger">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tags"></i>
            <h3>No Categories Configured</h3>
            <p>Start by adding categories for this competition.</p>
            <button onclick="addNewCategory()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add First Category
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Category Configuration Modal -->
<div class="modal" id="categoryModal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title" id="categoryModalTitle">Category Configuration</h3>
            <button class="modal-close" onclick="closeCategoryModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="categoryModalContent">
                <!-- Category configuration form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div class="modal" id="bulkEditModal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">Bulk Edit Categories</h3>
            <button class="modal-close" onclick="closeBulkEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="bulkEditContent">
                <!-- Bulk edit form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize category overview
    initializeCategoryOverview();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
});

function initializeCategoryOverview() {
    // Setup tooltips and other interactions
    console.log('Category overview initialized');
}

function addNewCategory() {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    loadCategoryForm();
}

function editCategory(categoryId) {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    loadCategoryForm(categoryId);
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function loadCategoryForm(categoryId = null) {
    const content = document.getElementById('categoryModalContent');
    content.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading category form...</div>';
    
    // Implementation for loading category form
    setTimeout(() => {
        content.innerHTML = '<p>Category configuration form will be implemented here</p>';
    }, 1000);
}

function bulkEditCategories() {
    document.getElementById('bulkEditModal').style.display = 'flex';
    loadBulkEditForm();
}

function closeBulkEditModal() {
    document.getElementById('bulkEditModal').style.display = 'none';
}

function loadBulkEditForm() {
    const content = document.getElementById('bulkEditContent');
    content.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading bulk edit form...</div>';
    
    // Implementation for loading bulk edit form
    setTimeout(() => {
        content.innerHTML = '<p>Bulk edit form will be implemented here</p>';
    }, 1000);
}

function validateAllCategories() {
    showLoading();
    
    fetch('<?= url('/admin/category-manager/validate-rules') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ competition_id: <?= $competition->id ?> })
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        
        if (result.success) {
            alert('All categories validated successfully!');
        } else {
            alert('Validation issues found. Please check the console for details.');
            console.log('Validation results:', result.validation_results);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function exportCategories(format) {
    const competitionId = <?= $competition->id ?>;
    window.location.href = `<?= url('/admin/category-manager/export-configuration') ?>?competition_id=${competitionId}&format=${format}`;
    
    // Hide menu
    document.getElementById('exportMenu').style.display = 'none';
}

function toggleCategoryActions(categoryId) {
    const menu = document.getElementById('categoryActions' + categoryId);
    
    // Hide all other menus
    document.querySelectorAll('.category-actions-menu').forEach(m => {
        if (m.id !== 'categoryActions' + categoryId) {
            m.style.display = 'none';
        }
    });
    
    // Toggle this menu
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function duplicateCategory(categoryId) {
    if (confirm('Create a duplicate of this category?')) {
        showLoading();
        
        // Implementation for duplicating category
        setTimeout(() => {
            hideLoading();
            alert('Category duplicated successfully!');
            window.location.reload();
        }, 1000);
    }
}

function viewTeams(categoryId) {
    window.location.href = `<?= url('/admin/teams') ?>?category_id=${categoryId}`;
}

function customizeRubric(categoryId) {
    window.location.href = `<?= url('/admin/category-manager/configure/') ?>${categoryId}#rubric`;
}

function setEquipmentRequirements(categoryId) {
    window.location.href = `<?= url('/admin/category-manager/configure/') ?>${categoryId}#equipment`;
}

function toggleCategoryStatus(categoryId) {
    const action = event.target.textContent.trim().includes('Activate') ? 'activate' : 'deactivate';
    
    if (confirm(`Are you sure you want to ${action} this category?`)) {
        showLoading();
        
        fetch('<?= url('/admin/category-manager/update-category') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                category_id: categoryId,
                category_data: {
                    is_active: action === 'activate'
                }
            })
        })
        .then(response => response.json())
        .then(result => {
            hideLoading();
            
            if (result.success) {
                window.location.reload();
            } else {
                alert('Error updating category status: ' + result.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone and will affect all associated teams.')) {
        showLoading();
        
        // Implementation for deleting category
        setTimeout(() => {
            hideLoading();
            alert('Category deleted successfully!');
            window.location.reload();
        }, 1000);
    }
}
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>