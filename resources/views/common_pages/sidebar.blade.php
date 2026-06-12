<div class="vertical-menu">
    @php
    $currentURL = \Request::fullUrl();
    $activeLink = explode('/',$currentURL);
    @endphp
    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" style="display: none;">Menu</li>
                <li>
                    <a href="{{ getProjectUrl('dashboard') }}" class="waves-effect">
                        <i class="ri-dashboard-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @php
                if (modulePermissionExists('roles')) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-user-cog"></i>
                        <span>Roles</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li> <a href="{{ getProjectUrl('role-management/add') }}">Create Role</a></li>
                        <li><a href="{{ getProjectUrl('role-management-list') }}">View Roles</a></li>
                    </ul>
                </li>
                @php
                }
                if (modulePermissionExists('users')) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-user-plus"></i>
                        <span>Users</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ getProjectUrl('user-management/add') }}">Create User</a></li>
                        <li><a href="{{ getProjectUrl('user-management-list') }}">View Users</a></li>
                    </ul>
                </li>
                @php
                }
                $masterDataItems = [
                    ['label' => 'Departments', 'value' => 'departments', 'route' => 'departments-list'],
                    ['label' => 'Projects', 'value' => 'projects', 'route' => 'projects-list'],
                ];
                $masterDataVisible = false;
                foreach ($masterDataItems as $item) {
                    if (modulePermissionExists($item['value'])) {
                        $masterDataVisible = true;
                        break;
                    }
                }
                if ($masterDataVisible) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="ri-building-2-line"></i>
                        <span>Project Tracking</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @php foreach ($masterDataItems as $value) {
                            if (modulePermissionExists($value['value'])) { @endphp
                        <li><a href="{{ getProjectUrl($value['route']) }}">{{ $value['label'] }}</a></li>
                        @php } } @endphp
                    </ul>
                </li>
                @php
                }
                @endphp
            </ul>
        </div>
    </div>
</div>
