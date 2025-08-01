<?php 
$layout = 'layouts/public';
ob_start(); 
?>

<div class="error-page">
    <div class="error-content">
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Internal Server Error</h2>
        <p class="error-message">
            <?= htmlspecialchars($message ?? 'Something went wrong on our end. Please try again later.') ?>
        </p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i> Go Home
            </a>
            <button onclick="window.location.reload()" class="btn btn-secondary">
                <i class="fas fa-refresh"></i> Try Again
            </button>
        </div>
    </div>
</div>

<style>
.error-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
    padding: 40px 20px;
}

.error-content {
    max-width: 500px;
}

.error-code {
    font-size: 8rem;
    font-weight: bold;
    color: #dc3545;
    margin: 0;
    line-height: 1;
}

.error-title {
    font-size: 2rem;
    color: #495057;
    margin: 20px 0 15px 0;
}

.error-message {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 30px;
    line-height: 1.5;
}

.error-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}
</style>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>