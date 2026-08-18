@extends('layouts.app')

@section('title', 'Create Appointment')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item active">Appointments</a>

    <a href="{{ route('vet.medical-records') }}" class="sidebar-item">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item">Medication Adherence</a>
@endsection

@section('content')
    @php
        $unavailablePetIds = $unavailablePetIds ?? [];
        $hasAvailablePets = $pets->contains(fn ($pet) => !in_array($pet->pet_id, $unavailablePetIds, true));
    @endphp

    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">Create Appointment</h1>
                <p class="hero-copy">
                    Schedule a new visit, assign the consultation mode, and define the initial appointment status.
                </p>
            </div>

            <div class="action-row">
                <a href="{{ route('vet.appointments') }}" class="btn btn-secondary">Back To Appointments</a>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Appointment Details</h2>
                <p class="section-copy">All required information below matches the current veterinarian appointment flow.</p>
            </div>
        </div>

        <form action="{{ route('vet.appointments.store') }}" method="POST" class="stack">
            @csrf

            <div class="form-grid">
                <div class="field">
                    <label class="field-label" for="pet_id">Pet</label>
                    <select id="pet_id" name="pet_id" class="field-control" required>
                        <option value="">Select a pet</option>
                        @foreach ($pets as $pet)
                            @php
                                $isUnavailable = in_array($pet->pet_id, $unavailablePetIds, true);
                            @endphp
                            <option value="{{ $pet->id }}" @selected(old('pet_id') == $pet->id) @disabled($isUnavailable)>
                                {{ $pet->name }} - {{ $pet->owner->name ?? 'Unknown Owner' }}{{ $isUnavailable ? ' - active appointment exists' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('pet_id')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                    <p class="section-copy" style="margin-top: 0.45rem;">
                        Pets with active appointments are unavailable until the current appointment is closed.
                    </p>
                </div>

                <div class="field">
                    <label class="field-label">Date And Time</label>
                    @include('appointments.partials.calendar-picker', [
                        'pickerId' => 'vet_create_appointment_picker',
                        'bookedAppointments' => $bookedAppointments ?? [],
                    ])
                    @error('appointment_date')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                    @error('appointment_time')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label class="field-label" for="status">Status</label>
                    <select id="status" name="status" class="field-control" required>
                        <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(old('status') === 'approved')>Approved</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                        <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="field">
                <label class="field-label" for="reason">Reason For Visit</label>
                <textarea id="reason" name="reason" class="field-control" placeholder="Describe the concern, follow-up purpose, or service requested.">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-primary" @disabled(!$hasAvailablePets)>Create Appointment</button>
                <a href="{{ route('vet.appointments') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection
