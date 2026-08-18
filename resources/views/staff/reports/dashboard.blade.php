@extends('layouts.app')

@section('title', 'Staff Reports')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    @php
        $complianceMap = $prescriptionsByCompliance->keyBy('intake_status');
        $takenCount = $complianceMap->get('Taken')->count ?? 0;
        $missedCount = $complianceMap->get('Missed')->count ?? 0;
        $pendingCount = $complianceMap->get('Pending')->count ?? 0;
        $delayedCount = $complianceMap->get('Delayed')->count ?? 0;
    @endphp

    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">Reports</h1>
                <p class="hero-copy">
                    Review clinic activity across appointments, consultations, prescriptions, and medical records for the selected date range.
                </p>
            </div>

            <div class="action-row">
                <a href="{{ route('staff.reports.appointments', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-secondary">Appointment Report</a>
                <a href="{{ route('staff.reports.prescriptions', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-primary">Prescription Report</a>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Reporting Window</h2>
                <p class="section-copy">Change the dates below to refresh every summary card on this dashboard.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('staff.reports') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="from_date">From</label>
                <input id="from_date" name="from_date" type="date" class="field-control" value="{{ $fromDate }}">
            </div>

            <div class="field">
                <label class="field-label" for="to_date">To</label>
                <input id="to_date" name="to_date" type="date" class="field-control" value="{{ $toDate }}">
            </div>

            <div class="action-row" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Refresh Report</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Appointments</h2>
                <p class="section-copy">Appointment volume and status totals for the current reporting window.</p>
            </div>
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <span class="metric-label">Total Appointments</span>
                <strong class="metric-value">{{ $totalAppointments }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Approved</span>
                <strong class="metric-value">{{ $approvedAppointments }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Completed</span>
                <strong class="metric-value">{{ $completedAppointments }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Cancelled</span>
                <strong class="metric-value">{{ $cancelledAppointments }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Missed</span>
                <strong class="metric-value">{{ $missedAppointments }}</strong>
            </div>
        </div>

        <div class="info-grid" style="margin-top: 1rem;">
            @forelse ($appointmentsByMode as $mode)
                <div class="detail-panel">
                    <span class="detail-label">{{ $mode->consultation_mode }}</span>
                    <p>{{ $mode->count }} appointment{{ $mode->count === 1 ? '' : 's' }}</p>
                </div>
            @empty
                <div class="empty-state">
                    No appointments fall within this date range.
                </div>
            @endforelse
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Consultations And Records</h2>
                <p class="section-copy">High-level totals for visits and records created during the same time period.</p>
            </div>
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <span class="metric-label">Consultations</span>
                <strong class="metric-value">{{ $totalConsultations }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Completed Consultations</span>
                <strong class="metric-value">{{ $completedConsultations }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Prescriptions Issued</span>
                <strong class="metric-value">{{ $totalPrescriptions }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Medical Records</span>
                <strong class="metric-value">{{ $totalMedicalRecords }}</strong>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Adherence Status Breakdown</h2>
                <p class="section-copy">Dose logs grouped by the current `intake_status` values in the adherence log table.</p>
            </div>
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <span class="metric-label">Taken</span>
                <strong class="metric-value">{{ $takenCount }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Missed</span>
                <strong class="metric-value">{{ $missedCount }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Pending</span>
                <strong class="metric-value">{{ $pendingCount }}</strong>
            </div>
            <div class="metric-card">
                <span class="metric-label">Delayed</span>
                <strong class="metric-value">{{ $delayedCount }}</strong>
            </div>
        </div>
    </section>
@endsection
