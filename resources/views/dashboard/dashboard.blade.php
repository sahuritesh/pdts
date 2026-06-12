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
<div class="row mb-2">
    <div class="col-12">
        <!-- <h5 class="mb-0 text-primary"><i class="ri-building-2-line me-1"></i> Delay Tracking</h5> -->
         <h5 class="dashboard-title mb-0">
    <span class="title-icon">
        <i class="ri-building-2-line"></i>
    </span>
    Project Tracking
</h5>
    </div>
</div>

@if($w('m1_kpis'))
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card primary">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-building-2-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['total_projects'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Total projects</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['open_delays'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Open delay entries</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card danger">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-alert-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['departments_delayed'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Departments delayed</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card success">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['total_delay_cost'] ?? 0, 0) }}</div>
            </div>
            <p class="stat-label">Total delay cost</p>
        </div>
    </div>
</div>
<div class="row mb-3">

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="tracker-card tracker-blue">
            <div class="tracker-count">
                {{ number_format($kpis['delayed_projects'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Delayed Projects</h6>
                <p>Current delayed projects</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="tracker-card tracker-purple">
            <div class="tracker-count">
                {{ number_format($kpis['departments_in_progress'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Departments In Progress</h6>
                <p>Active department workflows</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="tracker-card tracker-orange">
            <div class="tracker-count">
                {{ number_format($kpis['departments_completed'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Departments Completed</h6>
                <p>Finished on projects</p>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="tracker-card tracker-green">
            <div class="tracker-count">
                {{ number_format($kpis['total_departments'] ?? 0) }}
            </div>
            <div class="tracker-info">
                <h6>Total Departments</h6>
                <p>Across all projects</p>
            </div>
        </div>
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
            <h6> <span class="chart-icon"><i class="ri-error-warning-line me-1"></i></span> Delayed departments</h6>
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
                <tr>
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
            <a href="{{ getProjectUrl('projects-list') }}" class="quick-btn quick-project">
                <i class="ri-building-2-line"></i>
                <span>Projects</span>
            </a>
            @if(modulePermissionExists('departments'))
            <a href="{{ getProjectUrl('departments-list') }}" class="quick-btn quick-mitigation">
                <i class="ri-stack-line"></i>
                <span>Departments</span>
            </a>
            @endif
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
<script src="{{ getAssetUrl('js/pages/pdts-dashboard.init.js') }}"></script>
@endif
@endpush
