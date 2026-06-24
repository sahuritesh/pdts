/**
 * Reusable in-app notifications — bell badge, dropdown, live polling.
 * Requires: jQuery, ajaxRequestWithPromise, optional toastr + openSideLayout.
 * Config: window.inAppNotificationConfig (key configurable via configKey).
 */
(function () {
    var state = {
        latestId: 0,
        pollTimer: null,
        initialized: false
    };

    function config() {
        return window.inAppNotificationConfig || {};
    }

    function pollIntervalMs() {
        var ms = parseInt(config().pollIntervalMs, 10);
        return ms > 0 ? ms : 8000;
    }

    function pollUrl() {
        var url = config().pollUrl || '';
        if (!url) {
            return '';
        }
        if (state.latestId > 0) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'since_id=' + encodeURIComponent(state.latestId);
        }
        return url;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateBadge(count) {
        var $badge = $('#inAppNotifyCount');
        if (!$badge.length) {
            return;
        }
        count = parseInt(count, 10) || 0;
        $badge.text(count);
        if (count > 0) {
            $badge.show();
        } else {
            $badge.hide();
        }
    }

    function renderList(notifications) {
        var $container = $('#inAppNotifyList');
        if (!$container.length) {
            return;
        }

        if (!notifications || !notifications.length) {
            $container.html('<div class="p-3 text-center text-muted font-size-12">No new notifications</div>');
            return;
        }

        var html = '';
        notifications.forEach(function (item) {
            html += '<a href="javascript:void(0)" class="text-reset dropdown-item in-app-notification-item py-2" '
                + 'data-id="' + escapeHtml(item.id) + '" '
                + 'data-action-url="' + escapeHtml(item.action_url || '') + '" '
                + 'data-action-mode="' + escapeHtml(item.action_mode || 'redirect') + '" '
                + 'data-title="' + escapeHtml(item.title || '') + '">'
                + '<div class="d-flex">'
                + '<div class="avatar-xs me-3 flex-shrink-0">'
                + '<span class="avatar-title bg-primary rounded-circle font-size-16">'
                + '<i class="ri-notification-3-line"></i>'
                + '</span></div>'
                + '<div class="flex-grow-1 overflow-hidden">'
                + '<h6 class="mb-1 text-truncate">' + escapeHtml(item.title || 'Notification') + '</h6>'
                + '<p class="mb-0 font-size-12 text-muted text-truncate">' + escapeHtml(item.message || '') + '</p>'
                + '</div></div></a>';
        });
        $container.html(html);
    }

    function showNewToasts(items) {
        if (!items || !items.length || typeof toastr === 'undefined') {
            return;
        }

        items.slice().reverse().forEach(function (item) {
            toastr.options = {
                closeButton: true,
                progressBar: false,
                timeOut: 9000,
                extendedTimeOut: 4000
            };
            toastr.info(item.message || '', item.title || 'Notification');
        });
    }

    function applyPollResponse(res) {
        if (!res || res.error != 0) {
            return;
        }

        updateBadge(res.unread_count);
        if (res.new_items && res.new_items.length && state.initialized) {
            showNewToasts(res.new_items);
        }
        if (res.latest_id) {
            state.latestId = parseInt(res.latest_id, 10) || state.latestId;
        }
        if ($('#inAppNotifyDropdown').hasClass('show')) {
            renderList(res.notifications || []);
        }
        state.initialized = true;
    }

    function pollNotifications() {
        if (document.hidden) {
            return;
        }

        var url = pollUrl();
        if (!url || typeof ajaxRequestWithPromise !== 'function') {
            return;
        }

        ajaxRequestWithPromise(url, {}, 'in_app_notify_poll', 0, '', null, 'GET', 0, true)
            .then(applyPollResponse)
            .catch(function () {});
    }

    function loadDropdownList() {
        var url = config().pollUrl || '';
        if (!url || typeof ajaxRequestWithPromise !== 'function') {
            return;
        }

        ajaxRequestWithPromise(url, {}, 'in_app_notify_list', 0, '', null, 'GET', 0, true)
            .then(applyPollResponse)
            .catch(function () {});
    }

    function navigateNotification(item) {
        var actionUrl = item.action_url || '';
        var actionMode = item.action_mode || 'redirect';
        var title = item.title || 'Details';

        if (!actionUrl) {
            return;
        }

        if (actionMode === 'sidelayout' && typeof openSideLayout === 'function') {
            openSideLayout({}, actionUrl, title);
            return;
        }

        var target = actionUrl;
        if (target.indexOf('http://') !== 0 && target.indexOf('https://') !== 0) {
            var normalizedBase = (typeof baseURL !== 'undefined' && baseURL)
                ? ((baseURL.charAt(baseURL.length - 1) === '/') ? baseURL : baseURL + '/')
                : '/';
            target = normalizedBase + target.replace(/^\//, '');
        }
        window.location.href = target;
    }

    function markReadAndNavigate(notificationId, item, callback) {
        var markReadUrl = config().markReadUrl || '';
        if (!markReadUrl || typeof ajaxRequestWithPromise !== 'function') {
            navigateNotification(item);
            if (typeof callback === 'function') {
                callback();
            }
            return;
        }

        ajaxRequestWithPromise(markReadUrl, { notification_id: notificationId }, 'in_app_notify_read', 0, '', null, 'POST', 0, true)
            .then(function (res) {
                if (res && res.unread_count !== undefined) {
                    updateBadge(res.unread_count);
                }
                navigateNotification(item);
                if (typeof callback === 'function') {
                    callback();
                }
            })
            .catch(function () {
                navigateNotification(item);
                if (typeof callback === 'function') {
                    callback();
                }
            });
    }

    function bindEvents() {
        $(document).on('click', '.in-app-notification-bell', function () {
            loadDropdownList();
        });

        $(document).on('click', '.in-app-notification-item', function (e) {
            e.preventDefault();
            var $item = $(this);
            var notificationId = parseInt($item.data('id'), 10);
            if (!notificationId) {
                return;
            }

            var payload = {
                action_url: String($item.data('action-url') || ''),
                action_mode: String($item.data('action-mode') || 'redirect'),
                title: String($item.data('title') || '')
            };

            $item.addClass('pe-none opacity-75');
            markReadAndNavigate(notificationId, payload, function () {
                $item.remove();
                pollNotifications();
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollNotifications();
            }
        });
    }

    function init() {
        if (!$('#inAppNotifyCount').length) {
            return;
        }

        bindEvents();
        pollNotifications();
        state.pollTimer = setInterval(pollNotifications, pollIntervalMs());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
