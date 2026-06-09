<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ env('APP_NAME', 'Project Delay Tracking System') }} - {{$pageTitle??""}}</title>
    <!-- Stylesheets -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ getAssetUrl('frontend/css/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ getAssetUrl('frontend/css/style.css') }}" rel="stylesheet">
    <link href="{{ getAssetUrl('frontend/css/responsive.css') }}" rel="stylesheet">
    <link href="{{ getAssetUrl('frontend/css/cms-pages.css') }}" rel="stylesheet">
    <link href="{{ getAssetUrl('css/reusable-modal.css') }}" rel="stylesheet">
    <link href="{{ getAssetUrl('css/tinymce-content.css') }}" rel="stylesheet" type="text/css" />

    <!-- Color Switcher Mockup -->
    <link href="{{ getAssetUrl('frontend/css/color-switcher-design.css') }}" rel="stylesheet">
    
    <!-- Toastr CSS -->
    <link href="{{ getAssetUrl('libs/toaster/toaster.css') }}" id="app-toaster" rel="stylesheet" type="text/css" />
    
    <!-- Custom CSS for error borders and global loader -->
    <style>
        input.errorBorder,
        select.errorBorder,
        textarea.errorBorder,
        .form-control.errorBorder,
        .form-select.errorBorder {
            border: 1px solid #f80a0a !important;
            border-color: #f80a0a !important;
            box-shadow: 0 0 0 0.2rem rgba(248, 10, 10, 0.25) !important;
        }
        input.errorBorder:focus,
        select.errorBorder:focus,
        textarea.errorBorder:focus,
        .form-control.errorBorder:focus,
        .form-select.errorBorder:focus {
            border-color: #f80a0a !important;
            box-shadow: 0 0 0 0.2rem rgba(248, 10, 10, 0.25) !important;
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
        
        /* Global Loader Overlay - Unified for Admin and Frontend */
        .global-loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(241, 241, 243);
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

    <link rel="icon" type="image/svg+xml" href="{{ getAssetUrl('images/favicon.svg') }}">
    <link rel="shortcut icon" href="{{ getAssetUrl('images/favicon.svg') }}">

    <!-- Responsive -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <!--[if lt IE 9]><script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script><![endif]-->
    <!--[if lt IE 9]><script src="js/respond.js"></script><![endif]-->
    @stack('styles')
</head>

<body class="hidden-bar-wrapper frontend-page">

    <!-- Global Loader Overlay - Created dynamically but also available in HTML for immediate use -->
    <div id="global-loader-overlay" class="global-loader-overlay" style="display: none;">
        <div class="loader-content">
            <div class="loader-spinner">
                <img src="{{ getProjectUrl('assets/images/loader.svg') }}" alt="Loading..." />
            </div>
        </div>
    </div>

    <div class="page-wrapper">

        <!-- Header Style One / Two -->
        @include('common_pages.frontend_header')
        <!-- End Main Header -->
        @yield('content')
         
        @include('common_pages.frontend_footer')
    </div>
    <!-- End PageWrapper -->
    <!-- Back To Top Start -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
    <!-- back to top end -->
    <script>
        // Define baseURL for frontend (used by loader utility)
        var baseURL = "{{ getProjectRootUrl() }}";
    </script>
    <script src="{{ getAssetUrl('frontend/js/jquery.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/popper.min.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/bootstrap.min.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/appear.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/parallax.min.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/tilt.jquery.min.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/jquery.paroller.min.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/owl.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/wow.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/element-in-view.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/backtotop.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/odometer.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/parallax-scroll.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/jquery.countdown.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/magnific-popup.min.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/nav-tool.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/jquery-ui.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/color-settings.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/script.js') }}"></script>
    <script src="{{ getAssetUrl('libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ getAssetUrl('js/tinymce-utils.js') }}"></script>
    <script src="{{ getAssetUrl('js/growl.js') }}"></script>
    <script src="{{ getAssetUrl('js/ajaxPromise.js') }}"></script>
    <script src="{{ getAssetUrl('js/common.js') }}?v=0.2323232"></script>
    <script src="{{ getAssetUrl('js/custom_operations.js') }}"></script>
    <script src="{{ getAssetUrl('js/common-confirm.js') }}"></script>
    <!--confirm popup--->
    <script src="{{ getAssetUrl('js/jquery-confirm.js') }}"></script>
    <!-- Confirm & Alert Utility -->
    <script src="{{ getAssetUrl('js/confirm-utility.js') }}"></script>
    <!-- Global Modal Utility -->
    @include('components.global-modal')
    <script src="{{ getAssetUrl('js/modal-utility.js') }}"></script>
    <script src="{{ getAssetUrl('js/select2.min.js') }}"></script>
    <script src="{{ getAssetUrl('libs/toaster/toaster.js') }}"></script>
    <script src="{{ getAssetUrl('frontend/js/bootstrap.bundle.min.js') }}"></script>
    
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
 @stack('scripts')
 @stack('child-scripts')
</body>

</html>