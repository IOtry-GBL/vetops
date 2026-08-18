<section class="notification-feed-grid">
    <div class="notification-feed-column">
        <article class="notification-feed-card">
            <div class="surface-header" style="padding-inline: 0; padding-top: 0;">
                <div>
                    <h2>New Appointment Entries</h2>
                    <p class="notification-section-copy">Most recent appointments entered into the clinic queue.</p>
                </div>
            </div>

            <div class="notification-item-list">
                @forelse ($newAppointmentEntries as $appointment)
                    @php
                        $pet = $appointment->pet;
                        $owner = $pet?->owner;
                        $createdAt = $appointment->created_at?->copy()->timezone('Asia/Manila');
                    @endphp

                    <article class="notification-item notification-item-new">
                        <div class="notification-item-head">
                            <div class="notification-item-title">{{ $pet?->name ?? 'Unknown Pet' }}</div>
                            <span class="notification-time-chip">{{ $createdAt?->diffForHumans() ?? 'Just now' }}</span>
                        </div>

                        <p class="notification-item-copy">
                            {{ $owner?->name ?? $owner?->full_name ?? 'Unknown Owner' }} submitted a {{ strtolower($appointment->consultation_mode ?? 'clinic') }} appointment for
                            {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'an unscheduled time' }}.
                        </p>

                        <div class="notification-meta">
                            <span class="pill pill-neutral">{{ $appointment->status }}</span>
                            <span class="pill pill-neutral">{{ $pet?->species ?? 'Unknown species' }}</span>
                        </div>

                        <div class="action-row">
                            <a href="{{ route('staff.appointments.confirm', $appointment) }}" class="btn btn-secondary">Open Appointment</a>
                        </div>
                    </article>
                @empty
                    <div class="notification-empty">
                        No new appointment entries were created in the last 24 hours.
                    </div>
                @endforelse
            </div>
        </article>
    </div>

    <div class="notification-feed-column">
        <article class="notification-feed-card">
            <div class="surface-header" style="padding-inline: 0; padding-top: 0;">
                <div>
                    <h2>Cancelled Today</h2>
                    <p class="notification-section-copy">Appointment cancellations recorded today using Philippine time.</p>
                </div>
            </div>

            <div class="notification-item-list">
                @forelse ($cancelledTodayAppointments as $appointment)
                    @php
                        $pet = $appointment->pet;
                        $owner = $pet?->owner;
                        $updatedAt = $appointment->updated_at?->copy()->timezone('Asia/Manila');
                    @endphp

                    <article class="notification-item notification-item-cancelled">
                        <div class="notification-item-head">
                            <div class="notification-item-title">{{ $pet?->name ?? 'Unknown Pet' }}</div>
                            <span class="notification-time-chip">{{ $updatedAt?->diffForHumans() ?? 'Today' }}</span>
                        </div>

                        <p class="notification-item-copy">
                            {{ $owner?->name ?? $owner?->full_name ?? 'Unknown Owner' }} has a cancelled appointment that was scheduled for
                            {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'an unscheduled time' }}.
                        </p>

                        <div class="notification-meta">
                            <span class="pill pill-warn">Cancelled</span>
                            <span class="pill pill-neutral">{{ $appointment->consultation_mode ?? 'In-clinic' }}</span>
                        </div>

                    </article>
                @empty
                    <div class="notification-empty">
                        No appointments have been cancelled today.
                    </div>
                @endforelse
            </div>
        </article>
    </div>

    <div class="notification-feed-column">
        <article class="notification-feed-card">
            <div class="surface-header" style="padding-inline: 0; padding-top: 0;">
                <div>
                    <h2>Did Not Arrive Recently</h2>
                    <p class="notification-section-copy">Appointments marked as did not arrive in the last 24 hours.</p>
                </div>
            </div>

            <div class="notification-item-list">
                @forelse ($recentDidNotArriveAppointments as $appointment)
                    @php
                        $pet = $appointment->pet;
                        $owner = $pet?->owner;
                        $updatedAt = $appointment->updated_at?->copy()->timezone('Asia/Manila');
                    @endphp

                    <article class="notification-item notification-item-missed">
                        <div class="notification-item-head">
                            <div class="notification-item-title">{{ $pet?->name ?? 'Unknown Pet' }}</div>
                            <span class="notification-time-chip">{{ $updatedAt?->diffForHumans() ?? 'Recently' }}</span>
                        </div>

                        <p class="notification-item-copy">
                            {{ $owner?->name ?? $owner?->full_name ?? 'Unknown Owner' }} missed the appointment scheduled for
                            {{ $appointment->appointment_date?->format('M j, Y g:i A') ?? 'an unscheduled time' }}.
                        </p>

                        <div class="notification-meta">
                            <span class="pill pill-danger">Did Not Arrive</span>
                            <span class="pill pill-neutral">{{ $appointment->consultation_mode ?? 'In-clinic' }}</span>
                        </div>

                    </article>
                @empty
                    <div class="notification-empty">
                        No appointments were marked as did not arrive in the last 24 hours.
                    </div>
                @endforelse
            </div>
        </article>
    </div>

    <div class="notification-feed-column">
        <article class="notification-feed-card">
            <div class="surface-header" style="padding-inline: 0; padding-top: 0;">
                <div>
                    <h2>Adherence Alerts</h2>
                    <p class="notification-section-copy">Medication doses confirmed as taken in the last 24 hours.</p>
                </div>
            </div>

            <div class="notification-item-list">
                @forelse ($adherenceNotifications as $notification)
                    @php
                        $prescription = $notification->adherenceLog?->prescription;
                        $pet = $prescription?->medicalRecord?->pet;
                        $owner = $pet?->owner ?? $notification->user;
                        $scheduledAt = $notification->scheduled_at?->copy()->timezone('Asia/Manila');
                    @endphp

                    <article class="notification-item notification-item-new">
                        <div class="notification-item-head">
                            <div class="notification-item-title">{{ $pet?->name ?? 'Unknown Pet' }}</div>
                            <span class="notification-time-chip">{{ $scheduledAt?->diffForHumans() ?? 'Recently' }}</span>
                        </div>

                        <p class="notification-item-copy">
                            {{ $owner?->name ?? $owner?->full_name ?? 'Unknown Owner' }} confirmed the scheduled intake for
                            {{ $notification->medication_name }} {{ $notification->dosage }}.
                        </p>

                        <div class="notification-meta">
                            <span class="pill pill-success">Taken</span>
                            <span class="pill pill-neutral">{{ $scheduledAt?->format('M j, Y g:i A') ?? 'No schedule' }} PH</span>
                        </div>

                        @if ($prescription)
                            <div class="action-row">
                                <a href="{{ route('staff.prescriptions.details', $prescription) }}" class="btn btn-secondary">Open Adherence</a>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="notification-empty">
                        No scheduled doses were confirmed as taken in the last 24 hours.
                    </div>
                @endforelse
            </div>
        </article>
    </div>
</section>
