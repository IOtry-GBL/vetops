@php
    $calendarId = $calendarId ?? ('appointment-calendar-' . uniqid());
    $viewer = $viewer ?? 'client';
    $mode = $mode ?? 'booking';
    $bookings = $bookings ?? [];
    $slots = $slots ?? \App\Services\AppointmentScheduleService::WORKING_HOURS;
    $ignoreAppointmentId = $ignoreAppointmentId ?? null;
    $showDetails = ($viewer !== 'client') && ($mode !== 'reschedule');
    $startMinimized = $startMinimized ?? ($mode === 'reschedule');
    $sideByUside = $sideByUside ?? false;

    $selectedDate = old('appointment_date', $selectedDate ?? '');
    $selectedTime = old('appointment_time', $selectedTime ?? '');

    if (is_string($selectedDate) && str_contains($selectedDate, 'T')) {
        [$datePart, $timePart] = explode('T', $selectedDate, 2);
        $selectedDate = $datePart;
        $selectedTime = $selectedTime ?: substr($timePart, 0, 5);
    }
@endphp

@once
    @push('styles')
        <style>
            .appointment-calendar {
                display: grid;
                gap: 1rem;
                padding: 1rem;
                border: 1px solid rgba(23, 49, 61, 0.12);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.86);
            }

            .appointment-calendar.is-minimized {
                gap: 0;
                padding: 0;
                border: none;
                background: transparent;
            }

            .appointment-calendar__minimized-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem;
                border: 1px solid rgba(23, 49, 61, 0.12);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.86);
            }

            .appointment-calendar__minimized-content {
                display: flex;
                align-items: center;
                gap: 1rem;
                flex: 1;
            }

            .appointment-calendar__minimized-label {
                font-size: 0.875rem;
                color: #6b7280;
                font-weight: 500;
            }

            .appointment-calendar__minimized-value {
                font-size: 1.125rem;
                font-weight: 600;
                color: var(--shell-ink, #17313d);
            }

            .appointment-calendar__minimized-button {
                padding: 0.5rem 1rem;
                background: var(--shell-accent-deep, #0d6475);
                color: white;
                border: none;
                border-radius: 0.375rem;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.875rem;
                transition: background 0.2s ease;
            }

            .appointment-calendar__minimized-button:hover {
                background: rgba(13, 100, 117, 0.85);
            }

            .appointment-calendar__full-content {
                display: grid;
                gap: 1rem;
                padding: 1rem;
                border: 1px solid rgba(23, 49, 61, 0.12);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.86);
            }

            .appointment-calendar__full-content.is-side-by-side {
                grid-template-columns: 1fr 300px;
            }

            .appointment-calendar__full-content.is-side-by-side > .appointment-calendar__back-button {
                grid-column: 1 / -1;
            }

            .appointment-calendar__full-content.is-side-by-side > .appointment-calendar__bar {
                grid-column: 1 / -1;
            }

            .appointment-calendar__full-content.is-side-by-side > .appointment-calendar__weekdays {
                grid-column: 1 / -1;
            }

            .appointment-calendar__full-content.is-side-by-side > .appointment-calendar__grid {
                grid-column: 1;
            }

            .appointment-calendar__full-content.is-side-by-side > div:last-child {
                grid-column: 2;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .appointment-calendar__back-button {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.625rem 1rem;
                background: #e5e7eb;
                color: var(--shell-ink, #17313d);
                border: none;
                border-radius: 0.375rem;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.875rem;
                transition: background 0.2s ease;
            }

            .appointment-calendar__back-button:hover {
                background: #d1d5db;
            }

            .appointment-calendar__bar,
            .appointment-calendar__nav,
            .appointment-calendar__legend,
            .appointment-calendar__summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .appointment-calendar__title {
                font-weight: 800;
                color: var(--shell-ink, #17313d);
            }

            .appointment-calendar__nav {
                justify-content: flex-start;
            }

            .appointment-calendar__icon,
            .appointment-calendar__toggle {
                min-height: 36px;
                border: 1px solid rgba(23, 49, 61, 0.12);
                border-radius: 999px;
                background: #fff;
                color: var(--shell-ink, #17313d);
                cursor: pointer;
                font-weight: 700;
            }

            .appointment-calendar__icon {
                width: 38px;
            }

            .appointment-calendar__toggle {
                padding: 0.45rem 0.9rem;
            }

            .appointment-calendar__toggle.is-active {
                background: rgba(15, 139, 141, 0.12);
                color: var(--shell-accent-deep, #0d6475);
                border-color: rgba(15, 139, 141, 0.28);
            }

            .appointment-calendar__weekdays,
            .appointment-calendar__grid {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 0.4rem;
            }

            .appointment-calendar__weekday {
                color: var(--shell-muted, #60707d);
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-align: center;
                text-transform: uppercase;
            }

            .appointment-calendar__day {
                min-height: 64px;
                padding: 0.55rem;
                border: 1px solid rgba(23, 49, 61, 0.1);
                border-radius: 14px;
                background: #fff;
                color: var(--shell-ink, #17313d);
                cursor: pointer;
                text-align: left;
                transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
            }

            .appointment-calendar__day:hover {
                transform: translateY(-1px);
                border-color: rgba(15, 139, 141, 0.28);
                box-shadow: 0 10px 24px rgba(23, 49, 61, 0.08);
            }

            .appointment-calendar__day.is-muted {
                opacity: 0.48;
            }

            .appointment-calendar__day.is-disabled {
                cursor: not-allowed;
                background: #f3f4f6;
                color: #9ca3af;
                transform: none;
                box-shadow: none;
            }

            .appointment-calendar__day.is-selected {
                border-color: rgba(15, 139, 141, 0.48);
                box-shadow: 0 0 0 3px rgba(15, 139, 141, 0.12);
            }

            .appointment-calendar__day-number {
                display: block;
                font-weight: 800;
            }

            .appointment-calendar__day-state {
                display: inline-flex;
                margin-top: 0.45rem;
                padding: 0.18rem 0.5rem;
                border-radius: 999px;
                font-size: 0.68rem;
                font-weight: 800;
            }

            .appointment-calendar__day-state.is-open {
                background: rgba(25, 135, 84, 0.14);
                color: #116d44;
            }

            .appointment-calendar__day-state.is-busy {
                background: rgba(196, 79, 67, 0.14);
                color: #9f372d;
            }

            .appointment-calendar__slots {
                display: grid;
                gap: 0.55rem;
                max-height: 300px;
                overflow-y: auto;
                padding: 0.35rem;
                border: 1px solid rgba(23, 49, 61, 0.1);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.72);
            }

            .appointment-calendar__slot {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                width: 100%;
                min-height: 42px;
                padding: 0.62rem 0.8rem;
                border: 1px solid transparent;
                border-radius: 12px;
                color: #fff;
                cursor: pointer;
                font-weight: 800;
                text-align: left;
            }

            .appointment-calendar__slot.is-available {
                background: #198754;
            }

            .appointment-calendar__slot.is-taken {
                background: #c44f43;
                cursor: not-allowed;
            }

            .appointment-calendar__slot.is-disabled {
                background: #9ca3af;
                cursor: not-allowed;
            }

            .appointment-calendar__slot.is-selected {
                box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.24);
                outline: 2px solid #fff;
            }

            .appointment-calendar__slot-meta {
                color: rgba(255, 255, 255, 0.86);
                font-size: 0.82rem;
                font-weight: 700;
            }

            .appointment-calendar__legend {
                justify-content: flex-start;
                color: var(--shell-muted, #60707d);
                font-size: 0.86rem;
            }

            .appointment-calendar__key {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-weight: 700;
            }

            .appointment-calendar__dot {
                width: 0.7rem;
                height: 0.7rem;
                border-radius: 50%;
            }

            .appointment-calendar__dot.is-available {
                background: #198754;
            }

            .appointment-calendar__dot.is-taken {
                background: #c44f43;
            }

            .appointment-calendar__summary {
                justify-content: flex-start;
                padding: 0.8rem;
                border-radius: 14px;
                background: rgba(15, 139, 141, 0.09);
                color: var(--shell-accent-deep, #0d6475);
                font-weight: 800;
                font-size: 0.875rem;
            }

            @media (max-width: 1024px) {
                .appointment-calendar__full-content.is-side-by-side {
                    grid-template-columns: 1fr;
                }

                .appointment-calendar__full-content.is-side-by-side > div:last-child {
                    grid-column: 1;
                }
            }

            @media (max-width: 640px) {
                .appointment-calendar__day {
                    min-height: 54px;
                    padding: 0.45rem;
                }

                .appointment-calendar__day-state {
                    display: none;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.initAppointmentCalendar = window.initAppointmentCalendar || function (config) {
                const root = document.getElementById(config.id);
                if (!root) return;

                const title = root.querySelector('[data-calendar-title]');
                const grid = root.querySelector('[data-calendar-grid]');
                const slotsPanel = root.querySelector('[data-calendar-slots]');
                const summary = root.querySelector('[data-calendar-summary]');
                const dateInput = root.querySelector('[data-calendar-date]');
                const timeInput = root.querySelector('[data-calendar-time]');
                const monthButton = root.querySelector('[data-calendar-view="month"]');
                const weekButton = root.querySelector('[data-calendar-view="week"]');
                const minimizedHeader = root.querySelector('[data-calendar-minimized-header]');
                const fullContent = root.querySelector('[data-calendar-full-content]');
                const minimizedValue = root.querySelector('[data-calendar-minimized-value]');
                const editButton = root.querySelector('[data-calendar-edit-button]');
                const backButton = root.querySelector('[data-calendar-back-button]');
                
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                let selectedDate = config.selectedDate || toDateKey(today);
                let selectedTime = config.selectedTime || '';
                let cursor = parseDateKey(selectedDate);
                let view = 'month';
                let isMinimized = config.startMinimized || (config.mode === 'reschedule');

                function pad(value) {
                    return String(value).padStart(2, '0');
                }

                function toDateKey(date) {
                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
                }

                function parseDateKey(dateKey) {
                    const parts = dateKey.split('-').map(Number);
                    return new Date(parts[0], parts[1] - 1, parts[2]);
                }

                function formatMonth(date) {
                    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                }

                function formatSlot(time) {
                    const date = new Date(`2000-01-01T${time}:00`);
                    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                }

                function formatDateDisplay(dateKey) {
                    const date = parseDateKey(dateKey);
                    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                }

                function isSameMonth(left, right) {
                    return left.getMonth() === right.getMonth() && left.getFullYear() === right.getFullYear();
                }

                function bookingFor(dateKey, time) {
                    return config.bookings.find((booking) => {
                        return booking.date === dateKey
                            && booking.time === time
                            && Number(booking.appointment_id) !== Number(config.ignoreAppointmentId || 0);
                    });
                }

                function isPastDate(date) {
                    return date < today;
                }

                function isPastSlot(dateKey, time) {
                    return new Date(`${dateKey}T${time}:00`) < now;
                }

                function availableSlotsFor(dateKey) {
                    return config.slots.filter((time) => !bookingFor(dateKey, time) && !isPastSlot(dateKey, time));
                }

                function updateMinimizedHeader() {
                    if (selectedDate && selectedTime) {
                        minimizedValue.textContent = `${formatDateDisplay(selectedDate)} at ${formatSlot(selectedTime)}`;
                    } else {
                        minimizedValue.textContent = 'Not set';
                    }
                }

                function setSelectedSlot(dateKey, time) {
                    selectedDate = dateKey;
                    selectedTime = time;
                    dateInput.value = dateKey;
                    timeInput.value = time;
                    summary.textContent = `Selected: ${parseDateKey(dateKey).toLocaleDateString('en-US', {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                    })} at ${formatSlot(time)}`;
                    updateMinimizedHeader();
                    render();
                }

                function renderDay(date, muted) {
                    const dateKey = toDateKey(date);
                    const disabled = isPastDate(date);
                    const hasOpenSlots = !disabled && availableSlotsFor(dateKey).length > 0;
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = [
                        'appointment-calendar__day',
                        muted ? 'is-muted' : '',
                        disabled ? 'is-disabled' : '',
                        dateKey === selectedDate ? 'is-selected' : '',
                    ].filter(Boolean).join(' ');
                    button.disabled = disabled;
                    button.innerHTML = `
                        <span class="appointment-calendar__day-number">${date.getDate()}</span>
                        <span class="appointment-calendar__day-state ${hasOpenSlots ? 'is-open' : 'is-busy'}">${hasOpenSlots ? 'Open' : 'Full'}</span>
                    `;
                    button.addEventListener('click', () => {
                        selectedDate = dateKey;
                        cursor = parseDateKey(dateKey);
                        render();
                    });
                    return button;
                }

                function renderCalendarGrid() {
                    grid.innerHTML = '';

                    if (view === 'month') {
                        const first = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
                        const start = new Date(first);
                        start.setDate(first.getDate() - first.getDay());

                        for (let index = 0; index < 42; index++) {
                            const date = new Date(start);
                            date.setDate(start.getDate() + index);
                            grid.appendChild(renderDay(date, !isSameMonth(date, cursor)));
                        }
                    } else {
                        const start = new Date(cursor);
                        start.setDate(cursor.getDate() - cursor.getDay());

                        for (let index = 0; index < 7; index++) {
                            const date = new Date(start);
                            date.setDate(start.getDate() + index);
                            grid.appendChild(renderDay(date, false));
                        }
                    }
                }

                function renderSlots() {
                    slotsPanel.innerHTML = '';
                    const selectedDateObject = parseDateKey(selectedDate);

                    if (isPastDate(selectedDateObject)) {
                        slotsPanel.innerHTML = '<div class="empty-state">Past dates are unavailable.</div>';
                        return;
                    }

                    config.slots.forEach((time) => {
                        const booking = bookingFor(selectedDate, time);
                        const past = isPastSlot(selectedDate, time);
                        const isCurrentSelection = selectedDate === dateInput.value && selectedTime === time;
                        const selectedRescheduleSlot = config.mode === 'reschedule' && isCurrentSelection;
                        const slot = document.createElement('button');
                        slot.type = 'button';
                        slot.disabled = Boolean(booking) || past;
                        slot.className = [
                            'appointment-calendar__slot',
                            booking || selectedRescheduleSlot ? 'is-taken' : 'is-available',
                            past ? 'is-disabled' : '',
                            isCurrentSelection ? 'is-selected' : '',
                        ].filter(Boolean).join(' ');

                        let detail = '';
                        if (booking && config.showDetails) {
                            detail = `<span class="appointment-calendar__slot-meta">${booking.client_name} - ${booking.pet_name}</span>`;
                        } else if (booking) {
                            detail = '<span class="appointment-calendar__slot-meta">Taken</span>';
                        } else if (selectedRescheduleSlot) {
                            detail = '<span class="appointment-calendar__slot-meta">New selection</span>';
                        } else if (past) {
                            detail = '<span class="appointment-calendar__slot-meta">Unavailable</span>';
                        } else {
                            detail = '<span class="appointment-calendar__slot-meta">Available</span>';
                        }

                        slot.innerHTML = `<span>${formatSlot(time)}</span>${detail}`;
                        slot.addEventListener('click', () => {
                            if (!slot.disabled) setSelectedSlot(selectedDate, time);
                        });
                        slotsPanel.appendChild(slot);
                    });
                }

                function render() {
                    title.textContent = view === 'month' ? formatMonth(cursor) : `Week of ${cursor.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
                    monthButton.classList.toggle('is-active', view === 'month');
                    weekButton.classList.toggle('is-active', view === 'week');
                    renderCalendarGrid();
                    renderSlots();
                }

                function toggleMinimized() {
                    isMinimized = !isMinimized;
                    root.classList.toggle('is-minimized', isMinimized);
                    minimizedHeader.style.display = isMinimized ? 'flex' : 'none';
                    fullContent.style.display = isMinimized ? 'none' : 'grid';
                }

                root.querySelector('[data-calendar-prev]').addEventListener('click', () => {
                    cursor = new Date(cursor.getFullYear(), cursor.getMonth() + (view === 'month' ? -1 : 0), cursor.getDate() + (view === 'week' ? -7 : 0));
                    selectedDate = toDateKey(cursor);
                    render();
                });

                root.querySelector('[data-calendar-next]').addEventListener('click', () => {
                    cursor = new Date(cursor.getFullYear(), cursor.getMonth() + (view === 'month' ? 1 : 0), cursor.getDate() + (view === 'week' ? 7 : 0));
                    selectedDate = toDateKey(cursor);
                    render();
                });

                monthButton.addEventListener('click', () => {
                    view = 'month';
                    render();
                });

                weekButton.addEventListener('click', () => {
                    view = 'week';
                    render();
                });

                editButton.addEventListener('click', () => {
                    toggleMinimized();
                });

                backButton.addEventListener('click', () => {
                    toggleMinimized();
                });

                if (selectedDate && selectedTime) {
                    dateInput.value = selectedDate;
                    timeInput.value = selectedTime;
                    selectedTime = selectedTime.slice(0, 5);
                }

                updateMinimizedHeader();
                root.classList.toggle('is-minimized', isMinimized);
                minimizedHeader.style.display = isMinimized ? 'flex' : 'none';
                fullContent.style.display = isMinimized ? 'none' : 'grid';
                render();
            };
        </script>
    @endpush
@endonce

<div class="appointment-calendar" id="{{ $calendarId }}">
    <input type="hidden" name="appointment_date" value="{{ $selectedDate }}" data-calendar-date>
    <input type="hidden" name="appointment_time" value="{{ $selectedTime }}" data-calendar-time>

    <!-- Minimized View -->
    <div class="appointment-calendar__minimized-header" data-calendar-minimized-header style="display: none;">
        <div class="appointment-calendar__minimized-content">
            <div>
                <div class="appointment-calendar__minimized-label">Selected Date & Time:</div>
                <div class="appointment-calendar__minimized-value" data-calendar-minimized-value>Not set</div>
            </div>
        </div>
        <button type="button" class="appointment-calendar__minimized-button" data-calendar-edit-button>Edit</button>
    </div>

    <!-- Full Calendar View -->
    <div class="appointment-calendar__full-content{{ $sideByUside ? ' is-side-by-side' : '' }}" data-calendar-full-content>
        <button type="button" class="appointment-calendar__back-button" data-calendar-back-button>
            ← Back
        </button>

        <div class="appointment-calendar__bar">
            <div>
                <div class="appointment-calendar__title" data-calendar-title>Calendar</div>
                <div class="appointment-calendar__legend">
                    <span class="appointment-calendar__key"><span class="appointment-calendar__dot is-available"></span>Available</span>
                    <span class="appointment-calendar__key"><span class="appointment-calendar__dot is-taken"></span>Taken</span>
                </div>
            </div>

            <div class="appointment-calendar__nav">
                <button type="button" class="appointment-calendar__icon" data-calendar-prev aria-label="Previous period">&lt;</button>
                <button type="button" class="appointment-calendar__toggle is-active" data-calendar-view="month">Month</button>
                <button type="button" class="appointment-calendar__toggle" data-calendar-view="week">Week</button>
                <button type="button" class="appointment-calendar__icon" data-calendar-next aria-label="Next period">&gt;</button>
            </div>
        </div>

        <div class="appointment-calendar__weekdays">
            <div class="appointment-calendar__weekday">Sun</div>
            <div class="appointment-calendar__weekday">Mon</div>
            <div class="appointment-calendar__weekday">Tue</div>
            <div class="appointment-calendar__weekday">Wed</div>
            <div class="appointment-calendar__weekday">Thu</div>
            <div class="appointment-calendar__weekday">Fri</div>
            <div class="appointment-calendar__weekday">Sat</div>
        </div>

        <div class="appointment-calendar__grid" data-calendar-grid></div>

        <div>
            <div class="appointment-calendar__summary" data-calendar-summary>
                Select a date, then choose an available time slot.
            </div>
            <div class="appointment-calendar__slots" data-calendar-slots></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initAppointmentCalendar({
            id: @json($calendarId),
            viewer: @json($viewer),
            mode: @json($mode),
            startMinimized: @json($startMinimized),
            showDetails: @json($showDetails),
            ignoreAppointmentId: @json($ignoreAppointmentId),
            selectedDate: @json($selectedDate),
            selectedTime: @json($selectedTime),
            bookings: @json($bookings),
            slots: @json($slots),
        });
    });
</script>
