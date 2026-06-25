@php
    $pd = $pd ?? [];
    $deptId = (int) ($pd['department_id'] ?? 0);
    $pdId = (int) ($pd['id'] ?? 0);
    $deptName = $pd['department_name'] ?? 'Department';
    $spocName = trim($pd['spoc_name'] ?? '');
    $spocUserId = $pd['spoc_user_id'] ?? '';
    $allowParallel = !empty($pd['allow_parallel_next']);
    $isLast = !empty($isLast);
    $encPdId = $pdId > 0 ? Crypt::encrypt($pdId) : '';
    $plannedStartYmd = !empty($pd['planned_start_date']) ? date('Y-m-d', strtotime($pd['planned_start_date'])) : '';
    $plannedEndYmd = !empty($pd['planned_end_date']) ? date('Y-m-d', strtotime($pd['planned_end_date'])) : '';
@endphp
<li class="list-group-item dept-sortable-item {{ $spocName !== '' ? 'dept-has-spoc' : 'dept-spoc-missing' }}"
    data-dept-id="{{ $deptId }}"
    data-pd-id="{{ $pdId }}"
    data-spoc-user-id="{{ $spocUserId }}"
    data-spoc-name="{{ e($spocName) }}"
    data-allow-parallel="{{ $allowParallel ? 1 : 0 }}"
    data-planned-start="{{ $plannedStartYmd }}"
    data-planned-end="{{ $plannedEndYmd }}"
    data-pd-token="{{ $encPdId }}">
    <div class="dept-sortable-main flex-grow-1">
        <div class="dept-sortable-drag"><i class="ri-drag-move-2-line"></i></div>
        <div class="dept-sortable-content">
            <div class="dept-sortable-title">{{ $deptName }}</div>
            <div class="dept-sortable-meta">
                <span class="dept-meta-spoc">
                    <i class="{{ $spocName !== '' ? 'ri-user-follow-line dept-icon-spoc-assigned' : 'ri-user-unfollow-line dept-icon-spoc-missing' }}"></i>
                    <span class="dept-meta-spoc-text">{{ $spocName !== '' ? $spocName : 'SPOC not assigned' }}</span>
                </span>
                @if(!$isLast)
                <span class="dept-meta-parallel dept-meta-flow badge rounded-pill {{ $allowParallel ? 'bg-info-subtle text-info dept-flow-parallel' : 'bg-secondary-subtle text-secondary dept-flow-sequential' }}">
                    <i class="{{ $allowParallel ? 'ri-git-branch-line' : 'ri-arrow-right-line' }}"></i>
                    <span>{{ $allowParallel ? 'Parallel' : 'Sequential' }}</span>
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="dept-sortable-actions">
        @if(empty($isProjectReadOnly))
        <button type="button" class="btn btn-sm btn-outline-primary btn-config-dept" title="Configure SPOC, dates & workflow">
            <i class="ri-settings-3-line me-1"></i> Configure
        </button>
        @endif
        <button type="button" class="btn btn-sm btn-link text-danger remove-dept" title="Remove">&times;</button>
    </div>
</li>
