@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}" class="sidebar-item active">Dashboard</a>
    <a href="{{ route('admin.users') }}" class="sidebar-item">User Management</a>
    <a href="{{ route('admin.pets') }}" class="sidebar-item">Pet Management</a>
    <a href="{{ route('admin.analytics') }}" class="sidebar-item">Analytics</a>
@endsection

@section('content')
    <div class="card">
        <h1>Welcome, {{ auth()->user()->name }}!</h1>
        <p>System administration and analytics overview.</p>
    </div>

    <div class="grid">
        <div class="widget">
            <div class="widget-title">Total Appointments</div>
            <div class="widget-value">{{ $totalAppointments }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">{{ $completedAppointments }} completed</p>
        </div>

        <div class="widget">
            <div class="widget-title">Active Users</div>
            <div class="widget-value">{{ $totalUsers }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">{{ $petOwners }} owners, {{ $vets }} vets, {{ $staff }} staff</p>
        </div>

        <div class="widget">
            <div class="widget-title">Total Pets</div>
            <div class="widget-value">{{ $totalPets }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Registered in system</p>
        </div>

        <div class="widget">
            <div class="widget-title">AI Accuracy Rate</div>
            <div class="widget-value">{{ number_format($aiAccuracy, 1) }}%</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Symptom diagnosis accuracy</p>
        </div>
    </div>

    <div class="card">
        <h2>System Status</h2>
        <div style="display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>Database Connection</span>
                <span class="badge badge-monitor">Operational</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>AI Service</span>
                <span class="badge badge-monitor">Operational</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>Email Service</span>
                <span class="badge badge-monitor">Operational</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>Video Call Service</span>
                <span class="badge badge-monitor">Operational</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Recent System Activity</h2>
        <div style="display: grid; gap: 0.75rem; font-size: 0.875rem;">
            <div>OK - 23 new user registrations today</div>
            <div>OK - 156 consultations completed this month</div>
            <div>OK - AI model updated to v2.1</div>
            <div>WARNING - 2 failed login attempts detected and blocked</div>
            <div>OK - Daily backup completed successfully</div>
        </div>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="{{ route('admin.users') }}" class="btn btn-primary">Manage Users</a>
            <a href="{{ route('admin.analytics') }}" class="btn btn-secondary">View Analytics</a>
        </div>
    </div>
@endsection
