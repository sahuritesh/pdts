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
            this._filterTextDebounceTimer = null;
            /** Last order[] sent to server-side ajax (matches list query; used for server Excel export). */
            this._lastServerSideOrder = null;

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

            const $tbl = $(this.config.tableId);
            if ($.fn.DataTable && $tbl.length && $.fn.DataTable.isDataTable($tbl[0])) {
                const api = $tbl.DataTable();
                try {
                    api.state.clear();
                } catch (e) {
                    /* ignore */
                }
                api.page(0).ajax.reload(null, false);
                return;
            }

            this.initializeDataTable();
        }

        /**
         * Get index of Actions column (-1 if not found).
         * @returns {number}
         */
        getActionsColumnIndex() {
            const cols = this.config.gridConfig.columns || [];
            return cols.findIndex((col) => col === 'Actions' || col === 'Action');
        }

        /**
         * Build DataTables column definitions.
         * @returns {Array}
         */
        buildColumnDefs() {
            const columnDefs = [
                { targets: 'no-sort', orderable: false }
            ];
            const actionsIdx = this.getActionsColumnIndex();

            if (actionsIdx >= 0) {
                columnDefs.push({
                    targets: actionsIdx,
                    orderable: false,
                    className: 'grid-actions-cell',
                    width: '1%'
                });
            } else {
                columnDefs.push({ orderable: false, targets: -1 });
            }

            return columnDefs;
        }

        /**
         * Keep header/body column widths in sync.
         */
        syncGridColumnWidths() {
            const $table = $(this.config.tableId);
            if (!$table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($table[0])) {
                return;
            }

            const api = $table.DataTable();
            if (api.columns && typeof api.columns.adjust === 'function') {
                api.columns.adjust();
            }
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
                        return;
                    }
                    const actionsIdx = this.getActionsColumnIndex();
                    if (actionsIdx >= 0 && data && data.columns && data.columns[actionsIdx]) {
                        delete data.columns[actionsIdx].width;
                    }
                    const se = this.config.gridConfig.server_export;
                    if (se && se.url && data) {
                        try {
                            const raw = localStorage.getItem(this.getServerExportRowLimitStorageKey());
                            if (raw !== null && raw !== '') {
                                const n = parseInt(raw, 10);
                                const maxRows = se.max_rows || 25000;
                                if (!isNaN(n)) {
                                    data.length = n === -1 ? maxRows : Math.min(Math.max(n, 1), maxRows);
                                }
                            }
                        } catch (e) {
                            /* ignore */
                        }
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
                columnDefs: this.buildColumnDefs(),
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fa fa-file-excel">&nbsp;&nbsp;</i>',
                    titleAttr: 'Excel',
                    exportOptions: { columns: exportColumns }
                }],
                initComplete: () => {
                    $('.buttons-excel').attr('data-toggle', 'tooltip')
                        .attr('title', 'Export to Excel').tooltip();
                    this.setupServerExportToolbar();
                    this.bindFilterChangeReload();
                    setTimeout(() => this.syncGridColumnWidths(), 0);
                },
                drawCallback: () => {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    this.syncGridColumnWidths();
                },
                processing: true,
                searching: false,
                destroy: true,
                bSort: true,
                serverSide: true,
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
                        if (d.order && d.order.length && d.order[0] && typeof d.order[0].column !== 'undefined') {
                            this._lastServerSideOrder = {
                                column: parseInt(d.order[0].column, 10),
                                dir: (d.order[0].dir || 'desc').toString()
                            };
                        } else {
                            this._lastServerSideOrder = null;
                        }
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
            const se = this.config.gridConfig.server_export;
            if (se && se.url) {
                try {
                    const raw = localStorage.getItem(this.getServerExportRowLimitStorageKey());
                    if (raw !== null && raw !== '') {
                        const n = parseInt(raw, 10);
                        const maxRows = se.max_rows || 25000;
                        if (!isNaN(n)) {
                            if (n === -1) {
                                return maxRows;
                            }
                            if (n > 0) {
                                return Math.min(n, maxRows);
                            }
                        }
                    }
                } catch (e) {
                    /* ignore */
                }
            }
            const fallback = this.config.gridConfig.page_length || 0;
            return fallback > 0 ? fallback : 10;
        }

        getServerExportRowLimitStorageKey() {
            return 'gridServerRowLimit_' + window.location.pathname;
        }

        /**
         * Apply rows-per-page from #grid-server-export-limit to the active DataTable.
         */
        applyServerExportRowLimitToDataTable() {
            const se = this.config.gridConfig.server_export;
            if (!se || !se.url) {
                return;
            }
            const $sel = $('#grid-server-export-limit');
            if (!$sel.length) {
                return;
            }
            const $tbl = $(this.config.tableId);
            if (!$tbl.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($tbl[0])) {
                return;
            }
            const raw = parseInt($sel.val(), 10);
            const maxRows = se.max_rows || 25000;
            const len = (raw === -1 || isNaN(raw)) ? maxRows : Math.min(Math.max(raw, 1), maxRows);
            const api = $tbl.DataTable();
            try {
                api.state.clear();
            } catch (e) {
                /* ignore */
            }
            api.page.len(len).page(0).ajax.reload(null, false);
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
         * When server_export is configured, hide the client-only Excel button and offer server download with row limit.
         */
        /**
         * Reload grid when filter fields change (without requiring Search click).
         */
        bindFilterChangeReload() {
            if (this.config.gridConfig.reload_on_filter_change === false) {
                return;
            }
            const $form = $('#' + this.config.formId);
            if (!$form.length) {
                return;
            }
            const run = () => {
                if (typeof window.saveFiltersAndFetch === 'function') {
                    window.saveFiltersAndFetch();
                }
            };
            const self = this;
            $form.off('.gridAutoFilterReload');
            $form.on('change.gridAutoFilterReload', 'select', function (e) {
                if ($(e.target).hasClass('select2-hidden-accessible')) {
                    return;
                }
                run();
            });
            $form.on('select2:select.gridAutoFilterReload', 'select', run);
            $form.on('change.gridAutoFilterReload', 'input[type="checkbox"]', run);
            $form.on('changeDate.gridAutoFilterReload', 'input.default', run);
            $form.on('change.gridAutoFilterReload', 'input.flatpickr-input', run);
            $form.on('change.gridAutoFilterReload', 'input[type="text"]', function (e) {
                const $t = $(e.target);
                if ($t.attr('name') === '_token') {
                    return;
                }
                if ($t.data('datepicker')) {
                    return;
                }
                clearTimeout(self._filterTextDebounceTimer);
                run();
            });
            $form.on('input.gridAutoFilterReload', 'input[type="text"]', function (e) {
                const $t = $(e.target);
                if ($t.attr('name') === '_token') {
                    return;
                }
                if ($t.data('datepicker') || $t.data('daterangepicker')) {
                    return;
                }
                clearTimeout(self._filterTextDebounceTimer);
                self._filterTextDebounceTimer = setTimeout(run, 450);
            });
        }

        setupServerExportToolbar() {
            const se = this.config.gridConfig.server_export;
            if (!se || !se.url) {
                return;
            }
            const wrapId = 'grid-server-export-wrap';
            if ($('#' + wrapId).length) {
                return;
            }
            const $dtButtons = $(this.config.tableId + '_wrapper').find('.dt-buttons');
            if (!$dtButtons.length) {
                return;
            }
            $('.buttons-excel').hide();
            const options = (se.limit_options || []).map(function (o) {
                const label = $('<div/>').text(o.label || o.value).html();
                return '<option value="' + o.value + '">' + label + '</option>';
            }).join('');
            const html =
                '<div id="' + wrapId + '" class="d-inline-flex align-items-center me-2 flex-wrap gap-1">' +
                '<label for="grid-server-export-limit" class="mb-0 small text-muted">Rows</label>' +
                '<select id="grid-server-export-limit" class="form-select form-select-sm" style="width:auto;min-width:120px;max-width:220px;">' +
                options +
                '</select>' +
                '<button type="button" id="grid-server-export-btn" class="btn btn-success btn-sm waves-effect waves-light">' +
                '<i class="fa fa-file-excel"></i> Export</button></div>';
            $dtButtons.prepend(html);
            const self = this;
            const storageKey = this.getServerExportRowLimitStorageKey();
            const $limitSel = $('#grid-server-export-limit');
            try {
                const stored = localStorage.getItem(storageKey);
                if (stored !== null && $limitSel.find('option[value="' + stored + '"]').length) {
                    $limitSel.val(stored);
                } else {
                    const defLen = this.config.gridConfig.page_length || 20;
                    if ($limitSel.find('option[value="' + defLen + '"]').length) {
                        $limitSel.val(String(defLen));
                    }
                }
            } catch (e) {
                /* ignore */
            }
            $limitSel.on('change.gridServerRowLimit', function () {
                try {
                    localStorage.setItem(storageKey, String($(this).val()));
                } catch (e2) {
                    /* ignore */
                }
                self.applyServerExportRowLimitToDataTable();
            });
            $('#grid-server-export-btn').on('click', function () {
                self.triggerServerExport($(this));
            });
        }

        triggerServerExport($btn) {
            const se = this.config.gridConfig.server_export;
            if (!se || !se.url) {
                return;
            }
            const limit = parseInt($('#grid-server-export-limit').val(), 10);
            const filters = convertFormSearilizeToObject(this.config.formId);
            let orderPayload = this._lastServerSideOrder;
            const $tbl = $(this.config.tableId);
            if ((orderPayload === null || orderPayload === undefined) && $.fn.DataTable && $tbl.length && $.fn.DataTable.isDataTable($tbl[0])) {
                const ord = $tbl.DataTable().order();
                if (ord && ord.length && ord[0]) {
                    const o0 = ord[0];
                    if (typeof o0 === 'object' && o0 !== null && !Array.isArray(o0) && typeof o0.column !== 'undefined') {
                        orderPayload = { column: parseInt(o0.column, 10), dir: (o0.dir || 'desc').toString() };
                    } else if (Array.isArray(o0) && typeof o0[0] !== 'undefined') {
                        orderPayload = { column: parseInt(o0[0], 10), dir: (o0[1] || 'desc').toString() };
                    }
                }
            }
            const token = $('meta[name="csrf-token"]').attr('content');
            const body = new URLSearchParams();
            body.append('_token', token);
            body.append('postKey', 'export_inspections');
            body.append('data', JSON.stringify({ export_limit: limit, filters: filters, order: orderPayload }));
            showGlobalLoader(true);
            if ($btn && $btn.length) {
                $btn.prop('disabled', true);
            }
            fetch(se.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,*/*',
                },
                body: body,
            })
                .then(function (res) {
                    if (!res.ok) {
                        return res.text().then(function (text) {
                            let msg = 'Export failed.';
                            try {
                                const j = JSON.parse(text);
                                if (j.msg) {
                                    msg = j.msg;
                                } else if (j.message) {
                                    msg = j.message;
                                }
                            } catch (e) {
                                /* ignore */
                            }
                            throw new Error(msg);
                        });
                    }
                    const cd = res.headers.get('Content-Disposition') || '';
                    let filename = 'inspections_export.xlsx';
                    const m = /filename="([^"]+)"/i.exec(cd);
                    if (m && m[1]) {
                        filename = m[1].trim();
                    }
                    return res.blob().then(function (blob) {
                        return { blob: blob, filename: filename };
                    });
                })
                .then(function (result) {
                    showGlobalLoader(false);
                    if ($btn && $btn.length) {
                        $btn.prop('disabled', false);
                    }
                    const url = window.URL.createObjectURL(result.blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = result.filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                })
                .catch(function (err) {
                    showGlobalLoader(false);
                    if ($btn && $btn.length) {
                        $btn.prop('disabled', false);
                    }
                    window.alert(err && err.message ? err.message : 'Export failed.');
                });
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

