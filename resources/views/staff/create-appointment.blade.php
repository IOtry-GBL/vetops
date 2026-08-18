@extends('layouts.app')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    @php
        $unavailablePetIds = $unavailablePetIds ?? [];
        $hasAvailablePets = $pets->contains(fn ($pet) => !in_array($pet->pet_id, $unavailablePetIds, true));
    @endphp

    <div class="card">
        <h1>Create New Appointment</h1>
        <p>Schedule an appointment and assign to veterinarian</p>
    </div>

    <div class="card" style="max-width: 700px;">
        <form action="{{ route('staff.appointments.store') }}" method="POST">
            @csrf

            <div style="margin-bottom: 1.5rem;">
                <label for="pet_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Pet *</label>
                <select 
                    id="pet_id" 
                    name="pet_id" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                    required
                >
                    <option value="">-- Select a Pet --</option>
                    @foreach($pets as $pet)
                        @php
                            $isUnavailable = in_array($pet->pet_id, $unavailablePetIds, true);
                        @endphp
                        <option value="{{ $pet->id }}" {{ old('pet_id') == $pet->id ? 'selected' : '' }} @disabled($isUnavailable)>
                            {{ $pet->name }} ({{ $pet->owner->name ?? 'Unknown' }}){{ $isUnavailable ? ' - active appointment exists' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('pet_id')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">
                    Only pets without an active appointment can be scheduled from this form.
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="vet_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Assign Veterinarian</label>
                <select 
                    id="vet_id" 
                    name="vet_id" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                >
                    <option value="">-- Select Vet (Optional) --</option>
                    @foreach($vets as $vet)
                        <option value="{{ $vet->id }}" {{ old('vet_id') == $vet->id ? 'selected' : '' }}>
                            Dr. {{ $vet->name }}
                        </option>
                    @endforeach
                </select>
                @error('vet_id')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Appointment Date & Time *</label>
                @include('appointments.partials.calendar-picker', [
                    'pickerId' => 'staff_create_appointment_picker',
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
                <label for="reason" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Visit</label>
                <textarea 
                    id="reason" 
                    name="reason" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-family: inherit; resize: vertical; min-height: 100px;"
                    placeholder="e.g., Annual checkup, Vaccination, Skin issue"
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="priority_level" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Priority Level *</label>
                    <select 
                        id="priority_level" 
                        name="priority_level" 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                        required
                    >
                        <option value="">-- Select Level --</option>
                        <option value="monitor" {{ old('priority_level') == 'monitor' ? 'selected' : '' }}>Monitor</option>
                        <option value="recommended" {{ old('priority_level') == 'recommended' ? 'selected' : '' }}>Recommended</option>
                        <option value="emergency" {{ old('priority_level') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                    @error('priority_level')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Status *</label>
                    <select 
                        id="status" 
                        name="status" 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;"
                        required
                    >
                        <option value="">-- Select Status --</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p style="color: var(--color-emergency); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" @disabled(!$hasAvailablePets)>Create Appointment</button>
                <a href="{{ route('staff.queue') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
