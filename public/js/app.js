/**
 * GSCMS App-specific JavaScript
 * For authenticated user interface functionality
 */

(function() {
    'use strict';

    // Wait for GSCMS to be ready
    document.addEventListener('gscms:ready', function() {
        
        // App-specific functionality
        const App = {
            init: function() {
                this.initSidebar();
                this.initSearch();
                this.initDataTables();
                this.initCharts();
                this.initFileUpload();
                this.initNotifications();
            },

            // Sidebar functionality
            initSidebar: function() {
                const sidebar = GSCMS.utils.$('.app-sidebar');
                const toggle = GSCMS.utils.$('.mobile-sidebar-toggle');
                const overlay = GSCMS.utils.$('.sidebar-overlay');
                
                if (!sidebar || !toggle || !overlay) return;

                // Toggle sidebar
                GSCMS.utils.on(toggle, 'click', () => {
                    GSCMS.utils.toggleClass(sidebar, 'active');
                    GSCMS.utils.toggleClass(overlay, 'active');
                    GSCMS.utils.toggleClass(document.body, 'sidebar-open');
                });

                // Close sidebar on overlay click
                GSCMS.utils.on(overlay, 'click', () => {
                    GSCMS.utils.removeClass(sidebar, 'active');
                    GSCMS.utils.removeClass(overlay, 'active');
                    GSCMS.utils.removeClass(document.body, 'sidebar-open');
                });

                // Close sidebar on window resize
                GSCMS.utils.on(window, 'resize', GSCMS.utils.debounce(() => {
                    if (window.innerWidth > 1024) {
                        GSCMS.utils.removeClass(sidebar, 'active');
                        GSCMS.utils.removeClass(overlay, 'active');
                        GSCMS.utils.removeClass(document.body, 'sidebar-open');
                    }
                }, 250));

                // Active navigation highlighting
                this.highlightActiveNav();
            },

            highlightActiveNav: function() {
                const currentPath = window.location.pathname;
                const navLinks = GSCMS.utils.$$('.nav-link');
                
                navLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && currentPath.startsWith(href) && href !== '/') {
                        GSCMS.utils.addClass(link, 'active');
                        
                        // Expand parent if it's in a collapsible menu
                        const parent = link.closest('.nav-collapse');
                        if (parent) {
                            GSCMS.utils.addClass(parent, 'show');
                        }
                    }
                });
            },

            // Search functionality
            initSearch: function() {
                const searchInputs = GSCMS.utils.$$('[data-search]');
                
                searchInputs.forEach(input => {
                    const target = GSCMS.utils.getData(input, 'search');
                    const targetElements = GSCMS.utils.$$(target);
                    
                    if (!targetElements.length) return;

                    const performSearch = GSCMS.utils.debounce((query) => {
                        this.filterElements(targetElements, query);
                    }, GSCMS.config.debounceDelay);

                    GSCMS.utils.on(input, 'input', (e) => {
                        performSearch(e.target.value.toLowerCase());
                    });
                });
            },

            filterElements: function(elements, query) {
                elements.forEach(element => {
                    const text = element.textContent.toLowerCase();
                    const matches = !query || text.includes(query);
                    
                    element.style.display = matches ? '' : 'none';
                    
                    // Update table row highlighting
                    if (element.tagName === 'TR') {
                        if (matches && query) {
                            GSCMS.utils.addClass(element, 'search-highlight');
                        } else {
                            GSCMS.utils.removeClass(element, 'search-highlight');
                        }
                    }
                });

                // Show "no results" message if needed
                this.toggleNoResults(elements, query);
            },

            toggleNoResults: function(elements, query) {
                const visibleElements = Array.from(elements).filter(el => 
                    el.style.display !== 'none'
                );
                
                const container = elements[0]?.parentNode;
                if (!container) return;

                let noResultsMsg = GSCMS.utils.$('.no-results', container);
                
                if (visibleElements.length === 0 && query) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results text-center p-4 text-muted';
                        noResultsMsg.innerHTML = `
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>No results found for "${query}"</p>
                        `;
                        container.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            },

            // Data tables enhancement
            initDataTables: function() {
                const tables = GSCMS.utils.$$('.data-table');
                
                tables.forEach(table => {
                    this.enhanceTable(table);
                });
            },

            enhanceTable: function(table) {
                // Add sorting functionality
                const headers = GSCMS.utils.$$('th[data-sort]', table);
                headers.forEach(header => {
                    GSCMS.utils.addClass(header, 'sortable');
                    GSCMS.utils.on(header, 'click', () => {
                        this.sortTable(table, header);
                    });
                });

                // Add row selection
                const checkboxes = GSCMS.utils.$$('input[type="checkbox"]', table);
                if (checkboxes.length > 0) {
                    this.initTableSelection(table, checkboxes);
                }

                // Add row hover effects
                const rows = GSCMS.utils.$$('tbody tr', table);
                rows.forEach(row => {
                    GSCMS.utils.on(row, 'mouseenter', () => {
                        GSCMS.utils.addClass(row, 'table-row-hover');
                    });
                    GSCMS.utils.on(row, 'mouseleave', () => {
                        GSCMS.utils.removeClass(row, 'table-row-hover');
                    });
                });
            },

            sortTable: function(table, header) {
                const column = GSCMS.utils.getData(header, 'sort');
                const tbody = GSCMS.utils.$('tbody', table);
                const rows = Array.from(GSCMS.utils.$$('tr', tbody));
                
                // Determine sort direction
                const isAscending = !GSCMS.utils.hasClass(header, 'sort-desc');
                
                // Clear all sort classes
                GSCMS.utils.$$('th', table).forEach(th => {
                    GSCMS.utils.removeClass(th, 'sort-asc sort-desc');
                });
                
                // Add sort class to current header
                GSCMS.utils.addClass(header, isAscending ? 'sort-asc' : 'sort-desc');
                
                // Sort rows
                rows.sort((a, b) => {
                    const aVal = this.getCellValue(a, column);
                    const bVal = this.getCellValue(b, column);
                    
                    return isAscending ? 
                        (aVal > bVal ? 1 : -1) : 
                        (aVal < bVal ? 1 : -1);
                });
                
                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            },

            getCellValue: function(row, column) {
                const cell = GSCMS.utils.$(`[data-column="${column}"]`, row) || 
                            GSCMS.utils.$$('td', row)[parseInt(column)] ||
                            GSCMS.utils.$('td', row);
                
                if (!cell) return '';
                
                const value = cell.textContent.trim();
                
                // Try to parse as number or date
                if (!isNaN(value)) return parseFloat(value);
                if (Date.parse(value)) return new Date(value);
                
                return value.toLowerCase();
            },

            initTableSelection: function(table, checkboxes) {
                const selectAll = GSCMS.utils.$('thead input[type="checkbox"]', table);
                
                if (selectAll) {
                    GSCMS.utils.on(selectAll, 'change', () => {
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = selectAll.checked;
                            this.toggleRowSelection(checkbox);
                        });
                        this.updateBulkActions(table);
                    });
                }
                
                checkboxes.forEach(checkbox => {
                    GSCMS.utils.on(checkbox, 'change', () => {
                        this.toggleRowSelection(checkbox);
                        this.updateSelectAll(table);
                        this.updateBulkActions(table);
                    });
                });
            },

            toggleRowSelection: function(checkbox) {
                const row = checkbox.closest('tr');
                if (checkbox.checked) {
                    GSCMS.utils.addClass(row, 'selected');
                } else {
                    GSCMS.utils.removeClass(row, 'selected');
                }
            },

            updateSelectAll: function(table) {
                const selectAll = GSCMS.utils.$('thead input[type="checkbox"]', table);
                const checkboxes = GSCMS.utils.$$('tbody input[type="checkbox"]', table);
                const checkedCount = GSCMS.utils.$$('tbody input[type="checkbox"]:checked', table).length;
                
                if (selectAll) {
                    selectAll.checked = checkedCount === checkboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                }
            },

            updateBulkActions: function(table) {
                const checkedCount = GSCMS.utils.$$('tbody input[type="checkbox"]:checked', table).length;
                const bulkActions = GSCMS.utils.$('.bulk-actions');
                
                if (bulkActions) {
                    if (checkedCount > 0) {
                        GSCMS.utils.removeClass(bulkActions, 'd-none');
                        const countSpan = GSCMS.utils.$('.selected-count', bulkActions);
                        if (countSpan) {
                            countSpan.textContent = checkedCount;
                        }
                    } else {
                        GSCMS.utils.addClass(bulkActions, 'd-none');
                    }
                }
            },

            // Charts initialization
            initCharts: function() {
                const chartElements = GSCMS.utils.$$('[data-chart]');
                
                chartElements.forEach(element => {
                    const chartType = GSCMS.utils.getData(element, 'chart');
                    const chartData = GSCMS.utils.getData(element, 'chart-data');
                    
                    if (chartData) {
                        try {
                            const data = JSON.parse(chartData);
                            this.createChart(element, chartType, data);
                        } catch (e) {
                            console.error('Invalid chart data:', e);
                        }
                    }
                });
            },

            createChart: function(element, type, data) {
                // This would integrate with a charting library like Chart.js
                // For now, we'll create a simple placeholder
                element.innerHTML = `
                    <div class="chart-placeholder">
                        <i class="fas fa-chart-${type} fa-3x text-muted"></i>
                        <p class="text-muted mt-2">Chart: ${type}</p>
                    </div>
                `;
            },

            // File upload enhancement
            initFileUpload: function() {
                const fileInputs = GSCMS.utils.$$('input[type="file"]');
                
                fileInputs.forEach(input => {
                    this.enhanceFileInput(input);
                });
            },

            enhanceFileInput: function(input) {
                const wrapper = document.createElement('div');
                wrapper.className = 'file-upload-wrapper';
                
                const dropZone = document.createElement('div');
                dropZone.className = 'file-drop-zone';
                dropZone.innerHTML = `
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                    <p class="mt-2 mb-0">Drop files here or click to browse</p>
                    <small class="text-muted">Supported formats: ${this.getAcceptedFormats(input)}</small>
                `;
                
                // Replace input with enhanced version
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(dropZone);
                wrapper.appendChild(input);
                input.style.display = 'none';
                
                // Event handlers
                GSCMS.utils.on(dropZone, 'click', () => input.click());
                GSCMS.utils.on(dropZone, 'dragover', (e) => {
                    e.preventDefault();
                    GSCMS.utils.addClass(dropZone, 'drag-over');
                });
                GSCMS.utils.on(dropZone, 'dragleave', () => {
                    GSCMS.utils.removeClass(dropZone, 'drag-over');
                });
                GSCMS.utils.on(dropZone, 'drop', (e) => {
                    e.preventDefault();
                    GSCMS.utils.removeClass(dropZone, 'drag-over');
                    this.handleFileSelect(input, e.dataTransfer.files);
                });
                GSCMS.utils.on(input, 'change', () => {
                    this.handleFileSelect(input, input.files);
                });
            },

            getAcceptedFormats: function(input) {
                const accept = input.getAttribute('accept');
                return accept ? accept.replace(/,/g, ', ') : 'All files';
            },

            handleFileSelect: function(input, files) {
                const wrapper = input.closest('.file-upload-wrapper');
                const dropZone = GSCMS.utils.$('.file-drop-zone', wrapper);
                
                if (files.length > 0) {
                    const fileList = Array.from(files).map(file => 
                        `<li>${file.name} (${this.formatFileSize(file.size)})</li>`
                    ).join('');
                    
                    dropZone.innerHTML = `
                        <i class="fas fa-file fa-2x text-success"></i>
                        <p class="mt-2 mb-2 text-success">${files.length} file(s) selected</p>
                        <ul class="list-unstyled small text-muted">${fileList}</ul>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2">
                            Change files
                        </button>
                    `;
                    
                    const changeBtn = GSCMS.utils.$('button', dropZone);
                    GSCMS.utils.on(changeBtn, 'click', () => input.click());
                }
            },

            formatFileSize: function(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            },

            // Notifications
            initNotifications: function() {
                // Check for new notifications periodically
                if (window.location.pathname.includes('/dashboard')) {
                    this.checkNotifications();
                    setInterval(() => this.checkNotifications(), 60000); // Every minute
                }
            },

            checkNotifications: function() {
                // This would make an AJAX call to check for new notifications
                // For now, we'll simulate it
                const notificationBadge = GSCMS.utils.$('.notification-badge');
                if (notificationBadge) {
                    const count = Math.floor(Math.random() * 5);
                    if (count > 0) {
                        notificationBadge.textContent = count;
                        GSCMS.utils.removeClass(notificationBadge, 'd-none');
                    } else {
                        GSCMS.utils.addClass(notificationBadge, 'd-none');
                    }
                }
            }
        };

        // Initialize app functionality
        App.init();

        // Expose App globally for debugging
        window.App = App;
    });

})();