@extends('layouts.app')

@section('title', 'Staff Prescriptions')

@section('sidebar')
    @include('staff.sidebar')
@endsection

@section('content')
    @php
        $pagePrescriptions = $prescriptions->getCollection();
        $patientCount = $pagePrescriptions->pluck('medicalRecord.pet.pet_id')->filter()->unique()->count();
        $withLogsCount = $pagePrescriptions->filter(fn ($prescription) => $prescription->adherenceLogs->isNotEmpty())->count();
        $latestIssuedAt = $pagePrescriptions->first()?->issued_at;
    @endphp

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Filter Prescriptions</h2>
                <p class="section-copy">Search by medication or patient, then narrow the timeline if you need a smaller list.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('staff.prescriptions') }}" class="form-grid">
            <div class="field">
                <label class="field-label" for="search">Medication Or Patient</label>
                <input id="search" name="search" type="text" class="field-control" value="{{ request('search') }}" placeholder="Cetirizine or Peanut">
            </div>

            <div class="field">
                <label class="field-label" for="from_date">Issued From</label>
                <input id="from_date" name="from_date" type="date" class="field-control" value="{{ request('from_date') }}">
            </div>

            <div class="field">
                <label class="field-label" for="to_date">Issued To</label>
                <input id="to_date" name="to_date" type="date" class="field-control" value="{{ request('to_date') }}">
            </div>

            <div class="action-row" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('staff.prescriptions') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="surface-header">
            <div>
                <h2>Prescription List</h2>
                <p class="section-copy">Open any prescription to inspect the linked medical record and adherence activity.</p>
            </div>
        </div>

        <div class="table-responsive">
            @forelse ($prescriptions as $prescription)
                @php
                    $record = $prescription->medicalRecord;
                    $pet = $record?->pet;
                    $owner = $pet?->owner;
                    $veterinarian = $record?->consultation?->veterinarian;
                    $adherenceLogs = $prescription->adherenceLogs;
                    $adherenceRate = $adherenceLogs->count() > 0
                        ? round(($adherenceLogs->where('intake_status', 'Taken')->count() / $adherenceLogs->count()) * 100)
                        : null;
                @endphp
                @if ($loop->first)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Patient</th>
                                <th>Owner</th>
                                <th>Veterinarian</th>
                                <th>Dosage</th>
                                <th>Issued</th>
                                <th>Issued Ends</th>
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
                                <td class="col-patient">
                                    <div>{{ $pet?->name ?? 'Unknown Pet' }}</div>
                                    <div class="text-secondary" style="font-size: 0.875rem;">
                                        {{ $pet?->species ? $pet->species : '' }}{{ $pet?->breed ? ' - '.$pet->breed : '' }}
                                    </div>
                                </td>
                                <td class="col-owner">
                                    {{ $owner?->name ?? 'Unknown Owner' }}
                                </td>
                                <td class="col-vet">
                                    {{ $veterinarian?->name ? 'Dr. '.$veterinarian->name : 'Not assigned' }}
                                </td>
                                <td class="col-dosage">
                                    <small>{{ $prescription->dosage }} / {{ $prescription->frequency }} / {{ $prescription->duration }}</small>
                                </td>
                                <td class="col-issued">
                                    {{ $prescription->issued_at?->format('M j, Y') ?? 'Unknown' }}
                                </td>
                                <td class="col-issued-ends">
                                    @php
                                        $endDate = null;
                                        if ($prescription->issued_at && $prescription->duration) {
                                            $durationText = strtolower($prescription->duration);
                                            preg_match('/(\d+)/', $durationText, $matches);
                                            $durationNum = (int)($matches[1] ?? 0);
                                            
                                            if (strpos($durationText, 'week') !== false) {
                                                $durationNum *= 7;
                                            }
                                            
                                            $endDate = $prescription->issued_at->copy()->addDays($durationNum);
                                        }
                                    @endphp
                                    {{ $endDate?->format('M j, Y') ?? 'Unknown' }}
                                </td>
                                <td class="col-adherence">
                                    <span class="pill {{ $adherenceRate !== null && $adherenceRate >= 80 ? 'pill-success' : 'pill-neutral' }}">
                                        {{ $adherenceRate !== null ? $adherenceRate.'%' : 'No logs' }}
                                    </span>
                                </td>
                                <td class="col-logs">
                                    <span class="pill pill-neutral">{{ $adherenceLogs->count() }}</span>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <a href="{{ route('staff.prescriptions.details', $prescription->prescription_id) }}" class="btn btn-sm btn-primary" title="Open Prescription">View</a>
                                        @if ($record)
                                            <a href="{{ route('staff.medical-records.details', $record->record_id) }}" class="btn btn-sm btn-secondary" title="View Medical Record">Record</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                @endif
                @if ($loop->last)
                        </tbody>
                    </table>
                @endif
            @empty
                <div class="empty-state">
                    No prescriptions matched the current filters.
                </div>
            @endforelse
        </div>
    </section>

    @if ($prescriptions->hasPages())
        <section class="card">
            {{ $prescriptions->withQueryString()->links() }}
        </section>
    @endif
@endsection

@section('styles')
    <style>
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
            min-width: 150px;
        }

        .data-table .col-patient {
            min-width: 140px;
        }

        .data-table .col-patient .text-secondary {
            color: var(--text-secondary, #666);
            margin-top: 0.25rem;
        }

        .data-table .col-owner,
        .data-table .col-vet {
            min-width: 130px;
        }

        .data-table .col-dosage {
            min-width: 120px;
            color: var(--text-secondary, #666);
        }

        .data-table .col-issued {
            min-width: 100px;
        }

        .data-table .col-issued-ends {
            min-width: 100px;
        }

        .data-table .col-adherence {
            min-width: 100px;
        }

        .data-table .col-logs {
            text-align: center;
            min-width: 70px;
        }

        .data-table .col-actions {
            text-align: right;
            min-width: 160px;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-block;
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

        @media (max-width: 1024px) {
            .data-table {
                font-size: 0.875rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem 0.625rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn-sm {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .table-responsive {
                border: 1px solid var(--shell-line, #e0e0e0);
                border-radius: var(--shell-radius-lg, 0.5rem);
            }

            .data-table {
                font-size: 0.8125rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.625rem;
            }

            .data-table .col-dosage,
            .data-table .col-issued,
            .data-table .col-issued-ends {
                display: none;
            }

            .action-buttons {
                flex-direction: row;
                gap: 0.25rem;
            }

            .btn-sm {
                width: auto;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
@endsection
