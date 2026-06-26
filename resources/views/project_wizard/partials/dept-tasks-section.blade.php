@php
    $projectDepartmentId = (int) ($projectDepartmentId ?? 0);
    $projectId = (int) ($projectId ?? 0);
    $tasks = $tasks ?? [];
    $linkableDepartments = $linkableDepartments ?? [];
    $taskStatusLabels = $taskStatusLabels ?? [];
    $readOnly = !empty($readOnly);
    $mode = $mode ?? 'setup';
    $projectPlannedStart = $projectPlannedStart ?? '';
    $sectionId = 'deptTasks_' . $projectDepartmentId . '_' . $mode;
@endphp
<div class="dept-tasks-section card border-0 bg-light mt-3"
    id="{{ $sectionId }}"
    data-project-department-id="{{ $projectDepartmentId }}"
    data-project-id="{{ $projectId }}"
    data-mode="{{ $mode }}"
    data-read-only="{{ $readOnly ? '1' : '0' }}"
    data-project-min-start="{{ $projectPlannedStart }}"
    data-save-url="{{ getProjectUrl('save_project_department_task') }}"
    data-delete-url="{{ getProjectUrl('delete_project_department_task') }}"
    data-list-url="{{ getProjectUrl('get_project_department_tasks') }}"
    data-status-url="{{ getProjectUrl('update_project_department_task_status') }}"
    data-linked-panel-url="{{ getProjectUrl('projects/wizard/dept-tasks') }}">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="mb-0">Tasks</h6>
                <p class="text-muted small mb-0">Each row is one task. Optionally tie a task to another department module.</p>
            </div>
            @if(!$readOnly && $projectDepartmentId > 0)
            <button type="button" class="btn btn-outline-primary btn-sm btn-add-dept-task">
                <i class="ri-add-line"></i> Add task
            </button>
            @endif
        </div>

        @if($projectDepartmentId <= 0)
        <div class="alert alert-warning small mb-0">Save department configuration first to add tasks.</div>
        @else
        <div class="dept-task-form-wrap mb-3" style="display:none;">
            <div class="dept-task-form planned-date-range border rounded bg-white p-3"
                data-project-min-start="{{ $projectPlannedStart }}">
                <input type="hidden" name="project_department_id" value="{{ $projectDepartmentId }}">
                <input type="hidden" name="id" value="">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="small text-muted required-label">Task name</label>
                        <input type="text" class="form-control form-control-sm" name="task_name" maxlength="255" placeholder="e.g. Fire safety clearance">
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted">Department</label>
                        <select class="form-control form-control-sm dept-task-linked-dept-select" name="linked_department_id">
                            <option value="">Not linked</option>
                            @foreach($linkableDepartments as $dept)
                            <option value="{{ $dept['id'] }}">{{ $dept['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted d-block mt-1" style="font-size:11px;">Optional — opens that department&apos;s workflow when set</span>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Start date</label>
                        <input type="text" class="form-control form-control-sm planned-date-input js-planned-start" name="planned_start_date" autocomplete="off" placeholder="yyyy-mm-dd">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">End date</label>
                        <input type="text" class="form-control form-control-sm planned-date-input js-planned-end" name="planned_end_date" data-label="End date" autocomplete="off" placeholder="yyyy-mm-dd">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted">Status</label>
                        <select class="form-control form-control-sm" name="task_status">
                            @foreach($taskStatusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-submit btn-save-dept-task">Save</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel-dept-task">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="dept-task-list">
            @if(empty($tasks))
            <p class="text-muted small mb-0 dept-task-empty">No tasks yet.</p>
            @else
            @foreach($tasks as $task)
            @include('project_wizard.partials.dept-task-list-item', [
                'task' => $task,
                'readOnly' => $readOnly,
                'showQuickStatus' => ($mode === 'execution' && !$readOnly),
            ])
            @endforeach
            @endif
        </div>
        @endif
    </div>
</div>
