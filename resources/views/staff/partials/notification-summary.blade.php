<section class="notification-summary-grid">
    <article class="notification-summary-card">
        <span class="summary-label">New Entries</span>
        <strong>{{ $newAppointmentEntries->count() }}</strong>
        <p class="notification-section-copy">Appointments created in the last 24 hours.</p>
    </article>

    <article class="notification-summary-card">
        <span class="summary-label">Cancelled Today</span>
        <strong>{{ $cancelledTodayAppointments->count() }}</strong>
        <p class="notification-section-copy">Appointments updated to cancelled today in PH time.</p>
    </article>

    <article class="notification-summary-card">
        <span class="summary-label">Did Not Arrive</span>
        <strong>{{ $recentDidNotArriveAppointments->count() }}</strong>
        <p class="notification-section-copy">Appointments marked as missed in the last 24 hours.</p>
    </article>

    <article class="notification-summary-card">
        <span class="summary-label">Adherence</span>
        <strong>{{ $adherenceNotifications->count() }}</strong>
        <p class="notification-section-copy">Medication doses confirmed as taken in the live window.</p>
    </article>

    <article class="notification-summary-card">
        <span class="summary-label">Live Alerts</span>
        <strong>{{ $totalLiveAlerts }}</strong>
        <p class="notification-section-copy">Current appointment updates shown in this live feed.</p>
    </article>
</section>
