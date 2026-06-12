@php
    $ctx = $data['ctx'];
    $delays = $data['delays'];
    $mitigations = $data['mitigations'];
    $rootCauses = $data['root_causes'];
    $statuses = $data['register_statuses'];
    $encPd = Crypt::encrypt($ctx['id']);
@endphp
<div class="sidelayout-panel">
    <div class="sidelayout-context">{{ $ctx['project_code'] }} — {{ $ctx['department_name'] }}</div>

    @foreach($delays as $delay)
    <div class="card border">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <strong class="small">{{ $delay->delay_title }}</strong>
                <span class="badge bg-warning text-dark flex-shrink-0">{{ ucfirst($delay->severity ?? '') }} · {{ (int)$delay->delay_days }}d</span>
            </div>
            @if($delay->specific_event_description)
            <p class="small text-muted mb-2 mt-1">{{ $delay->specific_event_description }}</p>
            @endif
            @if(isset($mitigations[$delay->id]))
            <ul class="small mb-0 ps-3">
                @foreach($mitigations[$delay->id] as $m)
                <li>{{ $m->mitigation_action }} <em>({{ $m->current_status ?? 'open' }})</em></li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
    @endforeach

    <h6>Log Delay</h6>
    <form id="wizardDelayForm" class="custom-validations">
        @csrf
        <input type="hidden" name="project_department_id" value="{{ $ctx['id'] }}">
        <div class="row">
            <div class="col-12 mb-2">
                <label class="required-label">Delay Title</label>
                <input type="text" class="form-control required" name="delay_title">
            </div>
            <div class="col-12 mb-2">
                <label>Primary Delay Driver(s)</label>
                <textarea class="form-control" name="primary_delay_drivers" rows="2"></textarea>
            </div>
            <div class="col-12 mb-2">
                <label>Event Description</label>
                <textarea class="form-control" name="specific_event_description" rows="2"></textarea>
            </div>
            <div class="col-md-6 mb-2">
                <label>Start Date</label>
                <input type="date" class="form-control" name="delay_start_date">
            </div>
            <div class="col-md-6 mb-2">
                <label>End Date</label>
                <input type="date" class="form-control" name="delay_end_date">
            </div>
            <div class="col-md-6 mb-2">
                <label>Root Cause</label>
                <select name="root_cause_id" class="form-control dd-select">
                    <option value="">Select</option>
                    @foreach($rootCauses as $rc)
                    <option value="{{ $rc->id }}">{{ $rc->cause_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label>Status</label>
                <select name="register_status" class="form-control dd-select">
                    @foreach($statuses as $st)
                    <option value="{{ $st['value'] }}">{{ $st['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="licensing_openings_affected" value="1" id="licensing_{{ $ctx['id'] }}">
                    <label class="form-check-label" for="licensing_{{ $ctx['id'] }}">Licensing / openings affected (showstopper)</label>
                </div>
            </div>
        </div>
        <div class="sidelayout-actions">
            <button type="button" class="btn btn-submit btn-sm" id="saveWizardDelayBtn">Save Delay</button>
        </div>
    </form>

    <hr>
    <h6>Add Mitigation</h6>
    <form id="wizardMitigationForm">
        @csrf
        <div class="row">
            <div class="col-12 mb-2">
                <label class="required-label">For Delay Entry</label>
                <select name="delay_register_id" class="form-control dd-select required">
                    <option value="">Select delay</option>
                    @foreach($delays as $delay)
                    <option value="{{ $delay->id }}">{{ $delay->delay_title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 mb-2">
                <label class="required-label">Mitigation Action</label>
                <textarea class="form-control required" name="mitigation_action" rows="2"></textarea>
            </div>
            <div class="col-md-6 mb-2">
                <label>Owner</label>
                <input type="text" class="form-control" name="action_owner">
            </div>
            <div class="col-md-6 mb-2">
                <label>Target Date</label>
                <input type="date" class="form-control" name="target_completion_date">
            </div>
        </div>
        <div class="sidelayout-actions">
            <button type="button" class="btn btn-submit btn-sm" id="saveWizardMitigationBtn">Save Mitigation</button>
        </div>
    </form>
</div>

<script>
$(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle }}');
    if ($.fn.select2) { $('.dd-select').select2({ dropdownParent: $("#offcanvasRight"), width: '100%' }); }

    var panelUrl = "{{ getProjectUrl('projects/wizard/panel/delay/' . $encPd) }}";
    function reloadPanel() {
        openSideLayout({}, panelUrl, '{{ $pageTitle }}');
    }

    $('#saveWizardDelayBtn').on('click', function() {
        var $btn = $(this);
        ajaxRequestWithPromise("{{ getProjectUrl('wizard_save_delay') }}", $('#wizardDelayForm').serialize(), 'wizard_save_delay', 0, '', $btn)
            .then(function(res) {
                parseFormErrors(res, res.error == 0 ? 'success' : 'error');
                if (res.error == 0) reloadPanel();
            });
    });

    $('#saveWizardMitigationBtn').on('click', function() {
        var $btn = $(this);
        ajaxRequestWithPromise("{{ getProjectUrl('wizard_save_mitigation') }}", $('#wizardMitigationForm').serialize(), 'wizard_save_mitigation', 0, '', $btn)
            .then(function(res) {
                parseFormErrors(res, res.error == 0 ? 'success' : 'error');
                if (res.error == 0) reloadPanel();
            });
    });
});
</script>
