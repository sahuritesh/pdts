<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $hospital = $data['hospital'] ?? '';
                @endphp
                <form class="custom-validations" id="hospitalForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="hospital_id" id="hospital_id"
                        value="{{ $hospital['hospital_id'] ?? $hospital['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="hospital_code" class="required-label">Hospital Code</label>
                                <input type="text" class="form-control required" name="hospital_code" id="hospital_code"
                                    value="{{ $hospital['hospital_code'] ?? '' }}"
                                    placeholder="e.g. APL-GUR-01" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="hospital_name" class="required-label">Hospital Name</label>
                                <input type="text" class="form-control required" name="hospital_name" id="hospital_name"
                                    value="{{ $hospital['hospital_name'] ?? '' }}"
                                    placeholder="e.g. Apollo Hospitals — Gurugram" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control dd-select">
                                    <option value="{{ ACTIVE }}" @if(($hospital['status'] ?? ACTIVE) == ACTIVE) selected @endif>Active</option>
                                    <option value="{{ INACTIVE }}" @if(isset($hospital['status']) && $hospital['status'] == INACTIVE) selected @endif>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="3"
                                    placeholder="Optional notes about this hospital">{{ $hospital['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitHospitalBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Hospital" }}');

    var formId = 'hospitalForm';
    var url = "{{ getProjectUrl('insert_update_hospital') }}";

    $('#submitHospitalBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitHospitalBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_hospital', 0, '', $submitButton).then(function(res) {
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
