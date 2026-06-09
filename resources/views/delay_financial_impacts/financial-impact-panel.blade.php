@php
$delay = $data['delay'] ?? null;
$impact = $data['impact'] ?? null;
$addUrl = $data['add_url'] ?? '';
$editUrl = $data['edit_url'] ?? '';
$panelUrl = $data['panel_url'] ?? '';
@endphp
<div class="row">
    <div class="col-md-12">
        @if($delay)
        <div class="alert alert-light border mb-3 py-2">
            <div class="small mb-1"><strong>Project:</strong> {{ trim(($delay->project_code ?? '') . ' — ' . ($delay->project_name ?? '')) }}</div>
            <div class="small mb-1"><strong>Delay:</strong> {{ $delay->delay_title ?? '' }}</div>
            <div class="small"><strong>Severity:</strong> {{ ucfirst($delay->severity ?? '') }} &nbsp;|&nbsp; <strong>Days:</strong> {{ (int)($delay->delay_days ?? 0) }}</div>
        </div>
        @endif

        @if($impact)
        <div class="card border mb-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">Cost Summary</h6>
                    <a href="javascript:void(0)"
                       onclick="openSideLayout({}, '{{ $editUrl }}', 'Edit Financial Impact', 90); return false;"
                       class="btn btn-primary btn-sm waves-effect waves-light">
                        <i class="ri-edit-fill"></i> Edit
                    </a>
                </div>
                <div class="row small">
                    <div class="col-md-6 mb-2">
                        <strong>Direct costs</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Labor: {{ number_format((float)($impact->labor_overrun ?? 0), 2) }}</li>
                            <li>Material: {{ number_format((float)($impact->material_cost_overrun ?? 0), 2) }}</li>
                            <li>Contractor claims: {{ number_format((float)($impact->contractor_claims ?? 0), 2) }}</li>
                            <li>Equipment storage: {{ number_format((float)($impact->equipment_storage_charges ?? 0), 2) }}</li>
                        </ul>
                        <div class="mt-1"><strong>Direct total:</strong> {{ number_format((float)($impact->direct_cost_total ?? 0), 2) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Opportunity costs</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Delayed admissions: {{ number_format((float)($impact->delayed_admissions ?? 0), 2) }}</li>
                            <li>Delayed surgeries: {{ number_format((float)($impact->delayed_surgeries ?? 0), 2) }}</li>
                            <li>Delayed revenue: {{ number_format((float)($impact->delayed_revenue ?? 0), 2) }}</li>
                            <li>Lost operational days: {{ number_format((float)($impact->lost_operational_days ?? 0), 2) }}</li>
                        </ul>
                        <div class="mt-1"><strong>Opportunity total:</strong> {{ number_format((float)($impact->opportunity_cost_total ?? 0), 2) }}</div>
                    </div>
                </div>
                <hr class="my-2">
                <div class="small"><strong>Grand total delay cost:</strong> {{ number_format((float)($impact->total_project_delay_cost ?? 0), 2) }}</div>
            </div>
        </div>
        @else
        <div class="text-center py-4 mb-2">
            <p class="text-muted mb-3">No financial impact recorded for this delay yet.</p>
            <a href="javascript:void(0)"
               onclick="openSideLayout({}, '{{ $addUrl }}', 'Add Financial Impact', 90); return false;"
               class="btn btn-primary waves-effect waves-light">
                <i class="fas fa-plus fa-fw"></i> Add Financial Impact
            </a>
        </div>
        @endif
    </div>
</div>
<script>
(function() {
    var panelUrl = @json($panelUrl);

    window.reloadFinancialImpactPanel = function(url) {
        var reloadUrl = url || panelUrl;
        if (!reloadUrl) {
            return;
        }
        ajaxRequestWithPromise(reloadUrl, { postKey: 'sidelayoutContent' }, '', 0, '', null).then(function(html) {
            if (typeof html === 'string' && html.indexOf('error') === -1) {
                $('#offcanvasRight .offcanvas-body').html(html);
            }
        });
    };

    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Financial Impact" }}');
})();
</script>
