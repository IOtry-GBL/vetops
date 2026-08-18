@extends('layouts.app')

@section('sidebar')
    <a href="{{ route('pet-owner.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('pet-owner.pets') }}" class="sidebar-item">My Pets</a>
    <a href="{{ route('pet-owner.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('pet-owner.medical-records') }}" class="sidebar-item">Medical Records</a>

    <a href="{{ route('pet-owner.prescriptions') }}" class="sidebar-item active">Prescriptions</a>
    <a href="{{ route('adherence.dashboard') }}" class="sidebar-item">Medication Reminders</a>
    <a href="{{ route('pet-owner.notifications') }}" class="sidebar-item">Notifications</a>
    <a href="#" class="sidebar-item" onclick="openPetCareAI(event)">Ask Pet Care AI</a>
@endsection

@section('content')
    @php
        $petCount = $prescriptions->pluck('medicalRecord.pet.pet_id')->filter()->unique()->count();
        $latestIssuedAt = $prescriptions->first()?->issued_at;
    @endphp

    @if (session('success'))
        <div class="alert alert-info">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert" style="margin-bottom: 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b;">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: start; flex-wrap: wrap;">
            <div>
                <h1 style="margin: 0;">Prescriptions</h1>
                <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
                    These are the real prescriptions written from your pet's appointment sessions.
                </p>
            </div>

            <div class="metric-strip">
                <div class="metric-card">
                    <span class="metric-label">Prescriptions</span>
                    <strong>{{ $prescriptions->count() }}</strong>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Pets</span>
                    <strong>{{ $petCount }}</strong>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Latest Issued</span>
                    <strong>{{ $latestIssuedAt ? $latestIssuedAt->format('M j, Y') : 'None yet' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Prescription History</h2>
        <p style="margin: 0.45rem 0 1rem 0; color: #6b7280;">
            Each entry below is pulled from the e-prescriptions created during veterinarian appointment sessions.
        </p>

        @if ($prescriptions->isNotEmpty())
            <div class="prescription-table-wrap">
                <table class="prescription-table">
                    <thead>
                        <tr>
                            <th>Issued</th>
                            <th>Pet</th>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Veterinarian</th>
                            <th>Dose Confirmation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prescriptions as $prescription)
                            @php
                                $record = $prescription->medicalRecord;
                                $pet = $record?->pet;
                                $veterinarian = $record?->consultation?->veterinarian;
                                $adherenceLogs = $prescription->adherenceLogs->sortBy('scheduled_datetime')->values();
                                $nextPendingDose = $adherenceLogs
                                    ->filter(fn ($log) => $log->intake_status === 'Pending')
                                    ->first();
                            @endphp

                            <tr>
                                <td>
                                    <span class="issued-chip">
                                        {{ $prescription->issued_at?->format('M j, Y') ?? 'Issued' }}
                                    </span>
                                    <span class="table-muted">
                                        {{ $prescription->issued_at?->format('g:i A') ?? '' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $pet?->name ?? 'Unknown Pet' }}</strong>
                                    @if ($pet?->species)
                                        <span class="table-muted">{{ $pet->species }}</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $prescription->medication_name }}</strong>
                                </td>
                                <td>{{ $prescription->dosage }}</td>
                                <td>{{ $prescription->frequency }}</td>
                                <td>{{ $prescription->duration }}</td>
                                <td>
                                    @php
                                        $currentStatus = $prescription->current_status;
                                        $statusBadgeClass = $currentStatus === 'completed' ? 'status-completed' : 'status-ongoing';
                                        $statusLabel = ucfirst($currentStatus);
                                    @endphp
                                    <span class="status-badge {{ $statusBadgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>{{ $veterinarian?->name ? 'Dr. '.$veterinarian->name : 'Assigned veterinarian' }}</td>
                                <td class="dose-cell">
                                    @if ($nextPendingDose)
                                        <details class="dose-disclosure">
                                            <summary>
                                                <span>Next Dose</span>
                                                <small>1 pending</small>
                                            </summary>

                                            <div class="adherence-summary-grid compact">
                                                <div class="adherence-stat">
                                                    <span class="detail-label">Taken</span>
                                                    <strong>{{ $adherenceLogs->where('intake_status', 'Taken')->count() }}</strong>
                                                </div>
                                                <div class="adherence-stat">
                                                    <span class="detail-label">Pending</span>
                                                    <strong>{{ $adherenceLogs->where('intake_status', 'Pending')->count() }}</strong>
                                                </div>
                                                <div class="adherence-stat">
                                                    <span class="detail-label">Missed</span>
                                                    <strong>{{ $adherenceLogs->where('intake_status', 'Missed')->count() }}</strong>
                                                </div>
                                                <div class="adherence-stat">
                                                    <span class="detail-label">Delayed</span>
                                                    <strong>{{ $adherenceLogs->where('intake_status', 'Delayed')->count() }}</strong>
                                                </div>
                                            </div>

                                            @php
                                                $scheduledAt = $nextPendingDose->scheduledDatetimeInClinicTimezone();
                                                $deadlineAt = $nextPendingDose->confirmationDeadlineInClinicTimezone();
                                            @endphp

                                            <div class="dose-list">
                                                <div class="dose-card {{ $nextPendingDose->isAvailableForConfirmation() ? 'is-ready' : '' }}">
                                                    <div>
                                                        <div class="dose-time">
                                                            {{ $scheduledAt?->format('M j, Y g:i A') ?? 'Schedule unavailable' }} PH
                                                        </div>
                                                        <p class="dose-copy">
                                                            Confirmation closes {{ $deadlineAt?->format('g:i A') ?? 'N/A' }} PH
                                                        </p>
                                                    </div>

                                                    <div class="dose-action-group">
                                                        @if ($nextPendingDose->isAvailableForConfirmation())
                                                            <span class="dose-status dose-status-pending">Ready To Confirm</span>
                                                            <form action="{{ route('adherence.confirm-dose', $nextPendingDose) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="btn btn-primary">Confirm Dose</button>
                                                            </form>
                                                        @else
                                                            <span class="dose-status dose-status-upcoming">Upcoming</span>
                                                            <button type="button" class="btn btn-secondary" disabled>
                                                                Available {{ $scheduledAt?->format('g:i A') ?? 'Soon' }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </details>
                                    @else
                                        <span class="dose-note">No active confirmation</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
                <div style="padding: 1.5rem; border: 1px dashed #d1d5db; border-radius: 0.85rem; color: #6b7280; text-align: center;">
                    No prescriptions have been issued yet from your appointment sessions.
                </div>
        @endif
    </div>
@endsection

@section('styles')
    <style>
        .metric-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(130px, 1fr));
            gap: 0.75rem;
            min-width: min(100%, 420px);
        }

        .metric-card,
        .detail-panel {
            padding: 0.95rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            background: #f8fafc;
        }

        .metric-label,
        .detail-label {
            display: block;
            font-size: 0.78rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
        }

        .metric-card strong,
        .detail-panel strong {
            color: #111827;
        }

        .dose-card.is-ready {
            animation: dose-ready-pulse 1.35s ease-in-out infinite;
            border-color: #22c55e;
            background: #dcfce7;
        }

        @keyframes dose-ready-pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.36), 0 10px 24px rgba(22, 101, 52, 0.08);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(34, 197, 94, 0), 0 14px 28px rgba(22, 101, 52, 0.16);
            }
        }

        .prescription-table-wrap {
            max-height: 660px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .prescription-table {
            width: 100%;
            min-width: 1120px;
            border-collapse: collapse;
        }

        .prescription-table th,
        .prescription-table td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            text-align: left;
        }

        .prescription-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
            color: #6b7280;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .prescription-table tr:last-child td {
            border-bottom: 0;
        }

        .prescription-table td {
            color: #374151;
        }

        .prescription-table strong {
            display: block;
            color: #111827;
        }

        .table-muted {
            display: block;
            margin-top: 0.25rem;
            color: #6b7280;
            font-size: 0.86rem;
            line-height: 1.35;
        }

        .dose-cell {
            min-width: 360px;
        }

        .issued-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .adherence-copy,
        .dose-copy {
            margin: 0.35rem 0 0 0;
            color: #6b7280;
            line-height: 1.5;
        }

        .adherence-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(110px, 1fr));
            gap: 0.75rem;
        }

        .adherence-summary-grid.compact {
            grid-template-columns: repeat(4, minmax(72px, 1fr));
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .adherence-stat {
            padding: 0.85rem 0.95rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            background: #f8fafc;
        }

        .adherence-summary-grid.compact .adherence-stat {
            padding: 0.65rem;
        }

        .dose-list {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .dose-card {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            padding: 0.95rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.9rem;
            background: #f8fafc;
        }

        .dose-disclosure {
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            background: #f8fafc;
        }

        .dose-disclosure summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            min-height: 44px;
            padding: 0.65rem 0.8rem;
            color: #111827;
            cursor: pointer;
            font-weight: 700;
            list-style: none;
        }

        .dose-disclosure summary::-webkit-details-marker {
            display: none;
        }

        .dose-disclosure summary::after {
            content: "+";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.45rem;
            height: 1.45rem;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 800;
        }

        .dose-disclosure[open] {
            padding: 0 0.8rem 0.8rem;
        }

        .dose-disclosure[open] summary {
            padding-inline: 0;
        }

        .dose-disclosure[open] summary::after {
            content: "-";
        }

        .dose-disclosure small {
            margin-left: auto;
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .dose-time {
            font-weight: 700;
            color: #111827;
        }

        .dose-action-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .dose-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .dose-status-pending {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .dose-status-upcoming {
            background: #e0f2fe;
            color: #0369a1;
        }

        .dose-status-confirmed {
            background: #dcfce7;
            color: #166534;
        }

        .dose-status-missed {
            background: #fee2e2;
            color: #991b1b;
        }

        .dose-status-delayed {
            background: #ffedd5;
            color: #9a3412;
        }

        .dose-status-neutral {
            background: #e5e7eb;
            color: #374151;
        }

        .dose-note {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .dose-empty {
            padding: 0.95rem 1rem;
            border: 1px dashed #d1d5db;
            border-radius: 0.9rem;
            color: #6b7280;
            background: #f8fafc;
        }

        .history-link {
            color: #1d4ed8;
            font-weight: 700;
            text-decoration: none;
        }

        .history-link:hover {
            text-decoration: underline;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-ongoing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        @media (max-width: 900px) {
            .metric-strip,
            .adherence-summary-grid {
                grid-template-columns: 1fr;
            }

            .prescription-table-wrap {
                max-height: 70vh;
            }
        }
    </style>
@endsection
