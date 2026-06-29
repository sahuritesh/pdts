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
$taskKpis = $analytics['task_kpis'] ?? [];
$deptOpenTasks = $analytics['departments_with_open_tasks'] ?? [];
$recentDelayedDepts = $analytics['recent_delayed_departments'] ?? [];
$drillLinks = $data['drill_links'] ?? [];
$showTaskTracking = $w('m1_task_kpis') || $w('m1_chart_task_status') || $w('m1_chart_top_tasks') || $w('m1_table_dept_open_tasks');
@endphp

<div class="dashboard-welcome">
    <div class="welcome-left">
        <div class="welcome-icon">
           <i class="ri-bar-chart-box-line"></i>
        </div>

        <div>
            <h3>
                Welcome Back,
                <span>{{ $data['user_name'] ?? 'User' }}</span>
            </h3>

            <p>
                PDTS overview — project and department tracking from live data.
            </p>
        </div>
    </div>

    <div class="welcome-right">
        <div class="admin-badge">
            <i class="ri-shield-user-line"></i>
            {{ $data['role_name'] ?? 'User' }}
        </div>

        <small>
            <i class="ri-time-line"></i>
            Last login: {{ !empty($data['last_logged_on']) ? date('d M Y • h:i A', strtotime($data['last_logged_on'])) : '—' }}
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
    @if(!empty($data['zones']) || $hasWidgets)
    <div class="col-md-6 text-md-end">
        <div id="dashboard-filter-bar" class="dashboard-filter-bar">
            @if(!empty($data['zones']))
            <div class="dashboard-filter-item">
                <label for="dashboard_zone_id" class="dashboard-filter-label">Zone</label>
                <select id="dashboard_zone_id" class="form-select form-select-sm dashboard-filter-select">
                    <option value="all" @if(empty($data['selected_zone_id'])) selected @endif>All zones</option>
                    @foreach($data['zones'] as $zone)
                    <option value="{{ $zone['id'] }}" @if(($data['selected_zone_id'] ?? '') == $zone['id']) selected @endif>{{ $zone['label'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="dashboard-filter-item">
                <label for="dashboard_project_id" class="dashboard-filter-label">Project</label>
                <select id="dashboard_project_id" class="form-select form-select-sm dashboard-filter-select">
                    <option value="all" @if(empty($data['selected_project_id'])) selected @endif>All projects</option>
                    @foreach($data['projects'] ?? [] as $project)
                    <option value="{{ $project['id'] }}" @if(($data['selected_project_id'] ?? '') == $project['id']) selected @endif>{{ $project['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dashboard-filter-item">
                <label for="dashboard_date_preset" class="dashboard-filter-label">Period</label>
                <select id="dashboard_date_preset" class="form-select form-select-sm dashboard-filter-select">
                    <option value="all" @if(($data['selected_date_preset'] ?? 'all') === 'all') selected @endif>All time</option>
                    <option value="7d" @if(($data['selected_date_preset'] ?? '') === '7d') selected @endif>Last 7 days</option>
                    <option value="30d" @if(($data['selected_date_preset'] ?? '') === '30d') selected @endif>Last 30 days</option>
                    <option value="90d" @if(($data['selected_date_preset'] ?? '') === '90d') selected @endif>Last 3 months</option>
                    <option value="180d" @if(($data['selected_date_preset'] ?? '') === '180d') selected @endif>Last 6 months</option>
                    <option value="custom" @if(($data['selected_date_preset'] ?? '') === 'custom') selected @endif>Custom range</option>
                </select>
            </div>
            <div id="dashboard_custom_dates" class="dashboard-filter-dates @if(($data['selected_date_preset'] ?? 'all') !== 'custom') d-none @endif">
                <div class="dashboard-filter-item">
                    <label for="dashboard_date_from" class="dashboard-filter-label">From</label>
                    <input type="text" id="dashboard_date_from" class="form-control form-control-sm dashboard-filter-date" placeholder="dd/mm/yyyy" value="{{ !empty($data['selected_date_from']) ? date('d/m/Y', strtotime($data['selected_date_from'])) : '' }}" autocomplete="off">
                </div>
                <div class="dashboard-filter-item">
                    <label for="dashboard_date_to" class="dashboard-filter-label">To</label>
                    <input type="text" id="dashboard_date_to" class="form-control form-control-sm dashboard-filter-date" placeholder="dd/mm/yyyy" value="{{ !empty($data['selected_date_to']) ? date('d/m/Y', strtotime($data['selected_date_to'])) : '' }}" autocomplete="off">
                </div>
            </div>
            <div class="dashboard-filter-actions">
                <button type="button" id="dashboard_apply_filters" class="btn btn-sm btn-primary" title="Apply filters">
                    <i class="ri-refresh-line"></i>
                </button>
                <button type="button" id="dashboard_reset_filters" class="btn btn-sm btn-outline-secondary" title="Reset filters">
                    <i class="ri-restart-line"></i>
                </button>
            </div>
        </div>
        <div id="dashboard-filter-status" class="dashboard-filter-status text-muted small mt-1"></div>
    </div>
    @endif
</div>

@if($w('m1_kpis'))
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['total_projects'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="total_projects" title="View all projects">
        <div class="stat-card primary">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-building-2-line"></i></div>
                <div class="stat-value" data-kpi="total_projects">{{ number_format($kpis['total_projects'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Total projects <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['open_delays'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="open_delays" title="View delayed departments">
        <div class="stat-card warning">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value" data-kpi="open_delays">{{ number_format($kpis['open_delays'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Open delay logs <span class="dashboard-period-hint d-none">(in period)</span> <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['departments_delayed'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="departments_delayed" title="View delayed departments">
        <div class="stat-card danger">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-alert-line"></i></div>
                <div class="stat-value" data-kpi="departments_delayed">{{ number_format($kpis['departments_delayed'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Departments delayed <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['total_delay_cost'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="total_delay_cost" title="View delayed departments">
        <div class="stat-card success">
             <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                <div class="stat-value" data-kpi="total_delay_cost">{{ number_format($kpis['total_delay_cost'] ?? 0, 0) }}</div>
            </div>
            <p class="stat-label">Total delay cost <span class="dashboard-period-hint d-none">(in period)</span> <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
</div>
<div class="row mb-3">

    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['delayed_projects'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="delayed_projects" title="View delayed projects">
        <div class="tracker-card tracker-blue">
            <div class="tracker-count" data-kpi="delayed_projects">
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
        <a href="{{ $drillLinks['departments_in_progress'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="departments_in_progress" title="View departments in progress">
        <div class="tracker-card tracker-purple">
            <div class="tracker-count" data-kpi="departments_in_progress">
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
        <a href="{{ $drillLinks['departments_completed'] ?? getProjectsListingUrl() }}" class="dashboard-drill-link" data-drill-key="departments_completed" title="View completed departments">
        <div class="tracker-card tracker-orange">
            <div class="tracker-count" data-kpi="departments_completed">
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
        <a href="{{ $drillLinks['total_departments'] ?? getProjectUrl('departments-list') }}" class="dashboard-drill-link" data-drill-key="total_departments" title="View departments master">
        <div class="tracker-card tracker-green">
            <div class="tracker-count" data-kpi="master_departments">
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
            <h6> <span class="chart-icon"><i class="ri-line-chart-line me-1"></i></span> <span id="dashboard-trend-title">Delays logged — last 6 months</span></h6>
            <div id="chart-delay-trend"></div>
        </div>
    </div>
</div>
@endif

@if($w('m1_chart_zone'))
<div class="row mb-3 @if(!empty($data['selected_project_id'])) d-none @endif" id="dashboard-zone-chart-row">
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
            <a href="{{ ($drillLinks['departments_delayed'] ?? '') }}" class="chart-drill-hint text-decoration-none" data-drill-key="departments_delayed">View all</a></h6>
            <div id="dashboard-delayed-depts-body">
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
    </div>
    @endif
</div>
@endif

@if($showTaskTracking)
<div class="row mb-2 align-items-center mt-2">
    <div class="col-md-12">
        <h5 class="dashboard-title mb-0">
            <span class="title-icon"><i class="ri-list-check-2"></i></span>
            Task Tracking
        </h5>
    </div>
</div>

@if($w('m1_task_kpis'))
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['total_configured_tasks'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="total_configured_tasks" title="View configured tasks">
        <div class="stat-card primary">
            <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-list-check-2"></i></div>
                <div class="stat-value" data-kpi="total_configured_tasks">{{ number_format($taskKpis['total_configured_tasks'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Tasks configured (wizard) <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['master_tasks'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="master_tasks" title="View task master">
        <div class="stat-card info">
            <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-bookmark-line"></i></div>
                <div class="stat-value" data-kpi="master_tasks">{{ number_format($taskKpis['master_tasks'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Tasks in master catalog <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['tasks_in_progress'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="tasks_in_progress" title="View in-progress tasks">
        <div class="stat-card warning">
            <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-loader-4-line"></i></div>
                <div class="stat-value" data-kpi="tasks_in_progress">{{ number_format($taskKpis['tasks_in_progress'] ?? 0) }}</div>
            </div>
            <p class="stat-label">In progress <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['tasks_overdue'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="tasks_overdue" title="View overdue tasks">
        <div class="stat-card danger">
            <span class="shine"></span>
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-alarm-warning-line"></i></div>
                <div class="stat-value" data-kpi="tasks_overdue">{{ number_format($taskKpis['tasks_overdue'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Overdue (open tasks) <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></p>
        </div>
        </a>
    </div>
</div>
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['tasks_not_started'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="tasks_not_started">
        <div class="tracker-card tracker-blue">
            <div class="tracker-count" data-kpi="tasks_not_started">{{ number_format($taskKpis['tasks_not_started'] ?? 0) }}</div>
            <div class="tracker-info"><h6>Not started <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6><p>Wizard task rows</p></div>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['tasks_completed'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="tasks_completed">
        <div class="tracker-card tracker-green">
            <div class="tracker-count" data-kpi="tasks_completed">{{ number_format($taskKpis['tasks_completed'] ?? 0) }}</div>
            <div class="tracker-info"><h6>Completed <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6><p>Wizard task rows</p></div>
        </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <a href="{{ $drillLinks['tasks_on_hold'] ?? getProjectUrl('tasks-list') }}" class="dashboard-drill-link" data-drill-key="tasks_on_hold">
        <div class="tracker-card tracker-orange">
            <div class="tracker-count" data-kpi="tasks_on_hold">{{ number_format($taskKpis['tasks_on_hold'] ?? 0) }}</div>
            <div class="tracker-info"><h6>On hold <i class="ri-arrow-right-s-line dashboard-drill-icon"></i></h6><p>Wizard task rows</p></div>
        </div>
        </a>
    </div>
</div>
@endif

@if($w('m1_chart_task_status') || $w('m1_chart_top_tasks'))
<div class="row mb-3">
    @if($w('m1_chart_task_status'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><span class="chart-icon"><i class="ri-donut-chart-line"></i></span> Wizard tasks by status</h6>
            <div id="chart-wizard-task-status"></div>
        </div>
    </div>
    @endif
    @if($w('m1_chart_top_tasks'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><span class="chart-icon"><i class="ri-bar-chart-horizontal-line"></i></span> Top tasks across projects</h6>
            <div id="chart-top-wizard-tasks"></div>
        </div>
    </div>
    @endif
</div>
@endif

@if($w('m1_table_dept_open_tasks'))
<div class="row mb-3">
    <div class="col-12 mb-3">
        <div class="chart-card dashboardTable">
            <h6><span class="chart-icon"><i class="ri-building-line me-1"></i></span> Departments with open tasks</h6>
            <div id="dashboard-dept-open-tasks-body">
            @if(!empty($deptOpenTasks))
            <div class="custom-table-wrapper">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th><i class="ri-stack-line"></i> Department</th>
                                <th><i class="ri-building-2-line"></i> Project</th>
                                <th><i class="ri-list-check-2"></i> Open tasks</th>
                                <th><i class="ri-alarm-warning-line"></i> Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deptOpenTasks as $row)
                            <tr class="dashboard-drill-row" @if(!empty($row['url'])) data-href="{{ $row['url'] }}" @endif>
                                <td><div class="delay-title">{{ $row['department'] }}</div></td>
                                <td><span class="project-name">{{ $row['project'] }}</span></td>
                                <td><span class="badge bg-primary-subtle text-primary">{{ $row['open_tasks'] }}</span></td>
                                <td>
                                    @if(($row['overdue_tasks'] ?? 0) > 0)
                                    <span class="days-badge">{{ $row['overdue_tasks'] }} overdue</span>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <p class="text-muted mb-0 py-4 text-center">No departments with open tasks right now.</p>
            @endif
            </div>
        </div>
    </div>
</div>
@endif
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
            @if(modulePermissionExists('tasks'))
            <a href="{{ getProjectUrl('tasks-list') }}" class="quick-btn quick-mitigation">
                <i class="ri-list-check-2"></i>
                <span>Tasks</span>
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
    window.pdtsDashboardConfig = {
        analyticsUrl: @json(getProjectUrl('dashboard/analytics')),
        initialData: @json($analytics),
        widgets: @json($widgets),
        drillLinks: @json($drillLinks),
        filters: {
            zone_id: @json($data['selected_zone_id'] ?? null),
            project_id: @json($data['selected_project_id'] ?? null),
            date_from: @json($data['selected_date_from'] ?? null),
            date_to: @json($data['selected_date_to'] ?? null),
            date_preset: @json($data['selected_date_preset'] ?? 'all')
        },
        showZoneChart: @json(empty($data['selected_project_id']))
    };
</script>
<script src="{{ getAssetUrl('js/pages/pdts-dashboard.js') }}?v=2.1"></script>
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
