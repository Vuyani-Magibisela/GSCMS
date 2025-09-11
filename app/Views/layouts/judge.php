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
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --danger-color: #f56565;
            --info-color: #4299e1;
            --dark-color: #2d3748;
            --gray-100: #f7fafc;
            --gray-200: #edf2f7;
            --gray-300: #e2e8f0;
            --gray-400: #cbd5e0;
            --gray-500: #a0aec0;
            --gray-600: #718096;
            --gray-700: #4a5568;
            --gray-800: #2d3748;
            --gray-900: #1a202c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
        }

        .judge-navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.75rem 0;
        }

        .judge-navbar .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 1.25rem;
        }

        .judge-navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 20px;
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
        }

        .judge-navbar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white !important;
        }

        .judge-navbar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }

        .judge-navbar .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
        }

        .main-content {
            min-height: calc(100vh - 80px);
            padding-top: 2rem;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-radius: 15px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .notification-indicator {
            position: relative;
        }

        .notification-indicator .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .judge-footer {
            background: var(--gray-800);
            color: var(--gray-400);
            padding: 1rem 0;
            margin-top: auto;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        @media (max-width: 768px) {
            .judge-navbar .navbar-nav {
                padding-top: 1rem;
            }
            
            .main-content {
                padding-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg judge-navbar">
        <div class="container">
            <a class="navbar-brand" href="/judge/dashboard">
                <i class="fas fa-gavel me-2"></i>
                Judge Portal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#judgeNavbar">
                <span class="navbar-toggler-icon text-white">
                    <i class="fas fa-bars"></i>
                </span>
            </button>
            
            <div class="collapse navbar-collapse" id="judgeNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $this->isCurrentRoute('judge.dashboard') ? 'active' : '' ?>" href="/judge/dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $this->isCurrentRoute('judge.assignments') ? 'active' : '' ?>" href="/judge/assignments">
                            <i class="fas fa-tasks me-1"></i>
                            Assignments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $this->isCurrentRoute('judge.scoring') ? 'active' : '' ?>" href="/judge/scoring">
                            <i class="fas fa-clipboard-list me-1"></i>
                            Scoring
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $this->isCurrentRoute('judge.schedule') ? 'active' : '' ?>" href="/judge/schedule">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Schedule
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle notification-indicator" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge" id="notification-count" style="display: none;">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                            <li class="dropdown-header">
                                <h6 class="mb-0">Notifications</h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notification-dropdown" style="max-height: 300px; overflow-y: auto;">
                                <li><a class="dropdown-item text-muted" href="#"><small>Loading notifications...</small></a></li>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="/judge/notifications">View All Notifications</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_first_name'] ?? 'Judge') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/judge/profile">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/judge/performance">
                                <i class="fas fa-chart-line me-2"></i>Performance
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
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="judge-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?= date('Y') ?> GDE SciBOTICS Competition Management System
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure Judge Portal
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize notification system
            const notificationSystem = new JudgeNotificationSystem();
            notificationSystem.init();
            
            // Initialize session management
            const sessionManager = new JudgeSessionManager();
            sessionManager.init();
        });

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