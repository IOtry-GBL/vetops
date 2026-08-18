@php
    $pickerId = $pickerId ?? 'appointment_picker';
    $dateName = $dateName ?? 'appointment_date';
    $timeName = $timeName ?? 'appointment_time';
    $selectedDate = $selectedDate ?? old($dateName, '');
    $selectedTime = $selectedTime ?? old($timeName, '');
    $bookedAppointments = $bookedAppointments ?? [];
    $hasSelectedSchedule = filled($selectedDate) && filled($selectedTime);
    $startsCollapsed = $startsCollapsed ?? ($hasSelectedSchedule && !$errors->has($dateName) && !$errors->has($timeName));
    $selectedScheduleLabel = '';

    if ($hasSelectedSchedule) {
        try {
            $selectedScheduleLabel = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i', "{$selectedDate} {$selectedTime}")
                ->format('F j, Y \a\t g:i A');
        } catch (\Throwable) {
            $selectedScheduleLabel = "{$selectedDate} {$selectedTime}";
        }
    }
@endphp

<input type="hidden" id="{{ $pickerId }}_date" name="{{ $dateName }}" value="{{ $selectedDate }}">
<input type="hidden" id="{{ $pickerId }}_time" name="{{ $timeName }}" value="{{ $selectedTime }}">

@if($startsCollapsed && $hasSelectedSchedule)
    <div class="appointment-picker-current" data-current-schedule>
        <div>
            <span class="appointment-picker-current__label">Selected Date & Time</span>
            <strong>{{ $selectedScheduleLabel }}</strong>
        </div>
        <button type="button" class="appointment-picker-current__button" data-change-schedule>Change</button>
    </div>
@endif

<div
    class="appointment-picker js-appointment-picker"
    data-date-input-id="{{ $pickerId }}_date"
    data-time-input-id="{{ $pickerId }}_time"
    data-selected-date="{{ $selectedDate }}"
    data-selected-time="{{ $selectedTime }}"
    data-can-collapse="{{ $hasSelectedSchedule ? 'true' : 'false' }}"
    @if($startsCollapsed && $hasSelectedSchedule) hidden @endif
>
    @if($hasSelectedSchedule)
        <div class="appointment-picker__collapse-row">
            <button type="button" class="appointment-picker__back" data-collapse-schedule>Back</button>
        </div>
    @endif

    <div class="appointment-picker__header">
        <button type="button" class="appointment-picker__nav" data-prev-month>Prev</button>
        <strong data-month-year></strong>
        <button type="button" class="appointment-picker__nav" data-next-month>Next</button>
    </div>

    <div class="appointment-picker__weekdays">
        <span>Mon</span>
        <span>Tue</span>
        <span>Wed</span>
        <span>Thu</span>
        <span>Fri</span>
        <span>Sat</span>
        <span>Sun</span>
    </div>

    <div class="appointment-picker__grid" data-calendar-grid></div>

    <div class="appointment-picker__times" data-time-picker-panel hidden>
        <div>
            <strong data-selected-date-label>Choose a date</strong>
            <p style="margin: 0.25rem 0 0; color: #6b7280; font-size: 0.875rem;">Available and taken one-hour slots</p>
        </div>
        <div class="appointment-picker__slots" data-time-slot-container></div>
    </div>

    <div class="appointment-picker__summary" data-selected-summary hidden></div>
</div>

<script type="application/json" id="{{ $pickerId }}_booked">@json($bookedAppointments)</script>

@once
    @push('appointment-picker-styles')
        <style>
            .appointment-picker {
                border: 1px solid #d1d5db;
                border-radius: 0.375rem;
                overflow: hidden;
                background: #ffffff;
            }

            .appointment-picker-current {
                align-items: center;
                background: #ffffff;
                border: 1px solid #d1d5db;
                border-radius: 0.375rem;
                display: flex;
                gap: 1rem;
                justify-content: space-between;
                padding: 0.85rem 1rem;
            }

            .appointment-picker-current__label {
                color: #6b7280;
                display: block;
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                margin-bottom: 0.2rem;
                text-transform: uppercase;
            }

            .appointment-picker-current__button {
                background: var(--shell-accent, #0f8b8d);
                border: 0;
                border-radius: 0.375rem;
                color: #ffffff;
                cursor: pointer;
                font-weight: 700;
                padding: 0.55rem 0.9rem;
            }

            .appointment-picker__header {
                align-items: center;
                background: #f9fafb;
                display: flex;
                justify-content: space-between;
                padding: 0.75rem;
            }

            .appointment-picker__collapse-row {
                background: #ffffff;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: flex-end;
                padding: 0.6rem 0.75rem;
            }

            .appointment-picker__nav,
            .appointment-picker__back {
                background: var(--shell-accent, #0f8b8d);
                border: 0;
                border-radius: 0.375rem;
                color: #ffffff;
                cursor: pointer;
                font-weight: 700;
                padding: 0.5rem 0.85rem;
            }

            .appointment-picker__back {
                background: rgba(23, 49, 61, 0.08);
                color: var(--shell-ink, #17313d);
            }

            .appointment-picker__weekdays,
            .appointment-picker__grid {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
            }

            .appointment-picker__weekdays span {
                background: #e5e7eb;
                color: #374151;
                font-size: 0.75rem;
                font-weight: 700;
                padding: 0.5rem;
                text-align: center;
            }

            .appointment-picker__day {
                background: #ffffff;
                border: 0;
                border-right: 1px solid #e5e7eb;
                border-top: 1px solid #e5e7eb;
                cursor: pointer;
                min-height: 82px;
                padding: 0.5rem;
                text-align: left;
            }

            .appointment-picker__day:nth-child(7n) {
                border-right: 0;
            }

            .appointment-picker__day[disabled] {
                background: #f3f4f6;
                color: #9ca3af;
                cursor: not-allowed;
            }

            .appointment-picker__day.is-selected {
                box-shadow: inset 0 0 0 2px var(--shell-accent, #0f8b8d);
            }

            .appointment-picker__day-number {
                display: block;
                font-weight: 700;
                margin-bottom: 0.35rem;
            }

            .appointment-picker__day-status {
                color: #4b5563;
                display: block;
                font-size: 0.75rem;
                line-height: 1.25;
            }

            .appointment-picker__times {
                border-top: 1px solid #e5e7eb;
                padding: 1rem;
            }

            .appointment-picker__slots {
                display: grid;
                gap: 0.5rem;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                margin-top: 1rem;
            }

            .appointment-picker__slot {
                border: 1px solid #16a34a;
                border-radius: 0.375rem;
                cursor: pointer;
                font-weight: 700;
                padding: 0.65rem;
            }

            .appointment-picker__slot.is-available {
                background: #dcfce7;
                color: #166534;
            }

            .appointment-picker__slot.is-taken {
                background: #fee2e2;
                border-color: #fca5a5;
                color: #991b1b;
                cursor: not-allowed;
            }

            .appointment-picker__slot.is-selected {
                background: var(--shell-accent, #0f8b8d);
                border-color: var(--shell-accent, #0f8b8d);
                color: #ffffff;
            }

            .appointment-picker__summary {
                background: #dbeafe;
                border-top: 1px solid #bfdbfe;
                color: #0c4a6e;
                font-weight: 700;
                padding: 0.85rem 1rem;
            }
        </style>
    @endpush

    @push('appointment-picker-scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const workingHours = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

                function parseDateKey(dateKey) {
                    const parts = dateKey.split('-').map(Number);
                    return new Date(parts[0], parts[1] - 1, parts[2]);
                }

                function formatDateKey(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                function formatDisplayDate(dateKey) {
                    return parseDateKey(dateKey).toLocaleDateString('en-US', {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric'
                    });
                }

                function formatTimeLabel(time) {
                    const [hourValue, minute] = time.split(':').map(Number);
                    const suffix = hourValue >= 12 ? 'PM' : 'AM';
                    const hour = hourValue % 12 || 12;
                    return `${hour}:${String(minute).padStart(2, '0')} ${suffix}`;
                }

                document.querySelectorAll('.js-appointment-picker').forEach(function (picker) {
                    const dateInput = document.getElementById(picker.dataset.dateInputId);
                    const timeInput = document.getElementById(picker.dataset.timeInputId);
                    const bookedScript = picker.nextElementSibling;
                    const currentSchedule = picker.previousElementSibling?.matches('[data-current-schedule]')
                        ? picker.previousElementSibling
                        : null;
                    const changeSchedule = currentSchedule?.querySelector('[data-change-schedule]');
                    const collapseSchedule = picker.querySelector('[data-collapse-schedule]');
                    const bookedAppointments = JSON.parse(bookedScript?.textContent || '{}');
                    const calendarGrid = picker.querySelector('[data-calendar-grid]');
                    const monthYear = picker.querySelector('[data-month-year]');
                    const timePickerPanel = picker.querySelector('[data-time-picker-panel]');
                    const selectedDateLabel = picker.querySelector('[data-selected-date-label]');
                    const timeSlotContainer = picker.querySelector('[data-time-slot-container]');
                    const selectedSummary = picker.querySelector('[data-selected-summary]');
                    let selectedDate = dateInput.value || picker.dataset.selectedDate || '';
                    let selectedTime = timeInput.value || picker.dataset.selectedTime || '';
                    let currentDate = selectedDate ? parseDateKey(selectedDate) : new Date();

                    changeSchedule?.addEventListener('click', function () {
                        currentSchedule.hidden = true;
                        picker.hidden = false;
                    });

                    collapseSchedule?.addEventListener('click', function () {
                        if (!currentSchedule) {
                            return;
                        }

                        picker.hidden = true;
                        currentSchedule.hidden = false;
                    });

                    function bookedTimesFor(dateKey) {
                        return bookedAppointments[dateKey] || [];
                    }

                    function renderCalendar() {
                        const year = currentDate.getFullYear();
                        const month = currentDate.getMonth();
                        const firstDay = new Date(year, month, 1).getDay();
                        const daysInMonth = new Date(year, month + 1, 0).getDate();
                        const startOffset = firstDay === 0 ? 6 : firstDay - 1;
                        const todayKey = formatDateKey(new Date());

                        monthYear.textContent = currentDate.toLocaleDateString('en-US', {
                            month: 'long',
                            year: 'numeric'
                        });
                        calendarGrid.innerHTML = '';

                        for (let index = 0; index < startOffset; index++) {
                            const blank = document.createElement('div');
                            blank.className = 'appointment-picker__day';
                            blank.style.background = '#f9fafb';
                            calendarGrid.appendChild(blank);
                        }

                        for (let day = 1; day <= daysInMonth; day += 1) {
                            const date = new Date(year, month, day);
                            const dateKey = formatDateKey(date);
                            const bookedCount = bookedTimesFor(dateKey).filter(time => workingHours.includes(time)).length;
                            const availableCount = workingHours.length - bookedCount;
                            const isPast = dateKey < todayKey;
                            const cell = document.createElement('button');
                            cell.type = 'button';
                            cell.className = 'appointment-picker__day';
                            cell.disabled = isPast;
                            cell.dataset.date = dateKey;

                            if (dateKey === selectedDate) {
                                cell.classList.add('is-selected');
                            }

                            cell.innerHTML = `
                                <span class="appointment-picker__day-number">${day}</span>
                                <span class="appointment-picker__day-status">${isPast ? 'Unavailable' : `${availableCount} available`}</span>
                                <span class="appointment-picker__day-status">${isPast ? '' : `${bookedCount} taken`}</span>
                            `;

                            cell.addEventListener('click', function () {
                                selectedDate = dateKey;
                                selectedTime = '';
                                dateInput.value = selectedDate;
                                timeInput.value = '';
                                renderCalendar();
                                renderTimeSlots();
                                renderSummary();
                            });

                            calendarGrid.appendChild(cell);
                        }
                    }

                    function renderTimeSlots() {
                        if (!selectedDate) {
                            timePickerPanel.hidden = true;
                            return;
                        }

                        const bookedTimes = bookedTimesFor(selectedDate);
                        selectedDateLabel.textContent = formatDisplayDate(selectedDate);
                        timePickerPanel.hidden = false;
                        timeSlotContainer.innerHTML = '';

                        workingHours.forEach(function (time) {
                            const slot = document.createElement('button');
                            const isSelected = time === selectedTime;
                            const now = new Date();
                            const todayKey = formatDateKey(now);
                            const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
                            const isPastTime = selectedDate === todayKey && time <= currentTime;
                            const isTaken = (bookedTimes.includes(time) || isPastTime) && !isSelected;
                            slot.type = 'button';
                            slot.className = `appointment-picker__slot ${isTaken ? 'is-taken' : 'is-available'}`;
                            slot.textContent = `${formatTimeLabel(time)} ${isTaken ? (isPastTime ? '- Unavailable' : '- Taken') : '- Available'}`;
                            slot.disabled = isTaken;

                            if (isSelected) {
                                slot.classList.add('is-selected');
                                slot.textContent = `${formatTimeLabel(time)} - Selected`;
                            }

                            slot.addEventListener('click', function () {
                                selectedTime = time;
                                timeInput.value = selectedTime;
                                renderTimeSlots();
                                renderSummary();
                            });

                            timeSlotContainer.appendChild(slot);
                        });
                    }

                    function renderSummary() {
                        if (!selectedDate || !selectedTime) {
                            selectedSummary.hidden = true;
                            selectedSummary.textContent = '';
                            return;
                        }

                        selectedSummary.hidden = false;
                        selectedSummary.textContent = `Selected: ${formatDisplayDate(selectedDate)} at ${formatTimeLabel(selectedTime)}`;
                    }

                    picker.querySelector('[data-prev-month]').addEventListener('click', function () {
                        currentDate.setMonth(currentDate.getMonth() - 1);
                        renderCalendar();
                    });

                    picker.querySelector('[data-next-month]').addEventListener('click', function () {
                        currentDate.setMonth(currentDate.getMonth() + 1);
                        renderCalendar();
                    });

                    renderCalendar();
                    renderTimeSlots();
                    renderSummary();
                });
            });
        </script>
    @endpush
@endonce
