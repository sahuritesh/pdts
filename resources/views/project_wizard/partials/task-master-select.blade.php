@php
    $fieldName = $fieldName ?? 'task_id';
    $fieldId = $fieldId ?? ('task_master_' . uniqid());
    $selectedTaskId = (int) ($selectedTaskId ?? 0);
    $selectedTaskName = trim($selectedTaskName ?? '');
    $inlineCreate = !isset($inlineCreate) || $inlineCreate;
    $searchUrl = $searchUrl ?? getProjectUrl('search_master_tasks');
    $quickCreateUrl = $quickCreateUrl ?? getProjectUrl('quick_create_master_task');
    $colClass = trim($colClass ?? 'col-md-4');
@endphp
<div class="{{ $colClass }} task-master-select-wrap"
    data-search-url="{{ $searchUrl }}"
    data-quick-create-url="{{ $quickCreateUrl }}">
    <label for="{{ $fieldId }}" class="small text-muted required-label">Task</label>
    <select class="form-control form-control-sm task-master-select" name="{{ $fieldName }}" id="{{ $fieldId }}"
        data-selected-id="{{ $selectedTaskId }}"
        data-selected-text="{{ e($selectedTaskName) }}">
        @if($selectedTaskId > 0 && $selectedTaskName !== '')
        <option value="{{ $selectedTaskId }}" selected>{{ $selectedTaskName }}</option>
        @endif
    </select>
    @if($inlineCreate)
    <button type="button" class="btn btn-link btn-sm p-0 mt-1 task-master-toggle-create">+ Create new task</button>
    <div class="task-master-inline-create border rounded bg-white p-2 mt-2" style="display:none;">
        <label class="small text-muted required-label" for="{{ $fieldId }}_new_name">New task name</label>
        <input type="text" class="form-control form-control-sm mb-2 task-master-new-name" id="{{ $fieldId }}_new_name"
            maxlength="255" placeholder="e.g. Fire safety clearance" autocomplete="off">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-submit task-master-save-catalog">Save to catalog</button>
            <button type="button" class="btn btn-sm btn-outline-secondary task-master-cancel-create">Cancel</button>
        </div>
    </div>
    @endif
</div>
