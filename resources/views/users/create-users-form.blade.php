<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                <form class="custom-validations" id="adduserform" action="#" method="POST" autoComplete="off">
    @csrf
    <input type="hidden" name="user_id" id="user_id"
        value="@if(isset($data['user']['user_id'])){{$data['user']['user_id'] ?? ''}}@endif">
    <div class="formStyle">
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="first_name">First Name </label>
                <input type="text" class="form-control required nameOnly" name="first_name"
                    id="first_name"
                    value="@if(isset($data['user']['first_name'])){{$data['user']['first_name'] ?? ''}}@endif"
                    placeholder="First Name" data-msg="First Name" />
            </div>
            <div class="col-md-6 mb-3">
                <label>Last Name </label>
                <input type="text" class="form-control required nameOnly" name="last_name"
                    id="designation"
                    value="@if(isset($data['user']['last_name'])){{$data['user']['last_name'] ?? ''}}@endif"
                    placeholder="Last Name" data-msg="Last Name" />
            </div>
            <div class="col-md-6 mb-3">
                <label>Employee Email <span class="text-danger" id="email-id-mandatory" style="display:none">*</span></label>
                <input type="text" name="email_id" class="form-control required  emailOnly" id="email_id"
                    value="@if(isset($data['user']['email_id'])){{$data['user']['email_id'] ?? ''}}@endif"
                    placeholder="Employee Email" autocomplete="nope" data-msg="Employee Email"
                    @if(isset($data['user']['user_id']) && !empty($data['user']['user_id'])) readonly @endif>
            </div>
            <div class="col-md-6 mb-3">
                <label>Phone Number </label>
                <input class="form-control required numberOnly" name="mobile_no" minlength="10"
                    maxlength="10" class="form-control" id="number"
                    value="@if(isset($data['user']['mobile_no'])){{$data['user']['mobile_no'] ?? ''}}@endif"
                    placeholder="Phone number" data-msg="Phone Number">
            </div>
            @if(!isset($data['user']['mobile_no']))
            <div class="col-md-6 mb-3">
                <label>Password <span id="password-tooltip"><i class="ri-information-fill"></i></span></label>            
                <div class="tooltip-popup" id="password-rules">
                    <ul>
                        <li>Password requires a minimum length of 8 characters</li>
                        <li>Password should contain a mixture of lowercase, uppercase, numbers and special characters</li>
                        <li>Password Cannot be the same as the most recent previous 5 passwords</li>
                    </ul>
                </div>
                <input type="password" class="form-control required password nospaces" name="password"
                    value="" placeholder="Password" data-msg="Password">
            </div>
            @endif
            <div class="col-md-6 mb-3">
                <label>User Role </label>
                <select name="user_type" class="form-select required userRoles" id="user_type"
                    data-msg="User Role" @if(isset($data['user']['user_id']) && !empty($data['user']['user_id'])) disabled @endif>
                    <option value="">Select Role</option>
                    @foreach($data['roles'] as $role)
                        @if(!in_array($role['id'], $data['exclude_roles']))
                        <option value="{{$role['id']}}" @if(isset($data['user']['user_type']) &&
                            $data['user']['user_type']==$role['id']){{ 'selected' }}@endif>
                            {{$role['role_name']}}</option>
                        @endif    
                    @endforeach
                </select>
                @if(isset($data['user']['user_id']) && !empty($data['user']['user_id']))
                <input type="hidden" name="user_type" value="{{$data['user']['user_type'] ?? ''}}">
                @endif
            </div>

            <div class="col-md-6 mb-3">
                <label>Status</label>
                <select name="status" id="" class="form-select required" data-msg="Status">
                    @if(!empty($data['status']))
                    @foreach($data['status'] as $status)
                    <option value="{{$status['id']}}" @if(isset($data['user']['status']) &&
                        $data['user']['status']==$status['id']){{ 'selected' }}@endif>
                        {{$status['status_name']}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
            @if(!empty($data['departments']))
            <div class="col-md-12 mb-3">
                <label>Assigned Departments <small class="text-muted">(for Department SPOC users)</small></label>
                <select name="department_ids[]" id="department_ids" class="form-select" multiple="multiple">
                    @foreach($data['departments'] as $dept)
                    <option value="{{ $dept['id'] }}"
                        @if(in_array($dept['id'], $data['assigned_department_ids'] ?? [])) selected @endif>
                        {{ $dept['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>                  
   <div class="formfooter">
    <div class="text-center">
        <button type="button" id="adduser" class="btn btn-primary waves-effect waves-light me-1">
            Submit
        </button>
    </div>
  </div>
</form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Set title in sidelayout (matches CI4 pattern)
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "User Management" }}');
    
    // Initialize Select2 - check if in sidelayout or direct navigation
    var select2Options = {};
    if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
        select2Options.dropdownParent = $("#offcanvasRight");
    }
    $('.userRoles').select2(select2Options);
    if ($('#department_ids').length && $.fn.select2) {
        $('#department_ids').select2(Object.assign({}, select2Options, { placeholder: 'Select departments' }));
    }

    $('#adduser').unbind('click').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var form_id = $('#adduserform').attr('id');
        var action = "{{ getProjectUrl('insert_update_user') }}";
        var json_data = {
            "formId": form_id,
            "url": action,
            "postKey": "insert"
        };
        
        // Client-side validation
        if (validateFormdata(json_data)) {
            var form = $('#' + form_id)[0];
            var data = new FormData(form);
            
            // Find submit button for loading state
            var $submitButton = $('#adduser');
            
            ajaxRequestWithPromise(action, data, json_data.postKey, 1, '', $submitButton).then(function(response) {
                // Loader is automatically closed by ajaxRequestWithPromise
                if (response.error == 0 || response.error == "0") {
                    // Success - only close sidelayout on success
                    if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                        closeSideLayout();
                        if (typeof reloadDataTable === 'function') {
                            reloadDataTable();
                        } else if ($.fn.DataTable && $('#ucList').length && $('#ucList').hasClass('datatable')) {
                            $('#ucList').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        // Direct navigation - redirect to list
                        window.location.href = "{{ getProjectUrl('user-management-list') }}";
                    }
                } else {
                    // Error - DO NOT close sidelayout, keep it open to show errors
                    // Error message is already displayed by displayResponseMessage in ajaxRequestWithPromise
                    return false;
                }
            }).catch(function(err) {
                // Error occurred - ensure loader is closed and sidelayout stays open
                showGlobalLoader(false);
                if ($submitButton && $submitButton.length > 0) {
                    setButtonLoadingState($submitButton, false);
                }
                console.error('Form submission error:', err);
                // Show error message
                if (typeof displayResponseMessage === 'function') {
                    displayResponseMessage({error: 1, msg: 'An error occurred. Please try again.'});
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        } else {
            // Validation failed - loader should already be closed by validateFormdata
            // But ensure it's closed just in case
            showGlobalLoader(false);
            return false;
        }
    });

    var tooltips = document.getElementById('password-tooltip');
    var tooltipPopup = document.getElementById('password-rules');
    if (tooltips) {
        tooltips.addEventListener('mouseenter', () => {
            tooltipPopup.style.display = 'block';
        });

        tooltips.addEventListener('mouseleave', () => {
            tooltipPopup.style.display = 'none';
        });
    }
});
</script>

