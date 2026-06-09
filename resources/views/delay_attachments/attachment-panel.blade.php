@php
$delay = $data['delay'] ?? null;
$delayRegisterId = $data['delay_register_id'] ?? '';
$addUrl = $data['add_url'] ?? '';
$panelUrl = $data['panel_url'] ?? '';
@endphp
<div class="row">
    <div class="col-md-12">
        @if($delay)
        <div class="alert alert-light border mb-3 py-2">
            <div class="small mb-1"><strong>Project:</strong> {{ trim(($delay->project_code ?? '') . ' — ' . ($delay->project_name ?? '')) }}</div>
            <div class="small mb-1"><strong>Delay:</strong> {{ $delay->delay_title ?? '' }}</div>
            <div class="small"><strong>Severity:</strong> {{ ucfirst($delay->severity ?? '') }} &nbsp;|&nbsp; <strong>Days:</strong> {{ (int)($delay->delay_days ?? 0) }}</div>
        </div>
        @endif
        <div class="text-end mb-2">
            <a href="javascript:void(0)"
               onclick="openSideLayout({}, '{{ $addUrl }}', 'Upload Attachment', 85); return false;"
               class="btn btn-primary btn-sm waves-effect waves-light">
                <i class="fas fa-plus fa-fw"></i> Upload Attachment
            </a>
        </div>
        <div class="tablemain">
            <table id="attachmentPanelTable" class="table table-bordered nowrap datatable" style="width:100%;">
                <thead>
                    <tr>
                        @foreach($grid_data['columns'] as $column)
                        <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script>
(function() {
    var panelUrl = @json($panelUrl);
    var delayRegisterId = @json($delayRegisterId);
    var dataUrl = "{{ getProjectUrl('get_delay_attachment_list') }}";

    window.reloadAttachmentPanel = function(url) {
        var reloadUrl = url || panelUrl;
        if (!reloadUrl) {
            return;
        }
        ajaxRequestWithPromise(reloadUrl, { postKey: 'sidelayoutContent' }, '', 0, '', null).then(function(html) {
            if (typeof html === 'string' && html.indexOf('error') === -1) {
                $('#offcanvasRight .offcanvas-body').html(html);
            }
        });
    };

    window.openAttachmentEdit = function(editUrl) {
        openSideLayout({}, editUrl, 'Edit Attachment', 85);
    };

    $('.sidelayoutTitle').html('{{ $pageTitle ?? "Attachments" }}');

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#attachmentPanelTable')) {
        $('#attachmentPanelTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: true,
            pageLength: 10,
            order: [[0, 'desc']],
            ajax: {
                type: 'POST',
                url: dataUrl,
                data: function(d) {
                    return {
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        order: d.order,
                        columns: d.columns,
                        search: d.search,
                        table: 'tbl_delay_attachments',
                        panel_delay_register_id: delayRegisterId,
                        filters: {}
                    };
                }
            },
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    }
})();
</script>
