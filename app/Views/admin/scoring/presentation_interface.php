<?php
$layout = 'layouts/admin';
ob_start();
?>

<div class="presentation-scoring-container">
    <div class="scoring-header">
        <div class="header-info">
            <h2><?= htmlspecialchars($pageTitle ?? 'Presentation Judging') ?></h2>
            <div class="team-info">
                <span class="team-badge"><?= htmlspecialchars($team['name'] ?? 'Team TBD') ?></span>
                <span class="category-badge"><?= htmlspecialchars($competition['category_name'] ?? 'Category TBD') ?></span>
            </div>
        </div>
        <div class="session-info">
            <div class="judge-name">Judge: <?= htmlspecialchars($judge['name'] ?? 'Judge TBD') ?></div>
            <div class="session-type">Mode: Presentation Judging</div>
        </div>
    </div>

    <!-- Dynamic Interface Container -->
    <div id="scoring-interface-container">
        <?= $scoringInterface['html'] ?? '<p>Loading presentation scoring interface...</p>' ?>
    </div>

    <!-- Action Buttons -->
    <div class="scoring-actions">
        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
        <button type="button" class="btn btn-info" id="preview-scores">
            <i class="fas fa-eye"></i> Preview Scores
        </button>
        <button type="button" class="btn btn-primary" id="submit-presentation-scores">
            <i class="fas fa-check"></i> Submit Final Scores
        </button>
    </div>
</div>

<!-- Score Preview Modal -->
<div class="modal fade" id="scorePreviewModal" tabindex="-1" role="dialog" aria-labelledby="scorePreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scorePreviewLabel">Presentation Score Summary</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="score-preview-content">
                    <!-- Score preview will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Review Scores</button>
                <button type="button" class="btn btn-primary" id="confirm-submit-scores">Confirm & Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Include interface-specific styles and scripts -->
<style>
<?= $scoringInterface['css'] ?? '' ?>

/* Additional presentation-specific styles */
.presentation-scoring-container {
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
    background: #4facfe;
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
    color: #4facfe;
    font-weight: 600;
}

.scoring-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding: 25px;
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    margin-top: 25px;
}

.scoring-actions .btn {
    padding: 12px 25px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.scoring-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

#scoring-interface-container {
    margin-bottom: 25px;
}

/* Modal styles for score preview */
.modal-content {
    border-radius: 12px;
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.score-preview-section {
    background: #f8faff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.score-preview-section h6 {
    color: #2d3748;
    margin-bottom: 10px;
    font-weight: 600;
}

.score-preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
}

.score-preview-item:last-child {
    border-bottom: none;
    font-weight: 600;
    background: #ebf8ff;
    padding: 12px;
    border-radius: 6px;
    margin-top: 10px;
}

.alert-warning {
    background: #fef5e7;
    border: 1px solid #f6ad55;
    color: #744210;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}
</style>

<script>
<?= $scoringInterface['javascript'] ?? '' ?>

// Additional presentation judging functionality
$(document).ready(function() {

    // Initialize scoring interface
    if (typeof window.presentationScoring !== 'undefined') {
        console.log('Presentation scoring interface initialized');
    } else {
        console.error('Presentation scoring interface failed to initialize');
    }

    // Preview scores functionality
    $('#preview-scores').click(function() {
        if (typeof window.presentationScoring !== 'undefined') {
            const scoreData = window.presentationScoring.getScores();
            displayScorePreview(scoreData);
            $('#scorePreviewModal').modal('show');
        } else {
            alert('Scoring interface not available. Please refresh the page.');
        }
    });

    // Submit scores functionality
    $('#submit-presentation-scores, #confirm-submit-scores').click(function() {
        if (typeof window.presentationScoring !== 'undefined') {
            submitPresentationScores();
        } else {
            alert('Scoring interface not available. Please refresh the page.');
        }
    });

    function displayScorePreview(scoreData) {
        let previewHtml = '';

        // Check if all sections are scored
        const sectionsScored = Object.keys(scoreData.presentation_scores).length;
        const totalSections = <?= count($scoringInterface['rubric']['sections'] ?? []) ?>;

        if (sectionsScored < totalSections) {
            previewHtml += '<div class="alert alert-warning">';
            previewHtml += '<strong>Incomplete Scoring:</strong> ';
            previewHtml += `${sectionsScored} of ${totalSections} sections have been scored. Please complete all sections before submitting.`;
            previewHtml += '</div>';
        }

        // Presentation timing info
        previewHtml += '<div class="score-preview-section">';
        previewHtml += '<h6>Presentation Information</h6>';
        previewHtml += '<div class="score-preview-item">';
        previewHtml += '<span>Presentation Duration:</span>';
        previewHtml += `<span>${scoreData.presentation_duration_minutes} minutes</span>`;
        previewHtml += '</div>';
        previewHtml += '</div>';

        // Individual section scores
        previewHtml += '<div class="score-preview-section">';
        previewHtml += '<h6>Section Scores</h6>';

        <?php if (isset($scoringInterface['rubric']['sections'])): ?>
        <?php foreach ($scoringInterface['rubric']['sections'] as $sectionKey => $section): ?>
        if (scoreData.presentation_scores['<?= $sectionKey ?>']) {
            previewHtml += '<div class="score-preview-item">';
            previewHtml += '<span><?= htmlspecialchars($section['name']) ?>:</span>';
            previewHtml += `<span>${scoreData.presentation_scores['<?= $sectionKey ?>']} / <?= $section['max_points'] ?> points</span>`;
            previewHtml += '</div>';
        }
        <?php endforeach; ?>
        <?php endif; ?>

        // Total score
        previewHtml += '<div class="score-preview-item">';
        previewHtml += '<span><strong>Total Score:</strong></span>';
        previewHtml += `<span><strong>${scoreData.total_score} / <?= $scoringInterface['rubric']['max_score'] ?? 75 ?> points</strong></span>`;
        previewHtml += '</div>';
        previewHtml += '</div>';

        // Notes sections
        if (scoreData.section_notes && Object.keys(scoreData.section_notes).length > 0) {
            previewHtml += '<div class="score-preview-section">';
            previewHtml += '<h6>Judge Notes</h6>';
            Object.keys(scoreData.section_notes).forEach(section => {
                if (scoreData.section_notes[section].trim()) {
                    previewHtml += `<div class="mb-2"><strong>${section}:</strong> ${scoreData.section_notes[section]}</div>`;
                }
            });
            previewHtml += '</div>';
        }

        $('#score-preview-content').html(previewHtml);

        // Enable/disable submit button based on completion
        if (sectionsScored < totalSections) {
            $('#confirm-submit-scores').prop('disabled', true).text('Complete All Sections First');
        } else {
            $('#confirm-submit-scores').prop('disabled', false).text('Confirm & Submit');
        }
    }

    function submitPresentationScores() {
        const scoreData = window.presentationScoring.getScores();

        // Validate scores are complete
        const sectionsScored = Object.keys(scoreData.presentation_scores).length;
        const totalSections = <?= count($scoringInterface['rubric']['sections'] ?? []) ?>;

        if (sectionsScored < totalSections) {
            alert('Please complete scoring for all sections before submitting.');
            return;
        }

        // Prepare submission data
        const submissionData = {
            team_id: <?= $team['id'] ?? 0 ?>,
            competition_id: <?= $competition['id'] ?? 0 ?>,
            session_id: <?= $session['id'] ?? 0 ?>,
            judging_mode: 'presentation',
            scoring_data: scoreData,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        };

        // Show loading state
        $('#confirm-submit-scores').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        // Submit scores via AJAX
        $.ajax({
            url: '/admin/scoring/submit',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(submissionData),
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert('Presentation scores submitted successfully!');

                    // Redirect to dashboard or next team
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        window.location.href = '/admin/scoring';
                    }
                } else {
                    alert('Error submitting scores: ' + (response.message || 'Unknown error'));
                    $('#confirm-submit-scores').prop('disabled', false).text('Confirm & Submit');
                }
            },
            error: function(xhr, status, error) {
                console.error('Submission error:', error);
                alert('Failed to submit scores. Please check your connection and try again.');
                $('#confirm-submit-scores').prop('disabled', false).text('Confirm & Submit');
            }
        });

        // Close modal
        $('#scorePreviewModal').modal('hide');
    }

    // Auto-save functionality (optional)
    setInterval(function() {
        if (typeof window.presentationScoring !== 'undefined') {
            const scoreData = window.presentationScoring.getScores();
            localStorage.setItem('presentation_scores_backup', JSON.stringify(scoreData));
        }
    }, 30000); // Auto-save every 30 seconds

    // Restore from backup if available
    const backup = localStorage.getItem('presentation_scores_backup');
    if (backup) {
        try {
            const backupData = JSON.parse(backup);
            console.log('Score backup available:', backupData);
            // Could implement restore functionality here
        } catch (e) {
            console.warn('Invalid backup data found');
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>