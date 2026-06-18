@php
    $ctx = $data['ctx'];
    $attachments = $data['attachments'];
    $types = $data['attachment_types'];
    $encPd = Crypt::encrypt($ctx['id']);
@endphp
<div class="sidelayout-panel">
    <div class="sidelayout-context">{{ $ctx['project_code'] }} — {{ $ctx['department_name'] }}</div>

    @if($attachments->count())
    <h6>Uploaded Files</h6>
    <ul class="list-group mb-2">
        @foreach($attachments as $att)
        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div class="pe-2">
                <strong class="small">{{ $att->file_name }}</strong>
                <span class="badge bg-light text-dark ms-1">{{ $att->attachment_type }}</span>
                @if($att->description)<br><small class="text-muted">{{ $att->description }}</small>@endif
            </div>
            @if(!empty($att->file_path))
            <a href="{{ getImageUrl($att->file_path) }}" target="_blank" class="btn btn-sm btn-link flex-shrink-0">View</a>
            @endif
        </li>
        @endforeach
    </ul>
    @endif

    <h6>Upload New File</h6>
    <form id="wizardAttachmentForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="project_department_id" value="{{ $ctx['id'] }}">
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="required-label">Type</label>
                <select name="attachment_type" class="form-control dd-select required">
                    <option value="">Select</option>
                    @foreach($types as $t)
                    <option value="{{ $t['value'] }}">{{ $t['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label class="required-label">File</label>
                <input type="file" class="form-control required" name="attachment_file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
            </div>
            <div class="col-12 mb-2">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
        <div class="sidelayout-actions">
            <button type="button" class="btn btn-submit btn-sm" id="saveWizardAttachmentBtn">Upload</button>
        </div>
    </form>
</div>
<script>
$(function() {
    $('.sidelayoutTitle').html('{{ $pageTitle }}');
    if ($.fn.select2) { $('.dd-select').select2({ dropdownParent: $("#offcanvasRight"), width: '100%' }); }
    var panelUrl = "{{ getProjectUrl('projects/wizard/panel/attachments/' . $encPd) }}";

    $('#saveWizardAttachmentBtn').on('click', function() {
        var $btn = $(this);
        var form = document.getElementById('wizardAttachmentForm');
        ajaxRequestWithPromise("{{ getProjectUrl('wizard_save_attachment') }}", new FormData(form), 'wizard_save_attachment', 1, '', $btn)
            .then(function(res) {
                if (res.error == 0) openSideLayout({}, panelUrl, '{{ $pageTitle }}');
            });
    });
});
</script>
