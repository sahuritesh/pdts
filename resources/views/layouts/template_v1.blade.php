<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ env('APP_NAME', 'Project Delay Tracking System') }} - {{$pageTitle??""}}</title>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="robots" content="noodp">
    <!-- App favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ getAssetUrl('images/favicon.svg') }}">
    <link rel="shortcut icon" href="{{ getAssetUrl('images/favicon.svg') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- jquery.vectormap css -->
    <link href="{{ getAssetUrl('libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css') }}"
        rel="stylesheet" type="text/css" />

    <!-- DataTables -->
    <link href="{{ getAssetUrl('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ getAssetUrl('libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ getAssetUrl('libs/datatables.net-select-bs4/css/select.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <!-- Responsive Table css -->
    <link href="{{ getAssetUrl('libs/admin-resources/rwd-table/rwd-table.min.css') }}" rel="stylesheet"
        type="text/css" />
    <!-- Responsive datatable examples -->

    <link href="{{ getAssetUrl('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}"
        rel="stylesheet" type="text/css" />
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Bootstrap Css -->
    <!-- Icons Css -->
    <link rel="stylesheet" href="{{ getAssetUrl('css/select2.min.css') }}">
    {{-- FontAwesome 5 (required for existing `fas fa-*` usage across admin screens + icons picker) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="{{ getAssetUrl('css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ getAssetUrl('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ getAssetUrl('css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ getAssetUrl('css/custom-css.css') }}?v=2.4" rel="stylesheet" type="text/css" />
    <link href="{{ getAssetUrl('css/tinymce-content.css') }}?v=2.0" rel="stylesheet" type="text/css" />
    <!--autocomplete----->

    <link rel="stylesheet" href="{{ getAssetUrl('css/jquery-ui.css') }}">
    <!--end autocomplete----->
    <!--confirm popup--->
    <link rel="stylesheet" href="{{ getAssetUrl('css/jquery-confirm.css') }}">
    <!--end confirm popup--->
    <link href="{{ getAssetUrl('libs/toaster/toaster.css') }}" id="app-toaster" rel="stylesheet" type="text/css" />
    <link href="{{ getAssetUrl('css/bootstrap-datepicker.css') }}" id="app-datepicker" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="{{ getAssetUrl('css/app-growl.css') }}">

    <script src="{{ getAssetUrl('libs/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!--autocomplete----->
    <script src="{{ getAssetUrl('libs/jquery/jquery-ui.js') }}"></script>
    <script src="{{ getAssetUrl('libs/jquery/jquery-ui.min.js') }}"></script>
    <!--end autocomplete----->
    <!-- Datatable init js -->
    <!-- <script src="{{URL::to('/assets/js/pages/datatables.init.js')}}"></script> -->
    <script src="{{ getAssetUrl('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ getAssetUrl('js/moment.js') }}"></script>
    <script src="{{ getAssetUrl('js/daterangepicker.js') }}" defer></script>
    <link href="{{ getAssetUrl('css/daterangepicker.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
    <script>
     let baseURL = "{{ getProjectRootUrl() }}";
    let FULLDAY = 22;
    </script>
    <style>
    #toast-container>.toast-error {
        background-image: url('') !important;
    }
    
    /* Global AJAX Loader Overlay - Utility for entire project */
    .global-ajax-loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .global-ajax-loader-overlay .loader-spinner {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .global-ajax-loader-overlay .spinner-border {
        width: 3rem;
        height: 3rem;
    }
    
    /* Button Loading State - Utility for entire project */
    button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .btn-spinner {
        display: inline-flex;
        align-items: center;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }
    
    /* Global Loader Overlay - Unified for Admin and Frontend */
    .global-loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(241, 242, 243);
        z-index: 999999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .global-loader-overlay .loader-content {
        text-align: center;
    }
    
    .global-loader-overlay .loader-spinner {
        display: inline-block;
    }
    
    .global-loader-overlay .loader-spinner img {
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
</head>

<body data-sidebar="dark" class="sidebar-enable vertical-collpsed admin-page">
    <!-- <body data-layout="horizontal" data-topbar="dark"> -->
    <!-- Begin page -->
    <!-- <div class="preloader">
<img src="{{ getProjectUrl('assets/images/loader.svg') }}" alt="Logo">
</div> -->
    <div id="layout-wrapper">
        <!-- Header starts -->
        @include('common_pages.header')
        <!-- Header ends -->
        <!-- ========== Left Sidebar Start ========== -->
        @include('common_pages.sidebar')
        <!-- Left Sidebar End -->
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="pageBreadcrumbs">
                @include('common_pages.breadcrumbs')
            </div>

            <div class="page-content">
                @if(isset($data['backURL']) && $data['backURL']!='')
                <div class="col-sm-12 text-right">
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Back"
                        href="{{URL::to($data['backURL'])}}" id="Search"
                        class="btn btn-primary waves-effect waves-light searchBtn me-1">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                @endif
                @yield('content')

                <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog"
                    aria-labelledby="myLargeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="myLargeModalLabel">Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body displayDiscription">

                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div><!-- /.modal -->
            </div>
            <!-- End Page-content -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-12 text-end">
                            <script>
                            document.write(new Date().getFullYear())
                            </script> © {{ env('APP_NAME', 'Project Delay Tracking System') }}.
                        </div>
                        <!-- <div class="col-sm-6">
<div class="text-sm-end d-none d-sm-block">
Crafted with</i> bys RPWebapps.com
</div>
</div> -->
                    </div>
                </div>
            </footer>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->
    <!-- Right Sidebar -->
    <div class="right-bar">
        <div data-simplebar class="h-100">
            <div class="rightbar-title d-flex align-items-center px-3 py-4">
                <h5 class="m-0 me-2">Settings</h5>
                <a href="javascript:void(0);" class="right-bar-toggle ms-auto">
                    <i class="mdi mdi-close noti-icon"></i>
                </a>
            </div>
            <!-- Settings -->

        </div> <!-- end slimscroll-menu-->
    </div>

    <!--- common model popup----->
    <div class="modal fade" id="customModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="customModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customModalTitle"></h5>
                    <i class="fa fa-times-circle popup-close" aria-hidden="true" data-bs-dismiss="modal"
                        aria-label="Close"></i>
                </div>
                <div class="modal-body" id="customModalBody">

                </div>
            </div>
        </div>
    </div>

    <!-- IMAGE MODEL POPUP -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="width:100%;height:380px;overflow-y: auto;">
                    <img src="" class="img-responsive identity img-thumbnail">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary waves-effect waves-light me-1 "
                        data-bs-dismiss="modal">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Custom Modal starts -->
    <div class="modal fade" id="customeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="customeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width:700px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customeModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body displayCustomContent"
                    style="max-height: 370px; overflow-y: scroll;padding: 20px 20px;">
                </div>
            </div>
        </div>
    </div>
    <!-- Custom Modal ends -->
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" id="sidelayoutTriggerButton"
        style="display:none" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">Toggle right
        offcanvas</button>


    <div class="offcanvas offcanvas-end sidelayout-offcanvas" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header d-flex align-items-center">
            <h5 id="offcanvasRightLabel" class="sidelayoutTitle mb-0">Title</h5>
            <button type="button" class="btn-close popup-close flex-shrink-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="dynamicSideLayoutContent"></div>
        </div>

        <div class="offcanvas-footer text-right">
            <!-- <div class="flex-wrap gap-2 pb-3 pt-3 pe-3">
<a class="btn btn-primary">
Cancel
</a>
<button class="btn btn-primary" type="button">
Active
</button>
</div> -->
        </div>
    </div>



    <!--- end common model popup----->
    <!-- /Right-bar -->
    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>
    <!-- apexcharts -->
    <!-- <script src="{{URL::to('/assets/libs/apexcharts/apexcharts.min.js')}}"></script> -->
    <!-- jquery.vectormap map -->

    <script src="{{ getAssetUrl('libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js') }}">
    </script>

    <!-- Required datatable js -->


    <!-- Buttons examples -->
    <script src="{{ getAssetUrl('libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>

    <script src="{{ getAssetUrl('libs/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-select/js/dataTables.select.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ getAssetUrl('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ getAssetUrl('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>


    <!-- Include Bootstrap Datepicker -->
    <script src="{{ getAssetUrl('js/bootstrap-datepicker.min.js') }}"></script>

    <script src="{{ getAssetUrl('libs/toaster/toaster.js') }}"></script>
    <script src="{{ getAssetUrl('libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js') }}"></script>
    <script src="{{ getAssetUrl('js/pages/form-wizard.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ getAssetUrl('js/app.js') }}"></script>
    <!--tinymce js-->
    <script src="{{ getAssetUrl('libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ getAssetUrl('js/tinymce-utils.js') }}"></script>
    <script src="{{ getAssetUrl('js/growl.js') }}"></script>
    <script src="{{ getAssetUrl('js/ajaxPromise.js') }}"></script>
    {{-- Bump querystring to bust browser cache when common utilities change --}}
    <script src="{{ getAssetUrl('js/common.js') }}?v=2.2"></script>
    <script src="{{ getAssetUrl('js/custom_operations.js') }}"></script>
    <script src="{{ getAssetUrl('js/common-confirm.js') }}"></script>
    <!--confirm popup--->
    <script src="{{ getAssetUrl('js/jquery-confirm.js') }}"></script>
    <!-- Confirm & Alert Utility -->
    <script src="{{ getAssetUrl('js/confirm-utility.js') }}"></script>
    <script src="{{ getAssetUrl('js/select2.min.js') }}"></script>

    <script src="{{ getAssetUrl('js/formWizard.js') }}"></script>
    <!---end confirm popup-->
    <!-- apexcharts -->
    <script src="{{ getAssetUrl('libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ getAssetUrl('js/pages/dashboard.init.js') }}"></script>



    <script>
    $(document).ready(function() {
        // Reset all button states on page load (handles page refresh and browser back button)
        if (typeof resetAllButtonStates === 'function') {
            resetAllButtonStates();
        }
        
        // Ensure loader is closed on page load
        if (typeof showGlobalLoader === 'function') {
            showGlobalLoader(false);
        }
        
        /*var inactivityTime = function() {
            var time;
            var logoutTime = 15 * 60 * 1000; // 15 minutes in milliseconds

            // Reset the timer on mouse or keyboard activity
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;

            function logout() {
                // Make AJAX call to logout
                ajaxRequestWithPromise('{{ getProjectUrl("logout") }}', { _token: $('meta[name="csrf-token"]').attr('content') }, 'logout', 0, '', null, 'GET').then(function(response) {
                    // Redirect to login page or show a message
                    $("#customeModal").modal('show');
                    $(".displayCustomContent").html(
                        '<div class="alert alert-danger">Your session expired!!</div>'
                        );
                    $(".btn-close").remove();
                    
                    setTimeout(function(){
                        window.location.href = baseURL + "";
                    }, 2000);
                }).catch(function(error) {
                    // Even on error, redirect to login
                    window.location.href = baseURL + "";
                });
            }

            function resetTimer() {
                clearTimeout(time);
                time = setTimeout(logout, logoutTime);
            }
        };

        inactivityTime();*/
    });

   
    //get the norifications count
    getNotificationsCnt();
    // For new notification count automatically append
    //setInterval("getNotificationsCnt()",300000);	
    @if(Session::has('success'))
    toastr.options = {
        "closeButton": true,
        "progressBar": false
    }
    toastr.success("{{ session('success') }}");
    @endif
    @if(Session::has('error'))
    toastr.options = {
        "closeButton": true,
        "progressBar": false
    }
    toastr.error("{{ session('error') }}");
    @endif

    function getNotificationsCnt() {
        url = baseURL + "/getNotificationCnt";
        var postKey = "";
        var data = "";
        ajaxRequestPromise(url, data, postKey).then(function(response) {
            preloaderOverlay('hide');
            // ajaxRequestPromise already returns a parsed object, no need to JSON.parse
            var res = response;
            //console.log(res.noticationcnt);
            $("#notifycnt").text(res.noticationcnt);
        }).catch(function(err) {
            console.log(err);
            preloaderOverlay('hide');
        })
    }
    $(document).on('click', '.notificationCnt', function() {
        getNotifications();
    });

    $(document).on('click', '.notification-item', function() {
        var notify_id = $(this).attr("data-value");
        var notify_type = $(this).attr("data-type");
        var notify_type_id = $(this).attr("data-typeid");
        updateNotificaionStatus(notify_id, notify_type, notify_type_id);
        if (notify_type == 'Proposal Change') {
            //alert('Preoreproep'); return false;
            postKey = '';
            url = baseURL + "/getNotification";
            let data = {
                notify_id: notify_id
            };
            ajaxRequestPromise(url, data, postKey).then(function(response) {
                preloaderOverlay('hide');
                // ajaxRequestPromise already returns a parsed object, no need to JSON.parse
                var res = response;
                //console.log(res.html);return false;
                if (res.error == 0) {
                    $('#customModalBody').html(res.html);
                    $('#customModal').modal('toggle');

                }
            }).catch(function(err) {
                console.log(err);
                preloaderOverlay('hide');
            })
        }

    });


    function getNotifications() {
        url = baseURL + "/getNotifications";
        var postKey = "";
        var data = "";
        $('#display').html('');
        ajaxRequestPromise(url, data, postKey).then(function(response) {
            preloaderOverlay('hide');
            // ajaxRequestPromise already returns a parsed object, no need to JSON.parse
            var res = response;
            if (res.error == 0) {
                $('#display').html(res.html);
            }
        }).catch(function(err) {
            console.log(err);
            preloaderOverlay('hide');
        })

    }

    function updateNotificaionStatus(notify_id, notify_type, type_id) {
        url = baseURL + "/updateNotificationStatus";
        var postKey = "";
        let data = {
            id: notify_id,
        };
        ajaxRequestPromise(url, data, postKey).then(function(response) {
            preloaderOverlay('hide');
            if (notify_type != 'Proposal Change') {
                window.location = baseURL + '/view-notification/' + notify_type + '/' + notify_id + '/' +
                    type_id;
            } else {
                return false;
            }
        }).catch(function(err) {
            console.log(err);
            preloaderOverlay('hide');
        })

    }

    function initializeDaterangepicker() {
        let mindate = new Date(new Date().getFullYear()-1, 0, 1);
        let maxdate = new Date(new Date().getFullYear(), 11, 31);
        let startdata = moment().startOf('month');
        let enddate =  moment().endOf('month');
        if($('input[name="daterange"]').hasClass('default')){
            defaultDateSelected = new Date();
            startdata = moment();
            enddate = moment();
        }
        $('input[name="daterange"]').daterangepicker({
            opens: 'left',
            minDate: mindate,
            maxDate: maxdate,
            startDate: startdata,
            endDate:enddate,
            changeMonth: true,
            changeYear: true,
        }, function(start, end, label) {
            //console.log(start + '' + end + '' + label);
        });
    }
    </script>

    @stack('scripts')
    @stack('child-scripts')
    
    <!-- Global Modal Utility -->
    @include('components.global-modal')
    <script src="{{ getAssetUrl('js/modal-utility.js') }}"></script>
    
    <!-- Firebase Web SDK for Push Notifications -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>
    <script>
        // Set current user ID for web push notifications (if logged in)
        @if(Auth::check())
        window.currentUserId = {{ Auth::id() }};
        @else
        window.currentUserId = null;
        @endif
        
        // Set static app token for anonymous registration
        @php
        $staticToken = defined('MOBILE_APP_API_TOKEN') ? MOBILE_APP_API_TOKEN : env('MOBILE_APP_API_TOKEN', '');
        @endphp
        window.STATIC_APP_TOKEN = '{{ $staticToken }}';
    </script>
    <script src="{{ getAssetUrl('js/web-push-notifications.js') }}?v=1.9"></script>
    <script>
        // Web push bootstrap intentionally disabled in current skeleton until
        // /web-push/firebase-config and related APIs are finalized.
    </script>
</body>

</html>