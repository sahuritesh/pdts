/**
 * PDTS dashboard charts (ApexCharts) — project & department metrics.
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
            plotOptions: { pie: { donut: { size: '62%' } } }
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
                bar: { horizontal: !!horizontal, columnWidth: '45%', borderRadius: 4 }
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
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            colors: ['#003e6b'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        }).render();
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
    });
})();
