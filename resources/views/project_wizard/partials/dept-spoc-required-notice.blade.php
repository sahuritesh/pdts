@if(!empty($spocMissing))
<div class="alert alert-warning dept-spoc-required-alert d-flex align-items-start gap-2 py-2 px-3 mb-3" role="alert">
    <i class="ri-user-unfollow-line dept-icon-spoc-missing mt-1 flex-shrink-0"></i>
    <div class="small mb-0">
        <strong>Department SPOC required.</strong>
        Assign a department SPOC in <strong>Step 2 — Departments</strong> (use <strong>Configure</strong> on the department row) before saving dates, updating status, or using department actions.
    </div>
</div>
@endif
