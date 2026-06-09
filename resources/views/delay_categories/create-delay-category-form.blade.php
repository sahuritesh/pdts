<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $category = $data['category'] ?? '';
                @endphp
                <form class="custom-validations" id="delayCategoryForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="category_id" id="category_id"
                        value="{{ $category['category_id'] ?? $category['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="category_name" class="required-label">Category Name</label>
                                <input type="text" class="form-control required" name="category_name" id="category_name"
                                    value="{{ $category['category_name'] ?? '' }}" placeholder="e.g. Regulatory & Permitting" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control dd-select">
                                    <option value="{{ ACTIVE }}" @if(($category['status'] ?? ACTIVE) == ACTIVE) selected @endif>Active</option>
                                    <option value="{{ INACTIVE }}" @if(isset($category['status']) && $category['status'] == INACTIVE) selected @endif>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="description">Primary Delay Driver / Description</label>
                                <textarea class="form-control" name="description" id="description" rows="4"
                                    placeholder="Explanation of this delay category bucket">{{ $category['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitDelayCategoryBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Delay Category" }}');

    var formId = 'delayCategoryForm';
    var url = "{{ getProjectUrl('insert_update_delay_category') }}";

    $('#submitDelayCategoryBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitDelayCategoryBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_delay_category', 0, '', $submitButton).then(function(res) {
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
