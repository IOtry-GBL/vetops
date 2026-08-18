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
                <p class="hero-copy">
                    Manage today's schedule, review pending requests, and open consultation sessions when appointments are ready.
                </p>
            </div>

            <div class="action-row">
                <a href="{{ route('vet.appointments.create') }}" class="btn btn-primary">Create Appointment</a>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-info">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="banner banner-danger">
            {{ session('error') }}
        </div>
    @endif

    <section class="card" style="margin-top: 2rem;">
        <div class="surface-header">
            <div>
                <h2>Appointments</h2>
            </div>
            <span class="count-chip">{{ $approvedAppointments->count() }}</span>
        </div>

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 1rem;">
            <div class="widget">
                <div class="widget-title">Today</div>
                <div class="widget-value">{{ $todayAppointmentsCount }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Upcoming</div>
                <div class="widget-value">{{ $upcomingAppointmentsCount }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Pending</div>
                <div class="widget-value">{{ $pendingAppointments->count() }}</div>
            </div>
            <div class="widget">
                <div class="widget-title">Approved</div>
                <div class="widget-value">{{ $approvedAppointments->count() }}</div>
            </div>
        </div>

        <!-- FILTERS -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('vet.appointments', ['filter' => 'all']) }}" 
               class="btn" 
               style="padding: 0.625rem 1rem; font-size: 0.875rem; 
                      background-color: {{ $filter === 'all' ? '#3b82f6' : '#e5e7eb' }}; 
                      color: {{ $filter === 'all' ? 'white' : '#374151' }}; 
                      border: none; border-radius: 0.375rem; cursor: pointer; text-decoration: none;">
                All Scheduled
            </a>
            <a href="{{ route('vet.appointments', ['filter' => 'today']) }}" 
               class="btn" 
               style="padding: 0.625rem 1rem; font-size: 0.875rem; 
                      background-color: {{ $filter === 'today' ? '#3b82f6' : '#e5e7eb' }}; 
                      color: {{ $filter === 'today' ? 'white' : '#374151' }}; 
                      border: none; border-radius: 0.375rem; cursor: pointer; text-decoration: none;">
                Today
            </a>
        </div>

        @if ($approvedAppointments->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; table-layout: auto;">
                    <thead>
                        <tr style="background-color: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Pet Name</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Owner</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Date & Time</th>
                            <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Reason</th>
                            <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Status</th>
                            <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($approvedAppointments as $appointment)
                            <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s;">
                                <td style="padding: 1.25rem; color: #1f2937; font-weight: 500;">{{ $appointment->pet->name }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->owner->name ?? 'Unknown Owner' }}</td>
                                <td style="padding: 1.25rem; color: #4b5563; white-space: nowrap;">{{ $appointment->appointment_date?->format('M d, Y g:i A') ?? 'No time set' }}</td>
                                <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->reason ?? 'N/A' }}</td>
                                <td style="padding: 1.25rem; text-align: center;">
                                    <span style="display: inline-block; padding: 0.5rem 1rem; background: #10b981; color: white; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500;">Approved</span>
                                </td>
                                <td style="padding: 1.25rem; text-align: center;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                        <a href="{{ route('vet.appointments.session', $appointment) }}" class="btn btn-primary" target="_blank" rel="noopener" style="padding: 0.625rem 1rem; font-size: 0.875rem; white-space: nowrap;">Start Session</a>
                                        <form method="POST" action="{{ route('vet.appointments.dna', $appointment) }}" style="margin: 0; display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger" style="padding: 0.625rem 1rem; font-size: 0.875rem; white-space: nowrap;" onclick="return confirm('Mark this appointment as Did Not Arrive? The pet owner will see it as missed.')">Did Not Arrive</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding: 3rem; text-align: center; background-color: #f9fafb; color: #6b7280;">
                No approved appointments are ready for session work right now.
            </div>
        @endif

        <div style="margin-top: 1.5rem;">
            <h3 style="margin-bottom: 0.75rem;">Pending Requests</h3>
            @if ($pendingAppointments->isNotEmpty())
                <div style="display: grid; gap: 0.75rem;">
                    @foreach ($pendingAppointments as $appointment)
                        <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                            <strong>{{ $appointment->pet->name }}</strong>
                            <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                                {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $appointment->reason ?? 'No reason provided' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: #6b7280; margin: 0;">No pending appointment requests.</p>
            @endif
        </div>
    </section>
@endsection
