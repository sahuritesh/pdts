<div class="vertical-menu">
    @php
    $currentURL = \Request::fullUrl();
    $activeLink = explode('/',$currentURL);
    $showFullProjects = permissionexists('projects') === '1';
    $showMyProjects = (permissionexists('my_projects') === '1'
        || permissionexists('spoc_project_access') === '1'
        || permissionexists('spoc_department_access') === '1')
        && !$showFullProjects;
    $showMyTasks = permissionexists('spoc_tasks') === '1';
    $masterDataVisible = modulePermissionExists('departments')
        || modulePermissionExists('hospitals')
        || modulePermissionExists('locations')
        || $showFullProjects
        || $showMyProjects
        || $showMyTasks;
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
                if ($masterDataVisible) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="ri-building-2-line"></i>
                        <span>Project Tracking</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @php if (modulePermissionExists('departments')) { @endphp
                        <li><a href="{{ getProjectUrl('departments-list') }}">Departments</a></li>
                        @php } if (modulePermissionExists('hospitals')) { @endphp
                        <li><a href="{{ getProjectUrl('hospitals-list') }}">Hospitals</a></li>
                        @php } if (modulePermissionExists('locations')) { @endphp
                        <li><a href="{{ getProjectUrl('locations-list') }}">Locations</a></li>
                        @php } if ($showFullProjects) { @endphp
                        <li><a href="{{ getProjectUrl('projects-list') }}">Projects</a></li>
                        @php } if ($showMyProjects) { @endphp
                        <li><a href="{{ getProjectUrl('my-projects-list') }}">My Projects</a></li>
                        @php } if ($showMyTasks) { @endphp
                        <li><a href="{{ getProjectUrl('spoc-tasks-list') }}">My Department Tasks</a></li>
                        @php } @endphp
                    </ul>
                </li>
                @php
                }
                @endphp
            </ul>
        </div>
    </div>
</div>
