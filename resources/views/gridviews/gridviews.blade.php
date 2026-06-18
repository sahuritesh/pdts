@extends('layouts.template_v1')
@section('content')

@php
// Convert grid_data to JSON for JavaScript usage
$gridConfigJson = json_encode($grid_data);
@endphp

@if(isset($grid_data['filters']) && !empty($grid_data['filters']))
@include('gridviews.search_form')
@endif

@if(!empty($readonly))
<div class="alert alert-info py-2 mb-2">View only — use <strong>My Department Tasks</strong> to manage your department work.</div>
@endif

<div class="col-12">
    <div class="col-md-12 mb-2 text-right min-height-40">
        @if(isset($additional_buttons) && is_array($additional_buttons) && !empty($additional_buttons))
            @foreach($additional_buttons as $button)
                <a href="{{ $button['url'] ?? '#' }}" 
                   class="btn btn-{{ $button['class'] ?? 'secondary' }} waves-effect waves-light me-1"
                   data-bs-toggle="tooltip" 
                   data-bs-placement="top"
                   title="{{ $button['label'] ?? '' }}" style="margin-right: 50px !important; margin-top:3px;">
                    <i class="{{ $button['icon'] ?? 'fa fa-circle' }}"></i> {{ $button['label'] ?? '' }}
                </a>
            @endforeach
        @endif
        @if(isset($grid_data['addurl']) && $grid_data['addurl'] != '')
            @php
                $addTitle = isset($grid_data['addurllabel']) ? $grid_data['addurllabel'] : 'Add ' . ($pageTitle ?? 'Record');
                $addUrl = getProjectUrl($grid_data['addurl']);
                $addRedirect = !empty($grid_data['addurl_redirect']);
            @endphp
            @if($addRedirect)
            <a href="{{ $addUrl }}"
               data-bs-toggle="tooltip"
               data-bs-placement="top"
               title="{{ $addTitle }}"
               class="btn btn-primary waves-effect waves-light me-1 createTask mt">
                <i class="fas fa-plus fa-fw"></i>
            </a>
            @else
            <a href="javascript:void(0)" 
               onclick="openSideLayout({}, '{{ $addUrl }}', '{{ $addTitle }}'); return false;"
               data-bs-toggle="tooltip" 
               data-bs-placement="top"
               title="{{ $addTitle }}"
               class="btn btn-primary waves-effect waves-light me-1 createTask mt">
                <i class="fas fa-plus fa-fw"></i>
            </a>
            @endif
        @elseif(isset($grid_data['addjsform']) && $grid_data['addjsform'] != '')
            <a href="javascript:void(0)" 
               onclick="{{ $grid_data['addjsform'] }}" 
               data-bs-toggle="tooltip"
               data-bs-placement="top" 
               title="{{ isset($grid_data['addurllabel']) ? $grid_data['addurllabel'] : 'Add' }}"
               class="btn btn-primary waves-effect waves-light me-1 createTask mt">
                <i class="fas fa-plus fa-fw"></i>
            </a>
        @endif
    </div>

    <div class="card formCard">
        <div class="tablemain">
            <table id="ucList" class="table table-bordered nowrap datatable"
                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                <thead>
                    <tr>
                        @foreach($grid_data['columns'] as $column)
                        @php
                            $isActionsCol = in_array($column, ['Actions', 'Action'], true);
                            $isNoSort = $isActionsCol
                                || (isset($grid_data['no_sort_columns']) && in_array($column, $grid_data['no_sort_columns'], true));
                        @endphp
                        @if($isNoSort)
                        <th class="no-sort{{ $isActionsCol ? ' grid-actions-col' : '' }}">{{ $column }}</th>
                        @else
                        <th>{{ $column }}</th>
                        @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody id="dataresult">
                </tbody>
            </table>
        </div>
    </div>
</div>
 <!-- forgot password request Popup -->
<div class="modal fade" id="forgotPassword" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    role="dialog" aria-labelledby="forgotpasswordLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title statusTitle" id="staticBackdropLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure, you want to do this action?</p>
                <div class="col-md-12">
                    <label>Password </label>
                    <input type="text" name="forgot_password" id="forgot_password" value=""
                        class="form-control required" readonly />
                </div>
            </div>
            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-danger waves-effect waves-light  me-1 btn-custom"
                    data-bs-dismiss="modal">Close</button>
                <input type="hidden" id="rowid" value="">
                <button type="button" id="sendForgotPassword"
                    class="btn btn-primary waves-effect waves-light btn-custom">Send</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<!-- CMS Common Utilities (for deleteRecord and other CMS functions) - Load first -->
<script src="{{ getAssetUrl('js/cms/cms-common.js') }}?v=1.1"></script>
<script src="{{ getAssetUrl('js/gridview-utility.js') }}?v=1.4"></script>
@if(isset($additional_scripts) && is_array($additional_scripts) && !empty($additional_scripts))
@foreach($additional_scripts as $script)
@if(!empty($script))
<script src="{{ getAssetUrl('js/' . $script) }}"></script>
@endif
@endforeach
@endif
@if(isset($script_routes) && is_array($script_routes) && !empty($script_routes))
<script>
    // Define routes for additional scripts (passed from controller)
    @foreach($script_routes as $routeKey => $routes)
    window.{{ $routeKey }} = @json($routes);
    @endforeach
</script>
@endif
<script>
    // Initialize grid configuration
    const gridConfig = @json($grid_data);
    
    // Generic function to reload DataTable (used by additional scripts)
    function reloadDataTable() {
        if ($.fn.DataTable && $('#ucList').length && $('#ucList').hasClass('datatable')) {
            $('#ucList').DataTable().ajax.reload(null, false);
        }
    }
    
    // Hide preloader and ensure global loader is closed on page load
    $(".preloader").hide();
    if (typeof showGlobalLoader === 'function') {
        showGlobalLoader(false);
    }
    
    // Prevent form submission on Enter key
    const form = document.getElementById('searchform');
    if (form) {
        form.addEventListener('keypress', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
            }
        });
    }
    
    // Prevent default navigation for sidelayout links
    $(document).on('click', 'a[onclick*="openSideLayout"]', function(e) {
        // Let the onclick handler run, but prevent default navigation
        var onclickAttr = $(this).attr('onclick');
        if (onclickAttr && onclickAttr.indexOf('openSideLayout') !== -1) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    $(document).delegate(".openforgotpassword", "click", function () {
            $('#forgotPassword').modal('toggle');
            var rowid = $(this).data('id');
            $(".statusTitle").html("Send New Password Email To User");
            var rand = stringGen(8);
            $("#forgot_password").val(rand);
            $("#rowid").val(rowid);
    })

    $("#sendForgotPassword").click(function () {
        event.preventDefault();
        let rowid = $("#rowid").val();
        let password = $("#forgot_password").val();
        let url = "{{ url('/send_forgotemail')}}";
        let data = {
            id: rowid,
            password: password
        };
        response = ajaxRequestPromise(url, data);
        response.then(function (res) {
            if (res.error == 0) {
                parseFormErrors(res, 'success');
                $('#forgotPassword').modal('toggle');
                getdata();
            } else {
                parseFormErrors(res, 'error');
            }
        }).catch(function (e) {
            console.log(e);
        });

    });
</script>
@endpush
@endsection
