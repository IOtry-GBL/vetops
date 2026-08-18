@extends('layouts.app')

@section('title', 'Veterinarian Dashboard')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item active">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('vet.medical-records') }}" class="sidebar-item">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item">Medication Adherence</a>
@endsection

@section('content')
    <div class="card">
        <h1>Welcome, {{ auth()->user()->name }}!</h1>
        <p>Track your cases, manage appointments, and monitor patient adherence.</p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="widget">
            <div class="widget-title">Today's Appointments</div>
            <div class="widget-value">{{ $todayAppointments }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Scheduled for today</p>
        </div>
        <div class="widget">
            <div class="widget-title">Active Prescriptions</div>
            <div class="widget-value">{{ $totalPrescriptions }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Linked to patient records</p>
        </div>
        <div class="widget">
            <div class="widget-title">Medication Adherence</div>
            <div class="widget-value" style="color: #3b82f6;">{{ $adherenceRate }}%</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Across tracked prescriptions</p>
        </div>
        <div class="widget">
            <div class="widget-title">Low Adherence Cases</div>
            <div class="widget-value">{{ $lowAdherenceCases->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Needs follow-up</p>
        </div>
    </div>

    <div class="card">
        <h2>Quick Links</h2>
        <div class="action-row">
            <a href="{{ route('vet.appointments') }}" class="btn btn-primary">Manage Appointments</a>
            <a href="{{ route('vet.appointments.create') }}" class="btn btn-secondary">Create Appointment</a>
            <a href="{{ route('vet.medical-records') }}" class="btn btn-secondary">Medical Records</a>
            <a href="{{ route('vet.prescriptions') }}" class="btn btn-secondary">E-Prescriptions</a>
            <a href="{{ route('vet.adherence-monitoring') }}" class="btn btn-secondary">Medication Adherence</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Upcoming Schedule</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($upcomingAppointments as $appointment)
                    <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <strong>{{ $appointment->pet?->name ?? 'Unknown Pet' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $appointment->status }}
                        </p>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No upcoming appointments in your queue.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Recent Consultations</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($recentConsultations as $consultation)
                    <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <strong>{{ $consultation->appointment?->pet?->name ?? 'Unknown Pet' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $consultation->consultation_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $consultation->status }}
                        </p>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No recent consultations recorded.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Recent Prescriptions</h2>
        <div style="display: grid; gap: 0.75rem;">
            @forelse ($recentPrescriptions as $prescription)
                <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                    <strong>{{ $prescription->medication_name }}</strong>
                    <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                        {{ $prescription->medicalRecord?->pet?->name ?? 'Unknown Pet' }} | {{ $prescription->issued_at?->format('M j, Y g:i A') ?? 'N/A' }}
                    </p>
                </div>
            @empty
                <p style="color: #6b7280; margin: 0;">No prescriptions have been issued yet.</p>
            @endforelse
        </div>
    </div>
@endsection
