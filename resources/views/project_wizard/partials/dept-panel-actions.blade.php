@php
    $pd = $pd ?? [];
    $encPdId = $encPdId ?? \Illuminate\Support\Facades\Crypt::encrypt($pd['id'] ?? 0);
    $isPending = $isPending ?? (($pd['department_status'] ?? 'pending') === 'pending');
    $actionsDisabled = $actionsDisabled ?? $isPending;
    $workflowPanels = $workflowPanels ?? app(\App\Services\ProjectDepartmentService::class)->workflowPanels();
@endphp
<div class="dept-panel-actions">
    <p class="dept-panel-actions-label">Department actions</p>
    @foreach($workflowPanels as $type => $panel)
    <button type="button" class="dept-panel-card wizard-panel-btn {{ $panel['css_class'] }}"
        data-url="{{ getProjectUrl($panel['route'] . '/' . $encPdId) }}"
        data-title="{{ $panel['title_prefix'] }} — {{ $pd['department_name'] ?? '' }}" @if($actionsDisabled) disabled @endif>
        <span class="dept-panel-icon"><i class="{{ $panel['icon'] }}"></i></span>
        <span class="dept-panel-text">
            <strong>{{ $panel['label'] }}</strong>
            <small>{{ $panel['subtitle'] }}</small>
        </span>
        <i class="ri-arrow-right-s-line dept-panel-arrow"></i>
    </button>
    @endforeach
</div>
