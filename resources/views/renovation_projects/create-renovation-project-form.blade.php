<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $project = $data['project'] ?? '';
                $renovationTypes = $data['renovation_types'] ?? [];
                $statuses = $data['project_statuses'] ?? [];
                $escalationStatuses = $data['escalation_statuses'] ?? [];
                @endphp
                <form class="custom-validations" id="renovationProjectForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="renovation_project_id" id="renovation_project_id"
                        value="{{ $project['renovation_project_id'] ?? $project['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="project_code" class="required-label">Project ID</label>
                                <input type="text" class="form-control required" name="project_code" id="project_code"
                                    value="{{ $project['project_code'] ?? '' }}" placeholder="e.g. REN-001" />
                            </div>
                            <div class="col-md-8 mb-2">
                                <label for="project_name" class="required-label">Project Name</label>
                                <input type="text" class="form-control required" name="project_name" id="project_name"
                                    value="{{ $project['project_name'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="renovation_type">Renovation Type</label>
                                <select name="renovation_type" id="renovation_type" class="form-control dd-select">
                                    <option value="">Select type</option>
                                    @foreach($renovationTypes as $type)
                                    <option value="{{ $type['value'] }}" @if(($project['renovation_type'] ?? '') == $type['value']) selected @endif>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="project_status">Project Status</label>
                                <select name="project_status" id="project_status" class="form-control dd-select">
                                    @foreach($statuses as $st)
                                    <option value="{{ $st['value'] }}" @if(($project['project_status'] ?? 'planned') == $st['value']) selected @endif>{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="escalation_status">Escalation Status</label>
                                <select name="escalation_status" id="escalation_status" class="form-control dd-select">
                                    @foreach($escalationStatuses as $st)
                                    <option value="{{ $st['value'] }}" @if(($project['escalation_status'] ?? 'none') == $st['value']) selected @endif>{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="zone_department_impacted">Zone / Department Impacted</label>
                                <input type="text" class="form-control" name="zone_department_impacted" id="zone_department_impacted"
                                    value="{{ $project['zone_department_impacted'] ?? '' }}" placeholder="e.g. ICU, OPD, Emergency" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="location">Location</label>
                                <input type="text" class="form-control" name="location" id="location"
                                    value="{{ $project['location'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="final_handover_date">Final Handover Date</label>
                                <input type="date" class="form-control" name="final_handover_date" id="final_handover_date"
                                    value="{{ isset($project['final_handover_date']) ? date('Y-m-d', strtotime($project['final_handover_date'])) : '' }}" />
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="project_scope">Project Scope</label>
                                <textarea class="form-control" name="project_scope" id="project_scope" rows="3">{{ $project['project_scope'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="remarks">Remarks</label>
                                <textarea class="form-control" name="remarks" id="remarks" rows="2">{{ $project['remarks'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitRenovationProjectBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Renovation Project" }}');

    var formId = 'renovationProjectForm';
    var url = "{{ getProjectUrl('insert_update_renovation_project') }}";

    $('#submitRenovationProjectBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitRenovationProjectBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_renovation_project', 0, '', $submitButton).then(function(res) {
            if (res.error == 0 || res.error == "0") {
                parseFormErrors(res, 'success');
                if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                    closeSideLayout();
                    reloadDataTable();
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
