@extends('layouts.app')

@section('title', 'Medical Record Details')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    @php
        $pet = $medicalRecord->pet;
        $owner = $pet?->owner;
        $consultation = $medicalRecord->consultation;
        $veterinarian = $consultation?->veterinarian;
    @endphp

    <section class="hero-card">
        <div class="hero-row">
            <div>
                <h1 class="hero-title">{{ $pet?->name ?? 'Medical Record' }}</h1>
                <p class="hero-copy">
                    Review the consultation summary, treatment plan, vaccination notes, and every prescription linked to this record.
                </p>
            </div>

            <div class="action-row">
                <a href="{{ route('staff.medical-records') }}" class="btn btn-secondary">Back To Medical Records</a>
            </div>
        </div>
    </section>

    <div class="info-grid">
        <div class="summary-card">
            <span class="summary-label">Patient</span>
            <strong>{{ $pet?->name ?? 'Unknown Pet' }}</strong>
            <span>{{ $pet?->species ?? 'Unknown species' }}{{ $pet?->breed ? ' - '.$pet->breed : '' }}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Owner</span>
            <strong>{{ $owner?->name ?? 'Unknown Owner' }}</strong>
            <span>{{ $owner?->email ?? 'No email on file' }}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Veterinarian</span>
            <strong>{{ $veterinarian?->name ? 'Dr. '.$veterinarian->name : 'Not assigned' }}</strong>
            <span>{{ $consultation?->consultation_date?->format('M j, Y g:i A') ?? 'No consultation date recorded' }}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Record</span>
            <strong>Record {{ $medicalRecord->record_id }}</strong>
            <span>{{ $medicalRecord->prescriptions->count() }} linked prescription{{ $medicalRecord->prescriptions->count() === 1 ? '' : 's' }}</span>
        </div>
    </div>

    <div class="details-grid">
        <section class="card">
            <div class="surface-header">
                <div>
                    <h2>Clinical Details</h2>
                    <p class="section-copy">Core medical information saved during or after the consultation.</p>
                </div>
            </div>

            <div class="info-grid">
                <div class="detail-panel">
                    <span class="detail-label">Diagnosis</span>
                    <p>{{ $medicalRecord->diagnosis ?: 'No diagnosis recorded.' }}</p>
                </div>
                <div class="detail-panel">
                    <span class="detail-label">Treatment Plan</span>
                    <p>{{ $medicalRecord->treatment_plan ?: 'No treatment plan recorded.' }}</p>
                </div>
                <div class="detail-panel">
                    <span class="detail-label">Vaccination Notes</span>
                    <p>{{ $medicalRecord->vaccination_notes ?: 'No vaccination notes recorded.' }}</p>
                </div>
                <div class="detail-panel">
                    <span class="detail-label">Follow-Up Date</span>
                    <p>{{ $medicalRecord->follow_up_date?->format('F j, Y') ?? 'Not scheduled' }}</p>
                </div>
            </div>

            <div class="info-grid" style="margin-top: 1rem;">
                <div class="detail-panel">
                    <span class="detail-label">Chief Complaint</span>
                    <p>{{ $consultation?->chief_complaint ?: 'No chief complaint recorded.' }}</p>
                </div>
                <div class="detail-panel">
                    <span class="detail-label">Consultation Notes</span>
                    <p>{{ $consultation?->consultation_notes ?: 'No consultation notes recorded.' }}</p>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="surface-header">
                <div>
                    <h2>Linked Prescriptions</h2>
                    <p class="section-copy">Medication created from this medical record appears below.</p>
                </div>
            </div>

            <div class="table-responsive">
                @forelse ($medicalRecord->prescriptions as $prescription)
                    @php
                        $logCount = $prescription->adherenceLogs->count();
                        $takenRate = $logCount > 0
                            ? round(($prescription->adherenceLogs->where('intake_status', 'Taken')->count() / $logCount) * 100)
                            : null;
                    @endphp
                    @if ($loop->first)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Issued</th>
                                    <th>Adherence</th>
                                    <th>Logs</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                    @endif
                    @if ($loop->iteration <= 5)
                                <tr>
                                    <td class="col-medication">
                                        <strong>{{ $prescription->medication_name }}</strong>
                                    </td>
                                    <td class="col-dosage">
                                        <small>{{ $prescription->dosage }} / {{ $prescription->frequency }} / {{ $prescription->duration }}</small>
                                    </td>
                                    <td class="col-issued">
                                        {{ $prescription->issued_at?->format('M j, Y') ?? 'Unknown' }}
                                    </td>
                                    <td class="col-adherence">
                                        <span class="pill {{ $takenRate !== null && $takenRate >= 80 ? 'pill-success' : 'pill-neutral' }}">
                                            {{ $takenRate !== null ? $takenRate.'%' : 'No logs' }}
                                        </span>
                                    </td>
                                    <td class="col-logs">
                                        <span class="pill pill-neutral">{{ $logCount }}</span>
                                    </td>
                                    <td class="col-actions">
                                        <a href="{{ route('staff.prescriptions.details', $prescription->prescription_id) }}" class="btn btn-sm btn-primary" title="Open Prescription">View</a>
                                    </td>
                                </tr>
                    @endif
                    @if ($loop->last)
                            </tbody>
                        </table>
                    @endif
                @empty
                    <div class="empty-state">
                        No prescriptions are linked to this medical record yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@section('styles')
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1.2rem;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--shell-line, #e0e0e0);
            border-radius: var(--shell-radius-lg, 0.5rem);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9375rem;
        }

        .data-table thead {
            background: var(--shell-surface-secondary, #f5f5f5);
            border-bottom: 2px solid var(--shell-line, #e0e0e0);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table th {
            padding: 0.875rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary, #333);
            white-space: nowrap;
        }

        .data-table th:last-child {
            text-align: center;
        }

        .data-table tbody tr {
            border-bottom: 1px solid var(--shell-line, #e0e0e0);
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .data-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .data-table .col-medication {
            font-weight: 500;
            min-width: 120px;
        }

        .data-table .col-dosage {
            min-width: 100px;
            color: var(--text-secondary, #666);
            font-size: 0.875rem;
        }

        .data-table .col-issued {
            min-width: 90px;
        }

        .data-table .col-adherence {
            min-width: 90px;
        }

        .data-table .col-logs {
            text-align: center;
            min-width: 70px;
        }

        .data-table .col-actions {
            text-align: center;
            min-width: 100px;
            padding: 1rem 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
        }

        .pill {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .pill-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .pill-neutral {
            background-color: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        @media (max-width: 1200px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .data-table {
                font-size: 0.875rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem 0.625rem;
            }
        }

        @media (max-width: 768px) {
            .data-table {
                font-size: 0.8125rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.625rem;
            }

            .data-table .col-dosage,
            .data-table .col-issued {
                display: none;
            }

            .btn-sm {
                width: auto;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
@endsection
