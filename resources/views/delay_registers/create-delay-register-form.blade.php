<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $register = $data['register'] ?? '';
                $projects = $data['projects'] ?? [];
                $categories = $data['categories'] ?? [];
                $rootCauses = $data['root_causes'] ?? [];
                $statuses = $data['register_statuses'] ?? [];
                $isEdit = !empty($register);
                @endphp
                <form class="custom-validations" id="delayRegisterForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="delay_register_id" id="delay_register_id"
                        value="{{ $register['delay_register_id'] ?? $register['id'] ?? '' }}">
                    <div class="formStyle">
                        @if($isEdit)
                        <div class="alert alert-light border mb-3 py-2">
                            <div class="row small">
                                <div class="col-md-3"><strong>Delay days:</strong> {{ (int)($register['delay_days'] ?? 0) }}</div>
                                <div class="col-md-3"><strong>Severity:</strong> {{ ucfirst($register['severity'] ?? '') }}</div>
                                <div class="col-md-3"><strong>Alert:</strong> {{ ucfirst($register['alert_level'] ?? '') }}</div>
                                <div class="col-md-3"><strong>Escalation:</strong> L{{ (int)($register['escalation_level'] ?? 0) }}</div>
                            </div>
                            <p class="mb-0 mt-1 text-muted small">Recalculated automatically when you save.</p>
                        </div>
                        @else
                        <p class="text-muted small mb-3">Delay days, severity, alert level, and escalation are calculated automatically on save.</p>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="project_id" class="required-label">Project</label>
                                <select name="project_id" id="project_id" class="form-control dd-select required">
                                    <option value="">Select project</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project['id'] }}" @if(($register['project_id'] ?? '') == $project['id']) selected @endif>{{ $project['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="delay_category_id">Delay Category</label>
                                <select name="delay_category_id" id="delay_category_id" class="form-control dd-select">
                                    <option value="">Select category</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        data-description="{{ e($cat->description ?? '') }}"
                                        @if(($register['delay_category_id'] ?? '') == $cat->id) selected @endif>{{ $cat->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="delay_title" class="required-label">Delay Title / Specific Event</label>
                                <input type="text" class="form-control required" name="delay_title" id="delay_title"
                                    value="{{ $register['delay_title'] ?? '' }}"
                                    placeholder="Brief title for this delay entry" />
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="primary_delay_drivers">Primary Delay Driver(s)</label>
                                <textarea class="form-control" name="primary_delay_drivers" id="primary_delay_drivers" rows="2"
                                    placeholder="Main driver(s) from Excel framework">{{ $register['primary_delay_drivers'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="specific_event_description">Specific Event Description</label>
                                <textarea class="form-control" name="specific_event_description" id="specific_event_description" rows="3">{{ $register['specific_event_description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="impacted_task">Impacted Task / Critical Path</label>
                                <input type="text" class="form-control" name="impacted_task" id="impacted_task"
                                    value="{{ $register['impacted_task'] ?? '' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="responsibility_name">Responsibility (Name / Party)</label>
                                <input type="text" class="form-control" name="responsibility_name" id="responsibility_name"
                                    value="{{ $register['responsibility_name'] ?? '' }}" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="root_cause_id">Root Cause</label>
                                <select name="root_cause_id" id="root_cause_id" class="form-control dd-select">
                                    <option value="">Select root cause</option>
                                    @foreach($rootCauses as $rc)
                                    <option value="{{ $rc->id }}"
                                        data-label="{{ e($rc->cause_name) }}"
                                        @if(($register['root_cause_id'] ?? '') == $rc->id) selected @endif>{{ $rc->cause_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="root_cause_label">Root Cause (Other / Notes)</label>
                                <input type="text" class="form-control" name="root_cause_label" id="root_cause_label"
                                    value="{{ $register['root_cause_label'] ?? '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="delay_start_date">Delay Start Date</label>
                                <input type="date" class="form-control" name="delay_start_date" id="delay_start_date"
                                    value="{{ isset($register['delay_start_date']) ? date('Y-m-d', strtotime($register['delay_start_date'])) : '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="delay_end_date">Delay End Date</label>
                                <input type="date" class="form-control" name="delay_end_date" id="delay_end_date"
                                    value="{{ isset($register['delay_end_date']) ? date('Y-m-d', strtotime($register['delay_end_date'])) : '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="target_revised_completion_date">Target Revised Completion</label>
                                <input type="date" class="form-control" name="target_revised_completion_date" id="target_revised_completion_date"
                                    value="{{ isset($register['target_revised_completion_date']) ? date('Y-m-d', strtotime($register['target_revised_completion_date'])) : '' }}" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="register_status">Register Status</label>
                                <select name="register_status" id="register_status" class="form-control dd-select">
                                    @foreach($statuses as $st)
                                    <option value="{{ $st['value'] }}" @if(($register['register_status'] ?? 'open') == $st['value']) selected @endif>{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 mb-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="licensing_openings_affected" id="licensing_openings_affected" value="1"
                                        @if(!empty($register['licensing_openings_affected'])) checked @endif />
                                    <label class="form-check-label" for="licensing_openings_affected">
                                        Licensing / openings affected (Showstopper)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="delay_description">Additional Notes</label>
                                <textarea class="form-control" name="delay_description" id="delay_description" rows="2">{{ $register['delay_description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitDelayRegisterBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Delay Register" }}');

    var formId = 'delayRegisterForm';
    var url = "{{ getProjectUrl('insert_update_delay_register') }}";

    $('#delay_category_id').on('change', function() {
        var desc = $(this).find(':selected').data('description') || '';
        if (desc && !$('#primary_delay_drivers').val()) {
            $('#primary_delay_drivers').val(desc);
        }
    });

    $('#root_cause_id').on('change', function() {
        var label = $(this).find(':selected').data('label') || '';
        if (label) {
            $('#root_cause_label').val(label);
        }
    });

    $('#submitDelayRegisterBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitDelayRegisterBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_delay_register', 0, '', $submitButton).then(function(res) {
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
