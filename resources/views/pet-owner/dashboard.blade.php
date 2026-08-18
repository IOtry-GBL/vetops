@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item active"> Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item"> My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item"> Appointments</a>
    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item"> Medical Records</a>

    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item"> Prescriptions</a>
    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item"> Medication Reminders</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item"> Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')
    <div class="card">
        <h1>Welcome, {{ auth()->user()->name }}!</h1>
        <p>Manage your pets, appointments, records, prescriptions, and medication reminders in one place.</p>
    </div>

    <div class="grid">
        <div class="widget">
            <div class="widget-title">Upcoming Appointments</div>
            <div class="widget-value">{{ $upcomingAppointments->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">
                @if($upcomingAppointments->count() > 0)
                    Next: {{ $upcomingAppointments->first()->appointment_date->diffForHumans() }}
                @else
                    No upcoming appointments
                @endif
            </p>
        </div>

        <div class="widget">
            <div class="widget-title">Total Pets</div>
            <div class="widget-value">{{ $pets->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Active pets in system</p>
        </div>

        <div class="widget">
            <div class="widget-title">Active Prescriptions</div>
            <div class="widget-value">{{ $prescriptions->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Medications to track</p>
        </div>

        <div class="widget">
            <div class="widget-title">Medical Records</div>
            <div class="widget-value">{{ $medicalRecords->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Clinic records available</p>
        </div>

        <div class="widget">
            <div class="widget-title">Pending Reminders</div>
            <div class="widget-value">{{ $activeReminders->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Medication reminders due soon</p>
        </div>
    </div>

    <div class="card">
        <h2>Your Pets ({{ $pets->count() }})</h2>
        @if($pets->count() > 0)
            <div style="overflow-x: auto; overflow-y: auto; height: 280px; border: 1px solid #e5e7eb; border-radius: 0.375rem; display: block; width: 100%; min-width: 0;">
                <table style="width: 100%; min-width: 100%; border-collapse: collapse; table-layout: auto;">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                        <tr style="background: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; white-space: normal; word-break: break-word;">Pet Name</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; white-space: normal; word-break: break-word;">Breed</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; white-space: normal; word-break: break-word;">Species</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; white-space: normal; word-break: break-word;">Age</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; white-space: normal; word-break: break-word;">Weight</th>
                            <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #374151; white-space: normal; word-break: break-word;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pets as $pet)
                            <tr style="border-bottom: 1px solid #e5e7eb; transition: background 0.2s;">
                                <td style="padding: 0.75rem; font-weight: 600; color: #111827;">{{ $pet->name }}</td>
                                <td style="padding: 0.75rem; color: #6b7280;">{{ $pet->breed }}</td>
                                <td style="padding: 0.75rem; color: #6b7280;">{{ $pet->species }}</td>
                                <td style="padding: 0.75rem; color: #6b7280;">{{ $pet->age }} years</td>
                                <td style="padding: 0.75rem; color: #6b7280;">{{ $pet->weight }}kg</td>
                                <td style="padding: 0.75rem; text-align: center;">
                                    <span class="badge badge-monitor">Active</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 1rem; text-align: center; color: #6b7280;">No pets registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: #6b7280;">No pets registered yet.</p>
        @endif
        <a href="{{ route('pet-owner.pets') }}" class="btn btn-primary" style="margin-top: 1rem;">Manage All Pets</a>
    </div>


    <div class="card">
        <h2>Medication Reminders</h2>
        @if ($activeReminders->isNotEmpty())
            <div style="max-height: 280px; overflow-y: auto; display: grid; gap: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; padding: 0.5rem;">
    @foreach ($activeReminders as $reminder)
        <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
            <strong>{{ $reminder->medication_name }}</strong>
            <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                {{ $reminder->scheduled_at?->format('M j, Y g:i A') ?? 'N/A' }}
                |
                Confirm by {{ $reminder->confirmation_deadline?->format('g:i A') ?? 'N/A' }}
            </p>
        </div>
    @endforeach
</div>
        @else
            <p style="color: #6b7280;">No active medication reminders right now.</p>
        @endif
        <a href="{{ route('adherence.dashboard') }}" class="btn btn-secondary" style="margin-top: 1rem;">Open Reminder Center</a>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="{{ route('pet-owner.symptom-checker') }}" class="btn btn-primary">Start AI Symptom Check</a>
            <a href="{{ route('pet-owner.appointments') }}" class="btn btn-secondary">Book Appointment</a>
            <a href="{{ route('pet-owner.medical-records') }}" class="btn btn-secondary">View Records</a>
        </div>
    </div>
@endsection
