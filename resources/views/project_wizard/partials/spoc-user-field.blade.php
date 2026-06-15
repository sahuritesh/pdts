@php
    $spocUsers = $spocUsers ?? [];
    $selectedSpocUserId = $pd['spoc_user_id'] ?? '';
    $departmentId = $pd['department_id'] ?? '';
    $projectDepartmentId = $pd['id'] ?? '';
@endphp
<div class="col-12 spoc-user-block mb-2" data-department-id="{{ $departmentId }}" data-pd-id="{{ $projectDepartmentId }}">
  <div class="d-flex justify-content-between align-items-center mb-1">
    <label class="small text-muted mb-0">Department SPOC</label>
    <button type="button" class="btn btn-link btn-sm p-0 toggle-spoc-add-form">
      <i class="ri-user-add-line"></i> Add SPOC User
    </button>
  </div>

  <div class="spoc-add-form card card-body p-2 mb-2" style="display:none;">
    <div class="row g-2">
      <div class="col-md-6">
        <input type="text" class="form-control form-control-sm spoc-add-first-name" placeholder="First name" data-msg="First name">
      </div>
      <div class="col-md-6">
        <input type="text" class="form-control form-control-sm spoc-add-last-name" placeholder="Last name" data-msg="Last name">
      </div>
      <div class="col-md-6">
        <input type="email" class="form-control form-control-sm spoc-add-email" placeholder="Email" data-msg="Email">
      </div>
      <div class="col-md-6">
        <input type="text" class="form-control form-control-sm spoc-add-mobile numberOnly" maxlength="10" placeholder="Mobile (10 digits)" data-msg="Mobile">
      </div>
      <div class="col-md-6">
        <input type="password" class="form-control form-control-sm spoc-add-password" placeholder="Password" data-msg="Password">
      </div>
      <div class="col-md-6 d-flex align-items-end gap-2">
        <button type="button" class="btn btn-sm btn-primary save-spoc-user w-100">Create &amp; Select</button>
        <button type="button" class="btn btn-sm btn-light cancel-spoc-add">Cancel</button>
      </div>
    </div>
    <p class="text-muted small mb-0 mt-1">New users are created with the <strong>Department SPOC</strong> role and assigned to this department.</p>
  </div>

  <select name="spoc_user_id" class="form-control form-control-sm spoc-user-select dd-select-spoc">
    <option value="">Select SPOC user</option>
    @foreach($spocUsers as $user)
    <option value="{{ $user['id'] }}" @if((string)$selectedSpocUserId === (string)$user['id']) selected @endif>{{ $user['label'] }}</option>
    @endforeach
  </select>
  <input type="hidden" name="spoc_name" class="spoc-name-hidden" value="{{ $pd['spoc_name'] ?? '' }}">
</div>
