@php
    $current = $task['task_status'] ?? 'not_started';
    $quickStatuses = [
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
    ];
@endphp
<div class="dept-task-status-quick btn-group btn-group-sm flex-wrap" role="group" aria-label="Task status">
    @foreach($quickStatuses as $statusKey => $statusLabel)
    <button type="button"
        class="btn btn-dept-task-set-status {{ $current === $statusKey ? 'btn-primary' : 'btn-outline-secondary' }}"
        data-status="{{ $statusKey }}"
        @if($current === $statusKey) disabled @endif>
        {{ $statusLabel }}
    </button>
    @endforeach
</div>
