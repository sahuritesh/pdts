@php
    $row = $data['row'] ?? [];
    $projectId = (int) ($data['project_id'] ?? 0);
    $departmentId = (int) ($data['department_id'] ?? 0);
    $isLast = !empty($data['is_last']);
    $spocUsers = $data['spoc_users'] ?? [];
    $pd = $row;
    $plannedStartYmd = !empty($row['planned_start_date']) ? date('Y-m-d', strtotime($row['planned_start_date'])) : '';
    $plannedEndYmd = !empty($row['planned_end_date']) ? date('Y-m-d', strtotime($row['planned_end_date'])) : '';
@endphp
<div class="sidelayout-panel dept-setup-panel">
    <div class="sidelayout-context mb-3">
        <strong>{{ $row['department_name'] ?? 'Department' }}</strong>
        <p class="text-muted small mb-0">Assign the department SPOC and workflow options before execution begins.</p>
    </div>

    <form id="wizardDeptSetupForm">
        @csrf
        <input type="hidden" name="project_id" value="{{ $projectId }}">
        <input type="hidden" name="department_id" value="{{ $departmentId }}">
        <input type="hidden" name="department_order" id="deptSetupOrder" value="">
        <input type="hidden" name="department_parallel" id="deptSetupParallel" value="">

        @include('project_wizard.partials.spoc-user-field', ['pd' => $pd, 'spocUsers' => $spocUsers])

        <div class="row g-2 mt-2 planned-date-range">
            <div class="col-md-6">
                <label class="small text-muted">Planned start</label>
                <input type="text" class="form-control form-control-sm planned-date-input js-planned-start" name="planned_start_date" autocomplete="off" placeholder="yyyy-mm-dd"
                    value="{{ $plannedStartYmd }}">
            </div>
            <div class="col-md-6">
                <label class="small text-muted">Planned end</label>
                <input type="text" class="form-control form-control-sm planned-date-input js-planned-end" name="planned_end_date" data-label="Planned end" autocomplete="off" placeholder="yyyy-mm-dd"
                    value="{{ $plannedEndYmd }}" @if($plannedStartYmd === '') readonly @endif>
            </div>
        </div>

        @if(!$isLast)
        <div class="card card-body p-3 mt-3 bg-light border-0">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="allow_parallel_next" id="deptSetupAllowParallel" value="1"
                    @if(!empty($row['allow_parallel_next'])) checked @endif>
                <label class="form-check-label" for="deptSetupAllowParallel">
                    <strong>Allow next step parallely</strong>
                    <span class="d-block text-muted small">When checked, the next department may start before this one is completed.</span>
                </label>
            </div>
        </div>
        @else
        <p class="text-muted small mt-3 mb-0">Parallel option is not available for the last department in the sequence.</p>
        @endif

        <div class="sidelayout-actions mt-4">
            <button type="button" class="btn btn-submit btn-sm" id="saveDeptSetupBtn">
                <i class="ri-save-line me-1"></i> Save configuration
            </button>
        </div>
    </form>
</div>
<script>
$(function() {
    $('.sidelayoutTitle').html(@json($pageTitle ?? 'Configure Department'));

    if (typeof syncProjectDepartmentOrder === 'function') {
        syncProjectDepartmentOrder();
        $('#deptSetupOrder').val($('#department_order').val());
        $('#deptSetupParallel').val($('#department_parallel').val());
    }

    if ($.fn.select2 && $('.spoc-user-select').length && !$('.spoc-user-select').hasClass('select2-hidden-accessible')) {
        $('.spoc-user-select').select2({ width: '100%', placeholder: 'Select SPOC user', dropdownParent: $('.sidelayout-panel').closest('.offcanvas') });
    }

    if (typeof initSpocUserControls === 'function') {
        initSpocUserControls();
    }

    if (typeof bindPlannedDateRangeInputs === 'function') {
        bindPlannedDateRangeInputs($('#wizardDeptSetupForm'));
    }

    $('#saveDeptSetupBtn').on('click', function() {
        var $btn = $(this);
        if (typeof validatePlannedDateRangesInScope === 'function' &&
            !validatePlannedDateRangesInScope($('#wizardDeptSetupForm'))) {
            return;
        }
        if (typeof syncProjectDepartmentOrder === 'function') {
            syncProjectDepartmentOrder();
            $('#deptSetupOrder').val($('#department_order').val());
            $('#deptSetupParallel').val($('#department_parallel').val());
        }

        var $opt = $('#wizardDeptSetupForm .spoc-user-select option:selected');
        var spocName = ($('#wizardDeptSetupForm .spoc-name-hidden').val() || '').trim();
        if (!spocName && $opt.val()) {
            spocName = ($opt.text().split(' — ')[0] || $opt.text() || '').trim();
            $('#wizardDeptSetupForm .spoc-name-hidden').val(spocName);
        }

        var parallel = {};
        try { parallel = JSON.parse($('#deptSetupParallel').val() || '{}'); } catch (e) { parallel = {}; }
        parallel[@json($departmentId)] = $('#deptSetupAllowParallel').length && $('#deptSetupAllowParallel').is(':checked') ? 1 : 0;
        $('#deptSetupParallel').val(JSON.stringify(parallel));

        var payload = new FormData($('#wizardDeptSetupForm')[0]);
        ajaxRequestWithPromise("{{ getProjectUrl('save_wizard_department_setup') }}", payload, 'save_wizard_department_setup', 1, '', $btn)
            .then(function(res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); } catch (e) {}
                }
                parseFormErrors(res, (res.error == 0 || res.error == '0') ? 'success' : 'error');
                if (res.error == 0 || res.error == '0') {
                    if (!res.spoc_name && spocName) {
                        res.spoc_name = spocName;
                    }
                    if (typeof window.updateDeptSortableItem === 'function') {
                        window.updateDeptSortableItem(res);
                    }
                    setTimeout(function() {
                        if (typeof closeSideLayout === 'function') {
                            closeSideLayout();
                        }
                    }, 400);
                }
            });
    });
});
</script>
