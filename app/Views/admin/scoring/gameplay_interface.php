<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="gameplay-scoring-container">
    <div class="scoring-header">
        <div class="header-info">
            <h2><?= htmlspecialchars($pageTitle ?? 'Gameplay Judging') ?></h2>
            <div class="team-info">
                <span class="team-badge"><?= htmlspecialchars($team['name'] ?? 'Team TBD') ?></span>
                <span class="category-badge"><?= htmlspecialchars($competition['category_name'] ?? 'Category TBD') ?></span>
            </div>
        </div>
        <div class="session-info">
            <div class="judge-name">Judge: <?= htmlspecialchars($judge['name'] ?? 'Judge TBD') ?></div>
            <div class="session-type">Mode: Gameplay Judging</div>
            <div class="max-runs">Max Runs: <?= $session['max_gameplay_runs'] ?? 3 ?></div>
        </div>
    </div>

    <!-- Dynamic Interface Container -->
    <div id="scoring-interface-container">
        <?= $scoringInterface['html'] ?? '<p>Loading gameplay scoring interface...</p>' ?>
    </div>

    <!-- Technical Notes Section -->
    <div class="technical-notes-section">
        <div class="technical-notes-card">
            <h4><i class="fas fa-clipboard-list"></i> Technical Observations & Notes</h4>
            <textarea id="technical-observations" class="form-control" rows="6"
                      placeholder="Record technical observations, robot performance notes, rule violations, exceptional achievements, etc.&#10;&#10;Examples:&#10;- Robot navigation was smooth and precise&#10;- Minor sensor calibration issue in run 2&#10;- Excellent programming logic for obstacle avoidance&#10;- Team showed good troubleshooting skills"></textarea>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="scoring-actions">
        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
        <button type="button" class="btn btn-info" id="preview-scores">
            <i class="fas fa-eye"></i> Preview Results
        </button>
        <button type="button" class="btn btn-warning" id="reset-all-runs">
            <i class="fas fa-redo"></i> Reset All Runs
        </button>
        <button type="button" class="btn btn-primary" id="submit-gameplay-scores">
            <i class="fas fa-check"></i> Submit Final Results
        </button>
    </div>
</div>

<!-- Results Preview Modal -->
<div class="modal fade" id="resultsPreviewModal" tabindex="-1" role="dialog" aria-labelledby="resultsPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultsPreviewLabel">Gameplay Results Summary</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="results-preview-content">
                    <!-- Results preview will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Review Results</button>
                <button type="button" class="btn btn-primary" id="confirm-submit-results">Confirm & Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetConfirmModal" tabindex="-1" role="dialog" aria-labelledby="resetConfirmLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetConfirmLabel">Confirm Reset All Runs</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to reset all runs?</strong></p>
                <p>This will permanently delete all timing data and mission completion records for this team.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-reset-all">Yes, Reset All Runs</button>
            </div>
        </div>
    </div>
</div>

<!-- Include interface-specific styles and scripts -->
<style>
<?= $scoringInterface['css'] ?? '' ?>

/* Additional gameplay-specific styles */
.gameplay-scoring-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.scoring-header {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.header-info h2 {
    margin: 0 0 10px 0;
    color: #2d3748;
}

.team-info {
    display: flex;
    gap: 10px;
}

.team-badge, .category-badge {
    background: #667eea;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.session-info {
    text-align: right;
    color: #718096;
}

.judge-name {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
}

.session-type {
    font-size: 0.9rem;
    color: #667eea;
    font-weight: 600;
    margin-bottom: 3px;
}

.max-runs {
    font-size: 0.8rem;
    color: #4a5568;
}

.technical-notes-section {
    margin-bottom: 25px;
}

.technical-notes-card {
    background: rgba(255,255,255,0.95);
    color: #2d3748;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.technical-notes-card h4 {
    color: #2d3748;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.technical-notes-card h4 i {
    color: #667eea;
    margin-right: 8px;
}

.technical-notes-card textarea {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    font-size: 0.95rem;
    line-height: 1.5;
    resize: vertical;
}

.technical-notes-card textarea:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.scoring-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding: 25px;
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.scoring-actions .btn {
    padding: 12px 25px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
    min-width: 140px;
}

.scoring-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

#scoring-interface-container {
    margin-bottom: 25px;
}

/* Modal styles for results preview */
.modal-content {
    border-radius: 12px;
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.results-preview-section {
    background: #f8faff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.results-preview-section h6 {
    color: #2d3748;
    margin-bottom: 10px;
    font-weight: 600;
}

.run-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.run-details-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.run-details-card.best-run {
    border-color: #667eea;
    background: #ebf4ff;
}

.run-details-card h6 {
    margin-bottom: 10px;
    color: #2d3748;
}

.run-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    font-size: 0.9rem;
}

.final-score-display {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    margin-top: 15px;
}

.final-score-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 10px 0;
}

.alert-info {
    background: #ebf8ff;
    border: 1px solid #90cdf4;
    color: #2c5aa0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.alert-warning {
    background: #fef5e7;
    border: 1px solid #f6ad55;
    color: #744210;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .scoring-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .scoring-actions {
        flex-direction: column;
        align-items: center;
    }

    .scoring-actions .btn {
        width: 100%;
        max-width: 300px;
    }

    .run-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
<?= $scoringInterface['javascript'] ?? '' ?>

// Additional gameplay judging functionality
$(document).ready(function() {

    // Initialize scoring interface
    if (typeof window.gameplayScoring !== 'undefined') {
        console.log('Gameplay scoring interface initialized');
    } else {
        console.error('Gameplay scoring interface failed to initialize');
    }

    // Preview results functionality
    $('#preview-scores').click(function() {
        if (typeof window.gameplayScoring !== 'undefined') {
            const resultsData = window.gameplayScoring.getScores();
            displayResultsPreview(resultsData);
            $('#resultsPreviewModal').modal('show');
        } else {
            alert('Scoring interface not available. Please refresh the page.');
        }
    });

    // Reset all runs functionality
    $('#reset-all-runs').click(function() {
        $('#resetConfirmModal').modal('show');
    });

    $('#confirm-reset-all').click(function() {
        if (typeof window.gameplayScoring !== 'undefined') {
            // Reset all runs
            for (let i = 1; i <= 3; i++) {
                window.gameplayScoring.currentRun = i;
                window.gameplayScoring.resetRun();
            }
            window.gameplayScoring.currentRun = 1;
            window.gameplayScoring.updateCurrentRun();

            // Clear technical notes
            $('#technical-observations').val('');

            alert('All runs have been reset successfully.');
        }
        $('#resetConfirmModal').modal('hide');
    });

    // Submit results functionality
    $('#submit-gameplay-scores, #confirm-submit-results').click(function() {
        if (typeof window.gameplayScoring !== 'undefined') {
            submitGameplayResults();
        } else {
            alert('Scoring interface not available. Please refresh the page.');
        }
    });

    function displayResultsPreview(resultsData) {
        let previewHtml = '';

        // Check if any runs are completed
        const completedRuns = Object.values(resultsData.runs_data).filter(run => run.status === 'completed').length;

        if (completedRuns === 0) {
            previewHtml += '<div class="alert alert-warning">';
            previewHtml += '<strong>No Completed Runs:</strong> ';
            previewHtml += 'No runs have been completed yet. Please complete at least one run before submitting.';
            previewHtml += '</div>';
        } else if (completedRuns < 3) {
            previewHtml += '<div class="alert alert-info">';
            previewHtml += '<strong>Partial Results:</strong> ';
            previewHtml += `${completedRuns} of 3 runs completed. You can submit now or continue with remaining runs.`;
            previewHtml += '</div>';
        }

        // Run details
        previewHtml += '<div class="results-preview-section">';
        previewHtml += '<h6>Run Details</h6>';
        previewHtml += '<div class="run-details-grid">';

        for (let i = 1; i <= 3; i++) {
            const run = resultsData.runs_data[i];
            const isBestRun = resultsData.best_run_number === i;

            previewHtml += `<div class="run-details-card ${isBestRun ? 'best-run' : ''}">`;
            previewHtml += `<h6>Run ${i} ${isBestRun ? '👑 (Best)' : ''}</h6>`;
            previewHtml += '<div class="run-detail-item">';
            previewHtml += '<span>Status:</span>';
            previewHtml += `<span>${run.status.replace('_', ' ')}</span>`;
            previewHtml += '</div>';

            if (run.status === 'completed') {
                const timeDisplay = window.gameplayScoring ? window.gameplayScoring.formatTime(run.time) : '--:--';
                previewHtml += '<div class="run-detail-item">';
                previewHtml += '<span>Time:</span>';
                previewHtml += `<span>${timeDisplay}</span>`;
                previewHtml += '</div>';
                previewHtml += '<div class="run-detail-item">';
                previewHtml += '<span>Mission Score:</span>';
                previewHtml += `<span>${run.score} pts</span>`;
                previewHtml += '</div>';
            } else {
                previewHtml += '<div class="run-detail-item">';
                previewHtml += '<span>Time:</span>';
                previewHtml += '<span>--:--</span>';
                previewHtml += '</div>';
                previewHtml += '<div class="run-detail-item">';
                previewHtml += '<span>Mission Score:</span>';
                previewHtml += '<span>0 pts</span>';
                previewHtml += '</div>';
            }
            previewHtml += '</div>';
        }
        previewHtml += '</div>';
        previewHtml += '</div>';

        // Best run summary
        if (resultsData.best_run_number) {
            previewHtml += '<div class="results-preview-section">';
            previewHtml += '<h6>Final Results</h6>';
            previewHtml += '<div class="final-score-display">';
            previewHtml += `<div>Best Run: Run ${resultsData.best_run_number}</div>`;
            previewHtml += `<div>Fastest Time: ${window.gameplayScoring ? window.gameplayScoring.formatTime(resultsData.runs_data[resultsData.best_run_number].time) : '--:--'}</div>`;
            previewHtml += '<div class="final-score-value">';
            previewHtml += `${resultsData.final_score} points`;
            previewHtml += '</div>';
            previewHtml += '</div>';
            previewHtml += '</div>';
        }

        // Technical notes
        const technicalNotes = $('#technical-observations').val().trim();
        if (technicalNotes) {
            previewHtml += '<div class="results-preview-section">';
            previewHtml += '<h6>Technical Observations</h6>';
            previewHtml += `<div style="white-space: pre-wrap; background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">${technicalNotes}</div>`;
            previewHtml += '</div>';
        }

        $('#results-preview-content').html(previewHtml);

        // Enable/disable submit button based on completion
        if (completedRuns === 0) {
            $('#confirm-submit-results').prop('disabled', true).text('Complete At Least One Run');
        } else {
            $('#confirm-submit-results').prop('disabled', false).text('Confirm & Submit');
        }
    }

    function submitGameplayResults() {
        const resultsData = window.gameplayScoring.getScores();

        // Validate at least one run is completed
        const completedRuns = Object.values(resultsData.runs_data).filter(run => run.status === 'completed').length;

        if (completedRuns === 0) {
            alert('Please complete at least one run before submitting results.');
            return;
        }

        // Prepare submission data
        const submissionData = {
            team_id: <?= $team['id'] ?? 0 ?>,
            competition_id: <?= $competition['id'] ?? 0 ?>,
            session_id: <?= $session['id'] ?? 0 ?>,
            judging_mode: 'gameplay',
            scoring_data: resultsData,
            technical_notes: $('#technical-observations').val().trim(),
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        };

        // Show loading state
        $('#confirm-submit-results').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        // Submit results via AJAX
        $.ajax({
            url: '/admin/scoring/submit',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(submissionData),
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert('Gameplay results submitted successfully!');

                    // Redirect to dashboard or next team
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        window.location.href = '/admin/scoring';
                    }
                } else {
                    alert('Error submitting results: ' + (response.message || 'Unknown error'));
                    $('#confirm-submit-results').prop('disabled', false).text('Confirm & Submit');
                }
            },
            error: function(xhr, status, error) {
                console.error('Submission error:', error);
                alert('Failed to submit results. Please check your connection and try again.');
                $('#confirm-submit-results').prop('disabled', false).text('Confirm & Submit');
            }
        });

        // Close modal
        $('#resultsPreviewModal').modal('hide');
    }

    // Auto-save functionality (optional)
    setInterval(function() {
        if (typeof window.gameplayScoring !== 'undefined') {
            const resultsData = window.gameplayScoring.getScores();
            const technicalNotes = $('#technical-observations').val();
            const backupData = {
                ...resultsData,
                technical_notes: technicalNotes
            };
            localStorage.setItem('gameplay_results_backup', JSON.stringify(backupData));
        }
    }, 30000); // Auto-save every 30 seconds

    // Restore from backup if available
    const backup = localStorage.getItem('gameplay_results_backup');
    if (backup) {
        try {
            const backupData = JSON.parse(backup);
            console.log('Results backup available:', backupData);
            if (backupData.technical_notes) {
                $('#technical-observations').val(backupData.technical_notes);
            }
            // Could implement restore functionality here for runs data
        } catch (e) {
            console.warn('Invalid backup data found');
        }
    }

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+S or Cmd+S to auto-save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            // Trigger auto-save manually
            if (typeof window.gameplayScoring !== 'undefined') {
                const resultsData = window.gameplayScoring.getScores();
                const technicalNotes = $('#technical-observations').val();
                const backupData = { ...resultsData, technical_notes: technicalNotes };
                localStorage.setItem('gameplay_results_backup', JSON.stringify(backupData));

                // Show brief confirmation
                const originalText = 'Auto-saved locally';
                const $indicator = $('<div class="auto-save-indicator">').text(originalText)
                    .css({
                        position: 'fixed',
                        top: '20px',
                        right: '20px',
                        background: '#48bb78',
                        color: 'white',
                        padding: '10px 15px',
                        borderRadius: '6px',
                        zIndex: 9999,
                        fontSize: '14px'
                    });

                $('body').append($indicator);
                setTimeout(() => $indicator.fadeOut(() => $indicator.remove()), 2000);
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>