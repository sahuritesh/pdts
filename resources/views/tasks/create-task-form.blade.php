<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $task = $data['task'] ?? '';
                @endphp
                <form class="custom-validations" id="taskForm" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="task_id" id="task_id"
                        value="{{ $task['task_id'] ?? $task['id'] ?? '' }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="task_name" class="required-label">Task Name</label>
                                <input type="text" class="form-control required" name="task_name" id="task_name"
                                    value="{{ $task['task_name'] ?? '' }}"
                                    placeholder="e.g. Fire safety clearance" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="task_code">Task Code</label>
                                <input type="text" class="form-control" name="task_code" id="task_code"
                                    value="{{ $task['task_code'] ?? '' }}"
                                    placeholder="Optional short code" maxlength="50" />
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control dd-select">
                                    <option value="{{ ACTIVE }}" @if(($task['status'] ?? ACTIVE) == ACTIVE) selected @endif>Active</option>
                                    <option value="{{ INACTIVE }}" @if(isset($task['status']) && $task['status'] == INACTIVE) selected @endif>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="3"
                                    placeholder="Optional notes about this task">{{ $task['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitTaskBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Task" }}');

    var formId = 'taskForm';
    var url = "{{ getProjectUrl('insert_update_task') }}";

    $('#submitTaskBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var formData = $("#" + formId).serialize();
        var $submitButton = $('#submitTaskBtn');

        ajaxRequestWithPromise(url, formData, 'insert_update_task', 0, '', $submitButton).then(function(res) {
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
