<div class="dropdown d-inline-block">
    <button type="button"
        class="btn header-item noti-icon in-app-notification-bell notify-btn-modern"
        id="page-header-notifications-dropdown"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="ri-notification-3-line"></i>
        <span class="badge rounded-pill bg-danger in-app-notification-count notify-count-modern" id="inAppNotifyCount" style="display:none;">0</span>
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
<style>

/* notification */
/* ==========================================
   MODERN NOTIFICATION BUTTON
========================================== */

.notify-btn-modern{
    position:relative;

    width:46px;
    height:46px;

    border:none;

    border-radius:14px;

    background:#f8fafc;

    border:1px solid #e2e8f0;

    display:flex;
    align-items:center;
    justify-content:center;

    cursor:pointer;

    transition:.3s ease;

    padding:0;
}

.notify-btn-modern i{
    font-size:22px;

    color:#2563eb;

    transition:.3s ease;
}

.notify-btn-modern:hover{

    background:linear-gradient(
        135deg,
        #1F8EF1,
        #00A99D
    );

    border-color:transparent;

    transform:translateY(-2px);

    box-shadow:
        0 8px 20px rgba(31,142,241,.25);
}

.notify-btn-modern:hover i{
    color:#fff;
}

/* ==========================================
   NOTIFICATION BADGE
========================================== */

.notify-count-modern{

    position:absolute !important;

    top:-6px !important;
    right:-6px;

    min-width:22px;
    height:22px;

    padding:0 6px;

    border-radius:50px;

    background:#ef4444;

    color:#fff;

    font-size:11px;
    font-weight:700;

    display:flex;
    align-items:center;
    justify-content:center;

    border:2px solid #fff;

    box-shadow:
        0 4px 10px rgba(239,68,68,.35);

    animation:notifyPulse 1.8s infinite;
}

/* ==========================================
   PULSE ANIMATION
========================================== */

@keyframes notifyPulse{

    0%{
        transform:scale(1);
        box-shadow:0 0 0 0 rgba(239,68,68,.5);
    }

    70%{
        transform:scale(1.08);
        box-shadow:0 0 0 8px rgba(239,68,68,0);
    }

    100%{
        transform:scale(1);
        box-shadow:0 0 0 0 rgba(239,68,68,0);
    }
}


/* dropdown css */
/*=====================================================
  NOTIFICATION DROPDOWN
======================================================*/

.in-app-notification-dropdown{

    width:380px;

    padding:0;

    border:none;

    border-radius:16px;

    overflow:hidden;

    background:#fff;

    box-shadow:0 15px 40px rgba(15,23,42,.12);

    animation:notificationFade .25s ease;
}

/*=====================================================
  HEADER
======================================================*/

.in-app-notification-dropdown>.border-bottom{

    padding:16px 18px !important;

    background:linear-gradient(
        135deg,
        #f8fbff,
        #eef8ff
    );

    border-bottom:1px solid #e2e8f0 !important;
}

.in-app-notification-dropdown>.border-bottom h6{

    margin:0;

    display:flex;

    align-items:center;

    gap:12px;

    font-size:16px;

    font-weight:700;

    color:#1e293b;
}

/* Header Icon */

.in-app-notification-dropdown>.border-bottom h6::before{

    content:"\ea35";

    font-family:"remixicon";

    width:38px;
    height:38px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:linear-gradient(
        135deg,
        #1F8EF1,
        #00A99D
    );

    color:#fff;

    font-size:18px;

    flex-shrink:0;
}

/* View All */

.in-app-notification-dropdown>.border-bottom .small{

    padding:7px 14px;

    border-radius:30px;

    background:#dae8f6;

    color:#1F8EF1;

    text-decoration:none;

    font-size:12px;

    font-weight:600;

    transition:.3s;
}

.in-app-notification-dropdown>.border-bottom .small:hover{

    background:#1F8EF1;

    color:#fff;
}

/*=====================================================
  BODY
======================================================*/

.notificationsScroll{

    max-height:320px;

    overflow-y:auto;

    background:#fff;
}

/* Loading */

.notificationsScroll .text-center{

    padding:35px 20px !important;

    color:#64748b !important;

    font-size:14px;
}

/*=====================================================
  LIST ITEM
======================================================*/

.notificationsScroll>a,

.notificationsScroll>.notification-item,

.notificationsScroll>.dropdown-item{

    /* display:flex; */

    align-items:flex-start;

    gap:12px;

    padding:16px 18px;

    border-bottom:1px solid #edf2f7 !important;

    transition:.3s;

    text-decoration:none;
}

.notificationsScroll>a:last-child,

.notificationsScroll>.notification-item:last-child,

.notificationsScroll>.dropdown-item:last-child{

    border-bottom:none;
}

.notificationsScroll>a:hover,

.notificationsScroll>.notification-item:hover,

.notificationsScroll>.dropdown-item:hover{

    background:#f8fbff;
}

/* Remove icon from list h6 */

.notificationsScroll h6::before{

    content:none !important;
}

/* List Heading */

.notificationsScroll h6{

    margin:0;

    font-size:13px;

    font-weight:600;

    color:#1e293b;
}

/* Description */

.notificationsScroll p{

    margin:4px 0 0;

    font-size:12px;

    color:#64748b;
}

/*=====================================================
  SCROLLBAR
======================================================*/

.notificationsScroll::-webkit-scrollbar{

    width:6px;
}

.notificationsScroll::-webkit-scrollbar-track{

    background:#f8fafc;
}

.notificationsScroll::-webkit-scrollbar-thumb{

    background:#cbd5e1;

    border-radius:20px;
}

.notificationsScroll::-webkit-scrollbar-thumb:hover{

    background:#94a3b8;
}

/*=====================================================
  ANIMATION
======================================================*/

@keyframes notificationFade{

    from{

        opacity:0;

        transform:translateY(-10px);
    }

    to{

        opacity:1;

        transform:translateY(0);
    }
}
    </style>