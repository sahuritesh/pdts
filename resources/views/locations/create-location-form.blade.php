<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $location = $data['location'] ?? '';
                $zones = $data['zones'] ?? [];
                @endphp
                <form class="custom-validations" id="locationForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="location_id" id="location_id"
                        value="{{ $location['location_id'] ?? $location['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="location_code" class="required-label">Location Code</label>
                                <input type="text" class="form-control required" name="location_code" id="location_code"
                                    value="{{ $location['location_code'] ?? '' }}"
                                    placeholder="e.g. LOC-NORTH-01" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="location_name" class="required-label">Location Name</label>
                                <input type="text" class="form-control required" name="location_name" id="location_name"
                                    value="{{ $location['location_name'] ?? '' }}"
                                    placeholder="e.g. City General Hospital" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="zone_id" class="required-label">Zone</label>
                                <select name="zone_id" id="zone_id" class="form-control dd-select required">
                                    <option value="">Select zone</option>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone['id'] }}" @if(($location['zone_id'] ?? '') == $zone['id']) selected @endif>{{ $zone['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control dd-select">
                                    <option value="{{ ACTIVE }}" @if(($location['status'] ?? ACTIVE) == ACTIVE) selected @endif>Active</option>
                                    <option value="{{ INACTIVE }}" @if(isset($location['status']) && $location['status'] == INACTIVE) selected @endif>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="3"
                                    placeholder="Optional notes about this location">{{ $location['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitLocationBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Location" }}');

    var formId = 'locationForm';
    var url = "{{ getProjectUrl('insert_update_location') }}";

    $('#submitLocationBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitLocationBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_location', 0, '', $submitButton).then(function(res) {
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
