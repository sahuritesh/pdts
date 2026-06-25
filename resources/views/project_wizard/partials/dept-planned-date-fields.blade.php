@php
    $pd = $pd ?? [];
    $plannedStartYmd = !empty($pd['planned_start_date']) ? date('Y-m-d', strtotime($pd['planned_start_date'])) : '';
    $plannedEndYmd = !empty($pd['planned_end_date']) ? date('Y-m-d', strtotime($pd['planned_end_date'])) : '';
    $readOnly = !empty($readOnly);
    $dateColClass = $dateColClass ?? 'col-md-6';
    $fieldSuffix = $pd['department_id'] ?? $pd['id'] ?? 'new';
@endphp
<div class="{{ $dateColClass }}">
    <label class="small text-muted" for="dept_planned_start_{{ $fieldSuffix }}">Planned start</label>
    <input type="text" class="form-control form-control-sm planned-date-input js-planned-start" name="planned_start_date"
        id="dept_planned_start_{{ $fieldSuffix }}" autocomplete="off" placeholder="yyyy-mm-dd"
        value="{{ $plannedStartYmd }}" @if($readOnly) readonly @endif>
</div>
<div class="{{ $dateColClass }}">
    <label class="small text-muted" for="dept_planned_end_{{ $fieldSuffix }}">Planned end</label>
    <input type="text" class="form-control form-control-sm planned-date-input js-planned-end" name="planned_end_date"
        id="dept_planned_end_{{ $fieldSuffix }}" data-label="Planned end" autocomplete="off" placeholder="yyyy-mm-dd"
        value="{{ $plannedEndYmd }}" @if($plannedStartYmd === '' || $readOnly) readonly @endif>
</div>
