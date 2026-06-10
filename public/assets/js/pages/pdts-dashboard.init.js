/**
 * PDTS dashboard charts (ApexCharts) — Module 1 delay tracking + Module 3 renovation.
 */
(function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    var data = window.pdtsDashboardData || {};
    var flags = window.pdtsDashboardFlags || { showDelay: true, showRenovation: false };
    var renovation = data.renovation || {};

    function hasSeriesValues(series) {
        return Array.isArray(series) && series.some(function (v) { return Number(v) > 0; });
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
        new ApexCharts(el, {
            chart: { type: 'donut', height: 300, toolbar: { show: false } },
            series: chartData.series,
            labels: chartData.labels,
            colors: chartData.colors || ['#003e6b', '#1cbb8c', '#fcb92c', '#ff3d60', '#4aa3ff'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
            plotOptions: {
                pie: {
                    donut: { size: '62%' }
                }
            }
        }).render();
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
        new ApexCharts(el, {
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            series: [{ name: seriesName || 'Count', data: chartData.series }],
            xaxis: { categories: chartData.labels },
            plotOptions: {
                bar: {
                    horizontal: !!horizontal,
                    columnWidth: '45%',
                    borderRadius: 4
                }
            },
            colors: chartData.colors || ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        }).render();
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
        new ApexCharts(el, {
            chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
            series: [{ name: seriesName || 'Count', data: chartData.series }],
            xaxis: { categories: chartData.labels },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.35, opacityTo: 0.05 }
            },
            colors: ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        }).render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (flags.showDelay) {
            renderDonut('#chart-delays-severity', data.delays_by_severity, 'No delay entries yet.');
            renderBar('#chart-delays-category', data.delays_by_category, true, 'No categorised delays yet.');
            renderDonut('#chart-project-status', data.project_status, 'No projects yet.');
            renderDonut('#chart-mitigation-status', data.mitigation_status, 'No mitigations logged yet.');
            renderDonut('#chart-financial-impact', data.financial_impact, 'No financial impact records yet.');
            renderLine('#chart-delay-trend', data.delay_trend, 'No delay trend data for the last 6 months.', 'Delays logged');
            renderBar('#chart-delays-hospital', data.delays_by_hospital, true, 'No hospital delay data yet.');
        }

        if (flags.showRenovation && renovation) {
            renderDonut('#chart-reno-project-status', renovation.project_status, 'No renovation projects yet.');
            renderBar('#chart-reno-type', renovation.renovation_type, true, 'No renovation type data yet.');
            renderDonut('#chart-reno-task-status', renovation.task_status, 'No renovation tasks yet.');
            renderDonut('#chart-reno-task-risk', renovation.task_risk, 'No task risk data yet.');
            renderDonut('#chart-reno-escalation', renovation.escalation_status, 'No escalation data yet.');
            renderBar('#chart-reno-task-category', renovation.tasks_by_category, true, 'No task categories yet.');
            renderLine('#chart-reno-delay-trend', renovation.daily_delay_trend, 'No renovation daily delay logs for the last 6 months.', 'Daily delay logs');
        }
    });
})();
