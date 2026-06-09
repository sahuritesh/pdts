<div class="row">
    <div class="col-md-12">
        <div class="card formCard">
            <div class="card-body pt-0">
                @php
                $attachment = $data['attachment'] ?? '';
                $delayRegisters = $data['delay_registers'] ?? [];
                $attachmentTypes = $data['attachment_types'] ?? [];
                $presetDelayId = $data['preset_delay_register_id'] ?? '';
                $selectedDelayId = $attachment['delay_register_id'] ?? $presetDelayId;
                $panelReloadUrl = $data['panel_reload_url'] ?? '';
                $lockDelay = !empty($presetDelayId) && empty($attachment);
                $isEdit = !empty($attachment);
                $currentFileUrl = !empty($attachment['file_path']) ? getImageUrl($attachment['file_path']) : '';
                @endphp
                <form class="custom-validations" id="delayAttachmentForm" action="#" method="POST" autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="attachment_id" id="attachment_id"
                        value="{{ $attachment['attachment_id'] ?? $attachment['id'] ?? '' }}">
                    <input type="hidden" name="panel_reload_url" id="panel_reload_url" value="{{ $panelReloadUrl }}">
                    <div class="formStyle">
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label for="delay_register_id" class="required-label">Delay Entry</label>
                                <select name="delay_register_id" id="delay_register_id" class="form-control dd-select required" @if($lockDelay) disabled @endif>
                                    <option value="">Select delay entry</option>
                                    @foreach($delayRegisters as $delay)
                                    <option value="{{ $delay['value'] }}" @if($selectedDelayId == $delay['value']) selected @endif>{{ $delay['label'] }}</option>
                                    @endforeach
                                </select>
                                @if($lockDelay)
                                <input type="hidden" name="delay_register_id" value="{{ $selectedDelayId }}">
                                @endif
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="attachment_type" class="required-label">Attachment Type</label>
                                <select name="attachment_type" id="attachment_type" class="form-control dd-select required">
                                    <option value="">Select type</option>
                                    @foreach($attachmentTypes as $type)
                                    <option value="{{ $type['value'] }}" @if(($attachment['attachment_type'] ?? '') == $type['value']) selected @endif>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="attachment_file" class="{{ $isEdit ? '' : 'required-label' }}">File</label>
                                <input type="file" class="form-control {{ $isEdit ? '' : 'required' }}" name="attachment_file" id="attachment_file"
                                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx" />
                                <small class="text-muted">Max 10 MB. Allowed: images, PDF, Word, Excel.</small>
                            </div>
                            @if($isEdit && $currentFileUrl)
                            <div class="col-md-12 mb-2">
                                <label>Current File</label>
                                <div>
                                    <a href="{{ $currentFileUrl }}" target="_blank" rel="noopener">{{ $attachment['file_name'] ?? 'View file' }}</a>
                                </div>
                                <small class="text-muted">Leave file empty to keep the current attachment.</small>
                            </div>
                            @endif
                            <div class="col-md-12 mb-2">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="3"
                                    placeholder="Optional notes about this document">{{ $attachment['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="formfooter">
                        <div class="text-center">
                            <button type="button" id="submitDelayAttachmentBtn" class="btn btn-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Attachment" }}');

    var formId = 'delayAttachmentForm';
    var url = "{{ getProjectUrl('insert_update_delay_attachment') }}";

    $('#submitDelayAttachmentBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var form = document.getElementById(formId);
        var data = new FormData(form);
        var $submitButton = $('#submitDelayAttachmentBtn');
        var panelReloadUrl = $('#panel_reload_url').val();

        ajaxRequestWithPromise(url, data, 'insert_update_delay_attachment', 1, '', $submitButton).then(function(res) {
            if (res.error == 0 || res.error == "0") {
                parseFormErrors(res, 'success');
                if ($("#offcanvasRight").length > 0 && $("#offcanvasRight").hasClass('show')) {
                    closeSideLayout();
                    if (panelReloadUrl && typeof reloadAttachmentPanel === 'function') {
                        reloadAttachmentPanel(panelReloadUrl);
                    } else if (typeof reloadDataTable === 'function') {
                        reloadDataTable();
                    }
                }
            } else {
                parseFormErrors(res, 'error');
            }
        }).catch(function() {
            parseFormErrors({ error: 1, msg: { 0: 'An error occurred. Please try again.' } }, 'error');
        });
    });

    if ($.fn.select2) {
        $('.dd-select').select2({ dropdownParent: $("#offcanvasRight"), width: '100%' });
    }
});
</script>
