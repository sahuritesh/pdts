@php
    $department = $data['department'] ?? [];
    $project = $data['project'] ?? [];
    $projectId = (int) ($data['project_id'] ?? 0);
    $projectDepartmentId = (int) ($department['id'] ?? 0);
    $tasks = $data['tasks'] ?? [];
    $linkableDepartments = $data['linkable_departments'] ?? [];
    $taskStatusLabels = $data['task_status_labels'] ?? [];
    $readOnly = !empty($data['read_only']);
@endphp
<div class="sidelayout-panel dept-linked-tasks-panel">
    <div class="sidelayout-context mb-3">
        <h6 class="mb-1">{{ $project['project_code'] ?? '' }} — {{ $project['project_name'] ?? '' }}</h6>
        <p class="text-muted small mb-0">
            Linked department: <strong>{{ $department['department_name'] ?? '' }}</strong>
        </p>
    </div>

    @include('project_wizard.partials.dept-tasks-section', [
        'projectDepartmentId' => $projectDepartmentId,
        'projectId' => $projectId,
        'tasks' => $tasks,
        'linkableDepartments' => $linkableDepartments,
        'taskStatusLabels' => $taskStatusLabels,
        'readOnly' => $readOnly,
        'mode' => 'linked',
        'projectPlannedStart' => !empty($department['planned_start_date']) ? date('Y-m-d', strtotime($department['planned_start_date'])) : '',
    ])
</div>
<script>
(function initDeptLinkedTasksPanel() {
    $('.sidelayoutTitle').html(@json($pageTitle ?? 'Department Tasks'));
    if (typeof ProjectDepartmentTasks !== 'undefined') {
        ProjectDepartmentTasks.bind($('.dept-linked-tasks-panel'));
    }
})();
</script>
