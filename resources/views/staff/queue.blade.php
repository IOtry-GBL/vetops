@extends('layouts.app')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    <style>
        /* Calendar Styles */
        .calendar-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .calendar-wrapper {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid rgba(23, 49, 61, 0.12);
            padding: 1rem;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(23, 49, 61, 0.1);
        }

        .calendar-header h3 {
            margin: 0;
            font-size: 1.125rem;
            color: var(--shell-ink);
        }

        .calendar-nav {
            display: flex;
            gap: 0.5rem;
        }

        .calendar-nav button {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: background 0.2s ease;
        }

        .calendar-nav button:hover {
            background: #5568d3;
        }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .weekday-header {
            text-align: center;
            font-weight: 700;
            color: var(--shell-ink);
            font-size: 0.75rem;
            padding: 0.5rem 0;
            text-transform: uppercase;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(23, 49, 61, 0.1);
            border-radius: 0.375rem;
            background: white;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--shell-ink);
            transition: all 0.2s ease;
            position: relative;
        }

        .calendar-day:hover {
            background: #f0f4ff;
            border-color: #667eea;
        }

        .calendar-day.other-month {
            color: #d1d5db;
            background: #fafbfc;
        }

        .calendar-day.today {
            background: #dbeafe;
            border-color: #667eea;
            font-weight: 700;
        }

        .calendar-day.has-appointments {
            background: #d1fae5;
            border-color: #10b981;
        }

        .calendar-day.has-appointments.selected {
            background: #10b981;
            color: white;
        }

        .calendar-day.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .appointment-indicator {
            position: absolute;
            bottom: 2px;
            width: 4px;
            height: 4px;
            background: #10b981;
            border-radius: 50%;
        }

        .calendar-day.has-appointments.selected .appointment-indicator {
            background: white;
        }

        /* Appointments Display */
        .appointments-wrapper {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid rgba(23, 49, 61, 0.12);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            max-height: 600px;
        }

        .appointments-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(23, 49, 61, 0.1);
            flex-shrink: 0;
        }

        .appointments-header h3 {
            margin: 0;
            font-size: 1.125rem;
            color: var(--shell-ink);
        }

        .selected-date {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        #appointmentsContainer {
            overflow-y: auto;
            flex: 1;
            padding-right: 0.5rem;
        }

        #appointmentsContainer::-webkit-scrollbar {
            width: 6px;
        }

        #appointmentsContainer::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        #appointmentsContainer::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        #appointmentsContainer::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .appointment-empty {
            text-align: center;
            padding: 2rem 1rem;
            color: #6b7280;
        }

        .appointment-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            background: #f9fafb;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .appointment-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .time-slot {
            padding: 0.75rem;
            border-left: 4px solid #10b981;
            background: #f0fdf4;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .time-slot:hover {
            background: #e0fce8;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }

        .time-slot-time {
            font-weight: 700;
            font-size: 1rem;
            color: #10b981;
            margin-bottom: 0.25rem;
        }

        .time-slot-status {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .appointment-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            background: #f9fafb;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .appointment-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .appointment-time {
            font-weight: 700;
            font-size: 1.125rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .appointment-pet {
            font-weight: 600;
            color: var(--shell-ink);
            margin-bottom: 0.25rem;
        }

        .appointment-species {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }

        .appointment-owner {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .appointment-owner-label {
            color: #6b7280;
        }

        .appointment-owner-name {
            color: var(--shell-ink);
            font-weight: 600;
        }

        .appointment-phone {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }

        .appointment-reason {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            border-radius: 0.25rem;
            color: #92400e;
            margin-bottom: 1rem;
        }

        .appointment-status {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .appointment-status button {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
        }

        .btn-confirm {
            background: #10b981;
            color: white;
        }

        .btn-confirm:hover {
            background: #059669;
        }

        @media (max-width: 1024px) {
            .calendar-section {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card">
        <div class="section-head">
            <div>
                <h1>Appointment Queue</h1>
                <p class="muted-copy">Manage and organize appointment requests</p>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments Table -->
    @php
        $upcomingCount = isset($upcomingApprovedAppointments) ? count($upcomingApprovedAppointments) : 0;
    @endphp
    
    <div class="card">
        <div class="section-head">
            <div>
                <h1>Upcoming Appointments ({{ $upcomingCount }})</h1>
                <p class="muted-copy">Scheduled appointments for the clinic</p>
            </div>
        </div>
    </div>

    @if(isset($upcomingApprovedAppointments) && count($upcomingApprovedAppointments) > 0)
        <div class="card">
            <div class="table-wrap">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Owner</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingApprovedAppointments as $appointment)
                            <tr>
                                <td>
                                    <strong>{{ $appointment->pet->name }}</strong>
                                </td>
                                <td>{{ $appointment->reason ?? 'N/A' }}</td>
                                <td>{{ $appointment->appointment_date->format('M d, Y') }}</td>
                                <td>{{ $appointment->appointment_date->format('h:i A') }}</td>
                                <td>{{ $appointment->pet->owner->name }}</td>
                                <td>{{ $appointment->pet->owner->phone ?? 'N/A' }}</td>
                                <td><span class="status-badge status-open">{{ $appointment->status }}</span></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="{{ route('staff.appointments.confirm', $appointment) }}" class="btn btn-primary" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem;">View</a>
                                        <button onclick="rescheduleAppointment({{ $appointment->appointment_id }})" class="btn btn-warning" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem; background: #f59e0b; color: white; border: none;">Reschedule</button>
                                        <form method="POST" action="{{ route('staff.appointments.reject', $appointment) }}" style="margin: 0;" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem;">Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card">
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <p>No upcoming appointments scheduled</p>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="section-head">
            <div>
                <h1>Appointment Queue</h1>
                <p class="muted-copy">Latest 10 pending appointment requests, newest first.</p>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments Calendar Section -->
    <div class="card">
        <div class="calendar-section">
            <!-- Calendar -->
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <h3 id="calendarTitle">Calendar</h3>
                    <div class="calendar-nav">
                        <button onclick="previousMonth()">← Prev</button>
                        <button onclick="nextMonth()">Next →</button>
                    </div>
                </div>

                <div class="weekdays">
                    <div class="weekday-header">Sun</div>
                    <div class="weekday-header">Mon</div>
                    <div class="weekday-header">Tue</div>
                    <div class="weekday-header">Wed</div>
                    <div class="weekday-header">Thu</div>
                    <div class="weekday-header">Fri</div>
                    <div class="weekday-header">Sat</div>
                </div>

                <div class="calendar-grid" id="calendarGrid"></div>
            </div>

            <!-- Appointments for Selected Day -->
            <div class="appointments-wrapper">
                <div class="appointments-header">
                    <h3>Upcoming Appointments</h3>
                    <div class="selected-date" id="selectedDateDisplay">Select a date to view appointments</div>
                </div>
                <div id="appointmentsContainer">
                    <div class="appointment-empty">Select a date to view appointments</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-head">
            <div>
                <h1>Pending Requests</h1>
                <p class="muted-copy">Latest 10 pending appointment requests, newest first.</p>
            </div>
            <span class="count-chip">{{ $totalPendingAppointments }} total pending</span>
        </div>
    </div>
        <div class="table-wrap">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Added</th>
                        <th>Schedule</th>
                        <th>Pet</th>
                        <th>Owner</th>
                        <th>Reason</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingAppointments as $appointment)
                        @php
                            $latestSymptom = $appointment->pet->symptomLogs->last();
                            $concernLevel = strtolower((string) ($latestSymptom->concern_level ?? ''));
                            $priority = match (true) {
                                in_array($concernLevel, ['emergency', 'urgent', 'critical'], true) => ['Emergency', 'status-danger'],
                                in_array($concernLevel, ['high priority', 'vet_visit', 'priority'], true) => ['High Priority', 'pill-warning'],
                                default => ['Routine', 'status-open'],
                            };
                        @endphp
                        <tr>
                            <td>{{ $appointment->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                            <td>
                                @if($appointment->appointment_date)
                                    {{ $appointment->appointment_date->format('M d, Y') }}<br>
                                    <span style="color: #6b7280;">{{ $appointment->appointment_date->format('h:i A') }}</span>
                                @else
                                    TBD
                                @endif
                            </td>
                            <td>
                                <strong>{{ $appointment->pet->name }}</strong><br>
                                <span style="color: #6b7280;">{{ $appointment->pet->species }}</span>
                            </td>
                            <td>
                                {{ $appointment->pet->owner->name }}<br>
                                <span style="color: #6b7280;">{{ $appointment->pet->owner->phone ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $appointment->reason ?? 'N/A' }}</td>
                            <td><span class="status-badge {{ $priority[1] }}">{{ $priority[0] }}</span></td>
                            <td><span class="status-badge status-pending">Pending</span></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('staff.appointments.confirm', $appointment) }}" class="btn btn-primary" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem;">Review</a>
                                    <form method="POST" action="{{ route('staff.appointments.reject', $appointment) }}" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Are you sure you want to reject this appointment?')" class="btn btn-danger" style="min-height: 36px; padding: 0.45rem 0.75rem; font-size: 0.8rem;">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: #6b7280;">No pending appointment requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Calendar data from server
        const allAppointments = @json($upcomingApprovedAppointments ?? []);

        let currentDate = new Date();
        let selectedDate = null;

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            // Update title
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('calendarTitle').textContent = `${monthNames[month]} ${year}`;

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            // Previous month's days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const dayEl = createDayElement(day, false, true);
                calendarGrid.appendChild(dayEl);
            }

            // Current month's days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayEl = createDayElement(day, true, false);
                calendarGrid.appendChild(dayEl);
            }

            // Next month's days
            const totalCells = calendarGrid.children.length;
            const remainingCells = 42 - totalCells; // 6 rows × 7 days
            for (let day = 1; day <= remainingCells; day++) {
                const dayEl = createDayElement(day, false, true);
                calendarGrid.appendChild(dayEl);
            }
        }

        function createDayElement(day, isCurrentMonth, isOtherMonth) {
            const dayEl = document.createElement('div');
            dayEl.className = 'calendar-day';

            if (isOtherMonth) {
                dayEl.classList.add('other-month');
            } else {
                const today = new Date();
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();

                if (isCurrentMonth && day === today.getDate() && 
                    month === today.getMonth() && year === today.getFullYear()) {
                    dayEl.classList.add('today');
                }

                // Check if has appointments
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                const dayAppointments = allAppointments.filter(apt => {
                    const aptDate = new Date(apt.appointment_date).toISOString().split('T')[0];
                    return aptDate === dateStr;
                });

                if (dayAppointments.length > 0) {
                    dayEl.classList.add('has-appointments');
                    const indicator = document.createElement('div');
                    indicator.className = 'appointment-indicator';
                    dayEl.appendChild(indicator);
                }

                dayEl.onclick = () => selectDate(day);
            }

            dayEl.textContent = day;
            return dayEl;
        }

        function selectDate(day) {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            selectedDate = new Date(year, month, day);

            // Update UI
            document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
            event.target.closest('.calendar-day').classList.add('selected');

            displayAppointments();
        }

        function displayAppointments() {
            const container = document.getElementById('appointmentsContainer');
            const dateDisplay = document.getElementById('selectedDateDisplay');

            if (!selectedDate) {
                container.innerHTML = '<div class="appointment-empty">Select a date to view appointments</div>';
                dateDisplay.textContent = 'Select a date to view appointments';
                return;
            }

            const dateStr = selectedDate.toISOString().split('T')[0];
            const dayAppointments = allAppointments.filter(apt => {
                const aptDate = new Date(apt.appointment_date).toISOString().split('T')[0];
                return aptDate === dateStr;
            });

            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateDisplay.textContent = selectedDate.toLocaleDateString('en-US', options);

            if (dayAppointments.length === 0) {
                // Show available time slots
                container.innerHTML = generateAvailableSlots();
                return;
            }

            container.innerHTML = dayAppointments.map(apt => {
                const appointmentDate = new Date(apt.appointment_date);
                const timeStr = appointmentDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

                return `
                    <div class="appointment-item">
                        <div class="appointment-time">${timeStr}</div>
                        <div class="appointment-pet">${apt.pet.name}</div>
                        <div class="appointment-species">${apt.pet.species}</div>
                        <div class="appointment-owner">
                            <span class="appointment-owner-label">Owner: </span>
                            <span class="appointment-owner-name">${apt.pet.owner.name}</span>
                        </div>
                        <div class="appointment-phone">📱 ${apt.pet.owner.phone || 'N/A'}</div>
                        <div class="appointment-reason">${apt.reason || 'No reason provided'}</div>
                        <div class="appointment-status">
                            <a href="/staff/appointments/${apt.appointment_id}/confirm" class="btn-view">View Details</a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function generateAvailableSlots() {
            const slots = [];
            const startHour = 9; // 9 AM
            const endHour = 17; // 5 PM
            const intervalMinutes = 30;

            for (let hour = startHour; hour < endHour; hour++) {
                for (let minute = 0; minute < 60; minute += intervalMinutes) {
                    const time = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
                    const displayTime = new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });

                    slots.push(`
                        <div class="time-slot">
                            <div class="time-slot-time">${displayTime}</div>
                            <div class="time-slot-status">✓ Available - No appointments</div>
                        </div>
                    `);
                }
            }

            return slots.join('');
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        // Initialize calendar
        document.addEventListener('DOMContentLoaded', () => {
            renderCalendar();
        });

        function rescheduleAppointment(appointmentId) {
            if (confirm('Are you sure you want to reschedule this appointment?')) {
                // You can redirect to a reschedule page or open a modal
                window.location.href = `/staff/appointments/${appointmentId}/reschedule`;
            }
        }
    </script>
@endsection
