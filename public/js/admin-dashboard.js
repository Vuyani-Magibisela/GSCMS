/**
 * Admin Dashboard JavaScript
 * Handles interactive features and real-time updates
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    // Initialize all dashboard components
    initializeStatCards();
    initializeQuickActions();
    initializeWidgets();
    initializeRealTimeUpdates();
    initializeKeyboardShortcuts();
    initializeTooltips();
    
    console.log('Admin Dashboard initialized successfully');
}

/**
 * Initialize statistics cards with hover effects and animations
 */
function initializeStatCards() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach((card, index) => {
        // Add entrance animation with stagger
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
        
        // Add hover interaction
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        // Animate numbers on load
        const numberElement = card.querySelector('.stat-number');
        if (numberElement) {
            animateNumber(numberElement);
        }
    });
}

/**
 * Animate number counting effect
 */
function animateNumber(element) {
    const finalNumber = parseInt(element.textContent.replace(/,/g, ''));
    const duration = 1500;
    const steps = 60;
    const increment = finalNumber / steps;
    let currentNumber = 0;
    let step = 0;
    
    const timer = setInterval(() => {
        step++;
        currentNumber += increment;
        
        if (step >= steps) {
            currentNumber = finalNumber;
            clearInterval(timer);
        }
        
        element.textContent = Math.floor(currentNumber).toLocaleString();
    }, duration / steps);
}

/**
 * Initialize quick action buttons
 */
function initializeQuickActions() {
    const quickActions = document.querySelectorAll('.quick-action-btn');
    
    quickActions.forEach(button => {
        button.addEventListener('click', function(e) {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Show loading state if needed
            const icon = this.querySelector('i');
            if (icon) {
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                setTimeout(() => {
                    icon.className = originalClass;
                }, 1000);
            }
        });
    });
}

/**
 * Initialize dashboard widgets
 */
function initializeWidgets() {
    initializePendingApprovals();
    initializeRecentActivity();
    initializeUpcomingDeadlines();
    initializeSystemStatus();
}

/**
 * Initialize pending approvals widget
 */
function initializePendingApprovals() {
    const approvalItems = document.querySelectorAll('.approval-item');
    
    approvalItems.forEach(item => {
        item.addEventListener('click', function() {
            const actionLink = this.querySelector('.approval-action');
            if (actionLink) {
                window.location.href = actionLink.getAttribute('href');
            }
        });
        
        // Add hover effects
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
            this.style.cursor = 'pointer';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
}

/**
 * Initialize recent activity widget
 */
function initializeRecentActivity() {
    const activityItems = document.querySelectorAll('.activity-item');
    
    activityItems.forEach((item, index) => {
        // Staggered animation
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('slide-in-left');
    });
}

/**
 * Initialize upcoming deadlines widget
 */
function initializeUpcomingDeadlines() {
    const deadlineItems = document.querySelectorAll('.deadline-item');
    
    deadlineItems.forEach(item => {
        const daysRemaining = parseInt(item.querySelector('.deadline-days').textContent);
        
        // Add urgency animations
        if (daysRemaining <= 3) {
            item.classList.add('pulse-urgent');
        } else if (daysRemaining <= 7) {
            item.classList.add('pulse-warning');
        }
        
        // Add click interaction
        item.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}

/**
 * Initialize system status widget
 */
function initializeSystemStatus() {
    const statusIndicators = document.querySelectorAll('.status-indicator');
    
    statusIndicators.forEach(indicator => {
        if (indicator.classList.contains('healthy')) {
            indicator.classList.add('pulse-success');
        } else if (indicator.classList.contains('warning')) {
            indicator.classList.add('pulse-warning');
        } else if (indicator.classList.contains('error')) {
            indicator.classList.add('pulse-error');
        }
    });
    
    // Auto-refresh system status
    setInterval(updateSystemStatus, 30000); // Every 30 seconds
}

/**
 * Update system status
 */
async function updateSystemStatus() {
    try {
        const response = await fetch('/admin/api/system-status');
        const data = await response.json();
        
        if (data.success) {
            updateStatusIndicators(data.status);
        }
    } catch (error) {
        console.warn('Failed to update system status:', error);
    }
}

/**
 * Update status indicators
 */
function updateStatusIndicators(status) {
    const indicators = {
        database: document.querySelector('.status-item:nth-child(1) .status-indicator'),
        storage: document.querySelector('.status-item:nth-child(2) .status-indicator'),
        memory: document.querySelector('.status-item:nth-child(3) .status-indicator')
    };
    
    Object.keys(indicators).forEach(key => {
        const indicator = indicators[key];
        if (indicator && status[key]) {
            indicator.className = `status-indicator ${status[key]}`;
        }
    });
}

/**
 * Initialize real-time updates
 */
function initializeRealTimeUpdates() {
    // Check for updates every 5 minutes
    setInterval(checkForUpdates, 300000);
    
    // Initial check after 30 seconds
    setTimeout(checkForUpdates, 30000);
}

/**
 * Check for dashboard updates
 */
async function checkForUpdates() {
    try {
        const response = await fetch('/admin/api/dashboard-updates');
        const data = await response.json();
        
        if (data.success && data.hasUpdates) {
            showUpdateNotification(data.updates);
        }
    } catch (error) {
        console.warn('Failed to check for updates:', error);
    }
}

/**
 * Show update notification
 */
function showUpdateNotification(updates) {
    const notification = createNotification(
        'Dashboard Updated',
        `${updates} new items require attention`,
        'info'
    );
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

/**
 * Create notification element
 */
function createNotification(title, message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <h4 class="notification-title">${title}</h4>
            <p class="notification-message">${message}</p>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    return notification;
}

/**
 * Initialize keyboard shortcuts
 */
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Only trigger when Alt key is held
        if (!e.altKey) return;
        
        switch(e.key) {
            case 'd':
                e.preventDefault();
                window.location.href = '/admin/dashboard';
                break;
            case 's':
                e.preventDefault();
                window.location.href = '/admin/schools';
                break;
            case 't':
                e.preventDefault();
                window.location.href = '/admin/teams';
                break;
            case 'r':
                e.preventDefault();
                window.location.href = '/admin/reports';
                break;
            case 'u':
                e.preventDefault();
                window.location.href = '/admin/users';
                break;
        }
    });
    
    // Show keyboard shortcuts help
    document.addEventListener('keydown', function(e) {
        if (e.key === '?' && e.shiftKey) {
            showKeyboardShortcuts();
        }
    });
}

/**
 * Show keyboard shortcuts modal
 */
function showKeyboardShortcuts() {
    const modal = document.createElement('div');
    modal.className = 'keyboard-shortcuts-modal';
    modal.innerHTML = `
        <div class="modal-backdrop" onclick="this.parentElement.remove()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Keyboard Shortcuts</h3>
                <button onclick="this.closest('.keyboard-shortcuts-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="shortcut-list">
                    <div class="shortcut-item">
                        <kbd>Alt + D</kbd>
                        <span>Dashboard</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Alt + S</kbd>
                        <span>Schools</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Alt + T</kbd>
                        <span>Teams</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Alt + R</kbd>
                        <span>Reports</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Alt + U</kbd>
                        <span>Users</span>
                    </div>
                    <div class="shortcut-item">
                        <kbd>Shift + ?</kbd>
                        <span>Show this help</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    const elementsWithTooltips = document.querySelectorAll('[title]');
    
    elementsWithTooltips.forEach(element => {
        const title = element.getAttribute('title');
        element.removeAttribute('title');
        
        element.addEventListener('mouseenter', function(e) {
            showTooltip(e.target, title);
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

/**
 * Show tooltip
 */
function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    tooltip.classList.add('tooltip-visible');
}

/**
 * Hide tooltip
 */
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * Utility function to format numbers
 */
function formatNumber(number) {
    if (number >= 1000000) {
        return (number / 1000000).toFixed(1) + 'M';
    } else if (number >= 1000) {
        return (number / 1000).toFixed(1) + 'K';
    }
    return number.toString();
}

/**
 * Utility function to format time ago
 */
function formatTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diffInSeconds = Math.floor((now - time) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    return `${Math.floor(diffInSeconds / 86400)} days ago`;
}

/* CSS Animations */
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes pulseUrgent {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
    }
    
    @keyframes pulseWarning {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
    }
    
    @keyframes pulseSuccess {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    .slide-in-left {
        animation: slideInLeft 0.5s ease forwards;
    }
    
    .pulse-urgent {
        animation: pulseUrgent 2s infinite;
    }
    
    .pulse-warning {
        animation: pulseWarning 2s infinite;
    }
    
    .pulse-success {
        animation: pulseSuccess 2s infinite;
    }
    
    .pulse-error {
        animation: pulseUrgent 2s infinite;
    }
    
    /* Notification Styles */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        max-width: 400px;
        z-index: 1000;
        animation: slideInRight 0.3s ease;
    }
    
    .notification-info {
        border-left: 4px solid #3b82f6;
    }
    
    .notification-success {
        border-left: 4px solid #10b981;
    }
    
    .notification-warning {
        border-left: 4px solid #f59e0b;
    }
    
    .notification-error {
        border-left: 4px solid #ef4444;
    }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-size: 0.875rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
    }
    
    .notification-message {
        font-size: 0.75rem;
        color: #6b7280;
        margin: 0;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 0.25rem;
    }
    
    /* Keyboard Shortcuts Modal */
    .keyboard-shortcuts-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .keyboard-shortcuts-modal .modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
    }
    
    .keyboard-shortcuts-modal .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        position: relative;
        max-width: 400px;
        width: 90%;
    }
    
    .keyboard-shortcuts-modal .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 1.5rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .keyboard-shortcuts-modal .modal-body {
        padding: 1.5rem;
    }
    
    .shortcut-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .shortcut-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .shortcut-item kbd {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-family: monospace;
    }
    
    /* Tooltip Styles */
    .tooltip {
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        white-space: nowrap;
        z-index: 1000;
        opacity: 0;
        transform: translateY(4px);
        transition: all 0.2s ease;
        pointer-events: none;
    }
    
    .tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid #1f2937;
    }
    
    .tooltip-visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;

document.head.appendChild(style);