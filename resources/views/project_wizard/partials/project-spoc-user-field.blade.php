@php
    $projectSpocUsers = $projectSpocUsers ?? [];
    $selectedUserId = $project['responsible_user_id'] ?? '';
@endphp
<div class="project-spoc-user-block" data-spoc-role="project">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="mb-0">Project SPOC</label>
        @if(modulePermissionExists('projects') || modulePermissionExists('users'))
        <button type="button" class="btn btn-link btn-sm p-0 toggle-spoc-add-form">
            <i class="ri-user-add-line"></i> Add Project SPOC User
        </button>
        @endif
    </div>

    @if(modulePermissionExists('projects') || modulePermissionExists('users'))
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
        <p class="text-muted small mb-0 mt-1">New users are created with the <strong>Project SPOC</strong> role.</p>
    </div>
    @endif

    <select name="project_spoc_user_id" class="form-control project-spoc-user-select dd-select-spoc" @if(!modulePermissionExists('projects')) disabled @endif>
        <option value="">Select Project SPOC</option>
        @foreach($projectSpocUsers as $user)
        <option value="{{ $user['id'] }}" @if((string)$selectedUserId === (string)$user['id']) selected @endif>{{ $user['label'] }}</option>
        @endforeach
    </select>
    <input type="hidden" name="project_spoc_name" class="project-spoc-name-hidden" value="{{ $project['project_spoc_name'] ?? '' }}">
</div>
