<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin Dashboard - GSCMS') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Administrative Dashboard for GDE SciBOTICS Competition Management') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>/images/favicon.ico">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Main CSS Framework -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css?v=<?= file_exists(APP_ROOT . '/public/css/style.css') ? filemtime(APP_ROOT . '/public/css/style.css') : time() ?>">
    
    <!-- Admin-specific CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/admin.css?v=<?= file_exists(APP_ROOT . '/public/css/admin.css') ? filemtime(APP_ROOT . '/public/css/admin.css') : time() ?>">
    
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
<body class="admin-layout <?= htmlspecialchars($pageClass ?? '') ?>">
    
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <!-- Mobile Menu Toggle -->
            <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle admin navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Admin Brand -->
            <div class="admin-brand">
                <a href="<?= url('/admin/dashboard') ?>" class="brand-link">
                    <i class="fas fa-shield-alt brand-icon"></i>
                    <span class="brand-text">Admin Panel</span>
                </a>
            </div>
            
            <!-- Header Actions -->
            <div class="admin-header-actions">
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <button class="quick-action-btn" title="Notifications" data-count="3">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="quick-action-btn" title="Messages">
                        <i class="fas fa-envelope"></i>
                    </button>
                </div>
                
                <!-- User Menu -->
                <?php include VIEW_PATH . '/partials/_admin_user_menu.php'; ?>
            </div>
        </div>
    </header>
    
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-content">
            <!-- Admin Navigation -->
            <nav class="sidebar-nav" role="navigation" aria-label="Admin navigation">
                <?php include VIEW_PATH . '/partials/_admin_sidebar.php'; ?>
            </nav>
        </div>
    </aside>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content Area -->
    <main class="admin-main" id="main-content">
        <!-- Admin Breadcrumbs -->
        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
            <div class="breadcrumb-container">
                <?php include VIEW_PATH . '/partials/_breadcrumbs.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <?php if (isset($pageTitle) || isset($pageActions)): ?>
            <div class="page-header">
                <div class="page-header-content">
                    <?php if (isset($pageTitle)): ?>
                        <div class="page-title-section">
                            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                            <?php if (isset($pageSubtitle)): ?>
                                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($pageActions)): ?>
                        <div class="page-actions">
                            <?= $pageActions ?>
                        </div>
                    <?php endif; ?>
                </div>
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
        
        <!-- Admin Content -->
        <div class="admin-content">
            <?= $content ?? '' ?>
        </div>
    </main>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading...</span>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Action</h3>
                <button class="modal-close" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn btn-primary" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
    
    <!-- Core JavaScript -->
    <script src="<?= $baseUrl ?>/js/main.js?v=<?= file_exists(APP_ROOT . '/public/js/main.js') ? filemtime(APP_ROOT . '/public/js/main.js') : time() ?>"></script>
    
    <!-- Admin-specific JavaScript -->
    <script src="<?= $baseUrl ?>/js/admin.js?v=<?= file_exists(APP_ROOT . '/public/js/admin.js') ? filemtime(APP_ROOT . '/public/js/admin.js') : time() ?>"></script>
    
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
    
    <!-- Admin Layout Scripts -->
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileSidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
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
            
            // Confirmation modal functions
            window.showConfirmModal = function(message, callback) {
                document.getElementById('confirmMessage').textContent = message;
                document.getElementById('confirmModal').style.display = 'flex';
                
                const confirmBtn = document.getElementById('confirmButton');
                confirmBtn.onclick = function() {
                    closeConfirmModal();
                    if (callback) callback();
                };
            };
            
            window.closeConfirmModal = function() {
                document.getElementById('confirmModal').style.display = 'none';
            };
            
            // Data table enhancements
            const tables = document.querySelectorAll('.data-table');
            tables.forEach(table => {
                // Add hover effects
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    row.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = 'var(--gray-50)';
                    });
                    row.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                });
            });
        });
        
        // Keyboard navigation support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
            // Escape key closes modals and sidebar
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const modal = document.getElementById('confirmModal');
                
                if (modal && modal.style.display === 'flex') {
                    closeConfirmModal();
                } else if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
        
        // Delete confirmation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-btn') || e.target.closest('.delete-btn')) {
                e.preventDefault();
                const btn = e.target.classList.contains('delete-btn') ? e.target : e.target.closest('.delete-btn');
                const message = btn.dataset.message || 'Are you sure you want to delete this item? This action cannot be undone.';
                
                showConfirmModal(message, function() {
                    if (btn.tagName === 'A') {
                        window.location.href = btn.href;
                    } else if (btn.tagName === 'BUTTON' && btn.form) {
                        btn.form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>