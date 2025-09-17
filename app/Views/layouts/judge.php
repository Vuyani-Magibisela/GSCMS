<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GDE SciBOTICS - Judge Portal</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Judge Dashboard CSS -->
    <link rel="stylesheet" href="/GSCMS/public/css/judge-dashboard.css?v=<?= filemtime(__DIR__ . '/../../../public/css/judge-dashboard.css') ?>">

    <!-- Custom CSS -->
    <style>
        :root {
            /* Enhanced Modern Color System */
            --primary-color: #6366f1;
            --primary-hover: #5b21b6;
            --primary-light: #a5b4fc;
            --primary-dark: #4338ca;

            --secondary-color: #8b5cf6;
            --secondary-hover: #7c3aed;
            --secondary-light: #c4b5fd;

            --accent-color: #06b6d4;
            --accent-hover: #0891b2;

            --success-color: #10b981;
            --success-light: #6ee7b7;
            --warning-color: #f59e0b;
            --warning-light: #fcd34d;
            --danger-color: #ef4444;
            --danger-light: #fca5a5;
            --info-color: #3b82f6;
            --info-light: #93c5fd;

            /* Modern Neutral System */
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;

            /* Layout Variables */
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 70px;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

            /* Typography */
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;

            /* Transitions */
            --transition-fast: 150ms ease-in-out;
            --transition-base: 250ms ease-in-out;
            --transition-slow: 350ms ease-in-out;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--gray-50);
            color: var(--gray-800);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .judge-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Modern Sidebar */
        .judge-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: all var(--transition-base);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
        }

        .judge-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .sidebar-title {
            color: white;
            font-weight: 600;
            font-size: var(--font-size-lg);
            margin: 0;
            transition: opacity var(--transition-base);
        }

        .judge-sidebar.collapsed .sidebar-title {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
            list-style: none;
            margin: 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all var(--transition-fast);
            font-weight: 500;
            font-size: var(--font-size-sm);
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .nav-link:hover::before {
            opacity: 1;
        }

        .nav-link:hover {
            color: white;
            transform: translateX(4px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .nav-link.active::before {
            opacity: 1;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nav-text {
            transition: opacity var(--transition-base);
        }

        .judge-sidebar.collapsed .nav-text {
            opacity: 0;
            pointer-events: none;
        }

        /* Top Bar */
        .judge-topbar {
            height: var(--topbar-height);
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            z-index: 999;
            box-shadow: var(--shadow-sm);
            transition: left var(--transition-base);
        }

        .judge-sidebar.collapsed ~ .judge-content .judge-topbar {
            left: var(--sidebar-collapsed-width);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            color: var(--gray-600);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .sidebar-toggle:hover {
            background: var(--gray-100);
            color: var(--gray-800);
        }

        .sidebar-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            color: var(--gray-600);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .notification-btn:hover {
            background: var(--gray-100);
            color: var(--gray-800);
        }

        .notification-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            color: inherit;
        }

        .user-menu:hover {
            background: var(--gray-100);
        }

        .user-menu:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .user-menu.dropdown-toggle::after {
            display: none;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: var(--font-size-sm);
        }

        .user-info h6 {
            margin: 0;
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--gray-800);
        }

        .user-info span {
            font-size: var(--font-size-xs);
            color: var(--gray-500);
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            background: white !important;
            border: 1px solid var(--gray-200) !important;
            border-radius: var(--border-radius-lg) !important;
            box-shadow: var(--shadow-xl) !important;
            padding: 0.5rem 0 !important;
            min-width: 200px !important;
            margin-top: 0.5rem !important;
            z-index: 1055 !important;
            list-style: none !important;
            margin: 0.125rem 0 0 !important;
            font-size: var(--font-size-sm) !important;
            text-align: left !important;
        }

        .dropdown-menu li {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .dropdown-item {
            display: flex !important;
            align-items: center !important;
            padding: 0.75rem 1.5rem !important;
            color: var(--gray-700) !important;
            text-decoration: none !important;
            font-size: var(--font-size-sm) !important;
            font-weight: 500 !important;
            transition: all var(--transition-fast) !important;
            border: none !important;
            background: none !important;
            width: 100% !important;
            text-align: left !important;
            white-space: nowrap !important;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background: var(--gray-50) !important;
            color: var(--gray-900) !important;
            text-decoration: none !important;
        }

        .dropdown-item:active {
            background: var(--gray-100) !important;
            color: var(--gray-900) !important;
        }

        .dropdown-item i {
            width: 16px !important;
            opacity: 0.7 !important;
            margin-right: 0.5rem !important;
        }

        .dropdown-divider {
            margin: 0.5rem 0 !important;
            border-color: var(--gray-200) !important;
            border-top: 1px solid var(--gray-200) !important;
            height: 0 !important;
            overflow: hidden !important;
        }

        /* Dropdown visibility states */
        .dropdown-menu {
            display: none !important;
            position: absolute !important;
        }

        .dropdown-menu.show {
            display: block !important;
        }

        .dropdown {
            position: relative !important;
        }

        /* Override Bootstrap defaults */
        .dropdown-toggle:focus {
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25) !important;
        }

        /* Ensure proper stacking */
        .dropdown.show .dropdown-menu {
            transform: translate3d(0px, 100%, 0px) !important;
        }

        /* Notification Dropdown */
        .notification-dropdown {
            min-width: 320px;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-dropdown .dropdown-header {
            padding: 1rem 1.5rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-800);
        }

        /* Main Content */
        .judge-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-base);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .judge-sidebar.collapsed + .judge-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            margin-top: var(--topbar-height);
            background: var(--gray-50);
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .judge-sidebar {
                transform: translateX(-100%);
            }

            .judge-sidebar.mobile-open {
                transform: translateX(0);
            }

            .judge-content {
                margin-left: 0;
            }

            .judge-topbar {
                left: 0;
                padding: 0 1rem;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: all var(--transition-base);
            }

            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .dropdown-menu {
                min-width: 180px;
                right: 0;
                left: auto;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .judge-topbar {
                padding: 0 1rem;
                height: 60px;
            }

            .topbar-right .user-info {
                display: none;
            }

            .user-menu {
                padding: 0.5rem;
                gap: 0.5rem;
            }

            .user-avatar {
                width: 32px;
                height: 32px;
                font-size: var(--font-size-xs);
            }

            .notification-btn {
                padding: 0.375rem;
            }

            .dropdown-menu {
                min-width: 160px;
                margin-top: 0.25rem;
            }

            .dropdown-item {
                padding: 0.625rem 1rem;
                font-size: var(--font-size-xs);
            }
        }

        @media (max-width: 480px) {
            .judge-topbar {
                padding: 0 0.75rem;
            }

            .topbar-right {
                gap: 0.5rem;
            }

            .user-menu {
                padding: 0.375rem;
            }

            .dropdown-menu {
                transform: translateX(-20px);
            }
        }
    </style>
</head>
<body>
    <div class="judge-layout">
        <!-- Modern Sidebar -->
        <aside class="judge-sidebar" id="judgeSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-gavel"></i>
                </div>
                <h5 class="sidebar-title">Judge Portal</h5>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="/judge/dashboard" class="nav-link active">
                        <div class="nav-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/judge/assignments" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span class="nav-text">Assignments</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/judge/scoring" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span class="nav-text">Scoring</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/judge/live-scoring" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <span class="nav-text">Live Sessions</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/judge/schedule" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="nav-text">Schedule</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/judge/performance" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="nav-text">Performance</span>
                    </a>
                </div>

                <div class="nav-item">
                    <div class="nav-link" id="websocket-status" title="Connection Status">
                        <div class="nav-icon">
                            <i class="fas fa-circle text-success" id="websocket-indicator"></i>
                        </div>
                        <span class="nav-text" id="websocket-text">Connected</span>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content Area -->
        <div class="judge-content">
            <!-- Top Bar -->
            <header class="judge-topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <div class="topbar-right">
                    <button class="notification-btn" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>

                    <div class="dropdown">
                        <button class="user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?= strtoupper(substr($_SESSION['user_first_name'] ?? 'J', 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <h6><?= htmlspecialchars($_SESSION['user_first_name'] ?? 'Judge') ?></h6>
                                <span>Judge</span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/judge/profile">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/judge/auth/devices">
                                <i class="fas fa-mobile-alt me-2"></i>Devices
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/judge/auth/setup-2fa">
                                <i class="fas fa-shield-alt me-2"></i>Security Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/judge/auth/logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="main-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modern Judge Dashboard JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            initializeDropdowns();

            // Initialize modern sidebar
            const sidebarManager = new ModernSidebarManager();
            sidebarManager.init();

            // Initialize notification system
            const notificationSystem = new JudgeNotificationSystem();
            notificationSystem.init();

            // Initialize session management
            const sessionManager = new JudgeSessionManager();
            sessionManager.init();

            // Initialize WebSocket connection manager
            const websocketManager = new JudgeWebSocketManager();
            websocketManager.init();
        });

        function initializeDropdowns() {
            // Ensure Bootstrap dropdowns are working
            const dropdownElements = document.querySelectorAll('.dropdown-toggle');

            dropdownElements.forEach(function(dropdown) {
                // Initialize Bootstrap dropdown
                if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    new bootstrap.Dropdown(dropdown);
                }

                // Add fallback click handler
                dropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdownMenu = this.nextElementSibling || this.parentElement.querySelector('.dropdown-menu');

                    if (dropdownMenu) {
                        // Close all other dropdowns
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                            if (menu !== dropdownMenu) {
                                menu.classList.remove('show');
                            }
                        });

                        // Toggle current dropdown
                        dropdownMenu.classList.toggle('show');

                        // Position dropdown correctly
                        positionDropdown(this, dropdownMenu);
                    }
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                }
            });
        }

        function positionDropdown(trigger, menu) {
            const rect = trigger.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;

            // Reset positioning
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.transform = '';

            // Position dropdown
            if (rect.right + menuRect.width > windowWidth) {
                // Position to the left of trigger
                menu.style.right = '0';
                menu.style.left = 'auto';
            } else {
                // Position to the right of trigger
                menu.style.left = '0';
                menu.style.right = 'auto';
            }

            // Ensure dropdown doesn't go off screen vertically
            if (rect.bottom + menuRect.height > windowHeight) {
                menu.style.top = `-${menuRect.height + 5}px`;
            }
        }

        class ModernSidebarManager {
            constructor() {
                this.sidebar = document.getElementById('judgeSidebar');
                this.sidebarToggle = document.getElementById('sidebarToggle');
                this.sidebarOverlay = document.getElementById('sidebarOverlay');
                this.isCollapsed = localStorage.getItem('judge-sidebar-collapsed') === 'true';
                this.isMobile = window.innerWidth <= 1024;
            }

            init() {
                this.setupEventListeners();
                this.setupResponsive();
                this.applyStoredState();
                this.setActiveNavItem();
            }

            setupEventListeners() {
                // Sidebar toggle
                this.sidebarToggle?.addEventListener('click', () => {
                    this.toggleSidebar();
                });

                // Mobile overlay
                this.sidebarOverlay?.addEventListener('click', () => {
                    this.closeMobileSidebar();
                });

                // Window resize
                window.addEventListener('resize', () => {
                    this.handleResize();
                });

                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isMobile) {
                        this.closeMobileSidebar();
                    }
                });
            }

            setupResponsive() {
                this.isMobile = window.innerWidth <= 1024;

                if (this.isMobile) {
                    this.sidebar?.classList.remove('collapsed');
                } else {
                    this.sidebar?.classList.remove('mobile-open');
                    this.sidebarOverlay?.classList.remove('active');
                }
            }

            toggleSidebar() {
                if (this.isMobile) {
                    this.toggleMobileSidebar();
                } else {
                    this.toggleDesktopSidebar();
                }
            }

            toggleDesktopSidebar() {
                this.isCollapsed = !this.isCollapsed;
                this.sidebar?.classList.toggle('collapsed', this.isCollapsed);
                localStorage.setItem('judge-sidebar-collapsed', this.isCollapsed.toString());

                // Trigger custom event for other components
                window.dispatchEvent(new CustomEvent('sidebarToggle', {
                    detail: { collapsed: this.isCollapsed }
                }));
            }

            toggleMobileSidebar() {
                const isOpen = this.sidebar?.classList.contains('mobile-open');
                if (isOpen) {
                    this.closeMobileSidebar();
                } else {
                    this.openMobileSidebar();
                }
            }

            openMobileSidebar() {
                this.sidebar?.classList.add('mobile-open');
                this.sidebarOverlay?.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            closeMobileSidebar() {
                this.sidebar?.classList.remove('mobile-open');
                this.sidebarOverlay?.classList.remove('active');
                document.body.style.overflow = '';
            }

            handleResize() {
                const wasMobile = this.isMobile;
                this.isMobile = window.innerWidth <= 1024;

                if (wasMobile !== this.isMobile) {
                    if (this.isMobile) {
                        // Switched to mobile
                        this.sidebar?.classList.remove('collapsed');
                        this.closeMobileSidebar();
                    } else {
                        // Switched to desktop
                        this.sidebar?.classList.remove('mobile-open');
                        this.sidebarOverlay?.classList.remove('active');
                        document.body.style.overflow = '';

                        if (this.isCollapsed) {
                            this.sidebar?.classList.add('collapsed');
                        }
                    }
                }
            }

            applyStoredState() {
                if (!this.isMobile && this.isCollapsed) {
                    this.sidebar?.classList.add('collapsed');
                }
            }

            setActiveNavItem() {
                const currentPath = window.location.pathname;
                const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === currentPath ||
                        (currentPath.includes(link.getAttribute('href')) && link.getAttribute('href') !== '/judge/dashboard')) {
                        link.classList.add('active');
                    }
                });

                // Default to dashboard if no match
                if (!document.querySelector('.sidebar-nav .nav-link.active') && currentPath.includes('/judge')) {
                    document.querySelector('.sidebar-nav .nav-link[href="/judge/dashboard"]')?.classList.add('active');
                }
            }
        }

        class JudgeNotificationSystem {
            constructor() {
                this.lastCheck = Date.now();
                this.checkInterval = 30000; // 30 seconds
            }
            
            init() {
                this.loadNotifications();
                this.startPolling();
            }
            
            async loadNotifications() {
                try {
                    const response = await fetch('/judge/notifications', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        this.updateNotificationDropdown(data.notifications);
                        this.updateNotificationCount(data.notifications);
                    }
                } catch (error) {
                    console.error('Failed to load notifications:', error);
                }
            }
            
            updateNotificationDropdown(notifications) {
                const dropdown = document.getElementById('notification-dropdown');
                
                if (notifications.length === 0) {
                    dropdown.innerHTML = '<li><a class="dropdown-item text-muted" href="#"><small>No new notifications</small></a></li>';
                    return;
                }
                
                const recentNotifications = notifications.slice(0, 5);
                dropdown.innerHTML = recentNotifications.map(notification => `
                    <li>
                        <a class="dropdown-item ${!notification.is_read ? 'fw-bold' : ''}" 
                           href="${notification.action_url || '#'}"
                           data-notification-id="${notification.id}">
                            <div class="d-flex align-items-start">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">${notification.title}</div>
                                    <small class="text-muted">${notification.message.substring(0, 60)}${notification.message.length > 60 ? '...' : ''}</small>
                                    <div class="small text-muted mt-1">${this.timeAgo(notification.created_at)}</div>
                                </div>
                                ${!notification.is_read ? '<div class="badge bg-primary ms-2"></div>' : ''}
                            </div>
                        </a>
                    </li>
                `).join('');
            }
            
            updateNotificationCount(notifications) {
                const unreadCount = notifications.filter(n => !n.is_read).length;
                const badge = document.getElementById('notification-count');
                
                if (unreadCount > 0) {
                    badge.textContent = unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
            
            startPolling() {
                setInterval(() => {
                    this.loadNotifications();
                }, this.checkInterval);
            }
            
            timeAgo(datetime) {
                const time = Math.floor((Date.now() - new Date(datetime).getTime()) / 1000);
                
                if (time < 60) return 'just now';
                if (time < 3600) return Math.floor(time / 60) + 'm ago';
                if (time < 86400) return Math.floor(time / 3600) + 'h ago';
                if (time < 2592000) return Math.floor(time / 86400) + 'd ago';
                
                return new Date(datetime).toLocaleDateString();
            }
        }

        class JudgeSessionManager {
            constructor() {
                this.sessionCheckInterval = 60000; // 1 minute
                this.warningThreshold = 300000; // 5 minutes before expiry
                this.warningShown = false;
            }
            
            init() {
                this.startSessionMonitoring();
            }
            
            startSessionMonitoring() {
                setInterval(() => {
                    this.checkSessionStatus();
                }, this.sessionCheckInterval);
            }
            
            async checkSessionStatus() {
                try {
                    const response = await fetch('/judge/auth/session-status', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (response.status === 401) {
                        // Session expired
                        this.handleSessionExpiry();
                        return;
                    }
                    
                    const data = await response.json();
                    
                    if (data.expires_in < this.warningThreshold && !this.warningShown) {
                        this.showSessionWarning(data.expires_in);
                    }
                    
                } catch (error) {
                    console.error('Session check failed:', error);
                }
            }
            
            showSessionWarning(timeLeft) {
                this.warningShown = true;
                const minutes = Math.floor(timeLeft / 60000);
                
                const toast = this.createToast(
                    `Your session will expire in ${minutes} minutes. Please save your work.`,
                    'warning',
                    {
                        autohide: false,
                        actions: [
                            {
                                text: 'Extend Session',
                                action: () => this.extendSession()
                            }
                        ]
                    }
                );
                
                this.showToast(toast);
            }
            
            async extendSession() {
                try {
                    const response = await fetch('/judge/auth/extend-session', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (response.ok) {
                        this.warningShown = false;
                        this.showToast(this.createToast('Session extended successfully', 'success'));
                    }
                } catch (error) {
                    console.error('Failed to extend session:', error);
                }
            }
            
            handleSessionExpiry() {
                this.showToast(
                    this.createToast(
                        'Your session has expired. You will be redirected to login.',
                        'error',
                        { autohide: false }
                    )
                );
                
                setTimeout(() => {
                    window.location.href = '/judge/auth';
                }, 3000);
            }
            
            createToast(message, type = 'info', options = {}) {
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${this.getBootstrapClass(type)} border-0`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                if (!options.autohide) {
                    toast.setAttribute('data-bs-autohide', 'false');
                }
                
                let actionsHtml = '';
                if (options.actions) {
                    actionsHtml = options.actions.map(action => 
                        `<button type="button" class="btn btn-sm btn-outline-light me-2" onclick="${action.action.toString()}">${action.text}</button>`
                    ).join('');
                }
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body flex-grow-1">
                            ${message}
                            ${actionsHtml}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                return toast;
            }
            
            showToast(toast) {
                const container = document.getElementById('toast-container');
                container.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', () => {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                });
            }
            
            getBootstrapClass(type) {
                const classes = {
                    success: 'success',
                    error: 'danger',
                    warning: 'warning',
                    info: 'info'
                };
                return classes[type] || 'info';
            }
        }
        
        class JudgeWebSocketManager {
            constructor() {
                this.ws = null;
                this.reconnectAttempts = 0;
                this.maxReconnectAttempts = 5;
                this.reconnectDelay = 1000;
                this.heartbeatInterval = null;
                this.isConnected = false;
            }
            
            init() {
                this.connect();
                this.setupStatusIndicator();
            }
            
            connect() {
                const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const host = window.location.hostname;
                const port = 8080;
                const judgeId = <?= $_SESSION['judge_id'] ?? 'null' ?>;
                
                if (!judgeId) {
                    this.updateStatus('error', 'No judge session');
                    return;
                }
                
                try {
                    this.ws = new WebSocket(`${protocol}//${host}:${port}?judge=${judgeId}`);
                    this.setupWebSocketHandlers();
                    this.updateStatus('connecting', 'Connecting...');
                } catch (error) {
                    console.error('WebSocket connection failed:', error);
                    this.updateStatus('error', 'Connection failed');
                    this.scheduleReconnect();
                }
            }
            
            setupWebSocketHandlers() {
                this.ws.onopen = () => {
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    this.updateStatus('connected', 'Connected');
                    this.startHeartbeat();
                };
                
                this.ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.handleMessage(data);
                    } catch (error) {
                        console.error('Failed to parse WebSocket message:', error);
                    }
                };
                
                this.ws.onclose = () => {
                    this.isConnected = false;
                    this.stopHeartbeat();
                    this.updateStatus('disconnected', 'Disconnected');
                    this.scheduleReconnect();
                };
                
                this.ws.onerror = (error) => {
                    console.error('WebSocket error:', error);
                    this.updateStatus('error', 'Connection error');
                };
            }
            
            handleMessage(data) {
                switch (data.type) {
                    case 'session_started':
                        this.showNotification('Live session started', 'info');
                        break;
                    case 'session_ended':
                        this.showNotification('Live session ended', 'warning');
                        break;
                    case 'conflict_detected':
                        this.showNotification('Scoring conflict detected', 'error');
                        break;
                    case 'score_update':
                        this.handleScoreUpdate(data);
                        break;
                    case 'judge_notification':
                        this.showNotification(data.message, data.level || 'info');
                        break;
                }
            }
            
            handleScoreUpdate(data) {
                // Broadcast score update to any listening components
                window.dispatchEvent(new CustomEvent('liveScoreUpdate', { detail: data }));
            }
            
            sendMessage(type, data) {
                if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
                    const message = {
                        type: type,
                        data: data,
                        timestamp: Date.now(),
                        judge_id: <?= $_SESSION['judge_id'] ?? 'null' ?>
                    };
                    
                    this.ws.send(JSON.stringify(message));
                    return true;
                }
                return false;
            }
            
            startHeartbeat() {
                this.heartbeatInterval = setInterval(() => {
                    if (this.isConnected) {
                        this.sendMessage('heartbeat', {});
                    }
                }, 30000); // 30 seconds
            }
            
            stopHeartbeat() {
                if (this.heartbeatInterval) {
                    clearInterval(this.heartbeatInterval);
                    this.heartbeatInterval = null;
                }
            }
            
            scheduleReconnect() {
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
                    
                    setTimeout(() => {
                        this.updateStatus('connecting', `Reconnecting (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
                        this.connect();
                    }, delay);
                } else {
                    this.updateStatus('error', 'Connection failed');
                }
            }
            
            updateStatus(status, text) {
                const indicator = document.getElementById('websocket-indicator');
                const statusText = document.getElementById('websocket-text');
                
                if (indicator) {
                    indicator.className = 'fas fa-circle';
                    switch (status) {
                        case 'connected':
                            indicator.classList.add('text-success');
                            break;
                        case 'connecting':
                            indicator.classList.add('text-warning');
                            break;
                        case 'disconnected':
                            indicator.classList.add('text-muted');
                            break;
                        case 'error':
                            indicator.classList.add('text-danger');
                            break;
                    }
                }
                
                if (statusText) {
                    statusText.textContent = text;
                }
            }
            
            setupStatusIndicator() {
                const statusElement = document.getElementById('websocket-status');
                if (statusElement) {
                    statusElement.addEventListener('click', () => {
                        if (!this.isConnected) {
                            this.reconnectAttempts = 0;
                            this.connect();
                        }
                    });
                }
            }
            
            showNotification(message, type) {
                // Use the existing toast system
                if (window.showToast) {
                    const toast = this.createToast(message, type);
                    window.showToast(toast);
                } else {
                    // Fallback to browser notification
                    console.log(`${type.toUpperCase()}: ${message}`);
                }
            }
            
            createToast(message, type) {
                // Use the same toast creation from session manager
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${this.getBootstrapClass(type)} border-0`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body flex-grow-1">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                return toast;
            }
            
            getBootstrapClass(type) {
                const classes = {
                    success: 'success',
                    error: 'danger',
                    warning: 'warning',
                    info: 'info'
                };
                return classes[type] || 'info';
            }
            
            disconnect() {
                if (this.ws) {
                    this.ws.close();
                }
                this.stopHeartbeat();
            }
        }
        
        // Global WebSocket manager instance
        window.judgeWebSocket = null;
        
        // Helper function to check current route (would be implemented server-side)
        <?php if (isset($this)): ?>
        window.isCurrentRoute = function(routeName) {
            // This would be implemented to check against the current route
            return false;
        };
        <?php endif; ?>
    </script>
</body>
</html>