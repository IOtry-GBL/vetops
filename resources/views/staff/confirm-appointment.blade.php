@extends('layouts.app')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    <div class="card">
        <h1>Confirm Appointment</h1>
        <p>Review and adjust the appointment details</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Pet Information</h2>
            <div style="display: grid; gap: 1rem;">
                <div>
                    <div style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Pet Name</div>
                    <div style="font-weight: 600; font-size: 1.125rem;">{{ $appointment->pet->name }}</div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Species</div>
                        <div>{{ $appointment->pet->species }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Age</div>
                        <div>{{ $appointment->pet->age }} years</div>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">Owner</div>
                    <div style="font-weight: 600;">{{ $appointment->pet->owner->name }}</div>
                    <div style="font-size: 0.875rem; color: #6b7280;">{{ $appointment->pet->owner->email }}</div>
                    <div style="font-size: 0.875rem; color: #6b7280;">{{ $appointment->pet->owner->phone ?? 'N/A' }}</div>
                </div>
                @if($appointment->reason)
                    <div style="background: #f3f4f6; padding: 1rem; border-radius: 0.375rem;">
                        <div style="font-size: 0.875rem; color: #6b7280; font-weight: 500; margin-bottom: 0.5rem;">Reason</div>
                        <p style="margin: 0;">{{ $appointment->reason }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Confirm & Adjust</h2>
            <form action="{{ route('staff.appointments.approve', $appointment->id) }}" method="POST">
                @csrf

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Appointment Date & Time *</label>
                    @include('appointments.partials.calendar-picker', [
                        'pickerId' => 'staff_confirm_appointment_picker',
                        'selectedDate' => old('appointment_date', $appointment->appointment_date->format('Y-m-d')),
                        'selectedTime' => old('appointment_time', $appointment->appointment_time->format('H:i')),
                        'bookedAppointments' => $bookedAppointments ?? [],
                    ])
                    @error('appointment_date')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                    @error('appointment_time')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="vet_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Assign Veterinarian</label>
                    <select 
                        id="vet_id" 
                        name="vet_id" 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                    >
                        <option value="">-- Select Vet --</option>
                        @foreach($vets as $vet)
                            <option value="{{ $vet->id }}" {{ $appointment->vet_id == $vet->id ? 'selected' : '' }}>
                                Dr. {{ $vet->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vet_id')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="status" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Status *</label>
                    <select 
                        id="status" 
                        name="status" 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                        required
                    >
                        <option value="pending" {{ strtolower($appointment->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ strtolower($appointment->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="completed" {{ strtolower($appointment->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ strtolower($appointment->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Confirm Appointment</button>
                    <a href="{{ route('staff.appointments.pending') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
