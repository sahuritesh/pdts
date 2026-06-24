<div class="dropdown d-inline-block">
    <button type="button"
        class="btn header-item noti-icon waves-effect in-app-notification-bell"
        id="page-header-notifications-dropdown"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="ri-notification-3-line"></i>
        <span class="badge rounded-pill bg-danger in-app-notification-count" id="inAppNotifyCount" style="display:none;">0</span>
    </button>

    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 in-app-notification-dropdown"
        id="inAppNotifyDropdown"
        aria-labelledby="page-header-notifications-dropdown">
        <div class="p-3 border-bottom">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0"> Notifications </h6>
                </div>
                <div class="col-auto">
                    <a href="{{ function_exists('getProjectUrl') ? getProjectUrl(config('in_app_notifications.routes.list', 'in-app-notifications/list')) : url('in-app-notifications/list') }}" class="small">View all</a>
                </div>
            </div>
        </div>
        <div class="notificationsScroll" data-simplebar style="max-height: 280px;" id="inAppNotifyList">
            <div class="p-3 text-center text-muted font-size-12">Loading…</div>
        </div>
    </div>
</div>
