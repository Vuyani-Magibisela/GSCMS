<?php
// Debug script to test judge assignment modal without authentication

// Simple mock data to test the modal
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

// Include the admin scoring view with mock data
$layout = 'layouts/admin';
ob_start();

// Set required variables
$title = 'Debug - Judge Assignment';
$pageTitle = 'Debug Judge Assignment Modal';
$pageSubtitle = 'Testing modal functionality';
$baseUrl = '';
$teams = [];
$active_sessions = [];
$pending_scores = [];
$recent_activity = [];
$scoring_stats = ['completed_scores' => 0];
$total_teams = 0;

?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Judge Assignment Modal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><?= $pageTitle ?></h1>
        <p><?= $pageSubtitle ?></p>

        <!-- Judge Assignment Section (Just the button and modal part) -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie text-primary"></i>
                    Judge Assignments (Debug)
                </h5>
                <div class="header-actions">
                    <button class="btn btn-primary btn-sm" id="assign-judge-btn">
                        <i class="fas fa-plus"></i> Assign Judge
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p>Debug mode: Testing modal functionality only</p>
                <div class="alert alert-info">
                    Available judges: <?= count($available_judges) ?><br>
                    Competitions: <?= count($competitions) ?><br>
                    Click the "Assign Judge" button above to test the modal.
                </div>
            </div>
        </div>
    </div>

    <?php
    // Include just the modal HTML from the scoring view
    include 'app/Views/admin/scoring/index.php';
    ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
</html>