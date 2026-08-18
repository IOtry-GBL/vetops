@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item"> Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item">My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item active"> Appointments</a>
    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item">Medical Records</a>

    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item"> Prescriptions</a>
    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item">Medication Reminders</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item"> Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')
    @php
        $unavailablePetIds = $unavailablePetIds ?? [];
        $hasAvailablePets = $pets->contains(fn ($pet) => !in_array($pet->pet_id, $unavailablePetIds, true));
        $bookingEnabled = $onlineBookingEnabled ?? true;
    @endphp

    <div class="card">
        <h1>Book an Appointment</h1>
        <p>Schedule an appointment for your pet</p>
        @unless ($bookingEnabled)
            <div style="margin-top: 1rem; padding: 0.9rem 1rem; border-radius: 0.5rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">
                Online booking is currently disabled by the clinic. Please contact staff to schedule an appointment.
            </div>
        @endunless
    </div>

    <div class="card" style="max-width: 700px;">
        <form action="{{ route('pet-owner.appointments.store') }}" method="POST">
            @csrf

            <div style="margin-bottom: 1.5rem;">
                <label for="pet_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Select Pet *</label>
                <select 
                    id="pet_id" 
                    name="pet_id" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                    required
                    @disabled(!$bookingEnabled)
                >
                    <option value="">-- Choose a Pet --</option>
                    @foreach($pets as $pet)
                        @php
                            $isUnavailable = in_array($pet->pet_id, $unavailablePetIds, true);
                        @endphp
                        <option value="{{ $pet->id }}" {{ old('pet_id') == $pet->id ? 'selected' : '' }} @disabled($isUnavailable || !$bookingEnabled)>
                            {{ $pet->name }} ({{ $pet->species }}){{ $isUnavailable ? ' - active appointment exists' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('pet_id')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">
                    Pets with an active appointment are temporarily unavailable until that appointment is completed, cancelled, or marked missed.
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Select Date & Time *</label>
                @include('components.appointment-calendar', [
                    'calendarId' => 'owner-book-page-calendar',
                    'viewer' => 'client',
                    'mode' => 'booking',
                    'sideByUside' => true,
                    'bookings' => $calendarBookings,
                    'slots' => $calendarSlots,
                ])
                @error('appointment_date')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
                @error('appointment_time')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="reason" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Visit</label>
                <textarea 
                    id="reason" 
                    name="reason" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-family: inherit; resize: vertical; min-height: 100px;"
                    placeholder="e.g., Annual checkup, Vaccination, Behavioral issues"
                    @disabled(!$bookingEnabled)
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="background: #dbeafe; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
                <p style="margin: 0; font-size: 0.875rem; color: #0c4a6e;">
                    <strong>Note:</strong> Your appointment request will be reviewed by our staff. You'll receive a confirmation once it's approved.
                </p>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" @disabled(!$hasAvailablePets || !$bookingEnabled)>Request Appointment</button>
                <a href="{{ route('pet-owner.appointments') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>


@endsection
