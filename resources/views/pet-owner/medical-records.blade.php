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
        <h1 style="margin: 0;">Medical Records</h1>
        <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
            Review clinic records, diagnoses, treatment plans, and linked prescriptions for your pets.
        </p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="widget">
            <div class="widget-title">Records</div>
            <div class="widget-value">{{ $medicalRecords->count() }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Pets Covered</div>
            <div class="widget-value">{{ $medicalRecords->pluck('pet_id')->unique()->count() }}</div>
        </div>
        <div class="widget">
            <div class="widget-title">Linked Prescriptions</div>
            <div class="widget-value">{{ $medicalRecords->sum(fn ($record) => $record->prescriptions->count()) }}</div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Record List</h2>
        @if ($medicalRecords->isNotEmpty())
            <div style="display: grid; gap: 0.9rem;">
                @foreach ($medicalRecords as $record)
                    <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.85rem; background: #fff;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: start;">
                            <div>
                                <strong>{{ $record->pet?->name ?? 'Unknown Pet' }}</strong>
                                <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                                    {{ $record->pet?->species ?? 'Unknown species' }} | {{ $record->pet?->breed ?? 'Unknown breed' }}
                                </p>
                                <p style="margin: 0.35rem 0 0 0; color: #6b7280;">
                                    Diagnosis: {{ $record->diagnosis ?: 'Not recorded yet' }}
                                </p>
                            </div>
                            <a href="{{ route('pet-owner.medical-records.details', $record) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; margin: 0;">No medical records have been posted for your pets yet.</p>
        @endif
    </div>
@endsection
