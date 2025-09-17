<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="admin-competition-wizard-step5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($page_title ?? 'Competition Setup Wizard') ?></h1>
            <p class="text-muted">Step <?= $step ?> of 6: <?= htmlspecialchars($step_title ?? 'Competition Rules') ?></p>
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
                        <i class="fas fa-gavel"></i> <?= htmlspecialchars($step_title ?? 'Competition Rules') ?>
                    </h5>
                    <p class="card-text small mb-0 mt-2"><?= htmlspecialchars($step_description ?? 'Set up scoring systems and competition rules') ?></p>
                </div>
                <div class="card-body">
                    <form id="wizardStepForm" method="POST" action="<?= url('/admin/competition-setup/wizard/save-step') ?>">
                        <input type="hidden" name="step" value="<?= $step ?>">

                        <!-- Scoring System -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-calculator"></i> Scoring System
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="scoring_method">Scoring Method</label>
                                        <select class="form-control" id="scoring_method" name="scoring_method" required>
                                            <option value="">Select scoring method...</option>
                                            <option value="points_based">Points-Based System</option>
                                            <option value="time_based">Time-Based Scoring</option>
                                            <option value="combined">Combined Points & Time</option>
                                            <option value="rubric_based">Rubric-Based Assessment</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_score">Maximum Score</label>
                                        <input type="number" class="form-control" id="max_score" name="max_score" value="100" min="1" max="1000">
                                        <small class="form-text text-muted">Maximum possible score for each attempt</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="time_limit">Time Limit (minutes)</label>
                                        <input type="number" class="form-control" id="time_limit" name="time_limit" value="15" min="5" max="120">
                                        <small class="form-text text-muted">Time limit per attempt</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_attempts">Maximum Attempts</label>
                                        <input type="number" class="form-control" id="max_attempts" name="max_attempts" value="3" min="1" max="5">
                                        <small class="form-text text-muted">Number of attempts allowed per team</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Judging Configuration -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-users"></i> Judging Configuration
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="judges_per_team">Judges per Team</label>
                                        <select class="form-control" id="judges_per_team" name="judges_per_team">
                                            <option value="1">1 Judge</option>
                                            <option value="2">2 Judges</option>
                                            <option value="3" selected>3 Judges</option>
                                            <option value="panel">Judging Panel</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="score_aggregation">Score Aggregation Method</label>
                                        <select class="form-control" id="score_aggregation" name="score_aggregation">
                                            <option value="average">Average of All Scores</option>
                                            <option value="highest">Highest Score</option>
                                            <option value="median">Median Score</option>
                                            <option value="drop_lowest">Drop Lowest, Average Rest</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="anonymous_judging" name="anonymous_judging" value="1">
                                    <label class="form-check-label" for="anonymous_judging">
                                        <strong>Anonymous Judging</strong>
                                    </label>
                                    <small class="form-text text-muted">Judges cannot see team names or schools during scoring</small>
                                </div>
                            </div>
                        </div>

                        <!-- Competition Format -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-sitemap"></i> Competition Format
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="competition_format">Competition Format</label>
                                        <select class="form-control" id="competition_format" name="competition_format">
                                            <option value="elimination">Single Elimination</option>
                                            <option value="round_robin">Round Robin</option>
                                            <option value="swiss">Swiss System</option>
                                            <option value="qualification" selected>Qualification & Finals</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="advancement_criteria">Advancement Criteria</label>
                                        <select class="form-control" id="advancement_criteria" name="advancement_criteria">
                                            <option value="top_scores">Top Scores</option>
                                            <option value="percentage">Top Percentage</option>
                                            <option value="fixed_number">Fixed Number</option>
                                            <option value="threshold">Score Threshold</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="teams_advance">Teams to Advance</label>
                                        <input type="number" class="form-control" id="teams_advance" name="teams_advance" value="6" min="1" max="50">
                                        <small class="form-text text-muted">Number of teams advancing to next phase</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tie_breaking_method">Tie Breaking Method</label>
                                        <select class="form-control" id="tie_breaking_method" name="tie_breaking_method">
                                            <option value="time">Fastest Time</option>
                                            <option value="attempts">Fewer Attempts</option>
                                            <option value="consistency">Most Consistent</option>
                                            <option value="judge_decision">Judge Decision</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Penalties and Bonuses -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-balance-scale"></i> Penalties and Bonuses
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Penalty Types</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="penalty_time" name="penalty_types[]" value="time_penalty">
                                            <label class="form-check-label" for="penalty_time">
                                                Time Penalties
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="penalty_points" name="penalty_types[]" value="point_deduction">
                                            <label class="form-check-label" for="penalty_points">
                                                Point Deductions
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="penalty_disqualification" name="penalty_types[]" value="disqualification">
                                            <label class="form-check-label" for="penalty_disqualification">
                                                Disqualification
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Bonus Types</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="bonus_innovation" name="bonus_types[]" value="innovation">
                                            <label class="form-check-label" for="bonus_innovation">
                                                Innovation Bonus
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="bonus_teamwork" name="bonus_types[]" value="teamwork">
                                            <label class="form-check-label" for="bonus_teamwork">
                                                Teamwork Bonus
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="bonus_speed" name="bonus_types[]" value="speed">
                                            <label class="form-check-label" for="bonus_speed">
                                                Speed Bonus
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Competition Rules Text -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-alt"></i> Detailed Rules
                            </h6>
                            <div class="form-group">
                                <label for="competition_rules">Competition Rules Document</label>
                                <textarea class="form-control" id="competition_rules" name="competition_rules" rows="8"
                                          placeholder="Enter detailed competition rules, procedures, and guidelines..."></textarea>
                                <small class="form-text text-muted">Comprehensive rules that will be shared with participants</small>
                            </div>
                            <div class="form-group">
                                <label for="judging_criteria">Judging Criteria</label>
                                <textarea class="form-control" id="judging_criteria" name="judging_criteria" rows="4"
                                          placeholder="Describe how teams will be evaluated..."></textarea>
                                <small class="form-text text-muted">Specific criteria judges will use for evaluation</small>
                            </div>
                        </div>

                        <!-- Equipment and Resources -->
                        <div class="form-section mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-tools"></i> Equipment and Resources
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="allowed_equipment">Allowed Equipment</label>
                                        <textarea class="form-control" id="allowed_equipment" name="allowed_equipment" rows="3"
                                                  placeholder="List permitted equipment and robots..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="provided_materials">Provided Materials</label>
                                        <textarea class="form-control" id="provided_materials" name="provided_materials" rows="3"
                                                  placeholder="Materials provided by organizers..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="inspection_required" name="inspection_required" value="1">
                                    <label class="form-check-label" for="inspection_required">
                                        <strong>Equipment Inspection Required</strong>
                                    </label>
                                    <small class="form-text text-muted">Teams must have equipment inspected before competition</small>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('/admin/competition-setup/wizard/step/4') ?>" class="btn btn-outline-secondary">
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
                        <i class="fas fa-question-circle"></i> Competition Rules Help
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Scoring:</strong> Choose the primary method for evaluating team performance.</p>
                        <p><strong>Judging:</strong> Configure how many judges evaluate each team and how scores are combined.</p>
                        <p><strong>Format:</strong> Select the competition structure and advancement criteria.</p>
                        <p><strong>Rules:</strong> Document all regulations participants need to know.</p>
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
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 3. Category Setup
                        </div>
                        <div class="mb-2 text-success">
                            <i class="fas fa-check"></i> 4. Registration Rules
                        </div>
                        <div class="mb-2 text-primary">
                            <i class="fas fa-arrow-right"></i> <strong>5. Competition Rules</strong>
                        </div>
                        <div class="mb-2 text-muted">
                            <i class="fas fa-circle"></i> 6. Review & Deploy
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scoring Preview -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> Scoring Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="scoringPreview" class="small text-muted">
                        Configure scoring to see preview
                    </div>
                </div>
            </div>

            <!-- Rules Templates -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-file-copy"></i> Quick Templates
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTemplate('robotics')">
                            Robotics Template
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTemplate('inventor')">
                            Inventor Template
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTemplate('pilot')">
                            Pilot Programme
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizardStepForm');
    const scoringMethod = document.getElementById('scoring_method');
    const maxScore = document.getElementById('max_score');
    const timeLimit = document.getElementById('time_limit');
    const judgesPerTeam = document.getElementById('judges_per_team');

    // Update scoring preview
    function updateScoringPreview() {
        const preview = document.getElementById('scoringPreview');
        let text = '';

        if (scoringMethod.value) {
            text += `<div class="mb-1"><strong>Method:</strong> ${scoringMethod.options[scoringMethod.selectedIndex].text}</div>`;
            text += `<div class="mb-1"><strong>Max Score:</strong> ${maxScore.value} points</div>`;
            text += `<div class="mb-1"><strong>Time Limit:</strong> ${timeLimit.value} minutes</div>`;
            text += `<div class="mb-1"><strong>Judges:</strong> ${judgesPerTeam.options[judgesPerTeam.selectedIndex].text}</div>`;
        } else {
            text = 'Configure scoring to see preview';
        }

        preview.innerHTML = text;
    }

    scoringMethod.addEventListener('change', updateScoringPreview);
    maxScore.addEventListener('input', updateScoringPreview);
    timeLimit.addEventListener('input', updateScoringPreview);
    judgesPerTeam.addEventListener('change', updateScoringPreview);

    // Load rule templates
    window.loadTemplate = function(templateType) {
        const rulesField = document.getElementById('competition_rules');
        const criteriaField = document.getElementById('judging_criteria');

        const templates = {
            robotics: {
                rules: `1. TEAM COMPOSITION\n- Teams may consist of 2-4 participants\n- All team members must be registered students\n- One team coach must be present\n\n2. ROBOT SPECIFICATIONS\n- Maximum dimensions: 50cm x 50cm x 50cm\n- Weight limit: 5kg\n- Only approved programming platforms allowed\n\n3. COMPETITION FORMAT\n- Teams have 3 attempts to complete the challenge\n- Each attempt has a 15-minute time limit\n- Best score counts toward final ranking\n\n4. CONDUCT\n- Teams must demonstrate good sportsmanship\n- Assistance from coaches during competition is not allowed\n- Any form of cheating results in disqualification`,
                criteria: `Teams will be evaluated on:\n- Task completion (60%)\n- Innovation and creativity (20%)\n- Teamwork and presentation (20%)\n\nBonus points may be awarded for exceptional design or problem-solving approach.`
            },
            inventor: {
                rules: `1. INNOVATION CHALLENGE\n- Teams must present an original solution to a real-world problem\n- Solutions must incorporate technology or engineering principles\n- All work must be original to the team\n\n2. PRESENTATION FORMAT\n- 10-minute presentation followed by 5-minute Q&A\n- Teams must demonstrate their solution\n- Presentation materials are encouraged\n\n3. EVALUATION CRITERIA\n- Problem identification and research\n- Solution design and feasibility\n- Prototype or model demonstration\n- Communication and teamwork`,
                criteria: `Judging will consider:\n- Problem relevance and research (25%)\n- Solution creativity and innovation (30%)\n- Technical execution (25%)\n- Presentation quality (20%)`
            },
            pilot: {
                rules: `PILOT PROGRAMME 2025 RULES\n\n1. ELIGIBILITY\n- Open to Grade R-11 students\n- Teams of 2-4 participants\n- Multiple teams per school allowed\n\n2. CATEGORIES\n- 9 categories based on grade level and technology\n- Each category has specific requirements\n- See category-specific guidelines\n\n3. PHASES\n- Phase 1: School-based elimination\n- Phase 3: Provincial finals at Sci-Bono\n- No Phase 2 in pilot programme\n\n4. SPECIAL REQUIREMENTS\n- All participants need signed consent forms\n- School coordinator approval required\n- Follow health and safety protocols`,
                criteria: `Assessment based on:\n- Technical execution (40%)\n- Problem-solving approach (30%)\n- Innovation and creativity (20%)\n- Teamwork and communication (10%)`
            }
        };

        if (templates[templateType]) {
            rulesField.value = templates[templateType].rules;
            criteriaField.value = templates[templateType].criteria;
        }
    };

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!scoringMethod.value) {
            alert('Please select a scoring method.');
            return;
        }

        // Submit via AJAX
        const formData = new FormData(form);

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
    <?php if (!empty($wizard_data['step_5'])): ?>
    const savedData = <?= json_encode($wizard_data['step_5']) ?>;
    Object.keys(savedData).forEach(key => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = savedData[key];
            } else if (field.type === 'select-multiple') {
                Array.from(field.options).forEach(option => {
                    if (Array.isArray(savedData[key]) && savedData[key].includes(option.value)) {
                        option.selected = true;
                    }
                });
            } else {
                field.value = savedData[key];
            }
        }
    });
    updateScoringPreview();
    <?php endif; ?>

    // Initialize preview
    updateScoringPreview();
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>