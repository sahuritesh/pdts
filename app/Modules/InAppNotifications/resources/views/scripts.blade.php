@php
    $configKey = config('in_app_notifications.js_global_config_key', 'inAppNotificationConfig');
    $pollRoute = config('in_app_notifications.routes.poll');
    $markReadRoute = config('in_app_notifications.routes.mark_read');
    $assetVersion = config('in_app_notifications.asset_version', '1.0');
    $pollInterval = (int) config('in_app_notifications.poll_interval_ms', 8000);
@endphp
<script>
    window.{{ $configKey }} = {
        pollUrl: @json(function_exists('getProjectUrl') ? getProjectUrl($pollRoute) : url($pollRoute)),
        markReadUrl: @json(function_exists('getProjectUrl') ? getProjectUrl($markReadRoute) : url($markReadRoute)),
        pollIntervalMs: {{ $pollInterval }}
    };
</script>
<script src="{{ function_exists('getAssetUrl') ? getAssetUrl('js/in-app-notifications.js') : asset('assets/js/in-app-notifications.js') }}?v={{ $assetVersion }}"></script>
