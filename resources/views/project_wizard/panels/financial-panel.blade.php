@php
    $ctx = $data['ctx'];
    $impact = $data['impact'];
@endphp
<div class="sidelayout-panel">
    <div class="sidelayout-context">{{ $ctx['project_code'] }} — {{ $ctx['department_name'] }}</div>

    <form id="wizardFinancialForm">
        @csrf
        <input type="hidden" name="project_department_id" value="{{ $ctx['id'] }}">
        <input type="hidden" name="financial_impact_id" value="{{ $impact->id ?? '' }}">

        <h6>Direct Costs</h6>
        <div class="row">
            @foreach(['labor_overrun' => 'Labor Overrun', 'material_cost_overrun' => 'Material Cost Overrun', 'contractor_claims' => 'Contractor Claims', 'equipment_storage_charges' => 'Equipment Storage'] as $field => $label)
            <div class="col-md-6 mb-2">
                <label>{{ $label }}</label>
                <input type="number" step="0.01" min="0" class="form-control" name="{{ $field }}" value="{{ $impact->$field ?? '0.00' }}">
            </div>
            @endforeach
        </div>

        <h6>Opportunity Costs</h6>
        <div class="row">
            @foreach(['delayed_admissions' => 'Delayed Admissions', 'delayed_surgeries' => 'Delayed Surgeries', 'delayed_revenue' => 'Delayed Revenue', 'lost_operational_days' => 'Lost Operational Days'] as $field => $label)
            <div class="col-md-6 mb-2">
                <label>{{ $label }}</label>
                <input type="number" step="0.01" min="0" class="form-control" name="{{ $field }}" value="{{ $impact->$field ?? '0.00' }}">
            </div>
            @endforeach
        </div>

        @if(!empty($impact))
        <p class="small text-muted mt-2 mb-0">Department total: <strong>{{ number_format((float)$impact->total_project_delay_cost, 2) }}</strong></p>
        @endif

        <div class="sidelayout-actions">
            <button type="button" class="btn btn-submit btn-sm" id="saveWizardFinancialBtn">Save Financial Impact</button>
        </div>
    </form>
</div>
<script>
$(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle }}');
    $('#saveWizardFinancialBtn').on('click', function() {
        var $btn = $(this);
        ajaxRequestWithPromise("{{ getProjectUrl('wizard_save_financial') }}", $('#wizardFinancialForm').serialize(), 'wizard_save_financial', 0, '', $btn);
    });
});
</script>
