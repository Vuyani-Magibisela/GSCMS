// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeCountdown();
    initializeAnimations();
    initializeMobileMenu();
    initializeUserDropdown();
    
    console.log('🤖 GDE SciBOTICS 2025 - Landing Page Loaded Successfully!');
    console.log('🚀 Ready for the future of robotics education!');
});

// Navigation functionality
function initializeNavigation() {
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Close mobile menu if open
                const mobileMenu = document.getElementById('mobileMenu');
                const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                if (mobileMenu && mobileMenu.classList.contains('active')) {
                    mobileMenu.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                }
            }
        });
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

// Mobile menu functionality
function initializeMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            mobileMenuToggle.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const navbar = document.querySelector('.nav-container');
            if (!navbar.contains(e.target) && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close mobile menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
}

// User dropdown functionality
function initializeUserDropdown() {
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle the dropdown
            userDropdownMenu.classList.toggle('active');
            
            // Update aria-expanded
            const isExpanded = userDropdownMenu.classList.contains('active');
            userDropdownToggle.setAttribute('aria-expanded', isExpanded);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdownToggle.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('active');
                userDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && userDropdownMenu.classList.contains('active')) {
                userDropdownMenu.classList.remove('active');
                userDropdownToggle.setAttribute('aria-expanded', 'false');
                userDropdownToggle.focus();
            }
        });

        // Handle keyboard navigation in dropdown
        const dropdownItems = userDropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach((item, index) => {
            item.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIndex = (index + 1) % dropdownItems.length;
                    dropdownItems[nextIndex].focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIndex = (index - 1 + dropdownItems.length) % dropdownItems.length;
                    dropdownItems[prevIndex].focus();
                }
            });
        });
    }
}

// Countdown Timer functionality
function initializeCountdown() {
    function updateCountdown() {
        // Set target date (August 31, 2025 for Phase 2)
        const targetDate = new Date('2025-08-31T00:00:00').getTime();
        const now = new Date().getTime();
        const timeLeft = targetDate - now;

        if (timeLeft > 0) {
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            const daysEl = document.getElementById('days');
            const hoursEl = document.getElementById('hours');
            const minutesEl = document.getElementById('minutes');
            const secondsEl = document.getElementById('seconds');

            if (daysEl) daysEl.textContent = days;
            if (hoursEl) hoursEl.textContent = hours;
            if (minutesEl) minutesEl.textContent = minutes;
            if (secondsEl) secondsEl.textContent = seconds;
        } else {
            // Competition has started or ended
            const elements = ['days', 'hours', 'minutes', 'seconds'];
            elements.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '0';
            });
        }
    }

    // Update countdown every second
    updateCountdown(); // Initial call
    setInterval(updateCountdown, 1000);
}

// Animation functionality
function initializeAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observe all fade-in elements
    document.querySelectorAll('.fade-in').forEach(el => {
        observer.observe(el);
    });

    // Stats number animation
    initializeStatsAnimation();

    // Button hover effects
    initializeButtonEffects();

    // Parallax effect for hero section
    initializeParallaxEffect();

    // Typing effect for hero title
    initializeTypingEffect();
}

// Stats animation
function initializeStatsAnimation() {
    function animateNumbers() {
        const numbers = document.querySelectorAll('.stat-number');
        const targetValues = [4, 3, 15, 6]; // Corresponding to the stats
        
        numbers.forEach((number, index) => {
            if (number.dataset.animated) return; // Prevent re-animation
            
            const target = targetValues[index];
            let current = 0;
            const increment = target / 50; // Animation duration control
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                    number.dataset.animated = 'true';
                }
                number.textContent = Math.floor(current);
            }, 30);
        });
    }

    // Trigger number animation when stats section is visible
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumbers();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        statsObserver.observe(statsSection);
    }
}

// Button effects
function initializeButtonEffects() {
    // Add hover effects to interactive elements
    const interactiveElements = document.querySelectorAll('.cta-button, .nav-btn, .category-card, .stat-card, .feature-card');

    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.cursor = 'default';
        });
    });

    // Add ripple effect to buttons
    document.querySelectorAll('.cta-button, .nav-btn').forEach(button => {
        button.classList.add('btn-ripple');
        
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

// Parallax effect
function initializeParallaxEffect() {
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const hero = document.querySelector('.hero');
        if (hero && window.innerWidth > 768) {
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });
}

// Typing effect
function initializeTypingEffect() {
    function typeWriter(element, text, speed = 100) {
        let i = 0;
        element.innerHTML = '';
        
        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else {
                // Add blinking cursor effect
                element.innerHTML += '<span class="cursor">|</span>';
                setInterval(() => {
                    const cursor = element.querySelector('.cursor');
                    if (cursor) {
                        cursor.style.opacity = cursor.style.opacity === '0' ? '1' : '0';
                    }
                }, 500);
            }
        }
        type();
    }

    // Initialize typing effect when page loads
    window.addEventListener('load', function() {
        const heroTitle = document.querySelector('.hero h1');
        if (heroTitle && window.innerWidth > 768) {
            const originalText = heroTitle.textContent;
            setTimeout(() => {
                typeWriter(heroTitle, originalText, 150);
            }, 1000);
        }
    });
}

// Floating animation for category cards
function initializeFloatingAnimation() {
    document.querySelectorAll('.category-card').forEach((card, index) => {
        // Stagger the animation start time
        setTimeout(() => {
            card.style.animation = `float 6s ease-in-out infinite`;
            card.style.animationDelay = `${index * 0.5}s`;
        }, index * 200);
    });
}

// Enhanced hover effects for cards
function initializeCardEffects() {
    // Category cards
    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Feature cards
    document.querySelectorAll('.feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Keyboard navigation support
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // ESC key to close mobile menu
        if (e.key === 'Escape') {
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });

    // Add accessibility improvements
    document.querySelectorAll('button, a, input, select').forEach(element => {
        if (!element.hasAttribute('tabindex') && !element.href) {
            element.setAttribute('tabindex', '0');
        }
    });
}

// Smooth page load animation
function initializePageLoadAnimation() {
    window.addEventListener('load', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease-in-out';
        
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);
    });
}

// Performance optimization: Lazy load images
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    }
}

// Utility functions
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Optimize scroll events
const optimizedScrollHandler = throttle(() => {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 100) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
}, 16); // ~60fps

// Replace the existing scroll event listener
window.removeEventListener('scroll', initializeNavigation);
window.addEventListener('scroll', optimizedScrollHandler);

// Initialize additional features
document.addEventListener('DOMContentLoaded', function() {
    initializeFloatingAnimation();
    initializeCardEffects();
    initializeKeyboardNavigation();
    initializePageLoadAnimation();
    initializeLazyLoading();
});

// Add CSS animations dynamically
function addDynamicStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: rippleEffect 0.6s linear;
        }
        
        @keyframes rippleEffect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .cursor {
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    `;
    document.head.appendChild(style);
}

// Initialize dynamic styles
addDynamicStyles();

// Error handling for missing elements
function safeQuerySelector(selector, callback) {
    const element = document.querySelector(selector);
    if (element && typeof callback === 'function') {
        callback(element);
    }
}

// Safe event listeners
function safeAddEventListener(selector, event, handler) {
    const elements = document.querySelectorAll(selector);
    elements.forEach(element => {
        if (element) {
            element.addEventListener(event, handler);
        }
    });
}

// Export functions for potential external use
window.SciBOTICS = {
    initializeNavigation,
    initializeCountdown,
    initializeAnimations,
    initializeMobileMenu,
    initializeUserDropdown,
    debounce,
    throttle
};