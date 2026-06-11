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
$renoKpis = $analytics['renovation']['kpis'] ?? [];
$recentCritical = $analytics['recent_critical_delays'] ?? [];
$recentEscalated = $analytics['renovation']['recent_escalated_projects'] ?? [];
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
                PDTS overview — delay tracking analytics from live data.
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
    Delay Tracking
</h5>
    </div>
</div>

@if($w('m1_kpis'))
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card primary">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-building-2-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['total_projects'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Total projects</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['open_delays'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Open delays</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card danger">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-alert-line"></i></div>
                <div class="stat-value">{{ number_format($kpis['critical_delays'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Critical / showstopper</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card success">
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
        <div class="mini-stat-card info">
            <div class="mini-stat-value">{{ number_format($kpis['delayed_projects'] ?? 0) }}</div>
            <div class="mini-stat-label">Delayed projects</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card primary">
            <div class="mini-stat-value">{{ number_format($kpis['avg_delay_days'] ?? 0, 1) }}</div>
            <div class="mini-stat-label">Avg delay days</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card warning">
            <div class="mini-stat-value">{{ number_format($kpis['open_mitigations'] ?? 0) }}</div>
            <div class="mini-stat-label">Open mitigations</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card success">
            <div class="mini-stat-value">{{ number_format($kpis['attachment_count'] ?? 0) }}</div>
            <div class="mini-stat-label">Attachments</div>
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
                Delays by category</h6>
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
            <h6> <span class="chart-icon"><i class="ri-shield-check-line me-1"></i></span> Mitigation status</h6>
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
            <h6> <span class="chart-icon"><i class="ri-error-warning-line me-1"></i></span> Recent critical delays</h6>
            @if(!empty($recentCritical))
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Delay</th>
                            <th>Project</th>
                            <th>Days</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCritical as $row)
                        <tr>
                            <td>{{ $row['title'] }}</td>
                            <td><small>{{ $row['project'] }}</small></td>
                            <td>{{ $row['days'] }}</td>
                            <td><span class="badge rounded-pill badge-soft-danger">{{ $row['severity'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted mb-0 py-4 text-center">No critical or showstopper delays recorded.</p>
            @endif
        </div>
    </div>
    @endif
</div>
@endif

@if(modulePermissionExists('delay_registers'))
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ getProjectUrl('delay-registers-list') }}" class="btn btn-primary btn-sm">Delay Register</a>
            <a href="{{ getProjectUrl('projects-list') }}" class="btn btn-outline-primary btn-sm">Projects</a>
            <a href="{{ getProjectUrl('delay-mitigations-list') }}" class="btn btn-outline-primary btn-sm">Mitigations</a>
            <a href="{{ getProjectUrl('delay-financial-impacts-list') }}" class="btn btn-outline-primary btn-sm">Financial Impact</a>
            <a href="{{ getProjectUrl('delay-attachments-list') }}" class="btn btn-outline-primary btn-sm">Attachments</a>
        </div>
    </div>
</div>
@endif
@endif

@if($showModule3)
<div class="row mb-2">
    <div class="col-12">
        <h5 class="mb-0 text-primary"><i class="ri-hospital-line me-1"></i> Renovation Monitoring</h5>
    </div>
</div>

@if($w('m3_kpis'))
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card primary">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-hospital-line"></i></div>
                <div class="stat-value">{{ number_format($renoKpis['total_projects'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Renovation projects</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card info">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-task-line"></i></div>
                <div class="stat-value">{{ number_format($renoKpis['total_tasks'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Renovation tasks</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card danger">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-alarm-warning-line"></i></div>
                <div class="stat-value">{{ number_format($renoKpis['escalated_projects'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Escalated projects</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="stat-card-Inner">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value">{{ number_format($renoKpis['delayed_tasks'] ?? 0) }}</div>
            </div>
            <p class="stat-label">Delayed tasks</p>
        </div>
    </div>
</div>
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card primary">
            <div class="mini-stat-value">{{ number_format($renoKpis['in_progress_projects'] ?? 0) }}</div>
            <div class="mini-stat-label">In-progress projects</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card info">
            <div class="mini-stat-value">{{ number_format($renoKpis['avg_task_completion'] ?? 0, 1) }}%</div>
            <div class="mini-stat-label">Avg task completion</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card warning">
            <div class="mini-stat-value">{{ number_format($renoKpis['daily_delay_logs'] ?? 0) }}</div>
            <div class="mini-stat-label">Daily delay logs</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="mini-stat-card danger">
            <div class="mini-stat-value">{{ number_format($renoKpis['avg_cost_overrun_percent'] ?? 0, 1) }}%</div>
            <div class="mini-stat-label">Avg cost overrun</div>
        </div>
    </div>
</div>
@endif

@if($w('m3_chart_project_status') || $w('m3_chart_type'))
<div class="row mb-3">
    @if($w('m3_chart_project_status'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-donut-chart-line me-1"></i> Renovation project status</h6>
            <div id="chart-reno-project-status"></div>
        </div>
    </div>
    @endif
    @if($w('m3_chart_type'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-bar-chart-horizontal-line me-1"></i> Renovation type</h6>
            <div id="chart-reno-type"></div>
        </div>
    </div>
    @endif
</div>
@endif

@if($w('m3_chart_task_status') || $w('m3_chart_task_risk') || $w('m3_chart_escalation'))
<div class="row mb-3">
    @if($w('m3_chart_task_status'))
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-task-line me-1"></i> Task status</h6>
            <div id="chart-reno-task-status"></div>
        </div>
    </div>
    @endif
    @if($w('m3_chart_task_risk'))
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-alert-line me-1"></i> Task risk level</h6>
            <div id="chart-reno-task-risk"></div>
        </div>
    </div>
    @endif
    @if($w('m3_chart_escalation'))
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-arrow-up-circle-line me-1"></i> Escalation status</h6>
            <div id="chart-reno-escalation"></div>
        </div>
    </div>
    @endif
</div>
@endif

@if($w('m3_chart_tasks_category') || $w('m3_chart_delay_trend'))
<div class="row mb-3">
    @if($w('m3_chart_tasks_category'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-bar-chart-horizontal-line me-1"></i> Tasks by category</h6>
            <div id="chart-reno-task-category"></div>
        </div>
    </div>
    @endif
    @if($w('m3_chart_delay_trend'))
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-line-chart-line me-1"></i> Renovation daily delays — last 6 months</h6>
            <div id="chart-reno-delay-trend"></div>
        </div>
    </div>
    @endif
</div>
@endif

@if($w('m3_table_escalated'))
<div class="row mb-3">
    <div class="col-12 mb-3">
        <div class="chart-card dashboardTable">
            <h6><i class="ri-error-warning-line me-1"></i> Escalated / delayed renovation projects</h6>
            @if(!empty($recentEscalated))
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Zone / Dept</th>
                            <th>Status</th>
                            <th>Escalation</th>
                            <th>Handover</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentEscalated as $row)
                        <tr>
                            <td><strong>{{ $row['code'] }}</strong> — {{ $row['name'] }}</td>
                            <td>{{ $row['zone'] }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td><span class="badge rounded-pill badge-soft-danger">{{ $row['escalation'] }}</span></td>
                            <td>{{ $row['handover'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted mb-0 py-4 text-center">No escalated or delayed renovation projects.</p>
            @endif
        </div>
    </div>
</div>
@endif

@if(modulePermissionExists('renovation_projects'))
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ getProjectUrl('renovation-projects-list') }}" class="btn btn-primary btn-sm">Renovation Projects</a>
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
                <p class="text-muted mb-0">Dashboard widgets will appear when your role has the matching dashboard widget permissions under <strong>Dashboard — Module 1</strong> or <strong>Dashboard — Module 3</strong> in role management.</p>
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
