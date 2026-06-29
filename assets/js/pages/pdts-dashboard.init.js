/**
 * PDTS dashboard charts (ApexCharts) — project & department metrics with drilldown links.
 */
(function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    var data = window.pdtsDashboardData || {};
    var widgets = window.pdtsDashboardWidgets || {};

    function isVisible(key) {
        return widgets[key] === true;
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

    function bindDonutDrill(chart, chartData) {
        if (!chartData || !chartData.drill_urls || !chartData.drill_urls.length) {
            return;
        }
        chart.addEventListener('dataPointSelection', function (_event, _ctx, config) {
            navigateDrill(drillUrl(chartData, config.dataPointIndex));
        });
    }

    function bindBarDrill(chart, chartData) {
        if (!chartData || !chartData.drill_urls || !chartData.drill_urls.length) {
            return;
        }
        chart.addEventListener('dataPointSelection', function (_event, _ctx, config) {
            var index = config.dataPointIndex;
            navigateDrill(drillUrl(chartData, index));
        });
    }

    function bindLineDrill(chart, chartData) {
        if (!chartData || !chartData.drill_urls || !chartData.drill_urls.length) {
            return;
        }
        chart.addEventListener('dataPointSelection', function (_event, _ctx, config) {
            navigateDrill(drillUrl(chartData, config.dataPointIndex));
        });
    }

    function renderDonut(elId, chartData, fallbackText) {
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
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
                    dataPointSelection: function (_event, _ctx, config) {
                        navigateDrill(drillUrl(chartData, config.dataPointIndex));
                    }
                }
            },
            series: chartData.series,
            labels: chartData.labels,
            colors: chartData.colors || ['#003e6b', '#1cbb8c', '#fcb92c', '#ff3d60', '#4aa3ff'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
            plotOptions: { pie: { donut: { size: '62%' } } },
            states: {
                active: { filter: { type: 'darken', value: 0.35 } }
            }
        });
        chart.render();
        if (chartData.drill_urls && chartData.drill_urls.length) {
            el.classList.add('dashboard-chart-drillable');
            el.setAttribute('title', 'Click a segment to view details');
        }
    }

    function renderBar(elId, chartData, horizontal, fallbackText, seriesName) {
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
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
                    dataPointSelection: function (_event, _ctx, config) {
                        navigateDrill(drillUrl(chartData, config.dataPointIndex));
                    }
                }
            },
            series: [{ name: seriesName || 'Count', data: chartData.series }],
            xaxis: { categories: chartData.labels },
            plotOptions: {
                bar: { horizontal: !!horizontal, columnWidth: '45%', borderRadius: 4 }
            },
            colors: chartData.colors || ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            states: {
                active: { filter: { type: 'darken', value: 0.25 } }
            }
        });
        chart.render();
        if (chartData.drill_urls && chartData.drill_urls.length) {
            el.classList.add('dashboard-chart-drillable');
            el.setAttribute('title', 'Click a bar to view details');
        }
    }

    function renderLine(elId, chartData, fallbackText, seriesName) {
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
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
                    dataPointSelection: function (_event, _ctx, config) {
                        navigateDrill(drillUrl(chartData, config.dataPointIndex));
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
            states: {
                active: { filter: { type: 'darken', value: 0.25 } }
            }
        });
        chart.render();
        if (chartData.drill_urls && chartData.drill_urls.length) {
            el.classList.add('dashboard-chart-drillable');
            el.setAttribute('title', 'Click a point to view details');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (isVisible('m1_chart_severity')) {
            renderDonut('#chart-delays-severity', data.delays_by_severity, 'No delay entries yet.');
        }
        if (isVisible('m1_chart_category')) {
            renderBar('#chart-delays-category', data.delays_by_category, true, 'No delayed departments yet.');
        }
        if (isVisible('m1_chart_project_status')) {
            renderDonut('#chart-project-status', data.project_status, 'No projects yet.');
        }
        if (isVisible('m1_chart_mitigation')) {
            renderDonut('#chart-mitigation-status', data.department_status, 'No department execution data yet.');
        }
        if (isVisible('m1_chart_financial')) {
            renderDonut('#chart-financial-impact', data.financial_impact, 'No financial impact records yet.');
        }
        if (isVisible('m1_chart_trend')) {
            renderLine('#chart-delay-trend', data.delay_trend, 'No delay trend data for the last 6 months.', 'Delays logged');
        }
        if (isVisible('m1_chart_hospital')) {
            renderBar('#chart-delays-hospital', data.delays_by_hospital, true, 'No hospital delay data yet.');
        }
        if (isVisible('m1_chart_zone')) {
            renderZoneMetrics('#chart-zone-metrics', data.zone_metrics);
        }
        if (isVisible('m1_chart_task_status')) {
            renderDonut('#chart-wizard-task-status', data.wizard_task_status, 'No wizard tasks configured yet.');
        }
        if (isVisible('m1_chart_top_tasks')) {
            renderBar('#chart-top-wizard-tasks', data.top_wizard_tasks, true, 'No task usage data yet.', 'Instances');
        }
    });

    function renderZoneMetrics(elId, chartData) {
        var el = document.querySelector(elId);
        if (!el) {
            return;
        }
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
                    dataPointSelection: function (_event, _ctx, config) {
                        var seriesIndex = config.seriesIndex;
                        var dataIndex = config.dataPointIndex;
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
            states: {
                active: { filter: { type: 'darken', value: 0.25 } }
            }
        });
        chart.render();
        el.classList.add('dashboard-chart-drillable');
        el.setAttribute('title', 'Click a bar to view filtered projects or departments');
    }
})();
