@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item"> Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item"> My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item active"> Appointments</a>
    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item"> Medical Records</a>
    

    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item"> Prescriptions</a>
    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item"> Medication Reminders</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item"> Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')
    @php
        $bookingEnabled = $onlineBookingEnabled ?? true;
        $unavailablePetIds = $unavailablePetIds ?? [];
        $hasAvailablePets = $pets->contains(fn ($pet) => !in_array($pet->pet_id, $unavailablePetIds, true));
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

    <form action="{{ route('pet-owner.appointments.store') }}" method="POST" id="bookingForm">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Booking Form Card -->
            <div class="card">
                <h2 style="margin-top: 0;">Book an Appointment</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <!-- Pet Selection -->
                    <div>
                        <label for="pet_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Select Pet</label>
                        <select id="pet_id" name="pet_id" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;" @disabled(!$bookingEnabled)>
                            <option value="">-- Choose a pet --</option>
                            @foreach($pets as $pet)
                                <option value="{{ $pet->id }}" @disabled(!$bookingEnabled || in_array($pet->pet_id, $unavailablePetIds, true))>{{ $pet->name }} ({{ $pet->species }})</option>
                            @endforeach
                        </select>
                        @error('pet_id')
                            <span style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Calendar Selection -->
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Date & Time</label>
                        @include('components.appointment-calendar', [
                            'calendarId' => 'owner-booking-calendar',
                            'viewer' => 'client',
                            'mode' => 'booking',
                            'startMinimized' => true,
                            'sideByUside' => true,
                            'bookings' => $calendarBookings,
                            'slots' => $calendarSlots,
                        ])
                        @error('appointment_date')
                            <span style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                        @enderror
                        @error('appointment_time')
                            <span style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Reason Selection -->
                    <div>
                        <label for="reason" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Reason for Visit</label>
                        <select id="reason" name="reason" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                            <option value="">-- Select a reason --</option>
                            <option value="Regular Check-up">Regular Check-up</option>
                            <option value="Vaccination">Vaccination</option>
                            <option value="Follow-up Vaccination">Follow-up Vaccination</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                        @error('reason')
                            <span style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; display: block;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Submit -->
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="submit" class="btn btn-primary" @disabled(!$hasAvailablePets || !$bookingEnabled)>Request Appointment</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card" style="overflow-x: auto;">
        <h2>Upcoming Appointments ({{ $upcomingAppointments->count() }})</h2>
         <div style="max-height: 350px; overflow-y: auto; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; table-layout: auto;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                    <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Reason</th>
                    <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Pet Name</th>
                    <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Date & Time</th>
                    <th style="padding: 1.25rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Veterinarian</th>
                    <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Status</th>
                    <th style="padding: 1.25rem; text-align: center; font-weight: 600; color: #374151; font-size: 0.95rem; white-space: nowrap;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($upcomingAppointments as $appointment)
                    @php
                        $statusKey = strtolower($appointment->status);
                        $statusColor = match($statusKey) {
                            'pending' => '#f59e0b',
                            'approved' => '#10b981',
                            'completed' => '#10b981',
                            'missed' => '#ef4444',
                            'cancelled' => '#ef4444',
                            'rescheduled' => '#3b82f6',
                            default => '#3b82f6',
                        };
                        $statusDisplay = match($statusKey) {
                            'pending' => 'Pending Review',
                            'approved' => 'Confirmed',
                            'completed' => 'Completed',
                            'missed' => 'Missed',
                            'cancelled' => 'Cancelled',
                            'rescheduled' => 'Rescheduled',
                            default => 'Unknown',
                        };
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s;">
                        <td style="padding: 1.25rem; color: #1f2937; font-weight: 500;">{{ $appointment->reason ?? 'N/A' }}</td>
                        <td style="padding: 1.25rem; color: #4b5563;">{{ $appointment->pet->name }}</td>
                        <td style="padding: 1.25rem; color: #4b5563; white-space: nowrap;">{{ $appointment->appointment_date->format('M d, Y \a\t h:i A') }}</td>
                        <td style="padding: 1.25rem; color: #4b5563;">
                            @if($appointment->consultation && $appointment->consultation->veterinarian)
                                Dr. {{ $appointment->consultation->veterinarian->name }}
                            @else
                                <span style="color: #9ca3af; font-style: italic;">Awaiting assignment</span>
                            @endif
                        </td>
                        <td style="padding: 1.25rem; text-align: center;">
                            <span style="display: inline-block; padding: 0.5rem 1rem; background: {{ $statusColor }}; color: white; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500;">{{ $statusDisplay }}</span>
                        </td>
                        <td style="padding: 1.25rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <button type="button" class="btn-reschedule" data-id="{{ $appointment->id }}" style="padding: 0.625rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; font-size: 0.875rem; cursor: pointer; transition: background 0.2s; white-space: nowrap;">Reschedule</button>
                                <form method="POST" action="{{ route('pet-owner.appointments.cancel', $appointment->id) }}" style="margin: 0; display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure you want to cancel this appointment?')" style="padding: 0.625rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.375rem; font-size: 0.875rem; cursor: pointer; transition: background 0.2s; white-space: nowrap;">Cancel</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 3rem; text-align: center; background-color: #f9fafb; color: #6b7280; font-size: 1rem;">
                            No upcoming appointments.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach($upcomingAppointments as $appointment)
        <!-- Reschedule Modal -->
        <div id="reschedule-modal-{{ $appointment->id }}" style="display: none; position: fixed; inset: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: 1rem;">
            <div style="background: white; padding: 2rem; border-radius: 0.5rem; width: min(100%, 900px); max-height: 90vh; margin: auto; display: flex; flex-direction: column;">
                <h3 style="margin-top: 0; margin-bottom: 0.5rem;">Reschedule Appointment</h3>
                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">Current: {{ $appointment->appointment_date->format('M d, Y \a\t h:i A') }}</p>
                <form method="POST" action="{{ route('pet-owner.appointments.reschedule', $appointment->id) }}" style="display: flex; flex-direction: column; flex: 1; overflow-y: auto;">
                    @csrf
                    @method('PUT')
                    <div style="margin-bottom: 1rem; flex: 1; overflow-y: auto;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">New Date & Time</label>
                        @include('components.appointment-calendar', [
                            'calendarId' => 'owner-reschedule-calendar-' . $appointment->id,
                            'viewer' => 'client',
                            'mode' => 'reschedule',
                            'sideByUside' => true,
                            'bookings' => $calendarBookings,
                            'slots' => $calendarSlots,
                            'ignoreAppointmentId' => $appointment->appointment_id,
                            'selectedDate' => $appointment->appointment_date->format('Y-m-d'),
                            'selectedTime' => $appointment->appointment_time->format('H:i'),
                        ])
                    </div>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end; flex-shrink: 0;">
                        <button type="button" onclick="closeRescheduleModal({{ $appointment->id }})" style="padding: 0.75rem 1rem; background: #d1d5db; border: none; border-radius: 0.375rem; cursor: pointer;">Cancel</button>
                        <button type="submit" style="padding: 0.75rem 1rem; background: #667eea; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">Reschedule</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
    
    <script>
        function openRescheduleModal(appointmentId) {
            const modal = document.getElementById(`reschedule-modal-${appointmentId}`);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeRescheduleModal(appointmentId) {
            const modal = document.getElementById(`reschedule-modal-${appointmentId}`);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        // Setup reschedule buttons
        document.querySelectorAll('.btn-reschedule').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                openRescheduleModal(id);
            });
        });

        // Close modal on background click
        document.querySelectorAll('[id^="reschedule-modal-"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    const id = modal.id.split('-').pop();
                    closeRescheduleModal(id);
                }
            });
        });
    </script>
@endsection
