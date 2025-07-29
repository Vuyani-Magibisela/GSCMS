/**
 * GSCMS Main JavaScript
 * Core functionality and utilities
 */

(function() {
    'use strict';

    // Global app object
    window.GSCMS = window.GSCMS || {};

    // Configuration
    GSCMS.config = {
        fadeInThreshold: 0.1,
        toastDuration: 5000,
        debounceDelay: 300
    };

    // Utility functions
    GSCMS.utils = {
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Throttle function
        throttle: function(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        // Element selector helper
        $: function(selector, context = document) {
            return context.querySelector(selector);
        },

        // Element selector all helper
        $$: function(selector, context = document) {
            return context.querySelectorAll(selector);
        },

        // Add event listener helper
        on: function(element, event, handler, options = false) {
            if (element) {
                element.addEventListener(event, handler, options);
            }
        },

        // Remove event listener helper
        off: function(element, event, handler, options = false) {
            if (element) {
                element.removeEventListener(event, handler, options);
            }
        },

        // Add class helper
        addClass: function(element, className) {
            if (element && className) {
                element.classList.add(className);
            }
        },

        // Remove class helper
        removeClass: function(element, className) {
            if (element && className) {
                element.classList.remove(className);
            }
        },

        // Toggle class helper
        toggleClass: function(element, className) {
            if (element && className) {
                element.classList.toggle(className);
            }
        },

        // Has class helper
        hasClass: function(element, className) {
            return element && className ? element.classList.contains(className) : false;
        },

        // Get data attribute
        getData: function(element, key) {
            return element ? element.dataset[key] : null;
        },

        // Set data attribute
        setData: function(element, key, value) {
            if (element) {
                element.dataset[key] = value;
            }
        },

        // Animate element
        animate: function(element, keyframes, options = {}) {
            if (element && element.animate) {
                return element.animate(keyframes, {
                    duration: 300,
                    easing: 'ease-out',
                    fill: 'forwards',
                    ...options
                });
            }
        },

        // Scroll to element
        scrollTo: function(element, options = {}) {
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                    ...options
                });
            }
        },

        // Format number
        formatNumber: function(num) {
            return new Intl.NumberFormat().format(num);
        },

        // Format date
        formatDate: function(date, options = {}) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                ...options
            }).format(new Date(date));
        }
    };

    // Fade-in animation observer
    GSCMS.animations = {
        init: function() {
            this.observeFadeInElements();
            this.observeCounterElements();
        },

        observeFadeInElements: function() {
            const elements = GSCMS.utils.$$('.fade-in');
            
            if (!elements.length) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        GSCMS.utils.addClass(entry.target, 'visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { 
                threshold: GSCMS.config.fadeInThreshold,
                rootMargin: '50px'
            });

            elements.forEach(element => observer.observe(element));
        },

        observeCounterElements: function() {
            const counters = GSCMS.utils.$$('[data-counter]');
            
            if (!counters.length) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            counters.forEach(counter => observer.observe(counter));
        },

        animateCounter: function(element) {
            const target = parseInt(GSCMS.utils.getData(element, 'counter'));
            const duration = parseInt(GSCMS.utils.getData(element, 'duration')) || 2000;
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 16);
        }
    };

    // Form utilities
    GSCMS.forms = {
        init: function() {
            this.initFormValidation();
            this.initFormEnhancements();
        },

        initFormValidation: function() {
            const forms = GSCMS.utils.$$('form[data-validation]');
            
            forms.forEach(form => {
                GSCMS.utils.on(form, 'submit', (e) => {
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    GSCMS.utils.addClass(form, 'was-validated');
                });
            });
        },

        validateForm: function(form) {
            const inputs = GSCMS.utils.$$(
                'input[required], select[required], textarea[required]', 
                form
            );
            
            let isValid = true;

            inputs.forEach(input => {
                if (!this.validateInput(input)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        validateInput: function(input) {
            const value = input.value.trim();
            const type = input.type;
            let isValid = true;

            // Required validation
            if (input.hasAttribute('required') && !value) {
                isValid = false;
            }

            // Email validation
            if (type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                }
            }

            // Phone validation
            if (input.hasAttribute('data-phone') && value) {
                const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                }
            }

            // Password strength validation
            if (type === 'password' && input.hasAttribute('data-strength')) {
                const minLength = 8;
                const hasUpper = /[A-Z]/.test(value);
                const hasLower = /[a-z]/.test(value);
                const hasNumber = /\d/.test(value);
                
                if (value.length < minLength || !hasUpper || !hasLower || !hasNumber) {
                    isValid = false;
                }
            }

            // Update input state
            if (isValid) {
                GSCMS.utils.removeClass(input, 'is-invalid');
                GSCMS.utils.addClass(input, 'is-valid');
            } else {
                GSCMS.utils.removeClass(input, 'is-valid');
                GSCMS.utils.addClass(input, 'is-invalid');
            }

            return isValid;
        },

        initFormEnhancements: function() {
            // Auto-resize textareas
            const textareas = GSCMS.utils.$$('textarea[data-auto-resize]');
            textareas.forEach(textarea => {
                GSCMS.utils.on(textarea, 'input', () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                });
            });

            // Character counter
            const inputs = GSCMS.utils.$$('[data-maxlength]');
            inputs.forEach(input => {
                const maxLength = GSCMS.utils.getData(input, 'maxlength');
                const counter = document.createElement('small');
                counter.className = 'form-text text-muted';
                input.parentNode.appendChild(counter);

                const updateCounter = () => {
                    const remaining = maxLength - input.value.length;
                    counter.textContent = `${remaining} characters remaining`;
                    
                    if (remaining < 0) {
                        GSCMS.utils.addClass(counter, 'text-danger');
                    } else {
                        GSCMS.utils.removeClass(counter, 'text-danger');
                    }
                };

                GSCMS.utils.on(input, 'input', updateCounter);
                updateCounter();
            });
        }
    };

    // Toast notifications
    GSCMS.toast = {
        container: null,

        init: function() {
            this.createContainer();
        },

        createContainer: function() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }
        },

        show: function(message, type = 'info', duration = GSCMS.config.toastDuration) {
            this.createContainer();

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = this.getIcon(type);
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="${icon}"></i>
                    <span>${message}</span>
                    <button class="toast-close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            // Add close functionality
            const closeBtn = GSCMS.utils.$('.toast-close', toast);
            GSCMS.utils.on(closeBtn, 'click', () => this.hide(toast));

            // Add to container
            this.container.appendChild(toast);

            // Trigger animation
            setTimeout(() => GSCMS.utils.addClass(toast, 'show'), 10);

            // Auto-hide
            if (duration > 0) {
                setTimeout(() => this.hide(toast), duration);
            }

            return toast;
        },

        hide: function(toast) {
            GSCMS.utils.addClass(toast, 'hiding');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        },

        getIcon: function(type) {
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            return icons[type] || icons.info;
        },

        success: function(message, duration) {
            return this.show(message, 'success', duration);
        },

        error: function(message, duration) {
            return this.show(message, 'error', duration);
        },

        warning: function(message, duration) {
            return this.show(message, 'warning', duration);
        },

        info: function(message, duration) {
            return this.show(message, 'info', duration);
        }
    };

    // Loading utilities
    GSCMS.loading = {
        show: function(target = document.body) {
            const loader = document.createElement('div');
            loader.className = 'loading-overlay';
            loader.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading...</span>
                </div>
            `;
            
            if (target === document.body) {
                loader.style.position = 'fixed';
            } else {
                loader.style.position = 'absolute';
                target.style.position = 'relative';
            }
            
            target.appendChild(loader);
            return loader;
        },

        hide: function(loader) {
            if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        }
    };

    // Keyboard navigation
    GSCMS.keyboard = {
        init: function() {
            // Track keyboard usage for focus management
            GSCMS.utils.on(document, 'keydown', (e) => {
                if (e.key === 'Tab') {
                    GSCMS.utils.addClass(document.body, 'keyboard-navigation');
                }
                
                // Escape key handling
                if (e.key === 'Escape') {
                    this.handleEscape();
                }
            });

            GSCMS.utils.on(document, 'mousedown', () => {
                GSCMS.utils.removeClass(document.body, 'keyboard-navigation');
            });
        },

        handleEscape: function() {
            // Close modals
            const modals = GSCMS.utils.$$('.modal.show');
            modals.forEach(modal => {
                const closeBtn = GSCMS.utils.$('[data-dismiss="modal"]', modal);
                if (closeBtn) closeBtn.click();
            });

            // Close dropdowns
            const dropdowns = GSCMS.utils.$$('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                GSCMS.utils.removeClass(dropdown, 'show');
            });

            // Close mobile menus
            const mobileMenus = GSCMS.utils.$$('.mobile-menu.active, .sidebar.active');
            mobileMenus.forEach(menu => {
                GSCMS.utils.removeClass(menu, 'active');
            });
        }
    };

    // Initialize everything when DOM is ready
    function init() {
        GSCMS.animations.init();
        GSCMS.forms.init();
        GSCMS.toast.init();
        GSCMS.keyboard.init();

        // Custom event for when GSCMS is ready
        const event = new CustomEvent('gscms:ready', { detail: GSCMS });
        document.dispatchEvent(event);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        GSCMS.utils.on(document, 'DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose utilities globally
    window.showToast = GSCMS.toast.show.bind(GSCMS.toast);
    window.showLoading = GSCMS.loading.show.bind(GSCMS.loading);
    window.hideLoading = GSCMS.loading.hide.bind(GSCMS.loading);

})();