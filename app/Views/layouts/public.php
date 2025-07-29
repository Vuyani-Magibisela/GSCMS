<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'GDE SciBOTICS Competition Management System') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'GDE SciBOTICS Competition 2025 - Empowering Future Innovators Through Robotics Excellence') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>/images/favicon.ico">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Main CSS Framework -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css?v=<?= file_exists(APP_ROOT . '/public/css/style.css') ? filemtime(APP_ROOT . '/public/css/style.css') : time() ?>">
    
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
    
    <!-- Page Class -->
    <style>
        body.<?= htmlspecialchars($pageClass ?? 'public-page') ?> {
            <?= $bodyStyles ?? '' ?>
        }
    </style>
</head>
<body class="<?= htmlspecialchars($pageClass ?? 'public-page') ?>">
    
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
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
    
    <!-- Main Content -->
    <main id="main-content">
        <?= $content ?? '' ?>
    </main>
    
    <!-- Core JavaScript -->
    <script src="<?= $baseUrl ?>/js/main.js?v=<?= file_exists(APP_ROOT . '/public/js/main.js') ? filemtime(APP_ROOT . '/public/js/main.js') : time() ?>"></script>
    
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
    
    <!-- Accessibility and Animation Scripts -->
    <script>
        // Fade-in animation observer
        const observeElements = () => {
            const elements = document.querySelectorAll('.fade-in');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });
            
            elements.forEach(element => observer.observe(element));
        };
        
        // Initialize when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', observeElements);
        } else {
            observeElements();
        }
        
        // Accessible focus management
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    </script>
</body>
</html>