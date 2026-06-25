@php
    $pd = $pd ?? [];
    $status = $status ?? ($pd['department_status'] ?? 'pending');
    $showSpoc = $showSpoc ?? false;
    $spocUsers = $spocUsers ?? [];
    $formMarginClass = $formMarginClass ?? 'mb-2';
    $actionsMarginClass = $actionsMarginClass ?? 'mb-2';
    $saveButtonLabel = $saveButtonLabel ?? 'Save';
    $showRemarks = $showRemarks ?? false;
    $inProgressLabel = $inProgressLabel ?? 'In Progress';
    $completeLabel = $completeLabel ?? 'Complete';
    $sequentialEnforced = !empty($sequentialEnforced);
    $sequentialMinStart = $sequentialMinStart ?? '';
    $sequentialPrevName = $sequentialPrevName ?? '';
    $projectPlannedStart = $projectPlannedStart ?? '';
    $actionsDisabled = !empty($actionsDisabled);
@endphp
@if(($status ?? 'pending') !== 'pending' || !empty($forceShowPlannedDates))
<div class="dept-meta-form planned-date-range row g-2 {{ $formMarginClass }} @if($actionsDisabled) dept-actions-disabled @endif" data-pd-id="{{ $pd['id'] }}"
    data-seq-enforced="{{ $sequentialEnforced && $sequentialMinStart !== '' ? '1' : '0' }}"
    data-seq-min-start="{{ $sequentialMinStart }}"
    data-seq-prev-name="{{ e($sequentialPrevName) }}"
    data-project-min-start="{{ $projectPlannedStart }}">
    <input type="hidden" name="project_department_id" value="{{ $pd['id'] }}">
    @if($showSpoc)
    <div class="col-md-6">
        @include('project_wizard.partials.spoc-user-field', ['pd' => $pd, 'spocUsers' => $spocUsers])
    </div>
    @include('project_wizard.partials.dept-planned-date-fields', [
        'pd' => $pd,
        'dateColClass' => 'col-md-3',
        'readOnly' => $actionsDisabled,
    ])
    <div class="col-md-2 d-flex align-items-end">
        <button type="button" class="btn btn-sm btn-outline-secondary save-dept-meta w-100">{{ $saveButtonLabel }}</button>
    </div>
    @else
    @include('project_wizard.partials.dept-planned-date-fields', [
        'pd' => $pd,
        'readOnly' => $actionsDisabled,
    ])
    @if($showRemarks)
    <div class="col-12">
        <label class="small text-muted">Remarks</label>
        <textarea class="form-control form-control-sm" name="remarks" rows="2">{{ $pd['remarks'] ?? '' }}</textarea>
    </div>
    <div class="col-12">
        <button type="button" class="btn btn-sm btn-outline-primary save-dept-meta">{{ $saveButtonLabel }}</button>
    </div>
    @else
    <div class="col-md-2 d-flex align-items-end">
        <button type="button" class="btn btn-sm btn-outline-secondary save-dept-meta w-100 save-add-btn" @if($actionsDisabled) disabled @endif>{{ $saveButtonLabel }}</button>
    </div>
    @endif
    @endif
</div>
@if(in_array($status, ['start', 'in_progress', 'delay']))
<div class="btn-group btn-group-sm {{ $actionsMarginClass }}">
    <button type="button" class="btn btn-outline-primary dept-action" data-id="{{ $pd['id'] }}" data-action="in_progress" @if($actionsDisabled) disabled @endif>{{ $inProgressLabel }}</button>
    <button type="button" class="btn btn-outline-success dept-action" data-id="{{ $pd['id'] }}" data-action="complete" @if($actionsDisabled) disabled @endif>{{ $completeLabel }}</button>
</div>
@endif
@endif

@push('styles')
<style>


.save-add-btn{
    border-radius: 10px !important;
    padding: 8px 15px;
    font-weight: 600;

    cursor:pointer;

    transition:all .3s ease;
}


</style>
@endpush
