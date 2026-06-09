@extends('layouts.template_v1')
@section('title', 'Dashboard')
@push('styles')
<link href="{{ getAssetUrl('css/dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush
@section('content')

<div class="dashboard-welcome mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3 class="mb-2">Welcome back, <span class="text-primary">@php echo Auth::user()->first_name ?? 'Admin' @endphp</span></h3>
            <p class="text-muted mb-0 fs-12">Track and manage project delays from this admin panel.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <p class="text-muted mb-0">{{ $data['role_name'] ?? 'User' }}</p>
            <small class="text-muted">Last login:
                {{ $data['last_logged_on'] ? displayCustomDateTime($data['last_logged_on']) : 'Never' }}</small>
        </div>
    </div>
</div>

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
            <div class="card-header stats-header">
                <div class="stats-header-left">
                    <div class="stats-icon system-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <h5 class="stats-title mb-0">Getting started</h5>
                        <p class="stats-sub mb-0">Core modules in this build</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>User management — create and manage admin users</li>
                    <li>Role management — configure permissions per role</li>
                    <li>Settings — SMTP, payment gateway, email templates</li>
                    <li>Profile — update profile and change password</li>
                </ul>
                    <li>Module 1: Projects, delay register, mitigation, financial impact, attachments</li>
                    <li>Module 2: Early Warning System (EWS) alerts & config</li>
                    <li>Module 3: Renovation projects, tasks, daily delay logs, procurement, approvals</li>
                    <li>Module 4: Executive dashboard, delay analytics, renovation dashboard, audit trail</li>
            </div>
        </div>
    </div>
</div>

@endsection
