<?php
$layout = 'app';
ob_start();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-flask"></i> Navigation Test Results</h1>
                    <p class="mb-0">Testing critical navigation links</p>
                </div>
                <div class="card-body">
                    
                    <!-- Authentication Status -->
                    <div class="alert alert-<?= $auth_info['is_authenticated'] ? 'success' : 'warning' ?>">
                        <strong>Authentication Status:</strong> 
                        <?= $auth_info['is_authenticated'] ? 'Authenticated' : 'Not Authenticated' ?>
                        <?php if ($auth_info['is_authenticated']): ?>
                            (Role: <?= htmlspecialchars($auth_info['user_role']) ?>)
                        <?php endif; ?>
                    </div>

                    <!-- Test Results -->
                    <div class="row">
                        <?php foreach ($test_results as $link => $result): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>
                                            <a href="<?= htmlspecialchars($link) ?>" target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars($link) ?>
                                            </a>
                                            <?php if ($result['status'] === 'Route found'): ?>
                                                <span class="badge badge-success float-right">Found</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger float-right">Issue</span>
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt class="col-sm-4">Status:</dt>
                                            <dd class="col-sm-8">
                                                <span class="badge badge-<?= strpos($result['status'], 'found') !== false ? 'success' : 'danger' ?>">
                                                    <?= htmlspecialchars($result['status']) ?>
                                                </span>
                                            </dd>
                                            
                                            <dt class="col-sm-4">Controller:</dt>
                                            <dd class="col-sm-8">
                                                <span class="badge badge-<?= $result['controller_exists'] ? 'success' : 'danger' ?>">
                                                    <?= $result['controller_exists'] ? 'Exists' : 'Missing' ?>
                                                </span>
                                            </dd>
                                        </dl>
                                        
                                        <?php if ($result['route']): ?>
                                            <h6>Route Details:</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>Methods:</strong></td>
                                                    <td><?= implode(' | ', $result['route']['methods']) ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>URI:</strong></td>
                                                    <td><code><?= htmlspecialchars($result['route']['uri']) ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Action:</strong></td>
                                                    <td><code><?= is_string($result['route']['action']) ? htmlspecialchars($result['route']['action']) : 'Closure' ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Name:</strong></td>
                                                    <td><?= htmlspecialchars($result['route']['name'] ?? 'unnamed') ?></td>
                                                </tr>
                                                <?php if (!empty($result['route']['middleware'])): ?>
                                                <tr>
                                                    <td><strong>Middleware:</strong></td>
                                                    <td>
                                                        <?php foreach ($result['route']['middleware'] as $mw): ?>
                                                            <span class="badge badge-secondary"><?= htmlspecialchars($mw) ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                        <?php endif; ?>
                                        
                                        <?php if (is_array($result['middleware_status']) && !empty($result['middleware_status'])): ?>
                                            <h6>Middleware Status:</h6>
                                            <table class="table table-sm">
                                                <?php foreach ($result['middleware_status'] as $mw): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($mw['name']) ?></td>
                                                        <td>
                                                            <span class="badge badge-<?= $mw['exists'] ? 'success' : 'danger' ?>">
                                                                <?= $mw['exists'] ? 'OK' : 'Missing' ?>
                                                            </span>
                                                        </td>
                                                        <td><small><?= htmlspecialchars($mw['class'] ?: 'N/A') ?></small></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="mt-4">
                        <a href="/debug/routes" class="btn btn-primary">
                            <i class="fas fa-route"></i> View All Routes
                        </a>
                        <a href="/debug/logs" class="btn btn-warning">
                            <i class="fas fa-file-alt"></i> System Logs
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-info">
                            <i class="fas fa-redo"></i> Refresh Tests
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