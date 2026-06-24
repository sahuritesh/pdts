@php
    $pd = $data['department'] ?? [];
    $project = $data['project'] ?? [];
    $status = $pd['department_status'] ?? 'pending';
    $statusLabels = $data['status_labels'] ?? [];
    $encPdId = \Illuminate\Support\Facades\Crypt::encrypt($pd['id']);
    $badgeClass = ['pending'=>'secondary','start'=>'info','in_progress'=>'primary','delay'=>'warning','completed'=>'success'][$status] ?? 'secondary';
    $isPending = app(\App\Services\ProjectDepartmentService::class)->isDepartmentLocked($pd);
    $isProjectReadOnly = !empty($data['read_only']);
@endphp
<div class="spoc-task-detail">
    @if($isProjectReadOnly)
    <div class="alert alert-success small mb-3">
        <i class="ri-lock-line"></i> This project is completed and locked for editing.
    </div>
    @endif
    <div class="mb-3">
        <h6 class="mb-1">{{ $project['project_code'] ?? '' }} — {{ $project['project_name'] ?? '' }}</h6>
        <p class="text-muted small mb-2">
            {{ $project['hospital_name'] ?? '' }}
            @if(!empty($project['zone_name'])) · {{ $project['zone_name'] }} @endif
            @if(!empty($project['location_name'])) · {{ $project['location_name'] }} @endif
        </p>
        <div class="d-flex align-items-center gap-2">
            <strong>{{ $pd['department_name'] ?? '' }}</strong>
            <span class="badge bg-{{ $badgeClass }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
        </div>
    </div>

    @if(!$isPending)
    @include('project_wizard.partials.dept-workflow-fields', [
        'pd' => $pd,
        'status' => $status,
        'showSpoc' => false,
        'showRemarks' => true,
        'formMarginClass' => 'mb-3',
        'actionsMarginClass' => 'mb-0',
        'saveButtonLabel' => 'Save details',
        'inProgressLabel' => 'Mark In Progress',
        'completeLabel' => 'Mark Complete',
        'projectPlannedStart' => !empty($project['planned_start_date']) ? date('Y-m-d', strtotime($project['planned_start_date'])) : '',
    ])
    @else
    <div class="alert alert-info mb-0">This department is not yet active on the project. Contact the project manager when it is your turn to execute.</div>
    @endif
</div>

<script>
$(function() {
    $('.sidelayoutTitle').html(@json($pageTitle ?? 'My Department Task'));

    @if(!$isProjectReadOnly)
    bindDepartmentWorkflowHandlers({
        saveUrl: "{{ getProjectUrl('save_project_department') }}",
        statusUrl: "{{ getProjectUrl('update_department_status') }}",
        csrfToken: '{{ csrf_token() }}',
        reloadMode: 'sidelayout',
        sidelayoutUrl: "{{ getProjectUrl('spoc-tasks/view') }}/" + encodeURIComponent(@json($encPdId)),
        onSuccess: function() {
            setTimeout(function() {
                openSideLayout({}, "{{ getProjectUrl('spoc-tasks/view') }}/" + encodeURIComponent(@json($encPdId)), $('.sidelayoutTitle').text());
                if (typeof reloadDataTable === 'function') reloadDataTable();
            }, 500);
        }
    });
    @else
    $('.spoc-task-detail').find('input, select, textarea, button').prop('disabled', true);
    @endif
});
</script>
