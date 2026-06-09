<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $mitigation = $data['mitigation'] ?? '';
                $delayRegisters = $data['delay_registers'] ?? [];
                $statuses = $data['statuses'] ?? [];
                $presetDelayId = $data['preset_delay_register_id'] ?? '';
                $selectedDelayId = $mitigation['delay_register_id'] ?? $presetDelayId;
                $panelReloadUrl = $data['panel_reload_url'] ?? '';
                $lockDelay = !empty($presetDelayId) && empty($mitigation);
                @endphp
                <form class="custom-validations" id="delayMitigationForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="mitigation_id" id="mitigation_id"
                        value="{{ $mitigation['mitigation_id'] ?? $mitigation['id'] ?? '' }}">
                    <input type="hidden" name="panel_reload_url" id="panel_reload_url" value="{{ $panelReloadUrl }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-12 mb-2">
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
                            <div class="col-md-12 mb-2">
                                <label for="mitigation_action" class="required-label">Mitigation Action</label>
                                <textarea class="form-control required" name="mitigation_action" id="mitigation_action" rows="4"
                                    placeholder="Describe corrective action, change order, workaround, etc.">{{ $mitigation['mitigation_action'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="owner_name">Owner / Responsible Party</label>
                                <input type="text" class="form-control" name="owner_name" id="owner_name"
                                    value="{{ $mitigation['owner_name'] ?? '' }}" placeholder="Name or role" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="target_resolution_date">Target Resolution Date</label>
                                <input type="date" class="form-control" name="target_resolution_date" id="target_resolution_date"
                                    value="{{ isset($mitigation['target_resolution_date']) ? date('Y-m-d', strtotime($mitigation['target_resolution_date'])) : '' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="current_status">Status</label>
                                <select name="current_status" id="current_status" class="form-control dd-select">
                                    @foreach($statuses as $st)
                                    <option value="{{ $st['value'] }}" @if(($mitigation['current_status'] ?? 'open') == $st['value']) selected @endif>{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="resolution_remarks">Resolution Remarks</label>
                                <textarea class="form-control" name="resolution_remarks" id="resolution_remarks" rows="3"
                                    placeholder="Progress notes, escalation details, closure summary">{{ $mitigation['resolution_remarks'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitDelayMitigationBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Mitigation" }}');

    var formId = 'delayMitigationForm';
    var url = "{{ getProjectUrl('insert_update_mitigation') }}";

    $('#submitDelayMitigationBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitDelayMitigationBtn');
        var panelReloadUrl = $('#panel_reload_url').val();

        ajaxRequestWithPromise(url, formData, 'insert_update_mitigation', 0, '', $submitButton).then(function(res) {
            if (res.error == 0 || res.error == "0") {
                parseFormErrors(res, 'success');
                if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                    closeSideLayout();
                    if (panelReloadUrl && typeof reloadMitigationPanel === 'function') {
                        reloadMitigationPanel(panelReloadUrl);
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
