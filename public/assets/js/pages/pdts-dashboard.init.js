/**
 * PDTS Module 1 dashboard charts (ApexCharts).
 */
(function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    var data = window.pdtsDashboardData || {};
    var kpis = data.kpis || {};

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

    function renderBar(elId, chartData, horizontal, fallbackText) {
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
            series: [{ name: 'Count', data: chartData.series }],
            xaxis: horizontal ? { categories: chartData.labels } : { categories: chartData.labels },
            plotOptions: {
                bar: {
                    horizontal: !!horizontal,
                    columnWidth: '45%',
                    borderRadius: 4
                }
            },
            colors: ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        }).render();
    }

    function renderLine(elId, chartData, fallbackText) {
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
            series: [{ name: 'Delays logged', data: chartData.series }],
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
        renderDonut('#chart-delays-severity', data.delays_by_severity, 'No delay entries yet.');
        renderBar('#chart-delays-category', data.delays_by_category, true, 'No categorised delays yet.');
        renderDonut('#chart-project-status', data.project_status, 'No projects yet.');
        renderDonut('#chart-mitigation-status', data.mitigation_status, 'No mitigations logged yet.');
        renderLine('#chart-delay-trend', data.delay_trend, 'No delay trend data for the last 6 months.');
        renderBar('#chart-delays-hospital', data.delays_by_hospital, true, 'No hospital delay data yet.');
    });
})();
