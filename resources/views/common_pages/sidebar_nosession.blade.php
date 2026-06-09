<div class="vertical-menu">
    @php
    $currentURL = \Request::fullUrl();
    $activeLink = explode('/',$currentURL);
    @endphp
    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                <li>
                    <a href="{{url('/dashboard')}}" class="waves-effect">
                        <i class="ri-dashboard-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <!-- <li>
                    <a href="{{url('/create-promotions')}}" class="waves-effect">
                        <i class="fas fa-plus"></i>
                        <span>Create Promotions</span>
                    </a>
                </li> -->
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-user-cog"></i>
                        <span>Roles</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="{{url('/role-management/add')}}">Create Role</a>
                        </li>
                        <li>
                            <a href="{{url('/role-management-list')}}">View Roles</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-user"></i>
                        <span>Users</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="{{url('/user-management/add')}}">Create User</a>
                        </li>
                        <li>
                            <a href="{{url('/user-management-list')}}">View Users</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>