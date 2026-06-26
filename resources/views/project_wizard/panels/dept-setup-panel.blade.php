@php
    $row = $data['row'] ?? [];
    $projectId = (int) ($data['project_id'] ?? 0);
    $departmentId = (int) ($data['department_id'] ?? 0);
    $isLast = !empty($data['is_last']);
    $spocUsers = $data['spoc_users'] ?? [];
    $pd = $row;
    $sequentialEnforced = !empty($data['sequential_enforced']);
    $sequentialMinStart = $data['sequential_min_start'] ?? '';
    $sequentialPrevName = $data['sequential_prev_name'] ?? '';
    $projectPlannedStart = $data['project_planned_start'] ?? '';
    $projectDepartmentId = (int) ($data['project_department_id'] ?? ($row['id'] ?? 0));
    $deptTasks = $data['tasks'] ?? [];
    $linkableDepartments = $data['linkable_departments'] ?? [];
    $taskStatusLabels = $data['task_status_labels'] ?? [];
@endphp
<div class="sidelayout-panel dept-setup-panel">
    <div class="sidelayout-context mb-3">
        <strong>{{ $row['department_name'] ?? 'Department' }}</strong>
        <p class="text-muted small mb-0">Assign the department SPOC, planned dates, and workflow options before execution.</p>
    </div>

    <form id="wizardDeptSetupForm">
        @csrf
        <input type="hidden" name="project_id" value="{{ $projectId }}">
        <input type="hidden" name="department_id" value="{{ $departmentId }}">
        <input type="hidden" name="department_order" id="deptSetupOrder" value="">
        <input type="hidden" name="department_parallel" id="deptSetupParallel" value="">

        @include('project_wizard.partials.spoc-user-field', ['pd' => $pd, 'spocUsers' => $spocUsers])

        <div class="dept-meta-form planned-date-range row g-2 mt-3 mb-0"
            data-seq-enforced="{{ $sequentialEnforced && $sequentialMinStart !== '' ? '1' : '0' }}"
            data-seq-min-start="{{ $sequentialMinStart }}"
            data-seq-prev-name="{{ e($sequentialPrevName) }}"
            data-project-min-start="{{ $projectPlannedStart }}">
            @include('project_wizard.partials.dept-planned-date-fields', ['pd' => $pd])
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

    @include('project_wizard.partials.dept-tasks-section', [
        'projectDepartmentId' => $projectDepartmentId,
        'projectId' => $projectId,
        'tasks' => $deptTasks,
        'linkableDepartments' => $linkableDepartments,
        'taskStatusLabels' => $taskStatusLabels,
        'readOnly' => false,
        'mode' => 'setup',
        'projectPlannedStart' => $projectPlannedStart,
    ])
</div>
<script>
(function initDeptSetupPanelSideLayout() {
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
        bindPlannedDateRangeInputs($('.dept-setup-panel'));
    }

    if (typeof ProjectDepartmentTasks !== 'undefined') {
        ProjectDepartmentTasks.bind($('#dynamicSideLayoutContent'));
    }

    $(document).off('click.deptSetupSave', '#saveDeptSetupBtn').on('click.deptSetupSave', '#saveDeptSetupBtn', function() {
        var $btn = $(this);
        if (typeof syncProjectDepartmentOrder === 'function') {
            syncProjectDepartmentOrder();
            $('#deptSetupOrder').val($('#department_order').val());
            $('#deptSetupParallel').val($('#department_parallel').val());
        }

        if (typeof validatePlannedDateRangesInScope === 'function' && !validatePlannedDateRangesInScope($('#wizardDeptSetupForm'))) {
            return;
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
})();
</script>
