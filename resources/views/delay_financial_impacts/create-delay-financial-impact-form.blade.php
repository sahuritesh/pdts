<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $impact = $data['impact'] ?? '';
                $delayRegisters = $data['delay_registers'] ?? [];
                $presetDelayId = $data['preset_delay_register_id'] ?? '';
                $selectedDelayId = $impact['delay_register_id'] ?? $presetDelayId;
                $panelReloadUrl = $data['panel_reload_url'] ?? '';
                $lockDelay = !empty($presetDelayId) && empty($impact);
                @endphp
                <form class="custom-validations" id="delayFinancialImpactForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="financial_impact_id" id="financial_impact_id"
                        value="{{ $impact['financial_impact_id'] ?? $impact['id'] ?? '' }}">
                    <input type="hidden" name="panel_reload_url" id="panel_reload_url" value="{{ $panelReloadUrl }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="delay_register_id" class="required-label">Delay Entry</label>
                                <select name="delay_register_id" id="delay_register_id" class="form-control dd-select required" @if($lockDelay) disabled @endif>
                                    <option value="">Select delay entry</option>
                                    @foreach($delayRegisters as $delay)
                                    <option value="{{ $delay['value'] }}" @if($selectedDelayId == $delay['value']) selected @endif>{{ $delay['label'] }}</option>
                                    @endforeach
                                </select>
                                @if($lockDelay)
                                <input type="hidden" name="delay_register_id" value="{{ $selectedDelayId }}">
                                @endif
                            </div>
                        </div>

                        <h6 class="text-muted mb-2">Direct Costs</h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="labor_overrun">Labor Overrun</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="labor_overrun" id="labor_overrun"
                                    value="{{ $impact['labor_overrun'] ?? '0.00' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="material_cost_overrun">Material Cost Overrun</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="material_cost_overrun" id="material_cost_overrun"
                                    value="{{ $impact['material_cost_overrun'] ?? '0.00' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="contractor_claims">Contractor Claims</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="contractor_claims" id="contractor_claims"
                                    value="{{ $impact['contractor_claims'] ?? '0.00' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="equipment_storage_charges">Equipment Storage Charges</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="equipment_storage_charges" id="equipment_storage_charges"
                                    value="{{ $impact['equipment_storage_charges'] ?? '0.00' }}" />
                            </div>
                        </div>

                        <h6 class="text-muted mb-2 mt-2">Opportunity Costs</h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="delayed_admissions">Delayed Admissions</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="delayed_admissions" id="delayed_admissions"
                                    value="{{ $impact['delayed_admissions'] ?? '0.00' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="delayed_surgeries">Delayed Surgeries</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="delayed_surgeries" id="delayed_surgeries"
                                    value="{{ $impact['delayed_surgeries'] ?? '0.00' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="delayed_revenue">Delayed Revenue</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="delayed_revenue" id="delayed_revenue"
                                    value="{{ $impact['delayed_revenue'] ?? '0.00' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="lost_operational_days">Lost Operational Days</label>
                                <input type="number" step="0.01" min="0" class="form-control cost-input" name="lost_operational_days" id="lost_operational_days"
                                    value="{{ $impact['lost_operational_days'] ?? '0.00' }}" />
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0 py-2">
                            <div class="row small">
                                <div class="col-md-4"><strong>Direct total:</strong> <span id="preview_direct_total">0.00</span></div>
                                <div class="col-md-4"><strong>Opportunity total:</strong> <span id="preview_opportunity_total">0.00</span></div>
                                <div class="col-md-4"><strong>Grand total:</strong> <span id="preview_grand_total">0.00</span></div>
                            </div>
                            <p class="mb-0 mt-1 text-muted small">Totals are recalculated on save and rolled up to the project.</p>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitFinancialImpactBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Financial Impact" }}');

    var formId = 'delayFinancialImpactForm';
    var url = "{{ getProjectUrl('insert_update_financial_impact') }}";

    function parseAmount(val) {
        var n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function updateCostPreview() {
        var direct = parseAmount($('#labor_overrun').val())
            + parseAmount($('#material_cost_overrun').val())
            + parseAmount($('#contractor_claims').val())
            + parseAmount($('#equipment_storage_charges').val());
        var opportunity = parseAmount($('#delayed_admissions').val())
            + parseAmount($('#delayed_surgeries').val())
            + parseAmount($('#delayed_revenue').val())
            + parseAmount($('#lost_operational_days').val());
        $('#preview_direct_total').text(direct.toFixed(2));
        $('#preview_opportunity_total').text(opportunity.toFixed(2));
        $('#preview_grand_total').text((direct + opportunity).toFixed(2));
    }

    $('.cost-input').on('input change', updateCostPreview);
    updateCostPreview();

    $('#submitFinancialImpactBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitFinancialImpactBtn');
        var panelReloadUrl = $('#panel_reload_url').val();

        ajaxRequestWithPromise(url, formData, 'insert_update_financial_impact', 0, '', $submitButton).then(function(res) {
            if (res.error == 0 || res.error == "0") {
                parseFormErrors(res, 'success');
                if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                    closeSideLayout();
                    if (panelReloadUrl && typeof reloadFinancialImpactPanel === 'function') {
                        reloadFinancialImpactPanel(panelReloadUrl);
                    } else if (typeof reloadDataTable === 'function') {
                        reloadDataTable();
                    }
                }
            } else {
                parseFormErrors(res, 'error');
            }
        }).catch(function() {
            parseFormErrors({ error: 1, msg: { 0: 'An error occurred. Please try again.' } }, 'error');
        });
    });

    if ($.fn.select2) {
        $('.dd-select').select2({ dropdownParent: $("#offcanvasRight"), width: '100%' });
    }
});
</script>
