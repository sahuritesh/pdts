/**
 * GridView Utility
 * 
 * A reusable utility for managing DataTables with filter restoration,
 * date range pickers, and form state management.
 * 
 * @class GridViewUtility
 */
(function($) {
    'use strict';

    /**
     * GridView Utility Class
     */
    class GridViewUtility {
        /**
         * Initialize GridView Utility
         * @param {Object} config - Configuration object
         * @param {string} config.tableId - DataTable ID selector (default: '#ucList')
         * @param {string} config.formId - Search form ID selector (default: 'searchform')
         * @param {Object} config.gridConfig - Grid configuration from backend
         */
        constructor(config) {
            this.config = {
                tableId: config.tableId || '#ucList',
                formId: config.formId || 'searchform',
                gridConfig: config.gridConfig || {},
                filterStorageKey: 'ucFilterForm_' + window.location.pathname,
                tableStateKey: 'DataTables_' + (config.tableId || 'ucList'),
                filterExpiry: 20 * 60 * 1000 // 20 minutes
            };

            this.dateRangeFields = [];
            this.dateRangeInitPromises = [];
            this.dateRangeFilterData = {};

            this.init();
        }

        /**
         * Initialize the utility
         */
        init() {
            this.loadSavedFilterData();
            this.initializeSelect2();
            this.initializeDateRangePickers();
            this.restoreFilters();
        }

        /**
         * Load saved filter data from localStorage
         */
        loadSavedFilterData() {
            const savedData = localStorage.getItem(this.config.filterStorageKey);
            if (savedData) {
                try {
                    const parsed = JSON.parse(savedData);
                    parsed.values.forEach((field) => {
                        if (field.value) {
                            this.dateRangeFilterData[field.name] = field.value;
                        }
                    });
                } catch(e) {
                    console.warn('Error parsing saved filter data:', e);
                }
            }
        }

        /**
         * Initialize Select2 for select filters
         */
        initializeSelect2() {
            if (!this.config.gridConfig.filters) return;

            const select2Selectors = [];
            this.config.gridConfig.filters.forEach((filter) => {
                if (filter.type === 'select' && filter.select2 !== false) {
                    select2Selectors.push('#' + filter.name);
                }
            });

            if (select2Selectors.length > 0) {
                $(select2Selectors.join(',')).select2();
            }
        }

        /**
         * Initialize date range pickers
         */
        initializeDateRangePickers() {
            if (!this.config.gridConfig.filters) return;

            this.config.gridConfig.filters.forEach((filter) => {
                if (filter.type === 'daterange') {
                    this.dateRangeFields.push(filter.name);
                    const initPromise = this.createDateRangePickerPromise(filter.name);
                    this.dateRangeInitPromises.push(initPromise);
                } else if (filter.type === 'date' && filter.datepicker !== false) {
                    // Initialize single date pickers
                    this.initializeDatePicker(filter.name);
                }
            });
        }

        /**
         * Initialize a single date picker
         * @param {string} fieldName - Field name
         */
        initializeDatePicker(fieldName) {
            if (typeof $.fn.datepicker === 'undefined') {
                console.warn('Date picker library not loaded');
                return;
            }

            const $input = $(`input[name="${fieldName}"]`);
            if ($input.length === 0 || $input.data('datepicker')) {
                return;
            }

            $input.datepicker({
                todayBtn: 1,
                autoclose: true,
                format: "dd-mm-yyyy",
                orientation: "bottom auto"
            });
        }

        /**
         * Create a promise for date range picker initialization
         * @param {string} fieldName - Field name
         * @returns {Promise}
         */
        createDateRangePickerPromise(fieldName) {
            return new Promise((resolve) => {
                setTimeout(() => {
                    this.initializeDateRangePicker(fieldName);
                    
                    // Restore saved value if exists
                    if (this.dateRangeFilterData[fieldName]) {
                        setTimeout(() => {
                            this.restoreDateRangePickerValue(fieldName);
                            resolve();
                        }, 100);
                    } else {
                        setTimeout(() => resolve(), 50);
                    }
                }, 500);
            });
        }

        /**
         * Initialize a single date range picker
         * @param {string} fieldName - Field name
         */
        initializeDateRangePicker(fieldName) {
            if (typeof moment === 'undefined' || typeof $.fn.daterangepicker === 'undefined') {
                console.warn('Date range picker libraries not loaded');
                return;
            }

            const $input = $(`input[name="${fieldName}"]`);
            if ($input.length === 0 || $input.data('daterangepicker')) {
                return;
            }

            const mindate = new Date(new Date().getFullYear() - 10, 0, 1);
            const maxdate = new Date();

            $input.daterangepicker({
                opens: 'left',
                minDate: mindate,
                maxDate: maxdate,
                autoUpdateInput: false,
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: ' to ',
                    cancelLabel: 'Clear',
                    applyLabel: 'Apply'
                }
            }, (start, end) => {
                $input.val(start.format('DD-MM-YYYY') + ' to ' + end.format('DD-MM-YYYY'));
                $input.trigger('change');
            });

            $input.on('cancel.daterangepicker', function() {
                $(this).val('').trigger('change');
            });
        }

        /**
         * Restore date range picker value
         * @param {string} fieldName - Field name
         */
        restoreDateRangePickerValue(fieldName) {
            const $el = $(`input[name="${fieldName}"]`);
            if (!$el.length || !$el.data('daterangepicker')) return;

            const value = this.dateRangeFilterData[fieldName];
            if (!value) return;

            const dateParts = value.split(' to ');
            if (dateParts.length === 2) {
                const start = moment(dateParts[0].trim(), 'DD-MM-YYYY');
                const end = moment(dateParts[1].trim(), 'DD-MM-YYYY');
                if (start.isValid() && end.isValid()) {
                    $el.data('daterangepicker').setStartDate(start);
                    $el.data('daterangepicker').setEndDate(end);
                    $el.val(value);
                }
            }
        }

        /**
         * Restore filters from localStorage
         * @param {Function} callback - Callback function
         * @param {Array} excludeFields - Fields to exclude from restoration
         */
        restoreFilters(callback, excludeFields = []) {
            const savedData = localStorage.getItem(this.config.filterStorageKey);

            if (!savedData) {
                if (callback) callback();
                return;
            }

            try {
                const parsed = JSON.parse(savedData);
                const now = new Date().getTime();
                const age = now - parsed.timestamp;

                // Check if data is expired
                if (age > this.config.filterExpiry) {
                    localStorage.removeItem(this.config.filterStorageKey);
                    if (callback) callback();
                    return;
                }

                const select2Promises = [];
                let hasSelect2Fields = false;

                parsed.values.forEach((field) => {
                    if (excludeFields.indexOf(field.name) !== -1) return;

                    const $el = $(`[name="${field.name}"]`);
                    if (!$el.length) return;

                    if ($el.hasClass('select2-hidden-accessible')) {
                        hasSelect2Fields = true;
                        $el.val(field.value).trigger('change');
                        select2Promises.push(new Promise((resolve) => {
                            setTimeout(() => resolve(), 150);
                        }));
                    } else if ($el.hasClass('flatpickr-input')) {
                        if ($el[0]._flatpickr) {
                            $el[0]._flatpickr.setDate(field.value);
                        }
                    } else if ($el.data('daterangepicker')) {
                        this.restoreDateRangePickerValue(field.name);
                    } else {
                        $el.val(field.value);
                    }
                });

                // Wait for Select2 changes to complete
                if (hasSelect2Fields && select2Promises.length > 0) {
                    Promise.all(select2Promises).then(() => {
                        if (callback) callback();
                    });
                } else {
                    setTimeout(() => {
                        if (callback) callback();
                    }, 50);
                }
            } catch(e) {
                console.error('Error restoring filters:', e);
                if (callback) callback();
            }
        }

        /**
         * Save filters to localStorage and refresh DataTable
         */
        saveFiltersAndFetch() {
            const formData = $(`#${this.config.formId}`).serializeArray();
            const filterData = {
                values: formData,
                timestamp: new Date().getTime()
            };

            localStorage.setItem(this.config.filterStorageKey, JSON.stringify(filterData));

            const table = $(this.config.tableId).DataTable();
            if (table.length) {
                table.state.clear();
                table.page(0).draw(false);
            }

            this.initializeDataTable();
        }

        /**
         * Initialize DataTable
         */
        initializeDataTable() {
            const route = this.getCurrentRoute();
            const dataurl = this.getDataUrl();
            
            if (!dataurl) return;

            const pagelength = this.getPageLength(route);
            const exportColumns = this.getExportColumns(route);
            const swap = '';

            $(this.config.tableId).DataTable({
                dom: 'Bfrtip',
                pageLength: pagelength,
                pagingType: 'simple_numbers',
                stateSave: true,
                stateLoadParams: (settings, data) => {
                    if (this.isSpecialRoute(route)) {
                        data.length = -1;
                    }
                },
                stateSaveCallback: (settings, data) => {
                    localStorage.setItem(this.config.tableStateKey, JSON.stringify(data));
                },
                stateLoadCallback: () => {
                    const data = localStorage.getItem(this.config.tableStateKey);
                    return data ? JSON.parse(data) : null;
                },
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'></i>",
                        next: "<i class='mdi mdi-chevron-right'></i>"
                    }
                },
                lengthMenu: this.config.gridConfig.length_menu || [
                    [10, 20, 50, 100, 500, 1000, 5000, 10000, -1],
                    [10, 20, 50, 100, 500, 1000, 5000, 10000, 'All']
                ],
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { targets: 'no-sort', orderable: false }
                ],
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fa fa-file-excel">&nbsp;&nbsp;</i>',
                    titleAttr: 'Excel',
                    exportOptions: { columns: exportColumns }
                }],
                initComplete: () => {
                    $('.buttons-excel').attr('data-toggle', 'tooltip')
                        .attr('title', 'Export to Excel').tooltip();
                },
                drawCallback: () => {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                },
                processing: true,
                searching: false,
                destroy: true,
                bSort: true,
                serverSide: true,
                sScrollX: '100%',
                Paginate: true,
                processing: false,
                oLanguage: {
                    sProcessing: '',
                },
                ajax: {
                    type: 'POST',
                    url: (dataurl.indexOf('http') === -1 && typeof baseURL !== 'undefined') 
                        ? baseURL + '/' + dataurl.replace(/^\//, '')
                        : dataurl,
                    data: (d) => {
                        const filters = {};
                        filters.filters = convertFormSearilizeToObject(this.config.formId);
                        
                        return {
                            draw: d.draw,
                            start: d.start,
                            length: d.length,
                            order: d.order,
                            columns: d.columns,
                            search: d.search,
                            swap: swap,
                            table: this.config.gridConfig.table,
                            filters: filters.filters
                        };
                    }
                }
            });
        }

        /**
         * Get current route from URL
         * @returns {string}
         */
        getCurrentRoute() {
            const pathn = window.location.pathname;
            const routeArray = pathn.split('/');
            return routeArray[routeArray.length - 1];
        }

        /**
         * Get data URL for DataTable
         * @returns {string}
         */
        getDataUrl() {
            const pageName = this.config.gridConfig.page_name || null;
            const param = this.config.gridConfig.param || null;

            if (pageName === 'invoice-list') {
                return param ? `get-invoice-list/${param}` : 'get-invoice-list';
            }

            return this.config.gridConfig.dataurl || '';
        }

        /**
         * Get page length based on route
         * @param {string} route - Current route
         * @returns {number}
         */
        getPageLength(route) {
            if (this.isSpecialRoute(route)) {
                return -1;
            }
            return this.config.gridConfig.page_length || 0;
        }

        /**
         * Get export columns based on route
         * @param {string} route - Current route
         * @returns {string}
         */
        getExportColumns(route) {
            if (route === 'stock' || route === 'user-stock') {
                return ':visible';
            }
            return this.config.gridConfig.export_columns || ':not(:last-child)';
        }

        /**
         * Check if route is a special route
         * @param {string} route - Current route
         * @returns {boolean}
         */
        isSpecialRoute(route) {
            return ['stock', 'service-item-mapping-list', 'list-assigned-material'].indexOf(route) !== -1;
        }

        /**
         * Start the gridview initialization process
         */
        start() {
            this.restoreFilters(() => {
                if (this.dateRangeInitPromises.length > 0) {
                    Promise.all(this.dateRangeInitPromises).then(() => {
                        setTimeout(() => this.initializeDataTable(), 100);
                    });
                } else {
                    setTimeout(() => this.initializeDataTable(), 200);
                }
            }, this.dateRangeFields);
        }
    }

    // Make it globally available
    window.GridViewUtility = GridViewUtility;

    // Auto-initialize if gridConfig is available
    $(document).ready(function() {
        if (typeof gridConfig !== 'undefined' && gridConfig) {
            const gridView = new GridViewUtility({
                gridConfig: gridConfig
            });
            
            // Make saveFiltersAndFetch globally available
            window.saveFiltersAndFetch = function() {
                gridView.saveFiltersAndFetch();
            };
            
            // Start initialization
            gridView.start();
        }
    });

})(jQuery);

