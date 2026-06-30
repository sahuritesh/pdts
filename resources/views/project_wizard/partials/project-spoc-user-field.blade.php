@php
    $projectSpocUsers = $projectSpocUsers ?? [];
    $selectedUserId = $project['responsible_user_id'] ?? '';
@endphp
<div class="project-spoc-user-block" data-spoc-role="project">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="mb-0 width-100">Project SPOC</label>
        @if(modulePermissionExists('projects') || modulePermissionExists('users') || modulePermissionExists('my_projects'))
        <button type="button" class="spoc-action-btn toggle-spoc-add-form bbtn">
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
            <div class="col-md-12">
                <input type="password" class="form-control form-control-sm spoc-add-password" placeholder="Password" data-msg="Password">
            </div>
            <div class="col-md-12 d-flex align-items-end gap-2 spoc-action-buttons">

    <button type="button"
        class="btn btn-sm btn-primary save-spoc-user">
        <i class="ri-user-add-line me-1"></i>
        Create &amp; Select
    </button>

    <button type="button"
        class="btn btn-sm btn-light cancel-spoc-add">
        <i class="ri-close-circle-line me-1"></i>
        Cancel
    </button>

</div>
        </div>
        <p class="text-muted small mb-0 mt-1">New users are created with the <strong>Department SPOC</strong> role. Assign them here as the project owner; department-level work appears under My Department Tasks.</p>
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
@push('styles')
<style>
/*==========================
SPOC ACTION BUTTONS
===========================*/
.bbtn:hover{
    background-color: none;
    border-color: none;
}
.spoc-action-buttons{

    margin-top:8px;

    display:flex;

    gap:12px;

    flex-wrap:wrap;
}

/* Common Button */

.spoc-action-buttons .btn{


    height:42px;

    border-radius:10px;

    font-size:14px;

    font-weight:600;

    display:flex;

    align-items:center;

    justify-content:center;

    transition:all .3s ease;
}

/* Create Button */

.spoc-action-buttons .save-spoc-user{

    background-color: var(--dark-blue);
    border-color: var(--dark-blue);

    border:none;

    box-shadow:0 8px 20px rgba(37,99,235,.22);
}

.spoc-action-buttons .save-spoc-user:hover{

    transform:translateY(-2px);

    box-shadow:0 12px 28px rgba(37,99,235,.30);
}

/* Cancel Button */

.spoc-action-buttons .cancel-spoc-add{

    background:#fff;

    color:#475569;

    border:1px solid #dbe4ee;

    box-shadow:none;
}

.spoc-action-buttons .cancel-spoc-add:hover{

    background:#f8fafc;

    border-color:#dc2626;

    color:#dc2626;

    transform:translateY(-2px);
}

/* Icons */

.spoc-action-buttons .btn i{

    font-size:16px;
}

/* Mobile */

@media(max-width:768px){

.spoc-action-buttons{

    flex-direction:column;
}

.spoc-action-buttons .btn{

    width:100%;
}

}
.width-100{
    width:100px;
}
.spoc-action-btn{
       display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0px 1px;
    border: 0px solid #dbeafe;
    /* border-radius: 8px; */
    background: #f8fbff;
    color: #2563eb;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .3s ease;
}

.spoc-action-btn i{

    width:22px;
    height:22px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:6px;

    background:#2563eb;

    color:#fff;

    font-size:11px;
}




.spoc-action-btn:hover i{

    background:#2563eb;

    color:#fff;
}


</style>
@endpush