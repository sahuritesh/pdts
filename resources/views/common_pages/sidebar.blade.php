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
                $module1Items = [
                    ['label' => 'Delay Categories', 'value' => 'delay_categories', 'route' => 'delay-categories-list'],
                    ['label' => 'Projects', 'value' => 'projects', 'route' => 'projects-list'],
                    ['label' => 'Delay Register', 'value' => 'delay_registers', 'route' => 'delay-registers-list'],
                    ['label' => 'Mitigations', 'value' => 'mitigations', 'route' => 'delay-mitigations-list'],
                    ['label' => 'Financial Impact', 'value' => 'financial_impacts', 'route' => 'delay-financial-impacts-list'],
                    ['label' => 'Attachments', 'value' => 'delay_attachments', 'route' => 'delay-attachments-list'],
                ];
                $module1Visible = false;
                foreach ($module1Items as $item) {
                    if (modulePermissionExists($item['value'])) {
                        $module1Visible = true;
                        break;
                    }
                }
                if ($module1Visible) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="ri-building-2-line"></i>
                        <span>Delay Tracking</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @php foreach ($module1Items as $value) {
                            if (modulePermissionExists($value['value'])) { @endphp
                        <li><a href="{{ getProjectUrl($value['route']) }}">{{ $value['label'] }}</a></li>
                        @php } } @endphp
                    </ul>
                </li>
                @php
                }
                if (modulePermissionExists('renovation_projects')) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="ri-hospital-line"></i>
                        <span>Renovation Monitoring</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ getProjectUrl('renovation-projects-list') }}">Renovation Projects</a></li>
                    </ul>
                </li>
                @php
                }
                @endphp
            </ul>
        </div>
    </div>
</div>
