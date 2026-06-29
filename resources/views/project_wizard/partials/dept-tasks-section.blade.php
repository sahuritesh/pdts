@php
    $projectDepartmentId = (int) ($projectDepartmentId ?? 0);
    $projectId = (int) ($projectId ?? 0);
    $tasks = $tasks ?? [];
    $linkableDepartments = $linkableDepartments ?? [];
    $taskStatusLabels = $taskStatusLabels ?? [];
    $readOnly = !empty($readOnly);
    $mode = $mode ?? 'setup';
    $isExecution = ($mode === 'execution');
    $canManageTasks = !$readOnly && !$isExecution && $projectDepartmentId > 0;
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
    data-linked-panel-url="{{ getProjectUrl('projects/wizard/dept-tasks') }}"
    data-task-search-url="{{ getProjectUrl('search_master_tasks') }}"
    data-task-quick-create-url="{{ getProjectUrl('quick_create_master_task') }}">
    <div class="card-body p-3 custome-box">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="mb-3">Tasks</h6>
                @if($isExecution)
                <p class="text-muted small mb-0">Tasks configured for this department. Use Configure in Step 2 to add or change tasks.</p>
                @else
                <p class="text-muted small mb-0">Pick a task from the catalog and optionally tie it to another department module.</p>
                @endif
            </div>
            @if($canManageTasks)
            <button type="button" class="btn btn-outline-primary btn-sm btn-add-dept-task save-dept-meta d-flex">
                <i class="ri-add-line"></i> Add task
            </button>
            @endif
        </div>

        @if($projectDepartmentId <= 0)
        <div class="alert alert-warning small mb-0">Save department configuration first to add tasks.</div>
        @else
        <div class="dept-task-form-wrap mb-3" style="display:none;">
            <div class="dept-task-form planned-date-range task-form-modern border rounded bg-white p-3"
                data-project-min-start="{{ $projectPlannedStart }}">
                <input type="hidden" name="project_department_id" value="{{ $projectDepartmentId }}">
                <input type="hidden" name="id" value="">
                <div class="row g-2 align-items-end">
                    @include('project_wizard.partials.task-master-select', [
                        'fieldName' => 'task_id',
                        'searchUrl' => getProjectUrl('search_master_tasks'),
                        'quickCreateUrl' => getProjectUrl('quick_create_master_task'),
                    ])
                    <div class="col-md-4">
                        <label class="small text-muted">Department</label>
                        <select class="form-control form-control-sm dept-task-linked-dept-select" name="linked_department_id">
                            <option value="">Not linked</option>
                            @foreach($linkableDepartments as $dept)
                            <option value="{{ $dept['id'] }}">{{ $dept['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted mt-1 d-block" style="font-size:10px; margin-bottom: 4px !important;">Optional-opens that department&apos;s workflow when set</span>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Start date</label>
                        <input type="text" class="form-control form-control-sm planned-date-input js-planned-start" name="planned_start_date" autocomplete="off" placeholder="yyyy-mm-dd">
                    <span>&nbsp;</span>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">End date</label>
                        <input type="text" class="form-control form-control-sm planned-date-input js-planned-end" name="planned_end_date" data-label="End date" autocomplete="off" placeholder="yyyy-mm-dd">
                    <span>&nbsp;</span>
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


<style>
/* ===== Parent ===== */

.task-form-modern{
    border:1px solid #dbe4ee !important;
    border-radius:16px !important;
    background:#ffffff;
    padding:22px !important;

    box-shadow:
        0 8px 30px rgba(15,23,42,.06);

    transition:.35s;
}

.task-form-modern:hover{

    border-color:#2563eb !important;

    box-shadow:
        0 12px 35px rgba(37,99,235,.12);
}

/* ===== Row Alignment ===== */

.task-form-modern .row{

    align-items:flex-end;

    row-gap:18px;
}

/* ===== Labels ===== */

.task-form-modern label{

    display:block;

    font-size:13px;

    font-weight:600;

    color:#475569 !important;

    margin-bottom:8px;
}

/* ===== Inputs ===== */

.task-form-modern .form-control,
.task-form-modern .form-select{

    height:42px;

    border:1px solid #cfd8e3;

    border-radius:10px;

    font-size:14px;

    box-shadow:none;

    transition:.3s;
}

.task-form-modern .form-control:focus,
.task-form-modern .form-select:focus{

    border-color:#2563eb;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.10);
}

/* ===== Helper Text ===== */

.task-form-modern .text-muted{

    font-size:11px;

    color:#64748b !important;

    margin-top:5px;
}

/* ===== Buttons ===== */

.task-form-modern .btn-save-dept-task,
.task-form-modern .btn-cancel-dept-task{

    height:42px;

    min-width:110px;

    border-radius:10px;

    font-weight:600;
}

/* ===== Button Alignment ===== */

.task-form-modern .col-md-3.d-flex{

    align-items:flex-end;

    justify-content:flex-start;

    gap:10px;
}

/* ===== Mobile ===== */

@media(max-width:768px){

.task-form-modern .col-md-3.d-flex{

    flex-direction:column;

    width:100%;
}

.task-form-modern .btn{

    width:100%;
}

}
    </style>