@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item">My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item active">Medical Records</a>
    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item">Prescriptions</a>
    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item">Medication Reminders</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item">Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')
    <div class="card">
        <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: start;">
            <div>
                <h1 style="margin: 0;">Medical Record Details</h1>
                <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
                    Record #{{ $record->record_id }} for {{ $record->pet?->name ?? 'Unknown Pet' }}
                </p>
            </div>
            <a href="{{ route('pet-owner.medical-records') }}" class="btn btn-secondary">Back To Records</a>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="widget">
            <div class="widget-title">Pet</div>
            <div class="widget-value" style="font-size: 1.3rem;">{{ $record->pet?->name ?? 'Unknown' }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">{{ $record->pet?->species ?? 'Unknown species' }}</p>
        </div>
        <div class="widget">
            <div class="widget-title">Owner</div>
            <div class="widget-value" style="font-size: 1.3rem;">{{ $record->pet?->owner?->name ?? 'Unknown' }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">{{ $record->pet?->owner?->email ?? 'No email' }}</p>
        </div>
        <div class="widget">
            <div class="widget-title">Consultation Date</div>
            <div class="widget-value" style="font-size: 1.1rem;">{{ $record->consultation?->consultation_date?->format('M j, Y') ?? 'N/A' }}</div>
            <p style="color: #6b7280; margin-top: 0.5rem;">{{ $record->consultation?->veterinarian?->name ? 'Dr. '.$record->consultation->veterinarian->name : 'Veterinarian not set' }}</p>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Clinical Summary</h2>
        <div style="display: grid; gap: 0.85rem;">
            <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                <strong>Diagnosis</strong>
                <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $record->diagnosis ?: 'No diagnosis recorded.' }}</p>
            </div>
            <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                <strong>Treatment Plan</strong>
                <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $record->treatment_plan ?: 'No treatment plan recorded.' }}</p>
            </div>
            <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                <strong>Vaccination Notes</strong>
                <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $record->vaccination_notes ?: 'No vaccination notes recorded.' }}</p>
            </div>
            <div style="padding: 0.85rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #f8fafc;">
                <strong>Follow-Up</strong>
                <p style="margin: 0.35rem 0 0 0; color: #6b7280;">{{ $record->follow_up_date?->format('M j, Y') ?? 'No follow-up scheduled.' }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Linked Prescriptions</h2>
        @if ($record->prescriptions->isNotEmpty())
            <div style="display: grid; gap: 0.85rem;">
                @foreach ($record->prescriptions as $prescription)
                    @php
                        $pendingLog = $prescription->adherenceLogs->firstWhere('intake_status', 'Pending');
                    @endphp
                    <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                        <strong>{{ $prescription->medication_name }}</strong>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            {{ $prescription->dosage }} | {{ $prescription->frequency }} | {{ $prescription->duration }}
                        </p>
                        <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                            Issued {{ $prescription->issued_at?->format('M j, Y g:i A') ?? 'N/A' }}
                        </p>
                        <div style="margin-top: 0.75rem;">
                            @if ($pendingLog)
                                <span style="display: inline-block; padding: 0.3rem 0.65rem; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 0.85rem;">
                                    Pending dose reminder
                                </span>
                            @else
                                <span style="display: inline-block; padding: 0.3rem 0.65rem; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 0.85rem;">
                                    No pending dose
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; margin: 0;">No prescriptions are linked to this record yet.</p>
        @endif
    </div>
@endsection
