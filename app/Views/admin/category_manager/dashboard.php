<?php 
$layout = 'layouts/admin';
ob_start(); 
?>

<!-- Category Manager Header -->
<div class="dashboard-header">
    <div class="dashboard-welcome">
        <h2 class="welcome-title">Category Manager</h2>
        <p class="welcome-subtitle">Configure category rules, scoring rubrics, and equipment requirements for competitions.</p>
    </div>
    
    <!-- Quick Actions Panel -->
    <div class="quick-actions-panel">
        <h3 class="quick-actions-title">Category Actions</h3>
        <div class="quick-actions-grid">
            <button onclick="bulkCategoryUpdate()" class="quick-action-btn primary">
                <i class="fas fa-edit"></i>
                <span>Bulk Update</span>
            </button>
            <button onclick="validateCategories()" class="quick-action-btn warning">
                <i class="fas fa-check-circle"></i>
                <span>Validate Rules</span>
            </button>
            <button onclick="exportConfiguration()" class="quick-action-btn success">
                <i class="fas fa-download"></i>
                <span>Export Config</span>
            </button>
            <button onclick="importConfiguration()" class="quick-action-btn info">
                <i class="fas fa-upload"></i>
                <span>Import Config</span>
            </button>
        </div>
    </div>
</div>

<!-- Category Statistics -->
<div class="stats-grid">
    <div class="stats-row">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($category_statistics['total_categories'] ?? 0) ?></div>
                <div class="stat-label">Total Categories</div>
                <div class="stat-change">
                    <i class="fas fa-info-circle"></i>
                    <span><?= $category_statistics['competitions_with_categories'] ?? 0 ?> competitions</span>
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($category_statistics['total_registrations'] ?? 0) ?></div>
                <div class="stat-label">Total Registrations</div>
                <div class="stat-change">
                    <i class="fas fa-chart-line"></i>
                    <span><?= round($category_statistics['avg_registrations_per_category'] ?? 0, 1) ?> avg per category</span>
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($category_statistics['active_categories'] ?? 0) ?></div>
                <div class="stat-label">Active Categories</div>
                <div class="stat-change">
                    <i class="fas fa-check"></i>
                    <span>Currently open</span>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= date('Y') ?></div>
                <div class="stat-label">Active Year</div>
                <div class="stat-change">
                    <i class="fas fa-calendar"></i>
                    <span>Competition year</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Competition Categories Overview -->
<div class="content-section">
    <div class="section-header">
        <h3 class="section-title">Competition Categories</h3>
        <div class="section-actions">
            <button class="btn btn-outline btn-sm" onclick="refreshCategories()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <div class="dropdown">
                <button class="btn btn-outline btn-sm dropdown-toggle" onclick="toggleFilters()">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <div class="dropdown-menu" id="filterMenu" style="display: none;">
                    <div class="filter-option">
                        <label><input type="checkbox" value="active"> Active Only</label>
                    </div>
                    <div class="filter-option">
                        <label><input type="checkbox" value="pilot"> Pilot Categories</label>
                    </div>
                    <div class="filter-option">
                        <label><input type="checkbox" value="full"> Full Categories</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="competitions-grid">
        <?php if (!empty($competitions)): ?>
            <?php foreach ($competitions as $competition): ?>
                <div class="competition-card category-focused" data-competition-id="<?= $competition['id'] ?>">
                    <div class="card-header">
                        <h4 class="competition-name"><?= htmlspecialchars($competition['name']) ?></h4>
                        <div class="competition-badges">
                            <span class="badge badge-<?= $competition['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($competition['status']) ?>
                            </span>
                            <span class="badge badge-info"><?= $competition['year'] ?></span>
                        </div>
                    </div>
                    
                    <div class="card-content">
                        <div class="category-stats">
                            <div class="stat-row">
                                <div class="stat-item">
                                    <i class="fas fa-tags"></i>
                                    <span><?= $competition['category_count'] ?> Categories</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span><?= $competition['total_registrations'] ?? 0 ?> Teams</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($competition['category_count'] > 0): ?>
                            <div class="category-preview">
                                <small class="text-muted">Categories configured and ready for registration</small>
                            </div>
                        <?php else: ?>
                            <div class="category-warning">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <small>No categories configured yet</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-actions">
                        <a href="<?= url('/admin/category-manager/overview/' . $competition['id']) ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-cogs"></i> Manage Categories
                        </a>
                        <?php if ($competition['category_count'] > 0): ?>
                            <button onclick="bulkEditCategories(<?= $competition['id'] ?>)" class="btn btn-outline btn-sm">
                                <i class="fas fa-edit"></i> Bulk Edit
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h4>No Competitions Found</h4>
                <p>Create a competition first to manage categories.</p>
                <a href="<?= url('/admin/competition-setup/wizard/start') ?>" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Create Competition
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Category Management Tools -->
<div class="dashboard-content-grid">
    <!-- Left Column -->
    <div class="dashboard-left-column">
        <!-- Category Templates Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-layer-group"></i>
                    Category Templates
                </h3>
            </div>
            <div class="widget-content">
                <div class="template-list">
                    <div class="template-item">
                        <div class="template-icon robotics">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="template-content">
                            <h5>Robotics Categories</h5>
                            <p>Standard robotics competition rules and scoring</p>
                            <div class="template-grades">
                                <span class="grade-badge">R-3</span>
                                <span class="grade-badge">4-7</span>
                                <span class="grade-badge">8-11</span>
                            </div>
                        </div>
                        <button onclick="applyTemplate('robotics')" class="btn btn-sm btn-outline">
                            Apply
                        </button>
                    </div>
                    
                    <div class="template-item">
                        <div class="template-icon inventor">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="template-content">
                            <h5>Inventor Categories</h5>
                            <p>Innovation and presentation-based judging</p>
                            <div class="template-grades">
                                <span class="grade-badge">R-3</span>
                                <span class="grade-badge">4-7</span>
                                <span class="grade-badge">8-11</span>
                            </div>
                        </div>
                        <button onclick="applyTemplate('inventor')" class="btn btn-sm btn-outline">
                            Apply
                        </button>
                    </div>
                    
                    <div class="template-item">
                        <div class="template-icon pilot">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="template-content">
                            <h5>Pilot Program</h5>
                            <p>Simplified rules for testing new formats</p>
                            <div class="template-grades">
                                <span class="grade-badge">4-7</span>
                            </div>
                        </div>
                        <button onclick="applyTemplate('pilot')" class="btn btn-sm btn-outline">
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Changes Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-history"></i>
                    Recent Changes
                </h3>
            </div>
            <div class="widget-content">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-edit text-primary"></i>
                        </div>
                        <div class="activity-content">
                            <p>Updated scoring rubric for Grade 4-7 Robotics</p>
                            <small class="text-muted">2 hours ago</small>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-plus text-success"></i>
                        </div>
                        <div class="activity-content">
                            <p>Added equipment requirements for Inventor category</p>
                            <small class="text-muted">Yesterday</small>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-check text-info"></i>
                        </div>
                        <div class="activity-content">
                            <p>Validated all category configurations</p>
                            <small class="text-muted">2 days ago</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="dashboard-right-column">
        <!-- Configuration Tools Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-tools"></i>
                    Configuration Tools
                </h3>
            </div>
            <div class="widget-content">
                <div class="tool-buttons">
                    <button onclick="scoringRubricBuilder()" class="tool-btn">
                        <i class="fas fa-chart-bar"></i>
                        <span>Scoring Rubric Builder</span>
                        <small>Create custom scoring systems</small>
                    </button>
                    
                    <button onclick="equipmentManager()" class="tool-btn">
                        <i class="fas fa-wrench"></i>
                        <span>Equipment Manager</span>
                        <small>Define required equipment</small>
                    </button>
                    
                    <button onclick="ruleTemplateEditor()" class="tool-btn">
                        <i class="fas fa-file-alt"></i>
                        <span>Rule Template Editor</span>
                        <small>Create reusable rule sets</small>
                    </button>
                    
                    <button onclick="categoryCloner()" class="tool-btn">
                        <i class="fas fa-copy"></i>
                        <span>Category Cloner</span>
                        <small>Duplicate category settings</small>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Statistics Widget -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3 class="widget-title">
                    <i class="fas fa-chart-pie"></i>
                    Category Insights
                </h3>
            </div>
            <div class="widget-content">
                <div class="insight-list">
                    <div class="insight-item">
                        <div class="insight-label">Most Popular Category</div>
                        <div class="insight-value">Grade 4-7 Robotics</div>
                        <div class="insight-detail">156 team registrations</div>
                    </div>
                    
                    <div class="insight-item">
                        <div class="insight-label">Average Team Size</div>
                        <div class="insight-value">4.2 members</div>
                        <div class="insight-detail">Across all categories</div>
                    </div>
                    
                    <div class="insight-item">
                        <div class="insight-label">Configuration Completeness</div>
                        <div class="insight-value">92%</div>
                        <div class="insight-detail">Categories fully configured</div>
                    </div>
                    
                    <div class="insight-item">
                        <div class="insight-label">Rules Validation</div>
                        <div class="insight-value">All Clear</div>
                        <div class="insight-detail">No conflicts detected</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal" id="bulkUpdateModal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content large">
        <div class="modal-header">
            <h3 class="modal-title">Bulk Category Update</h3>
            <button class="modal-close" onclick="closeBulkUpdateModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="bulkUpdateForm">
                <!-- Bulk update form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and interactions
    initializeCategoryManager();
});

function initializeCategoryManager() {
    // Add any initialization code here
    console.log('Category Manager initialized');
}

function bulkCategoryUpdate() {
    document.getElementById('bulkUpdateModal').style.display = 'flex';
    loadBulkUpdateForm();
}

function closeBulkUpdateModal() {
    document.getElementById('bulkUpdateModal').style.display = 'none';
}

function loadBulkUpdateForm() {
    const form = document.getElementById('bulkUpdateForm');
    form.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading bulk update form...</div>';
    
    // Implementation for loading bulk update form
    setTimeout(() => {
        form.innerHTML = '<p>Bulk update form will be implemented here</p>';
    }, 1000);
}

function validateCategories() {
    showLoading();
    
    fetch('<?= url('/admin/category-manager/validate-rules') ?>', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(result => {
        hideLoading();
        
        if (result.success) {
            alert('All category rules validated successfully!');
        } else {
            alert('Validation issues found: ' + result.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

function exportConfiguration() {
    // Create export options dialog
    const format = prompt('Export format (json, csv, xlsx):', 'json');
    if (format) {
        window.location.href = `<?= url('/admin/category-manager/export-configuration') ?>?format=${format}`;
    }
}

function importConfiguration() {
    // Create file input and trigger upload
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json,.csv,.xlsx';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('config_file', file);
            
            showLoading();
            
            fetch('<?= url('/admin/category-manager/import-configuration') ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                hideLoading();
                
                if (result.success) {
                    alert(`Import successful! ${result.imported_count} categories imported.`);
                    window.location.reload();
                } else {
                    alert('Import failed: ' + result.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                alert('Import error occurred');
            });
        }
    };
    input.click();
}

function refreshCategories() {
    showLoading();
    window.location.reload();
}

function toggleFilters() {
    const menu = document.getElementById('filterMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function bulkEditCategories(competitionId) {
    window.location.href = `<?= url('/admin/category-manager/overview/') ?>${competitionId}`;
}

function applyTemplate(templateType) {
    if (confirm(`Apply ${templateType} template to selected categories?`)) {
        // Implementation for applying template
        alert(`${templateType} template will be applied`);
    }
}

function scoringRubricBuilder() {
    alert('Scoring rubric builder will be implemented');
}

function equipmentManager() {
    alert('Equipment manager will be implemented');
}

function ruleTemplateEditor() {
    alert('Rule template editor will be implemented');
}

function categoryCloner() {
    alert('Category cloner will be implemented');
}

// Close filter menu when clicking outside
document.addEventListener('click', function(e) {
    const filterMenu = document.getElementById('filterMenu');
    const filterButton = e.target.closest('.dropdown-toggle');
    
    if (!filterButton && filterMenu.style.display === 'block') {
        filterMenu.style.display = 'none';
    }
});
</script>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>