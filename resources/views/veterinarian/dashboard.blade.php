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

    <div class="grid">
        <div class="widget">
            <div class="widget-title">Emergency Alerts</div>
            <div class="widget-value" style="color: var(--color-emergency);">{{ $emergencies->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Urgent cases</p>
        </div>

        <div class="widget">
            <div class="widget-title">Triage Queue</div>
            <div class="widget-value" style="color: #f59e0b;">{{ $triage->count() }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">High priority</p>
        </div>

        <div class="widget">
            <div class="widget-title">Medication Adherence</div>
            <div class="widget-value" style="color: #3b82f6;">{{ $adherenceRate }}%</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">Across {{ $totalPrescriptions }} prescriptions</p>
        </div>
    </div>

    @if($emergencies->count() > 0)
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="margin: 0;">Emergency Cases ({{ $emergencies->count() }})</h2>
            </div>

            <div class="table-wrap">
                <table class="app-table">
                    <thead>
                        <tr><th>Added</th><th>Pet</th><th>Symptom</th><th>Level</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach($emergencies->sortByDesc('created_at')->take(10) as $emergency)
                            <tr>
                                <td>{{ $emergency->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td>{{ $emergency->pet->name ?? 'Patient' }}</td>
                                <td>{{ $emergency->chief_symptom ?? 'No details recorded' }}</td>
                                <td><span class="status-badge status-danger">{{ $emergency->concern_level }}</span></td>
                                <td><a href="{{ route('vet.appointments') }}" class="btn btn-primary" style="min-height: 36px; padding: 0.45rem 0.75rem;">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="surface-header">
            <div>
                <h2>Recent Appointments</h2>
                <p class="section-copy">Latest 10 assigned appointment records.</p>
            </div>
            <a href="{{ route('vet.appointments') }}" class="btn btn-secondary">See All</a>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr><th>Date</th><th>Time</th><th>Pet</th><th>Owner</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($recentAppointments as $appointment)
                        <tr>
                            <td>{{ $appointment->appointment_date?->format('M d, Y') ?? 'TBD' }}</td>
                            <td>{{ $appointment->appointment_date?->format('h:i A') ?? 'TBD' }}</td>
                            <td>{{ $appointment->pet?->name ?? 'Unknown Pet' }}</td>
                            <td>{{ $appointment->pet?->owner?->name ?? 'Unknown Owner' }}</td>
                            <td><span class="status-badge status-open">{{ $appointment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: #6b7280;">No appointment records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="surface-header">
            <div>
                <h2>Recent Medical Records</h2>
                <p class="section-copy">Latest 10 medical records in the clinic file.</p>
            </div>
            <a href="{{ route('vet.medical-records') }}" class="btn btn-secondary">See All</a>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr><th>Created</th><th>Pet</th><th>Owner</th><th>Diagnosis</th><th>Follow-Up</th></tr>
                </thead>
                <tbody>
                    @forelse($recentMedicalRecords as $record)
                        <tr>
                            <td>{{ $record->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>{{ $record->pet?->name ?? 'Unknown Pet' }}</td>
                            <td>{{ $record->pet?->owner?->name ?? 'Unknown Owner' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($record->diagnosis ?: 'No diagnosis recorded', 90) }}</td>
                            <td>{{ $record->follow_up_date?->format('M d, Y') ?? 'Not scheduled' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: #6b7280;">No medical records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="surface-header">
            <div>
                <h2>Recent E-Prescriptions</h2>
                <p class="section-copy">Latest 10 prescription records.</p>
            </div>
            <a href="{{ route('vet.prescriptions') }}" class="btn btn-secondary">See All</a>
        </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr><th>Issued</th><th>Pet</th><th>Medication</th><th>Dosage</th><th>Duration</th></tr>
                </thead>
                <tbody>
                    @forelse($recentPrescriptions as $prescription)
                        <tr>
                            <td>{{ $prescription->issued_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>{{ $prescription->medicalRecord?->pet?->name ?? 'Unknown Pet' }}</td>
                            <td>{{ $prescription->medication_name }}</td>
                            <td>{{ $prescription->dosage }}</td>
                            <td>{{ $prescription->duration }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: #6b7280;">No prescriptions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Quick Links</h2>
        <div class="action-row">
            <a href="{{ route('vet.appointments') }}" class="btn btn-primary">Manage Appointments</a>
            <a href="{{ route('vet.medical-records') }}" class="btn btn-secondary">Medical Records</a>
            <a href="{{ route('vet.prescriptions') }}" class="btn btn-secondary">E-Prescriptions</a>
            <a href="{{ route('vet.adherence-monitoring') }}" class="btn btn-secondary">Medication Adherence</a>
        </div>
    </div>
@endsection
