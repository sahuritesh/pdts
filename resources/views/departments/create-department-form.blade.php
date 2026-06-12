<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $department = $data['department'] ?? '';
                @endphp
                <form class="custom-validations" id="departmentForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="department_id" id="department_id"
                        value="{{ $department['department_id'] ?? $department['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="department_name" class="required-label">Department Name</label>
                                <input type="text" class="form-control required" name="department_name" id="department_name"
                                    value="{{ $department['department_name'] ?? $department['category_name'] ?? '' }}"
                                    placeholder="e.g. Design &amp; Planning" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control dd-select">
                                    <option value="{{ ACTIVE }}" @if(($department['status'] ?? ACTIVE) == ACTIVE) selected @endif>Active</option>
                                    <option value="{{ INACTIVE }}" @if(isset($department['status']) && $department['status'] == INACTIVE) selected @endif>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="4"
                                    placeholder="Scope of this department on a project">{{ $department['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitDepartmentBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Department" }}');

    var formId = 'departmentForm';
    var url = "{{ getProjectUrl('insert_update_department') }}";

    $('#submitDepartmentBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitDepartmentBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_department', 0, '', $submitButton).then(function(res) {
            if (res.error == 0 || res.error == "0") {
                parseFormErrors(res, 'success');
                if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                    closeSideLayout();
                    reloadDataTable();
                }
            } else {
                parseFormErrors(res, 'error');
            }
        }).catch(function() {
            parseFormErrors({ error: 1, msg: { 0: 'An error occurred. Please try again.' } }, 'error');
        });
    });

    if ($.fn.select2) {
        $('.dd-select').select2({ dropdownParent: $("#offcanvasRight") });
    }
});
</script>
