<?php 
$layout = 'admin';
$title = 'Team Composition Management';
ob_start(); 
?>

<div class="team-composition-dashboard">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-users"></i>
                Team Composition Management
            </h1>
            <p class="page-description">
                Manage team sizes, participant roles, and coach assignments with real-time validation
            </p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="bulkValidateTeams()">
                <i class="fas fa-check-double"></i>
                Bulk Validate All Teams
            </button>
            <button class="btn btn-info" onclick="refreshStatistics()">
                <i class="fas fa-sync-alt"></i>
                Refresh Statistics
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="statistics-section">
        <div class="stats-grid">
            <div class="stat-card complete">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $statistics['complete_teams'] ?? 0 ?></h3>
                    <p>Complete Teams</p>
                    <small><?= $statistics['total_teams'] > 0 ? round(($statistics['complete_teams'] ?? 0) / $statistics['total_teams'] * 100, 1) : 0 ?>% of total</small>
                </div>
            </div>

            <div class="stat-card incomplete">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $statistics['incomplete_teams'] ?? 0 ?></h3>
                    <p>Incomplete Teams</p>
                    <small>Need more participants</small>
                </div>
            </div>

            <div class="stat-card oversize">
                <div class="stat-icon">
                    <i class="fas fa-users-slash"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $statistics['oversize_teams'] ?? 0 ?></h3>
                    <p>Oversize Teams</p>
                    <small>Exceed maximum limit</small>
                </div>
            </div>

            <div class="stat-card participants">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $statistics['total_participants'] ?? 0 ?></h3>
                    <p>Total Participants</p>
                    <small>Avg: <?= $statistics['average_team_size'] ?? 0 ?> per team</small>
                </div>
            </div>

            <div class="stat-card coaches">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $statistics['total_coaches'] ?? 0 ?></h3>
                    <p>Total Coaches</p>
                    <small>Active assignments</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <div class="filters-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="team-search" placeholder="Search teams..." onkeyup="filterTeams()">
            </div>
            
            <div class="filter-group">
                <select id="status-filter" onchange="filterTeams()">
                    <option value="">All Statuses</option>
                    <option value="complete">Complete</option>
                    <option value="incomplete">Incomplete</option>
                    <option value="oversize">Oversize</option>
                    <option value="invalid">Invalid</option>
                </select>
            </div>

            <div class="filter-group">
                <select id="category-filter" onchange="filterTeams()">
                    <option value="">All Categories</option>
                    <!-- Categories will be populated by JavaScript -->
                </select>
            </div>

            <div class="filter-group">
                <select id="school-filter" onchange="filterTeams()">
                    <option value="">All Schools</option>
                    <!-- Schools will be populated by JavaScript -->
                </select>
            </div>
        </div>
    </div>

    <!-- Team Compositions Grid -->
    <div class="compositions-section">
        <div class="compositions-grid" id="compositions-grid">
            <?php foreach ($compositions as $compositionData): ?>
                <?php 
                $team = $compositionData['team'];
                $composition = $compositionData['composition'];
                $summary = $compositionData['summary'];
                $validation = $compositionData['validation'];
                ?>
                <div class="composition-card" 
                     data-team-id="<?= $team->id ?>"
                     data-status="<?= $composition->composition_status ?>"
                     data-category="<?= $team->category_id ?>"
                     data-school="<?= $team->school_id ?>">
                    
                    <!-- Card Header -->
                    <div class="card-header">
                        <div class="team-info">
                            <h3 class="team-name"><?= htmlspecialchars($team->name) ?></h3>
                            <p class="team-details">
                                <span class="category"><?= htmlspecialchars($team->category_name ?? 'Unknown Category') ?></span>
                                <span class="school"><?= htmlspecialchars($team->school_name ?? 'Unknown School') ?></span>
                            </p>
                        </div>
                        <div class="status-badge status-<?= $composition->composition_status ?>">
                            <?= ucfirst($composition->composition_status) ?>
                        </div>
                    </div>

                    <!-- Composition Status -->
                    <div class="composition-status">
                        <div class="size-indicator">
                            <div class="size-bar">
                                <div class="size-fill" style="width: <?= $summary['max_allowed'] > 0 ? ($summary['participant_count'] / $summary['max_allowed'] * 100) : 0 ?>%"></div>
                            </div>
                            <div class="size-text">
                                <?= $summary['participant_count'] ?> / <?= $summary['max_allowed'] ?> participants
                            </div>
                        </div>
                        
                        <?php if ($summary['slots_available'] > 0): ?>
                            <div class="slots-available">
                                <i class="fas fa-plus-circle"></i>
                                <?= $summary['slots_available'] ?> slots available
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Participants Preview -->
                    <div class="participants-preview">
                        <div class="participants-list">
                            <?php foreach (array_slice($summary['participants'], 0, 4) as $participant): ?>
                                <div class="participant-item">
                                    <span class="participant-role role-<?= $participant['role'] ?>"><?= ucfirst($participant['role']) ?></span>
                                    <span class="participant-status status-<?= $participant['eligibility'] ?>"><?= ucfirst($participant['eligibility']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($summary['participants']) > 4): ?>
                                <div class="participant-more">+<?= count($summary['participants']) - 4 ?> more</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Validation Status -->
                    <?php if (!$validation['is_valid']): ?>
                        <div class="validation-errors">
                            <div class="error-summary">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= $validation['error_count'] ?> validation error(s)
                            </div>
                            <div class="error-details">
                                <?php foreach ($validation['errors'] as $field => $errors): ?>
                                    <?php foreach (array_slice($errors, 0, 2) as $error): ?>
                                        <div class="error-item"><?= htmlspecialchars($error) ?></div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Last Validation -->
                    <div class="last-validation">
                        <small>
                            <i class="fas fa-clock"></i>
                            Last validated: <?= $summary['last_validated'] ? date('M j, Y g:i A', strtotime($summary['last_validated'])) : 'Never' ?>
                        </small>
                    </div>

                    <!-- Card Actions -->
                    <div class="card-actions">
                        <button class="btn btn-sm btn-primary" onclick="viewTeamComposition(<?= $team->id ?>)">
                            <i class="fas fa-eye"></i>
                            View Details
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="validateTeam(<?= $team->id ?>)">
                            <i class="fas fa-check"></i>
                            Validate
                        </button>
                        <?php if ($user_role === 'admin' || $user_role === 'school_coordinator'): ?>
                            <button class="btn btn-sm btn-info" onclick="editTeamComposition(<?= $team->id ?>)">
                                <i class="fas fa-edit"></i>
                                Edit
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($compositions)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>No Team Compositions Found</h3>
                <p>There are no team compositions to display based on your current permissions.</p>
                <?php if ($user_role === 'admin'): ?>
                    <button class="btn btn-primary" onclick="createTeam()">
                        <i class="fas fa-plus"></i>
                        Create New Team
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Validation Modal -->
<div class="modal" id="validation-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Validation Results</h3>
            <button class="modal-close" onclick="closeModal('validation-modal')">&times;</button>
        </div>
        <div class="modal-body" id="validation-results">
            <!-- Validation results will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('validation-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Bulk Validation Modal -->
<div class="modal" id="bulk-validation-modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Bulk Validation Results</h3>
            <button class="modal-close" onclick="closeModal('bulk-validation-modal')">&times;</button>
        </div>
        <div class="modal-body" id="bulk-validation-results">
            <!-- Bulk validation results will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="exportValidationReport()">
                <i class="fas fa-download"></i>
                Export Report
            </button>
            <button class="btn btn-secondary" onclick="closeModal('bulk-validation-modal')">Close</button>
        </div>
    </div>
</div>

<style>
.team-composition-dashboard {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e6ed;
}

.page-title {
    font-size: 28px;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-description {
    color: #7f8c8d;
    margin: 5px 0 0 0;
    font-size: 16px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

/* Statistics Section */
.statistics-section {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-card.complete .stat-icon { background: #27ae60; }
.stat-card.incomplete .stat-icon { background: #f39c12; }
.stat-card.oversize .stat-icon { background: #e74c3c; }
.stat-card.participants .stat-icon { background: #3498db; }
.stat-card.coaches .stat-icon { background: #9b59b6; }

.stat-content h3 {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.stat-content p {
    font-size: 14px;
    color: #7f8c8d;
    margin: 2px 0;
}

.stat-content small {
    font-size: 12px;
    color: #95a5a6;
}

/* Filters Section */
.filters-section {
    margin-bottom: 25px;
}

.filters-container {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
}

.search-box input {
    width: 100%;
    padding: 10px 12px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.filter-group select {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    min-width: 150px;
}

/* Compositions Grid */
.compositions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.composition-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.composition-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.team-name {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.team-details {
    font-size: 12px;
    color: #7f8c8d;
    margin: 4px 0 0 0;
}

.team-details .category,
.team-details .school {
    margin-right: 15px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-complete { background: #d5f4e6; color: #27ae60; }
.status-incomplete { background: #fef9e7; color: #f39c12; }
.status-oversize { background: #fadbd8; color: #e74c3c; }
.status-invalid { background: #f8f9fa; color: #6c757d; }

/* Composition Status */
.composition-status {
    margin-bottom: 15px;
}

.size-indicator {
    margin-bottom: 8px;
}

.size-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.size-fill {
    height: 100%;
    background: linear-gradient(90deg, #27ae60, #2ecc71);
    transition: width 0.3s ease;
}

.size-text {
    font-size: 13px;
    color: #7f8c8d;
    font-weight: 500;
}

.slots-available {
    font-size: 12px;
    color: #27ae60;
    font-weight: 500;
}

.slots-available i {
    margin-right: 4px;
}

/* Participants Preview */
.participants-preview {
    margin-bottom: 15px;
}

.participants-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.participant-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}

.participant-role {
    padding: 2px 6px;
    border-radius: 12px;
    font-weight: 500;
}

.role-team_leader { background: #e8f4fd; color: #2980b9; }
.role-programmer { background: #f0f8e8; color: #27ae60; }
.role-builder { background: #fff3e0; color: #f39c12; }
.role-designer { background: #f3e5f5; color: #9b59b6; }
.role-regular { background: #f8f9fa; color: #6c757d; }

.participant-status {
    font-size: 10px;
    padding: 2px 5px;
    border-radius: 8px;
}

.status-eligible { background: #d5f4e6; color: #27ae60; }
.status-pending { background: #fef9e7; color: #f39c12; }
.status-ineligible { background: #fadbd8; color: #e74c3c; }

.participant-more {
    font-size: 11px;
    color: #95a5a6;
    font-style: italic;
}

/* Validation Errors */
.validation-errors {
    background: #fadbd8;
    border: 1px solid #e74c3c;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
}

.error-summary {
    font-size: 12px;
    font-weight: 600;
    color: #e74c3c;
    margin-bottom: 5px;
}

.error-summary i {
    margin-right: 5px;
}

.error-details {
    font-size: 11px;
}

.error-item {
    color: #c0392b;
    margin-bottom: 2px;
}

/* Last Validation */
.last-validation {
    margin-bottom: 15px;
    padding-top: 10px;
    border-top: 1px solid #ecf0f1;
}

.last-validation small {
    color: #95a5a6;
    font-size: 11px;
}

.last-validation i {
    margin-right: 4px;
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.card-actions .btn {
    flex: 1;
    min-width: 80px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.empty-icon {
    font-size: 64px;
    color: #bdc3c7;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow: hidden;
}

.modal-content.large {
    max-width: 800px;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e0e6ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e0e6ed;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #95a5a6;
    cursor: pointer;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .header-actions {
        width: 100%;
    }
    
    .header-actions .btn {
        flex: 1;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    
    .filters-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .compositions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// JavaScript functions for team composition dashboard
let currentTeams = [];
let filteredTeams = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadTeamData();
    populateFilterOptions();
});

function loadTeamData() {
    // This would typically load from the server
    // For now, we'll use the data already rendered
    currentTeams = Array.from(document.querySelectorAll('.composition-card')).map(card => ({
        id: card.dataset.teamId,
        status: card.dataset.status,
        category: card.dataset.category,
        school: card.dataset.school,
        element: card
    }));
    filteredTeams = [...currentTeams];
}

function populateFilterOptions() {
    // Get unique categories and schools from current teams
    const categories = [...new Set(currentTeams.map(team => team.category))];
    const schools = [...new Set(currentTeams.map(team => team.school))];
    
    const categoryFilter = document.getElementById('category-filter');
    const schoolFilter = document.getElementById('school-filter');
    
    // Populate category filter
    categories.forEach(categoryId => {
        if (categoryId) {
            const option = document.createElement('option');
            option.value = categoryId;
            option.textContent = `Category ${categoryId}`; // Would get real category name from server
            categoryFilter.appendChild(option);
        }
    });
    
    // Populate school filter
    schools.forEach(schoolId => {
        if (schoolId) {
            const option = document.createElement('option');
            option.value = schoolId;
            option.textContent = `School ${schoolId}`; // Would get real school name from server
            schoolFilter.appendChild(option);
        }
    });
}

function filterTeams() {
    const searchTerm = document.getElementById('team-search').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const categoryFilter = document.getElementById('category-filter').value;
    const schoolFilter = document.getElementById('school-filter').value;
    
    filteredTeams = currentTeams.filter(team => {
        const teamName = team.element.querySelector('.team-name').textContent.toLowerCase();
        const matchesSearch = teamName.includes(searchTerm);
        const matchesStatus = !statusFilter || team.status === statusFilter;
        const matchesCategory = !categoryFilter || team.category === categoryFilter;
        const matchesSchool = !schoolFilter || team.school === schoolFilter;
        
        return matchesSearch && matchesStatus && matchesCategory && matchesSchool;
    });
    
    // Show/hide team cards
    currentTeams.forEach(team => {
        const isVisible = filteredTeams.includes(team);
        team.element.style.display = isVisible ? 'block' : 'none';
    });
}

function viewTeamComposition(teamId) {
    window.location.href = `/team/composition/${teamId}`;
}

function editTeamComposition(teamId) {
    window.location.href = `/team/composition/${teamId}/edit`;
}

function validateTeam(teamId) {
    showLoadingSpinner();
    
    fetch('/team/composition/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_id: teamId,
            context: 'real_time'
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        if (data.success) {
            showValidationResults(data.validation);
        } else {
            showError('Validation failed: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingSpinner();
        showError('Validation request failed');
        console.error('Validation error:', error);
    });
}

function bulkValidateTeams() {
    const teamIds = filteredTeams.map(team => team.id);
    
    if (teamIds.length === 0) {
        showError('No teams to validate');
        return;
    }
    
    showLoadingSpinner('Validating all teams...');
    
    fetch('/team/composition/bulk-validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_ids: teamIds,
            context: 'bulk_import'
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        if (data.success) {
            showBulkValidationResults(data.bulk_validation);
        } else {
            showError('Bulk validation failed: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingSpinner();
        showError('Bulk validation request failed');
        console.error('Bulk validation error:', error);
    });
}

function showValidationResults(validation) {
    const modal = document.getElementById('validation-modal');
    const resultsDiv = document.getElementById('validation-results');
    
    let html = `
        <div class="validation-summary">
            <div class="validation-status ${validation.is_valid ? 'valid' : 'invalid'}">
                <i class="fas ${validation.is_valid ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
                ${validation.is_valid ? 'Valid' : 'Invalid'} Team Composition
            </div>
        </div>
    `;
    
    if (!validation.is_valid && validation.errors) {
        html += '<div class="validation-errors-detail">';
        html += '<h4>Validation Errors:</h4>';
        for (const [field, errors] of Object.entries(validation.errors)) {
            html += `<div class="error-group">`;
            html += `<strong>${field.replace('_', ' ').toUpperCase()}:</strong>`;
            html += '<ul>';
            errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        html += '</div>';
    }
    
    if (validation.warnings && Object.keys(validation.warnings).length > 0) {
        html += '<div class="validation-warnings-detail">';
        html += '<h4>Warnings:</h4>';
        for (const [field, warnings] of Object.entries(validation.warnings)) {
            html += `<div class="warning-group">`;
            html += `<strong>${field.replace('_', ' ').toUpperCase()}:</strong>`;
            html += '<ul>';
            warnings.forEach(warning => {
                html += `<li>${warning}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        html += '</div>';
    }
    
    resultsDiv.innerHTML = html;
    modal.classList.add('show');
}

function showBulkValidationResults(bulkValidation) {
    const modal = document.getElementById('bulk-validation-modal');
    const resultsDiv = document.getElementById('bulk-validation-results');
    
    let html = `
        <div class="bulk-validation-summary">
            <h4>Validation Summary</h4>
            <div class="summary-stats">
                <div class="summary-stat">
                    <span class="stat-value">${bulkValidation.total_teams}</span>
                    <span class="stat-label">Total Teams</span>
                </div>
                <div class="summary-stat valid">
                    <span class="stat-value">${bulkValidation.valid_teams}</span>
                    <span class="stat-label">Valid Teams</span>
                </div>
                <div class="summary-stat invalid">
                    <span class="stat-value">${bulkValidation.invalid_teams}</span>
                    <span class="stat-label">Invalid Teams</span>
                </div>
            </div>
        </div>
    `;
    
    if (bulkValidation.summary && bulkValidation.summary.common_errors) {
        html += '<div class="common-issues">';
        html += '<h4>Common Issues:</h4>';
        for (const [field, count] of Object.entries(bulkValidation.summary.common_errors)) {
            html += `<div class="issue-item">
                <span class="issue-field">${field.replace('_', ' ')}</span>
                <span class="issue-count">${count} teams affected</span>
            </div>`;
        }
        html += '</div>';
    }
    
    resultsDiv.innerHTML = html;
    modal.classList.add('show');
}

function refreshStatistics() {
    showLoadingSpinner('Refreshing statistics...');
    
    fetch('/team/composition/statistics', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        if (data.success) {
            updateStatisticsDisplay(data.statistics);
            showSuccess('Statistics refreshed successfully');
        } else {
            showError('Failed to refresh statistics: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingSpinner();
        showError('Statistics request failed');
        console.error('Statistics error:', error);
    });
}

function updateStatisticsDisplay(statistics) {
    // Update the statistics cards with new data
    document.querySelector('.stat-card.complete h3').textContent = statistics.complete_teams || 0;
    document.querySelector('.stat-card.incomplete h3').textContent = statistics.incomplete_teams || 0;
    document.querySelector('.stat-card.oversize h3').textContent = statistics.oversize_teams || 0;
    document.querySelector('.stat-card.participants h3').textContent = statistics.total_participants || 0;
    document.querySelector('.stat-card.coaches h3').textContent = statistics.total_coaches || 0;
    
    // Update percentage in complete teams
    const completePercentage = statistics.total_teams > 0 
        ? Math.round((statistics.complete_teams || 0) / statistics.total_teams * 100) 
        : 0;
    document.querySelector('.stat-card.complete small').textContent = `${completePercentage}% of total`;
    
    // Update average team size
    document.querySelector('.stat-card.participants small').textContent = `Avg: ${statistics.average_team_size || 0} per team`;
}

function exportValidationReport() {
    // This would generate and download a validation report
    showInfo('Validation report export feature coming soon');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function createTeam() {
    window.location.href = '/admin/teams/create';
}

// Utility functions
function showLoadingSpinner(message = 'Loading...') {
    // Implementation for loading spinner
    console.log('Loading:', message);
}

function hideLoadingSpinner() {
    // Implementation to hide loading spinner
    console.log('Loading complete');
}

function showSuccess(message) {
    // Implementation for success notification
    console.log('Success:', message);
}

function showError(message) {
    // Implementation for error notification
    console.log('Error:', message);
}

function showInfo(message) {
    // Implementation for info notification
    console.log('Info:', message);
}

// Close modal on outside click
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>