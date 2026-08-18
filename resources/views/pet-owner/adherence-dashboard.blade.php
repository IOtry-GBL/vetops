@extends('layouts.app')

@section('sidebar')
    @if (auth()->user()->role === 'Pet Owner')
        <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item">Dashboard</a>
        <a href="{{ route('pet-owner.pets') }}" class="sidebar-item">My Pets</a>
        <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item">Appointments</a>
        <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item">Medical Records</a>
        <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item">Prescriptions</a>
        <a href="{{ route('adherence.dashboard') }}" class="sidebar-item active">Medication Reminders</a>
        <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item">Notifications</a>
        <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
    @else
        <a href="{{ route('staff.dashboard') }}" class="sidebar-item">Dashboard</a>
        <a href="{{ route('staff.queue') }}" class="sidebar-item">Appointment Queue</a>
        <a href="{{ route('staff.notifications') }}" class="sidebar-item">Notifications</a>
        <a href="{{ route('adherence.dashboard') }}" class="sidebar-item active">Medication Reminders</a>
    @endif
@endsection

@section('content')
    <div class="card">
        <h1 style="margin: 0;">Medication Reminders</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Confirm doses, review reminder timing, and keep up with your pet's treatment plan.
        </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="widget">
            <div class="widget-title">Active Reminders</div>
            <div class="widget-value">{{ $pendingNotifications }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Recent Missed</div>
            <div class="widget-value">{{ $recentMissed }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Confirmation Rate</div>
            <div class="widget-value">{{ $confirmationRate }}%</div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
        <div class="card">
            <h2 style="margin-top: 0;">Ready To Confirm</h2>
            @if ($pendingNotificationsList->isNotEmpty())
                <div style="max-height: 320px; overflow-y: auto; display: grid; gap: 0.75rem; padding-right: 0.25rem;">
                    @foreach ($pendingNotificationsList as $notification)
                        @php
                            $log = $notification->adherenceLog;
                            $petName = $log?->prescription?->medicalRecord?->pet?->name ?? $notification->medication_name;
                            $scheduledAt = $notification->scheduledAtInClinicTimezone();
                            $deadlineAt = $notification->confirmationDeadlineInClinicTimezone();
                        @endphp
                        <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                            <strong>{{ $notification->medication_name }}</strong>
                            <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                                {{ $petName }} | {{ $scheduledAt?->format('M j, Y g:i A') ?? 'N/A' }} PH
                            </p>
                            <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                                Confirm by {{ $deadlineAt?->format('g:i A') ?? 'N/A' }} PH
                            </p>
                            @if (auth()->user()->role === 'Pet Owner' && $log && $log->isAvailableForConfirmation())
                                <form action="{{ route('adherence.confirm', $notification->notification_id) }}" method="POST" style="margin-top: 0.75rem;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Confirm Intake</button>
                                </form>
                            @else
                                <span style="display: inline-block; margin-top: 0.75rem; padding: 0.3rem 0.65rem; border-radius: 999px; background: #dbeafe; color: #1d4ed8;">
                                    {{ auth()->user()->role === 'Pet Owner' ? 'Waiting for confirmation window' : 'Owner confirmation only' }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: #6b7280; margin: 0;">No active reminders right now.</p>
            @endif
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Upcoming Reminders</h2>
            @if ($upcomingNotificationsList->isNotEmpty())
                <div style="max-height: 320px; overflow-y: auto; display: grid; gap: 0.75rem; padding-right: 0.25rem;">
                    @foreach ($upcomingNotificationsList as $notification)
                        @php
                            $scheduledAt = $notification->scheduledAtInClinicTimezone();
                        @endphp
                        <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                            <strong>{{ $notification->medication_name }}</strong>
                            <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                                {{ $notification->dosage }} | {{ $scheduledAt?->format('M j, Y g:i A') ?? 'N/A' }} PH
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: #6b7280; margin: 0;">No upcoming reminders scheduled.</p>
            @endif
        </div>
    </div>
@endsection
