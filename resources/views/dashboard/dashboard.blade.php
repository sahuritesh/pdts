@extends('layouts.template_v1')
@section('title', 'Dashboard')
@push('styles')
<link href="{{ getAssetUrl('css/dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush
@section('content')

@php
$analytics = $data['analytics'] ?? null;
$widgets = $data['widgets'] ?? [];
$showModule1 = !empty($data['show_module1']);
$showModule3 = !empty($data['show_module3']);
$hasWidgets = !empty($data['has_dashboard_widgets']);
$w = function ($key) use ($widgets) { return !empty($widgets[$key]); };
$kpis = $analytics['kpis'] ?? [];
$recentDelayedDepts = $analytics['recent_delayed_departments'] ?? [];
$drillLinks = $data['drill_links'] ?? [];
@endphp

<div class="dashboard-welcome">
    <div class="welcome-left">
        <div class="welcome-icon">
           <i class="ri-bar-chart-box-line"></i>
        </div>

        <div>
            <h3>
                Welcome Back,
                <span>Admin</span>
            </h3>

            <p>
                PDTS overview — project and department tracking from live data.
            </p>
        </div>
    </div>

    <div class="welcome-right">
        <div class="admin-badge">
            <i class="ri-shield-user-line"></i>
            Super Admin
        </div>

        <small>
            <i class="ri-time-line"></i>
            Last login: 11 Jun 2026 • 01:22 PM
        </small>
    </div>
</div>

@if($hasWidgets && !empty($analytics))

@if($showModule1)
<div class="row mb-2 align-items-center">
    <div class="col-md-6">
         <h5 class="dashboard-title mb-0">
    <span class="title-icon">
        <i class="ri-building-2-line"></i>
    </span>
    Project Tracking
</h5>
    </div>
    @if(!empty($data['zones']))
    <div class="col-md-6 text-md-end">
        <form method="get" action="{{ getProjectUrl('dashboard') }}" class="d-inline-flex align-items-center gap-2">
            <label for="zone_id" class="mb-0 me-1 text-muted small">Zone:</label>
            <select name="zone_id" id="zone_id" class="form-select form-select-sm" style="width:auto; min-width:180px;" onchange="this.form.submit()">
                <option value="all" @if(empty($data['selected_zone_id'])) selected @endif>All zones</option>
                @foreach($data['zones'] as $zone)
                <option value="{{ $zone['id'] }}" @if(($data['selected_zone_id'] ?? '') == $zone['id']) selected @endif>{{ $zone['label'] }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @endif
</div>

@if($w('m1_kpis'))
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['total_projects'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View all projects">
        <div class="stat-card primary">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-building-2-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['total_projects'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Total projects <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['open_delays'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View delayed departments">
        <div class="stat-card warning">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['open_delays'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Open delay logs (delayed departments) <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['departments_delayed'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View delayed departments">
        <div class="stat-card danger">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-alert-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['departments_delayed'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Departments delayed <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['total_delay_cost'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View delayed departments">
        <div class="stat-card success">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['total_delay_cost'] ?? 0, 0) }}</div>
            </div>
            <p class="stat-label">Total delay cost <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
</div>
<div class="row mb-3">

    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['delayed_projects'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View delayed projects">
        <div class="tracker-card tracker-blue">
            <div class="tracker-count">
                {{ number_format($kpis['delayed_projects'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Delayed Projects <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6>
                <p>Current delayed projects</p>
            </div>
        </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['departments_in_progress'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View departments in progress">
        <div class="tracker-card tracker-purple">
            <div class="tracker-count">
                {{ number_format($kpis['departments_in_progress'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Departments In Progress <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6>
                <p>Active department workflows</p>
            </div>
        </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['departments_completed'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" title="View completed departments">
        <div class="tracker-card tracker-orange">
            <div class="tracker-count">
                {{ number_format($kpis['departments_completed'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Departments Completed <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6>
                <p>Finished on projects</p>
            </div>
        </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['total_departments'] ?? getProjectUrl('departments-list') }}" class="dashboard-drill-link" title="View departments master">
        <div class="tracker-card tracker-green">
            <div class="tracker-count">
                {{ number_format($kpis['master_departments'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Departments (Master) <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6>
                <p>Active department types in master</p>
            </div>
        </div>
        </a>
    </div>

</div>
@endif

@if($w('m1_chart_severity') || $w('m1_chart_category'))
<div class="row mb-3">
    @if($w('m1_chart_severity'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
    <h6>
        <span class="chart-icon">
            <i class="ri-pie-chart-2-line"></i>
        </span>
        Delays by Severity
    </h6>

    <div id="chart-delays-severity"></div>
</div>
    </div>
    @endif
    @if($w('m1_chart_category'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6>
                <span class="chart-icon"><i class="ri-bar-chart-horizontal-line me-1"></i> </span>
                Delayed departments</h6>
            <div id="chart-delays-category"></div>
        </div>
    </div>
    @endif
</div>
@endif

@if($w('m1_chart_project_status') || $w('m1_chart_mitigation') || $w('m1_chart_financial'))
<div class="row mb-3">
    @if($w('m1_chart_project_status'))
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6>
                 <span class="chart-icon"><i class="ri-donut-chart-line me-1"></i> </span>
                Project status</h6>
            <div id="chart-project-status"></div>
        </div>
    </div>
    @endif
    @if($w('m1_chart_mitigation'))
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6> <span class="chart-icon"><i class="ri-stack-line me-1"></i></span> Department execution status</h6>
            <div id="chart-mitigation-status"></div>
        </div>
    </div>
    @endif
    @if($w('m1_chart_financial'))
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6> <span class="chart-icon"><i class="ri-money-dollar-circle-line me-1"></i></span> Financial impact split</h6>
            <div id="chart-financial-impact"></div>
        </div>
    </div>
    @endif
</div>
@endif

@if($w('m1_chart_trend'))
<div class="row mb-3">
    <div class="col-12 mb-3">
        <div class="chart-card">
            <h6> <span class="chart-icon"><i class="ri-line-chart-line me-1"></i></span> Delays logged — last 6 months</h6>
            <div id="chart-delay-trend"></div>
        </div>
    </div>
</div>
@endif

@if($w('m1_chart_zone'))
<div class="row mb-3">
    <div class="col-12 mb-3">
        <div class="chart-card">
            <h6><span class="chart-icon"><i class="ri-map-pin-line me-1"></i></span> Zone-wise metrics</h6>
            <div id="chart-zone-metrics"></div>
        </div>
    </div>
</div>
@endif

@if($w('m1_chart_hospital') || $w('m1_table_critical'))
<div class="row mb-3">
    @if($w('m1_chart_hospital'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6> <span class="chart-icon"><i class="ri-hospital-line me-1"></i></span> Delays by hospital</h6>
            <div id="chart-delays-hospital"></div>
        </div>
    </div>
    @endif
    @if($w('m1_table_critical'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card dashboardTable">
            <h6> <span class="chart-icon"><i class="ri-error-warning-line me-1"></i></span> Delayed departments
            <a href="{{ ($drillLinks['departments_delayed'] ?? '') }}" class="chart-drill-hint text-decoration-none">View all</a></h6>
            @if(!empty($recentDelayedDepts))
            <div class="custom-table-wrapper">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>
                        <i class="ri-error-warning-line"></i>
                        Department
                    </th>
                    <th>
                        <i class="ri-building-2-line"></i>
                        Project
                    </th>
                    <th>
                        <i class="ri-hospital-line"></i>
                        Hospital
                    </th>
                    <th>
                        <i class="ri-time-line"></i>
                        Days
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach($recentDelayedDepts as $row)
                <tr class="dashboard-drill-row" @if(!empty($row['url'])) data-href="{{ $row['url'] }}" @endif>
                    <td><div class="delay-title">{{ $row['department'] }}</div></td>
                    <td><span class="project-name">{{ $row['project'] }}</span></td>
                    <td>{{ $row['hospital'] }}</td>
                    <td><span class="days-badge">{{ $row['days'] }} Days</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
            @else
            <p class="text-muted mb-0 py-4 text-center">No delayed departments right now.</p>
            @endif
        </div>
    </div>
    @endif
</div>
@endif

@if(modulePermissionExists('projects'))
<div class="row mb-4">
    <div class="col-12">
        <div class="quick-actions">
            <a href="{{ getProjectUrl('projects/wizard/new') }}" class="quick-btn quick-primary">
                <i class="ri-add-circle-line"></i>
                <span>New Project</span>
            </a>
            <a href="{{ getProjectsListingUrl() }}" class="quick-btn quick-project">
                <i class="ri-building-2-line"></i>
                <span>Projects</span>
            </a>
            @if(modulePermissionExists('departments'))
            <a href="{{ getProjectUrl('departments-list') }}" class="quick-btn quick-mitigation">
                <i class="ri-stack-line"></i>
                <span>Departments</span>
            </a>
            @endif
            @if(permissionexists('spoc_tasks') === '1')
            <a href="{{ getProjectUrl('spoc-tasks-list') }}" class="quick-btn quick-mitigation">
                <i class="ri-task-line"></i>
                <span>My Tasks</span>
            </a>
            @endif
            @if((permissionexists('my_projects') === '1' || permissionexists('spoc_project_access') === '1' || permissionexists('spoc_department_access') === '1') && permissionexists('projects') !== '1')
            <a href="{{ getProjectsListingUrl() }}" class="quick-btn quick-project">
                <i class="ri-building-2-line"></i>
                <span>My Projects</span>
            </a>
            @endif
        </div>
    </div>
</div>
@endif

@if(permissionexists('spoc_tasks') === '1' && permissionexists('projects') !== '1')
<div class="row mb-4">
    <div class="col-12">
        <div class="quick-actions">
            <a href="{{ getProjectUrl('spoc-tasks-list') }}" class="quick-btn quick-primary">
                <i class="ri-task-line"></i>
                <span>My Department Tasks</span>
            </a>
        </div>
    </div>
</div>
@endif
@endif

@else
<div class="row mb-3">
    <div class="col-lg-6 col-md-6 mb-3">
        <div class="stat-card primary">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value">{{ number_format($data['total_users'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Active users</p>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 mb-3">
        <div class="stat-card info">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                <div class="stat-value">{{ number_format($data['active_roles'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Active roles</p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-0">Dashboard widgets will appear when your role has the matching permissions under <strong>Dashboard — Project Tracking</strong> in role management.</p>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if($hasWidgets && !empty($analytics))
<script>
    window.pdtsDashboardData = @json($analytics);
    window.pdtsDashboardWidgets = @json($widgets);
</script>
<script src="{{ getAssetUrl('js/pages/pdts-dashboard.init.js') }}?v=1.2"></script>
<script>
$(document).on('click', '.dashboard-drill-row[data-href]', function() {
    var url = $(this).data('href');
    if (url) {
        window.location.href = url;
    }
});
</script>
@endif
@endpush
