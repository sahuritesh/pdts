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
                $module = 'roles';
                $res = permissionexists($module);
                if($res == 1)
                {
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
                $module = [
                ['label' =>'Create User','value' => 'users_creation','route' => 'user-management/add'],
                ['label' =>'View Users','value' => 'users_list','route'=> 'user-management-list'],
                ];
                $count = moduleexists($module);
                if($count > 0){
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-user-plus"></i>
                        <span>Users</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @php
                        foreach($module as $value){
                        $data = $value['value'];
                        $res = permissionexists($data);
                        if($res == 1){
                        @endphp
                        <li><a href="{{ getProjectUrl($value['route']) }}">{{ $value['label']}}</a></li>
                        @php
                        } }
                        @endphp
                    </ul>
                </li>
                @php
                }
                $module1Items = [
                    ['label' => 'Delay Categories', 'value' => 'delay_categories_list', 'route' => 'delay-categories-list'],
                    ['label' => 'Projects', 'value' => 'projects_list', 'route' => 'projects-list'],
                    ['label' => 'Delay Register', 'value' => 'delay_registers_list', 'route' => 'delay-registers-list'],
                    ['label' => 'Mitigations', 'value' => 'mitigations_list', 'route' => 'delay-mitigations-list'],
                    ['label' => 'Financial Impact', 'value' => 'financial_impacts_list', 'route' => 'delay-financial-impacts-list'],
                    ['label' => 'Attachments', 'value' => 'delay_attachments', 'route' => 'delay-attachments-list'],
                ];
                if (moduleexists($module1Items) > 0) {
                @endphp
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="ri-building-2-line"></i>
                        <span>Delay Tracking</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @php foreach ($module1Items as $value) {
                            if (permissionexists($value['value']) == 1) { @endphp
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
