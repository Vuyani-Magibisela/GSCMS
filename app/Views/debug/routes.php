<?php
$layout = 'app';
ob_start();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-route"></i> Route Debug Information</h1>
                    <p class="mb-0">Comprehensive analysis of the routing system</p>
                </div>
                <div class="card-body">
                    
                    <!-- Navigation Test Links -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3>Quick Navigation Tests</h3>
                            <div class="btn-group" role="group">
                                <?php foreach ($navigation_links as $name => $url): ?>
                                    <a href="<?= htmlspecialchars($url) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                        <?= htmlspecialchars($name) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <a href="/debug/test-navigation" class="btn btn-info btn-sm">
                                    <i class="fas fa-flask"></i> Run Navigation Tests
                                </a>
                                <a href="/debug/logs" class="btn btn-warning btn-sm">
                                    <i class="fas fa-file-alt"></i> View System Logs
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h3>System Information</h3>
                            <table class="table table-sm">
                                <?php foreach ($system_info as $key => $value): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?>:</strong></td>
                                        <td><code><?= htmlspecialchars($value) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h3>Authentication Status</h3>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Authenticated:</strong></td>
                                    <td>
                                        <span class="badge badge-<?= $auth_info['is_authenticated'] ? 'success' : 'danger' ?>">
                                            <?= $auth_info['is_authenticated'] ? 'Yes' : 'No' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>User ID:</strong></td>
                                    <td><?= htmlspecialchars($auth_info['user_id'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>User Role:</strong></td>
                                    <td><code><?= htmlspecialchars($auth_info['user_role']) ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Session ID:</strong></td>
                                    <td><code><?= htmlspecialchars($auth_info['session_id']) ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Session Status:</strong></td>
                                    <td>
                                        <?php
                                        $status_text = match($auth_info['session_status']) {
                                            PHP_SESSION_NONE => 'None',
                                            PHP_SESSION_ACTIVE => 'Active',
                                            PHP_SESSION_DISABLED => 'Disabled',
                                            default => 'Unknown'
                                        };
                                        ?>
                                        <span class="badge badge-<?= $auth_info['session_status'] === PHP_SESSION_ACTIVE ? 'success' : 'warning' ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($current_route): ?>
                    <!-- Current Route Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3>Current Route</h3>
                            <div class="card">
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Methods:</dt>
                                        <dd class="col-sm-9"><code><?= implode(' | ', $current_route['methods']) ?></code></dd>
                                        
                                        <dt class="col-sm-3">URI:</dt>
                                        <dd class="col-sm-9"><code><?= htmlspecialchars($current_route['uri']) ?></code></dd>
                                        
                                        <dt class="col-sm-3">Action:</dt>
                                        <dd class="col-sm-9">
                                            <code><?= is_string($current_route['action']) ? htmlspecialchars($current_route['action']) : 'Closure' ?></code>
                                        </dd>
                                        
                                        <dt class="col-sm-3">Name:</dt>
                                        <dd class="col-sm-9"><code><?= htmlspecialchars($current_route['name'] ?? 'unnamed') ?></code></dd>
                                        
                                        <dt class="col-sm-3">Middleware:</dt>
                                        <dd class="col-sm-9">
                                            <?php if (!empty($current_route['middleware'])): ?>
                                                <?php foreach ($current_route['middleware'] as $mw): ?>
                                                    <span class="badge badge-secondary"><?= htmlspecialchars($mw) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <em>None</em>
                                            <?php endif; ?>
                                        </dd>
                                        
                                        <dt class="col-sm-3">Parameters:</dt>
                                        <dd class="col-sm-9">
                                            <?php if (!empty($current_route['parameters'])): ?>
                                                <pre><?= htmlspecialchars(json_encode($current_route['parameters'], JSON_PRETTY_PRINT)) ?></pre>
                                            <?php else: ?>
                                                <em>None</em>
                                            <?php endif; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Middleware Information -->
                    <?php if (!empty($middleware_info)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3>Middleware Analysis</h3>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Middleware</th>
                                        <th>Class Exists</th>
                                        <th>File Path</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($middleware_info as $mw): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($mw['name']) ?></code></td>
                                            <td>
                                                <span class="badge badge-<?= $mw['class_exists'] ? 'success' : 'danger' ?>">
                                                    <?= $mw['class_exists'] ? 'Yes' : 'No' ?>
                                                </span>
                                                <?php if ($mw['class_exists']): ?>
                                                    <br><small><code><?= htmlspecialchars($mw['class_exists']) ?></code></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($mw['file_path']) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- All Routes -->
                    <div class="row">
                        <div class="col-12">
                            <h3>All Registered Routes (<?= $total_routes ?> total)</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm" id="routesTable">
                                    <thead>
                                        <tr>
                                            <th>Methods</th>
                                            <th>URI</th>
                                            <th>Action</th>
                                            <th>Name</th>
                                            <th>Middleware</th>
                                            <th>Controller Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($routes as $route): ?>
                                            <tr>
                                                <td><span class="badge badge-info"><?= htmlspecialchars($route['methods']) ?></span></td>
                                                <td><code><?= htmlspecialchars($route['uri']) ?></code></td>
                                                <td><small><?= htmlspecialchars($route['action']) ?></small></td>
                                                <td><?= htmlspecialchars($route['name']) ?></td>
                                                <td><small><?= htmlspecialchars($route['middleware']) ?></small></td>
                                                <td>
                                                    <span class="badge badge-<?= $route['controller_exists'] ? 'success' : 'danger' ?>">
                                                        <?= $route['controller_exists'] ? 'OK' : 'Missing' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add search functionality to routes table
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'form-control mb-3';
    searchInput.placeholder = 'Search routes...';
    
    const table = document.getElementById('routesTable');
    table.parentNode.insertBefore(searchInput, table);
    
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>