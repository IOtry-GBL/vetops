@extends('layouts.app')

@section('title', 'Medical Record Report')

@section('sidebar')
    <a href="{{ route('vet.dashboard') }}" class="sidebar-item">Dashboard</a>
    <a href="{{ route('vet.appointments') }}" class="sidebar-item">Appointments</a>
    <a href="{{ route('vet.medical-records') }}" class="sidebar-item active">Medical Records</a>
    <a href="{{ route('vet.prescriptions') }}" class="sidebar-item">E-Prescriptions</a>
    <a href="{{ route('vet.adherence-monitoring') }}" class="sidebar-item">Medication Adherence</a>
@endsection

@section('content')
    @php
        $pet = $record->pet;
        $owner = $pet?->owner;
        $veterinarian = $record->consultation?->veterinarian;
        $recordAdherenceLogs = $record->prescriptions->flatMap->adherenceLogs;
        $takenDoses = $recordAdherenceLogs->where('intake_status', 'Taken')->count();
        $totalDoses = $recordAdherenceLogs->count();
        $adherenceRate = $totalDoses > 0 ? round(($takenDoses / $totalDoses) * 100) : null;
    @endphp

    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">Record No. {{ $record->record_id }}</h1>
                <p class="hero-copy">
                    Medical report for {{ $pet?->name ?? 'Unknown Pet' }}.
                </p>
            </div>

            <div class="action-row">
                <a href="{{ route('vet.medical-records') }}" class="btn btn-secondary">Back To Record List</a>
            </div>
        </div>
    </section>

    <article class="card medical-report">
        <div class="report-section-title">
            <div>
                <span class="eyebrow">Clinical Report</span>
                <h3>{{ $record->diagnosis ?: 'Medical record details' }}</h3>
            </div>
        </div>

        <div class="report-form-grid">
            <div class="report-field">
                <span class="detail-label">Record No.</span>
                <p>{{ $record->record_id }}</p>
            </div>
            <div class="report-field">
                <span class="detail-label">Consultation Date</span>
                <p>{{ $record->consultation?->consultation_date?->format('F j, Y g:i A') ?? 'Not recorded' }}</p>
            </div>
            <div class="report-field wide">
                <span class="detail-label">Pet / Owner Name And Contact Info</span>
                <p>
                    {{ $pet?->name ?? 'Unknown Pet' }}
                    {{ $pet?->species ? '('.$pet->species.')' : '' }}
                    / {{ $owner?->name ?? 'Unknown Owner' }}
                    / {{ $owner?->contact_no ?? $owner?->phone ?? 'No contact recorded' }}
                    / {{ $owner?->email ?? 'No email recorded' }}
                </p>
            </div>
            <div class="report-field">
                <span class="detail-label">Attending Veterinarian</span>
                <p>{{ $veterinarian?->name ? 'Dr. '.$veterinarian->name : 'Not assigned' }}</p>
            </div>
            @if ($record->follow_up_date)
                <div class="report-field">
                    <span class="detail-label">Follow-Up Date</span>
                    <p>{{ $record->follow_up_date->format('F j, Y') }}</p>
                </div>
            @endif
            <div class="report-field wide">
                <span class="detail-label">Diagnosis</span>
                <p>{{ $record->diagnosis ?: 'No diagnosis recorded.' }}</p>
            </div>
            <div class="report-field wide">
                <span class="detail-label">Treatment Plan</span>
                <p>{{ $record->treatment_plan ?: 'No treatment plan recorded.' }}</p>
            </div>
            <div class="report-field">
                <span class="detail-label">Vaccination Notes</span>
                <p>{{ $record->vaccination_notes ?: 'No vaccination notes recorded.' }}</p>
            </div>
        </div>

        <div class="report-subsection">
            <div class="report-section-head compact">
                <div>
                    <span class="detail-label">Prescription Records And Adherence Percentage</span>
                    <p class="section-copy">Prescription records linked to this medical record with adherence percentage per prescription.</p>
                </div>
                <span class="pill {{ $record->prescriptions->count() > 0 ? 'pill-success' : 'pill-neutral' }}">
                    {{ $record->prescriptions->count() }} item{{ $record->prescriptions->count() === 1 ? '' : 's' }}
                </span>
            </div>

            @if ($record->prescriptions->isNotEmpty())
                <div class="mini-table-wrap">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Dosage</th>
                                <th>Schedule</th>
                                <th>Issued</th>
                                <th>Adherence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($record->prescriptions as $prescription)
                                @php
                                    $prescriptionDoseTotal = $prescription->adherenceLogs->count();
                                    $prescriptionDoseTaken = $prescription->adherenceLogs->where('intake_status', 'Taken')->count();
                                    $prescriptionAdherence = $prescriptionDoseTotal > 0
                                        ? round(($prescriptionDoseTaken / $prescriptionDoseTotal) * 100)
                                        : null;
                                @endphp
                                <tr>
                                    <td>{{ $prescription->medication_name }}</td>
                                    <td>{{ $prescription->dosage }}</td>
                                    <td>{{ $prescription->frequency }} / {{ $prescription->duration }}</td>
                                    <td>{{ $prescription->issued_at?->format('M j, Y') ?? 'Not recorded' }}</td>
                                    <td>{{ $prescriptionAdherence !== null ? $prescriptionAdherence.'%' : 'No data' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">No prescriptions are linked to this medical record.</div>
            @endif
        </div>

        <div class="report-subsection">
            <div class="report-section-head compact">
                <div>
                    <span class="detail-label">Medical Adherence Record</span>
                    <p class="section-copy">Overall dose adherence for prescriptions linked to this medical record.</p>
                </div>
                <span class="pill {{ $adherenceRate !== null && $adherenceRate >= 80 ? 'pill-success' : 'pill-warning' }}">
                    {{ $adherenceRate !== null ? $adherenceRate.'%' : 'No data' }}
                </span>
            </div>

            <div class="adherence-report-grid">
                <div class="report-field">
                    <span class="summary-label">Taken</span>
                    <strong>{{ $takenDoses }}</strong>
                </div>
                <div class="report-field">
                    <span class="summary-label">Total Amount To Be Taken</span>
                    <strong>{{ $totalDoses }}</strong>
                </div>
                <div class="report-field">
                    <span class="summary-label">Percentage</span>
                    <strong>{{ $adherenceRate !== null ? $adherenceRate.'%' : 'No data' }}</strong>
                </div>
            </div>
        </div>
    </article>
@endsection

@section('styles')
    <style>
        .report-card,
        .medical-report {
            background: rgba(255, 255, 255, 0.92);
        }

        .report-card {
            border-left: 5px solid rgba(15, 139, 141, 0.55);
        }

        .report-header,
        .report-section-head {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .report-section-head.compact {
            align-items: center;
        }

        .report-section-title {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--shell-line);
        }

        .report-section-title h3 {
            margin: 0.15rem 0 0;
            font-size: 1.35rem;
        }

        .report-stamp {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: rgba(15, 139, 141, 0.1);
            color: var(--shell-accent-deep);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .report-form-grid,
        .adherence-report-grid {
            display: grid;
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .report-form-grid {
            grid-template-columns: repeat(2, minmax(260px, 1fr));
        }

        .adherence-report-grid {
            grid-template-columns: repeat(3, minmax(180px, 1fr));
        }

        .report-field {
            padding: 1.05rem 1.1rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(248, 250, 252, 0.86);
        }

        .report-field.wide {
            grid-column: 1 / -1;
        }

        .report-field p {
            margin: 0.35rem 0 0;
            color: #374151;
            line-height: 1.55;
            word-break: break-word;
        }

        .report-field strong {
            display: block;
            color: var(--shell-ink);
            font-size: 1.2rem;
        }

        .report-subsection {
            margin-top: 1.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--shell-line);
        }

        .mini-table-wrap {
            overflow: auto;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(255, 255, 255, 0.92);
        }

        .mini-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .mini-table th,
        .mini-table td {
            padding: 1rem 1.05rem;
            border-bottom: 1px solid rgba(23, 49, 61, 0.08);
            text-align: left;
            vertical-align: top;
        }

        .mini-table th {
            background: #f8fafc;
            color: var(--shell-muted);
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .mini-table td:first-child {
            font-weight: 700;
            color: var(--shell-ink);
        }

        .pill-warning {
            background: rgba(183, 121, 31, 0.14);
            color: #8d5e12;
        }

        @media (max-width: 1100px) {
            .report-form-grid,
            .adherence-report-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
