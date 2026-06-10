@extends('layouts.template_v1')
@section('title', 'Dashboard')
@push('styles')
<link href="{{ getAssetUrl('css/dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush
@section('content')

@php
$analytics = $data['analytics'] ?? null;
$showDelay = !empty($data['show_delay_analytics']);
$showRenovation = !empty($data['show_renovation_analytics']);
$kpis = $analytics['kpis'] ?? [];
$renoKpis = $analytics['renovation']['kpis'] ?? [];
$recentCritical = $analytics['recent_critical_delays'] ?? [];
$recentEscalated = $analytics['renovation']['recent_escalated_projects'] ?? [];
@endphp

<div class="dashboard-welcome mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3 class="mb-2">Welcome back, <span class="text-primary">@php echo Auth::user()->first_name ?? 'Admin' @endphp</span></h3>
            <p class="text-muted mb-0 fs-12">PDTS overview — delay tracking, financial impact, mitigations, attachments, and renovation monitoring from live data.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="text-muted mb-0">{{ $data['role_name'] ?? 'User' }}</p>
            <small class="text-muted">Last login:
                {{ $data['last_logged_on'] ? displayCustomDateTime($data['last_logged_on']) : 'Never' }}</small>
        </div>
    </div>
</div>

@if((!empty($data['show_delay_analytics']) || !empty($data['show_renovation_analytics'])) && !empty($analytics))

@if($showDelay)
<div class="row mb-2">
    <div class="col-12">
        <h5 class="mb-0 text-primary"><i class="ri-building-2-line me-1"></i> Delay Tracking</h5>
    </div>
</div>
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

<div class="row mb-3">
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-pie-chart-2-line me-1"></i> Delays by severity</h6>
            <div id="chart-delays-severity"></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-bar-chart-horizontal-line me-1"></i> Delays by category</h6>
            <div id="chart-delays-category"></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-donut-chart-line me-1"></i> Project status</h6>
            <div id="chart-project-status"></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-shield-check-line me-1"></i> Mitigation status</h6>
            <div id="chart-mitigation-status"></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-money-dollar-circle-line me-1"></i> Financial impact split</h6>
            <div id="chart-financial-impact"></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12 mb-3">
        <div class="chart-card">
            <h6><i class="ri-line-chart-line me-1"></i> Delays logged — last 6 months</h6>
            <div id="chart-delay-trend"></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-hospital-line me-1"></i> Delays by hospital</h6>
            <div id="chart-delays-hospital"></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="chart-card dashboardTable">
            <h6><i class="ri-error-warning-line me-1"></i> Recent critical delays</h6>
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
</div>

@if(permissionexists('delay_registers_list') == '1')
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

@if($showRenovation && !empty($analytics['renovation']))
<div class="row mb-2">
    <div class="col-12">
        <h5 class="mb-0 text-primary"><i class="ri-hospital-line me-1"></i> Renovation Monitoring</h5>
    </div>
</div>
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

<div class="row mb-3">
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-donut-chart-line me-1"></i> Renovation project status</h6>
            <div id="chart-reno-project-status"></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-bar-chart-horizontal-line me-1"></i> Renovation type</h6>
            <div id="chart-reno-type"></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-task-line me-1"></i> Task status</h6>
            <div id="chart-reno-task-status"></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-alert-line me-1"></i> Task risk level</h6>
            <div id="chart-reno-task-risk"></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <h6><i class="ri-arrow-up-circle-line me-1"></i> Escalation status</h6>
            <div id="chart-reno-escalation"></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-bar-chart-horizontal-line me-1"></i> Tasks by category</h6>
            <div id="chart-reno-task-category"></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="chart-card">
            <h6><i class="ri-line-chart-line me-1"></i> Renovation daily delays — last 6 months</h6>
            <div id="chart-reno-delay-trend"></div>
        </div>
    </div>
</div>

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

@if(permissionexists('renovation_projects_list') == '1')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ getProjectUrl('renovation-projects-list') }}" class="btn btn-primary btn-sm">Renovation Projects</a>
        </div>
    </div>
</div>
@endif
@endif

@elseif(empty($analytics))
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
                <p class="text-muted mb-0">Module analytics will appear here when your role has delay tracking or renovation monitoring access.</p>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if((!empty($data['show_delay_analytics']) || !empty($data['show_renovation_analytics'])) && !empty($analytics))
<script>
    window.pdtsDashboardData = @json($analytics);
    window.pdtsDashboardFlags = {
        showDelay: @json($showDelay),
        showRenovation: @json($showRenovation)
    };
</script>
<script src="{{ getAssetUrl('js/pages/pdts-dashboard.init.js') }}"></script>
@endif
@endpush
