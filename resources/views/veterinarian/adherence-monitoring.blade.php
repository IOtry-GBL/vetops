@extends('layouts.app')

@section('title', 'Medication Adherence Monitoring')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('vet.medical-records') }}" class="sidebar-item">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item active">
        Medication Adherence
    </a>
@endsection

@section('content')

<!-- DATATABLE CSS -->
<link rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<link rel="stylesheet"
    href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<section class="hero-card">

    <div class="hero-row">

        <div>

            <h1 class="hero-title">
                Medication Adherence Monitoring
            </h1>

            <p class="hero-copy">
                Track medication compliance across your patients,
                identify non-adherent cases, and intervene early
                to improve treatment outcomes.
            </p>

        </div>

        <div class="action-row">

            <a href="{{ route('vet.prescriptions') }}"
                class="btn btn-secondary">

                Create Prescription

            </a>

        </div>

    </div>

</section>

<!-- STATS -->
<section class="stat-grid">

    <div class="widget">

        <span class="widget-title">
            Overall Adherence Rate
        </span>

        <strong class="widget-value">
            {{ $adherenceRate }}%
        </strong>

        <p class="muted-copy">

            @if ($adherenceRate >= 80)

                <span style="color:#10b981;">
                    Excellent adherence
                </span>

            @elseif($adherenceRate >= 60)

                <span style="color:#f59e0b;">
                    Moderate adherence
                </span>

            @else

                <span style="color:#ef4444;">
                    Needs improvement
                </span>

            @endif

        </p>

    </div>

    <div class="widget">

        <span class="widget-title">
            Active Prescriptions
        </span>

        <strong class="widget-value">
            {{ $totalPrescriptions }}
        </strong>

        <p class="muted-copy">
            Currently tracked medications.
        </p>

    </div>

    <div class="widget">

        <span class="widget-title">
            Low Adherence Cases
        </span>

        <strong class="widget-value">
            {{ $lowAdherencePrescriptions->count() }}
        </strong>

        <p class="muted-copy">
            Prescriptions with &lt; 70% taken doses.
        </p>

    </div>

    <div class="widget">

        <span class="widget-title">
            Tracked Doses
        </span>

        <strong class="widget-value">
            {{ $prescriptionsWithLogs }}
        </strong>

        <p class="muted-copy">
            Medications with adherence logs.
        </p>

    </div>

</section>



<!-- ALL PRESCRIPTIONS -->
<section class="card" data-filter="all">

    <div class="surface-header">

        <div>

            <h2>All Prescriptions</h2>

            <p class="section-copy">
                Search, sort, and manage prescriptions.
            </p>

        </div>

    </div>

    <!-- FILTER BUTTONS -->
    <div style="
        display:flex;
        justify-content:flex-end;
        align-items:center;
        gap:0.75rem;
        margin-bottom:1rem;
        flex-wrap:wrap;
    ">

        <button
            class="btn btn-secondary filter-btn"
            onclick="showFilter('all')">

            All Prescriptions
            ({{ $totalPrescriptions }})

        </button>

        

    </div>

    <div style="overflow-x:auto;">

        <table
            id="prescriptionsTable"
            class="display responsive nowrap"
            style="width:100%">

            <thead>

                <tr>

                    <th>Medication</th>
                    <th>Patient</th>
                    <th>Owner</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Adherence</th>
                    <th>Issued</th>
                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

                @foreach ($prescriptions as $prescription)

                    @php

                        $adherenceLogs = is_iterable($prescription->adherenceLogs)
                            ? $prescription->adherenceLogs
                            : collect();

                        $confirmedDoses = $adherenceLogs
                            ->where('intake_status', 'Taken')
                            ->count();

                        $totalScheduled = $adherenceLogs->count();

                        $prescriptionAdherence = $totalScheduled > 0
                            ? round(($confirmedDoses / $totalScheduled) * 100, 2)
                            : 0;

                        $statusColor =
                            $prescriptionAdherence >= 80
                                ? '#10b981'
                                : ($prescriptionAdherence >= 60
                                    ? '#f59e0b'
                                    : '#ef4444');

                        // Check if prescription is complete
                        $issuedDate = $prescription->issued_at;
                        $durationDays = (int) filter_var($prescription->duration, FILTER_SANITIZE_NUMBER_INT);
                        $endDate = $issuedDate ? $issuedDate->addDays($durationDays) : null;
                        $isComplete = $endDate && now() > $endDate;

                    @endphp

                    <tr>

                        <td>

                            <strong>
                                {{ $prescription->medication_name }}
                            </strong>

                            @if($isComplete)
                                <br>
                                <small style="color: #10b981; font-weight: 600;">Completed</small>
                            @endif

                        </td>

                        <td>

                            {{ $prescription->medicalRecord->pet->name ?? 'Unknown' }}

                            <br>

                            <small>
                                {{ $prescription->medicalRecord->pet->species ?? 'N/A' }}
                            </small>

                        </td>

                        <td>

                            {{ $prescription->medicalRecord->pet->owner->name ?? 'Unknown Owner' }}

                        </td>

                        <td>
                            {{ $prescription->dosage }}
                        </td>

                        <td>
                            {{ $prescription->frequency }}
                        </td>

                        <td>
                            {{ $prescription->duration }}
                        </td>

                        <td>

                            <span style="
                                background:{{ $statusColor }};
                                color:white;
                                padding:0.35rem 0.75rem;
                                border-radius:999px;
                                font-size:0.85rem;
                                font-weight:600;
                            ">

                                {{ $prescriptionAdherence }}%

                            </span>

                        </td>

                        <td>

                            {{ $prescription->issued_at?->timezone(\App\Services\AdherenceService::clinicTimezone())->format('M d, Y') ?? 'N/A' }}

                        </td>

                        <td>

                            <button
                                class="btn btn-secondary"
                                style="font-size:0.75rem;"
                                onclick="showLogs({{ $prescription->prescription_id }})">

                                View Details

                            </button>

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</section>
<!-- LOW ADHERENCE -->
<section
    class="card"
    data-filter="low"
    style="display:none;">

    <div class="surface-header">

        <div>

            <h2>
                At-Risk Cases (&lt; 70% Adherence)
            </h2>

            <p class="section-copy">
                Prescriptions requiring intervention.
            </p>

        </div>

    </div>

    <div
        class="list-grid"
        style="display:grid; gap:1rem;">

        @forelse ($lowAdherencePrescriptions as $prescription)

            @php

                $adherenceLogs = is_iterable($prescription->adherenceLogs)
                    ? $prescription->adherenceLogs
                    : collect();

                $confirmedDoses = $adherenceLogs
                    ->where('intake_status', 'Taken')
                    ->count();

                $totalScheduled = $adherenceLogs->count();

                $prescriptionAdherence = $totalScheduled > 0
                    ? round(($confirmedDoses / $totalScheduled) * 100, 2)
                    : 0;

            @endphp

            <article
                class="list-card"
                style="border-left:4px solid #ef4444;">

                <div>

                    <div
                        class="item-title"
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                        ">

                        <span>
                            {{ $prescription->medication_name }}
                        </span>

                        <span style="
                            font-size:0.875rem;
                            background:#ef4444;
                            color:white;
                            padding:0.25rem 0.75rem;
                            border-radius:9999px;
                            font-weight:600;
                        ">

                            {{ $prescriptionAdherence }}%

                        </span>

                    </div>

                    <div style="margin-top:0.75rem;">

                        <p class="item-copy">

                            <strong>Patient:</strong>

                            {{ $prescription->medicalRecord->pet->name ?? 'Unknown' }}

                        </p>

                        <p class="item-copy">

                            <strong>Owner:</strong>

                            {{ $prescription->medicalRecord->pet->owner->name ?? 'Unknown Owner' }}

                        </p>

                    </div>

                </div>

            </article>

        @empty

            <div class="empty-state">

                <p style="font-size:1rem; margin:0;">
                    Great news! No low-adherence cases detected.
                </p>

            </div>

        @endforelse

    </div>

</section>

<!-- MODAL -->
<div id="logsModal" style="
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    z-index:9999;
    overflow:auto;
">

    <div style="
        background:white;
        width:90%;
        max-width:900px;
        margin:5% auto;
        padding:2rem;
        border-radius:1rem;
        position:relative;
    ">

        <button onclick="closeLogsModal()" style="
            position:absolute;
            top:1rem;
            right:1rem;
            border:none;
            background:none;
            font-size:2rem;
            cursor:pointer;
        ">
            &times;
        </button>

        <h2 style="margin-bottom:1rem;">
            Adherence Logs
        </h2>

        @foreach ($prescriptions as $prescription)

            <div
                class="log-content"
                id="logs-{{ $prescription->prescription_id }}"
                style="display:none;">

                @php

                    $adherenceLogs = is_iterable($prescription->adherenceLogs)
                        ? $prescription->adherenceLogs
                        : collect();

                @endphp

                @if ($adherenceLogs->isNotEmpty())

                    <table style="
                        width:100%;
                        border-collapse:collapse;
                    ">

                        <thead>

                            <tr style="background:#f3f4f6;">

                                <th style="padding:0.75rem;">
                                    Scheduled
                                </th>

                                <th style="padding:0.75rem;">
                                    Status
                                </th>

                                <th style="padding:0.75rem;">
                                    Confirmed
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($adherenceLogs as $log)

                                @php
                                    $scheduledTime = $log->scheduled_datetime;
                                    $currentTime = now();
                                    
                                    // Auto-mark as "miss" if 3+ hours late and not taken
                                    $effectiveStatus = $log->intake_status;
                                    
                                    if ($scheduledTime && $log->intake_status !== 'Taken') {
                                        // Check if current time is 3+ hours after scheduled time
                                        $threeHoursAfterScheduled = $scheduledTime->copy()->addHours(3);
                                        if ($currentTime->isAfter($threeHoursAfterScheduled)) {
                                            $effectiveStatus = 'Missed';
                                        }
                                    }
                                @endphp

                                <tr style="
                                    border-top:1px solid #e5e7eb;
                                ">

                                    <td style="padding:0.75rem;">

                                        {{ $scheduledTime?->timezone(\App\Services\AdherenceService::clinicTimezone())->format('M d, Y g:i A') ?? 'N/A' }}

                                    </td>

                                    <td style="padding:0.75rem;">

                                        <span style="
                                            padding: 0.25rem 0.5rem;
                                            border-radius: 0.375rem;
                                            font-size: 0.85rem;
                                            font-weight: 600;
                                            @if($effectiveStatus === 'Taken')
                                                background: #d1fae5;
                                                color: #065f46;
                                            @elseif($effectiveStatus === 'Missed')
                                                background: #fee2e2;
                                                color: #7f1d1d;
                                            @else
                                                background: #fef3c7;
                                                color: #92400e;
                                            @endif
                                        ">
                                            {{ $effectiveStatus }}
                                        </span>

                                    </td>

                                    <td style="padding:0.75rem;">

                                        {{ $log->confirmation_time?->timezone(\App\Services\AdherenceService::clinicTimezone())->format('M d, Y g:i A') ?? '—' }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                @else

                    <div style="
                        padding:1rem;
                        background:#fef3c7;
                        border-radius:0.5rem;
                        color:#92400e;
                    ">

                        No adherence logs recorded yet.

                    </div>

                @endif

            </div>

        @endforeach

    </div>

</div>

<!-- JQUERY -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DATATABLE -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>

    $(document).ready(function () {

        $('#prescriptionsTable').DataTable({

            responsive: true,

            pageLength: 10,

            order: [[6, 'desc']],

            columnDefs: [
                {
                    orderable: false,
                    targets: 8
                }
            ]

        });

    });

    function showLogs(id)
    {
        document.querySelectorAll('.log-content').forEach(el => {
            el.style.display = 'none';
        });

        document.getElementById('logs-' + id).style.display = 'block';

        document.getElementById('logsModal').style.display = 'block';
    }

    function closeLogsModal()
    {
        document.getElementById('logsModal').style.display = 'none';
    }

    function showFilter(filter)
    {
        document.querySelectorAll('[data-filter]').forEach(el => {
            el.style.display = 'none';
        });

        document.querySelector(`[data-filter="${filter}"]`).style.display = 'block';
    }

</script>

@endsection