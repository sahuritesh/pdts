@php
    $projectDepartmentId = (int) ($projectDepartmentId ?? 0);
    $taskCount = (int) ($taskCount ?? 0);
    $cssClass = trim($cssClass ?? '');
@endphp
<span class="badge rounded-pill dept-task-count-badge {{ $taskCount > 0 ? 'bg-primary-subtle text-primary' : 'bg-light text-muted' }}{{ $cssClass !== '' ? ' ' . $cssClass : '' }}"
    data-pd-id="{{ $projectDepartmentId }}"
    title="Tasks configured">
    <i class="ri-list-check-2"></i>
    <span class="dept-task-count-label">{{ $taskCount }} {{ $taskCount === 1 ? 'task' : 'tasks' }}</span>
</span>
