@php 
    $sessionName = Auth::user()->name ?? '';
    use Illuminate\Support\Facades\DB;
@endphp
<header id="page-topbar" class="themeMain">
    <div class="navbar-header">
        <div class="d-flex flex-row align-items-center">
            <!-- LOGO -->
            <div class="navbar-brand-box d-flex align-items-center">
               
                <a href="{{ getProjectUrl() }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ getProjectUrl('assets/images/logo-pdts-icon.svg') }}" alt="PDTS" height="40" style="object-fit: contain;">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ getProjectUrl('assets/images/logo-pdts.svg') }}" alt="PDTS" height="50" style="object-fit: contain; max-width: 220px;">
                    </span>
                </a>
                <a href="{{ getProjectUrl() }}" class="logo logo-light mx-auto">
                    <span class="logo-sm">
                        <img src="{{ getProjectUrl('assets/images/logo-pdts-icon.svg') }}" alt="PDTS" height="40" style="object-fit: contain;">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ getProjectUrl('assets/images/logo-pdts.svg') }}" alt="{{ config('app.name') }}" height="50" style="object-fit: contain; max-width: 180px;">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect ms-2" id="vertical-menu-btn">
                <i class="ri-menu-2-line align-middle"></i>
            </button>
            <div class="page-title-box ms-3 flex-grow-1">
                <h4 class="mb-0 fw-semibold">{{$pageTitle ?? 'Dashboard'}}</h4>
            </div>
        </div>

     
        <div class="d-flex align-items-center">
            

       
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="ri-search-line"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-search-dropdown">

                    <form class="p-3">
                        <div class="mb-3 m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i
                                            class="ri-search-line"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
          <!--  <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect notificationCnt"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-notification-3-line"></i>
                    <span class="badge rounded-pill bg-danger getNotificationCnt" id="notifycnt">0</span>
                </button>
                
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">                        
                            <div class="col">
                                <h6 class="m-0"> Notifications </h6>
                            </div>
                            <div class="col-auto">
                                <a href="{{url('/getallNotifications')}}" class="small"> View All</a>
                            </div>
                        </div>
                    </div>

                    
                    <div class="notificationsScroll"  data-simplebar style="max-height: 230px;" id="display">

                    </div>
                    
                </div>
            </div> --> 
            <div class="dropdown d-inline-block user-dropdown">
                <button type="button" class="btn header-item waves-effect d-flex align-items-center" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar-circle">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="d-none d-xl-inline-block ms-2 text-start">
                            <div style="font-weight: 600; font-size: 14px; line-height: 1.2; color: var(--full-white);">
                                @php echo ucwords(substr(Auth::user()->first_name ?? 'Admin', 0, 15)) @endphp
                            </div>
                            @php
                                $role = DB::table('tbl_roles')->where('id', Auth::user()->user_type)->first();
                                $roleName = $role ? $role->role_name : 'User';
                            @endphp
                            <small style="font-size: 11px; opacity: 0.7; display: block; color: var(--full-white);">{{ $roleName }}</small>
                        </div>
                    </div>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block ms-2" style="color: var(--full-white);"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- item-->
                    <a class="dropdown-item" href="{{ getProjectUrl('myprofile') }}"><i
                            class="ri-user-line align-middle me-1"></i>Update Profile</a>
                    <a class="dropdown-item" href="{{ getProjectUrl('changepassword') }}"><i
                            class="ri-lock-password-line align-middle me-1"></i>Change Password</a>
                    @php
                        $linkedRegistrations = session('linked_registrations', []);
                        $hasMultipleRoles = hasMultipleRoles(Auth::user()->user_type, $linkedRegistrations);
                    @endphp
                    @if($hasMultipleRoles)
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{url('/select-role')}}"><i
                            class="ri-exchange-line align-middle me-1"></i>Switch Role</a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ getProjectUrl('logout') }}"><i
                            class="ri-shut-down-line align-middle me-1 text-danger"></i> Logout</a>
                </div>
            </div>
        </div>

    </div>

</header>
<!-- Custome Modal starts -->
<div class="modal fade" id="customeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="customeModalLabel" aria-hidden="true">
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
<!-- Custome Modal ends -->