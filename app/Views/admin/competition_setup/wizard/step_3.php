<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competition-wizard-step3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($page_title ?? 'Competition Setup Wizard') ?></h1>
            <p class="text-muted">Step <?= $step ?> of 6: <?= htmlspecialchars($step_title ?? 'Category Setup') ?></p>
        </div>
        <div class="admin-actions">
            <a href="<?= url('/admin/competition-setup') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($step / 6) * 100 ?>%"></div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <small class="text-muted">Step <?= $step ?> of 6</small>
            <small class="text-muted"><?= round(($step / 6) * 100) ?>% Complete</small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> <?= htmlspecialchars($step_title ?? 'Category Setup') ?>
                    </h5>
                    <p class="card-text small mb-0 mt-2"><?= htmlspecialchars($step_description ?? 'Select and configure competition categories') ?></p>
                </div>
                <div class="card-body">
                    <form id="wizardStepForm" method="POST" action="<?= url('/admin/competition-setup/wizard/save-step') ?>">
                        <input type="hidden" name="step" value="<?= $step ?>">

                        <div class="form-group">
                            <label class="form-label">Available Categories</label>
                            <div class="row">
                                <?php if (!empty($available_categories)): ?>
                                    <?php foreach ($available_categories as $category): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card category-card">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input category-checkbox"
                                                           type="checkbox"
                                                           name="categories[]"
                                                           value="<?= $category['id'] ?>"
                                                           id="category_<?= $category['id'] ?>"
                                                           data-category-code="<?= htmlspecialchars($category['code'] ?? '') ?>"
                                                           data-category-name="<?= htmlspecialchars($category['name']) ?>">
                                                    <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                                        <?php if (!empty($category['code'])): ?>
                                                            <span class="badge badge-secondary ml-2"><?= htmlspecialchars($category['code']) ?></span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                                <?php if (!empty($category['description'])): ?>
                                                    <p class="small text-muted mt-2 mb-1"><?= htmlspecialchars($category['description']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($category['grade_range'])): ?>
                                                    <div class="small">
                                                        <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($category['grade_range']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>No categories found.</strong> You may need to create categories first.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Category Configuration -->
                        <div id="categoryConfiguration" style="display: none;" class="mt-4">
                            <h6>Category Configuration</h6>
                            <div id="categoryConfigFields"></div>
                        </div>

                        <!-- Mission Templates -->
                        <?php if (!empty($mission_templates)): ?>
                        <div class="form-group mt-4">
                            <label class="form-label">Mission Templates (Optional)</label>
                            <div class="row">
                                <?php foreach ($mission_templates as $template): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card mission-template-card">
                                        <div class="card-body p-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="default_mission_template" value="<?= $template['id'] ?>" id="mission_<?= $template['id'] ?>">
                                                <label class="form-check-label" for="mission_<?= $template['id'] ?>">
                                                    <strong><?= htmlspecialchars($template['name']) ?></strong>
                                                </label>
                                            </div>
                                            <?php if (!empty($template['description'])): ?>
                                                <p class="small text-muted mt-2 mb-0"><?= htmlspecialchars($template['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Navigation -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('/admin/competition-setup/wizard/step/2') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Previous
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Help Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle"></i> Category Setup Help
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Select Categories:</strong> Choose which competition categories will be available for this competition.</p>
                        <p><strong>Mission Templates:</strong> Optionally select a default mission template that will apply to all categories.</p>
                        <p><strong>Configuration:</strong> Each selected category can be configured with specific rules and limits.</p>
                    </div>
                </div>
            </div>

            <!-- Wizard Progress -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list-ol"></i> Wizard Steps
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 1. Basic Information
                        </div>
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 2. Phase Configuration
                        </div>
                        <div class="mb-2 text-primary">
                            <i class="fas fa-arrow-right"></i> <strong>3. Category Setup</strong>
                        </div>
                        <div class="mb-2 text-muted">
                            <i class="fas fa-circle"></i> 4. Registration Rules
                        </div>
                        <div class="mb-2 text-muted">
                            <i class="fas fa-circle"></i> 5. Competition Rules
                        </div>
                        <div class="mb-2 text-muted">
                            <i class="fas fa-circle"></i> 6. Review & Deploy
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saved Data -->
            <?php if (!empty($wizard_data)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-save"></i> Saved Data
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <?php if (isset($wizard_data['step_1'])): ?>
                        <div class="mb-2">
                            <strong>Competition:</strong> <?= htmlspecialchars($wizard_data['step_1']['name'] ?? 'N/A') ?>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($wizard_data['step_2'])): ?>
                        <div class="mb-2">
                            <strong>Phases:</strong> <?= count($wizard_data['step_2']['phases'] ?? []) ?> configured
                        </div>
                        <?php endif; ?>
                        <div class="text-muted">Auto-save enabled</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizardStepForm');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const categoryConfiguration = document.getElementById('categoryConfiguration');
    const configFields = document.getElementById('categoryConfigFields');

    // Handle category selection
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateCategoryConfiguration();
        });
    });

    function updateCategoryConfiguration() {
        const selectedCategories = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => ({
                id: cb.value,
                code: cb.dataset.categoryCode,
                name: cb.dataset.categoryName
            }));

        if (selectedCategories.length > 0) {
            categoryConfiguration.style.display = 'block';
            configFields.innerHTML = generateConfigFields(selectedCategories);
        } else {
            categoryConfiguration.style.display = 'none';
            configFields.innerHTML = '';
        }
    }

    function generateConfigFields(categories) {
        return categories.map(category => `
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">${category.name} Configuration</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Teams per School</label>
                                <input type="number" class="form-control" name="category_config[${category.id}][max_teams_per_school]" value="3" min="1" max="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Team Size</label>
                                <input type="number" class="form-control" name="category_config[${category.id}][team_size]" value="4" min="1" max="8">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Time Limit (minutes)</label>
                                <input type="number" class="form-control" name="category_config[${category.id}][time_limit_minutes]" value="15" min="5" max="60">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Attempts</label>
                                <input type="number" class="form-control" name="category_config[${category.id}][max_attempts]" value="3" min="1" max="5">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const selectedCategories = Array.from(categoryCheckboxes).filter(cb => cb.checked);
        if (selectedCategories.length === 0) {
            alert('Please select at least one category.');
            return;
        }

        // Prepare form data
        const formData = new FormData(form);

        // Submit via AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.next_step) {
                    window.location.href = `/admin/competition-setup/wizard/step/${data.next_step}`;
                } else {
                    window.location.href = '/admin/competition-setup/wizard/review';
                }
            } else {
                alert('Error: ' + (data.message || 'Failed to save step'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the step.');
        });
    });

    // Load saved data if available
    <?php if (!empty($wizard_data['step_3'])): ?>
    const savedData = <?= json_encode($wizard_data['step_3']) ?>;
    if (savedData.categories) {
        savedData.categories.forEach(categoryId => {
            const checkbox = document.getElementById(`category_${categoryId}`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        updateCategoryConfiguration();
    }
    <?php endif; ?>
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>