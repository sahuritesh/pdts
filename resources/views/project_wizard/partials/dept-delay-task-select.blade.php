@php
    $fieldName = $fieldName ?? 'project_department_task_id';
    $fieldId = $fieldId ?? ('delay_task_' . ($projectDepartmentId ?? 0));
    $tasks = $tasks ?? [];
    $selectedTaskId = (int) ($selectedTaskId ?? 0);
    $required = !empty($required);
@endphp
<div class="col-12 mb-2">
    <label for="{{ $fieldId }}" class="{{ $required ? 'required-label' : '' }}">Impacted task</label>
    <select class="form-control dd-select" name="{{ $fieldName }}" id="{{ $fieldId }}">
        <option value="">Department level (not task-specific)</option>
        @foreach($tasks as $task)
        <option value="{{ $task['id'] }}" @if($selectedTaskId === (int) $task['id']) selected @endif>
            {{ $task['task_name'] ?? 'Task' }}
        </option>
        @endforeach
    </select>
    @if(empty($tasks))
    <span class="text-muted d-block mt-1" style="font-size:11px;">No tasks configured for this department yet.</span>
    @else
    <span class="text-muted d-block mt-1" style="font-size:11px;">Optional — link this delay to a specific task under this department.</span>
    @endif
</div>
