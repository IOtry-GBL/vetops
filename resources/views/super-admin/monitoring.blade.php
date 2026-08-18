@extends('layouts.app')

@section('sidebar')
    @include('super-admin.sidebar')
@endsection

@section('content')
    <div class="card">
        <h1 style="margin: 0;">Monitoring</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Track appointments, consultations, and system activity in one place.
        </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
        @foreach ($appointmentStats as $label => $value)
            <div class="widget">
                <div class="widget-title">{{ ucfirst($label) }} Appointments</div>
                <div class="widget-value">{{ $value }}</div>
            </div>
        @endforeach
        @foreach ($consultationStats as $label => $value)
            <div class="widget">
                <div class="widget-title">{{ ucfirst($label) }} Consultations</div>
                <div class="widget-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
        <div class="card">
            <h2 style="margin-top: 0;">Recent Appointments</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($recentAppointments as $appointment)
                    <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <strong>{{ $appointment->pet?->pet_name ?? 'Unknown pet' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $appointment->status }}
                        </p>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No appointment records found.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Recent Consultations</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse ($recentConsultations as $consultation)
                    <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                        <strong>{{ $consultation->appointment?->pet?->pet_name ?? 'Unknown pet' }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $consultation->consultation_date?->format('M j, Y g:i A') ?? 'N/A' }} | {{ $consultation->status }}
                        </p>
                    </div>
                @empty
                    <p style="color: #6b7280; margin: 0;">No consultation records found.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Recent Activity</h2>
        <div style="display: grid; gap: 0.75rem;">
            @forelse ($recentActivities as $activity)
                <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                    <strong>{{ $activity->action }}</strong>
                    <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                        {{ $activity->description ?: 'No description provided.' }}
                    </p>
                </div>
            @empty
                <p style="color: #6b7280; margin: 0;">No system activity captured yet.</p>
            @endforelse
        </div>
    </div>
@endsection
