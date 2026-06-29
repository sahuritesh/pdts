/**
 * PDTS dashboard — AJAX filters, date range, ApexCharts with drilldown.
 */
(function ($) {
    'use strict';

    if (typeof ApexCharts === 'undefined') {
        return;
    }

    var config = window.pdtsDashboardConfig || {};
    var state = {
        data: config.initialData || {},
        widgets: config.widgets || {},
        drillLinks: config.drillLinks || {},
        filters: $.extend({
            zone_id: null,
            project_id: null,
            date_from: null,
            date_to: null,
            date_preset: 'all'
        }, config.filters || {}),
        showZoneChart: config.showZoneChart !== false,
        charts: {},
        fetchToken: 0,
        debounceTimer: null
    };

    function isVisible(key) {
        return state.widgets[key] === true;
    }

    function hasSeriesValues(series) {
        return Array.isArray(series) && series.some(function (v) { return Number(v) > 0; });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatNumber(value, decimals) {
        var n = Number(value) || 0;
        return n.toLocaleString(undefined, {
            minimumFractionDigits: decimals || 0,
            maximumFractionDigits: decimals || 0
        });
    }

    function drillUrl(chartData, index) {
        if (!chartData || !chartData.drill_urls || !chartData.drill_urls.length) {
            return '';
        }
        return chartData.drill_urls[index] || '';
    }

    function navigateDrill(url) {
        if (url) {
            window.location.href = url;
        }
    }

    function destroyChart(key) {
        if (state.charts[key]) {
            try {
                state.charts[key].destroy();
            } catch (e) { /* ignore */ }
            delete state.charts[key];
        }
    }

    function setChartContainerHtml(selector, html) {
        var el = document.querySelector(selector);
        if (el) {
            el.innerHTML = html;
        }
    }

    function renderDonut(key, elId, chartData, fallbackText) {
        destroyChart(key);
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
        el.classList.remove('dashboard-chart-drillable');
        if (!chartData || !hasSeriesValues(chartData.series)) {
            el.innerHTML = '<p class="text-muted text-center py-5 mb-0">' + fallbackText + '</p>';
            return;
        }
        var chart = new ApexCharts(el, {
            chart: {
                type: 'donut',
                height: 300,
                toolbar: { show: false },
                events: {
                    dataPointSelection: function (_event, _ctx, cfg) {
                        navigateDrill(drillUrl(chartData, cfg.dataPointIndex));
                    }
                }
            },
            series: chartData.series,
            labels: chartData.labels,
            colors: chartData.colors || ['#003e6b', '#1cbb8c', '#fcb92c', '#ff3d60', '#4aa3ff'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
            plotOptions: { pie: { donut: { size: '62%' } } },
            states: { active: { filter: { type: 'darken', value: 0.35 } } }
        });
        state.charts[key] = chart;
        chart.render();
        if (chartData.drill_urls && chartData.drill_urls.length) {
            el.classList.add('dashboard-chart-drillable');
            el.setAttribute('title', 'Click a segment to view details');
        }
    }

    function renderBar(key, elId, chartData, horizontal, fallbackText, seriesName) {
        destroyChart(key);
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
        el.classList.remove('dashboard-chart-drillable');
        if (!chartData || !hasSeriesValues(chartData.series)) {
            el.innerHTML = '<p class="text-muted text-center py-5 mb-0">' + fallbackText + '</p>';
            return;
        }
        var chart = new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false },
                events: {
                    dataPointSelection: function (_event, _ctx, cfg) {
                        navigateDrill(drillUrl(chartData, cfg.dataPointIndex));
                    }
                }
            },
            series: [{ name: seriesName || 'Count', data: chartData.series }],
            xaxis: { categories: chartData.labels },
            plotOptions: { bar: { horizontal: !!horizontal, columnWidth: '45%', borderRadius: 4 } },
            colors: chartData.colors || ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            states: { active: { filter: { type: 'darken', value: 0.25 } } }
        });
        state.charts[key] = chart;
        chart.render();
        if (chartData.drill_urls && chartData.drill_urls.length) {
            el.classList.add('dashboard-chart-drillable');
            el.setAttribute('title', 'Click a bar to view details');
        }
    }

    function renderLine(key, elId, chartData, fallbackText, seriesName) {
        destroyChart(key);
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
        el.classList.remove('dashboard-chart-drillable');
        if (!chartData || !hasSeriesValues(chartData.series)) {
            el.innerHTML = '<p class="text-muted text-center py-5 mb-0">' + fallbackText + '</p>';
            return;
        }
        var chart = new ApexCharts(el, {
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                zoom: { enabled: false },
                events: {
                    dataPointSelection: function (_event, _ctx, cfg) {
                        navigateDrill(drillUrl(chartData, cfg.dataPointIndex));
                    }
                }
            },
            series: [{ name: seriesName || 'Count', data: chartData.series }],
            xaxis: { categories: chartData.labels },
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            colors: ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            markers: { size: 4, hover: { size: 7 } },
            states: { active: { filter: { type: 'darken', value: 0.25 } } }
        });
        state.charts[key] = chart;
        chart.render();
        if (chartData.drill_urls && chartData.drill_urls.length) {
            el.classList.add('dashboard-chart-drillable');
            el.setAttribute('title', 'Click a point to view details');
        }
    }

    function buildZoneTooltipHtml(zoneLabel, seriesName, count, bucket, seriesKey) {
        bucket = bucket || { items: [], total: 0, more: 0 };
        var items = bucket.items || [];
        var more = Number(bucket.more) || 0;
        var html = '<div class="zone-chart-tooltip">';
        html += '<div class="zone-chart-tooltip-title">' + escapeHtml(zoneLabel) + '</div>';
        html += '<div class="zone-chart-tooltip-count"><strong>' + escapeHtml(seriesName) + ':</strong> ' + escapeHtml(count) + '</div>';
        if (items.length) {
            html += '<ul class="zone-chart-tooltip-list">';
            items.forEach(function (item) {
                if (seriesKey === 'departments_delayed' && item && typeof item === 'object') {
                    var line = escapeHtml(item.department || 'Department');
                    if (item.project) {
                        line += ' <span class="zone-chart-tooltip-muted">(' + escapeHtml(item.project) + ')</span>';
                    }
                    if (item.hospital) {
                        line += ' <span class="zone-chart-tooltip-muted">· ' + escapeHtml(item.hospital) + '</span>';
                    }
                    if (Number(item.days) > 0) {
                        line += ' <span class="zone-chart-tooltip-days">' + escapeHtml(item.days) + 'd</span>';
                    }
                    html += '<li>' + line + '</li>';
                } else {
                    html += '<li>' + escapeHtml(item) + '</li>';
                }
            });
            if (more > 0) {
                html += '<li class="zone-chart-tooltip-more">+' + escapeHtml(more) + ' more — click bar for full list</li>';
            }
            html += '</ul>';
        } else if (Number(count) > 0) {
            html += '<div class="zone-chart-tooltip-muted">Click bar to view full list</div>';
        }
        html += '</div>';
        return html;
    }

    function renderZoneMetrics(chartData) {
        var key = 'zone_metrics';
        destroyChart(key);
        var el = document.querySelector('#chart-zone-metrics');
        if (!el) {
            return;
        }
        el.classList.remove('dashboard-chart-drillable');
        if (!chartData || !chartData.labels || !chartData.labels.length) {
            el.innerHTML = '<p class="text-muted text-center py-5 mb-0">No zone data yet.</p>';
            return;
        }
        var drillUrls = chartData.drill_urls || {};
        var tooltipDetails = chartData.tooltip_details || {};
        var zoneSeriesKeys = ['projects', 'delayed_projects', 'departments_delayed'];
        var chart = new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                events: {
                    dataPointSelection: function (_event, _ctx, cfg) {
                        var seriesIndex = cfg.seriesIndex;
                        var dataIndex = cfg.dataPointIndex;
                        var url = '';
                        if (seriesIndex === 0 && drillUrls.projects) {
                            url = drillUrls.projects[dataIndex] || '';
                        } else if (seriesIndex === 1 && drillUrls.delayed_projects) {
                            url = drillUrls.delayed_projects[dataIndex] || '';
                        } else if (seriesIndex === 2 && drillUrls.departments_delayed) {
                            url = drillUrls.departments_delayed[dataIndex] || '';
                        }
                        navigateDrill(url);
                    }
                }
            },
            series: [
                { name: 'Projects', data: chartData.projects || [] },
                { name: 'Delayed projects', data: chartData.delayed_projects || [] },
                { name: 'Departments delayed', data: chartData.departments_delayed || [] }
            ],
            xaxis: { categories: chartData.labels },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
            colors: ['#003e6b', '#fcb92c', '#ff3d60'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            legend: { position: 'bottom' },
            tooltip: {
                shared: false,
                intersect: true,
                custom: function (opts) {
                    var seriesIndex = opts.seriesIndex;
                    var dataPointIndex = opts.dataPointIndex;
                    var zoneLabel = (opts.w.globals.labels && opts.w.globals.labels[dataPointIndex]) || '';
                    var seriesName = (opts.w.globals.seriesNames && opts.w.globals.seriesNames[seriesIndex]) || '';
                    var count = opts.series[seriesIndex][dataPointIndex];
                    var seriesKey = zoneSeriesKeys[seriesIndex] || '';
                    var bucket = (tooltipDetails[seriesKey] && tooltipDetails[seriesKey][dataPointIndex])
                        ? tooltipDetails[seriesKey][dataPointIndex]
                        : null;
                    return buildZoneTooltipHtml(zoneLabel, seriesName, count, bucket, seriesKey);
                }
            },
            states: { active: { filter: { type: 'darken', value: 0.25 } } }
        });
        state.charts[key] = chart;
        chart.render();
        el.classList.add('dashboard-chart-drillable');
        el.setAttribute('title', 'Click a bar to view filtered projects or departments');
    }

    function renderCharts() {
        var data = state.data || {};
        if (isVisible('m1_chart_severity')) {
            renderDonut('delays_severity', '#chart-delays-severity', data.delays_by_severity, 'No delay entries yet.');
        }
        if (isVisible('m1_chart_category')) {
            renderBar('delays_category', '#chart-delays-category', data.delays_by_category, true, 'No delayed departments yet.');
        }
        if (isVisible('m1_chart_project_status')) {
            renderDonut('project_status', '#chart-project-status', data.project_status, 'No projects yet.');
        }
        if (isVisible('m1_chart_mitigation')) {
            renderDonut('department_status', '#chart-mitigation-status', data.department_status, 'No department execution data yet.');
        }
        if (isVisible('m1_chart_financial')) {
            renderDonut('financial_impact', '#chart-financial-impact', data.financial_impact, 'No financial impact records yet.');
        }
        if (isVisible('m1_chart_trend')) {
            renderLine('delay_trend', '#chart-delay-trend', data.delay_trend, 'No delay trend data for the selected period.', 'Delays logged');
        }
        if (isVisible('m1_chart_hospital')) {
            renderBar('delays_hospital', '#chart-delays-hospital', data.delays_by_hospital, true, 'No hospital delay data yet.');
        }
        if (isVisible('m1_chart_zone') && state.showZoneChart && data.zone_metrics) {
            renderZoneMetrics(data.zone_metrics);
        } else {
            destroyChart('zone_metrics');
        }
        if (isVisible('m1_chart_task_status')) {
            renderDonut('wizard_task_status', '#chart-wizard-task-status', data.wizard_task_status, 'No wizard tasks configured yet.');
        }
        if (isVisible('m1_chart_top_tasks')) {
            renderBar('top_wizard_tasks', '#chart-top-wizard-tasks', data.top_wizard_tasks, true, 'No task usage data yet.', 'Instances');
        }
    }

    function updateKpis() {
        var kpis = state.data.kpis || {};
        var taskKpis = state.data.task_kpis || {};
        var merged = $.extend({}, kpis, taskKpis);

        $('[data-kpi]').each(function () {
            var key = $(this).data('kpi');
            if (merged[key] === undefined) {
                return;
            }
            var decimals = key === 'total_delay_cost' ? 0 : 0;
            $(this).text(formatNumber(merged[key], decimals));
        });
    }

    function updateDrillLinks() {
        $('[data-drill-key]').each(function () {
            var key = $(this).data('drill-key');
            if (state.drillLinks[key]) {
                $(this).attr('href', state.drillLinks[key]);
            }
        });
    }

    function buildDelayedDeptsTable(rows) {
        if (!rows || !rows.length) {
            return '<p class="text-muted mb-0 py-4 text-center">No delayed departments for the selected filters.</p>';
        }
        var html = '<div class="custom-table-wrapper"><div class="table-responsive"><table class="custom-table"><thead><tr>' +
            '<th><i class="ri-error-warning-line"></i> Department</th>' +
            '<th><i class="ri-building-2-line"></i> Project</th>' +
            '<th><i class="ri-hospital-line"></i> Hospital</th>' +
            '<th><i class="ri-time-line"></i> Days</th>' +
            '</tr></thead><tbody>';
        rows.forEach(function (row) {
            html += '<tr class="dashboard-drill-row"' + (row.url ? ' data-href="' + escapeHtml(row.url) + '"' : '') + '>';
            html += '<td><div class="delay-title">' + escapeHtml(row.department) + '</div></td>';
            html += '<td><span class="project-name">' + escapeHtml(row.project) + '</span></td>';
            html += '<td>' + escapeHtml(row.hospital) + '</td>';
            html += '<td><span class="days-badge">' + escapeHtml(row.days) + ' Days</span></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div></div>';
        return html;
    }

    function buildDeptOpenTasksTable(rows) {
        if (!rows || !rows.length) {
            return '<p class="text-muted mb-0 py-4 text-center">No departments with open tasks right now.</p>';
        }
        var html = '<div class="custom-table-wrapper"><div class="table-responsive"><table class="custom-table"><thead><tr>' +
            '<th><i class="ri-stack-line"></i> Department</th>' +
            '<th><i class="ri-building-2-line"></i> Project</th>' +
            '<th><i class="ri-list-check-2"></i> Open tasks</th>' +
            '<th><i class="ri-alarm-warning-line"></i> Overdue</th>' +
            '</tr></thead><tbody>';
        rows.forEach(function (row) {
            html += '<tr class="dashboard-drill-row"' + (row.url ? ' data-href="' + escapeHtml(row.url) + '"' : '') + '>';
            html += '<td><div class="delay-title">' + escapeHtml(row.department) + '</div></td>';
            html += '<td><span class="project-name">' + escapeHtml(row.project) + '</span></td>';
            html += '<td><span class="badge bg-primary-subtle text-primary">' + escapeHtml(row.open_tasks) + '</span></td>';
            html += '<td>';
            if (Number(row.overdue_tasks) > 0) {
                html += '<span class="days-badge">' + escapeHtml(row.overdue_tasks) + ' overdue</span>';
            } else {
                html += '<span class="text-muted">—</span>';
            }
            html += '</td></tr>';
        });
        html += '</tbody></table></div></div>';
        return html;
    }

    function updateTables() {
        if ($('#dashboard-delayed-depts-body').length) {
            $('#dashboard-delayed-depts-body').html(
                buildDelayedDeptsTable(state.data.recent_delayed_departments || [])
            );
        }
        if ($('#dashboard-dept-open-tasks-body').length) {
            $('#dashboard-dept-open-tasks-body').html(
                buildDeptOpenTasksTable(state.data.departments_with_open_tasks || [])
            );
        }
    }

    function toggleZoneChartRow() {
        var $row = $('#dashboard-zone-chart-row');
        if (!$row.length) {
            return;
        }
        if (state.showZoneChart && isVisible('m1_chart_zone')) {
            $row.removeClass('d-none');
        } else {
            $row.addClass('d-none');
            destroyChart('zone_metrics');
        }
    }

    function updateTrendTitle() {
        var $title = $('#dashboard-trend-title');
        if (!$title.length) {
            return;
        }
        var preset = state.filters.date_preset || 'all';
        if (preset === 'all') {
            $title.text('Delays logged — last 6 months');
        } else if (preset === 'custom' && state.filters.date_from && state.filters.date_to) {
            $title.text('Delays logged — selected period');
        } else {
            $title.text('Delays logged — selected period');
        }
    }

    function togglePeriodHints() {
        var show = state.filters.date_preset && state.filters.date_preset !== 'all';
        $('.dashboard-period-hint').toggleClass('d-none', !show);
    }

    function updateFilterStatus() {
        var parts = [];
        var zoneText = $('#dashboard_zone_id option:selected').text();
        var projectText = $('#dashboard_project_id option:selected').text();
        if ($('#dashboard_zone_id').length && $('#dashboard_zone_id').val() !== 'all') {
            parts.push(zoneText);
        }
        if ($('#dashboard_project_id').val() !== 'all') {
            parts.push(projectText);
        }
        if (state.filters.date_preset && state.filters.date_preset !== 'all') {
            if (state.filters.date_preset === 'custom') {
                parts.push(($('#dashboard_date_from').val() || '…') + ' – ' + ($('#dashboard_date_to').val() || '…'));
            } else {
                parts.push($('#dashboard_date_preset option:selected').text());
            }
        }
        var text = parts.length ? 'Showing: ' + parts.join(' · ') : 'Showing all data';
        $('#dashboard-filter-status').text(text);
    }

    function renderDashboard() {
        updateKpis();
        updateDrillLinks();
        updateTables();
        toggleZoneChartRow();
        updateTrendTitle();
        togglePeriodHints();
        renderCharts();
        updateFilterStatus();
    }

    function parseDisplayDate(value) {
        if (!value) {
            return '';
        }
        var parts = String(value).split('/');
        if (parts.length !== 3) {
            return '';
        }
        var d = parts[0].padStart(2, '0');
        var m = parts[1].padStart(2, '0');
        var y = parts[2];
        return y + '-' + m + '-' + d;
    }

    function collectFiltersFromUi() {
        var zoneVal = $('#dashboard_zone_id').val();
        var projectVal = $('#dashboard_project_id').val();
        var preset = $('#dashboard_date_preset').val() || 'all';
        var filters = {
            zone_id: zoneVal && zoneVal !== 'all' ? zoneVal : null,
            project_id: projectVal && projectVal !== 'all' ? projectVal : null,
            date_preset: preset,
            date_from: null,
            date_to: null
        };
        if (preset === 'custom') {
            filters.date_from = parseDisplayDate($('#dashboard_date_from').val());
            filters.date_to = parseDisplayDate($('#dashboard_date_to').val());
        }
        return filters;
    }

    function buildQueryParams(filters) {
        var params = {};
        if (filters.zone_id) {
            params.zone_id = filters.zone_id;
        }
        if (filters.project_id) {
            params.project_id = filters.project_id;
        }
        if (filters.date_preset) {
            params.date_preset = filters.date_preset;
        }
        if (filters.date_preset === 'custom') {
            if (filters.date_from) {
                params.date_from = filters.date_from;
            }
            if (filters.date_to) {
                params.date_to = filters.date_to;
            }
        }
        return params;
    }

    function syncUrl(filters) {
        if (!window.history || !window.history.replaceState) {
            return;
        }
        var params = new URLSearchParams(buildQueryParams(filters));
        var query = params.toString();
        var url = window.location.pathname + (query ? '?' + query : '');
        window.history.replaceState({}, '', url);
    }

    function populateProjects(projects, selectedId) {
        var $select = $('#dashboard_project_id');
        if (!$select.length) {
            return;
        }
        var html = '<option value="all">All projects</option>';
        (projects || []).forEach(function (project) {
            var selected = String(selectedId) === String(project.id) ? ' selected' : '';
            html += '<option value="' + escapeHtml(project.id) + '"' + selected + '>' + escapeHtml(project.label) + '</option>';
        });
        $select.html(html);
    }

    function fetchDashboard(options) {
        options = options || {};
        var filters = options.filters || collectFiltersFromUi();
        state.filters = filters;
        var token = ++state.fetchToken;
        var baseParams = buildQueryParams(filters);

        state.data = {};
        syncUrl(filters);

        return fetchDashboardSection(baseParams, 'kpis', token, 'kpis').then(function () {
            if (token !== state.fetchToken) {
                return;
            }
            return Promise.all([
                fetchDashboardSection(baseParams, 'charts', token, 'charts'),
                fetchDashboardSection(baseParams, 'tables,zone', token, 'tables')
            ]);
        });
    }

    function mergeAnalyticsResponse(response, token) {
        if (token !== state.fetchToken) {
            return false;
        }
        if (String(response.error) !== '0') {
            if (typeof displayResponseMessage === 'function') {
                displayResponseMessage(response);
            }
            return false;
        }

        state.data = $.extend(true, {}, state.data, response.analytics || {});
        state.drillLinks = $.extend({}, state.drillLinks, response.drill_links || {});
        if (response.show_zone_chart !== undefined) {
            state.showZoneChart = response.show_zone_chart !== false;
        }
        if (response.projects) {
            populateProjects(response.projects, response.filters ? response.filters.project_id : null);
        }
        if (response.filters) {
            state.filters = response.filters;
        }
        return true;
    }

    function fetchDashboardSection(baseParams, sections, token, renderMode) {
        var params = $.extend({}, baseParams, { sections: sections });

        return ajaxRequestWithPromise(
            config.analyticsUrl,
            params,
            'get_dashboard_analytics',
            0,
            null,
            null,
            'GET'
        ).then(function (response) {
            if (!mergeAnalyticsResponse(response, token)) {
                return;
            }

            if (renderMode === 'kpis') {
                updateKpis();
                updateDrillLinks();
                togglePeriodHints();
                updateFilterStatus();
                return;
            }
            if (renderMode === 'charts') {
                renderCharts();
                updateTrendTitle();
                return;
            }
            if (renderMode === 'tables') {
                updateTables();
                toggleZoneChartRow();
                if (state.showZoneChart && isVisible('m1_chart_zone')) {
                    renderZoneMetrics(state.data.zone_metrics);
                }
                updateFilterStatus();
            }
        });
    }

    function scheduleFetch(delayMs) {
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(function () {
            fetchDashboard();
        }, delayMs || 250);
    }

    function initDatepickers() {
        if (typeof $.fn.datepicker === 'undefined') {
            return;
        }
        $('.dashboard-filter-date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            clearBtn: true
        }).on('changeDate clearDate', function () {
            if ($('#dashboard_date_preset').val() === 'custom') {
                scheduleFetch(400);
            }
        });
    }

    function resetFilters() {
        if ($('#dashboard_zone_id').length) {
            $('#dashboard_zone_id').val('all');
        }
        $('#dashboard_project_id').val('all');
        $('#dashboard_date_preset').val('all');
        $('#dashboard_date_from').val('');
        $('#dashboard_date_to').val('');
        $('#dashboard_custom_dates').addClass('d-none');
        fetchDashboard({
            filters: {
                zone_id: null,
                project_id: null,
                date_preset: 'all',
                date_from: null,
                date_to: null
            }
        });
    }

    function bindEvents() {
        $('#dashboard_zone_id').on('change', function () {
            $('#dashboard_project_id').val('all');
            fetchDashboard();
        });

        $('#dashboard_project_id').on('change', function () {
            fetchDashboard();
        });

        $('#dashboard_date_preset').on('change', function () {
            var preset = $(this).val();
            if (preset === 'custom') {
                $('#dashboard_custom_dates').removeClass('d-none');
                return;
            }
            $('#dashboard_custom_dates').addClass('d-none');
            fetchDashboard();
        });

        $('#dashboard_apply_filters').on('click', function () {
            fetchDashboard();
        });

        $('#dashboard_reset_filters').on('click', function () {
            resetFilters();
        });
    }

    $(function () {
        if (!config.analyticsUrl) {
            return;
        }
        initDatepickers();
        bindEvents();
        renderDashboard();
    });
}(jQuery));
