<div class="row">
    <div class="col-md-12">
        <div class="card formCard role-management">
            <div class="card-body pt-0">
                @php
                if(isset($data['roles'])){
                $roles = $data['roles'];
                if(isset($roles['permission_types'])){
                $permissions = explode(",",$roles['permission_types']);
                }
                }
                @endphp
                <form class="custom-validations" id="addrolesform" action="#" method="POST" autocomplete="off">
                    @csrf

                    <input type="hidden" name="role_id" id="role_id"
                        value="@if(isset($roles['id'])){{ $roles['id'] ?? '' }}@endif">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label>Role Name

                                </label>
                                <input type="text" class="form-control" name="role_name"
                                    value="@if(isset($roles['role_name'])){{$roles['role_name'] ?? ''}}@endif" id="name"
                                    @if(isset($roles['role_name'])){{'readonly' ?? ''}}@endif placeholder="Role Name" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label>Description
                                    <!--span class="text-danger"> * </span-->
                                </label>
                                <input type="text" class="form-control" name="role_description"
                                    value="@if(isset($roles['role_description'])){{$roles['role_description'] ?? ''}}@endif"
                                    id="name" placeholder="Description" />
                            </div>
                        </div>
                        <hr>
                         <!--44 line $parent_name = strtolower(str_replace(" ","_",$key)); -->
                        <div class="row">
                            <div class="col-md-12 role-permission-ui">
                                <h3 class="font-size-16">Permissions</h3>

                                @foreach($data['manage_permissions'] as $key=>$parent)
                                @php
                                $parent_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $key));
                                $parent_class = $parent_name.'_parent';
                                $child_class = $parent_name.'_child';
                                @endphp

                                <h5 class="font-size-13 ">
                                    <label>
                                        <input type="checkbox" class="{{$parent_class}}"
                                            onClick="selectCheckbox('{{$parent_class}}','{{$child_class}}','all')"
                                            name="" id="{{$key}}_parent_all" value="all">
                                        {{$key}}
                                    </label>
                                </h5>

                                <div class="row">
                                    @foreach($parent as $child)
                                    @php
                                    $id = '';
                                    @endphp
                                    <div class="col-md-3 mb-2">
                                        <label>
                                            <input type="checkbox" class="{{$child_class}}"
                                                onchange="selectCheckbox('{{$parent_class}}','{{$child_class}}','child')"
                                                name="permission_types[]" id="{{$id}}" value="{{$child['value']}}"
                                                @if(isset($permissions) && in_array($child['value'],$permissions))
                                                {{ 'checked' }} @else {{''}} @endif>
                                            {{$child['label']}}

                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="addroles" class="btn btn-submit">
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
function selectCheckbox(pr_cls, chld_cls, type) {
    var parentCls = pr_cls;
    var childCls = chld_cls;
    // Parent header checkbox: toggle all children on/off.
    if (type === 'all') {
        if ($('.' + parentCls).is(':checked')) {
            $('.' + childCls).prop('checked', true);
        } else {
            $('.' + childCls).prop('checked', false);
        }
        return;
    }
    // Child checkbox: sync parent checked state from children.
    syncParentFromChildren(parentCls, childCls);
}

function syncParentFromChildren(parentCls, childCls) {
    var chkdLength = $('.' + childCls).filter(':checked').length;
    var totalLength = $('.' + childCls).length;
    $('.' + parentCls).prop('checked', totalLength > 0 && chkdLength === totalLength);
}

function syncAllPermissionParents() {
    $('.role-permission-ui h5').each(function() {
        var $parent = $(this).find('input[type=checkbox]').first();
        if (!$parent.length) {
            return;
        }
        var parentCls = ($parent.attr('class') || '').split(/\s+/).filter(function(c) {
            return c.indexOf('_parent') !== -1;
        })[0];
        if (!parentCls) {
            return;
        }
        var childCls = parentCls.replace('_parent', '_child');
        syncParentFromChildren(parentCls, childCls);
    });
}

$(document).ready(function() {
    // Set title in sidelayout (matches CI4 pattern)
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Role Management" }}');
    syncAllPermissionParents();

    var formId = 'addrolesform';
    var url = "{{ getProjectUrl('insert_update_roles') }}";

    $('#addroles').unbind('click').click(function(e) {
        e.preventDefault();
        e.stopPropagation();

        var data = $("#" + formId).serialize();
        var $submitButton = $('#addroles');

        response = ajaxRequestPromise(url, data);
        response.then(function(res) {
            // Loader is automatically closed by ajaxRequestPromise
            if (res.error == 0 || res.error == "0") {
                parseFormErrors(res, 'success');
                // Success - close sidelayout and reload table
                if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                    closeSideLayout();
                    if (typeof reloadDataTable === 'function') {
                        reloadDataTable();
                    } else if ($.fn.DataTable && $('#ucList').length && $('#ucList').hasClass(
                            'datatable')) {
                        $('#ucList').DataTable().ajax.reload(null, false);
                    }
                }
            } else {
                // Error - DO NOT close sidelayout, keep it open to show errors
                parseFormErrors(res, 'error');
                return false;
            }
        }, function(e) {
            // Error occurred - ensure loader is closed and sidelayout stays open
            showGlobalLoader(false);
            if ($submitButton && $submitButton.length > 0) {
                setButtonLoadingState($submitButton, false);
            }
            console.error('Form submission error:', e);
            parseFormErrors({
                error: 1,
                msg: 'An error occurred. Please try again.'
            }, 'error');
        });
    });

    // Initialize Select2 with dropdownParent for sidelayout (matches CI4 pattern)
    if ($.fn.select2) {
        $('.multiselect, .dd-select').select2({
            dropdownParent: $("#offcanvasRight")
        });
    }
});
</script>