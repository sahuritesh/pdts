@php
    $task = $task ?? [];
    $readOnly = !empty($readOnly);
    $showQuickStatus = !empty($showQuickStatus);
    $taskName = trim($task['task_name'] ?? '') ?: ($task['display_name'] ?? 'Task');
    $linkedDeptName = trim($task['linked_department_name'] ?? '');
    $hasDeptLink = !empty($task['linked_department_id']) && $linkedDeptName !== '';
    $start = $task['planned_start_date'] ?? '';
    $end = $task['planned_end_date'] ?? '';
    $dateLabel = ($start ?: '—') . ' → ' . ($end ?: '—');
@endphp
<div class="dept-task-item d-flex align-items-center gap-2 border rounded bg-white p-2 mb-2"
    data-task-id="{{ $task['id'] ?? 0 }}"
    data-task-status="{{ $task['task_status'] ?? 'not_started' }}"
    data-linked-pd-id="{{ $task['linked_project_department_token'] ?? '' }}">
    <div class="dept-task-item-main flex-grow-1 min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <strong>{{ $taskName }}</strong>
            @if($hasDeptLink)
            <span class="badge bg-info-subtle text-info">{{ $linkedDeptName }}</span>
            @endif
            {!! $task['status_badge_html'] ?? '' !!}
        </div>
        <div class="text-muted small mt-1">
            <i class="ri-calendar-line"></i> {{ $dateLabel }}
        </div>
    </div>
    @if($showQuickStatus)
    @include('project_wizard.partials.dept-task-quick-status', ['task' => $task])
    @endif
    <div class="dept-task-item-actions d-flex gap-1 flex-shrink-0">
        @if($hasDeptLink && !empty($task['linked_project_department_token']) && !$showQuickStatus)
        <button type="button" class="btn btn-sm btn-outline-primary btn-open-linked-dept-tasks" title="Open {{ $linkedDeptName }} workflow">
            <i class="ri-external-link-line"></i>
        </button>
        @endif
        @if(!$readOnly)
        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-dept-task" title="Edit task">
            <i class="ri-edit-line"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-dept-task" title="Remove task">
            <i class="ri-delete-bin-line"></i>
        </button>
        @endif
    </div>
</div>
