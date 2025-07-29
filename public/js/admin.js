/**
 * GSCMS Admin-specific JavaScript
 * For administrative interface functionality
 */

(function() {
    'use strict';

    // Wait for GSCMS to be ready
    document.addEventListener('gscms:ready', function() {
        
        // Admin-specific functionality
        const Admin = {
            init: function() {
                this.initDashboard();
                this.initDataManagement();
                this.initBulkActions();
                this.initConfirmations();
                this.initAdvancedFilters();
                this.initSystemMonitoring();
                this.initQuickActions();
            },

            // Dashboard functionality
            initDashboard: function() {
                this.initStatsCards();
                this.initDashboardCharts();
                this.initRecentActivity();
                this.initSystemStatus();
            },

            initStatsCards: function() {
                const statsCards = GSCMS.utils.$$('.stat-card[data-stat]');
                
                statsCards.forEach(card => {
                    const statType = GSCMS.utils.getData(card, 'stat');
                    const valueElement = GSCMS.utils.$('.stat-card-value', card);
                    
                    if (valueElement && statType) {
                        this.animateStatCard(valueElement, statType);
                    }
                });
            },

            animateStatCard: function(element, statType) {
                const targetValue = parseInt(element.textContent) || 0;
                const duration = 2000;
                const step = targetValue / (duration / 16);
                let currentValue = 0;

                const animation = () => {
                    currentValue += step;
                    if (currentValue >= targetValue) {
                        currentValue = targetValue;
                    }
                    
                    element.textContent = GSCMS.utils.formatNumber(Math.floor(currentValue));
                    
                    if (currentValue < targetValue) {
                        requestAnimationFrame(animation);
                    }
                };

                // Start animation when card comes into view
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            setTimeout(animation, 200);
                            observer.unobserve(entry.target);
                        }
                    });
                });

                observer.observe(element.closest('.stat-card'));
            },

            initDashboardCharts: function() {
                // Initialize dashboard charts
                const chartContainers = GSCMS.utils.$$('.dashboard-chart');
                
                chartContainers.forEach(container => {
                    const chartType = GSCMS.utils.getData(container, 'chart-type');
                    const chartData = GSCMS.utils.getData(container, 'chart-data');
                    
                    if (chartData) {
                        try {
                            const data = JSON.parse(chartData);
                            this.createDashboardChart(container, chartType, data);
                        } catch (e) {
                            console.error('Invalid chart data:', e);
                        }
                    }
                });
            },

            createDashboardChart: function(container, type, data) {
                // Placeholder for chart creation
                // In a real implementation, this would use Chart.js or similar
                container.innerHTML = `
                    <div class="chart-placeholder d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-chart-${type} fa-3x text-primary mb-3"></i>
                            <h6 class="text-muted">${type.charAt(0).toUpperCase() + type.slice(1)} Chart</h6>
                            <small class="text-muted">${data.datasets?.[0]?.data?.length || 0} data points</small>
                        </div>
                    </div>
                `;
            },

            initRecentActivity: function() {
                const activityContainer = GSCMS.utils.$('.recent-activity');
                if (!activityContainer) return;

                // Auto-refresh recent activity
                setInterval(() => {
                    this.refreshRecentActivity(activityContainer);
                }, 30000); // Refresh every 30 seconds
            },

            refreshRecentActivity: function(container) {
                // Placeholder for AJAX call to refresh activity
                const activities = GSCMS.utils.$$('.activity-item', container);
                if (activities.length > 0) {
                    // Add a subtle animation to indicate refresh
                    GSCMS.utils.addClass(container, 'refreshing');
                    setTimeout(() => {
                        GSCMS.utils.removeClass(container, 'refreshing');
                    }, 500);
                }
            },

            initSystemStatus: function() {
                const statusIndicators = GSCMS.utils.$$('.system-status-indicator');
                
                statusIndicators.forEach(indicator => {
                    const status = GSCMS.utils.getData(indicator, 'status');
                    this.updateStatusIndicator(indicator, status);
                });

                // Check system status periodically
                setInterval(() => {
                    this.checkSystemStatus();
                }, 60000); // Check every minute
            },

            updateStatusIndicator: function(indicator, status) {
                const statusClasses = ['status-good', 'status-warning', 'status-error'];
                statusClasses.forEach(cls => GSCMS.utils.removeClass(indicator, cls));
                
                GSCMS.utils.addClass(indicator, `status-${status}`);
                
                const icon = GSCMS.utils.$('i', indicator);
                if (icon) {
                    const iconClasses = ['fa-check-circle', 'fa-exclamation-triangle', 'fa-times-circle'];
                    iconClasses.forEach(cls => GSCMS.utils.removeClass(icon, cls));
                    
                    const iconMap = {
                        good: 'fa-check-circle',
                        warning: 'fa-exclamation-triangle',
                        error: 'fa-times-circle'
                    };
                    
                    GSCMS.utils.addClass(icon, iconMap[status] || 'fa-question-circle');
                }
            },

            checkSystemStatus: function() {
                // Placeholder for system status check
                // In a real implementation, this would make an AJAX call
            },

            // Data management functionality
            initDataManagement: function() {
                this.initAdvancedSearch();
                this.initColumnToggle();
                this.initRowActions();
                this.initPagination();
            },

            initAdvancedSearch: function() {
                const searchForm = GSCMS.utils.$('.advanced-search-form');
                if (!searchForm) return;

                const toggleBtn = GSCMS.utils.$('.advanced-search-toggle');
                const searchFields = GSCMS.utils.$('.advanced-search-fields');
                
                if (toggleBtn && searchFields) {
                    GSCMS.utils.on(toggleBtn, 'click', () => {
                        const isExpanded = GSCMS.utils.hasClass(searchFields, 'show');
                        
                        if (isExpanded) {
                            GSCMS.utils.removeClass(searchFields, 'show');
                            toggleBtn.innerHTML = '<i class="fas fa-search-plus"></i> Advanced Search';
                        } else {
                            GSCMS.utils.addClass(searchFields, 'show');
                            toggleBtn.innerHTML = '<i class="fas fa-search-minus"></i> Hide Advanced';
                        }
                    });
                }

                // Auto-submit form on field changes with debouncing
                const searchInputs = GSCMS.utils.$$('input, select', searchForm);
                searchInputs.forEach(input => {
                    const submitSearch = GSCMS.utils.debounce(() => {
                        this.performAdvancedSearch(searchForm);
                    }, 500);

                    GSCMS.utils.on(input, 'input', submitSearch);
                    GSCMS.utils.on(input, 'change', submitSearch);
                });
            },

            performAdvancedSearch: function(form) {
                // Placeholder for advanced search functionality
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                
                // Show loading state
                const resultsContainer = GSCMS.utils.$('.search-results');
                if (resultsContainer) {
                    GSCMS.utils.addClass(resultsContainer, 'loading');
                }
                
                // In a real implementation, this would make an AJAX call
                setTimeout(() => {
                    if (resultsContainer) {
                        GSCMS.utils.removeClass(resultsContainer, 'loading');
                    }
                }, 1000);
            },

            initColumnToggle: function() {
                const columnToggle = GSCMS.utils.$('.column-toggle');
                if (!columnToggle) return;

                const checkboxes = GSCMS.utils.$$('input[type="checkbox"]', columnToggle);
                checkboxes.forEach(checkbox => {
                    GSCMS.utils.on(checkbox, 'change', () => {
                        this.toggleTableColumn(checkbox);
                    });
                });
            },

            toggleTableColumn: function(checkbox) {
                const column = GSCMS.utils.getData(checkbox, 'column');
                const table = GSCMS.utils.$('.data-table');
                
                if (!table || !column) return;

                const cells = GSCMS.utils.$$(`[data-column="${column}"]`, table);
                const headerCell = GSCMS.utils.$(`th[data-column="${column}"]`, table);
                
                const isVisible = checkbox.checked;
                
                cells.forEach(cell => {
                    cell.style.display = isVisible ? '' : 'none';
                });
                
                if (headerCell) {
                    headerCell.style.display = isVisible ? '' : 'none';
                }
            },

            initRowActions: function() {
                // Initialize quick edit
                const editBtns = GSCMS.utils.$$('.quick-edit-btn');
                editBtns.forEach(btn => {
                    GSCMS.utils.on(btn, 'click', (e) => {
                        e.preventDefault();
                        const row = btn.closest('tr');
                        this.enableQuickEdit(row);
                    });
                });

                // Initialize duplicate actions
                const duplicateBtns = GSCMS.utils.$$('.duplicate-btn');
                duplicateBtns.forEach(btn => {
                    GSCMS.utils.on(btn, 'click', (e) => {
                        e.preventDefault();
                        this.duplicateRow(btn);
                    });
                });
            },

            enableQuickEdit: function(row) {
                const cells = GSCMS.utils.$$('td[data-editable]', row);
                
                cells.forEach(cell => {
                    const currentValue = cell.textContent.trim();
                    const fieldType = GSCMS.utils.getData(cell, 'editable');
                    
                    let input;
                    if (fieldType === 'select') {
                        const options = GSCMS.utils.getData(cell, 'options');
                        input = this.createSelectInput(currentValue, options);
                    } else {
                        input = this.createTextInput(currentValue, fieldType);
                    }
                    
                    input.className = 'form-control form-control-sm';
                    cell.innerHTML = '';
                    cell.appendChild(input);
                    input.focus();
                    
                    // Save on blur or Enter
                    GSCMS.utils.on(input, 'blur', () => this.saveQuickEdit(cell, input));
                    GSCMS.utils.on(input, 'keydown', (e) => {
                        if (e.key === 'Enter') {
                            input.blur();
                        } else if (e.key === 'Escape') {
                            this.cancelQuickEdit(cell, currentValue);
                        }
                    });
                });
                
                // Add save/cancel buttons
                this.addQuickEditButtons(row);
            },

            createTextInput: function(value, type) {
                const input = document.createElement('input');
                input.type = type === 'email' ? 'email' : 'text';
                input.value = value;
                return input;
            },

            createSelectInput: function(value, optionsData) {
                const select = document.createElement('select');
                const options = JSON.parse(optionsData || '[]');
                
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    optionElement.selected = option.value === value;
                    select.appendChild(optionElement);
                });
                
                return select;
            },

            saveQuickEdit: function(cell, input) {
                const newValue = input.value;
                const originalValue = GSCMS.utils.getData(cell, 'original-value') || cell.textContent;
                
                cell.textContent = newValue;
                
                // In a real implementation, this would make an AJAX call to save
                if (newValue !== originalValue) {
                    GSCMS.utils.addClass(cell, 'edited');
                    this.showQuickEditSuccess(cell);
                }
                
                this.removeQuickEditButtons(cell.closest('tr'));
            },

            cancelQuickEdit: function(cell, originalValue) {
                cell.textContent = originalValue;
                this.removeQuickEditButtons(cell.closest('tr'));
            },

            addQuickEditButtons: function(row) {
                const actionsCell = GSCMS.utils.$('.table-actions', row);
                if (!actionsCell) return;

                const buttonsContainer = document.createElement('div');
                buttonsContainer.className = 'quick-edit-buttons';
                buttonsContainer.innerHTML = `
                    <button class="btn btn-sm btn-success save-edit-btn" title="Save">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-sm btn-secondary cancel-edit-btn" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                actionsCell.appendChild(buttonsContainer);
                
                // Event handlers
                const saveBtn = GSCMS.utils.$('.save-edit-btn', buttonsContainer);
                const cancelBtn = GSCMS.utils.$('.cancel-edit-btn', buttonsContainer);
                
                GSCMS.utils.on(saveBtn, 'click', () => this.saveAllEdits(row));
                GSCMS.utils.on(cancelBtn, 'click', () => this.cancelAllEdits(row));
            },

            removeQuickEditButtons: function(row) {
                const buttons = GSCMS.utils.$('.quick-edit-buttons', row);
                if (buttons) {
                    buttons.remove();
                }
            },

            saveAllEdits: function(row) {
                const inputs = GSCMS.utils.$$('input, select', row);
                inputs.forEach(input => {
                    const cell = input.closest('td');
                    this.saveQuickEdit(cell, input);
                });
            },

            cancelAllEdits: function(row) {
                const editedCells = GSCMS.utils.$$('td[data-editable]', row);
                editedCells.forEach(cell => {
                    const originalValue = GSCMS.utils.getData(cell, 'original-value') || '';
                    this.cancelQuickEdit(cell, originalValue);
                });
            },

            showQuickEditSuccess: function(cell) {
                const indicator = document.createElement('i');
                indicator.className = 'fas fa-check text-success edit-indicator';
                cell.appendChild(indicator);
                
                setTimeout(() => {
                    indicator.remove();
                    GSCMS.utils.removeClass(cell, 'edited');
                }, 2000);
            },

            duplicateRow: function(btn) {
                const id = GSCMS.utils.getData(btn, 'id');
                const type = GSCMS.utils.getData(btn, 'type');
                
                if (confirm(`Are you sure you want to duplicate this ${type}?`)) {
                    // In a real implementation, this would make an AJAX call
                    GSCMS.toast.success(`${type} duplicated successfully`);
                    
                    // Reload the page or update the table
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            },

            // Bulk actions functionality
            initBulkActions: function() {
                const bulkActionForm = GSCMS.utils.$('.bulk-actions-form');
                if (!bulkActionForm) return;

                GSCMS.utils.on(bulkActionForm, 'submit', (e) => {
                    e.preventDefault();
                    this.performBulkAction(bulkActionForm);
                });
            },

            performBulkAction: function(form) {
                const action = GSCMS.utils.$('select[name="bulk_action"]', form).value;
                const selectedIds = Array.from(GSCMS.utils.$$('input[name="selected_ids[]"]:checked', form))
                    .map(checkbox => checkbox.value);

                if (!action || selectedIds.length === 0) {
                    GSCMS.toast.warning('Please select an action and at least one item');
                    return;
                }

                const confirmMessage = this.getBulkActionConfirmMessage(action, selectedIds.length);
                
                if (confirm(confirmMessage)) {
                    this.executeBulkAction(action, selectedIds);
                }
            },

            getBulkActionConfirmMessage: function(action, count) {
                const messages = {
                    delete: `Are you sure you want to delete ${count} item(s)? This action cannot be undone.`,
                    activate: `Are you sure you want to activate ${count} item(s)?`,
                    deactivate: `Are you sure you want to deactivate ${count} item(s)?`,
                    export: `Export ${count} item(s) to CSV?`
                };
                
                return messages[action] || `Perform ${action} on ${count} item(s)?`;
            },

            executeBulkAction: function(action, ids) {
                // Show loading state
                const loader = GSCMS.loading.show();
                
                // In a real implementation, this would make an AJAX call
                setTimeout(() => {
                    GSCMS.loading.hide(loader);
                    GSCMS.toast.success(`Bulk ${action} completed successfully`);
                    
                    // Reload the page or update the table
                    window.location.reload();
                }, 2000);
            },

            // Confirmation dialogs
            initConfirmations: function() {
                const deleteButtons = GSCMS.utils.$$('.delete-btn, [data-action="delete"]');
                
                deleteButtons.forEach(btn => {
                    GSCMS.utils.on(btn, 'click', (e) => {
                        e.preventDefault();
                        
                        const message = GSCMS.utils.getData(btn, 'message') || 
                                      'Are you sure you want to delete this item? This action cannot be undone.';
                        
                        this.showConfirmDialog(message, () => {
                            this.executeDelete(btn);
                        });
                    });
                });
            },

            showConfirmDialog: function(message, callback) {
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.display = 'flex';
                modal.innerHTML = `
                    <div class="modal-backdrop"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Action</h5>
                            <button class="modal-close" aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
                                <p class="mb-0">${message}</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary cancel-btn">Cancel</button>
                            <button class="btn btn-danger confirm-btn">Confirm</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Event handlers
                const closeBtn = GSCMS.utils.$('.modal-close', modal);
                const cancelBtn = GSCMS.utils.$('.cancel-btn', modal);
                const confirmBtn = GSCMS.utils.$('.confirm-btn', modal);
                const backdrop = GSCMS.utils.$('.modal-backdrop', modal);
                
                const closeModal = () => {
                    modal.remove();
                };
                
                const confirmAction = () => {
                    closeModal();
                    callback();
                };
                
                GSCMS.utils.on(closeBtn, 'click', closeModal);
                GSCMS.utils.on(cancelBtn, 'click', closeModal);
                GSCMS.utils.on(confirmBtn, 'click', confirmAction);
                GSCMS.utils.on(backdrop, 'click', closeModal);
                
                // Focus the confirm button
                confirmBtn.focus();
            },

            executeDelete: function(btn) {
                // Show loading state
                const loader = GSCMS.loading.show();
                
                // In a real implementation, this would make an AJAX call
                setTimeout(() => {
                    GSCMS.loading.hide(loader);
                    GSCMS.toast.success('Item deleted successfully');
                    
                    // Remove the row or reload the page
                    const row = btn.closest('tr');
                    if (row) {
                        GSCMS.utils.animate(row, [
                            { opacity: 1, transform: 'scale(1)' },
                            { opacity: 0, transform: 'scale(0.8)' }
                        ], { duration: 300 }).onfinish = () => {
                            row.remove();
                        };
                    } else {
                        window.location.reload();
                    }
                }, 1000);
            },

            // Advanced filters
            initAdvancedFilters: function() {
                const filterForm = GSCMS.utils.$('.advanced-filters-form');
                if (!filterForm) return;

                // Date range picker initialization
                const dateRangeInputs = GSCMS.utils.$$('input[data-daterange]', filterForm);
                dateRangeInputs.forEach(input => {
                    this.initDateRangePicker(input);
                });

                // Multi-select initialization
                const multiSelects = GSCMS.utils.$$('select[multiple]', filterForm);
                multiSelects.forEach(select => {
                    this.enhanceMultiSelect(select);
                });
            },

            initDateRangePicker: function(input) {
                // Placeholder for date range picker
                // In a real implementation, this would integrate with a date picker library
                input.placeholder = 'Select date range (YYYY-MM-DD to YYYY-MM-DD)';
                
                GSCMS.utils.on(input, 'focus', () => {
                    // Show date picker
                });
            },

            enhanceMultiSelect: function(select) {
                // Create custom multi-select interface
                const wrapper = document.createElement('div');
                wrapper.className = 'multi-select-wrapper';
                
                const display = document.createElement('div');
                display.className = 'multi-select-display';
                display.innerHTML = '<span class="placeholder">Select options...</span>';
                
                const dropdown = document.createElement('div');
                dropdown.className = 'multi-select-dropdown';
                
                // Move select and create custom interface
                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(display);
                wrapper.appendChild(dropdown);
                wrapper.appendChild(select);
                select.style.display = 'none';
                
                // Populate dropdown
                this.populateMultiSelectDropdown(select, dropdown, display);
                
                // Toggle dropdown
                GSCMS.utils.on(display, 'click', () => {
                    GSCMS.utils.toggleClass(dropdown, 'show');
                });
                
                // Close on outside click
                GSCMS.utils.on(document, 'click', (e) => {
                    if (!wrapper.contains(e.target)) {
                        GSCMS.utils.removeClass(dropdown, 'show');
                    }
                });
            },

            populateMultiSelectDropdown: function(select, dropdown, display) {
                const options = GSCMS.utils.$$('option', select);
                
                options.forEach(option => {
                    const item = document.createElement('div');
                    item.className = 'multi-select-item';
                    item.innerHTML = `
                        <input type="checkbox" id="ms_${option.value}" ${option.selected ? 'checked' : ''}>
                        <label for="ms_${option.value}">${option.textContent}</label>
                    `;
                    
                    dropdown.appendChild(item);
                    
                    const checkbox = GSCMS.utils.$('input', item);
                    GSCMS.utils.on(checkbox, 'change', () => {
                        option.selected = checkbox.checked;
                        this.updateMultiSelectDisplay(select, display);
                    });
                });
                
                this.updateMultiSelectDisplay(select, display);
            },

            updateMultiSelectDisplay: function(select, display) {
                const selectedOptions = GSCMS.utils.$$('option:checked', select);
                
                if (selectedOptions.length === 0) {
                    display.innerHTML = '<span class="placeholder">Select options...</span>';
                } else if (selectedOptions.length === 1) {
                    display.innerHTML = `<span>${selectedOptions[0].textContent}</span>`;
                } else {
                    display.innerHTML = `<span>${selectedOptions.length} options selected</span>`;
                }
            },

            // System monitoring
            initSystemMonitoring: function() {
                if (window.location.pathname.includes('/admin/system')) {
                    this.startSystemMonitoring();
                }
            },

            startSystemMonitoring: function() {
                // Monitor system resources
                setInterval(() => {
                    this.updateSystemMetrics();
                }, 5000); // Update every 5 seconds
            },

            updateSystemMetrics: function() {
                // In a real implementation, this would fetch actual system metrics
                const metrics = {
                    cpu: Math.random() * 100,
                    memory: Math.random() * 100,
                    disk: Math.random() * 100,
                    network: Math.random() * 1000
                };
                
                Object.keys(metrics).forEach(metric => {
                    const element = GSCMS.utils.$(`[data-metric="${metric}"]`);
                    if (element) {
                        this.updateMetricDisplay(element, metrics[metric], metric);
                    }
                });
            },

            updateMetricDisplay: function(element, value, type) {
                const valueElement = GSCMS.utils.$('.metric-value', element);
                const progressBar = GSCMS.utils.$('.progress-bar', element);
                
                if (valueElement) {
                    const displayValue = type === 'network' ? 
                        `${value.toFixed(1)} KB/s` : 
                        `${value.toFixed(1)}%`;
                    valueElement.textContent = displayValue;
                }
                
                if (progressBar) {
                    const percentage = type === 'network' ? (value / 1000) * 100 : value;
                    progressBar.style.width = `${Math.min(percentage, 100)}%`;
                    
                    // Update color based on threshold
                    const progressClasses = ['bg-success', 'bg-warning', 'bg-danger'];
                    progressClasses.forEach(cls => GSCMS.utils.removeClass(progressBar, cls));
                    
                    if (percentage < 60) {
                        GSCMS.utils.addClass(progressBar, 'bg-success');
                    } else if (percentage < 80) {
                        GSCMS.utils.addClass(progressBar, 'bg-warning');
                    } else {
                        GSCMS.utils.addClass(progressBar, 'bg-danger');
                    }
                }
            },

            // Quick actions
            initQuickActions: function() {
                const quickActionBtns = GSCMS.utils.$$('.quick-action-btn');
                
                quickActionBtns.forEach(btn => {
                    const action = GSCMS.utils.getData(btn, 'action');
                    
                    GSCMS.utils.on(btn, 'click', () => {
                        this.executeQuickAction(action, btn);
                    });
                });
            },

            executeQuickAction: function(action, btn) {
                const actions = {
                    backup: () => this.performBackup(),
                    clear_cache: () => this.clearCache(),
                    send_notifications: () => this.sendNotifications(),
                    generate_report: () => this.generateReport()
                };
                
                if (actions[action]) {
                    actions[action]();
                } else {
                    console.warn(`Unknown quick action: ${action}`);
                }
            },

            performBackup: function() {
                GSCMS.toast.info('Starting backup process...');
                
                // Simulate backup process
                setTimeout(() => {
                    GSCMS.toast.success('Backup completed successfully');
                }, 3000);
            },

            clearCache: function() {
                GSCMS.toast.info('Clearing cache...');
                
                setTimeout(() => {
                    GSCMS.toast.success('Cache cleared successfully');
                }, 1000);
            },

            sendNotifications: function() {
                GSCMS.toast.info('Sending notifications...');
                
                setTimeout(() => {
                    GSCMS.toast.success('Notifications sent successfully');
                }, 2000);
            },

            generateReport: function() {
                GSCMS.toast.info('Generating report...');
                
                setTimeout(() => {
                    GSCMS.toast.success('Report generated and downloaded');
                }, 4000);
            }
        };

        // Initialize admin functionality
        Admin.init();

        // Expose Admin globally for debugging
        window.Admin = Admin;
    });

})();