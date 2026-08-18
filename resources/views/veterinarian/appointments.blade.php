@extends('layouts.app')

@section('title', 'Appointments')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item active">Appointments</a>
    <a href="{{ route('vet.medical-records') }}" class="sidebar-item">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item">Medication Adherence</a>
@endsection

@section('content')
    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">Appointments</h1>
                <p class="hero-copy">Review appointment data in tables, start approved sessions, and mark missed visits.</p>
            </div>
            <div class="action-row">
                <a href="{{ route('vet.appointments.create') }}" class="btn btn-primary">Create Appointment</a>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-info">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="banner banner-danger">{{ session('error') }}</div>
    @endif

    <section class="card">
        <form method="GET" action="{{ route('vet.appointments') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="search">Search appointments</label>
                <input id="search" name="search" value="{{ $search }}" class="field-control" placeholder="Pet, owner, reason, or mode">
            </div>
            <div class="field" style="align-self: end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if($search !== '')
                    <a href="{{ route('vet.appointments') }}" class="btn btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Pending Appointments</h2>
                <p class="section-copy">Showing {{ $pendingAppointments->count() }} of {{ $totalPendingAppointments }} pending records.</p>
            </div>
            @if(!$seeAll && $totalPendingAppointments > 10)
                <a href="{{ route('vet.appointments', ['search' => $search, 'see_all' => 1]) }}" class="btn btn-secondary">See All</a>
            @endif
        </div>

        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Pet</th>
                        <th>Owner</th>
                        <th>Mode</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingAppointments as $appointment)
                        <tr>
                            <td>{{ $appointment->appointment_date?->format('M d, Y') ?? 'TBD' }}</td>
                            <td>{{ $appointment->appointment_date?->format('h:i A') ?? 'TBD' }}</td>
                            <td>{{ $appointment->pet->name }}<br><span class="muted-copy">{{ $appointment->pet->species }}</span></td>
                            <td>{{ $appointment->pet->owner->name ?? 'Unknown Owner' }}</td>
                            <td>{{ $appointment->consultation_mode }}</td>
                            <td>{{ $appointment->reason ?? 'N/A' }}</td>
                            <td><span class="status-badge status-pending">Pending</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #6b7280;">No pending appointments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Approved Appointments</h2>
                <p class="section-copy">Showing {{ $approvedAppointments->count() }} of {{ $totalApprovedAppointments }} approved records.</p>
            </div>
            @if(!$seeAll && $totalApprovedAppointments > 10)
                <a href="{{ route('vet.appointments', ['search' => $search, 'see_all' => 1]) }}" class="btn btn-secondary">See All</a>
            @endif
        </div>

        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Pet</th>
                        <th>Owner</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvedAppointments as $appointment)
                        <tr>
                            <td>{{ $appointment->appointment_date?->format('M d, Y') ?? 'TBD' }}</td>
                            <td>{{ $appointment->appointment_date?->format('h:i A') ?? 'TBD' }}</td>
                            <td>{{ $appointment->pet->name }}<br><span class="muted-copy">{{ $appointment->pet->species }}</span></td>
                            <td>{{ $appointment->pet->owner->name ?? 'Unknown Owner' }}</td>
                            <td>{{ $appointment->reason ?? 'N/A' }}</td>
                            <td><span class="status-badge status-approved">Approved</span></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('vet.appointments.session', $appointment) }}" class="btn btn-primary" target="_blank" rel="noopener" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem;">Start</a>
                                    <form method="POST" action="{{ route('vet.appointments.dna', $appointment) }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem;" onclick="return confirm('Mark this appointment as Did Not Arrive?')">DNA</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #6b7280;">No approved appointments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
