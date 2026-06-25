<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $project = $data['project'] ?? '';
                $projectTypes = $data['project_types'] ?? [];
                $zones = $data['zones'] ?? [];
                $hospitals = $data['hospitals'] ?? [];
                $statuses = $data['project_statuses'] ?? [];
                $selectedZoneId = $project['zone_id'] ?? '';
                $selectedHospitalId = $project['hospital_id'] ?? '';
                if ($selectedHospitalId === '' && !empty($project['hospital_name']) && !empty($hospitals)) {
                    foreach ($hospitals as $hospital) {
                        if (($hospital['label'] ?? '') === $project['hospital_name']) {
                            $selectedHospitalId = $hospital['id'];
                            break;
                        }
                    }
                }
                if ($selectedZoneId === '' && !empty($project['zone_department']) && !empty($zones)) {
                    foreach ($zones as $zone) {
                        if (($zone['label'] ?? '') === $project['zone_department']) {
                            $selectedZoneId = $zone['id'];
                            break;
                        }
                    }
                }
                $projectCodeValue = $project['project_code'] ?? ($data['suggested_project_code'] ?? '');
                @endphp
                <form class="custom-validations" id="projectForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="project_id" id="project_id"
                        value="{{ $project['project_id'] ?? $project['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="project_code" class="required-label">Project ID</label>
                                <input type="text" class="form-control required" name="project_code" id="project_code"
                                    value="{{ $projectCodeValue }}" placeholder="Auto-generated — edit if needed" />
                                @if(empty($project['project_id'] ?? $project['id'] ?? ''))
                                <small class="text-muted">Auto-generated. You can change it before saving.</small>
                                @endif
                            </div>
                            <div class="col-md-8 mb-2">
                                <label for="project_name" class="required-label">Project Name</label>
                                <input type="text" class="form-control required" name="project_name" id="project_name"
                                    value="{{ $project['project_name'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="hospital_id">Hospital Name</label>
                                <select name="hospital_id" id="hospital_id" class="form-control dd-select">
                                    <option value="">Select hospital</option>
                                    @foreach($hospitals as $hospital)
                                    <option value="{{ $hospital['id'] }}" @if($selectedHospitalId == $hospital['id']) selected @endif>{{ $hospital['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="project_type_id">Project Type</label>
                                <select name="project_type_id" id="project_type_id" class="form-control dd-select">
                                    <option value="">Select type</option>
                                    @foreach($projectTypes as $type)
                                    <option value="{{ $type['id'] }}" @if(($project['project_type_id'] ?? '') == $type['id']) selected @endif>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="project_status">Project Status</label>
                                <select name="project_status" id="project_status" class="form-control dd-select">
                                    @foreach($statuses as $st)
                                    <option value="{{ $st['value'] }}" @if(($project['project_status'] ?? 'active') == $st['value']) selected @endif>{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="area_facility">Area / Facility</label>
                                <input type="text" class="form-control" name="area_facility" id="area_facility"
                                    value="{{ $project['area_facility'] ?? '' }}" placeholder="e.g. Emergency, MRI Room" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="zone_id">Zone</label>
                                <select name="zone_id" id="zone_id" class="form-control dd-select">
                                    <option value="">Select zone</option>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone['id'] }}" @if($selectedZoneId == $zone['id']) selected @endif>{{ $zone['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="location">Location</label>
                                <input type="text" class="form-control" name="location" id="location"
                                    value="{{ $project['location'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="project_spoc_name">Project SPOC</label>
                                <input type="text" class="form-control" name="project_spoc_name" id="project_spoc_name"
                                    value="{{ $project['project_spoc_name'] ?? $project['responsibility_name'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="contractor_name">Contractor</label>
                                <input type="text" class="form-control" name="contractor_name" id="contractor_name"
                                    value="{{ $project['contractor_name'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="planned_start_date">Planned Start Date</label>
                                <input type="date" class="form-control" name="planned_start_date" id="planned_start_date"
                                    value="{{ isset($project['planned_start_date']) ? date('Y-m-d', strtotime($project['planned_start_date'])) : '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="planned_completion_date">Planned Completion Date</label>
                                <input type="date" class="form-control" name="planned_completion_date" id="planned_completion_date"
                                    value="{{ isset($project['planned_completion_date']) ? date('Y-m-d', strtotime($project['planned_completion_date'])) : '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="target_revised_completion_date">Target Revised Completion</label>
                                <input type="date" class="form-control" name="target_revised_completion_date" id="target_revised_completion_date"
                                    value="{{ isset($project['target_revised_completion_date']) ? date('Y-m-d', strtotime($project['target_revised_completion_date'])) : '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="actual_completion_date">Actual Completion Date</label>
                                <input type="date" class="form-control" name="actual_completion_date" id="actual_completion_date"
                                    value="{{ isset($project['actual_completion_date']) ? date('Y-m-d', strtotime($project['actual_completion_date'])) : '' }}" />
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="project_scope">Project Scope</label>
                                <textarea class="form-control" name="project_scope" id="project_scope" rows="3">{{ $project['project_scope'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitProjectBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Project" }}');

    var formId = 'projectForm';
    var url = "{{ getProjectUrl('insert_update_project') }}";

    $('#submitProjectBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitProjectBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_project', 0, '', $submitButton).then(function(res) {
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
