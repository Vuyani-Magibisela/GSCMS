<?php
$layout = 'app';
ob_start();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-file-alt"></i> System Logs</h1>
                    <p class="mb-0">Recent errors and system messages</p>
                </div>
                <div class="card-body">
                    
                    <!-- Log Directory Info -->
                    <div class="alert alert-info">
                        <strong>Log Directory:</strong> <code><?= htmlspecialchars($log_directory) ?></code>
                        <br>
                        <strong>Directory Exists:</strong> 
                        <span class="badge badge-<?= is_dir($log_directory) ? 'success' : 'danger' ?>">
                            <?= is_dir($log_directory) ? 'Yes' : 'No' ?>
                        </span>
                    </div>

                    <!-- Log Files -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3>Log Files</h3>
                            <?php if (!empty($log_files)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Size</th>
                                                <th>Last Modified</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($log_files as $file): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($file['name']) ?></code></td>
                                                    <td><?= number_format($file['size']) ?> bytes</td>
                                                    <td><?= date('Y-m-d H:i:s', $file['modified']) ?></td>
                                                    <td>
                                                        <a href="<?= htmlspecialchars($file['path']) ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           target="_blank">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    No log files found in the log directory.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Errors -->
                    <div class="row">
                        <div class="col-12">
                            <h3>Recent Log Entries (Latest 50 lines)</h3>
                            <?php if (!empty($recent_errors)): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="mb-0" style="max-height: 500px; overflow-y: auto; font-size: 0.9em;"><?php
                                            foreach ($recent_errors as $line) {
                                                if (trim($line)) {
                                                    // Color code different log levels
                                                    $class = '';
                                                    if (strpos($line, 'ERROR') !== false) {
                                                        $class = 'text-danger';
                                                    } elseif (strpos($line, 'WARNING') !== false) {
                                                        $class = 'text-warning';
                                                    } elseif (strpos($line, 'INFO') !== false) {
                                                        $class = 'text-info';
                                                    }
                                                    
                                                    echo '<div class="' . $class . '">' . htmlspecialchars($line) . '</div>';
                                                }
                                            }
                                        ?></pre>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No recent log entries found.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Error Pattern Analysis -->
                    <?php if (!empty($recent_errors)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h3>Error Pattern Analysis</h3>
                            <div class="card">
                                <div class="card-body">
                                    <?php
                                    $patterns = [
                                        'Route not found' => 0,
                                        'Controller' => 0,
                                        'Middleware' => 0,
                                        'Authentication' => 0,
                                        'Database' => 0,
                                        'Session' => 0
                                    ];
                                    
                                    foreach ($recent_errors as $line) {
                                        foreach ($patterns as $pattern => $count) {
                                            if (stripos($line, $pattern) !== false) {
                                                $patterns[$pattern]++;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <div class="row">
                                        <?php foreach ($patterns as $pattern => $count): ?>
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body text-center">
                                                        <h5><?= $count ?></h5>
                                                        <p class="mb-0"><?= htmlspecialchars($pattern) ?> Issues</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Navigation -->
                    <div class="mt-4">
                        <a href="/debug/routes" class="btn btn-primary">
                            <i class="fas fa-route"></i> View Routes
                        </a>
                        <a href="/debug/test-navigation" class="btn btn-info">
                            <i class="fas fa-flask"></i> Test Navigation
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-success">
                            <i class="fas fa-sync"></i> Refresh Logs
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>