<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Dashboard - GSCMS') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'GDE SciBOTICS Competition Management Dashboard') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>/images/favicon.ico">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Main CSS Framework -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css?v=<?= file_exists(APP_ROOT . '/public/css/style.css') ? filemtime(APP_ROOT . '/public/css/style.css') : time() ?>">
    
    <!-- App-specific CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/app.css?v=<?= file_exists(APP_ROOT . '/public/css/app.css') ? filemtime(APP_ROOT . '/public/css/app.css') : time() ?>">
    
    <!-- Enhanced Forms CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/enhanced-forms.css?v=<?= file_exists(APP_ROOT . '/public/css/enhanced-forms.css') ? filemtime(APP_ROOT . '/public/css/enhanced-forms.css') : time() ?>">
    
    <!-- Page-specific CSS -->
    <?php if (isset($pageCSS)): ?>
        <?php foreach ($pageCSS as $css): ?>
            <link rel="stylesheet" href="<?= $baseUrl ?><?= htmlspecialchars($css) ?>?v=<?= file_exists(APP_ROOT . '/public' . $css) ? filemtime(APP_ROOT . '/public' . $css) : time() ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Custom inline styles -->
    <?php if (isset($customStyles)): ?>
        <style><?= $customStyles ?></style>
    <?php endif; ?>
</head>
<?php 
use App\Core\Auth;
$auth = Auth::getInstance();
$isAuthenticated = $auth->check();
$layoutClass = $isAuthenticated ? 'app-layout' : 'app-layout public-layout';
?>
<body class="<?= $layoutClass ?> <?= htmlspecialchars($pageClass ?? '') ?>">
    
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- App Header -->
    <?php if ($auth->check()): ?>
    <header class="app-header">
        <div class="app-header-content">
            <!-- Mobile Menu Toggle -->
            <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle navigation menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- App Logo/Title -->
            <div class="app-brand">
                <a href="/dashboard" class="brand-link">
                    <i class="fas fa-robot brand-icon"></i>
                    <span class="brand-text">GSCMS</span>
                </a>
            </div>
            
            <!-- Header Actions -->
            <div class="app-header-actions">
                <!-- User Menu -->
                <?php include VIEW_PATH . '/partials/_user_menu.php'; ?>
            </div>
        </div>
    </header>
    <?php else: ?>
    <!-- Public Navigation for unauthenticated users -->
    <?php include VIEW_PATH . '/partials/_public_navigation.php'; ?>
    <?php endif; ?>
    
    <!-- App Sidebar (only for authenticated users) -->
    <?php if ($auth->check()): ?>
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-content">
            <!-- Navigation Menu -->
            <nav class="sidebar-nav" role="navigation" aria-label="Main navigation">
                <?php include VIEW_PATH . '/partials/_app_navigation.php'; ?>
            </nav>
        </div>
    </aside>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main class="app-main" id="main-content">
        <!-- Breadcrumbs -->
        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
            <div class="breadcrumb-container">
                <?php include VIEW_PATH . '/partials/_breadcrumbs.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_messages'])): ?>
            <div class="flash-messages">
                <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= htmlspecialchars($type) ?>" role="alert">
                            <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php unset($_SESSION['flash_messages']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <div class="app-content">
            <?php if (isset($is_auth_page) && $is_auth_page): ?>
                <!-- Auth Page Layout -->
                <div class="auth-container">
                    <div class="auth-wrapper">
                        <div class="auth-card">
                            <?= $content ?? '' ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Dashboard Content -->
                <?= $content ?? '' ?>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading...</span>
        </div>
    </div>
    
    <!-- Core JavaScript -->
    <script src="<?= $baseUrl ?>/js/main.js?v=<?= file_exists(APP_ROOT . '/public/js/main.js') ? filemtime(APP_ROOT . '/public/js/main.js') : time() ?>"></script>
    
    <!-- App-specific JavaScript -->
    <script src="<?= $baseUrl ?>/js/app.js?v=<?= file_exists(APP_ROOT . '/public/js/app.js') ? filemtime(APP_ROOT . '/public/js/app.js') : time() ?>"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($pageJS)): ?>
        <?php foreach ($pageJS as $js): ?>
            <script src="<?= $baseUrl ?><?= htmlspecialchars($js) ?>?v=<?= file_exists(APP_ROOT . '/public' . $js) ? filemtime(APP_ROOT . '/public' . $js) : time() ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Custom inline scripts -->
    <?php if (isset($customScripts)): ?>
        <script><?= $customScripts ?></script>
    <?php endif; ?>
    
    <!-- App Layout Scripts -->
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileSidebarToggle');
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (mobileToggle && sidebar && overlay) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                    document.body.classList.toggle('sidebar-open');
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                });
            }
            
            // Loading overlay functions
            window.showLoading = function() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            };
            
            window.hideLoading = function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            };
            
            // Auto-hide flash messages
            setTimeout(() => {
                const flashMessages = document.querySelectorAll('.flash-messages .alert');
                flashMessages.forEach(message => {
                    message.style.animation = 'slideOutUp 0.5s ease forwards';
                    setTimeout(() => message.remove(), 500);
                });
            }, 5000);
        });
        
        // Keyboard navigation support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
            // Escape key closes mobile sidebar
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('appSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    </script>
</body>
</html>