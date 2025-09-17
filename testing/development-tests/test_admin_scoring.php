<?php
// Simple test page that mimics the admin scoring view structure
$available_judges = [
    ['id' => 1, 'first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john@test.com', 'judge_code' => 'J001', 'current_assignments' => 0],
    ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@test.com', 'judge_code' => 'J002', 'current_assignments' => 2],
];
$competitions = [
    ['id' => 1, 'name' => 'Test Competition 1'],
    ['id' => 2, 'name' => 'Test Competition 2']
];
$judge_assignments = [];
$unassigned_sessions = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Admin Scoring - Judge Assignment</title>
    <!-- Bootstrap CSS (required for modals and components) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Admin Scoring - Judge Assignment Debug</h1>
        <div class="alert alert-warning">
            <strong>Debug Mode:</strong> This page tests the judge assignment modal functionality.
            <br>Open browser dev tools (F12) and check the Console tab for debug messages.
        </div>

        <!-- Judge Assignment Section (extracted from admin scoring view) -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie text-primary"></i>
                    Judge Assignments
                </h5>
                <div class="header-actions">
                    <button class="btn btn-primary btn-sm" id="assign-judge-btn">
                        <i class="fas fa-plus"></i> Assign Judge
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Available Judges -->
                    <div class="col-md-4">
                        <h6 class="mb-3">Available Judges (<?= count($available_judges) ?>)</h6>
                        <div class="judges-list">
                            <?php if (empty($available_judges)): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-user-slash mb-2"></i>
                                    <p class="mb-0">No judges available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($available_judges as $judge): ?>
                                <div class="judge-item border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($judge['email']) ?></small>
                                            <br><small class="text-info">Code: <?= htmlspecialchars($judge['judge_code']) ?></small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-success"><?= $judge['current_assignments'] ?> assignments</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <h6>Debug Information</h6>
                        <ul>
                            <li>Available judges: <?= count($available_judges) ?></li>
                            <li>Competitions: <?= count($competitions) ?></li>
                            <li>Judge assignments: <?= count($judge_assignments) ?></li>
                            <li>Unassigned sessions: <?= count($unassigned_sessions) ?></li>
                        </ul>
                        <p>Click the "Assign Judge" button above to test the modal functionality.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Judge Assignment Modal -->
    <div class="modal fade" id="judgeAssignmentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Judge to Session</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <strong>Success!</strong> The modal opened correctly. The judge assignment functionality is working.
                    </div>
                    <form id="judgeAssignmentForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assignJudgeSelect">Select Judge</label>
                                    <select class="form-control" id="assignJudgeSelect" name="judge_id" required>
                                        <option value="">Choose a judge...</option>
                                        <?php foreach ($available_judges as $judge): ?>
                                        <option value="<?= $judge['id'] ?>">
                                            <?= htmlspecialchars($judge['first_name'] . ' ' . $judge['last_name']) ?>
                                            (<?= $judge['current_assignments'] ?> assignments)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="assignCompetitionSelect">Competition</label>
                                    <select class="form-control" id="assignCompetitionSelect" name="competition_id" required>
                                        <option value="">Select competition...</option>
                                        <?php foreach ($competitions as $competition): ?>
                                        <option value="<?= $competition['id'] ?>">
                                            <?= htmlspecialchars($competition['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assignJudgeType">Judge Type</label>
                                    <select class="form-control" id="assignJudgeType" name="judge_type">
                                        <option value="primary">Primary Judge</option>
                                        <option value="secondary">Secondary Judge</option>
                                        <option value="backup">Backup Judge</option>
                                        <option value="head">Head Judge</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmAssignment">
                        <i class="fas fa-user-plus"></i> Test Assign
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for Bootstrap and custom functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!-- Bootstrap JavaScript (required for modals and components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>

    <script>
    console.log('SCRIPT TAG STARTED - Basic test');

    $(document).ready(function() {
        console.log('DOCUMENT READY FIRED - jQuery is working');
        // Debug information
        console.log('Judge Assignment System - Admin Scoring Dashboard');
        console.log('JavaScript is loading and executing');
        console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'jQuery not loaded');
        console.log('Bootstrap modal available:', typeof $.fn !== 'undefined' && typeof $.fn.modal !== 'undefined' ? 'Yes' : 'No');

        // Check if button exists
        console.log('Assign judge button found:', $('#assign-judge-btn').length > 0 ? 'Yes' : 'No');
        console.log('Judge assignment modal found:', $('#judgeAssignmentModal').length > 0 ? 'Yes' : 'No');

        // Judge Assignment functionality
        $('#assign-judge-btn').click(function() {
            console.log('🎯 Assign Judge button clicked');

            // Check if jQuery and Bootstrap are loaded
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded');
                alert('Error: jQuery is required but not loaded. Please refresh the page.');
                return;
            }

            if (typeof $.fn.modal === 'undefined') {
                console.error('Bootstrap modal is not loaded');
                alert('Error: Bootstrap is required but not loaded. Please refresh the page.');
                return;
            }

            // Check if modal exists
            if ($('#judgeAssignmentModal').length === 0) {
                console.error('Judge assignment modal element not found');
                alert('Error: Modal element not found. Please refresh the page.');
                return;
            }

            console.log('Opening judge assignment modal');
            $('#judgeAssignmentModal').modal('show');
        });

        // Test confirm button
        $('#confirmAssignment').click(function() {
            console.log('Test assign button clicked');
            alert('Test: Judge assignment would be processed here');
            $('#judgeAssignmentModal').modal('hide');
        });

        console.log('All event handlers attached successfully');
    });

    // Fallback event handler in case document ready doesn't work
    window.addEventListener('load', function() {
        console.log('WINDOW LOAD EVENT FIRED');

        const assignBtn = document.getElementById('assign-judge-btn');
        if (assignBtn) {
            console.log('Found assign-judge-btn element, adding fallback click listener');
            assignBtn.addEventListener('click', function() {
                console.log('🎯 FALLBACK: Assign Judge button clicked!');
                alert('Assign Judge button was clicked! (Fallback handler)');
            });
        } else {
            console.log('assign-judge-btn element not found');
        }
    });
    </script>
</body>
</html>